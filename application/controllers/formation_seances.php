<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Formation Seances Controller
 *
 * Gère l'enregistrement des séances de formation :
 * - Séances liées à une inscription (inscription_id NOT NULL)
 * - Séances libres sans inscription (inscription_id IS NULL)
 * - Évaluations par sujet (-, A, R, Q)
 * - Gestion des conditions météo
 *
 * @package controllers
 * @author GVV Development Team
 * @see doc/prds/suivi_formation_prd.md
 * @see doc/plans/suivi_formation_plan.md Phase 4
 */
class Formation_seances extends CI_Controller {

    /**
     * Weather conditions list
     */
    private $meteo_options = array(
        'cavok', 'vent_faible', 'vent_modere', 'vent_fort',
        'thermiques', 'turbulences', 'nuageux', 'couvert',
        'pluie', 'vent_travers'
    );

    public function __construct() {
        parent::__construct();

        // Load required models
        $this->load->model('formation_seance_model');
        $this->load->model('formation_evaluation_model');
        $this->load->model('formation_inscription_model');
        $this->load->model('formation_programme_model');
        $this->load->model('formation_lecon_model');
        $this->load->model('formation_sujet_model');
        $this->load->model('membres_model');
        $this->load->model('planeurs_model');

        // Load libraries
        $this->load->library('gvvmetadata');
        $this->load->library('form_validation');

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
    }

