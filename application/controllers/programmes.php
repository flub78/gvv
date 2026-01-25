<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * GVV Gestion vol à voile
 * Copyright (C) 2011 Philippe Boissel & Frédéric Peignot
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @filesource Programmes.php
 * @package controllers
 * Controller for training programs management (Formation programmes)
 *
 * @see doc/design_notes/suivi_formation_design.md
 * @see doc/plans/suivi_formation_plan.md
 *
 * Playwright tests:
 *   - npx playwright test tests/formation/programmes.spec.js
 */

include_once(APPPATH . '/libraries/Gvv_Controller.php');

class Programmes extends Gvv_Controller
{
    protected $controller = 'programmes';
    protected $model = 'formation_programme_model';
    protected $modification_level = 'admin'; // Legacy authorization
    protected $use_new_auth = FALSE; // Use legacy authorization system
    protected $rules = array();
    protected $filter_variables = array();

    /**
     * Constructor
     */
    function __construct()
    {
        parent::__construct();

        // Check feature flag first
        $this->load->library('formation_access');
        $this->formation_access->check_access_or_403();

        // Authorization: Only admins can manage programs
        if (!$this->dx_auth->is_admin()) {
            show_error('Vous devez être administrateur pour gérer les programmes de formation.', 403, 'Accès refusé');
        }

        $this->load->model('formation_programme_model');
        $this->load->model('formation_lecon_model');
        $this->load->model('formation_sujet_model');
        $this->load->library('formation_markdown_parser');
        $this->load->library('form_validation');
        $this->lang->load('formation');
        $this->lang->load('gvv');
    }

    /**
     * Index - Display all training programs
     */
    public function index()
    {
        log_message('debug', 'PROGRAMMES: index() method called');

        $data['title'] = $this->lang->line('formation_programmes_title');
        $data['controller'] = $this->controller;

        // Get all programs
        $data['programmes'] = $this->formation_programme_model->get_all();

        // Count lessons for each program
        foreach ($data['programmes'] as &$programme) {
            $programme['nb_lecons'] = $this->formation_lecon_model->count_by_programme($programme['id']);
            $programme['nb_sujets'] = $this->formation_sujet_model->count_by_programme($programme['id']);
        }

        return load_last_view('programmes/index', $data, $this->unit_test);
    }

    /**
     * Create - Display form for creating new program
     */
    public function create()
    {
        log_message('debug', 'PROGRAMMES: create() method called');

        $data['title'] = $this->lang->line('formation_programmes_create');
        $data['controller'] = $this->controller;
        $data['action'] = 'create';

        return load_last_view('programmes/form', $data, $this->unit_test);
    }

    /**
     * Store - Save new program
     */
    public function store()
    {
        log_message('debug', 'PROGRAMMES: store() method called');
        log_message('debug', 'PROGRAMMES: POST data: ' . print_r($_POST, TRUE));

        // Check if importing from Markdown
        if ($this->input->post('import_markdown') && isset($_FILES['markdown_file']) && $_FILES['markdown_file']['size'] > 0) {
            return $this->import_from_markdown();
        }

        // Manual program creation
        $this->form_validation->set_rules('titre', $this->lang->line('formation_programme_titre'), 'required|max_length[255]');
        $this->form_validation->set_rules('description', $this->lang->line('formation_programme_description'), 'max_length[1000]');
        $this->form_validation->set_rules('objectifs', $this->lang->line('formation_programme_objectifs'), 'max_length[2000]');

        if ($this->form_validation->run() === FALSE) {
            // Validation failed - redisplay form
            return $this->create();
        }

        // Prepare program data
        $programme_data = array(
            'titre' => $this->input->post('titre'),
            'description' => $this->input->post('description'),
            'objectifs' => $this->input->post('objectifs'),
            'section_id' => $this->input->post('section_id') ?: NULL,
            'version' => 1,
            'actif' => 1,
            'date_creation' => date('Y-m-d H:i:s')
        );

        $programme_id = $this->formation_programme_model->create($programme_data);

        if (!$programme_id) {
            $this->session->set_flashdata('error', $this->lang->line('formation_programme_create_error'));
            return $this->create();
        }

        // Success - redirect to edit to add lessons
        $this->session->set_flashdata('success', $this->lang->line('formation_programme_create_success'));
        redirect('programmes/edit/' . $programme_id);
    }

