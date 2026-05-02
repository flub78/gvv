<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Contrôleur — Impression des cartes de membre
 *
 * Actions :
 *   index()          → redirige vers lot()
 *   lot()            → écran de sélection du lot (GET) / redirection PDF (POST)
 *   lot_pdf()        → génère et envoie le PDF recto/verso en lot
 *   config()         → gestion des fonds recto/verso par année (upload)
 */
class Cartes_membre extends CI_Controller {

    protected $controller = 'cartes_membre';

    public function __construct() {
        parent::__construct();

        if (!$this->dx_auth->is_logged_in()) {
            redirect('auth/login');
        }
        $this->dx_auth->check_uri_permissions();

        $this->load->model('cartes_membre_model');
        $this->lang->load('gvv');
    }

    /** Redirige vers l'écran de sélection du lot. */
    public function index() {
        redirect(controller_url('cartes_membre/lot'));
    }

    /**
     * Écran de sélection du lot.
     * GET  : affiche le formulaire (année + liste membres).
     * POST : valide et redirige vers lot_pdf (members en session).
     */
    public function lot() {
        if (!$this->dx_auth->is_admin()) {
            show_error($this->lang->line('gvv_error_not_authorized'), 403);
        }

        $year = (int)($this->input->get('year') ?: $this->input->post('year') ?: date('Y'));

        if ($this->input->post('generate')) {
            $selected = $this->input->post('membres') ?: array();
            if (empty($selected)) {
                $all = $this->cartes_membre_model->get_membres_actifs_annee($year);
                $selected = array_column($all, 'mlogin');
            }
            $this->session->set_userdata('cartes_lot_membres', $selected);
            $this->session->set_userdata('cartes_lot_year', $year);
            redirect(controller_url('cartes_membre/lot_pdf'));
            return;
        }

        $membres = $this->cartes_membre_model->get_membres_actifs_annee($year);

        $year_selector = array();
        for ($y = (int)date('Y') + 1; $y >= 2010; $y--) {
            $year_selector[$y] = $y;
        }

        $data = array(
            'controller' => $this->controller,
            'year'          => $year,
            'year_selector' => $year_selector,
            'membres'       => $membres,
        );

        load_last_view('cartes_membre/bs_lot', $data);
    }

    /**
     * Génère le PDF recto/verso en lot et l'envoie au navigateur.
     * Les logins sélectionnés sont lus depuis la session (positionnés par lot()).
     */
    public function lot_pdf() {
        if (!$this->dx_auth->is_admin()) {
            show_error($this->lang->line('gvv_error_not_authorized'), 403);
        }

        $selected = $this->session->userdata('cartes_lot_membres') ?: array();
        $year     = (int)($this->session->userdata('cartes_lot_year') ?: date('Y'));

        if (empty($selected)) {
            redirect(controller_url('cartes_membre/lot'));
            return;
        }

        $fond_recto = $this->cartes_membre_model->get_fond_path($year, 'recto');
        $fond_verso = $this->cartes_membre_model->get_fond_path($year, 'verso');
        $layout     = $this->cartes_membre_model->get_layout($year);
        $nom_club   = $this->config->item('nom_club') ?: 'GVV';

        $membres = array();
        $numero_carte = 1;
        foreach ($selected as $login) {
            $m = $this->cartes_membre_model->get_membre($login);
            if (!$m) continue;
            $m['annee']        = $year;
            $m['photo_path']   = $this->cartes_membre_model->get_photo_path($m['photo'] ?? null);
            $m['nom_club']     = $nom_club;
            $m['numero_carte'] = $numero_carte++;
            $membres[] = $m;
        }

        if (empty($membres)) {
            redirect(controller_url('cartes_membre/lot'));
            return;
        }

        require_once(APPPATH . 'libraries/Cartes_membre_pdf.php');

        $pdf = new Cartes_membre_pdf();
        $pdf->generate_lot($membres, $layout, $fond_recto, $fond_verso);
        $pdf->Output('cartes_membre_' . $year . '.pdf', 'I');
    }

