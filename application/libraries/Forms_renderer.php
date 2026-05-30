<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Forms renderer library
 *
 * Prepares dynamic field definitions for public rendering.
 */
class Forms_renderer {

    public function normalize_fields_for_view(array $fields, array $old_values = array()) {
        $normalized = array();

        foreach ($fields as $field) {
            $field_id = isset($field['id']) ? (int) $field['id'] : 0;
            $type = isset($field['field_type']) ? (string) $field['field_type'] : 'text';
            $name = 'field_' . $field_id;

            $options = array();
            if (!empty($field['options_json'])) {
                $decoded = json_decode($field['options_json'], true);
                if (is_array($decoded)) {
                    $options = array_values($decoded);
                }
            }

            $old = array_key_exists($field_id, $old_values) ? $old_values[$field_id] : '';
            if ($type === 'checkbox') {
                $old = is_array($old) ? $old : array();
            }

            $html_type = 'text';
            if (in_array($type, array('email', 'date', 'number', 'file'), true)) {
                $html_type = $type;
            }

            $normalized[] = array(
                'id'         => $field_id,
                'name'       => $name,
                'type'       => $type,
                'label'      => isset($field['label']) ? $field['label'] : '',
                'required'   => !empty($field['is_required']),
                'options'    => $options,
                'old_value'  => $old,
                'html_type'  => $html_type,
            );
        }

        return $normalized;
    }
}
