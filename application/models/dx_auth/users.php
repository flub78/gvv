<?php
$CI = &get_instance();
$CI->load->model('common_model');

class Users extends Common_Model {
    public $table = 'users';
    protected $primary_key = 'id';

    function __construct() {
        parent::__construct();
        // Other stuff
        $this->_prefix = $this->config->item('DX_table_prefix');
        $this->_table = $this->_prefix . $this->config->item('DX_users_table');
        $this->_roles_table = $this->_prefix . $this->config->item('DX_roles_table');
    }

    // General function

    function get_all($offset = 0, $row_count = 0) {
        $users_table = $this->_table;
        $roles_table = $this->_roles_table;

        if ($offset >= 0 and $row_count > 0) {
            $this->db->select("$users_table.*", FALSE);
            $this->db->select("$roles_table.name AS role_name", FALSE);
            $this->db->join($roles_table, "$roles_table.id = $users_table.role_id");
            $this->db->order_by("$users_table.id", "ASC");

            $query = $this->db->get($this->_table, $row_count, $offset);
        } else {
            $query = $this->db->get($this->_table);
        }

        return $query;
    }

    public function select_page($nb = 1000, $debut = 0) {
        return $this->select_columns($debut, $nb)->result();
    }

    function get_user_by_id($user_id) {
        $this->db->where('id', $user_id);
        return $this->db->get($this->_table);
    }

    function get_user_by_username($username) {
        $this->db->where('username', $username);
        return $this->db->get($this->_table);
    }

    function get_user_by_email($email) {
        $this->db->where('email', $email);
        return $this->db->get($this->_table);
    }

    function get_login($login) {
        $this->db->where('username', $login);
        $this->db->or_where('email', $login);
        return $this->db->get($this->_table);
    }

    function check_ban($user_id) {
        $this->db->select('1', FALSE);
        $this->db->where('id', $user_id);
        $this->db->where('banned', '1');
        return $this->db->get($this->_table);
    }

    function check_username($username) {
        $this->db->select('1', FALSE);
        $this->db->where('LOWER(username)=', strtolower($username));
        return $this->db->get($this->_table);
    }

    function check_email($email) {
        $this->db->select('1', FALSE);
        $this->db->where('LOWER(email)=', strtolower($email));
        return $this->db->get($this->_table);
    }

    function ban_user($user_id, $reason = NULL) {
        $data = array(
            'banned' => 1,
            'ban_reason' => $reason
        );
        return $this->set_user($user_id, $data);
    }

    function unban_user($user_id) {
        $data = array(
            'banned' => 0,
            'ban_reason' => NULL
        );
        return $this->set_user($user_id, $data);
    }

    function set_role($user_id, $role_id) {
        $data = array(
            'role_id' => $role_id
        );
        return $this->set_user($user_id, $data);
    }

    // User table function

    function create_user($data) {
        $data['created'] = date('Y-m-d H:i:s', time());
        return $this->db->insert($this->_table, $data);
    }

    function get_user_field($user_id, $fields) {
        $this->db->select($fields);
        $this->db->where('id', $user_id);
        return $this->db->get($this->_table);
    }

    function set_user($user_id, $data) {
        $this->db->where('id', $user_id);
        return $this->db->update($this->_table, $data);
    }

