<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * GVV Gestion vol à voile
 * Copyright (C) 2011 Philippe Boissel & Frédéric Peignot
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @filesource email_lists.php
 * @package controllers
 * Controller for email distribution lists management
 *
 * @see doc/design_notes/gestion_emails_design.md
 */

include_once(APPPATH . '/libraries/Gvv_Controller.php');

class Email_lists extends Gvv_Controller
{
    protected $controller = 'email_lists';
    protected $model = 'email_lists_model';
    protected $modification_level = 'secretaire'; // Legacy authorization for non-migrated users
    protected $use_new_auth = FALSE; // Use legacy authorization system
    protected $rules = array();
    protected $filter_variables = array();

    /**
     * Constructor
     */
    function __construct()
    {
        parent::__construct();

        // Authorization: Code-based (v2.0) - only for migrated users
        if ($this->use_new_auth) {
            $this->require_roles(['secretaire', 'ca']);
        }

        $this->load->model('email_lists_model');
        $this->load->helper('email');
        $this->load->library('form_validation');
        $this->lang->load('email_lists');
        $this->lang->load('gvv');
    }

    /**
     * Index - Display all email lists
     */
    public function index()
    {
        $data['title'] = $this->lang->line('email_lists_title');
        $data['controller'] = $this->controller;

        // Get all lists (or user's lists depending on permissions)
        $user_id = $this->dx_auth->get_user_id();
        $data['lists'] = $this->email_lists_model->get_user_lists($user_id);

        // Add recipient count for each list
        foreach ($data['lists'] as &$list) {
            $list['recipient_count'] = $this->email_lists_model->count_members($list['id']);
        }

        return load_last_view('email_lists/index', $data, $this->unit_test);
    }

    /**
     * Create - Display simple form for creating new list
     * Workflow v1.4 SIMPLIFIED: Just metadata, no JavaScript
     */
    public function create()
    {
        log_message('debug', 'EMAIL_LISTS: create() method called');

        $data['title'] = $this->lang->line('email_lists_create');

        return load_last_view('email_lists/create', $data, $this->unit_test);
    }

    /**
     * Store - Save new list (metadata only)
     * Workflow v1.4: Create list with metadata, then redirect to edit() for addresses
     */
    public function store()
    {
        // DEBUG: Log that we entered store()
        log_message('debug', 'EMAIL_LISTS: store() method called');
        log_message('debug', 'EMAIL_LISTS: POST data: ' . print_r($_POST, TRUE));

        // Validate input (metadata only)
        $this->form_validation->set_rules('name', $this->lang->line('email_lists_name'), 'required|max_length[255]|callback_check_name_unique');
        $this->form_validation->set_rules('description', $this->lang->line('email_lists_description'), 'max_length[1000]');
        $this->form_validation->set_rules('active_member', $this->lang->line('email_lists_active_member'), 'required|in_list[active,inactive,all]');

        if ($this->form_validation->run() === FALSE) {
            // Validation failed - redisplay form
            return $this->create();
        }

        // Create the list with metadata only
        $user_id = $this->dx_auth->get_user_id();
        $list_data = array(
            'name' => $this->input->post('name'),
            'description' => $this->input->post('description'),
            'active_member' => $this->input->post('active_member'),
            'visible' => $this->input->post('visible') ? 1 : 0,
            'created_by' => $user_id
        );

        $list_id = $this->email_lists_model->create_list($list_data);

        if (!$list_id) {
            $this->session->set_flashdata('error', $this->lang->line('email_lists_create_error'));
            return $this->create();
        }

        // Success - redirect to edit mode where address section is enabled
        $this->session->set_flashdata('success', $this->lang->line('email_lists_create_success'));
        redirect('email_lists/edit/' . $list_id);
    }

    /**
     * View - Display list details and export options
     *
     * @param int $id List ID
     */
    public function view($id)
    {
        $data['title'] = $this->lang->line('email_lists_view');
        $data['controller'] = $this->controller;

        $list = $this->email_lists_model->get_list($id);
        if (!$list) {
            show_404();
        }

        $data['list'] = $list;
        $data['emails'] = $this->email_lists_model->detailed_list($id);
        $data['recipient_count'] = count($data['emails']);

        // Get detailed sources
        $data['roles'] = $this->email_lists_model->get_list_roles($id);
        $data['manual_members'] = $this->email_lists_model->get_manual_members($id);
        $data['external_emails'] = $this->email_lists_model->get_external_emails($id);

        return load_last_view('email_lists/view', $data, $this->unit_test);
    }

