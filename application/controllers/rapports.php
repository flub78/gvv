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
 * @filesource rapports.php
 *
 * Controleur d'édition des rapports PDF
 *
 * @package controllers
 */

/**
 *
 * Edition des rapports PDF
 *
 * @author Frédéric
 *
 */
include('./application/libraries/Gvv_Controller.php');
class Rapports extends Gvv_Controller {
        protected $controller = 'rapports';
        protected $model = 'rapports_model';
        protected $modification_level = 'ca';

        // L'idée générale est de créer un document PDF, et d'appeller des routines spécialisées
        // pour en remplir des sections
        private $annee_exercise;

        /**
         * Constructor
         */
        function __construct() {
                parent::__construct();

                date_default_timezone_set("Europe/Paris");

                // Check if user is logged in or not
                if (! $this->dx_auth->is_logged_in()) {
                        redirect("auth/login");
                }
                $this->dx_auth->check_uri_permissions();
                $this->load->library('Document');

                $this->annee_exercise = $this->session->userdata('year');
                $this->lang->load('rapports');
        }

        /**
         * Génération du rapport complet
         * Tous + Planches
         */
        public function complet($chapters = array(
                'licence',
                'result',
                'bilan',
                'categorie',
                'comptes',
                'ventes'
        )) {
                $year = $this->session->userdata('year');
                $doc = new Document(array(
                        'year' => $this->annee_exercise
                ));

                if (in_array('licence', $chapters))
                        $doc->pagesLicences($this->annee_exercise);

                if (in_array('result', $chapters)) {
                        $doc->pagesRapportFinancier($this->annee_exercise);
                        $doc->pagesResultats($this->annee_exercise);
                }

                if (in_array('bilan', $chapters))
                        $doc->pagesBilan($this->annee_exercise);

                if (in_array('balance', $chapters))
                        $doc->pagesBalance($this->annee_exercise);

                if (in_array('categorie', $chapters))
                        $doc->pagesResultatsCategorie($this->annee_exercise);

                if (in_array('ventes', $chapters))
                        $doc->pagesVentes($this->annee_exercise);

                if (in_array('comptes', $chapters))
                        $doc->pagesComptes($this->annee_exercise);

                $doc->generate();
        }

        /**
         * Génération du rapport annuel
         */
        public function annuel() {
                $this->complet(array(
                        'categorie'
                ));
        }

        /**
         * Génération du rapport financier
         */
        public function financier() {
                $this->complet(array(
                        'result',
                        'bilan',
                        'balance',
                        'ventes'
                ));
        }

        /**
         * Génération du rapport financier
         */
        public function comptes() {
                $this->complet(array(
                        'comptes'
                ));
        }

        /**
         * Document avec seulement les résultats
         */
        public function pdf_resultats() {
                $this->complet(array(
                        'result'
                ));
        }

        /**
         * Document avec seulement les résultats par catégorie
         */
        public function pdf_resultats_par_categories() {
                $this->complet(array(
                        'categorie'
                ));
        }

        /**
         * Licenciés en PDF
         */
        public function licences() {
                $this->complet(array(
                        'licence'
                ));
        }

        /**
         * Génération du rapport de ventes
         */
        public function ventes() {
                $this->complet(array(
                        'ventes'
                ));
        }

        /**
         * Impression du bilan
         */
        public function bilan() {
                $this->complet(array(
                        'bilan'
                ));
        }

        /**
         * Extraction des stats par planeur
         *
         * @param unknown_type $year
         */
        private function stat_per_planeur($year) {
                $this->load->model('planeurs_model');
                $this->load->model('vols_planeur_model');

                $selection = array(
                        'year(vpdate)' => $year
                );

                $pm = array();
                // $pm[] = array('Modèle', 'Fabrication', 'Immat', '', 'Heures planeur');

                $machines = $this->planeurs_model->select_all(array(
                        'mpprive <' => 2,
                        'actif' => 1
                ), "mpmodele");
                foreach ($machines as $machine) {
                        $line = array();
                        $line[] = $machine['mpmodele'];
                        $line[] = $machine['fabrication'];
                        $line[] = $immat = $machine['mpimmat'];
                        $line[] = '';
                        $where = array_merge($selection, array(
                                'vpmacid' => $immat
                        ));
                        $line[] = $this->vols_planeur_model->sum('vpduree', $where) / 60;
                        $pm[] = $line;
                }
                return $pm;
        }

        /**
         * Extraction des stats par avion
         *
         * @param unknown_type $year
         */
        private function stat_per_avion($year) {
                $this->load->model('avions_model');
                $this->load->model('vols_avion_model');

                $selection = array(
                        'year(vadate)' => $year
                );

                $pm = array();
                // $pm[] = array('Modèle', 'Fabrication', 'Immat', 'Heures remorquage', '');

                $machines = $this->avions_model->select_all(array(
                        'maprive <' => 2,
                        'actif' => 1
                ), "macmodele");
                foreach ($machines as $machine) {
                        $line = array();
                        $line[] = $machine['macmodele'];
                        $line[] = $machine['fabrication'];
                        $line[] = $immat = $machine['macimmat'];
                        $where = array_merge($selection, array(
                                'vamacid' => $immat,
                                'vacategorie' => 3
                        ));
                        $line[] = $this->vols_avion_model->sum('vaduree', $where);
                        $line[] = '';
                        $pm[] = $line;
                }
                return $pm;
        }

