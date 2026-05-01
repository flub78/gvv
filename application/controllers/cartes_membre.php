<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Contrôleur — Impression des cartes de membre
 *
 * Actions :
 *   index()          → redirige vers lot()
 *   lot()            → écran de sélection du lot (GET) / redirection PDF (POST)
 *   lot_pdf()        → génère et envoie le PDF recto/verso en lot
 *   config()         → gestion des fonds recto/verso par année (upload)
 */
class Cartes_membre extends CI_Controller {

    protected $controller = 'cartes_membre';

    public function __construct() {
        parent::__construct();

        if (!$this->dx_auth->is_logged_in()) {
            redirect('auth/login');
        }
        $this->dx_auth->check_uri_permissions();

        $this->load->model('cartes_membre_model');
        $this->lang->load('gvv');
    }

    /** Redirige vers l'écran de sélection du lot. */
    public function index() {
        redirect(controller_url('cartes_membre/lot'));
    }

    /**
     * Écran de sélection du lot.
     * GET  : affiche le formulaire (année + liste membres).
     * POST : valide et redirige vers lot_pdf (members en session).
     */
    public function lot() {
        if (!$this->dx_auth->is_admin()) {
            show_error($this->lang->line('gvv_error_not_authorized'), 403);
        }

        $year = (int)($this->input->get('year') ?: $this->input->post('year') ?: date('Y'));

        if ($this->input->post('generate')) {
            $selected = $this->input->post('membres') ?: array();
            if (empty($selected)) {
                $all = $this->cartes_membre_model->get_membres_actifs_annee($year);
                $selected = array_column($all, 'mlogin');
            }
            $this->session->set_userdata('cartes_lot_membres', $selected);
            $this->session->set_userdata('cartes_lot_year', $year);
            redirect(controller_url('cartes_membre/lot_pdf'));
            return;
        }

        $membres = $this->cartes_membre_model->get_membres_actifs_annee($year);

        $year_selector = array();
        for ($y = (int)date('Y') + 1; $y >= 2010; $y--) {
            $year_selector[$y] = $y;
        }

        $data = array(
            'controller' => $this->controller,
            'year'          => $year,
            'year_selector' => $year_selector,
            'membres'       => $membres,
        );

        load_last_view('cartes_membre/bs_lot', $data);
    }

    /**
     * Génère le PDF recto/verso en lot et l'envoie au navigateur.
     * Les logins sélectionnés sont lus depuis la session (positionnés par lot()).
     */
    public function lot_pdf() {
        if (!$this->dx_auth->is_admin()) {
            show_error($this->lang->line('gvv_error_not_authorized'), 403);
        }

        $selected = $this->session->userdata('cartes_lot_membres') ?: array();
        $year     = (int)($this->session->userdata('cartes_lot_year') ?: date('Y'));

        if (empty($selected)) {
            redirect(controller_url('cartes_membre/lot'));
            return;
        }

        $president  = $this->cartes_membre_model->get_president();
        $fond_recto = $this->cartes_membre_model->get_fond_path($year, 'recto');
        $fond_verso = $this->cartes_membre_model->get_fond_path($year, 'verso');
        $nom_club   = $this->config->item('nom_club') ?: 'GVV';

        $membres = array();
        foreach ($selected as $login) {
            $m = $this->cartes_membre_model->get_membre($login);
            if (!$m) continue;
            $m['annee']      = $year;
            $m['photo_path'] = $this->cartes_membre_model->get_photo_path($m['photo'] ?? null);
            $membres[] = $m;
        }

        if (empty($membres)) {
            redirect(controller_url('cartes_membre/lot'));
            return;
        }

        require_once(APPPATH . 'libraries/Cartes_membre_pdf.php');

        $pdf = new Cartes_membre_pdf($nom_club);
        $pdf->generate_lot($membres, $president, $fond_recto, $fond_verso);
        $pdf->Output('cartes_membre_' . $year . '.pdf', 'I');
    }

    /**
     * Configuration des fonds recto/verso par année.
     * GET  : affiche le formulaire.
     * POST : traite l'upload et enregistre en configuration.
     */
    public function config() {
        if (!$this->dx_auth->is_admin()) {
            show_error($this->lang->line('gvv_error_not_authorized'), 403);
        }

        $year = (int)($this->input->get('year') ?: $this->input->post('year') ?: date('Y'));
        $message = '';
        $error   = '';

        if ($this->input->post('upload')) {
            $face = $this->input->post('face');
            if (!in_array($face, array('recto', 'verso'))) {
                $error = $this->lang->line('gvv_cartes_membre_invalid_face');
            } else {
                $result = $this->_upload_fond($year, $face);
                if ($result['success']) {
                    $message = $this->lang->line('gvv_cartes_membre_upload_ok');
                } else {
                    $error = $result['error'];
                }
            }
        }

        $year_selector = array();
        for ($y = (int)date('Y') + 1; $y >= 2010; $y--) {
            $year_selector[$y] = $y;
        }

        $data = array(
            'controller'    => $this->controller,
            'year'          => $year,
            'year_selector' => $year_selector,
            'fond_recto'    => $this->cartes_membre_model->get_fond_path($year, 'recto'),
            'fond_verso'    => $this->cartes_membre_model->get_fond_path($year, 'verso'),
            'message'       => $message,
            'error'         => $error,
        );

        load_last_view('cartes_membre/bs_config', $data);
    }

    // -------------------------------------------------------------------------
    // Méthodes privées
    // -------------------------------------------------------------------------

    /**
     * Traite l'upload d'un fond de carte et l'enregistre dans la configuration.
     *
     * @param int    $year
     * @param string $face  'recto' ou 'verso'
     * @return array  ['success' => bool, 'error' => string]
     */
    private function _upload_fond($year, $face) {
        $upload_dir = FCPATH . 'uploads/configuration/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0775, true);
        }

        $cle      = 'carte_' . $face . '_' . $year;
        $config   = array(
            'upload_path'   => $upload_dir,
            'allowed_types' => 'jpg|jpeg|png',
            'max_size'      => 4096,
            'file_name'     => $cle,
            'overwrite'     => true,
        );

        $this->load->library('upload', $config);
        if (!$this->upload->do_upload('fond_' . $face)) {
            return array('success' => false, 'error' => strip_tags($this->upload->display_errors()));
        }

        $upload_data = $this->upload->data();
        $relative    = 'uploads/configuration/' . $upload_data['file_name'];
        $this->cartes_membre_model->save_fond_path($year, $face, $relative);

        return array('success' => true);
    }
}
