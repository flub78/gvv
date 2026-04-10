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
 *    This library genrates the Pdf documents. It has been centralised as
 *    several documents may include the same pages.
 *
 */
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();

class Document {
    protected $pdf;
    protected $year;
    protected $CI;

    /**
     * Constructor
     */
    function __construct($args = array()) {
        // parent::__construct();
        $this->CI = &get_instance();
        $this->CI->load->library('Pdf');
        $this->CI->load->helper('validation');
        $this->CI->lang->load('comptes');
        $this->CI->lang->load('compta');
        $this->CI->load->model('ecritures_model');
        $this->CI->load->model('comptes_model');
        $this->CI->load->model('sections_model');


        // Il est obligatoire de vérifier l'existence du paramètre parce que
        // Le chargement de la librairie crée un objet par défaut (sans paramères)
        if (array_key_exists('year', $args))
            $this->year = $args['year'];

        $this->pdf = new Pdf();
        $nom_club = $this->CI->config->item('nom_club');
        $this->pdf->SetTitle($nom_club);
        // Don't set $this->pdf->title here - each page method should set its own title
        $this->pdf->AliasNbPages();
    }

    /**
     * Calcul le titre en fonction de la section
     */
    function title($language_key, $year) {
        $section = $this->CI->gvv_model->section();
        $title = $this->CI->lang->line($language_key);
        if ($section) {
            $title .= " section " . $section['nom'];
        }
        $title .= " " . $year;
        return $title;
    }

    function pagesResultats($year) {

        $title = $this->title("gvv_comptes_title_resultat", $year);

        $this->pdf->set_title($title);
        $this->pdf->AddPage();
        $this->pdf->title($title, 2);

        $resultat = $this->CI->ecritures_model->select_resultat();
        $tab = $this->CI->ecritures_model->resultat_table($resultat, false, '', ',', 'pdf');

        $w = array(
            7,   // Code expenses
            37,  // Label expenses (increased from 36)
            10,  // Section expenses
            20,  // Year amount expenses - fits "99 999,99 €"
            20,  // Previous year expenses - same width as current year
            2,   // Separator (reduced from 4 - minimal spacing)
            7,   // Code earnings
            37,  // Label earnings (increased from 36)
            10,  // Section earnings
            20,  // Year amount earnings - same width as expenses
            20   // Previous year earnings - same width as current year
        );
        $align = array(
            'R',  // Code expenses
            'L',  // Label expenses
            'L',  // Section expenses
            'R',  // Year amount expenses
            'R',  // Previous year expenses
            'C',  // Separator
            'R',  // Code earnings
            'L',  // Label earnings
            'L',  // Section earnings
            'R',  // Year amount earnings
            'R'   // Previous year earnings
        );
        $border = array(
            'LRTB',  // Code expenses
            'LRTB',  // Label expenses
            'LRTB',  // Section expenses
            'LRTB',  // Year amount expenses
            'LRTB',  // Previous year expenses
            'LR',    // Separator - only left/right borders (no top/bottom)
            'LRTB',  // Code earnings
            'LRTB',  // Label earnings
            'LRTB',  // Section earnings
            'LRTB',  // Year amount earnings
            'LRTB'   // Previous year earnings
        );
        $this->pdf->table($w, 5, $align, $tab, $border);  // Pass custom border array
    }

