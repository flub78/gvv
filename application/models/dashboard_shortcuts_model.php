<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Dashboard shortcuts model
 *
 * Manages navigation shortcuts (cards) injected into welcome.php dashboard
 * sections. Scope: NULL club_id = all sections; integer club_id = section-specific.
 * A shortcut with role_required is only shown to users holding that role
 * (checked via the has_role() helper, session-scoped to the active section).
 */
class Dashboard_shortcuts_model extends CI_Model {

    public $table = 'dashboard_shortcuts';

    public function __construct() {
        parent::__construct();
        // has_role() (used by get_for_dashboard) lives in the 'views' helper;
        // autoloaded in normal requests, but not in the PHPUnit bootstrap.
        $this->load->helper('views');
    }

    /**
     * Shortcuts to render for a given dashboard section, already filtered
     * by active status, club scope, and role, ordered for display.
     *
     * @param  string   $dashboard welcome.php section name (user, flights, ...)
     * @param  int|null $club_id   active section id, or null for global-only
     * @return array
     */
    public function get_for_dashboard($dashboard, $club_id = null) {
        $this->db
            ->where('dashboard', $dashboard)
            ->where('active', 1);

        if ($club_id) {
            $this->db->where('(club_id IS NULL OR club_id = ' . (int) $club_id . ')', null, false);
        } else {
            $this->db->where('club_id IS NULL', null, false);
        }

        $rows = $this->db
            ->order_by('section', 'ASC')
            ->order_by('sort_order', 'ASC')
            ->get($this->table)
            ->result_array();

        return array_values(array_filter($rows, function ($row) {
            return empty($row['role_required']) || has_role($row['role_required']);
        }));
    }

    /**
     * List all shortcuts for admin, optionally filtered by club_id (NULL = global only).
     */
    public function list_shortcuts($club_id = null, $global_too = true) {
        if ($club_id !== null && $global_too) {
            $this->db->where(
                '(club_id = ' . (int) $club_id . ' OR club_id IS NULL)',
                null, false
            );
        } elseif ($club_id !== null) {
            $this->db->where('club_id', (int) $club_id);
        } else {
            $this->db->where('club_id IS NULL', null, false);
        }

        return $this->db
            ->order_by('dashboard', 'ASC')
            ->order_by('sort_order', 'ASC')
            ->get($this->table)
            ->result_array();
    }

    public function get_by_id($id) {
        $row = $this->db
            ->where('id', (int) $id)
            ->get($this->table)
            ->row_array();
        return $row ?: false;
    }

    public function create(array $data, $by = null) {
        $now = date('Y-m-d H:i:s');
        $row = $this->_normalize($data);
        $row['created_at'] = $now;
        $row['updated_at'] = $now;
        $row['created_by'] = $by;
        $row['updated_by'] = $by;

        $this->db->insert($this->table, $row);
        return $this->db->insert_id() ?: false;
    }

    public function update($id, array $data, $by = null) {
        $row = $this->_normalize($data);
        $row['updated_at'] = date('Y-m-d H:i:s');
        $row['updated_by'] = $by;

        $this->db->where('id', (int) $id)->update($this->table, $row);
        return $this->db->affected_rows() >= 0;
    }

    public function delete($id) {
        $this->db->where('id', (int) $id)->delete($this->table);
        return $this->db->affected_rows() > 0;
    }

    public function toggle_active($id) {
        $row = $this->get_by_id($id);
        if (!$row) {
            return false;
        }
        $this->db->where('id', (int) $id)->update($this->table, array(
            'active'     => $row['active'] ? 0 : 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ));
        return $this->db->affected_rows() >= 0;
    }

    private function _normalize(array $data) {
        $club_id = (isset($data['club_id']) && $data['club_id'] !== '' && $data['club_id'] !== null)
            ? (int) $data['club_id'] : null;

        return array(
            'dashboard'       => trim($data['dashboard']),
            'section'         => isset($data['section']) && $data['section'] !== '' ? trim($data['section']) : null,
            'title_key'       => isset($data['title_key']) && $data['title_key'] !== '' ? trim($data['title_key']) : null,
            'title'           => trim($data['title']),
            'description_key' => isset($data['description_key']) && $data['description_key'] !== '' ? trim($data['description_key']) : null,
            'description'     => isset($data['description']) && $data['description'] !== '' ? trim($data['description']) : null,
            'url'             => trim($data['url']),
            'icon'            => isset($data['icon']) && $data['icon'] !== '' ? trim($data['icon']) : null,
            'color'           => isset($data['color']) && $data['color'] !== '' ? trim($data['color']) : null,
            'role_required'   => isset($data['role_required']) && $data['role_required'] !== '' ? trim($data['role_required']) : null,
            'sort_order'      => isset($data['sort_order']) && $data['sort_order'] !== '' ? (int) $data['sort_order'] : 0,
            'active'          => isset($data['active']) ? (int) (bool) $data['active'] : 1,
            'club_id'         => $club_id,
        );
    }
}
