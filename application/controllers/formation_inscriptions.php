<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Formation Inscriptions Controller
 *
 * Gère le cycle de vie complet des inscriptions aux formations :
 * - Ouverture d'inscription à un programme
 * - Suspension temporaire (avec motif)
 * - Réactivation
 * - Clôture (succès ou abandon)
 *
 * @package controllers
 * @author GVV Development Team
 * @see doc/prds/suivi_formation_prd.md
 * @see doc/plans/suivi_formation_plan.md Phase 3
 */
class Formation_inscriptions extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        
        // Load required models
        $this->load->model('formation_inscription_model');
        $this->load->model('formation_programme_model');
        $this->load->model('formation_seance_model');
        $this->load->model('membres_model');
        
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
     * Liste des inscriptions avec filtres
     */
    public function index() {
        log_message('debug', 'FORMATION_INSCRIPTIONS: index() method called');
        
        // Get filters from query string
        $filters = array(
            'pilote_id' => $this->input->get('pilote_id'),
            'programme_id' => $this->input->get('programme_id'),
            'statut' => $this->input->get('statut'),
            'instructeur_referent_id' => $this->input->get('instructeur_referent_id')
        );
        
        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });
        
        // Get inscriptions
        $inscriptions = $this->formation_inscription_model->get_all($filters);
        
        // Prepare data for view
        $data = array(
            'controller' => 'formation_inscriptions',
            'inscriptions' => $inscriptions,
            'filters' => $filters,
            'pilotes' => $this->membres_model->get_selector(),
            'programmes' => $this->formation_programme_model->get_selector(),
            'instructeurs' => $this->membres_model->get_selector_instructeurs()
        );
        
        $this->load->view('formation_inscriptions/index', $data);
    }
    
    /**
     * Formulaire d'ouverture d'une nouvelle inscription
     */
    public function ouvrir() {
        log_message('debug', 'FORMATION_INSCRIPTIONS: ouvrir() method called');
        
        // Prepare data for view
        $data = array(
            'controller' => 'formation_inscriptions',
            'action' => 'ouvrir',
            'inscription' => array(),
            'pilotes' => $this->membres_model->get_selector(),
            'programmes' => $this->formation_programme_model->get_selector(),
            'instructeurs' => $this->membres_model->get_selector_instructeurs()
        );
        
        $this->load->view('formation_inscriptions/ouvrir', $data);
    }
    
    /**
     * Enregistrement d'une nouvelle inscription
     */
    public function store() {
        log_message('debug', 'FORMATION_INSCRIPTIONS: store() method called');
        log_message('debug', 'FORMATION_INSCRIPTIONS: POST data: ' . print_r($_POST, TRUE));
        
        // Validation
        $this->form_validation->set_rules('pilote_id', $this->lang->line('formation_inscription_pilote'), 'required');
        $this->form_validation->set_rules('programme_id', $this->lang->line('formation_inscription_programme'), 'required|integer');
        $this->form_validation->set_rules('instructeur_referent_id', $this->lang->line('formation_inscription_instructeur'), '');
        $this->form_validation->set_rules('date_ouverture', $this->lang->line('formation_inscription_date_ouverture'), 'required');
        
        if ($this->form_validation->run() === FALSE) {
            return $this->ouvrir();
        }
        
        // Check if pilot already has an open inscription for this programme
        $pilote_id = $this->input->post('pilote_id');
        $programme_id = $this->input->post('programme_id');
        
        $existing = $this->formation_inscription_model->get_by_pilote_programme($pilote_id, $programme_id, 'ouverte');
        if (!empty($existing)) {
            $this->session->set_flashdata('error', $this->lang->line('formation_inscription_already_open'));
            return $this->ouvrir();
        }
        
        // Get programme version
        $programme = $this->formation_programme_model->get($programme_id);
        if (!$programme) {
            $this->session->set_flashdata('error', 'Programme introuvable');
            return $this->ouvrir();
        }
        
        // Prepare inscription data
        $inscription_data = array(
            'pilote_id' => $pilote_id,
            'programme_id' => $programme_id,
            'version_programme' => $programme['version'],
            'instructeur_referent_id' => $this->input->post('instructeur_referent_id') ?: NULL,
            'date_ouverture' => $this->input->post('date_ouverture'),
            'statut' => 'ouverte',
            'commentaires' => $this->input->post('commentaire')
        );
        
        // Log the data being inserted
        log_message('debug', 'FORMATION_INSCRIPTIONS: Creating inscription with data: ' . print_r($inscription_data, true));
        
        // Create inscription
        $inscription_id = $this->formation_inscription_model->create($inscription_data);
        
        if (!$inscription_id) {
            // Get database error (CI 2.x methods)
            $db_error_msg = $this->db->_error_message();
            $db_error_num = $this->db->_error_number();
            $error_msg = $this->lang->line('formation_inscription_create_error');
            
            // Add detailed error information
            if (!empty($db_error_msg)) {
                $error_msg .= '<br><strong>Erreur technique:</strong> ' . htmlspecialchars($db_error_msg) . ' (Code: ' . $db_error_num . ')';
                log_message('error', 'FORMATION_INSCRIPTIONS: Database error during create: ' . $db_error_num . ' - ' . $db_error_msg);
            }
            
            // Log the SQL query
            log_message('error', 'FORMATION_INSCRIPTIONS: Last query: ' . $this->db->last_query());
            
            $this->session->set_flashdata('error', $error_msg);
            return $this->ouvrir();
        }
        
        log_message('debug', 'FORMATION_INSCRIPTIONS: Inscription created successfully with ID: ' . $inscription_id);
        
        // Success
        $this->session->set_flashdata('success', $this->lang->line('formation_inscription_create_success'));
        redirect('formation_inscriptions/detail/' . $inscription_id);
    }
    
    /**
     * Suspendre une inscription
     *
     * @param int $id Inscription ID
     */
    public function suspendre($id) {
        log_message('debug', 'FORMATION_INSCRIPTIONS: suspendre() method called for id=' . $id);
        
        $inscription = $this->formation_inscription_model->get($id);
        if (!$inscription) {
            show_404();
        }
        
        // Check status
        if ($inscription['statut'] !== 'ouverte') {
            $this->session->set_flashdata('error', $this->lang->line('formation_inscription_cannot_suspend'));
            redirect('formation_inscriptions/detail/' . $id);
        }
        
        // Handle POST
        if ($this->input->post()) {
            $motif = $this->input->post('motif');
            
            if (empty($motif)) {
                $this->session->set_flashdata('error', $this->lang->line('formation_inscription_motif_required'));
            } else {
                log_message('debug', 'FORMATION_INSCRIPTIONS: Suspending inscription ID ' . $id . ' with motif: ' . $motif);
                $success = $this->formation_inscription_model->suspendre($id, $motif);
                
                if ($success) {
                    $this->session->set_flashdata('success', $this->lang->line('formation_inscription_suspend_success'));
                    log_message('debug', 'FORMATION_INSCRIPTIONS: Inscription suspended successfully');
                } else {
                    $db_error_msg = $this->db->_error_message();
                    $db_error_num = $this->db->_error_number();
                    $error_msg = $this->lang->line('formation_inscription_suspend_error');
                    if (!empty($db_error_msg)) {
                        $error_msg .= '<br><strong>Erreur technique:</strong> ' . htmlspecialchars($db_error_msg) . ' (Code: ' . $db_error_num . ')';
                        log_message('error', 'FORMATION_INSCRIPTIONS: Database error during suspend: ' . $db_error_num . ' - ' . $db_error_msg);
                    }
                    log_message('error', 'FORMATION_INSCRIPTIONS: Last query: ' . $this->db->last_query());
                    $this->session->set_flashdata('error', $error_msg);
                }
            }
            
            redirect('formation_inscriptions/detail/' . $id);
        }
        
        // Show confirmation form
        $data = array(
            'controller' => 'formation_inscriptions',
            'inscription' => $inscription
        );
        
        $this->load->view('formation_inscriptions/suspendre', $data);
    }
    
    /**
     * Réactiver une inscription suspendue
     *
     * @param int $id Inscription ID
     */
    public function reactiver($id) {
        log_message('debug', 'FORMATION_INSCRIPTIONS: reactiver() method called for id=' . $id);
        
        $inscription = $this->formation_inscription_model->get($id);
        if (!$inscription) {
            show_404();
        }
        
        // Check status
        if ($inscription['statut'] !== 'suspendue') {
            $this->session->set_flashdata('error', $this->lang->line('formation_inscription_cannot_reactivate'));
            redirect('formation_inscriptions/detail/' . $id);
        }
        
        // Reactivate
        log_message('debug', 'FORMATION_INSCRIPTIONS: Reactivating inscription ID ' . $id);
        $success = $this->formation_inscription_model->reactiver($id);
        
        if ($success) {
            $this->session->set_flashdata('success', $this->lang->line('formation_inscription_reactivate_success'));
            log_message('debug', 'FORMATION_INSCRIPTIONS: Inscription reactivated successfully');
        } else {
            $db_error_msg = $this->db->_error_message();
            $db_error_num = $this->db->_error_number();
            $error_msg = $this->lang->line('formation_inscription_reactivate_error');
            if (!empty($db_error_msg)) {
                $error_msg .= '<br><strong>Erreur technique:</strong> ' . htmlspecialchars($db_error_msg) . ' (Code: ' . $db_error_num . ')';
                log_message('error', 'FORMATION_INSCRIPTIONS: Database error during reactivate: ' . $db_error_num . ' - ' . $db_error_msg);
            }
            log_message('error', 'FORMATION_INSCRIPTIONS: Last query: ' . $this->db->last_query());
            $this->session->set_flashdata('error', $error_msg);
        }
        
        redirect('formation_inscriptions/detail/' . $id);
    }
    
    /**
     * Clôturer une inscription
     *
     * @param int $id Inscription ID
     */
    public function cloturer($id) {
        log_message('debug', 'FORMATION_INSCRIPTIONS: cloturer() method called for id=' . $id);
        
        $inscription = $this->formation_inscription_model->get($id);
        if (!$inscription) {
            show_404();
        }
        
        // Check status
        if (!in_array($inscription['statut'], array('ouverte', 'suspendue'))) {
            $this->session->set_flashdata('error', $this->lang->line('formation_inscription_cannot_close'));
            redirect('formation_inscriptions/detail/' . $id);
        }
        
        // Handle POST
        if ($this->input->post()) {
            $type = $this->input->post('type'); // 'cloturee' or 'abandonnee'
            $motif = $this->input->post('motif');
            
            if (empty($type) || !in_array($type, array('cloturee', 'abandonnee'))) {
                $this->session->set_flashdata('error', $this->lang->line('formation_inscription_type_required'));
            } elseif ($type === 'abandonnee' && empty($motif)) {
                $this->session->set_flashdata('error', $this->lang->line('formation_inscription_motif_required'));
            } else {
                log_message('debug', 'FORMATION_INSCRIPTIONS: Closing inscription ID ' . $id . ' with type: ' . $type . ', motif: ' . $motif);
                $success = $this->formation_inscription_model->cloturer($id, $type, $motif);
                
                if ($success) {
                    $this->session->set_flashdata('success', $this->lang->line('formation_inscription_close_success'));
                    log_message('debug', 'FORMATION_INSCRIPTIONS: Inscription closed successfully');
                } else {
                    $db_error_msg = $this->db->_error_message();
                    $db_error_num = $this->db->_error_number();
                    $error_msg = $this->lang->line('formation_inscription_close_error');
                    if (!empty($db_error_msg)) {
                        $error_msg .= '<br><strong>Erreur technique:</strong> ' . htmlspecialchars($db_error_msg) . ' (Code: ' . $db_error_num . ')';
                        log_message('error', 'FORMATION_INSCRIPTIONS: Database error during close: ' . $db_error_num . ' - ' . $db_error_msg);
                    }
                    log_message('error', 'FORMATION_INSCRIPTIONS: Last query: ' . $this->db->last_query());
                    $this->session->set_flashdata('error', $error_msg);
                }
            }
            
            redirect('formation_inscriptions/detail/' . $id);
        }
        
        // Show confirmation form
        $data = array(
            'controller' => 'formation_inscriptions',
            'inscription' => $inscription
        );
        
        $this->load->view('formation_inscriptions/cloturer', $data);
    }
    
    /**
     * Détail d'une inscription avec historique des séances
     *
     * @param int $id Inscription ID
     */
    public function detail($id) {
        log_message('debug', 'FORMATION_INSCRIPTIONS: detail() method called for id=' . $id);
        
        $inscription = $this->formation_inscription_model->get_with_details($id);
        if (!$inscription) {
            show_404();
        }
        
        // Get seances
        $seances = $this->formation_seance_model->get_by_inscription($id);
        
        // Use Formation_progression library for rich progression data
        $this->load->library('Formation_progression');
        $progression_data = $this->formation_progression->calculer($id);
        
        // Check if current user is the student (read-only mode)
        $current_user = $this->dx_auth->get_username();
        $is_student_view = ($current_user === $inscription['pilote_id']);

        // Get solo authorizations for this inscription
        $this->load->model('formation_autorisation_solo_model');
        $autorisations_solo = $this->formation_autorisation_solo_model->get_by_inscription($id);

        // Prepare data for view
        $data = array(
            'controller' => 'formation_inscriptions',
            'inscription' => $inscription,
            'seances' => $seances,
            'lecons' => $progression_data ? $progression_data['lecons'] : array(),
            'stats' => $progression_data ? $progression_data['stats'] : array(),
            'progression' => $progression_data ? array(
                'total_sujets' => $progression_data['stats']['nb_sujets_total'],
                'sujets_acquis' => $progression_data['stats']['nb_sujets_acquis'],
                'pourcentage' => $progression_data['stats']['pourcentage_acquis']
            ) : array('total_sujets' => 0, 'sujets_acquis' => 0, 'pourcentage' => 0),
            'formation_progression' => $this->formation_progression,
            'is_student_view' => $is_student_view,
            'autorisations_solo' => $autorisations_solo
        );

        $this->load->view('formation_inscriptions/detail', $data);
    }
}
