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
$CI->load->library('Fpdf');

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
        $this->pdf->set_title($nom_club);
        $this->pdf->AliasNbPages();
    }

    /**
     * Génération de la page résultats
     * @param unknown_type $year
     */
    function pagesResultats($year) {
        $this->pdf->AddPage();
        $this->pdf->title($this->CI->lang->line("gvv_comptes_title_resultat") . " " . $year, 2);

        $resultat = $this->CI->ecritures_model->select_resultat();
        $tab = $this->CI->ecritures_model->resultat_table($resultat, false, '', ',', 'pdf');

        $w = array(
            12,
            48,
            16,
            16,
            10,
            12,
            48,
            16,
            16
        );
        $align = array(
            'R',
            'L',
            'R',
            'R',
            'C',
            'R',
            'L',
            'R',
            'R'
        );
        $this->pdf->table($w, 8, $align, $tab);
    }

    /**
     * Génération de la page résultats par catégorie
     * @param unknown_type $year
     */
    function pagesResultatsCategorie($year) {
        $this->pdf->AddPage();
        $this->pdf->title($this->CI->lang->line("gvv_comptes_title_resultat") . " " . $year . " par catégorie", 2);

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

        $this->pdf->AddPage('P');
        $this->pdf->title($this->CI->lang->line("gvv_comptes_title_balance") . " $balance_date", 1);

        $this->CI->session->set_userdata('balance_date', $balance_date);

        $result = $this->CI->comptes_model->select_page_general(array(), $balance_date);

        $fields = array('codec', 'nom', 'solde_debit', 'solde_credit');
        $this->CI->gvvmetadata->pdf_table("vue_comptes", $result, $this->pdf, array(
            'width' => array(12, 50, 30, 30),
            'fields' => $fields
        ));
    }

    /**
     * Génération des pages des licenciés
     * @param unknown_type $year
     */
    function pagesLicences($year) {

        $this->pdf->AddPage('P');
        $this->pdf->title($this->CI->lang->line("gvv_achats_title_year") . "Licenciés $year", 1);

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

        $this->pdf->AddPage('P');
        for ($i = 0; $i < 5; $i++) {
            $this->pdf->Ln();
        }
        $this->pdf->title($this->CI->config->item('nom_club'));
        $this->pdf->title($this->CI->lang->line("gvv_comptes_title_journaux") . " $year", 1);
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

        $this->pdf->AddPage('P');
        for ($i = 0; $i < 5; $i++) {
            $this->pdf->Ln();
        }
        $this->pdf->title($this->CI->config->item('nom_club'));
        $this->pdf->title($this->CI->lang->line("gvv_comptes_title_financial") . " $year", 1);
        $this->pdf->Ln();

        $this->pdf->title("                    " . $this->CI->lang->line("gvv_comptes_title_resultat"), 2);
        $this->pdf->title("                    " . $this->CI->lang->line("gvv_comptes_title_bilan"), 2);
        $this->pdf->title("                    " . $this->CI->lang->line("gvv_comptes_title_balance"), 2);
        $this->pdf->title("                    " . $this->CI->lang->line("gvv_comptes_title_sales"), 2);
    }

    /**
     * Génération des pages pour tous les ventes
     * @param unknown_type $year
     */
    function pagesVentes($year) {

        $this->CI->lang->load('achats');

        $this->pdf->AddPage('P');
        $this->pdf->title($this->CI->lang->line("gvv_achats_title_year") . " $year", 1);
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

        $title = $this->CI->lang->line('gvv_comptes_title_bilan');
        $title .= " $year";

        $this->pdf->AddPage('P');
        $this->pdf->title($title, 1);

        $bilan = $this->CI->comptes_model->select_all_for_bilan($year);
        $bilan_prec = $this->CI->comptes_model->select_all_for_bilan($year - 1);
        $data = bilan_table($bilan, $bilan_prec, false);

        $width = array(
            39,
            19,
            19,
            19,
            19,
            2,
            39,
            19,
            19
        );
        $align = array(
            'L',
            'R',
            'R',
            'R',
            'R',
            'C',
            'L',
            'R',
            'R'
        );

        $this->pdf->table($width, 8, $align, $data);
    }

    function generate() {
        $this->pdf->Output();
    }
}
