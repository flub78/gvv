<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Formation Seances Theoriques Controller
 *
 * Gère la saisie et la consultation des séances de formation théoriques
 * (cours sol, briefings, etc.) avec plusieurs participants.
 *
 * Routes :
 *   index   – liste des séances théoriques
 *   create  – formulaire de création
 *   store   – traitement de la création
 *   edit/id – formulaire d'édition
 *   update/id – traitement de la modification
 *   delete/id – suppression
 *   detail/id – vue détaillée
 *   ajax_search_membres – recherche AJAX de membres (JSON)
 *
 * @package controllers
 */
class Formation_seances_theoriques extends CI_Controller {

    public function __construct() {
        parent::__construct();

        $this->load->model('formation_seance_model');
        $this->load->model('formation_seance_participants_model');
        $this->load->model('formation_type_seance_model');
        $this->load->model('formation_programme_model');
        $this->load->model('membres_model');
        $this->load->library('form_validation');
        $this->lang->load('formation');
        $this->lang->load('gvv');

        if (!$this->config->item('gestion_formations')) {
            show_404();
        }

        if (!$this->dx_auth->is_logged_in()) {
            redirect('auth/login');
        }
    }

    // -----------------------------------------------------------------------
    // Liste
    // -----------------------------------------------------------------------

    public function index() {
        $filters = array(
            'instructeur_id' => $this->input->get('instructeur_id'),
            'programme_id'   => $this->input->get('programme_id'),
            'date_debut'     => $this->input->get('date_debut'),
            'date_fin'       => $this->input->get('date_fin'),
            'nature'         => 'theorique',  // always filter on theoretical
        );

        foreach ($filters as $k => $v) {
            if ($v === '' || $v === null) {
                unset($filters[$k]);
            }
        }
        $filters['nature'] = 'theorique';

        $seances = $this->formation_seance_model->select_page($filters, 200);

        // Attach participant count (already in nb_participants from select_page)

        $data = array(
            'controller'   => 'formation_seances_theoriques',
            'seances'      => $seances,
            'filters'      => $filters,
            'instructeurs' => $this->membres_model->get_selector_instructeurs(),
            'programmes'   => $this->formation_programme_model->get_selector(),
        );

        $this->load->view('formation_seances_theoriques/index', $data);
    }

    // -----------------------------------------------------------------------
    // Création
    // -----------------------------------------------------------------------

    public function create() {
        $data = $this->_prepare_form_data(array(), 'create');
        $this->load->view('formation_seances_theoriques/form', $data);
    }

    public function store() {
        $this->form_validation->set_rules('date_seance',     'Date',         'required');
        $this->form_validation->set_rules('type_seance_id',  'Type de séance','required|integer');
        $this->form_validation->set_rules('instructeur_id',  'Instructeur',   'required');

        if ($this->form_validation->run() === FALSE) {
            $data = $this->_prepare_form_data(
                $this->_collect_post(),
                'create',
                validation_errors()
            );
            $this->load->view('formation_seances_theoriques/form', $data);
            return;
        }

        // Vérification : le type doit être de nature théorique
        $type = $this->formation_type_seance_model->get_by_id('id', $this->input->post('type_seance_id'));
        if (empty($type) || $type['nature'] !== 'theorique') {
            $data = $this->_prepare_form_data(
                $this->_collect_post(),
                'create',
                $this->lang->line('formation_seance_type_invalide')
            );
            $this->load->view('formation_seances_theoriques/form', $data);
            return;
        }

        // Vérification : au moins un participant
        $participants = $this->input->post('participants') ?: array();
        if (empty($participants)) {
            $data = $this->_prepare_form_data(
                $this->_collect_post(),
                'create',
                $this->lang->line('formation_seance_participants_requis')
            );
            $this->load->view('formation_seances_theoriques/form', $data);
            return;
        }

        $seance_data = $this->_collect_seance_data();
        $seance_id = $this->formation_seance_model->create_theorique($seance_data, $participants);

        if ($seance_id) {
            $this->session->set_flashdata('success', $this->lang->line('formation_seance_theorique_create_success'));
            redirect('formation_seances_theoriques/detail/' . $seance_id);
        } else {
            $data = $this->_prepare_form_data(
                $this->_collect_post(),
                'create',
                $this->lang->line('formation_seance_theorique_create_error')
            );
            $this->load->view('formation_seances_theoriques/form', $data);
        }
    }

    // -----------------------------------------------------------------------
    // Édition
    // -----------------------------------------------------------------------

    public function edit($id) {
        $seance = $this->formation_seance_model->get((int)$id);
        if (empty($seance)) {
            show_404();
        }

        $participants = $this->formation_seance_model->get_participants((int)$id);
        $seance['participants'] = array_column($participants, 'pilote_id');

        $data = $this->_prepare_form_data($seance, 'edit');
        $data['participants_data'] = $participants;
        $this->load->view('formation_seances_theoriques/form', $data);
    }

