<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Maintenance Bulletins Controller
 *
 * Bulletins de service : documents types et versionnes (systeme
 * documentaire existant, scope 'machine'), rattaches a un aeronef via
 * machine_immat, avec un statut applicatif (a_traiter / traite /
 * non_applicable) porte par maintenance_bulletin_statuts.
 *
 * Seuls mecano/admin peuvent acceder a cet ecran (PRD EF6.3) -- deja
 * garanti par le filtre de role du controleur, comme les autres ecrans
 * du module.
 *
 * @package controllers
 * @see doc/prds/maintenance_aeronefs_prd.md (EF6)
 * @see doc/plans/maintenance_aeronefs_plan.md (Etape 5.5)
 */
class Maintenance_bulletins extends MY_Controller {

    public function __construct() {
        parent::__construct();

        $this->load->model('maintenance_bulletin_model');
        $this->load->model('maintenance_equipement_model');
        $this->load->model('document_types_model');
        $this->load->model('archived_documents_model');
        $this->load->library('form_validation');
        $this->lang->load('maintenance');
        $this->lang->load('gvv');

        if (!$this->dx_auth->is_logged_in()) {
            redirect('auth/login');
        }

        if (!$this->user_has_role('mecano')) {
            show_error($this->lang->line('maintenance_acces_refuse'), 403);
        }

        $this->lang->load('tableaux_de_bord');
        $this->load->vars([
            'nav_back_url'   => $this->session->userdata('nav_from_url')   ?: 'welcome/section/maintenance',
            'nav_back_label' => $this->session->userdata('nav_from_label') ?: $this->lang->line('db_section_maintenance'),
        ]);
    }

    /**
     * Liste des bulletins d'un aeronef, ou selecteur d'aeronef si aucun choisi
     * @param string $machine_immat
     */
    public function index($machine_immat = '') {
        $bulletins = array();
        if ($machine_immat) {
            $document_type = $this->document_types_model->get_by_code('maintenance_bulletin');
            $bulletins = $this->maintenance_bulletin_model->get_by_machine($machine_immat, $document_type ? $document_type['id'] : null);
        }

        $data = array(
            'controller'       => 'maintenance_bulletins',
            'machine_immat'    => $machine_immat,
            'aeronef_selector' => $this->maintenance_equipement_model->get_aeronef_selector(),
            'bulletins'        => $bulletins,
            'statuts'          => $this->statuts_selector(),
        );

        $this->load->view('maintenance_bulletins/index', $data);
    }

    /**
     * Libelles traduits des statuts de bulletin (le modele ne porte que
     * les cles, jamais de texte affichable, pour rester independant de la langue).
     *
     * @return array [statut => libelle]
     */
    private function statuts_selector() {
        $result = array();
        foreach (array_keys(Maintenance_bulletin_model::get_statuts()) as $statut) {
            $result[$statut] = $this->lang->line('maintenance_bulletin_statut_' . $statut);
        }
        return $result;
    }

    /**
     * Formulaire de depot d'un nouveau bulletin
     * @param string $machine_immat
     */
    public function upload_form($machine_immat = '') {
        if (empty($machine_immat)) {
            redirect('maintenance_bulletins');
        }

        $data = array(
            'controller'    => 'maintenance_bulletins',
            'machine_immat' => $machine_immat,
            'error'         => '',
        );

        $this->load->view('maintenance_bulletins/upload', $data);
    }

    /**
     * Depot d'un bulletin de service
     * @param string $machine_immat
     */
    public function upload($machine_immat = '') {
        if (empty($machine_immat)) {
            redirect('maintenance_bulletins');
        }

        if (empty($_FILES['bulletin_file']['name']) || $_FILES['bulletin_file']['error'] !== UPLOAD_ERR_OK) {
            $this->session->set_flashdata('error', $this->lang->line('maintenance_bulletin_upload_error'));
            redirect('maintenance_bulletins/upload_form/' . $machine_immat);
            return;
        }

        $dirname = './uploads/documents/club/maintenance_bulletin/';
        if (!file_exists($dirname)) {
            $old_umask = umask(0);
            @mkdir($dirname, 0777, true);
            umask($old_umask);
        }

        $storage_file = time() . '_' . rand(1000, 9999) . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $_FILES['bulletin_file']['name']);
        $config['upload_path'] = $dirname;
        $config['allowed_types'] = 'pdf|jpg|jpeg|png|gif|doc|docx';
        $config['max_size'] = 10000; // 10MB
        $config['file_name'] = $storage_file;

        $this->load->library('upload', $config);
        if (!$this->upload->do_upload('bulletin_file')) {
            $this->session->set_flashdata('error', $this->upload->display_errors());
            redirect('maintenance_bulletins/upload_form/' . $machine_immat);
            return;
        }

        $upload_data = $this->upload->data();
        $document_type = $this->document_types_model->get_by_code('maintenance_bulletin');

        $this->archived_documents_model->create_document(array(
            'document_type_id'  => $document_type ? $document_type['id'] : null,
            'pilot_login'       => null,
            'section_id'        => null,
            'machine_immat'     => $machine_immat,
            'file_path'         => $dirname . $upload_data['file_name'],
            'original_filename' => $_FILES['bulletin_file']['name'],
            'description'       => $this->input->post('description'),
            'uploaded_by'       => $this->dx_auth->get_username(),
            'file_size'         => $upload_data['file_size'] * 1024,
            'mime_type'         => $upload_data['file_type'],
            'validation_status' => 'approved',
            'validated_by'      => $this->dx_auth->get_username(),
            'validated_at'      => date('Y-m-d H:i:s'),
        ));

        $this->session->set_flashdata('success', $this->lang->line('maintenance_bulletin_uploaded'));
        redirect('maintenance_bulletins/index/' . $machine_immat);
    }

    /**
     * Changement de statut d'un bulletin (mecano/admin uniquement -- deja
     * garanti par le filtre de role du controleur, PRD EF6.3)
     * @param int $archived_document_id
     */
    public function set_statut($archived_document_id = '') {
        if (empty($archived_document_id)) {
            redirect('maintenance_bulletins');
        }

        $document = $this->archived_documents_model->get_by_id('id', $archived_document_id);
        if (!$document) {
            show_404();
        }

        $statut = $this->input->post('statut');
        $statuts_valides = array_keys(Maintenance_bulletin_model::get_statuts());
        if (!in_array($statut, $statuts_valides)) {
            $this->session->set_flashdata('error', $this->lang->line('maintenance_bulletin_statut_invalide'));
            redirect('maintenance_bulletins/index/' . $document['machine_immat']);
            return;
        }

        $this->maintenance_bulletin_model->set_statut($archived_document_id, $statut);
        $this->session->set_flashdata('success', $this->lang->line('maintenance_bulletin_statut_mis_a_jour'));
        redirect('maintenance_bulletins/index/' . $document['machine_immat']);
    }
}

/* End of file maintenance_bulletins.php */
/* Location: ./application/controllers/maintenance_bulletins.php */
