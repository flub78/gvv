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
                $label_map[$for] = $this->_clean_label($label_node->textContent);
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

            $label = $this->_resolve_label($node, $xpath, $label_map, $name);

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

            $explicit_label = trim($node->getAttribute('data-gvv-label'));
            $label = $explicit_label !== '' ? $this->_clean_label($explicit_label) : trim($node->textContent);
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

            $explicit_label = trim($node->getAttribute('data-gvv-label'));
            $label = $explicit_label !== '' ? $this->_clean_label($explicit_label) : trim($node->textContent);
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

    /**
     * Resolve a field's display label, in priority order:
     *   (a) explicit  data-gvv-label="..."  attribute on the field
     *   (b) <label for="{id}">  matching the field's id  (historical behaviour)
     *   (c) a <label> without @for that wraps the field
     *   (d) a <label> without @for that is the field's immediately preceding sibling
     *   (e) fallback: the field's name attribute
     *
     * Non-<label> elements (span, td, ...) are never used as a label source —
     * a form that needs a specific header for such a field must carry
     * data-gvv-label. See doc/design_notes/remplissage_formulaires_design.md.
     */
    private function _resolve_label(DOMElement $node, DOMXPath $xpath, array $label_map, $name) {
        $explicit = trim($node->getAttribute('data-gvv-label'));
        if ($explicit !== '') {
            return $this->_clean_label($explicit);
        }

        $id = trim($node->getAttribute('id'));
        if ($id !== '' && isset($label_map[$id]) && $label_map[$id] !== '') {
            return $label_map[$id];
        }

        $ancestor = $xpath->query('ancestor::label[not(@for)][1]', $node);
        if ($ancestor->length && trim($ancestor->item(0)->textContent) !== '') {
            return $this->_clean_label($ancestor->item(0)->textContent);
        }

        $prev_sibling = $xpath->query('preceding-sibling::*[1][self::label][not(@for)]', $node);
        if ($prev_sibling->length && trim($prev_sibling->item(0)->textContent) !== '') {
            return $this->_clean_label($prev_sibling->item(0)->textContent);
        }

        return $name;
    }

    /**
     * Normalise a raw label string: collapse whitespace and drop the lone
     * "required" asterisk (which is usually a separate <span> inside the label).
     */
    private function _clean_label($text) {
        $text = preg_replace('/\s*\*\s*/', ' ', (string) $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
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