    /**
     * View - Display program details (read-only)
     *
     * @param int $id Program ID
     */
    public function view($id)
    {
        log_message('debug', 'PROGRAMMES: view() method called for id=' . $id);

        $data['title'] = $this->lang->line('formation_programmes_view');
        $data['controller'] = $this->controller;

        // Get program details
        $data['programme'] = $this->formation_programme_model->get($id);
        if (!$data['programme']) {
            show_404();
        }

        // Get lessons with their subjects
        $data['lecons'] = $this->formation_lecon_model->get_by_programme($id);
        foreach ($data['lecons'] as &$lecon) {
            $lecon['sujets'] = $this->formation_sujet_model->get_by_lecon($lecon['id']);
        }

        return load_last_view('programmes/view', $data, $this->unit_test);
    }

    /**
     * Edit - Display form for editing program
     *
     * @param int $id Program ID
     * @param bool $load_view Unused, for parent compatibility
     * @param int $action Unused, for parent compatibility
     */
    public function edit($id = '', $load_view = TRUE, $action = MODIFICATION)
    {
        log_message('debug', 'PROGRAMMES: edit() method called for id=' . $id);

        $data['title'] = $this->lang->line('formation_programmes_edit');
        $data['controller'] = $this->controller;
        $data['action'] = 'edit';

        // Get program details
        $data['programme'] = $this->formation_programme_model->get($id);
        if (!$data['programme']) {
            show_404();
        }

        // Get lessons with their subjects
        $data['lecons'] = $this->formation_lecon_model->get_by_programme($id);
        foreach ($data['lecons'] as &$lecon) {
            $lecon['sujets'] = $this->formation_sujet_model->get_by_lecon($lecon['id']);
        }

        return load_last_view('programmes/form', $data, $this->unit_test);
    }

    /**
     * Update - Save program modifications
     *
     * @param int $id Program ID
     */
    public function update($id)
    {
        log_message('debug', 'PROGRAMMES: update() method called for id=' . $id);
        log_message('debug', 'PROGRAMMES: POST data: ' . print_r($_POST, TRUE));

        // Check if program exists
        $programme = $this->formation_programme_model->get($id);
        if (!$programme) {
            show_404();
        }

        // Validation
        $this->form_validation->set_rules('titre', $this->lang->line('formation_programme_titre'), 'required|max_length[255]');
        $this->form_validation->set_rules('description', $this->lang->line('formation_programme_description'), 'max_length[1000]');

        log_message('debug', 'PROGRAMMES: Validation run result: ' . ($this->form_validation->run() ? 'TRUE' : 'FALSE'));
        log_message('debug', 'PROGRAMMES: Validation errors: ' . validation_errors());

        if ($this->form_validation->run() === FALSE) {
            // Validation failed - redisplay form
            return $this->edit($id);
        }

        // Prepare update data
        $update_data = array(
            'titre' => $this->input->post('titre'),
            'description' => $this->input->post('description'),
            'section_id' => $this->input->post('section_id') ?: NULL,
            'statut' => $this->input->post('statut') ?: 'actif'
        );

        log_message('debug', 'PROGRAMMES: update_data: ' . print_r($update_data, TRUE));
        
        $success = $this->formation_programme_model->update_programme($id, $update_data);

        log_message('debug', 'PROGRAMMES: update_programme returned: ' . ($success ? 'TRUE' : 'FALSE'));
        log_message('debug', 'PROGRAMMES: DB error: ' . $this->db->_error_message());
        
        if (!$success) {
            $this->session->set_flashdata('error', $this->lang->line('formation_programme_update_error'));
            return $this->edit($id);
        }

        // Success
        $this->session->set_flashdata('success', $this->lang->line('formation_programme_update_success'));
        redirect('programmes');
    }

