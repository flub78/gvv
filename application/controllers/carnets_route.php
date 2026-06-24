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
 * @filesource carnets_route.php
 * @package controllers
 */

/**
 * Contrôle des carnets de route.
 *
 * Affiche les vols d'une machine sur une période et met en évidence
 * les discontinuités d'horamètre.
 */
class Carnets_route extends MY_Controller {

    protected $controller = 'carnets_route';
    protected $data = array();

    function __construct() {
        parent::__construct();

        if (!$this->dx_auth->is_logged_in()) {
            redirect('auth/login');
        }

        $this->require_roles(['ca', 'club-admin']);

        $this->load->model('carnets_route_model');
        $this->load->helper('carnets_route');
        $this->load->helper('validation');
        $this->load->helper('date');
        $this->lang->load('gvv');
        $this->lang->load('carnets_route');
    }

    /**
     * Sauvegarde les filtres en session et redirige vers page().
     */
    public function filter() {
        $macid      = $this->input->post('carnet_macid');
        $date_debut = $this->input->post('carnet_date_debut');
        $date_fin   = $this->input->post('carnet_date_fin');

        $this->session->set_userdata([
            'carnet_macid'      => $macid,
            'carnet_date_debut' => $date_debut,
            'carnet_date_fin'   => $date_fin,
        ]);

        redirect($this->controller . '/page');
    }

    /**
     * Affichage principal du contrôle de continuité.
     */
    public function page() {
        $macid      = $this->session->userdata('carnet_macid');
        $date_debut = $this->session->userdata('carnet_date_debut');
        $date_fin   = $this->session->userdata('carnet_date_fin');

        if (empty($date_debut)) {
            $date_debut = date('Y') . '-01-01';
        }
        if (empty($date_fin)) {
            $date_fin = date('Y-m-d');
        }

        $avion_selector = $this->carnets_route_model->get_avions();

        $rows    = [];
        $summary = ['gap' => 0, 'overlap' => 0, 'missing' => 0];
        $error   = '';

        if (!empty($macid)) {
            if ($date_debut > $date_fin) {
                $error = $this->lang->line('carnets_route_error_dates');
            } else {
                $flights = $this->carnets_route_model->get_flights($macid, $date_debut, $date_fin);
                $rows    = build_continuity_rows($flights);
                $summary = compute_continuity_summary($rows);
            }
        }

        $this->data['macid']          = $macid;
        $this->data['date_debut']     = $date_debut;
        $this->data['date_fin']       = $date_fin;
        $this->data['avion_selector'] = $avion_selector;
        $this->data['rows']           = $rows;
        $this->data['summary']        = $summary;
        $this->data['error']          = $error;

        return load_last_view('carnets_route/page', $this->data);
    }

    /**
     * Export CSV du contrôle de continuité.
     */
    public function csv() {
        $macid      = $this->session->userdata('carnet_macid');
        $date_debut = $this->session->userdata('carnet_date_debut') ?: date('Y') . '-01-01';
        $date_fin   = $this->session->userdata('carnet_date_fin')   ?: date('Y-m-d');

        if (empty($macid)) {
            redirect($this->controller . '/page');
            return;
        }

        $flights = $this->carnets_route_model->get_flights($macid, $date_debut, $date_fin);
        $rows    = build_continuity_rows($flights);

        $filename = 'carnet_route_' . $macid . '_' . $date_debut . '_' . $date_fin . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

        fputcsv($out, [
            $this->lang->line('carnets_route_col_date'),
            $this->lang->line('carnets_route_col_pilote'),
            $this->lang->line('carnets_route_col_immat'),
            $this->lang->line('carnets_route_col_hora_deb'),
            $this->lang->line('carnets_route_col_hora_fin'),
            $this->lang->line('carnets_route_col_duree'),
            $this->lang->line('carnets_route_col_depart'),
            $this->lang->line('carnets_route_col_arrivee'),
            $this->lang->line('carnets_route_col_obs'),
        ], ';');

        foreach ($rows as $row) {
            if ($row['type'] === 'flight') {
                $f = $row['data'];
                $mode = isset($f['horametre_mode']) ? (int)$f['horametre_mode'] : 0;
                fputcsv($out, [
                    date_db2ht($f['vadate']),
                    $f['pilote'],
                    $f['vamacid'],
                    horametre_display($f['vacdeb'], $mode),
                    horametre_display($f['vacfin'], $mode),
                    horametre_display($f['vaduree'], $mode),
                    $f['valieudeco'],
                    $f['valieuatt'],
                    $f['vaobs'],
                ], ';');
            } else {
                $label = '[' . strtoupper($this->lang->line('carnets_route_' . $row['type'])) . ']';
                if ($row['duration'] > 0) {
                    $label .= ' ' . $row['duration'];
                }
                fputcsv($out, [$label, '', '', '', '', '', '', '', ''], ';');
            }
        }

        fclose($out);
    }

