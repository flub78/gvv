<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Form fields model
 *
 * Manages field definitions attached to a form page.
 */
class Form_fields_model extends CI_Model {

    public $table = 'form_fields';

    private $allowed_field_types = array(
        'text', 'email', 'date', 'number', 'textarea',
        'select', 'radio', 'checkbox', 'file', 'signature'
    );

    public function __construct() {
        parent::__construct();
    }

    public function create_field(array $data) {
        if (empty($data['form_id']) || empty($data['page_id']) || empty($data['name']) || empty($data['label'])) {
            return false;
        }

        $field_type = isset($data['field_type']) ? $data['field_type'] : 'text';
        if (!$this->is_allowed_field_type($field_type)) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $row = array(
            'form_id'          => (int) $data['form_id'],
            'page_id'          => (int) $data['page_id'],
            'name'             => trim($data['name']),
            'label'            => trim($data['label']),
            'field_type'       => $field_type,
            'is_required'      => !empty($data['is_required']) ? 1 : 0,
            'sort_order'       => isset($data['sort_order']) ? (int) $data['sort_order'] : $this->next_sort_order((int) $data['page_id']),
            'options_json'     => $this->normalize_options_json(isset($data['options_json']) ? $data['options_json'] : null),
            'validation_rules' => isset($data['validation_rules']) ? $data['validation_rules'] : null,
            'created_at'       => $now,
            'updated_at'       => $now,
            'created_by'       => isset($data['created_by']) ? $data['created_by'] : null,
            'updated_by'       => isset($data['created_by']) ? $data['created_by'] : null,
        );

        $this->db->insert($this->table, $row);
        $id = $this->db->insert_id();
        return $id ? $id : false;
    }

    public function get_by_id($id) {
        $row = $this->db
            ->where('id', (int) $id)
            ->get($this->table)
            ->row_array();

        return $row ?: false;
    }

    public function get_page_fields($page_id) {
        return $this->db
            ->where('page_id', (int) $page_id)
            ->order_by('sort_order', 'ASC')
            ->order_by('id', 'ASC')
            ->get($this->table)
            ->result_array();
    }

    public function get_form_fields($form_id) {
        return $this->db
            ->where('form_id', (int) $form_id)
            ->order_by('page_id', 'ASC')
            ->order_by('sort_order', 'ASC')
            ->order_by('id', 'ASC')
            ->get($this->table)
            ->result_array();
    }

    public function update_field($id, array $data) {
        $current = $this->get_by_id($id);
        if (!$current) {
            return false;
        }

        if (isset($data['field_type']) && !$this->is_allowed_field_type($data['field_type'])) {
            return false;
        }

        $update = array(
            'name'             => isset($data['name']) ? trim($data['name']) : $current['name'],
            'label'            => isset($data['label']) ? trim($data['label']) : $current['label'],
            'field_type'       => isset($data['field_type']) ? $data['field_type'] : $current['field_type'],
            'is_required'      => array_key_exists('is_required', $data) ? (!empty($data['is_required']) ? 1 : 0) : $current['is_required'],
            'sort_order'       => isset($data['sort_order']) ? (int) $data['sort_order'] : $current['sort_order'],
            'options_json'     => array_key_exists('options_json', $data) ? $this->normalize_options_json($data['options_json']) : $current['options_json'],
            'validation_rules' => array_key_exists('validation_rules', $data) ? $data['validation_rules'] : $current['validation_rules'],
            'updated_at'       => date('Y-m-d H:i:s'),
            'updated_by'       => isset($data['updated_by']) ? $data['updated_by'] : null,
        );

        if (isset($data['page_id'])) {
            $update['page_id'] = (int) $data['page_id'];
        }
        if (isset($data['form_id'])) {
            $update['form_id'] = (int) $data['form_id'];
        }

        $this->db->where('id', (int) $id)->update($this->table, $update);
        return $this->db->affected_rows() >= 0;
    }

    public function delete_field($id) {
        $this->db->where('id', (int) $id)->delete($this->table);
        return $this->db->affected_rows() > 0;
    }

    public function reorder_page_fields($page_id, array $ordered_ids, $updated_by = null) {
        $page_id = (int) $page_id;
        $sort = 1;
        $now = date('Y-m-d H:i:s');

        foreach ($ordered_ids as $id) {
            $this->db
                ->where('id', (int) $id)
                ->where('page_id', $page_id)
                ->update($this->table, array(
                    'sort_order' => $sort,
                    'updated_at' => $now,
                    'updated_by' => $updated_by,
                ));
            $sort += 1;
        }

        return true;
    }

    private function next_sort_order($page_id) {
        $row = $this->db
            ->select_max('sort_order', 'max_sort')
            ->where('page_id', (int) $page_id)
            ->get($this->table)
            ->row_array();

        $max = isset($row['max_sort']) ? (int) $row['max_sort'] : 0;
        return $max + 1;
    }

    private function is_allowed_field_type($field_type) {
        return in_array($field_type, $this->allowed_field_types, true);
    }

    private function normalize_options_json($options_json) {
        if (is_array($options_json)) {
            return json_encode($options_json);
        }
        if ($options_json === '') {
            return null;
        }
        return $options_json;
    }
}