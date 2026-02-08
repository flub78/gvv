<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Model for acceptance_tokens table
 *
 * Manages temporary tokens for external signature links (direct, link, QR code).
 * Tokens are random hex strings, single-use, with configurable expiration.
 *
 * @package models
 * @see application/migrations/068_acceptance_system.php
 */
class Acceptance_tokens_model extends Common_Model {
    public $table = 'acceptance_tokens';
    protected $primary_key = 'id';

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();
    }

    /**
     * Generate a unique random token and store it
     * @param int $item_id Acceptance item ID
     * @param string $mode Token mode (direct, link, qrcode)
     * @param string $created_by User login who created the token
     * @param int $expires_hours Hours until expiration (default 24)
     * @return string|false The token string or false on failure
     */
    public function generate_token($item_id, $mode, $created_by, $expires_hours = 24) {
        // Generate cryptographically secure random token (64 hex chars = 32 bytes)
        $token = bin2hex(random_bytes(32));

        $data = array(
            'token' => $token,
            'item_id' => $item_id,
            'mode' => $mode,
            'created_by' => $created_by,
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', time() + ($expires_hours * 3600)),
            'used' => 0
        );

        $id = $this->create($data);
        if ($id === false) {
            return false;
        }
        return $token;
    }

    /**
     * Validate a token: check existence, not expired, not used
     * @param string $token Token string
     * @return array|false Token data if valid, false otherwise
     */
    public function validate_token($token) {
        $this->db->select('acceptance_tokens.*, acceptance_items.title as item_title,
            acceptance_items.category as item_category, acceptance_items.pdf_path');
        $this->db->from($this->table);
        $this->db->join('acceptance_items', 'acceptance_tokens.item_id = acceptance_items.id', 'left');
        $this->db->where('acceptance_tokens.token', $token);
        $this->db->where('acceptance_tokens.used', 0);
        $this->db->where('acceptance_tokens.expires_at >', date('Y-m-d H:i:s'));
        $query = $this->db->get();
        $result = $query->row_array();

        if (empty($result)) {
            return false;
        }
        return $result;
    }

    /**
     * Mark a token as used and associate with a record
     * @param string $token Token string
     * @param int $record_id Associated acceptance record ID
     * @return bool
     */
    public function mark_used($token, $record_id) {
        $this->db->where('token', $token);
        return $this->db->update($this->table, array(
            'used' => 1,
            'used_at' => date('Y-m-d H:i:s'),
            'record_id' => $record_id
        ));
    }

    /**
     * Delete expired tokens older than the given number of days
     * @param int $days_old Delete tokens expired more than this many days ago (default 7)
     * @return int Number of deleted tokens
     */
    public function cleanup_expired($days_old = 7) {
        $cutoff = date('Y-m-d H:i:s', time() - ($days_old * 86400));
        $this->db->where('expires_at <', $cutoff);
        $this->db->delete($this->table);
        return $this->db->affected_rows();
    }

    /**
     * Get active (unused, non-expired) tokens for an item
     * @param int $item_id Item ID
     * @return array
     */
    public function get_by_item($item_id) {
        $this->db->where('item_id', $item_id);
        $this->db->where('used', 0);
        $this->db->where('expires_at >', date('Y-m-d H:i:s'));
        $this->db->order_by('created_at', 'desc');
        $query = $this->db->get($this->table);
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
            $short_token = substr($vals['token'], 0, 8) . '...';
            return $short_token . ' (' . $vals['mode'] . ')';
        }
        return "token inconnu $key";
    }
}

/* End of file acceptance_tokens_model.php */
/* Location: ./application/models/acceptance_tokens_model.php */
