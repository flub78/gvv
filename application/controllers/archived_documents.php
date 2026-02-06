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
        $this->lang->load('archived_documents');
        $this->load->model('document_types_model');
        $this->load->model('membres_model');
        $this->load->model('sections_model');
    }

    /**
     * Default page - shows current user's documents
     */
    function index() {
        $this->my_documents();
    }

    /**
     * Shows current user's documents
     */
    function my_documents() {
        $pilot_login = $this->dx_auth->get_username();

        $this->data['documents'] = $this->gvv_model->get_pilot_documents($pilot_login);
        $this->data['missing'] = $this->gvv_model->get_missing_documents($pilot_login, $this->session->userdata('section'));
        $this->data['controller'] = $this->controller;
        $this->data['is_admin'] = $this->dx_auth->is_role('ca', true, true) || $this->dx_auth->is_admin();
        $this->data['pilot_login'] = $pilot_login;
        $this->data['title'] = 'Mes documents';

        load_last_view($this->controller . '/my_documents', $this->data);
    }

    /**
     * Shows all documents (admin only)
     */
    function page($premier = 0, $message = '', $selection = array()) {
        // Check admin access
        if (!$this->_is_admin()) {
            redirect('archived_documents/my_documents');
            return;
        }

        $this->push_return_url("archived_documents page");

        $this->data['select_result'] = $this->gvv_model->select_page(PER_PAGE, $premier, $selection);
        $this->data['kid'] = $this->kid;
        $this->data['controller'] = $this->controller;
        $this->data['count'] = $this->gvv_model->count();
        $this->data['premier'] = $premier;
        $this->data['message'] = $message;
        $this->data['is_admin'] = true;
        $this->data['has_modification_rights'] = true;

        return load_last_view($this->table_view, $this->data, $this->unit_test);
    }

    /**
     * Shows expired documents list (admin only)
     */
    function expired() {
        // Check admin access
        if (!$this->_is_admin()) {
            redirect('archived_documents/my_documents');
            return;
        }

        $this->data['documents'] = $this->gvv_model->get_expired_documents();
        $this->data['expiring_soon'] = $this->gvv_model->get_expiring_soon_documents();
        $this->data['controller'] = $this->controller;
        $this->data['is_admin'] = true;
        $this->data['title'] = 'Documents expires';

        load_last_view($this->controller . '/expired', $this->data);
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

        $this->data['documents'] = $this->gvv_model->get_pilot_documents($pilot_login);
        $this->data['missing'] = $this->gvv_model->get_missing_documents($pilot_login);
        $this->data['controller'] = $this->controller;
        $this->data['is_admin'] = true;
        $this->data['pilot_login'] = $pilot_login;

        // Get pilot info
        $pilot = $this->membres_model->get_by_id('mlogin', $pilot_login);
        $this->data['title'] = 'Documents de ' . $pilot['mprenom'] . ' ' . $pilot['mnom'];

        load_last_view($this->controller . '/my_documents', $this->data);
    }

    /**
     * Create form for adding a new document
     */
    function create() {
        $table = $this->gvv_model->table();
        $this->data = $this->gvvmetadata->defaults_list($table);

        // Pre-fill with current user
        $this->data['pilot_login'] = $this->dx_auth->get_username();
        $this->data['uploaded_by'] = $this->dx_auth->get_username();

        $this->form_static_element(CREATION);

        return load_last_view($this->form_view, $this->data, $this->unit_test);
    }

    /**
     * Generate form static elements
     */
    function form_static_element($action) {
        parent::form_static_element($action);

        // Document type selector
        $this->data['type_selector'] = $this->document_types_model->type_selector('pilot');

        // Section selector for admins
        if ($this->_is_admin()) {
            $this->data['section_selector'] = $this->sections_model->section_selector_with_null();
            $this->data['pilot_selector'] = $this->membres_model->get_selector();
        }

        $this->data['is_admin'] = $this->_is_admin();
    }

    /**
     * Form validation and file upload
     */
    public function formValidation($action, $return_on_success = false) {
        $button = $this->input->post('button');

        if ($button == $this->lang->line("gvv_button_show_list")) {
            redirect('archived_documents/my_documents');
            return;
        } else if ($button == $this->lang->line("gvv_button_cancel")) {
            $this->pop_return_url();
            return;
        }

        // Get document type to determine storage path
        $document_type_id = $this->input->post('document_type_id');
        $document_type = $this->document_types_model->get_by_id('id', $document_type_id);

        if (!$document_type) {
            $this->data['message'] = '<div class="alert alert-danger">Type de document invalide</div>';
            $this->form_static_element($action);
            load_last_view($this->form_view, $this->data);
            return;
        }

        // Determine pilot login
        $pilot_login = $this->input->post('pilot_login');
        if (empty($pilot_login)) {
            $pilot_login = $this->dx_auth->get_username();
        }

        // Security check: non-admin can only upload for themselves
        if (!$this->_is_admin() && $pilot_login !== $this->dx_auth->get_username()) {
            $this->data['message'] = '<div class="alert alert-danger">Vous ne pouvez ajouter des documents que pour vous-meme</div>';
            $this->form_static_element($action);
            load_last_view($this->form_view, $this->data);
            return;
        }

        // Build storage directory
        $dirname = $this->_get_storage_path($document_type, $pilot_login);

        if (!$this->_ensure_directory($dirname)) {
            $this->data['message'] = '<div class="alert alert-danger">Impossible de creer le repertoire: ' . $dirname . '</div>';
            $this->form_static_element($action);
            load_last_view($this->form_view, $this->data);
            return;
        }

        // Handle file upload
        $storage_file = time() . '_' . rand(1000, 9999) . '_' . $this->_sanitize_filename($_FILES['userfile']['name']);

        $config['upload_path'] = $dirname;
        $config['allowed_types'] = 'gif|jpg|jpeg|png|pdf';
        $config['max_size'] = 10000; // 10MB
        $config['file_name'] = $storage_file;

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload("userfile")) {
            $this->data['message'] = '<div class="alert alert-danger">' . $this->upload->display_errors() . '</div>';
            $this->form_static_element($action);
            load_last_view($this->form_view, $this->data);
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

        // Prepare document data
        $doc_data = array(
            'document_type_id' => $document_type_id,
            'pilot_login' => $pilot_login,
            'section_id' => $this->input->post('section_id') ?: null,
            'file_path' => $file_path,
            'original_filename' => $_FILES['userfile']['name'],
            'description' => $this->input->post('description'),
            'uploaded_by' => $this->dx_auth->get_username(),
            'valid_from' => mysql_date($this->input->post('valid_from')) ?: null,
            'valid_until' => mysql_date($this->input->post('valid_until')) ?: null,
            'file_size' => $upload_data['file_size'] * 1024, // Convert KB to bytes
            'mime_type' => $mime
        );

        // Create document (handles versioning automatically)
        $doc_id = $this->gvv_model->create_document($doc_data);

        if ($doc_id) {
            $this->session->set_flashdata('message', '<div class="alert alert-success">Document ajoute avec succes</div>');
            redirect('archived_documents/my_documents');
        } else {
            $this->data['message'] = '<div class="alert alert-danger">Erreur lors de l\'enregistrement du document</div>';
            $this->form_static_element($action);
            load_last_view($this->form_view, $this->data);
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
        $this->data['type'] = $this->document_types_model->get_by_id('id', $doc['document_type_id']);
        $this->data['versions'] = $this->gvv_model->get_version_history($id);
        $this->data['controller'] = $this->controller;
        $this->data['is_admin'] = $this->_is_admin();

        load_last_view($this->controller . '/view', $this->data);
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
     * Toggle alarm for a document (admin only, AJAX)
     */
    function toggle_alarm($id) {
        // Check admin access
        if (!$this->_is_admin()) {
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
     * Check if current user is admin (CA or admin)
     */
    private function _is_admin() {
        return $this->dx_auth->is_role('ca', true, true) || $this->dx_auth->is_admin();
    }

    /**
     * Get storage path for a document
     */
    private function _get_storage_path($document_type, $pilot_login) {
        $base = './uploads/documents/';

        if ($document_type['scope'] === 'pilot') {
            return $base . 'pilots/' . $pilot_login . '/' . $document_type['code'] . '/';
        } elseif ($document_type['scope'] === 'section') {
            $section_id = $this->session->userdata('section') ?: 'default';
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
