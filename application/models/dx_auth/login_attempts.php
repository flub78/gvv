<?php
#[AllowDynamicProperties]
class Login_Attempts extends CI_Model {

    function __construct() {
        parent :: __construct();
        // Other stuff
        $this->_prefix = $this->config->item('DX_table_prefix');
        $this->_table = $this->_prefix . $this->config->item('DX_login_attempts_table');
    }

    // Failed attempts of the IP address within the DX_login_attempts_window
    // (seconds, 1800 when not configured, 0 = no time limit)
    function check_attempts($ip_address) {
        $window = $this->config->item('DX_login_attempts_window');
        $window = ($window === FALSE || $window === NULL || $window === '') ? 1800 : (int) $window;
        $this->db->select('1', FALSE);
        $this->db->where('ip_address', $ip_address);
        if ($window > 0) {
            $this->db->where("time >= DATE_SUB(NOW(), INTERVAL $window SECOND)", NULL, FALSE);
        }
        return $this->db->get($this->_table);
    }

    // Increase attempts count
    function increase_attempt($ip_address) {
        // Insert new record
        $data = array (
            'ip_address' => $ip_address
        );

        $this->db->insert($this->_table, $data);
    }

    function clear_attempts($ip_address) {
        $this->db->where('ip_address', $ip_address);
        $this->db->delete($this->_table);
    }

}
?>