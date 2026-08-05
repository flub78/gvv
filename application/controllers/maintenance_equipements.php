<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Maintenance Equipements Controller
 *
 * CRUD des equipements maintenables (moteur, helice, parachute, radio,
 * etc.) rattaches a un aeronef, plus l'action de transfert vers un autre
 * aeronef (PRD Parcours 5). Suppression toujours logique (actif = 0),
 * jamais de suppression definitive (EF1.3).
 *
 * Mirroir du style des controleurs Formation (MY_Controller direct,
 * validation manuelle) plutot que du Gvv_Controller/Gvvmetadata generique,
 * pour rester coherent avec le reste du module Maintenance.
 *
 * @package controllers
 * @see doc/prds/maintenance_aeronefs_prd.md (EF1)
 * @see doc/plans/maintenance_aeronefs_plan.md (Etape 5.1)
 */
class Maintenance_equipements extends MY_Controller {

    public function __construct() {
        parent::__construct();

        $this->load->model('maintenance_equipement_model');
        $this->load->library('form_validation');
        $this->lang->load('maintenance');
        $this->lang->load('gvv');

        if (!$this->dx_auth->is_logged_in()) {
            redirect('auth/login');
        }

        if (!$this->user_has_role('mecano')) {
            show_error($this->lang->line('maintenance_acces_refuse'), 403);
        }

        // Bouton retour vers le tableau de bord Maintenance
        $this->lang->load('tableaux_de_bord');
        $this->load->vars([
            'nav_back_url'   => $this->session->userdata('nav_from_url')   ?: 'welcome/section/maintenance',
            'nav_back_label' => $this->session->userdata('nav_from_label') ?: $this->lang->line('db_section_maintenance'),
        ]);
    }

    /**
     * Liste de tous les equipements (actifs et inactifs), aeronef affiche en clair
     */
    public function index() {
        $equipements = $this->maintenance_equipement_model->get_all(false);

        $data = array(
            'controller'   => 'maintenance_equipements',
            'equipements'  => $equipements,
        );

        $this->load->view('maintenance_equipements/index', $data);
    }

    /**
     * Formulaire de creation
     */
    public function create() {
        $data = array(
            'controller'       => 'maintenance_equipements',
            'action'           => 'create',
            'equipement'       => array(
                'nom'         => '',
                'aeronef_id'  => '',
                'description' => '',
            ),
            'aeronef_selector' => $this->maintenance_equipement_model->get_aeronef_selector(),
            'error'            => '',
        );

        $this->load->view('maintenance_equipements/form', $data);
    }

    /**
     * Enregistrement d'un nouvel equipement
     */
    public function store() {
        $this->form_validation->set_rules('nom', $this->lang->line('maintenance_equipement_nom'), 'required|max_length[100]');
        $this->form_validation->set_rules('aeronef_id', $this->lang->line('maintenance_equipement_aeronef'), 'required');
        $this->form_validation->set_rules('description', $this->lang->line('maintenance_equipement_description'), 'max_length[255]');

        if ($this->form_validation->run() === FALSE) {
            $data = array(
                'controller'       => 'maintenance_equipements',
                'action'           => 'create',
                'equipement'       => $this->input->post(),
                'aeronef_selector' => $this->maintenance_equipement_model->get_aeronef_selector(),
                'error'            => validation_errors(),
            );
            $this->load->view('maintenance_equipements/form', $data);
            return;
        }

        $row = array(
            'nom'         => $this->input->post('nom'),
            'aeronef_id'  => $this->input->post('aeronef_id'),
            'description' => $this->input->post('description'),
            'actif'       => 1,
        );

        $this->maintenance_equipement_model->create($row);
        $this->session->set_flashdata('success', $this->lang->line('maintenance_equipement_created'));
        redirect('maintenance_equipements');
    }

    /**
     * Formulaire d'edition
     * @param int $id
     */
    public function edit($id = '') {
        if (empty($id)) {
            redirect('maintenance_equipements');
        }

        $equipement = $this->maintenance_equipement_model->get($id);
        if (!$equipement) {
            show_404();
        }

        $data = array(
            'controller'       => 'maintenance_equipements',
            'action'           => 'edit',
            'equipement'       => $equipement,
            'aeronef_selector' => $this->maintenance_equipement_model->get_aeronef_selector(),
            'error'            => '',
        );

        $this->load->view('maintenance_equipements/form', $data);
    }

