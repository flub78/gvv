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
 * @filesource paiements_en_ligne.php
 * @package controllers
 *
 * Paiements en ligne : bar (débit de solde), provisionnement, etc.
 *
 * PHPUnit tests:
 *   - phpunit --configuration phpunit_mysql.xml application/tests/mysql/PaiementsEnLigneBarTest.php
 *   - phpunit --configuration phpunit_mysql.xml application/tests/mysql/PaiementsEnLigneModelTest.php
 *
 * Playwright tests:
 *   - npx playwright test tests/paiements-en-ligne-smoke.spec.js
 *   - npx playwright test tests/paiements-en-ligne-admin-config.spec.js
 *   - npx playwright test tests/paiements-en-ligne-base.spec.js
 */

include('./application/libraries/Gvv_Controller.php');

class Paiements_en_ligne extends MY_Controller {

    protected $controller = 'paiements_en_ligne';

    function __construct() {
        parent::__construct();

        if (!$this->dx_auth->is_logged_in()) {
            redirect('auth/login');
        }

        $this->load->helper('validation');
        $this->load->model('comptes_model');
        $this->load->model('sections_model');
        $this->load->model('ecritures_model');
        $this->load->model('paiements_en_ligne_model');
        $this->load->library('Helloasso');
        $this->lang->load('paiements_en_ligne');
        $this->lang->load('compta');
    }

    /**
     * Vérifie qu'une section spécifique (pas "Toutes") est active en session.
     * Retourne la section active ou null si la vérification échoue.
     * En cas d'échec, redirige avec un message d'erreur.
     */
    private function _require_active_section() {
        $section = $this->sections_model->section();
        if (!$section || !isset($section['id']) || $section['id'] == 0) {
            $this->session->set_flashdata('error', $this->lang->line('gvv_bar_error_section'));
            redirect('compta/mon_compte');
            return null;
        }
        return $section;
    }

    /**
     * Paiement des consommations de bar par débit du solde pilote (UC5).
     *
     * GET  : affiche le formulaire de saisie (montant + descriptif)
     * POST : valide et crée l'écriture comptable
     *
     * Accès : pilote authentifié, section active avec has_bar = true
     */
    function bar_debit_solde() {
        $section = $this->_require_active_section();
        if (!$section) return;

        // Vérifier que la section dispose d'un bar
        if (empty($section['has_bar'])) {
            $this->session->set_flashdata('error', $this->lang->line('gvv_bar_error_no_bar'));
            redirect('compta/mon_compte');
            return;
        }

        // Vérifier que le compte bar est configuré pour cette section
        if (empty($section['bar_account_id'])) {
            $this->session->set_flashdata('error', $this->lang->line('gvv_bar_error_no_account'));
            redirect('compta/mon_compte');
            return;
        }

        $mlogin = $this->dx_auth->get_username();
        $compte_pilote = $this->comptes_model->compte_pilote($mlogin, $section);

        if (!$compte_pilote) {
            $this->session->set_flashdata('error', $this->lang->line('gvv_bar_error_no_pilot_account'));
            redirect('compta/mon_compte');
            return;
        }

        $solde = $this->ecritures_model->solde_compte($compte_pilote['id']);

        if ($this->input->post('button') === 'valider') {
            $this->_process_bar_payment($section, $compte_pilote, $solde);
            return;
        }

        // GET : afficher le formulaire
        $data = array(
            'section'       => $section,
            'solde'         => $solde,
            'compte_pilote' => $compte_pilote,
            'montant'       => '',
            'description'   => '',
            'error'         => $this->session->flashdata('error'),
        );

        $this->load->view('bs_header', $data);
        $this->load->view('bs_menu', $data);
        $this->load->view('bs_banner', $data);
        $this->load->view('paiements_en_ligne/bs_bar_form', $data);
        $this->load->view('bs_footer');
    }

