<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Forms validation library
 *
 * Centralizes server-side validation rules for dynamic form fields.
 */
class Forms_validation {

    public function validate_fields(array $fields, array $submitted_values) {
        $errors = array();

        foreach ($fields as $field) {
            $field_id = isset($field['id']) ? (int) $field['id'] : 0;
            $value = array_key_exists($field_id, $submitted_values) ? $submitted_values[$field_id] : null;
            $error = $this->validate_field_value($field, $value);
            if ($error !== null) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    public function validate_field_value($field, $value) {
        $field_label = isset($field['label']) ? $field['label'] : 'Champ';
        $field_type = isset($field['field_type']) ? $field['field_type'] : 'text';
        $is_required = !empty($field['is_required']);

        $normalized_value = $this->normalize_value($value);
        $is_empty = $this->is_empty_value($normalized_value);

        if ($is_required && $is_empty) {
            return 'Le champ "' . html_escape($field_label) . '" est obligatoire.';
        }

        if ($is_empty) {
            return null;
        }

        if ($field_type === 'email' && !$this->is_valid_email($normalized_value)) {
            return 'Le champ "' . html_escape($field_label) . '" doit contenir un email valide.';
        }

        if ($field_type === 'number' && !$this->is_valid_number($normalized_value)) {
            return 'Le champ "' . html_escape($field_label) . '" doit contenir un nombre valide.';
        }

        if ($field_type === 'date' && !$this->is_valid_date($normalized_value)) {
            return 'Le champ "' . html_escape($field_label) . '" doit contenir une date valide.';
        }

        $rule_error = $this->validate_additional_rules($field, $normalized_value);
        if ($rule_error !== null) {
            return str_replace('{label}', html_escape($field_label), $rule_error);
        }

        return null;
    }

    private function validate_additional_rules($field, $value) {
        $rules = isset($field['validation_rules']) ? trim((string) $field['validation_rules']) : '';
        if ($rules === '') {
            return null;
        }

        $parts = explode('|', $rules);
        foreach ($parts as $rule) {
            $rule = trim($rule);
            if ($rule === '' || $rule === 'required') {
                continue;
            }

            if (preg_match('/^max_length\[(\d+)\]$/', $rule, $matches)) {
                $limit = (int) $matches[1];
                if (mb_strlen((string) $value) > $limit) {
                    return 'Le champ "{label}" depasse la longueur maximale autorisee.';
                }
                continue;
            }

            if (preg_match('/^min_length\[(\d+)\]$/', $rule, $matches)) {
                $limit = (int) $matches[1];
                if (mb_strlen((string) $value) < $limit) {
                    return 'Le champ "{label}" ne respecte pas la longueur minimale attendue.';
                }
                continue;
            }

            if ($rule === 'valid_email' && !$this->is_valid_email($value)) {
                return 'Le champ "{label}" doit contenir un email valide.';
            }

            if ($rule === 'numeric' && !$this->is_valid_number($value)) {
                return 'Le champ "{label}" doit contenir une valeur numerique.';
            }
        }

        return null;
    }

    private function normalize_value($value) {
        if (is_array($value)) {
            return array_values($value);
        }
        return is_string($value) ? trim($value) : $value;
    }

    private function is_empty_value($value) {
        if (is_array($value)) {
            return count($value) === 0;
        }
        return trim((string) $value) === '';
    }

    private function is_valid_email($value) {
        return filter_var((string) $value, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function is_valid_number($value) {
        return is_numeric($value);
    }

    private function is_valid_date($value) {
        if (!is_string($value) || trim($value) === '') {
            return false;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return false;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $parts = explode('-', $value);
            return checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]);
        }

        return true;
    }
}