    /**
     * Edit - Display form for editing existing list
     * Workflow v1.4: Both metadata and address sections enabled
     *
     * @param int $id List ID
     * @param bool $load_view Whether to load the view (default true)
     * @param string $action Action type (default MODIFICATION)
     */
    public function edit($id = '', $load_view = true, $action = MODIFICATION)
    {
        if (empty($id)) {
            show_404();
        }

        $list = $this->email_lists_model->get_list($id);
        if (!$list) {
            show_404();
        }
        
        log_message('debug', 'EMAIL_LISTS: edit() called for id=' . $id);
        
        // If validation errors exist, show them
        if (validation_errors()) {
            log_message('debug', 'EMAIL_LISTS: edit() has validation errors');
        }

        $data['title'] = $this->lang->line('email_lists_edit');
        $data['controller'] = $this->controller;
        $data['action'] = 'update';
        $data['list'] = $list;
        $data['email_list_id'] = $id; // ID known - address section will be enabled

        // Load available roles and sections for criteria tab
        $data['available_roles'] = $this->email_lists_model->get_available_roles();
        $data['available_sections'] = $this->email_lists_model->get_available_sections();

        // Load current list configuration
        $data['current_roles'] = $this->email_lists_model->get_list_roles($id);
        $data['current_manual_members'] = $this->email_lists_model->get_manual_members($id);
        $data['current_external_emails'] = $this->email_lists_model->get_external_emails($id);
        $data['uploaded_files'] = $this->email_lists_model->get_uploaded_files($id);

        // Load all members for manual selection
        $this->load->model('membres_model');
        $data['available_members'] = $this->membres_model->selector(array('actif' => 1));

        return load_last_view('email_lists/form', $data, $this->unit_test);
    }

