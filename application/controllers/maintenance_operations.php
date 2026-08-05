<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Maintenance Operations Controller
 *
 * Enregistrement d'une operation de maintenance sur un dossier, sur un
 * seul ecran pour les deux modes (PRD EF4) : saisie directe (case a
 * cocher par tache, regroupees par section) et/ou depot d'un compte
 * rendu papier (reutilise le systeme documentaire existant). A la
 * validation, le potentiel du dossier est recalcule via
 * Maintenance_potentiel::appliquer_operation().
 *
 * Correction d'une operation existante possible (edit/update), jamais de
 * suppression (PRD EF4.4 : traçabilite, pas de suppression silencieuse).
 *
 * @package controllers
 * @see doc/prds/maintenance_aeronefs_prd.md (EF4)
 * @see doc/plans/maintenance_aeronefs_plan.md (Etape 5.4)
 */
class Maintenance_operations extends MY_Controller {

    public function __construct() {
        parent::__construct();

        $this->load->model('maintenance_operation_model');
        $this->load->model('maintenance_realisation_model');
        $this->load->model('maintenance_dossier_model');
        $this->load->model('maintenance_programme_model');
        $this->load->model('maintenance_tache_model');
        $this->load->model('document_types_model');
        $this->load->model('archived_documents_model');
        $this->load->library('form_validation');
        $this->load->library('Maintenance_potentiel');
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
     * Liste des operations recentes (tout dossier), scopee par section
     * courante -- point d'entree "Operations de maintenance" du dashboard
     */
    public function index() {
        $operations = $this->maintenance_operation_model->get_all($this->session->userdata('section'));

        foreach ($operations as &$operation) {
            $operation['entite_label'] = $this->maintenance_dossier_model->entite_label($operation['entite_type'], $operation['entite_id']);
        }
        unset($operation);

        $data = array(
            'controller' => 'maintenance_operations',
            'operations' => $operations,
        );

        $this->load->view('maintenance_operations/index', $data);
    }

    /**
     * Formulaire de nouvelle operation sur un dossier
     * @param int $dossier_id
     */
    public function create($dossier_id = '') {
        if (empty($dossier_id)) {
            redirect('maintenance_dossiers');
        }

        $dossier = $this->maintenance_dossier_model->get_full($dossier_id);
        if (!$dossier) {
            show_404();
        }
        $programme = $this->maintenance_programme_model->get($dossier['programme_id']);

        $data = array(
            'controller' => 'maintenance_operations',
            'action'     => 'create',
            'dossier'    => $dossier,
            'programme'  => $programme,
            'taches'     => $this->maintenance_tache_model->get_by_programme($programme['id']),
            'operation'  => array(
                'date_operation' => date('Y-m-d'), 'horametre_releve' => '', 'nouvelle_echeance' => '', 'commentaire' => '',
            ),
            'realisations' => array(),
            'error'      => '',
        );

        $this->load->view('maintenance_operations/form', $data);
    }

    /**
     * Enregistrement d'une nouvelle operation (saisie directe et/ou compte rendu)
     * @param int $dossier_id
     */
    public function store($dossier_id = '') {
        if (empty($dossier_id)) {
            redirect('maintenance_dossiers');
        }

        $dossier = $this->maintenance_dossier_model->get_full($dossier_id);
        if (!$dossier) {
            show_404();
        }

        $this->form_validation->set_rules('date_operation', $this->lang->line('maintenance_operation_date'), 'required');
        $this->form_validation->set_rules('horametre_releve', $this->lang->line('maintenance_operation_horametre'), 'numeric');

        if ($this->form_validation->run() === FALSE) {
            $this->redisplay_form($dossier, 'create', $this->input->post(), validation_errors());
            return;
        }

        $mode_saisie = (!empty($_FILES['compte_rendu']['name'])) ? 'compte_rendu' : 'directe';

        $document_id = null;
        if ($mode_saisie === 'compte_rendu') {
            $document_id = $this->store_compte_rendu();
            if ($document_id === false) {
                $this->redisplay_form($dossier, 'create', $this->input->post(), $this->upload->display_errors());
                return;
            }
        }

        // Champs date HTML5 (type="date") : deja au format Y-m-d, pas de conversion necessaire.
        $date_operation = $this->input->post('date_operation');
        $nouvelle_echeance = $this->input->post('nouvelle_echeance') ?: null;

        $operation_id = $this->maintenance_operation_model->create(array(
            'dossier_id'        => $dossier_id,
            'date_operation'    => $date_operation,
            'mecano_id'         => $this->dx_auth->get_username(),
            'mode_saisie'       => $mode_saisie,
            'document_id'       => $document_id,
            'horametre_releve'  => $this->input->post('horametre_releve') !== '' ? $this->input->post('horametre_releve') : null,
            'nouvelle_echeance' => $nouvelle_echeance,
            'commentaire'       => $this->input->post('commentaire'),
        ));

        $this->save_realisations($operation_id);
        $this->maintenance_potentiel->appliquer_operation($operation_id);

        $this->session->set_flashdata('success', $this->lang->line('maintenance_operation_created'));
        redirect('maintenance_dossiers/view/' . $dossier_id);
    }

    /**
     * Formulaire de correction d'une operation existante
     * @param int $id
     */
    public function edit($id = '') {
        if (empty($id)) {
            redirect('maintenance_dossiers');
        }

        $operation = $this->maintenance_operation_model->get($id);
        if (!$operation) {
            show_404();
        }
        $dossier = $this->maintenance_dossier_model->get_full($operation['dossier_id']);
        $programme = $this->maintenance_programme_model->get($dossier['programme_id']);

        $realisations_par_tache = array();
        foreach ($this->maintenance_realisation_model->get_by_operation($id) as $realisation) {
            $realisations_par_tache[$realisation['tache_id']] = $realisation;
        }

        $data = array(
            'controller'   => 'maintenance_operations',
            'action'       => 'edit',
            'dossier'      => $dossier,
            'programme'    => $programme,
            'taches'       => $this->maintenance_tache_model->get_by_programme($programme['id']),
            'operation'    => $operation,
            'realisations' => $realisations_par_tache,
            'error'        => '',
        );

        $this->load->view('maintenance_operations/form', $data);
    }

    /**
     * Mise a jour d'une operation existante (correction, PRD EF4.4).
     * Les realisations sont remplacees et le potentiel recalcule.
     * @param int $id
     */
    public function update($id = '') {
        if (empty($id)) {
            redirect('maintenance_dossiers');
        }

        $operation = $this->maintenance_operation_model->get($id);
        if (!$operation) {
            show_404();
        }
        $dossier = $this->maintenance_dossier_model->get_full($operation['dossier_id']);

        $this->form_validation->set_rules('date_operation', $this->lang->line('maintenance_operation_date'), 'required');
        $this->form_validation->set_rules('horametre_releve', $this->lang->line('maintenance_operation_horametre'), 'numeric');

        if ($this->form_validation->run() === FALSE) {
            $this->redisplay_form($dossier, 'edit', array_merge($operation, $this->input->post()), validation_errors(), $id);
            return;
        }

        $document_id = $operation['document_id'];
        if (!empty($_FILES['compte_rendu']['name'])) {
            $new_document_id = $this->store_compte_rendu($document_id);
            if ($new_document_id === false) {
                $this->redisplay_form($dossier, 'edit', array_merge($operation, $this->input->post()), $this->upload->display_errors(), $id);
                return;
            }
            $document_id = $new_document_id;
        }

        // Champs date HTML5 (type="date") : deja au format Y-m-d, pas de conversion necessaire.
        $date_operation = $this->input->post('date_operation');
        $nouvelle_echeance = $this->input->post('nouvelle_echeance') ?: null;

        $this->maintenance_operation_model->update('id', array(
            'id'                => $id,
            'date_operation'    => $date_operation,
            'document_id'       => $document_id,
            'mode_saisie'       => $document_id ? 'compte_rendu' : 'directe',
            'horametre_releve'  => $this->input->post('horametre_releve') !== '' ? $this->input->post('horametre_releve') : null,
            'nouvelle_echeance' => $nouvelle_echeance,
            'commentaire'       => $this->input->post('commentaire'),
        ));

        $this->maintenance_realisation_model->delete_by_operation($id);
        $this->save_realisations($id);
        $this->maintenance_potentiel->appliquer_operation($id);

        $this->session->set_flashdata('success', $this->lang->line('maintenance_operation_updated'));
        redirect('maintenance_dossiers/view/' . $dossier['id']);
    }

    /**
     * Enregistre les realisations postees (une par tache affichee sur le
     * formulaire) : realisations[tache_id][statut|commentaire].
     */
    private function save_realisations($operation_id) {
        $posted = $this->input->post('realisations');
        if (!is_array($posted)) {
            return;
        }

        $realisations = array();
        foreach ($posted as $tache_id => $realisation) {
            $realisations[(int) $tache_id] = array(
                'statut'      => $realisation['statut'] ?? 'non_fait',
                'commentaire' => $realisation['commentaire'] ?? null,
            );
        }

        $this->maintenance_realisation_model->save_batch($operation_id, $realisations);
    }

    /**
     * Depose le compte rendu papier via le systeme documentaire existant
     * (Archived_documents_model::create_document(), meme mecanisme que
     * pour les programmes d'entretien, Etape 5.2).
     *
     * @param int|null $previous_document_id Document precedent a chainer (correction)
     * @return int|false Nouvel id de document, ou false en cas d'echec
     */
    private function store_compte_rendu($previous_document_id = null) {
        $dirname = './uploads/documents/club/maintenance_compte_rendu/';
        if (!file_exists($dirname)) {
            $old_umask = umask(0);
            @mkdir($dirname, 0777, true);
            umask($old_umask);
        }

        $storage_file = time() . '_' . rand(1000, 9999) . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $_FILES['compte_rendu']['name']);
        $config['upload_path'] = $dirname;
        $config['allowed_types'] = 'gif|jpg|jpeg|png|pdf';
        $config['max_size'] = 10000; // 10MB
        $config['file_name'] = $storage_file;

        $this->load->library('upload', $config);
        if (!$this->upload->do_upload('compte_rendu')) {
            return false;
        }

        $upload_data = $this->upload->data();
        $document_type = $this->document_types_model->get_by_code('maintenance_compte_rendu');

        return $this->archived_documents_model->create_document(array(
            'document_type_id'    => $document_type ? $document_type['id'] : null,
            'pilot_login'         => null,
            'section_id'          => null,
            'machine_immat'       => null,
            'file_path'           => $dirname . $upload_data['file_name'],
            'original_filename'   => $_FILES['compte_rendu']['name'],
            'uploaded_by'         => $this->dx_auth->get_username(),
            'file_size'           => $upload_data['file_size'] * 1024,
            'mime_type'           => $upload_data['file_type'],
            'validation_status'   => 'approved',
            'validated_by'        => $this->dx_auth->get_username(),
            'validated_at'        => date('Y-m-d H:i:s'),
            'previous_version_id' => $previous_document_id ?: null,
        ));
    }

    private function redisplay_form($dossier, $action, $operation_post, $error, $operation_id = null) {
        $programme = $this->maintenance_programme_model->get($dossier['programme_id']);
        $realisations_par_tache = array();
        if ($operation_id) {
            foreach ($this->maintenance_realisation_model->get_by_operation($operation_id) as $realisation) {
                $realisations_par_tache[$realisation['tache_id']] = $realisation;
            }
        }

        $data = array(
            'controller'   => 'maintenance_operations',
            'action'       => $action,
            'dossier'      => $dossier,
            'programme'    => $programme,
            'taches'       => $this->maintenance_tache_model->get_by_programme($programme['id']),
            'operation'    => $operation_post,
            'realisations' => $realisations_par_tache,
            'error'        => $error,
        );
        if ($operation_id) {
            $data['operation']['id'] = $operation_id;
        }

        $this->load->view('maintenance_operations/form', $data);
    }
}

/* End of file maintenance_operations.php */
/* Location: ./application/controllers/maintenance_operations.php */
