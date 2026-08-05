<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Maintenance Programmes Controller
 *
 * Gestion des programmes d'entretien : metadonnees (code, titre, section,
 * regle de butee) + structure (sections/taches) importee depuis un fichier
 * markdown deverse via le systeme documentaire existant (archived_documents
 * / document_types, reutilise plutot que duplique).
 *
 * Miroir du controleur Formation `programmes.php` (meme role, mais
 * document verse via l'archivage plutot que stocke en base).
 *
 * @package controllers
 * @see doc/prds/maintenance_aeronefs_prd.md (EF2)
 * @see doc/plans/maintenance_aeronefs_plan.md (Etape 5.2)
 */
class Maintenance_programmes extends MY_Controller {

    public function __construct() {
        parent::__construct();

        $this->load->model('maintenance_programme_model');
        $this->load->model('maintenance_programme_section_model');
        $this->load->model('maintenance_tache_model');
        $this->load->model('sections_model');
        $this->load->model('document_types_model');
        $this->load->model('archived_documents_model');
        $this->load->library('form_validation');
        $this->load->library('Maintenance_markdown_parser');
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
     * Liste des programmes visibles pour la section courante
     */
    public function index() {
        $programmes = $this->maintenance_programme_model->get_by_section_admin($this->session->userdata('section'));

        foreach ($programmes as &$programme) {
            $programme['nb_sections'] = count($this->maintenance_programme_section_model->get_by_programme($programme['id']));
            $programme['nb_taches'] = count($this->maintenance_tache_model->get_by_programme($programme['id']));
        }
        unset($programme);

        $data = array(
            'controller'  => 'maintenance_programmes',
            'programmes'  => $programmes,
        );

        $this->load->view('maintenance_programmes/index', $data);
    }

    /**
     * Formulaire de creation (metadonnees uniquement, pas de document a cette etape)
     */
    public function create() {
        $data = array(
            'controller'       => 'maintenance_programmes',
            'action'           => 'create',
            'programme'        => array(
                'code' => '', 'titre' => '', 'section_id' => '',
                'regle_butee_date' => 0, 'regle_butee_heures' => 0,
                'seuil_heures' => '', 'periodicite_mois' => '',
            ),
            'section_selector' => $this->sections_model->section_selector_with_null(),
            'error'            => '',
        );

        $this->load->view('maintenance_programmes/form', $data);
    }

    /**
     * Callback de validation : unicite du code programme
     */
    public function code_unique($code) {
        $exclude_id = $this->input->post('id') ?: null;
        if (!$this->maintenance_programme_model->is_code_unique($code, $exclude_id)) {
            $this->form_validation->set_message('code_unique', $this->lang->line('maintenance_programme_code_deja_utilise'));
            return false;
        }
        return true;
    }

    private function set_programme_validation_rules() {
        $this->form_validation->set_rules('code', $this->lang->line('maintenance_programme_code'), 'required|max_length[50]|callback_code_unique');
        $this->form_validation->set_rules('titre', $this->lang->line('maintenance_programme_titre'), 'required|max_length[255]');
        $this->form_validation->set_rules('seuil_heures', $this->lang->line('maintenance_programme_seuil_heures'), 'numeric');
        $this->form_validation->set_rules('periodicite_mois', $this->lang->line('maintenance_programme_periodicite_mois'), 'integer|greater_than[0]');
    }

    /**
     * Enregistrement d'un nouveau programme
     */
    public function store() {
        $this->set_programme_validation_rules();

        if ($this->form_validation->run() === FALSE) {
            $data = array(
                'controller'       => 'maintenance_programmes',
                'action'           => 'create',
                'programme'        => $this->input->post(),
                'section_selector' => $this->sections_model->section_selector_with_null(),
                'error'            => validation_errors(),
            );
            $this->load->view('maintenance_programmes/form', $data);
            return;
        }

        $row = $this->programme_row_from_post();
        $id = $this->maintenance_programme_model->create($row);

        $this->session->set_flashdata('success', $this->lang->line('maintenance_programme_created'));
        redirect('maintenance_programmes/view/' . $id);
    }

    /**
     * Detail d'un programme : metadonnees, document lie, sections/taches dans l'ordre
     * @param int $id
     */
    public function view($id = '') {
        if (empty($id)) {
            redirect('maintenance_programmes');
        }

        $programme = $this->maintenance_programme_model->get($id);
        if (!$programme) {
            show_404();
        }

        $document = null;
        if (!empty($programme['document_id'])) {
            $document = $this->archived_documents_model->get_by_id('id', $programme['document_id']);
        }

        $sections = $this->maintenance_programme_section_model->get_by_programme($id);
        foreach ($sections as &$section) {
            $section['taches'] = $this->maintenance_tache_model->get_by_programme_section($section['id']);
        }
        unset($section);

        $data = array(
            'controller' => 'maintenance_programmes',
            'programme'  => $programme,
            'document'   => $document,
            'sections'   => $sections,
        );

        $this->load->view('maintenance_programmes/view', $data);
    }

    /**
     * Formulaire d'edition des metadonnees
     * @param int $id
     */
    public function edit($id = '') {
        if (empty($id)) {
            redirect('maintenance_programmes');
        }

        $programme = $this->maintenance_programme_model->get($id);
        if (!$programme) {
            show_404();
        }

        $data = array(
            'controller'       => 'maintenance_programmes',
            'action'           => 'edit',
            'programme'        => $programme,
            'section_selector' => $this->sections_model->section_selector_with_null(),
            'error'            => '',
        );

        $this->load->view('maintenance_programmes/form', $data);
    }

    /**
     * Mise a jour des metadonnees d'un programme existant
     * @param int $id
     */
    public function update($id = '') {
        if (empty($id)) {
            redirect('maintenance_programmes');
        }

        $programme = $this->maintenance_programme_model->get($id);
        if (!$programme) {
            show_404();
        }

        $this->set_programme_validation_rules();

        if ($this->form_validation->run() === FALSE) {
            $data = array(
                'controller'       => 'maintenance_programmes',
                'action'           => 'edit',
                'programme'        => array_merge($programme, $this->input->post()),
                'section_selector' => $this->sections_model->section_selector_with_null(),
                'error'            => validation_errors(),
            );
            $this->load->view('maintenance_programmes/form', $data);
            return;
        }

        $row = $this->programme_row_from_post();
        $this->maintenance_programme_model->update('id', array_merge($row, array('id' => $id)));

        $this->session->set_flashdata('success', $this->lang->line('maintenance_programme_updated'));
        redirect('maintenance_programmes/view/' . $id);
    }

    private function programme_row_from_post() {
        return array(
            'code'               => $this->input->post('code'),
            'titre'              => $this->input->post('titre'),
            'section_id'         => $this->input->post('section_id') ?: null,
            'regle_butee_date'   => $this->input->post('regle_butee_date') ? 1 : 0,
            'regle_butee_heures' => $this->input->post('regle_butee_heures') ? 1 : 0,
            'seuil_heures'       => $this->input->post('seuil_heures') !== '' ? $this->input->post('seuil_heures') : null,
            'periodicite_mois'   => $this->input->post('periodicite_mois') !== '' ? $this->input->post('periodicite_mois') : null,
        );
    }

    /**
     * Archivage (statut inactif) d'un programme
     * @param int $id
     */
    public function deactivate($id = '') {
        if (empty($id)) {
            redirect('maintenance_programmes');
        }

        $this->maintenance_programme_model->archiver($id);
        $this->session->set_flashdata('success', $this->lang->line('maintenance_programme_deactivated'));
        redirect('maintenance_programmes');
    }

    /**
     * Reactivation d'un programme archive
     * @param int $id
     */
    public function reactivate($id = '') {
        if (empty($id)) {
            redirect('maintenance_programmes');
        }

        $this->maintenance_programme_model->reactiver($id);
        $this->session->set_flashdata('success', $this->lang->line('maintenance_programme_reactivated'));
        redirect('maintenance_programmes');
    }

    /**
     * Formulaire de depot d'une (nouvelle) version du programme
     * @param int $id
     */
    public function upload_form($id = '') {
        if (empty($id)) {
            redirect('maintenance_programmes');
        }

        $programme = $this->maintenance_programme_model->get($id);
        if (!$programme) {
            show_404();
        }

        $data = array(
            'controller' => 'maintenance_programmes',
            'programme'  => $programme,
            'error'      => '',
        );

        $this->load->view('maintenance_programmes/upload', $data);
    }

    /**
     * Traitement du depot : parsing + validation avant tout stockage
     * (aucun fichier invalide n'est archive), puis synchronisation de la
     * structure (Etape 4.2) via le document archive existant.
     * @param int $id
     */
    public function upload($id = '') {
        if (empty($id)) {
            redirect('maintenance_programmes');
        }

        $programme = $this->maintenance_programme_model->get($id);
        if (!$programme) {
            show_404();
        }

        if (empty($_FILES['markdown_file']['name']) || $_FILES['markdown_file']['error'] !== UPLOAD_ERR_OK) {
            $this->session->set_flashdata('error', $this->lang->line('maintenance_programme_upload_error'));
            redirect('maintenance_programmes/upload_form/' . $id);
            return;
        }

        $extension = strtolower(pathinfo($_FILES['markdown_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, array('md', 'txt'))) {
            $this->session->set_flashdata('error', $this->lang->line('maintenance_programme_upload_extension'));
            redirect('maintenance_programmes/upload_form/' . $id);
            return;
        }

        $markdown_content = file_get_contents($_FILES['markdown_file']['tmp_name']);

        // Parse et valide AVANT tout stockage : un fichier invalide n'est jamais archive.
        try {
            $parsed = $this->maintenance_markdown_parser->parse($markdown_content);
            $validation_result = $this->maintenance_markdown_parser->validate($parsed);
            if ($validation_result !== true) {
                $this->session->set_flashdata('error', $this->lang->line('maintenance_programme_validation_error') . "\n\n" . $validation_result);
                redirect('maintenance_programmes/upload_form/' . $id);
                return;
            }
        } catch (Exception $e) {
            $this->session->set_flashdata('error', $this->lang->line('maintenance_programme_parse_error') . "\n\n" . $e->getMessage());
            redirect('maintenance_programmes/upload_form/' . $id);
            return;
        }

        // Stockage physique (memes conventions que archived_documents : club/<code>/)
        $dirname = './uploads/documents/club/maintenance_programme/';
        if (!file_exists($dirname)) {
            $old_umask = umask(0);
            @mkdir($dirname, 0777, true);
            umask($old_umask);
        }
        if (!is_writable($dirname)) {
            $this->session->set_flashdata('error', $this->lang->line('maintenance_programme_storage_error'));
            redirect('maintenance_programmes/upload_form/' . $id);
            return;
        }

        $storage_file = time() . '_' . rand(1000, 9999) . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $_FILES['markdown_file']['name']);
        $config['upload_path'] = $dirname;
        $config['allowed_types'] = 'md|txt';
        $config['max_size'] = 2000; // 2MB, largement suffisant pour un fichier texte
        $config['file_name'] = $storage_file;

        $this->load->library('upload', $config);
        if (!$this->upload->do_upload('markdown_file')) {
            $this->session->set_flashdata('error', $this->upload->display_errors());
            redirect('maintenance_programmes/upload_form/' . $id);
            return;
        }

        $upload_data = $this->upload->data();
        $file_path = $dirname . $upload_data['file_name'];

        $document_type = $this->document_types_model->get_by_code('maintenance_programme');

        $doc_data = array(
            'document_type_id'    => $document_type ? $document_type['id'] : null,
            'pilot_login'         => null,
            'section_id'          => $programme['section_id'],
            'machine_immat'       => null,
            'file_path'           => $file_path,
            'original_filename'   => $_FILES['markdown_file']['name'],
            'description'         => $this->input->post('description'),
            'uploaded_by'         => $this->dx_auth->get_username(),
            'file_size'           => $upload_data['file_size'] * 1024,
            'mime_type'           => 'text/plain',
            'validation_status'   => 'approved',
            'validated_by'        => $this->dx_auth->get_username(),
            'validated_at'        => date('Y-m-d H:i:s'),
            'previous_version_id' => !empty($programme['document_id']) ? $programme['document_id'] : null,
        );

        $document_id = $this->archived_documents_model->create_document($doc_data);

        try {
            $this->maintenance_programme_model->synchroniser_structure($id, $markdown_content, $document_id);
        } catch (Exception $e) {
            $this->session->set_flashdata('error', $this->lang->line('maintenance_programme_sync_error') . "\n\n" . $e->getMessage());
            redirect('maintenance_programmes/view/' . $id);
            return;
        }

        $this->session->set_flashdata('success', $this->lang->line('maintenance_programme_uploaded'));
        redirect('maintenance_programmes/view/' . $id);
    }
}

/* End of file maintenance_programmes.php */
/* Location: ./application/controllers/maintenance_programmes.php */
