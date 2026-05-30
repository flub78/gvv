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
        if (empty($data['code']) || empty($data['title'])) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $club = null;
        if (array_key_exists('club', $data) && $data['club'] !== '' && $data['club'] !== null) {
            $club = (int) $data['club'];
        }

        $row = array(
            'club'        => $club,
            'code'        => $data['code'],
            'title'       => $data['title'],
            'description' => isset($data['description']) ? $data['description'] : null,
            'status'      => isset($data['status']) ? $data['status'] : 'draft',
            'public_slug' => isset($data['public_slug']) && $data['public_slug'] !== ''
                ? $this->ensure_unique_slug($data['public_slug'])
                : $this->ensure_unique_slug($data['code']),
            'css_scope'   => isset($data['css_scope']) ? $data['css_scope'] : null,
            'global_css'  => isset($data['global_css']) ? $data['global_css'] : null,
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
        $this->db->select('forms.*, sections.nom as section_name');
        $this->db->from($this->table);
        $this->db->join('sections', 'forms.club = sections.id', 'left');

        if (isset($filters['section_context'])) {
            $section_context = (int) $filters['section_context'];
            if ($section_context > 0) {
                $this->db->where('(forms.club = ' . $section_context . ' OR forms.club IS NULL)', null, false);
            }
        }

        if (!empty($filters['club'])) {
            $this->db->where('forms.club', (int) $filters['club']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('forms.status', $filters['status']);
        }
        if (!empty($filters['code'])) {
            $this->db->where('forms.code', $filters['code']);
        }

        $this->db->order_by('forms.updated_at', 'DESC');
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
            'global_css'  => array_key_exists('global_css', $data) ? $data['global_css'] : (isset($current['global_css']) ? $current['global_css'] : null),
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

    public function delete_form($id) {
        $this->db->where('id', (int) $id)->delete($this->table);
        return $this->db->affected_rows() > 0;
    }

    public function duplicate_form($id, $created_by = null) {
        $source = $this->get_by_id($id);
        if (!$source) {
            return false;
        }

        $new_code = $this->ensure_unique_code($source['code']);
        $new_slug = $this->ensure_unique_slug($source['public_slug']);
        $new_title = $source['title'] . ' (copie)';

        $this->db->trans_start();

        $new_form_id = $this->create_form(array(
            'club'        => array_key_exists('club', $source) ? $source['club'] : null,
            'code'        => $new_code,
            'title'       => $new_title,
            'description' => $source['description'],
            'status'      => 'draft',
            'public_slug' => $new_slug,
            'css_scope'   => $source['css_scope'],
            'global_css'  => isset($source['global_css']) ? $source['global_css'] : null,
            'created_by'  => $created_by,
        ));

        if (!$new_form_id) {
            $this->db->trans_complete();
            return false;
        }

        $page_map = array();
        $source_pages = $this->db
            ->where('form_id', (int) $id)
            ->order_by('page_number', 'ASC')
            ->get('form_pages')
            ->result_array();

        $now = date('Y-m-d H:i:s');
        foreach ($source_pages as $page) {
            $row = array(
                'form_id'      => (int) $new_form_id,
                'page_number'  => (int) $page['page_number'],
                'title'        => $page['title'],
                'content_html' => $page['content_html'],
                'created_at'   => $now,
                'updated_at'   => $now,
                'created_by'   => $created_by,
                'updated_by'   => $created_by,
            );
            $this->db->insert('form_pages', $row);
            $page_map[(int) $page['id']] = (int) $this->db->insert_id();
        }

        $source_fields = $this->db
            ->where('form_id', (int) $id)
            ->order_by('page_id', 'ASC')
            ->order_by('sort_order', 'ASC')
            ->order_by('id', 'ASC')
            ->get('form_fields')
            ->result_array();

        foreach ($source_fields as $field) {
            $old_page_id = (int) $field['page_id'];
            if (!isset($page_map[$old_page_id])) {
                continue;
            }

            $row = array(
                'form_id'          => (int) $new_form_id,
                'page_id'          => (int) $page_map[$old_page_id],
                'name'             => $field['name'],
                'label'            => $field['label'],
                'field_type'       => $field['field_type'],
                'is_required'      => (int) $field['is_required'],
                'sort_order'       => (int) $field['sort_order'],
                'options_json'     => $field['options_json'],
                'validation_rules' => $field['validation_rules'],
                'created_at'       => $now,
                'updated_at'       => $now,
                'created_by'       => $created_by,
                'updated_by'       => $created_by,
            );
            $this->db->insert('form_fields', $row);
        }

        $this->db->trans_complete();
        if (!$this->db->trans_status()) {
            return false;
        }

        return $new_form_id;
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

    private function ensure_unique_code($raw_code, $exclude_id = 0) {
        $code = strtolower(trim($raw_code));
        $code = preg_replace('/[^a-z0-9_-]+/', '-', $code);
        $code = trim($code, '-_');

        if ($code === '') {
            $code = 'form-copy';
        }

        $base = $code . '-copy';
        if (strlen($base) > 50) {
            $base = substr($base, 0, 50);
            $base = rtrim($base, '-_');
        }
        if ($base === '') {
            $base = 'form-copy';
        }

        $candidate = $base;
        $suffix = 2;

        while ($this->code_exists($candidate, $exclude_id)) {
            $suffix_text = '-' . $suffix;
            $head = $base;
            if (strlen($head . $suffix_text) > 50) {
                $head = substr($head, 0, 50 - strlen($suffix_text));
                $head = rtrim($head, '-_');
            }
            $candidate = $head . $suffix_text;
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

    private function code_exists($code, $exclude_id = 0) {
        $this->db->from($this->table);
        $this->db->where('code', $code);
        if ($exclude_id) {
            $this->db->where('id !=', (int) $exclude_id);
        }

        return $this->db->count_all_results() > 0;
    }
}