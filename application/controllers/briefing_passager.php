<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

include('./application/libraries/Gvv_Controller.php');

/**
 * Passenger Briefing controller
 *
 * Manages risk acceptance declarations for discovery flights (vols de découverte).
 * Supports two modes:
 *  - UC1: Upload of a scanned signed document
 *  - UC2: Digital signature via unique link / QR code
 *  - UC3: Admin list and PDF export
 */
class Briefing_passager extends Gvv_Controller {

    protected $controller        = 'briefing_passager';
    protected $model             = 'archived_documents_model';
    protected $modification_level = 'gestion_vd';

    function __construct() {
        parent::__construct();
        $this->load->model('archived_documents_model');
        $this->load->model('document_types_model');
        $this->load->model('vols_decouverte_model');
        $this->load->model('terrains_model');
        $this->load->model('membres_model');
        $this->load->model('sections_model');
        $this->lang->load('briefing_passager');
        $this->lang->load('vols_decouverte');
        $this->load->library('upload');
    }

    // -----------------------------------------------------------------------
    // UC1 — Standalone briefing form (search + upload)
    // -----------------------------------------------------------------------

    /**
     * Standalone briefing form: search a VLD and upload or generate link.
     */
    function index() {
        if (!$this->dx_auth->is_logged_in()) {
            redirect('welcome/login');
            return;
        }
        $this->data['title']   = $this->lang->line('briefing_passager_title');
        $this->data['vld']     = null;
        $this->data['vld_id']  = null;
        $this->data['briefing'] = null;
        $this->data['message'] = '';
        load_last_view('briefing_passager/indexView', $this->data);
    }

    /**
     * Upload form for a specific VLD (also linked from VLD list icon).
     * @param int $vld_id Discovery flight ID
     */
    function upload($vld_id = 0) {
        if (!$this->dx_auth->is_logged_in()) {
            redirect('welcome/login');
            return;
        }

        $vld_id = (int)$vld_id;
        $vld = $this->vols_decouverte_model->get_by_id('id', $vld_id);
        if (!$vld) {
            $this->data['message'] = '<div class="alert alert-danger">' . $this->lang->line('briefing_passager_not_found') . '</div>';
            $this->data['vld'] = null;
            $this->data['vld_id'] = $vld_id;
            $this->data['briefing'] = null;
            $this->data['title'] = $this->lang->line('briefing_passager_upload');
            load_last_view('briefing_passager/uploadView', $this->data);
            return;
        }

        $existing = $this->archived_documents_model->get_briefing_by_vld($vld_id);

        $this->data['title']           = $this->lang->line('briefing_passager_upload');
        $this->data['vld']             = $vld;
        $this->data['vld_id']          = $vld_id;
        $this->data['briefing']        = $existing;
        $this->data['message']         = '';
        $this->data['terrain_selector'] = $this->terrains_model->selector_with_null();
        $this->data['machine_selector'] = $this->vols_decouverte_model->machine_selector();
        $pilote_selector = $this->membres_model->vd_pilots();
        if (count($pilote_selector) <= 1) {
            $pilote_selector = $this->membres_model->selector_with_null(array('actif' => 1));
        }
        $this->data['pilote_selector'] = $pilote_selector;

        load_last_view('briefing_passager/uploadView', $this->data);
    }

    /**
     * Save VLD fields (date, aerodrome, airplane, name) from the upload form.
     * @param int $vld_id Discovery flight ID
     */
    function update_vld($vld_id = 0) {
        if (!$this->dx_auth->is_logged_in()) {
            redirect('welcome/login');
            return;
        }

        $vld_id = (int)$vld_id;
        $vld = $this->vols_decouverte_model->get_by_id('id', $vld_id);
        if (!$vld) {
            redirect('briefing_passager/upload/' . $vld_id);
            return;
        }

        $date_vol     = trim($this->input->post('date_vol', true));
        $aerodrome    = trim($this->input->post('aerodrome', true));
        $airplane     = trim($this->input->post('airplane_immat', true));
        $beneficiaire = trim($this->input->post('beneficiaire', true));
        $pilote       = trim($this->input->post('pilote', true));

        $update = array();
        if ($date_vol !== '')     $update['date_vol']      = $date_vol;
        if ($aerodrome !== '')    $update['aerodrome']      = $aerodrome;
        else                      $update['aerodrome']      = null;
        if ($airplane !== '')     $update['airplane_immat'] = $airplane;
        else                      $update['airplane_immat'] = null;
        if ($beneficiaire !== '') $update['beneficiaire']   = $beneficiaire;
        if ($pilote !== '')       $update['pilote']         = $pilote;
        else                      $update['pilote']         = null;

        if (!empty($update)) {
            $this->db->where('id', $vld_id)->update('vols_decouverte', $update);
        }

        redirect('briefing_passager/upload/' . $vld_id);
    }

