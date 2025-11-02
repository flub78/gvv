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
     * Create - Display form for creating new list
     */
    public function create()
    {
        $data['title'] = $this->lang->line('email_lists_create');
        $data['controller'] = $this->controller;
        $data['action'] = 'store';

        // Load available roles and sections for criteria tab
        $data['available_roles'] = $this->email_lists_model->get_available_roles();
        $data['available_sections'] = $this->email_lists_model->get_available_sections();

        // Load all members for manual selection tab
        $this->load->model('membres_model');
        $data['available_members'] = $this->membres_model->selector(array('actif' => 1));

        // Initialize empty list data
        $data['list'] = array(
            'name' => '',
            'description' => '',
            'active_member' => 'active',
            'visible' => 1
        );

        return load_last_view('email_lists/form', $data, $this->unit_test);
    }

    /**
     * Store - Save new list
     */
    public function store()
    {
        // Validate input
        $this->form_validation->set_rules('name', $this->lang->line('email_lists_name'), 'required|max_length[255]');
        $this->form_validation->set_rules('description', $this->lang->line('email_lists_description'), 'max_length[1000]');
        $this->form_validation->set_rules('active_member', $this->lang->line('email_lists_active_member'), 'required|in_list[active,inactive,all]');

        if ($this->form_validation->run() === FALSE) {
            // Validation failed - redisplay form
            return $this->create();
        }

        // Create the list
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

        // Add roles if provided
        $roles = $this->input->post('roles');
        if ($roles && is_array($roles)) {
            foreach ($roles as $role_data) {
                if (isset($role_data['types_roles_id']) && isset($role_data['section_id'])) {
                    $this->email_lists_model->add_role_to_list(
                        $list_id,
                        $role_data['types_roles_id'],
                        $role_data['section_id'],
                        $user_id
                    );
                }
            }
        }

        // Add manual members if provided
        $manual_members = $this->input->post('manual_members');
        if ($manual_members && is_array($manual_members)) {
            foreach ($manual_members as $membre_id) {
                $this->email_lists_model->add_manual_member($list_id, $membre_id);
            }
        }

        // Add external emails if provided
        $external_emails = $this->input->post('external_emails');
        if ($external_emails) {
            $parsed = parse_text_emails($external_emails);
            foreach ($parsed as $email_data) {
                if ($email_data['valid']) {
                    $this->email_lists_model->add_external_email(
                        $list_id,
                        $email_data['email'],
                        $email_data['name'] ?? NULL
                    );
                }
            }
        }

        $this->session->set_flashdata('success', $this->lang->line('email_lists_create_success'));
        redirect('email_lists/view/' . $list_id);
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
        $data['emails'] = $this->email_lists_model->textual_list($id);
        $data['recipient_count'] = count($data['emails']);

        // Get detailed sources
        $data['roles'] = $this->email_lists_model->get_list_roles($id);
        $data['manual_members'] = $this->email_lists_model->get_manual_members($id);
        $data['external_emails'] = $this->email_lists_model->get_external_emails($id);

        return load_last_view('email_lists/view', $data, $this->unit_test);
    }

    /**
     * Edit - Display form for editing existing list
     *
     * @param int $id List ID
     */
    public function edit($id)
    {
        $list = $this->email_lists_model->get_list($id);
        if (!$list) {
            show_404();
        }

        $data['title'] = $this->lang->line('email_lists_edit');
        $data['controller'] = $this->controller;
        $data['action'] = 'update';
        $data['list'] = $list;

        // Load available roles and sections
        $data['available_roles'] = $this->email_lists_model->get_available_roles();
        $data['available_sections'] = $this->email_lists_model->get_available_sections();

        // Load current list configuration
        $data['current_roles'] = $this->email_lists_model->get_list_roles($id);
        $data['current_manual_members'] = $this->email_lists_model->get_manual_members($id);
        $data['current_external_emails'] = $this->email_lists_model->get_external_emails($id);

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
        $list = $this->email_lists_model->get_list($id);
        if (!$list) {
            show_404();
        }

        // Validate input
        $this->form_validation->set_rules('name', $this->lang->line('email_lists_name'), 'required|max_length[255]');
        $this->form_validation->set_rules('description', $this->lang->line('email_lists_description'), 'max_length[1000]');
        $this->form_validation->set_rules('active_member', $this->lang->line('email_lists_active_member'), 'required|in_list[active,inactive,all]');

        if ($this->form_validation->run() === FALSE) {
            return $this->edit($id);
        }

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

        $this->session->set_flashdata('success', $this->lang->line('email_lists_update_success'));
        redirect('email_lists/view/' . $id);
    }

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
     * Sanitize filename for safe file downloads
     *
     * @param string $filename Filename to sanitize
     * @return string Sanitized filename
     */
    private function sanitize_filename($filename)
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
