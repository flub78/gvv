<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Administration des raccourcis de dashboard (cartes dynamiques).
 *
 * Permet aux club-admins d'ajouter des cartes de navigation dans les
 * dashboards welcome.php sans développement (Lot 7 — voir
 * doc/design_notes/remplissage_formulaires_design.md § 14).
 */
class Shortcuts_admin extends MY_Controller {

    protected $controller = 'shortcuts_admin';

    /** Valeurs autorisées par Welcome::section($name) */
    private $allowed_dashboards = array(
        'user', 'flights', 'treasurer', 'formation', 'maintenance', 'admin_club', 'admin_sys', 'dev',
    );

    public function __construct() {
        parent::__construct();

        $this->load->model('dashboard_shortcuts_model');
        $this->lang->load('shortcuts');
        $this->lang->load('gvv');

        if (!$this->dx_auth->is_logged_in()) {
            redirect('auth/login');
        }

        if (!$this->user_has_role('ca') && !$this->user_has_role('club-admin')) {
            show_error('Acces reserve aux administrateurs.', 403);
        }

        $this->lang->load('tableaux_de_bord');
        $this->load->vars([
            'nav_back_url'   => 'welcome/section/admin_club',
            'nav_back_label' => $this->lang->line('db_section_admin_club'),
        ]);
    }

    public function index() {
        $section_id = (int) $this->session->userdata('section');
        $shortcuts = $this->dashboard_shortcuts_model->list_shortcuts(
            $section_id > 0 ? $section_id : null,
            $section_id > 0
        );

        $sections_by_id = $this->_sections_by_id();
        foreach ($shortcuts as &$s) {
            $s['section_name'] = $s['club_id'] ? (isset($sections_by_id[$s['club_id']]) ? $sections_by_id[$s['club_id']] : $s['club_id']) : null;
        }
        unset($s);

        $data = array(
            'controller' => $this->controller,
            'shortcuts'  => $shortcuts,
            'success'    => $this->session->flashdata('shortcuts_success') ?: '',
            'error'      => $this->session->flashdata('shortcuts_error') ?: '',
        );
        $this->render_view('shortcuts_admin/bs_index', $data);
    }

    public function create() {
        $data = $this->_form_data('create', site_url('shortcuts_admin/store'), array(
            'dashboard'       => '',
            'section'         => '',
            'title_key'       => '',
            'title'           => '',
            'description_key' => '',
            'description'     => '',
            'url'             => '',
            'icon'            => '',
            'color'           => '',
            'role_required'   => '',
            'sort_order'      => 0,
            'active'          => 1,
            'club_id'         => null,
        ), '');
        $this->render_view('shortcuts_admin/bs_form', $data);
    }

    public function store() {
        $error = $this->_validate_post();
        if ($error !== '') {
            $this->_form_error('create', $error);
            return;
        }

        $section_id = (int) $this->session->userdata('section');
        $is_global  = (bool) $this->input->post('is_global');
        $club_id    = ($section_id > 0 && !$is_global) ? $section_id : null;

        $by = $this->dx_auth->get_username();
        $this->dashboard_shortcuts_model->create(array_merge($this->_post_fields(), array('club_id' => $club_id)), $by);

        $this->session->set_flashdata('shortcuts_success', $this->lang->line('shortcuts_created'));
        redirect('shortcuts_admin');
    }

    public function edit($id = 0) {
        $shortcut = $this->dashboard_shortcuts_model->get_by_id((int) $id);
        if (!$shortcut) {
            redirect('shortcuts_admin');
            return;
        }

        $data = $this->_form_data('edit', site_url('shortcuts_admin/update/' . (int) $id), $shortcut, '');
        $this->render_view('shortcuts_admin/bs_form', $data);
    }

