<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Forms model
 *
 * First standalone CRUD layer for the forms module.
 */
class Forms_model extends CI_Model {

    public $table = 'forms';

    public function __construct() {
        parent::__construct();
    }

    public function create_form(array $data) {
        if (empty($data['club']) || empty($data['code']) || empty($data['title'])) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $row = array(
            'club'        => (int) $data['club'],
            'code'        => $data['code'],
            'title'       => $data['title'],
            'description' => isset($data['description']) ? $data['description'] : null,
            'status'      => isset($data['status']) ? $data['status'] : 'draft',
            'public_slug' => isset($data['public_slug']) && $data['public_slug'] !== ''
                ? $this->ensure_unique_slug($data['public_slug'])
                : $this->ensure_unique_slug($data['code']),
            'css_scope'   => isset($data['css_scope']) ? $data['css_scope'] : null,
            'created_at'  => $now,
            'updated_at'  => $now,
            'created_by'  => isset($data['created_by']) ? $data['created_by'] : null,
            'updated_by'  => isset($data['created_by']) ? $data['created_by'] : null,
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

    public function get_by_public_slug($public_slug) {
        $row = $this->db
            ->where('public_slug', $public_slug)
            ->get($this->table)
            ->row_array();

        return $row ?: false;
    }

    public function list_forms(array $filters = array()) {
        $this->db->from($this->table);

        if (!empty($filters['club'])) {
            $this->db->where('club', (int) $filters['club']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('status', $filters['status']);
        }
        if (!empty($filters['code'])) {
            $this->db->where('code', $filters['code']);
        }

        $this->db->order_by('updated_at', 'DESC');
        return $this->db->get()->result_array();
    }

    public function update_form($id, array $data) {
        $current = $this->get_by_id($id);
        if (!$current) {
            return false;
        }

        $update = array(
            'title'       => isset($data['title']) ? $data['title'] : $current['title'],
            'description' => array_key_exists('description', $data) ? $data['description'] : $current['description'],
            'status'      => isset($data['status']) ? $data['status'] : $current['status'],
            'css_scope'   => array_key_exists('css_scope', $data) ? $data['css_scope'] : $current['css_scope'],
            'updated_at'  => date('Y-m-d H:i:s'),
            'updated_by'  => isset($data['updated_by']) ? $data['updated_by'] : null,
        );

        if (!empty($data['public_slug']) && $data['public_slug'] !== $current['public_slug']) {
            $update['public_slug'] = $this->ensure_unique_slug($data['public_slug'], (int) $id);
        }

        $this->db->where('id', (int) $id)->update($this->table, $update);
        return $this->db->affected_rows() >= 0;
    }

    public function publish_form($id, $updated_by = null) {
        return $this->update_form($id, array(
            'status'     => 'published',
            'updated_by' => $updated_by,
        ));
    }

    public function archive_form($id, $updated_by = null) {
        return $this->update_form($id, array(
            'status'     => 'archived',
            'updated_by' => $updated_by,
        ));
    }

    private function ensure_unique_slug($raw_slug, $exclude_id = 0) {
        $slug = strtolower(trim($raw_slug));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        if ($slug === '') {
            $slug = 'form-' . date('YmdHis');
        }

        $candidate = $slug;
        $suffix = 2;

        while ($this->slug_exists($candidate, $exclude_id)) {
            $candidate = $slug . '-' . $suffix;
            $suffix += 1;
        }

        return $candidate;
    }

    private function slug_exists($public_slug, $exclude_id = 0) {
        $this->db->from($this->table);
        $this->db->where('public_slug', $public_slug);
        if ($exclude_id) {
            $this->db->where('id !=', (int) $exclude_id);
        }

        return $this->db->count_all_results() > 0;
    }
}