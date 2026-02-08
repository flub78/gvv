<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Model for acceptance_records table
 *
 * Manages acceptance/refusal records per person, including internal and external
 * acceptances, dual validation tracking, and deferred pilot linking.
 *
 * @package models
 * @see application/migrations/068_acceptance_system.php
 */
class Acceptance_records_model extends Common_Model {
    public $table = 'acceptance_records';
    protected $primary_key = 'id';

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();
        $this->load->model('acceptance_items_model');
    }

    /**
     * Returns paginated list for display
     * @param int $per_page Number of items per page
     * @param int $premier Offset
     * @param array $selection Filter criteria
     * @return array
     */
    public function select_page($per_page = 0, $premier = 0, $selection = []) {
        $this->db->select('acceptance_records.id, acceptance_records.item_id,
            acceptance_records.user_login, acceptance_records.external_name,
            acceptance_records.status, acceptance_records.validation_role,
            acceptance_records.formula_text, acceptance_records.acted_at,
            acceptance_records.created_at, acceptance_records.signature_mode,
            acceptance_records.linked_pilot_login, acceptance_records.linked_by,
            acceptance_records.linked_at,
            acceptance_items.title as item_title,
            acceptance_items.category as item_category,
            m1.mnom as pilot_nom, m1.mprenom as pilot_prenom,
            m2.mnom as linked_pilot_nom, m2.mprenom as linked_pilot_prenom');
        $this->db->from($this->table);
        $this->db->join('acceptance_items', 'acceptance_records.item_id = acceptance_items.id', 'left');
        $this->db->join('membres m1', 'acceptance_records.user_login = m1.mlogin', 'left');
        $this->db->join('membres m2', 'acceptance_records.linked_pilot_login = m2.mlogin', 'left');

        if (!empty($selection)) {
            $this->db->where($selection);
        }

        $this->db->order_by('acceptance_records.created_at', 'desc');

        $query = $this->db->get();
        $select = $this->get_to_array($query);

        $this->gvvmetadata->store_table("vue_acceptance_records", $select);
        return $select;
    }

    /**
     * Get records for a specific user
     * @param string $user_login Member login
     * @param string|null $status Optional status filter
     * @return array
     */
    public function get_by_user($user_login, $status = null) {
        $this->db->select('acceptance_records.*, acceptance_items.title as item_title,
            acceptance_items.category as item_category, acceptance_items.deadline');
        $this->db->from($this->table);
        $this->db->join('acceptance_items', 'acceptance_records.item_id = acceptance_items.id', 'left');
        $this->db->where('acceptance_records.user_login', $user_login);

        if ($status !== null) {
            $this->db->where('acceptance_records.status', $status);
        }

        $this->db->order_by('acceptance_records.created_at', 'desc');
        $query = $this->db->get();
        return $this->get_to_array($query);
    }

    /**
     * Get records for a specific item
     * @param int $item_id Item ID
     * @param string|null $status Optional status filter
     * @return array
     */
    public function get_by_item($item_id, $status = null) {
        $this->db->select('acceptance_records.*,
            m1.mnom as pilot_nom, m1.mprenom as pilot_prenom');
        $this->db->from($this->table);
        $this->db->join('membres m1', 'acceptance_records.user_login = m1.mlogin', 'left');
        $this->db->where('acceptance_records.item_id', $item_id);

        if ($status !== null) {
            $this->db->where('acceptance_records.status', $status);
        }

        $this->db->order_by('acceptance_records.created_at', 'desc');
        $query = $this->db->get();
        return $this->get_to_array($query);
    }

    /**
     * Get pending records for a user
     * @param string $user_login Member login
     * @return array
     */
    public function get_pending_for_user($user_login) {
        return $this->get_by_user($user_login, 'pending');
    }

    /**
     * Count pending records for a user (for notification badge)
     * @param string $user_login Member login
     * @return int
     */
    public function count_pending_for_user($user_login) {
        $this->db->where('user_login', $user_login);
        $this->db->where('status', 'pending');
        return $this->db->count_all_results($this->table);
    }

    /**
     * Accept a record
     * @param int $record_id Record ID
     * @param string $formula_text Acceptance formula text
     * @return bool
     */
    public function accept($record_id, $formula_text) {
        $this->db->where('id', $record_id);
        return $this->db->update($this->table, array(
            'status' => 'accepted',
            'formula_text' => $formula_text,
            'acted_at' => date('Y-m-d H:i:s')
        ));
    }

    /**
     * Refuse a record
     * @param int $record_id Record ID
     * @return bool
     */
    public function refuse($record_id) {
        $this->db->where('id', $record_id);
        return $this->db->update($this->table, array(
            'status' => 'refused',
            'acted_at' => date('Y-m-d H:i:s')
        ));
    }

    /**
     * Link an external acceptance record to a pilot
     * @param int $record_id Record ID
     * @param string $pilot_login Pilot login to link to
     * @param string $linked_by User who performed the linking
     * @return bool
     */
    public function link_to_pilot($record_id, $pilot_login, $linked_by) {
        $this->db->where('id', $record_id);
        return $this->db->update($this->table, array(
            'linked_pilot_login' => $pilot_login,
            'linked_by' => $linked_by,
            'linked_at' => date('Y-m-d H:i:s')
        ));
    }

    /**
     * Get records linked to a specific pilot
     * @param string $pilot_login Pilot login
     * @return array
     */
    public function get_linked_records($pilot_login) {
        $this->db->select('acceptance_records.*, acceptance_items.title as item_title,
            acceptance_items.category as item_category');
        $this->db->from($this->table);
        $this->db->join('acceptance_items', 'acceptance_records.item_id = acceptance_items.id', 'left');
        $this->db->where('acceptance_records.linked_pilot_login', $pilot_login);
        $this->db->order_by('acceptance_records.linked_at', 'desc');
        $query = $this->db->get();
        return $this->get_to_array($query);
    }

    /**
     * Human-readable identifier
     * @param mixed $key Primary key value
     * @return string
     */
    public function image($key) {
        if ($key == "") return "";

        $vals = $this->get_by_id('id', $key);
        if ($vals) {
            $item_title = $this->acceptance_items_model->image($vals['item_id']);
            $user = isset($vals['user_login']) ? $vals['user_login'] : $vals['external_name'];
            return $item_title . ' - ' . $user . ' (' . $vals['status'] . ')';
        }
        return "enregistrement inconnu $key";
    }
}

/* End of file acceptance_records_model.php */
/* Location: ./application/models/acceptance_records_model.php */