    /**
     * Process the upload form (POST).
     * @param int $vld_id Discovery flight ID
     */
    function upload_submit($vld_id = 0) {
        if (!$this->dx_auth->is_logged_in()) {
            redirect('welcome/login');
            return;
        }

        $vld_id = (int)$vld_id;
        $vld = $this->vols_decouverte_model->get_by_id('id', $vld_id);
        if (!$vld) {
            show_404();
            return;
        }

        // Get briefing_passager document type
        $doc_type = $this->document_types_model->get_by_code('briefing_passager');
        if (!$doc_type) {
            $this->data['message'] = '<div class="alert alert-danger">Type de document briefing_passager introuvable.</div>';
            $this->data['vld'] = $vld;
            $this->data['vld_id'] = $vld_id;
            $this->data['briefing'] = null;
            $this->data['title'] = $this->lang->line('briefing_passager_upload');
            load_last_view('briefing_passager/uploadView', $this->data);
            return;
        }

        $section_id = $vld['club'];
        $dirname = './uploads/documents/sections/' . $section_id . '/briefing_passager/';

        if (!$this->_ensure_directory($dirname)) {
            $this->data['message'] = '<div class="alert alert-danger">Impossible de créer le répertoire de stockage.</div>';
            $this->data['vld'] = $vld;
            $this->data['vld_id'] = $vld_id;
            $this->data['briefing'] = null;
            $this->data['title'] = $this->lang->line('briefing_passager_upload');
            load_last_view('briefing_passager/uploadView', $this->data);
            return;
        }

        $storage_file = time() . '_' . rand(1000, 9999) . '_' . $this->_sanitize_filename($_FILES['userfile']['name']);

        $config = array(
            'upload_path'   => $dirname,
            'allowed_types' => 'jpg|jpeg|png|pdf',
            'max_size'      => 10000,
            'file_name'     => $storage_file,
        );
        $this->upload->initialize($config);

        if (!$this->upload->do_upload('userfile')) {
            $this->data['message'] = '<div class="alert alert-danger">' . $this->upload->display_errors() . '</div>';
            $this->data['vld'] = $vld;
            $this->data['vld_id'] = $vld_id;
            $this->data['briefing'] = null;
            $this->data['title'] = $this->lang->line('briefing_passager_upload');
            load_last_view('briefing_passager/uploadView', $this->data);
            return;
        }

        $upload_data = $this->upload->data();
        $file_path   = $this->_to_relative_path($upload_data['full_path']);

        // Mark any existing current briefing as non-current
        $existing = $this->archived_documents_model->get_briefing_by_vld($vld_id);
        if ($existing) {
            $this->archived_documents_model->update_document($existing['id'], array('is_current_version' => 0));
        }

        $doc_data = array(
            'document_type_id'  => $doc_type['id'],
            'vld_id'            => $vld_id,
            'section_id'        => $section_id,
            'file_path'         => $file_path,
            'original_filename' => $_FILES['userfile']['name'],
            'description'       => 'Briefing passager VLD #' . $vld_id . ' — ' . ($vld['beneficiaire'] ?? ''),
            'uploaded_by'       => $this->dx_auth->get_username(),
            'validation_status' => 'approved',
        );

        $doc_id = $this->archived_documents_model->create_document($doc_data);

        if ($doc_id) {
            redirect('briefing_passager/view/' . $doc_id);
        } else {
            $this->data['message'] = '<div class="alert alert-danger">' . $this->lang->line('briefing_passager_upload_error') . '</div>';
            $this->data['vld'] = $vld;
            $this->data['vld_id'] = $vld_id;
            $this->data['briefing'] = null;
            $this->data['title'] = $this->lang->line('briefing_passager_upload');
            load_last_view('briefing_passager/uploadView', $this->data);
        }
    }

    // -----------------------------------------------------------------------
    // AJAX search
    // -----------------------------------------------------------------------