    /**
     * Traite le paiement bar après soumission du formulaire.
     */
    private function _process_bar_payment($section, $compte_pilote, $solde) {
        $montant     = (float) str_replace(',', '.', $this->input->post('montant'));
        $description = trim($this->input->post('description'));

        // Validation
        $errors = array();

        if ($montant < 0.50) {
            $errors[] = $this->lang->line('gvv_bar_error_montant_min');
        }

        if (empty($description)) {
            $errors[] = $this->lang->line('gvv_bar_error_description');
        }

        if ($montant > $solde) {
            $errors[] = sprintf(
                $this->lang->line('gvv_bar_error_solde'),
                number_format($solde, 2, ',', ' ')
            );
        }

        if (!empty($errors)) {
            $data = array(
                'section'       => $section,
                'solde'         => $solde,
                'compte_pilote' => $compte_pilote,
                'montant'       => $montant,
                'description'   => $description,
                'error'         => implode('<br>', $errors),
            );
            $this->load->view('bs_header', $data);
            $this->load->view('bs_menu', $data);
            $this->load->view('bs_banner', $data);
            $this->load->view('paiements_en_ligne/bs_bar_form', $data);
            $this->load->view('bs_footer');
            return;
        }

        // Création de l'écriture comptable (transaction atomique)
        $ecriture_data = array(
            'annee_exercise' => date('Y'),
            'date_creation'  => date('Y-m-d H:i:s'),
            'date_op'        => date('Y-m-d'),
            'compte1'        => $compte_pilote['id'],          // débit compte pilote 411
            'compte2'        => $section['bar_account_id'],    // crédit compte recette bar 7xx
            'montant'        => $montant,
            'description'    => $description,
            'num_cheque'     => 'Débit solde pilote',
            'saisie_par'     => $this->dx_auth->get_username(),
            'club'           => $section['id'],
        );

        $result = $this->ecritures_model->create_ecriture($ecriture_data);

        if ($result === false) {
            $this->session->set_flashdata('error', $this->lang->line('gvv_bar_error_creation'));
            redirect('paiements_en_ligne/bar_debit_solde');
            return;
        }

        $this->session->set_flashdata(
            'popup',
            sprintf($this->lang->line('gvv_bar_success'), number_format($montant, 2, ',', ' '))
        );
        redirect('compta/mon_compte');
    }

    // =========================================================================
    // EF6 — Contrôleur et modèle de base
    // =========================================================================

    /**
     * Page d'accueil : liste des paiements en ligne du pilote connecté.
     *
     * Accès : pilote authentifié
     */
    public function index() {
        $section = $this->_require_active_section();
        if (!$section) return;

        $user_id = (int) $this->dx_auth->get_user_id();
        $transactions = $this->paiements_en_ligne_model->get_transactions(array(
            'user_id' => $user_id,
            'club'    => (int) $section['id'],
        ));

        $data = array('transactions' => $transactions);
        $this->load->view('bs_header', $data);
        $this->load->view('bs_menu', $data);
        $this->load->view('bs_banner', $data);
        $this->load->view('paiements_en_ligne/bs_index', $data);
        $this->load->view('bs_footer');
    }

    /**
     * Page de confirmation après paiement HelloAsso réussi.
     * Appelée depuis l'URL de retour configurée dans le checkout HelloAsso.
     *
     * @param string $transaction_id  ID externe HelloAsso (dans l'URL)
     */
    public function confirmation($transaction_id = '') {
        $transaction = false;
        if ($transaction_id !== '') {
            $transaction = $this->paiements_en_ligne_model->get_by_transaction_id($transaction_id);
        }

        $data = array('transaction' => $transaction);
        $this->load->view('bs_header', $data);
        $this->load->view('bs_menu', $data);
        $this->load->view('bs_banner', $data);
        $this->load->view('paiements_en_ligne/bs_confirmation', $data);
        $this->load->view('bs_footer');
    }

    /**
     * Page d'annulation après refus ou abandon du paiement HelloAsso.
     */
    public function annulation() {
        $data = array();
        $this->load->view('bs_header', $data);
        $this->load->view('bs_menu', $data);
        $this->load->view('bs_banner', $data);
        $this->load->view('paiements_en_ligne/bs_annulation', $data);
        $this->load->view('bs_footer');
    }

