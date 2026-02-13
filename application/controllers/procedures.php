<?php

/**
 *    GVV Gestion vol à voile
 *    Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
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
 *
 * @filesource procedures.php
 * @package controllers
 * Contrôleur des procédures / CRUD
 *
 * Gestion des procédures du club avec support markdown et fichiers attachés
 */

/**
 * Include parent library
 */
include('./application/libraries/Gvv_Controller.php');

/**
 * Contrôleur de gestion des procédures
 */
class Procedures extends Gvv_Controller {
    protected $controller = 'procedures';
    protected $model = 'procedures_model';
    protected $modification_level = 'ca'; // Seuls les admins club peuvent modifier

    protected $rules = array();

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();

        // Authorization: Code-based (v2.0) - only for migrated users
        // page accessible to all users, view/edit/delete requires ca (via modification_level)
        if ($this->use_new_auth) {
            $this->require_roles(['user']);
        }

        $this->lang->load('procedures');
        $this->load->model('procedures_model');
        $this->load->model('sections_model');
        $this->load->library('File_manager');
        $this->load->helper('markdown');
        $this->load->helper('file');

        // Pour compatibilité avec le parent Gvv_Controller
        $this->gvv_model = $this->procedures_model;
    }

    /**
     * Page d'index - Liste des procédures
     */
    public function index() {
        $data = array();
        
        // Filtres optionnels
        $status_filter = $this->input->get('status');
        $section_filter = $this->input->get('section');
        
        $selection = array();
        if ($status_filter && $status_filter !== 'all') {
            $selection['status'] = $status_filter;
        }
        if ($section_filter && $section_filter !== 'all') {
            if ($section_filter === 'global') {
                $selection['section_id'] = null;
            } else {
                $selection['section_id'] = $section_filter;
            }
        }
        
        // Récupérer les procédures
        $procedures = $this->procedures_model->select_page(0, 0, $selection);
        
        $data['procedures'] = $procedures;
        $data['status_filter'] = $status_filter;
        $data['section_filter'] = $section_filter;
        
        // Options pour les filtres
        $data['status_options'] = array(
            'all' => 'Tous les statuts',
            'draft' => 'Brouillons',
            'published' => 'Publiées',
            'archived' => 'Archivées'
        );
        
        $data['section_options'] = array('all' => 'Toutes les sections', 'global' => 'Globales');
        $sections = $this->sections_model->select_all();
        foreach ($sections as $section) {
            $data['section_options'][$section['id']] = $section['nom'];
        }
        
        load_last_view('procedures/tableView', $data);
    }

    /**
     * Afficher une procédure avec rendu markdown
     */
    public function view($id) {
        $procedure = $this->procedures_model->get_by_id('id', $id);
        if (!$procedure) {
            show_404();
            return;
        }
        
        $data = array();
        $data['procedure'] = $procedure;
        
        // Récupérer le contenu markdown
        $markdown_content = $this->procedures_model->get_markdown_content($procedure['name']);
        $data['markdown_content'] = $markdown_content;
        $data['markdown_html'] = $markdown_content ? markdown($markdown_content) : '';
        
        // Récupérer les fichiers attachés
        $data['attached_files'] = $this->procedures_model->list_procedure_files($procedure['name']);
        
        // Vérifier les permissions de modification
        $data['can_edit'] = $this->dx_auth->is_role('ca') || $this->dx_auth->is_role('admin');
        
        load_last_view('procedures/view', $data);
    }

    /**
     * Affiche le formulaire de création
     */
    function create() {
        if (empty($this->data)) { // Check if data is already populated from a failed submission
            $table = $this->procedures_model->table();
            $this->data = $this->gvvmetadata->defaults_list($table);
        }
        
        $this->form_static_element(CREATION);
        
        return load_last_view($this->form_view, $this->data, $this->unit_test);
    }

    /**
     * Affiche le formulaire de modification
     */
    function edit($id = "", $load_view = true, $action = MODIFICATION) {
        $record = $this->procedures_model->get_by_id('id', $id);
        if (!$record) {
            show_404();
            return;
        }
        
        $this->data = $record;
        $this->form_static_element($action);
        
        // Récupérer le contenu markdown pour l'édition
        $this->data['markdown_content'] = $this->procedures_model->get_markdown_content($record['name']);
        
        if ($load_view) {
            return load_last_view($this->form_view, $this->data, $this->unit_test);
        }
        return $this->data;
    }

    /**
     * Traiter l'ajout d'une nouvelle procédure
     */
    function ajout() {
        $this->form_validation->set_rules('name', 'Nom', 'required|alpha_dash|is_unique[procedures.name]');
        $this->form_validation->set_rules('title', 'Titre', 'required');
        
        if ($this->form_validation->run() === FALSE) {
            $this->create();
            return;
        }
        
        // Données de la procédure
        $data = array(
            'name' => $this->input->post('name'),
            'title' => $this->input->post('title'),
            'description' => $this->input->post('description'),
            'section_id' => $this->input->post('section_id') ?: null,
            'status' => $this->input->post('status') ?: 'draft',
            'version' => $this->input->post('version') ?: '1.0'
        );
        
        $is_uploading_md = !empty($_FILES['markdown_file']['name']);
        
        $procedure_id = $this->procedures_model->create_procedure($data, $is_uploading_md);
        
        if ($procedure_id) {
            if ($is_uploading_md) {
                if (!$this->handle_markdown_upload($data['name'])) {
                    // L'upload a échoué, on annule tout
                    $this->procedures_model->delete_procedure($procedure_id);
                    // handle_markdown_upload a déjà mis un message flash d'erreur
                    $this->data = $this->input->post();
                    $this->create();
                    return;
                }
            }
            
            $this->session->set_flashdata('success', 'Procédure créée avec succès.');
            redirect('procedures');
        } else {
            $error_message = $this->procedures_model->error ?: 'Erreur lors de la création de la procédure';
            $this->session->set_flashdata('error', $error_message);
            
            $this->data = $this->input->post();
            $this->create();
            return;
        }
    }

    /**
     * Traiter la modification d'une procédure
     */
    function modifier() {
        $id = $this->input->post('id');
        $procedure = $this->procedures_model->get_by_id('id', $id);
        
        if (!$procedure) {
            show_404();
            return;
        }
        
        $this->form_validation->set_rules('title', 'Titre', 'required');
        
        if ($this->form_validation->run() === FALSE) {
            $this->edit($id);
            return;
        }
        
        // Données à mettre à jour
        $data = array(
            'title' => $this->input->post('title'),
            'description' => $this->input->post('description'),
            'section_id' => $this->input->post('section_id') ?: null,
            'status' => $this->input->post('status'),
            'version' => $this->input->post('version')
        );
        
        $success = $this->procedures_model->update_procedure($id, $data);
        
        // Sauvegarder le contenu markdown si fourni
        $markdown_content = $this->input->post('markdown_content');
        if ($markdown_content !== null) {
            $this->procedures_model->save_markdown_content($procedure['name'], $markdown_content);
        }
        
        // Traiter l'upload du fichier markdown si fourni
        if (!empty($_FILES['markdown_file']['name'])) {
            $this->handle_markdown_upload($procedure['name']);
        }
        
        if ($success) {
            $this->session->set_flashdata('success', 'Procédure modifiée avec succès');
            redirect("procedures/view/$id");
        } else {
            $this->session->set_flashdata('error', 'Erreur lors de la modification');
            $this->edit($id);
        }
    }

    /**
     * Supprimer une procédure
     */
    function delete($id) {
        if (!$this->dx_auth->is_role('admin')) {
            $this->dx_auth->deny_access();
            return;
        }
        
        $procedure = $this->procedures_model->get_by_id('id', $id);
        if (!$procedure) {
            show_404();
            return;
        }
        
        if ($this->procedures_model->delete_procedure($id)) {
            $this->session->set_flashdata('success', 'Procédure supprimée avec succès');
        } else {
            $error_message = $this->procedures_model->error ?: 'Erreur lors de la suppression';
            $this->session->set_flashdata('error', $error_message);
        }
        
        redirect('procedures');
    }

    /**
     * Éditer le contenu markdown d'une procédure
     */
    function edit_markdown($id) {
        $procedure = $this->procedures_model->get_by_id('id', $id);
        if (!$procedure) {
            show_404();
            return;
        }
        
        $data = array();
        $data['procedure'] = $procedure;
        $data['markdown_content'] = $this->procedures_model->get_markdown_content($procedure['name']);
        
        load_last_view('procedures/editMarkdown', $data);
    }

    /**
     * Sauvegarder le contenu markdown via AJAX
     */
    function save_markdown() {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }
        
        $id = $this->input->post('id');
        $content = $this->input->post('content');
        
        $procedure = $this->procedures_model->get_by_id('id', $id);
        if (!$procedure) {
            echo json_encode(array('success' => false, 'error' => 'Procédure non trouvée'));
            return;
        }
        
        $success = $this->procedures_model->save_markdown_content($procedure['name'], $content);
        
        echo json_encode(array(
            'success' => $success,
            'message' => $success ? 'Contenu sauvegardé' : 'Erreur de sauvegarde'
        ));
    }

    /**
     * Gérer les fichiers attachés
     */
    function attachments($id) {
        $procedure = $this->procedures_model->get_by_id('id', $id);
        if (!$procedure) {
            show_404();
            return;
        }
        
        $data = array();
        $data['procedure'] = $procedure;
        $data['files'] = $this->procedures_model->list_procedure_files($procedure['name']);
        
        load_last_view('procedures/attachments', $data);
    }

    /**
     * Upload d'un fichier attaché
     */
    function upload_file($id) {
        $procedure = $this->procedures_model->get_by_id('id', $id);
        if (!$procedure) {
            show_404();
            return;
        }
        
        $result = $this->procedures_model->upload_procedure_file($procedure['name'], 'file');
        
        if ($this->input->is_ajax_request()) {
            echo json_encode($result);
        } else {
            if ($result['success']) {
                $this->session->set_flashdata('success', 'Fichier uploadé avec succès');
            } else {
                $this->session->set_flashdata('error', 'Erreur: ' . $result['error']);
            }
            redirect("procedures/attachments/$id");
        }
    }

    /**
     * Supprimer un fichier attaché
     */
    function delete_file($id, $filename) {
        $procedure = $this->procedures_model->get_by_id('id', $id);
        if (!$procedure) {
            show_404();
            return;
        }
        
        // Empêcher la suppression du fichier markdown principal
        $markdown_filename = "procedure_{$procedure['name']}.md";
        if ($filename === $markdown_filename) {
            $this->session->set_flashdata('error', 'Impossible de supprimer le fichier markdown principal');
            redirect("procedures/attachments/$id");
            return;
        }
        
        $success = $this->procedures_model->delete_procedure_file($procedure['name'], $filename);
        
        if ($this->input->is_ajax_request()) {
            echo json_encode(array('success' => $success));
        } else {
            if ($success) {
                $this->session->set_flashdata('success', 'Fichier supprimé avec succès');
            } else {
                $this->session->set_flashdata('error', 'Erreur lors de la suppression');
            }
            redirect("procedures/attachments/$id");
        }
    }

    /**
     * Télécharger un fichier
     */
    function download($id, $filename) {
        $procedure = $this->procedures_model->get_by_id('id', $id);
        if (!$procedure) {
            show_404();
            return;
        }
        
        $file_path = "./uploads/procedures/{$procedure['name']}/" . $filename;
        
        if (!file_exists($file_path)) {
            show_404();
            return;
        }
        
        $this->load->helper('download');
        force_download($filename, file_get_contents($file_path));
    }

    /**
     * Génération des éléments à passer au formulaire
     */
    function form_static_element($action) {
        parent::form_static_element($action);
        
        // Sélecteur de sections avec option "Globale"
        $sections = $this->sections_model->select_all();
        $section_options = array('' => 'Globale (toutes sections)');
        foreach ($sections as $section) {
            $section_options[$section['id']] = $section['nom'];
        }
        $this->data['section_options'] = $section_options;
        
        // Options de statut
        $this->data['status_options'] = array(
            'draft' => 'Brouillon',
            'published' => 'Publiée',
            'archived' => 'Archivée'
        );
        
        if ($action == CREATION) {
            $this->data['created_by'] = $this->dx_auth->get_username();
        }
    }

    /**
     * Gérer l'upload d'un fichier markdown
     */
    private function handle_markdown_upload($procedure_name) {
        gvv_debug("procedure: handle_markdown_upload started for procedure '{$procedure_name}'.");
        $config = array(
            'allowed_types' => 'md|txt',
            'max_file_size' => 5120, // 5MB
            'overwrite' => true,
            'file_name' => "procedure_{$procedure_name}.md"
        );
        gvv_debug("procedure: upload config: " . var_export($config, true));
        
        $result = $this->file_manager->upload_file("procedures/{$procedure_name}", 'markdown_file', $config);
        
        if (!$result['success']) {
            $error_msg = isset($result['error']) ? $result['error'] : 'Unknown upload error.';
            gvv_debug("procedure: markdown upload failed for procedure '{$procedure_name}'. Error: " . $error_msg);
            $this->session->set_flashdata('warning', 'Erreur upload markdown: ' . $error_msg);
        } else {
            gvv_debug("procedure: markdown upload successful for procedure '{$procedure_name}'.");
        }
        
        return $result['success'];
    }
}