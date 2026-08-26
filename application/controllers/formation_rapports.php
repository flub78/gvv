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
 * Contrôleur des rapports de formation
 *
 * @package controllers
 */

include_once(APPPATH . '/third_party/tcpdf/tcpdf.php');

class Formation_rapports extends MY_Controller
{
    protected $controller = 'formation_rapports';

    function __construct()
    {
        parent::__construct();

        // Check feature flag
        $this->load->library('formation_access');
        $this->formation_access->check_access_or_403();

        $this->load->model('formation_inscription_model');
        $this->load->model('formation_seance_model');
        $this->load->model('formation_programme_model');
        $this->load->model('formation_evaluation_model');
        $this->load->model('membres_model');
        $this->load->library('formation_progression');
        $this->load->helper('validation');
        $this->lang->load('formation');
        $this->lang->load('gvv');
        $this->lang->load('sections');

        // Bouton retour → tableau de bord Formation
        $this->lang->load('tableaux_de_bord');
        $this->load->vars([
            'nav_back_url'   => $this->session->userdata('nav_from_url')   ?: 'welcome/section/formation',
            'nav_back_label' => $this->session->userdata('nav_from_label') ?: $this->lang->line('db_section_training'),
        ]);
    }

    /**
     * Libellé de la section actuellement active (sélecteur du menu), ou
     * "Toutes" si aucune section spécifique n'est sélectionnée.
     *
     * @return string
     */
    private function _section_active_label()
    {
        $section = $this->formation_seance_model->section();
        return $section ? $section['nom'] : $this->lang->line('all_sections');
    }

    /**
     * Vue principale des rapports de formation
     */
    public function index()
    {
        log_message('debug', 'FORMATION_RAPPORTS: index() called');

        // Déterminer l'année
        $year = $this->input->get('year');
        if (empty($year)) {
            $year = $this->session->userdata('year');
        }
        if (empty($year)) {
            $year = date('Y');
        }
        $year = (int) $year;

        // Construire le sélecteur d'années
        $year_selector = $this->formation_inscription_model->getYearSelector();
        $seance_years = $this->formation_seance_model->getYearSelector();
        $year_selector = $year_selector + $seance_years;
        ksort($year_selector);

        // S'assurer que l'année courante est dans le sélecteur
        if (!isset($year_selector[$year])) {
            $year_selector[$year] = (string) $year;
        }

        // Récupérer les formations groupées par statut
        $formations = $this->formation_inscription_model->get_by_year($year);

        // Calculer la progression pour les formations en cours
        $date_limite = $year . '-12-31';
        foreach ($formations['en_cours'] as &$inscription) {
            $progression = $this->formation_progression->calculer_pourcentage_a_date(
                $inscription['id'], $date_limite
            );
            $inscription['progression'] = $progression;
        }
        unset($inscription);

        // Séances de ré-entrainement de l'année
        $seances_libres = $this->formation_seance_model->select_page(
            array('type' => 'libre', 'year' => $year, 'section_id' => $this->formation_seance_model->section_id()),
            1000, 0
        );

        // Statistiques par instructeur
        $instructeurs = $this->formation_seance_model->get_stats_par_instructeur($year);

        // Statistiques par catégorie de séance
        $stats_par_categorie = $this->formation_seance_model->count_by_categorie($year);

        // Vols DC (volsa) sans séance de formation déclarée : un vol DC = une
        // séance ; ceux qui ne correspondent à aucune formation_seances
        // (même jour, pilote, instructeur) sont comptabilisés à part.
        $vols_dc_sans_seance = $this->formation_seance_model->get_vols_dc_sans_seance($year);

        $nb_dc_sans_seance = 0;
        foreach ($vols_dc_sans_seance as $row) {
            $nb_dc_sans_seance += (int) $row['nb_vols'];
        }
        if ($nb_dc_sans_seance > 0) {
            $stats_par_categorie[$this->lang->line('formation_rapports_categorie_dc_sans_seance')] = $nb_dc_sans_seance;
            arsort($stats_par_categorie);
        }

        // Intégration des vols DC sans séance dans les stats par instructeur
        $instructeurs_by_id = array();
        foreach ($instructeurs as $inst) {
            $instructeurs_by_id[$inst['id']] = $inst;
        }
        foreach ($vols_dc_sans_seance as $row) {
            $iid = $row['instructeur_id'];
            if (!isset($instructeurs_by_id[$iid])) {
                $instructeurs_by_id[$iid] = array(
                    'id' => $iid,
                    'nom' => $row['instructeur_nom'],
                    'prenom' => $row['instructeur_prenom'],
                    'formations' => array(),
                    'nb_seances_libres' => 0,
                    'vols_dc_sans_seance' => array()
                );
            }
            if (!isset($instructeurs_by_id[$iid]['vols_dc_sans_seance'])) {
                $instructeurs_by_id[$iid]['vols_dc_sans_seance'] = array();
            }
            $instructeurs_by_id[$iid]['vols_dc_sans_seance'][] = $row;
        }
        $instructeurs = array_values($instructeurs_by_id);

        // Heures et vols d'instruction (volsa, DC coché), toutes séances confondues
        $stats_dc_instructeur = $this->formation_seance_model->get_stats_dc_par_instructeur($year);
        $stats_dc_machine = $this->formation_seance_model->get_stats_dc_par_machine($year);

        $data = array(
            'title' => $this->lang->line('formation_rapports_title'),
            'controller' => $this->controller,
            'year' => $year,
            'year_selector' => $year_selector,
            'formations' => $formations,
            'seances_libres' => $seances_libres,
            'instructeurs' => $instructeurs,
            'stats_par_categorie' => $stats_par_categorie,
            'stats_dc_instructeur' => $stats_dc_instructeur,
            'stats_dc_machine' => $stats_dc_machine,
            'formation_progression' => $this->formation_progression,
            'section_active_label' => $this->_section_active_label(),
        );

        $this->load->view('formation_rapports/index', $data);
    }

