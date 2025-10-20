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
 * @filesource authorization.php
 * @package controllers
 */
/**
 * Authorization Management Controller
 *
 * Provides admin interface for managing the new authorization system:
 * - User roles management
 * - Role permissions management
 * - Data access rules management
 * - Audit log viewer
 *
 * @see /doc/plans/2025_authorization_refactoring_plan.md
 */
class Authorization extends CI_Controller {
    protected $controller = 'authorization';

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();

        // Setup from Gvv_Controller
        date_default_timezone_set("Europe/Paris");
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->helper('form');
        $this->load->helper('form_elements');
        $this->session->set_userdata('requested_url', current_url());

        $this->load->library('DX_Auth');
        $this->lang->load('gvv');

        log_message('error', '[DEBUG] User role from session: ' . $this->session->userdata('DX_role_name'));

        if (getenv('TEST') != '1') {
            $this->dx_auth->check_login();
        }

        // Authorization-specific setup
        $user_roles = array_merge(
            (array)$this->session->userdata('DX_parent_roles_name'),
            (array)$this->session->userdata('DX_role_name')
        );
        $required_roles = array('admin', 'club-admin');
        $has_role = FALSE;
        foreach ($required_roles as $required_role) {
            foreach ($user_roles as $user_role) {
                if (strtolower($required_role) == strtolower($user_role)) {
                    $has_role = TRUE;
                    break 2;
                }
            }
        }

        log_message('error', 'ROLES_DEBUG: User roles: ' . print_r($user_roles, TRUE));
        log_message('error', 'ROLES_DEBUG: Required roles: ' . print_r($required_roles, TRUE));
        log_message('error', 'ROLES_DEBUG: Has role: ' . ($has_role ? 'yes' : 'no'));
        log_message('error', 'ROLES_DEBUG: is_role check: ' . ($this->dx_auth->is_role(array('admin', 'club-admin')) ? 'yes' : 'no'));

        if (!$has_role) {
            $this->dx_auth->deny_access();
        }

