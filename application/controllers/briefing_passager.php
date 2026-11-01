<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

include('./application/libraries/Gvv_Controller.php');

/**
 * Passenger Briefing controller
 *
 * Manages risk acceptance declarations for discovery flights (vols de découverte).
 * A briefing is a response to the briefing-passager-ulm form attached to the flight:
 *  - UC1: deposit of the scan or photo of a paper document signed by the passenger
 *  - UC2: digital signature on the online form
 *  - UC3: admin list and PDF export (forms_admin/submissions)
 */
class Briefing_passager extends Gvv_Controller {

    protected $controller        = 'briefing_passager';
    protected $back_dashboard    = 'welcome/section/flights';
    protected $model             = 'archived_documents_model';
    protected $modification_level = 'gestion_vd';

    const BRIEFING_FORM_SLUG = 'briefing-passager-ulm';

    function __construct() {
        parent::__construct();
        $this->load->model('archived_documents_model');
        $this->load->model('vols_decouverte_model');
        $this->load->model('terrains_model');
        $this->load->model('membres_model');
        $this->load->model('sections_model');
        $this->load->model('configuration_model');
        $this->load->model('forms_model');
        $this->load->model('form_submissions_model');
        $this->lang->load('briefing_passager');
        $this->lang->load('vols_decouverte');
    }

    /**
     * pilote_vd implies gestion_vd: a VD pilot has all briefing management permissions.
     */
    public function user_has_role($role) {
        if ($role === 'gestion_vd') {
            return parent::user_has_role('gestion_vd') || parent::user_has_role('pilote_vd');
        }
        return parent::user_has_role($role);
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
        if (!$this->ensure_modification_rights()) return;

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

        $target_form = $this->forms_model->get_by_public_slug(self::BRIEFING_FORM_SLUG);
        $form_submission = $target_form
            ? $this->form_submissions_model->get_current_for_subject('vols_decouverte', $vld_id, (int) $target_form['id'])
            : null;

        $dev_users = array_map('trim', explode(',', $this->config->item('dev_users') ?: ''));
        $current_user   = $this->session->userdata('DX_username');

        // Pre-fill aerodrome default when not yet set on the VLD
        if (empty($vld['aerodrome'])) {
            $defaut_aerodrome = $this->configuration_model->get_param('defaut.aerodrome');
            if ($defaut_aerodrome) {
                $terrain = $this->terrains_model->get_by_id('oaci', $defaut_aerodrome);
                if (!empty($terrain)) {
                    $vld['aerodrome'] = $defaut_aerodrome;
                }
            }
        }

        $this->data['title']           = $this->lang->line('briefing_passager_upload');
        $this->data['vld']             = $vld;
        $this->data['vld_id']          = $vld_id;
        $this->data['briefing']        = $existing;
        $this->data['form_submission'] = $form_submission;
        $this->data['is_dev_user']     = in_array($current_user, $dev_users);
        $this->data['message']         = '';
        $this->_load_upload_selectors();

        load_last_view('briefing_passager/uploadView', $this->data);
    }

    /**
     * Save VLD fields (date, aerodrome, airplane, name) from the upload form.
     * Kept for backward compatibility (direct GET/POST to this URL).
     * @param int $vld_id Discovery flight ID
     */
    function update_vld($vld_id = 0) {
        if (!$this->dx_auth->is_logged_in()) {
            redirect('welcome/login');
            return;
        }
        if (!$this->ensure_modification_rights()) return;
        $vld_id = (int)$vld_id;
        $this->_save_vld_fields($vld_id);
        redirect('briefing_passager/upload/' . $vld_id);
    }