    /**
     * Update - Save changes to existing list
     *
     * @param int $id List ID
     */
    public function update($id)
    {
        log_message('debug', 'EMAIL_LISTS: update() called with id=' . $id);
        
        $list = $this->email_lists_model->get_list($id);
        if (!$list) {
            log_message('debug', 'EMAIL_LISTS: list not found');
            show_404();
        }
        
        log_message('debug', 'EMAIL_LISTS: list found, name=' . $list['name']);

        // Store the list ID for validation callback
        $this->_update_list_id = $id;

        // Validate input
        $this->form_validation->set_rules('name', $this->lang->line('email_lists_name'), 'required|max_length[255]|callback_check_name_unique');
        $this->form_validation->set_rules('description', $this->lang->line('email_lists_description'), 'max_length[1000]');
        $this->form_validation->set_rules('active_member', $this->lang->line('email_lists_active_member'), 'required|in_list[active,inactive,all]');
        
        log_message('debug', 'EMAIL_LISTS: validation rules set');

        if ($this->form_validation->run() === FALSE) {
            log_message('debug', 'EMAIL_LISTS: validation failed, returning to edit form');
            return $this->edit($id);
        }
        
        log_message('debug', 'EMAIL_LISTS: validation passed');

        // Update the list
        $list_data = array(
            'name' => $this->input->post('name'),
            'description' => $this->input->post('description'),
            'active_member' => $this->input->post('active_member'),
            'visible' => $this->input->post('visible') ? 1 : 0
        );

        $success = $this->email_lists_model->update_list($id, $list_data);

        if (!$success) {
            $this->session->set_flashdata('error', $this->lang->line('email_lists_update_error'));
            return $this->edit($id);
        }

        // Process manual members
        $manual_members = $this->input->post('manual_members');
        if (is_array($manual_members)) {
            // Get current manual members
            $current_members = $this->email_lists_model->get_manual_members($id);
            $current_member_ids = array_column($current_members, 'membre_id');

            // Remove members that are no longer selected
            foreach ($current_member_ids as $member_id) {
                if (!in_array($member_id, $manual_members)) {
                    $this->email_lists_model->remove_manual_member($id, $member_id);
                }
            }

            // Add new members
            foreach ($manual_members as $member_id) {
                if (!in_array($member_id, $current_member_ids)) {
                    $this->email_lists_model->add_manual_member($id, $member_id);
                }
            }
        } else {
            // No manual members selected - remove all
            $current_members = $this->email_lists_model->get_manual_members($id);
            foreach ($current_members as $member) {
                $this->email_lists_model->remove_manual_member($id, $member['membre_id']);
            }
        }

        // Process external emails
        $external_emails = $this->input->post('external_emails');
        $external_names = $this->input->post('external_names');

        // DEBUG: Log what we received
        log_message('debug', 'EMAIL_LISTS UPDATE: external_emails = ' . print_r($external_emails, TRUE));
        log_message('debug', 'EMAIL_LISTS UPDATE: external_names = ' . print_r($external_names, TRUE));

        if (is_array($external_emails)) {
            // Get current external emails
            $current_external = $this->email_lists_model->get_external_emails($id);
            $current_external_emails = array_column($current_external, 'external_email');

            // Remove emails that are no longer in the list
            foreach ($current_external as $ext) {
                if (!in_array($ext['external_email'], $external_emails)) {
                    $this->email_lists_model->remove_external_email($id, $ext['external_email']);
                }
            }

            // Add new emails
            foreach ($external_emails as $index => $email) {
                if (!in_array($email, $current_external_emails)) {
                    $name = isset($external_names[$index]) ? $external_names[$index] : '';
                    log_message('debug', "EMAIL_LISTS UPDATE: Adding external email: $email, name: $name");
                    $result = $this->email_lists_model->add_external_email($id, $email, $name);
                    log_message('debug', "EMAIL_LISTS UPDATE: Add result: " . ($result ? $result : 'FALSE'));
                }
            }
        } else {
            // No external emails - remove all
            $current_external = $this->email_lists_model->get_external_emails($id);
            foreach ($current_external as $ext) {
                $this->email_lists_model->remove_external_email($id, $ext['external_email']);
            }
        }

        $this->session->set_flashdata('success', $this->lang->line('email_lists_update_success'));
        redirect('email_lists/edit/' . $id);
    }