    /**
     * AJAX endpoint: search VLDs by partial name, flight number or phone.
     * Returns JSON array.
     */
    function search_vld() {
        if (!$this->dx_auth->is_logged_in()) {
            echo json_encode(array());
            return;
        }

        $q = trim($this->input->get('q', true));
        if (strlen($q) < 2) {
            echo json_encode(array());
            return;
        }

        $escaped = '%' . $this->db->escape_like_str($q) . '%';
        $escaped_id = $this->db->escape($escaped);
        $escaped_val = $this->db->escape($escaped);

        $sql = "SELECT id, date_vol, date_vente, beneficiaire, airplane_immat, aerodrome, pilote, club
                FROM vols_decouverte
                WHERE cancelled = 0
                  AND (beneficiaire LIKE {$escaped_val}
                       OR CAST(id AS CHAR) LIKE {$escaped_id}
                       OR beneficiaire_tel LIKE {$escaped_val})
                ORDER BY date_vente DESC
                LIMIT 20";

        $results = $this->db->query($sql)->result_array();

        $output = array();
        foreach ($results as $row) {
            $output[] = array(
                'id'           => $row['id'],
                'label'        => '#' . $row['id'] . ' — ' . ($row['beneficiaire'] ?? '') .
                                  ($row['date_vol'] ? ' — ' . date_db2ht($row['date_vol']) : '') .
                                  ($row['aerodrome'] ? ' (' . $row['aerodrome'] . ')' : ''),
                'beneficiaire' => $row['beneficiaire'],
                'date_vol'     => $row['date_vol'],
                'aerodrome'    => $row['aerodrome'],
                'airplane_immat' => $row['airplane_immat'],
            );
        }

        header('Content-Type: application/json');
        echo json_encode($output);
    }

    // -----------------------------------------------------------------------
    // View a briefing document
    // -----------------------------------------------------------------------

    /**
     * View a briefing archived document.
     * @param int $id Archived document ID
     */
    function view($id = 0) {
        if (!$this->dx_auth->is_logged_in()) {
            redirect('welcome/login');
            return;
        }

        $id = (int)$id;
        $doc = $this->archived_documents_model->get_by_id('id', $id);
        if (!$doc) {
            show_404();
            return;
        }

        $vld = null;
        if (!empty($doc['vld_id'])) {
            $vld = $this->vols_decouverte_model->get_by_id('id', $doc['vld_id']);
        }

        $dev_menu_users = array_map('trim', explode(',', $this->config->item('dev_menu_users') ?: ''));
        $current_user   = $this->session->userdata('DX_username');

        $this->data['title']        = $this->lang->line('briefing_passager_title');
        $this->data['doc']          = $doc;
        $this->data['vld']          = $vld;
        $this->data['message']      = '';
        $this->data['is_dev_user']  = in_array($current_user, $dev_menu_users);

        load_last_view('briefing_passager/viewView', $this->data);
    }

    // -----------------------------------------------------------------------
    // Delete a briefing document (dev users only)
    // -----------------------------------------------------------------------

    /**
     * Delete a briefing archived document. Restricted to dev_menu_users.
     * @param int $id Archived document ID
     */
    function delete($id = 0) {
        if (!$this->dx_auth->is_logged_in()) {
            redirect('welcome/login');
            return;
        }

        $dev_menu_users = array_map('trim', explode(',', $this->config->item('dev_menu_users') ?: ''));
        $current_user   = $this->session->userdata('DX_username');
        if (!in_array($current_user, $dev_menu_users)) {
            show_error('Accès refusé', 403);
            return;
        }

        $id  = (int)$id;
        $doc = $this->archived_documents_model->get_by_id('id', $id);
        if (!$doc) {
            show_404();
            return;
        }

        if (!empty($doc['file_path']) && file_exists($doc['file_path'])) {
            unlink($doc['file_path']);
        }

        $this->archived_documents_model->delete_document($id, $current_user, true);

        redirect('briefing_passager/admin_list');
    }

    // -----------------------------------------------------------------------
    // UC3 — Admin list
    // -----------------------------------------------------------------------

    /**
     * Admin list of all briefings for the past N days.
     */
    function admin_list() {
        if (!$this->dx_auth->is_logged_in()) {
            redirect('welcome/login');
            return;
        }
        if (!$this->_is_admin()) {
            show_error('Accès refusé', 403);
            return;
        }

        $days = (int)($this->input->get('days') ?: 90);
        if ($days <= 0 || $days > 3650) {
            $days = 90;
        }

        $briefings = $this->archived_documents_model->get_briefings_recent($days);

        $this->data['title']    = $this->lang->line('briefing_passager_list_title');
        $this->data['briefings'] = $briefings;
        $this->data['days']     = $days;
        $this->data['message']  = '';

        load_last_view('briefing_passager/adminListView', $this->data);
    }

