<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Contrôleur — Configuration des bons de vol de découverte (looks)
 *
 * Actions :
 *   index()              → liste des looks configurés
 *   create()              → crée un look (nom + layout par défaut), redirige vers edit()
 *   edit($id)             → écran d'édition (fonds, mise en page)
 *   upload_fond($id)      → upload d'un fond recto/verso (POST)
 *   layout_save($id)      → enregistre la mise en page (POST)
 *   layout_export($id)    → télécharge le layout au format JSON
 *   layout_import($id)    → importe un layout depuis un fichier JSON (POST)
 *   set_default($id)      → marque un look comme look par défaut
 *   delete($id)           → supprime un look
 *   sections()            → association section → look
 *
 * Réservé aux mêmes rôles que les actions d'administration de
 * `vols_decouverte` (club-admin, gestion_vd, tresorier, bureau).
 *
 * @see doc/plans/configuration_bons_vols_decouverte_plan.md
 */
class Vols_decouverte_looks extends MY_Controller {

    protected $controller = 'vols_decouverte_looks';

    public function __construct() {
        parent::__construct();

        if (!$this->dx_auth->is_logged_in()) {
            redirect('auth/login');
        }
        if (!$this->has_full_vd_rights()) {
            show_error($this->lang->line('gvv_error_not_authorized'), 403);
        }

        $this->load->model('vols_decouverte_looks_model');
        $this->load->model('vols_decouverte_look_sections_model');
        $this->load->model('sections_model');
        $this->lang->load('gvv');

        $this->load->vars([
            'nav_back_url'   => $this->session->userdata('nav_from_url')   ?: 'welcome/section/admin_club',
            'nav_back_label' => $this->session->userdata('nav_from_label') ?: $this->lang->line('db_section_admin_club'),
        ]);
    }

    /**
     * Mêmes droits que les actions d'administration de `vols_decouverte`
     * (Vols_decouverte::has_full_vd_rights()).
     */
    private function has_full_vd_rights() {
        return $this->user_has_role('club-admin')
            || $this->user_has_role('gestion_vd')
            || $this->user_has_role('tresorier')
            || $this->user_has_role('bureau');
    }

    /** Liste des looks configurés. */
    public function index() {
        $data = array(
            'controller' => $this->controller,
            'looks'      => $this->vols_decouverte_looks_model->select_all(array(), 'nom asc'),
            'message'    => $this->session->flashdata('vd_looks_message'),
            'error'      => $this->session->flashdata('vd_looks_error'),
        );
        load_last_view('vols_decouverte_looks/bs_index', $data);
    }

    /** Crée un nouveau look (nom + layout par défaut), puis ouvre son édition. */
    public function create() {
        $nom = trim((string) $this->input->post('nom'));
        if ($nom === '') {
            $this->session->set_flashdata('vd_looks_error', $this->lang->line('gvv_vd_looks_name_required'));
            redirect(controller_url('vols_decouverte_looks'));
            return;
        }

        $id = $this->vols_decouverte_looks_model->save_look(null, $nom, $this->vols_decouverte_looks_model->default_layout());
        redirect(controller_url('vols_decouverte_looks/edit/' . $id));
    }

    /** Écran d'édition d'un look : fonds recto/verso et mise en page. */
    public function edit($id) {
        $look = $this->vols_decouverte_looks_model->get_by_id('id', $id);
        if (empty($look)) {
            show_error($this->lang->line('gvv_error_not_found'), 404);
            return;
        }

        $data = array(
            'controller' => $this->controller,
            'look'       => $look,
            'layout'     => $this->vols_decouverte_looks_model->get_layout($look),
            'message'    => $this->session->flashdata('vd_looks_message'),
            'error'      => $this->session->flashdata('vd_looks_error'),
        );
        load_last_view('vols_decouverte_looks/bs_edit', $data);
    }

    /** Upload d'un fond recto ou verso pour un look. */
    public function upload_fond($id) {
        $look = $this->vols_decouverte_looks_model->get_by_id('id', $id);
        if (empty($look)) {
            show_error($this->lang->line('gvv_error_not_found'), 404);
            return;
        }

        $face = $this->input->post('face');
        if (!in_array($face, array('recto', 'verso'))) {
            $this->session->set_flashdata('vd_looks_error', $this->lang->line('gvv_vd_looks_invalid_face'));
            redirect(controller_url('vols_decouverte_looks/edit/' . $id));
            return;
        }

        $result = $this->_upload_fond($look, $face);
        if ($result['success']) {
            $this->session->set_flashdata('vd_looks_message', $this->lang->line('gvv_vd_looks_upload_ok'));
        } else {
            $this->session->set_flashdata('vd_looks_error', $result['error']);
        }
        redirect(controller_url('vols_decouverte_looks/edit/' . $id));
    }

