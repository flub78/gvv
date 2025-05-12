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

        if (!count($this->data)) {
            $data = [];
            $data['msg'] = "Le vol de découverte $obfuscated_id n'existe pas";
            load_last_view('error', $data);
            return;
        }

        $tempDir = sys_get_temp_dir();
        $qr_url = base_url() . 'vols_decouverte/action/' . $obfuscated_id;
        $qr_name =  $tempDir . '/qrcode_' . $id . '.png';
        QRcode::png($qr_url, $qr_name, QR_ECLEVEL_L, 10, 1);


        $contentWidth = 120; // Leave space for QR code

        $pdf = new TCPDF('L', 'mm', 'A5', true, 'UTF-8', false);
        // Set document information
        $pdf->SetCreator('VolDecouvertePDFGenerator');
        $pdf->SetAuthor('Aéro-Club');
        $pdf->SetTitle('Vol de Découverte - ' . ($this->data['obfuscated_id'] ?? ''));
        $pdf->SetSubject('Information Vol de Découverte');

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set auto page breaks
        $pdf->SetAutoPageBreak(true, 10);

        // Set image scale factor
        $pdf->setImageScale(1.25);

        // Set default font
        $pdf->SetFont('helvetica', '', 11);
        $pdf->AddPage();

        // Set margins (left, top, right)
        $pdf->SetMargins(0, 0, 0);

        $background = image_dir() . "vd_recto.jpg";
        if (file_exists($background)) {
            // Get page dimensions
            $pageWidth = $pdf->getPageWidth();      // 210 mm
            $pageHeight = $pdf->getPageHeight();    // 148 mm

            // Add background image (x, y, width, height)
            //            $pdf->Image($background, 0, 0, $pageWidth - 20, $pageHeight - 20, '', '', '', false, 300, '', false, false, 0);
            // Create a template ID for the background
            $pdf->Image($background, 0, 0, $pageWidth, $pageHeight, '', '', '', false, 300, '', false, false, 0);
            $pdf->setPageMark();
        }

        // Set margins (left, top, right)
        $pdf->SetMargins(4, 4, 4);

        // Set content position (after background image)
        $pdf->SetXY(15, 15);

        // Add title
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Vol de Découverte', 0, 1, 'L');
        $pdf->Ln(2);

        // Reset font for normal content
        $pdf->SetFont('helvetica', '', 11);

        // Add dynamic content from data array
        $startX = 15;
        $contentWidth = 120; // Leave space for QR code

        // Flight information - customize based on your actual data structure
        $flightInfo = [
            'Reference' => $id,
            'Date' => $this->data['date_vente'],
            'Time' => date("YMD "),
            'Produit' => $this->data['product'],
        ];

        foreach ($flightInfo as $key => $value) {
            if (empty($value)) continue;

            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(40, 7, $key . ':', 0, 0);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell($contentWidth - 40, 7, $value, 0, 1);
        }

        // Add notes or additional information if available
        if (isset($this->data['notes']) && !empty($this->data['notes'])) {
            $pdf->Ln(5);
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell($contentWidth, 7, 'Notes:', 0, 1);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->MultiCell($contentWidth, 7, $this->data['notes'], 0, 'L');
        }

        // Add footer information
        $pdf->Ln(20);
        $pdf->SetFont('helvetica', 'I', 9);
        $pdf->MultiCell($contentWidth, 5, 'Ce document doit être présenter lors de votre arrivée à l\'aérodrome.', 0, 'L');

        // Check if QR code image exists
        if (file_exists($qr_name)) {
            // Position QR code at the right side of the page
            $qrX = 175;
            $qrY = 5;
            $qrSize = 30;

            // Add QR code
            $pdf->Image($qr_name, $qrX, $qrY, $qrSize, $qrSize, 'PNG', '', 'T', false, 300, '', false, false, 0, 'CM');
        }


        $pdf->Output('table.pdf', 'I');
    }

    function pdf2() {

        $obfuscated_id = "137";
        $tempDir = sys_get_temp_dir();
        $qr_url = base_url() . 'vols_decouverte/action/' . $obfuscated_id;
        $qr_name =  $tempDir . '/qrcode_' . $id . '.png';
        QRcode::png($qr_url, $qr_name, QR_ECLEVEL_L, 10, 1);

        // create new PDF document
        //$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf = new TCPDF('L', 'mm', 'A5', true, 'UTF-8', false);


        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Nicola Asuni');
        $pdf->SetTitle('TCPDF Example 051');
        $pdf->SetSubject('TCPDF Tutorial');
        $pdf->SetKeywords('TCPDF, PDF, example, test, guide');

        // set header and footer fonts
        $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(0);
        $pdf->SetFooterMargin(0);

        // remove default footer
        $pdf->setPrintFooter(false);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // ---------------------------------------------------------

        // set font
        $pdf->SetFont('times', '', 48);


        // add a page
        $pdf->AddPage();


        // -- set new background ---

        // get the current page break margin
        $bMargin = $pdf->getBreakMargin();
        // get current auto-page-break mode
        $auto_page_break = $pdf->getAutoPageBreak();
        // disable auto-page-break
        $pdf->SetAutoPageBreak(false, 0);
        // set bacground image
        $img_file = K_PATH_IMAGES . 'image_demo.jpg';
        $img_file = image_dir() . "vd_recto.jpg";
        // $pdf->Image($img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
        $pdf->Image($img_file, 0, 0, 210, 150, '', '', '', false, 300, '', false, false, 0);
        // restore auto-page-break status
        $pdf->SetAutoPageBreak($auto_page_break, $bMargin);
        // set the starting point for the page content
        $pdf->setPageMark();


        // Print a text
        $html = '<span style="color:white;text-align:center;font-weight:bold;font-size:80pt;">PAGE A5</span>';
        $pdf->writeHTML($html, true, false, true, false, '');

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
