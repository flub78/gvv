<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

include_once(APPPATH . '/third_party/tcpdf/tcpdf.php');

/**
 * Maintenance Synthese Controller
 *
 * Vue de synthese de l'etat de navigabilite : par aeronef (etat de
 * chaque entite maintenable) et vue flotte filtrable par section (pire
 * etat par aeronef). Export PDF de synthese par aeronef.
 *
 * @package controllers
 * @see doc/prds/maintenance_aeronefs_prd.md (EF7)
 * @see doc/plans/maintenance_aeronefs_plan.md (Etape 5.6)
 */
class Maintenance_synthese extends MY_Controller {

    // Codes couleur partages entre la vue flotte, la vue aeronef et le PDF (PRD EF7 : coherence)
    const ETAT_BADGES = array(
        'a_jour'          => 'bg-success',
        'echeance_proche' => 'bg-warning text-dark',
        'depasse'         => 'bg-danger',
    );

    public function __construct() {
        parent::__construct();

        $this->load->model('maintenance_equipement_model');
        $this->load->model('maintenance_dossier_model');
        $this->load->model('sections_model');
        $this->load->library('Maintenance_potentiel');
        $this->lang->load('maintenance');
        $this->lang->load('gvv');

        if (!$this->dx_auth->is_logged_in()) {
            redirect('auth/login');
        }

        if (!$this->user_has_role('mecano')) {
            show_error($this->lang->line('maintenance_acces_refuse'), 403);
        }

        $this->lang->load('tableaux_de_bord');
        $this->load->vars([
            'nav_back_url'   => $this->session->userdata('nav_from_url')   ?: 'welcome/section/maintenance',
            'nav_back_label' => $this->session->userdata('nav_from_label') ?: $this->lang->line('db_section_maintenance'),
        ]);
    }

    /**
     * Vue flotte : pire etat par aeronef, filtrable par section (PRD EF7.3)
     * @param int|string $section_id
     */
    public function index($section_id = '') {
        $aeronefs = $this->maintenance_equipement_model->get_aeronefs_by_section($section_id);
        foreach ($aeronefs as &$aeronef) {
            $aeronef['etat'] = $this->maintenance_potentiel->etat_pire_cas($aeronef['macimmat']);
        }
        unset($aeronef);

        $data = array(
            'controller'       => 'maintenance_synthese',
            'aeronefs'         => $aeronefs,
            'section_id'       => $section_id,
            'section_selector' => $this->sections_model->section_selector_with_null(),
            'etat_badges'      => self::ETAT_BADGES,
            'etat_labels'      => $this->etat_labels(),
        );

        $this->load->view('maintenance_synthese/index', $data);
    }

    /**
     * Detail des entites maintenables d'un aeronef, avec leur etat (PRD EF7.1)
     * @param string $machine_immat
     */
    public function aeronef($machine_immat = '') {
        if (empty($machine_immat)) {
            redirect('maintenance_synthese');
        }

        $data = $this->build_aeronef_synthese($machine_immat);
        if ($data === null) {
            show_404();
        }

        $data['controller'] = 'maintenance_synthese';
        $this->load->view('maintenance_synthese/aeronef', $data);
    }