    /**
     * Page d'erreur après échec du paiement HelloAsso.
     */
    public function erreur() {
        $data = array();
        $this->load->view('bs_header', $data);
        $this->load->view('bs_menu', $data);
        $this->load->view('bs_banner', $data);
        $this->load->view('paiements_en_ligne/bs_erreur', $data);
        $this->load->view('bs_footer');
    }

    /**
     * Endpoint de détection sandbox (utilisé par les specs Playwright).
     *
     * GET/POST : HTTP 200 si client_id et client_secret sont définis et non vides
     *            pour la section active, sinon HTTP 503.
     *
     * Pas de vue — réponse JSON minimale.
     */
    public function sandbox_available() {
        $section = $this->sections_model->section();
        $club_id = isset($section['id']) ? (int) $section['id'] : 0;

        $available = false;
        if ($club_id > 0) {
            $client_id     = $this->paiements_en_ligne_model->get_config('helloasso', 'client_id', $club_id);
            $client_secret = $this->paiements_en_ligne_model->get_config('helloasso', 'client_secret', $club_id);
            $available = !empty($client_id) && !empty($client_secret);
        }

        $this->output->set_content_type('application/json');
        if ($available) {
            $this->output->set_status_header(200);
            $this->output->set_output(json_encode(array('available' => true)));
        } else {
            $this->output->set_status_header(503);
            $this->output->set_output(json_encode(array('available' => false)));
        }
    }

    // =========================================================================
    // EF5 — Configuration admin HelloAsso
    // =========================================================================

    /**
     * Page de configuration HelloAsso par section (EF5).
     *
     * GET  : affiche le formulaire pour la section sélectionnée
     * POST : enregistre la configuration
     *
     * Accès : admin uniquement
     */
    public function admin_config() {
        if (!$this->dx_auth->is_admin()) {
            $this->dx_auth->deny_access();
            return;
        }

        // Section cible : paramètre GET ou section active
        $club_id = (int) ($this->input->get('section') ?: 0);
        if ($club_id === 0) {
            $active = $this->sections_model->section();
            $club_id = isset($active['id']) ? (int) $active['id'] : 0;
        }

        if ($this->input->post('button') === 'save') {
            $this->_save_admin_config($club_id);
            return;
        }

        // Sélecteur de comptes 7xx pour le bar
        $bar_account_selector = $this->comptes_model->selector_with_null(
            array('codec >=' => '700', 'codec <' => '800'),
            TRUE
        );

        // Chargement de la config courante
        $cfg = $this->_load_config($club_id);

        // Info de la section courante (has_bar, bar_account_id)
        $section_row = null;
        if ($club_id) {
            $query = $this->db->where('id', $club_id)->get('sections');
            $section_row = $query->row_array();
        }

        $data = array(
            'sections_selector'    => $this->sections_model->selector(),
            'bar_account_selector' => $bar_account_selector,
            'club_id'              => $club_id,
            'cfg'                  => $cfg,
            'section_row'          => $section_row,
            'webhook_url'          => site_url('paiements_en_ligne/webhook'),
            'success'              => $this->session->flashdata('success'),
            'error'                => $this->session->flashdata('error'),
        );

        $this->load->view('bs_header', $data);
        $this->load->view('bs_menu', $data);
        $this->load->view('bs_banner', $data);
        $this->load->view('paiements_en_ligne/bs_admin_config', $data);
        $this->load->view('bs_footer');
    }

    /**
     * Test de la connexion HelloAsso (AJAX).
     *
     * POST → JSON { success: bool, message: string }
     *
     * Accès : admin uniquement
     */
    public function test_connexion() {
        if (!$this->dx_auth->is_admin()) {
            $this->output->set_status_header(403);
            $this->output->set_content_type('application/json');
            $this->output->set_output(json_encode(array('success' => false, 'message' => 'Accès refusé')));
            return;
        }

        $club_id = (int) $this->input->post('club_id');
        $this->output->set_content_type('application/json');

        $token = $this->helloasso->get_oauth_token($club_id);
        if ($token !== FALSE) {
            $this->output->set_output(json_encode(array(
                'success' => true,
                'message' => $this->lang->line('gvv_admin_config_test_ok'),
            )));
        } else {
            $this->output->set_output(json_encode(array(
                'success' => false,
                'message' => $this->lang->line('gvv_admin_config_test_fail'),
            )));
        }
    }