        $this->load->model('authorization_model');
        $this->load->library('Gvv_Authorization');
    }

    /**
     * Dashboard - Overview of authorization system
     */
    function index() {
        $data = array();
        $data['controller'] = $this->controller;
        $data['title'] = $this->lang->line('authorization_title');

        // Get system statistics
        $data['total_roles'] = count($this->Authorization_model->get_all_roles());
        $data['total_users'] = $this->db->count_all('users');

        // Get recent audit log entries
        $data['recent_audits'] = $this->Authorization_model->get_audit_log(array(), 10);

        // Check if new system is enabled
        $this->config->load('gvv_config', TRUE);
        $config = $this->config->item('gvv_config');
        $data['new_system_enabled'] = isset($config['use_new_authorization']) ? $config['use_new_authorization'] : FALSE;

        load_last_view('authorization/dashboard', $data);
    }

    /**
     * Manage user roles - List all users and their roles
     */
    function user_roles($message = '') {
        $data = array();
        $data['controller'] = $this->controller;
        $data['title'] = $this->lang->line('authorization_users');
        $data['message'] = $message;

        // Get all users with their roles
        $this->db->select('u.id, u.username, u.email, m.mnom, m.mprenom, m.club as section_id, s.nom as section_name');
        $this->db->from('users u');
        $this->db->join('membres m', 'u.username = m.mlogin', 'left');
        $this->db->join('sections s', 'm.club = s.id', 'left');
        $this->db->order_by('u.username', 'ASC');
        $query = $this->db->get();
        $users = $query->result_array();

        // Get roles for each user (across ALL sections)
        foreach ($users as &$user) {
            $user['roles'] = $this->Authorization_model->get_user_roles($user['id'], NULL);
        }

        $data['users'] = $users;
        $data['all_roles'] = $this->Authorization_model->get_all_roles();
        $data['sections'] = $this->db->get('sections')->result_array();

        load_last_view('authorization/user_roles', $data);
    }

    /**
     * Get user roles - AJAX endpoint for updating UI
     */
    function get_user_roles() {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $user_id = $this->input->get('user_id');

        if (!$user_id) {
            echo json_encode(array('success' => FALSE, 'message' => 'Missing user_id'));
            return;
        }

        // Get ALL roles for this user across ALL sections
        // Pass NULL as section_id to get all roles
        $roles = $this->Authorization_model->get_user_roles($user_id, NULL);

        echo json_encode(array('success' => TRUE, 'roles' => $roles));
    }

    /**
     * Edit user roles - AJAX endpoint
     */
    function edit_user_roles() {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $user_id = $this->input->post('user_id');
        $types_roles_id = $this->input->post('types_roles_id');
        $section_id = $this->input->post('section_id');
        $action = $this->input->post('action'); // 'grant' or 'revoke'

        // Log the received parameters for debugging
        log_message('debug', 'edit_user_roles called with: user_id=' . var_export($user_id, true) . ', types_roles_id=' . var_export($types_roles_id, true) . ', section_id=' . var_export($section_id, true) . ', action=' . var_export($action, true));

        if (!$user_id || !$types_roles_id) {
            echo json_encode(array('success' => FALSE, 'message' => 'Missing required parameters: user_id or types_roles_id'));
            return;
        }

        if (!$action || !in_array($action, array('grant', 'revoke'))) {
            echo json_encode(array('success' => FALSE, 'message' => 'Invalid or missing action'));
            return;
        }

        // Validate section_id - it must be provided (0 for cross-section, or specific section ID)
        if ($section_id === '' || $section_id === 'null' || $section_id === NULL || $section_id === 'undefined') {
            echo json_encode(array('success' => FALSE, 'message' => 'Missing required parameter: section_id'));
            return;
        }

        // Convert section_id to integer
        $section_id = (int)$section_id;

        $current_user_id = $this->dx_auth->get_user_id();

        try {
            // Check if the library is loaded
            if (!isset($this->gvv_authorization)) {
                log_message('error', 'Gvv_Authorization library not loaded');
                echo json_encode(array('success' => FALSE, 'message' => 'Authorization library not loaded'));
                return;
            }

        if ($section_id == -1) {
            $this->db->select('id');
            $this->db->from('sections');
            $query = $this->db->get();
            $sections = $query->result_array();

            foreach ($sections as $section) {
                if ($action === 'grant') {
                    $this->gvv_authorization->grant_role($user_id, $types_roles_id, $section['id'], $current_user_id, NULL);
                } else {
                    $this->gvv_authorization->revoke_role($user_id, $types_roles_id, $section['id'], $current_user_id);
                }
            }
            $result = TRUE;
            $message = $action === 'grant' ? 'Roles granted successfully for all sections' : 'Roles revoked successfully for all sections';
        } else {
            if ($action === 'grant') {
                log_message('info', 'edit_user_roles: Attempting grant - user_id=' . $user_id . ', types_roles_id=' . $types_roles_id . ', section_id=' . $section_id . ', current_user_id=' . $current_user_id);
                $result = $this->gvv_authorization->grant_role($user_id, $types_roles_id, $section_id, $current_user_id, NULL);

                if ($result === 'EXISTS') {
                    $message = 'Role already assigned';
                    $result = TRUE; // Not an error, just already exists
                } else if ($result === TRUE) {
                    $message = 'Role granted successfully';
                } else {
                    $message = 'Error granting role';
                }
            } else if ($action === 'revoke') {
                log_message('info', 'edit_user_roles: Attempting revoke - user_id=' . $user_id . ', types_roles_id=' . $types_roles_id . ', section_id=' . $section_id . ', current_user_id=' . $current_user_id);
                $result = $this->gvv_authorization->revoke_role($user_id, $types_roles_id, $section_id, $current_user_id);
                $message = $result ? 'Role revoked successfully' : 'Role not found or already revoked';
            } else {
                $result = FALSE;
                $message = 'Invalid action';
            }
        }

        log_message('debug', 'edit_user_roles result: ' . ($result ? 'success' : 'failure') . ', message: ' . $message);

        } catch (Exception $e) {
            log_message('error', 'edit_user_roles exception: ' . $e->getMessage());
            log_message('error', 'Exception trace: ' . $e->getTraceAsString());
            $result = FALSE;
            $message = 'System error: ' . $e->getMessage();
        }

        $roles = $this->Authorization_model->get_user_roles($user_id, NULL);
        echo json_encode(array('success' => $result, 'message' => $message, 'roles' => $roles));
    }

    /**
     * Manage roles - List all roles
     */
    function roles($message = '') {
        $data = array();
        $data['controller'] = $this->controller;
        $data['title'] = $this->lang->line('authorization_roles');
        $data['message'] = $message;

        // Get all roles
        $data['roles'] = $this->Authorization_model->get_all_roles();

        load_last_view('authorization/roles', $data);
    }

    /**
     * Manage role permissions - List permissions for a role
     */
    function role_permissions($types_roles_id = NULL, $message = '') {
        $data = array();
        $data['controller'] = $this->controller;
        $data['title'] = $this->lang->line('authorization_permissions');
        $data['message'] = $message;

        if ($types_roles_id === NULL) {
            // Show role selector
            $data['roles'] = $this->Authorization_model->get_all_roles();
            load_last_view('authorization/select_role', $data);
            return;
        }

        // Get role details
        $data['role'] = $this->Authorization_model->get_role($types_roles_id);
        if (!$data['role']) {
            show_404();
        }

        // Get permissions for this role
        $data['permissions'] = $this->Authorization_model->get_role_permissions($types_roles_id);

        // Get all controllers for dropdown
        $data['available_controllers'] = $this->_get_available_controllers();

        load_last_view('authorization/role_permissions', $data);
    }

    /**
     * Add permission to role - AJAX endpoint
     */
    function add_permission() {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $types_roles_id = $this->input->post('types_roles_id');
        $controller = $this->input->post('controller');
        $action = $this->input->post('action');
        $section_id = $this->input->post('section_id');
        $permission_type = $this->input->post('permission_type');

        if (!$types_roles_id || !$controller) {
            echo json_encode(array('success' => FALSE, 'message' => 'Missing required parameters'));
            return;
        }

        // NULL action means all actions
        if ($action === '' || $action === 'null') {
            $action = NULL;
        }

        // NULL section_id for global roles
        if ($section_id === '' || $section_id === 'null') {
            $section_id = NULL;
        }

        $result = $this->Authorization_model->add_permission($types_roles_id, $controller, $action, $section_id, $permission_type);
        $message = $result ? 'Permission added successfully' : 'Permission already exists or error occurred';

        echo json_encode(array('success' => $result, 'message' => $message));
    }

    /**
     * Remove permission from role - AJAX endpoint
     */
    function remove_permission() {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $permission_id = $this->input->post('permission_id');

        if (!$permission_id) {
            echo json_encode(array('success' => FALSE, 'message' => 'Missing permission ID'));
            return;
        }

        $result = $this->Authorization_model->remove_permission($permission_id);
        $message = $result ? 'Permission removed successfully' : 'Error removing permission';

        echo json_encode(array('success' => $result, 'message' => $message));
    }

    /**
     * Manage data access rules
     */
    function data_access_rules($types_roles_id = NULL, $message = '') {
        $data = array();
        $data['controller'] = $this->controller;
        $data['title'] = $this->lang->line('authorization_data_access_rules');
        $data['message'] = $message;

        if ($types_roles_id === NULL) {
            // Show role selector
            $data['roles'] = $this->Authorization_model->get_all_roles();
            load_last_view('authorization/select_role_data', $data);
            return;
        }

        // Get role details
        $data['role'] = $this->Authorization_model->get_role($types_roles_id);
        if (!$data['role']) {
            show_404();
        }

        // Get data access rules for this role
        $data['rules'] = $this->Authorization_model->get_data_access_rules($types_roles_id, '*');

        // Get all tables for dropdown
        $data['available_tables'] = $this->_get_available_tables();

        load_last_view('authorization/data_access_rules', $data);
    }

    /**
     * Add data access rule - AJAX endpoint
     */
    function add_data_access_rule() {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $types_roles_id = $this->input->post('types_roles_id');
        $table_name = $this->input->post('table_name');
        $access_scope = $this->input->post('access_scope');
        $field_name = $this->input->post('field_name');
        $section_field = $this->input->post('section_field');
        $description = $this->input->post('description');

        if (!$types_roles_id || !$table_name || !$access_scope) {
            echo json_encode(array('success' => FALSE, 'message' => 'Missing required parameters'));
            return;
        }

        $result = $this->Authorization_model->add_data_access_rule(
            $types_roles_id,
            $table_name,
            $access_scope,
            $field_name,
            $section_field,
            $description
        );

        $message = $result ? 'Data access rule added successfully' : 'Rule already exists or error occurred';

        echo json_encode(array('success' => $result, 'message' => $message));
    }

    /**
     * Remove data access rule - AJAX endpoint
     */
    function remove_data_access_rule() {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $rule_id = $this->input->post('rule_id');

        if (!$rule_id) {
            echo json_encode(array('success' => FALSE, 'message' => 'Missing rule ID'));
            return;
        }

        $result = $this->Authorization_model->remove_data_access_rule($rule_id);
        $message = $result ? 'Data access rule removed successfully' : 'Error removing rule';

        echo json_encode(array('success' => $result, 'message' => $message));
    }

    /**
     * Create a new role
     */
    function create_role() {
        if ($this->input->post()) {
            $nom = $this->input->post('nom');
            $description = $this->input->post('description');
            $scope = $this->input->post('scope');
            $translation_key = $this->input->post('translation_key');
            
            if (!$nom || !$description || !$scope) {
                $data = array();
                $data['controller'] = $this->controller;
                $data['title'] = $this->lang->line('authorization_create_role');
                $data['message'] = 'Missing required fields';
                $data['role'] = array(
                    'nom' => $nom,
                    'description' => $description,
                    'scope' => $scope,
                    'translation_key' => $translation_key,
                );
                load_last_view('authorization/role_form', $data);
                return;
            }
            
            $result = $this->Authorization_model->create_role($nom, $description, $scope, $translation_key);
            
            if ($result) {
                redirect('authorization/roles/Role created successfully');
            } else {
                $data = array();
                $data['controller'] = $this->controller;
                $data['title'] = $this->lang->line('authorization_create_role');
                $data['message'] = 'Error creating role';
                $data['role'] = array(
                    'nom' => $nom,
                    'description' => $description,
                    'scope' => $scope,
                    'translation_key' => $translation_key,
                );
                load_last_view('authorization/role_form', $data);
            }
        } else {
            $data = array();
            $data['controller'] = $this->controller;
            $data['title'] = $this->lang->line('authorization_create_role');
            $data['role'] = array(
                'nom' => '',
                'description' => '',
                'scope' => 'section',
                'translation_key' => '',
            );
            $data['mode'] = 'create';
            load_last_view('authorization/role_form', $data);
        }
    }
    
    /**
     * Edit an existing role
     */
    function edit_role($types_roles_id) {
        $role = $this->Authorization_model->get_role($types_roles_id);
        
        if (!$role) {
            show_404();
        }
        
        if ($role['is_system_role']) {
            redirect('authorization/roles/Cannot edit system roles');
        }
        
        if ($this->input->post()) {
            $nom = $this->input->post('nom');
            $description = $this->input->post('description');
            $scope = $this->input->post('scope');
            $translation_key = $this->input->post('translation_key');
            
            if (!$nom || !$description || !$scope) {
                $data = array();
                $data['controller'] = $this->controller;
                $data['title'] = $this->lang->line('authorization_edit_role');
                $data['message'] = 'Missing required fields';
                $data['role'] = array(
                    'id' => $types_roles_id,
                    'nom' => $nom,
                    'description' => $description,
                    'scope' => $scope,
                    'translation_key' => $translation_key,
                );
                $data['mode'] = 'edit';
                load_last_view('authorization/role_form', $data);
                return;
            }
            
            $result = $this->Authorization_model->update_role($types_roles_id, $nom, $description, $scope, $translation_key);
            
            if ($result) {
                redirect('authorization/roles/Role updated successfully');
            } else {
                $data = array();
                $data['controller'] = $this->controller;
                $data['title'] = $this->lang->line('authorization_edit_role');
                $data['message'] = 'Error updating role';
                $data['role'] = array(
                    'id' => $types_roles_id,
                    'nom' => $nom,
                    'description' => $description,
                    'scope' => $scope,
                    'translation_key' => $translation_key,
                );
                $data['mode'] = 'edit';
                load_last_view('authorization/role_form', $data);
            }
        } else {
            $data = array();
            $data['controller'] = $this->controller;
            $data['title'] = $this->lang->line('authorization_edit_role');
            $data['role'] = $role;
            $data['mode'] = 'edit';
            load_last_view('authorization/role_form', $data);
        }
    }
    
    /**
     * Delete a role
     */
    function delete_role($types_roles_id) {
        $role = $this->Authorization_model->get_role($types_roles_id);
        
        if (!$role) {
            redirect('authorization/roles/Role not found');
        }
        
        if ($role['is_system_role']) {
            redirect('authorization/roles/Cannot delete system roles');
        }
        
        // Check if role is in use
        $users_with_role = $this->Authorization_model->get_users_with_role($types_roles_id, NULL, FALSE);
        if (!empty($users_with_role)) {
            redirect('authorization/roles/Cannot delete role: it is assigned to ' . count($users_with_role) . ' user(s)');
        }
        
        $result = $this->Authorization_model->delete_role($types_roles_id);
        
        if ($result) {
            redirect('authorization/roles/Role deleted successfully');
        } else {
            redirect('authorization/roles/Error deleting role');
        }
    }

    /**
     * View audit log
     */
    function audit_log($page = 0) {
        $data = array();
        $data['controller'] = $this->controller;
        $data['title'] = $this->lang->line('authorization_audit_log');

        $per_page = 50;
        $offset = $page * $per_page;

        // Get filters from session/GET
        $filters = array();
        if ($this->input->get('action_type')) {
            $filters['action_type'] = $this->input->get('action_type');
        }
        if ($this->input->get('user_id')) {
            $filters['target_user_id'] = $this->input->get('user_id');
        }

        // Get audit log entries
        $data['audit_log'] = $this->Authorization_model->get_audit_log($filters, $per_page, $offset);
        $data['page'] = $page;
        $data['per_page'] = $per_page;
        $data['filters'] = $filters;

        load_last_view('authorization/audit_log', $data);
    }

    /**
     * Get list of available controllers
     * @return array
     */
    private function _get_available_controllers() {
        $controllers = array();

        // Scan controller directory
        $controller_path = APPPATH . 'controllers/';
        $files = scandir($controller_path);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            if (is_file($controller_path . $file) && substr($file, -4) === '.php') {
                $controller_name = substr($file, 0, -4);
                $controllers[] = $controller_name;
            }
        }

        sort($controllers);
        return $controllers;
    }

    /**
     * Get list of available tables
     * @return array
     */
    private function _get_available_tables() {
        $query = $this->db->query('SHOW TABLES');
        $tables = array();

        foreach ($query->result_array() as $row) {
            $tables[] = array_values($row)[0];
        }

        sort($tables);
        return $tables;
    }
}
