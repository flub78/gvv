<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Model for motd_replies table
 *
 * Replies to a "message du jour". Visibility (recipients of the original
 * message + its author) is enforced by the controller via
 * Motd_model::user_can_access_message(), not duplicated here.
 *
 * @package models
 * @see application/migrations/143_create_motd_tables.php
 */
class Motd_replies_model extends Common_Model {
    public $table = 'motd_replies';
    protected $primary_key = 'id';

    public function create_reply($data) {
        return $this->create($data);
    }

    public function update_reply($id, $data) {
        $this->update('id', $data, $id);
    }

    public function delete_reply($id) {
        $this->delete(array('id' => $id));
    }

    public function get_reply($id) {
        return $this->get_by_id('id', $id);
    }

    public function replies_for_message($message_id) {
        return $this->select_all(array('message_id' => $message_id), 'created_at ASC');
    }

    /**
     * Batched version of replies_for_message() for a set of messages (avoids
     * one query per message on the dashboard).
     *
     * @param array $message_ids
     * @return array Replies grouped by message_id: array($message_id => array(reply, ...))
     */
    public function replies_for_messages($message_ids) {
        $grouped = array();
        if (empty($message_ids)) {
            return $grouped;
        }
        $rows = $this->get_to_array(
            $this->db->from($this->table)->where_in('message_id', $message_ids)->order_by('created_at', 'ASC')->get()
        );
        foreach ($rows as $row) {
            $grouped[$row['message_id']][] = $row;
        }
        return $grouped;
    }
}

/* End of file motd_replies_model.php */
/* Location: ./application/models/motd_replies_model.php */
