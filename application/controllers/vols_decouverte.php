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
 * 
 *  reviewed by: copilot on 2025-07-31

 */
include('./application/libraries/Gvv_Controller.php');
include(APPPATH . '/third_party/phpqrcode/qrlib.php');
include(APPPATH . '/third_party/tcpdf/tcpdf.php');


class Vols_decouverte extends Gvv_Controller {

    // Tout le travail est fait par le parent
    protected $controller = 'vols_decouverte';
    protected $model = 'vols_decouverte_model';
    protected $modification_level = 'ca';
    protected $rules = array('club' => "callback_section_selected");


    /**
     * Constructeur
     */
    function __construct() {
        parent::__construct();

        $this->load->helper('crypto');
        $this->load->model('tarifs_model');
        $this->load->model('configuration_model');
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

        $product_selector = $this->tarifs_model->selector(array('type_ticket' => 1));
        $this->gvvmetadata->set_selector('product_selector', $product_selector);

        $pilote_selector = $this->membres_model->selector_with_null(['actif' => 1]);
        $this->gvvmetadata->set_selector('pilote_selector', $pilote_selector);

        $this->gvvmetadata->set_selector('machine_selector', $this->gvv_model->machine_selector());
    }


    /**
     * Affiche les différentes action possibles sur un vol de découverte
     */
    function action($obfuscated_id) {
        $this->push_return_url("action");

        $id = reverseTransform($obfuscated_id);

        $this->data = $this->gvv_model->get_by_id($this->kid, $id);

        if (!count($this->data)) {
            $data = [];
            $data['msg'] = "Le vol de découverte $obfuscated_id n'existe pas";
            load_last_view('error', $data);
            return;
        }

        $this->data['obfuscated_id'] = $obfuscated_id;
        $product = $this->data['product'];
        $tarif = $this->tarifs_model->get_tarif($product, date("Y-m-d"));
        $this->data['description'] = ($tarif['description'] != "") ? $tarif['description'] : $product;

        $this->data['expired'] = strtotime($this->data['date_vente']) < strtotime('-1 year -1 day', time());

        // var_dump($this->data);

        return load_last_view("vols_decouverte/formMenu", $this->data, $this->unit_test);
    }

    /**
     * Action when the flight is selected by the selector
     */
    function action_clear() {
        if ($this->input->post('vd_id')) {
            $this->push_return_url("action");

            $id = $this->input->post('vd_id');
            $obfuscated = transformInteger($id);
            redirect("vols_decouverte/action/" . $obfuscated);
        }
    }

    /**
     * Accés à un vol de découverte par numéro
     */
    function select_by_id() {

        $this->data['vd_selector'] = $this->gvv_model->selector();
        return load_last_view("vols_decouverte/formSelector", $this->data, $this->unit_test);
    }

    /**
     * pdf request
     */
    function print_vd($obfuscated_id) {
        $id = reverseTransform($obfuscated_id);
        $vd = $this->gvv_model->get_by_id($this->kid, $id);
        // var_dump($vd);exit;

        if (!count($vd)) {
            $data = [];
            $data['msg'] = "Le vol de découverte $obfuscated_id n'existe pas";
            load_last_view('error', $data);
            return;
        }

        $data = [];
        $data['obfuscated_id'] = $obfuscated_id;
        $data['id'] = $id;

        $data['offer_a'] = $vd['beneficiaire'];
        $data['occasion'] = $vd['occasion'];
        $data['de_la_part'] = $vd['de_la_part'];

        $data['validity'] = date_db2ht(date('Y-m-d', strtotime($vd['date_vente'] . ' +1 year')));

        $data[$vd['product']] = true;

        $this->generate_pdf($data);
    }

    /**
     * email un bon
     */
    function email_vd($obfuscated_id) {
        $id = reverseTransform($obfuscated_id);
        $vd = $this->gvv_model->get_by_id($this->kid, $id);
        // var_dump($vd);exit;

        if (!count($vd)) {
            $data = [];
            $data['msg'] = "Le vol de découverte $obfuscated_id n'existe pas";
            load_last_view('error', $data);
            return;
        }

        $data = [];
        $data['obfuscated_id'] = $obfuscated_id;
        $data['id'] = $id;

        $data['offer_a'] = $vd['beneficiaire'];
        $data['occasion'] = $vd['occasion'];
        $data['de_la_part'] = $vd['de_la_part'];

        $data['validity'] = date_db2ht(date('Y-m-d', strtotime($vd['date_vente'] . ' +1 year')));

        $data[$vd['product']] = true;

        $pdf_content = $this->generate_pdf($data, 'S');

        // Send email with PDF attachment
        $this->send_email_with_pdf($vd, $pdf_content, $id);
    }

