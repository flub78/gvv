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
 * @filesource archived_documents.php
 * @package controllers
 *
 * Controleur des documents archivés (archivage documentaire)
 *
 * Fonctionnalités:
 * - Pilotes: voir/ajouter/supprimer leurs propres documents
 * - Admins (CA): voir tous les documents, liste expirés, désactiver alertes
 */

/**
 * Include parent library
 */
include('./application/libraries/Gvv_Controller.php');

/**
 * Controleur de gestion des documents archivés
 */
class Archived_documents extends Gvv_Controller {
    protected $controller = 'archived_documents';
    protected $model = 'archived_documents_model';
    protected $view_level = 'membre';        // All members can view their own documents
    protected $modification_level = 'membre'; // Members can add their own documents

    protected $rules = array();

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();

        // Check if feature is enabled
        if (!$this->config->item('gestion_documentaire')) {
            show_404();
        }

        $this->lang->load('archived_documents');
        $this->load->model('document_types_model');
        $this->load->model('membres_model');
        $this->load->model('sections_model');

        $this->table_view = $this->controller . '/documentsListView';
    }

    /**
     * Default page - admins see admin view, pilots see their documents
     */
    function index() {
        if ($this->_is_admin()) {
            $this->page();
        } else {
            $this->my_documents();
        }
    }

    /**
     * Shows current user's documents
     */
    function my_documents() {
        $pilot_login = $this->dx_auth->get_username();

        $this->push_return_url("archived_documents my_documents");

        $this->data['documents'] = $this->gvv_model->get_pilot_documents($pilot_login);
        $section_id = $this->session->userdata('section');
        $this->data['section_documents'] = $section_id ? $this->gvv_model->get_section_documents($section_id) : array();
        $this->data['club_documents'] = $this->gvv_model->get_club_documents();
        $this->data['missing'] = $this->gvv_model->get_missing_documents($pilot_login, $this->session->userdata('section'));
        $this->data['controller'] = $this->controller;
        $this->data['is_admin'] = $this->_is_admin();
        $this->data['is_bureau'] = $this->dx_auth->is_role('bureau', true, true);
        $this->data['pilot_login'] = $pilot_login;

        $pilot = $this->membres_model->get_by_id('mlogin', $pilot_login);
        $this->data['title'] = $this->lang->line('archived_documents_documents_of') . ' ' . $pilot['mprenom'] . ' ' . $pilot['mnom'];

        load_last_view($this->controller . '/my_documents', $this->data);
    }

    /**
     * Admin view: unassociated documents + pilot selector, with optional filter
     */
    function page($premier = 0, $message = '', $selection = array()) {
        // Check admin access
        if (!$this->_is_admin()) {
            redirect('archived_documents/my_documents');
            return;
        }

        $this->push_return_url("archived_documents page");

        // Read filter parameter
        $filter = $this->input->get('filter');
        $this->data['active_filter'] = $filter;

        // Count pending documents for badge (always needed)
        $pending_docs = $this->gvv_model->get_pending_documents();
        $this->data['pending_count'] = count($pending_docs);

        // Count expired documents for badge (always needed)
        $expired_docs = $this->gvv_model->get_expired_documents();
        $expiring_soon_docs = $this->gvv_model->get_expiring_soon_documents();
        $this->data['expired_count'] = count($expired_docs) + count($expiring_soon_docs);

        if ($filter === 'expired') {
            // Expired filter: show expired + expiring soon documents
            $this->data['documents'] = $expired_docs;
            $this->data['expiring_soon'] = $expiring_soon_docs;
        } elseif ($filter === 'pending') {
            // Pending filter: show documents awaiting validation
            $this->data['pending_documents'] = $pending_docs;
        } else {
            // No filter: show unassociated + pilot selector
            $this->data['unassociated_documents'] = $this->gvv_model->get_unassociated_documents();

            // Pilot selector
            $this->data['pilot_selector'] = $this->membres_model->selector_with_null(array('actif' => 1));

            // Selected pilot documents
            $selected_pilot = $this->input->get('pilot');
            $this->data['selected_pilot'] = $selected_pilot;
            if ($selected_pilot) {
                $this->data['pilot_documents'] = $this->gvv_model->get_pilot_documents($selected_pilot);
                $this->data['pilot_missing'] = $this->gvv_model->get_missing_documents($selected_pilot, $this->session->userdata('section'));
                $pilot = $this->membres_model->get_by_id('mlogin', $selected_pilot);
                $this->data['pilot_name'] = $pilot ? $pilot['mprenom'] . ' ' . $pilot['mnom'] : $selected_pilot;
            }
        }

        $this->data['kid'] = $this->kid;
        $this->data['controller'] = $this->controller;
        $this->data['message'] = $message;
        $this->data['is_admin'] = true;
        $this->data['is_ca'] = $this->dx_auth->is_role('ca', true, true) || $this->dx_auth->is_admin();
        $this->data['has_modification_rights'] = true;

        return load_last_view($this->table_view, $this->data, $this->unit_test);
    }

    /**
     * Alternate admin view with datatable and filters
     */
    function alternate() {
        if (!$this->_is_admin()) {
            redirect('archived_documents/my_documents');
            return;
        }

        $this->push_return_url("archived_documents alternate");

        $filters = array(
            'expired' => $this->input->get('filter_expired') ? true : false,
            'pending' => $this->input->get('filter_pending') ? true : false,
            'document_type_id' => $this->input->get('document_type_id'),
            'section_id' => $this->input->get('section_id'),
            'pilot_login' => $this->input->get('pilot_login')
        );

        $this->data['filters'] = $filters;
        $this->data['documents'] = $this->gvv_model->get_filtered_documents($filters);

        $type_selector = $this->document_types_model->type_selector();
        $this->data['type_selector'] = array('' => $this->lang->line('archived_documents_filter_all')) + $type_selector;
        $this->data['section_selector'] = array('' => $this->lang->line('archived_documents_filter_all')) + $this->sections_model->section_selector_with_null();
        $this->data['pilot_selector'] = array('' => $this->lang->line('archived_documents_filter_all')) + $this->membres_model->selector_with_null(array('actif' => 1));

        $this->data['controller'] = $this->controller;
        $this->data['is_admin'] = true;
        $this->data['has_modification_rights'] = true;

        return load_last_view($this->controller . '/documentsListView', $this->data, $this->unit_test);
    }

    /**
     * Redirects to page with expired filter
     */
    function expired() {
        redirect('archived_documents/page?filter=expired');
    }

    /**
     * Shows documents for a specific pilot (admin only)
     */
    function pilot_documents($pilot_login) {
        // Check admin access
        if (!$this->_is_admin()) {
            redirect('archived_documents/my_documents');
            return;
        }

        $this->push_return_url("archived_documents pilot_documents");

        $this->data['documents'] = $this->gvv_model->get_pilot_documents($pilot_login);
        $this->data['missing'] = $this->gvv_model->get_missing_documents($pilot_login);
        $this->data['controller'] = $this->controller;
        $this->data['is_admin'] = true;
        $this->data['is_bureau'] = $this->dx_auth->is_role('bureau', true, true);
        $this->data['pilot_login'] = $pilot_login;

        // Get pilot info
        $pilot = $this->membres_model->get_by_id('mlogin', $pilot_login);
        $this->data['title'] = $this->lang->line('archived_documents_documents_of') . ' ' . $pilot['mprenom'] . ' ' . $pilot['mnom'];

        load_last_view($this->controller . '/my_documents', $this->data);
    }

    /**
     * Create form for adding a new document (admin view with pilot/section selectors)
     */
    function create() {
        if (!$this->_is_admin()) {
            redirect('archived_documents/create_pilot');
            return;
        }

        $table = $this->gvv_model->table();
        $this->data = $this->gvvmetadata->defaults_list($table);

        $this->data['pilot_login'] = '';
        $this->data['uploaded_by'] = $this->dx_auth->get_username();

        $this->form_static_element(CREATION);

        return load_last_view($this->controller . '/formView', $this->data, $this->unit_test);
    }

    /**
     * Create form for adding a pilot document (simplified, no pilot/section selectors)
     */
    function create_pilot() {
        $table = $this->gvv_model->table();
        $this->data = $this->gvvmetadata->defaults_list($table);

        $this->data['pilot_login'] = $this->dx_auth->get_username();
        $this->data['uploaded_by'] = $this->dx_auth->get_username();
        $this->data['force_pilot_types'] = true;

        $this->form_static_element(CREATION);

        return load_last_view($this->controller . '/formPilotView', $this->data, $this->unit_test);
    }

    /**
     * Generate form static elements
     */
    function form_static_element($action) {
        parent::form_static_element($action);

        $this->data['is_admin'] = $this->_is_admin();

        if (!empty($this->data['force_pilot_types'])) {
            $type_selector = $this->document_types_model->type_selector('pilot');
            $this->data['type_selector'] = array('' => $this->lang->line('archived_documents_type_other')) + $type_selector;
            $this->data['default_section_id'] = $this->session->userdata('section');
            return;
        }

        if ($this->_is_admin()) {
            // Admin: all document types, pilot and section selectors
            $type_selector = $this->document_types_model->type_selector();
            $this->data['type_selector'] = array('' => $this->lang->line('archived_documents_type_other')) + $type_selector;
            $this->data['section_selector'] = $this->sections_model->section_selector_with_null();
            $this->data['pilot_selector'] = $this->membres_model->selector_with_null(array('actif' => 1));
        } else {
            // Pilot: only pilot-scoped types, default section
            $type_selector = $this->document_types_model->type_selector('pilot');
            $this->data['type_selector'] = array('' => $this->lang->line('archived_documents_type_other')) + $type_selector;
            $this->data['default_section_id'] = $this->session->userdata('section');
        }
    }

    /**
     * Form validation and file upload
     */
    public function formValidation($action, $return_on_success = false) {
        $button = $this->input->post('button');

        // Determine which form view to use for error re-rendering
        $is_admin = $this->_is_admin();
        $error_view = $is_admin ? $this->controller . '/formView' : $this->controller . '/formPilotView';
        $is_pilot_form = ($this->input->post('source') === 'pilot');

        if ($button == $this->lang->line("gvv_button_show_list")) {
            redirect('archived_documents/my_documents');
            return;
        } else if ($button == $this->lang->line("gvv_button_cancel")) {
            $this->pop_return_url();
            return;
        }

        // Get document type to determine storage path
        $document_type_id = $this->input->post('document_type_id');
        if ($document_type_id === '') {
            $document_type_id = null;
        }

        $document_type = null;
        if (!empty($document_type_id)) {
            $document_type = $this->document_types_model->get_by_id('id', $document_type_id);
            if (!$document_type) {
                $this->data['message'] = '<div class="alert alert-danger">Type de document invalide</div>';
                $this->form_static_element($action);
                load_last_view($error_view, $this->data);
                return;
            }
            if (!$is_admin && $document_type['scope'] !== 'pilot') {
                $this->data['message'] = '<div class="alert alert-danger">' . $this->lang->line('archived_documents_pilot_only_types') . '</div>';
                $this->form_static_element($action);
                load_last_view($error_view, $this->data);
                return;
            }
        }

        // Determine pilot login
        $pilot_login = $this->input->post('pilot_login');
        if (!$is_admin && empty($pilot_login)) {
            // Non-admin: force current user
            $pilot_login = $this->dx_auth->get_username();
        }
        // Admin: pilot_login may be empty (club/global document)

        // Security check: non-admin can only upload for themselves
        if (!$is_admin && $pilot_login !== $this->dx_auth->get_username()) {
            $this->data['message'] = '<div class="alert alert-danger">Vous ne pouvez ajouter des documents que pour vous-meme</div>';
            $this->form_static_element($action);
            load_last_view($error_view, $this->data);
            return;
        }

        // Determine section association
        $section_id = $is_admin ? ($this->input->post('section_id') ?: null) : ($this->session->userdata('section') ?: null);

        // Build storage directory
        $dirname = $this->_get_storage_path($document_type, $pilot_login, $section_id);

        if (!$this->_ensure_directory($dirname)) {
            $this->data['message'] = '<div class="alert alert-danger">Impossible de creer le repertoire: ' . $dirname . '</div>';
            $this->form_static_element($action);
            load_last_view($error_view, $this->data);
            return;
        }

        // Handle file upload
        $storage_file = time() . '_' . rand(1000, 9999) . '_' . $this->_sanitize_filename($_FILES['userfile']['name']);

        $config['upload_path'] = $dirname;
        $config['allowed_types'] = 'gif|jpg|jpeg|png|pdf|doc|docx|xls|xlsx|odt|ods|odp|ppt|pptx|html|htm';
        $config['max_size'] = 10000; // 10MB
        $config['file_name'] = $storage_file;

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload("userfile")) {
            $this->data['message'] = '<div class="alert alert-danger">' . $this->upload->display_errors() . '</div>';
            $this->form_static_element($action);
            load_last_view($error_view, $this->data);
            return;
        }

        // Upload success
        $upload_data = $this->upload->data();
        $file_path = $dirname . $storage_file;

        // Compress if possible
        $this->load->library('file_compressor');
        $compression_result = $this->file_compressor->compress($file_path);

        if ($compression_result['success']) {
            $file_path = $compression_result['compressed_path'];
        }

        // Generate PDF thumbnail if applicable
        $mime = mime_content_type($file_path);
        if ($mime === 'application/pdf') {
            $this->load->library('pdf_thumbnail');
            $this->pdf_thumbnail->generate($file_path);
        }

        // Determine validation status: admin/CA = approved, others = pending
        $validation_status = ($is_admin && !$is_pilot_form) ? 'approved' : 'pending';

        // Prepare document data
        $doc_data = array(
            'document_type_id' => $document_type_id ?: null,
            'pilot_login' => !empty($pilot_login) ? $pilot_login : null,
            'section_id' => $section_id,
            'file_path' => $file_path,
            'original_filename' => $_FILES['userfile']['name'],
            'description' => $this->input->post('description'),
            'uploaded_by' => $this->dx_auth->get_username(),
            'valid_from' => mysql_date($this->input->post('valid_from')) ?: null,
            'valid_until' => mysql_date($this->input->post('valid_until')) ?: null,
            'file_size' => $upload_data['file_size'] * 1024, // Convert KB to bytes
            'mime_type' => $mime,
            'validation_status' => $validation_status
        );

        // If admin approves directly, record validation info
        if ($validation_status === 'approved') {
            $doc_data['validated_by'] = $this->dx_auth->get_username();
            $doc_data['validated_at'] = date('Y-m-d H:i:s');
        }

        // Create document (handles versioning automatically)
        $doc_id = $this->gvv_model->create_document($doc_data);

        if ($doc_id) {
            if ($validation_status === 'pending') {
                $this->session->set_flashdata('message', '<div class="alert alert-info"><i class="fas fa-clock"></i> ' . $this->lang->line('archived_documents_pending_notice') . '</div>');
                redirect('archived_documents/my_documents');
            } else {
                $this->session->set_flashdata('message', '<div class="alert alert-success">Document ajoute avec succes</div>');
                redirect($is_pilot_form ? 'archived_documents/my_documents' : ($is_admin ? 'archived_documents/page' : 'archived_documents/my_documents'));
            }
        } else {
            $db_error = $this->db->error();
            $detail = !empty($db_error['message']) ? htmlspecialchars($db_error['message']) : 'create_document a retourné false';
            $this->data['message'] = '<div class="alert alert-danger">Erreur lors de l\'enregistrement du document : ' . $detail . '</div>';
            $this->form_static_element($action);
            load_last_view($error_view, $this->data);
        }
    }

    /**
     * View document details
     */
    function view($id) {
        $doc = $this->gvv_model->get_by_id('id', $id);

        if (!$doc) {
            redirect('archived_documents/my_documents');
            return;
        }

        // Security check
        if (!$this->_is_admin() && $doc['pilot_login'] !== $this->dx_auth->get_username()) {
            redirect('archived_documents/my_documents');
            return;
        }

        $this->data['document'] = $doc;
        $this->data['document']['expiration_status'] = $this->gvv_model->compute_expiration_status($doc);
        $this->data['type'] = !empty($doc['document_type_id'])
            ? $this->document_types_model->get_by_id('id', $doc['document_type_id'])
            : null;
        $this->data['versions'] = $this->gvv_model->get_version_history($id);
        $this->data['controller'] = $this->controller;
        $this->data['is_admin'] = $this->_is_admin();
        $this->data['is_ca'] = $this->dx_auth->is_role('ca', true, true) || $this->dx_auth->is_admin();
        $this->data['is_bureau'] = $this->dx_auth->is_role('bureau', true, true);

        load_last_view($this->controller . '/view', $this->data);
    }

    /**
     * Redirects to page with pending filter
     */
    function pending() {
        redirect('archived_documents/page?filter=pending');
    }

    /**
     * Approve a pending document (admin/CA only)
     */
    function approve($id) {
        if (!$this->_is_admin()) {
            redirect('archived_documents/my_documents');
            return;
        }

        $doc = $this->gvv_model->get_by_id('id', $id);
        if (!$doc || $doc['validation_status'] !== 'pending') {
            $this->session->set_flashdata('message', '<div class="alert alert-warning">Document non trouvé ou déjà traité</div>');
            redirect('archived_documents/page?filter=pending');
            return;
        }

        $result = $this->gvv_model->approve_document($id, $this->dx_auth->get_username());

        if ($result) {
            $this->session->set_flashdata('message', '<div class="alert alert-success"><i class="fas fa-check"></i> Document validé avec succès</div>');
        } else {
            $this->session->set_flashdata('message', '<div class="alert alert-danger">Erreur lors de la validation</div>');
        }

        redirect('archived_documents/page?filter=pending');
    }

    /**
     * Reject a pending document (admin/CA only)
     */
    function reject($id) {
        if (!$this->_is_admin()) {
            redirect('archived_documents/my_documents');
            return;
        }

        $doc = $this->gvv_model->get_by_id('id', $id);
        if (!$doc || $doc['validation_status'] !== 'pending') {
            $this->session->set_flashdata('message', '<div class="alert alert-warning">Document non trouvé ou déjà traité</div>');
            redirect('archived_documents/page?filter=pending');
            return;
        }

        $reason = $this->input->post('rejection_reason');
        $result = $this->gvv_model->reject_document($id, $this->dx_auth->get_username(), $reason);

        if ($result) {
            $this->session->set_flashdata('message', '<div class="alert alert-success"><i class="fas fa-times"></i> Document refusé</div>');
        } else {
            $this->session->set_flashdata('message', '<div class="alert alert-danger">Erreur lors du refus</div>');
        }

        redirect('archived_documents/page?filter=pending');
    }

    /**
     * Delete a document
     */
    function delete($id) {
        $doc = $this->gvv_model->get_by_id('id', $id);

        if (!$doc) {
            redirect('archived_documents/my_documents');
            return;
        }

        $is_admin = $this->_is_admin();
        $current_user = $this->dx_auth->get_username();

        // Security check: pilot can delete own documents, admin can delete all
        if (!$is_admin && $doc['pilot_login'] !== $current_user) {
            $this->session->set_flashdata('message', '<div class="alert alert-danger">Vous ne pouvez pas supprimer ce document</div>');
            redirect('archived_documents/my_documents');
            return;
        }

        // Delete the file
        if (!empty($doc['file_path']) && file_exists($doc['file_path'])) {
            // Delete thumbnail if exists
            $this->load->library('pdf_thumbnail');
            $this->pdf_thumbnail->delete_thumbnail($doc['file_path']);
            unlink($doc['file_path']);
        }

        // Delete from database
        $this->gvv_model->delete_document($id, $current_user, $is_admin);

        $this->session->set_flashdata('message', '<div class="alert alert-success">Document supprime</div>');
        redirect('archived_documents/my_documents');
    }

    /**
     * Toggle alarm for a document (bureau only, AJAX)
     */
    function toggle_alarm($id) {
        // Check bureau access
        if (!$this->dx_auth->is_role('bureau', true, true)) {
            echo json_encode(array('success' => false, 'error' => 'Acces refuse'));
            return;
        }

        $new_state = $this->gvv_model->toggle_alarm($id);

        if ($new_state !== false) {
            echo json_encode(array(
                'success' => true,
                'alarm_disabled' => $new_state,
                'message' => $new_state ? 'Alerte desactivee' : 'Alerte activee'
            ));
        } else {
            echo json_encode(array('success' => false, 'error' => 'Document non trouve'));
        }
    }

    /**
     * Download a document
     */
    function download($id) {
        $doc = $this->gvv_model->get_by_id('id', $id);

        if (!$doc) {
            redirect('archived_documents/my_documents');
            return;
        }

        // Security check
        if (!$this->_is_admin() && $doc['pilot_login'] !== $this->dx_auth->get_username()) {
            redirect('archived_documents/my_documents');
            return;
        }

        if (!file_exists($doc['file_path'])) {
            $this->session->set_flashdata('message', '<div class="alert alert-danger">Fichier non trouve</div>');
            redirect('archived_documents/my_documents');
            return;
        }

        // Force download
        $this->load->helper('download');
        force_download($doc['original_filename'], file_get_contents($doc['file_path']));
    }

    /**
     * Preview/display a document inline in the browser
     * The browser will display it if it supports the MIME type, otherwise it will offer to download.
     */
    function preview($id) {
        $doc = $this->gvv_model->get_by_id('id', $id);

        if (!$doc) {
            redirect('archived_documents/my_documents');
            return;
        }

        // Security check
        if (!$this->_is_admin() && $doc['pilot_login'] !== $this->dx_auth->get_username()) {
            redirect('archived_documents/my_documents');
            return;
        }

        if (!file_exists($doc['file_path'])) {
            $this->session->set_flashdata('message', '<div class="alert alert-danger">Fichier non trouve</div>');
            redirect('archived_documents/my_documents');
            return;
        }

        $mime_type = $doc['mime_type'];
        if (!$mime_type) {
            $mime_type = mime_content_type($doc['file_path']);
        }

        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: inline; filename="' . basename($doc['original_filename']) . '"');
        header('Content-Length: ' . filesize($doc['file_path']));
        readfile($doc['file_path']);
        exit;
    }

    /**
     * Check if current user is admin (CA or admin)
     */
    private function _is_admin() {
        return $this->dx_auth->is_role('ca', true, true) || $this->dx_auth->is_admin();
    }

    /**
     * Get storage path for a document
     */
    private function _get_storage_path($document_type, $pilot_login, $section_id = null) {
        $base = './uploads/documents/';

        if (!$document_type) {
            if (!empty($pilot_login)) {
                return $base . 'pilots/' . $pilot_login . '/other/';
            }
            if (!empty($section_id)) {
                return $base . 'sections/' . $section_id . '/other/';
            }
            return $base . 'club/other/';
        }

        if ($document_type['scope'] === 'pilot') {
            return $base . 'pilots/' . $pilot_login . '/' . $document_type['code'] . '/';
        } elseif ($document_type['scope'] === 'section') {
            $section_id = $section_id ?: ($this->session->userdata('section') ?: 'default');
            return $base . 'sections/' . $section_id . '/' . $document_type['code'] . '/';
        } else {
            return $base . 'club/' . $document_type['code'] . '/';
        }
    }

    /**
     * Ensure directory exists and is writable
     */
    private function _ensure_directory($dirname) {
        if (!file_exists($dirname)) {
            $old_umask = umask(0);
            $created = @mkdir($dirname, 0777, true);
            umask($old_umask);
            return $created;
        }
        return is_writable($dirname);
    }

    /**
     * Sanitize filename for storage
     */
    private function _sanitize_filename($filename) {
        // Remove accents and special characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        // Remove multiple underscores
        $filename = preg_replace('/_+/', '_', $filename);
        return $filename;
    }
}

/* End of file archived_documents.php */
/* Location: ./application/controllers/archived_documents.php */