    /**
     * Unified form handler: saves VLD fields, then dispatches based on action button.
     * action=save   → save fields and return to upload form
     * action=link2  → save fields and open the online form, prefilled for this flight
     * action=upload → save fields and deposit the signed paper document
     * @param int $vld_id Discovery flight ID
     */
    function upload_submit($vld_id = 0) {
        if (!$this->dx_auth->is_logged_in()) {
            redirect('welcome/login');
            return;
        }
        if (!$this->ensure_modification_rights()) return;

        $vld_id = (int)$vld_id;
        $vld = $this->vols_decouverte_model->get_by_id('id', $vld_id);
        if (!$vld) {
            show_404();
            return;
        }

        // Validate required fields before saving
        $aerodrome    = trim($this->input->post('aerodrome', true));
        $airplane     = trim($this->input->post('airplane_immat', true));
        $pilote       = trim($this->input->post('pilote', true));

        $errors = array();
        if ($aerodrome    === '') $errors[] = $this->lang->line('briefing_passager_field_aerodrome');
        if ($airplane     === '') $errors[] = $this->lang->line('briefing_passager_field_appareil');
        if ($pilote       === '') $errors[] = $this->lang->line('briefing_passager_field_pilote');

        if (!empty($errors)) {
            // Merge posted values into $vld so the form keeps the user's input
            $vld = array_merge($vld, array(
                'date_vol'      => trim($this->input->post('date_vol', true)) ?: ($vld['date_vol'] ?? ''),
                'aerodrome'     => $aerodrome,
                'airplane_immat'=> $airplane,
                'beneficiaire'  => trim($this->input->post('beneficiaire', true)),
                'pilote'        => $pilote,
            ));
            $fields = implode(', ', $errors);
            $this->data['message'] = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> '
                . sprintf($this->lang->line('briefing_passager_fields_required'), $fields)
                . '</div>';
            $this->_render_upload_form($vld, $vld_id);
            return;
        }

        // Save VLD fields
        $this->_save_vld_fields($vld_id);

        $action = $this->input->post('action');
        $vld = $this->vols_decouverte_model->get_by_id('id', $vld_id);

        if ($action === 'link2') {
            $errors2 = array();
            if (empty($vld['date_vol']))       $errors2[] = $this->lang->line('briefing_passager_field_date_vol');
            if (empty($vld['aerodrome']))       $errors2[] = $this->lang->line('briefing_passager_field_aerodrome');
            if (empty($vld['airplane_immat']))  $errors2[] = $this->lang->line('briefing_passager_field_appareil');
            if (empty($vld['beneficiaire']))    $errors2[] = $this->lang->line('briefing_passager_field_nom');
            if (empty($vld['pilote']))          $errors2[] = $this->lang->line('briefing_passager_field_pilote');

            if (!empty($errors2)) {
                $fields = implode(', ', $errors2);
                $this->data['message'] = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> '
                    . sprintf($this->lang->line('briefing_passager_fields_required'), $fields)
                    . '</div>';
                $this->_render_upload_form($vld, $vld_id);
                return;
            }

            $target_form = $this->_published_briefing_form($vld, $vld_id);
            if (!$target_form) {
                return;
            }

            $params = http_build_query(array(
                'date_vol'           => $vld['date_vol'],
                'site_decollage'     => $vld['aerodrome'],
                'identification_ulm' => $vld['airplane_immat'],
                'nom'                => $vld['beneficiaire'],
                'pilot_login'        => $vld['pilote'],
                'subject_type'       => 'vols_decouverte',
                'subject_id'         => $vld_id,
            ));
            redirect('forms/' . $target_form['public_slug'] . '?' . $params);
            return;
        }

        if ($action === 'upload') {
            $this->_deposit_paper_briefing($vld, $vld_id);
            return;
        }

        redirect('briefing_passager/upload/' . $vld_id);
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

        $escaped = $this->db->escape('%' . $this->db->escape_like_str($q) . '%');

        $sql = "SELECT id, date_vol, date_vente, beneficiaire, airplane_immat, aerodrome, pilote, club
                FROM vols_decouverte
                WHERE cancelled = 0
                  AND (beneficiaire LIKE {$escaped}
                       OR CAST(id AS CHAR) LIKE {$escaped}
                       OR beneficiaire_tel LIKE {$escaped})
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

        $dev_users = array_map('trim', explode(',', $this->config->item('dev_users') ?: ''));
        $current_user   = $this->session->userdata('DX_username');

        $this->data['title']        = $this->lang->line('briefing_passager_title');
        $this->data['doc']          = $doc;
        $this->data['vld']          = $vld;
        $this->data['message']      = '';
        $this->data['is_dev_user']  = in_array($current_user, $dev_users);

        load_last_view('briefing_passager/viewView', $this->data);
    }

    // -----------------------------------------------------------------------
    // Delete a briefing document (dev users only)
    // -----------------------------------------------------------------------

    /**
     * Delete a briefing archived document. Restricted to dev_users.
     * @param int $id Archived document ID
     */
    function delete($id = 0) {
        if (!$this->dx_auth->is_logged_in()) {
            redirect('welcome/login');
            return;
        }

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            show_error('Méthode non autorisée', 405);
            return;
        }

