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
 * @filesource avion.php
 * @package controllers
 * Controleur des rapports utilisateur
 */
include ('./application/libraries/Gvv_Controller.php');
class Reports extends Gvv_Controller {

    // Tout le travail est fait par le parent
    protected $controller = 'reports';
    protected $model = 'reports_model';
    protected $modification_level = 'ca';
    protected $rules = array (
            'sql' => 'callback_safe_sql'
    );

    /**
     * Constructeur
     */
    function __construct() {
        parent::__construct();
        $this->load->library('Database');
    }

    /**
     * Duplique une requête
     */
    function clone_elt($id) {
        $data = $this->gvv_model->get_by_id('nom', $id);
        $data ['nom'] .= "_clone";
        $this->gvv_model->create($data);
        redirect(controller_url("reports/page"));
    }

    /**
     * Execute une requête SQL
     */
    function execute($id) {
        $elt = $this->gvv_model->get_by_id('nom', $id);

        $sql = $elt ['sql'];
        $select = $this->database->sql($sql, true);

        $this->lang->load('reports');

        $data ['title'] = $this->lang->line("gvv_reports_title");
        $data ['text'] = $sql;
        $data ['request'] = $id;
        $data ['table'] = $select [0];
        $data ['attrs'] = array (
                'fields' => explode(",", $elt ['fields_list']),
                'align' => explode(",", $elt ['align']),
                'title' => $elt ['titre'],
                'class' => "table"
        );

        load_last_view('message', $data);
    }

    /*
     * Activé par les boutons CVS et Pdf
     */
    public function export($type, $request) {
        $elt = $this->gvv_model->get_by_id('nom', $request);

        $sql = $elt ['sql'];
        $select = $this->database->sql($sql, true);
        $data = $select [0];
        $title = $elt ['titre'];
        $fields = explode(",", $elt ['fields_list']);
        $align = explode(",", $elt ['align']);
        $width = explode(",", $elt ['width']);
        $landscape = $elt ['landscape'];

        if ($type == 'pdf') {
            $this->gen_pdf($title, $data, $fields, $align, $width, $landscape);
        } else {
            $this->gen_csv($request, $title, $data, $fields);
        }
    }

    /*
     * Activé par le lien CVS
     */
    public function csv($request) {
        $elt = $this->gvv_model->get_by_id('nom', $request);

        $sql = $elt ['sql'];
        $select = $this->database->sql($sql, true);
        $data = $select [0];
        $title = $elt ['titre'];
        $fields = explode(",", $elt ['fields_list']);
        $align = explode(",", $elt ['align']);
        $width = explode(",", $elt ['width']);
        $landscape = $elt ['landscape'];

        $this->gen_csv($request, $title, $data, $fields);
    }

    /*
     * Activé par le lien Pdf
     */
    public function pdf($request) {
        $elt = $this->gvv_model->get_by_id('nom', $request);

        $sql = $elt ['sql'];
        $select = $this->database->sql($sql, true);
        $data = $select [0];
        $title = $elt ['titre'];
        $fields = explode(",", $elt ['fields_list']);
        $align = explode(",", $elt ['align']);
        $width = explode(",", $elt ['width']);
        $landscape = $elt ['landscape'];

        $this->gen_pdf($title, $data, $fields, $align, $width, $landscape);
    }

    /*
     * Génère un rapport pdf
     */
    private function gen_pdf($title, $data, $fields, $align, $width, $landscape) {
        $this->load->library('Pdf');
        $pdf = new Pdf();

        if ($landscape) {
            $pdf->AddPage('L');
        } else {
            $pdf->AddPage();
        }

        $align_pdf = array ();
        foreach ( $align as $elt ) {
            $align_pdf [] = substr($elt, 0, 1);
        }

        $fields_pdf = array ();
        $cnt = 0;
        foreach ( $data [0] as $key => $value ) {
            $fields_pdf [$key] = $fields [$cnt];
            $cnt ++;
        }
        $first_line [0] = $fields_pdf;

        $table = array_merge($first_line, $data);
        $pdf->title($title);
        $pdf->table($width, 8, $align_pdf, $table);

        $pdf->Output();
    }

    /*
     * Génère un rapport csv
     */
    private function gen_csv($request, $title, $data, $fields) {
        $res = "";
        $res .= "$title\n";

        foreach ( $fields as $field ) {
            $res .= "$field; ";
        }
        $res .= "\n";

        foreach ( $data as $row ) {
            foreach ( $row as $elt ) {
                $res .= "$elt; ";
            }
            $res .= "\n";
        }

        date_default_timezone_set('Europe/Paris');
        $dt = date("Y_m_d");
        $filename = "gvv_" . $request . "_$dt.csv";

        $res = iconv('UTF-8', 'windows-1252', $res);
        $CI = & get_instance();
        // Load the download helper and send the file to your desktop
        $CI->load->helper('download');
        force_download($filename, $res);
    }

    /**
     * (non-PHPdoc)
     *
     * @see Gvv_Controller::form_static_element()
     */
    // function form_static_element($action) {
    // parent :: form_static_element($action);
    // }

    /**
     * Tests unitaires pour le controleur
     */
    function test_methodes() {
        $this->unit->run('Foo', 'is_string', 'test reports');
    }

    /**
     * Test unitaire
     */
    function test($format = "html") {
        parent::test($format);

        $this->test_methodes();
        $this->test_model("nom");

        $this->tests_results($format);
    }
}