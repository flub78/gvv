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
 * @filesource vols_decouverte.php
 * @package controllers
 * Contrôleur de gestion des avions.
 */
include('./application/libraries/Gvv_Controller.php');
include(APPPATH . '/third_party/phpqrcode/qrlib.php');
include(APPPATH . '/third_party/tcpdf/tcpdf.php');


class Vols_decouverte extends Gvv_Controller {

    // Tout le travail est fait par le parent
    protected $controller = 'vols_decouverte';
    protected $model = 'vols_decouverte_model';
    protected $modification_level = 'ca';
    protected $rules = array();


    /**
     * Constructeur
     */
    function __construct() {
        parent::__construct();

        $this->load->helper('crypto');
    }

    /**
     * Génération des éléments statiques à passer au formulaire en cas de création,
     * modification ou ré-affichage après erreur.
     * Sont statiques les parties qui ne changent pas d'un élément sur l'autre.
     *
     * @param $action CREATION
     *            | MODIFICATION | VISUALISATION
     * @see constants.php
     */
    protected function form_static_element($action) {
        $this->data['action'] = $action;
        $this->data['fields'] = $this->fields;
        $this->data['controller'] = $this->controller;
        if ($action == "visualisation") {
            $this->data['readonly'] = "readonly";
        }

        $this->data['saisie_par'] = $this->dx_auth->get_username();

        $pilote_selector = $this->membres_model->selector_with_null(['actif' => 1]);
        $this->gvvmetadata->set_selector('pilote_selector', $pilote_selector);
    }


    /**
     * Affiche les différentes action possibles sur un vol de découverte
     */
    function action($obfuscated_id) {
        $id = reverseTransform($obfuscated_id);

        // echo "action = $id";

        $this->data = $this->gvv_model->get_by_id($this->kid, $id);

        if (!count($this->data)) {
            $data = [];
            $data['msg'] = "Le vol de découverte $obfuscated_id n'existe pas";
            load_last_view('error', $data);
            return;
        }

        $tempDir = sys_get_temp_dir();

        $this->data['obfuscated_id'] = $obfuscated_id;

        // $qr_url = base_url() . 'vols_decouverte/action/' . $obfuscated_id;
        // $qr_name =  $tempDir . 'qrcode_' . $id . '.png';
        // QRcode::png($qr_url);
        // QRcode::png($qr_url, $qr_name, QR_ECLEVEL_L, 10, 1);

        return load_last_view("vols_decouverte/formMenu", $this->data, $this->unit_test);
    }

    /**
     * Generation du pdf
     */