    /**
     * Change d'année et redirige vers les rapports
     *
     * @param string $year Année sélectionnée
     */
    public function new_year($year)
    {
        $this->session->set_userdata('year', $year);
        redirect('formation_rapports');
    }


    /**
     * Rapport annuel consolidé (vol + théorique) par instructeur et par programme.
     *
     * @param int|null $year Année (défaut : année de session ou année courante)
     */
    public function annuel($year = null)
    {
        if (empty($year)) {
            $year = $this->input->get('year') ?: $this->session->userdata('year') ?: date('Y');
        }
        $year = (int) $year;

        $year_selector = $this->formation_seance_model->getYearSelector();
        if (!isset($year_selector[$year])) {
            $year_selector[$year] = (string) $year;
        }
        ksort($year_selector);

        $stats_instructeurs = $this->formation_seance_model->get_stats_annuels_par_instructeur($year);
        $stats_programmes   = $this->formation_seance_model->get_stats_annuels_par_programme($year);

        $data = array(
            'title'                => $this->lang->line('formation_rapports_annuel_title'),
            'controller'           => $this->controller,
            'year'                 => $year,
            'year_selector'        => $year_selector,
            'stats_instructeurs'   => $stats_instructeurs,
            'stats_programmes'     => $stats_programmes,
            'section_active_label' => $this->_section_active_label(),
        );

        $this->load->view('formation_rapports/annuel', $data);
    }

    /**
     * Change d'année et redirige vers le rapport annuel.
     *
     * @param string $year Année sélectionnée
     */
    public function new_year_annuel($year)
    {
        $this->session->set_userdata('year', $year);
        redirect('formation_rapports/annuel');
    }

    /**
     * Rapport de conformité : pilotes ne respectant pas la périodicité.
     */
    public function conformite()
    {
        $this->load->model('formation_type_seance_model');

        $types   = $this->formation_type_seance_model->get_with_periodicite();
        $rapport = array();
        foreach ($types as $type) {
            $rapport[] = array(
                'type'          => $type,
                'non_conformes' => $this->formation_type_seance_model->get_eleves_non_conformes($type['id']),
            );
        }

        $data = array(
            'title'      => $this->lang->line('formation_rapports_conformite_title'),
            'controller' => $this->controller,
            'rapport'    => $rapport,
        );

        $this->load->view('formation_rapports/conformite', $data);
    }

