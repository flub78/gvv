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

        $programme_id = $this->formation_programme_model->insert($programme_data);

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
     */
    public function edit($id)
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
        $this->form_validation->set_rules('objectifs', $this->lang->line('formation_programme_objectifs'), 'max_length[2000]');

        if ($this->form_validation->run() === FALSE) {
            // Validation failed - redisplay form
            return $this->edit($id);
        }

        // Prepare update data
        $update_data = array(
            'titre' => $this->input->post('titre'),
            'description' => $this->input->post('description'),
            'objectifs' => $this->input->post('objectifs'),
            'section_id' => $this->input->post('section_id') ?: NULL,
            'actif' => $this->input->post('actif') ? 1 : 0,
            'version' => $programme['version'] + 1,
            'date_modification' => date('Y-m-d H:i:s')
        );

        $success = $this->formation_programme_model->update($id, $update_data);

        if (!$success) {
            $this->session->set_flashdata('error', $this->lang->line('formation_programme_update_error'));
            return $this->edit($id);
        }

        // Success
        $this->session->set_flashdata('success', $this->lang->line('formation_programme_update_success'));
        redirect('programmes/view/' . $id);
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
     * Import from Markdown file
     */
    private function import_from_markdown()
    {
        log_message('debug', 'PROGRAMMES: import_from_markdown() called');

        // Check file upload
        if (!isset($_FILES['markdown_file']) || $_FILES['markdown_file']['error'] != UPLOAD_ERR_OK) {
            $this->session->set_flashdata('error', $this->lang->line('formation_import_error_upload'));
            return $this->create();
        }

        // Read file content
        $markdown_content = file_get_contents($_FILES['markdown_file']['tmp_name']);
        
        if ($markdown_content === FALSE || empty($markdown_content)) {
            $this->session->set_flashdata('error', $this->lang->line('formation_import_error_empty'));
            return $this->create();
        }

        // Parse Markdown
        try {
            $parsed_data = $this->formation_markdown_parser->parse($markdown_content);
            
            // Validate structure
            $validation_result = $this->formation_markdown_parser->validate($parsed_data);
            if ($validation_result !== TRUE) {
                $this->session->set_flashdata('error', $this->lang->line('formation_import_error_invalid') . ': ' . $validation_result);
                return $this->create();
            }
        } catch (Exception $e) {
            log_message('error', 'PROGRAMMES: Parse error: ' . $e->getMessage());
            $this->session->set_flashdata('error', $this->lang->line('formation_import_error_parse') . ': ' . $e->getMessage());
            return $this->create();
        }

        // Start transaction
        $this->db->trans_start();

        // Create program
        $programme_data = array(
            'titre' => $parsed_data['titre'],
            'description' => isset($parsed_data['description']) ? $parsed_data['description'] : '',
            'objectifs' => isset($parsed_data['objectifs']) ? $parsed_data['objectifs'] : '',
            'section_id' => $this->input->post('section_id') ?: NULL,
            'version' => 1,
            'actif' => 1,
            'contenu_markdown' => $markdown_content,
            'date_creation' => date('Y-m-d H:i:s')
        );

        $programme_id = $this->formation_programme_model->insert($programme_data);

        if (!$programme_id) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', $this->lang->line('formation_import_error_db'));
            return $this->create();
        }

        // Create lessons and subjects
        foreach ($parsed_data['lecons'] as $lecon_data) {
            $lecon_record = array(
                'programme_id' => $programme_id,
                'numero' => $lecon_data['numero'],
                'titre' => $lecon_data['titre'],
                'description' => isset($lecon_data['description']) ? $lecon_data['description'] : '',
                'objectifs' => isset($lecon_data['objectifs']) ? $lecon_data['objectifs'] : '',
                'ordre' => $lecon_data['numero']
            );

            $lecon_id = $this->formation_lecon_model->insert($lecon_record);

            if (!$lecon_id) {
                $this->db->trans_rollback();
                $this->session->set_flashdata('error', $this->lang->line('formation_import_error_lecon'));
                return $this->create();
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

                    $sujet_id = $this->formation_sujet_model->insert($sujet_record);

                    if (!$sujet_id) {
                        $this->db->trans_rollback();
                        $this->session->set_flashdata('error', $this->lang->line('formation_import_error_sujet'));
                        return $this->create();
                    }
                }
            }
        }

        // Commit transaction
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->session->set_flashdata('error', $this->lang->line('formation_import_error_transaction'));
            return $this->create();
        }

        // Success
        log_message('info', 'PROGRAMMES: Successfully imported program from Markdown: ' . $programme_data['titre']);
        $this->session->set_flashdata('success', $this->lang->line('formation_import_success'));
        redirect('programmes/view/' . $programme_id);
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
