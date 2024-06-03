<?php
/**
 *    GVV Gestion vol à voile
 *    Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * File: avion.php
 * controleur de gestion des droits
 *
 */
include ('./application/libraries/Gvv_Controller.php');

class Backend extends GVV_Controller {
    protected $controller = 'backend';
    protected $model = 'dx_auth/users';
    function __construct() {
        parent::__construct();

        $this->load->library('Table');
        $this->load->library('Pagination');
        $this->load->library('DX_Auth');

        $this->load->helper('form');
        $this->load->helper('url');

        $this->load->model('dx_auth/roles', 'roles');

        // Protect entire controller so only admin,
        // and users that have granted role in permissions table can access it.
        $this->dx_auth->check_uri_permissions();

        $this->lang->load('auth');
    }

    /**
     * Génération des éléments à passer au formulaire en cas de création,
     * modification ou réaffichage après erreur.
     */
    function form_static_element($action) {
        parent::form_static_element($action);
        $this->data ['passconf'] = '';
        $this->data ['password'] = '';
        $this->data ['role_selector'] = $this->roles->selector();
    }
    function index() {
        $this->users();
    }
    function users($offset = 0) {
        $this->load->model('dx_auth/users', 'users');

        // Search checkbox in post array
        foreach ( $_POST as $key => $value ) {
            // If checkbox found
            if (substr($key, 0, 9) == 'checkbox_') {
                // If ban button pressed
                if (isset($_POST ['ban'])) {
                    // Ban user based on checkbox value (id)
                    $this->users->ban_user($value);
                }                 // If unban button pressed
                else if (isset($_POST ['unban'])) {
                    // Unban user
                    $this->users->unban_user($value);
                } else if (isset($_POST ['reset_pass'])) {
                    // Set default message
                    $data ['reset_message'] = $this->lang->line("gvv_error_reset_password_failed");

                    // Get user and check if User ID exist
                    if ($query = $this->users->get_user_by_id($value) and $query->num_rows() == 1) {
                        // Get user record
                        $user = $query->row();

                        // Create new key, password and send email to user
                        if ($this->dx_auth->forgot_password($user->username)) {
                            // Query once again, because the database is updated after calling forgot_password.
                            $query = $this->users->get_user_by_id($value);
                            // Get user record
                            $user = $query->row();

                            // Reset the password
                            if ($this->dx_auth->reset_password($user->username, $user->newpass_key)) {
                                $data ['reset_message'] = $this->lang->line("gvv_reset_password_success");
                            }
                        }
                    }
                }
            }
        }

        /* Showing page to user */

        // Number of record showing per page
        if ($this->config->item('ajax')) {
            $row_count = 1000000;
        } else {
            $row_count = 100;
        }

        // Get all users
        $data ['users'] = $this->users->get_all($offset, $row_count)->result();

        // Pagination config
        $p_config ['base_url'] = base_url() . '/index.php/backend/users/';
        $p_config ['uri_segment'] = 3;
        $p_config ['num_links'] = 2;
        $p_config ['total_rows'] = $this->users->get_all()->num_rows();
        $p_config ['per_page'] = $row_count;

        // Init pagination
        $this->pagination->initialize($p_config);
        // Create pagination links
        $data ['pagination'] = $this->pagination->create_links();

        // Load view
        load_last_view('backend/users', $data);
    }

    function unactivated_users() {
        $this->load->model('dx_auth/user_temp', 'user_temp');

        /* Database related */

        // If activate button pressed
        if ($this->input->post('activate')) {
            // Search checkbox in post array
            foreach ( $_POST as $key => $value ) {
                // If checkbox found
                if (substr($key, 0, 9) == 'checkbox_') {
                    // Check if user exist, $value is username
                    if ($query = $this->user_temp->get_login($value) and $query->num_rows() == 1) {
                        // Activate user
                        $this->dx_auth->activate($value, $query->row()->activation_key);
                    }
                }
            }
        }

        /* Showing page to user */

        // Get offset and limit for page viewing
        $offset = ( int ) $this->uri->segment(3);
        // Number of record showing per page
        $row_count = 10;

        // Get all unactivated users
        $data ['users'] = $this->user_temp->get_all($offset, $row_count)->result();

        // Pagination config
        $p_config ['base_url'] = '/backend/unactivated_users/';
        $p_config ['uri_segment'] = 3;
        $p_config ['num_links'] = 2;
        $p_config ['total_rows'] = $this->user_temp->get_all()->num_rows();
        $p_config ['per_page'] = $row_count;

        // Init pagination
        $this->pagination->initialize($p_config);
        // Create pagination links
        $data ['pagination'] = $this->pagination->create_links();

        // Load view
        load_last_view('backend/unactivated_users', $data);
    }

    function roles() {
        /* Database related */

        // If Add role button pressed
        if ($this->input->post('add')) {
            // Create role
            $this->roles->create_role($this->input->post('role_name'), $this->input->post('role_parent'));
        } else if ($this->input->post('delete')) {
            // Loop trough $_POST array and delete checked checkbox
            foreach ( $_POST as $key => $value ) {
                // If checkbox found
                if (substr($key, 0, 9) == 'checkbox_') {
                    // Delete role
                    $this->roles->delete_role($value);
                }
            }
        }

        /* Showing page to user */

        // Get all roles from database
        $data ['roles'] = $this->roles->get_all()->result();

        // Load view
        load_last_view('backend/roles', $data);
    }