    function pagesResultatsAvecDepreciation($year) {
        $title = $this->title("gvv_comptes_title_resultat_avec_depreciation", $year);

        $this->pdf->set_title($title);
        $this->pdf->AddPage();
        $this->pdf->title($title, 2);

        $resultat = $this->CI->ecritures_model->select_resultat_avec_depreciation();
        $tab = $this->CI->ecritures_model->resultat_avec_depreciation_table($resultat, false, '', ',', 'pdf');

        $w = array(
            7,   // Code charges
            37,  // Label charges
            10,  // Section charges
            20,  // Année N charges
            20,  // Année N-1 charges
            2,   // Séparateur
            7,   // Code produits
            37,  // Label produits
            10,  // Section produits
            20,  // Année N produits
            20   // Année N-1 produits
        );
        $align = array('R', 'L', 'L', 'R', 'R', 'C', 'R', 'L', 'L', 'R', 'R');
        $border = array(
            'LRTB', 'LRTB', 'LRTB', 'LRTB', 'LRTB',
            'LR',
            'LRTB', 'LRTB', 'LRTB', 'LRTB', 'LRTB'
        );
        $this->pdf->table($w, 5, $align, $tab, $border);
    }

    /**
     * Génération de la page résultats par catégorie
     * @param unknown_type $year
     */
    function pagesResultatsCategorie($year) {
        $title = $this->title("gvv_comptes_title_resultat", $year) . " par catégorie";
        $this->pdf->set_title($title);
        $this->pdf->AddPage();
        $this->pdf->title($title, 2);

        $results = $this->CI->ecritures_model->select_categorie('code1 >= "6" and code1 < "7"');

        $tab = array();
        $tab[0] = array(
            "Catégorie",
            "Code",
            "Compte",
            "Montant"
        );

        $i = 0;
        foreach ($results as $row) {
            $fld = 0;
            $i++;
            // $tab[$i][$fld++] = $row['annee_exercise'];
            $tab[$i][$fld++] = $row['categorie'];
            // $tab[$i][$fld++] = $row['compte1'];
            $tab[$i][$fld++] = $row['code1'];
            $tab[$i][$fld++] = $row['nom_compte1'];
            $tab[$i][$fld++] = $row['total'];
        }

        $w = array(
            30,
            20,
            50,
            20,
            20,
            20,
            50,
            18
        );
        $align = array(
            'L',
            'R',
            'L',
            'R',
            'R',
            'L',
            'R'
        );

        $this->pdf->table($w, 8, $align, $tab);
    }