    /**
     * Configuration des fonds recto/verso par année.
     * GET  : affiche le formulaire.
     * POST : traite l'upload et enregistre en configuration.
     */
    public function config() {
        if (!$this->dx_auth->is_admin()) {
            show_error($this->lang->line('gvv_error_not_authorized'), 403);
        }

        $year = (int)($this->input->get('year') ?: $this->input->post('year') ?: date('Y'));
        $message = '';
        $error   = '';

        if ($this->input->post('upload')) {
            $face = $this->input->post('face');
            if (!in_array($face, array('recto', 'verso'))) {
                $error = $this->lang->line('gvv_cartes_membre_invalid_face');
            } else {
                $result = $this->_upload_fond($year, $face);
                if ($result['success']) {
                    $message = $this->lang->line('gvv_cartes_membre_upload_ok');
                } else {
                    $error = $result['error'];
                }
            }
        }

        $year_selector = array();
        for ($y = (int)date('Y') + 1; $y >= 2010; $y--) {
            $year_selector[$y] = $y;
        }

        $data = array(
            'controller'    => $this->controller,
            'year'          => $year,
            'year_selector' => $year_selector,
            'fond_recto'    => $this->cartes_membre_model->get_fond_path($year, 'recto'),
            'fond_verso'    => $this->cartes_membre_model->get_fond_path($year, 'verso'),
            'layout'        => $this->cartes_membre_model->get_layout($year),
            'message'       => $message,
            'error'         => $error,
        );

        load_last_view('cartes_membre/bs_config', $data);
    }

    /**
     * Enregistre la configuration de mise en page (POST depuis bs_config).
     */
    public function layout_save() {
        if (!$this->dx_auth->is_admin()) {
            show_error($this->lang->line('gvv_error_not_authorized'), 403);
        }

        $year   = (int)($this->input->post('year') ?: date('Y'));
        $layout = $this->_parse_layout_from_post();
        $this->cartes_membre_model->save_layout($year, $layout);

        $this->session->set_flashdata('layout_message', $this->lang->line('gvv_cartes_membre_layout_save_ok'));
        redirect(controller_url('cartes_membre/config') . '?year=' . $year);
    }

    /**
     * Télécharge le fichier JSON de configuration de mise en page.
     */
    public function layout_export($annee = null) {
        if (!$this->dx_auth->is_admin()) {
            show_error($this->lang->line('gvv_error_not_authorized'), 403);
        }

        $year   = (int)($annee ?: $this->input->get('year') ?: date('Y'));
        $layout = $this->cartes_membre_model->get_layout($year);
        $json   = json_encode($layout, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $this->output
            ->set_content_type('application/json')
            ->set_header('Content-Disposition: attachment; filename="carte_layout_' . $year . '.json"')
            ->set_output($json);
    }

    /**
     * Importe une configuration de mise en page depuis un fichier JSON uploadé.
     */
    public function layout_import() {
        if (!$this->dx_auth->is_admin()) {
            show_error($this->lang->line('gvv_error_not_authorized'), 403);
        }

        $year  = (int)($this->input->post('year') ?: date('Y'));
        $error = '';

        if (isset($_FILES['layout_json']) && $_FILES['layout_json']['error'] === UPLOAD_ERR_OK) {
            $raw     = file_get_contents($_FILES['layout_json']['tmp_name']);
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && isset($decoded['recto'], $decoded['verso'])) {
                $this->cartes_membre_model->save_layout($year, $decoded);
                $this->session->set_flashdata('layout_message', $this->lang->line('gvv_cartes_membre_layout_import_ok'));
            } else {
                $error = $this->lang->line('gvv_cartes_membre_layout_import_err');
                $this->session->set_flashdata('layout_error', $error);
            }
        }

        redirect(controller_url('cartes_membre/config') . '?year=' . $year);
    }

    /**
     * Réinitialise la mise en page au défaut (supprime la clé configuration).
     */
    public function layout_reset() {
        if (!$this->dx_auth->is_admin()) {
            show_error($this->lang->line('gvv_error_not_authorized'), 403);
        }

        $year = (int)($this->input->post('year') ?: date('Y'));
        $cle  = 'carte_layout_' . $year;
        $this->db->where('cle', $cle)->delete('configuration');

        $this->session->set_flashdata('layout_message', $this->lang->line('gvv_cartes_membre_layout_reset_ok'));
        redirect(controller_url('cartes_membre/config') . '?year=' . $year);
    }

    // -------------------------------------------------------------------------
    // Méthodes privées
    // -------------------------------------------------------------------------

    /**
     * Convertit une couleur CSS hexadécimale (#rrggbb) en tableau [r, g, b].
     *
     * @param string $hex
     * @return int[]
     */
    private function _hex_to_rgb($hex) {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        return array(
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        );
    }

