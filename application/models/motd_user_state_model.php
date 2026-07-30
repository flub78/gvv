<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Model for motd_user_message_state table
 *
 * Persists per-user actions on a message: hide individually, hide all
 * (implemented by hiding every currently active+visible message, so a
 * message received afterwards still reopens the dashboard section), and
 * acknowledge ("pris connaissance").
 *
 * @package models
 * @see application/migrations/143_create_motd_tables.php
 */
class Motd_user_state_model extends Common_Model {
    public $table = 'motd_user_message_state';
    protected $primary_key = 'id';

    function __construct() {
        parent::__construct();
        $this->load->model('motd_model');
    }

    public function get_state($message_id, $user_login) {
        return $this->get_first(array('message_id' => $message_id, 'user_login' => $user_login));
    }

    public function hide_message($message_id, $user_login) {
        return $this->upsert_state($message_id, $user_login, array('hidden' => 1));
    }

    /**
     * Hide every message currently active and visible to the user, in a
     * single bulk upsert query rather than one per message.
     *
     * @return int|FALSE Number of messages hidden, or FALSE if the write failed.
     */
    public function hide_all_messages($user_login) {
        $active = $this->motd_model->active_messages_for_user($user_login, 'priority', TRUE);
        if (empty($active)) {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        $placeholders = array();
        $bindings = array();
        foreach ($active as $message) {
            $placeholders[] = '(?, ?, 1, ?, ?, ?, ?)';
            array_push($bindings, $message['id'], $user_login, $now, $now, $user_login, $user_login);
        }

        $sql = "INSERT INTO `{$this->table}`
            (`message_id`, `user_login`, `hidden`, `created_at`, `updated_at`, `created_by`, `updated_by`)
            VALUES " . implode(', ', $placeholders) . "
            ON DUPLICATE KEY UPDATE `hidden` = 1, `updated_at` = VALUES(`updated_at`), `updated_by` = VALUES(`updated_by`)";

        if (!$this->db->query($sql, $bindings)) {
            gvv_error("motd_user_state_model::hide_all_messages failed: " . $this->db->_error_message());
            return FALSE;
        }
        return count($active);
    }

    /**
     * Unhide every message the user had hidden (acknowledged state untouched).
     *
     * @return int|FALSE Number of messages unhidden, or FALSE if the update failed.
     */
    public function unhide_all_messages($user_login) {
        $this->db->where('user_login', $user_login);
        $this->db->where('hidden', 1);
        $hidden_rows = $this->db->get($this->table)->result_array();
        $count = count($hidden_rows);
        if ($count > 0) {
            $this->db->where('user_login', $user_login);
            $this->db->where('hidden', 1);
            if (!$this->db->update($this->table, array('hidden' => 0, 'updated_by' => $user_login))) {
                return FALSE;
            }
        }
        return $count;
    }

    public function acknowledge_message($message_id, $user_login) {
        return $this->upsert_state($message_id, $user_login, array(
            'acknowledged' => 1,
            'acknowledged_at' => date('Y-m-d H:i:s'),
        ));
    }

    /**
     * @return int|FALSE The state row id on success, FALSE if the write failed.
     */
    private function upsert_state($message_id, $user_login, $data) {
        $existing = $this->get_state($message_id, $user_login);
        $data['message_id'] = $message_id;
        $data['user_login'] = $user_login;
        // Self-service action: the acting user is already known here, use it
        // directly rather than relying on the session-derived audit fields.
        $data['updated_by'] = $user_login;

        if ($existing) {
            return $this->update('id', $data, $existing['id']) ? $existing['id'] : FALSE;
        }
        $data['created_by'] = $user_login;
        return $this->create($data);
    }
}

/* End of file motd_user_state_model.php */
/* Location: ./application/models/motd_user_state_model.php */
