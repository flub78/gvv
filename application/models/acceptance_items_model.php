<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Model for acceptance_items table
 *
 * Manages elements to be accepted (documents, training, checks, briefings, authorizations).
 *
 * @package models
 * @see application/migrations/068_acceptance_system.php
 */
class Acceptance_items_model extends Common_Model {
    public $table = 'acceptance_items';
    protected $primary_key = 'id';

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();
    }

    /**
     * Returns paginated list for display
     * @param int $per_page Number of items per page
     * @param int $premier Offset
     * @param array $selection Filter criteria
     * @return array
     */
    public function select_page($per_page = 0, $premier = 0, $selection = []) {
        $this->db->select('acceptance_items.*,
            membres.mnom as created_by_nom, membres.mprenom as created_by_prenom');
        $this->db->from($this->table);
        $this->db->join('membres', 'acceptance_items.created_by = membres.mlogin', 'left');

        if (!empty($selection)) {
            $this->db->where($selection);
        }

        $this->db->order_by('acceptance_items.created_at', 'desc');

        $query = $this->db->get();
        $select = $this->get_to_array($query);

        // Add computed display name for creator
        foreach ($select as &$row) {
            $row['created_by_name'] = trim(
                (isset($row['created_by_prenom']) ? $row['created_by_prenom'] : '') . ' ' .
                (isset($row['created_by_nom']) ? $row['created_by_nom'] : '')
            );
        }

        $this->gvvmetadata->store_table("vue_acceptance_items", $select);
        return $select;
    }

    /**
     * Get active items, optionally filtered by category
     * @param string|null $category Category filter
     * @return array
     */
    public function get_active_items($category = null) {
        $this->db->where('active', 1);
        if ($category !== null) {
            $this->db->where('category', $category);
        }
        $this->db->order_by('title', 'asc');
        $query = $this->db->get($this->table);
        return $this->get_to_array($query);
    }

    /**
     * Get items targeting specific roles (comma-separated in target_roles),
     * or individually targeting this user (target_user_login) — the two are
     * exclusive per item (cf. formulaire admin). Items with no targeting
     * restriction at all (both columns NULL/empty) apply to everyone.
     * @param string $user_login User login
     * @return array Active items excluding those individually targeting a
     *   different user. Items with target_roles still need to be filtered in
     *   PHP by the caller (comma-separated list), unchanged from before.
     */
    public function get_items_for_user($user_login) {
        $this->db->select('acceptance_items.*');
        $this->db->from($this->table);
        $this->db->where('active', 1);
        // Exclude items individually targeting a different user; items with
        // no target_user_login (NULL/empty) or targeting this user remain.
        $this->db->group_start();
        $this->db->where('target_user_login', $user_login);
        $this->db->or_where('target_user_login', null);
        $this->db->or_where("target_user_login = ''", null, false);
        $this->db->group_end();
        // Items with target_roles need to be filtered in PHP (comma-separated list).
        $this->db->order_by('title', 'asc');
        $query = $this->db->get();
        return $this->get_to_array($query);
    }

    /**
     * Get overdue items (deadline passed, still active)
     * @return array
     */
    public function get_overdue_items() {
        $this->db->where('active', 1);
        $this->db->where('deadline IS NOT NULL', null, false);
        $this->db->where('deadline <', date('Y-m-d'));
        $this->db->order_by('deadline', 'asc');
        $query = $this->db->get($this->table);
        return $this->get_to_array($query);
    }

    /**
     * Human-readable identifier for selectors
     * @param mixed $key Primary key value
     * @return string
     */
    public function image($key) {
        if ($key == "") return "";

        $vals = $this->get_by_id('id', $key);
        if ($vals && array_key_exists('title', $vals)) {
            return $vals['title'];
        }
        return "element inconnu $key";
    }

    /**
     * Returns selector array for dropdown
     * @param array $where Additional where conditions
     * @return array
     */
    public function selector($where = array(), $order = "asc", $filter_section = false) {
        $this->db->select('id, title');
        $this->db->from($this->table);
        if (!empty($where)) {
            $this->db->where($where);
        }
        $this->db->where('active', 1);
        $this->db->order_by('title', $order);
        $query = $this->db->get();
        $rows = $this->get_to_array($query);

        $result = array('' => '');
        foreach ($rows as $row) {
            $result[$row['id']] = $row['title'];
        }
        return $result;
    }
}

/* End of file acceptance_items_model.php */
/* Location: ./application/models/acceptance_items_model.php */