    /**
     * Mise a jour d'un equipement existant
     * @param int $id
     */
    public function update($id = '') {
        if (empty($id)) {
            redirect('maintenance_equipements');
        }

        $equipement = $this->maintenance_equipement_model->get($id);
        if (!$equipement) {
            show_404();
        }

        $this->form_validation->set_rules('nom', $this->lang->line('maintenance_equipement_nom'), 'required|max_length[100]');
        $this->form_validation->set_rules('description', $this->lang->line('maintenance_equipement_description'), 'max_length[255]');

        if ($this->form_validation->run() === FALSE) {
            $data = array(
                'controller'       => 'maintenance_equipements',
                'action'           => 'edit',
                'equipement'       => array_merge($equipement, $this->input->post()),
                'aeronef_selector' => $this->maintenance_equipement_model->get_aeronef_selector(),
                'error'            => validation_errors(),
            );
            $this->load->view('maintenance_equipements/form', $data);
            return;
        }

        // aeronef_id ne se modifie pas depuis ce formulaire : passe par
        // l'action dediee "transferer" (confirmation explicite, PRD Parcours 5)
        $row = array(
            'nom'         => $this->input->post('nom'),
            'description' => $this->input->post('description'),
        );

        $this->maintenance_equipement_model->update('id', array_merge($row, array('id' => $id)));
        $this->session->set_flashdata('success', $this->lang->line('maintenance_equipement_updated'));
        redirect('maintenance_equipements');
    }

    /**
     * Desactivation logique (jamais de suppression definitive, PRD EF1.3)
     * @param int $id
     */
    public function deactivate($id = '') {
        if (empty($id)) {
            redirect('maintenance_equipements');
        }

        $this->maintenance_equipement_model->desactiver($id);
        $this->session->set_flashdata('success', $this->lang->line('maintenance_equipement_deactivated'));
        redirect('maintenance_equipements');
    }

    /**
     * Reactivation d'un equipement desactive
     * @param int $id
     */
    public function reactivate($id = '') {
        if (empty($id)) {
            redirect('maintenance_equipements');
        }

        $this->maintenance_equipement_model->reactiver($id);
        $this->session->set_flashdata('success', $this->lang->line('maintenance_equipement_reactivated'));
        redirect('maintenance_equipements');
    }

    /**
     * Formulaire de transfert vers un autre aeronef (PRD Parcours 5)
     * @param int $id
     */
    public function transfer($id = '') {
        if (empty($id)) {
            redirect('maintenance_equipements');
        }

        $equipement = $this->maintenance_equipement_model->get($id);
        if (!$equipement) {
            show_404();
        }

        $data = array(
            'controller'       => 'maintenance_equipements',
            'equipement'       => $equipement,
            'aeronef_selector' => $this->maintenance_equipement_model->get_aeronef_selector(),
            'error'            => '',
        );

        $this->load->view('maintenance_equipements/transfer', $data);
    }

    /**
     * Confirmation du transfert vers l'aeronef cible
     * @param int $id
     */
    public function transfer_store($id = '') {
        if (empty($id)) {
            redirect('maintenance_equipements');
        }

        $equipement = $this->maintenance_equipement_model->get($id);
        if (!$equipement) {
            show_404();
        }

        $this->form_validation->set_rules('nouvel_aeronef_id', $this->lang->line('maintenance_equipement_aeronef'), 'required');
        $this->form_validation->set_rules('confirmation', $this->lang->line('maintenance_transfert_confirmation'), 'required');

        if ($this->form_validation->run() === FALSE) {
            $data = array(
                'controller'       => 'maintenance_equipements',
                'equipement'       => $equipement,
                'aeronef_selector' => $this->maintenance_equipement_model->get_aeronef_selector(),
                'error'            => validation_errors(),
            );
            $this->load->view('maintenance_equipements/transfer', $data);
            return;
        }

        $nouvel_aeronef_id = $this->input->post('nouvel_aeronef_id');
        $this->maintenance_equipement_model->transferer($id, $nouvel_aeronef_id);

        $message = sprintf(
            $this->lang->line('maintenance_equipement_transferred'),
            htmlspecialchars($equipement['nom'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($nouvel_aeronef_id, ENT_QUOTES, 'UTF-8')
        );
        $this->session->set_flashdata('success', $message);
        redirect('maintenance_equipements');
    }
}

/* End of file maintenance_equipements.php */
/* Location: ./application/controllers/maintenance_equipements.php */