    /**
     * Reconstruit un layout complet depuis les données POST du formulaire bs_layout.
     *
     * @return array
     */
    private function _parse_layout_from_post() {
        $layout = array('version' => 1, 'recto' => array(), 'verso' => array());

        foreach (array('recto', 'verso') as $face) {
            // Champs variables
            $variable_fields = array();
            $ids    = $this->input->post($face . '_var_id')      ?: array();
            $enab   = $this->input->post($face . '_var_enabled') ?: array();
            $xs     = $this->input->post($face . '_var_x')       ?: array();
            $ys     = $this->input->post($face . '_var_y')       ?: array();
            $fonts  = $this->input->post($face . '_var_font')    ?: array();
            $bolds  = $this->input->post($face . '_var_bold')    ?: array();
            $sizes  = $this->input->post($face . '_var_size')    ?: array();
            $colors = $this->input->post($face . '_var_color')   ?: array();
            $aligns = $this->input->post($face . '_var_align')   ?: array();
            $widths = $this->input->post($face . '_var_width')   ?: array();

            foreach ($ids as $i => $id) {
                $variable_fields[] = array(
                    'id'      => $id,
                    'enabled' => !empty($enab[$i]),
                    'x'       => (float)($xs[$i] ?? 0),
                    'y'       => (float)($ys[$i] ?? 0),
                    'font'    => $fonts[$i] ?? 'helvetica',
                    'bold'    => !empty($bolds[$i]),
                    'size'    => (int)($sizes[$i] ?? 7),
                    'color'   => $this->_hex_to_rgb($colors[$i] ?? '#000000'),
                    'align'   => $aligns[$i] ?? 'L',
                    'width'   => (float)($widths[$i] ?? 60),
                );
            }

            // Champs statiques
            $static_fields = array();
            $texts   = $this->input->post($face . '_st_text')  ?: array();
            $st_xs   = $this->input->post($face . '_st_x')     ?: array();
            $st_ys   = $this->input->post($face . '_st_y')     ?: array();
            $st_fonts= $this->input->post($face . '_st_font')  ?: array();
            $st_bolds= $this->input->post($face . '_st_bold')  ?: array();
            $st_sizes= $this->input->post($face . '_st_size')  ?: array();
            $st_colrs= $this->input->post($face . '_st_color') ?: array();
            $st_algns= $this->input->post($face . '_st_align') ?: array();
            $st_wdths= $this->input->post($face . '_st_width') ?: array();

            foreach ($texts as $i => $text) {
                if (trim($text) === '') continue;
                $static_fields[] = array(
                    'text'  => $text,
                    'x'     => (float)($st_xs[$i] ?? 0),
                    'y'     => (float)($st_ys[$i] ?? 0),
                    'font'  => $st_fonts[$i] ?? 'helvetica',
                    'bold'  => !empty($st_bolds[$i]),
                    'size'  => (int)($st_sizes[$i] ?? 7),
                    'color' => $this->_hex_to_rgb($st_colrs[$i] ?? '#000000'),
                    'align' => $st_algns[$i] ?? 'L',
                    'width' => (float)($st_wdths[$i] ?? 60),
                );
            }

            // Photo
            $photo = null;
            if ($face === 'recto') {
                $photo_enabled = $this->input->post('recto_photo_enabled');
                $photo = array(
                    'enabled' => (bool)$photo_enabled,
                    'x'       => (float)($this->input->post('recto_photo_x') ?: 62),
                    'y'       => (float)($this->input->post('recto_photo_y') ?: 14),
                    'w'       => (float)($this->input->post('recto_photo_w') ?: 20),
                    'h'       => (float)($this->input->post('recto_photo_h') ?: 25),
                );
            } else {
                $photo_enabled = $this->input->post('verso_photo_enabled');
                if ($photo_enabled) {
                    $photo = array(
                        'enabled' => true,
                        'x'       => (float)($this->input->post('verso_photo_x') ?: 62),
                        'y'       => (float)($this->input->post('verso_photo_y') ?: 14),
                        'w'       => (float)($this->input->post('verso_photo_w') ?: 20),
                        'h'       => (float)($this->input->post('verso_photo_h') ?: 25),
                    );
                }
            }

            $layout[$face] = array(
                'variable_fields' => $variable_fields,
                'static_fields'   => $static_fields,
                'photo'           => $photo,
            );
        }

        return $layout;
    }

    /**
     * Traite l'upload d'un fond de carte et l'enregistre dans la configuration.
     *
     * @param int    $year
     * @param string $face  'recto' ou 'verso'
     * @return array  ['success' => bool, 'error' => string]
     */
    private function _upload_fond($year, $face) {
        $upload_dir = FCPATH . 'uploads/configuration/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0775, true);
        }

        $cle      = 'carte_' . $face . '_' . $year;
        $config   = array(
            'upload_path'   => $upload_dir,
            'allowed_types' => 'jpg|jpeg|png',
            'max_size'      => 4096,
            'file_name'     => $cle,
            'overwrite'     => true,
        );

        $this->load->library('upload', $config);
        if (!$this->upload->do_upload('fond_' . $face)) {
            return array('success' => false, 'error' => strip_tags($this->upload->display_errors()));
        }

        $upload_data = $this->upload->data();
        $relative    = 'uploads/configuration/' . $upload_data['file_name'];
        $this->cartes_membre_model->save_fond_path($year, $face, $relative);

        return array('success' => true);
    }
}
