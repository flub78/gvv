<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Maintenance Dossiers Controller
 *
 * Ouverture d'un dossier d'entretien (association programme + entite
 * maintenable), et transitions de statut (suspendre / reactiver /
 * cloturer / abandonner) -- miroir exact de formation_inscriptions.
 *
 * @package controllers
 * @see doc/prds/maintenance_aeronefs_prd.md (EF3)
 * @see doc/plans/maintenance_aeronefs_plan.md (Etape 5.3)
 */
class Maintenance_dossiers extends MY_Controller {

    public function __construct() {
        parent::__construct();

        $this->load->model('maintenance_dossier_model');
        $this->load->model('maintenance_programme_model');
        $this->load->model('maintenance_equipement_model');
        $this->load->model('maintenance_operation_model');
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
     * Liste de tous les dossiers (tout statut), optionnellement filtres
     * par entite -- c'est aussi ainsi que l'historique d'une entite est
     * consultable (PRD EF3.4) : /maintenance_dossiers?entite_type=aeronef&entite_id=F-XXXX
     */
    public function index() {
        $entite_type = $this->input->get('entite_type');
        $entite_id = $this->input->get('entite_id');

        if ($entite_type && $entite_id) {
            $dossiers = $this->maintenance_dossier_model->get_by_entite($entite_type, $entite_id);
        } else {
            $dossiers = $this->maintenance_dossier_model->get_all($this->session->userdata('section'));
        }

        foreach ($dossiers as &$dossier) {
            $dossier['entite_label'] = $this->maintenance_dossier_model->entite_label($dossier['entite_type'], $dossier['entite_id']);
        }
        unset($dossier);

        $data = array(
            'controller'  => 'maintenance_dossiers',
            'dossiers'    => $dossiers,
            'entite_type' => $entite_type,
            'entite_id'   => $entite_id,
        );

        $this->load->view('maintenance_dossiers/index', $data);
    }

    /**
     * Formulaire d'ouverture d'un dossier
     * @param string $entite_type 'aeronef' ou 'equipement' (defaut aeronef)
     */
    public function ouvrir_form($entite_type = 'aeronef') {
        if (!in_array($entite_type, array('aeronef', 'equipement'))) {
            $entite_type = 'aeronef';
        }

        $data = array(
            'controller'        => 'maintenance_dossiers',
            'entite_type'       => $entite_type,
            'entite_selector'   => $entite_type === 'aeronef'
                ? $this->maintenance_equipement_model->get_aeronef_selector()
                : $this->maintenance_equipement_model->get_all_selector(),
            'programme_selector' => $this->programme_selector(),
            'error'             => '',
        );

        $this->load->view('maintenance_dossiers/ouvrir', $data);
    }

    private function programme_selector() {
        $programmes = $this->maintenance_programme_model->get_visibles();
        $result = array('' => '');
        foreach ($programmes as $programme) {
            $result[$programme['id']] = $programme['code'] . ' - ' . $programme['titre'];
        }
        return $result;
    }

    /**
     * Enregistrement de l'ouverture d'un dossier
     */
    public function ouvrir_store() {
        $entite_type = $this->input->post('entite_type');
        $entite_id = $this->input->post('entite_id');
        $programme_id = $this->input->post('programme_id');

        $this->form_validation->set_rules('entite_type', $this->lang->line('maintenance_dossier_entite'), 'required|in_list[aeronef,equipement]');
        $this->form_validation->set_rules('entite_id', $this->lang->line('maintenance_dossier_entite'), 'required');
        $this->form_validation->set_rules('programme_id', $this->lang->line('maintenance_dossier_programme'), 'required|integer');

        $validation_failed = ($this->form_validation->run() === FALSE);
        $entite_invalide = !$validation_failed && !$this->maintenance_dossier_model->entite_exists($entite_type, $entite_id);

        if ($validation_failed || $entite_invalide) {
            $data = array(
                'controller'         => 'maintenance_dossiers',
                'entite_type'        => $entite_type ?: 'aeronef',
                'entite_selector'    => ($entite_type === 'equipement')
                    ? $this->maintenance_equipement_model->get_all_selector()
                    : $this->maintenance_equipement_model->get_aeronef_selector(),
                'programme_selector' => $this->programme_selector(),
                'error'              => $entite_invalide ? $this->lang->line('maintenance_dossier_entite_invalide') : validation_errors(),
            );
            $this->load->view('maintenance_dossiers/ouvrir', $data);
            return;
        }

        $id = $this->maintenance_dossier_model->ouvrir(array(
            'entite_type'         => $entite_type,
            'entite_id'           => $entite_id,
            'programme_id'        => $programme_id,
            'mecano_referent_id'  => $this->dx_auth->get_username(),
            'commentaire'         => $this->input->post('commentaire'),
        ));

        $this->session->set_flashdata('success', $this->lang->line('maintenance_dossier_ouvert'));
        redirect('maintenance_dossiers/view/' . $id);
    }

    /**
     * Detail d'un dossier : programme, entite, statut, operations enregistrees
     * @param int $id
     */
    public function view($id = '') {
        if (empty($id)) {
            redirect('maintenance_dossiers');
        }

        $dossier = $this->maintenance_dossier_model->get_full($id);
        if (!$dossier) {
            show_404();
        }
        $dossier['entite_label'] = $this->maintenance_dossier_model->entite_label($dossier['entite_type'], $dossier['entite_id']);

        $operations = $this->maintenance_operation_model->get_by_dossier($id);

        $data = array(
            'controller' => 'maintenance_dossiers',
            'dossier'    => $dossier,
            'operations' => $operations,
        );

        $this->load->view('maintenance_dossiers/view', $data);
    }

    /**
     * Suspension d'un dossier
     * @param int $id
     */
    public function suspend($id = '') {
        if (empty($id)) {
            redirect('maintenance_dossiers');
        }

        $this->maintenance_dossier_model->suspendre($id);
        $this->session->set_flashdata('success', $this->lang->line('maintenance_dossier_suspendu'));
        redirect('maintenance_dossiers/view/' . $id);
    }

    /**
     * Reactivation d'un dossier suspendu
     * @param int $id
     */
    public function reactivate($id = '') {
        if (empty($id)) {
            redirect('maintenance_dossiers');
        }

        $this->maintenance_dossier_model->reactiver($id);
        $this->session->set_flashdata('success', $this->lang->line('maintenance_dossier_reactive'));
        redirect('maintenance_dossiers/view/' . $id);
    }

    /**
     * Cloture d'un dossier (succes)
     * @param int $id
     */
    public function close($id = '') {
        if (empty($id)) {
            redirect('maintenance_dossiers');
        }

        $this->maintenance_dossier_model->cloturer($id, 'cloture');
        $this->session->set_flashdata('success', $this->lang->line('maintenance_dossier_cloture'));
        redirect('maintenance_dossiers/view/' . $id);
    }

    /**
     * Abandon d'un dossier
     * @param int $id
     */
    public function abandon($id = '') {
        if (empty($id)) {
            redirect('maintenance_dossiers');
        }

        $this->maintenance_dossier_model->cloturer($id, 'abandonne');
        $this->session->set_flashdata('success', $this->lang->line('maintenance_dossier_abandonne'));
        redirect('maintenance_dossiers/view/' . $id);
    }
}

/* End of file maintenance_dossiers.php */
/* Location: ./application/controllers/maintenance_dossiers.php */
