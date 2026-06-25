<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Relances — Suivi des comptes membres débiteurs.
 *
 * Phase 1 : affichage de la liste des débiteurs avec seuils d'alerte.
 * Phase 2 : relances email (à implémenter ultérieurement).
 *
 * Rôles autorisés : tresorier, bureau, club-admin.
 *
 * Playwright tests :
 *   cd playwright && npx playwright test tests/relances.spec.js
 */

include('./application/libraries/Gvv_Controller.php');

class Relances extends MY_Controller
{
    function __construct()
    {
        parent::__construct();

        if (!$this->dx_auth->is_logged_in()) {
            redirect('auth/login');
        }

        $this->require_roles(['tresorier', 'bureau', 'club-admin']);

        $this->load->model('relances_model');
        $this->load->model('configuration_model');
        $this->lang->load('relances');
    }

    /**
     * Page principale : liste des débiteurs.
     */
    public function index()
    {
        $seuil_alarme   = (float)($this->configuration_model->get_param('relances.seuil_alarme')   ?? 300);
        $seuil_critique = (float)($this->configuration_model->get_param('relances.seuil_critique') ?? 500);

        $data = $this->relances_model->get_debiteurs();

        load_last_view('relances/bs_relancesView', array(
            'sections'        => $data['sections'],
            'debiteurs'       => $data['rows'],
            'seuil_alarme'    => $seuil_alarme,
            'seuil_critique'  => $seuil_critique,
            'controller'      => 'relances',
        ));
    }

    /**
     * POST : sauvegarde les seuils d'alerte.
     */
    public function update_seuils()
    {
        if (!$this->input->is_ajax_request() && strtolower($_SERVER['REQUEST_METHOD']) !== 'post') {
            redirect('relances/index');
        }

        $seuil_alarme   = (float)$this->input->post('seuil_alarme');
        $seuil_critique = (float)$this->input->post('seuil_critique');

        if ($seuil_alarme <= 0 || $seuil_critique <= 0 || $seuil_alarme >= $seuil_critique) {
            $this->session->set_flashdata('error', $this->lang->line('relances_seuils_invalides'));
            redirect('relances/index');
            return;
        }

        $this->_upsert_config('relances.seuil_alarme',   $seuil_alarme);
        $this->_upsert_config('relances.seuil_critique', $seuil_critique);

        $this->session->set_flashdata('success', $this->lang->line('relances_seuils_sauvegardes'));
        redirect('relances/index');
    }

    /**
     * Export CSV de la liste des débiteurs.
     */
    public function export_csv()
    {
        $data = $this->relances_model->get_debiteurs();
        $sections = $data['sections'];
        $debiteurs = $data['rows'];

        $title = "Relances_debiteurs";

        $header = array($this->lang->line('relances_col_nom'));
        foreach ($sections as $s) {
            $header[] = $s['acronyme'];
        }
        $header[] = $this->lang->line('relances_col_total');
        $header[] = $this->lang->line('relances_col_6mois');
        $header[] = $this->lang->line('relances_col_1an');

        $csv_data = array();
        $csv_data[] = array($this->lang->line('relances_title'));
        $csv_data[] = array($this->lang->line('relances_col_nom'), date('d/m/Y'));
        $csv_data[] = array();
        $csv_data[] = $header;

        foreach ($debiteurs as $d) {
            $row = array($d['mnom'] . ' ' . $d['mprenom']);
            foreach ($sections as $s) {
                $solde = $d['par_section'][$s['id']]['solde'] ?? 0;
                $row[] = $solde != 0 ? number_format($solde, 2, ',', ' ') : '';
            }
            $row[] = number_format($d['total'],    2, ',', ' ');
            $row[] = number_format($d['total_6m'], 2, ',', ' ');
            $row[] = number_format($d['total_1an'], 2, ',', ' ');
            $csv_data[] = $row;
        }

        $this->load->helper('csv');
        csv_file($title, $csv_data);
    }

    /**
     * Export PDF de la liste des débiteurs.
     */
    public function export_pdf()
    {
        $seuil_alarme   = (float)($this->configuration_model->get_param('relances.seuil_alarme')   ?? 300);
        $seuil_critique = (float)($this->configuration_model->get_param('relances.seuil_critique') ?? 500);

        $data = $this->relances_model->get_debiteurs();
        $sections = $data['sections'];
        $debiteurs = $data['rows'];

        $title = $this->lang->line('relances_title');

        $header = array($this->lang->line('relances_col_nom'));
        foreach ($sections as $s) {
            $header[] = $s['acronyme'];
        }
        $header[] = $this->lang->line('relances_col_total');
        $header[] = $this->lang->line('relances_col_6mois');
        $header[] = $this->lang->line('relances_col_1an');

        $this->load->helper('csv');
        $this->load->library('Pdf');
        $pdf = new Pdf();
        $pdf->AddPage('L');
        $pdf->title($title . ' — ' . date('d/m/Y'), 1);

        $nb_cols = count($header);
        $usable  = 270;
        $w_nom   = 50;
        $n_extra = max(1, $nb_cols - 1);
        $w_each  = ($usable - $w_nom) / $n_extra;
        $widths  = array($w_nom);
        $aligns  = array('L');
        for ($i = 1; $i < $nb_cols; $i++) {
            $widths[] = $w_each;
            $aligns[] = 'R';
        }

        // Print header row (bold, dark background)
        $pdf->SetFont('DejaVu', '', 6);
        $pdf->set_table_header($widths, 6, $aligns, array($header));

        // Print data rows with per-row coloring
        // Critique (rouge) : Bootstrap table-danger #f8d7da
        // Alarme (jaune)   : Bootstrap table-warning #fff3cd
        // Pair / impair    : gris clair / blanc
        $row_idx = 0;
        foreach ($debiteurs as $d) {
            $total = $d['total'];
            $abs   = abs($total);

            if ($abs >= $seuil_critique) {
                $pdf->SetFillColor(248, 215, 218); // rouge clair
            } elseif ($abs >= $seuil_alarme) {
                $pdf->SetFillColor(255, 243, 205); // jaune clair
            } elseif ($row_idx % 2 === 0) {
                $pdf->SetFillColor(242, 242, 242); // gris clair (ligne paire)
            } else {
                $pdf->SetFillColor(255, 255, 255); // blanc (ligne impaire)
            }

            $row = array($d['mnom'] . ' ' . $d['mprenom']);
            foreach ($sections as $s) {
                $solde = $d['par_section'][$s['id']]['solde'] ?? 0;
                $row[] = $solde != 0 ? euros($solde, 'pdf') : '';
            }
            $row[] = euros($total, 'pdf');
            $row[] = $d['total_6m'] != 0 ? euros($d['total_6m'], 'pdf') : '';
            $row[] = $d['total_1an'] != 0 ? euros($d['total_1an'], 'pdf') : '';

            $pdf->row($widths, 6, $aligns, $row, 'LRTB', true);
            $row_idx++;
        }

        $pdf->Output('I', pdf_filename($title));
    }

    /**
     * Upsert d'une clé dans la table configuration.
     */
    private function _upsert_config($cle, $valeur)
    {
        $exists = $this->db->where('cle', $cle)->count_all_results('configuration') > 0;
        if ($exists) {
            $this->db->where('cle', $cle)->update('configuration', array(
                'valeur'     => (string)$valeur,
                'updated_at' => date('Y-m-d H:i:s'),
            ));
        } else {
            $this->db->insert('configuration', array(
                'cle'        => $cle,
                'valeur'     => (string)$valeur,
                'categorie'  => 'relances',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ));
        }
    }
}
