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
    protected $rules = [
        'club' => "callback_section_selected",
        'masked' => "callback_check_masked_with_balance"
    ];
    protected $filter_variables = array(
        'filter_active',
        'filter_solde',
        'filter_masked'
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
            redirect($this->controller . "/balance");
            return;
        } else {
            // détruit en base
            $this->pre_delete($id);
            $this->gvv_model->delete(array(
                $this->kid => $id
            ));

            // réaffiche la liste (serait sympa de réafficher la même page)
            redirect($this->controller . "/balance");
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
        
        // For modification, calculate current balance and pass to view
        if ($action == MODIFICATION && isset($this->data['id'])) {
            $compte_id = $this->data['id'];
            $solde = $this->gvv_model->solde($compte_id);
            $this->data['compte_solde'] = $solde;
            $this->data['can_mask'] = ($solde == 0);
            
            // Disable masked checkbox if balance is not 0
            if ($solde != 0) {
                $this->gvvmetadata->set_field_attr('comptes', 'masked', 'disabled', 'disabled');
                $this->gvvmetadata->set_field_attr('comptes', 'masked', 'title', 
                    sprintf($this->lang->line('gvv_comptes_masked_warning'), 
                        number_format($solde, 2, ',', ' ')));
            }
        }
    }

    /**
     * Validation callback for masked field
     * A compte can only be masked if its balance is 0
     */
    public function check_masked_with_balance($masked_value) {
        // If not trying to mask (masked = 0), allow
        if (!$masked_value) {
            return TRUE;
        }
        
        // If trying to mask (masked = 1), check balance
        $compte_id = $this->input->post('id');
        if ($compte_id) {
            $solde = $this->gvv_model->solde($compte_id);
            if ($solde != 0) {
                $solde_formatted = number_format($solde, 2, ',', ' ');
                $msg = sprintf(
                    $this->lang->line('gvv_comptes_error_cannot_mask_non_zero_balance'),
                    $solde_formatted
                );
                $this->form_validation->set_message('check_masked_with_balance', $msg);
                return FALSE;
            }
        }
        
        return TRUE;
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
     * Retourne le filtre pour les comptes masqués
     * 0 = Tous les comptes
     * 1 = Comptes non masqués uniquement (défaut)
     * 2 = Comptes masqués uniquement
     */
    function filter_masked() {
        $filter_masked = $this->session->userdata('filter_masked');
        
        // Défaut: afficher uniquement les comptes non masqués
        if ($filter_masked === false || $filter_masked === null) {
            $filter_masked = 1;
            $this->session->set_userdata('filter_masked', $filter_masked);
        }
        
        $this->data['filter_masked'] = $filter_masked;
        return $filter_masked;
    }

    /**
     * Affiche une page de compte
     * $codec: classe de compte à afficher, ex 512= tous les comptes bancaires
     * $codec2: permet d'afficher entre deux classe ex: 4/5 = tous les comptes de classe 4
     * $detail: si 1 affiche la somme des débits et des crédits (utile
     * pour afficher la somme des créances et des dettes des comptes 400)
     */
    function page($codec = "", $codec2 = "", $detail = 0) {
        $this->push_return_url("comptes balance");

        $general = $this->session->userdata('general') && !$codec;

        $this->load_filter($this->filter_variables);
        $filter_solde = $this->filter_solde();
        $filter_masked = $this->filter_masked();

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

            $result = $this->gvv_model->select_page_general($selection, $this->data['balance_date'], $filter_solde, $filter_masked);
        } else {
            // détaillé
            $this->data['title_key'] = "gvv_comptes_title_detailed_balance";

            $result = $this->gvv_model->select_page($selection, $this->data['balance_date'], $filter_solde, $filter_masked);
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
     * Balance hiérarchique développable à la demande avec accordéons Bootstrap
     * Utilise des accordéons pour afficher la balance générale et détaillée
     * 
     * @param string $codec Code compte début (optionnel)
     * @param string $codec2 Code compte fin (optionnel)
     */
    function balance($codec = "", $codec2 = "") {
        $this->push_return_url("comptes balance");

        $this->load_filter($this->filter_variables);
        $filter_solde = $this->filter_solde();
        $filter_masked = $this->filter_masked();

        // gestion de la date d'affichage
        $balance_date = $this->session->userdata('balance_date');
        if ($balance_date) {
            $this->data['balance_date'] = $balance_date;
        } else {
            $this->data['balance_date'] = date('d/m/Y');
        }

        // selection des codec
        $selection = array();
        if ($codec != '') {
            $selection = "codec = \"$codec\"";
            if ($codec2 != "") {
                $selection = "codec >= \"$codec\" and codec < \"$codec2\"";
            }
        }

        $this->data['codec'] = $codec;
        $this->data['codec2'] = $codec2;
        $this->data['section'] = $this->gvv_model->section();
        $this->data['title_key'] = "gvv_comptes_title_hierarchical_balance";
        
        // Récupérer le paramètre start_expanded depuis la query string
        $this->data['start_expanded'] = ($this->input->get('start_expanded') === 'true');

        // Récupération de la balance générale
        $result_general = $this->gvv_model->select_page_general($selection, $this->data['balance_date'], $filter_solde, $filter_masked);
        
        // Organisation des comptes détaillés par codec
        $details_by_codec = array();
        foreach ($result_general as $general_row) {
            $codec_key = $general_row['codec'];
            // Récupération de la balance détaillée pour ce codec spécifique
            $selection_detail = "codec = \"$codec_key\"";
            $result_detail = $this->gvv_model->select_page($selection_detail, $this->data['balance_date'], $filter_solde, $filter_masked);
            $details_by_codec[$codec_key] = $result_detail;
        }

        $total = array(
            'debit' => 0,
            'credit' => 0,
            'solde_debit' => 0,
            'solde_credit' => 0
        );
        foreach ($result_general as $row) {
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

        $this->data['result_general'] = $result_general;
        $this->data['details_by_codec'] = $details_by_codec;
        $this->data['kid'] = $this->kid;
        $this->data['controller'] = $this->controller;
        $this->data['count'] = count($result_general);
        $this->data['premier'] = 0;
        $this->data['message'] = "";
        $this->data['has_modification_rights'] = (!isset($this->modification_level) || $this->dx_auth->is_role($this->modification_level, true, true));

        return load_last_view('comptes/bs_balanceView', $this->data, $this->unit_test);
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
        $csv_data[] = [$this->lang->line("comptes_bilan_resultat_avant_repartition")];
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
        $render_section($this->lang->line("comptes_bilan_resultat_avant_repartition"), isset($data['resultat']) ? $data['resultat'] : array(), 1);
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
            $this->pdf_resultat();
        }
    }

    /**
     * Export des résultats en PDF (page unique)
     */
    function pdf_resultat() {
        $year = $this->session->userdata('year');

        $this->load->library('Document', array('year' => $year));
        $this->document->pagesResultats($year);
        $this->document->generate();
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
     * Export CSV de la balance hiérarchique
     *
     * @param string $codec Code compte début
     * @param string $codec2 Code compte fin
     */
    function balance_hierarchical_csv($codec = '', $codec2 = "") {
        $filter_solde = $this->filter_solde();
        $filter_masked = $this->filter_masked();
        
        $titre = $this->lang->line("gvv_comptes_title_hierarchical_balance");
        $selection = array();
        
        if ($codec != '') {
            $selection = "codec = \"$codec\"";
            $titre .= ", " . $this->lang->line('comptes_label_class') . "=$codec";
            if ($codec2 != "") {
                $selection = "codec >= \"$codec\" and codec < \"$codec2\"";
                $titre .= " " . $this->lang->line('comptes_label_to') . " $codec2";
            }
        }

        $balance_date = $this->session->userdata('balance_date');
        if (!$balance_date) {
            $balance_date = date('d/m/Y');
        }
        $titre .= "=$balance_date";
        
        $section = $this->gvv_model->section();
        if ($section) {
            $titre .= " section " . $section['nom'];
        }

        $result_general = $this->gvv_model->select_page_general($selection, $balance_date, $filter_solde, $filter_masked);
        $result_detail = $this->gvv_model->select_page($selection, $balance_date, $filter_solde, $filter_masked);

        $details_by_codec = array();
        foreach ($result_detail as $row) {
            $codec_key = $row['codec'];
            if (!isset($details_by_codec[$codec_key])) {
                $details_by_codec[$codec_key] = array();
            }
            $details_by_codec[$codec_key][] = $row;
        }

        $merged_result = array();
        foreach ($result_general as $general_row) {
            $merged_result[] = array(
                'codec' => $general_row['codec'],
                'nom' => $general_row['nom'],
                'section_name' => '',
                'solde_debit' => isset($general_row['solde_debit']) ? $general_row['solde_debit'] : '',
                'solde_credit' => isset($general_row['solde_credit']) ? $general_row['solde_credit'] : ''
            );

            $codec_key = $general_row['codec'];
            if (isset($details_by_codec[$codec_key])) {
                $total_solde_debit = 0;
                $total_solde_credit = 0;
                $detail_count = count($details_by_codec[$codec_key]);

                foreach ($details_by_codec[$codec_key] as $detail_row) {
                    $merged_result[] = array(
                        'codec' => '  ' . $detail_row['codec'],
                        'nom' => '  ' . $detail_row['nom'],
                        'section_name' => $detail_row['section_name'],
                        'solde_debit' => isset($detail_row['solde_debit']) ? $detail_row['solde_debit'] : '',
                        'solde_credit' => isset($detail_row['solde_credit']) ? $detail_row['solde_credit'] : ''
                    );

                    // Calcul des totaux
                    if (isset($detail_row['solde_debit']) && $detail_row['solde_debit']) {
                        $total_solde_debit += $detail_row['solde_debit'];
                    }
                    if (isset($detail_row['solde_credit']) && $detail_row['solde_credit']) {
                        $total_solde_credit += $detail_row['solde_credit'];
                    }
                }

                // Ajouter la ligne de total si plus d'un compte dans le groupe
                if ($detail_count > 1) {
                    $merged_result[] = array(
                        'codec' => '',
                        'nom' => '',
                        'section_name' => 'Total',
                        'solde_debit' => $total_solde_debit ? $total_solde_debit : '',
                        'solde_credit' => $total_solde_credit ? $total_solde_credit : ''
                    );
                }
            }
        }

        $fields = array('codec', 'nom', 'section_name', 'solde_debit', 'solde_credit');
        $this->gvvmetadata->csv_table("vue_comptes", $merged_result, array(
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
     * Export PDF de la balance hiérarchique
     *
     * @param string $codec Code compte début
     * @param string $codec2 Code compte fin
     */
    function balance_hierarchical_pdf($codec = '', $codec2 = "") {
        $filter_solde = $this->filter_solde();
        $filter_masked = $this->filter_masked();
        
        $titre = $this->lang->line("gvv_comptes_title_hierarchical_balance");
        $section = $this->gvv_model->section();
        if ($section) {
            $titre .= " section " . $section['nom'];
        }
        
        $selection = array();
        if ($codec != '') {
            $selection = "codec = \"$codec\"";
            $titre .= ", " . $this->lang->line('comptes_label_class') . "=$codec";
            if ($codec2 != "") {
                $selection = "codec >= \"$codec\" and codec < \"$codec2\"";
                $titre .= " " . $this->lang->line('comptes_label_to') . " $codec2";
            }
        }

        $balance_date = $this->session->userdata('balance_date');
        if (!$balance_date) {
            $balance_date = date('d/m/Y');
        }
        $titre .= ", " . $this->lang->line("comptes_label_date") . "=$balance_date";

        $result_general = $this->gvv_model->select_page_general($selection, $balance_date, $filter_solde, $filter_masked);
        $result_detail = $this->gvv_model->select_page($selection, $balance_date, $filter_solde, $filter_masked);

        $details_by_codec = array();
        foreach ($result_detail as $row) {
            $codec_key = $row['codec'];
            if (!isset($details_by_codec[$codec_key])) {
                $details_by_codec[$codec_key] = array();
            }
            $details_by_codec[$codec_key][] = $row;
        }

        $merged_result = array();
        foreach ($result_general as $general_row) {
            $merged_result[] = array(
                'codec' => $general_row['codec'],
                'nom' => $general_row['nom'],
                'section_name' => '',
                'solde_debit' => isset($general_row['solde_debit']) ? $general_row['solde_debit'] : '',
                'solde_credit' => isset($general_row['solde_credit']) ? $general_row['solde_credit'] : '',
                'is_general' => true
            );

            $codec_key = $general_row['codec'];
            if (isset($details_by_codec[$codec_key])) {
                $total_solde_debit = 0;
                $total_solde_credit = 0;
                $detail_count = count($details_by_codec[$codec_key]);

                foreach ($details_by_codec[$codec_key] as $detail_row) {
                    $merged_result[] = array(
                        'codec' => '  ' . $detail_row['codec'],
                        'nom' => '  ' . $detail_row['nom'],
                        'section_name' => $detail_row['section_name'],
                        'solde_debit' => isset($detail_row['solde_debit']) ? $detail_row['solde_debit'] : '',
                        'solde_credit' => isset($detail_row['solde_credit']) ? $detail_row['solde_credit'] : '',
                        'is_general' => false
                    );

                    // Calcul des totaux
                    if (isset($detail_row['solde_debit']) && $detail_row['solde_debit']) {
                        $total_solde_debit += $detail_row['solde_debit'];
                    }
                    if (isset($detail_row['solde_credit']) && $detail_row['solde_credit']) {
                        $total_solde_credit += $detail_row['solde_credit'];
                    }
                }

                // Ajouter la ligne de total si plus d'un compte dans le groupe
                if ($detail_count > 1) {
                    $merged_result[] = array(
                        'codec' => '',
                        'nom' => '',
                        'section_name' => 'Total',
                        'solde_debit' => $total_solde_debit ? $total_solde_debit : '',
                        'solde_credit' => $total_solde_credit ? $total_solde_credit : '',
                        'is_general' => false,
                        'is_total' => true
                    );
                }
            }
        }

        $this->load->library('Pdf');
        $pdf = new Pdf();
        $pdf->set_title($titre);  // Set title before adding first page
        $pdf->AddPage();

        // Générer un PDF personnalisé avec couleurs différentes pour les entêtes
        $this->pdf_table_hierarchical_balance($merged_result, $pdf, $titre);
        
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

        // Build detailed explanation
        $explanation = "<div class='alert alert-info mb-4'>";
        $explanation .= "<h5><i class='fas fa-info-circle'></i> Que vérifie cette page ?</h5>";
        $explanation .= "<p class='mb-2'>Cette page vérifie que les <strong>soldes enregistrés</strong> dans la table <code>comptes</code> correspondent bien à la <strong>somme calculée des écritures</strong> pour chaque compte.</p>";
        $explanation .= "<p class='mb-2'><strong>Principe :</strong> Dans GVV, chaque compte a deux valeurs de solde :</p>";
        $explanation .= "<ol class='mb-2'>";
        $explanation .= "<li><strong>Solde stocké :</strong> Colonnes <code>debit</code> et <code>credit</code> dans la table <code>comptes</code> (optimisation pour performance)</li>";
        $explanation .= "<li><strong>Solde calculé :</strong> Somme de toutes les écritures dans la table <code>ecritures</code> pour ce compte</li>";
        $explanation .= "</ol>";
        $explanation .= "<p class='mb-2'><strong>Processus :</strong></p>";
        $explanation .= "<ul class='mb-2'>";
        $explanation .= "<li>Pour chaque compte, compare le solde stocké avec le solde calculé</li>";
        $explanation .= "<li>Si différence ≥ 0.01€ : corrige automatiquement et affiche l'anomalie</li>";
        $explanation .= "</ul>";
        $explanation .= "<p class='mb-0'><strong>Pourquoi ?</strong> L'enregistrement du solde dans les comptes est une optimisation, mais nécessite cette vérification pour garantir la cohérence en cas de problème de transactions.</p>";
        $explanation .= "</div>";

        // Build summary
        $summary = "<div class='alert alert-" . ($errors > 0 ? "warning" : "success") . " mb-3'>";
        $summary .= "<strong>Résultat :</strong> $cnt comptes vérifiés, $errors erreur(s) trouvée(s)";
        if ($errors > 0) {
            $summary .= " et corrigée(s) automatiquement.";
        }
        $summary .= "</div>";

        // Combine explanation, summary and details
        $full_text = $explanation . $summary . $msg;

        $data = array(
            'text' => $full_text,
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
            $this->bilan_pdf();
        }
    }

    /**
     * Export du bilan en PDF (page unique)
     */
    function bilan_pdf() {
        $year = $this->session->userdata('year');

        $this->load->library('Document', array('year' => $year));
        $this->document->pagesBilan($year);
        $this->document->generate();
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
        // Use specific header for clôture view: only 4 columns
        $table[] = $this->lang->line("comptes_cloture_list_header");

        foreach ($result as $row) {
            $table[] = array(
                $row['codec'],
                $row['nom'],
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
        $this->data['year_selector'] = $this->ecritures_model->getYearSelector("date_op");
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
     * Génère un tableau PDF personnalisé pour la balance hiérarchique
     * avec couleur de fond différente pour les entêtes de codec
     *
     * @param array $data Les données à afficher
     * @param object $pdf L'objet PDF
     * @param string $title Le titre du tableau
     */
    private function pdf_table_hierarchical_balance($data, $pdf, $title) {
        // Set title for header display on all pages
        $pdf->set_title($title);
        
        // Définir les colonnes et leurs largeurs
        $fields = array('codec', 'nom', 'section_name', 'solde_debit', 'solde_credit');
        $widths = array(12, 100, 20, 25, 25);
        $align = array('L', 'L', 'L', 'R', 'R');
        $height = 8;
        
        // En-tête du tableau
        $header_row = array();
        foreach ($fields as $field) {
            $header_row[] = $this->gvvmetadata->field_name('vue_comptes', $field);
        }
        
        // Définir la couleur de fond pour les en-têtes du tableau
        $pdf->SetFillColor(220, 220, 220); // Gris clair
        $pdf->row($widths, $height, $align, $header_row, 'LRTB', TRUE);
        
        // Corps du tableau
        foreach ($data as $row) {
            $table_row = array();
            
            // Formatage manuel des champs
            $table_row[] = isset($row['codec']) ? $row['codec'] : '';
            $table_row[] = isset($row['nom']) ? $row['nom'] : '';
            $table_row[] = isset($row['section_name']) ? $row['section_name'] : '';
            
            // Formatage des montants (logique similaire à array_field pour currency)
            $solde_debit = isset($row['solde_debit']) ? $row['solde_debit'] : '';
            $solde_credit = isset($row['solde_credit']) ? $row['solde_credit'] : '';
            
            if ($solde_debit !== '' && is_numeric($solde_debit)) {
                $table_row[] = euro($solde_debit, ',', 'pdf');
            } else {
                $table_row[] = '';
            }
            
            if ($solde_credit !== '' && is_numeric($solde_credit)) {
                $table_row[] = euro($solde_credit, ',', 'pdf');
            } else {
                $table_row[] = '';
            }
            
            // Apply bold and blue text for general account headers
            $is_general_header = isset($row['is_general']) && $row['is_general'];
            
            if ($is_general_header) {
                // Wrap all fields in bold tags
                for ($i = 0; $i < count($table_row); $i++) {
                    $table_row[$i] = '<b>' . $table_row[$i] . '</b>';
                }
                
                // General account headers: blue text in bold
                $pdf->SetTextColor(0, 0, 139); // Dark blue text
                $pdf->row($widths, $height, $align, $table_row, 'LRTB', FALSE);
                $pdf->SetTextColor(0, 0, 0); // Reset to black
            } else {
                // Detail rows: normal black text
                $pdf->SetTextColor(0, 0, 0); // Black text
                $pdf->row($widths, $height, $align, $table_row, 'LRTB', FALSE);
            }
        }
        
        // Reset colors to default
        $pdf->SetTextColor(0, 0, 0);
    }


    /**
     * Affiche le résultat d'exploitation par sections pour deux années consécutives
     * 
     * @param string $mode Mode d'affichage: 'html' (défaut), 'csv' ou 'pdf'
     */
    function resultat_par_sections($mode = 'html') {
        $this->data['controller'] = 'comptes';
        // Ensure model is loaded and year selector provided to view
        $this->load->model('ecritures_model');
        $year_selector = $this->ecritures_model->getYearSelector("date_op");
        $this->data['year_selector'] = is_array($year_selector) ? $year_selector : array();

        $year = $this->session->userdata('year');
        $this->data['year'] = $year;

        // Gestion de la date d'affichage
        // Affichage par exercice: utilise la session 'year' et fin d'exercice
        $this->data['balance_date'] = '31/12/' . $year;

        // Récupération des données pour deux années
        $html = ($mode == "html");
        $use_full_names = true; // Utiliser les noms complets partout (HTML, PDF, CSV)
        $tables = $this->gvv_model->select_resultat_par_sections_deux_annees($this->data['balance_date'], $html, $use_full_names);

        $this->data['charges'] = $tables['charges'];
        $this->data['produits'] = $tables['produits'];
        $this->data['resultat'] = $tables['resultat'];

        // Gestion des exports
        if ($mode == "csv") {
            $this->csv_resultat_par_sections($this->data);
            return;
        } else if ($mode == "pdf") {
            $this->pdf_resultat_par_sections($this->data);
            return;
        }

        $this->push_return_url("resultat_par_sections");

        load_last_view('comptes/bs_resultat_par_sectionsView', $this->data);
    }

    /**
     * Affiche le détail d'un codec par sections pour deux années consécutives
     *
     * @param string $codec Code comptable (ex: '606', '701')
     * @param string $mode Mode d'affichage: 'html' (défaut), 'csv' ou 'pdf'
     */
    function resultat_par_sections_detail($codec = '', $mode = 'html') {
        if (empty($codec)) {
            show_404();
            return;
        }

        $this->data['controller'] = 'comptes';
        $this->data['year_selector'] = $this->ecritures_model->getYearSelector("date_op");

        $year = $this->session->userdata('year');
        $this->data['year'] = $year;

        // Gestion de la date d'affichage
        $balance_date = $this->session->userdata('balance_date');
        if ($balance_date) {
            $this->data['balance_date'] = $balance_date;
        } else {
            $this->data['balance_date'] = '31/12/' . $year;
        }

        // Récupération du nom du codec depuis la table plan comptable
        $this->load->model('plan_comptable_model');
        $codec_info = $this->plan_comptable_model->get_by_id('pcode', $codec);
        $codec_nom = $codec_info && isset($codec_info['pdesc']) ? $codec_info['pdesc'] : $codec;

        $this->data['codec'] = $codec;
        $this->data['codec_nom'] = $codec_nom;

        // Déterminer si c'est une charge ou un produit
        $is_charge = (intval($codec) >= 600 && intval($codec) < 700);
        // Les charges sont affichées en positif (facteur = 1)
        $factor = 1;

        // Récupération des données de détail pour deux années (données brutes en float)
        $use_full_names = true; // Utiliser les noms complets partout (HTML, PDF, CSV)
        $detail = $this->gvv_model->select_detail_codec_deux_annees($codec, $this->data['balance_date'], $factor, $use_full_names);

        // Formatage selon le mode d'affichage
        // Colonnes: Code, Libellé, compte_id (caché), Section, Year N, Year N-1
        // Les colonnes numériques commencent à l'index 4
        $html = ($mode == "html");
        $detail = $this->gvv_model->format_numeric_columns($detail, 4, $html);

        // Pour CSV et PDF, supprimer la colonne compte_id (index 2) qui est cachée
        if ($mode == "csv" || $mode == "pdf") {
            $detail_export = array();
            foreach ($detail as $row_idx => $row) {
                $export_row = array();
                foreach ($row as $col_idx => $value) {
                    // Pour la ligne d'en-tête (index 0), tout garder sauf pas de colonne ID
                    // Pour les lignes de données, sauter la colonne compte_id (index 2)
                    if ($row_idx == 0) {
                        // En-tête: Code, Libellé, Section, N, N-1 (pas de compte_id)
                        $export_row[] = $value;
                    } else {
                        // Données: sauter compte_id à l'index 2
                        if ($col_idx == 2) continue;
                        $export_row[] = $value;
                    }
                }
                $detail_export[] = $export_row;
            }
            $this->data['detail'] = $detail_export;
        } else {
            $this->data['detail'] = $detail;
        }

        $this->data['is_charge'] = $is_charge;

        // Gestion des exports
        if ($mode == "csv") {
            $this->csv_resultat_par_sections_detail($this->data);
            return;
        } else if ($mode == "pdf") {
            $this->pdf_resultat_par_sections_detail($this->data);
            return;
        }

        $this->push_return_url("resultat_par_sections_detail");

        load_last_view('comptes/bs_resultat_par_sections_detailView', $this->data);
    }

    /**
     * Transforme les données avec en-têtes "Section Année" en format à deux lignes
     * Ligne 1: sections | Ligne 2: années
     * 
     * @param array $data Données brutes avec en-têtes comme "Avion 2025", "Avion 2024"
     * @param bool $skip_label_cols Si true, n'inclut pas Code/Comptes dans les en-têtes
     * @return array ['header_sections' => [...], 'header_years' => [...], 'rows' => [...]]
     */
    private function transform_to_two_line_header($data, $skip_label_cols = false) {
        if (empty($data)) {
            return ['header_sections' => [], 'header_years' => [], 'rows' => []];
        }

        $header = $data[0];
        $rows = array_slice($data, 1);

        // Parser l'en-tête pour extraire sections et années
        $sections = [];
        $current_section = null;
        
        $start_col = $skip_label_cols ? 0 : 2;
        for ($i = $start_col; $i < count($header); $i++) {
            $col_name = $header[$i];
            // Extraire section et année
            if (preg_match('/^(.+?)\s+(\d{4})$/', $col_name, $matches)) {
                $section_name = $matches[1];
                $year = $matches[2];
                
                if ($current_section === null || $current_section['name'] !== $section_name) {
                    // Sauvegarder la section précédente si elle existe
                    if ($current_section !== null) {
                        $sections[] = $current_section;
                    }
                    // Créer une nouvelle section
                    $current_section = ['name' => $section_name, 'years' => []];
                }
                $current_section['years'][] = $year;
            }
        }
        // Ajouter la dernière section
        if ($current_section !== null) {
            $sections[] = $current_section;
        }

        // Construire la ligne des sections
        $header_sections = [];
        if (!$skip_label_cols) {
            $header_sections[] = 'Code';
            $header_sections[] = 'Comptes';
        } else {
            $header_sections[] = '';
        }
        foreach ($sections as $section) {
            $header_sections[] = $section['name'];
            // Ajouter des cellules vides pour les années suivantes
            for ($i = 1; $i < count($section['years']); $i++) {
                $header_sections[] = '';
            }
        }

        // Construire la ligne des années
        $header_years = [];
        if (!$skip_label_cols) {
            $header_years[] = '';
            $header_years[] = '';
        } else {
            $header_years[] = '';
        }
        foreach ($sections as $section) {
            foreach ($section['years'] as $year) {
                $header_years[] = $year;
            }
        }

        return [
            'header_sections' => $header_sections,
            'header_years' => $header_years,
            'rows' => $rows,
            'sections' => $sections
        ];
    }

    /**
     * Export CSV du résultat par sections
     * 
     * @param array $data Données à exporter
     */
    function csv_resultat_par_sections($data) {
        $title = $this->lang->line("gvv_comptes_title_resultat_par_sections");

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

        // Helper pour ajouter une section avec entêtes à deux lignes
        $add_section = function($section_title, $section_data, $skip_label_cols) use (&$csv_data) {
            $csv_data[] = [];
            $csv_data[] = [$section_title];
            
            $transformed = $this->transform_to_two_line_header($section_data, $skip_label_cols);
            
            if (!empty($transformed['header_sections'])) {
                $csv_data[] = $transformed['header_sections'];
                $csv_data[] = $transformed['header_years'];
                $csv_data = array_merge($csv_data, $transformed['rows']);
            }
        };

        // Charges
        $add_section($this->lang->line("comptes_label_charges"), $data['charges'], false);

        // Produits
        $add_section($this->lang->line("comptes_label_produits"), $data['produits'], false);

        // Total (sans colonnes Code/Comptes)
        $add_section($this->lang->line("comptes_label_total"), $data['resultat'], true);

        csv_file($title, $csv_data);
    }

    /**
     * Export PDF du résultat par sections
     * 
     * @param array $data Données à exporter
     */
    function pdf_resultat_par_sections($data) {
        $title = $this->lang->line("gvv_comptes_title_resultat_par_sections");
        $this->load->library('Pdf');
        $pdf = new Pdf();

        // Paysage pour avoir plus de colonnes
        $pdf->AddPage('L');
        $pdf->title($title, 1);

        // Helper pour rendre une section avec en-têtes à deux lignes
        $render_section = function($section_title, $section_data, $skip_label_cols) use ($pdf) {
            if (empty($section_data)) return;
            
            $pdf->title($section_title, 2);
            $pdf->SetY($pdf->GetY() - 3);
            
            // Transformer les données
            $transformed = $this->transform_to_two_line_header($section_data, $skip_label_cols);
            
            if (empty($transformed['header_sections'])) return;
            
            // Calculer les largeurs de colonnes
            $usable = 270; // A4 paysage
            $num_data_cols = count($transformed['header_years']) - ($skip_label_cols ? 1 : 2);
            
            $widths = [];
            if (!$skip_label_cols) {
                $widths[] = 20;  // Code
                $widths[] = 60;  // Comptes
                $remaining = $usable - 80;
            } else {
                $widths[] = 40;  // Libellé (Charges/Produits/Total)
                $remaining = $usable - 40;
            }
            
            // Distribuer le reste pour les colonnes de données
            $col_width = $num_data_cols > 0 ? $remaining / $num_data_cols : 0;
            for ($i = 0; $i < $num_data_cols; $i++) {
                $widths[] = $col_width;
            }
            
            // Dessiner l'en-tête ligne 1 (sections avec colspan)
            $pdf->SetFont('DejaVu', 'B', 9);
            $pdf->SetFillColor(218, 227, 236); // Couleur des sections
            
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $col_idx = 0;
            
            // Colonnes Code/Comptes ou Libellé (sans bordure en bas car ligne 2 va la compléter)
            if (!$skip_label_cols) {
                $pdf->Cell($widths[0], 6, 'Code', 'LRT', 0, 'C', true);
                $pdf->Cell($widths[1], 6, 'Comptes', 'LRT', 0, 'C', true);
                $col_idx = 2;
            } else {
                $pdf->Cell($widths[0], 6, '', 'LRT', 0, 'C', true);
                $col_idx = 1;
            }
            
            // Sections
            foreach ($transformed['sections'] as $section) {
                $section_width = 0;
                foreach ($section['years'] as $year) {
                    $section_width += $widths[$col_idx];
                    $col_idx++;
                }
                $pdf->Cell($section_width, 6, $section['name'], 1, 0, 'C', true);
            }
            $pdf->Ln();
            
            // Dessiner l'en-tête ligne 2 (années)
            $pdf->SetFillColor(200, 217, 230); // Couleur année courante
            $col_idx = 0;

            if (!$skip_label_cols) {
                // Compléter les cellules Code/Comptes avec bordure en bas
                $pdf->SetFillColor(248, 249, 250);
                $pdf->Cell($widths[0], 6, '', 'LRB', 0, 'C', true);
                $pdf->Cell($widths[1], 6, '', 'LRB', 0, 'C', true);
                $col_idx = 2;
            } else {
                // Compléter la cellule de libellé avec bordure en bas
                $pdf->SetFillColor(248, 249, 250);
                $pdf->Cell($widths[0], 6, '', 'LRB', 0, 'C', true);
                $col_idx = 1;
            }
            
            // Années
            $year_idx = 0;
            foreach ($transformed['sections'] as $section) {
                foreach ($section['years'] as $year) {
                    // Alterner les couleurs (année courante / année précédente)
                    $fill_color = ($year_idx % 2 == 0) ? [200, 217, 230] : [245, 234, 212];
                    $pdf->SetFillColor($fill_color[0], $fill_color[1], $fill_color[2]);
                    $pdf->Cell($widths[$col_idx], 6, $year, 1, 0, 'C', true);
                    $col_idx++;
                    $year_idx++;
                }
            }
            $pdf->Ln();
            
            // Dessiner les lignes de données
            $pdf->SetFont('DejaVu', '', 8);
            foreach ($transformed['rows'] as $row_idx => $row) {
                $col_idx = 0;
                
                // Filtrer la première colonne vide si skip_label_cols
                if ($skip_label_cols) {
                    $row = array_slice($row, 1);
                }
                
                foreach ($row as $cell_idx => $cell) {
                    // Déterminer l'alignement et le fond
                    $align = ($cell_idx < ($skip_label_cols ? 1 : 2)) ? 'L' : 'R';
                    
                    if ($cell_idx < ($skip_label_cols ? 1 : 2)) {
                        // Colonnes de libellé : fond blanc
                        $pdf->SetFillColor(255, 255, 255);
                        $fill = true;
                    } else {
                        // Colonnes de données : alterner les couleurs
                        $data_col_idx = $cell_idx - ($skip_label_cols ? 1 : 2);
                        $fill_color = ($data_col_idx % 2 == 0) ? [227, 240, 247] : [254, 245, 231];
                        $pdf->SetFillColor($fill_color[0], $fill_color[1], $fill_color[2]);
                        $fill = true;
                    }
                    
                    // Alternance des lignes
                    if ($row_idx % 2 == 1) {
                        $pdf->SetFillColor(248, 248, 248);
                    }
                    
                    // Détection des valeurs négatives pour les colonnes numériques
                    $is_negative = false;
                    if ($cell_idx >= ($skip_label_cols ? 1 : 2)) {
                        // Nettoyer la valeur pour détecter les négatifs
                        $clean_value = str_replace([' ', '€'], '', $cell);
                        $clean_value = str_replace(',', '.', $clean_value);
                        if (is_numeric($clean_value) && floatval($clean_value) < 0) {
                            $is_negative = true;
                        }
                    }
                    
                    // Appliquer la couleur rouge pour les valeurs négatives
                    if ($is_negative) {
                        $pdf->SetTextColor(220, 53, 69); // Rouge Bootstrap danger
                        $pdf->SetFont('DejaVu', 'B', 8); // Gras
                    } else {
                        $pdf->SetTextColor(0, 0, 0); // Noir
                        $pdf->SetFont('DejaVu', '', 8); // Normal
                    }
                    
                    $pdf->Cell($widths[$col_idx], 5, $cell, 1, 0, $align, $fill);
                    $col_idx++;
                }
                $pdf->Ln();
            }
            
            // Réinitialiser la couleur du texte
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('DejaVu', '', 8);
            
            $pdf->Ln(6);
        };

        // Charges
        $render_section($this->lang->line("comptes_label_charges"), $data['charges'], false);
        
        // Nouvelle page avant Produits
        $pdf->AddPage('L');
        
        // Produits
        $render_section($this->lang->line("comptes_label_produits"), $data['produits'], false);
        
        // Nouvelle page avant Résultat
        $pdf->AddPage('L');
        
        // Résultat (sans Code/Comptes)
        $render_section($this->lang->line("comptes_label_total"), $data['resultat'], true);

        $pdf->Output();
    }

    /**
     * Export CSV du détail d'un codec par sections
     * 
     * @param array $data Données à exporter
     */
    function csv_resultat_par_sections_detail($data) {
        $title = sprintf($this->lang->line("gvv_comptes_title_resultat_par_sections_detail"), $data['codec'] . ' - ' . $data['codec_nom']);

        $csv_data = array();
        $csv_data[] = [$this->config->item('nom_club')];
        // Structure: Code, Libellé, Section, N, N-1 (5 colonnes)
        $csv_data[] = array(
            $this->lang->line("comptes_label_date"),
            $data['balance_date'],
            '',
            '',
            ''
        );

        $csv_data[] = [];
        $section_label = $data['is_charge'] ? $this->lang->line("comptes_label_charges") : $this->lang->line("comptes_label_produits");
        $csv_data[] = [$section_label . ' - ' . $data['codec'] . ' ' . $data['codec_nom']];
        $csv_data = array_merge($csv_data, $data['detail']);

        csv_file($title, $csv_data);
    }

    /**
     * Export PDF du détail d'un codec par sections
     * 
     * @param array $data Données à exporter
     */
    function pdf_resultat_par_sections_detail($data) {
        $title = sprintf($this->lang->line("gvv_comptes_title_resultat_par_sections_detail"), $data['codec'] . ' - ' . $data['codec_nom']);
        $this->load->library('Pdf');
        $pdf = new Pdf();

        // Paysage pour avoir plus de colonnes
        $pdf->AddPage('L');
        $pdf->title($title, 1);

        // Helper pour calculer les largeurs dynamiques
        // Structure: Code (20), Libellé (70), Section (60), Year N (60), Year N-1 (60)
        $compute_widths = function($cols, $leadingCols = 3) {
            $usable = 270;
            $w = array();
            if ($cols <= 0) return $w;
            
            if ($leadingCols == 3) {
                // Nouvelle structure: Code, Libellé, Section
                $w0 = 20;  // code
                $w1 = 70;  // nom
                $w2 = 60;  // section
                $w[] = $w0; 
                $w[] = $w1; 
                $w[] = $w2;
                $remain = $usable - $w0 - $w1 - $w2;
                $rest = max(0, $cols - 3);
                $each = ($rest > 0) ? ($remain / $rest) : 0;
                for ($i = 0; $i < $rest; $i++) $w[] = $each;
            } else if ($leadingCols == 2) {
                // Ancienne structure: Code, Libellé (pour compatibilité)
                $w0 = 20;  // code
                $w1 = 90;  // nom
                $w[] = $w0; 
                $w[] = $w1;
                $remain = $usable - $w0 - $w1;
                $rest = max(0, $cols - 2);
                $each = ($rest > 0) ? ($remain / $rest) : 0;
                for ($i = 0; $i < $rest; $i++) $w[] = $each;
            }
            return $w;
        };

        $compute_align = function($cols, $leadingCols = 3) {
            $align = array();
            for ($i = 0; $i < $cols; $i++) {
                if ($i < $leadingCols) $align[] = 'L'; else $align[] = 'R';
            }
            return $align;
        };

        $section_label = $data['is_charge'] ? $this->lang->line("comptes_label_charges") : $this->lang->line("comptes_label_produits");
        
        if (!empty($data['detail'])) {
            $pdf->title($section_label . ' - ' . $data['codec'] . ' ' . $data['codec_nom'], 2);
            $pdf->SetY($pdf->GetY() - 3);
            $cols = count($data['detail'][0]);
            $pdf->table($compute_widths($cols, 3), 6, $compute_align($cols, 3), $data['detail']);
        }

        $pdf->Output();
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