    public function update($id = 0) {
        $shortcut = $this->dashboard_shortcuts_model->get_by_id((int) $id);
        if (!$shortcut) {
            redirect('shortcuts_admin');
            return;
        }

        $error = $this->_validate_post();
        if ($error !== '') {
            $this->_form_error('edit', $error, $id);
            return;
        }

        $section_id = (int) $this->session->userdata('section');
        $is_global  = (bool) $this->input->post('is_global');
        $club_id    = ($section_id > 0 && !$is_global) ? $section_id : null;

        $by = $this->dx_auth->get_username();
        $this->dashboard_shortcuts_model->update((int) $id, array_merge($this->_post_fields(), array('club_id' => $club_id)), $by);

        $this->session->set_flashdata('shortcuts_success', $this->lang->line('shortcuts_updated'));
        redirect('shortcuts_admin');
    }

    public function delete($id = 0) {
        $this->dashboard_shortcuts_model->delete((int) $id);
        $this->session->set_flashdata('shortcuts_success', $this->lang->line('shortcuts_deleted'));
        redirect('shortcuts_admin');
    }

    public function toggle($id = 0) {
        $this->dashboard_shortcuts_model->toggle_active((int) $id);
        redirect('shortcuts_admin');
    }

    private function _validate_post() {
        $dashboard = trim((string) $this->input->post('dashboard'));
        $title     = trim((string) $this->input->post('title'));
        $url       = trim((string) $this->input->post('url'));

        if (!in_array($dashboard, $this->allowed_dashboards, true)) {
            return $this->lang->line('shortcuts_error_dashboard_required');
        }
        if ($title === '') {
            return $this->lang->line('shortcuts_error_title_required');
        }
        if ($url === '') {
            return $this->lang->line('shortcuts_error_url_required');
        }
        return '';
    }

    private function _post_fields() {
        return array(
            'dashboard'       => $this->input->post('dashboard'),
            'section'         => $this->input->post('section'),
            'title_key'       => $this->input->post('title_key'),
            'title'           => $this->input->post('title'),
            'description_key' => $this->input->post('description_key'),
            'description'     => $this->input->post('description'),
            'url'             => $this->input->post('url'),
            'icon'            => $this->input->post('icon'),
            'color'           => $this->input->post('color'),
            'role_required'   => $this->input->post('role_required'),
            'sort_order'      => $this->input->post('sort_order') ?: 0,
            'active'          => $this->input->post('active') ? 1 : 0,
        );
    }

    private function _form_data($mode, $form_action, array $shortcut, $error) {
        $section_id = (int) $this->session->userdata('section');
        $section_name = '';
        if ($section_id > 0) {
            $s = $this->db->select('nom')->where('id', $section_id)->get('sections')->row_array();
            $section_name = $s ? $s['nom'] : $section_id;
        }

        return array(
            'controller'         => $this->controller,
            'form_mode'          => $mode,
            'form_action'        => $form_action,
            'section_id'         => $section_id,
            'section_name'       => $section_name,
            'shortcut'           => $shortcut,
            'allowed_dashboards' => $this->allowed_dashboards,
            'roles'              => $this->_role_names(),
            'error'              => $error,
        );
    }

    private function _form_error($mode, $error_msg, $id = 0) {
        $shortcut = $id ? $this->dashboard_shortcuts_model->get_by_id($id) : array_merge(
            $this->_post_fields(),
            array('club_id' => null)
        );
        $form_action = $mode === 'edit'
            ? site_url('shortcuts_admin/update/' . $id)
            : site_url('shortcuts_admin/store');

        $data = $this->_form_data($mode, $form_action, $shortcut, $error_msg);
        $this->render_view('shortcuts_admin/bs_form', $data);
    }

    private function _sections_by_id() {
        $sections = $this->db->select('id, nom as name')->get('sections')->result_array();
        $by_id = array();
        foreach ($sections as $s) {
            $by_id[$s['id']] = $s['name'];
        }
        return $by_id;
    }

    private function _role_names() {
        $rows = $this->db->select('nom')->order_by('nom', 'ASC')->get('types_roles')->result_array();
        return array_map(function ($r) { return $r['nom']; }, $rows);
    }

    private function render_view($view, $data = array()) {
        load_bs_view('header', null, false);
        load_bs_view('menu', null, false);
        load_bs_view('banner', null, false);
        return load_last_view($view, $data);
    }
}
