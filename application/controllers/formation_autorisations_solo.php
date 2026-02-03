<?php
/**
 *    GVV Gestion vol a voile
 *    Copyright (C) 2011  Philippe Boissel & Frederic Peignot
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
 */

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Formation Autorisations Solo Controller
 *
 * Gère les autorisations de vol solo pour les élèves pilotes :
 * - Liste des autorisations
 * - Création par un instructeur
 * - Modification
 * - Suppression
 * - Consultation détaillée
 *
 * @package controllers
 * @author GVV Development Team
 * @see doc/design_notes/autorisations_vol_solo_plan.md
 */
class Formation_autorisations_solo extends CI_Controller {

    public function __construct() {
        parent::__construct();

        // Load required models
        $this->load->model('formation_autorisation_solo_model');
        $this->load->model('formation_inscription_model');
        $this->load->model('membres_model');

        // Load libraries
        $this->load->library('gvvmetadata');
        $this->load->library('form_validation');
        $this->load->library('formation_access');

        // Load language files
        $this->lang->load('formation');
        $this->lang->load('gvv');

        // Check if feature is enabled
        if (!$this->config->item('gestion_formations')) {
            show_404();
        }

        // Check user authentication
        if (!$this->dx_auth->is_logged_in()) {
            redirect('auth/login');
        }

        // Check instructor access for all methods except detail
        $method = $this->router->fetch_method();
        if ($method !== 'detail' && !$this->formation_access->is_instructeur()) {
            show_error($this->lang->line('formation_acces_instructeur_requis'), 403);
        }
    }

    /**
     * Liste des autorisations avec filtres
     */
    public function index() {
        log_message('debug', 'FORMATION_AUTORISATIONS_SOLO: index() method called');

        // Get filters from query string
        $filters = array(
            'eleve_id' => $this->input->get('eleve_id'),
            'instructeur_id' => $this->input->get('instructeur_id'),
            'inscription_id' => $this->input->get('inscription_id')
        );

        // Remove empty filters
        $filters = array_filter($filters, function ($value) {
            return $value !== null && $value !== '';
        });

        // Get autorisations
        $autorisations = $this->formation_autorisation_solo_model->select_page($filters);

        // Prepare data for view
        $data = array(
            'controller' => 'formation_autorisations_solo',
            'autorisations' => $autorisations,
            'filters' => $filters,
            'pilotes' => $this->membres_model->get_selector(),
            'instructeurs' => $this->membres_model->get_selector_instructeurs()
        );

        $this->load->view('formation_autorisations_solo/index', $data);
    }

    /**
     * Formulaire de création d'une nouvelle autorisation
     */
    public function create() {
        log_message('debug', 'FORMATION_AUTORISATIONS_SOLO: create() method called');

        // Prepare data for view
        $data = array(
            'controller' => 'formation_autorisations_solo',
            'action' => 'store',
            'autorisation' => array(
                'date_autorisation' => date('Y-m-d'),
                'instructeur_id' => $this->dx_auth->get_username()
            ),
            'inscriptions' => $this->formation_autorisation_solo_model->get_inscription_selector(),
            'inscriptions_data' => $this->formation_autorisation_solo_model->get_inscriptions_with_type(),
            'instructeurs' => $this->membres_model->get_selector_instructeurs(),
            'planeurs' => $this->_get_planeur_selector(),
            'avions' => $this->_get_avion_selector()
        );

        $this->load->view('formation_autorisations_solo/form', $data);
    }