    /**
     * Export CSV du rapport annuel consolidé par instructeur.
     *
     * @param int $year
     */
    public function export_annuel_csv($year = null)
    {
        if (empty($year)) {
            $year = $this->session->userdata('year') ?: date('Y');
        }
        $year = (int) $year;

        $this->load->helper('csv');
        $stats = $this->formation_seance_model->get_stats_annuels_par_instructeur($year);
        $title = $this->lang->line('formation_rapports_annuel_title') . ' ' . $year;

        $rows   = array();
        $rows[] = array(
            $this->lang->line('formation_inscription_instructeur'),
            $this->lang->line('formation_rapports_annuel_nb_seances_vol'),
            $this->lang->line('formation_rapports_annuel_heures_vol'),
            $this->lang->line('formation_rapports_annuel_nb_eleves_vol'),
            $this->lang->line('formation_rapports_annuel_nb_seances_sol'),
            $this->lang->line('formation_rapports_annuel_heures_sol'),
            $this->lang->line('formation_rapports_annuel_nb_eleves_sol'),
        );
        foreach ($stats as $s) {
            $rows[] = array(
                trim($s['prenom'] . ' ' . $s['nom']),
                $s['nb_seances_vol'],
                $s['heures_vol'],
                $s['nb_eleves_vol'],
                $s['nb_seances_sol'],
                $s['heures_sol'],
                $s['nb_eleves_sol'],
            );
        }

        csv_file($title, $rows);
    }