        $dev_users = array_map('trim', explode(',', $this->config->item('dev_users') ?: ''));
        $current_user   = $this->session->userdata('DX_username');
        if (!in_array($current_user, $dev_users)) {
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

        $vld_id = !empty($doc['vld_id']) ? (int)$doc['vld_id'] : 0;

        $this->archived_documents_model->delete_document($id, $current_user, true);

        if ($vld_id) {
            redirect('briefing_passager/upload/' . $vld_id);
        } else {
            redirect('forms_admin/submissions/briefing_passager_ulm');
        }
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    private function _save_vld_fields($vld_id) {
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
    }

    private function _load_upload_selectors() {
        $this->data['terrain_selector'] = $this->terrains_model->selector_with_null();
        $this->data['machine_selector'] = $this->vols_decouverte_model->machine_selector();
        $pilote_selector = $this->membres_model->vd_pilots();
        if (count($pilote_selector) <= 1) {
            $pilote_selector = $this->membres_model->selector_with_null(array('actif' => 1));
        }
        $this->data['pilote_selector'] = $pilote_selector;
    }

    /**
     * Displays the briefing form of a flight, with $this->data['message'] if any.
     */
    private function _render_upload_form($vld, $vld_id) {
        $dev_users   = array_map('trim', explode(',', $this->config->item('dev_users') ?: ''));
        $target_form = $this->forms_model->get_by_public_slug(self::BRIEFING_FORM_SLUG);

        $this->data['title']           = $this->lang->line('briefing_passager_upload');
        $this->data['vld']             = $vld;
        $this->data['vld_id']          = $vld_id;
        $this->data['briefing']        = $this->archived_documents_model->get_briefing_by_vld($vld_id);
        $this->data['form_submission'] = $target_form
            ? $this->form_submissions_model->get_current_for_subject('vols_decouverte', $vld_id, (int) $target_form['id'])
            : null;
        $this->data['is_dev_user']     = in_array($this->session->userdata('DX_username'), $dev_users);
        $this->_load_upload_selectors();
        load_last_view('briefing_passager/uploadView', $this->data);
    }

    /**
     * Returns the published briefing form. When it is unavailable, explains why
     * on the briefing page of the flight and returns null.
     */
    private function _published_briefing_form($vld, $vld_id) {
        $target_form = $this->forms_model->get_by_public_slug(self::BRIEFING_FORM_SLUG);
        if ($target_form && $target_form['status'] === 'published') {
            return $target_form;
        }

        $this->data['message'] = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> '
            . sprintf($this->lang->line('briefing_passager_form_unavailable'), self::BRIEFING_FORM_SLUG)
            . '</div>';
        $this->_render_upload_form($vld, $vld_id);
        return null;
    }

    /**
     * Files the scan or photo of a paper briefing as a response of the briefing
     * form attached to the flight, so it counts as the briefing of that flight.
     * Independent from forms.allow_upload_response: that option opens the deposit
     * on the public page, whereas this one is reserved to users managing briefings.
     */
    private function _deposit_paper_briefing($vld, $vld_id) {
        $target_form = $this->_published_briefing_form($vld, $vld_id);
        if (!$target_form) {
            return;
        }

        $this->load->library('forms_response_upload');
        $username = $this->dx_auth->get_username();
        $result = $this->forms_response_upload->store($target_form, 'userfile', array(
            'upload_comment' => sprintf($this->lang->line('briefing_passager_paper_comment'), $vld_id, $vld['beneficiaire'] ?? ''),
            'subject_type'   => 'vols_decouverte',
            'subject_id'     => $vld_id,
            'submitter_name' => $username,
            'created_by'     => $username,
        ));

        if ($result['error'] !== null) {
            $this->data['message'] = $this->_alert('danger', $result['error']);
            $this->_render_upload_form($vld, $vld_id);
            return;
        }

        $this->session->set_flashdata('message', $this->_alert('success', $this->lang->line('briefing_passager_upload_success')));
        redirect('vols_decouverte/page');
    }

    private function _alert($type, $message) {
        return '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">'
             . htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
             . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
             . '</div>';
    }

}

/* End of file briefing_passager.php */
/* Location: ./application/controllers/briefing_passager.php */
