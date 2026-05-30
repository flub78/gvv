<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Form pages model
 *
 * Manages ordered HTML pages attached to a form.
 */
class Form_pages_model extends CI_Model {

    public $table = 'form_pages';

    public function __construct() {
        parent::__construct();
    }

    public function create_page(array $data) {
        if (empty($data['form_id']) || !isset($data['page_number'])) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $row = array(
            'form_id'      => (int) $data['form_id'],
            'page_number'  => (int) $data['page_number'],
            'title'        => isset($data['title']) ? $data['title'] : null,
            'content_html' => isset($data['content_html']) ? $data['content_html'] : null,
            'created_at'   => $now,
            'updated_at'   => $now,
            'created_by'   => isset($data['created_by']) ? $data['created_by'] : null,
            'updated_by'   => isset($data['created_by']) ? $data['created_by'] : null,
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

    public function get_form_pages($form_id) {
        return $this->db
            ->where('form_id', (int) $form_id)
            ->order_by('page_number', 'ASC')
            ->get($this->table)
            ->result_array();
    }

    public function next_page_number($form_id) {
        $row = $this->db
            ->select_max('page_number', 'max_page')
            ->where('form_id', (int) $form_id)
            ->get($this->table)
            ->row_array();

        $max = isset($row['max_page']) ? (int) $row['max_page'] : 0;
        return $max + 1;
    }

    public function update_page($id, array $data) {
        $current = $this->get_by_id($id);
        if (!$current) {
            return false;
        }

        $update = array(
            'title'        => array_key_exists('title', $data) ? $data['title'] : $current['title'],
            'content_html' => array_key_exists('content_html', $data) ? $data['content_html'] : $current['content_html'],
            'updated_at'   => date('Y-m-d H:i:s'),
            'updated_by'   => isset($data['updated_by']) ? $data['updated_by'] : null,
        );

        if (isset($data['page_number'])) {
            $update['page_number'] = (int) $data['page_number'];
        }

        $this->db->where('id', (int) $id)->update($this->table, $update);
        return $this->db->affected_rows() >= 0;
    }

    public function delete_page($id) {
        $this->db->where('id', (int) $id)->delete($this->table);
        return $this->db->affected_rows() > 0;
    }
}