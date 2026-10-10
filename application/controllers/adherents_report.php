<?php
/**
 * GVV Gestion vol à voile
 * Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Contrôleur des statistiques adhérents
 *
 * @package controllers
 */

class Adherents_report extends MY_Controller {

    protected $controller = 'adherents_report';
    protected $modification_level = 'ca';

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();

        date_default_timezone_set("Europe/Paris");

        // Check if user is logged in
        if (!$this->dx_auth->is_logged_in()) {
            redirect("auth/login");
        }
        $this->dx_auth->check_uri_permissions();

        $this->require_roles(['ca']);

        $this->load->model('adherents_report_model');
        $this->load->library('adherents_stats');
        $this->lang->load('gvv');

        // Bouton retour → tableau de bord Administration du club
        $this->lang->load('tableaux_de_bord');
        $this->load->vars([
            'nav_back_url'   => $this->session->userdata('nav_from_url')   ?: 'welcome/section/admin_club',
            'nav_back_label' => $this->session->userdata('nav_from_label') ?: $this->lang->line('db_section_admin_club'),
        ]);
    }

    /**
     * Page principale du rapport adhérents
     */
    public function index() {
        $this->page();
    }

    /**
     * Affiche le rapport des adhérents
     */
    public function page() {
        // Récupérer l'année depuis la session ou utiliser l'année courante
        $year = $this->session->userdata('adherents_report_year');
        if (!$year) {
            $year = (int)date('Y');
            $this->session->set_userdata('adherents_report_year', $year);
        }

        $raw = $this->adherents_report_model->get_adherents_data($year);
        $sections = $raw['sections'];
        $adherents = $this->adherents_stats->adherents_de_l_annee($raw['members'], $year);

        $data = array(
            'controller' => $this->controller,
            'year' => $year,
            'year_selector' => $this->adherents_report_model->get_year_selector(),
            'sections' => $sections,
            'columns' => $this->adherents_stats->colonnes($sections),
            'cles_classes_reglementaires' => $this->adherents_stats->cles_classes_reglementaires(),
            'classes_reglementaires' => $this->adherents_stats->repartition_par_classe_reglementaire($adherents, $sections, $year),
            'tranches_10_ans' => $this->adherents_stats->repartition_par_tranche_10_ans($adherents, $sections, $year),
            'age_inconnu' => $this->adherents_stats->adherents_age_inconnu($adherents, $year),
            'sexes' => $this->adherents_stats->repartition_par_sexe($adherents, $sections),
            'pyramide' => $this->adherents_stats->pyramide_des_ages($adherents, $year),
            'indicateurs' => $this->adherents_stats->indicateurs($adherents, $sections, $year),
            'evolution' => $this->adherents_stats->evolution($raw['members'], $year),
            'fidelisation' => $this->adherents_stats->fidelisation($raw['members'], $sections, $year),
            'anciennete' => $this->adherents_stats->repartition_par_anciennete($adherents, $sections, $year),
            'annee_en_cours' => ($year == (int) date('Y')),
        );

        load_last_view('adherents_report/bs_page', $data);
    }

    /**
     * Change l'année sélectionnée (endpoint AJAX)
     *
     * @param int $year L'année à sélectionner
     */
    public function set_year($year) {
        $year = (int)$year;
        if ($year >= 1990 && $year <= 2100) {
            $this->session->set_userdata('adherents_report_year', $year);
            echo json_encode(array('success' => true, 'year' => $year));
        } else {
            echo json_encode(array('success' => false, 'error' => 'Invalid year'));
        }
    }
}