    function delete_user($user_id) {
        // First, get the username for this user
        $query = $this->get_user_by_id($user_id);
        if (!$query || $query->num_rows() == 0) {
            return FALSE;
        }
        $user = $query->row();
        $username = $user->username;
        
        // Get CodeIgniter instance for language support
        $CI =& get_instance();
        $CI->lang->load('users');
        
        // Check if user is referenced in other tables
        $references = array();
        
        // Check membres.mlogin
        $this->db->where('mlogin', $username);
        $count = $this->db->count_all_results('membres');
        if ($count > 0) {
            $references[] = $CI->lang->line('user_delete_ref_membre') . " ($count)";
        }
        
        // Check comptes.pilote
        $this->db->where('pilote', $username);
        $count = $this->db->count_all_results('comptes');
        if ($count > 0) {
            $references[] = $CI->lang->line('user_delete_ref_compte') . " ($count)";
        }
        
        // Check volsa.vapilid (airplane flights - pilot)
        $this->db->where('vapilid', $username);
        $count = $this->db->count_all_results('volsa');
        if ($count > 0) {
            $references[] = $CI->lang->line('user_delete_ref_volsa_pilot') . " ($count)";
        }
        
        // Check volsa.vainst (airplane flights - instructor)
        $this->db->where('vainst', $username);
        $count = $this->db->count_all_results('volsa');
        if ($count > 0) {
            $references[] = $CI->lang->line('user_delete_ref_volsa_instructor') . " ($count)";
        }
        
        // Check volsp.vppilid (glider flights - pilot)
        $this->db->where('vppilid', $username);
        $count = $this->db->count_all_results('volsp');
        if ($count > 0) {
            $references[] = $CI->lang->line('user_delete_ref_volsp_pilot') . " ($count)";
        }
        
        // Check volsp.vpinst (glider flights - instructor)
        $this->db->where('vpinst', $username);
        $count = $this->db->count_all_results('volsp');
        if ($count > 0) {
            $references[] = $CI->lang->line('user_delete_ref_volsp_instructor') . " ($count)";
        }
        
        // Check volsp.pilote_remorqueur (glider flights - tow pilot)
        $this->db->where('pilote_remorqueur', $username);
        $count = $this->db->count_all_results('volsp');
        if ($count > 0) {
            $references[] = $CI->lang->line('user_delete_ref_volsp_towpilot') . " ($count)";
        }
        
        // If there are references, return FALSE with error message
        if (!empty($references)) {
            $CI->load->library('session');
            $unique_refs = array_unique($references);
            
            // Create detailed error message with username
            $error_msg = sprintf($CI->lang->line('user_delete_blocked'), $username) . "\n\n";
            $error_msg .= $CI->lang->line('user_delete_dependencies') . "\n";
            $error_msg .= "• " . implode("\n• ", $unique_refs);
            
            $CI->session->set_flashdata('error', $error_msg);
            $CI->session->set_flashdata('delete_failed_user', $username);
            return FALSE;
        }
        
        // If no references, proceed with deletion
        
        // Delete user roles per section (permissions)
        $this->db->where('user_id', $user_id);
        $this->db->delete('user_roles_per_section');
        
        // Delete authorization comparison log entries
        $this->db->where('user_id', $user_id);
        $this->db->delete('authorization_comparison_log');
        
        // Delete authorization migration status entries (as migrator)
        $this->db->where('migrated_by', $user_id);
        $this->db->delete('authorization_migration_status');
        
        // Delete authorization migration status entries (as user)
        $this->db->where('user_id', $user_id);
        $this->db->delete('authorization_migration_status');
        
        // Delete user autologin entries
        $this->db->where('user_id', $user_id);
        $this->db->delete('user_autologin');
        
        // Finally, delete the user
        $this->db->where('id', $user_id);
        $this->db->delete($this->_table);
        return $this->db->affected_rows() > 0;
    }

    // Forgot password function

    function newpass($user_id, $pass, $key) {
        $data = array(
            'newpass' => $pass,
            'newpass_key' => $key,
            'newpass_time' => date('Y-m-d h:i:s', time() + $this->config->item('DX_forgot_password_expire'))
        );
        return $this->set_user($user_id, $data);
    }

    function activate_newpass($user_id, $key) {
        $this->db->set('password', 'newpass', FALSE);
        $this->db->set('newpass', NULL);
        $this->db->set('newpass_key', NULL);
        $this->db->set('newpass_time', NULL);
        $this->db->where('id', $user_id);
        $this->db->where('newpass_key', $key);

        return $this->db->update($this->_table);
    }

    function clear_newpass($user_id) {
        $data = array(
            'newpass' => NULL,
            'newpass_key' => NULL,
            'newpass_time' => NULL
        );
        return $this->set_user($user_id, $data);
    }

    // Change password function

    function change_password($user_id, $new_pass) {
        $this->db->set('password', $new_pass);
        $this->db->where('id', $user_id);
        return $this->db->update($this->_table);
    }

    /**
     * Retourne une chaine de caractère qui identifie une ligne de façon unique.
     * Cette chaine est utilisé dans les affichages.
     * Par défaut elle retourne la valeur de la clé, mais elle est conçue pour être
     * surchargée.
     */
    public function image($key) {
        if ($key == "")
            return "";
        $vals = $this->get_by_id('id', $key);
        if (array_key_exists('id', $vals) && array_key_exists('username', $vals)) {
            return $vals['username'] . " - " . $vals['email'];
        } else {
            return "user inconnu $key";
        }
    }
}