    /**
     * Export PDF du rapport annuel consolidé (par instructeur et par programme).
     *
     * @param int $year
     */
    public function export_annuel_pdf($year = null)
    {
        if (empty($year)) {
            $year = $this->session->userdata('year') ?: date('Y');
        }
        $year = (int) $year;

        $stats_instructeurs = $this->formation_seance_model->get_stats_annuels_par_instructeur($year);
        $stats_programmes   = $this->formation_seance_model->get_stats_annuels_par_programme($year);

        // Listes de formations (mêmes données/colonnes que formation_rapports/index)
        $formations = $this->formation_inscription_model->get_by_year($year);
        $date_limite = $year . '-12-31';
        foreach ($formations['en_cours'] as &$inscription) {
            $inscription['progression'] = $this->formation_progression->calculer_pourcentage_a_date(
                $inscription['id'], $date_limite
            );
        }
        unset($inscription);

        $nom_club = $this->config->item('nom_club');
        $title    = $this->lang->line('formation_rapports_annuel_title') . ' ' . $year;

        $html = '<p style="text-align:right;color:#555;font-size:9pt;">' . htmlspecialchars($nom_club) . '</p>';
        $html .= '<h1>' . htmlspecialchars($title) . '</h1>';
        $html .= '<p style="font-size:9pt;color:#777;">'
            . $this->lang->line('formation_rapports_annuel_genere_le') . ' ' . date('d/m/Y H:i')
            . ' — ' . $this->lang->line('formation_rapports_section_active') . ' : '
            . htmlspecialchars($this->_section_active_label())
            . '</p>';
        $html .= '<hr>';

        // Clôturées avec succès
        $rows = array();
        foreach ($formations['cloturees'] as $f) {
            $rows[] = array(
                htmlspecialchars(trim(($f['pilote_prenom'] ?? '') . ' ' . ($f['pilote_nom'] ?? ''))),
                htmlspecialchars($f['programme_titre'] ?? ''),
                htmlspecialchars(trim(($f['instructeur_prenom'] ?? '') . ' ' . ($f['instructeur_nom'] ?? ''))),
                !empty($f['date_cloture']) ? date('d/m/Y', strtotime($f['date_cloture'])) : '-',
            );
        }
        $html .= $this->_pdf_section_table(
            $this->lang->line('formation_rapports_cloturees_succes') . ' (' . count($formations['cloturees']) . ')',
            array(
                $this->lang->line('formation_inscription_pilote'),
                $this->lang->line('formation_inscription_programme'),
                $this->lang->line('formation_inscription_instructeur'),
                $this->lang->line('formation_rapports_date_cloture'),
            ),
            $rows
        );

        // Abandonnées
        $rows = array();
        foreach ($formations['abandonnees'] as $f) {
            $rows[] = array(
                htmlspecialchars(trim(($f['pilote_prenom'] ?? '') . ' ' . ($f['pilote_nom'] ?? ''))),
                htmlspecialchars($f['programme_titre'] ?? ''),
                !empty($f['date_cloture']) ? date('d/m/Y', strtotime($f['date_cloture'])) : '-',
                htmlspecialchars($f['motif_cloture'] ?? ''),
            );
        }
        $html .= $this->_pdf_section_table(
            $this->lang->line('formation_rapports_abandonnees') . ' (' . count($formations['abandonnees']) . ')',
            array(
                $this->lang->line('formation_inscription_pilote'),
                $this->lang->line('formation_inscription_programme'),
                $this->lang->line('formation_rapports_date_cloture'),
                $this->lang->line('formation_rapports_motif'),
            ),
            $rows
        );

        // Suspendues
        $rows = array();
        foreach ($formations['suspendues'] as $f) {
            $rows[] = array(
                htmlspecialchars(trim(($f['pilote_prenom'] ?? '') . ' ' . ($f['pilote_nom'] ?? ''))),
                htmlspecialchars($f['programme_titre'] ?? ''),
                !empty($f['date_suspension']) ? date('d/m/Y', strtotime($f['date_suspension'])) : '-',
                htmlspecialchars($f['motif_suspension'] ?? ''),
            );
        }
        $html .= $this->_pdf_section_table(
            $this->lang->line('formation_rapports_suspendues') . ' (' . count($formations['suspendues']) . ')',
            array(
                $this->lang->line('formation_inscription_pilote'),
                $this->lang->line('formation_inscription_programme'),
                $this->lang->line('formation_rapports_date_suspension'),
                $this->lang->line('formation_rapports_motif'),
            ),
            $rows
        );

        // Ouvertes dans l'année
        $rows = array();
        foreach ($formations['ouvertes'] as $f) {
            $rows[] = array(
                htmlspecialchars(trim(($f['pilote_prenom'] ?? '') . ' ' . ($f['pilote_nom'] ?? ''))),
                htmlspecialchars($f['programme_titre'] ?? ''),
                htmlspecialchars(trim(($f['instructeur_prenom'] ?? '') . ' ' . ($f['instructeur_nom'] ?? ''))),
                !empty($f['date_ouverture']) ? date('d/m/Y', strtotime($f['date_ouverture'])) : '-',
                htmlspecialchars($this->lang->line('formation_inscription_statut_' . $f['statut'])),
            );
        }
        $html .= $this->_pdf_section_table(
            $this->lang->line('formation_rapports_ouvertes') . ' (' . count($formations['ouvertes']) . ')',
            array(
                $this->lang->line('formation_inscription_pilote'),
                $this->lang->line('formation_inscription_programme'),
                $this->lang->line('formation_inscription_instructeur'),
                $this->lang->line('formation_inscription_date_ouverture'),
                $this->lang->line('formation_inscription_statut'),
            ),
            $rows
        );

        // En cours (avec progression)
        $rows = array();
        foreach ($formations['en_cours'] as $f) {
            $pct = isset($f['progression']) ? $f['progression']['pourcentage'] : 0;
            $rows[] = array(
                htmlspecialchars(trim(($f['pilote_prenom'] ?? '') . ' ' . ($f['pilote_nom'] ?? ''))),
                htmlspecialchars($f['programme_titre'] ?? ''),
                htmlspecialchars(trim(($f['instructeur_prenom'] ?? '') . ' ' . ($f['instructeur_nom'] ?? ''))),
                !empty($f['date_ouverture']) ? date('d/m/Y', strtotime($f['date_ouverture'])) : '-',
                $pct . ' %',
            );
        }
        $html .= $this->_pdf_section_table(
            $this->lang->line('formation_rapports_en_cours') . ' (' . count($formations['en_cours']) . ')',
            array(
                $this->lang->line('formation_inscription_pilote'),
                $this->lang->line('formation_inscription_programme'),
                $this->lang->line('formation_inscription_instructeur'),
                $this->lang->line('formation_inscription_date_ouverture'),
                $this->lang->line('formation_rapports_progression'),
            ),
            $rows
        );

        $html .= '<hr>';

        // Par instructeur
        $html .= '<div style="page-break-inside:avoid;">';
        $html .= '<h2>' . $this->lang->line('formation_rapports_annuel_par_instructeur') . '</h2>';
        $html .= '<table border="1" cellpadding="3" cellspacing="0" style="width:100%;font-size:8pt;">';
        $html .= '<tr style="background-color:#eee;">'
            . '<th>' . $this->lang->line('formation_inscription_instructeur') . '</th>'
            . '<th>' . $this->lang->line('formation_rapports_annuel_nb_seances_vol') . '</th>'
            . '<th>' . $this->lang->line('formation_rapports_annuel_heures_vol') . '</th>'
            . '<th>' . $this->lang->line('formation_rapports_annuel_nb_eleves_vol') . '</th>'
            . '<th>' . $this->lang->line('formation_rapports_annuel_nb_seances_sol') . '</th>'
            . '<th>' . $this->lang->line('formation_rapports_annuel_heures_sol') . '</th>'
            . '<th>' . $this->lang->line('formation_rapports_annuel_nb_eleves_sol') . '</th>'
            . '<th>' . $this->lang->line('formation_rapports_nb_seances') . '</th>'
            . '</tr>';

        $tot_sv = $tot_hv = $tot_ev = $tot_ss = $tot_hs = $tot_es = $tot_t = 0;
        foreach ($stats_instructeurs as $s) {
            $total   = $s['nb_seances_vol'] + $s['nb_seances_sol'];
            $tot_sv += $s['nb_seances_vol'];
            $tot_hv += $s['heures_vol'];
            $tot_ev += $s['nb_eleves_vol'];
            $tot_ss += $s['nb_seances_sol'];
            $tot_hs += $s['heures_sol'];
            $tot_es += $s['nb_eleves_sol'];
            $tot_t  += $total;

            $html .= '<tr>'
                . '<td>' . htmlspecialchars(trim($s['prenom'] . ' ' . $s['nom'])) . '</td>'
                . '<td align="center">' . ($s['nb_seances_vol'] ?: '-') . '</td>'
                . '<td align="center">' . ($s['heures_vol'] > 0 ? number_format($s['heures_vol'], 1, ',', '') . ' h' : '-') . '</td>'
                . '<td align="center">' . ($s['nb_eleves_vol'] ?: '-') . '</td>'
                . '<td align="center">' . ($s['nb_seances_sol'] ?: '-') . '</td>'
                . '<td align="center">' . ($s['heures_sol'] > 0 ? number_format($s['heures_sol'], 1, ',', '') . ' h' : '-') . '</td>'
                . '<td align="center">' . ($s['nb_eleves_sol'] ?: '-') . '</td>'
                . '<td align="center"><b>' . $total . '</b></td>'
                . '</tr>';
        }
        $html .= '<tr style="background-color:#eee;">'
            . '<th>' . $this->lang->line('gvv_total') . '</th>'
            . '<th>' . $tot_sv . '</th>'
            . '<th>' . number_format($tot_hv, 1, ',', '') . ' h</th>'
            . '<th>' . $tot_ev . '</th>'
            . '<th>' . $tot_ss . '</th>'
            . '<th>' . number_format($tot_hs, 1, ',', '') . ' h</th>'
            . '<th>' . $tot_es . '</th>'
            . '<th>' . $tot_t . '</th>'
            . '</tr>';
        $html .= '</table>';
        $html .= '</div>';

        // Par programme
        $html .= '<div style="page-break-inside:avoid;">';
        $html .= '<h2>' . $this->lang->line('formation_rapports_annuel_par_programme') . '</h2>';
        $html .= '<table border="1" cellpadding="3" cellspacing="0" style="width:100%;font-size:8pt;">';
        $html .= '<tr style="background-color:#eee;">'
            . '<th>' . $this->lang->line('formation_seance_programme') . '</th>'
            . '<th>' . $this->lang->line('formation_rapports_annuel_nb_seances_vol') . '</th>'
            . '<th>' . $this->lang->line('formation_rapports_annuel_heures_vol') . '</th>'
            . '<th>' . $this->lang->line('formation_rapports_annuel_nb_seances_sol') . '</th>'
            . '<th>' . $this->lang->line('formation_rapports_annuel_heures_sol') . '</th>'
            . '</tr>';

        $tot_p_sv = $tot_p_hv = $tot_p_ss = $tot_p_hs = 0;
        foreach ($stats_programmes as $p) {
            $tot_p_sv += $p['nb_seances_vol'];
            $tot_p_hv += $p['heures_vol'];
            $tot_p_ss += $p['nb_seances_sol'];
            $tot_p_hs += $p['heures_sol'];

            $html .= '<tr>'
                . '<td>' . htmlspecialchars($p['programme_titre']) . '</td>'
                . '<td align="center">' . ($p['nb_seances_vol'] ?: '-') . '</td>'
                . '<td align="center">' . ($p['heures_vol'] > 0 ? number_format($p['heures_vol'], 1, ',', '') . ' h' : '-') . '</td>'
                . '<td align="center">' . ($p['nb_seances_sol'] ?: '-') . '</td>'
                . '<td align="center">' . ($p['heures_sol'] > 0 ? number_format($p['heures_sol'], 1, ',', '') . ' h' : '-') . '</td>'
                . '</tr>';
        }
        $html .= '<tr style="background-color:#eee;">'
            . '<th>' . $this->lang->line('gvv_total') . '</th>'
            . '<th>' . $tot_p_sv . '</th>'
            . '<th>' . number_format($tot_p_hv, 1, ',', '') . ' h</th>'
            . '<th>' . $tot_p_ss . '</th>'
            . '<th>' . number_format($tot_p_hs, 1, ',', '') . ' h</th>'
            . '</tr>';
        $html .= '</table>';
        $html .= '</div>';

        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator($nom_club);
        $pdf->SetAuthor($nom_club);
        $pdf->SetTitle($title);
        $pdf->SetSubject($this->lang->line('formation_rapports_annuel_title'));

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);

        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);
        $pdf->writeHTML($html, true, false, true, false, '');

        $filename = 'rapport_annuel_formation_' . $year . '.pdf';
        $pdf->Output($filename, 'I');
        exit;
    }

    /**
     * Génère le HTML d'un tableau de section pour export_annuel_pdf()
     * (cellules déjà formatées/échappées par l'appelant).
     *
     * @param string $title   Titre de section (avec effectif entre parenthèses)
     * @param array  $headers En-têtes de colonnes
     * @param array  $rows    Lignes, chacune un tableau de cellules HTML
     * @return string
     */
    private function _pdf_section_table($title, array $headers, array $rows)
    {
        // page-break-inside:avoid pousse tout le bloc (titre + tableau) sur la
        // page suivante s'il ne tient pas dans l'espace restant, pour ne
        // jamais laisser le titre seul en bas de page.
        $html = '<div style="page-break-inside:avoid;">';
        $html .= '<h2>' . htmlspecialchars($title) . '</h2>';

        if (empty($rows)) {
            $html .= '<p style="font-size:8pt;color:#777;">' . $this->lang->line('formation_rapports_aucune') . '</p>';
            $html .= '</div>';
            return $html;
        }

        $html .= '<table border="1" cellpadding="3" cellspacing="0" style="width:100%;font-size:8pt;">';
        $html .= '<tr style="background-color:#eee;">';
        foreach ($headers as $h) {
            $html .= '<th>' . htmlspecialchars($h) . '</th>';
        }
        $html .= '</tr>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . $cell . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Export CSV du rapport de conformité pour un type de séance.
     *
     * @param int $type_id
     */
    public function export_conformite_csv($type_id)
    {
        $this->load->model('formation_type_seance_model');
        $this->load->helper('csv');

        $type = $this->formation_type_seance_model->get_by_id('id', (int) $type_id);
        if (!$type) {
            show_404();
        }

        $non_conformes = $this->formation_type_seance_model->get_eleves_non_conformes((int) $type_id);
        $title         = $this->lang->line('formation_rapports_conformite_title') . ' - ' . $type['nom'];

        $rows   = array();
        $rows[] = array(
            $this->lang->line('formation_rapports_conformite_pilote'),
            $this->lang->line('formation_rapports_conformite_derniere_seance'),
            $this->lang->line('formation_rapports_conformite_jours_ecoules'),
            $this->lang->line('formation_rapports_conformite_periodicite'),
        );
        foreach ($non_conformes as $p) {
            $rows[] = array(
                trim($p['mprenom'] . ' ' . $p['mnom']),
                !empty($p['derniere_seance']) ? $p['derniere_seance'] : '-',
                $p['jours_ecoules'] !== null ? $p['jours_ecoules'] : $this->lang->line('formation_rapports_conformite_jamais'),
                $p['periodicite_max_jours'],
            );
        }

        csv_file($title, $rows);
    }
}