    /**
     * Export PDF du contrôle de continuité avec coloration des anomalies.
     */
    public function pdf() {
        $macid      = $this->session->userdata('carnet_macid');
        $date_debut = $this->session->userdata('carnet_date_debut') ?: date('Y') . '-01-01';
        $date_fin   = $this->session->userdata('carnet_date_fin')   ?: date('Y-m-d');

        if (empty($macid)) {
            redirect($this->controller . '/page');
            return;
        }

        $flights = $this->carnets_route_model->get_flights($macid, $date_debut, $date_fin);
        $rows    = build_continuity_rows($flights);

        $this->load->library('Pdf');
        $pdf = new Pdf();

        $titre = $this->lang->line('carnets_route_title') . ' — ' . $macid
               . ' (' . date_db2ht($date_debut) . ' – ' . date_db2ht($date_fin) . ')';
        $pdf->set_title($titre);
        $pdf->AddPage('L');
        $pdf->title($titre, 1);

        $header = [
            $this->lang->line('carnets_route_col_date'),
            $this->lang->line('carnets_route_col_pilote'),
            $this->lang->line('carnets_route_col_immat'),
            $this->lang->line('carnets_route_col_hora_deb'),
            $this->lang->line('carnets_route_col_hora_fin'),
            $this->lang->line('carnets_route_col_duree'),
            $this->lang->line('carnets_route_col_depart'),
            $this->lang->line('carnets_route_col_arrivee'),
            $this->lang->line('carnets_route_col_obs'),
        ];

        // Calcul dynamique de la largeur de la colonne observation
        $fixed_widths = [22, 40, 20, 22, 22, 18, 20, 20];
        $usable_width = $pdf->GetPageWidth() - 20; // marges gauche + droite = 2 × 10 mm
        $obs_width    = $usable_width - array_sum($fixed_widths);
        $widths       = array_merge($fixed_widths, [$obs_width]);

        // Header row
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetFont('DejaVu', 'B', 8);
        foreach ($header as $i => $h) {
            $pdf->Cell($widths[$i], 6, $h, 1, 0, 'C', true);
        }
        $pdf->Ln();

        $pdf->SetFont('DejaVu', '', 8);

        foreach ($rows as $row) {
            if ($row['type'] === 'flight') {
                $f    = $row['data'];
                $mode = isset($f['horametre_mode']) ? (int)$f['horametre_mode'] : 0;

                if ($f['status'] === 'ok') {
                    $pdf->SetFillColor(198, 239, 206); // vert
                } else {
                    $pdf->SetFillColor(255, 199, 206); // rouge
                }

                $cells = [
                    date_db2ht($f['vadate']),
                    $f['pilote'],
                    $f['vamacid'],
                    horametre_display($f['vacdeb'], $mode),
                    horametre_display($f['vacfin'], $mode),
                    horametre_display($f['vaduree'], $mode),
                    $f['valieudeco'],
                    $f['valieuatt'],
                    $f['vaobs'],
                ];
                foreach ($cells as $i => $cell) {
                    $pdf->Cell($widths[$i], 5, $cell, 1, 0, 'L', true);
                }
                $pdf->Ln();
            } else {
                // Ligne intermédiaire
                if ($row['type'] === 'gap') {
                    $pdf->SetFillColor(255, 235, 156); // orange
                } elseif ($row['type'] === 'overlap') {
                    $pdf->SetFillColor(255, 199, 206); // rouge
                } else {
                    $pdf->SetFillColor(210, 210, 210); // gris
                }

                $label = strtoupper($this->lang->line('carnets_route_' . $row['type']));
                if ($row['duration'] > 0) {
                    $label .= ' : ' . $row['duration'];
                }
                $pdf->Cell($usable_width, 5, $label, 1, 0, 'C', true);
                $pdf->Ln();
            }
        }

        $pdf->Output($macid . '_carnet_route.pdf', 'I');
    }
}
