<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Formation Types Seances Controller
 *
 * CRUD pour le référentiel des types de séances de formation.
 * Réservé aux administrateurs (responsable pédagogique).
 * Chaque type définit :
 *   - nature : 'vol' (séance en vol) ou 'theorique' (cours au sol)
 *   - periodicite_max_jours : délai max entre deux séances de ce type
 *
 * @package controllers
 * @see doc/prds/gestion_des_seances_theoriques.md
 * @see doc/plans/seances_theoriques_plan.md Phase 1
 */
class Formation_types_seances extends CI_Controller {

    public function __construct() {
        parent::__construct();

        $this->load->model('formation_type_seance_model');
        $this->load->library('gvvmetadata');
        $this->load->library('form_validation');
        $this->lang->load('formation');
        $this->lang->load('gvv');

        if (!$this->config->item('gestion_formations')) {
            show_404();
        }

        if (!$this->dx_auth->is_logged_in()) {
            redirect('auth/login');
        }

        if (!$this->dx_auth->is_admin()) {
            show_error($this->lang->line('formation_acces_refuse'), 403);
        }
    }

    /**
     * Liste de tous les types de séances
     */
    public function index() {
        $types = $this->formation_type_seance_model->get_all();

        $data = array(
            'controller' => 'formation_types_seances',
            'types'      => $types,
        );

        $this->load->view('formation_types_seances/index', $data);
    }

    /**
     * Formulaire de création
     */
    public function create() {
        $data = array(
            'controller' => 'formation_types_seances',
            'action'     => 'create',
            'type'       => array(
                'nom'                   => '',
                'nature'                => 'theorique',
                'description'           => '',
                'periodicite_max_jours' => '',
                'actif'                 => 1,
            ),
            'error'      => '',
        );

        $this->load->view('formation_types_seances/form', $data);
    }

    /**
     * Enregistrement d'un nouveau type
     */
    public function store() {
        $this->form_validation->set_rules('nom',    'Nom',    'required|max_length[100]');
        $this->form_validation->set_rules('nature', 'Nature', 'required|in_list[vol,theorique]');
        $this->form_validation->set_rules('periodicite_max_jours', 'Périodicité', 'integer|greater_than_equal_to[1]');

        if ($this->form_validation->run() === FALSE) {
            $data = array(
                'controller' => 'formation_types_seances',
                'action'     => 'create',
                'type'       => $this->input->post(),
                'error'      => validation_errors(),
            );
            $this->load->view('formation_types_seances/form', $data);
            return;
        }

        $periodicite = $this->input->post('periodicite_max_jours');
        $row = array(
            'nom'                   => $this->input->post('nom'),
            'nature'                => $this->input->post('nature'),
            'description'           => $this->input->post('description'),
            'periodicite_max_jours' => ($periodicite !== '' && $periodicite !== null) ? (int)$periodicite : null,
            'actif'                 => 1,
        );

        $this->formation_type_seance_model->create($row);
        $this->session->set_flashdata('success', $this->lang->line('formation_type_seance_created'));
        redirect('formation_types_seances');
    }

    /**
     * Formulaire d'édition
     * @param int $id
     */
    public function edit($id = '') {
        if (empty($id)) {
            redirect('formation_types_seances');
        }

        $type = $this->formation_type_seance_model->get_by_id('id', $id);
        if (!$type) {
            show_404();
        }

        $data = array(
            'controller' => 'formation_types_seances',
            'action'     => 'edit',
            'type'       => $type,
            'error'      => '',
        );

        $this->load->view('formation_types_seances/form', $data);
    }

    /**
     * Mise à jour d'un type existant
     * @param int $id
     */
    public function update($id = '') {
        if (empty($id)) {
            redirect('formation_types_seances');
        }

        $type = $this->formation_type_seance_model->get_by_id('id', $id);
        if (!$type) {
            show_404();
        }

        $this->form_validation->set_rules('nom',    'Nom',    'required|max_length[100]');
        $this->form_validation->set_rules('nature', 'Nature', 'required|in_list[vol,theorique]');
        $this->form_validation->set_rules('periodicite_max_jours', 'Périodicité', 'integer|greater_than_equal_to[1]');

        if ($this->form_validation->run() === FALSE) {
            $data = array(
                'controller' => 'formation_types_seances',
                'action'     => 'edit',
                'type'       => array_merge($type, $this->input->post()),
                'error'      => validation_errors(),
            );
            $this->load->view('formation_types_seances/form', $data);
            return;
        }

        $periodicite = $this->input->post('periodicite_max_jours');
        $row = array(
            'nom'                   => $this->input->post('nom'),
            'nature'                => $this->input->post('nature'),
            'description'           => $this->input->post('description'),
            'periodicite_max_jours' => ($periodicite !== '' && $periodicite !== null) ? (int)$periodicite : null,
            'actif'                 => (int)(bool)$this->input->post('actif'),
        );

        $this->formation_type_seance_model->update('id', array_merge($row, array('id' => $id)));
        $this->session->set_flashdata('success', $this->lang->line('formation_type_seance_updated'));
        redirect('formation_types_seances');
    }

    /**
     * Suppression d'un type (refusée si en cours d'utilisation)
     * @param int $id
     */
    public function delete($id = '') {
        if (empty($id)) {
            redirect('formation_types_seances');
        }

        if ($this->formation_type_seance_model->is_in_use($id)) {
            $this->session->set_flashdata('error', $this->lang->line('formation_type_seance_in_use'));
            redirect('formation_types_seances');
            return;
        }

        $this->formation_type_seance_model->delete(array('id' => $id));
        $this->session->set_flashdata('success', $this->lang->line('formation_type_seance_deleted'));
        redirect('formation_types_seances');
    }

    /**
     * Désactivation douce d'un type
     * @param int $id
     */
    public function deactivate($id = '') {
        if (empty($id)) {
            redirect('formation_types_seances');
        }

        $this->formation_type_seance_model->deactivate($id);
        $this->session->set_flashdata('success', $this->lang->line('formation_type_seance_deactivated'));
        redirect('formation_types_seances');
    }
}

/* End of file formation_types_seances.php */
/* Location: ./application/controllers/formation_types_seances.php */