    /**
     * génère la balance des comptes
     */
    function pagesBalance($year) {

        $balance_date = "31/12/$year";

        $title = $this->title("gvv_comptes_title_balance", $balance_date);

        $this->pdf->set_title($title);
        $this->pdf->AddPage('P');

        $this->pdf->title($title, 1);

        $this->CI->session->set_userdata('balance_date', $balance_date);

        // Use hierarchical balance like balance_hierarchical_pdf
        $filter_solde = false;  // No filter for the global report
        $filter_masked = false;
        $selection = array();

        $result_general = $this->CI->comptes_model->select_page_general($selection, $balance_date, $filter_solde, $filter_masked);
        $result_detail = $this->CI->comptes_model->select_page($selection, $balance_date, $filter_solde, $filter_masked);

        // Group detail rows by codec
        $details_by_codec = array();
        foreach ($result_detail as $row) {
            $codec_key = $row['codec'];
            if (!isset($details_by_codec[$codec_key])) {
                $details_by_codec[$codec_key] = array();
            }
            $details_by_codec[$codec_key][] = $row;
        }

        // Merge general and detail rows
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
                foreach ($details_by_codec[$codec_key] as $detail_row) {
                    $merged_result[] = array(
                        'codec' => '  ' . $detail_row['codec'],
                        'nom' => '  ' . $detail_row['nom'],
                        'section_name' => $detail_row['section_name'],
                        'solde_debit' => isset($detail_row['solde_debit']) ? $detail_row['solde_debit'] : '',
                        'solde_credit' => isset($detail_row['solde_credit']) ? $detail_row['solde_credit'] : '',
                        'is_general' => false
                    );
                }
            }
        }

        // Generate hierarchical balance table
        $this->pdf_table_hierarchical_balance($merged_result, $this->pdf);
    }

    /**
     * Génération des pages des licenciés
     * @param unknown_type $year
     */
    function pagesLicences($year) {

        $title = $this->CI->lang->line("gvv_achats_title_year") . "Licenciés $year";
        $this->pdf->set_title($title);
        $this->pdf->AddPage('P');
        $this->pdf->title($title, 1);

        $this->CI->load->model('membres_model');
        $this->CI->membres_model->select_licences();
        $this->CI->gvvmetadata->pdf("membres", $this->pdf, array(
            'fields' => array(
                'nom_prenom',
                'madresse',
                'cp',
                'ville',
                'mdaten'
            ),
            'numbered' => 1,
            'width' => array(
                6,
                50,
                50,
                20,
                40,
                30
            )
        ));
    }


    /**
     * Génération des pages pour un compte
     * @param unknown_type $year
     */
    function pagesCompte($year, $compte, $nom, $date_solde, $solde, $first_day, $solde_avant) {

        // We do not want to include a new page for each account
        // Just skip two lines
        $this->pdf->Ln();
        $this->pdf->Ln();
        $this->pdf->title($this->CI->lang->line("gvv_compta_title_entries")
            . " $nom " . $this->CI->lang->line("gvv_year") . " $year", 1);

        $results = $this->CI->ecritures_model->select_journal($compte);

        $this->CI->gvvmetadata->store_table("ecritures", $results);

        $str = $this->CI->lang->line("gvv_compta_label_balance_at") . " $first_day";
        if ($solde_avant < 0) {
            $str .= " " . $this->CI->lang->line("gvv_compta_label_debitor") . " = ";
            $str .= abs(euro($solde_avant));
        } else {
            $str .= " " . $this->CI->lang->line("gvv_compta_label_creditor") . " = ";
            $str .= euro($solde_avant);
        }
        $this->pdf->printl($str);

        $this->CI->gvvmetadata->pdf("ecritures", $this->pdf, array(
            'fields' => array(
                'date_op',
                'code2',
                'nom_compte2',
                'description',
                'num_cheque',
                'debit',
                'credit'
            ),
            'numbered' => FALSE,
            'width' => array(
                18,
                9,
                30,
                70,
                34,
                18,
                18
            )
        ));
        $str = "$nom " . $this->CI->lang->line("gvv_compta_label_balance_at") . " $date_solde";
        if ($solde < 0) {
            $str .= " " . $this->CI->lang->line("gvv_compta_label_debitor") . " = ";
            $str .= abs(euro($solde));
        } else {
            $str .= " " . $this->CI->lang->line("gvv_compta_label_creditor") . " = ";
            $str .= euro($solde);
        }

        $this->pdf->printl($str);
    }

    /**
     * Génération des pages pour tous les comptes
     * @param unknown_type $year
     */
    function pagesComptes($year) {

        $title = $this->title("gvv_comptes_title_journaux", $year);

        $this->pdf->set_title($title);
        $this->pdf->AddPage('P');
        for ($i = 0; $i < 5; $i++) {
            $this->pdf->Ln();
        }
        $this->pdf->title($this->CI->config->item('nom_club'));
        $this->pdf->title($title, 1);
        $this->pdf->AddPage('P');

        $first_day = "01/01/$year";
        $date = "31/12/$year";
        $results = $this->CI->comptes_model->select_page(array(), $date);
        foreach ($results as $row) {
            $compte = $row['id'];
            $nom = $row['nom'];
            $solde_avant =  $this->CI->ecritures_model->solde_compte($compte, $first_day, "<");
            $solde = $this->CI->ecritures_model->solde_compte($compte, $date);
            $this->pagesCompte($year, $compte, $nom, $date, $solde, $first_day, $solde_avant);
        }
    }

    /**
     * Entête du rapport
     * @param unknown_type $year
     */
    function pagesRapportFinancier($year) {


        $section = $this->CI->gvv_model->section();
        $title = $this->CI->lang->line("gvv_comptes_title_financial");
        if ($section) {
            $title .= " section " . $section['nom'];
        }
        $title .= " " . $year;

        $this->pdf->set_title($title);
        $this->pdf->AddPage('P');
        for ($i = 0; $i < 5; $i++) {
            $this->pdf->Ln();
        }
        $this->pdf->title($this->CI->config->item('nom_club'));
        $this->pdf->title($title, 1);
        $this->pdf->Ln();

        $this->pdf->title("                    " . $this->title("gvv_comptes_title_resultat", $year), 2);
        $this->pdf->title("                    " . $this->title("gvv_comptes_title_bilan", $year), 2);
        $this->pdf->title("                    " . $this->title("gvv_comptes_title_balance", $year), 2);
        $this->pdf->title("                    " . $this->title("gvv_comptes_title_sales", $year), 2);
    }

    /**
     * Génération des pages pour tous les ventes
     * @param unknown_type $year
     */
    function pagesVentes($year) {

        $this->CI->lang->load('achats');

        $title = $this->title("gvv_achats_title_year", $year);
        $this->pdf->set_title($title);
        $this->pdf->AddPage('P');
        $this->pdf->title($title, 1);
        // $this->pdf->AddPage('P');

        // Fetch the data
        $this->CI->load->model('achats_model');
        $this->CI->achats_model->list_per_year($year);

        $attrs = array(
            'numbered' => FALSE,
            'fields' => array(
                'produit',
                'prix_unit',
                'quantite',
                'prix'
            ),
            'width' => array(
                50,
                25,
                20,
                25
            )
        );
        $this->CI->gvvmetadata->pdf("vue_achats_per_year", $this->pdf, $attrs);
    }

    /**
     * Génération des pages pour tous les ventes
     * @param unknown_type $year
     */
    function pagesBilan($year) {

        $title = $this->title('gvv_comptes_title_bilan', $year);

        $this->pdf->set_title($title);
        $this->pdf->AddPage('P');
        $this->pdf->title($title, 1);

        $bilan = $this->CI->comptes_model->select_all_for_bilan($year);
        $bilan_prec = $this->CI->comptes_model->select_all_for_bilan($year - 1);

        $build_actif_detail = function ($bilan_data) {
            $disponibilites = -$this->CI->gvv_model->total_of($bilan_data['dispo']);

            $immobilisations_corporelles = array(
                'brut' => $bilan_data['valeur_brute_immo_corp'],
                'amort' => $bilan_data['amortissements_corp'],
                'net' => $bilan_data['valeur_nette_immo_corp'],
            );

            $immobilisations_financieres = array(
                'brut' => $bilan_data['prets'],
                'amort' => 0,
                'net' => $bilan_data['prets'],
            );

            $total_actif_immobilise = array(
                'brut' => $immobilisations_corporelles['brut'] + $immobilisations_financieres['brut'],
                'amort' => $immobilisations_corporelles['amort'] + $immobilisations_financieres['amort'],
                'net' => $immobilisations_corporelles['net'] + $immobilisations_financieres['net'],
            );

            $creances_tiers = array(
                'brut' => $bilan_data['creances_pilotes'],
                'amort' => 0,
                'net' => $bilan_data['creances_pilotes'],
            );

            $dispo = array(
                'brut' => $disponibilites,
                'amort' => 0,
                'net' => $disponibilites,
            );

            $total_actif_circulant = array(
                'brut' => $creances_tiers['brut'] + $dispo['brut'],
                'amort' => 0,
                'net' => $creances_tiers['net'] + $dispo['net'],
            );

            return array(
                'immobilisations_corporelles' => $immobilisations_corporelles,
                'immobilisations_financieres' => $immobilisations_financieres,
                'total_actif_immobilise' => $total_actif_immobilise,
                'creances_tiers' => $creances_tiers,
                'disponibilites' => $dispo,
                'total_actif_circulant' => $total_actif_circulant,
                'total_actif' => $bilan_data['total_actif'],
            );
        };

        $build_passif_detail = function ($year_data, $bilan_data) {
            $date_op = $year_data . '-12-31';

            $reserves = $this->CI->gvv_model->total_of($this->CI->ecritures_model->select_solde($date_op, 106, 107, TRUE));
            $subventions_investissement = $this->CI->gvv_model->total_of($this->CI->ecritures_model->select_solde($date_op, 13, 14, TRUE));

            $provisions_risques = $this->CI->gvv_model->total_of($this->CI->ecritures_model->select_solde($date_op, 151, 156, TRUE));
            $provisions_charges = $this->CI->gvv_model->total_of($this->CI->ecritures_model->select_solde($date_op, 157, 159, TRUE));

            $avances_membres = $bilan_data['dettes_pilotes'];
            $dettes_financieres = $bilan_data['emprunts'];

            $fonds_propres_sans_droit_reprise = $bilan_data['fonds_associatifs'] + $bilan_data['reports_cred'] + $bilan_data['reports_deb'];

            $total_fonds_reportes_dedies =
                $fonds_propres_sans_droit_reprise +
                $reserves +
                $bilan_data['resultat'] +
                $subventions_investissement;

            $total_provisions = $provisions_risques + $provisions_charges;
            $total_dettes = $avances_membres + $dettes_financieres;

            return array(
                'fonds_propres_sans_droit_reprise' => $fonds_propres_sans_droit_reprise,
                'reserves' => $reserves,
                'resultat' => $bilan_data['resultat'],
                'subventions_investissement' => $subventions_investissement,
                'total_fonds_reportes_dedies' => $total_fonds_reportes_dedies,
                'provisions_risques' => $provisions_risques,
                'provisions_charges' => $provisions_charges,
                'total_provisions' => $total_provisions,
                'avances_membres' => $avances_membres,
                'dettes_financieres' => $dettes_financieres,
                'total_dettes' => $total_dettes,
                'total_passif' => $bilan_data['total_passif'],
            );
        };

        $actif_detail_n = $build_actif_detail($bilan);
        $actif_detail_n1 = $build_actif_detail($bilan_prec);
        $passif_detail_n = $build_passif_detail($year, $bilan);
        $passif_detail_n1 = $build_passif_detail($year - 1, $bilan_prec);

        $year_n = (int)$year;
        $year_n1 = $year_n - 1;

        $non_zero = function ($value) {
            return abs((float)$value) >= 0.005;
        };

        $show_line = function ($line_n, $line_n1) use ($non_zero) {
            return $non_zero($line_n['brut']) || $non_zero($line_n['amort']) || $non_zero($line_n['net']) || $non_zero($line_n1['net']);
        };

        $actif_data = array();
        $actif_data[] = array('<b>Actif immobilise</b>', '<b></b>', '<b></b>', '<b></b>', '<b></b>');

        if ($show_line($actif_detail_n['immobilisations_corporelles'], $actif_detail_n1['immobilisations_corporelles'])) {
            $actif_data[] = array(
                'Immobilisations corporelles',
                euro($actif_detail_n['immobilisations_corporelles']['brut'], ',', 'pdf'),
                euro($actif_detail_n['immobilisations_corporelles']['amort'], ',', 'pdf'),
                euro($actif_detail_n['immobilisations_corporelles']['net'], ',', 'pdf'),
                euro($actif_detail_n1['immobilisations_corporelles']['net'], ',', 'pdf')
            );
        }

        if ($show_line($actif_detail_n['immobilisations_financieres'], $actif_detail_n1['immobilisations_financieres'])) {
            $actif_data[] = array(
                'Immobilisations financieres',
                euro($actif_detail_n['immobilisations_financieres']['brut'], ',', 'pdf'),
                euro($actif_detail_n['immobilisations_financieres']['amort'], ',', 'pdf'),
                euro($actif_detail_n['immobilisations_financieres']['net'], ',', 'pdf'),
                euro($actif_detail_n1['immobilisations_financieres']['net'], ',', 'pdf')
            );
        }

        $actif_data[] = array(
            '<b>Total actif immobilise</b>',
            '<b>' . euro($actif_detail_n['total_actif_immobilise']['brut'], ',', 'pdf') . '</b>',
            '<b>' . euro($actif_detail_n['total_actif_immobilise']['amort'], ',', 'pdf') . '</b>',
            '<b>' . euro($actif_detail_n['total_actif_immobilise']['net'], ',', 'pdf') . '</b>',
            '<b>' . euro($actif_detail_n1['total_actif_immobilise']['net'], ',', 'pdf') . '</b>'
        );

        $actif_data[] = array('<b>Actif circulant</b>', '<b></b>', '<b></b>', '<b></b>', '<b></b>');

        if ($show_line($actif_detail_n['creances_tiers'], $actif_detail_n1['creances_tiers'])) {
            $actif_data[] = array(
                'Creances de tiers',
                euro($actif_detail_n['creances_tiers']['brut'], ',', 'pdf'),
                euro($actif_detail_n['creances_tiers']['amort'], ',', 'pdf'),
                euro($actif_detail_n['creances_tiers']['net'], ',', 'pdf'),
                euro($actif_detail_n1['creances_tiers']['net'], ',', 'pdf')
            );
        }

        if ($show_line($actif_detail_n['disponibilites'], $actif_detail_n1['disponibilites'])) {
            $actif_data[] = array(
                'Disponibilites',
                euro($actif_detail_n['disponibilites']['brut'], ',', 'pdf'),
                euro($actif_detail_n['disponibilites']['amort'], ',', 'pdf'),
                euro($actif_detail_n['disponibilites']['net'], ',', 'pdf'),
                euro($actif_detail_n1['disponibilites']['net'], ',', 'pdf')
            );
        }

        $actif_data[] = array(
            '<b>Total actif circulant</b>',
            '<b>' . euro($actif_detail_n['total_actif_circulant']['brut'], ',', 'pdf') . '</b>',
            '<b>' . euro($actif_detail_n['total_actif_circulant']['amort'], ',', 'pdf') . '</b>',
            '<b>' . euro($actif_detail_n['total_actif_circulant']['net'], ',', 'pdf') . '</b>',
            '<b>' . euro($actif_detail_n1['total_actif_circulant']['net'], ',', 'pdf') . '</b>'
        );

        $actif_data[] = array(
            '<b>Total actif</b>',
            '<b></b>',
            '<b></b>',
            '<b>' . euro($actif_detail_n['total_actif'], ',', 'pdf') . '</b>',
            '<b>' . euro($actif_detail_n1['total_actif'], ',', 'pdf') . '</b>'
        );

        $this->pdf->title('Bilan Actif', 2);
        $actif_width = array(80, 27.5, 27.5, 27.5, 27.5);
        $actif_height = 8;
        $actif_align = array('L', 'R', 'R', 'R', 'R');

        // Header row 1 with merged year cell across columns 2, 3 and 4.
        $this->pdf->SetFont('DejaVu', 'B', 6);
        $this->pdf->Cell($actif_width[0], $actif_height, 'Actif', 'LRT', 0, 'L');
        $this->pdf->Cell($actif_width[1] + $actif_width[2] + $actif_width[3], $actif_height, "31/12/$year_n", 'LRTB', 0, 'C');
        $this->pdf->Cell($actif_width[4], $actif_height, "31/12/$year_n1", 'LRTB', 1, 'C');

        // Header row 2; first cell completes the rowspan effect of "Actif".
        $this->pdf->Cell($actif_width[0], $actif_height, '', 'LRB', 0, 'L');
        $this->pdf->Cell($actif_width[1], $actif_height, 'Brut', 'LRTB', 0, 'R');
        $this->pdf->Cell($actif_width[2], $actif_height, 'Amort. et depr.', 'LRTB', 0, 'R');
        $this->pdf->Cell($actif_width[3], $actif_height, 'Net', 'LRTB', 0, 'R');
        $this->pdf->Cell($actif_width[4], $actif_height, 'Net', 'LRTB', 1, 'R');

        $this->pdf->SetFont('DejaVu', '', 6);

        foreach ($actif_data as $row) {
            $this->pdf->row($actif_width, $actif_height, $actif_align, $row);
        }
        $this->pdf->Ln(4);

        $passif_data = array();
        $passif_data[] = array('Passif', "31/12/$year_n", "31/12/$year_n1");

        $passif_rows = array(
            array('Fonds propres sans droit de reprise', $passif_detail_n['fonds_propres_sans_droit_reprise'], $passif_detail_n1['fonds_propres_sans_droit_reprise']),
            array('Reserves', $passif_detail_n['reserves'], $passif_detail_n1['reserves']),
            array('Resultat', $passif_detail_n['resultat'], $passif_detail_n1['resultat']),
            array('Subventions d\'investissement', $passif_detail_n['subventions_investissement'], $passif_detail_n1['subventions_investissement']),
            array('Total des fonds reportes et dedies', $passif_detail_n['total_fonds_reportes_dedies'], $passif_detail_n1['total_fonds_reportes_dedies']),
            array('Provisions pour risques', $passif_detail_n['provisions_risques'], $passif_detail_n1['provisions_risques']),
            array('Provisions pour charges', $passif_detail_n['provisions_charges'], $passif_detail_n1['provisions_charges']),
            array('Total des provisions', $passif_detail_n['total_provisions'], $passif_detail_n1['total_provisions']),
            array('Dettes envers des tiers', $passif_detail_n['avances_membres'], $passif_detail_n1['avances_membres']),
            array('Dettes financieres', $passif_detail_n['dettes_financieres'], $passif_detail_n1['dettes_financieres']),
            array('Total des dettes', $passif_detail_n['total_dettes'], $passif_detail_n1['total_dettes']),
            array('Total du passif', $passif_detail_n['total_passif'], $passif_detail_n1['total_passif'])
        );

        foreach ($passif_rows as $row) {
            $is_bold = in_array($row[0], array(
                'Total des fonds reportes et dedies',
                'Total des provisions',
                'Total des dettes',
                'Total du passif'
            ));

            if ($is_bold) {
                $passif_data[] = array(
                    '<b>' . $row[0] . '</b>',
                    '<b>' . euro($row[1], ',', 'pdf') . '</b>',
                    '<b>' . euro($row[2], ',', 'pdf') . '</b>'
                );
            } else {
                $passif_data[] = array(
                    $row[0],
                    euro($row[1], ',', 'pdf'),
                    euro($row[2], ',', 'pdf')
                );
            }
        }

        $this->pdf->title('Bilan Passif', 2);
        $this->pdf->table(array(135, 27.5, 27.5), 8, array('L', 'R', 'R'), $passif_data);
    }

    /**
     * Génère un tableau PDF personnalisé pour la balance hiérarchique
     * avec couleur de fond différente pour les entêtes de codec
     *
     * @param array $data Les données à afficher
     * @param object $pdf L'objet PDF
     */
    private function pdf_table_hierarchical_balance($data, $pdf) {
        // Définir les colonnes et leurs largeurs
        $fields = array('codec', 'nom', 'section_name', 'solde_debit', 'solde_credit');
        $widths = array(12, 100, 20, 25, 25);
        $align = array('L', 'L', 'L', 'R', 'R');
        $height = 8;

        // En-tête du tableau
        $header_row = array();
        foreach ($fields as $field) {
            $header_row[] = $this->CI->gvvmetadata->field_name('vue_comptes', $field);
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

    function generate() {
        $this->pdf->Output();
    }
}