    function pdf($obfuscated_id) {

        $id = reverseTransform($obfuscated_id);
        $this->data = $this->gvv_model->get_by_id($this->kid, $id);

        $tempDir = sys_get_temp_dir();
        $qr_url = base_url() . 'vols_decouverte/action/' . $obfuscated_id;
        $qr_name =  $tempDir . '/qrcode_' . $id . '.png';
        QRcode::png($qr_url, $qr_name, QR_ECLEVEL_L, 10, 1);

        // create new PDF document
        $pdf = new TCPDF('L', 'mm', 'A5', true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor("Aéroclub d'Abbeville");
        $pdf->SetTitle('Vol de découverte');
        $pdf->SetSubject('Bon cadeau');
        $pdf->SetKeywords('Abbeville, vol, découverte');

        // set header and footer fonts
        $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(0);
        $pdf->SetFooterMargin(0);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // remove default footer
        $pdf->setPrintFooter(false);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // ---------------------------------------------------------


        // add a page
        $pdf->AddPage();

        // -- set background ---

        // get the current page break margin
        $bMargin = $pdf->getBreakMargin();
        // get current auto-page-break mode
        $auto_page_break = $pdf->getAutoPageBreak();
        // disable auto-page-break
        $pdf->SetAutoPageBreak(false, 0);
        // set background image
        $img_file = image_dir() . "vd_recto.jpg";
        $pdf->Image($img_file, 0, 0, 210, 150, '', '', '', false, 300, '', false, false, 0);
        // restore auto-page-break status
        $pdf->SetAutoPageBreak($auto_page_break, $bMargin);
        // set the starting point for the page content
        $pdf->setPageMark();

        // ---------------------------------------------------------
        // Check if QR code image exists
        if (file_exists($qr_name)) {
            // Position QR code at the right side of the page
            $qrX = 175;
            $qrY = 5;
            $qrSize = 30;

            // Add QR code
            $pdf->Image($qr_name, $qrX, $qrY, $qrSize, $qrSize, 'PNG', '', 'T', false, 300, '', false, false, 0, 'CM');
        }

        // skipp a page for easier printing
        $pdf->AddPage();

        /** Verso */
        $pdf->AddPage();

        // Set content position 
        $pdf->SetXY(5, 5);
        $pdf->SetMargins(5, 5, 5);

        // Reset font for normal content
        $pdf->SetFont('helvetica', '', 11);


        // Flight information - customize based on your actual data structure
        $flightInfo = [
            'Numéro' => $id,
            'Date' => $this->data['date_vente'],
            'Time' => date("YMD "),
            'Produit' => $this->data['product'],
        ];

        // foreach ($flightInfo as $key => $value) {
        //     if (empty($value)) continue;

        //     $pdf->SetFont('helvetica', 'B', 11);
        //     $pdf->Cell(40, 7, $key . ':', 0, 0);
        //     $pdf->SetFont('helvetica', '', 10);
        //     $pdf->Cell($contentWidth - 40, 7, $value, 0, 1);
        // }

        // Set font
        $pdf->SetFont('helvetica', '', 10);


        // Header section
        $header_html = <<<EOD
<table cellspacing="0" cellpadding="3" border="1">
    <tr>
        <td width="75%">Ce bon pour le survol de la région défini ci-après est offert à</td>
        <td width="25%">N°</td>
    </tr>
    <tr>
        <td width="75%">à l'occasion de</td>
        <td width="25%">de la part de</td>
    </tr>
    <tr>
        <td width="75%">Ce bon est valable 1 an jusqu'au</td>
        <td width="25%">Date, signature et cachet :</td>
    </tr>
</table>
EOD;

        $pdf->writeHTML($header_html, true, false, false, false, '');

        // Options section - Airplane and Glider and Ultralight
        $options_html = <<<EOD
<table cellspacing="0" cellpadding="5" border="1">
    <tr>
        <td width="33%" align="center"><strong>Pour l'avion</strong></td>
        <td width="34%" align="center"><strong>Pour le planeur</strong></td>
        <td width="33%" align="center"><strong>Pour l'ULM</strong></td>
    </tr>
    <tr>
        <td width="33%" style="height: 120px; vertical-align: top;">
            <br /><input type="checkbox" name="abbeville" value="1" /> Tour d'Abbeville (15 mn environ) pour 2 personnes
            <br /><br /><input type="checkbox" name="baie" value="1" /> Baie de Somme (30 mn environ) pour 2 personnes
            <br /><br /><input type="checkbox" name="falaises" value="1" /> Falaises ou Marquenterre (40 mn) pour 2 personnes
            <br /><br /><input type="checkbox" name="autre" value="1" /> Autre (à détailler) :
        </td>
        <td width="34%" style="vertical-align: top;">

            <br /><br /><input type="checkbox" name="promenade1" value="1" /> Vol en planeur (largage 500 m, 15 à 30 mn suivant la météo)

            <br /><br />
        </td>
        <td width="33%" style="height: 120px; vertical-align: top;">
            <br /><input type="checkbox" name="abbeville" value="1" /> Tour d'Abbeville (15 mn environ) pour 1 personne
            <br /><br /><input type="checkbox" name="baie" value="1" /> Baie de Somme (30 mn environ) pour 1 personne
            <br /><br /><input type="checkbox" name="falaises" value="1" /> Falaises ou Marquenterre (40 mn) pour 1 personne
            <br /><br /><input type="checkbox" name="autre" value="1" /> Autre (à détailler) :
        </td>
    </tr>
</table>
EOD;

        $pdf->writeHTML($options_html, true, false, false, false, '');

        // Contact section
        $contact_html = <<<EOD
<table cellspacing="0" cellpadding="5" border="1" style="width: 100%;">
    <tr>
        <td>
            Pour prendre rendez-vous et organiser votre vol, vous devez contacter
            <br />- pour l'Avion <strong>Jean-Pierre LIGNIER (06 75 29 84 90)</strong> ou <strong>Daniel TELLIER (06 12 01 37 22)</strong>
            <br />- pour le planeur <strong>Mathieu CAUDRELIER</strong> au <strong>06 07 23 09 75</strong>
            <br />- pour l'ULM <strong>Mathieu CAUDRELIER</strong> au <strong>06 07 23 09 75</strong>

        </td>
    </tr>

    <tr style="width: 100%; background-color: #dddddd;">
        <td width="33%">Vol effectué le :</td>
        <td width="33%">sur (nom de l'appareil) :</td>
        <td width="34%">par (nom du pilote) :</td>
    </tr>
</table>
EOD;

        $pdf->writeHTML($contact_html, true, false, false, false, '');



        //Close and output PDF document
        $pdf->Output('example_051.pdf', 'I');
    }

    function email($obfuscated_id) {
        $id = reverseTransform($obfuscated_id);
    }

    function edit_pre_flight_info($obfuscated_id) {
        $id = reverseTransform($obfuscated_id);
    }

    function done($obfuscated_id) {
        $id = reverseTransform($obfuscated_id);
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

    function qr() {

        $originalNumber = 12345;
        $transformed = transformInteger($originalNumber);
        $recovered = reverseTransform($transformed);

        echo "QR:";
        echo "Nombre original: " . $originalNumber . "\n";
        echo "Nombre transformé: " . $transformed . "\n";
        echo "Nombre récupéré: " . $recovered . "\n";

        // Test avec quelques autres valeurs
        $testValues = [0, 1, 42, 99999, 1000000];
        foreach ($testValues as $value) {
            $transformed = transformInteger($value);
            $recovered = reverseTransform($transformed);
            echo "<br> Test avec $value: transformé = $transformed, récupéré = $recovered\n";
        }

        QRcode::png('https://example.com');
        QRcode::png('https://example.com', 'qrcode.png');
    }
}