        /**
         * Génération du rapport FFVV
         */
        public function ffvv_data($year) {
                $this->load->model('vols_planeur_model');
                $this->load->model('event_model');

                $data = array();
                $date25 = date_m25ans($year);

                $data['year'] = $year;
                $data['year_selector'] = $this->vols_planeur_model->getYearSelector("vpdate");
                $data['controller'] = 'rapports';

                $data['association'] = $this->config->item('nom_club');
                $data['code'] = $this->config->item('code_club');

                $activity = array();
                $selections = array(
                        array(
                                'YEAR(vpdate)' => $year,
                                'categorie' => 0,
                                'mdaten >=' => $date25
                        ), // Francais - 25 ans
                        array(
                                'YEAR(vpdate)' => $year,
                                'categorie' => 0,
                                'mdaten <' => $date25
                        ), // Français +25 ans
                        array(
                                'YEAR(vpdate)' => $year,
                                'categorie' => 0
                        )
                ) // Français
                ;

                $labels = $this->lang->line("gvv_rapports_categories");

                $event_selections = array(
                        array(
                                'YEAR(edate)' => $year,
                                'categorie' => 0,
                                'mdaten >=' => $date25
                        ), // Francais - 25 ans
                        array(
                                'YEAR(edate)' => $year,
                                'categorie' => 0,
                                'mdaten <' => $date25
                        ), // Français +25 ans
                        array(
                                'YEAR(edate)' => $year,
                                'categorie' => 0
                        )
                ) // Français
                ;

                foreach ($this->config->item('categories_pilote') as $k => $v) {
                        if ($k) {
                                $selections[] = array(
                                        'YEAR(vpdate)' => $year,
                                        'categorie' => $k
                                );
                                $event_selections[] = array(
                                        'YEAR(edate)' => $year,
                                        'categorie' => $k
                                );
                                $labels[] = $v;
                        }
                }

                $remorque = 3;
                $treuille = 1;
                // pour -25, +25, Français, Etranger, stagiaires
                for ($i = 0; $i < count($selections); $i++) {
                        $selection = $selections[$i];
                        $event_selection = $event_selections[$i];
                        $line = array(
                                $labels[$i]
                        );
                        $decimal_hour = $this->vols_planeur_model->sum('vpduree', $selection) / 60;
                        $line[] = decimal_to_hm($decimal_hour);
                        $line[] = $this->vols_planeur_model->count(array_merge($selection, array(
                                'vpautonome' => $remorque
                        )));
                        $line[] = $this->vols_planeur_model->count(array_merge($selection, array(
                                'vpautonome' => $treuille
                        )));
                        $line[] = $this->event_model->count(array_merge($event_selection, array(
                                'name' => 'Laché planeur'
                        )));
                        $line[] = $this->event_model->count(array_merge($event_selection, array(
                                'name' => 'BPP'
                        )));
                        $line[] = $this->event_model->count(array_merge($event_selection, array(
                                'name' => 'BPP'
                        )));
                        $line[] = $this->event_model->count(array_merge($event_selection, array(
                                'name' => 'Campagne'
                        )));
                        $line[] = $this->vols_planeur_model->sum('vpnbkm', $selection);
                        $activity[] = $line;
                }
                // ajuste les totaux de remorqué (supprime autonomes et extérieur
                $data['activity'] = $activity;

                $data['machine_activity'] = array_merge($this->stat_per_planeur($year), $this->stat_per_avion($year));

                $total_glider = 0;
                $total_towing = 0;
                $i = 0;
                foreach ($data['machine_activity'] as $row) {
                        if ($row[4]) {
                                $total_glider += $row[4];
                                $data['machine_activity'][$i][4] = decimal_to_hm($row[4]);
                        }
                        if ($row[3]) {
                                $total_towing += $row[3];
                                $data['machine_activity'][$i][3] = decimal_to_hm($row[3]);
                        }
                        $i++;
                }
                $total_glider = decimal_to_hm($total_glider);
                $total_towing = decimal_to_hm($total_towing);
                $data['machine_activity'][] = array(
                        '',
                        '',
                        '',
                        '',
                        ''
                );
                $data['machine_activity'][] = array(
                        '',
                        '',
                        'Total',
                        $total_towing,
                        $total_glider
                );
                $data['total_glider'] = $total_glider;
                $data['total_towing'] = $total_towing;

                return $data;
        }

        /**
         * Affiche les résultats FFVV
         */
        public function ffvv() {
                $this->push_return_url("Rapports FFVV");

                $year = $this->session->userdata('year');
                $data = $this->ffvv_data($year);
                return load_last_view('rapports/ffvv', $data);
        }