    /**
     * Delete - Remove program and all related data
     *
     * @param int $id Program ID
     */
    public function delete($id)
    {
        log_message('debug', 'PROGRAMMES: delete() method called for id=' . $id);

        // Check if program exists
        $programme = $this->formation_programme_model->get($id);
        if (!$programme) {
            show_404();
        }

        // Check if program is used in inscriptions
        $this->load->model('formation_inscription_model');
        $inscriptions = $this->formation_inscription_model->get_by_programme($id);
        
        if (count($inscriptions) > 0) {
            $this->session->set_flashdata('error', $this->lang->line('formation_programme_delete_error_used'));
            redirect('programmes');
        }

        // Delete program (cascade will handle lessons and subjects)
        $success = $this->formation_programme_model->delete($id);

        if (!$success) {
            $this->session->set_flashdata('error', $this->lang->line('formation_programme_delete_error'));
        } else {
            $this->session->set_flashdata('success', $this->lang->line('formation_programme_delete_success'));
        }

        redirect('programmes');
    }

    /**
     * Generate a unique program code from title
     * 
     * @param string $titre Program title
     * @return string Unique code (max 50 chars)
     */
    private function generate_programme_code($titre)
    {
        // Convert to uppercase and remove accents
        $code = strtoupper($titre);
        $code = iconv('UTF-8', 'ASCII//TRANSLIT', $code);
        
        // Keep only alphanumeric and spaces
        $code = preg_replace('/[^A-Z0-9\s]/', '', $code);
        
        // Replace spaces with underscores
        $code = str_replace(' ', '_', $code);
        
        // Limit to 40 chars to leave room for uniqueness suffix
        $code = substr($code, 0, 40);
        
        // Check if code already exists
        $original_code = $code;
        $counter = 1;
        while (!$this->formation_programme_model->is_code_unique($code)) {
            $code = $original_code . '_' . $counter;
            $counter++;
        }
        
        return $code;
    }

