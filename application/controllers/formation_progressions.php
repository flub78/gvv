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

        // Enrichir avec infos pilote et programme
        foreach ($formations as &$formation) {
            // Récupérer pilote
            $pilote_query = $this->db->where('id', $formation['pilote_id'])->get('membres');
            if ($pilote_query && $pilote_query->num_rows() > 0) {
                $pilote = $pilote_query->row();
                $formation['pilote_nom'] = $pilote->nom ?? '';
                $formation['pilote_prenom'] = $pilote->prenom ?? '';
            } else {
                $formation['pilote_nom'] = '';
                $formation['pilote_prenom'] = '';
            }
            
            // Récupérer programme
            $programme = $this->formation_programme_model->get($formation['programme_id']);
            $formation['programme_titre'] = $programme->titre ?? '';
            $formation['programme_code'] = $programme->code ?? '';
        }
        unset($formation);

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

        // Charger TCPDF
        $this->load->library('pdf');

        // Configuration du PDF
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Informations du document
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor($this->config->item('club_name'));
        $pdf->SetTitle('Fiche de progression - ' . $progression['pilote']['prenom'] . ' ' . $progression['pilote']['nom']);
        $pdf->SetSubject('Formation ' . $progression['programme']['titre']);

        // Supprimer header/footer par défaut
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Marges
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);

        // Police
        $pdf->SetFont('helvetica', '', 10);

        // Ajouter une page
        $pdf->AddPage();

        // Générer le contenu HTML
        $html = $this->_generate_pdf_html($progression);

        // Écrire le HTML
        $pdf->writeHTML($html, true, false, true, false, '');

        // Nom du fichier
        $filename = 'progression_' . 
                    strtolower($progression['pilote']['nom']) . '_' .
                    strtolower($progression['programme']['code']) . '_' .
                    date('Ymd') . '.pdf';

        // Output PDF
        $pdf->Output($filename, 'D'); // D = téléchargement
    }

    /**
     * Génère le HTML pour le PDF
     * 
     * @param array $progression Données de progression
     * @return string HTML
     */
    private function _generate_pdf_html($progression)
    {
        $stats = $progression['stats'];
        $pilote = $progression['pilote'];
        $programme = $progression['programme'];

        $html = '<h1 style="color: #0066cc;">Fiche de Progression</h1>';

        // En-tête
        $html .= '<table style="width: 100%; border: 1px solid #ddd; margin-bottom: 20px;">';
        $html .= '<tr>';
        $html .= '<td style="width: 50%; padding: 10px;"><strong>Élève :</strong> ' . htmlspecialchars($pilote['prenom'] . ' ' . $pilote['nom']) . '</td>';
        $html .= '<td style="width: 50%; padding: 10px;"><strong>Programme :</strong> ' . htmlspecialchars($programme['titre']) . '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td style="padding: 10px;"><strong>Date d\'ouverture :</strong> ' . date('d/m/Y', strtotime($progression['inscription']['date_ouverture'])) . '</td>';
        $html .= '<td style="padding: 10px;"><strong>Statut :</strong> ' . $this->lang->line('formation_inscription_statut_' . $progression['inscription']['statut']) . '</td>';
        $html .= '</tr>';
        $html .= '</table>';

        // Statistiques
        $html .= '<h2 style="color: #0066cc; margin-top: 20px;">Statistiques</h2>';
        $html .= '<table style="width: 100%; border: 1px solid #ddd; margin-bottom: 20px;">';
        $html .= '<tr>';
        $html .= '<td style="width: 33%; padding: 10px; text-align: center; background-color: #f0f0f0;">';
        $html .= '<strong>Séances</strong><br/><span style="font-size: 20px; color: #0066cc;">' . $stats['nb_seances'] . '</span>';
        $html .= '</td>';
        $html .= '<td style="width: 33%; padding: 10px; text-align: center; background-color: #f8f8f8;">';
        $html .= '<strong>Heures de vol</strong><br/><span style="font-size: 20px; color: #0066cc;">' . $stats['heures_totales'] . '</span>';
        $html .= '</td>';
        $html .= '<td style="width: 33%; padding: 10px; text-align: center; background-color: #f0f0f0;">';
        $html .= '<strong>Atterrissages</strong><br/><span style="font-size: 20px; color: #0066cc;">' . $stats['atterrissages_totaux'] . '</span>';
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</table>';

        // Progression
        $pourcentage = $stats['pourcentage_acquis'];
        $html .= '<h2 style="color: #0066cc; margin-top: 20px;">Progression</h2>';
        $html .= '<p style="font-size: 14px;"><strong>' . $pourcentage . '%</strong> des sujets acquis (' . $stats['nb_sujets_acquis'] . '/' . $stats['nb_sujets_total'] . ')</p>';
        
        // Barre de progression simplifiée
        $bar_color = '#dc3545'; // rouge
        if ($pourcentage >= 75) {
            $bar_color = '#28a745'; // vert
        } elseif ($pourcentage >= 50) {
            $bar_color = '#17a2b8'; // bleu
        } elseif ($pourcentage >= 25) {
            $bar_color = '#ffc107'; // orange
        }
        
        $html .= '<div style="width: 100%; height: 30px; background-color: #e9ecef; border-radius: 5px; overflow: hidden; margin-bottom: 20px;">';
        $html .= '<div style="width: ' . $pourcentage . '%; height: 100%; background-color: ' . $bar_color . ';"></div>';
        $html .= '</div>';

        // Répartition des niveaux
        $html .= '<table style="width: 100%; border: 1px solid #ddd; margin-bottom: 20px; font-size: 12px;">';
        $html .= '<tr style="background-color: #f0f0f0;">';
        $html .= '<td style="padding: 8px; text-align: center;"><strong>Non abordés</strong></td>';
        $html .= '<td style="padding: 8px; text-align: center;"><strong>Abordés</strong></td>';
        $html .= '<td style="padding: 8px; text-align: center;"><strong>À revoir</strong></td>';
        $html .= '<td style="padding: 8px; text-align: center;"><strong>Acquis</strong></td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td style="padding: 8px; text-align: center; font-size: 18px;">' . $stats['nb_sujets_non_abordes'] . '</td>';
        $html .= '<td style="padding: 8px; text-align: center; font-size: 18px;">' . $stats['nb_sujets_abordes'] . '</td>';
        $html .= '<td style="padding: 8px; text-align: center; font-size: 18px;">' . $stats['nb_sujets_a_revoir'] . '</td>';
        $html .= '<td style="padding: 8px; text-align: center; font-size: 18px; color: #28a745;"><strong>' . $stats['nb_sujets_acquis'] . '</strong></td>';
        $html .= '</tr>';
        $html .= '</table>';

        // Détail par leçon
        $html .= '<h2 style="color: #0066cc; margin-top: 20px;">Détail par leçon</h2>';

        foreach ($progression['lecons'] as $lecon) {
            $html .= '<h3 style="color: #495057; margin-top: 15px; margin-bottom: 10px;">Leçon ' . $lecon['numero'] . ' : ' . htmlspecialchars($lecon['titre']) . '</h3>';
            
            if (empty($lecon['sujets'])) {
                $html .= '<p style="font-style: italic; color: #999;">Aucun sujet défini</p>';
                continue;
            }
            
            $html .= '<table style="width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 11px;">';
            $html .= '<tr style="background-color: #f8f9fa;">';
            $html .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left; width: 10%;">N°</th>';
            $html .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left; width: 50%;">Sujet</th>';
            $html .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: center; width: 15%;">Niveau</th>';
            $html .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: center; width: 10%;">Séances</th>';
            $html .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: center; width: 15%;">Dernière éval</th>';
            $html .= '</tr>';
            
            foreach ($lecon['sujets'] as $sujet) {
                $niveau_label = $this->formation_progression->get_niveau_label($sujet['dernier_niveau']);
                $date_eval = $sujet['date_derniere_eval'] ? date('d/m/Y', strtotime($sujet['date_derniere_eval'])) : '-';
                
                $html .= '<tr>';
                $html .= '<td style="border: 1px solid #ddd; padding: 8px;">' . $sujet['numero'] . '</td>';
                $html .= '<td style="border: 1px solid #ddd; padding: 8px;">' . htmlspecialchars($sujet['titre']) . '</td>';
                $html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: center;"><strong>' . $niveau_label . '</strong></td>';
                $html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: center;">' . $sujet['nb_seances'] . '</td>';
                $html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: center;">' . $date_eval . '</td>';
                $html .= '</tr>';
            }
            
            $html .= '</table>';
        }

        // Pied de page
        $html .= '<p style="margin-top: 30px; font-size: 10px; color: #999; text-align: center;">';
        $html .= 'Document généré le ' . date('d/m/Y à H:i') . ' - ' . $this->config->item('club_name');
        $html .= '</p>';

        return $html;
    }
}
