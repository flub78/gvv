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
 * User-defined SQL reports with HTML/PDF/CSV export.
 */
include('./application/libraries/Gvv_Controller.php');

class Reports extends Gvv_Controller {

    protected $controller = 'reports';
    protected $model = 'reports_model';
    protected $modification_level = 'ca';
    protected $rules = array(
        'sql' => 'callback_safe_sql'  // SQL injection protection
    );

    function __construct() {
        parent::__construct();
        $this->load->library('Database');
    }

    /**
     * Duplicates a report with "_clone" suffix
     *
     * @param string|int $id Report identifier
     */
    function clone_elt($id) {
        $data = $this->gvv_model->get_by_id('nom', $id);
        $data['nom'] .= "_clone";
        $this->gvv_model->create($data);
        redirect(controller_url("reports/page"));
    }

    /**
     * Executes SQL query and displays results in HTML table
     *
     * @param string|int $id Report identifier
     */
    function execute($id) {
        $elt = $this->gvv_model->get_by_id('nom', $id);

        $sql = $elt['sql'];
        $select = $this->database->sql($sql, true);

        $this->lang->load('reports');

        $data['title'] = $this->lang->line("gvv_reports_title");
        $data['text'] = $sql;
        $data['request'] = $id;
        $data['table'] = $select[0];
        $data['attrs'] = array(
            'fields' => explode(",", $elt['fields_list']),
            'align' => explode(",", $elt['align']),
            'title' => $elt['titre'],
            'class' => "table"
        );

        load_last_view('message', $data);
    }

    /**
     * Exports report as PDF or CSV
     *
     * @param string $type 'pdf' or 'csv'
     * @param string|int $request Report identifier
     */
    public function export($type, $request) {
        $elt = $this->gvv_model->get_by_id('nom', $request);

        $sql = $elt['sql'];
        $select = $this->database->sql($sql, true);
        $data = $select[0];
        $title = $elt['titre'];
        $fields = explode(",", $elt['fields_list']);
        $align = explode(",", $elt['align']);
        $width = explode(",", $elt['width']);
        $landscape = $elt['landscape'];

        if ($type == 'pdf') {
            $this->gen_pdf($title, $data, $fields, $align, $width, $landscape);
        } else {
            $this->gen_csv($request, $title, $data, $fields);
        }
    }

    /**
     * Direct CSV export endpoint
     *
     * @param string|int $request Report identifier
     */
    public function csv($request) {
        $elt = $this->gvv_model->get_by_id('nom', $request);

        $sql = $elt['sql'];
        $select = $this->database->sql($sql, true);
        $data = $select[0];
        $title = $elt['titre'];
        $fields = explode(",", $elt['fields_list']);
        $align = explode(",", $elt['align']);
        $width = explode(",", $elt['width']);
        $landscape = $elt['landscape'];

        $this->gen_csv($request, $title, $data, $fields);
    }

    /**
     * Direct PDF export endpoint
     *
     * @param string|int $request Report identifier
     */
    public function pdf($request) {
        $elt = $this->gvv_model->get_by_id('nom', $request);

        $sql = $elt['sql'];
        $select = $this->database->sql($sql, true);
        $data = $select[0];
        $title = $elt['titre'];
        $fields = explode(",", $elt['fields_list']);
        $align = explode(",", $elt['align']);
        $width = explode(",", $elt['width']);
        $landscape = $elt['landscape'];

        $this->gen_pdf($title, $data, $fields, $align, $width, $landscape);
    }

    /**
     * Generates PDF with TCPDF
     *
     * @param string $title Report title
     * @param array $data Result rows
     * @param array $fields Column headers
     * @param array $align Column alignments ('left', 'center', 'right')
     * @param array $width Column widths in mm
     * @param bool $landscape Orientation
     */
    private function gen_pdf($title, $data, $fields, $align, $width, $landscape) {
        $this->load->library('Pdf');
        $pdf = new Pdf();

        if ($landscape) {
            $pdf->AddPage('L');
        } else {
            $pdf->AddPage();
        }

        // Convert alignment to PDF format (first character only)
        $align_pdf = array();
        foreach ($align as $elt) {
            $align_pdf[] = substr($elt, 0, 1);
        }

        // Build header row
        $fields_pdf = array();
        $cnt = 0;
        foreach ($data[0] as $key => $value) {
            $fields_pdf[$key] = $fields[$cnt];
            $cnt++;
        }
        $first_line[0] = $fields_pdf;

        $table = array_merge($first_line, $data);
        $pdf->title($title);
        $pdf->table($width, 8, $align_pdf, $table);

        $pdf->Output();
    }

    /**
     * Generates semicolon-delimited CSV file
     *
     * @param string $request Report ID for filename
     * @param string $title Report title
     * @param array $data Result rows
     * @param array $fields Column headers
     */
    private function gen_csv($request, $title, $data, $fields) {
        $res = "";
        $res .= "$title\n";

        foreach ($fields as $field) {
            $res .= "$field; ";
        }
        $res .= "\n";

        foreach ($data as $row) {
            foreach ($row as $elt) {
                $res .= "$elt; ";
            }
            $res .= "\n";
        }

        date_default_timezone_set('Europe/Paris');
        $dt = date("Y_m_d");
        $filename = "gvv_" . $request . "_$dt.csv";

        $CI = &get_instance();
        $CI->load->helper('download');
        force_download($filename, $res);
    }

    /**
     * Legacy unit tests (being migrated to PHPUnit)
     *
     * @deprecated Use PHPUnit tests instead
     */
    function test_methodes() {
        $this->unit->run('Foo', 'is_string', 'test reports');
    }

    /**
     * Legacy test runner
     *
     * @deprecated Use PHPUnit tests instead
     * @param string $format Output format ('html' or 'text')
     */
    function test($format = "html") {
        parent::test($format);

        $this->test_methodes();
        $this->test_model("nom");

        $this->tests_results($format);
    }
}