    /**
     * Import from Markdown file
     */
    private function import_from_markdown()
    {
        log_message('debug', 'PROGRAMMES: import_from_markdown() called');

        // Check file upload
        if (!isset($_FILES['markdown_file']) || $_FILES['markdown_file']['error'] != UPLOAD_ERR_OK) {
            $data['title'] = $this->lang->line('formation_programmes_create');
            $data['controller'] = $this->controller;
            $data['action'] = 'create';
            $data['import_error'] = $this->lang->line('formation_import_error_upload');
            return load_last_view('programmes/form', $data, $this->unit_test);
        }

        // Read file content
        $markdown_content = file_get_contents($_FILES['markdown_file']['tmp_name']);
        $filename = $_FILES['markdown_file']['name'];
        
        if ($markdown_content === FALSE || empty($markdown_content)) {
            $data['title'] = $this->lang->line('formation_programmes_create');
            $data['controller'] = $this->controller;
            $data['action'] = 'create';
            $data['import_error'] = "Erreur de lecture du fichier '$filename' :\n\nLe fichier est vide ou illisible.";
            return load_last_view('programmes/form', $data, $this->unit_test);
        }

        // Parse Markdown
        try {
            $parsed_data = $this->formation_markdown_parser->parse($markdown_content);
            
            // Validate structure
            $validation_result = $this->formation_markdown_parser->validate($parsed_data);
            if ($validation_result !== TRUE) {
                $data['title'] = $this->lang->line('formation_programmes_create');
                $data['controller'] = $this->controller;
                $data['action'] = 'create';
                $data['import_error'] = "Erreurs de validation dans le fichier '$filename' :\n\n" . $validation_result;
                return load_last_view('programmes/form', $data, $this->unit_test);
            }
        } catch (Exception $e) {
            log_message('error', 'PROGRAMMES: Parse error: ' . $e->getMessage());
            $data['title'] = $this->lang->line('formation_programmes_create');
            $data['controller'] = $this->controller;
            $data['action'] = 'create';
            $data['import_error'] = "Erreur d'analyse du fichier '$filename' :\n\n" . $e->getMessage();
            return load_last_view('programmes/form', $data, $this->unit_test);
        }

        // Start transaction
        $this->db->trans_start();

        // Generate a unique code based on title
        $code = $this->generate_programme_code($parsed_data['titre']);

        // Create program
        $programme_data = array(
            'code' => $code,
            'titre' => $parsed_data['titre'],
            'description' => isset($parsed_data['description']) ? $parsed_data['description'] : '',
            'contenu_markdown' => $markdown_content,
            'section_id' => $this->input->post('section_id') ?: NULL,
            'version' => 1,
            'statut' => 'actif',
            'date_creation' => date('Y-m-d H:i:s')
        );

        $programme_id = $this->formation_programme_model->create($programme_data);
        
        // Get DB errors immediately (before any other operation)
        $db_error_msg = $this->db->_error_message();
        $db_error_num = $this->db->_error_number();
        $last_query = $this->db->last_query();

        if (!$programme_id) {
            $this->db->trans_rollback();
            log_message('error', 'PROGRAMMES: DB error creating programme: ' . $db_error_num . ' - ' . $db_error_msg);
            log_message('error', 'PROGRAMMES: Last query: ' . $last_query);
            log_message('error', 'PROGRAMMES: Programme data: ' . print_r($programme_data, true));
            
            $data['title'] = $this->lang->line('formation_programmes_create');
            $data['controller'] = $this->controller;
            $data['action'] = 'create';
            $data['import_error'] = "Erreur lors de la création du programme dans la base de données :\n\n";
            $data['import_error'] .= "Titre : " . $programme_data['titre'] . "\n\n";
            
            if (!empty($db_error_msg)) {
                $data['import_error'] .= "Erreur MySQL :\n";
                $data['import_error'] .= "Code : #" . $db_error_num . "\n";
                $data['import_error'] .= "Message : " . $db_error_msg . "\n\n";
            }
            
            $data['import_error'] .= "Requête SQL tentée :\n" . $last_query . "\n\n";
            $data['import_error'] .= "Données soumises :\n";
            foreach ($programme_data as $key => $value) {
                $display_value = is_null($value) ? 'NULL' : $value;
                $data['import_error'] .= "  - {$key} : " . $display_value . "\n";
            }
            
            return load_last_view('programmes/form', $data, $this->unit_test);
        }

        // Create lessons and subjects
        foreach ($parsed_data['lecons'] as $lecon_data) {
            $lecon_record = array(
                'programme_id' => $programme_id,
                'numero' => $lecon_data['numero'],
                'titre' => $lecon_data['titre'],
                'description' => isset($lecon_data['description']) ? $lecon_data['description'] : '',
                'ordre' => $lecon_data['numero']
            );

            $lecon_id = $this->formation_lecon_model->create($lecon_record);
            
            // Get DB errors immediately
            $db_error_msg = $this->db->_error_message();
            $db_error_num = $this->db->_error_number();
            $last_query = $this->db->last_query();

            if (!$lecon_id) {
                $this->db->trans_rollback();
                log_message('error', 'PROGRAMMES: DB error creating lesson: ' . $db_error_num . ' - ' . $db_error_msg);
                log_message('error', 'PROGRAMMES: Last query: ' . $last_query);
                log_message('error', 'PROGRAMMES: Lesson data: ' . print_r($lecon_record, true));
                
                $data['title'] = $this->lang->line('formation_programmes_create');
                $data['controller'] = $this->controller;
                $data['action'] = 'create';
                $data['import_error'] = "Erreur lors de la création de la leçon :\n\n";
                $data['import_error'] .= "Leçon {$lecon_data['numero']} : {$lecon_data['titre']}\n\n";
                
                if (!empty($db_error_msg)) {
                    $data['import_error'] .= "Erreur MySQL :\n";
                    $data['import_error'] .= "Code : #" . $db_error_num . "\n";
                    $data['import_error'] .= "Message : " . $db_error_msg . "\n\n";
                }
                
                $data['import_error'] .= "Requête SQL tentée :\n" . $last_query . "\n\n";
                $data['import_error'] .= "Données de la leçon :\n";
                foreach ($lecon_record as $key => $value) {
                    $display_value = is_null($value) ? 'NULL' : $value;
                    $data['import_error'] .= "  - {$key} : " . $display_value . "\n";
                }
                
                return load_last_view('programmes/form', $data, $this->unit_test);
            }

            // Create subjects for this lesson
            if (isset($lecon_data['sujets'])) {
                foreach ($lecon_data['sujets'] as $sujet_data) {
                    $sujet_record = array(
                        'lecon_id' => $lecon_id,
                        'numero' => $sujet_data['numero'],
                        'titre' => $sujet_data['titre'],
                        'description' => isset($sujet_data['description']) ? $sujet_data['description'] : '',
                        'objectifs' => isset($sujet_data['objectifs']) ? $sujet_data['objectifs'] : '',
                        'ordre' => $sujet_data['numero']
                    );

                    $sujet_id = $this->formation_sujet_model->create($sujet_record);
                    
                    // Get DB errors immediately
                    $db_error_msg = $this->db->_error_message();
                    $db_error_num = $this->db->_error_number();
                    $last_query = $this->db->last_query();

                    if (!$sujet_id) {
                        $this->db->trans_rollback();
                        log_message('error', 'PROGRAMMES: DB error creating subject: ' . $db_error_num . ' - ' . $db_error_msg);
                        log_message('error', 'PROGRAMMES: Last query: ' . $last_query);
                        log_message('error', 'PROGRAMMES: Subject data: ' . print_r($sujet_record, true));
                        
                        $data['title'] = $this->lang->line('formation_programmes_create');
                        $data['controller'] = $this->controller;
                        $data['action'] = 'create';
                        $data['import_error'] = "Erreur lors de la création du sujet :\n\n";
                        $data['import_error'] .= "Sujet {$sujet_data['numero']} : {$sujet_data['titre']}\n";
                        $data['import_error'] .= "Dans la leçon {$lecon_data['numero']} : {$lecon_data['titre']}\n\n";
                        
                        if (!empty($db_error_msg)) {
                            $data['import_error'] .= "Erreur MySQL :\n";
                            $data['import_error'] .= "Code : #" . $db_error_num . "\n";
                            $data['import_error'] .= "Message : " . $db_error_msg . "\n\n";
                        }
                        
                        $data['import_error'] .= "Requête SQL tentée :\n" . $last_query . "\n\n";
                        $data['import_error'] .= "Données du sujet :\n";
                        foreach ($sujet_record as $key => $value) {
                            $display_value = is_null($value) ? 'NULL' : $value;
                            $data['import_error'] .= "  - {$key} : " . $display_value . "\n";
                        }
                        
                        return load_last_view('programmes/form', $data, $this->unit_test);
                    }
                }
            }
        }

        // Commit transaction
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $db_error_msg = $this->db->_error_message();
            $db_error_num = $this->db->_error_number();
            log_message('error', 'PROGRAMMES: Transaction failed: ' . $db_error_num . ' - ' . $db_error_msg);
            $data['title'] = $this->lang->line('formation_programmes_create');
            $data['controller'] = $this->controller;
            $data['action'] = 'create';
            $data['import_error'] = "Erreur lors de la validation de la transaction en base de données :\n\n";
            if (!empty($db_error_msg)) {
                $data['import_error'] .= "Message d'erreur : " . $db_error_msg . "\n";
                $data['import_error'] .= "Code d'erreur : " . $db_error_num . "\n\n";
            }
            $data['import_error'] .= "Les données n'ont pas été enregistrées. Veuillez réessayer.";
            return load_last_view('programmes/form', $data, $this->unit_test);
        }

