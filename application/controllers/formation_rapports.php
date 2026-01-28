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
 * Contrôleur des rapports de formation
 *
 * @package controllers
 */

class Formation_rapports extends CI_Controller
{
    protected $controller = 'formation_rapports';

    function __construct()
    {
        parent::__construct();

        // Check feature flag
        $this->load->library('formation_access');
        $this->formation_access->check_access_or_403();

        $this->load->model('formation_inscription_model');
        $this->load->model('formation_seance_model');
        $this->load->model('formation_programme_model');
        $this->load->model('formation_evaluation_model');
        $this->load->model('membres_model');
        $this->load->library('formation_progression');
        $this->lang->load('formation');
        $this->lang->load('gvv');
    }

    /**
     * Vue principale des rapports de formation
     */
    public function index()
    {
        log_message('debug', 'FORMATION_RAPPORTS: index() called');

        // Déterminer l'année
        $year = $this->input->get('year');
        if (empty($year)) {
            $year = $this->session->userdata('year');
        }
        if (empty($year)) {
            $year = date('Y');
        }
        $year = (int) $year;

        // Construire le sélecteur d'années
        $year_selector = $this->formation_inscription_model->getYearSelector();
        $seance_years = $this->formation_seance_model->getYearSelector();
        $year_selector = $year_selector + $seance_years;
        ksort($year_selector);

        // S'assurer que l'année courante est dans le sélecteur
        if (!isset($year_selector[$year])) {
            $year_selector[$year] = (string) $year;
        }

        // Récupérer les formations groupées par statut
        $formations = $this->formation_inscription_model->get_by_year($year);

        // Calculer la progression pour les formations en cours
        $date_limite = $year . '-12-31';
        foreach ($formations['en_cours'] as &$inscription) {
            $progression = $this->formation_progression->calculer_pourcentage_a_date(
                $inscription['id'], $date_limite
            );
            $inscription['progression'] = $progression;
        }
        unset($inscription);

        // Séances de ré-entrainement de l'année
        $seances_libres = $this->formation_seance_model->select_page(
            array('type' => 'libre', 'year' => $year), 1000, 0
        );

        // Statistiques par instructeur
        $instructeurs = $this->formation_seance_model->get_stats_par_instructeur($year);

        $data = array(
            'title' => $this->lang->line('formation_rapports_title'),
            'controller' => $this->controller,
            'year' => $year,
            'year_selector' => $year_selector,
            'formations' => $formations,
            'seances_libres' => $seances_libres,
            'instructeurs' => $instructeurs,
            'formation_progression' => $this->formation_progression
        );

        $this->load->view('formation_rapports/index', $data);
    }

    /**
     * Change d'année et redirige vers les rapports
     *
     * @param string $year Année sélectionnée
     */
    public function new_year($year)
    {
        $this->session->set_userdata('year', $year);
        redirect('formation_rapports');
    }
}
