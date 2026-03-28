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
 *
 * Playwright tests:
 *   - npx playwright test tests/paiements-en-ligne-smoke.spec.js
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
}