    /**
     * Enregistrement d'une nouvelle autorisation
     */
    public function store() {
        log_message('debug', 'FORMATION_AUTORISATIONS_SOLO: store() method called');
        log_message('debug', 'FORMATION_AUTORISATIONS_SOLO: POST data: ' . print_r($_POST, TRUE));

        // Validation
        $this->form_validation->set_rules('inscription_id', $this->lang->line('formation_autorisation_solo_formation'), 'required|integer');
        $this->form_validation->set_rules('date_autorisation', $this->lang->line('formation_autorisation_solo_date'), 'required');
        $this->form_validation->set_rules('machine_id', $this->lang->line('formation_autorisation_solo_machine'), 'required');
        $this->form_validation->set_rules('consignes', $this->lang->line('formation_autorisation_solo_consignes'), 'max_length[250]');

        if ($this->form_validation->run() === FALSE) {
            return $this->create();
        }

        // Get inscription to retrieve eleve_id
        $inscription_id = $this->input->post('inscription_id');
        $inscription = $this->formation_inscription_model->get($inscription_id);

        if (!$inscription) {
            $this->session->set_flashdata('error', $this->lang->line('formation_inscription_not_found'));
            return $this->create();
        }

        // Prepare autorisation data - use current session section
        $section_id = $this->session->userdata('section');
        $autorisation_data = array(
            'inscription_id' => $inscription_id,
            'eleve_id' => $inscription['pilote_id'],
            'instructeur_id' => $this->input->post('instructeur_id') ?: $this->dx_auth->get_username(),
            'date_autorisation' => $this->input->post('date_autorisation'),
            'section_id' => ($section_id && $section_id != 'Toutes') ? $section_id : null,
            'machine_id' => $this->input->post('machine_id'),
            'consignes' => $this->input->post('consignes')
        );

        log_message('debug', 'FORMATION_AUTORISATIONS_SOLO: Creating autorisation with data: ' . print_r($autorisation_data, true));

        // Create autorisation
        $autorisation_id = $this->formation_autorisation_solo_model->create_autorisation($autorisation_data);

        if (!$autorisation_id) {
            $db_error_msg = $this->db->_error_message();
            $db_error_num = $this->db->_error_number();
            $error_msg = $this->lang->line('formation_autorisation_solo_create_error');

            if (!empty($db_error_msg)) {
                $error_msg .= '<br><strong>Erreur technique:</strong> ' . htmlspecialchars($db_error_msg) . ' (Code: ' . $db_error_num . ')';
                log_message('error', 'FORMATION_AUTORISATIONS_SOLO: Database error during create: ' . $db_error_num . ' - ' . $db_error_msg);
            }

            log_message('error', 'FORMATION_AUTORISATIONS_SOLO: Last query: ' . $this->db->last_query());

            $this->session->set_flashdata('error', $error_msg);
            return $this->create();
        }

        log_message('debug', 'FORMATION_AUTORISATIONS_SOLO: Autorisation created successfully with ID: ' . $autorisation_id);

        // Success
        $this->session->set_flashdata('success', $this->lang->line('formation_autorisation_solo_created'));
        redirect('formation_autorisations_solo/detail/' . $autorisation_id);
    }

    /**
     * Formulaire de modification d'une autorisation
     *
     * @param int $id Autorisation ID
     */
    public function edit($id) {
        log_message('debug', 'FORMATION_AUTORISATIONS_SOLO: edit() method called for id=' . $id);

        $autorisation = $this->formation_autorisation_solo_model->get_full($id);
        if (!$autorisation) {
            show_404();
        }

        // Prepare data for view
        $data = array(
            'controller' => 'formation_autorisations_solo',
            'action' => 'update/' . $id,
            'autorisation' => $autorisation,
            'inscriptions' => $this->formation_autorisation_solo_model->get_inscription_selector(),
            'inscriptions_data' => $this->formation_autorisation_solo_model->get_inscriptions_with_type(),
            'instructeurs' => $this->membres_model->get_selector_instructeurs(),
            'planeurs' => $this->_get_planeur_selector(),
            'avions' => $this->_get_avion_selector()
        );

        $this->load->view('formation_autorisations_solo/form', $data);
    }

