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
        $data['total_roles'] = count($this->authorization_model->get_all_roles());
        $data['total_users'] = $this->db->count_all('users');

        // Get recent audit log entries
        $data['recent_audits'] = $this->authorization_model->get_audit_log(array(), 10);

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
            $user['roles'] = $this->authorization_model->get_user_roles($user['id'], NULL);
        }

        $data['users'] = $users;
        $data['all_roles'] = $this->authorization_model->get_all_roles();
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
        $roles = $this->authorization_model->get_user_roles($user_id, NULL);

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

        // Check if the role is global or section-scoped
        $role = $this->authorization_model->get_role($types_roles_id);
        if (!$role) {
            echo json_encode(array('success' => FALSE, 'message' => 'Role not found'));
            return;
        }

        if ($section_id == -1) {
            // "Toutes sections" - only applies to section roles, not global roles
            if ($role['scope'] === 'global') {
                echo json_encode(array('success' => FALSE, 'message' => 'Cannot use "Toutes sections" for global roles'));
                return;
            }

            $this->db->select('id');
            $this->db->from('sections');
            $this->db->where('id !=', 0); // Exclude the dummy section 0
            $this->db->where('id !=', 89); // Exclude the "Toutes sections" meta-section
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
            // Special handling for global roles
            if ($role['scope'] === 'global') {
                // For global roles, section_id should be NULL (not 0)
                // Frontend sends 0, so convert it to NULL
                $global_section_id = NULL;

                if ($action === 'grant') {
                    // For global roles, grant with section_id = NULL
                    // But check if user already has it in ANY section
                    $existing_roles = $this->authorization_model->get_user_roles($user_id, NULL, TRUE);
                    $has_role = false;
                    foreach ($existing_roles as $existing_role) {
                        if ($existing_role['types_roles_id'] == $types_roles_id) {
                            $has_role = true;
                            break;
                        }
                    }

                    if ($has_role) {
                        $result = TRUE;
                        $message = 'Role already assigned';
                    } else {
                        log_message('info', 'edit_user_roles: Granting global role - user_id=' . $user_id . ', types_roles_id=' . $types_roles_id);
                        $result = $this->gvv_authorization->grant_role($user_id, $types_roles_id, $global_section_id, $current_user_id, NULL);

                        if ($result === 'EXISTS') {
                            $message = 'Role already assigned';
                            $result = TRUE;
                        } else if ($result === TRUE) {
                            $message = 'Role granted successfully';
                        } else {
                            $message = 'Error granting role';
                        }
                    }
                } else if ($action === 'revoke') {
                    // For global roles, revoke from ALL sections where user has it
                    log_message('info', 'edit_user_roles: Revoking global role from all sections - user_id=' . $user_id . ', types_roles_id=' . $types_roles_id);
                    $existing_roles = $this->authorization_model->get_user_roles($user_id, NULL, TRUE);
                    $revoked_count = 0;
                    foreach ($existing_roles as $existing_role) {
                        if ($existing_role['types_roles_id'] == $types_roles_id) {
                            if ($this->gvv_authorization->revoke_role($user_id, $types_roles_id, $existing_role['section_id'], $current_user_id)) {
                                $revoked_count++;
                            }
                        }
                    }
                    $result = $revoked_count > 0;
                    $message = $result ? "Role revoked successfully ($revoked_count assignment(s))" : 'Role not found or already revoked';
                } else {
                    $result = FALSE;
                    $message = 'Invalid action';
                }
            } else {
                // Normal section-scoped role handling
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
        }

        log_message('debug', 'edit_user_roles result: ' . ($result ? 'success' : 'failure') . ', message: ' . $message);

        } catch (Exception $e) {
            log_message('error', 'edit_user_roles exception: ' . $e->getMessage());
            log_message('error', 'Exception trace: ' . $e->getTraceAsString());
            $result = FALSE;
            $message = 'System error: ' . $e->getMessage();
        }

        $roles = $this->authorization_model->get_user_roles($user_id, NULL);
        echo json_encode(array('success' => $result, 'message' => $message, 'roles' => $roles));
    }

    /**
     * Manage roles - List all roles
     */
    function roles($message = '') {
        $data = array();
        $data['controller'] = $this->controller;
        $data['title'] = $this->lang->line('authorization_roles');
        $data['message'] = $message ? urldecode($message) : '';

        // Get all roles
        $data['roles'] = $this->authorization_model->get_all_roles();

        load_last_view('authorization/roles', $data);
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
            $data['roles'] = $this->authorization_model->get_all_roles();
            load_last_view('authorization/select_role_data', $data);
            return;
        }

        // Get role details
        $data['role'] = $this->authorization_model->get_role($types_roles_id);
        if (!$data['role']) {
            show_404();
        }

        // Get data access rules for this role
        $data['rules'] = $this->authorization_model->get_data_access_rules($types_roles_id, '*');

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

        $result = $this->authorization_model->add_data_access_rule(
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

        $result = $this->authorization_model->remove_data_access_rule($rule_id);
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
                $data['message'] = $this->lang->line('authorization_missing_required_fields');
                $data['role'] = array(
                    'nom' => $nom,
                    'description' => $description,
                    'scope' => $scope,
                    'translation_key' => $translation_key,
                );
                load_last_view('authorization/role_form', $data);
                return;
            }
            
            $result = $this->authorization_model->create_role($nom, $description, $scope, $translation_key);
            
            if ($result) {
                redirect('authorization/roles/' . $this->lang->line('authorization_role_created'));
            } else {
                $data = array();
                $data['controller'] = $this->controller;
                $data['title'] = $this->lang->line('authorization_create_role');
                $data['message'] = $this->lang->line('authorization_error_creating_role');
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
        $role = $this->authorization_model->get_role($types_roles_id);
        
        if (!$role) {
            show_404();
        }
        
        if ($role['is_system_role']) {
            redirect('authorization/roles/' . $this->lang->line('authorization_cannot_edit_system_role'));
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
                $data['message'] = $this->lang->line('authorization_missing_required_fields');
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
            
            $result = $this->authorization_model->update_role($types_roles_id, $nom, $description, $scope, $translation_key);
            
            if ($result) {
                redirect('authorization/roles/' . $this->lang->line('authorization_role_updated'));
            } else {
                $data = array();
                $data['controller'] = $this->controller;
                $data['title'] = $this->lang->line('authorization_edit_role');
                $data['message'] = $this->lang->line('authorization_error_updating_role');
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
        $role = $this->authorization_model->get_role($types_roles_id);

        if (!$role) {
            redirect('authorization/roles/' . $this->lang->line('authorization_role_not_found'));
        }

        if ($role['is_system_role']) {
            redirect('authorization/roles/' . $this->lang->line('authorization_cannot_delete_system_role'));
        }

        // Check if role is in use
        $users_with_role = $this->authorization_model->get_users_with_role($types_roles_id, NULL, FALSE);
        if (!empty($users_with_role)) {
            $message = sprintf($this->lang->line('authorization_cannot_delete_role_in_use'), count($users_with_role));
            redirect('authorization/roles/' . $message);
        }

        $result = $this->authorization_model->delete_role($types_roles_id);
        
        if ($result) {
            redirect('authorization/roles/' . $this->lang->line('authorization_role_deleted'));
        } else {
            redirect('authorization/roles/' . $this->lang->line('authorization_error_deleting_role'));
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
        $data['audit_log'] = $this->authorization_model->get_audit_log($filters, $per_page, $offset);
        $data['page'] = $page;
        $data['per_page'] = $per_page;
        $data['filters'] = $filters;

        load_last_view('authorization/audit_log', $data);
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

    /**
     * ========================================================================
     * MIGRATION DASHBOARD METHODS (Phase 6)
     * ========================================================================
     */

    /**
     * Migration Dashboard - Main Overview
     *
     * Displays migration status summary, pilot users, and alerts.
     * Tab 1 of the migration dashboard.
     */
    public function migration() {
        $this->load->model('authorization_model');

        // Get migration statistics
        $data['total_users'] = $this->db->count_all('users');

        // Count users by migration status
        $data['migrated_count'] = $this->db
            ->where('use_new_system', 1)
            ->where('migration_status', 'completed')
            ->count_all_results('authorization_migration_status');

        $data['in_progress_count'] = $this->db
            ->where('use_new_system', 1)
            ->where('migration_status', 'in_progress')
            ->count_all_results('authorization_migration_status');

        // Get pilot users status
        $data['pilot_users'] = $this->_get_pilot_users_summary();

        // Get recent alerts (authorization mismatches)
        $data['recent_alerts'] = $this->_get_recent_alerts();

        // Calculate migration progress
        $data['progress_percentage'] = $data['total_users'] > 0
            ? round(($data['migrated_count'] / $data['total_users']) * 100, 1)
            : 0;

        $this->load->view('authorization/migration/overview', $data);
    }

    /**
     * Migration Dashboard - Pilot Users Management
     *
     * Displays and manages pilot user migrations.
     * Tab 2 of the migration dashboard.
     */
    public function migration_pilot_users() {
        $this->load->model('authorization_model');

        // Get pilot users with detailed status
        $data['pilot_users'] = $this->_get_pilot_users_detailed();

        $this->load->view('authorization/migration/pilot_users', $data);
    }

    /**
     * Migration Dashboard - Comparison Log
     *
     * Displays authorization comparison log showing discrepancies
     * between old and new systems.
     * Tab 3 of the migration dashboard.
     */
    public function migration_comparison_log() {
        // Get filter parameters
        $user_id = $this->input->get('user_id');
        $controller = $this->input->get('controller');
        $action = $this->input->get('action');
        $date_from = $this->input->get('date_from');
        $date_to = $this->input->get('date_to');
        $mismatches_only = $this->input->get('mismatches_only') === '1';

        // Build query
        $this->db->select('acl.*, u.username, u.email')
            ->from('authorization_comparison_log acl')
            ->join('users u', 'acl.user_id = u.id', 'left')
            ->order_by('acl.created_at', 'DESC');

        // Apply filters
        if ($user_id) {
            $this->db->where('acl.user_id', $user_id);
        }
        if ($controller) {
            $this->db->like('acl.controller', $controller);
        }
        if ($action) {
            $this->db->like('acl.action', $action);
        }
        if ($date_from) {
            $this->db->where('acl.created_at >=', $date_from . ' 00:00:00');
        }
        if ($date_to) {
            $this->db->where('acl.created_at <=', $date_to . ' 23:59:59');
        }
        if ($mismatches_only) {
            $this->db->where('acl.new_system_result !=', 'acl.legacy_system_result', FALSE);
        }

        $data['comparison_logs'] = $this->db->get()->result_array();

        // Get pilot users for filter dropdown
        $data['pilot_users'] = $this->_get_pilot_users_summary();

        // Count mismatches in last 24 hours
        $data['recent_mismatches'] = $this->db
            ->where('new_system_result !=', 'legacy_system_result', FALSE)
            ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-24 hours')))
            ->count_all_results('authorization_comparison_log');

        $this->load->view('authorization/migration/comparison_log', $data);
    }

    /**
     * Migration Dashboard - Statistics
     *
     * Displays migration statistics, charts, and metrics.
     * Tab 4 of the migration dashboard.
     */
    public function migration_statistics() {
        $this->load->model('authorization_model');

        // Get wave progress
        $data['wave_progress'] = $this->_get_wave_progress();

        // Get comparison statistics (last 7 days)
        $data['comparison_stats'] = $this->_get_comparison_statistics(7);

        // Get top controllers with divergences
        $data['top_divergences'] = $this->_get_top_divergences(5);

        // Calculate concordance rate
        $total_comparisons = $this->db
            ->where('created_at >=', date('Y-m-d', strtotime('-7 days')))
            ->count_all_results('authorization_comparison_log');

        $divergences = $this->db
            ->where('new_system_result !=', 'legacy_system_result', FALSE)
            ->where('created_at >=', date('Y-m-d', strtotime('-7 days')))
            ->count_all_results('authorization_comparison_log');

        $data['total_comparisons'] = $total_comparisons;
        $data['total_divergences'] = $divergences;
        $data['concordance_rate'] = $total_comparisons > 0
            ? round((($total_comparisons - $divergences) / $total_comparisons) * 100, 1)
            : 100;

        $this->load->view('authorization/migration/statistics', $data);
    }

    /**
     * AJAX: Migrate a pilot user to new system
     *
     * Handles the migration wizard workflow.
     */
    public function ajax_migrate_user() {
        header('Content-Type: application/json');

        $user_id = $this->input->post('user_id');
        $notes = $this->input->post('notes');
        $current_user_id = $this->dx_auth->get_user_id();

        if (!$user_id) {
            echo json_encode(array('success' => false, 'message' => 'User ID required'));
            return;
        }

        $this->load->model('authorization_model');

        try {
            // Backup old permissions (from DX_Auth)
            $old_permissions = $this->_backup_user_permissions($user_id);

            // Set migration status to in_progress
            $result = $this->authorization_model->set_migration_status(
                $user_id,
                'in_progress',
                TRUE, // use_new_system
                $current_user_id
            );

            if ($result) {
                // Update notes if provided
                if ($notes) {
                    $this->db->where('user_id', $user_id)
                        ->update('authorization_migration_status', array('notes' => $notes));
                }

                echo json_encode(array(
                    'success' => true,
                    'message' => 'User migrated successfully',
                    'user_id' => $user_id,
                    'migrated_at' => date('Y-m-d H:i:s')
                ));
            } else {
                echo json_encode(array('success' => false, 'message' => 'Migration failed'));
            }
        } catch (Exception $e) {
            log_message('error', 'Migration error: ' . $e->getMessage());
            echo json_encode(array('success' => false, 'message' => $e->getMessage()));
        }
    }

    /**
     * AJAX: Rollback a user migration
     *
     * Reverts user to legacy authorization system.
     */
    public function ajax_rollback_user() {
        header('Content-Type: application/json');

        $user_id = $this->input->post('user_id');
        $reason = $this->input->post('reason');
        $current_user_id = $this->dx_auth->get_user_id();

        if (!$user_id || !$reason) {
            echo json_encode(array('success' => false, 'message' => 'User ID and reason required'));
            return;
        }

        $this->load->model('authorization_model');

        try {
            // Set migration status to failed and disable new system
            $result = $this->authorization_model->set_migration_status(
                $user_id,
                'failed',
                FALSE, // use_new_system = 0
                $current_user_id
            );

            if ($result) {
                // Update notes with rollback reason
                $this->db->where('user_id', $user_id)
                    ->update('authorization_migration_status', array(
                        'notes' => 'ROLLBACK: ' . $reason
                    ));

                echo json_encode(array(
                    'success' => true,
                    'message' => 'User rolled back successfully',
                    'user_id' => $user_id
                ));
            } else {
                echo json_encode(array('success' => false, 'message' => 'Rollback failed'));
            }
        } catch (Exception $e) {
            log_message('error', 'Rollback error: ' . $e->getMessage());
            echo json_encode(array('success' => false, 'message' => $e->getMessage()));
        }
    }

    /**
     * AJAX: Complete a user migration
     *
     * Marks migration as completed after validation period.
     */
    public function ajax_complete_migration() {
        header('Content-Type: application/json');

        $user_id = $this->input->post('user_id');
        $current_user_id = $this->dx_auth->get_user_id();

        if (!$user_id) {
            echo json_encode(array('success' => false, 'message' => 'User ID required'));
            return;
        }

        $this->load->model('authorization_model');

        try {
            // Set migration status to completed
            $result = $this->authorization_model->set_migration_status(
                $user_id,
                'completed',
                TRUE, // keep use_new_system = 1
                $current_user_id
            );

            if ($result) {
                echo json_encode(array(
                    'success' => true,
                    'message' => 'Migration completed successfully',
                    'user_id' => $user_id,
                    'completed_at' => date('Y-m-d H:i:s')
                ));
            } else {
                echo json_encode(array('success' => false, 'message' => 'Completion failed'));
            }
        } catch (Exception $e) {
            log_message('error', 'Migration completion error: ' . $e->getMessage());
            echo json_encode(array('success' => false, 'message' => $e->getMessage()));
        }
    }

    /**
     * Helper: Get pilot users summary
     * @return array
     */
    private function _get_pilot_users_summary() {
        // Pilot users from bin/create_test_users.sh
        $pilot_usernames = array('testuser', 'testplanchiste', 'testadmin', 'testca', 'testbureau', 'testtresorier');

        $this->db->select('u.id, u.username, u.email, ams.migration_status, ams.use_new_system, ams.migrated_at')
            ->from('users u')
            ->join('authorization_migration_status ams', 'u.id = ams.user_id', 'left')
            ->where_in('u.username', $pilot_usernames);

        return $this->db->get()->result_array();
    }

    /**
     * Helper: Get detailed pilot users information
     * @return array
     */
    private function _get_pilot_users_detailed() {
        $pilot_users = $this->_get_pilot_users_summary();

        // Enrich with role information
        foreach ($pilot_users as &$user) {
            // Get user roles from DX_Auth (legacy)
            $user['legacy_roles'] = $this->_get_user_legacy_roles($user['id']);

            // Get user roles from new system
            $this->load->model('authorization_model');
            $user['new_roles'] = $this->authorization_model->get_user_roles($user['id']);
        }

        return $pilot_users;
    }

    /**
     * Helper: Get recent alerts (mismatches)
     * @return array
     */
    private function _get_recent_alerts() {
        return $this->db
            ->select('acl.*, u.username')
            ->from('authorization_comparison_log acl')
            ->join('users u', 'acl.user_id = u.id', 'left')
            ->where('acl.new_system_result !=', 'acl.legacy_system_result', FALSE)
            ->where('acl.created_at >=', date('Y-m-d H:i:s', strtotime('-24 hours')))
            ->order_by('acl.created_at', 'DESC')
            ->limit(10)
            ->get()
            ->result_array();
    }

    /**
     * Helper: Get wave progress for pilot users
     * @return array
     */
    private function _get_wave_progress() {
        $waves = array(
            'wave1' => array('testuser'),
            'wave2' => array('testplanchiste'),
            'wave3' => array('testadmin')
        );

        $progress = array();
        foreach ($waves as $wave_name => $usernames) {
            $progress[$wave_name] = $this->db
                ->select('u.username, ams.migration_status, ams.migrated_at')
                ->from('users u')
                ->join('authorization_migration_status ams', 'u.id = ams.user_id', 'left')
                ->where_in('u.username', $usernames)
                ->get()
                ->row_array();
        }

        return $progress;
    }

    /**
     * Helper: Get comparison statistics for last N days
     * @param int $days Number of days to analyze
     * @return array
     */
    private function _get_comparison_statistics($days = 7) {
        $stats = array();
        $start_date = date('Y-m-d', strtotime("-{$days} days"));

        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', strtotime("-{$i} days"));

            $total = $this->db
                ->where('DATE(created_at)', $date)
                ->count_all_results('authorization_comparison_log');

            $divergences = $this->db
                ->where('DATE(created_at)', $date)
                ->where('new_system_result !=', 'legacy_system_result', FALSE)
                ->count_all_results('authorization_comparison_log');

            $stats[] = array(
                'date' => $date,
                'total' => $total,
                'divergences' => $divergences
            );
        }

        return array_reverse($stats);
    }

    /**
     * Helper: Get top N controllers with most divergences
     * @param int $limit Number of results
     * @return array
     */
    private function _get_top_divergences($limit = 5) {
        return $this->db
            ->select('controller, COUNT(*) as divergence_count')
            ->from('authorization_comparison_log')
            ->where('new_system_result !=', 'legacy_system_result', FALSE)
            ->group_by('controller')
            ->order_by('divergence_count', 'DESC')
            ->limit($limit)
            ->get()
            ->result_array();
    }

    /**
     * Helper: Backup user permissions from legacy system
     * @param int $user_id
     * @return string JSON encoded permissions
     */
    private function _backup_user_permissions($user_id) {
        // Get user's DX_Auth data
        $user_data = $this->db
            ->select('role_id, banned')
            ->from('users')
            ->where('id', $user_id)
            ->get()
            ->row_array();

        $backup = array(
            'role_id' => $user_data['role_id'],
            'banned' => $user_data['banned'],
            'backed_up_at' => date('Y-m-d H:i:s')
        );

        // Store backup in migration table
        $this->db->where('user_id', $user_id)
            ->update('authorization_migration_status', array(
                'old_permissions' => json_encode($backup)
            ));

        return json_encode($backup);
    }

    /**
     * Helper: Get user's legacy roles from DX_Auth
     * @param int $user_id
     * @return array
     */
    private function _get_user_legacy_roles($user_id) {
        return $this->db
            ->select('r.name, r.id')
            ->from('roles r')
            ->join('users u', 'u.role_id = r.id', 'inner')
            ->where('u.id', $user_id)
            ->get()
            ->result_array();
    }
}