    function uri_permissions() {
        function trim_value(&$value) {
            $value = trim($value);
        }

        $this->load->model('dx_auth/permissions', 'permissions');

        if ($this->input->post('save')) {
            // Convert back text area into array to be stored in permission data
            $allowed_uris = explode("\n", $this->input->post('allowed_uris'));

            // Remove white space if available
            array_walk($allowed_uris, 'trim_value');

            // Set URI permission data
            // IMPORTANT: uri permission data, is saved using 'uri' as key.
            // So this key name is preserved, if you want to use custom permission use other key.
            $this->permissions->set_permission_value($this->input->post('role'), 'uri', $allowed_uris);
        }

        /* Showing page to user */

        // Default role_id that will be showed
        $role_id = $this->input->post('role') ? $this->input->post('role') : 1;

        // Get all role from database
        $data ['roles'] = $this->roles->get_all()->result();
        // Get allowed uri permissions
        $data ['allowed_uris'] = $this->permissions->get_permission_value($role_id, 'uri');

        // Load view
        load_last_view('backend/uri_permissions', $data);
    }

    function custom_permissions() {
        // Load models
        $this->load->model('dx_auth/permissions', 'permissions');

        /* Get post input and apply it to database */

        // If button save pressed
        if ($this->input->post('save')) {
            // Note: Since in this case we want to insert two key with each value at once,
            // it's not advisable using set_permission_value() function
            // If you calling that function twice that means, you will query database 4 times,
            // because set_permission_value() will access table 2 times,
            // one for get previous permission and the other one is to save it.

            // For this case (or you need to insert few key with each value at once)
            // Use the example below

            // Get role_id permission data first.
            // So the previously set permission array key won't be overwritten with new array with key $key only,
            // when calling set_permission_data later.
            $permission_data = $this->permissions->get_permission_data($this->input->post('role'));

            // Set value in permission data array
            $permission_data ['edit'] = $this->input->post('edit');
            $permission_data ['delete'] = $this->input->post('delete');

            // Set permission data for role_id
            $this->permissions->set_permission_data($this->input->post('role'), $permission_data);
        }

        /* Showing page to user */

        // Default role_id that will be showed
        $role_id = $this->input->post('role') ? $this->input->post('role') : 1;

        // Get all role from database
        $data ['roles'] = $this->roles->get_all()->result();
        // Get edit and delete permissions
        $data ['edit'] = $this->permissions->get_permission_value($role_id, 'edit');
        $data ['delete'] = $this->permissions->get_permission_value($role_id, 'delete');

        // Load view
        load_last_view('backend/formView', $data);
    }

    /**
     * Vérifie que l'élément n'existe pas déjà en base de données
     *
     * @param unknown_type $id
     */
    function check_uniq($id) {
        // if (!$this->dx_auth->is_username_available($this->data['username']))
        if (! $this->dx_auth->is_username_available($id)) {
            $this->form_validation->set_message('check_uniq', $this->lang->line("gvv_error_duplicate_user"));
            return FALSE;
        } else {
            return $id;
        }
    }

    /**
     * Crée un nouvel élèment
     */
    function create() {

        // initialise les valeurs par défaut
        $data ['username'] = '';
        $data ['password'] = '';
        $data ['passconf'] = '';
        $data ['email'] = '';
        $data ['role_id'] = 1;
        $data ['role_selector'] = $this->roles->selector();
        $data ['action'] = CREATION;
        load_last_view('backend/formView', $data);
    }

    /**
     * Supprime un élèment
     */
    function delete($id) {
        $this->load->model('dx_auth/users', 'users');

        // détruit en base
        $this->users->delete_user($id);

        // réaffiche la liste (serait sympa de réafficher la même page)
        redirect("backend/users");
    }

    /**
     * Validation du formulaire d'édition
     */
    public function formValidation($action) {
        // Validates the form entries
        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');

        // definit les règles de validation et charge les données
        $config = array (
                array (
                        'field' => 'username',
                        'label' => $this->lang->line("auth_user_name"),
                        'rules' => 'trim|required|max_length[25]'
                ),
                array (
                        'field' => 'password',
                        'label' => $this->lang->line("auth_password"),
                        'rules' => 'trim|max_length[34]'
                ),
                array (
                        'field' => 'passconf',
                        'label' => $this->lang->line("auth_confirm_password"),
                        'rules' => 'trim|callback_equal_password'
                ),
                array (
                        'field' => 'email',
                        'label' => $this->lang->line("auth_email"),
                        'rules' => 'trim|required|valid_email'
                )
        );

        if ($action == CREATION) {
            $config [0] ['rules'] .= '|callback_check_uniq';
        }
        $this->form_validation->set_rules($config);

        $fields = array (
                'username',
                'password',
                'email',
                'role_id'
        );
        foreach ( $fields as $field ) {
            $this->data [$field] = $this->input->post($field);
        }

        if ($this->form_validation->run()) {
            // get the processed data. It must not be done before because all the
            // processing is done by the run method.

            if ($action == CREATION) {
                if (! $user = $this->dx_auth->register($this->data ['username'], $this->data ['password'], $this->data ['email'])) {
                    echo $this->lang->line("gvv_error_saving_user") . "<br>";
                }
            } else {
                // Load Models
                $this->load->model('dx_auth/users', 'users');
                $id = $this->input->post('id');
                if ($this->data ['password'] != "") {
                    $this->dx_auth->force_password($id, $this->data ['password']);
                }
                unset($this->data ['password']);
                $this->users->set_user($id, $this->data);
            }
            redirect("backend/users");
        } else {
            // Display the form again
            $this->form_static_element($action);
            load_last_view('backend/formView', $this->data);
        }
    }

    /**
     * Vérifie que le mot de passe et la confirmation sont egaux
     *
     * @return boolean
     */
    public function equal_password() {
        $this->form_validation->set_message('equal_password', $this->lang->line("gvv_error_confirm_password"));
        return ($this->input->post('password') == $this->input->post('passconf'));
    }
}
?>