    /**
     * Export the admin list as PDF.
     */
    function export_pdf() {
        if (!$this->dx_auth->is_logged_in()) {
            redirect('welcome/login');
            return;
        }
        if (!$this->_is_admin()) {
            show_error('Accès refusé', 403);
            return;
        }

        $days = (int)($this->input->get('days') ?: 90);
        if ($days <= 0 || $days > 3650) {
            $days = 90;
        }

        $briefings = $this->archived_documents_model->get_briefings_recent($days);

        $this->load->library('tcpdf');
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('GVV');
        $pdf->SetTitle($this->lang->line('briefing_passager_list_title'));
        $pdf->SetMargins(10, 20, 10);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, $this->lang->line('briefing_passager_list_title') . ' — ' . $days . ' derniers jours', 0, 1, 'C');
        $pdf->SetFont('helvetica', 'B', 9);

        $headers = array(
            $this->lang->line('briefing_passager_field_date_vol'),
            $this->lang->line('briefing_passager_field_aerodrome'),
            $this->lang->line('briefing_passager_field_appareil'),
            $this->lang->line('briefing_passager_field_pilote'),
            $this->lang->line('briefing_passager_field_nom'),
            $this->lang->line('briefing_passager_field_mode'),
            $this->lang->line('briefing_passager_field_date_sign'),
        );
        $widths = array(28, 40, 28, 30, 50, 32, 32);

        foreach ($headers as $i => $h) {
            $pdf->Cell($widths[$i], 7, $h, 1, 0, 'C');
        }
        $pdf->Ln();

        $pdf->SetFont('helvetica', '', 8);
        foreach ($briefings as $b) {
            $pdf->Cell($widths[0], 6, $b['date_vol'] ? date_db2ht($b['date_vol']) : '', 1);
            $pdf->Cell($widths[1], 6, $b['aerodrome'] ?? '', 1);
            $pdf->Cell($widths[2], 6, $b['airplane_immat'] ?? '', 1);
            $pdf->Cell($widths[3], 6, $b['pilote'] ?? '', 1);
            $pdf->Cell($widths[4], 6, $b['beneficiaire'] ?? '', 1);
            $pdf->Cell($widths[5], 6, $b['type_code'] === 'briefing_passager' ? $this->lang->line('briefing_passager_mode_upload') : '', 1);
            $pdf->Cell($widths[6], 6, $b['uploaded_at'] ? date('d/m/Y', strtotime($b['uploaded_at'])) : '', 1);
            $pdf->Ln();
        }

        $pdf->Output('briefings_passagers.pdf', 'D');
    }

    // -----------------------------------------------------------------------
    // UC2 — Generate digital signature link
    // -----------------------------------------------------------------------

    /**
     * Generate a one-time signature token for a VLD and redirect to the sign page.
     * @param int $vld_id Discovery flight ID
     */
    function generate_link($vld_id = 0) {
        if (!$this->dx_auth->is_logged_in()) {
            redirect('welcome/login');
            return;
        }

        $vld_id = (int)$vld_id;
        $vld = $this->vols_decouverte_model->get_by_id('id', $vld_id);
        if (!$vld) {
            show_404();
            return;
        }

        $token = bin2hex(random_bytes(32)); // 64 hex chars

        $this->db->insert('briefing_tokens', array(
            'vld_id'     => $vld_id,
            'token'      => $token,
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days')),
        ));

        $this->data['title']   = $this->lang->line('briefing_passager_link_title');
        $this->data['vld']     = $vld;
        $this->data['vld_id']  = $vld_id;
        $this->data['token']   = $token;
        $this->data['sign_url'] = site_url('briefing_sign/' . $token);
        $this->data['message'] = '';

        load_last_view('briefing_passager/linkView', $this->data);
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    private function _is_admin() {
        return $this->dx_auth->is_role('ca', true, true) || $this->dx_auth->is_admin();
    }

    private function _ensure_directory($dirname) {
        if (!file_exists($dirname)) {
            $old_umask = umask(0);
            $created = @mkdir($dirname, 0777, true);
            umask($old_umask);
            return $created;
        }
        return is_writable($dirname);
    }

    private function _sanitize_filename($filename) {
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        return substr($filename, 0, 200);
    }

    private function _to_relative_path($abs_path) {
        $base = realpath('./') . '/';
        if (strpos($abs_path, $base) === 0) {
            return substr($abs_path, strlen($base));
        }
        return $abs_path;
    }
}

/* End of file briefing_passager.php */
/* Location: ./application/controllers/briefing_passager.php */
