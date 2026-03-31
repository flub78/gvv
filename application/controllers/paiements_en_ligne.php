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
 *   - phpunit --configuration phpunit_mysql.xml application/tests/mysql/PaiementsEnLigneWebhookTest.php
 *
 * Playwright tests:
 *   - npx playwright test tests/paiements-en-ligne-smoke.spec.js
 *   - npx playwright test tests/paiements-en-ligne-admin-config.spec.js
 *   - npx playwright test tests/paiements-en-ligne-base.spec.js
 *   - npx playwright test tests/paiements-en-ligne-webhook.spec.js
 *   - npx playwright test tests/paiements-en-ligne-uc1-bar-carte.spec.js
 */

include('./application/libraries/Gvv_Controller.php');

class Paiements_en_ligne extends MY_Controller {

    protected $controller = 'paiements_en_ligne';

    function __construct() {
        parent::__construct();

        // Endpoints publics (sans session utilisateur).
        $public_methods = array('helloasso_webhook', 'public_bar', 'public_bar_confirmation');
        if (!in_array($this->router->fetch_method(), $public_methods)) {
            if (!$this->dx_auth->is_logged_in()) {
                redirect('auth/login');
            }
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
     * Hub de paiement bar — choix entre débit de solde (UC5) et paiement CB (UC1).
     *
     * Accès : pilote authentifié, section active avec has_bar = true
     */
    function bar_hub() {
        $section = $this->_require_active_section();
        if (!$section) return;

        if (empty($section['has_bar'])) {
            $this->session->set_flashdata('error', $this->lang->line('gvv_bar_error_no_bar'));
            redirect('compta/mon_compte');
            return;
        }

        $helloasso_enabled = $this->paiements_en_ligne_model->get_config('helloasso', 'enabled', (int) $section['id']) === '1';

        $data = array(
            'section'            => $section,
            'helloasso_enabled'  => $helloasso_enabled,
        );

        $this->load->view('bs_header', $data);
        $this->load->view('bs_menu', $data);
        $this->load->view('bs_banner', $data);
        $this->load->view('paiements_en_ligne/bs_bar_hub', $data);
        $this->load->view('bs_footer');
    }

    /**
     * Provisionnement du compte pilote 411 par carte bancaire via HelloAsso (EF1).
     *
     * GET  : formulaire montant (entre montant_min et montant_max configurés)
     * POST : validation, limite journalière, création transaction pending,
     *        appel create_checkout(), redirection HelloAsso
     *
     * Retour webhook → handler étape 7 → type=provisionnement → crédit 411, débit 467
     *
     * Accès : pilote authentifié, section active, HelloAsso activé
     */
    function demande() {
        $section = $this->_require_active_section();
        if (!$section) return;

        $club_id = (int) $section['id'];

        $enabled = $this->paiements_en_ligne_model->get_config('helloasso', 'enabled', $club_id);
        if ($enabled !== '1') {
            $this->session->set_flashdata('error', $this->lang->line('gvv_bar_carte_error_disabled'));
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

        $montant_min = (float) ($this->paiements_en_ligne_model->get_config('helloasso', 'montant_min', $club_id) ?: 5);
        $montant_max = (float) ($this->paiements_en_ligne_model->get_config('helloasso', 'montant_max', $club_id) ?: 500);

        if ($this->input->post('button') === 'valider') {
            $this->_process_demande($section, $club_id, $compte_pilote, $montant_min, $montant_max);
            return;
        }

        $data = array(
            'section'     => $section,
            'montant'     => '',
            'montant_min' => $montant_min,
            'montant_max' => $montant_max,
            'error'       => $this->session->flashdata('error'),
        );
        $this->load->view('bs_header', $data);
        $this->load->view('bs_menu', $data);
        $this->load->view('bs_banner', $data);
        $this->load->view('paiements_en_ligne/bs_demande_form', $data);
        $this->load->view('bs_footer');
    }

    /**
     * Traite la soumission du formulaire demande :
     * validation montant, limite 5 transactions pending/jour,
     * création transaction, appel HelloAsso, redirection.
     */
    private function _process_demande($section, $club_id, $compte_pilote, $montant_min, $montant_max) {
        $montant = (float) str_replace(',', '.', $this->input->post('montant'));

        $errors = array();

        if ($montant < $montant_min) {
            $errors[] = sprintf($this->lang->line('gvv_provision_error_montant_min'),
                number_format($montant_min, 2, ',', ' '));
        }
        if ($montant > $montant_max) {
            $errors[] = sprintf($this->lang->line('gvv_provision_error_montant_max'),
                number_format($montant_max, 2, ',', ' '));
        }

        if (empty($errors)) {
            $user_id   = (int) $this->dx_auth->get_user_id();
            $nb_pending = $this->paiements_en_ligne_model->count_pending_today($user_id, $club_id);
            if ($nb_pending >= 5) {
                $errors[] = $this->lang->line('gvv_provision_error_limit_day');
            }
        }

        if (!empty($errors)) {
            $data = array(
                'section'     => $section,
                'montant'     => $montant,
                'montant_min' => $montant_min,
                'montant_max' => $montant_max,
                'error'       => implode('<br>', $errors),
            );
            $this->load->view('bs_header', $data);
            $this->load->view('bs_menu', $data);
            $this->load->view('bs_banner', $data);
            $this->load->view('paiements_en_ligne/bs_demande_form', $data);
            $this->load->view('bs_footer');
            return;
        }

        $user_id = (int) $this->dx_auth->get_user_id();
        $mlogin  = $this->dx_auth->get_username();
        $txid    = 'prov-' . $club_id . '-' . $user_id . '-' . time() . '-' . substr(uniqid(), -6);
        $description = sprintf($this->lang->line('gvv_provision_checkout_description'),
            htmlspecialchars($section['nom']));

        $tx_id = $this->paiements_en_ligne_model->create_transaction(array(
            'user_id'        => $user_id,
            'montant'        => $montant,
            'plateforme'     => 'helloasso',
            'club'           => $club_id,
            'transaction_id' => $txid,
            'metadata'       => json_encode(array(
                'type'               => 'provisionnement',
                'description'        => $description,
                'gvv_transaction_id' => $txid,
            )),
            'created_by' => $mlogin,
        ));

        if (!$tx_id) {
            $this->session->set_flashdata('error', $this->lang->line('gvv_bar_error_creation'));
            redirect('paiements_en_ligne/demande');
            return;
        }

        $member = $this->db
            ->select('mprenom, memail')
            ->from('membres')
            ->where('mlogin', $mlogin)
            ->get()->row_array();

        $checkout = $this->helloasso->create_checkout($club_id, array(
            'amount'           => $montant,
            'item_name'        => $description,
            'payer_first_name' => isset($member['mprenom']) ? $member['mprenom'] : '',
            'payer_email'      => isset($member['memail'])  ? $member['memail']  : '',
            'return_url'       => site_url('paiements_en_ligne/confirmation/' . $txid),
            'back_url'         => site_url('paiements_en_ligne/annulation'),
            'error_url'        => site_url('paiements_en_ligne/erreur'),
            'metadata'         => array(
                'type'               => 'provisionnement',
                'description'        => $description,
                'gvv_transaction_id' => $txid,
            ),
        ));

        if (!$checkout['success']) {
            $this->paiements_en_ligne_model->update_transaction_status($txid, 'failed', array());
            $this->session->set_flashdata('error', $this->lang->line('gvv_bar_carte_error_checkout'));
            redirect('paiements_en_ligne/demande');
            return;
        }

        redirect($checkout['redirect_url']);
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
    // UC1 — Paiement bar par carte (pilote authentifié)
    // =========================================================================

    /**
     * Paiement des consommations de bar par carte bancaire via HelloAsso (UC1).
     *
     * GET  : affiche le formulaire (montant + descriptif)
     * POST : valide, crée une transaction pending, redirige vers HelloAsso
     *
     * Accès : pilote authentifié, section active avec has_bar = true, HelloAsso activé
     */
    public function bar_carte() {
        $section = $this->_require_active_section();
        if (!$section) return;

        if (empty($section['has_bar'])) {
            $this->session->set_flashdata('error', $this->lang->line('gvv_bar_error_no_bar'));
            redirect('compta/mon_compte');
            return;
        }

        if (empty($section['bar_account_id'])) {
            $this->session->set_flashdata('error', $this->lang->line('gvv_bar_error_no_account'));
            redirect('compta/mon_compte');
            return;
        }

        $club_id = (int) $section['id'];
        $enabled = $this->paiements_en_ligne_model->get_config('helloasso', 'enabled', $club_id);
        if (!$enabled) {
            $this->session->set_flashdata('error', $this->lang->line('gvv_bar_carte_error_disabled'));
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

        if ($this->input->post('button') === 'valider') {
            $this->_process_bar_carte($section, $club_id, $compte_pilote);
            return;
        }

        $data = array(
            'section'     => $section,
            'montant'     => '',
            'description' => '',
            'error'       => $this->session->flashdata('error'),
        );
        $this->load->view('bs_header', $data);
        $this->load->view('bs_menu', $data);
        $this->load->view('bs_banner', $data);
        $this->load->view('paiements_en_ligne/bs_bar_carte_form', $data);
        $this->load->view('bs_footer');
    }

    /**
     * Traite la soumission du formulaire bar_carte : validation, création transaction, redirection HelloAsso.
     */
    private function _process_bar_carte($section, $club_id, $compte_pilote) {
        $montant     = (float) str_replace(',', '.', $this->input->post('montant'));
        $description = trim($this->input->post('description'));

        $errors = array();
        if ($montant < 0.50) {
            $errors[] = $this->lang->line('gvv_bar_error_montant_min');
        }
        if (empty($description)) {
            $errors[] = $this->lang->line('gvv_bar_error_description');
        }

        if (!empty($errors)) {
            $data = array(
                'section'     => $section,
                'montant'     => $montant,
                'description' => $description,
                'error'       => implode('<br>', $errors),
            );
            $this->load->view('bs_header', $data);
            $this->load->view('bs_menu', $data);
            $this->load->view('bs_banner', $data);
            $this->load->view('paiements_en_ligne/bs_bar_carte_form', $data);
            $this->load->view('bs_footer');
            return;
        }

        $user_id = (int) $this->dx_auth->get_user_id();
        $mlogin  = $this->dx_auth->get_username();
        $txid    = 'bar-' . $club_id . '-' . $user_id . '-' . time() . '-' . substr(uniqid(), -6);

        $tx_id = $this->paiements_en_ligne_model->create_transaction(array(
            'user_id'        => $user_id,
            'montant'        => $montant,
            'plateforme'     => 'helloasso',
            'club'           => $club_id,
            'transaction_id' => $txid,
            'metadata'       => json_encode(array(
                'type'               => 'bar',
                'description'        => $description,
                'gvv_transaction_id' => $txid,
            )),
            'created_by' => $mlogin,
        ));

        if (!$tx_id) {
            $this->session->set_flashdata('error', $this->lang->line('gvv_bar_error_creation'));
            redirect('paiements_en_ligne/bar_carte');
            return;
        }

        // Pré-remplissage HelloAsso avec les infos du membre
        $member = $this->db
            ->select('m.mprenom, m.memail')
            ->from('membres m')
            ->where('m.mlogin', $mlogin)
            ->get()->row_array();

        $checkout = $this->helloasso->create_checkout($club_id, array(
            'amount'           => $montant,
            'item_name'        => $description,
            'payer_first_name' => isset($member['mprenom']) ? $member['mprenom'] : '',
            'payer_email'      => isset($member['memail'])  ? $member['memail']  : '',
            'return_url'       => site_url('paiements_en_ligne/confirmation/' . $txid),
            'back_url'         => site_url('paiements_en_ligne/annulation'),
            'error_url'        => site_url('paiements_en_ligne/erreur'),
            'metadata'         => array(
                'type'               => 'bar',
                'description'        => $description,
                'gvv_transaction_id' => $txid,
            ),
        ));

        if (!$checkout['success']) {
            $this->paiements_en_ligne_model->update_transaction_status($txid, 'failed');
            $this->session->set_flashdata('error', $this->lang->line('gvv_bar_carte_error_checkout'));
            redirect('paiements_en_ligne/bar_carte');
            return;
        }

        redirect($checkout['redirect_url']);
    }

    // =========================================================================
    // UC2 — Bar externe via QR Code — personne sans compte GVV
    // =========================================================================

    /**
     * Formulaire public de règlement des consommations de bar par QR Code.
     * Accessible sans connexion. Le paramètre club identifie la section.
     *
     * Accès : public (pas de session requise)
     */
    public function public_bar($club = null) {
        $club_id = (int) ($club ?: $this->input->get('club'));

        $data = array(
            'club_id'     => $club_id,
            'section'     => null,
            'nom'         => '',
            'prenom'      => '',
            'email'       => '',
            'description' => '',
            'montant'     => '',
            'error'       => '',
        );

        if (!$club_id) {
            $data['error'] = $this->lang->line('gvv_public_bar_error_club');
            $this->_render_public_bar($data);
            return;
        }

        $section = $this->sections_model->get_by_id('id', $club_id);
        if (!$section || empty($section['has_bar'])) {
            $data['error'] = $this->lang->line('gvv_public_bar_error_no_bar');
            $this->_render_public_bar($data);
            return;
        }

        $enabled = $this->paiements_en_ligne_model->get_config('helloasso', 'enabled', $club_id);
        if (!$enabled) {
            $data['error'] = $this->lang->line('gvv_public_bar_error_disabled');
            $this->_render_public_bar($data);
            return;
        }

        $data['section'] = $section;

        if ($this->input->post('button') === 'valider') {
            $this->_process_public_bar($data, $club_id, $section);
            return;
        }

        $this->_render_public_bar($data);
    }

    private function _render_public_bar(array $data) {
        $this->load->view('bs_header', $data);
        $this->load->view('paiements_en_ligne/bs_public_bar', $data);
        $this->load->view('bs_footer');
    }

    private function _process_public_bar(array $data, $club_id, array $section) {
        $nom         = trim($this->input->post('nom'));
        $prenom      = trim($this->input->post('prenom'));
        $email       = trim($this->input->post('email'));
        $description = trim($this->input->post('description'));
        $montant     = (float) str_replace(',', '.', $this->input->post('montant'));

        $errors = array();
        if (empty($nom)) {
            $errors[] = $this->lang->line('gvv_public_bar_error_nom');
        }
        if (empty($prenom)) {
            $errors[] = $this->lang->line('gvv_public_bar_error_prenom');
        }
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = $this->lang->line('gvv_public_bar_error_email');
        }
        if (empty($description)) {
            $errors[] = $this->lang->line('gvv_bar_error_description');
        }
        if ($montant < 2.00) {
            $errors[] = $this->lang->line('gvv_public_bar_error_montant_min');
        }

        if (!empty($errors)) {
            $data['nom']         = $nom;
            $data['prenom']      = $prenom;
            $data['email']       = $email;
            $data['description'] = $description;
            $data['montant']     = $montant ?: '';
            $data['error']       = implode('<br>', $errors);
            $this->_render_public_bar($data);
            return;
        }

        $payer_name = $prenom . ' ' . $nom;
        $txid = 'barext-' . $club_id . '-0-' . time() . '-' . substr(uniqid(), -6);

        $tx_id = $this->paiements_en_ligne_model->create_transaction(array(
            'user_id'        => 0,
            'montant'        => $montant,
            'plateforme'     => 'helloasso',
            'club'           => $club_id,
            'transaction_id' => $txid,
            'metadata'       => json_encode(array(
                'type'               => 'bar_externe',
                'payer_name'         => $payer_name,
                'payer_email'        => $email,
                'description'        => $description,
                'gvv_transaction_id' => $txid,
            )),
            'created_by' => 'public_bar',
        ));

        if (!$tx_id) {
            $data['nom']         = $nom;
            $data['prenom']      = $prenom;
            $data['email']       = $email;
            $data['description'] = $description;
            $data['montant']     = $montant;
            $data['error']       = $this->lang->line('gvv_bar_error_creation');
            $this->_render_public_bar($data);
            return;
        }

        $checkout = $this->helloasso->create_checkout($club_id, array(
            'amount'           => $montant,
            'item_name'        => $description,
            'payer_first_name' => $prenom,
            'payer_last_name'  => $nom,
            'payer_email'      => $email,
            'return_url'       => site_url('paiements_en_ligne/public_bar_confirmation?club=' . $club_id),
            'back_url'         => site_url('paiements_en_ligne/public_bar?club=' . $club_id),
            'error_url'        => site_url('paiements_en_ligne/public_bar?club=' . $club_id),
            'metadata'         => array(
                'type'               => 'bar_externe',
                'payer_name'         => $payer_name,
                'payer_email'        => $email,
                'description'        => $description,
                'gvv_transaction_id' => $txid,
            ),
        ));

        if (!$checkout['success']) {
            $this->paiements_en_ligne_model->update_transaction_status($txid, 'failed');
            $data['nom']         = $nom;
            $data['prenom']      = $prenom;
            $data['email']       = $email;
            $data['description'] = $description;
            $data['montant']     = $montant;
            $data['error']       = $this->lang->line('gvv_public_bar_error_checkout');
            $this->_render_public_bar($data);
            return;
        }

        redirect($checkout['redirect_url']);
    }

    /**
     * Page de confirmation après retour de HelloAsso — accès public.
     */
    public function public_bar_confirmation() {
        $club_id = (int) $this->input->get('club');
        $section = $club_id ? $this->sections_model->get_by_id('id', $club_id) : null;
        $data = array('section' => $section);
        $this->load->view('bs_header', $data);
        $this->load->view('paiements_en_ligne/bs_public_bar_confirmation', $data);
        $this->load->view('bs_footer');
    }

    // =========================================================================
    // UC6 — Cotisation trésorier par carte HelloAsso
    // =========================================================================

    /**
     * Page intermédiaire QR code + lien direct après création d'un checkout
     * cotisation trésorier (UC6).
     *
     * Accès : tresorier, bureau, admin
     */
    public function cotisation_qr($transaction_id = '') {
        if (!has_role('tresorier') && !has_role('bureau') && !$this->dx_auth->is_admin()) {
            $this->dx_auth->deny_access();
            return;
        }

        $tx = $this->paiements_en_ligne_model->get_by_transaction_id($transaction_id);
        if (!$tx) {
            $this->session->set_flashdata('error', $this->lang->line('gvv_bar_error_creation'));
            redirect('compta/saisie_cotisation');
            return;
        }

        $meta         = json_decode($tx['metadata'], true) ?: array();
        $checkout_url = isset($meta['checkout_url']) ? $meta['checkout_url'] : '';

        $data = array(
            'transaction'    => $tx,
            'meta'           => $meta,
            'checkout_url'   => $checkout_url,
            'transaction_id' => $transaction_id,
        );

        $this->load->view('bs_header', $data);
        $this->load->view('bs_menu', $data);
        $this->load->view('bs_banner', $data);
        $this->load->view('paiements_en_ligne/bs_cotisation_qr', $data);
        $this->load->view('bs_footer');
    }

    /**
     * Génère l'image QR code PNG pour l'URL checkout d'une transaction.
     * Endpoint public (pas de session requise : le QR est affiché sur l'écran du trésorier).
     *
     * @param string $transaction_id
     */
    public function cotisation_qr_image($transaction_id = '') {
        $tx = $this->paiements_en_ligne_model->get_by_transaction_id($transaction_id);
        if (!$tx) {
            $this->output->set_status_header(404);
            return;
        }

        $meta         = json_decode($tx['metadata'], true) ?: array();
        $checkout_url = isset($meta['checkout_url']) ? $meta['checkout_url'] : '';

        if (empty($checkout_url)) {
            $this->output->set_status_header(404);
            return;
        }

        include_once(APPPATH . '/third_party/phpqrcode/qrlib.php');
        header('Content-Type: image/png');
        QRcode::png($checkout_url, false, QR_ECLEVEL_M, 5, 2);
        exit;
    }

    // =========================================================================
    // UC7 — Approvisionnement compte pilote par CB via trésorier
    // =========================================================================

    /**
     * Page intermédiaire QR code + lien direct après création d'un checkout
     * approvisionnement compte pilote par le trésorier (UC7).
     *
     * Accès : tresorier, bureau, admin
     */
    public function credit_qr($transaction_id = '') {
        if (!has_role('tresorier') && !has_role('bureau') && !$this->dx_auth->is_admin()) {
            $this->dx_auth->deny_access();
            return;
        }

        $tx = $this->paiements_en_ligne_model->get_by_transaction_id($transaction_id);
        if (!$tx) {
            $this->session->set_flashdata('error', $this->lang->line('gvv_bar_error_creation'));
            redirect('compta/provisionnement_tresorier');
            return;
        }

        $meta         = json_decode($tx['metadata'], true) ?: array();
        $checkout_url = isset($meta['checkout_url']) ? $meta['checkout_url'] : '';

        $data = array(
            'transaction'    => $tx,
            'meta'           => $meta,
            'checkout_url'   => $checkout_url,
            'transaction_id' => $transaction_id,
        );

        $this->load->view('bs_header', $data);
        $this->load->view('bs_menu', $data);
        $this->load->view('bs_banner', $data);
        $this->load->view('paiements_en_ligne/bs_credit_qr', $data);
        $this->load->view('bs_footer');
    }

    /**
     * Génère l'image QR code PNG pour l'URL checkout d'une transaction credit_tresorier.
     * Endpoint public (pas de session requise).
     *
     * @param string $transaction_id
     */
    public function credit_qr_image($transaction_id = '') {
        $tx = $this->paiements_en_ligne_model->get_by_transaction_id($transaction_id);
        if (!$tx) {
            $this->output->set_status_header(404);
            return;
        }

        $meta         = json_decode($tx['metadata'], true) ?: array();
        $checkout_url = isset($meta['checkout_url']) ? $meta['checkout_url'] : '';

        if (empty($checkout_url)) {
            $this->output->set_status_header(404);
            return;
        }

        include_once(APPPATH . '/third_party/phpqrcode/qrlib.php');
        header('Content-Type: image/png');
        QRcode::png($checkout_url, false, QR_ECLEVEL_M, 5, 2);
        exit;
    }

    // =========================================================================
    // EF4 — Liste des paiements pour le trésorier
    // =========================================================================

    /**
     * Liste des paiements en ligne — vue trésorier (EF4).
     *
     * Filtres : période, statut, plateforme, section
     * Statistiques : count, montant total, commissions totales
     *
     * Accès : tresorier, bureau, admin
     */
    public function liste() {
        if (!has_role('tresorier') && !has_role('bureau') && !$this->dx_auth->is_admin()) {
            $this->dx_auth->deny_access();
            return;
        }

        // Filtres
        $date_from  = $this->input->get('date_from')  ?: date('Y-m-01');  // 1er du mois
        $date_to    = $this->input->get('date_to')    ?: date('Y-m-d');
        $statut     = $this->input->get('statut')     ?: '';
        $plateforme = $this->input->get('plateforme') ?: '';
        $club_filter= (int) $this->input->get('club');

        $filters = array('date_from' => $date_from, 'date_to' => $date_to);
        if ($statut)      $filters['statut']     = $statut;
        if ($plateforme)  $filters['plateforme'] = $plateforme;
        if ($club_filter) $filters['club']       = $club_filter;

        $transactions = $this->paiements_en_ligne_model->get_transactions_with_user($filters);

        // Statistiques
        $stats = array('count' => 0, 'total' => 0.0, 'commissions' => 0.0);
        foreach ($transactions as $tx) {
            if ($tx['statut'] === 'completed') {
                $stats['count']++;
                $stats['total']       += (float) $tx['montant'];
                $stats['commissions'] += (float) $tx['commission'];
            }
        }

        $sections = $this->sections_model->section_list();

        $data = array(
            'transactions' => $transactions,
            'stats'        => $stats,
            'sections'     => $sections,
            'filters'      => array(
                'date_from'  => $date_from,
                'date_to'    => $date_to,
                'statut'     => $statut,
                'plateforme' => $plateforme,
                'club'       => $club_filter,
            ),
        );

        $this->load->view('bs_header', $data);
        $this->load->view('bs_menu', $data);
        $this->load->view('bs_banner', $data);
        $this->load->view('paiements_en_ligne/bs_liste', $data);
        $this->load->view('bs_footer');
    }

    /**
     * Export CSV de la liste des paiements (EF4).
     * Mêmes filtres que liste().
     *
     * Accès : tresorier, bureau, admin
     */
    public function liste_csv() {
        if (!has_role('tresorier') && !has_role('bureau') && !$this->dx_auth->is_admin()) {
            $this->dx_auth->deny_access();
            return;
        }

        $date_from  = $this->input->get('date_from')  ?: date('Y-m-01');
        $date_to    = $this->input->get('date_to')    ?: date('Y-m-d');
        $statut     = $this->input->get('statut')     ?: '';
        $plateforme = $this->input->get('plateforme') ?: '';
        $club_filter= (int) $this->input->get('club');

        $filters = array('date_from' => $date_from, 'date_to' => $date_to);
        if ($statut)      $filters['statut']     = $statut;
        if ($plateforme)  $filters['plateforme'] = $plateforme;
        if ($club_filter) $filters['club']       = $club_filter;

        $transactions = $this->paiements_en_ligne_model->get_transactions_with_user($filters);

        $filename = 'paiements_en_ligne_' . $date_from . '_' . $date_to . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        // BOM UTF-8 pour Excel
        fputs($out, "\xEF\xBB\xBF");
        fputcsv($out, array(
            'Date', 'Pilote', 'Montant (€)', 'Commission (€)',
            'Plateforme', 'Référence', 'Statut', 'Section',
        ), ';');

        foreach ($transactions as $tx) {
            $prenom = isset($tx['mprenom']) ? $tx['mprenom'] : '';
            $nom    = isset($tx['mnom'])    ? $tx['mnom']    : '';
            fputcsv($out, array(
                $tx['date_demande'],
                trim($prenom . ' ' . $nom) ?: $tx['username'],
                number_format((float) $tx['montant'],    2, ',', ''),
                number_format((float) $tx['commission'], 2, ',', ''),
                $tx['plateforme'],
                $tx['transaction_id'],
                $tx['statut'],
                $tx['club'],
            ), ';');
        }

        fclose($out);
        exit;
    }

    // =========================================================================
    // EF2 — Webhook HelloAsso (endpoint public)
    // =========================================================================

    /**
     * Webhook HelloAsso — POST serveur-à-serveur, sans session.
     *
     * Algorithme :
     *  1. Vérifier que la méthode HTTP est POST
     *  2. Lire le payload brut et le header X-Ha-Signature
     *  3. Décoder JSON — ignorer silencieusement tout eventType != 'Order'
     *  4. Extraire gvv_transaction_id pour déterminer le club_id (la signature
     *     est propre à chaque section)
     *  5. Vérifier HMAC-SHA256 — HTTP 401 si invalide
     *  6. Déléguer au modèle : idempotence, payment state, écritures, mise à jour
     *  7. Logger le résultat, envoyer un email de confirmation si completed
     *  8. Retourner HTTP 200
     *
     * Retourne toujours HTTP 200 (sauf 401 sig invalide) pour éviter les retries HA
     * sur des erreurs de configuration permanentes.
     */
    public function helloasso_webhook()
    {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->output->set_status_header(405);
            $this->output->set_content_type('text/plain');
            $this->output->set_output('Method Not Allowed');
            return;
        }

        $raw_body  = (string) file_get_contents('php://input');
        $signature = (string) ($this->input->server('HTTP_X_HA_SIGNATURE') ?: '');

        // ── Décoder JSON ────────────────────────────────────────────────────
        $event = json_decode($raw_body, true);
        if (!is_array($event)) {
            $this->helloasso->log('ERROR', 'none', 'webhook', 'Payload JSON invalide');
            $this->_webhook_respond_ok();
            return;
        }

        // Ignorer silencieusement les événements autres que 'Order'
        $event_type = isset($event['eventType']) ? $event['eventType'] : '';
        if ($event_type !== 'Order') {
            $this->_webhook_respond_ok();
            return;
        }

        $order_data = isset($event['data']) ? $event['data'] : array();

        // ── Déterminer club_id pour la vérification de signature ────────────
        $gvv_txid   = $this->_extract_gvv_txid($order_data);
        $transaction = $gvv_txid
            ? $this->paiements_en_ligne_model->get_by_transaction_id($gvv_txid)
            : null;
        $club_id = ($transaction && isset($transaction['club'])) ? (int) $transaction['club'] : 0;

        if (!$club_id) {
            $this->helloasso->log('ERROR', $gvv_txid ?: 'none', 'webhook',
                'club_id indéterminable — transaction introuvable pour txid=' . ($gvv_txid ?: 'none'));
            $this->_webhook_respond_ok();
            return;
        }

        // ── Vérifier la signature HMAC-SHA256 ───────────────────────────────
        if (!$this->helloasso->verify_webhook_signature($raw_body, $signature, $club_id)) {
            $this->helloasso->log('ERROR', $gvv_txid, 'webhook',
                'STATUS=REJECTED signature HMAC invalide club=' . $club_id);
            $this->output->set_status_header(401);
            $this->output->set_content_type('text/plain');
            $this->output->set_output('Unauthorized');
            return;
        }

        // ── Traiter l'événement ─────────────────────────────────────────────
        $result = $this->paiements_en_ligne_model->process_order_event($order_data, $club_id);

        switch ($result['status']) {

            case 'already_completed':
                $this->helloasso->log('INFO', $gvv_txid, 'webhook',
                    'STATUS=DUPLICATE transaction déjà traitée — idempotence OK');
                break;

            case 'completed':
                $montant = isset($transaction['montant']) ? $transaction['montant'] : '?';
                $this->helloasso->log('INFO', $gvv_txid, 'webhook',
                    'STATUS=SUCCESS montant=' . $montant . ' ecriture_id=' . $result['ecriture_id']);
                $this->_send_payment_confirmation_email(
                    isset($result['transaction']) ? $result['transaction'] : $transaction,
                    $result
                );
                // Email de confirmation pour les payeurs externes (bar_externe)
                if (isset($result['type']) && $result['type'] === 'bar_externe') {
                    $this->_send_external_bar_email(
                        isset($result['metadata']) ? $result['metadata'] : array(),
                        isset($result['transaction']) ? $result['transaction'] : $transaction
                    );
                }
                // Création de la licence pour les cotisations trésorier
                if (isset($result['type']) && $result['type'] === 'cotisation_tresorier') {
                    $this->_create_licence_from_cotisation_meta(
                        isset($result['metadata']) ? $result['metadata'] : array(),
                        isset($result['transaction']) ? $result['transaction'] : $transaction
                    );
                }
                break;

            case 'failed':
                $this->helloasso->log('INFO', $gvv_txid, 'webhook',
                    'STATUS=FAILED ' . ($result['error'] ?? ''));
                break;

            default: // 'error'
                $this->helloasso->log('ERROR', $gvv_txid, 'webhook',
                    'STATUS=ERROR ' . ($result['error'] ?? 'erreur inconnue'));
                break;
        }

        $this->_webhook_respond_ok();
    }

    /** Extrait gvv_transaction_id depuis order_data.metadata. */
    private function _extract_gvv_txid(array $order_data)
    {
        $raw_meta = isset($order_data['metadata']) ? $order_data['metadata'] : null;
        if (is_string($raw_meta)) {
            $raw_meta = json_decode($raw_meta, true);
        }
        if (!is_array($raw_meta)) {
            return null;
        }
        return isset($raw_meta['gvv_transaction_id'])
            ? (string) $raw_meta['gvv_transaction_id']
            : null;
    }

    /**
     * Crée la licence de cotisation suite à un paiement cotisation_tresorier confirmé.
     * Echec silencieux loggé — le webhook a déjà répondu 200 et les écritures sont créées.
     *
     * @param array $meta        Metadata de la transaction (pilote_login, annee_cotisation)
     * @param array $transaction Transaction GVV
     */
    private function _create_licence_from_cotisation_meta(array $meta, array $transaction)
    {
        $pilote_login     = isset($meta['pilote_login'])     ? (string) $meta['pilote_login']     : '';
        $annee_cotisation = isset($meta['annee_cotisation']) ? (int)    $meta['annee_cotisation'] : 0;

        if (empty($pilote_login) || $annee_cotisation < 2000) {
            $this->helloasso->log('ERROR', $transaction['transaction_id'], 'webhook',
                'cotisation_tresorier : pilote_login ou annee_cotisation manquant dans metadata');
            return;
        }

        try {
            $this->load->model('licences_model');
            $date = isset($transaction['date_paiement'])
                ? substr($transaction['date_paiement'], 0, 10)
                : date('Y-m-d');

            $licence_id = $this->licences_model->create_cotisation(
                $pilote_login,
                0,
                $annee_cotisation,
                $date,
                'Cotisation enregistrée via paiement HelloAsso'
            );

            if ($licence_id) {
                $this->helloasso->log('INFO', $transaction['transaction_id'], 'webhook',
                    'Licence cotisation créée id=' . $licence_id . ' pilote=' . $pilote_login);
            } else {
                $this->helloasso->log('ERROR', $transaction['transaction_id'], 'webhook',
                    'Échec création licence cotisation pilote=' . $pilote_login);
            }
        } catch (Exception $e) {
            $this->helloasso->log('ERROR', $transaction['transaction_id'], 'webhook',
                'Exception création licence : ' . $e->getMessage());
        }
    }

    /** Réponse HTTP 200 standard pour le webhook. */
    private function _webhook_respond_ok()
    {
        $this->output->set_status_header(200);
        $this->output->set_content_type('text/plain');
        $this->output->set_output('OK');
    }

    /**
     * Envoie un email de confirmation de paiement au pilote.
     * Echec silencieux si email non configuré ou adresse absente.
     */
    private function _send_payment_confirmation_email(array $transaction, array $result)
    {
        if (empty($transaction['user_id'])) {
            return;
        }

        try {
            $user = $this->db
                ->select('u.username, m.memail, m.mprenom, m.mnom')
                ->from('users u')
                ->join('membres m', 'm.mlogin = u.username', 'left')
                ->where('u.id', (int) $transaction['user_id'])
                ->get()->row_array();

            if (empty($user) || empty($user['memail'])) {
                return;
            }

            $montant = number_format((float) $transaction['montant'], 2, ',', ' ');
            $prenom  = isset($user['mprenom']) ? $user['mprenom'] : '';
            $nom     = isset($user['mnom'])    ? $user['mnom']    : '';
            $nom_club = $this->config->item('nom_club') ?: 'GVV';

            $subject = $this->lang->line('gvv_payment_email_subject') ?: 'Paiement en ligne confirmé';
            $message = sprintf(
                "%s %s,\n\nVotre paiement de %s\xe2\x82\xac a \xc3\xa9t\xc3\xa9 enregistr\xc3\xa9 avec succ\xc3\xa8s.\n\nCordialement,\n%s",
                $prenom, $nom, $montant, $nom_club
            );

            $this->load->library('email');
            $this->email->initialize(array(
                'wordwrap' => true,
                'mailtype' => 'text',
                'charset'  => 'utf-8',
            ));
            $this->email->from('noreply@gvv.net', $nom_club);
            $this->email->to($user['memail']);
            $this->email->subject($subject);
            $this->email->message($message);
            @$this->email->send();   // Échec silencieux

        } catch (Exception $e) {
            $this->helloasso->log('ERROR', 'none', 'webhook',
                'Erreur email confirmation : ' . $e->getMessage());
        }
    }

    /**
     * Envoie un email de confirmation à un payeur externe (bar_externe).
     * L'adresse email est lue depuis metadata.payer_email.
     */
    private function _send_external_bar_email(array $meta, array $transaction)
    {
        $email = isset($meta['payer_email']) ? trim($meta['payer_email']) : '';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            $nom_club = $this->config->item('nom_club') ?: 'GVV';
            $prenom   = '';
            if (!empty($meta['payer_name'])) {
                $parts  = explode(' ', $meta['payer_name'], 2);
                $prenom = $parts[0];
            }
            $montant = number_format((float) $transaction['montant'], 2, ',', ' ');

            $subject = $this->lang->line('gvv_payment_email_subject') ?: 'Paiement en ligne confirmé';
            $message = sprintf(
                "%s,\n\nVotre r\xc3\xa8glement de %s\xe2\x82\xac a \xc3\xa9t\xc3\xa9 enregistr\xc3\xa9 avec succ\xc3\xa8s.\n\nCordialement,\n%s",
                $prenom ?: $email, $montant, $nom_club
            );

            $this->load->library('email');
            $this->email->initialize(array(
                'wordwrap' => true,
                'mailtype' => 'text',
                'charset'  => 'utf-8',
            ));
            $this->email->from('noreply@gvv.net', $nom_club);
            $this->email->to($email);
            $this->email->subject($subject);
            $this->email->message($message);
            @$this->email->send();

        } catch (Exception $e) {
            $this->helloasso->log('ERROR', 'none', 'webhook',
                'Erreur email bar_externe : ' . $e->getMessage());
        }
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

        // Sélecteur de comptes 467 pour le compte de passage
        $compte_passage_selector = $this->comptes_model->selector_with_null(
            array('codec' => '467'),
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
            'bar_account_selector'      => $bar_account_selector,
            'compte_passage_selector'   => $compte_passage_selector,
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
            'compte_passage' => (string)(int)($this->input->post('compte_passage') ?: 0),
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
            'compte_passage' => '0',
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
