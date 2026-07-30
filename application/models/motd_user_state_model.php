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
     * Hide every message currently active and visible to the user.
     *
     * @return int Number of messages hidden.
     */
    public function hide_all_messages($user_login) {
        $active = $this->motd_model->active_messages_for_user($user_login, 'priority', TRUE);
        foreach ($active as $message) {
            $this->hide_message($message['id'], $user_login);
        }
        return count($active);
    }

    /**
     * Unhide every message the user had hidden (acknowledged state untouched).
     *
     * @return int Number of messages unhidden.
     */
    public function unhide_all_messages($user_login) {
        $this->db->where('user_login', $user_login);
        $this->db->where('hidden', 1);
        $hidden_rows = $this->db->get($this->table)->result_array();
        $count = count($hidden_rows);
        if ($count > 0) {
            $this->db->where('user_login', $user_login);
            $this->db->where('hidden', 1);
            $this->db->update($this->table, array('hidden' => 0, 'updated_by' => $user_login));
        }
        return $count;
    }

    public function acknowledge_message($message_id, $user_login) {
        return $this->upsert_state($message_id, $user_login, array(
            'acknowledged' => 1,
            'acknowledged_at' => date('Y-m-d H:i:s'),
        ));
    }

    private function upsert_state($message_id, $user_login, $data) {
        $existing = $this->get_state($message_id, $user_login);
        $data['message_id'] = $message_id;
        $data['user_login'] = $user_login;
        // Self-service action: the acting user is already known here, use it
        // directly rather than relying on the session-derived audit fields.
        $data['updated_by'] = $user_login;

        if ($existing) {
            $this->update('id', $data, $existing['id']);
            return $existing['id'];
        }
        $data['created_by'] = $user_login;
        return $this->create($data);
    }
}

/* End of file motd_user_state_model.php */
/* Location: ./application/models/motd_user_state_model.php */