    /**
     * Send email with PDF attachment
     */
    function send_email_with_pdf($vd, $pdf_content, $id) {
        $this->load->library('email');

        $sender = "info@aeroclub-abbeville.fr";

        // Configure email settings
        $this->email->clear();
        $config['mailtype'] = 'html';
        // Configure SMTP settings for Ionos
        $config = array(
            'protocol'    => 'smtp',
            'smtp_host'   => 'smtp.ionos.fr',  // or smtp.ionos.com depending on your account
            'smtp_port'   => 587,
            'smtp_user'   => $sender,  // Your full email address
            'smtp_pass'   => $this->config->item('email_password'), // config/config.php
            'smtp_crypto' => 'tls',
            'mailtype'    => 'html',
            'charset'     => 'utf-8',
            'wordwrap'    => TRUE,
            'newline'     => "\r\n"
        );


        gvv_debug(var_export($config, true), "email_config");

        $this->email->initialize($config);

        // Set email parameters
        $this->email->from($sender, 'Aéroclub d\'Abbeville');
        $this->email->to($vd['beneficiaire_email']);
        $this->email->bcc($sender);

        $this->email->subject('Votre bon de vol de découverte');

        $message = "Bonjour " . $vd['beneficiaire'] . ",<br><br>";

        $message .= "Voici votre bon pour un vol de découverte. Il est valable un an à partir de la date d'achat.<br><br>";
        $message .= "Cordialement,<br><br>L'équipe de l'Aéroclub d'Abbeville";

        $this->email->message($message);

        // Attach PDF
        $temp_file = "/tmp/vol_decouverte_acs_" . $id . ".pdf";
        file_put_contents($temp_file, $pdf_content);
        $this->email->attach($temp_file, 'attachment', "vol_decouverte_acs_" . $id . ".pdf", 'application/pdf');

        // Send email
        if ($this->email->send()) {
            // Success message
            $data['title'] = "Succès";
            $data['text'] = "Email envoyé avec succès à " . $vd['beneficiaire_email'];
            unlink($temp_file); // Clean up after sending

            load_last_view('message', $data);
        } else {
            // Error message
            $data['msg'] = "Erreur lors de l'envoi de l'email: " . $this->email->print_debugger();
            // unlink($temp_file); // Clean up after sending

            load_last_view('error', $data);
        }
    }