    public function update($id) {
        $this->form_validation->set_rules('date_seance',    'Date',          'required');
        $this->form_validation->set_rules('type_seance_id', 'Type de séance','required|integer');
        $this->form_validation->set_rules('instructeur_id', 'Instructeur',   'required');

        if ($this->form_validation->run() === FALSE) {
            $seance = $this->input->post();
            $seance['id'] = (int)$id;
            $participants_data = array();
            foreach (($this->input->post('participants') ?: array()) as $pid) {
                $participants_data[] = array('pilote_id' => $pid);
            }
            $data = $this->_prepare_form_data($seance, 'edit', validation_errors());
            $data['participants_data'] = $participants_data;
            $this->load->view('formation_seances_theoriques/form', $data);
            return;
        }

        $participants = $this->input->post('participants') ?: array();
        if (empty($participants)) {
            $seance = $this->input->post();
            $seance['id'] = (int)$id;
            $data = $this->_prepare_form_data($seance, 'edit', $this->lang->line('formation_seance_participants_requis'));
            $data['participants_data'] = array();
            $this->load->view('formation_seances_theoriques/form', $data);
            return;
        }

        $seance_data = $this->_collect_seance_data();
        $ok = $this->formation_seance_model->update_theorique((int)$id, $seance_data, $participants);

        if ($ok) {
            $this->session->set_flashdata('success', $this->lang->line('formation_seance_theorique_update_success'));
            redirect('formation_seances_theoriques/detail/' . $id);
        } else {
            $seance = array_merge($seance_data, array('id' => (int)$id));
            $data = $this->_prepare_form_data($seance, 'edit', $this->lang->line('formation_seance_theorique_update_error'));
            $data['participants_data'] = array();
            $this->load->view('formation_seances_theoriques/form', $data);
        }
    }

    // -----------------------------------------------------------------------
    // Suppression
    // -----------------------------------------------------------------------

    public function delete($id) {
        $seance = $this->formation_seance_model->get((int)$id);
        if (empty($seance)) {
            show_404();
        }

        // Les participants sont supprimés par CASCADE
        $this->formation_seance_model->delete(array('id' => (int)$id));
        $this->session->set_flashdata('success', $this->lang->line('formation_seance_theorique_delete_success'));
        redirect('formation_seances_theoriques');
    }

    // -----------------------------------------------------------------------
    // Détail
    // -----------------------------------------------------------------------

    public function detail($id) {
        $seance = $this->formation_seance_model->get_full((int)$id);
        if (empty($seance)) {
            show_404();
        }

        $participants = $this->formation_seance_model->get_participants((int)$id);
        $type = array();
        if (!empty($seance['type_seance_id'])) {
            $type = $this->formation_type_seance_model->get_by_id('id', $seance['type_seance_id']);
        }

        $data = array(
            'controller'   => 'formation_seances_theoriques',
            'seance'       => $seance,
            'participants' => $participants,
            'type'         => $type,
        );

        $this->load->view('formation_seances_theoriques/detail', $data);
    }

    // -----------------------------------------------------------------------
    // AJAX
    // -----------------------------------------------------------------------

    /**
     * Recherche de membres pour le sélecteur multi-participants.
     * GET ?q=<terme>
     * Retourne JSON [{id, label}, ...]
     */
    public function ajax_search_membres() {
        $q = trim($this->input->get('q'));
        $result = array();

        if (strlen($q) >= 2) {
            $section_id = $this->membres_model->section_id();
            $like = $this->db->escape_like_str($q);

            $rows = $this->db
                ->select('membres.mlogin, membres.mnom, membres.mprenom')
                ->from('membres')
                ->join('comptes', 'comptes.pilote = membres.mlogin', 'inner')
                ->where('comptes.codec', '411')
                ->where('comptes.club', $section_id)
                ->where('comptes.actif', 1)
                ->where('comptes.masked', 0)
                ->where('membres.actif', 1)
                ->group_start()
                    ->like('membres.mnom',    $q, 'both')
                    ->or_like('membres.mprenom', $q, 'both')
                ->group_end()
                ->order_by('membres.mnom, membres.mprenom')
                ->limit(20)
                ->get()->result_array();

            foreach ($rows as $row) {
                $result[] = array(
                    'id'    => $row['mlogin'],
                    'label' => $row['mnom'] . ' ' . $row['mprenom'],
                );
            }
        }

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    // -----------------------------------------------------------------------
    // Helpers privés
    // -----------------------------------------------------------------------

    private function _prepare_form_data($seance, $action, $error = '') {
        return array(
            'controller'       => 'formation_seances_theoriques',
            'action'           => $action,
            'seance'           => $seance,
            'error'            => $error,
            'types_seance'     => $this->formation_type_seance_model->get_selector('theorique'),
            'programmes'       => $this->formation_programme_model->get_selector(),
            'instructeurs'     => $this->membres_model->get_selector_instructeurs(),
            'membres'          => $this->membres_model->get_selector(),
            'participants_data' => array(),
        );
    }

    private function _collect_post() {
        return array(
            'date_seance'    => $this->input->post('date_seance'),
            'type_seance_id' => $this->input->post('type_seance_id'),
            'instructeur_id' => $this->input->post('instructeur_id'),
            'programme_id'   => $this->input->post('programme_id') ?: null,
            'lieu'           => $this->input->post('lieu') ?: null,
            'duree'          => $this->input->post('duree') ?: null,
            'commentaires'   => $this->input->post('commentaires') ?: null,
            'participants'   => $this->input->post('participants') ?: array(),
        );
    }

    private function _collect_seance_data() {
        $programme_id = $this->input->post('programme_id');
        $duree        = $this->input->post('duree');

        return array(
            'date_seance'    => $this->input->post('date_seance'),
            'type_seance_id' => (int)$this->input->post('type_seance_id'),
            'instructeur_id' => $this->input->post('instructeur_id'),
            'programme_id'   => !empty($programme_id)  ? (int)$programme_id  : null,
            'lieu'           => $this->input->post('lieu') ?: null,
            'duree'          => !empty($duree)           ? $duree              : null,
            'commentaires'   => $this->input->post('commentaires') ?: null,
            // Champs vol laissés NULL
            'pilote_id'        => null,
            'machine_id'       => null,
            'nb_atterrissages' => null,
        );
    }
}
