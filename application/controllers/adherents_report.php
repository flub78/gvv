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
 * Contrôleur pour le rapport des adhérents par année et classe d'âge
 *
 * @package controllers
 */

class Adherents_report extends CI_Controller {

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

        $this->load->model('adherents_report_model');
        $this->lang->load('gvv');
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

        // Récupérer les statistiques
        $stats_data = $this->adherents_report_model->get_adherents_stats($year);

        // Préparer les données pour la vue
        $data = array(
            'controller' => $this->controller,
            'year' => $year,
            'year_selector' => $this->adherents_report_model->get_year_selector(),
            'sections' => $stats_data['sections'],
            'stats' => $stats_data['stats']
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
