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
 * @filesource acceptance_admin.php
 * @package controllers
 *
 * Administration des éléments à accepter (documents, formations, contrôles, briefings, autorisations)
 *
 * Fonctionnalités:
 * - CRUD éléments d'acceptation
 * - Upload PDF des documents
 * - Suivi des acceptations par élément
 * - Activation/désactivation
 * - Rattachement acceptation externe à un pilote
 */

/**
 * Include parent library
 */
include('./application/libraries/Gvv_Controller.php');

/**
 * Controleur d'administration des acceptations
 */
class Acceptance_admin extends Gvv_Controller {
    protected $controller = 'acceptance_admin';
    protected $model = 'acceptance_items_model';
    protected $view_level = 'ca';
    protected $modification_level = 'ca';

    protected $rules = array();

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();

        $this->lang->load('acceptance');
        $this->load->model('acceptance_records_model');
        $this->load->model('membres_model');

        $this->table_view = $this->controller . '/itemsListView';
        $this->form_view = $this->controller . '/itemFormView';
    }

    /**
     * Default page - list items
     */
    function index() {
        $this->page();
    }

    /**
     * List acceptance items with filters
     */
    function page($premier = 0, $message = '', $selection = array()) {
        if (!$this->_is_admin()) {
            show_404();
            return;
        }

        $this->push_return_url("acceptance_admin page");

        // Filters
        $filter_category = $this->input->get('filter_category') ?: '';
        $filter_active = $this->input->get('filter_active') ?: 'all';
        $filter_overdue = $this->input->get('filter_overdue') ?: '';

        $where = array();
        if (!empty($filter_category)) {
            $where['acceptance_items.category'] = $filter_category;
        }
        if ($filter_active !== 'all') {
            $where['acceptance_items.active'] = (int) $filter_active;
        }

        $this->data['select_result'] = $this->gvv_model->select_page(0, 0, $where);

        // If overdue filter, keep only overdue items
        if (!empty($filter_overdue)) {
            $today = date('Y-m-d');
            $this->data['select_result'] = array_filter($this->data['select_result'], function ($row) use ($today) {
                return !empty($row['deadline']) && $row['deadline'] < $today;
            });
        }

        // Counts
        $overdue_items = $this->gvv_model->get_overdue_items();
        $this->data['overdue_count'] = count($overdue_items);

        $this->data['filter_category'] = $filter_category;
        $this->data['filter_active'] = $filter_active;
        $this->data['filter_overdue'] = $filter_overdue;
        $this->data['kid'] = $this->kid;
        $this->data['controller'] = $this->controller;
        $this->data['has_modification_rights'] = true;
        $this->data['message'] = $message;

        return load_last_view($this->table_view, $this->data, $this->unit_test);
    }

    /**
     * Create form
     */
    function create() {
        if (!$this->_is_admin()) {
            show_404();
            return;
        }

        $table = $this->gvv_model->table();
        $this->data = $this->gvvmetadata->defaults_list($table);

        $this->data['created_by'] = $this->dx_auth->get_username();

        $this->form_static_element(CREATION);

        return load_last_view($this->form_view, $this->data, $this->unit_test);
    }

    /**
     * Edit form
     */
    function edit($id = "", $load_view = TRUE, $action = MODIFICATION) {
        if (!$this->_is_admin()) {
            show_404();
            return;
        }

        $this->data = $this->gvv_model->get_by_id($this->kid, $id);
        if (!$this->data || count($this->data) < 1) {
            $this->session->set_flashdata('message', '<div class="alert alert-danger">' . $this->lang->line('acceptance_item_not_found') . '</div>');
            redirect('acceptance_admin/page');
            return;
        }

        $this->form_static_element($action);

        $this->data['original_' . $this->kid] = $id;
        $this->data[$this->kid] = $id;
        $this->data['kid'] = $this->kid;

        if ($load_view) {
            return load_last_view($this->form_view, $this->data, $this->unit_test);
        }
    }

    /**
     * Generate form static elements
     */
    function form_static_element($action) {
        parent::form_static_element($action);

        $this->data['category_options'] = array(
            '' => '',
            'document' => $this->lang->line('acceptance_category_document'),
            'formation' => $this->lang->line('acceptance_category_formation'),
            'controle' => $this->lang->line('acceptance_category_controle'),
            'briefing' => $this->lang->line('acceptance_category_briefing'),
            'autorisation' => $this->lang->line('acceptance_category_autorisation')
        );

        $this->data['target_type_options'] = array(
            'internal' => $this->lang->line('acceptance_target_type_internal'),
            'external' => $this->lang->line('acceptance_target_type_external')
        );

        $this->data['is_admin'] = $this->_is_admin();
    }

    /**
     * Form validation and file upload
     */
    public function formValidation($action, $return_on_success = false) {
        if (!$this->_is_admin()) {
            show_404();
            return;
        }

        $button = $this->input->post('button');

        if ($button == $this->lang->line("gvv_button_show_list")) {
            redirect('acceptance_admin/page');
            return;
        } else if ($button == $this->lang->line("gvv_button_cancel")) {
            $this->pop_return_url();
            return;
        }

        // Validate required fields
        $title = trim($this->input->post('title'));
        $category = $this->input->post('category');

        if (empty($title)) {
            $this->data['message'] = '<div class="alert alert-danger">' . $this->lang->line('acceptance_error_title_required') . '</div>';
            $this->_reload_form($action);
            return;
        }

        if (empty($category)) {
            $this->data['message'] = '<div class="alert alert-danger">' . $this->lang->line('acceptance_error_category_required') . '</div>';
            $this->_reload_form($action);
            return;
        }

        // Build item data
        $item_data = array(
            'title' => $title,
            'category' => $category,
            'target_type' => $this->input->post('target_type') ?: 'internal',
            'version_date' => mysql_date($this->input->post('version_date')) ?: null,
            'mandatory' => $this->input->post('mandatory') ? 1 : 0,
            'deadline' => mysql_date($this->input->post('deadline')) ?: null,
            'dual_validation' => $this->input->post('dual_validation') ? 1 : 0,
            'role_1' => $this->input->post('role_1') ?: null,
            'role_2' => $this->input->post('role_2') ?: null,
            'target_roles' => $this->input->post('target_roles') ?: null,
            'active' => $this->input->post('active') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s')
        );

        // Handle PDF upload (only if a file was selected)
        if (!empty($_FILES['pdf_file']['name'])) {
            $upload_result = $this->_handle_pdf_upload($action);
            if ($upload_result === false) {
                return; // Error already displayed
            }
            $item_data['pdf_path'] = $upload_result;
        }

        if ($action == CREATION) {
            $item_data['created_by'] = $this->dx_auth->get_username();
            $item_data['created_at'] = date('Y-m-d H:i:s');

            $id = $this->gvv_model->create($item_data);

            if ($id) {
                $this->session->set_flashdata('message', '<div class="alert alert-success">' . $this->lang->line('acceptance_item_created') . '</div>');
                redirect('acceptance_admin/page');
            } else {
                $db_error = $this->db->error();
                $detail = !empty($db_error['message']) ? htmlspecialchars($db_error['message']) : '';
                $this->data['message'] = '<div class="alert alert-danger">' . $this->lang->line('acceptance_error_create') . ' ' . $detail . '</div>';
                $this->_reload_form($action);
            }
        } elseif ($action == MODIFICATION) {
            $id = $this->input->post('original_id');

            $this->gvv_model->update('id', $item_data, $id);

            $code = $this->db->_error_number();
            if ($code) {
                $msg = $this->db->_error_message();
                $this->data['message'] = '<div class="alert alert-danger">' . htmlspecialchars($msg) . '</div>';
                $this->_reload_form($action);
                return;
            }

            $this->session->set_flashdata('message', '<div class="alert alert-success">' . $this->lang->line('acceptance_item_updated') . '</div>');
            redirect('acceptance_admin/page');
        }
    }

    /**
     * Toggle active status of an item
     */
    function toggle_active($id) {
        if (!$this->_is_admin()) {
            show_404();
            return;
        }

        $item = $this->gvv_model->get_by_id('id', $id);
        if (!$item) {
            $this->session->set_flashdata('message', '<div class="alert alert-danger">' . $this->lang->line('acceptance_item_not_found') . '</div>');
            redirect('acceptance_admin/page');
            return;
        }

        $new_active = $item['active'] ? 0 : 1;
        $this->gvv_model->update('id', array(
            'active' => $new_active,
            'updated_at' => date('Y-m-d H:i:s')
        ), $id);

        $msg = $new_active
            ? $this->lang->line('acceptance_item_activated')
            : $this->lang->line('acceptance_item_deactivated');
        $this->session->set_flashdata('message', '<div class="alert alert-success">' . $msg . '</div>');
        redirect('acceptance_admin/page');
    }

    /**
     * Tracking view - show all acceptance records for a specific item
     */
    function tracking($item_id) {
        if (!$this->_is_admin()) {
            show_404();
            return;
        }

        $this->push_return_url("acceptance_admin tracking");

        $item = $this->gvv_model->get_by_id('id', $item_id);
        if (!$item) {
            $this->session->set_flashdata('message', '<div class="alert alert-danger">' . $this->lang->line('acceptance_item_not_found') . '</div>');
            redirect('acceptance_admin/page');
            return;
        }

        // Get filter
        $filter_status = $this->input->get('filter_status');
        $filter_linked = $this->input->get('filter_linked');

        $where = array('acceptance_records.item_id' => $item_id);
        if (!empty($filter_status)) {
            $where['acceptance_records.status'] = $filter_status;
        }

        $records = $this->acceptance_records_model->select_page(0, 0, $where);

        // Filter linked/unlinked
        if ($filter_linked === 'linked') {
            $records = array_filter($records, function ($r) {
                return !empty($r['linked_pilot_login']);
            });
        } elseif ($filter_linked === 'unlinked') {
            $records = array_filter($records, function ($r) {
                return empty($r['linked_pilot_login']) && empty($r['user_login']);
            });
        }

        // Count stats
        $all_records = $this->acceptance_records_model->get_by_item($item_id);
        $pending_count = 0;
        $accepted_count = 0;
        $refused_count = 0;
        $unlinked_count = 0;
        foreach ($all_records as $r) {
            if ($r['status'] === 'pending') $pending_count++;
            if ($r['status'] === 'accepted') $accepted_count++;
            if ($r['status'] === 'refused') $refused_count++;
            if (empty($r['user_login']) && empty($r['linked_pilot_login'])) $unlinked_count++;
        }

        $this->data['item'] = $item;
        $this->data['records'] = $records;
        $this->data['filter_status'] = $filter_status;
        $this->data['filter_linked'] = $filter_linked;
        $this->data['pending_count'] = $pending_count;
        $this->data['accepted_count'] = $accepted_count;
        $this->data['refused_count'] = $refused_count;
        $this->data['unlinked_count'] = $unlinked_count;
        $this->data['controller'] = $this->controller;

        // Pilot selector for linking
        $this->data['pilot_selector'] = $this->membres_model->selector(array('actif' => 1));

        return load_last_view($this->controller . '/trackingView', $this->data, $this->unit_test);
    }

    /**
     * Link an external acceptance record to a pilot
     */
    function link_pilot($record_id) {
        if (!$this->_is_admin()) {
            show_404();
            return;
        }

        $pilot_login = $this->input->post('pilot_login');
        if (empty($pilot_login)) {
            $this->session->set_flashdata('message', '<div class="alert alert-danger">' . $this->lang->line('acceptance_error_pilot_required') . '</div>');
            redirect($_SERVER['HTTP_REFERER'] ?? 'acceptance_admin/page');
            return;
        }

        // Get the record to find item_id for redirect
        $record = $this->acceptance_records_model->get_by_id('id', $record_id);
        if (!$record) {
            $this->session->set_flashdata('message', '<div class="alert alert-danger">' . $this->lang->line('acceptance_record_not_found') . '</div>');
            redirect('acceptance_admin/page');
            return;
        }

        $result = $this->acceptance_records_model->link_to_pilot(
            $record_id,
            $pilot_login,
            $this->dx_auth->get_username()
        );

        if ($result) {
            $this->session->set_flashdata('message', '<div class="alert alert-success">' . $this->lang->line('acceptance_pilot_linked') . '</div>');
        } else {
            $this->session->set_flashdata('message', '<div class="alert alert-danger">' . $this->lang->line('acceptance_error_link') . '</div>');
        }

        redirect('acceptance_admin/tracking/' . $record['item_id']);
    }

    /**
     * Download the PDF file of an item
     */
    function download($id) {
        $item = $this->gvv_model->get_by_id('id', $id);
        if (!$item || empty($item['pdf_path'])) {
            show_404();
            return;
        }

        $file_path = $item['pdf_path'];
        if (!file_exists($file_path)) {
            show_404();
            return;
        }

        $this->load->helper('download');
        $filename = basename($file_path);
        force_download($filename, file_get_contents($file_path));
    }

    // ---------------------------------------------------------------
    // Private helpers
    // ---------------------------------------------------------------

    /**
     * Check if current user is admin (CA or admin)
     */
    private function _is_admin() {
        return $this->dx_auth->is_role('ca', true, true) || $this->dx_auth->is_admin();
    }

    /**
     * Handle PDF file upload
     * @param string $action Form action (CREATION or MODIFICATION)
     * @return string|false File path on success, false on failure
     */
    private function _handle_pdf_upload($action) {
        $item_id = ($action == MODIFICATION) ? $this->input->post('original_id') : time();
        $dirname = 'uploads/acceptances/items/' . $item_id . '/';

        if (!is_dir($dirname)) {
            if (!mkdir($dirname, 0755, true)) {
                $this->data['message'] = '<div class="alert alert-danger">' . $this->lang->line('acceptance_error_directory') . '</div>';
                $this->_reload_form($action);
                return false;
            }
        }

        $config['upload_path'] = $dirname;
        $config['allowed_types'] = 'pdf';
        $config['max_size'] = 10000; // 10MB
        $config['file_name'] = 'document_' . time() . '.pdf';

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload("pdf_file")) {
            $this->data['message'] = '<div class="alert alert-danger">' . $this->upload->display_errors() . '</div>';
            $this->_reload_form($action);
            return false;
        }

        $upload_data = $this->upload->data();
        $file_path = $dirname . $upload_data['file_name'];

        // Compress if possible
        $this->load->library('file_compressor');
        $compression_result = $this->file_compressor->compress($file_path);
        if ($compression_result['success']) {
            $file_path = $compression_result['compressed_path'];
        }

        return $file_path;
    }

    /**
     * Reload form after validation error
     */
    private function _reload_form($action) {
        // Restore form data from POST
        $table = $this->gvv_model->table();
        $fields_list = $this->gvvmetadata->fields_list($table);
        foreach ($fields_list as $field) {
            $val = $this->input->post($field);
            if ($val !== null) {
                $this->data[$field] = $val;
            }
        }
        if ($action == MODIFICATION) {
            $this->data['original_id'] = $this->input->post('original_id');
            $this->data['id'] = $this->input->post('original_id');
        }
        $this->form_static_element($action);
        load_last_view($this->form_view, $this->data);
    }
}