        /*
     * Imprime le rapport FFVV en PDF
     */
        function pdf_ffvv() {
                $year = $this->session->userdata('year');
                $data = $this->ffvv_data($year);

                $this->load->library('Pdf');
                $pdf = new Pdf();
                $pdf->AddPage();
                $pdf->title($this->lang->line("gvv_rapports_title") . " $year", 1);
                $pdf->Output();
        }

        /**
         * Gérération du rapport dgac
         */
        public function dgac() {
                $this->load->model('vols_planeur_model');
                $this->load->model('event_model');

                $data = array();
                $this->push_return_url("Rapports DGAC");
                $year = $this->session->userdata('year');
                $date25 = date_m25ans($year);

                $data['year'] = $year;
                $data['year_selector'] = $this->vols_planeur_model->getYearSelector("vpdate");
                $data['controller'] = 'rapports';

                $data['association'] = $this->config->item('nom_club');
                $data['code'] = $this->config->item('code_club');

                $activity = array();
                $selections = array(
                        array(
                                'YEAR(vpdate)' => $year,
                                'categorie' => 0,
                                'mdaten >=' => $date25
                        ), // Francais - 25 ans
                        array(
                                'YEAR(vpdate)' => $year,
                                'categorie' => 0,
                                'mdaten <' => $date25
                        ), // Français +25 ans
                        array(
                                'YEAR(vpdate)' => $year,
                                'categorie' => 0
                        ), // Français
                        array(
                                'YEAR(vpdate)' => $year,
                                'categorie' => 2
                        ), // Etrangers
                        array(
                                'YEAR(vpdate)' => $year,
                                'categorie' => 1
                        )
                ) // Stagiaires
                ;

                $labels = array(
                        'Français -25 ans',
                        'Français +25 ans',
                        'Total Français',
                        'Etrangers',
                        'Stagiaires'
                );

                $event_selections = array(
                        array(
                                'YEAR(edate)' => $year,
                                'categorie' => 0,
                                'mdaten >=' => $date25
                        ), // Francais - 25 ans
                        array(
                                'YEAR(edate)' => $year,
                                'categorie' => 0,
                                'mdaten <' => $date25
                        ), // Français +25 ans
                        array(
                                'YEAR(edate)' => $year,
                                'categorie' => 0
                        ), // Français
                        array(
                                'YEAR(edate)' => $year,
                                'categorie' => 2
                        ), // Etrangers
                        array(
                                'YEAR(edate)' => $year,
                                'categorie' => 1
                        )
                ) // Stagiaires
                ;

                $remorque = 3;
                $treuille = 1;
                for ($i = 0; $i < count($selections); $i++) {
                        $selection = $selections[$i];
                        $event_selection = $event_selections[$i];
                        $line = array(
                                $labels[$i]
                        );
                        $line[] = sprintf("%4.2f", $this->vols_planeur_model->sum('vpduree', $selection) / 60);
                        $line[] = $this->vols_planeur_model->count(array_merge($selection, array(
                                'vpautonome' => $remorque
                        )));
                        $line[] = $this->vols_planeur_model->count(array_merge($selection, array(
                                'vpautonome' => $treuille
                        )));
                        $line[] = $this->event_model->count(array_merge($event_selection, array(
                                'name' => 'Laché planeur'
                        )));
                        $line[] = $this->event_model->count(array_merge($event_selection, array(
                                'name' => 'BPP'
                        )));
                        $line[] = $this->event_model->count(array_merge($event_selection, array(
                                'name' => 'BPP'
                        )));
                        $line[] = $this->event_model->count(array_merge($event_selection, array(
                                'name' => 'Campagne'
                        )));
                        $line[] = $this->vols_planeur_model->sum('vpnbkm', $selection);
                        $activity[] = $line;
                }
                // ajuste les totaux de remorqué (supprime autonomes et extérieur
                $data['activity'] = $activity;

                $data['machine_activity'] = array_merge($this->stat_per_planeur($year), $this->stat_per_avion($year));

                $total_glider = 0;
                $total_towing = 0;
                foreach ($data['machine_activity'] as $row) {
                        if ($row[4])
                                $total_glider += $row[4];
                        if ($row[3])
                                $total_towing += $row[3];
                }
                $data['machine_activity'][] = array(
                        '',
                        '',
                        '',
                        '',
                        ''
                );
                $data['machine_activity'][] = array(
                        '',
                        '',
                        'Total',
                        $total_towing,
                        $total_glider
                );
                $data['total_glider'] = $total_glider;
                $data['total_towing'] = $total_towing;
                return load_last_view('rapports/dgac', $data);

                $this->load->model('vols_planeur_model');
        }

        /**
         * Test unitaire
         */
        function test($format = "html") {
                $this->unit_test = TRUE;
                $this->load->library('unit_test');

                $this->unit->run(true, true, "Tests édition des rapports");
                $this->unit->XML_result("results/test_$controller.xml", "Test $controller");
                echo $this->unit->report();

                $this->unit->save_coverage();
        }
}
