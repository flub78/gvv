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
 * Contrôleur de gestion des progressions de formation
 *
 * @package controllers
 */

class Formation_progressions extends CI_Controller
{
    protected $controller = 'formation_progressions';
    protected $unit_test = FALSE;

    function __construct()
    {
        parent::__construct();

        // Check feature flag
        $this->load->library('formation_access');
        $this->formation_access->check_access_or_403();

        $this->load->model('formation_inscription_model');
        $this->load->model('formation_programme_model');
        $this->load->model('membres_model');
        $this->load->library('formation_progression');
        $this->lang->load('formation');
        $this->lang->load('gvv');
    }

    /**
     * Liste toutes les formations en cours avec accès aux fiches de progression
     */
    public function index()
    {
        log_message('debug', 'FORMATION_PROGRESSIONS: index() called');

        $data['title'] = $this->lang->line('formation_progressions_title');
        $data['controller'] = $this->controller;

        // Récupérer toutes les formations ouvertes ou clôturées de la section
        $filters = [];
        $formations = $this->formation_inscription_model->get_all($filters);

        $data['formations'] = $formations;

        return load_last_view('formation_progressions/index', $data, $this->unit_test);
    }

    /**
     * Affiche la fiche de progression d'une formation
     * 
     * @param int $inscription_id ID de la formation
     */
    public function fiche($inscription_id)
    {
        log_message('debug', 'FORMATION_PROGRESSIONS: fiche(' . $inscription_id . ') called');

        // Calculer la progression
        $progression = $this->formation_progression->calculer($inscription_id);

        if (!$progression) {
            show_error('Formation introuvable', 404);
            return;
        }

        $data['title'] = $this->lang->line('formation_progression_fiche_title');
        $data['controller'] = $this->controller;
        $data['progression'] = $progression;
        $data['formation_progression'] = $this->formation_progression; // Pour les helpers

        return load_last_view('formation_progressions/fiche', $data, $this->unit_test);
    }

    /**
     * Exporte la fiche de progression en PDF
     *
     * @param int $inscription_id ID de la formation
     */
    public function export_pdf($inscription_id)
    {
        log_message('debug', 'FORMATION_PROGRESSIONS: export_pdf(' . $inscription_id . ') called');

        // Calculer la progression
        $progression = $this->formation_progression->calculer($inscription_id);

        if (!$progression) {
            show_error('Formation introuvable', 404);
            return;
        }

        $this->load->library('Pdf');
        $pdf = new Pdf('P', 'mm', 'A4');

        $pilote = $progression['pilote'];
        $programme = $progression['programme'];
        $inscription = $progression['inscription'];
        $stats = $progression['stats'];

        $pilote_name = $pilote['mprenom'] . ' ' . $pilote['mnom'];
        $pdf->SetTitle('Fiche de progression - ' . $pilote_name);
        $pdf->set_title('Fiche de progression');

        // Page 1: infos + statistiques + progression
        $pdf->AddPage('P');
        $pdf->title('Fiche de Progression', 1);

        // En-tête: élève et programme
        $pdf->SetFont('DejaVu', 'B', 9);
        $pdf->Cell(30, 6, 'Elève :', 0, 0);
        $pdf->SetFont('DejaVu', '', 9);
        $pdf->Cell(60, 6, $pilote_name, 0, 0);
        $pdf->SetFont('DejaVu', 'B', 9);
        $pdf->Cell(30, 6, 'Programme :', 0, 0);
        $pdf->SetFont('DejaVu', '', 9);
        $pdf->Cell(0, 6, $programme['titre'], 0, 1);

        $pdf->SetFont('DejaVu', 'B', 9);
        $pdf->Cell(30, 6, 'Ouverture :', 0, 0);
        $pdf->SetFont('DejaVu', '', 9);
        $pdf->Cell(60, 6, date('d/m/Y', strtotime($inscription['date_ouverture'])), 0, 0);
        $pdf->SetFont('DejaVu', 'B', 9);
        $pdf->Cell(30, 6, 'Statut :', 0, 0);
        $pdf->SetFont('DejaVu', '', 9);
        $pdf->Cell(0, 6, $this->lang->line('formation_inscription_statut_' . $inscription['statut']), 0, 1);
        $pdf->Ln(4);

        // Statistiques
        $pdf->title('Statistiques', 2);
        $col_w = [45, 45, 45, 45];
        $col_a = ['C', 'C', 'C', 'C'];
        $pdf->set_table_header($col_w, 6, $col_a, [
            ['Séances', 'Heures de vol', 'Atterrissages', '% Acquis']
        ]);
        $pdf->row($col_w, 6, $col_a, [
            $stats['nb_seances'],
            $stats['heures_totales'],
            $stats['atterrissages_totaux'],
            $stats['pourcentage_acquis'] . '%'
        ]);
        $pdf->Ln(4);

        // Répartition des niveaux
        $pdf->title('Répartition des niveaux', 2);
        $col_w2 = [45, 45, 45, 45];
        $col_a2 = ['C', 'C', 'C', 'C'];
        $pdf->set_table_header($col_w2, 6, $col_a2, [
            ['Non abordés', 'Abordés', 'A revoir', 'Acquis']
        ]);
        $pdf->row($col_w2, 6, $col_a2, [
            $stats['nb_sujets_non_abordes'],
            $stats['nb_sujets_abordes'],
            $stats['nb_sujets_a_revoir'],
            $stats['nb_sujets_acquis']
        ]);
        $pdf->Ln(4);

        // Détail par leçon
        $pdf->title('Détail par leçon', 2);

        $sub_w = [15, 80, 30, 20, 30];
        $sub_a = ['L', 'L', 'C', 'C', 'C'];

        foreach ($progression['lecons'] as $lecon) {
            // Titre de la leçon
            $pdf->SetFont('DejaVu', 'B', 8);
            $pdf->Cell(0, 6, 'Leçon ' . $lecon['numero'] . ' : ' . $lecon['titre'], 0, 1);
            $pdf->SetFont('DejaVu', '', 7);

            if (empty($lecon['sujets'])) {
                $pdf->SetFont('DejaVu', 'I', 7);
                $pdf->Cell(0, 5, 'Aucun sujet défini', 0, 1);
                $pdf->SetFont('DejaVu', '', 7);
                continue;
            }

            $pdf->set_table_header($sub_w, 5, $sub_a, [
                ['N°', 'Sujet', 'Niveau', 'Séances', 'Dernière éval']
            ]);

            foreach ($lecon['sujets'] as $sujet) {
                $niveau_label = $this->formation_progression->get_niveau_label($sujet['dernier_niveau']);
                $date_eval = $sujet['date_derniere_eval'] ? date('d/m/Y', strtotime($sujet['date_derniere_eval'])) : '-';

                $pdf->row($sub_w, 5, $sub_a, [
                    $sujet['numero'],
                    $sujet['titre'],
                    $sujet['dernier_niveau'] . ' - ' . $niveau_label,
                    $sujet['nb_seances'],
                    $date_eval
                ]);
            }
            $pdf->Ln(3);
        }

        // Pied de page
        $pdf->Ln(5);
        $pdf->SetFont('DejaVu', 'I', 7);
        $pdf->Cell(0, 5, 'Document généré le ' . date('d/m/Y à H:i'), 0, 1, 'C');

        // Nom du fichier
        $filename = 'progression_' .
                    strtolower($pilote['mnom']) . '_' .
                    strtolower($programme['code']) . '_' .
                    date('Ymd') . '.pdf';

        $pdf->Output($filename, 'I');
    }
}
