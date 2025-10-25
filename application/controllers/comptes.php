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
 * @filesource comptes.php
 * @package controllers
 *
 * controleur de gestion des comptes.
 */
set_include_path(getcwd() . "/..:" . get_include_path());
include_once('application/libraries/Gvv_Controller.php');

class Comptes extends Gvv_Controller {
    protected $controller = 'comptes';
    protected $model = 'comptes_model';
    protected $modification_level = 'tresorier';

    // régles de validation
    protected $rules = ['club' => "callback_section_selected"];
    protected $filter_variables = array(
        'filter_active',
        'filter_solde'
    );

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();

        $this->load->model('plan_comptable_model');
        $this->load->model('ecritures_model');
        $this->load->model('clotures_model');
        $this->load->helper('csv');
        $this->lang->load('comptes');
    }

    /**
     * Supprime un élèment
     */
    function delete($id) {
        $this->load->model('ecritures_model');
        $count = $this->ecritures_model->count_all($id);
        if ($count) {
            $this->session->set_flashdata('popup', "Suppression $id non authorisée $count lignes dans le compte");
            redirect($this->controller . "/page");
            return;
        } else {
            // détruit en base
            $this->pre_delete($id);
            $this->gvv_model->delete(array(
                $this->kid => $id
            ));

            // réaffiche la liste (serait sympa de réafficher la même page)
            redirect($this->controller . "/page");
        }
    }

    /**
     * Génération des éléments à passer au formulaire en cas de création,
     * modification ou réaffichage après erreur.
     *
     * @param
     *            $actions
     */
    function form_static_element($action) {
        parent::form_static_element($action);
        // @todo supprimer après validation
        $this->data['codec_selector'] = $this->plan_comptable_model->selector();
        $this->data['compte_selector'] = $this->gvv_model->selector();
        $this->data['saisie_par'] = $this->dx_auth->get_username();
        $pil_selector = $this->membres_model->selector_with_null();
        $this->data['pil_selector'] = $pil_selector;

        $this->gvvmetadata->set_selector('codec_selector', $this->plan_comptable_model->selector());
        $this->gvvmetadata->set_selector('compte_selector', $this->gvv_model->selector());
        $this->gvvmetadata->set_selector('pilote_selector', $pil_selector);
    }

    /**
     * Active ou désactive le filtrage
     */
    public function filterValidation() {

        // echo "in filterValidation " . $this->session->userdata('return_url') . br();
        $this->active_filter($this->filter_variables);

        $this->pop_return_url();
    }

    /**
     * Retourne la selection format ActiveData utilisable par les requêtes
     * SQL pour filtrer les données en fonction des choix faits par l'utilisateur
     * dans la section de filtrage.
     */
    function filter_solde() {
        $this->data['filter_active'] = $this->session->userdata('filter_active');

        $filter_solde = 0;
        if ($this->session->userdata('filter_solde')) {
            $filter_solde = $this->session->userdata('filter_solde');
        }

        return $filter_solde;
    }

    /**
     * Affiche une page de compte
     * $codec: classe de compte à afficher, ex 512= tous les comptes bancaires
     * $codec2: permet d'afficher entre deux classe ex: 4/5 = tous les comptes de classe 4
     * $detail: si 1 affiche la somme des débits et des crédits (utile
     * pour afficher la somme des créances et des dettes des comptes 400)
     */
    function page($codec = "", $codec2 = "", $detail = 0) {
        $this->push_return_url("comptes page");

        $general = $this->session->userdata('general') && !$codec;

        $this->load_filter($this->filter_variables);
        $filter_solde = $this->filter_solde();

        // gestion de la date d'affichage
        $balance_date = $this->session->userdata('balance_date');
        if ($balance_date) {
            $this->data['balance_date'] = $balance_date;
        } else {
            $this->data['balance_date'] = date('d/m/Y');
        }
        $date_op = date_ht2db($balance_date);

        // selection des codec
        $titre = "";
        $selection = array();
        if ($codec != '') {
            // $selection['codec'] = $codec;
            $selection = "codec = \"$codec\"";
            if ($codec2 != "") {
                $selection = "codec >= \"$codec\" and codec < \"$codec2\"";
            }
        }

        $this->data['codec'] = $codec;
        $this->data['codec2'] = $codec2;
        $this->data['general'] = $general;
        $this->data['section'] = $this->gvv_model->section();

        if ($general) {
            // général
            $this->data['title_key'] = "gvv_comptes_title_balance";

            $result = $this->gvv_model->select_page_general($selection, $this->data['balance_date'], $filter_solde);
        } else {
            // détaillé
            $this->data['title_key'] = "gvv_comptes_title_detailed_balance";

            $result = $this->gvv_model->select_page($selection, $this->data['balance_date'], $filter_solde);
        }

        $total = array(
            'debit' => 0,
            'credit' => 0,
            'solde_debit' => 0,
            'solde_credit' => 0
        );
        foreach ($result as $row) {
            foreach (
                array(
                    'debit',
                    'credit',
                    'solde_debit',
                    'solde_credit'
                ) as $field
            ) {
                if (is_numeric($row[$field])) {
                    $total[$field] += $row[$field];
                }
            }
        }
        $this->data['total'] = $total;
        $this->data['detail'] = $detail;

        $this->data['select_result'] = $result;
        $this->data['kid'] = $this->kid;
        $this->data['controller'] = $this->controller;
        $this->data['count'] = $this->gvv_model->count();
        $this->data['premier'] = 0;
        $this->data['message'] = "";
        $this->data['has_modification_rights'] = (!isset($this->modification_level) || $this->dx_auth->is_role($this->modification_level, true, true));

        // Ici l'URL de retour est toujours correct, donc écrasé après ...
        // echo "# " . $this->session->userdata('return_url');
        return load_last_view($this->table_view, $this->data, $this->unit_test);
    }

    /*
     * Affiche la balance en comptablitié générale
     */
    function general() {
        $this->session->set_userdata('general', true);
        $this->page();
    }

    /*
     * Affiche la balance en comptablitié détaillé
     */
    function detail($codec = "", $codec2 = "", $detail = 0) {
        $this->session->set_userdata('general', false);
        $this->page($codec, $codec2, $detail);
    }

    /**
     * Balance des comptes
     */
    function view($codec = '') {
        $this->page($codec);
    }

    /**
     * Affiche le résultat de l'exercice'
     */
    function resultat() {
        $this->data['controller'] = "comptes";
        $this->data['year_selector'] = $this->ecritures_model->getYearSelector("date_op");

        $this->data['year'] = $this->session->userdata('year');
        $this->data['resultat_table'] = $this->ecritures_model->resultat_table($this->ecritures_model->select_resultat(), true, nbs(6), '.');

        $this->data['section'] = $this->gvv_model->section();

        $this->push_return_url("resultat");

        load_last_view('comptes/resultatView', $this->data);
    }

    /**
     * Affiche un résultat synthétique de l'exercice
     * 
     * Charges par sections
     * Produits par section
     * Résultat avant répartition
     * 
     * Disponible
     *      Créances de tiers
     *      Comptes de banque et financier
     *
     * Dettes
     *      Dettes envers des tiers
     *      Emprunts bancaires
     */
    function dashboard($mode = "html") {

        $this->data['controller'] = "comptes";
        $this->data['year_selector'] = $this->ecritures_model->getYearSelector("date_op");

        $year = $this->session->userdata('year');
        $this->data['year'] = $year;

        // gestion de la date d'affichage
        $balance_date = $this->session->userdata('balance_date');
        if ($balance_date) {
            $this->data['balance_date'] = $balance_date;
        } else {
            $this->data['balance_date'] = date('d/m/Y');
        }

        $html = ($mode == "html");
        $tables = $this->gvv_model->select_charges_et_produits($this->data['balance_date'], $html);
        $this->data['charges'] = $tables['charges'];
        $this->data['produits'] = $tables['produits'];
        $this->data['resultat'] = $tables['resultat'];

        $this->data['disponible'] = $tables['disponible'];
        $this->data['dettes'] = $tables['dettes'];

        $this->data['immos'] = $tables['immos'];

        if ($mode == "csv") {
            $this->csv_dashboard($this->data);
            return;
        } else if ($mode == "pdf") {
            $this->pdf_dashboard($this->data);
            return;
        }
        $this->push_return_url("resultat");

        load_last_view('comptes/dashboardView', $this->data);
    }

    /**
     * Export du dashboard en CSV
     */
    function csv_dashboard($data) {

        // echo "<pre>";
        // print_r($data);
        // echo "</pre>";
        // exit;

        $title = $this->lang->line("gvv_comptes_title_dashboard");

        $csv_data = array();
        $csv_data[] = [$this->config->item('nom_club')];
        $csv_data[] = array(
            $this->lang->line("comptes_label_date"),
            $data['balance_date'],
            '',
            '',
            '',
            ''
        );

        $csv_data[] = [];
        $csv_data[] = ["Charges par sections"];
        $csv_data = array_merge($csv_data, $data['charges']);

        $csv_data[] = [];
        $csv_data[] = ["Produits par sections"];
        $csv_data = array_merge($csv_data, $data['produits']);

        $csv_data[] = [];
        $csv_data[] = ["Résultat avant répartition"];
        $csv_data = array_merge($csv_data, $data['resultat']);

        $csv_data[] = [];
        $csv_data[] = ["Actifs financiers"];
        $csv_data = array_merge($csv_data, $data['disponible']);

        $csv_data[] = [];
        $csv_data[] = ["Dettes"];
        $csv_data = array_merge($csv_data, $data['dettes']);

        $csv_data[] = [];
        $csv_data[] = ["Immobilisations"];
        $csv_data = array_merge($csv_data, $data['immos']);

        csv_file($title, $csv_data);
    }

    /**
     * Export du dashboard en PDF
     */
    function pdf_dashboard($data) {
        $title = $this->lang->line("gvv_comptes_title_dashboard");
        $this->load->library('Pdf');
        $pdf = new Pdf();

        // Landscape to fit more columns
        $pdf->AddPage('L');
        $pdf->title($title, 1);

        // Helper to compute dynamic widths
        $compute_widths = function($cols, $leadingCols = 2) {
            // Landscape A4: ~277mm usable width, stay a bit smaller for margins
            $usable = 270;
            $w = array();
            if ($cols <= 0) return $w;
            if ($leadingCols == 2) {
                $w0 = 20; // code or first column
                $w1 = 90; // name/label
                $w[] = $w0; $w[] = $w1;
                $remain = $usable - $w0 - $w1;
                $rest = max(0, $cols - 2);
                $each = ($rest > 0) ? ($remain / $rest) : 0;
                for ($i = 0; $i < $rest; $i++) $w[] = $each;
            } else if ($leadingCols == 1) {
                $w0 = 60;
                $w[] = $w0;
                $remain = $usable - $w0;
                $rest = max(0, $cols - 1);
                $each = ($rest > 0) ? ($remain / $rest) : 0;
                for ($i = 0; $i < $rest; $i++) $w[] = $each;
            } else {
                $each = $usable / $cols;
                for ($i = 0; $i < $cols; $i++) $w[] = $each;
            }
            return $w;
        };
        $compute_align = function($cols, $leadingCols = 2) {
            $align = array();
            for ($i = 0; $i < $cols; $i++) {
                if ($i < $leadingCols) $align[] = 'L'; else $align[] = 'R';
            }
            return $align;
        };

        // Small helper to standardize spacing: minimal gap before table, larger after
        $render_section = function($section_title, $table_data, $leadingCols) use ($pdf, $compute_widths, $compute_align) {
            if (empty($table_data)) return;
            $pdf->title($section_title, 2);
            // Reduce space after title (move up a bit)
            $pdf->SetY($pdf->GetY() - 3);
            $cols = count($table_data[0]);
            $pdf->table($compute_widths($cols, $leadingCols), 6, $compute_align($cols, $leadingCols), $table_data);
            // Add larger space after table before next title
            $pdf->Ln(6);
        };

        // Charges
        $render_section("Charges par sections", isset($data['charges']) ? $data['charges'] : array(), 2);
        // Produits
        $render_section("Produits par sections", isset($data['produits']) ? $data['produits'] : array(), 2);
        // Résultat
        $render_section("Résultat avant répartition", isset($data['resultat']) ? $data['resultat'] : array(), 1);
        // Actifs financiers
        $render_section("Actifs financiers", isset($data['disponible']) ? $data['disponible'] : array(), 1);
        // Dettes
        $render_section("Dettes", isset($data['dettes']) ? $data['dettes'] : array(), 1);
        // Immos
        $render_section("Immobilisations", isset($data['immos']) ? $data['immos'] : array(), 1);

        $pdf->Output();
    }


    /**
     * Activé par la barre de boutons de résultats
     */
    function export_resultat($mode = "csv") {
        if ($mode == "csv") {
            $this->csv_resultat();
        } else {
            redirect(controller_url("rapports/pdf_resultats"));
        }
    }

    /**
     * Export des résultats en CSV
     */
    function csv_resultat() {
        $title = $this->lang->line("gvv_comptes_title_resultat");
        $resultat = $this->ecritures_model->select_resultat();
        
        // Generate the same table data as shown in HTML and PDF
        $resultat_table = $this->ecritures_model->resultat_table($resultat, false, '', ',', 'csv');
        
        $csv_data = array();
        
        // Add header with date
        $csv_data[] = array(
            $this->lang->line("comptes_label_date"),
            $resultat['balance_date'],
            '',
            '',
            '',
            '',
            '',
            '',
            ''
        );
        
        // Add empty line
        $csv_data[] = array('', '', '', '', '', '', '', '', '');
        
        // Add the actual table data
        foreach ($resultat_table as $row) {
            $csv_data[] = $row;
        }

        csv_file($title, $csv_data);
    }

    /**
     * Résultats par catégories
     */
    function csv_resultat_categories() {
        $this->select_categorie();

        $annee_exercise = $this->data['annee_exercise'];

        $csv_data = array();
        $csv_data[] = array(
            "Résultat par catégories",
            $annee_exercise
        );
        $csv_data[] = array(
            "Catégorie",
            "Compte",
            "Code",
            "Montant",
            "Total"
        );

        foreach ($this->data['results'] as $row) {
            $annee_exercise = $row['annee_exercise'];
            $categorie = $row['categorie'];
            $nom_compte1 = $row['nom_compte1'];
            $code1 = $row['code1'];
            $total = number_format($row['total'], 2, ",", "");
            $csv_data[] = array(
                $categorie,
                $nom_compte1,
                $code1,
                $total
            );
        }

        csv_file("Résultat par catégories", $csv_data);
    }

    /**
     * Résultat
     */
    function select_categorie() {
        $this->data = array();
        $this->data['annee_exercise'] = date("Y");
        $this->data['nom_club'] = $this->config->item('nom_club');
        $this->data['results'] = $this->ecritures_model->select_categorie('code1 >= "6" and code1 < "7"');
    }

    /**
     * Résultat par catégorie
     *
     * @deprecated
     *
     */
    function resultat_categorie() {
        $this->select_categorie();
        // print_r($this->data['results']);
        $this->data['annee_exercise'] = date("Y");
        $this->data['controller'] = "comptes";
        load_last_view('comptes/resultatCategorie', $this->data);
    }

    /**
     * Affiche la trésorerie, écart entre les dépenses et les recettes
     */
    function tresorerie() {
        $this->data['controller'] = "comptes";
        $this->data['year_selector'] = $this->ecritures_model->getYearSelector("date_op");

        $this->data['year'] = $this->session->userdata('year');
        $this->data['jsonurl'] = site_url() . '/' . $this->controller . '/ajax_cumuls';
        $this->data['title'] = $this->lang->line("gvv_comptes_title_cash");

        $this->push_return_url("tresorerie");

        load_last_view('comptes/tresorerie', $this->data);
    }

    /**
     * Retourne les informations pour le cumul
     */
    function ajax_cumuls() {
        $year = $this->session->userdata('year');

        $json = $this->ecritures_model->json_resultat($year);
        echo $json;
    }

    /**
     * Balance des comptes
     *
     * @param
     *            $comptes
     */
    function balance_csv($codec = '', $codec2 = "") {
        $general = $this->session->userdata('general');

        // selection des codec
        // Titre
        $titre = $this->lang->line("gvv_comptes_title_balance");

        $selection = array();
        if ($codec != '') {
            // $selection['codec'] = $codec;
            $selection = "codec = \"$codec\"";
            $titre .= ", " . $this->lang->line('comptes_label_class') . "=$codec";
            if ($codec2 != "") {
                $selection = "codec >= \"$codec\" and codec < \"$codec2\"";
                $titre .= " " . $this->lang->line('comptes_label_to') . " $codec2";
            }
        } else {
            $this->data['codec'] = '';
        }

        // gestion de la date d'affichage
        $balance_date = $this->session->userdata('balance_date');
        if (!$balance_date) {
            $balance_date = date('d/m/Y');
        }
        $titre .= "=$balance_date";
        $section = $this->gvv_model->section();
        if ($section) {
            $titre .= " section " . $section['nom'];
        }

        if ($general) {
            $result = $this->gvv_model->select_page_general($selection, $balance_date);
            $fields = array(
                'codec',
                'nom',
                'section_name',
                'solde_debit',
                'solde_credit'
            );
        } else {
            $result = $this->gvv_model->select_page($selection, $balance_date);
            $fields = array(
                'codec',
                'id',
                'section_name',
                'solde_debit',
                'solde_credit'
            );
        }

        $this->gvvmetadata->csv_table("vue_comptes", $result, array(
            'title' => $titre,
            'fields' => $fields
        ));
    }

    /**
     * Soldes pilotes
     */
    function balance_pdf($codec = '', $codec2 = "") {
        $general = $this->session->userdata('general');
        // selection des codec
        // Titre
        $titre = $this->lang->line("gvv_comptes_title_balance");
        $section = $this->gvv_model->section();
        if ($section) {
            $titre .= " section " . $section['nom'];
        }
        $selection = array();
        if ($codec != '') {
            // $selection['codec'] = $codec;
            $selection = "codec = \"$codec\"";
            $titre .= ", " . $this->lang->line('comptes_label_class') . "=$codec";
            if ($codec2 != "") {
                $selection = "codec >= \"$codec\" and codec < \"$codec2\"";
                $titre .= " " . $this->lang->line('comptes_label_to') . " $codec2";
            }
        } else {
            $this->data['codec'] = '';
        }

        // gestion de la date d'affichage
        $balance_date = $this->session->userdata('balance_date');
        if (!$balance_date) {
            $balance_date = date('d/m/Y');
        }
        $titre .= ", " . $this->lang->line("comptes_label_date") . "=$balance_date";

        if ($general) {
            $result = $this->gvv_model->select_page_general($selection, $balance_date);
            $fields = array(
                'codec',
                'nom',
                'solde_debit',
                'solde_credit'
            );
        } else {
            $result = $this->gvv_model->select_page($selection, $balance_date);
            $fields = array(
                'codec',
                'id',
                'solde_debit',
                'solde_credit'
            );
        }
        $this->load->library('Pdf');
        $pdf = new Pdf();
        $pdf->AddPage();
        $this->gvvmetadata->pdf_table("vue_comptes", $result, $pdf, array(
            'title' => $titre,
            'width' => array(
                12,
                50,
                30,
                30
            ),
            'fields' => $fields
        ));
        $pdf->Output();
    }

    /**
     * Bilan
     */
    function bilan() {
        $this->push_return_url("bilan");

        $year = $this->session->userdata('year');

        $bilan = $this->gvv_model->select_all_for_bilan($year);
        $bilan_prec = $this->gvv_model->select_all_for_bilan($year - 1);
        $this->data = $bilan;
        $this->data['bilan_table'] = bilan_table($bilan, $bilan_prec, true);
        $this->data['section'] = $this->gvv_model->section();

        load_last_view('comptes/bilanView', $this->data);
    }

    /**
     * Export du bilan en CSV
     *
     * @param
     *            $comptes
     */
    function bilan_csv() {
        $year = $this->session->userdata('year');
        $bilan = $this->gvv_model->select_all_for_bilan($year);
        $bilan_prec = $this->gvv_model->select_all_for_bilan($year - 1);
        // bilan table not in HTML
        $bilan_table = bilan_table($bilan, $bilan_prec, false);

        $year = $this->session->userdata('year');
        $section = $this->gvv_model->section();

        $title = $this->lang->line('gvv_comptes_title_bilan');
        if ($section) {
            $title .= " section " . $section['nom'];
        }
        csv_file($title . " $year", $bilan_table);
    }

    /**
     * Vérifie que les soldes des comptes enregistrés dans les comptes correspondent bien
     * à la somme des opérations sur ces comptes.
     * L'enregistrement du solde dans les comptes
     * peut-être considéré comme une optimisation, mais c'est un risque si le moteur de la base de données
     * ne gre pas correctement les transactions. (Cas chez free).
     *
     * Only available to authorized user (fpeignot)
     */
    function check() {
        // Check if user is authorized (fpeignot only)
        if ($this->dx_auth->get_username() !== 'fpeignot') {
            show_error('Cette fonction est réservée aux administrateurs autorisés', 403, 'Accès refusé');
            return;
        }

        $selection = array();

        $result = $this->gvv_model->select_page($selection, "31/12/2013", 0);
        $cnt = 0;
        $errors = 0;
        $msg = "";
        foreach ($result as $row) {
            $cnt++;
            $id = $row['id'];
            $date = date("Y-m-d");
            $actif = $row['actif'];

            // echo $row['nom'] . ' ' . $actif . ' ' . $row['debit'] . ' ' . $row['credit'] . br();
            $solde_compte = $row['credit'] - $row['debit'];
            if ($actif) {
                $credit = $this->ecritures_model->select_emploi_compte($date, $id);
                $debit = $this->ecritures_model->select_ressource_compte($date, $id);
            } else {
                $debit = $this->ecritures_model->select_emploi_compte($date, $id);
                $credit = $this->ecritures_model->select_ressource_compte($date, $id);
            }
            $solde = $credit - $debit;
            if ($actif)
                $solde = -$solde;
            if (abs($solde_compte - $solde) >= 0.01) {
                $errors++;
                // var_dump($row);
                $msg .= $row['nom'];
                $msg .= " solde compte=$solde_compte, solde ecritures=$solde" . br();
                $changes = ($actif) ? array(
                    'debit' => $credit,
                    'credit' => $debit
                ) : array(
                    'debit' => $debit,
                    'credit' => $credit
                );
                $this->db->where('id', $row['id']);
                $this->db->update('comptes', $changes);
            }
        }

        // Build summary at the beginning
        $summary = "<div class='alert alert-" . ($errors > 0 ? "warning" : "success") . " mb-3'>";
        $summary .= "<strong>Résultat :</strong> $cnt comptes vérifiés, $errors erreur(s) trouvée(s)";
        if ($errors > 0) {
            $summary .= " et corrigée(s) automatiquement.";
        }
        $summary .= "</div>";

        // Prepend summary to the message
        $msg = $summary . $msg;

        $data = array(
            'text' => $msg,
            'title' => "Vérification de la cohérence des comptes"
        );
        load_last_view('message', $data);
    }

    /**
     * Callback pour les boutons de stat
     *
     * @param unknown_type $year
     */
    function export_bilan($mode = "csv") {
        if ($mode == "csv") {
            $this->bilan_csv();
        } else {
            redirect(controller_url("rapports/bilan"));
        }
    }

    /*
     * Recalcul la balance pour la nouvelle date
     */
    function new_balance_date($jour, $mois, $annee) {
        $date = "$jour/$mois/$annee";
        gvv_debug("Balance date = $date");

        $this->session->set_userdata('balance_date', $date);
        $this->session->set_userdata('year', $annee);

        $this->pop_return_url();
    }

    /**
     * Retourne une liste de comptes
     *
     * @param unknown $selection
     */
    private function liste_comptes($selection, $date) {
        $result = $this->gvv_model->select_page($selection, $date);
        $table = array();
        $table[] = $this->lang->line("comptes_list_header");;

        foreach ($result as $row) {
            $table[] = array(
                $row['codec'],
                $row['nom'],
                euro($row['debit']),
                euro($row['credit']),
                euro($row['solde_debit']),
                euro($row['solde_credit'])
            );
        }
        return $table;
    }

    /**
     * Retourne une liste de comptes
     *
     * @param unknown $selection
     */
    private function comptes_de_resultat($selection, $date) {
        $result = $this->gvv_model->select_page($selection, $date);

        $exedant = 0;
        $deficit = 0;
        foreach ($result as $row) {
            if ($row['codec'] == 120)
                $exedant = $row['id'];
            if ($row['codec'] == 129)
                $deficit = $row['id'];
        }
        return array(
            '120' => $exedant,
            '129' => $deficit
        );
    }

    /**
     * passe les écritures d'intégration
     *
     * @param unknown $selection
     */
    private function ecritures_integration($selection, $date, $year, $capital, $comment) {
        $result = $this->gvv_model->select_page($selection, $date);
        // var_dump($result);

        /*
         * Array ( [id] => 0
         * [annee_exercise] => 2011
         * [date_creation] => 2011-03-20
         * [date_op] => 2011-03-20
         * [compte1] => 6
         * [compte2] => 7
         * [montant] => 100
         * [description] => heure de vol en fauconet
         * [num_cheque] => xxx
         * [saisie_par] => fpeignot )
         * [club] => 1
         */

        $section = $this->gvv_model->section();
        $line = array(
            'annee_exercise' => $year,
            'date_creation' => $date,
            'date_op' => $date,
            'description' => $comment,
            'num_cheque' => "Clôture exercice $year",
            'saisie_par' => $this->dx_auth->get_username(),
            'club' => $section['id']
        );

        foreach ($result as $row) {

            if ($row['solde_debit'] > 0.001) {
                $line['montant'] = $row['solde_debit'];
                $line['compte2'] = $row['id'];
                $line['compte1'] = $capital;
                $this->ecritures_model->create_ecriture($line);
            }

            if ($row['solde_credit'] > 0.001) {
                $line['montant'] = $row['solde_credit'];
                $line['compte1'] = $row['id'];
                $line['compte2'] = $capital;
                $this->ecritures_model->create_ecriture($line);
            }
        }
    }

    /**
     * passe les écritures d'intégration
     *
     * @param unknown $selection
     */
    private function charges_integration($selection, $date, $year, $debiteur, $crediteur, $comment) {
        $result = $this->gvv_model->select_page($selection, $date);
        // var_dump($result);

        $section = $this->gvv_model->section();
        $line = array(
            'annee_exercise' => $year,
            'date_creation' => $date,
            'date_op' => $date,
            'description' => $comment,
            'num_cheque' => "Clôture exercice $year",
            'saisie_par' => $this->dx_auth->get_username(),
            'club' => $section['id']
        );

        foreach ($result as $row) {

            if ($row['solde_debit'] > 0.001) {
                $line['montant'] = $row['solde_debit'];
                $line['compte2'] = $row['id'];
                $line['compte1'] = $debiteur;
                $this->ecritures_model->create_ecriture($line);
            }

            if ($row['solde_credit'] > 0.001) {
                $line['montant'] = $row['solde_credit'];
                $line['compte1'] = $row['id'];
                $line['compte2'] = $crediteur;
                $this->ecritures_model->create_ecriture($line);
            }
        }
    }

    /**
     * Clôture de l'exercice
     */
    function cloture($action = MODIFICATION) {

        // remplissage des dates
        $balance_date = $this->session->userdata('balance_date');
        if (!$balance_date) {
            $balance_date = date("d/m/Y");
        }
        $year = substr($balance_date, 6, 4);
        $date_fin = "31/12/$year";
        $db_date_fin = "$year-12-31";
        $date_gel = $this->clotures_model->freeze_date(true);

        $section = $this->gvv_model->section();

        $this->data['controller'] = 'comptes';
        $this->data['action'] = $action;
        $this->data['balance_date'] = $balance_date;
        $this->data['year'] = $year;
        $this->data['date_fin'] = $date_fin;
        $this->data['date_gel'] = $date_gel;
        $this->data['section'] = $section;

        if ($this->clotures_model->before_freeze_date($db_date_fin)) {
            $error = $this->lang->line("comptes_cloture_impossible") . " $date_gel.";
        } else {
            $error = "";
        }

        $error_120 = $this->lang->line("comptes_cloture_error_120");
        $error_129 = $this->lang->line("comptes_cloture_error_129");

        $where = [];
        if ($section) $where['club'] = $section['id'];
        $where["codec"] = "120";
        if ($this->gvv_model->count($where) == 0)  $error .= "<BR>$error_120";
        $where["codec"] = "129";
        if ($this->gvv_model->count($where) == 0)  $error .= "<BR>$error_129";

        $this->data['error'] = $error;

        // compte de fonds associatif ou capital à utiliser pour intégrer le résultat et les ecarts
        // des exercices précédants.
        $where = array(
            "codec >=" => "10",
            'codec <' => "11"
        );
        if ($section) $where['club'] = $section['id'];

        // $this->gvvmetadata->set_selector('capital_selector', $this->gvv_model->selector($where));
        $this->data['capital_selector'] = $this->gvv_model->selector($where);
        $this->data['capital'] = '';

        // liste_compte prend déjà la section active en compte
        $this->data['a_integrer'] = $this->liste_comptes("codec >= \"110\" and codec < \"130\"", $balance_date);
        $this->data['charges'] = $this->liste_comptes("codec >= \"6\" and codec < \"7\"", $balance_date);
        $this->data['produits'] = $this->liste_comptes("codec >= \"7\" and codec < \"8\"", $balance_date);

        if ($action == VALIDATION) {

            $this->load->model('comptes_model');

            $date_op = date_ht2db($balance_date);

            $capital = $this->input->post('capital');
            $comment = $this->lang->line("comptes_cloture") . " $year, " . $this->lang->line("comptes_cloture_reintegration_resultat") . " ($year -1)";
            $this->ecritures_integration("codec >= \"110\" and codec < \"130\"", $date_op, $year, $capital, $comment);

            $cpts_resultat = $this->comptes_de_resultat("codec >= \"110\" and codec < \"130\"", $date_op);
            $resultat_debiteur = $cpts_resultat['129'];
            $resultat_crediteur = $cpts_resultat['120'];

            $comment = $this->lang->line("comptes_cloture") . " $year, " . $this->lang->line("comptes_cloture_raz_charges");
            $this->charges_integration("codec >= \"6\" and codec < \"7\"", $date_op, $year, $resultat_debiteur, $resultat_crediteur, $comment);
            $comment = $this->lang->line("comptes_cloture") . " $year, " . $this->lang->line("comptes_cloture_raz_produits");

            $this->charges_integration("codec >= \"7\" and codec < \"8\"", $date_op, $year, $resultat_debiteur, $resultat_crediteur, $comment);

            // Modifie la date de gel
            $description = "Clôture $year - " . $section['nom'];
            $this->clotures_model->create_freeze_date($db_date_fin, $description);

            redirect($this->controller . "/cloture/" . VISUALISATION);
        }
        return load_last_view($this->controller . "/cloture", $this->data, $this->unit_test);
    }
    /**
     * Test unitaire
     */
    function test($format = "html") {
        // parent::test($format);
        $this->unit_test = TRUE;
        $this->load->library('unit_test');

        $this->unit->run(true, true, "Tests $this->controller");
        $this->tests_results($format);
    }
}
