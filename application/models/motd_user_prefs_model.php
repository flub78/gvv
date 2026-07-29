<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Model for motd_user_prefs table
 *
 * Persistent per-user display preferences for the MOTD dashboard section
 * (collapsed/expanded state, sort criterion).
 *
 * @package models
 * @see application/migrations/143_create_motd_tables.php
 */
class Motd_user_prefs_model extends Common_Model {
    public $table = 'motd_user_prefs';
    protected $primary_key = 'id';

    private static $defaults = array(
        'section_collapsed' => 1,
        'sort_by' => 'priority',
    );

    public function get_prefs($user_login) {
        $prefs = $this->get_first(array('user_login' => $user_login));
        if (!$prefs) {
            return array_merge(array('user_login' => $user_login), self::$defaults);
        }
        return $prefs;
    }

    public function save_prefs($user_login, $data) {
        $existing = $this->get_first(array('user_login' => $user_login));
        $data['user_login'] = $user_login;
        // Self-service action: the acting user is already known here, use it
        // directly rather than relying on the session-derived audit fields.
        $data['updated_by'] = $user_login;

        if ($existing) {
            $this->update('id', $data, $existing['id']);
            return $existing['id'];
        }
        $data['created_by'] = $user_login;
        return $this->create(array_merge(self::$defaults, $data));
    }
}

/* End of file motd_user_prefs_model.php */
/* Location: ./application/models/motd_user_prefs_model.php */
