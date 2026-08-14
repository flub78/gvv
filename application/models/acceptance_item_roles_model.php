<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Model for acceptance_item_roles table
 *
 * Role x section targeting for acceptance_items: one row per (item, role,
 * section) combination, section_id NULL meaning "all sections" for that
 * role. Mirrors the email_list_roles / _criteria_tab.php grid selector.
 *
 * @package models
 * @see application/migrations/170_acceptance_item_roles.php
 * @see application/models/email_lists_model.php (same role x section pattern)
 */
class Acceptance_item_roles_model extends Common_Model {
    public $table = 'acceptance_item_roles';
    protected $primary_key = 'id';

    function __construct() {
        parent::__construct();
    }

    /**
     * Get all role targets for an item, with role/section names
     * @param int $item_id
     * @return array
     */
    public function get_for_item($item_id) {
        $this->db->select('air.*, tr.nom as role_name, s.nom as section_name');
        $this->db->from($this->table . ' air');
        $this->db->join('types_roles tr', 'air.types_roles_id = tr.id', 'left');
        $this->db->join('sections s', 'air.section_id = s.id', 'left');
        $this->db->where('air.item_id', $item_id);
        $query = $this->db->get();
        return $this->get_to_array($query);
    }

    /**
     * Checkbox values pre-checked in the role x section grid, format
     * "role_id_section_id" ("role_id_0" for "all sections"), matching the
     * value convention used by email_lists/_criteria_tab.php.
     * @param int $item_id
     * @return array Map keyed by "role_id_section_id" => true
     */
    public function get_checked_map($item_id) {
        $rows = $this->get_for_item($item_id);
        $map = array();
        foreach ($rows as $row) {
            $key = $row['types_roles_id'] . '_' . ($row['section_id'] !== null ? $row['section_id'] : '0');
            $map[$key] = true;
        }
        return $map;
    }

    /**
     * Replace all role targets for an item (delete + re-insert). The admin
     * form posts the full set of checked roles on every submit (no
     * incremental add/remove like the email lists AJAX UI), so a full
     * replace keeps this in sync without diffing.
     * @param int $item_id
     * @param array $role_values Array of "role_id_section_id" strings (posted checkbox values)
     * @param string $user Username performing the change
     * @return bool
     */
    public function replace_for_item($item_id, $role_values, $user) {
        $this->db->where('item_id', $item_id);
        $this->db->delete($this->table);

        if (empty($role_values)) {
            return TRUE;
        }

        $now = date('Y-m-d H:i:s');
        foreach ($role_values as $value) {
            $parts = explode('_', $value);
            if (count($parts) != 2) {
                continue;
            }
            $role_id = (int) $parts[0];
            $section_id = (int) $parts[1];
            if ($role_id <= 0) {
                continue;
            }

            $this->db->insert($this->table, array(
                'item_id' => $item_id,
                'types_roles_id' => $role_id,
                'section_id' => $section_id === 0 ? null : $section_id,
                'created_at' => $now,
                'created_by' => $user,
                'updated_by' => $user
            ));
        }

        return TRUE;
    }
}