    /**
     * Enregistre la configuration HelloAsso pour un club.
     */
    private function _save_admin_config($club_id) {
        if ($club_id === 0) {
            $this->session->set_flashdata('error', $this->lang->line('gvv_admin_config_error_no_section'));
            redirect('paiements_en_ligne/admin_config');
            return;
        }

        $username = $this->dx_auth->get_username();

        // Paramètres HelloAsso dans paiements_en_ligne_config
        $keys = array(
            'client_id'      => trim($this->input->post('client_id') ?: ''),
            'account_slug'   => trim($this->input->post('account_slug') ?: ''),
            'environment'    => $this->input->post('environment') === 'production' ? 'production' : 'sandbox',
            'webhook_secret' => trim($this->input->post('webhook_secret') ?: ''),
            'compte_passage' => trim($this->input->post('compte_passage') ?: '467'),
            'montant_min'    => (float) ($this->input->post('montant_min') ?: 10),
            'montant_max'    => (float) ($this->input->post('montant_max') ?: 500),
            'enabled'        => $this->input->post('enabled') ? '1' : '0',
        );

        // client_secret : ne remplacer que si une nouvelle valeur est saisie
        $new_secret = trim($this->input->post('client_secret') ?: '');
        if ($new_secret !== '') {
            $keys['client_secret'] = $new_secret;
        }

        foreach ($keys as $key => $value) {
            $ok = $this->paiements_en_ligne_model->upsert_config(
                'helloasso',
                $key,
                (string) $value,
                $club_id,
                $username
            );
            if (!$ok) {
                $this->session->set_flashdata('error', $this->lang->line('gvv_admin_config_test_fail'));
                redirect('paiements_en_ligne/admin_config?section=' . $club_id);
                return;
            }
        }

        // Paramètres bar dans la table sections
        $has_bar        = $this->input->post('has_bar') ? 1 : 0;
        $bar_account_id = (int) ($this->input->post('bar_account_id') ?: 0) ?: null;

        $this->db->where('id', $club_id)->update('sections', array(
            'has_bar'        => $has_bar,
            'bar_account_id' => $bar_account_id,
        ));

        // Log d'audit
        $this->helloasso->log('INFO', 'none', 'admin_config',
            'Config updated for club=' . $club_id . ' by=' . $username
            . ' enabled=' . $keys['enabled']
            . ' environment=' . $keys['environment']
            . ' has_bar=' . $has_bar
        );

        $this->session->set_flashdata('success', $this->lang->line('gvv_admin_config_saved'));
        redirect('paiements_en_ligne/admin_config?section=' . $club_id);
    }

    /**
     * Charge toute la configuration HelloAsso d'un club depuis paiements_en_ligne_config.
     *
     * @param  int   $club_id
     * @return array Tableau associatif param_key => param_value avec valeurs par défaut
     */
    private function _load_config($club_id) {
        $defaults = array(
            'client_id'      => '',
            'client_secret'  => '',
            'account_slug'   => '',
            'environment'    => 'sandbox',
            'webhook_secret' => '',
            'compte_passage' => '467',
            'montant_min'    => '10',
            'montant_max'    => '500',
            'enabled'        => '0',
        );

        if (!$club_id) return $defaults;

        $config = $this->paiements_en_ligne_model->get_all_config('helloasso', $club_id);
        foreach ($config as $key => $value) {
            $defaults[$key] = $value;
        }
        return $defaults;
    }

    /**
     * Insert ou update une clé de configuration (upsert).
     */
    private function _upsert_config($club_id, $key, $value, $username) {
        return $this->paiements_en_ligne_model->upsert_config(
            'helloasso',
            $key,
            $value,
            (int) $club_id,
            $username
        );
    }
}
