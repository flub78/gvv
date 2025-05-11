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

        // $tempDir = sys_get_temp_dir(); 

        $this->data['obfuscated_id'] = $obfuscated_id;
        $qr_url = 'https://example.com';
        $qr_url = base_url() . 'vols_decouverte/action/' . $obfuscated_id;
        $qr_name = 'qrcode_' . $id . '.png';
        QRcode::png($qr_url);
        QRcode::png($qr_url, $qr_name, QR_ECLEVEL_L, 10, 1);

        return load_last_view("vols_decouverte/formMenu", $this->data, $this->unit_test);
    }

    function pdf($obfuscated_id) {
        $id = reverseTransform($obfuscated_id);

        $this->data = $this->gvv_model->get_by_id($this->kid, $id);

        if (!count($this->data)) {
            $data = [];
            $data['msg'] = "Le vol de découverte $obfuscated_id n'existe pas";
            load_last_view('error', $data);
            return;
        }

        $pdf = new TCPDF('L', 'mm', 'A5', true, 'UTF-8', false);
        // Set document information
        $pdf->SetCreator('VolDecouvertePDFGenerator');
        $pdf->SetAuthor('Aéro-Club');
        $pdf->SetTitle('Vol de Découverte - ' . ($this->data['obfuscated_id'] ?? ''));
        $pdf->SetSubject('Information Vol de Découverte');

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set margins (left, top, right)
        $pdf->SetMargins(10, 10, 10);

        // Set auto page breaks
        $pdf->SetAutoPageBreak(true, 10);

        // Set image scale factor
        $pdf->setImageScale(1.25);

        // Set default font
        $pdf->SetFont('helvetica', '', 11);
        $pdf->AddPage();

        // $html = '<table border="1">
        //     <tr><td>Hello</td><td>World</td></tr>
        //  </table>';

        // $pdf->writeHTML($html, true, false, true, false, '');

        $pdf->Output('table.pdf', 'I');
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