    /**
     * Export PDF de la synthese d'un aeronef (reutilise TCPDF, deja
     * utilise ailleurs dans GVV -- ex. programmes.php::export_pdf())
     * @param string $machine_immat
     */
    public function export_pdf($machine_immat = '') {
        if (empty($machine_immat)) {
            redirect('maintenance_synthese');
        }

        $data = $this->build_aeronef_synthese($machine_immat);
        if ($data === null) {
            show_404();
        }

        $nom_club = $this->config->item('nom_club');
        $etat_labels = $this->etat_labels();

        $html = '<p style="text-align:right;color:#555;font-size:9pt;">' . htmlspecialchars($nom_club) . '</p>';
        $html .= '<h1>' . $this->lang->line('maintenance_synthese_titre_aeronef') . ' : ' . htmlspecialchars($data['aeronef']['macmodele']) . ' - ' . htmlspecialchars($machine_immat) . '</h1>';
        $html .= '<p><strong>' . $this->lang->line('maintenance_synthese_etat_global') . ' :</strong> ' . htmlspecialchars($etat_labels[$data['aeronef']['etat']]) . '</p>';
        $html .= '<p style="font-size:9pt;color:#777;">' . $this->lang->line('maintenance_synthese_genere_le') . ' ' . date('d/m/Y H:i') . '</p>';
        $html .= '<hr>';

        $html .= '<table border="1" cellpadding="4" cellspacing="0" style="width:100%">';
        $html .= '<tr style="background-color:#eee;"><th>' . $this->lang->line('maintenance_synthese_entite') . '</th><th>' . $this->lang->line('maintenance_synthese_programme') . '</th><th>' . $this->lang->line('maintenance_equipement_actif') . '</th></tr>';
        foreach ($data['entites'] as $entite) {
            foreach ($entite['dossiers'] as $dossier) {
                $html .= '<tr><td>' . htmlspecialchars($entite['label']) . '</td>';
                $html .= '<td>' . htmlspecialchars($dossier['programme_code']) . ' - ' . htmlspecialchars($dossier['programme_titre']) . '</td>';
                $html .= '<td>' . htmlspecialchars($etat_labels[$dossier['etat']]) . '</td></tr>';
            }
            if (empty($entite['dossiers'])) {
                $html .= '<tr><td>' . htmlspecialchars($entite['label']) . '</td><td colspan="2">' . $this->lang->line('maintenance_synthese_aucun_dossier') . '</td></tr>';
            }
        }
        $html .= '</table>';

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator($nom_club);
        $pdf->SetAuthor($nom_club);
        $pdf->SetTitle($this->lang->line('maintenance_synthese_titre_aeronef') . ' ' . $machine_immat);
        $pdf->SetSubject($this->lang->line('maintenance_synthese_titre'));

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);

        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);
        $pdf->writeHTML($html, true, false, true, false, '');

        $filename = 'synthese_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $machine_immat) . '_' . date('Y-m-d') . '.pdf';
        $pdf->Output($filename, 'I');
        exit;
    }

    /**
     * Construit les donnees de synthese d'un aeronef : son propre etat,
     * puis chacun de ses equipements actifs, avec le detail des dossiers
     * ouverts et l'etat de chacun.
     *
     * @param string $machine_immat
     * @return array|null null si l'aeronef n'existe pas
     */
    private function build_aeronef_synthese($machine_immat) {
        $this->load->model('avions_model');
        $aeronef = $this->avions_model->get_by_id('macimmat', $machine_immat);
        if (!$aeronef) {
            return null;
        }
        $aeronef['etat'] = $this->maintenance_potentiel->etat_pire_cas($machine_immat);

        $entites = array();
        $entites[] = $this->entite_synthese('aeronef', $machine_immat, $aeronef['macmodele'] . ' - ' . $machine_immat);

        $equipements = $this->maintenance_equipement_model->get_by_aeronef($machine_immat, true);
        foreach ($equipements as $equipement) {
            $entites[] = $this->entite_synthese('equipement', $equipement['id'], $equipement['nom']);
        }

        return array(
            'aeronef'     => $aeronef,
            'entites'     => $entites,
            'etat_badges' => self::ETAT_BADGES,
            'etat_labels' => $this->etat_labels(),
        );
    }

    /**
     * Detail d'une entite pour la vue aeronef : son libelle, ses dossiers
     * ouverts avec l'etat de chacun, et son pire etat.
     */
    private function entite_synthese($entite_type, $entite_id, $label) {
        $dossiers = $this->maintenance_dossier_model->get_ouverts($entite_type, $entite_id);
        foreach ($dossiers as &$dossier) {
            $dossier['etat'] = $this->maintenance_potentiel->calculer_etat($dossier);
        }
        unset($dossier);

        return array(
            'entite_type' => $entite_type,
            'entite_id'   => $entite_id,
            'label'       => $label,
            'dossiers'    => $dossiers,
            'etat'        => $this->maintenance_potentiel->etat_entite($entite_type, $entite_id),
        );
    }

    /**
     * Libelles traduits des etats (le modele/librairie ne porte que des
     * cles techniques, jamais de texte affichable).
     */
    private function etat_labels() {
        return array(
            'a_jour'          => $this->lang->line('maintenance_etat_a_jour'),
            'echeance_proche' => $this->lang->line('maintenance_etat_echeance_proche'),
            'depasse'         => $this->lang->line('maintenance_etat_depasse'),
        );
    }
}

/* End of file maintenance_synthese.php */
/* Location: ./application/controllers/maintenance_synthese.php */