    /**
     * Validation callback - Check if list name is unique
     *
     * @param string $name List name to validate
     * @return bool TRUE if unique, FALSE if duplicate
     */
    public function check_name_unique($name)
    {
        log_message('debug', 'EMAIL_LISTS: check_name_unique called with name=' . $name);
        
        // Check if updating (exclude current list ID)
        $exclude_id = isset($this->_update_list_id) ? $this->_update_list_id : NULL;
        
        log_message('debug', 'EMAIL_LISTS: exclude_id=' . ($exclude_id ? $exclude_id : 'NULL'));
        
        $exists = $this->email_lists_model->name_exists($name, $exclude_id);
        
        log_message('debug', 'EMAIL_LISTS: name_exists returned ' . ($exists ? 'TRUE' : 'FALSE'));
        
        if ($exists) {
            $this->form_validation->set_message('check_name_unique', $this->lang->line('email_lists_name_exists'));
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Delete - Remove a list and all its associations
     */

    /**
     * Delete - Remove a list
     *
     * @param int $id List ID
     */
    public function delete($id)
    {
        $list = $this->email_lists_model->get_list($id);
        if (!$list) {
            show_404();
        }

        $success = $this->email_lists_model->delete_list($id);

        if ($success) {
            $this->session->set_flashdata('success', $this->lang->line('email_lists_delete_success'));
        } else {
            $this->session->set_flashdata('error', $this->lang->line('email_lists_delete_error'));
        }

        redirect('email_lists');
    }

    /**
     * AJAX: Add external email to list
     * Called when user clicks "Ajouter une adresse"
     */
    public function add_external_ajax()
    {
        header('Content-Type: application/json');

        $list_id = $this->input->post('list_id');
        $email = $this->input->post('email');
        $name = $this->input->post('name');

        if (empty($list_id) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }

        // Verify list exists
        $list = $this->email_lists_model->get_list($list_id);
        if (!$list) {
            echo json_encode(['success' => false, 'message' => 'List not found']);
            return;
        }

        $result = $this->email_lists_model->add_external_email($list_id, $email, $name);

        if ($result) {
            echo json_encode(['success' => true, 'id' => $result, 'message' => 'Email added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add email']);
        }
    }

    /**
     * AJAX: Remove external email from list
     * Called when user clicks delete button
     */
    public function remove_external_ajax()
    {
        header('Content-Type: application/json');

        $list_id = $this->input->post('list_id');
        $email = $this->input->post('email');

        if (empty($list_id) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }

        $result = $this->email_lists_model->remove_external_email($list_id, $email);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Email removed successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove email']);
        }
    }

    /**
     * AJAX: Add manual member to list
     * Called when user clicks "Ajouter un membre"
     */
    public function add_manual_member_ajax()
    {
        header('Content-Type: application/json');

        $list_id = $this->input->post('list_id');
        $membre_id = $this->input->post('membre_id');

        if (empty($list_id) || empty($membre_id)) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }

        // Verify list exists
        $list = $this->email_lists_model->get_list($list_id);
        if (!$list) {
            echo json_encode(['success' => false, 'message' => 'List not found']);
            return;
        }

        $result = $this->email_lists_model->add_manual_member($list_id, $membre_id);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Member added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add member']);
        }
    }

    /**
     * AJAX: Remove manual member from list
     * Called when user clicks delete button
     */
    public function remove_manual_member_ajax()
    {
        header('Content-Type: application/json');

        $list_id = $this->input->post('list_id');
        $membre_id = $this->input->post('membre_id');

        if (empty($list_id) || empty($membre_id)) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }

        $result = $this->email_lists_model->remove_manual_member($list_id, $membre_id);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Member removed successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove member']);
        }
    }

    /**
     * AJAX: Add role criteria to list
     * Called when user checks a role checkbox
     */
    public function add_role_ajax()
    {
        header('Content-Type: application/json');

        $list_id = $this->input->post('list_id');
        $role_value = $this->input->post('role_value'); // Format: "role_id_section_id"

        if (empty($list_id) || empty($role_value)) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }

        // Parse role_id and section_id
        $parts = explode('_', $role_value);
        if (count($parts) != 2) {
            echo json_encode(['success' => false, 'message' => 'Invalid role format']);
            return;
        }

        $role_id = (int)$parts[0];
        $section_id = (int)$parts[1];
        $section_id = ($section_id === 0) ? NULL : $section_id;

        $result = $this->email_lists_model->add_role($list_id, $role_id, $section_id);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Role added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add role']);
        }
    }

    /**
     * AJAX: Remove role criteria from list
     * Called when user unchecks a role checkbox
     */
    public function remove_role_ajax()
    {
        header('Content-Type: application/json');

        $list_id = $this->input->post('list_id');
        $role_value = $this->input->post('role_value'); // Format: "role_id_section_id"

        if (empty($list_id) || empty($role_value)) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }

        // Parse role_id and section_id
        $parts = explode('_', $role_value);
        if (count($parts) != 2) {
            echo json_encode(['success' => false, 'message' => 'Invalid role format']);
            return;
        }

        $role_id = (int)$parts[0];
        $section_id = (int)$parts[1];
        $section_id = ($section_id === 0) ? NULL : $section_id;

        $result = $this->email_lists_model->remove_role($list_id, $role_id, $section_id);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Role removed successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove role']);
        }
    }

    /**
     * Addresses - Interface to get email addresses from lists
     * Similar to mails/addresses but using email_lists
     *
     * @param int $email_list_id Optional list ID to preselect and load addresses
     */
    public function addresses($email_list_id = NULL)
    {
        $data['controller'] = $this->controller;
        $data['action'] = 'addresses';

        // Get all visible email lists for selection dropdown
        $visible_lists = $this->email_lists_model->get_visible_lists();
        $data['selection'] = array();
        foreach ($visible_lists as $list) {
            $data['selection'][$list['id']] = $list['name'];
        }

        // Default values
        $data['selected_list'] = '';
        $data['email_addresses'] = '';
        $data['subject'] = '';
        $data['send_to_self'] = false;

        // If list_id provided, preload the addresses
        if ($email_list_id) {
            $list = $this->email_lists_model->get_list($email_list_id);
            if ($list) {
                $data['selected_list'] = $email_list_id;

                // Get email addresses
                $email_array = $this->email_lists_model->textual_list($email_list_id);
                $data['email_addresses'] = is_array($email_array) ? implode(', ', $email_array) : '';
                $data['address_count'] = is_array($email_array) ? count($email_array) : 0;
            }
        }

        return load_last_view('email_lists/addresses', $data, $this->unit_test);
    }

    /**
     * AJAX: Get email addresses for the selected list
     */
    public function ajax_get_addresses()
    {
        header('Content-Type: application/json');

        $list_id = $this->input->post('list_id');

        if (empty($list_id)) {
            echo json_encode(['addresses' => '', 'count' => 0]);
            return;
        }

        // Get the email list from the model - returns array of email strings
        $email_array = $this->email_lists_model->textual_list($list_id);

        // Convert array to comma-separated string
        $emails = is_array($email_array) ? implode(', ', $email_array) : '';

        // Count emails
        $count = is_array($email_array) ? count($email_array) : 0;

        echo json_encode([
            'addresses' => $emails,
            'count' => $count
        ]);
    }

    /**
     * Download TXT export
     *
     * @param int $id List ID
     */
    public function download_txt($id)
    {
        $list = $this->email_lists_model->get_list($id);
        if (!$list) {
            show_404();
        }

        $emails = $this->email_lists_model->textual_list($id);
        $separator = $this->input->get('separator') ?: ',';

        $content = generate_txt_export($emails, $separator);

        // Generate filename: listname_YYYYMMDD.txt
        $filename = $this->sanitize_filename($list['name']) . '_' . date('Ymd') . '.txt';

        // Set headers
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));

        echo $content;
        exit;
    }

    /**
     * Download Markdown export
     *
     * @param int $id List ID
     */
    public function download_md($id)
    {
        $list = $this->email_lists_model->get_list($id);
        if (!$list) {
            show_404();
        }

        $emails = $this->email_lists_model->textual_list($id);
        $content = generate_markdown_export($list, $emails);

        // Generate filename: listname_YYYYMMDD.md
        $filename = $this->sanitize_filename($list['name']) . '_' . date('Ymd') . '.md';

        // Set headers
        header('Content-Type: text/markdown; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));

        echo $content;
        exit;
    }

    /**
     * AJAX: Preview recipient count
     *
     * Returns JSON with recipient count based on current form data
     */
    public function preview_count()
    {
        // This would be called via AJAX to update recipient count in real-time
        // For now, return simple JSON response
        header('Content-Type: application/json');

        $list_id = $this->input->post('list_id');
        if ($list_id) {
            $count = $this->email_lists_model->count_members($list_id);
        } else {
            $count = 0;
        }

        echo json_encode(array('count' => $count));
        exit;
    }

    /**
     * AJAX endpoint for preview list during creation/editing
     * Returns the full resolved list with counts
     */
    public function preview_list()
    {
        try {
            // Get form data
            $roles = $this->input->post('roles') ?: array();
            $manual_members = $this->input->post('manual_members') ?: array();
            $external_emails = $this->input->post('external_emails') ?: array();
            $external_names = $this->input->post('external_names') ?: array();
            $active_member = $this->input->post('active_member') ?: 'active';
            $list_id = $this->input->post('list_id') ?: NULL;

            // If we have a list_id, fetch external emails from database (overrides posted data)
            if ($list_id) {
                $db_external = $this->email_lists_model->get_external_emails($list_id);
                // Always reset arrays when list_id is provided (fetch from DB, not POST)
                $external_emails = array();
                $external_names = array();
                if (!empty($db_external)) {
                    foreach ($db_external as $ext) {
                        $external_emails[] = $ext['email'];
                        $external_names[] = $ext['name'];
                    }
                }
            }

            $all_emails = array();
            $criteria_count = 0;

        // Resolve emails from criteria (roles)
        if (!empty($roles)) {
            foreach ($roles as $role_value) {
                // Parse role_id and section_id from value (format: "role_id_section_id")
                $parts = explode('_', $role_value);
                if (count($parts) == 2) {
                    $role_id = (int)$parts[0];
                    $section_id = (int)$parts[1];
                    $section_id = ($section_id === 0) ? NULL : $section_id;

                    // Get users for this role/section
                    $users = $this->email_lists_model->get_users_by_role_and_section($role_id, $section_id, $active_member);
                    foreach ($users as $user) {
                        // Add primary email
                        if (!empty($user['email'])) {
                            $all_emails[] = strtolower(trim($user['email']));
                        }
                        // Add parent email if present
                        if (!empty($user['memailparent'])) {
                            $all_emails[] = strtolower(trim($user['memailparent']));
                        }
                    }
                }
            }
            // Count unique emails from criteria
            $criteria_count = count(array_unique($all_emails));
        }

        // Add manual members (store with names)
        $member_names = array(); // Map email -> name
        if (!empty($manual_members)) {
            $this->load->model('membres_model');
            foreach ($manual_members as $membre_id) {
                $membre = $this->membres_model->get_by_id('mlogin', $membre_id);
                if ($membre) {
                    $name = trim($membre['mnom'] . ' ' . $membre['mprenom']);

                    // Add primary email
                    if (!empty($membre['memail'])) {
                        $email_lower = strtolower(trim($membre['memail']));
                        $all_emails[] = $email_lower;
                        // Store name
                        if (!empty($name)) {
                            $member_names[$email_lower] = $name;
                        }
                    }

                    // Add parent email if present
                    if (!empty($membre['memailparent'])) {
                        $parent_email_lower = strtolower(trim($membre['memailparent']));
                        $all_emails[] = $parent_email_lower;
                        // Store name with (parent) suffix
                        if (!empty($name)) {
                            $member_names[$parent_email_lower] = $name . ' (parent)';
                        }
                    }
                }
            }
        }

        // Add external emails (store with names)
        $external_with_names = array();
        $external_emails_set = array(); // Set of all external emails for quick lookup
        if (!empty($external_emails)) {
            foreach ($external_emails as $idx => $email) {
                if (!empty($email)) {
                    $email_lower = strtolower(trim($email));
                    $all_emails[] = $email_lower;
                    $external_emails_set[$email_lower] = true; // Mark as external
                    // Store name if provided
                    $name = isset($external_names[$idx]) ? trim($external_names[$idx]) : '';
                    if (!empty($name)) {
                        $external_with_names[$email_lower] = $name;
                    }
                }
            }
        }

        // Deduplicate
        $this->load->helper('email');
        $unique_emails = deduplicate_emails($all_emails);

        // Build email list with metadata
        $emails_with_metadata = array();
        foreach ($unique_emails as $email) {
            $item = array(
                'email' => $email,
                'display' => $email,
                'is_external' => isset($external_emails_set[$email])
            );

            // Add name from external emails
            if (isset($external_with_names[$email])) {
                $item['display'] = $email . ' - ' . $external_with_names[$email];
                $item['name'] = $external_with_names[$email];
            }
            // Add name from members
            elseif (isset($member_names[$email])) {
                $item['name'] = $member_names[$email];
            }

            $emails_with_metadata[] = $item;
        }

            // Return JSON response
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => TRUE,
                    'total' => count($unique_emails),
                    'criteria_count' => $criteria_count,
                    'manual_count' => count($manual_members),
                    'external_count' => count($external_emails),
                    'emails' => array_values($emails_with_metadata)
                )));
        } catch (Exception $e) {
            log_message('error', 'Error in preview_list: ' . $e->getMessage());
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => FALSE,
                    'message' => 'Erreur: ' . $e->getMessage()
                )));
        }
    }

    /**
     * Upload external email file (v1.3)
     * Form handler for file upload in import tab
     *
     * @param int $id List ID
     */
    public function upload_file($id = NULL)
    {
        if (empty($id) || !$this->email_lists_model->get_list($id)) {
            $this->session->set_flashdata('error', $this->lang->line('email_lists_upload_error_invalid_id'));
            redirect('email_lists/edit/' . $id);
            return;
        }

        // Check file upload
        if (!isset($_FILES['uploaded_file']) || $_FILES['uploaded_file']['error'] !== UPLOAD_ERR_OK) {
            $error_msg = $this->lang->line('email_lists_upload_error_no_file');
            if (isset($_FILES['uploaded_file']['error'])) {
                $error_msg .= ' (code: ' . $_FILES['uploaded_file']['error'] . ')';
            }
            $this->session->set_flashdata('error', $error_msg);
            redirect('email_lists/edit/' . $id);
            return;
        }

        // Use model to handle upload
        $result = $this->email_lists_model->upload_external_file($id, $_FILES['uploaded_file']);

        // Verify upload actually worked
        if ($result['success']) {
            // Double-check file exists
            $file_path = FCPATH . 'uploads/email_lists/' . $id . '/' . $result['filename'];
            $file_exists = file_exists($file_path);

            // Double-check database entries
            $this->db->where('email_list_id', $id);
            $this->db->where('source_file', $result['filename']);
            $db_count = $this->db->count_all_results('email_list_external');

            log_message('info', "EMAIL_LISTS: Upload verification - file_exists={$file_exists}, db_count={$db_count}, valid_count={$result['valid_count']}");

            if (!$file_exists) {
                $this->session->set_flashdata('error',
                    $this->lang->line('email_lists_upload_error') . ': ' .
                    $this->lang->line('email_lists_upload_error_file_not_saved'));
            } elseif ($db_count == 0) {
                $this->session->set_flashdata('error',
                    $this->lang->line('email_lists_upload_error') . ': ' .
                    $this->lang->line('email_lists_upload_error_no_addresses'));
            } else {
                $msg = str_replace('{count}', $result['valid_count'],
                    $this->lang->line('email_lists_upload_success'));
                $this->session->set_flashdata('success', $msg);
            }
        } else {
            $this->session->set_flashdata('error',
                $this->lang->line('email_lists_upload_error') . ': ' . implode(', ', $result['errors']));
        }
        redirect('email_lists/edit/' . $id);
    }

    /**
     * Delete uploaded file and its addresses (v1.3)
     * AJAX handler for file deletion in import tab
     *
     * @param int $id List ID
     */
    public function delete_file($id = NULL)
    {
        if (empty($id) || !$this->email_lists_model->get_list($id)) {
            // Check if this is an AJAX request
            if ($this->input->server('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode(array(
                    'success' => FALSE,
                    'errors' => array('Invalid list ID')
                ));
                return;
            } else {
                // Form submission - redirect with error
                $this->session->set_flashdata('error', 'Invalid list ID');
                redirect('email_lists/edit/' . $id);
                return;
            }
        }

        // Get filename from JSON (AJAX) or POST (form)
        $filename = NULL;
        if ($this->input->server('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest') {
            // AJAX - get JSON input
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);
            $filename = isset($data['filename']) ? $data['filename'] : NULL;
        } else {
            // Form submission - get POST data
            $filename = $this->input->post('filename');
        }

        if (empty($filename)) {
            // Check if this is an AJAX request
            if ($this->input->server('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode(array(
                    'success' => FALSE,
                    'errors' => array('Filename required')
                ));
                return;
            } else {
                // Form submission - redirect with error
                $this->session->set_flashdata('error', 'Filename required');
                redirect('email_lists/edit/' . $id);
                return;
            }
        }

        // Use model to handle deletion
        $result = $this->email_lists_model->delete_file_and_addresses($id, $filename);

        // Check if this is an AJAX request
        if ($this->input->server('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode($result);
        } else {
            // Form submission - redirect with message
            if ($result['success']) {
                $this->session->set_flashdata('success',
                    'File deleted successfully. ' . $result['deleted_count'] . ' addresses removed.');
            } else {
                $this->session->set_flashdata('error',
                    'Delete error: ' . implode(', ', $result['errors']));
            }
            redirect('email_lists/edit/' . $id);
        }
    }

    /**
     * Sanitize filename for safe file downloads
     *
     * @param string $filename Filename to sanitize
     * @return string Sanitized filename
     */
    protected function sanitize_filename($filename)
    {
        // Remove special characters, keep only alphanumeric, dash, underscore
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
        // Remove multiple consecutive underscores
        $filename = preg_replace('/_+/', '_', $filename);
        // Remove leading/trailing underscores
        $filename = trim($filename, '_');

        return $filename ?: 'email_list';
    }
}