        // Success
        log_message('info', 'PROGRAMMES: Successfully imported program from Markdown: ' . $programme_data['titre']);
        $this->session->set_flashdata('success', $this->lang->line('formation_import_success'));
        redirect('programmes/view/' . $programme_id);
    }

    /**
     * Update programme structure from Markdown (text or file upload)
     * 
     * @param int $id Program ID
     */
    public function update_structure($id)
    {
        log_message('debug', 'PROGRAMMES: update_structure() called for id=' . $id);

        // Get existing program
        $programme = $this->formation_programme_model->get($id);
        if (!$programme) {
            show_404();
        }

        // Determine source: text or file
        $markdown_content = '';
        $filename = '';

        if (!empty($_FILES['markdown_file']['name'])) {
            // File upload
            if ($_FILES['markdown_file']['error'] != UPLOAD_ERR_OK) {
                $this->session->set_flashdata('error', $this->lang->line('formation_import_error_upload'));
                redirect('programmes/view/' . $id);
                return;
            }
            $markdown_content = file_get_contents($_FILES['markdown_file']['tmp_name']);
            $filename = $_FILES['markdown_file']['name'];
        } else {
            // Text editor
            $markdown_content = $this->input->post('markdown_content');
            $filename = 'édition manuelle';
        }

        if (empty($markdown_content)) {
            $this->session->set_flashdata('error', 'Le contenu Markdown est vide.');
            redirect('programmes/view/' . $id);
            return;
        }

        // Parse Markdown
        try {
            $parsed_data = $this->formation_markdown_parser->parse($markdown_content);
            
            // Validate structure
            $validation_result = $this->formation_markdown_parser->validate($parsed_data);
            if ($validation_result !== TRUE) {
                $this->session->set_flashdata('error', "Erreurs de validation ($filename) :\n\n" . $validation_result);
                redirect('programmes/view/' . $id);
                return;
            }
        } catch (Exception $e) {
            log_message('error', 'PROGRAMMES: Parse error in update_structure: ' . $e->getMessage());
            $this->session->set_flashdata('error', "Erreur d'analyse ($filename) :\n\n" . $e->getMessage());
            redirect('programmes/view/' . $id);
            return;
        }

        // Start transaction
        $this->db->trans_start();

        // Delete existing lessons and subjects (cascade will handle subjects)
        $this->db->where('programme_id', $id);
        $this->db->delete('formation_lecons');

        // Update program metadata
        $programme_update = array(
            'titre' => $parsed_data['titre'],
            'description' => isset($parsed_data['description']) ? $parsed_data['description'] : '',
            'contenu_markdown' => $markdown_content,
            'date_modification' => date('Y-m-d H:i:s')
        );
        $this->formation_programme_model->update('id', $programme_update, $id);

        // Increment version
        $this->formation_programme_model->increment_version($id);

        // Recreate lessons and subjects
        foreach ($parsed_data['lecons'] as $lecon_data) {
            $lecon_record = array(
                'programme_id' => $id,
                'numero' => $lecon_data['numero'],
                'titre' => $lecon_data['titre'],
                'description' => isset($lecon_data['description']) ? $lecon_data['description'] : '',
                'ordre' => $lecon_data['numero']
            );

            $lecon_id = $this->formation_lecon_model->create($lecon_record);

            if (!$lecon_id) {
                $this->db->trans_rollback();
                $db_error_msg = $this->db->_error_message();
                $db_error_num = $this->db->_error_number();
                log_message('error', 'PROGRAMMES: DB error updating lesson: ' . $db_error_num . ' - ' . $db_error_msg);
                $this->session->set_flashdata('error', "Erreur lors de la mise à jour de la leçon {$lecon_data['numero']} : {$lecon_data['titre']}\nErreur MySQL: " . $db_error_msg);
                redirect('programmes/view/' . $id);
                return;
            }

            // Create subjects for this lesson
            if (isset($lecon_data['sujets'])) {
                foreach ($lecon_data['sujets'] as $sujet_data) {
                    $sujet_record = array(
                        'lecon_id' => $lecon_id,
                        'numero' => $sujet_data['numero'],
                        'titre' => $sujet_data['titre'],
                        'description' => isset($sujet_data['description']) ? $sujet_data['description'] : '',
                        'objectifs' => isset($sujet_data['objectifs']) ? $sujet_data['objectifs'] : '',
                        'ordre' => $sujet_data['numero']
                    );

                    $sujet_id = $this->formation_sujet_model->create($sujet_record);

                    if (!$sujet_id) {
                        $this->db->trans_rollback();
                        $db_error_msg = $this->db->_error_message();
                        $db_error_num = $this->db->_error_number();
                        log_message('error', 'PROGRAMMES: DB error updating subject: ' . $db_error_num . ' - ' . $db_error_msg);
                        $this->session->set_flashdata('error', "Erreur lors de la mise à jour du sujet {$sujet_data['numero']} : {$sujet_data['titre']}\nErreur MySQL: " . $db_error_msg);
                        redirect('programmes/view/' . $id);
                        return;
                    }
                }
            }
        }

        // Commit transaction
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->session->set_flashdata('error', 'Erreur lors de la mise à jour de la structure du programme.');
            redirect('programmes/view/' . $id);
            return;
        }

        // Success
        log_message('info', 'PROGRAMMES: Successfully updated structure for program id=' . $id);
        $this->session->set_flashdata('success', 'Structure du programme mise à jour avec succès. Version incrémentée.');
        redirect('programmes/view/' . $id);
    }

    /**
     * Export program to Markdown file
     *
     * @param int $id Program ID
     */
    public function export($id)
    {
        log_message('debug', 'PROGRAMMES: export() method called for id=' . $id);

        // Get program details
        $programme = $this->formation_programme_model->get($id);
        if (!$programme) {
            show_404();
        }

        // If we have stored Markdown content, use it directly
        if (!empty($programme['contenu_markdown'])) {
            $markdown_content = $programme['contenu_markdown'];
        } else {
            // Generate Markdown from database structure
            $lecons = $this->formation_lecon_model->get_by_programme($id);
            
            // Prepare data for export
            $lecons_export = array();
            foreach ($lecons as $lecon) {
                $sujets = $this->formation_sujet_model->get_by_lecon($lecon['id']);
                
                $sujets_export = array();
                foreach ($sujets as $sujet) {
                    $sujets_export[] = array(
                        'numero' => $sujet['numero'],
                        'titre' => $sujet['titre'],
                        'description' => $sujet['description'],
                        'objectifs' => $sujet['objectifs']
                    );
                }
                
                $lecons_export[] = array(
                    'numero' => $lecon['numero'],
                    'titre' => $lecon['titre'],
                    'description' => isset($lecon['description']) ? $lecon['description'] : '',
                    'objectifs' => isset($lecon['objectifs']) ? $lecon['objectifs'] : '',
                    'sujets' => $sujets_export
                );
            }

            // Export to Markdown
            $markdown_content = $this->formation_markdown_parser->export($programme['titre'], $lecons_export);
        }

        // Sanitize filename
        $filename = preg_replace('/[^a-z0-9_\-]/i', '_', $programme['titre']);
        $filename = $filename . '_v' . $programme['version'] . '.md';

        // Send file for download
        header('Content-Type: text/markdown; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($markdown_content));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $markdown_content;
        exit;
    }
}

/* End of file Programmes.php */
/* Location: ./application/controllers/Programmes.php */