    /** Enregistre la mise en page (nom + champs) soumise depuis bs_edit. */
    public function layout_save($id) {
        $look = $this->vols_decouverte_looks_model->get_by_id('id', $id);
        if (empty($look)) {
            show_error($this->lang->line('gvv_error_not_found'), 404);
            return;
        }

        $nom = trim((string) $this->input->post('nom'));
        if ($nom === '') {
            $nom = $look['nom'];
        }

        $layout = $this->_parse_layout_from_post();
        $this->vols_decouverte_looks_model->save_look($id, $nom, $layout, $look['fond_recto_path'], $look['fond_verso_path']);

        $this->session->set_flashdata('vd_looks_message', $this->lang->line('gvv_vd_looks_layout_save_ok'));
        redirect(controller_url('vols_decouverte_looks/edit/' . $id));
    }

    /** Télécharge la mise en page d'un look au format JSON. */
    public function layout_export($id) {
        $look = $this->vols_decouverte_looks_model->get_by_id('id', $id);
        if (empty($look)) {
            show_error($this->lang->line('gvv_error_not_found'), 404);
            return;
        }

        $layout = $this->vols_decouverte_looks_model->get_layout($look);
        $json = json_encode($layout, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $this->output
            ->set_content_type('application/json')
            ->set_header('Content-Disposition: attachment; filename="vd_look_layout_' . $id . '.json"')
            ->set_output($json);
    }

    /** Importe une mise en page depuis un fichier JSON uploadé (nom et fonds inchangés). */
    public function layout_import($id) {
        $look = $this->vols_decouverte_looks_model->get_by_id('id', $id);
        if (empty($look)) {
            show_error($this->lang->line('gvv_error_not_found'), 404);
            return;
        }

        if (isset($_FILES['layout_json']) && $_FILES['layout_json']['error'] === UPLOAD_ERR_OK) {
            $raw = file_get_contents($_FILES['layout_json']['tmp_name']);
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && isset($decoded['recto'], $decoded['verso'])) {
                $this->vols_decouverte_looks_model->save_look($id, $look['nom'], $decoded, $look['fond_recto_path'], $look['fond_verso_path']);
                $this->session->set_flashdata('vd_looks_message', $this->lang->line('gvv_vd_looks_layout_import_ok'));
            } else {
                $this->session->set_flashdata('vd_looks_error', $this->lang->line('gvv_vd_looks_layout_import_err'));
            }
        }

        redirect(controller_url('vols_decouverte_looks/edit/' . $id));
    }

    /** Marque un look comme look par défaut. */
    public function set_default($id) {
        $look = $this->vols_decouverte_looks_model->get_by_id('id', $id);
        if (empty($look)) {
            show_error($this->lang->line('gvv_error_not_found'), 404);
            return;
        }

        $this->vols_decouverte_looks_model->set_default($id);
        $this->session->set_flashdata('vd_looks_message', $this->lang->line('gvv_vd_looks_set_default_ok'));
        redirect(controller_url('vols_decouverte_looks'));
    }

    /** Supprime un look (les sections associées reviennent au look par défaut). */
    public function delete($id) {
        $look = $this->vols_decouverte_looks_model->get_by_id('id', $id);
        if (empty($look)) {
            show_error($this->lang->line('gvv_error_not_found'), 404);
            return;
        }

        $this->vols_decouverte_looks_model->delete(array('id' => $id));
        $this->session->set_flashdata('vd_looks_message', $this->lang->line('gvv_vd_looks_delete_ok'));
        redirect(controller_url('vols_decouverte_looks'));
    }

    /**
     * Association section → look. GET affiche le formulaire, POST enregistre
     * les associations (une entrée vide revient au look par défaut).
     */
    public function sections() {
        $sections = $this->sections_model->select_all(array(), 'nom asc');

        if ($this->input->post('save_sections')) {
            foreach ($sections as $section) {
                $look_id = $this->input->post('look_' . $section['id']);
                if (empty($look_id)) {
                    $this->vols_decouverte_look_sections_model->clear($section['id']);
                } else {
                    $this->vols_decouverte_look_sections_model->assign($section['id'], $look_id);
                }
            }
            $this->session->set_flashdata('vd_looks_message', $this->lang->line('gvv_vd_looks_sections_save_ok'));
            redirect(controller_url('vols_decouverte_looks/sections'));
            return;
        }

        $current = array();
        foreach ($sections as $section) {
            $current[$section['id']] = $this->vols_decouverte_look_sections_model->get_look_id_for_section($section['id']);
        }

        $data = array(
            'controller' => $this->controller,
            'sections'   => $sections,
            'looks'      => $this->vols_decouverte_looks_model->select_all(array(), 'nom asc'),
            'current'    => $current,
            'message'    => $this->session->flashdata('vd_looks_message'),
        );
        load_last_view('vols_decouverte_looks/bs_sections', $data);
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
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        return array(
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        );
    }