    /**
     * Mise à jour d'une autorisation
     *
     * @param int $id Autorisation ID
     */
    public function update($id) {
        log_message('debug', 'FORMATION_AUTORISATIONS_SOLO: update() method called for id=' . $id);

        $autorisation = $this->formation_autorisation_solo_model->get($id);
        if (!$autorisation) {
            show_404();
        }

        // Validation
        $this->form_validation->set_rules('inscription_id', $this->lang->line('formation_autorisation_solo_formation'), 'required|integer');
        $this->form_validation->set_rules('date_autorisation', $this->lang->line('formation_autorisation_solo_date'), 'required');
        $this->form_validation->set_rules('machine_id', $this->lang->line('formation_autorisation_solo_machine'), 'required');
        $this->form_validation->set_rules('consignes', $this->lang->line('formation_autorisation_solo_consignes'), 'max_length[250]');

        if ($this->form_validation->run() === FALSE) {
            return $this->edit($id);
        }

        // Get inscription to retrieve eleve_id
        $inscription_id = $this->input->post('inscription_id');
        $inscription = $this->formation_inscription_model->get($inscription_id);

        if (!$inscription) {
            $this->session->set_flashdata('error', $this->lang->line('formation_inscription_not_found'));
            return $this->edit($id);
        }

        // Prepare autorisation data - use current session section
        $section_id = $this->session->userdata('section');
        $autorisation_data = array(
            'inscription_id' => $inscription_id,
            'eleve_id' => $inscription['pilote_id'],
            'instructeur_id' => $this->input->post('instructeur_id'),
            'date_autorisation' => $this->input->post('date_autorisation'),
            'section_id' => ($section_id && $section_id != 'Toutes') ? $section_id : null,
            'machine_id' => $this->input->post('machine_id'),
            'consignes' => $this->input->post('consignes')
        );

        log_message('debug', 'FORMATION_AUTORISATIONS_SOLO: Updating autorisation with data: ' . print_r($autorisation_data, true));

        // Update autorisation
        $this->formation_autorisation_solo_model->update_autorisation($id, $autorisation_data);

        log_message('debug', 'FORMATION_AUTORISATIONS_SOLO: Autorisation updated successfully');

        // Success
        $this->session->set_flashdata('success', $this->lang->line('formation_autorisation_solo_updated'));
        redirect('formation_autorisations_solo/detail/' . $id);
    }

    /**
     * Suppression d'une autorisation
     *
     * @param int $id Autorisation ID
     */
    public function delete($id) {
        log_message('debug', 'FORMATION_AUTORISATIONS_SOLO: delete() method called for id=' . $id);

        $autorisation = $this->formation_autorisation_solo_model->get($id);
        if (!$autorisation) {
            show_404();
        }

        // Handle POST confirmation
        if ($this->input->post('confirm') === 'yes') {
            $this->formation_autorisation_solo_model->delete(array('id' => $id));

            log_message('debug', 'FORMATION_AUTORISATIONS_SOLO: Autorisation deleted successfully');

            $this->session->set_flashdata('success', $this->lang->line('formation_autorisation_solo_deleted'));
            redirect('formation_autorisations_solo');
        }

        // Show confirmation view
        $data = array(
            'controller' => 'formation_autorisations_solo',
            'autorisation' => $this->formation_autorisation_solo_model->get_full($id)
        );

        $this->load->view('formation_autorisations_solo/delete', $data);
    }

    /**
     * Détail d'une autorisation
     *
     * @param int $id Autorisation ID
     */
    public function detail($id) {
        log_message('debug', 'FORMATION_AUTORISATIONS_SOLO: detail() method called for id=' . $id);

        $autorisation = $this->formation_autorisation_solo_model->get_full($id);
        if (!$autorisation) {
            show_404();
        }

        // Check access: instructors or the student themselves
        $current_user = $this->dx_auth->get_username();
        $is_instructeur = $this->formation_access->is_instructeur();
        $is_student = ($current_user === $autorisation['eleve_id']);

        if (!$is_instructeur && !$is_student) {
            show_error($this->lang->line('formation_acces_refuse'), 403);
        }

        // Prepare data for view
        $data = array(
            'controller' => 'formation_autorisations_solo',
            'autorisation' => $autorisation,
            'is_instructeur' => $is_instructeur,
            'is_student_view' => $is_student && !$is_instructeur
        );

        $this->load->view('formation_autorisations_solo/detail', $data);
    }

    /**
     * Get planeur selector for current section
     *
     * @return array
     */
    private function _get_planeur_selector() {
        $this->load->model('planeurs_model');
        return $this->planeurs_model->get_selector();
    }

    /**
     * Get avion selector for current section
     *
     * @return array
     */
    private function _get_avion_selector() {
        $this->load->model('avions_model');
        return $this->avions_model->selector();
    }
}

/* End of file formation_autorisations_solo.php */
/* Location: ./application/controllers/formation_autorisations_solo.php */
