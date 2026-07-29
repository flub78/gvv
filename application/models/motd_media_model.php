<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Model for motd_media table
 *
 * Uploaded images referenced from a message's Markdown content
 * (`![alt](motd/media/{id})`). A media row can exist with message_id=NULL
 * right after upload, before the message it belongs to is saved; it is
 * linked to its message afterwards (see Motd::link_uploaded_media()).
 *
 * @package models
 * @see application/migrations/143_create_motd_tables.php
 */
class Motd_media_model extends Common_Model {
    public $table = 'motd_media';
    protected $primary_key = 'id';

    public function create_media($data) {
        return $this->create($data);
    }

    public function get_media($id) {
        return $this->get_by_id('id', $id);
    }

    /**
     * Attach a set of not-yet-linked media (uploaded by $created_by) to a message.
     *
     * @param array $media_ids
     * @param int $message_id
     * @param string $created_by Only media uploaded by this user can be linked,
     *                            preventing a user from hijacking someone else's upload.
     */
    public function link_to_message($media_ids, $message_id, $created_by) {
        $media_ids = array_filter(array_map('intval', $media_ids));
        if (empty($media_ids)) {
            return;
        }

        $this->db->where_in('id', $media_ids);
        $this->db->where('created_by', $created_by);
        $this->db->where('message_id IS NULL');
        $this->db->update($this->table, array('message_id' => $message_id));
    }

    public function media_for_message($message_id) {
        return $this->select_all(array('message_id' => $message_id), 'created_at ASC');
    }
}

/* End of file motd_media_model.php */
/* Location: ./application/models/motd_media_model.php */