    /**
     * Génération du bon cadeau
     */
    function generate_pdf($data, $output = "I") {

        $obfuscated_id = $data['obfuscated_id'];

        $id = $data['id'];
        $this->data = $this->gvv_model->get_by_id($this->kid, $id);

        $tempDir = sys_get_temp_dir();
        $index_page = $this->config->item('index_page');

        $qr_url = site_url() . '/vols_decouverte/action/' . $obfuscated_id;
        $qr_name =  $tempDir . '/qrcode_' . $id . '.png';
        QRcode::png($qr_url, $qr_name, QR_ECLEVEL_L, 10, 1);

        // create new PDF document
        $pdf = new TCPDF('L', 'mm', 'A5', true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        // $pdf->SetAuthor("Aéroclub d'Abbeville");
        $pdf->SetAuthor($this->configuration_model->get_param('vd.email.sender_name'));
        $pdf->SetTitle('Vol de découverte ' . $id);
        $pdf->SetSubject('Bon cadeau');
        $pdf->SetKeywords('vol, découverte');

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
        $img_file = image_dir() . "Bon-Bapteme.png";
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

        /** Verso */
        $pdf->AddPage();

        // Set content position 
        $pdf->SetXY(5, 5);
        $pdf->SetMargins(5, 5, 5);
        $pdf->setAutoPageBreak(false);

        // Reset font for normal content
        $pdf->SetFont('helvetica', '', 11);

        // Set font
        $pdf->SetFont('helvetica', '', 10);

        // Header section


        $offer_a = $data['offer_a'];
        $occasion = $data['occasion'];
        $de_la_part = $data['de_la_part'];
        $validity = $data['validity'];

        $header_html = <<<EOD
<table cellspacing="0" cellpadding="3" border="1">
    <tr>
        <td width="67%">Ce bon pour le survol de la région défini ci-après</td>
        <td width="33%">N° <strong>{$id}</strong></td>
    </tr>
    <tr>
        <td width="67%">Offert à <strong>{$offer_a}</strong></td>
        <td width="33%"></td>
    </tr>
    <tr>
        <td width="67%">à l'occasion de {$occasion}</td>
        <td width="33%">de la part de {$de_la_part}</td>
    </tr>
    <tr>
        <td width="67%">Ce bon est valable 1 an jusqu'au <strong>{$validity}</strong></td>
        <td width="33%"></td>
    </tr>
</table>
EOD;
        $pdf->writeHTML($header_html, true, false, false, false, '');

        // Options section - Airplane and Glider and Ultralight
        $checked = '<img src="checked.png" width="10" height="10" alt="Checked checkbox" >';
        $unchecked = '<img src="unchecked.png" width="10" height="10" alt="Unchecked checkbox" >';

        $abbeville = isset($data['abbeville']) ? $checked : $unchecked;
        $baie = isset($data['baie']) ? $checked : $unchecked;
        $falaise = isset($data['falaises']) ? $checked : $unchecked;
        $autre = isset($data['autre']) ? $checked : $unchecked;
        $planeur = isset($data['planeur']) ? $checked : $unchecked;
        $abbeville_ulm = isset($data['abbeville_ulm']) ? $checked : $unchecked;
        $baie_ulm = isset($data['baie_ulm']) ? $checked : $unchecked;
        $falaise_ulm = isset($data['falaises_ulm']) ? $checked : $unchecked;
        $autre_ulm = isset($data['autre_ulm']) ? $checked : $unchecked;

        $options_html = <<<EOD
<table cellspacing="0" cellpadding="5" border="1">
    <tr>
        <td width="33%" align="center"><strong>Pour l'avion</strong></td>
        <td width="34%" align="center"><strong>Pour le planeur</strong></td>
        <td width="33%" align="center"><strong>Pour l'ULM</strong></td>
    </tr>
    <tr>
        <td width="33%" style="height: 120px; vertical-align: top;">
            <br /> {$abbeville} Tour d'Abbeville (15 mn environ) pour 2 personnes
            <br /><br />{$baie} Baie de Somme (30 mn environ) pour 2 personnes
            <br /><br />{$falaise} Falaises ou Marquenterre (40 mn) pour 2 personnes
            <br /><br />{$autre} Autre (à détailler) :
        </td>
        <td width="34%" style="vertical-align: top;">
            <br /><br />{$planeur} Vol en planeur (largage 500 m, 15 à 30 mn suivant la météo)
            <br /><br />
        </td>
        <td width="33%" style="height: 120px; vertical-align: top;">
            <br />{$abbeville_ulm} Tour d'Abbeville (15 mn environ) pour 1 personne
            <br /><br />{$baie_ulm} Baie de Somme (30 mn environ) pour 1 personne
            <br /><br />{$falaise_ulm} Falaises ou Marquenterre (40 mn) pour 1 personne
            <br /><br />{$autre_ulm} Autre (à détailler) :
        </td>
    </tr>
</table>
EOD;
        $pdf->writeHTML($options_html, true, false, false, false, '');

        // Contact section
        $contact_avion = $this->configuration_model->get_param('vd.avion.contact_name');
        $contact_planeur = $this->configuration_model->get_param('vd.planeur.contact_name');
        $contact_ulm = $this->configuration_model->get_param('vd.ulm.contact_name');
        $tel_avion = $this->configuration_model->get_param('vd.avion.contact_tel');
        $tel_planeur = $this->configuration_model->get_param('vd.planeur.contact_tel');
        $tel_ulm = $this->configuration_model->get_param('vd.ulm.contact_tel');

        $contact_html = <<<EOD
<table cellspacing="0" cellpadding="5" border="1" style="width: 100%;">
    <tr>
        <td>
            Pour prendre rendez-vous et organiser votre vol, vous devez contacter<br>
            
            <br />- pour l'avion <strong>{$contact_avion} ({$tel_avion})</strong> 
            <br />- pour le planeur <strong>{$contact_planeur} ({$tel_planeur})</strong>
            <br />- pour l'ULM <strong>{$contact_ulm} ({$tel_ulm})</strong>
            <br>
        </td>
    </tr>

    <tr style="width: 100%; background-color: #ddddd">
        <td width="33%" height="1.5cm">Vol effectué le :</td>
        <td width="33%">sur (nom de l'appareil) :</td>
        <td width="34%">par (nom du pilote) :</td>
    </tr>
</table>
EOD;

        $pdf->writeHTML($contact_html, true, false, false, false, '');

        //Close and output PDF document
        $res = $pdf->Output("vol_decouverte_acs_" . $id . ".pdf", $output);
        if ($output == "S") return $res;
    }


    function pre_flight($obfuscated_id) {
        $id = reverseTransform($obfuscated_id);
        $this->edit($id);
    }

    function done($obfuscated_id) {
        $id = reverseTransform($obfuscated_id);
        $this->edit($id);
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
