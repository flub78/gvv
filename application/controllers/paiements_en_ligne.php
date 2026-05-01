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
        $public_methods = array(
            'helloasso_webhook',
            'public_bar',
            'public_bar_confirmation',
            'public_decouverte',
            'public_decouverte_confirmation',
            'decouverte_qr_image',
            'sandbox_available',
        );
        if (!in_array($this->router->fetch_method(), $public_methods)) {
            if (!$this->dx_auth->is_logged_in()) {
                redirect('auth/login');
            }
        }

        $this->load->helper('validation');
        $this->load->helper('crypto');
        $this->load->model('comptes_model');
        $this->load->model('sections_model');
        $this->load->model('ecritures_model');
        $this->load->model('paiements_en_ligne_model');
        $this->load->model('cotisation_produits_model');
        $this->load->model('tarifs_model');
        $this->load->model('vols_decouverte_model');
        $this->load->model('configuration_model');
        $this->load->library('Helloasso');
        $this->lang->load('paiements_en_ligne');
        $this->lang->load('compta');
        $this->lang->load('vols_decouverte');
    }

    // =========================================================================
    // UC4 — Bon découverte via lien / QR public
    // =========================================================================

    /**
     * Page QR/lien direct pour un paiement bon découverte.
     *
     * Accès : tresorier, bureau, gestion_vd, pilote_vd, admin
     */
    public function decouverte_qr($transaction_id = '') {
        if (!has_role('tresorier') && !has_role('bureau')
            && !$this->dx_auth->is_admin()
            && !$this->user_has_role('gestion_vd')
            && !$this->user_has_role('pilote_vd')) {
            $this->dx_auth->deny_access();
            return;
        }

        $tx = $this->paiements_en_ligne_model->get_by_transaction_id($transaction_id);
        if (!$tx) {
            $this->session->set_flashdata('error', $this->lang->line('gvv_bar_error_creation'));
            redirect('vols_decouverte/create');
            return;
        }

        $meta       = json_decode($tx['metadata'], true) ?: array();
        $public_url = site_url('paiements_en_ligne/public_decouverte/' . $transaction_id);

        $nom_club    = $this->config->item('nom_club') ?: 'GVV';
        $sender_name = $this->configuration_model->get_param('vd.email.sender_name') ?: $nom_club;
        $beneficiaire_email = isset($meta['beneficiaire_email']) ? $meta['beneficiaire_email'] : '';
        $beneficiaire       = isset($meta['beneficiaire'])       ? $meta['beneficiaire']       : '';

        $data = array(
            'transaction'        => $tx,
            'meta'               => $meta,
            'public_url'         => $public_url,
            'transaction_id'     => $transaction_id,
            'sender_name'        => $sender_name,
            'beneficiaire_email' => $beneficiaire_email,
            'beneficiaire'       => $beneficiaire,
        );

        $this->load->view('bs_header', $data);
        $this->load->view('bs_menu', $data);
        $this->load->view('bs_banner', $data);
        $this->load->view('paiements_en_ligne/bs_decouverte_qr', $data);
        $this->load->view('bs_footer');
    }

    /**
     * Initie le paiement HelloAsso depuis le contexte GVV authentifié (UC4).
     * Crée le checkout et redirige vers HelloAsso. Retour sur decouverte_qr.
     */
    public function decouverte_pay($transaction_id = '') {
        if (!has_role('tresorier') && !has_role('bureau')
            && !$this->dx_auth->is_admin()
            && !$this->user_has_role('gestion_vd')
            && !$this->user_has_role('pilote_vd')) {
            $this->dx_auth->deny_access();
            return;
        }

        $tx = $this->paiements_en_ligne_model->get_by_transaction_id($transaction_id);
        if (!$tx) {
            $this->session->set_flashdata('error', $this->lang->line('gvv_bar_error_creation'));
            redirect('vols_decouverte/create');
            return;
        }

        if ($tx['statut'] === 'completed') {
            $this->session->set_flashdata('error', $this->lang->line('gvv_decouverte_already_paid'));
            redirect('paiements_en_ligne/decouverte_qr/' . $transaction_id);
            return;
        }

        $meta               = json_decode($tx['metadata'], true) ?: array();
        $club_id            = $tx['club'];
        $montant            = (float) $tx['montant'];
        $description        = isset($meta['description'])        ? (string) $meta['description']        : 'Bon découverte';
        $beneficiaire       = isset($meta['beneficiaire'])       ? (string) $meta['beneficiaire']       : '';
        $beneficiaire_email = isset($meta['beneficiaire_email']) ? (string) $meta['beneficiaire_email'] : '';

        $checkout = $this->helloasso->create_checkout($club_id, array(
            'amount'           => $montant,
            'item_name'        => $description,
            'payer_first_name' => $beneficiaire,
            'payer_last_name'  => '',
            'payer_email'      => $beneficiaire_email,
            'return_url'       => site_url('paiements_en_ligne/decouverte_pay_confirmation/' . $transaction_id),
            'back_url'         => site_url('paiements_en_ligne/decouverte_qr/' . $transaction_id),
            'error_url'        => site_url('paiements_en_ligne/decouverte_qr/' . $transaction_id),
            'metadata'         => array_merge($meta, array('gvv_transaction_id' => $transaction_id)),
        ));

        if (!$checkout['success']) {
            $this->session->set_flashdata('error', $this->lang->line('gvv_decouverte_error_checkout'));
            redirect('paiements_en_ligne/decouverte_qr/' . $transaction_id);
            return;
        }

        if (!empty($checkout['session_id'])) {
            $this->paiements_en_ligne_model->attach_checkout_info(
                $transaction_id,
                $checkout['session_id'],
                isset($checkout['redirect_url']) ? $checkout['redirect_url'] : null
            );
        }

        redirect($checkout['redirect_url']);
    }

    /**
     * Image QR PNG pour un checkout bon découverte.
     *
     * @param string $transaction_id
     */
    public function decouverte_qr_image($transaction_id = '') {
        $tx = $this->paiements_en_ligne_model->get_by_transaction_id($transaction_id);
        if (!$tx) {
            $this->output->set_status_header(404);
            return;
        }

        $public_url = site_url('paiements_en_ligne/public_decouverte/' . $transaction_id);

        include_once(APPPATH . '/third_party/phpqrcode/qrlib.php');
        header('Content-Type: image/png');
        QRcode::png($public_url, false, QR_ECLEVEL_M, 5, 2);
        exit;
    }

    /**
     * Page publique de paiement d'un bon découverte.
     *
     * Accessible sans connexion. Affiche les détails du bon et un bouton "Payer par CB".
     * Sur POST, crée le checkout HelloAsso à la demande et redirige.
     *
     * Accès : public
     */
    public function public_decouverte($txid = '') {
        $tx = $this->paiements_en_ligne_model->get_by_transaction_id($txid);
        if (!$tx) {
            show_404();
            return;
        }

        $meta    = json_decode($tx['metadata'], true) ?: array();
        $club_id = (int) $tx['club'];
        $section = $club_id ? $this->sections_model->get_by_id('id', $club_id) : null;

        $data = array(
            'tx'      => $tx,
            'meta'    => $meta,
            'txid'    => $txid,
            'section' => $section,
            'error'   => '',
        );

        if ($this->input->post('button') === 'payer') {
            $this->_process_public_decouverte($txid, $tx, $meta, $club_id, $section, $data);
            return;
        }

        $this->_render_public_decouverte($data);
    }

    private function _render_public_decouverte(array $data) {
        $this->load->view('bs_header', $data);
        $this->load->view('paiements_en_ligne/bs_public_decouverte', $data);
        $this->load->view('bs_footer');
    }

    private function _process_public_decouverte($txid, array $tx, array $meta, $club_id, $section, array $data) {
        if (empty($section['has_vd_par_cb'])) {
            $data['error'] = $this->lang->line('gvv_decouverte_error_cb_disabled');
            $this->_render_public_decouverte($data);
            return;
        }
        $enabled = $this->paiements_en_ligne_model->get_config('helloasso', 'enabled', $club_id);
        if ($enabled !== '1') {
            $data['error'] = $this->lang->line('gvv_bar_carte_error_disabled');
            $this->_render_public_decouverte($data);
            return;
        }

        $montant            = (float) $tx['montant'];
        $description        = isset($meta['description'])        ? (string) $meta['description']        : 'Bon découverte';
        $beneficiaire       = isset($meta['beneficiaire'])       ? (string) $meta['beneficiaire']       : '';
        $beneficiaire_email = isset($meta['beneficiaire_email']) ? (string) $meta['beneficiaire_email'] : '';

        $checkout = $this->helloasso->create_checkout($club_id, array(
            'amount'           => $montant,
            'item_name'        => $description,
            'payer_first_name' => $beneficiaire,
            'payer_last_name'  => '',
            'payer_email'      => $beneficiaire_email,
            'return_url'       => site_url('paiements_en_ligne/public_decouverte_confirmation?club=' . $club_id . '&txid=' . urlencode($txid)),
            'back_url'         => site_url('paiements_en_ligne/public_decouverte/' . $txid),
            'error_url'        => site_url('paiements_en_ligne/public_decouverte/' . $txid),
            'metadata'         => array_merge($meta, array('gvv_transaction_id' => $txid)),
        ));

        if (!$checkout['success']) {
            $data['error'] = $this->lang->line('gvv_decouverte_error_checkout');
            $this->_render_public_decouverte($data);
            return;
        }

        if (!empty($checkout['session_id'])) {
            $this->paiements_en_ligne_model->attach_checkout_info(
                $txid,
                $checkout['session_id'],
                isset($checkout['redirect_url']) ? $checkout['redirect_url'] : null
            );
        }

        redirect($checkout['redirect_url']);
    }

    /**
     * Envoie le lien de paiement HelloAsso par email (modal sur la page QR).
     *
     * POST : to, subject, body
     * Accès : tresorier, bureau, gestion_vd, pilote_vd, admin
     */
    public function send_payment_link_email($transaction_id = '') {
        if (!has_role('tresorier') && !has_role('bureau')
            && !$this->dx_auth->is_admin()
            && !$this->user_has_role('gestion_vd')
            && !$this->user_has_role('pilote_vd')) {
            $this->dx_auth->deny_access();
            return;
        }

        $to      = trim($this->input->post('to')      ?: '');
        $subject = trim($this->input->post('subject') ?: '');
        $body    = trim($this->input->post('body')    ?: '');

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->session->set_flashdata('error', $this->lang->line('gvv_decouverte_qr_email_invalid_to'));
            redirect('paiements_en_ligne/decouverte_qr/' . $transaction_id);
            return;
        }

        try {
            $nom_club     = $this->config->item('nom_club') ?: 'GVV';
            $sender_email = $this->configuration_model->get_param('vd.email.sender_email') ?: 'noreply@gvv.net';
            $sender_name  = $this->configuration_model->get_param('vd.email.sender_name')  ?: $nom_club;

            $this->load->library('email');
            $this->email->initialize(array(
                'wordwrap' => true,
                'mailtype' => 'html',
                'charset'  => 'utf-8',
            ));
            $this->email->from($sender_email, $sender_name);
            $this->email->to($to);
            $this->email->subject($subject);
            $this->email->message(nl2br($body));

            if (@$this->email->send()) {
                $this->session->set_flashdata('success', $this->lang->line('gvv_decouverte_qr_email_success'));
            } else {
                $this->session->set_flashdata('error', $this->lang->line('gvv_decouverte_qr_email_error'));
            }
        } catch (Exception $e) {
            $this->helloasso->log('ERROR', 'none', 'send_payment_link_email',
                'Erreur envoi lien paiement : ' . $e->getMessage());
            $this->session->set_flashdata('error', $this->lang->line('gvv_decouverte_qr_email_error'));
        }

        redirect('paiements_en_ligne/decouverte_qr/' . $transaction_id);
    }

    /**
     * Confirmation publique après retour HelloAsso pour un bon découverte.
     */
    public function public_decouverte_confirmation() {
        $club_id = (int) $this->input->get('club');
        $section = $club_id ? $this->sections_model->get_by_id('id', $club_id) : null;

        $txid = $this->input->get('txid');
        $beneficiaire = '';
        $montant      = '';
        $email        = '';
        if ($txid) {
            $tx = $this->paiements_en_ligne_model->get_by_transaction_id($txid);
            if ($tx) {
                $meta         = json_decode($tx['metadata'], true) ?: array();
                $beneficiaire = isset($meta['beneficiaire']) ? $meta['beneficiaire'] : '';
                $montant      = isset($tx['montant'])        ? euros((float) $tx['montant']) : '';
                $email        = isset($meta['beneficiaire_email']) ? $meta['beneficiaire_email'] : '';
            }
        }

        $club_email = (string) ($this->config->item('email_club') ?: '');
        if ($club_email === '') {
            $club_email = (string) ($this->configuration_model->get_param('vd.email.sender_email') ?: '');
        }

        $sender_signature = (string) ($this->configuration_model->get_param('vd.email.sender_signature') ?: '');
        if ($sender_signature === '') {
            $sender_signature = (string) ($this->configuration_model->get_param('vd.email.sender_name') ?: ($this->config->item('nom_club') ?: 'GVV'));
        }

        $data = array(
            'section'      => $section,
            'beneficiaire' => $beneficiaire,
            'montant'      => $montant,
            'email'        => $email,
            'club_email'   => $club_email,
            'signature'    => $sender_signature,
        );
        $this->load->view('bs_header', $data);
        $this->load->view('paiements_en_ligne/bs_public_decouverte_confirmation', $data);
        $this->load->view('bs_footer');
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

        if (empty($section['has_approvisio_par_cb'])) {
            $this->session->set_flashdata('error', $this->lang->line('gvv_provision_error_cb_disabled'));
            redirect('compta/mon_compte');
            return;
        }
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
        $montant = (float) $this->input->post('montant');

        $errors = $this->paiements_en_ligne_model->validate_demande_montant($montant, $montant_max, $montant_min);

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
            ->select('mprenom, mnom, memail')
            ->from('membres')
            ->where('mlogin', $mlogin)
            ->get()->row_array();

        $checkout = $this->helloasso->create_checkout($club_id, array(
            'amount'           => $montant,
            'item_name'        => $description,
            'payer_first_name' => isset($member['mprenom']) ? $member['mprenom'] : '',
            'payer_last_name'  => isset($member['mnom'])    ? $member['mnom']    : '',
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
            $this->paiements_en_ligne_model->update_transaction_status($txid, 'failed');
            $this->session->set_flashdata('error', $this->lang->line('gvv_bar_carte_error_checkout'));
            redirect('paiements_en_ligne/demande');
            return;
        }

        if (!empty($checkout['session_id'])) {
            $this->paiements_en_ligne_model->attach_checkout_info(
                $txid,
                $checkout['session_id'],
                isset($checkout['redirect_url']) ? $checkout['redirect_url'] : null
            );
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
        $montant     = (int) $this->input->post('montant');
        $description = trim($this->input->post('description'));

        // Validation
        $errors = array();

        if ($montant < 1) {
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
    // UC3 — Renouvellement de cotisation en ligne par le pilote (débit de solde)
    // =========================================================================

    /**
     * Renouvellement de cotisation par débit du compte pilote.
     *
     * GET  : liste des produits cotisation actifs, solde du pilote
     * POST : validation produit, vérification solde et doublon, écriture comptable, licence
     *
     * Accès : pilote authentifié, section active
     */
    public function cotisation() {
        $section = $this->_require_active_section();
        if (!$section) return;

        $club_id = (int) $section['id'];
        $mlogin  = $this->dx_auth->get_username();

        $this->load->model('comptes_model');
        $this->load->model('ecritures_model');
        $this->load->model('licences_model');

        if ($this->input->post('button') === 'payer') {
            $this->_process_cotisation($section, $club_id, $mlogin);
            return;
        }

        $produits      = $this->tarifs_model->get_cotisation_products_for_section($club_id);
        $compte_pilote = $this->comptes_model->compte_pilote($mlogin, $section);
        $solde         = $compte_pilote ? (float) $this->ecritures_model->solde_compte($compte_pilote['id']) : 0.0;

        $data = array(
            'section'       => $section,
            'produits'      => $produits,
            'solde'         => $solde,
            'annee_courante' => ((int) date('m') === 12) ? (int) date('Y') + 1 : (int) date('Y'),
            'error'         => $this->session->flashdata('error'),
        );
        $this->load->view('bs_header', $data);
        $this->load->view('bs_menu', $data);
        $this->load->view('bs_banner', $data);
        $this->load->view('paiements_en_ligne/bs_cotisation_form', $data);
        $this->load->view('bs_footer');
    }

    /**
     * Traite la soumission du formulaire cotisation (débit de solde).
     * Vérifie produit, doublon, solde ; crée l'écriture et la licence.
     */
    private function _process_cotisation($section, $club_id, $mlogin) {
        $produit_id = (int) $this->input->post('produit_id');

        if (!$produit_id) {
            $this->session->set_flashdata('error', $this->lang->line('gvv_cotisation_error_produit'));
            redirect('paiements_en_ligne/cotisation');
            return;
        }

        $produit = $this->tarifs_model->get_cotisation_product_by_id($produit_id);
        if (!$produit || (int) $produit['section_id'] !== $club_id) {
            $this->session->set_flashdata('error', $this->lang->line('gvv_cotisation_error_produit'));
            redirect('paiements_en_ligne/cotisation');
            return;
        }

        $annee   = ((int) date('m') === 12) ? (int) date('Y') + 1 : (int) date('Y');
        $montant = (float) $produit['montant'];

        // Doublon cotisation
        if ($this->licences_model->check_cotisation_exists($mlogin, $annee)) {
            $this->session->set_flashdata('error', sprintf(
                $this->lang->line('gvv_cotisation_error_already_paid'),
                $annee
            ));
            redirect('paiements_en_ligne/cotisation');
            return;
        }

        // Compte pilote (411)
        $compte_pilote = $this->comptes_model->compte_pilote($mlogin, $section);
        if (!$compte_pilote) {
            $this->session->set_flashdata('error', $this->lang->line('gvv_bar_error_no_pilot_account'));
            redirect('paiements_en_ligne/cotisation');
            return;
        }

        // Vérification du solde
        $solde = (float) $this->ecritures_model->solde_compte($compte_pilote['id']);
        if ($solde < $montant) {
            $this->session->set_flashdata('error', sprintf(
                $this->lang->line('gvv_bar_error_solde_insuffisant'),
                number_format($solde, 2, ',', ' ')
            ));
            redirect('paiements_en_ligne/cotisation');
            return;
        }

        // Compte recette cotisation
        $compte_cotisation_id = (int) $produit['compte_cotisation_id'];
        $ccot = $this->comptes_model->get_by_id('id', $compte_cotisation_id);
        if (!$ccot) {
            $this->session->set_flashdata('error', $this->lang->line('gvv_bar_error_no_account'));
            redirect('paiements_en_ligne/cotisation');
            return;
        }

        $description = $produit['libelle'] . ' ' . $annee;

        // Écriture comptable : débit 411 pilote → crédit compte cotisation
        $ecriture_id = $this->ecritures_model->create_ecriture(array(
            'annee_exercise' => date('Y'),
            'date_creation'  => date('Y-m-d'),
            'date_op'        => date('Y-m-d'),
            'compte1'        => (int) $compte_pilote['id'],
            'compte2'        => $compte_cotisation_id,
            'montant'        => $montant,
            'description'    => $description,
            'type'           => 0,
            'num_cheque'     => '',
            'saisie_par'     => $mlogin,
            'gel'            => 0,
            'club'           => $club_id,
            'categorie'      => 0,
        ));

        if (!$ecriture_id) {
            $this->session->set_flashdata('error', $this->lang->line('gvv_bar_error_creation'));
            redirect('paiements_en_ligne/cotisation');
            return;
        }

        // Création de la licence
        $this->licences_model->create_cotisation(
            $mlogin, 0, $annee, date('Y-m-d'),
            'Cotisation enregistrée en ligne (débit compte)'
        );

        $this->session->set_flashdata('success', sprintf(
            $this->lang->line('gvv_cotisation_success'),
            $annee
        ));
        redirect('compta/mon_compte');
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

        // Prérequis technique : les crédentiels HelloAsso doivent être configurés pour cette section
        $enabled = $this->paiements_en_ligne_model->get_config('helloasso', 'enabled', $club_id);
        if (!$enabled) {
            $data['error'] = $this->lang->line('gvv_public_bar_error_disabled');
            $this->_render_public_bar($data);
            return;
        }

        $montant_min = (float) ($this->paiements_en_ligne_model->get_config('helloasso', 'montant_min', $club_id) ?: 2.00);
        $montant_max = (float) ($this->paiements_en_ligne_model->get_config('helloasso', 'montant_max', $club_id) ?: 500.00);

        $data['section'] = $section;
        $data['montant_min'] = $montant_min;
        $data['montant_max'] = $montant_max;

        if ($this->input->post('button') === 'valider') {
            $this->_process_public_bar($data, $club_id, $section, $montant_min, $montant_max);
            return;
        }

        $this->_render_public_bar($data);
    }

    private function _render_public_bar(array $data) {
        $this->load->view('bs_header', $data);
        $this->load->view('paiements_en_ligne/bs_public_bar', $data);
        $this->load->view('bs_footer');
    }

    private function _process_public_bar(array $data, $club_id, array $section, $montant_min, $montant_max) {
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
        if ($montant < $montant_min) {
            $errors[] = sprintf(
                $this->lang->line('gvv_public_bar_error_montant_min'),
                number_format((float) $montant_min, 2, ',', ' ')
            );
        }
        if ($montant > $montant_max) {
            $errors[] = sprintf(
                $this->lang->line('gvv_public_bar_error_montant_max'),
                number_format((float) $montant_max, 2, ',', ' ')
            );
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
            'return_url'       => site_url('paiements_en_ligne/public_bar_confirmation?club=' . $club_id . '&tx=' . rawurlencode($txid)),
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

        if (!empty($checkout['session_id'])) {
            $this->paiements_en_ligne_model->attach_checkout_info(
                $txid,
                $checkout['session_id'],
                isset($checkout['redirect_url']) ? $checkout['redirect_url'] : null
            );
        }

        redirect($checkout['redirect_url']);
    }

    /**
     * Page de confirmation après retour de HelloAsso — accès public.
     */
    public function public_bar_confirmation() {
        $club_id = (int) $this->input->get('club');
        $txid = trim((string) $this->input->get('tx'));
        $section = $club_id ? $this->sections_model->get_by_id('id', $club_id) : null;

        $transaction = null;
        $tx_meta = array();
        if ($txid !== '') {
            $transaction = $this->paiements_en_ligne_model->get_by_transaction_id($txid);
            if ($transaction && !empty($transaction['metadata'])) {
                $decoded = json_decode($transaction['metadata'], true);
                if (is_array($decoded)) {
                    $tx_meta = $decoded;
                }
            }
        }

        $data = array(
            'section'     => $section,
            'transaction' => $transaction,
            'tx_meta'     => $tx_meta,
        );
        $this->load->view('bs_header', $data);
        $this->load->view('paiements_en_ligne/bs_public_bar_confirmation', $data);
        $this->load->view('bs_footer');
    }

    /**
     * Génère une affiche PDF avec QR code vers la page publique de paiement bar.
     *
     * Accès : tresorier, bureau, admin
     */
    public function genere_bar_qrcode() {
        if (!has_role('tresorier') && !has_role('bureau') && !$this->dx_auth->is_admin()) {
            $this->dx_auth->deny_access();
            return;
        }

        $section = $this->_require_active_section();
        if (!$section) {
            return;
        }

        $club_id = (int) $section['id'];
        $payment_url = site_url('paiements_en_ligne/public_bar/' . $club_id);
        $can_generate = !empty($section['has_bar']);

        $data = array(
            'section'      => $section,
            'title'        => $this->input->post('title') ?: $this->lang->line('gvv_bar_qrcode_default_title'),
            'text_top'     => $this->input->post('text_top') ?: $this->lang->line('gvv_bar_qrcode_default_text_top'),
            'text_bottom'  => $this->input->post('text_bottom') ?: $this->lang->line('gvv_bar_qrcode_default_text_bottom'),
            'payment_url'  => $payment_url,
            'error'        => $can_generate ? '' : $this->lang->line('gvv_bar_error_no_bar'),
            'can_generate' => $can_generate,
        );

        if ($can_generate && $this->input->post('button') === 'generate_pdf') {
            $title = trim((string) $this->input->post('title'));
            $text_top = trim((string) $this->input->post('text_top'));
            $text_bottom = trim((string) $this->input->post('text_bottom'));

            if ($title === '') {
                $data['error'] = $this->lang->line('gvv_bar_qrcode_error_title_required');
            } else {
                include_once(APPPATH . '/third_party/tcpdf/tcpdf.php');

                $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
                $pdf->SetCreator('GVV');
                $pdf->SetAuthor('GVV');
                $pdf->SetTitle($title);
                $pdf->SetMargins(15, 15, 15);
                $pdf->SetAutoPageBreak(true, 15);
                $pdf->setPrintHeader(false);
                $pdf->setPrintFooter(false);
                $pdf->AddPage();

                $pdf->SetFont('helvetica', 'B', 24);
                $pdf->MultiCell(0, 14, $title, 0, 'C', false, 1);

                if ($text_top !== '') {
                    $pdf->Ln(3);
                    $pdf->SetFont('helvetica', '', 13);
                    $pdf->MultiCell(0, 8, $text_top, 0, 'C', false, 1);
                }

                $qr_size = 80;
                $qr_x = (210 - $qr_size) / 2;
                $qr_y = $pdf->GetY() + 6;
                $style = array(
                    'border' => 0,
                    'padding' => 0,
                    'fgcolor' => array(0, 0, 0),
                    'bgcolor' => false,
                );
                $pdf->write2DBarcode($payment_url, 'QRCODE,H', $qr_x, $qr_y, $qr_size, $qr_size, $style, 'N');

                $pdf->SetY($qr_y + $qr_size + 8);
                if ($text_bottom !== '') {
                    $pdf->SetFont('helvetica', '', 12);
                    $pdf->MultiCell(0, 8, $text_bottom, 0, 'C', false, 1);
                }

                $pdf->Ln(4);
                $pdf->SetFont('helvetica', '', 9);
                $pdf->MultiCell(0, 6, $payment_url, 0, 'C', false, 1);

                $filename = 'affiche-bar-qrcode-section-' . $club_id . '.pdf';
                $pdf->Output($filename, 'I');
                return;
            }
        }

        $this->load->view('bs_header', $data);
        $this->load->view('bs_menu', $data);
        $this->load->view('bs_banner', $data);
        $this->load->view('paiements_en_ligne/bs_genere_bar_qrcode', $data);
        $this->load->view('bs_footer');
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
        $sections = $this->sections_model->section_list();
        $sections_map = array();
        foreach ($sections as $section) {
            $sections_map[(int) $section['id']] = $section['nom'];
        }

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
            $section_name = isset($sections_map[(int) $tx['club']])
                ? $sections_map[(int) $tx['club']]
                : (string) $tx['club'];
            $statut_key = 'gvv_pel_statut_' . $tx['statut'];
            $statut_label = $this->lang->line($statut_key);
            if ($statut_label === FALSE || $statut_label === '') {
                $statut_label = $tx['statut'];
            }
            fputcsv($out, array(
                $tx['date_demande'],
                trim($prenom . ' ' . $nom) ?: $tx['username'],
                number_format((float) $tx['montant'],    2, ',', ''),
                number_format((float) $tx['commission'], 2, ',', ''),
                $tx['plateforme'],
                $tx['transaction_id'],
                $statut_label,
                $section_name,
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
     * L'URL doit inclure le club_id : paiements_en_ligne/helloasso_webhook/{club_id}
     * Ce club_id provient de l'URL (source fiable), pas du payload (contrôlé par l'appelant).
     *
     * Algorithme :
     *  1. Vérifier que la méthode HTTP est POST
     *  2. Valider club_id (paramètre URL)
     *  3. Lire le payload brut et le header X-Ha-Signature
     *  4. Vérifier HMAC-SHA256 — HTTP 401 si invalide
     *  5. Décoder JSON — ignorer silencieusement tout eventType != 'Order'
     *  6. Déléguer au modèle : idempotence, payment state, écritures, mise à jour
     *  7. Logger le résultat, envoyer un email de confirmation si completed
     *  8. Retourner HTTP 200
     *
     * Retourne toujours HTTP 200 (sauf 401 sig invalide) pour éviter les retries HA
     * sur des erreurs de configuration permanentes.
     */
    public function helloasso_webhook($club_id = 0)
    {
        $club_id = (int) $club_id;

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->output->set_status_header(405);
            $this->output->set_content_type('text/plain');
            $this->output->set_output('Method Not Allowed');
            return;
        }

        $raw_body  = (string) file_get_contents('php://input');
        $signature = (string) ($this->input->server('HTTP_X_HA_SIGNATURE') ?: '');

        if (!$club_id) {
            $event = json_decode($raw_body, true);
            $order_data = (is_array($event) && !empty($event['data']) && is_array($event['data']))
                ? $event['data']
                : array();
            $resolved_club_id = !empty($order_data)
                ? (int) $this->paiements_en_ligne_model->resolve_club_id_from_order_data($order_data)
                : 0;

            if ($resolved_club_id > 0) {
                $club_id = $resolved_club_id;
                $this->helloasso->log('INFO', 'none', 'webhook',
                    'club_id résolu depuis le payload : club=' . $club_id);
            }
        }

        if (!$club_id) {
            $this->helloasso->log('ERROR', 'none', 'webhook',
                'club_id manquant dans l\'URL — utiliser paiements_en_ligne/helloasso_webhook/{club_id}');
            $this->output->set_status_header(400);
            $this->output->set_content_type('text/plain');
            $this->output->set_output('Bad Request');
            return;
        }
        $request_ip = $this->helloasso->get_request_ip();

        // ── Auth webhook : signature HMAC (partenaires) OU IP allowlist (non-partenaires) ──
        $signature_ok = $this->helloasso->verify_webhook_signature($raw_body, $signature, $club_id);
        $ip_ok = $this->helloasso->is_webhook_ip_allowed($club_id, $request_ip);

        if (!$signature_ok && !$ip_ok) {
            $this->helloasso->log('ERROR', 'none', 'webhook',
                'STATUS=REJECTED auth invalid club=' . $club_id
                . ' ip=' . ($request_ip ?: 'unknown')
                . ' signature=' . ($signature === '' ? 'missing' : 'present')
            );
            $this->output->set_status_header(401);
            $this->output->set_content_type('text/plain');
            $this->output->set_output('Unauthorized');
            return;
        }

        $auth_mode = $signature_ok ? 'signature' : 'ip_allowlist';
        $this->helloasso->log('INFO', 'none', 'webhook',
            'STATUS=ACCEPTED auth=' . $auth_mode
            . ' club=' . $club_id
            . ' ip=' . ($request_ip ?: 'unknown')
        );

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
        $gvv_txid   = $this->_extract_gvv_txid($order_data);

        // ── Traiter l'événement ─────────────────────────────────────────────
        $result = $this->paiements_en_ligne_model->process_order_event($order_data, $club_id);
        $transaction = isset($result['transaction']) ? $result['transaction'] : array();

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
                // Création du bon découverte + emails pour le flux UC4
                if (isset($result['type']) && $result['type'] === 'decouverte') {
                    $voucher_id = $this->_create_decouverte_voucher(
                        isset($result['metadata']) ? $result['metadata'] : array(),
                        isset($result['transaction']) ? $result['transaction'] : $transaction
                    );
                    $this->_send_external_decouverte_email(
                        isset($result['metadata']) ? $result['metadata'] : array(),
                        isset($result['transaction']) ? $result['transaction'] : $transaction,
                        (int) $voucher_id
                    );
                    $this->_notify_tresorier_decouverte(
                        isset($result['transaction']) ? $result['transaction'] : $transaction,
                        isset($result['metadata']) ? $result['metadata'] : array()
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
        $raw_meta = $this->_extract_order_metadata($order_data);
        if (!is_array($raw_meta)) {
            return null;
        }
        return $this->_extract_gvv_txid_from_meta($raw_meta);
    }

    /** Extrait metadata depuis les variantes de payload HelloAsso (metadata/metaData/items). */
    private function _extract_order_metadata(array $order_data)
    {
        $candidates = array();

        if (array_key_exists('metadata', $order_data)) {
            $candidates[] = $order_data['metadata'];
        }
        if (array_key_exists('metaData', $order_data)) {
            $candidates[] = $order_data['metaData'];
        }
        if (array_key_exists('meta', $order_data)) {
            $candidates[] = $order_data['meta'];
        }

        if (!empty($order_data['items']) && is_array($order_data['items'])) {
            foreach ($order_data['items'] as $item) {
                if (!is_array($item)) {
                    continue;
                }
                if (array_key_exists('metadata', $item)) {
                    $candidates[] = $item['metadata'];
                }
                if (array_key_exists('metaData', $item)) {
                    $candidates[] = $item['metaData'];
                }
                if (array_key_exists('meta', $item)) {
                    $candidates[] = $item['meta'];
                }
            }
        }

        foreach ($candidates as $candidate) {
            if (is_array($candidate)) {
                return $this->_normalize_metadata_array($candidate);
            }
            if (is_string($candidate) && trim($candidate) !== '') {
                $decoded = json_decode($candidate, true);
                if (is_array($decoded)) {
                    return $this->_normalize_metadata_array($decoded);
                }
            }
        }

        return null;
    }

    /** Normalise metadata pour supporter les listes key/value. */
    private function _normalize_metadata_array(array $meta)
    {
        $is_assoc = array_keys($meta) !== range(0, count($meta) - 1);
        if ($is_assoc) {
            return $meta;
        }

        $normalized = array();
        foreach ($meta as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $k = null;
            if (isset($entry['key'])) {
                $k = $entry['key'];
            } elseif (isset($entry['name'])) {
                $k = $entry['name'];
            }
            if ($k === null || $k === '') {
                continue;
            }

            if (array_key_exists('value', $entry)) {
                $normalized[$k] = $entry['value'];
            } elseif (array_key_exists('val', $entry)) {
                $normalized[$k] = $entry['val'];
            }
        }

        return !empty($normalized) ? $normalized : $meta;
    }

    /** Extrait gvv_transaction_id en tolérant variantes de clé/casse. */
    private function _extract_gvv_txid_from_meta(array $meta)
    {
        $candidates = array(
            'gvv_transaction_id',
            'gvvTransactionId',
            'transaction_id',
            'reference',
        );

        foreach ($candidates as $key) {
            if (!empty($meta[$key])) {
                return (string) $meta[$key];
            }
        }

        foreach ($meta as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            if (strtolower($key) === 'gvv_transaction_id' && !empty($value)) {
                return (string) $value;
            }
        }

        return null;
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

    /**
     * Crée un bon découverte en base après paiement confirmé.
     * Idempotence renforcée : un paiement HelloAsso ne crée qu'un bon.
     */
    private function _create_decouverte_voucher(array $meta, array $transaction)
    {
        $txid = isset($transaction['transaction_id']) ? (string) $transaction['transaction_id'] : '';
        if ($txid === '') {
            return 0;
        }

        $paiement_ref = 'HelloAsso:' . $txid;
        $existing = $this->db->where('paiement', $paiement_ref)->get('vols_decouverte')->row_array();
        if (!empty($existing)) {
            return isset($existing['id']) ? (int) $existing['id'] : 0;
        }

        $club_id = isset($transaction['club']) ? (int) $transaction['club'] : 0;
        $product = isset($meta['product_reference']) ? trim((string) $meta['product_reference']) : '';
        if ($club_id <= 0 || $product === '') {
            $this->helloasso->log('ERROR', $txid, 'webhook', 'decouverte: metadata incomplète pour créer le bon');
            return 0;
        }

        $date_vente = isset($transaction['date_paiement'])
            ? substr((string) $transaction['date_paiement'], 0, 10)
            : date('Y-m-d');
        $date_validite = date('Y-m-d', strtotime($date_vente . ' +1 year'));
        $year = (int) date('Y', strtotime($date_vente));
        // Advisory lock prevents duplicate IDs under concurrent webhook deliveries
        $this->db->query("SELECT GET_LOCK('vols_decouverte_id', 10)");
        $next_id = ((int) $this->vols_decouverte_model->highest_id_by_year($year)) + 1;
        $now = date('Y-m-d H:i:s');

        $insert = array(
            'id'                => $next_id,
            'date_vente'        => $date_vente,
            'club'              => $club_id,
            'product'           => $product,
            'beneficiaire'      => isset($meta['beneficiaire']) ? (string) $meta['beneficiaire'] : '',
            'de_la_part'        => isset($meta['de_la_part']) ? (string) $meta['de_la_part'] : '',
            'beneficiaire_email'=> isset($meta['beneficiaire_email']) ? (string) $meta['beneficiaire_email'] : '',
            'paiement'          => $paiement_ref,
            'participation'     => 'payé en ligne',
            'saisie_par'        => 'helloasso',
        );

        if ($this->db->field_exists('beneficiaire_tel', 'vols_decouverte')) {
            $insert['beneficiaire_tel'] = isset($meta['beneficiaire_tel']) ? (string) $meta['beneficiaire_tel'] : '';
        }
        if ($this->db->field_exists('urgence', 'vols_decouverte')) {
            $insert['urgence'] = isset($meta['urgence']) ? (string) $meta['urgence'] : '';
        }
        if ($this->db->field_exists('occasion', 'vols_decouverte')) {
            $insert['occasion'] = isset($meta['occasion']) ? (string) $meta['occasion'] : '';
        }
        if ($this->db->field_exists('nb_personnes', 'vols_decouverte')) {
            $insert['nb_personnes'] = isset($meta['nb_personnes']) ? (int) $meta['nb_personnes'] : 1;
        }

        if ($this->db->field_exists('date_validite', 'vols_decouverte')) {
            $insert['date_validite'] = $date_validite;
        }
        if ($this->db->field_exists('created_at', 'vols_decouverte')) {
            $insert['created_at'] = $now;
        }
        if ($this->db->field_exists('updated_at', 'vols_decouverte')) {
            $insert['updated_at'] = $now;
        }

        if ($this->db->field_exists('created_by', 'vols_decouverte')) {
            $insert['created_by'] = 'helloasso';
        }
        if ($this->db->field_exists('updated_by', 'vols_decouverte')) {
            $insert['updated_by'] = 'helloasso';
        }

        $ok = $this->db->insert('vols_decouverte', $insert);
        $this->db->query("SELECT RELEASE_LOCK('vols_decouverte_id')");
        if (!$ok) {
            $this->helloasso->log('ERROR', $txid, 'webhook',
                'decouverte: échec insertion bon découverte - ' . $this->db->_error_message());
            return 0;
        }

        $this->helloasso->log('INFO', $txid, 'webhook',
            'Bon découverte créé id=' . $next_id . ' product=' . $product);
        return $next_id;
    }

    /**
     * Envoie un email de confirmation au bénéficiaire d'un bon découverte.
     * Si $voucher_id > 0, génère et joint le bon en PDF.
     */
    private function _send_external_decouverte_email(array $meta, array $transaction, $voucher_id = 0)
    {
        $email = isset($meta['beneficiaire_email']) ? trim((string) $meta['beneficiaire_email']) : '';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            $nom_club     = $this->config->item('nom_club') ?: 'GVV';
            $sender_email = $this->configuration_model->get_param('vd.email.sender_email') ?: 'noreply@gvv.net';
            $sender_name  = $this->configuration_model->get_param('vd.email.sender_name')  ?: $nom_club;
            $montant      = number_format((float) $transaction['montant'], 2, ',', ' ');
            $benef        = isset($meta['beneficiaire']) ? trim((string) $meta['beneficiaire']) : $email;
            $product      = isset($meta['product_description']) ? (string) $meta['product_description'] : '';

            $subject = $this->lang->line('gvv_decouverte_email_subject') ?: 'Bon découverte confirmé';
            $message = sprintf(
                "Bonjour %s,\n\nVotre bon découverte (%s) a été réglé avec succès (%s€).\n\nVeuillez trouver ci-joint votre bon en PDF.\n\nBon envoyé par : %s\n\nCordialement,\n%s",
                $benef,
                $product ?: 'vol découverte',
                $montant,
                $sender_name,
                $sender_name
            );

            $this->load->library('email');
            $this->email->initialize(array(
                'wordwrap' => true,
                'mailtype' => 'text',
                'charset'  => 'utf-8',
            ));
            $this->email->from($sender_email, $sender_name);
            $this->email->to($email);
            $this->email->subject($subject);
            $this->email->message($message);

            // Joindre le PDF du bon découverte si disponible
            $temp_file = null;
            if ($voucher_id > 0) {
                $pdf_content = $this->_generate_vd_pdf($voucher_id);
                if ($pdf_content) {
                    $temp_file = sys_get_temp_dir() . '/vd_' . $voucher_id . '.pdf';
                    file_put_contents($temp_file, $pdf_content);
                    $this->email->attach($temp_file, 'attachment',
                        'vol_decouverte_' . $voucher_id . '.pdf', 'application/pdf');
                }
            }

            @$this->email->send();

            if ($temp_file && file_exists($temp_file)) {
                unlink($temp_file);
            }
        } catch (Exception $e) {
            $this->helloasso->log('ERROR', 'none', 'webhook',
                'Erreur email bon découverte : ' . $e->getMessage());
        }
    }

    /**
     * Génère le PDF d'un bon découverte à partir de son ID.
     * Retourne le contenu PDF (string) ou null en cas d'échec.
     */
    private function _generate_vd_pdf($voucher_id)
    {
        try {
            $vd = $this->db->where('id', $voucher_id)->get('vols_decouverte')->row_array();
            if (!$vd) return null;

            require_once(APPPATH . 'third_party/phpqrcode/qrlib.php');
            require_once(APPPATH . 'third_party/tcpdf/tcpdf.php');

            $obfuscated_id = transformInteger($voucher_id);
            $tempDir       = sys_get_temp_dir();

            // QR code
            $qr_url  = site_url('vols_decouverte/action/' . $obfuscated_id);
            $qr_name = $tempDir . '/qrcode_vd_' . $voucher_id . '.png';
            QRcode::png($qr_url, $qr_name, QR_ECLEVEL_L, 10, 1);

            $pdf = new TCPDF('L', 'mm', 'A5', true, 'UTF-8', false);
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor($this->configuration_model->get_param('vd.email.sender_name'));
            $pdf->SetTitle('Vol de découverte ' . $voucher_id);
            $pdf->SetSubject('Bon cadeau');
            $pdf->SetKeywords('vol, découverte');
            $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetHeaderMargin(0);
            $pdf->SetFooterMargin(0);
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

            // Page 1 — image de fond
            $pdf->AddPage();
            $bMargin         = $pdf->getBreakMargin();
            $auto_page_break = $pdf->getAutoPageBreak();
            $pdf->SetAutoPageBreak(false, 0);
            $background_image = $this->configuration_model->get_file('vd.background_image');
            $img_file = (!empty($background_image) && file_exists($background_image))
                ? $background_image
                : image_dir() . 'Bon-Bapteme.png';
            $pdf->Image($img_file, 0, 0, 210, 150, '', '', '', false, 300, '', false, false, 0);
            $pdf->SetAutoPageBreak($auto_page_break, $bMargin);
            $pdf->setPageMark();
            if (file_exists($qr_name)) {
                $pdf->Image($qr_name, 175, 5, 30, 30, 'PNG', '', 'T', false, 300, '', false, false, 0, 'CM');
            }

            // Page 2 — détails
            $pdf->AddPage();
            $pdf->SetXY(5, 5);
            $pdf->SetMargins(5, 5, 5);
            $pdf->setAutoPageBreak(false);
            $pdf->SetFont('helvetica', '', 10);

            $offer_a    = isset($vd['beneficiaire'])  ? $vd['beneficiaire']  : '';
            $de_la_part = isset($vd['de_la_part'])    ? $vd['de_la_part']    : '';
            $occasion   = isset($vd['occasion'])      ? $vd['occasion']      : '';
            $validity   = !empty($vd['date_validite'])
                ? date_db2ht($vd['date_validite'])
                : date_db2ht(date('Y-m-d', strtotime($vd['date_vente'] . ' +1 year')));

            $header_html = <<<EOD
<table cellspacing="0" cellpadding="3" border="1">
    <tr><td width="67%">Ce bon pour le survol de la région défini ci-après</td><td width="33%">N° <strong>{$voucher_id}</strong></td></tr>
    <tr><td width="67%">Offert à <strong>{$offer_a}</strong></td><td width="33%"></td></tr>
    <tr><td width="67%">à l'occasion de {$occasion}</td><td width="33%">de la part de {$de_la_part}</td></tr>
    <tr><td width="67%">Ce bon est valable 1 an jusqu'au <strong>{$validity}</strong></td><td width="33%"></td></tr>
</table>
EOD;
            $pdf->writeHTML($header_html, true, false, false, false, '');

            $this->load->model('sections_model');

            $section = $this->sections_model->get_by_id('id', $vd['club']);
            $section_name = isset($section['nom']) ? trim((string) $section['nom']) : '';
            $section_label = $section_name;
            if ($section_label !== '' && !preg_match('/^[A-Z0-9]+$/', $section_label)) {
                $section_label = strtolower($section_label);
            }
            $voucher_title = $section_label !== '' ? 'Un vol en ' . $section_label : 'Un vol de découverte';

            $tarif = $this->db->select('description')
                ->from('tarifs')
                ->where('reference', $vd['product'])
                ->where('club', (int) $vd['club'])
                ->where('date <=', $vd['date_vente'])
                ->order_by('date', 'desc')
                ->limit(1)
                ->get()
                ->row_array();
            $voucher_detail = !empty($tarif['description'])
                ? trim((string) $tarif['description'])
                : trim((string) $vd['product']);

            $title_safe = htmlspecialchars($voucher_title, ENT_QUOTES, 'UTF-8');
            $detail_safe = htmlspecialchars($voucher_detail, ENT_QUOTES, 'UTF-8');

            $options_html = <<<EOD
<table cellspacing="0" cellpadding="5" border="1">
    <tr>
        <td style="height: 150px; vertical-align: middle; text-align: center;">
            <div style="font-size: 22px; font-weight: bold;">{$title_safe}</div>
            <br />
            <div style="font-size: 14px;">{$detail_safe}</div>
        </td>
    </tr>
</table>
EOD;
            $pdf->writeHTML($options_html, true, false, false, false, '');

            $contact_avion   = $this->configuration_model->get_param('vd.avion.contact_name');
            $contact_planeur = $this->configuration_model->get_param('vd.planeur.contact_name');
            $contact_ulm     = $this->configuration_model->get_param('vd.ulm.contact_name');
            $tel_avion       = $this->configuration_model->get_param('vd.avion.contact_tel');
            $tel_planeur     = $this->configuration_model->get_param('vd.planeur.contact_tel');
            $tel_ulm         = $this->configuration_model->get_param('vd.ulm.contact_tel');

            $contact_html = <<<EOD
<table cellspacing="0" cellpadding="5" border="1" style="width: 100%;">
    <tr>
        <td>
            Pour prendre rendez-vous et organiser votre vol, vous devez contacter<br>
            <br />- pour l'avion <strong>{$contact_avion} ({$tel_avion})</strong>
            <br />- pour le planeur <strong>{$contact_planeur} ({$tel_planeur})</strong>
            <br />- pour l'ULM <strong>{$contact_ulm} ({$tel_ulm})</strong>
        </td>
    </tr>
    <tr>
        <td width="33%" height="1.5cm">Vol effectué le :</td>
        <td width="33%">sur (nom de l'appareil) :</td>
        <td width="34%">par (nom du pilote) :</td>
    </tr>
</table>
EOD;
            $pdf->writeHTML($contact_html, true, false, false, false, '');

            return $pdf->Output('vol_decouverte_' . $voucher_id . '.pdf', 'S');

        } catch (Exception $e) {
            $this->helloasso->log('ERROR', 'none', 'webhook',
                'Erreur génération PDF bon découverte id=' . $voucher_id . ' : ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Notifie le club après création d'un bon découverte via paiement en ligne.
     */
    private function _notify_tresorier_decouverte(array $transaction, array $meta)
    {
        $email_club = $this->config->item('email_club');
        if (empty($email_club)) {
            return;
        }

        try {
            $nom_club = $this->config->item('nom_club') ?: 'GVV';
            $benef    = isset($meta['beneficiaire']) ? (string) $meta['beneficiaire'] : '?';
            $offreur  = isset($meta['de_la_part']) ? (string) $meta['de_la_part'] : '';
            $product  = isset($meta['product_description']) ? (string) $meta['product_description'] : '';
            $montant  = number_format((float) $transaction['montant'], 2, ',', ' ');

            $subject = sprintf('[%s] Bon découverte réglé — %s', $nom_club, $benef);
            $message = sprintf(
                "Bonjour,\n\nUn bon découverte a été réglé en ligne via HelloAsso.\n\nBénéficiaire : %s\nDe la part de : %s\nProduit : %s\nMontant : %s€\nTransaction : %s\n\nCordialement,\n%s",
                $benef,
                $offreur ?: '-',
                $product ?: '-',
                $montant,
                isset($transaction['transaction_id']) ? $transaction['transaction_id'] : '-',
                $nom_club
            );

            $this->load->library('email');
            $this->email->initialize(array(
                'wordwrap' => true,
                'mailtype' => 'text',
                'charset'  => 'utf-8',
            ));
            $this->email->from('noreply@gvv.net', $nom_club);
            $this->email->to($email_club);
            $this->email->subject($subject);
            $this->email->message($message);
            @$this->email->send();
        } catch (Exception $e) {
            $this->helloasso->log('ERROR', 'none', 'webhook',
                'Erreur notification trésorier bon découverte : ' . $e->getMessage());
        }
    }

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
     * Page de confirmation après paiement HelloAsso réussi — flux découverte GVV authentifié.
     * Appelée depuis l'URL de retour de decouverte_pay().
     *
     * @param string $transaction_id  ID de la transaction GVV
     */
    public function decouverte_pay_confirmation($transaction_id = '') {
        $transaction = false;
        if ($transaction_id !== '') {
            $transaction = $this->paiements_en_ligne_model->get_by_transaction_id($transaction_id);
        }

        $data = array(
            'transaction' => $transaction,
            'back_url'    => site_url('paiements_en_ligne/decouverte_qr/' . $transaction_id),
        );
        $this->load->view('bs_header', $data);
        $this->load->view('bs_menu', $data);
        $this->load->view('bs_banner', $data);
        $this->load->view('paiements_en_ligne/bs_confirmation', $data);
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
        $club_id = (int) ($this->input->get('club') ?: 0);
        if (!$club_id) {
            $section = $this->sections_model->section();
            $club_id = isset($section['id']) ? (int) $section['id'] : 0;
        }

        $available = false;
        if ($club_id > 0) {
            $client_id     = $this->paiements_en_ligne_model->get_config('helloasso', 'client_id', $club_id);
            $client_secret = $this->paiements_en_ligne_model->get_config('helloasso', 'client_secret', $club_id);
            $enabled       = $this->paiements_en_ligne_model->get_config('helloasso', 'enabled', $club_id);
            $available = !empty($client_id) && !empty($client_secret) && !empty($enabled);
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
        $is_admin = $this->dx_auth->is_admin();
        if (!$is_admin && $this->session->userdata('use_new_auth')) {
            $this->load->library('Gvv_Authorization');
            $is_admin = $this->gvv_authorization->has_role($this->dx_auth->get_user_id(), 'club-admin', NULL);
        }
        if (!$is_admin) {
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

        // Sélecteur de comptes 7xx pour le bar — filtre par la section cible, pas la session
        $bar_where = array('codec >=' => '700', 'codec <' => '800');
        if ($club_id) $bar_where['club'] = $club_id;
        $bar_account_selector = $this->comptes_model->selector_with_null($bar_where, FALSE);

        // Sélecteur de comptes 467 pour le compte de passage — filtre par la section cible, pas la session
        $compte_where = array('codec' => '467');
        if ($club_id) $compte_where['club'] = $club_id;
        $compte_passage_selector = $this->comptes_model->selector_with_null($compte_where, FALSE);

        // Chargement de la config courante
        $cfg = $this->_load_config($club_id);

        // Info de la section courante (has_bar, bar_account_id)
        $section_row = null;
        if ($club_id) {
            $query = $this->db->where('id', $club_id)->get('sections');
            $section_row = $query->row_array();
        }

        // Comptage des bons VD vendus dans les 30 derniers jours pour la section
        $vd_vendu_30j = 0;
        if ($club_id) {
            $row = $this->db->query(
                'SELECT COUNT(*) AS cnt FROM vols_decouverte
                 WHERE club = ? AND cancelled = 0
                   AND date_vente >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)',
                array($club_id)
            )->row();
            $vd_vendu_30j = $row ? (int) $row->cnt : 0;
        }

        $data = array(
            'sections_selector'         => $this->sections_model->selector(),
            'bar_account_selector'      => $bar_account_selector,
            'compte_passage_selector'   => $compte_passage_selector,
            'club_id'                   => $club_id,
            'cfg'                       => $cfg,
            'section_row'               => $section_row,
            'webhook_url'               => site_url('paiements_en_ligne/helloasso_webhook/' . $club_id),
            'vd_vendu_30j'              => $vd_vendu_30j,
            'success'                   => $this->session->flashdata('success'),
            'error'                     => $this->session->flashdata('error'),
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

        $club_id       = (int) $this->input->post('club_id');
        $client_id     = trim($this->input->post('client_id') ?: '');
        $client_secret = trim($this->input->post('client_secret') ?: '');
        $environment   = $this->input->post('environment') ?: 'sandbox';
        $this->output->set_content_type('application/json');

        if (!empty($client_id) && !empty($client_secret)) {
            $token = $this->helloasso->get_oauth_token_with_credentials($client_id, $client_secret, $environment);
        } else {
            $token = $this->helloasso->get_oauth_token($club_id);
        }
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
            'client_id'        => trim($this->input->post('client_id') ?: ''),
            'account_slug'     => trim($this->input->post('account_slug') ?: ''),
            'environment'      => $this->input->post('environment') === 'production' ? 'production' : 'sandbox',
            'webhook_secret'   => trim($this->input->post('webhook_secret') ?: ''),
            'compte_passage'   => (string)(int)($this->input->post('compte_passage') ?: 0),
            'montant_min'      => (float) ($this->input->post('montant_min') ?: 10),
            'montant_max'      => (float) ($this->input->post('montant_max') ?: 500),
            'enabled'          => $this->input->post('enabled') ? '1' : '0',
            'vd_accueil_text'  => (string) ($this->input->post('vd_accueil_text') ?: ''),
            'vd_quota_mensuel' => (string) max(0, (int) ($this->input->post('vd_quota_mensuel') ?: 0)),
        );

        // client_secret : ne remplacer que si une nouvelle valeur est saisie
        $new_secret = trim($this->input->post('client_secret') ?: '');
        if ($new_secret !== '') {
            $keys['client_secret'] = $new_secret;
        }

        // Validation : compte de passage obligatoire
        if ((int) $keys['compte_passage'] === 0) {
            $this->session->set_flashdata('error', $this->lang->line('gvv_admin_config_error_no_compte_passage'));
            redirect('paiements_en_ligne/admin_config?section=' . $club_id);
            return;
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
                $this->session->set_flashdata('error', $this->lang->line('gvv_admin_config_error_crypto_key'));
                redirect('paiements_en_ligne/admin_config?section=' . $club_id);
                return;
            }
        }

        // Flags fonctionnels dans la table sections
        $has_bar              = $this->input->post('has_bar') ? 1 : 0;
        $bar_account_id       = (int) ($this->input->post('bar_account_id') ?: 0) ?: null;
        $has_vd_par_cb        = $this->input->post('has_vd_par_cb') ? 1 : 0;
        $has_approvisio_par_cb = $this->input->post('has_approvisio_par_cb') ? 1 : 0;

        $this->db->where('id', $club_id)->update('sections', array(
            'has_bar'               => $has_bar,
            'bar_account_id'        => $bar_account_id,
            'has_vd_par_cb'         => $has_vd_par_cb,
            'has_approvisio_par_cb' => $has_approvisio_par_cb,
        ));

        // Log d'audit
        $this->helloasso->log('INFO', 'none', 'admin_config',
            'Config updated for club=' . $club_id . ' by=' . $username
            . ' enabled=' . $keys['enabled']
            . ' environment=' . $keys['environment']
            . ' has_bar=' . $has_bar
            . ' has_vd_par_cb=' . $has_vd_par_cb
            . ' has_approvisio_par_cb=' . $has_approvisio_par_cb
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
            'client_id'        => '',
            'client_secret'    => '',
            'account_slug'     => '',
            'environment'      => 'sandbox',
            'webhook_secret'   => '',
            'compte_passage'   => '0',
            'montant_min'      => '10',
            'montant_max'      => '500',
            'enabled'          => '0',
            'vd_accueil_text'  => '',
            'vd_quota_mensuel' => '0',
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