    /**
     * Reconstruit un layout complet depuis les données POST du formulaire bs_edit.
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
                    'x'       => (float) ($xs[$i] ?? 0),
                    'y'       => (float) ($ys[$i] ?? 0),
                    'font'    => $fonts[$i] ?? 'helvetica',
                    'bold'    => !empty($bolds[$i]),
                    'size'    => (int) ($sizes[$i] ?? 10),
                    'color'   => $this->_hex_to_rgb($colors[$i] ?? '#000000'),
                    'align'   => $aligns[$i] ?? 'L',
                    'width'   => (float) ($widths[$i] ?? 60),
                );
            }

            // Champs fixes
            $static_fields = array();
            $texts    = $this->input->post($face . '_st_text')  ?: array();
            $st_xs    = $this->input->post($face . '_st_x')     ?: array();
            $st_ys    = $this->input->post($face . '_st_y')     ?: array();
            $st_fonts = $this->input->post($face . '_st_font')  ?: array();
            $st_bolds = $this->input->post($face . '_st_bold')  ?: array();
            $st_sizes = $this->input->post($face . '_st_size')  ?: array();
            $st_colrs = $this->input->post($face . '_st_color') ?: array();
            $st_algns = $this->input->post($face . '_st_align') ?: array();
            $st_wdths = $this->input->post($face . '_st_width') ?: array();

            foreach ($texts as $i => $text) {
                if (trim($text) === '') continue;
                $static_fields[] = array(
                    'text'  => $text,
                    'x'     => (float) ($st_xs[$i] ?? 0),
                    'y'     => (float) ($st_ys[$i] ?? 0),
                    'font'  => $st_fonts[$i] ?? 'helvetica',
                    'bold'  => !empty($st_bolds[$i]),
                    'size'  => (int) ($st_sizes[$i] ?? 10),
                    'color' => $this->_hex_to_rgb($st_colrs[$i] ?? '#000000'),
                    'align' => $st_algns[$i] ?? 'L',
                    'width' => (float) ($st_wdths[$i] ?? 60),
                );
            }

            // QR code : toujours présent côté recto (activable/désactivable),
            // seulement si activé côté verso — même principe que la photo des
            // cartes de membre.
            $qr_enabled = $this->input->post($face . '_qr_enabled');
            if ($face === 'recto') {
                $qr = array(
                    'enabled' => (bool) $qr_enabled,
                    'x'       => (float) ($this->input->post($face . '_qr_x') ?: 175),
                    'y'       => (float) ($this->input->post($face . '_qr_y') ?: 5),
                    'size'    => (float) ($this->input->post($face . '_qr_size') ?: 30),
                );
            } else {
                $qr = null;
                if ($qr_enabled) {
                    $qr = array(
                        'enabled' => true,
                        'x'       => (float) ($this->input->post($face . '_qr_x') ?: 175),
                        'y'       => (float) ($this->input->post($face . '_qr_y') ?: 5),
                        'size'    => (float) ($this->input->post($face . '_qr_size') ?: 30),
                    );
                }
            }

            $layout[$face] = array(
                'variable_fields' => $variable_fields,
                'static_fields'   => $static_fields,
                'qr_field'        => $qr,
            );
        }

        return $layout;
    }

    /**
     * Traite l'upload d'un fond de bon et l'enregistre sur le look.
     *
     * @param array  $look
     * @param string $face  'recto' ou 'verso'
     * @return array  ['success' => bool, 'error' => string]
     */
    private function _upload_fond($look, $face) {
        $upload_dir = FCPATH . 'uploads/configuration/vd/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0775, true);
        }

        $file_name = 'look_' . $look['id'] . '_' . $face;
        $config = array(
            'upload_path'   => $upload_dir,
            'allowed_types' => 'jpg|jpeg|png',
            'max_size'      => 4096,
            'file_name'     => $file_name,
            'overwrite'     => true,
        );

        $this->load->library('upload', $config);
        if (!$this->upload->do_upload('fond_' . $face)) {
            return array('success' => false, 'error' => strip_tags($this->upload->display_errors()));
        }

        $upload_data = $this->upload->data();
        $relative = 'uploads/configuration/vd/' . $upload_data['file_name'];

        $fond_recto = $face === 'recto' ? $relative : $look['fond_recto_path'];
        $fond_verso = $face === 'verso' ? $relative : $look['fond_verso_path'];
        $layout = $this->vols_decouverte_looks_model->get_layout($look);
        $this->vols_decouverte_looks_model->save_look($look['id'], $look['nom'], $layout, $fond_recto, $fond_verso);

        return array('success' => true);
    }
}
