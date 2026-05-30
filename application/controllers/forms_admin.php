<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Minimal administration controller for forms.
 *
 * First usable slice for the standalone forms module:
 * - list forms
 * - create a form
 * - publish a form
 */
class Forms_admin extends CI_Controller {

    protected $controller = 'forms_admin';

    public function __construct() {
        parent::__construct();

        $this->load->model('forms_model');
        $this->load->library('form_validation');
        $this->lang->load('gvv');

        if (!$this->dx_auth->is_logged_in()) {
            redirect('auth/login');
        }

        $is_admin = $this->dx_auth->is_admin() || $this->dx_auth->is_role('ca', true, true);
        if (!$is_admin && $this->session->userdata('use_new_auth')) {
            $this->load->library('Gvv_Authorization');
            $is_admin = $this->gvv_authorization->has_role($this->dx_auth->get_user_id(), 'club-admin', NULL);
        }
        if (!$is_admin) {
            show_error('Acces reserve aux administrateurs.', 403);
        }

        $this->lang->load('tableaux_de_bord');
        $this->load->vars([
            'nav_back_url'   => $this->session->userdata('nav_from_url')   ?: 'welcome/section/admin_sys',
            'nav_back_label' => $this->session->userdata('nav_from_label') ?: $this->lang->line('db_section_admin_sys'),
        ]);
    }

    public function index() {
        $section_id = (int) $this->session->userdata('section');
        $filters = array();
        if ($section_id > 0) {
            $filters['club'] = $section_id;
        }

        $data = array(
            'controller' => $this->controller,
            'forms'      => $this->forms_model->list_forms($filters),
            'section_id' => $section_id,
            'success'    => $this->session->flashdata('forms_success') ?: '',
            'error'      => $this->session->flashdata('forms_error') ?: '',
        );

        load_last_view('forms_admin/bs_index', $data);
    }

    public function create() {
        $data = array(
            'controller' => $this->controller,
            'form'       => array(
                'code'        => '',
                'title'       => '',
                'description' => '',
                'public_slug' => '',
                'css_scope'   => '',
            ),
            'error'      => '',
        );

        load_last_view('forms_admin/bs_form', $data);
    }

    public function store() {
        $section_id = (int) $this->session->userdata('section');
        if ($section_id <= 0) {
            $this->session->set_flashdata('forms_error', 'Selectionnez une section active avant de creer un formulaire.');
            redirect('forms_admin');
            return;
        }

        $this->form_validation->set_rules('code', 'Code', 'required|max_length[50]|alpha_dash');
        $this->form_validation->set_rules('title', 'Titre', 'required|max_length[255]');
        $this->form_validation->set_rules('public_slug', 'Lien public', 'max_length[100]');
        $this->form_validation->set_rules('css_scope', 'CSS scope', 'max_length[100]');

        if ($this->form_validation->run() === FALSE) {
            $data = array(
                'controller' => $this->controller,
                'form'       => $this->input->post(),
                'error'      => validation_errors(),
            );
            load_last_view('forms_admin/bs_form', $data);
            return;
        }

        $id = $this->forms_model->create_form(array(
            'club'        => $section_id,
            'code'        => trim($this->input->post('code')),
            'title'       => trim($this->input->post('title')),
            'description' => trim($this->input->post('description')),
            'public_slug' => trim($this->input->post('public_slug')),
            'css_scope'   => trim($this->input->post('css_scope')),
            'created_by'  => $this->dx_auth->get_username(),
        ));

        if (!$id) {
            $data = array(
                'controller' => $this->controller,
                'form'       => $this->input->post(),
                'error'      => 'Impossible de creer le formulaire.',
            );
            load_last_view('forms_admin/bs_form', $data);
            return;
        }

        $this->session->set_flashdata('forms_success', 'Formulaire cree.');
        redirect('forms_admin');
    }

    public function publish($id = 0) {
        $id = (int) $id;
        if ($id <= 0 || !$this->forms_model->publish_form($id, $this->dx_auth->get_username())) {
            $this->session->set_flashdata('forms_error', 'Impossible de publier ce formulaire.');
            redirect('forms_admin');
            return;
        }

        $this->session->set_flashdata('forms_success', 'Formulaire publie.');
        redirect('forms_admin');
    }
}