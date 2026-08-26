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
        $this->load->model('archived_documents_model');
        $this->load->model('acceptance_item_roles_model');
        // Role x section lookups (get_available_roles/get_available_sections)
        // are generic and already implemented for the email lists role grid.
        $this->load->model('email_lists_model');

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
        $filter_archived_document_id = $this->input->get('filter_archived_document_id') ?: '';

        $where = array();
        if (!empty($filter_category)) {
            $where['acceptance_items.category'] = $filter_category;
        }
        if ($filter_active !== 'all') {
            $where['acceptance_items.active'] = (int) $filter_active;
        }
        if (!empty($filter_archived_document_id)) {
            $where['acceptance_items.archived_document_id'] = (int) $filter_archived_document_id;
        }

        $this->data['select_result'] = $this->gvv_model->select_page(0, 0, $where);

        // Approvals done / expected per item. "Done" comes from actual
        // acceptance_records; "expected" is the real targeted audience
        // (resolve_targets()), not just the people who happen to already
        // have a record — otherwise members who never opened the item are
        // silently left out of the denominator.
        $approval_counts = $this->acceptance_records_model->count_by_item();
        foreach ($this->data['select_result'] as &$row) {
            $row['approved_count'] = isset($approval_counts[$row['id']]) ? $approval_counts[$row['id']]['accepted'] : 0;
            $row['expected_count'] = count($this->gvv_model->resolve_targets($row));
        }
        unset($row);

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
        $this->data['filter_archived_document_id'] = $filter_archived_document_id;
        if (!empty($filter_archived_document_id)) {
            $this->data['filter_archived_document'] = $this->archived_documents_model->get_by_id('id', $filter_archived_document_id);
        }
        $this->data['kid'] = $this->kid;
        $this->data['controller'] = $this->controller;
        $this->data['has_modification_rights'] = true;
        $this->data['message'] = $message;

        return load_last_view($this->table_view, $this->data, $this->unit_test);
    }

    /**
     * Create form
     * @param int|null $archived_document_id When set, pre-fills the form to create a
     *   'document' category item referencing an already archived document
     *   (application/controllers/archived_documents.php) instead of uploading a new PDF.
     *   When absent, a new item can only be created by first picking an archived
     *   document (amendment Lot 4, doc/plans/acceptations_reconnaissances_plan.md) —
     *   free category choice and PDF upload are no longer offered for new items.
     */
    function create($archived_document_id = null) {
        if (!$this->_is_admin()) {
            show_404();
            return;
        }

        if (empty($archived_document_id)) {
            $this->data['archived_document_selector'] = $this->archived_documents_model->selector();
            return load_last_view($this->controller . '/selectDocumentView', $this->data, $this->unit_test);
        }

        $table = $this->gvv_model->table();
        $this->data = $this->gvvmetadata->defaults_list($table);

        $this->data['created_by'] = $this->dx_auth->get_username();

        $archived_document = $this->archived_documents_model->get_by_id('id', $archived_document_id);
        if (!$archived_document) {
            $this->session->set_flashdata('message', '<div class="alert alert-danger">' . $this->lang->line('acceptance_archived_document_not_found') . '</div>');
            redirect('acceptance_admin/create');
            return;
        }
        $this->data['archived_document_id'] = $archived_document_id;
        $this->data['archived_document'] = $archived_document;
        $this->data['category'] = 'document';
        $this->data['title'] = $archived_document['description'] ?: $archived_document['original_filename'];
        $this->data['version_date'] = $this->_archived_document_date($archived_document);

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
        $this->data['target_mode'] = !empty($this->data['target_user_login']) ? 'user' : 'roles';
        $this->data['checked_roles'] = $this->acceptance_item_roles_model->get_checked_map($id);

        if (!empty($this->data['archived_document_id'])) {
            $this->data['archived_document'] = $this->archived_documents_model->get_by_id('id', $this->data['archived_document_id']);
            $this->data['version_date'] = $this->_archived_document_date($this->data['archived_document']);
        }

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

        $this->data['member_selector'] = $this->membres_model->selector(array('actif' => 1));
        $this->data['available_roles'] = $this->email_lists_model->get_available_roles();
        $this->data['available_sections'] = $this->email_lists_model->get_available_sections();
        if (!isset($this->data['checked_roles'])) {
            $this->data['checked_roles'] = array();
        }

        $this->data['is_admin'] = $this->_is_admin();
    }

    /**
     * Version date to display/enforce when an item references an archived
     * document: the document's deposit date, not a freely edited value.
     * @param array $archived_document
     * @return string Date formatted jj/mm/aaaa (matches mysql_date() input)
     */
    private function _archived_document_date($archived_document) {
        if (empty($archived_document['uploaded_at'])) {
            return '';
        }
        return date('d/m/Y', strtotime($archived_document['uploaded_at']));
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

        // Item referencing an already archived document (cf. archived_documents.php)
        // instead of a PDF uploaded specifically for the acceptance. Category is forced
        // to 'document' server-side regardless of the posted value, since the hidden
        // category field is not meant to be tampered with.
        $archived_document_id = $this->input->post('archived_document_id') ?: null;
        if (!empty($archived_document_id)) {
            $archived_document = $this->archived_documents_model->get_by_id('id', $archived_document_id);
            if (!$archived_document) {
                $this->data['message'] = '<div class="alert alert-danger">' . $this->lang->line('acceptance_archived_document_not_found') . '</div>';
                $this->_reload_form($action);
                return;
            }
            $category = 'document';
        }

        // Targeting is exclusive: either an individual user, or one or more
        // role x section entries (acceptance_item_roles) — never both (cf.
        // PRD, Cas d'utilisation Administrateur). Role targeting uses the
        // same role x section grid as the email lists selector; target_roles
        // (free text) is legacy and no longer written by this form.
        $target_mode = $this->input->post('target_mode') ?: 'roles';
        if ($target_mode === 'user') {
            $target_user_login = $this->input->post('target_user_login') ?: null;
            $role_values = array();
        } else {
            $target_user_login = null;
            $role_values = $this->input->post('roles') ?: array();
        }

        // Version date is not user-editable when the item references an
        // archived document: it must match that document's deposit date.
        if (!empty($archived_document_id)) {
            $version_date = mysql_date($this->_archived_document_date($archived_document)) ?: null;
        } else {
            $version_date = mysql_date($this->input->post('version_date')) ?: null;
        }

        // Obligation level: optional / mandatory_soft / mandatory_hard (Lot 3d).
        $mandatory_level = $this->input->post('mandatory_level');
        if (!in_array($mandatory_level, array('optional', 'mandatory_soft', 'mandatory_hard'), true)) {
            $mandatory_level = 'optional';
        }

        // Build item data
        $item_data = array(
            'title' => $title,
            'description' => trim($this->input->post('description')) ?: null,
            'category' => $category,
            'archived_document_id' => $archived_document_id ?: null,
            'target_type' => $this->input->post('target_type') ?: 'internal',
            'version_date' => $version_date,
            'mandatory_level' => $mandatory_level,
            'deadline' => mysql_date($this->input->post('deadline')) ?: null,
            'dual_validation' => $this->input->post('dual_validation') ? 1 : 0,
            'role_1' => $this->input->post('role_1') ?: null,
            'role_2' => $this->input->post('role_2') ?: null,
            'target_user_login' => $target_user_login,
            'active' => $this->input->post('active') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s')
        );

        // Handle PDF upload (only if a file was selected and the item isn't
        // referencing an archived document, which already has its own file)
        if (empty($archived_document_id) && !empty($_FILES['pdf_file']['name'])) {
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
                $this->acceptance_item_roles_model->replace_for_item($id, $role_values, $this->dx_auth->get_username());
                $this->gvv_model->sync_target_motd($id);
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

            $this->acceptance_item_roles_model->replace_for_item($id, $role_values, $this->dx_auth->get_username());
            $this->gvv_model->sync_target_motd($id);
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
        $this->gvv_model->sync_target_motd($id);

        $msg = $new_active
            ? $this->lang->line('acceptance_item_activated')
            : $this->lang->line('acceptance_item_deactivated');
        $this->session->set_flashdata('message', '<div class="alert alert-success">' . $msg . '</div>');
        redirect('acceptance_admin/page');
    }

    /**
     * Delete an item. Cascades in DB to its acceptance_records (and their
     * acceptance_signatures) and acceptance_item_roles (migrations 068/170,
     * ON DELETE CASCADE) — the confirmation dialog warns about this. Its
     * generated message(s) du jour are cleared explicitly (source_type/
     * source_ref is a soft link, no FK). The referenced archived_documents
     * file (if any) is NOT touched, it belongs to the document archiving
     * module; only a PDF uploaded for this item specifically (pdf_path) is
     * removed from disk.
     */
    function delete($id) {
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

        $this->_delete_item_pdf($item);
        $this->gvv_model->clear_target_motd($id);
        $this->gvv_model->delete(array('id' => $id));

        $this->session->set_flashdata('message', '<div class="alert alert-success">' . $this->lang->line('acceptance_item_deleted') . '</div>');
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
        $all_records = $this->acceptance_records_model->get_by_item($item_id);

        // Targeted members who never opened/acted on the item have no
        // acceptance_records row at all (rows are created lazily on first
        // visit or action). Synthesize a virtual 'pending' row for each of
        // them so they aren't invisible on this page.
        $targeted_logins = $this->gvv_model->resolve_targets($item);
        $existing_logins = array_filter(array_column($all_records, 'user_login'));
        $missing_logins = array_values(array_diff($targeted_logins, $existing_logins));

        if (!empty($missing_logins) && (empty($filter_status) || $filter_status === 'pending')) {
            $this->load->model('membres_model');
            $this->db->select('mlogin, mnom, mprenom');
            $this->db->from('membres');
            $this->db->where_in('mlogin', $missing_logins);
            $names = array();
            foreach ($this->db->get()->result_array() as $row) {
                $names[$row['mlogin']] = $row;
            }

            $virtual_records = array();
            foreach ($missing_logins as $login) {
                $virtual_records[] = array(
                    'id' => null,
                    'item_id' => $item_id,
                    'user_login' => $login,
                    'external_name' => null,
                    'status' => 'pending',
                    'validation_role' => null,
                    'formula_text' => null,
                    'acted_at' => null,
                    'created_at' => null,
                    'signature_mode' => null,
                    'linked_pilot_login' => null,
                    'linked_by' => null,
                    'linked_at' => null,
                    'pilot_nom' => isset($names[$login]) ? $names[$login]['mnom'] : '',
                    'pilot_prenom' => isset($names[$login]) ? $names[$login]['mprenom'] : '',
                    'linked_pilot_nom' => null,
                    'linked_pilot_prenom' => null,
                );
            }
            $records = array_merge($records, $virtual_records);
            $all_records = array_merge($all_records, $virtual_records);
        }

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
     * Reset an accepted/refused record back to pending, so the targeted
     * person is asked to approve the item again.
     */
    function reset_approval($record_id) {
        if (!$this->_is_admin()) {
            show_404();
            return;
        }

        $record = $this->acceptance_records_model->get_by_id('id', $record_id);
        if (!$record) {
            $this->session->set_flashdata('message', '<div class="alert alert-danger">' . $this->lang->line('acceptance_record_not_found') . '</div>');
            redirect('acceptance_admin/page');
            return;
        }

        $result = $this->acceptance_records_model->reset_to_pending($record_id);

        if ($result) {
            $this->gvv_model->sync_target_motd($record['item_id']);
            $this->session->set_flashdata('message', '<div class="alert alert-success">' . $this->lang->line('acceptance_reset_success') . '</div>');
        } else {
            $this->session->set_flashdata('message', '<div class="alert alert-danger">' . $this->lang->line('acceptance_error_reset') . '</div>');
        }

        redirect('acceptance_admin/tracking/' . $record['item_id']);
    }

    /**
     * Stream the item's PDF inline, so it opens in a new browser tab instead
     * of being downloaded (mirrors acceptance::pdf() for the member side).
     */
    function pdf($id) {
        if (!$this->_is_admin()) {
            show_404();
            return;
        }

        $item = $this->gvv_model->get_by_id('id', $id);
        if (!$item) {
            show_404();
            return;
        }

        if (!empty($item['archived_document_id'])) {
            $doc = $this->archived_documents_model->get_by_id('id', $item['archived_document_id']);
            $file_path = $doc ? $doc['file_path'] : null;
            $filename = $doc ? $doc['original_filename'] : 'document.pdf';
        } else {
            $file_path = $item['pdf_path'];
            $filename = $file_path ? basename($file_path) : 'document.pdf';
        }

        if (empty($file_path) || !file_exists($file_path)) {
            show_404();
            return;
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file_path));
        readfile($file_path);
        exit;
    }

    /**
     * Download the PDF file of an item
     */
    function download($id) {
        if (!$this->_is_admin()) {
            show_404();
            return;
        }

        $item = $this->gvv_model->get_by_id('id', $id);
        if (!$item) {
            show_404();
            return;
        }

        if (!empty($item['archived_document_id'])) {
            redirect('archived_documents/download/' . $item['archived_document_id']);
            return;
        }

        if (empty($item['pdf_path'])) {
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
        return $this->user_has_role('ca') || $this->user_has_role('club-admin');
    }

    /**
     * Remove the PDF uploaded specifically for this item (uploads/acceptances/items/<id>/),
     * if any. Does nothing when the item references an archived_documents
     * file instead (that file belongs to the document archiving module).
     */
    private function _delete_item_pdf($item) {
        if (empty($item['pdf_path']) || !empty($item['archived_document_id'])) {
            return;
        }

        $dir = dirname($item['pdf_path']);
        if (strpos($dir, 'uploads/acceptances/items/') === false || !is_dir($dir)) {
            return;
        }

        foreach (glob($dir . '/*') ?: array() as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        @rmdir($dir);
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
        $this->data['target_mode'] = $this->input->post('target_mode') ?: 'roles';
        if (!empty($this->data['archived_document_id'])) {
            $this->data['archived_document'] = $this->archived_documents_model->get_by_id('id', $this->data['archived_document_id']);
            $this->data['version_date'] = $this->_archived_document_date($this->data['archived_document']);
        }
        // Re-check the role x section boxes the admin had selected, from POST
        // (not yet persisted since validation failed before saving).
        $posted_roles = $this->input->post('roles') ?: array();
        $this->data['checked_roles'] = array_fill_keys($posted_roles, true);
        $this->form_static_element($action);
        load_last_view($this->form_view, $this->data);
    }
}