    /**
     * Liste des séances avec filtres et distinction inscription/libre
     */
    public function index() {
        log_message('debug', 'FORMATION_SEANCES: index() method called');

        // Get filters from query string
        $filters = array(
            'pilote_id' => $this->input->get('pilote_id'),
            'instructeur_id' => $this->input->get('instructeur_id'),
            'programme_id' => $this->input->get('programme_id'),
            'type' => $this->input->get('type'),
            'date_debut' => $this->input->get('date_debut'),
            'date_fin' => $this->input->get('date_fin')
        );

        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });

        // Get seances
        $seances = $this->formation_seance_model->select_page($filters);

        // Prepare data for view
        $data = array(
            'controller' => 'formation_seances',
            'seances' => $seances,
            'filters' => $filters,
            'pilotes' => $this->membres_model->get_selector(),
            'instructeurs' => $this->membres_model->get_selector_instructeurs(),
            'programmes' => $this->formation_programme_model->get_selector()
        );

        $this->load->view('formation_seances/index', $data);
    }

    /**
     * Formulaire de création d'une séance
     *
     * @param string mode - 'inscription' ou 'libre' (optionnel, depuis query string)
     * Query params:
     *   inscription_id - ID de l'inscription (mode inscription)
     *   pilote_id - ID du pilote (mode libre)
     */
    public function create() {
        log_message('debug', 'FORMATION_SEANCES: create() method called');

        $inscription_id = $this->input->get('inscription_id');
        $inscription = null;
        $is_libre = true;

        // If inscription_id provided, load inscription data
        if ($inscription_id) {
            $inscription = $this->formation_inscription_model->get_with_details($inscription_id);
            if ($inscription && $inscription['statut'] === 'ouverte') {
                $is_libre = false;
            }
        }

        $data = $this->_prepare_form_data($inscription, $is_libre);
        $data['action'] = 'create';
        $data['seance'] = array(
            'date_seance' => date('Y-m-d'),
            'nb_atterrissages' => 1,
            'duree' => '',
            'meteo' => '[]',
            'commentaires' => '',
            'prochaines_lecons' => '',
            'inscription_id' => $inscription_id ?: '',
            'pilote_id' => $inscription ? $inscription['pilote_id'] : '',
            'programme_id' => $inscription ? $inscription['programme_id'] : '',
            'instructeur_id' => '',
            'machine_id' => ''
        );

        $this->load->view('formation_seances/form', $data);
    }

    /**
     * Enregistrement d'une nouvelle séance
     */
    public function store() {
        log_message('debug', 'FORMATION_SEANCES: store() method called');
        log_message('debug', 'FORMATION_SEANCES: POST data: ' . print_r($_POST, TRUE));

        // Determine mode
        $is_libre = ($this->input->post('mode_seance') === 'libre');

        // Common validation rules
        $this->form_validation->set_rules('date_seance', $this->lang->line('formation_seance_date'), 'required');
        $this->form_validation->set_rules('instructeur_id', $this->lang->line('formation_seance_instructeur'), 'required');
        $this->form_validation->set_rules('machine_id', $this->lang->line('formation_seance_machine'), 'required');
        $this->form_validation->set_rules('duree', $this->lang->line('formation_seance_duree'), 'required');
        $this->form_validation->set_rules('nb_atterrissages', $this->lang->line('formation_seance_nb_atterrissages'), 'required|integer|greater_than[0]');

        if ($is_libre) {
            $this->form_validation->set_rules('pilote_id', $this->lang->line('formation_seance_pilote'), 'required');
            $this->form_validation->set_rules('programme_id', $this->lang->line('formation_seance_programme'), 'required|integer');
        } else {
            $this->form_validation->set_rules('inscription_id', $this->lang->line('formation_seance_inscription'), 'required|integer');
        }

        if ($this->form_validation->run() === FALSE) {
            log_message('debug', 'FORMATION_SEANCES: Validation failed: ' . validation_errors());
            return $this->create();
        }

        // Build seance data
        $seance_data = $this->_build_seance_data($is_libre);

        if ($seance_data === false) {
            return $this->create();
        }

        // Collect evaluations
        $evaluations = $this->_collect_evaluations();

        // Create seance with evaluations
        $seance_id = $this->formation_seance_model->create_with_evaluations($seance_data, $evaluations);

        if (!$seance_id) {
            $db_error_msg = $this->db->_error_message();
            $db_error_num = $this->db->_error_number();
            $error_msg = $this->lang->line('formation_seance_create_error');
            if (!empty($db_error_msg)) {
                $error_msg .= '<br><strong>Erreur technique:</strong> ' . htmlspecialchars($db_error_msg) . ' (Code: ' . $db_error_num . ')';
                log_message('error', 'FORMATION_SEANCES: Database error: ' . $db_error_num . ' - ' . $db_error_msg);
            }
            log_message('error', 'FORMATION_SEANCES: Last query: ' . $this->db->last_query());
            $this->session->set_flashdata('error', $error_msg);
            return $this->create();
        }

        log_message('debug', 'FORMATION_SEANCES: Seance created with ID: ' . $seance_id);
        $this->session->set_flashdata('success', $this->lang->line('formation_seance_create_success'));
        redirect('formation_seances/detail/' . $seance_id);
    }

    /**
     * Formulaire d'édition d'une séance
     *
     * @param int $id Seance ID
     */
    public function edit($id) {
        log_message('debug', 'FORMATION_SEANCES: edit() method called for id=' . $id);

        $seance = $this->formation_seance_model->get_full($id);
        if (!$seance) {
            show_404();
        }

        $is_libre = empty($seance['inscription_id']);
        $inscription = null;

        if (!$is_libre) {
            $inscription = $this->formation_inscription_model->get_with_details($seance['inscription_id']);
        }

        $data = $this->_prepare_form_data($inscription, $is_libre);
        $data['action'] = 'edit';
        $data['seance'] = $seance;

        // Load existing evaluations
        $data['existing_evaluations'] = $this->formation_evaluation_model->get_by_seance($id);

        $this->load->view('formation_seances/form', $data);
    }

    /**
     * Mise à jour d'une séance existante
     *
     * @param int $id Seance ID
     */
    public function update($id) {
        log_message('debug', 'FORMATION_SEANCES: update() method called for id=' . $id);

        $seance = $this->formation_seance_model->get($id);
        if (!$seance) {
            show_404();
        }

        $is_libre = ($this->input->post('mode_seance') === 'libre');

        // Common validation rules
        $this->form_validation->set_rules('date_seance', $this->lang->line('formation_seance_date'), 'required');
        $this->form_validation->set_rules('instructeur_id', $this->lang->line('formation_seance_instructeur'), 'required');
        $this->form_validation->set_rules('machine_id', $this->lang->line('formation_seance_machine'), 'required');
        $this->form_validation->set_rules('duree', $this->lang->line('formation_seance_duree'), 'required');
        $this->form_validation->set_rules('nb_atterrissages', $this->lang->line('formation_seance_nb_atterrissages'), 'required|integer|greater_than[0]');

        if ($is_libre) {
            $this->form_validation->set_rules('pilote_id', $this->lang->line('formation_seance_pilote'), 'required');
            $this->form_validation->set_rules('programme_id', $this->lang->line('formation_seance_programme'), 'required|integer');
        } else {
            $this->form_validation->set_rules('inscription_id', $this->lang->line('formation_seance_inscription'), 'required|integer');
        }

        if ($this->form_validation->run() === FALSE) {
            return $this->edit($id);
        }

        // Build seance data
        $seance_data = $this->_build_seance_data($is_libre);

        if ($seance_data === false) {
            return $this->edit($id);
        }

        // Collect evaluations
        $evaluations = $this->_collect_evaluations();

        // Update seance with evaluations
        $success = $this->formation_seance_model->update_with_evaluations($id, $seance_data, $evaluations);

        if (!$success) {
            $db_error_msg = $this->db->_error_message();
            $error_msg = $this->lang->line('formation_seance_update_error');
            if (!empty($db_error_msg)) {
                $error_msg .= '<br><strong>Erreur technique:</strong> ' . htmlspecialchars($db_error_msg);
            }
            $this->session->set_flashdata('error', $error_msg);
            return $this->edit($id);
        }

        $this->session->set_flashdata('success', $this->lang->line('formation_seance_update_success'));
        redirect('formation_seances/detail/' . $id);
    }

    /**
     * Détail d'une séance
     *
     * @param int $id Seance ID
     */
    public function detail($id) {
        log_message('debug', 'FORMATION_SEANCES: detail() method called for id=' . $id);

        $seance = $this->formation_seance_model->get_full($id);
        if (!$seance) {
            show_404();
        }

        // Get evaluations
        $evaluations = $this->formation_evaluation_model->get_by_seance($id);

        // Decode meteo
        $meteo = array();
        if (!empty($seance['meteo'])) {
            $meteo = json_decode($seance['meteo'], true);
            if (!is_array($meteo)) {
                $meteo = array();
            }
        }

        $data = array(
            'controller' => 'formation_seances',
            'seance' => $seance,
            'evaluations' => $evaluations,
            'meteo' => $meteo,
            'meteo_options' => $this->meteo_options,
            'is_libre' => empty($seance['inscription_id'])
        );

        $this->load->view('formation_seances/detail', $data);
    }

    /**
     * Suppression d'une séance
     *
     * @param int $id Seance ID
     */
    public function delete($id) {
        log_message('debug', 'FORMATION_SEANCES: delete() method called for id=' . $id);

        $seance = $this->formation_seance_model->get($id);
        if (!$seance) {
            show_404();
        }

        // Delete evaluations first (cascade should handle it, but be explicit)
        $this->formation_evaluation_model->delete_by_seance($id);

        // Delete seance
        $this->formation_seance_model->delete(array('id' => $id));

        if ($this->db->affected_rows() > 0) {
            $this->session->set_flashdata('success', $this->lang->line('formation_seance_delete_success'));
        } else {
            $this->session->set_flashdata('error', $this->lang->line('formation_seance_delete_error'));
        }

        redirect('formation_seances');
    }

    // -------------------------------------------------------------------------
    // AJAX Endpoints
    // -------------------------------------------------------------------------

    /**
     * AJAX: Retourne les inscriptions ouvertes d'un pilote
     */
    public function ajax_inscriptions_pilote() {
        $pilote_id = $this->input->get('pilote_id');
        if (empty($pilote_id)) {
            echo json_encode(array());
            return;
        }

        $inscriptions = $this->formation_inscription_model->get_ouvertes($pilote_id);

        $result = array();
        foreach ($inscriptions as $insc) {
            $result[] = array(
                'id' => $insc['id'],
                'label' => $insc['programme_code'] . ' - ' . $insc['programme_titre'] .
                    ' (depuis ' . $insc['date_ouverture'] . ')',
                'programme_id' => $insc['programme_id']
            );
        }

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    /**
     * AJAX: Retourne les leçons et sujets d'un programme
     */
    public function ajax_programme_structure() {
        $programme_id = $this->input->get('programme_id');
        if (empty($programme_id)) {
            echo json_encode(array());
            return;
        }

        $lecons = $this->formation_lecon_model->get_by_programme($programme_id);
        foreach ($lecons as &$lecon) {
            $lecon['sujets'] = $this->formation_sujet_model->get_by_lecon($lecon['id']);
        }

        header('Content-Type: application/json');
        echo json_encode($lecons);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Prepare data for the form view
     *
     * @param array|null $inscription Inscription data if in inscription mode
     * @param bool $is_libre Whether this is a free session
     * @return array Data for the view
     */
    private function _prepare_form_data($inscription, $is_libre) {
        $data = array(
            'controller' => 'formation_seances',
            'is_libre' => $is_libre,
            'inscription' => $inscription,
            'pilotes' => $this->membres_model->get_selector(),
            'instructeurs' => $this->membres_model->get_selector_instructeurs(),
            'programmes' => $this->formation_programme_model->get_selector(),
            'machines' => $this->planeurs_model->get_selector(),
            'meteo_options' => $this->meteo_options,
            'lecons' => array(),
            'existing_evaluations' => array()
        );

        // Load programme structure if we know the programme
        if ($inscription && !empty($inscription['programme_id'])) {
            $data['lecons'] = $this->_get_programme_structure($inscription['programme_id']);
        }

        return $data;
    }

    /**
     * Get programme structure (lessons with topics)
     *
     * @param int $programme_id Programme ID
     * @return array Lessons with their topics
     */
    private function _get_programme_structure($programme_id) {
        $lecons = $this->formation_lecon_model->get_by_programme($programme_id);
        foreach ($lecons as &$lecon) {
            $lecon['sujets'] = $this->formation_sujet_model->get_by_lecon($lecon['id']);
        }
        return $lecons;
    }

    /**
     * Build seance data from POST input
     *
     * @param bool $is_libre Whether this is a free session
     * @return array|false Seance data or false on validation failure
     */
    private function _build_seance_data($is_libre) {
        // Collect meteo
        $meteo = array();
        foreach ($this->meteo_options as $option) {
            if ($this->input->post('meteo_' . $option)) {
                $meteo[] = $option;
            }
        }

        // Convert duration from HH:MM to TIME format
        $duree = $this->input->post('duree');
        if (strpos($duree, ':') !== false && substr_count($duree, ':') == 1) {
            $duree .= ':00'; // Add seconds
        }

        $seance_data = array(
            'date_seance' => $this->input->post('date_seance'),
            'instructeur_id' => $this->input->post('instructeur_id'),
            'machine_id' => $this->input->post('machine_id'),
            'duree' => $duree,
            'nb_atterrissages' => (int) $this->input->post('nb_atterrissages'),
            'meteo' => json_encode($meteo),
            'commentaires' => $this->input->post('commentaires'),
            'prochaines_lecons' => $this->input->post('prochaines_lecons')
        );

        if ($is_libre) {
            // Free session: pilote_id and programme_id from form
            $seance_data['inscription_id'] = null;
            $seance_data['pilote_id'] = $this->input->post('pilote_id');
            $seance_data['programme_id'] = (int) $this->input->post('programme_id');
        } else {
            // Inscription session: get pilote and programme from inscription
            $inscription_id = (int) $this->input->post('inscription_id');
            $inscription = $this->formation_inscription_model->get_with_details($inscription_id);

            if (!$inscription) {
                $this->session->set_flashdata('error', $this->lang->line('formation_seance_inscription_required'));
                return false;
            }

            if ($inscription['statut'] !== 'ouverte') {
                $this->session->set_flashdata('error', $this->lang->line('formation_seance_inscription_not_open'));
                return false;
            }

            $seance_data['inscription_id'] = $inscription_id;
            $seance_data['pilote_id'] = $inscription['pilote_id'];
            $seance_data['programme_id'] = $inscription['programme_id'];
        }

        return $seance_data;
    }

    /**
     * Collect evaluations from POST data
     *
     * Evaluations are posted as:
     *   eval[sujet_id][niveau] = 'A'
     *   eval[sujet_id][commentaire] = '...'
     *
     * @return array Evaluations ready for save_batch
     */
    private function _collect_evaluations() {
        $evals_post = $this->input->post('eval');
        $evaluations = array();

        if (!empty($evals_post) && is_array($evals_post)) {
            foreach ($evals_post as $sujet_id => $eval_data) {
                $niveau = $eval_data['niveau'] ?? '-';
                if ($niveau !== '-') {
                    $evaluations[] = array(
                        'sujet_id' => (int) $sujet_id,
                        'niveau' => $niveau,
                        'commentaire' => $eval_data['commentaire'] ?? null
                    );
                }
            }
        }

        return $evaluations;
    }
}
