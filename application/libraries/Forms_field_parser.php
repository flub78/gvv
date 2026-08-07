<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Forms field parser library
 *
 * Parses a page's HTML content into field descriptors on demand. Replaces
 * the form_fields DB table (removed by migration 166): field structure is
 * no longer persisted, it is derived from the HTML file every time it is
 * needed (admin display, submission validation, notification mapping).
 *
 * Field descriptors have no numeric id — `name` (the HTML name/
 * data-gvv-name attribute) is the sole, stable key, matching
 * form_submission_values.field_name / form_submission_files.widget_name.
 *
 * @see doc/prds/remplissage_formulaires_prd.md (EF2-bis)
 */
class Forms_field_parser {

    /**
     * @return array list of field descriptors: name, label, field_type,
     *   is_required, is_identifier, sort_order, options (array),
     *   validation_rules, gvv_role
     */
    public function parse_fields($html) {
        $html = html_entity_decode((string) $html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (trim($html) === '') {
            return array();
        }

        libxml_use_internal_errors(true);
        $dom = new DOMDocument('1.0', 'UTF-8');
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        $label_map = array();
        foreach ($xpath->query('//label[@for]') as $label_node) {
            $for = trim($label_node->getAttribute('for'));
            if ($for !== '') {
                $label_map[$for] = trim(preg_replace('/\s*\*\s*/', '', $label_node->textContent));
            }
        }

        $fields = array();
        $seen = array();
        $sort = 1;
        $skip_types = array('hidden', 'submit', 'reset', 'button', 'image');

        foreach ($xpath->query('//input[@name] | //select[@name] | //textarea[@name]') as $node) {
            $tag  = strtolower($node->tagName);
            $name = trim($node->getAttribute('name'));
            if ($name === '') {
                continue;
            }
            if ($tag === 'input' && in_array(strtolower($node->getAttribute('type') ?: 'text'), $skip_types, true)) {
                continue;
            }
            if (isset($seen[$name])) {
                continue;
            }
            $seen[$name] = true;

            if ($tag === 'textarea') {
                $field_type = 'textarea';
            } elseif ($tag === 'select') {
                $field_type = 'select';
            } else {
                $type_map = array('email' => 'email', 'date' => 'date', 'number' => 'number',
                                  'checkbox' => 'checkbox', 'radio' => 'radio', 'file' => 'file');
                $raw_type = strtolower($node->getAttribute('type') ?: 'text');
                $field_type = isset($type_map[$raw_type]) ? $type_map[$raw_type] : 'text';
            }

            $id    = trim($node->getAttribute('id'));
            $label = ($id !== '' && isset($label_map[$id])) ? $label_map[$id] : '';
            if ($label === '') {
                $label = $name;
            }

            $options = array();
            if ($tag === 'select') {
                foreach ($xpath->query('.//option', $node) as $opt) {
                    if ($opt->getAttribute('value') !== '') {
                        $options[] = trim($opt->textContent);
                    }
                }
            }

            $gvv_role = trim($node->getAttribute('data-gvv-role'));

            $fields[] = $this->_descriptor(
                $name, $label, $field_type,
                $node->hasAttribute('required'),
                $sort++, $options, $node, $gvv_role
            );
        }

        // Signature widgets: <div data-gvv-type="signature" data-gvv-name="...">
        foreach ($xpath->query('//*[@data-gvv-type and @data-gvv-name]') as $node) {
            if (strtolower($node->getAttribute('data-gvv-type')) !== 'signature') {
                continue;
            }
            $name = trim($node->getAttribute('data-gvv-name'));
            if ($name === '' || isset($seen[$name])) {
                continue;
            }
            $seen[$name] = true;

            $label = trim($node->textContent);
            if ($label === '') {
                $label = $name;
            }

            $fields[] = $this->_descriptor(
                $name, $label, 'signature',
                $node->hasAttribute('data-gvv-required'),
                $sort++, array(), $node, null
            );
        }

        // Sub-form widgets: <div data-gvv-type="subform" data-gvv-name="..." data-gvv-form-slug="...">
        foreach ($xpath->query('//*[@data-gvv-type and @data-gvv-name]') as $node) {
            if (strtolower($node->getAttribute('data-gvv-type')) !== 'subform') {
                continue;
            }
            $name = trim($node->getAttribute('data-gvv-name'));
            if ($name === '' || isset($seen[$name])) {
                continue;
            }
            $seen[$name] = true;

            $label = trim($node->textContent);
            if ($label === '') {
                $label = $name;
            }

            $fields[] = $this->_descriptor(
                $name, $label, 'subform',
                $node->hasAttribute('data-gvv-required'),
                $sort++, array(), $node, null
            );
        }

        return $fields;
    }

    private function _descriptor($name, $label, $field_type, $is_required, $sort_order, array $options, DOMElement $node, $gvv_role) {
        $validation_rules = trim($node->getAttribute('data-gvv-validation'));
        $is_identifier = $node->hasAttribute('data-gvv-identifier')
            && strtolower($node->getAttribute('data-gvv-identifier')) !== 'false';

        return array(
            'name'             => $name,
            'label'            => $label,
            'field_type'       => $field_type,
            'is_required'      => $is_required ? 1 : 0,
            'is_identifier'    => $is_identifier ? 1 : 0,
            'sort_order'       => $sort_order,
            'options'          => $options,
            'options_json'     => !empty($options) ? json_encode($options) : null,
            'validation_rules' => $validation_rules !== '' ? $validation_rules : null,
            'gvv_role'         => ($gvv_role !== null && $gvv_role !== '') ? $gvv_role : null,
        );
    }

    /**
     * Parses every page of a form (given already file-sourced page contents,
     * i.e. `content_html` already overlaid from disk) into one flat, ordered
     * list of field descriptors, each also carrying `page_number`.
     *
     * @param array $pages list of page rows (must include page_number, content_html)
     */
    public function parse_form_pages(array $pages) {
        $all = array();
        foreach ($pages as $page) {
            $page_number = (int) $page['page_number'];
            foreach ($this->parse_fields((string) $page['content_html']) as $field) {
                $field['page_number'] = $page_number;
                $all[] = $field;
            }
        }
        return $all;
    }

    /**
     * @return array field name => descriptor
     */
    public function fields_by_name(array $fields) {
        $by_name = array();
        foreach ($fields as $field) {
            $by_name[$field['name']] = $field;
        }
        return $by_name;
    }
}
