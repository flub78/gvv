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
class Forms_admin extends MY_Controller {

    protected $controller = 'forms_admin';

    /**
     * public_slug values referenced by GVV workflows outside the forms module
     * (e.g. briefing_passager.php redirects here). Used to strengthen the
     * delete/unpublish confirmation for these forms, since removing or
     * unpublishing them silently breaks the workflow that depends on them.
     */
    private $workflow_form_slugs = array('briefing-passager-ulm');

    public function __construct() {
        parent::__construct();

        $this->load->helper('views');
        $this->load->model('forms_model');
        $this->load->model('form_pages_model');
        $this->load->model('form_fields_model');
        $this->load->model('form_submissions_model');
        $this->load->library('form_validation');
        $this->load->library('forms_renderer');
        $this->lang->load('gvv');
        $this->lang->load('forms');

        if (!$this->dx_auth->is_logged_in()) {
            redirect('auth/login');
        }

        if (!$this->user_has_role('ca') && !$this->user_has_role('club-admin') && !$this->_can_access_workflow_pdf()) {
            show_error('Acces reserve aux administrateurs.', 403);
        }

        $this->lang->load('tableaux_de_bord');
        $this->load->vars([
            'nav_back_url'   => 'forms_admin',
            'nav_back_label' => 'Liste des formulaires',
        ]);
    }

    /**
     * Narrow bypass of the ca/club-admin guard: the "briefing_vd" icon on
     * vols_decouverte (visible to instructeur/pilote_vd, not just admins,
     * see MetaData::action()) links straight to submission_pdf for workflow
     * forms. Restricted to this one method/form list, not a general relaxation.
     */
    private function _can_access_workflow_pdf() {
        if ($this->router->fetch_method() !== 'submission_pdf') {
            return false;
        }
        if (!$this->user_has_role('instructeur') && !$this->user_has_role('pilote_vd')) {
            return false;
        }
        $form_id = (int) $this->uri->segment(3);
        if ($form_id <= 0) {
            return false;
        }
        $form = $this->forms_model->get_by_id($form_id);
        return $form && in_array($form['public_slug'], $this->workflow_form_slugs, true);
    }

    public function index() {
        $this->load->vars([
            'nav_back_url'   => 'welcome/section/admin_sys',
            'nav_back_label' => 'Administration système',
        ]);
        $section_id = (int) $this->session->userdata('section');
        $forms      = $this->forms_model->list_forms(array('section_context' => $section_id));
        $form_ids   = array_column($forms, 'id');
        $counts     = $this->form_submissions_model->count_by_form($form_ids);

        $data = array(
            'controller'          => $this->controller,
            'forms'               => $forms,
            'submission_counts'   => $counts,
            'section_id'          => $section_id,
            'workflow_form_slugs' => $this->workflow_form_slugs,
            'success'             => $this->session->flashdata('forms_success') ?: '',
            'error'               => $this->session->flashdata('forms_error') ?: '',
        );

        $this->render_view('forms_admin/bs_index', $data);
    }

    public function create() {
        $section_id = (int) $this->session->userdata('section');
        $data = array(
            'controller' => $this->controller,
            'form_mode'  => 'create',
            'form_action'=> site_url('forms_admin/store'),
            'submit_label' => 'Creer',
            'form'       => array(
                'code'        => '',
                'title'       => '',
                'description' => '',
                'public_slug' => '',
                'css_scope'   => '',
                'global_css'  => '',
                'handler_class' => '',
                'is_global'   => ($section_id <= 0) ? 1 : 0,
            ),
            'section_id' => $section_id,
            'handler_classes' => $this->_available_handler_classes(),
            'error'      => '',
        );

        $this->render_view('forms_admin/bs_form', $data);
    }

    public function edit($id = 0) {
        $id = (int) $id;
        $row = $this->forms_model->get_by_id($id);
        if (!$row) {
            $this->session->set_flashdata('forms_error', 'Formulaire introuvable.');
            redirect('forms_admin');
            return;
        }

        $section_id = (int) $this->session->userdata('section');
        $row['is_global'] = empty($row['club']) ? 1 : 0;

        $data = array(
            'controller'       => $this->controller,
            'form_mode'        => 'edit',
            'form_action'      => site_url('forms_admin/update/' . $id),
            'submit_label'     => 'Enregistrer',
            'form'             => $row,
            'section_id'       => $section_id,
            'handler_classes'  => $this->_available_handler_classes(),
            'is_workflow_form' => in_array($row['public_slug'], $this->workflow_form_slugs, true),
            'is_currently_published' => $row['status'] === 'published',
            'error'            => '',
        );

        $this->render_view('forms_admin/bs_form', $data);
    }

    /**
     * Handler classes declarable via forms.handler_class (Lot 6, étape 6.3/6.4).
     * Scans application/libraries/form_handlers/ for classes implementing
     * GvvFormHandlerInterface, excluding the interface file itself.
     *
     * @return string[] Class names.
     */
    private function _available_handler_classes() {
        $interface_path = APPPATH . 'libraries/form_handlers/GvvFormHandlerInterface.php';
        if (!is_file($interface_path)) {
            return array();
        }
        require_once $interface_path;

        $classes = array();
        foreach (glob(APPPATH . 'libraries/form_handlers/*.php') as $path) {
            $class = basename($path, '.php');
            if ($class === 'GvvFormHandlerInterface') {
                continue;
            }
            require_once $path;
            if (class_exists($class, false) && in_array('GvvFormHandlerInterface', class_implements($class), true)) {
                $classes[] = $class;
            }
        }
        sort($classes);

        return $classes;
    }

    /**
     * Only accept a handler_class value that is actually a known handler
     * (or empty, to clear it) — the select is the only intended input path,
     * this guards against a forged POST setting an arbitrary string.
     */
    private function _validated_handler_class($value) {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        return in_array($value, $this->_available_handler_classes(), true) ? $value : null;
    }

    public function store() {
        $section_id = (int) $this->session->userdata('section');

        $this->form_validation->set_rules('code', 'Code', 'required|max_length[50]|alpha_dash');
        $this->form_validation->set_rules('title', 'Titre', 'required|max_length[255]');
        $this->form_validation->set_rules('public_slug', 'Lien public', 'max_length[100]');
        $this->form_validation->set_rules('css_scope', 'CSS scope', 'max_length[100]');
        $this->form_validation->set_rules('global_css', 'CSS global', 'max_length[65535]');

        if ($this->form_validation->run() === FALSE) {
            $data = array(
                'controller' => $this->controller,
                'form'       => $this->input->post(),
                'section_id' => $section_id,
                'handler_classes' => $this->_available_handler_classes(),
                'error'      => validation_errors(),
            );
            $this->render_view('forms_admin/bs_form', $data);
            return;
        }

        $is_global = (int) $this->input->post('is_global');
        $club = ($section_id > 0 && !$is_global) ? $section_id : null;

        $id = $this->forms_model->create_form(array(
            'club'            => $club,
            'code'            => trim($this->input->post('code')),
            'title'           => trim($this->input->post('title')),
            'description'     => trim($this->input->post('description')),
            'public_slug'     => trim($this->input->post('public_slug')),
            'css_scope'       => trim($this->input->post('css_scope')),
            'global_css'      => (string) $this->input->post('global_css'),
            'required_params' => $this->input->post('required_params') ?: 'none',
            'allow_upload_response' => (int) $this->input->post('allow_upload_response'),
            'handler_class'   => $this->_validated_handler_class($this->input->post('handler_class')),
            'created_by'      => $this->dx_auth->get_username(),
        ));

        if (!$id) {
            $data = array(
                'controller' => $this->controller,
                'form'       => $this->input->post(),
                'section_id' => $section_id,
                'handler_classes' => $this->_available_handler_classes(),
                'error'      => 'Impossible de creer le formulaire.',
            );
            $this->render_view('forms_admin/bs_form', $data);
            return;
        }

        $this->session->set_flashdata('forms_success', 'Formulaire cree.');
        redirect('forms_admin');
    }

    public function update($id = 0) {
        $id = (int) $id;
        $current = $this->forms_model->get_by_id($id);
        if (!$current) {
            $this->session->set_flashdata('forms_error', 'Formulaire introuvable.');
            redirect('forms_admin');
            return;
        }

        $section_id = (int) $this->session->userdata('section');

        $this->form_validation->set_rules('code', 'Code', 'required|max_length[50]|alpha_dash');
        $this->form_validation->set_rules('title', 'Titre', 'required|max_length[255]');
        $this->form_validation->set_rules('public_slug', 'Lien public', 'max_length[100]');
        $this->form_validation->set_rules('css_scope', 'CSS scope', 'max_length[100]');
        $this->form_validation->set_rules('global_css', 'CSS global', 'max_length[65535]');
        $this->form_validation->set_rules('status', 'Statut', 'in_list[draft,published,archived]');

        if ($this->form_validation->run() === FALSE) {
            $form = array_merge($current, $this->input->post());
            $form['is_global'] = !empty($form['is_global']) ? 1 : 0;

            $data = array(
                'controller' => $this->controller,
                'form_mode'  => 'edit',
                'form_action'=> site_url('forms_admin/update/' . $id),
                'submit_label' => 'Enregistrer',
                'form'       => $form,
                'section_id' => $section_id,
                'handler_classes' => $this->_available_handler_classes(),
                'is_workflow_form' => in_array($current['public_slug'], $this->workflow_form_slugs, true),
                'is_currently_published' => $current['status'] === 'published',
                'error'      => validation_errors(),
            );
            $this->render_view('forms_admin/bs_form', $data);
            return;
        }

        $is_global = (int) $this->input->post('is_global');
        $club = ($section_id > 0 && !$is_global) ? $section_id : null;

        $new_status = $this->input->post('status');
        $allowed    = array('draft', 'published', 'archived');
        $status     = in_array($new_status, $allowed, true) ? $new_status : $current['status'];

        $new_code = trim($this->input->post('code'));
        if ($new_code !== $current['code'] && $this->forms_model->code_exists($new_code, $id)) {
            $form = array_merge($current, $this->input->post());
            $form['is_global'] = !empty($form['is_global']) ? 1 : 0;
            $data = array(
                'controller'   => $this->controller,
                'form_mode'    => 'edit',
                'form_action'  => site_url('forms_admin/update/' . $id),
                'submit_label' => 'Enregistrer',
                'form'         => $form,
                'section_id'   => $section_id,
                'handler_classes' => $this->_available_handler_classes(),
                'is_workflow_form' => in_array($current['public_slug'], $this->workflow_form_slugs, true),
                'is_currently_published' => $current['status'] === 'published',
                'error'        => 'Ce code est déjà utilisé par un autre formulaire.',
            );
            $this->render_view('forms_admin/bs_form', $data);
            return;
        }

        $ok = $this->forms_model->update_form($id, array(
            'code'            => $new_code,
            'club'            => $club,
            'title'           => trim($this->input->post('title')),
            'description'     => trim($this->input->post('description')),
            'public_slug'     => trim($this->input->post('public_slug')),
            'css_scope'       => trim($this->input->post('css_scope')),
            'global_css'      => (string) $this->input->post('global_css'),
            'required_params' => $this->input->post('required_params') ?: $current['required_params'],
            'allow_upload_response' => (int) $this->input->post('allow_upload_response'),
            'handler_class'   => $this->_validated_handler_class($this->input->post('handler_class')),
            'status'          => $status,
            'updated_by'      => $this->dx_auth->get_username(),
        ));

        if (!$ok) {
            $this->session->set_flashdata('forms_error', 'Impossible de modifier ce formulaire.');
            redirect('forms_admin/edit/' . $id);
            return;
        }

        $this->session->set_flashdata('forms_success', 'Formulaire « ' . trim($this->input->post('title')) . ' » mis à jour.');
        redirect('forms_admin');
    }

    public function delete($id = 0) {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            show_error('Méthode non autorisée.', 405);
            return;
        }
        $id = (int) $id;
        if ($id <= 0 || !$this->forms_model->delete_form($id)) {
            $this->session->set_flashdata('forms_error', 'Impossible de supprimer ce formulaire.');
            redirect('forms_admin');
            return;
        }

        $this->session->set_flashdata('forms_success', 'Formulaire supprime.');
        redirect('forms_admin');
    }

    public function duplicate($id = 0) {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            show_error('Méthode non autorisée.', 405);
            return;
        }
        $id = (int) $id;
        if ($id <= 0) {
            $this->session->set_flashdata('forms_error', 'Formulaire introuvable.');
            redirect('forms_admin');
            return;
        }

        $new_id = $this->forms_model->duplicate_form($id, $this->dx_auth->get_username());
        if (!$new_id) {
            $this->session->set_flashdata('forms_error', 'Impossible de dupliquer ce formulaire.');
            redirect('forms_admin');
            return;
        }

        $this->session->set_flashdata('forms_success', 'Formulaire duplique.');
        redirect('forms_admin/edit/' . (int) $new_id);
    }

    public function publish($id = 0) {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            show_error('Méthode non autorisée.', 405);
            return;
        }
        $id = (int) $id;
        if ($id <= 0 || !$this->forms_model->publish_form($id, $this->dx_auth->get_username())) {
            $this->session->set_flashdata('forms_error', 'Impossible de publier ce formulaire.');
            redirect('forms_admin');
            return;
        }

        $this->session->set_flashdata('forms_success', 'Formulaire publie.');
        redirect('forms_admin');
    }

    public function css_preview($id = 0) {
        $id = (int) $id;
        $form = $this->forms_model->get_by_id($id);
        if (!$form) {
            $this->session->set_flashdata('forms_error', 'Formulaire introuvable.');
            redirect('forms_admin');
            return;
        }

        $pages = $this->form_pages_model->get_form_pages((int) $form['id']);
        $page_count = count($pages);

        $current_page_number = (int) $this->input->get('page');
        if ($current_page_number <= 0) {
            $current_page_number = 1;
        }
        if ($current_page_number > $page_count && $page_count > 0) {
            $current_page_number = $page_count;
        }

        $current_page = null;
        foreach ($pages as $page) {
            if ((int) $page['page_number'] === $current_page_number) {
                $current_page = $page;
                break;
            }
        }
        if (!$current_page && !empty($pages)) {
            $current_page = $pages[0];
            $current_page_number = (int) $current_page['page_number'];
        }

        $render_fields = array();
        if ($current_page) {
            $fields = $this->form_fields_model->get_page_fields((int) $current_page['id']);
            $render_fields = $this->forms_renderer->normalize_fields_for_view($fields, array());
        } else {
            $fields = array();
        }

        $data = array(
            'controller'          => $this->controller,
            'form'                => $form,
            'pages'               => $pages,
            'current_page'        => $current_page ?: array('title' => '', 'content_html' => ''),
            'current_page_number' => $current_page_number,
            'page_count'          => $page_count,
            'fields'              => $fields,
            'render_fields'       => $render_fields,
        );

        $this->render_view('forms_admin/bs_css_preview', $data);
    }

    public function pages($form_id = 0) {
        $form = $this->load_form_or_redirect($form_id);
        if (!$form) {
            return;
        }

        $data = array(
            'controller' => $this->controller,
            'form'       => $form,
            'pages'      => $this->form_pages_model->get_form_pages((int) $form['id']),
            'success'    => $this->session->flashdata('forms_success') ?: '',
            'error'      => $this->session->flashdata('forms_error') ?: '',
        );

        $this->render_view('forms_admin/bs_pages', $data);
    }

    public function page_create($form_id = 0) {
        $form = $this->load_form_or_redirect($form_id);
        if (!$form) {
            return;
        }

        $data = array(
            'controller'    => $this->controller,
            'form'          => $form,
            'page_mode'     => 'create',
            'form_action'   => site_url('forms_admin/page_store/' . (int) $form['id']),
            'submit_label'  => 'Ajouter la page',
            'page'          => array(
                'page_number'  => $this->form_pages_model->next_page_number((int) $form['id']),
                'title'        => '',
                'content_html' => '',
            ),
            'error'         => '',
        );

        $this->render_view('forms_admin/bs_page_form', $data);
    }

    public function page_store($form_id = 0) {
        $form = $this->load_form_or_redirect($form_id);
        if (!$form) {
            return;
        }

        $this->form_validation->set_rules('page_number', 'Numero de page', 'required|integer|greater_than[0]');
        $this->form_validation->set_rules('title', 'Titre', 'max_length[255]');

        if ($this->form_validation->run() === FALSE) {
            $data = array(
                'controller'    => $this->controller,
                'form'          => $form,
                'page_mode'     => 'create',
                'form_action'   => site_url('forms_admin/page_store/' . (int) $form['id']),
                'submit_label'  => 'Ajouter la page',
                'page'          => $this->input->post(),
                'error'         => validation_errors(),
            );
            $this->render_view('forms_admin/bs_page_form', $data);
            return;
        }

        $content_html = html_entity_decode((string) $this->input->post('content_html', FALSE), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $extracted    = $this->extract_html_fields($content_html);
        $field_names  = array_column($extracted, 'name');
        $conflict     = $this->validate_html_field_names((int) $form['id'], 0, $field_names);

        if ($conflict) {
            $data = array(
                'controller'    => $this->controller,
                'form'          => $form,
                'page_mode'     => 'create',
                'form_action'   => site_url('forms_admin/page_store/' . (int) $form['id']),
                'submit_label'  => 'Ajouter la page',
                'page'          => $this->input->post(),
                'error'         => $conflict,
            );
            $this->render_view('forms_admin/bs_page_form', $data);
            return;
        }

        $page_id = $this->form_pages_model->create_page(array(
            'form_id'      => (int) $form['id'],
            'page_number'  => (int) $this->input->post('page_number'),
            'title'        => trim((string) $this->input->post('title')),
            'content_html' => $content_html,
            'created_by'   => $this->dx_auth->get_username(),
        ));

        if (!$page_id) {
            $this->session->set_flashdata('forms_error', 'Impossible d\'ajouter la page.');
            redirect('forms_admin/pages/' . (int) $form['id']);
            return;
        }

        $this->sync_fields_from_html((int) $form['id'], (int) $page_id, $content_html, $this->dx_auth->get_username());

        $this->session->set_flashdata('forms_success', 'Page ajoutee.');
        redirect('forms_admin/pages/' . (int) $form['id']);
    }

    public function page_edit($form_id = 0, $page_id = 0) {
        $form = $this->load_form_or_redirect($form_id);
        if (!$form) {
            return;
        }

        $page = $this->load_page_for_form_or_redirect($form, $page_id);
        if (!$page) {
            return;
        }

        $data = array(
            'controller'    => $this->controller,
            'form'          => $form,
            'page_mode'     => 'edit',
            'form_action'   => site_url('forms_admin/page_update/' . (int) $form['id'] . '/' . (int) $page['id']),
            'submit_label'  => 'Enregistrer',
            'page'          => $page,
            'error'         => '',
        );

        $this->render_view('forms_admin/bs_page_form', $data);
    }

    public function page_update($form_id = 0, $page_id = 0) {
        $form = $this->load_form_or_redirect($form_id);
        if (!$form) {
            return;
        }

        $page = $this->load_page_for_form_or_redirect($form, $page_id);
        if (!$page) {
            return;
        }

        $this->form_validation->set_rules('page_number', 'Numero de page', 'required|integer|greater_than[0]');
        $this->form_validation->set_rules('title', 'Titre', 'max_length[255]');

        if ($this->form_validation->run() === FALSE) {
            $data = array(
                'controller'    => $this->controller,
                'form'          => $form,
                'page_mode'     => 'edit',
                'form_action'   => site_url('forms_admin/page_update/' . (int) $form['id'] . '/' . (int) $page['id']),
                'submit_label'  => 'Enregistrer',
                'page'          => array_merge($page, $this->input->post()),
                'error'         => validation_errors(),
            );
            $this->render_view('forms_admin/bs_page_form', $data);
            return;
        }

        $content_html = html_entity_decode((string) $this->input->post('content_html', FALSE), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $extracted    = $this->extract_html_fields($content_html);
        $field_names  = array_column($extracted, 'name');
        $conflict     = $this->validate_html_field_names((int) $form['id'], (int) $page['id'], $field_names);

        if ($conflict) {
            $data = array(
                'controller'    => $this->controller,
                'form'          => $form,
                'page_mode'     => 'edit',
                'form_action'   => site_url('forms_admin/page_update/' . (int) $form['id'] . '/' . (int) $page['id']),
                'submit_label'  => 'Enregistrer',
                'page'          => array_merge($page, $this->input->post()),
                'error'         => $conflict,
            );
            $this->render_view('forms_admin/bs_page_form', $data);
            return;
        }

        $ok = $this->form_pages_model->update_page((int) $page['id'], array(
            'page_number'  => (int) $this->input->post('page_number'),
            'title'        => trim((string) $this->input->post('title')),
            'content_html' => $content_html,
            'updated_by'   => $this->dx_auth->get_username(),
        ));

        if (!$ok) {
            $this->session->set_flashdata('forms_error', 'Impossible de modifier la page.');
            redirect('forms_admin/pages/' . (int) $form['id']);
            return;
        }

        $this->sync_fields_from_html((int) $form['id'], (int) $page['id'], $content_html, $this->dx_auth->get_username());

        $this->session->set_flashdata('forms_success', 'Page mise a jour.');
        redirect('forms_admin/pages/' . (int) $form['id']);
    }

    public function page_delete($form_id = 0, $page_id = 0) {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            show_error('Méthode non autorisée.', 405);
            return;
        }
        $form = $this->load_form_or_redirect($form_id);
        if (!$form) {
            return;
        }

        $page = $this->load_page_for_form_or_redirect($form, $page_id);
        if (!$page) {
            return;
        }

        if (!$this->form_pages_model->delete_page((int) $page['id'])) {
            $this->session->set_flashdata('forms_error', 'Impossible de supprimer la page.');
            redirect('forms_admin/pages/' . (int) $form['id']);
            return;
        }

        $this->session->set_flashdata('forms_success', 'Page supprimee.');
        redirect('forms_admin/pages/' . (int) $form['id']);
    }

    public function page_import($form_id = 0) {
        $form = $this->load_form_or_redirect($form_id);
        if (!$form) {
            return;
        }

        $this->form_validation->set_rules('import_title', 'Titre', 'max_length[255]');
        $this->form_validation->set_rules('import_content', 'Contenu', 'required');
        $this->form_validation->set_rules('import_format', 'Format', 'required|in_list[text,html]');

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('forms_error', validation_errors());
            redirect('forms_admin/pages/' . (int) $form['id']);
            return;
        }

        $format       = (string) $this->input->post('import_format');
        $raw_content  = (string) $this->input->post('import_content', FALSE);
        $content_html = $format === 'text'
            ? nl2br(html_escape($raw_content))
            : html_entity_decode($raw_content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $extracted   = $this->extract_html_fields($content_html);
        $field_names = array_column($extracted, 'name');
        $conflict    = $this->validate_html_field_names((int) $form['id'], 0, $field_names);

        if ($conflict) {
            $this->session->set_flashdata('forms_error', $conflict);
            redirect('forms_admin/pages/' . (int) $form['id']);
            return;
        }

        $page_id = $this->form_pages_model->create_page(array(
            'form_id'      => (int) $form['id'],
            'page_number'  => $this->form_pages_model->next_page_number((int) $form['id']),
            'title'        => trim((string) $this->input->post('import_title')),
            'content_html' => $content_html,
            'created_by'   => $this->dx_auth->get_username(),
        ));

        if (!$page_id) {
            $this->session->set_flashdata('forms_error', 'Impossible d\'importer la page.');
            redirect('forms_admin/pages/' . (int) $form['id']);
            return;
        }

        $this->sync_fields_from_html((int) $form['id'], (int) $page_id, $content_html, $this->dx_auth->get_username());

        $count = count($extracted);
        $this->session->set_flashdata('forms_success', 'Page importee.' . ($count > 0 ? ' ' . $count . ' champ(s) détecté(s).' : ''));
        redirect('forms_admin/pages/' . (int) $form['id']);
    }

    public function page_export($form_id = 0, $page_id = 0, $format = 'html') {
        $form = $this->load_form_or_redirect($form_id);
        if (!$form) {
            return;
        }

        $page = $this->load_page_for_form_or_redirect($form, $page_id);
        if (!$page) {
            return;
        }

        $safe_code = preg_replace('/[^a-zA-Z0-9_-]+/', '-', (string) $form['code']);
        $filename = $safe_code . '-page-' . (int) $page['page_number'];

        if ($format === 'txt') {
            $content = trim(strip_tags((string) $page['content_html']));
            header('Content-Type: text/plain; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.txt"');
            echo $content;
            return;
        }

        $content = (string) $page['content_html'];
        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.html"');
        echo $content;
    }

    public function submissions($form_id = 0) {
        $form = $this->load_form_or_redirect($form_id);
        if (!$form) {
            return;
        }

        $submissions = $this->form_submissions_model->get_form_submissions((int) $form['id'], 200, 0);

        $upload_submission_ids = array();
        foreach ($submissions as $submission) {
            if ($submission['submission_method'] === 'upload') {
                $upload_submission_ids[] = (int) $submission['id'];
            }
        }

        $data = array(
            'controller'    => $this->controller,
            'form'          => $form,
            'submissions'   => $submissions,
            'upload_files'  => $this->form_submissions_model->get_uploaded_response_files_for_submissions($upload_submission_ids),
            'success'       => $this->session->flashdata('forms_success') ?: '',
            'error'         => $this->session->flashdata('forms_error') ?: '',
        );

        $this->render_view('forms_admin/bs_submissions', $data);
    }

    public function submission($form_id = 0, $submission_id = 0) {
        $form = $this->load_form_or_redirect($form_id);
        if (!$form) {
            return;
        }

        $this->load->vars([
            'nav_back_url'   => 'forms_admin/submissions/' . (int) $form['id'],
            'nav_back_label' => 'Réponses',
        ]);

        $submission = $this->form_submissions_model->get_by_id((int) $submission_id);
        if (!$submission || (int) $submission['form_id'] !== (int) $form['id']) {
            $this->session->set_flashdata('forms_error', 'Soumission introuvable pour ce formulaire.');
            redirect('forms_admin/submissions/' . (int) $form['id']);
            return;
        }

        if ($submission['submission_method'] === 'upload') {
            $this->_redirect_to_uploaded_response_file($form, $submission);
            return;
        }

        $data = array(
            'controller' => $this->controller,
            'form'       => $form,
            'submission' => $submission,
            'values'     => $this->form_submissions_model->get_submission_values((int) $submission['id']),
            'files'      => $this->form_submissions_model->get_submission_files((int) $submission['id']),
            'error'      => $this->session->flashdata('forms_error') ?: '',
        );

        $this->render_view('forms_admin/bs_submission', $data);
    }

    /**
     * Réponses par téléchargement (submission_method='upload') : pas de vue "champs",
     * on redirige directement vers le fichier téléversé.
     */
    private function _redirect_to_uploaded_response_file($form, $submission) {
        $file = $this->form_submissions_model->get_uploaded_response_file((int) $submission['id']);
        if (!$file) {
            $this->session->set_flashdata('forms_error', 'Fichier introuvable pour cette soumission.');
            redirect('forms_admin/submissions/' . (int) $form['id']);
            return;
        }

        redirect('forms_admin/submission_file/' . (int) $form['id'] . '/' . (int) $submission['id'] . '/' . (int) $file['id'] . '?inline=1');
    }

    public function submission_view($form_id = 0, $submission_id = 0) {
        $form = $this->load_form_or_redirect($form_id);
        if (!$form) {
            return;
        }

        $this->load->vars([
            'nav_back_url'   => 'forms_admin/submissions/' . (int) $form['id'],
            'nav_back_label' => 'Réponses',
        ]);

        $submission = $this->form_submissions_model->get_by_id((int) $submission_id);
        if (!$submission || (int) $submission['form_id'] !== (int) $form['id']) {
            show_404();
            return;
        }

        if ($submission['submission_method'] === 'upload') {
            $this->_redirect_to_uploaded_response_file($form, $submission);
            return;
        }

        $values_raw = $this->form_submissions_model->get_submission_values((int) $submission['id']);
        $files_raw  = $this->form_submissions_model->get_submission_files((int) $submission['id']);
        $pages      = $this->form_pages_model->get_form_pages((int) $form['id']);

        $values_by_name = array();
        foreach ($values_raw as $v) {
            $values_by_name[(string) $v['field_name']] = (string) $v['value_text'];
        }
        $files_by_name = array();
        $sig_files     = array();
        foreach ($files_raw as $f) {
            $fname = (string) $f['field_name'];
            $files_by_name[$fname] = (string) $f['original_name'];
            if (!empty($f['storage_path'])) {
                $sig_files[$fname] = $f;
            }
        }

        $body_parts = array();
        foreach ($pages as $page) {
            $raw = html_entity_decode((string) $page['content_html'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $raw = preg_replace('/<\!DOCTYPE[^>]*>/i', '', $raw);
            $raw = preg_replace('/<html[^>]*>/i', '', $raw);
            $raw = preg_replace('/<\/html>/i', '', $raw);
            $raw = preg_replace('/<head\b[^>]*>.*?<\/head>/is', '', $raw);
            $raw = preg_replace('/<body[^>]*>/i', '', $raw);
            $raw = preg_replace('/<\/body>/i', '', $raw);
            $raw = preg_replace('/<form\b[^>]*>/i', '', $raw);
            $raw = preg_replace('/<\/form>/i', '', $raw);
            $raw = preg_replace('/<button\b[^>]*\btype=["\']?(submit|reset)["\']?[^>]*>.*?<\/button>/is', '', $raw);
            $raw = preg_replace('/<input\b[^>]*\btype=["\']?(submit|reset|button)["\']?[^>]*\/?>/i', '', $raw);
            $raw = trim($raw);

            $body_parts[] = $this->_fill_html_values_readonly($raw, $values_by_name, $files_by_name, $sig_files);
        }

        $global_css   = !empty($form['global_css']) ? (string) $form['global_css'] : '';
        $title_safe   = htmlspecialchars($form['title'] . ' — Réponse #' . (int) $submission['id'], ENT_QUOTES, 'UTF-8');
        $meta_safe    = htmlspecialchars($form['title'], ENT_QUOTES, 'UTF-8')
                      . ' — Réponse&nbsp;#' . (int) $submission['id']
                      . ' — ' . htmlspecialchars((string) $submission['submitted_at'], ENT_QUOTES, 'UTF-8');

        $separator = '<div style="page-break-after:always;"></div>' . "\n";
        $content   = implode($separator, $body_parts);

        $html = '<!DOCTYPE html>' . "\n"
              . '<html lang="fr"><head>' . "\n"
              . '<meta charset="UTF-8">' . "\n"
              . '<meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n"
              . '<title>' . $title_safe . '</title>' . "\n"
              . '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">' . "\n"
              . '<style>' . "\n"
              . 'body{background:#e8edf1;padding-top:56px;}' . "\n"
              . '.gvv-print-toolbar{position:fixed;top:0;left:0;right:0;z-index:9999;'
              .   'background:#fff;border-bottom:1px solid #dee2e6;padding:8px 16px;'
              .   'display:flex;align-items:center;gap:12px;box-shadow:0 2px 4px rgba(0,0,0,.08);}' . "\n"
              . '.gvv-print-toolbar .pt-title{font-size:.9rem;color:#6c757d;flex:1;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;}' . "\n"
              . '@media print{'
              .   'body{background:#fff;padding-top:0;}'
              .   '.gvv-print-toolbar{display:none!important;}'
              .   '*{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important;}'
              .   '.gvv-forms{border:none!important;box-shadow:none!important;margin:0!important;}'
              .   '.gvv-forms::before,.gvv-forms::after{display:none!important;}'
              . '}' . "\n"
              . '</style>' . "\n"
              . '<style>' . "\n"
              . str_ireplace('</style>', '<\/style>', $global_css) . "\n"
              . '</style>' . "\n"
              . '</head><body>' . "\n"
              . '<div class="gvv-print-toolbar">' . "\n"
              . '  <button onclick="window.print()" class="btn btn-sm btn-primary">🖨&nbsp; Imprimer / Enregistrer en PDF</button>' . "\n"
              . '  <a href="#" onclick="window.close();return false;" class="btn btn-sm btn-outline-secondary">Fermer</a>' . "\n"
              . '  <span class="pt-title">' . $meta_safe . '</span>' . "\n"
              . '</div>' . "\n"
              . '<div style="padding:8px;">' . "\n"
              . $content . "\n"
              . '</div>' . "\n"
              . '</body></html>';

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: text/html; charset=UTF-8');
        echo $html;
        exit;
    }

    public function submission_pdf($form_id = 0, $submission_id = 0) {
        $form = $this->load_form_or_redirect($form_id);
        if (!$form) {
            return;
        }

        $submission = $this->form_submissions_model->get_by_id((int) $submission_id);
        if (!$submission || (int) $submission['form_id'] !== (int) $form['id']) {
            $this->session->set_flashdata('forms_error', 'Soumission introuvable pour ce formulaire.');
            redirect('forms_admin/submissions/' . (int) $form['id']);
            return;
        }

        if ($submission['submission_method'] === 'upload') {
            $this->_redirect_to_uploaded_response_file($form, $submission);
            return;
        }

        $values_raw = $this->form_submissions_model->get_submission_values((int) $submission['id']);
        $files_raw  = $this->form_submissions_model->get_submission_files((int) $submission['id']);
        $pages      = $this->form_pages_model->get_form_pages((int) $form['id']);

        $values_by_name = array();
        foreach ($values_raw as $v) {
            $values_by_name[(string) $v['field_name']] = (string) $v['value_text'];
        }
        $files_by_name = array();
        $sig_files     = array();
        foreach ($files_raw as $f) {
            $fname = (string) $f['field_name'];
            $files_by_name[$fname] = (string) $f['original_name'];
            if (!empty($f['storage_path'])) {
                $sig_files[$fname] = $f;
            }
        }

        $css = !empty($form['global_css']) ? (string) $form['global_css'] : '';

        $body_parts = array();
        foreach ($pages as $page) {
            $raw = html_entity_decode(
                (string) $page['content_html'],
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            );
            $raw = preg_replace('/<\!DOCTYPE[^>]*>/i', '', $raw);
            $raw = preg_replace('/<html[^>]*>/i', '', $raw);
            $raw = preg_replace('/<\/html>/i', '', $raw);
            $raw = preg_replace('/<head\b[^>]*>.*?<\/head>/is', '', $raw);
            $raw = preg_replace('/<body[^>]*>/i', '', $raw);
            $raw = preg_replace('/<\/body>/i', '', $raw);
            $raw = preg_replace('/<form\b[^>]*>/i', '', $raw);
            $raw = preg_replace('/<\/form>/i', '', $raw);
            $raw = preg_replace('/<button\b[^>]*\btype=["\']?(submit|reset)["\']?[^>]*>.*?<\/button>/is', '', $raw);
            $raw = preg_replace('/<input\b[^>]*\btype=["\']?(submit|reset|button)["\']?[^>]*\/?>/i', '', $raw);
            $raw = trim($raw);

            $body_parts[] = $this->_fill_html_values($raw, $values_by_name, $files_by_name, $sig_files);
        }

        // Include Bootstrap CSS so grid/component classes (col-md-*, form-control, etc.)
        // are applied by wkhtmltopdf. Embedded inline to keep the HTML self-contained.
        $bootstrap_css = '';
        $bootstrap_path = FCPATH . 'assets/css/bootstrap.min.css';
        if (is_readable($bootstrap_path)) {
            $bootstrap_css = file_get_contents($bootstrap_path);
        }

        // wkhtmltopdf renders A4 at ~794 CSS px, below Bootstrap's md breakpoint (768px).
        // Force col-md-* and col-lg-* widths unconditionally so the grid is always applied.
        $pdf_grid_fix = '.row{display:flex!important;flex-wrap:wrap!important;}'
            . '.col-md-1{flex:0 0 auto!important;width:8.333333%!important;}'
            . '.col-md-2{flex:0 0 auto!important;width:16.666667%!important;}'
            . '.col-md-3{flex:0 0 auto!important;width:25%!important;}'
            . '.col-md-4{flex:0 0 auto!important;width:33.333333%!important;}'
            . '.col-md-5{flex:0 0 auto!important;width:41.666667%!important;}'
            . '.col-md-6{flex:0 0 auto!important;width:50%!important;}'
            . '.col-md-7{flex:0 0 auto!important;width:58.333333%!important;}'
            . '.col-md-8{flex:0 0 auto!important;width:66.666667%!important;}'
            . '.col-md-9{flex:0 0 auto!important;width:75%!important;}'
            . '.col-md-10{flex:0 0 auto!important;width:83.333333%!important;}'
            . '.col-md-11{flex:0 0 auto!important;width:91.666667%!important;}'
            . '.col-md-12{flex:0 0 auto!important;width:100%!important;}'
            . '.col-lg-1{flex:0 0 auto!important;width:8.333333%!important;}'
            . '.col-lg-2{flex:0 0 auto!important;width:16.666667%!important;}'
            . '.col-lg-3{flex:0 0 auto!important;width:25%!important;}'
            . '.col-lg-4{flex:0 0 auto!important;width:33.333333%!important;}'
            . '.col-lg-5{flex:0 0 auto!important;width:41.666667%!important;}'
            . '.col-lg-6{flex:0 0 auto!important;width:50%!important;}'
            . '.col-lg-7{flex:0 0 auto!important;width:58.333333%!important;}'
            . '.col-lg-8{flex:0 0 auto!important;width:66.666667%!important;}'
            . '.col-lg-9{flex:0 0 auto!important;width:75%!important;}'
            . '.col-lg-10{flex:0 0 auto!important;width:83.333333%!important;}'
            . '.col-lg-11{flex:0 0 auto!important;width:91.666667%!important;}'
            . '.col-lg-12{flex:0 0 auto!important;width:100%!important;}';

        // Force wkhtmltopdf to render background colours and images (suppressed by
        // --print-media-type by default, which activates @media print rules that
        // strip backgrounds for ink economy).
        $pdf_bg_fix = '* { -webkit-print-color-adjust: exact !important;'
                    . '    print-color-adjust: exact !important; }';

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">'
              . '<style>' . $bootstrap_css . '</style>'
              . '<style>' . $pdf_grid_fix . '</style>'
              . '<style>' . $pdf_bg_fix . '</style>'
              . '<style>' . $css . '</style>'
              . '</head><body>'
              . implode('<p style="page-break-after:always;"></p>', $body_parts)
              . '</body></html>';

        $html = $this->_embed_local_images_as_base64($html);

        $safe_title = preg_replace('/[^a-z0-9_\-]/i', '_', $form['title']);
        $filename   = 'reponse_' . (int) $submission['id'] . '_' . $safe_title . '.pdf';

        $tmp_html = tempnam(sys_get_temp_dir(), 'gvv_pdf_') . '.html';
        $tmp_pdf  = tempnam(sys_get_temp_dir(), 'gvv_pdf_') . '.pdf';

        file_put_contents($tmp_html, $html);

        // Use configurable path; fall back to common install locations
        $binary = $this->config->item('wkhtmltopdf_path') ?: 'wkhtmltopdf';
        if ($binary === 'wkhtmltopdf') {
            foreach (array('/usr/bin/wkhtmltopdf', '/usr/local/bin/wkhtmltopdf', '/usr/local/wkhtmltox/bin/wkhtmltopdf') as $candidate) {
                if (is_executable($candidate)) { $binary = $candidate; break; }
            }
        }

        $cmd = sprintf(
            '%s --page-size A4 --margin-top 10mm --margin-bottom 10mm'
            . ' --margin-left 10mm --margin-right 10mm'
            . ' --encoding utf-8 --disable-javascript --print-media-type --quiet %s %s 2>&1',
            escapeshellarg($binary),
            escapeshellarg($tmp_html),
            escapeshellarg($tmp_pdf)
        );

        $output = shell_exec($cmd);

        if (!file_exists($tmp_pdf) || filesize($tmp_pdf) === 0) {
            @unlink($tmp_html);
            @unlink($tmp_pdf);
            log_message('error', 'wkhtmltopdf failed: ' . $output);
            show_error('La génération du PDF a échoué. Vérifiez que wkhtmltopdf est installé.');
            return;
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($tmp_pdf));
        readfile($tmp_pdf);

        @unlink($tmp_html);
        @unlink($tmp_pdf);
        exit;
    }

    private function _fill_html_values_readonly($html, array $values_by_name, array $files_by_name, array $sig_files = array()) {
        if (trim($html) === '') {
            return $html;
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>'
        );
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        foreach (iterator_to_array($xpath->query('//input[@name]')) as $input) {
            $name      = $input->getAttribute('name');
            $base_name = rtrim($name, '[]');
            $type      = strtolower($input->getAttribute('type') ?: 'text');

            if (in_array($type, array('submit', 'reset', 'button', 'hidden'))) {
                continue;
            }

            if ($type === 'file') {
                $display = isset($files_by_name[$base_name]) ? $files_by_name[$base_name] : '—';
                $span    = $dom->createElement('span');
                $span->setAttribute('style', 'font-style:italic; color:#555;');
                $span->appendChild($dom->createTextNode($display));
                $input->parentNode->replaceChild($span, $input);
                continue;
            }

            if ($type === 'checkbox') {
                $checked_value   = $input->getAttribute('value');
                $effective_value = ($checked_value === '') ? 'on' : $checked_value;
                $submitted       = isset($values_by_name[$base_name]) ? $values_by_name[$base_name] : '';
                $is_checked      = ($submitted === $effective_value)
                                || (strpos(',' . $submitted . ',', ',' . $effective_value . ',') !== false);
                if ($is_checked) {
                    $input->setAttribute('checked', 'checked');
                } else {
                    $input->removeAttribute('checked');
                }
            } elseif ($type === 'radio') {
                $radio_value = $input->getAttribute('value');
                $submitted   = isset($values_by_name[$base_name]) ? $values_by_name[$base_name] : '';
                if ($submitted === $radio_value) {
                    $input->setAttribute('checked', 'checked');
                } else {
                    $input->removeAttribute('checked');
                }
            } else {
                $value = isset($values_by_name[$base_name]) ? $values_by_name[$base_name] : '';
                $input->setAttribute('value', $value);
            }
            $input->setAttribute('readonly', 'readonly');
            $input->setAttribute('tabindex', '-1');
        }

        foreach (iterator_to_array($xpath->query('//textarea[@name]')) as $textarea) {
            $name  = $textarea->getAttribute('name');
            $value = isset($values_by_name[$name]) ? $values_by_name[$name] : '';
            while ($textarea->firstChild) {
                $textarea->removeChild($textarea->firstChild);
            }
            $textarea->appendChild($dom->createTextNode($value));
            $textarea->setAttribute('readonly', 'readonly');
            $textarea->setAttribute('tabindex', '-1');
        }

        foreach (iterator_to_array($xpath->query('//select[@name]')) as $select) {
            $name      = $select->getAttribute('name');
            $submitted = isset($values_by_name[$name]) ? $values_by_name[$name] : '';
            foreach (iterator_to_array($xpath->query('.//option', $select)) as $option) {
                $opt_value = $option->hasAttribute('value') ? $option->getAttribute('value') : $option->nodeValue;
                if ($opt_value === $submitted) {
                    $option->setAttribute('selected', 'selected');
                } else {
                    $option->removeAttribute('selected');
                }
            }
            $select->setAttribute('disabled', 'disabled');
        }

        // Replace signature widgets with the stored image (or an empty placeholder)
        foreach (iterator_to_array($xpath->query('//*[@data-gvv-type][@data-gvv-name]')) as $div) {
            if (strtolower($div->getAttribute('data-gvv-type')) !== 'signature') {
                continue;
            }
            $name = $div->getAttribute('data-gvv-name');
            if (isset($sig_files[$name]) && !empty($sig_files[$name]['storage_path'])) {
                $img = $dom->createElement('img');
                $img->setAttribute('src', base_url(ltrim((string) $sig_files[$name]['storage_path'], '/')));
                $img->setAttribute('style', 'max-width:100%; max-height:80px; border:1px solid #dee2e6; border-radius:4px; display:block;');
                $div->parentNode->replaceChild($img, $div);
            } else {
                $span = $dom->createElement('span');
                $span->setAttribute('style', 'display:block; height:60px; border:1px dashed #adb5bd; border-radius:4px;');
                $div->parentNode->replaceChild($span, $div);
            }
        }

        $body   = $dom->getElementsByTagName('body')->item(0);
        $result = '';
        foreach ($body->childNodes as $child) {
            $result .= $dom->saveHTML($child);
        }

        return $result;
    }

    private function _fill_html_values($html, array $values_by_name, array $files_by_name, array $sig_files = array()) {
        if (trim($html) === '') {
            return $html;
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>'
        );
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        $inputs = iterator_to_array($xpath->query('//input[@name]'));
        foreach ($inputs as $input) {
            $name      = $input->getAttribute('name');
            $base_name = rtrim($name, '[]');
            $type      = strtolower($input->getAttribute('type') ?: 'text');

            if ($type === 'hidden') {
                continue;
            }

            if ($type === 'checkbox') {
                // CSS-drawn box — Unicode ☑/☐ are unreliable in wkhtmltopdf (font gaps)
                $checked_value   = $input->getAttribute('value');
                // Browsers submit 'on' when a checkbox has no value attribute
                $effective_value = ($checked_value === '') ? 'on' : $checked_value;
                $submitted       = isset($values_by_name[$base_name]) ? $values_by_name[$base_name] : '';
                $is_checked      = ($submitted === $effective_value)
                                || (strpos(',' . $submitted . ',', ',' . $effective_value . ',') !== false);
                $box = $dom->createElement('span');
                $box->setAttribute('style', 'display:inline-block;width:13px;height:13px;border:1.5px solid #333;vertical-align:middle;margin-right:3px;position:relative;background:#fff;');
                if ($is_checked) {
                    $tick = $dom->createElement('span');
                    $tick->setAttribute('style', 'display:block;position:absolute;left:2px;top:-1px;width:5px;height:9px;border-right:2px solid #000;border-bottom:2px solid #000;-webkit-transform:rotate(42deg);transform:rotate(42deg);');
                    $box->appendChild($tick);
                }
                $input->parentNode->replaceChild($box, $input);
                continue;
            }

            if ($type === 'radio') {
                // CSS-drawn circle — same reason
                $radio_value = $input->getAttribute('value');
                $submitted   = isset($values_by_name[$base_name]) ? $values_by_name[$base_name] : '';
                $is_selected = ($submitted === $radio_value);
                $dot = $dom->createElement('span');
                $bg  = $is_selected ? 'background:#222;' : 'background:#fff;';
                $dot->setAttribute('style', 'display:inline-block;width:13px;height:13px;border:1.5px solid #333;border-radius:50%;vertical-align:middle;margin-right:3px;' . $bg);
                $input->parentNode->replaceChild($dot, $input);
                continue;
            }

            if ($type === 'file') {
                $display = isset($files_by_name[$base_name]) ? $files_by_name[$base_name] : '—';
            } else {
                $display = isset($values_by_name[$base_name]) ? $values_by_name[$base_name] : '';
            }

            $el = $dom->createElement('span');
            $el->setAttribute('style', 'display:inline-block; border:1px solid #ced4da; border-radius:4px; background:#fff; padding:3px 8px; min-width:100px; min-height:24px; font-size:0.95em; vertical-align:middle;');
            $el->appendChild($dom->createTextNode($display));
            $input->parentNode->replaceChild($el, $input);
        }

        $textareas = iterator_to_array($xpath->query('//textarea[@name]'));
        foreach ($textareas as $textarea) {
            $name    = $textarea->getAttribute('name');
            $display = isset($values_by_name[$name]) ? $values_by_name[$name] : '';
            $div     = $dom->createElement('div');
            $div->setAttribute('style', 'border:1px solid #ced4da; border-radius:4px; background:#fff; padding:6px 8px; min-height:60px; width:100%; font-size:0.95em;');
            $div->appendChild($dom->createTextNode($display));
            $textarea->parentNode->replaceChild($div, $textarea);
        }

        $selects = iterator_to_array($xpath->query('//select[@name]'));
        foreach ($selects as $select) {
            $name    = $select->getAttribute('name');
            $display = isset($values_by_name[$name]) ? $values_by_name[$name] : '';
            $span    = $dom->createElement('span');
            $span->setAttribute('style', 'display:inline-block; border:1px solid #ced4da; border-radius:4px; background:#fff; padding:3px 8px; min-width:100px; min-height:24px; font-size:0.95em; vertical-align:middle;');
            $span->appendChild($dom->createTextNode($display));
            $select->parentNode->replaceChild($span, $select);
        }

        // Replace signature widgets with the stored image (or an empty placeholder)
        foreach (iterator_to_array($xpath->query('//*[@data-gvv-type][@data-gvv-name]')) as $div) {
            if (strtolower($div->getAttribute('data-gvv-type')) !== 'signature') {
                continue;
            }
            $name = $div->getAttribute('data-gvv-name');
            if (isset($sig_files[$name]) && !empty($sig_files[$name]['storage_path'])) {
                $abs_path = FCPATH . ltrim((string) $sig_files[$name]['storage_path'], '/');
                if (file_exists($abs_path) && is_readable($abs_path)) {
                    $img = $dom->createElement('img');
                    $img->setAttribute('src', 'data:image/png;base64,' . base64_encode(file_get_contents($abs_path)));
                    $img->setAttribute('style', 'max-width:100%; max-height:80px; border:1px solid #dee2e6; display:block;');
                    $div->parentNode->replaceChild($img, $div);
                } else {
                    $span = $dom->createElement('span');
                    $span->setAttribute('style', 'display:block; height:60px; border:1px dashed #adb5bd;');
                    $div->parentNode->replaceChild($span, $div);
                }
            } else {
                $span = $dom->createElement('span');
                $span->setAttribute('style', 'display:block; height:60px; border:1px dashed #adb5bd;');
                $div->parentNode->replaceChild($span, $div);
            }
        }

        $body   = $dom->getElementsByTagName('body')->item(0);
        $result = '';
        foreach ($body->childNodes as $child) {
            $result .= $dom->saveHTML($child);
        }

        return $result;
    }

    /**
     * Replace <img src="http://our-server/path"> with a standard data URI so the
     * HTML is self-contained and wkhtmltopdf can render it without network requests.
     */
    private function _embed_local_images_as_base64($html) {
        $base_url = rtrim(base_url(), '/') . '/';

        $embed_src = function ($src) use ($base_url) {
            if (strpos($src, $base_url) !== 0) {
                return null;
            }
            $abs_path = FCPATH . ltrim(substr($src, strlen($base_url)), '/');
            if (!file_exists($abs_path) || !is_readable($abs_path)) {
                return null;
            }
            $info = getimagesize($abs_path);
            $mime = $info ? $info['mime'] : 'image/png';
            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($abs_path));
        };

        // Double quotes
        $html = preg_replace_callback(
            '/(<img\b[^>]*\bsrc=")([^"]+)("[^>]*>)/i',
            function ($m) use ($embed_src) {
                $data = $embed_src($m[2]);
                return $data !== null ? $m[1] . $data . $m[3] : $m[0];
            },
            $html
        );

        // Single quotes
        $html = preg_replace_callback(
            "/(<img\b[^>]*\bsrc=')([^']+)('[^>]*>)/i",
            function ($m) use ($embed_src) {
                $data = $embed_src($m[2]);
                return $data !== null ? $m[1] . $data . $m[3] : $m[0];
            },
            $html
        );

        return $html;
    }

    private function _make_css_tcpdf_compatible($css) {
        if (trim($css) === '') {
            return '';
        }

        // Remove @import (fonts not available in TCPDF context)
        $css = preg_replace('/@import\b[^;]+;/i', '', $css);

        // Remove @media blocks
        $css = preg_replace('/@media\b[^{]*\{(?:[^{}]*|\{[^{}]*\})*\}/is', '', $css);

        // Remove ::before and ::after pseudo-elements
        $css = preg_replace('/[^{,}]+::(?:before|after)\s*\{[^{}]*\}/i', '', $css);

        // Extract CSS custom property declarations and build a resolution map
        $vars = array();
        preg_match_all('/--([a-zA-Z0-9_-]+)\s*:\s*([^;}{]+);/', $css, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $vars['--' . trim($m[1])] = trim($m[2]);
        }

        // Resolve var() — two passes to handle vars referencing other vars
        for ($pass = 0; $pass < 2; $pass++) {
            foreach ($vars as $name => $value) {
                $css = preg_replace('/var\(\s*' . preg_quote($name, '/') . '\s*\)/', $value, $css);
            }
        }
        $css = preg_replace('/var\([^)]+\)/', 'inherit', $css);

        // Replace flex/grid with block (unsupported by TCPDF)
        $css = preg_replace('/display\s*:\s*(?:flex|inline-flex|grid)\s*;/i', 'display: block;', $css);

        // Convert 'background' shorthand to 'background-color' when value is a plain color.
        // TCPDF only processes background-color; the shorthand 'background' is silently ignored.
        $css = preg_replace(
            '/\bbackground\s*:\s*(#[0-9a-fA-F]{3,8}|rgba?\([^)]+\)|[a-zA-Z]+)\s*;/i',
            'background-color:$1;',
            $css
        );
        // Remove remaining 'background' shorthand (images, gradients — not renderable in TCPDF)
        $css = preg_replace('/\bbackground\s*:[^;]+;/i', '', $css);

        // Remove unsupported properties
        $css = preg_replace('/box-shadow\s*:[^;]+;/i', '', $css);
        $css = preg_replace('/transition\s*:[^;]+;/i', '', $css);
        $css = preg_replace('/gap\s*:[^;]+;/i', '', $css);
        $css = preg_replace('/flex(?:-[a-z]+)?\s*:[^;]+;/i', '', $css);
        $css = preg_replace('/border-radius(?:-[a-z]+)?\s*:[^;]+;/i', '', $css);
        $css = preg_replace('/(?:min|max)-(?:width|height)\s*:[^;]+;/i', '', $css);
        $css = preg_replace('/(?:align|justify)-(?:items|content|self)\s*:[^;]+;/i', '', $css);

        return $css;
    }

    public function submission_delete($form_id = 0, $submission_id = 0) {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            redirect('forms_admin/submissions/' . (int) $form_id);
            return;
        }

        $form = $this->load_form_or_redirect($form_id);
        if (!$form) {
            return;
        }

        $submission = $this->form_submissions_model->get_by_id((int) $submission_id);
        if (!$submission || (int) $submission['form_id'] !== (int) $form['id']) {
            $this->session->set_flashdata('forms_error', 'Soumission introuvable pour ce formulaire.');
            redirect('forms_admin/submissions/' . (int) $form['id']);
            return;
        }

        if ($this->form_submissions_model->delete_submission((int) $submission_id)) {
            $this->session->set_flashdata('forms_success', 'Réponse #' . (int) $submission_id . ' supprimée.');
        } else {
            $this->session->set_flashdata('forms_error', 'Impossible de supprimer cette réponse.');
        }

        redirect('forms_admin/submissions/' . (int) $form['id']);
    }

    public function submission_rotate($form_id = 0, $submission_id = 0, $direction = '') {
        $form = $this->load_form_or_redirect($form_id);
        if (!$form) {
            return;
        }

        $this->lang->load('archived_documents');

        $submission = $this->form_submissions_model->get_by_id((int) $submission_id);
        if (!$submission || (int) $submission['form_id'] !== (int) $form['id']) {
            $this->session->set_flashdata('forms_error', 'Soumission introuvable pour ce formulaire.');
            redirect('forms_admin/submissions/' . (int) $form['id']);
            return;
        }

        if (!in_array($direction, array('cw', 'ccw'), true)) {
            $this->session->set_flashdata('forms_error', 'Direction invalide.');
            redirect('forms_admin/submissions/' . (int) $form['id']);
            return;
        }

        $file = $this->form_submissions_model->get_uploaded_response_file((int) $submission['id']);
        if (!$file) {
            $this->session->set_flashdata('forms_error', 'Fichier introuvable pour cette soumission.');
            redirect('forms_admin/submissions/' . (int) $form['id']);
            return;
        }

        $relative_path = ltrim((string) $file['storage_path'], '/');
        $full_path = FCPATH . $relative_path;
        $uploads_root = realpath(FCPATH . 'uploads');
        $real = realpath($full_path);

        if ($real === false || $uploads_root === false || strpos($real, $uploads_root) !== 0 || !is_file($real)) {
            $this->session->set_flashdata('forms_error', 'Fichier introuvable sur le stockage.');
            redirect('forms_admin/submissions/' . (int) $form['id']);
            return;
        }

        $mime = !empty($file['mime_type']) ? $file['mime_type'] : (string) @mime_content_type($real);

        $this->load->library('file_rotator');
        $result = $this->file_rotator->rotate($real, $mime, $direction);

        if (!$result['success']) {
            switch ($result['error_code']) {
                case 'tool_missing':
                    $this->session->set_flashdata('forms_error', $this->lang->line('archived_documents_rotate_tool_missing') . ' (' . $result['tool'] . ')');
                    break;
                case 'rotate_failed':
                    $this->session->set_flashdata('forms_error', $this->lang->line('archived_documents_rotate_error') . ': ' . htmlspecialchars($result['detail']));
                    break;
                case 'not_supported':
                    $this->session->set_flashdata('forms_error', $this->lang->line('archived_documents_rotate_not_supported'));
                    break;
                default:
                    $this->session->set_flashdata('forms_error', 'Direction invalide.');
                    break;
            }
            redirect('forms_admin/submissions/' . (int) $form['id']);
            return;
        }

        if ($mime === 'application/pdf') {
            $this->load->library('pdf_thumbnail');
            $this->pdf_thumbnail->delete_thumbnail($real);
            $this->pdf_thumbnail->generate($real);
        }

        $this->session->set_flashdata('forms_success', $this->lang->line('archived_documents_rotate_success'));
        redirect('forms_admin/submissions/' . (int) $form['id']);
    }

    public function submission_file($form_id = 0, $submission_id = 0, $file_id = 0) {
        $form = $this->load_form_or_redirect($form_id);
        if (!$form) {
            return;
        }

        $submission = $this->form_submissions_model->get_by_id((int) $submission_id);
        if (!$submission || (int) $submission['form_id'] !== (int) $form['id']) {
            $this->session->set_flashdata('forms_error', 'Soumission introuvable pour ce formulaire.');
            redirect('forms_admin/submissions/' . (int) $form['id']);
            return;
        }

        $file = $this->form_submissions_model->get_submission_file_by_id((int) $file_id);
        if (!$file || (int) $file['submission_id'] !== (int) $submission['id']) {
            $this->session->set_flashdata('forms_error', 'Fichier introuvable pour cette soumission.');
            redirect('forms_admin/submission/' . (int) $form['id'] . '/' . (int) $submission['id']);
            return;
        }

        $relative_path = ltrim((string) $file['storage_path'], '/');
        $full_path = FCPATH . $relative_path;
        $uploads_root = realpath(FCPATH . 'uploads');
        $resolved = realpath($full_path);

        if ($resolved === false || $uploads_root === false || strpos($resolved, $uploads_root) !== 0 || !is_file($resolved)) {
            $this->session->set_flashdata('forms_error', 'Fichier introuvable sur le stockage.');
            redirect('forms_admin/submission/' . (int) $form['id'] . '/' . (int) $submission['id']);
            return;
        }

        $mime_type = !empty($file['mime_type']) ? $file['mime_type'] : 'application/octet-stream';
        $inline = ((int) $this->input->get('inline') === 1);
        $inline_allowed = $inline && (strpos($mime_type, 'image/') === 0 || $mime_type === 'application/pdf');

        header('Content-Type: ' . $mime_type);
        header('Content-Length: ' . filesize($resolved));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store, no-cache, must-revalidate');

        $safe_name = basename((string) $file['original_name']);
        if ($safe_name === '') {
            $safe_name = basename((string) $file['stored_name']);
        }
        $safe_name = str_replace(["\r", "\n", '"'], ['', '', ''], $safe_name);

        if ($inline_allowed) {
            header('Content-Disposition: inline; filename="' . $safe_name . '"');
        } else {
            header('Content-Disposition: attachment; filename="' . $safe_name . '"');
        }

        readfile($resolved);
    }

    public function fields($form_id = 0, $page_id = 0) {
        $form = $this->load_form_or_redirect($form_id);
        if (!$form) {
            return;
        }
        $page = $this->load_page_for_form_or_redirect($form, $page_id);
        if (!$page) {
            return;
        }

        $data = array(
            'controller' => $this->controller,
            'form'       => $form,
            'page'       => $page,
            'fields'     => $this->form_fields_model->get_page_fields((int) $page['id']),
            'success'    => $this->session->flashdata('forms_success') ?: '',
            'error'      => $this->session->flashdata('forms_error') ?: '',
        );

        $this->render_view('forms_admin/bs_fields', $data);
    }

    public function field_create($form_id = 0, $page_id = 0) {
        $form = $this->load_form_or_redirect($form_id);
        if (!$form) {
            return;
        }
        $page = $this->load_page_for_form_or_redirect($form, $page_id);
        if (!$page) {
            return;
        }

        $data = array(
            'controller'   => $this->controller,
            'form'         => $form,
            'page'         => $page,
            'field_mode'   => 'create',
            'form_action'  => site_url('forms_admin/field_store/' . (int) $form['id'] . '/' . (int) $page['id']),
            'submit_label' => 'Ajouter le champ',
            'field'        => array(
                'name'         => '',
                'label'        => '',
                'field_type'   => 'text',
                'is_required'  => 0,
                'sort_order'   => '',
                'options_text' => '',
            ),
            'error' => '',
        );

        $this->render_view('forms_admin/bs_field_form', $data);
    }

    public function field_store($form_id = 0, $page_id = 0) {
        $form = $this->load_form_or_redirect($form_id);
        if (!$form) {
            return;
        }
        $page = $this->load_page_for_form_or_redirect($form, $page_id);
        if (!$page) {
            return;
        }

        $this->form_validation->set_rules('label', 'Libellé', 'required|max_length[255]');
        $this->form_validation->set_rules('name', 'Nom technique', 'required|max_length[100]|alpha_dash');
        $this->form_validation->set_rules('field_type', 'Type', 'required');

        if ($this->form_validation->run() === FALSE) {
            $data = array(
                'controller'   => $this->controller,
                'form'         => $form,
                'page'         => $page,
                'field_mode'   => 'create',
                'form_action'  => site_url('forms_admin/field_store/' . (int) $form['id'] . '/' . (int) $page['id']),
                'submit_label' => 'Ajouter le champ',
                'field'        => array_merge($this->input->post(), array('options_text' => (string) $this->input->post('options_text'))),
                'error'        => validation_errors(),
            );
            $this->render_view('forms_admin/bs_field_form', $data);
            return;
        }

        $options_json = $this->options_text_to_json((string) $this->input->post('options_text'));

        $id = $this->form_fields_model->create_field(array(
            'form_id'          => (int) $form['id'],
            'page_id'          => (int) $page['id'],
            'label'            => trim($this->input->post('label')),
            'name'             => trim($this->input->post('name')),
            'field_type'       => trim($this->input->post('field_type')),
            'is_required'      => (int) (bool) $this->input->post('is_required'),
            'is_identifier'    => (int) (bool) $this->input->post('is_identifier'),
            'sort_order'       => (int) $this->input->post('sort_order') ?: null,
            'options_json'     => $options_json,
            'created_by'       => $this->dx_auth->get_username(),
        ));

        if (!$id) {
            $this->session->set_flashdata('forms_error', 'Impossible de créer le champ.');
        } else {
            $this->session->set_flashdata('forms_success', 'Champ ajouté.');
        }

        redirect('forms_admin/fields/' . (int) $form['id'] . '/' . (int) $page['id']);
    }

    public function field_edit($form_id = 0, $page_id = 0, $field_id = 0) {
        $form = $this->load_form_or_redirect($form_id);
        if (!$form) {
            return;
        }
        $page = $this->load_page_for_form_or_redirect($form, $page_id);
        if (!$page) {
            return;
        }

        $field = $this->form_fields_model->get_by_id((int) $field_id);
        if (!$field || (int) $field['page_id'] !== (int) $page['id']) {
            $this->session->set_flashdata('forms_error', 'Champ introuvable.');
            redirect('forms_admin/fields/' . (int) $form['id'] . '/' . (int) $page['id']);
            return;
        }

        $field['options_text'] = $this->options_json_to_text($field['options_json']);

        $data = array(
            'controller'   => $this->controller,
            'form'         => $form,
            'page'         => $page,
            'field_mode'   => 'edit',
            'form_action'  => site_url('forms_admin/field_update/' . (int) $form['id'] . '/' . (int) $page['id'] . '/' . (int) $field['id']),
            'submit_label' => 'Enregistrer',
            'field'        => $field,
            'error'        => '',
        );

        $this->render_view('forms_admin/bs_field_form', $data);
    }

    public function field_update($form_id = 0, $page_id = 0, $field_id = 0) {
        $form = $this->load_form_or_redirect($form_id);
        if (!$form) {
            return;
        }
        $page = $this->load_page_for_form_or_redirect($form, $page_id);
        if (!$page) {
            return;
        }

        $field = $this->form_fields_model->get_by_id((int) $field_id);
        if (!$field || (int) $field['page_id'] !== (int) $page['id']) {
            $this->session->set_flashdata('forms_error', 'Champ introuvable.');
            redirect('forms_admin/fields/' . (int) $form['id'] . '/' . (int) $page['id']);
            return;
        }

        $this->form_validation->set_rules('label', 'Libellé', 'required|max_length[255]');
        $this->form_validation->set_rules('name', 'Nom technique', 'required|max_length[100]|alpha_dash');
        $this->form_validation->set_rules('field_type', 'Type', 'required');

        if ($this->form_validation->run() === FALSE) {
            $posted = $this->input->post();
            $posted['options_text'] = (string) $this->input->post('options_text');
            $posted['id'] = $field['id'];
            $data = array(
                'controller'   => $this->controller,
                'form'         => $form,
                'page'         => $page,
                'field_mode'   => 'edit',
                'form_action'  => site_url('forms_admin/field_update/' . (int) $form['id'] . '/' . (int) $page['id'] . '/' . (int) $field['id']),
                'submit_label' => 'Enregistrer',
                'field'        => $posted,
                'error'        => validation_errors(),
            );
            $this->render_view('forms_admin/bs_field_form', $data);
            return;
        }

        $options_json = $this->options_text_to_json((string) $this->input->post('options_text'));

        $old_name    = $field['name'];
        $new_name    = trim($this->input->post('name'));
        $is_required = (int) (bool) $this->input->post('is_required');

        $this->form_fields_model->update_field((int) $field['id'], array(
            'label'            => trim($this->input->post('label')),
            'name'             => $new_name,
            'field_type'       => trim($this->input->post('field_type')),
            'is_required'      => $is_required,
            'is_identifier'    => (int) (bool) $this->input->post('is_identifier'),
            'sort_order'       => (int) $this->input->post('sort_order') ?: $field['sort_order'],
            'options_json'     => $options_json,
            'updated_by'       => $this->dx_auth->get_username(),
        ));

        // Propagate required/name changes back to the page HTML so that
        // sync_fields_from_html does not overwrite is_required on next page save.
        $raw_html = html_entity_decode((string) $page['content_html'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (trim($raw_html) !== '') {
            $dom = new DOMDocument('1.0', 'UTF-8');
            libxml_use_internal_errors(true);
            $dom->loadHTML('<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $raw_html . '</body></html>');
            libxml_clear_errors();
            $xpath = new DOMXPath($dom);
            $nodes = iterator_to_array($xpath->query(
                '//input[@name="' . $old_name . '"] | //select[@name="' . $old_name . '"] | //textarea[@name="' . $old_name . '"]'
            ));
            if (!empty($nodes)) {
                foreach ($nodes as $node) {
                    if ($new_name !== '' && $new_name !== $old_name) {
                        $node->setAttribute('name', $new_name);
                    }
                    if ($is_required) {
                        $node->setAttribute('required', 'required');
                    } else {
                        $node->removeAttribute('required');
                    }
                }
                $body = $dom->getElementsByTagName('body')->item(0);
                $updated = '';
                foreach ($body->childNodes as $child) {
                    $updated .= $dom->saveHTML($child);
                }
                $this->form_pages_model->update_page((int) $page['id'], array(
                    'content_html' => $updated,
                    'updated_by'   => $this->dx_auth->get_username(),
                ));
            }
        }

        $this->session->set_flashdata('forms_success', 'Champ mis à jour.');
        redirect('forms_admin/fields/' . (int) $form['id'] . '/' . (int) $page['id']);
    }

    public function field_delete($form_id = 0, $page_id = 0, $field_id = 0) {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            show_error('Méthode non autorisée.', 405);
            return;
        }
        $form = $this->load_form_or_redirect($form_id);
        if (!$form) {
            return;
        }
        $page = $this->load_page_for_form_or_redirect($form, $page_id);
        if (!$page) {
            return;
        }

        $field = $this->form_fields_model->get_by_id((int) $field_id);
        if (!$field || (int) $field['page_id'] !== (int) $page['id']) {
            $this->session->set_flashdata('forms_error', 'Champ introuvable.');
            redirect('forms_admin/fields/' . (int) $form['id'] . '/' . (int) $page['id']);
            return;
        }

        if ($this->form_fields_model->delete_field((int) $field['id'])) {
            $this->session->set_flashdata('forms_success', 'Champ supprimé.');
        } else {
            $this->session->set_flashdata('forms_error', 'Impossible de supprimer ce champ.');
        }

        redirect('forms_admin/fields/' . (int) $form['id'] . '/' . (int) $page['id']);
    }

    private function extract_html_fields($html) {
        if (trim($html) === '') {
            return array();
        }

        libxml_use_internal_errors(true);
        $dom = new DOMDocument('1.0', 'UTF-8');
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        $label_map = array();
        foreach ($xpath->query('//label[@for]') as $label_node) {
            $for = trim($label_node->getAttribute('for'));
            if ($for !== '') {
                $label_map[$for] = trim(preg_replace('/\s*\*\s*/', '', $label_node->textContent));
            }
        }

        $fields = array();
        $seen = array();
        $sort = 1;
        $skip_types = array('hidden', 'submit', 'reset', 'button', 'image');

        foreach ($xpath->query('//input[@name] | //select[@name] | //textarea[@name]') as $node) {
            $tag  = strtolower($node->tagName);
            $name = trim($node->getAttribute('name'));
            if ($name === '') {
                continue;
            }
            if ($tag === 'input' && in_array(strtolower($node->getAttribute('type') ?: 'text'), $skip_types, true)) {
                continue;
            }
            if (isset($seen[$name])) {
                continue;
            }
            $seen[$name] = true;

            if ($tag === 'textarea') {
                $field_type = 'textarea';
            } elseif ($tag === 'select') {
                $field_type = 'select';
            } else {
                $type_map = array('email' => 'email', 'date' => 'date', 'number' => 'number',
                                  'checkbox' => 'checkbox', 'radio' => 'radio', 'file' => 'file');
                $raw_type = strtolower($node->getAttribute('type') ?: 'text');
                $field_type = isset($type_map[$raw_type]) ? $type_map[$raw_type] : 'text';
            }

            $id    = trim($node->getAttribute('id'));
            $label = ($id !== '' && isset($label_map[$id])) ? $label_map[$id] : '';
            if ($label === '') {
                $label = $name;
            }

            $options = array();
            if ($tag === 'select') {
                foreach ($xpath->query('.//option', $node) as $opt) {
                    if ($opt->getAttribute('value') !== '') {
                        $options[] = trim($opt->textContent);
                    }
                }
            }

            $gvv_role = trim($node->getAttribute('data-gvv-role'));

            $fields[] = array(
                'name'        => $name,
                'label'       => $label,
                'field_type'  => $field_type,
                'is_required' => $node->hasAttribute('required') ? 1 : 0,
                'sort_order'  => $sort++,
                'options'     => $options,
                'gvv_role'    => $gvv_role !== '' ? $gvv_role : null,
            );
        }

        // Detect signature widgets declared as <div data-gvv-type="signature" data-gvv-name="...">
        foreach ($xpath->query('//*[@data-gvv-type and @data-gvv-name]') as $node) {
            if (strtolower($node->getAttribute('data-gvv-type')) !== 'signature') {
                continue;
            }
            $name = trim($node->getAttribute('data-gvv-name'));
            if ($name === '' || isset($seen[$name])) {
                continue;
            }
            $seen[$name] = true;

            $label = trim($node->textContent);
            if ($label === '') {
                $label = $name;
            }

            $fields[] = array(
                'name'        => $name,
                'label'       => $label,
                'field_type'  => 'signature',
                'is_required' => $node->hasAttribute('data-gvv-required') ? 1 : 0,
                'sort_order'  => $sort++,
                'options'     => array(),
                'gvv_role'    => null,
            );
        }

        return $fields;
    }

    private function validate_html_field_names($form_id, $exclude_page_id, array $names) {
        if (empty($names)) {
            return null;
        }

        $other = $this->db
            ->select('ff.name')
            ->from('form_fields ff')
            ->join('form_pages fp', 'fp.id = ff.page_id')
            ->where('fp.form_id', (int) $form_id)
            ->where('ff.page_id !=', (int) $exclude_page_id)
            ->get()
            ->result_array();

        $other_names = array_column($other, 'name');
        $conflicts   = array_intersect($names, $other_names);
        if (!empty($conflicts)) {
            return 'Noms de champs déjà utilisés dans une autre page : ' . implode(', ', $conflicts);
        }

        return null;
    }

    private function sync_fields_from_html($form_id, $page_id, $html, $by = null) {
        $html = html_entity_decode((string) $html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $new_fields = $this->extract_html_fields($html);
        $now        = date('Y-m-d H:i:s');

        // Index existing fields by name to preserve IDs (avoid CASCADE on submission values)
        $existing = $this->db
            ->where('page_id', (int) $page_id)
            ->get('form_fields')
            ->result_array();
        $existing_by_name = array();
        foreach ($existing as $row) {
            $existing_by_name[$row['name']] = $row;
        }

        $new_names = array_column($new_fields, 'name');

        // Delete fields no longer present in HTML
        foreach ($existing_by_name as $name => $row) {
            if (!in_array($name, $new_names, true)) {
                $this->db->where('id', (int) $row['id'])->delete('form_fields');
            }
        }

        // Update or insert
        foreach ($new_fields as $field) {
            $options_json = !empty($field['options']) ? json_encode($field['options']) : null;
            $gvv_role     = isset($field['gvv_role']) ? $field['gvv_role'] : null;

            if (isset($existing_by_name[$field['name']])) {
                // Update in place — ID preserved, no cascade on submission values
                $this->db
                    ->where('id', (int) $existing_by_name[$field['name']]['id'])
                    ->update('form_fields', array(
                        'label'        => $field['label'],
                        'field_type'   => $field['field_type'],
                        'is_required'  => $field['is_required'],
                        'sort_order'   => $field['sort_order'],
                        'options_json' => $options_json,
                        'gvv_role'     => $gvv_role,
                        'updated_at'   => $now,
                        'updated_by'   => $by,
                    ));
            } else {
                $this->db->insert('form_fields', array(
                    'form_id'      => (int) $form_id,
                    'page_id'      => (int) $page_id,
                    'name'         => $field['name'],
                    'label'        => $field['label'],
                    'field_type'   => $field['field_type'],
                    'is_required'  => $field['is_required'],
                    'sort_order'   => $field['sort_order'],
                    'options_json' => $options_json,
                    'gvv_role'     => $gvv_role,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                    'created_by'   => $by,
                    'updated_by'   => $by,
                ));
            }
        }
    }

    private function options_text_to_json($text) {
        $text = trim((string) $text);
        if ($text === '') {
            return null;
        }
        $lines = array_values(array_filter(array_map('trim', explode("\n", $text)), function ($l) { return $l !== ''; }));
        return empty($lines) ? null : json_encode($lines);
    }

    private function options_json_to_text($json) {
        if (empty($json)) {
            return '';
        }
        $arr = json_decode($json, true);
        if (!is_array($arr)) {
            return '';
        }
        return implode("\n", $arr);
    }

    private function load_form_or_redirect($form_id) {
        $form = $this->forms_model->get_by_id((int) $form_id);
        if (!$form) {
            $this->session->set_flashdata('forms_error', 'Formulaire introuvable.');
            redirect('forms_admin');
            return false;
        }

        $section_id = (int) $this->session->userdata('section');
        if ($section_id > 0) {
            $form_club = $form['club'];
            // Allow access only to forms of the active section or global forms (club IS NULL)
            if ($form_club !== null && (int) $form_club !== $section_id) {
                $this->session->set_flashdata('forms_error', 'Accès refusé à ce formulaire.');
                redirect('forms_admin');
                return false;
            }
        }

        return $form;
    }

    private function load_page_for_form_or_redirect($form, $page_id) {
        $page = $this->form_pages_model->get_by_id((int) $page_id);
        if (!$page || (int) $page['form_id'] !== (int) $form['id']) {
            $this->session->set_flashdata('forms_error', 'Page introuvable pour ce formulaire.');
            redirect('forms_admin/pages/' . (int) $form['id']);
            return false;
        }
        return $page;
    }

    public function form_import_html() {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            redirect('forms_admin');
            return;
        }

        if (empty($_FILES['html_file']['tmp_name']) || (int) $_FILES['html_file']['error'] !== UPLOAD_ERR_OK) {
            $this->session->set_flashdata('forms_error', 'Aucun fichier HTML valide reçu.');
            redirect('forms_admin');
            return;
        }

        $html_raw = file_get_contents($_FILES['html_file']['tmp_name']);
        if ($html_raw === false || trim($html_raw) === '') {
            $this->session->set_flashdata('forms_error', 'Fichier HTML vide ou illisible.');
            redirect('forms_admin');
            return;
        }

        // Parse full HTML document
        libxml_use_internal_errors(true);
        $dom = new DOMDocument('1.0', 'UTF-8');
        @$dom->loadHTML($html_raw);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        // Extract <title>
        $title_nodes = $dom->getElementsByTagName('title');
        $html_title  = $title_nodes->length > 0 ? trim($title_nodes->item(0)->textContent) : '';
        if ($html_title === '') {
            $html_title = 'Formulaire importé';
        }

        // Collect CSS from every <style> tag (head and body)
        $css_parts = array();
        foreach ($xpath->query('//style') as $style_node) {
            $css = trim($style_node->textContent);
            if ($css !== '') {
                $css_parts[] = $css;
            }
        }
        $global_css = implode("\n\n", $css_parts);

        // Rewrite body { } so styles target the GVV form container
        $global_css = preg_replace('/\bbody\s*\{/', '.forms-public-root {', $global_css);
        $global_css = preg_replace('/\bbody\s*,/', '.forms-public-root,', $global_css);

        // Detect CSS scope: first standalone class selector at start of a CSS line
        $css_scope = '';
        if (preg_match('/^\.([\w-]+)\s*\{/m', $global_css, $m)) {
            if ($m[1] !== 'forms-public-root') {
                $css_scope = $m[1];
            }
        }

        // Remove <style> and <script> before extracting body content
        $to_remove = array();
        foreach ($xpath->query('//style | //script') as $node) {
            $to_remove[] = $node;
        }
        foreach ($to_remove as $node) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }
        }

        // Extract body content (inner HTML)
        $body = $dom->getElementsByTagName('body')->item(0);
        $content_html = '';
        if ($body) {
            foreach ($body->childNodes as $child) {
                $content_html .= $dom->saveHTML($child);
            }
        }
        $content_html = trim($content_html);

        // Build a unique code from POST or from the HTML title
        $code_input = trim((string) $this->input->post('import_code'));
        if ($code_input !== '') {
            $base_code = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $code_input);
        } else {
            $base_code = preg_replace('/[^a-z0-9_-]+/', '_', strtolower($html_title));
            $base_code = trim($base_code, '_');
            if ($base_code === '') {
                $base_code = 'form_' . date('Ymd');
            }
        }
        $base_code = substr($base_code, 0, 47);

        $code = $base_code;
        $i    = 1;
        while ($this->db->where('code', $code)->count_all_results('forms') > 0) {
            $code = $base_code . '_' . $i++;
        }

        $section_id = (int) $this->session->userdata('section');
        $club       = $section_id > 0 ? $section_id : null;
        $by         = $this->dx_auth->get_username();

        $form_id = $this->forms_model->create_form(array(
            'club'        => $club,
            'code'        => $code,
            'title'       => $html_title,
            'description' => '',
            'css_scope'   => $css_scope,
            'global_css'  => $global_css,
            'created_by'  => $by,
        ));

        if (!$form_id) {
            $this->session->set_flashdata('forms_error', 'Impossible de créer le formulaire.');
            redirect('forms_admin');
            return;
        }

        $page_id = $this->form_pages_model->create_page(array(
            'form_id'      => (int) $form_id,
            'page_number'  => 1,
            'title'        => '',
            'content_html' => $content_html,
            'created_by'   => $by,
        ));

        if ($page_id) {
            $this->sync_fields_from_html((int) $form_id, (int) $page_id, $content_html, $by);
        }

        $this->session->set_flashdata('forms_success', 'Formulaire « ' . html_escape($html_title) . ' » créé depuis le fichier HTML.');
        redirect('forms_admin/edit/' . (int) $form_id);
    }

    public function form_backup($form_id = 0) {
        $form = $this->load_form_or_redirect($form_id);
        if (!$form) {
            return;
        }

        $pages = $this->form_pages_model->get_form_pages((int) $form['id']);

        $meta = array(
            'version'         => '1',
            'code'            => (string) $form['code'],
            'title'           => (string) $form['title'],
            'description'     => (string) $form['description'],
            'css_scope'       => (string) $form['css_scope'],
            'public_slug'     => (string) $form['public_slug'],
            'required_params' => (string) $form['required_params'],
            'pages'           => array(),
        );

        // Build content in a temp directory then zip with the system zip command (same as DB backup)
        $tmp_dir = sys_get_temp_dir() . '/gvv_form_' . uniqid();
        mkdir($tmp_dir . '/pages', 0700, true);

        foreach ($pages as $page) {
            $num = (int) $page['page_number'];
            $meta['pages'][] = array(
                'page_number' => $num,
                'title'       => (string) $page['title'],
            );
            file_put_contents(
                sprintf('%s/pages/%02d.html', $tmp_dir, $num),
                (string) $page['content_html']
            );
        }

        file_put_contents($tmp_dir . '/meta.json', json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        file_put_contents($tmp_dir . '/styles.css', (string) $form['global_css']);

        $safe_code = preg_replace('/[^a-zA-Z0-9_-]+/', '-', (string) $form['code']);
        $zip_path  = sys_get_temp_dir() . '/' . $safe_code . '.zip';

        $original_dir = getcwd();
        chdir($tmp_dir);
        exec('zip -r ' . escapeshellarg($zip_path) . ' .', $output, $return_code);
        chdir($original_dir);

        // Clean up temp directory
        foreach (glob($tmp_dir . '/pages/*.html') as $f) { unlink($f); }
        rmdir($tmp_dir . '/pages');
        foreach (array('meta.json', 'styles.css') as $f) {
            if (file_exists($tmp_dir . '/' . $f)) { unlink($tmp_dir . '/' . $f); }
        }
        rmdir($tmp_dir);

        if ($return_code !== 0 || !file_exists($zip_path)) {
            $this->session->set_flashdata('forms_error', 'Erreur lors de la création du fichier ZIP.');
            redirect('forms_admin/edit/' . (int) $form['id']);
            return;
        }

        $zip_data = file_get_contents($zip_path);
        unlink($zip_path);

        $this->load->helper('download');
        force_download($safe_code . '.zip', $zip_data);
    }

    public function form_import_zip() {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            redirect('forms_admin');
            return;
        }

        if (empty($_FILES['import_zip']['tmp_name']) || (int) $_FILES['import_zip']['error'] !== UPLOAD_ERR_OK) {
            $this->session->set_flashdata('forms_error', 'Aucun fichier ZIP valide reçu.');
            redirect('forms_admin');
            return;
        }

        $tmp_dir = $this->_unzip_to_tmpdir($_FILES['import_zip']['tmp_name']);
        if ($tmp_dir === false) {
            $this->session->set_flashdata('forms_error', 'Impossible d\'extraire le fichier ZIP (commande unzip indisponible ou archive corrompue).');
            redirect('forms_admin');
            return;
        }

        $meta_path = $tmp_dir . '/meta.json';
        if (!file_exists($meta_path)) {
            $this->_cleanup_tmpdir($tmp_dir);
            $this->session->set_flashdata('forms_error', 'meta.json absent de l\'archive — ce fichier n\'est pas une sauvegarde de formulaire GVV.');
            redirect('forms_admin');
            return;
        }

        $meta = json_decode(file_get_contents($meta_path), true);
        if (!is_array($meta) || !isset($meta['pages']) || !is_array($meta['pages'])) {
            $this->_cleanup_tmpdir($tmp_dir);
            $this->session->set_flashdata('forms_error', 'meta.json invalide ou mal formé.');
            redirect('forms_admin');
            return;
        }

        $css_path = $tmp_dir . '/styles.css';
        $css = file_exists($css_path) ? file_get_contents($css_path) : '';

        // Build a unique code: use the one from the backup, suffix _2/_3/... on conflict
        $base_code = preg_replace('/[^a-zA-Z0-9_-]+/', '_', isset($meta['code']) ? (string) $meta['code'] : 'form');
        $base_code = trim($base_code, '_');
        if ($base_code === '') {
            $base_code = 'form_' . date('Ymd');
        }
        $base_code = substr($base_code, 0, 47);

        $code = $base_code;
        $i    = 2;
        while ($this->db->where('code', $code)->count_all_results('forms') > 0) {
            $code = $base_code . '_' . $i++;
        }

        $section_id = (int) $this->session->userdata('section');
        $club       = $section_id > 0 ? $section_id : null;
        $by         = $this->dx_auth->get_username();

        $form_id = $this->forms_model->create_form(array(
            'club'            => $club,
            'code'            => $code,
            'title'           => isset($meta['title'])           ? (string) $meta['title']           : $code,
            'description'     => isset($meta['description'])     ? (string) $meta['description']     : '',
            'css_scope'       => isset($meta['css_scope'])       ? (string) $meta['css_scope']       : '',
            'required_params' => isset($meta['required_params']) ? (string) $meta['required_params'] : 'none',
            'global_css'      => $css,
            'created_by'      => $by,
        ));

        if (!$form_id) {
            $this->_cleanup_tmpdir($tmp_dir);
            $this->session->set_flashdata('forms_error', 'Impossible de créer le formulaire depuis la sauvegarde.');
            redirect('forms_admin');
            return;
        }

        $page_count = 0;
        foreach ($meta['pages'] as $pm) {
            $num      = (int) $pm['page_number'];
            $page_file = $tmp_dir . sprintf('/pages/%02d.html', $num);
            $content_html = file_exists($page_file) ? file_get_contents($page_file) : '';

            $page_id = $this->form_pages_model->create_page(array(
                'form_id'      => (int) $form_id,
                'page_number'  => $num,
                'title'        => isset($pm['title']) ? (string) $pm['title'] : '',
                'content_html' => $content_html,
                'created_by'   => $by,
            ));

            if ($page_id) {
                $this->sync_fields_from_html((int) $form_id, (int) $page_id, $content_html, $by);
                $page_count++;
            }
        }

        $this->_cleanup_tmpdir($tmp_dir);

        $title = isset($meta['title']) ? (string) $meta['title'] : $code;
        $this->session->set_flashdata('forms_success', 'Formulaire « ' . html_escape($title) . ' » importé depuis la sauvegarde (' . $page_count . ' page(s)).');
        redirect('forms_admin/edit/' . (int) $form_id);
    }

    public function form_restore($form_id = 0) {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            redirect('forms_admin/edit/' . (int) $form_id);
            return;
        }

        $form = $this->load_form_or_redirect($form_id);
        if (!$form) {
            return;
        }

        if (empty($_FILES['restore_zip']['tmp_name']) || (int) $_FILES['restore_zip']['error'] !== UPLOAD_ERR_OK) {
            $this->session->set_flashdata('forms_error', 'Aucun fichier valide reçu.');
            redirect('forms_admin/edit/' . (int) $form['id']);
            return;
        }

        $tmp_dir = $this->_unzip_to_tmpdir($_FILES['restore_zip']['tmp_name']);
        if ($tmp_dir === false) {
            $this->session->set_flashdata('forms_error', 'Impossible d\'extraire le fichier ZIP (commande unzip indisponible ou archive corrompue).');
            redirect('forms_admin/edit/' . (int) $form['id']);
            return;
        }

        $meta_path = $tmp_dir . '/meta.json';
        if (!file_exists($meta_path)) {
            $this->_cleanup_tmpdir($tmp_dir);
            $this->session->set_flashdata('forms_error', 'meta.json absent de l\'archive.');
            redirect('forms_admin/edit/' . (int) $form['id']);
            return;
        }

        $meta = json_decode(file_get_contents($meta_path), true);
        if (!is_array($meta) || !isset($meta['pages']) || !is_array($meta['pages'])) {
            $this->_cleanup_tmpdir($tmp_dir);
            $this->session->set_flashdata('forms_error', 'meta.json invalide ou mal formé.');
            redirect('forms_admin/edit/' . (int) $form['id']);
            return;
        }

        $css_path = $tmp_dir . '/styles.css';
        $css = file_exists($css_path) ? file_get_contents($css_path) : '';

        // Update metadata — code, status and public_slug are not overwritten
        $this->forms_model->update_form((int) $form['id'], array(
            'title'       => isset($meta['title'])       ? (string) $meta['title']       : $form['title'],
            'description' => isset($meta['description']) ? (string) $meta['description'] : '',
            'css_scope'   => isset($meta['css_scope'])   ? (string) $meta['css_scope']   : '',
            'global_css'  => $css,
            'updated_by'  => $this->dx_auth->get_username(),
        ));

        // Remove all existing pages (cascade removes their fields)
        foreach ($this->form_pages_model->get_form_pages((int) $form['id']) as $ep) {
            $this->form_pages_model->delete_page((int) $ep['id']);
        }

        // Recreate pages from the extracted ZIP
        $by         = $this->dx_auth->get_username();
        $page_count = 0;
        foreach ($meta['pages'] as $pm) {
            $num       = (int) $pm['page_number'];
            $page_file = $tmp_dir . sprintf('/pages/%02d.html', $num);
            $content_html = file_exists($page_file) ? file_get_contents($page_file) : '';

            $page_id = $this->form_pages_model->create_page(array(
                'form_id'      => (int) $form['id'],
                'page_number'  => $num,
                'title'        => isset($pm['title']) ? (string) $pm['title'] : '',
                'content_html' => $content_html,
                'created_by'   => $by,
            ));

            if ($page_id) {
                $this->sync_fields_from_html((int) $form['id'], (int) $page_id, $content_html, $by);
                $page_count++;
            }
        }

        $this->_cleanup_tmpdir($tmp_dir);

        $this->session->set_flashdata('forms_success', 'Formulaire restauré : ' . $page_count . ' page(s) importée(s).');
        redirect('forms_admin/edit/' . (int) $form['id']);
    }

    // -------------------------------------------------------------------------
    // Config params
    // -------------------------------------------------------------------------

    public function config() {
        $this->load->vars([
            'nav_back_url'   => 'welcome/section/admin_sys',
            'nav_back_label' => 'Administration système',
        ]);
        $this->load->model('form_config_params_model');
        $section_id = (int) $this->session->userdata('section');
        $params = $this->form_config_params_model->list_params(
            $section_id > 0 ? $section_id : null,
            $section_id > 0
        );

        $this->db->select('id, nom as name');
        $sections = $this->db->get('sections')->result_array();
        $sections_by_id = array();
        foreach ($sections as $s) {
            $sections_by_id[$s['id']] = $s['name'];
        }
        foreach ($params as &$p) {
            $p['section_name'] = $p['club_id'] ? (isset($sections_by_id[$p['club_id']]) ? $sections_by_id[$p['club_id']] : $p['club_id']) : null;
        }
        unset($p);

        $data = array(
            'controller' => $this->controller,
            'params'     => $params,
            'success'    => $this->session->flashdata('forms_success') ?: '',
            'error'      => $this->session->flashdata('forms_error') ?: '',
        );
        $this->render_view('forms_admin/bs_config', $data);
    }

    public function config_create() {
        $this->load->vars([
            'nav_back_url'   => 'welcome/section/admin_sys',
            'nav_back_label' => 'Administration système',
        ]);
        $section_id = (int) $this->session->userdata('section');
        $section_name = '';
        if ($section_id > 0) {
            $s = $this->db->select('nom')->where('id', $section_id)->get('sections')->row_array();
            $section_name = $s ? $s['nom'] : $section_id;
        }

        $data = array(
            'controller'  => $this->controller,
            'form_mode'   => 'create',
            'form_action' => site_url('forms_admin/config_store'),
            'section_id'  => $section_id,
            'section_name'=> $section_name,
            'param'       => array('param_key' => '', 'param_label' => '', 'param_value' => '', 'param_description' => '', 'club_id' => null),
            'error'       => '',
        );
        $this->render_view('forms_admin/bs_config_form', $data);
    }

    public function config_store() {
        $this->load->model('form_config_params_model');
        $section_id = (int) $this->session->userdata('section');

        $key   = trim($this->input->post('param_key'));
        $label = trim($this->input->post('param_label'));

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $key)) {
            $this->_config_form_error('create', $this->lang->line('forms_config_error_invalid_key'));
            return;
        }
        if ($key === '') {
            $this->_config_form_error('create', $this->lang->line('forms_config_error_key_required'));
            return;
        }
        if ($label === '') {
            $this->_config_form_error('create', $this->lang->line('forms_config_error_label_required'));
            return;
        }

        $is_global = (bool) $this->input->post('is_global');
        $club_id   = ($section_id > 0 && !$is_global) ? $section_id : null;

        if ($this->form_config_params_model->key_exists($key, $club_id)) {
            $this->_config_form_error('create', $this->lang->line('forms_config_error_key_exists'));
            return;
        }

        $by = $this->dx_auth->get_username();
        $this->form_config_params_model->create(array(
            'club_id'           => $club_id,
            'param_key'         => $key,
            'param_label'       => $label,
            'param_value'       => $this->input->post('param_value') ?: '',
            'param_description' => $this->input->post('param_description') ?: null,
        ), $by);

        $this->session->set_flashdata('forms_success', $this->lang->line('forms_config_created'));
        redirect('forms_admin/config');
    }

    public function config_edit($id = 0) {
        $this->load->vars([
            'nav_back_url'   => 'welcome/section/admin_sys',
            'nav_back_label' => 'Administration système',
        ]);
        $this->load->model('form_config_params_model');
        $param = $this->form_config_params_model->get_by_id((int) $id);
        if (!$param) {
            redirect('forms_admin/config');
            return;
        }

        $section_id = (int) $this->session->userdata('section');
        $section_name = '';
        if ($section_id > 0) {
            $s = $this->db->select('nom')->where('id', $section_id)->get('sections')->row_array();
            $section_name = $s ? $s['nom'] : $section_id;
        }

        $data = array(
            'controller'  => $this->controller,
            'form_mode'   => 'edit',
            'form_action' => site_url('forms_admin/config_update/' . (int) $id),
            'section_id'  => $section_id,
            'section_name'=> $section_name,
            'param'       => $param,
            'error'       => '',
        );
        $this->render_view('forms_admin/bs_config_form', $data);
    }

    public function config_update($id = 0) {
        $this->load->model('form_config_params_model');
        $param = $this->form_config_params_model->get_by_id((int) $id);
        if (!$param) {
            redirect('forms_admin/config');
            return;
        }

        $label = trim($this->input->post('param_label'));
        if ($label === '') {
            $this->_config_form_error('edit', $this->lang->line('forms_config_error_label_required'), $id);
            return;
        }

        $section_id = (int) $this->session->userdata('section');
        $is_global  = (bool) $this->input->post('is_global');
        $club_id    = ($section_id > 0 && !$is_global) ? $section_id : null;

        $by = $this->dx_auth->get_username();
        $this->form_config_params_model->update((int) $id, array(
            'club_id'           => $club_id,
            'param_label'       => $label,
            'param_value'       => $this->input->post('param_value') ?: '',
            'param_description' => $this->input->post('param_description') ?: null,
        ), $by);

        $this->session->set_flashdata('forms_success', $this->lang->line('forms_config_updated'));
        redirect('forms_admin/config');
    }

    public function config_delete($id = 0) {
        $this->load->model('form_config_params_model');
        $this->form_config_params_model->delete((int) $id);
        $this->session->set_flashdata('forms_success', $this->lang->line('forms_config_deleted'));
        redirect('forms_admin/config');
    }

    private function _config_form_error($mode, $error_msg, $id = 0) {
        $this->load->vars([
            'nav_back_url'   => 'welcome/section/admin_sys',
            'nav_back_label' => 'Administration système',
        ]);
        $this->load->model('form_config_params_model');
        $section_id = (int) $this->session->userdata('section');
        $section_name = '';
        if ($section_id > 0) {
            $s = $this->db->select('nom')->where('id', $section_id)->get('sections')->row_array();
            $section_name = $s ? $s['nom'] : $section_id;
        }

        $param = $id ? $this->form_config_params_model->get_by_id($id) : array(
            'param_key' => $this->input->post('param_key') ?: '',
            'param_label' => $this->input->post('param_label') ?: '',
            'param_value' => $this->input->post('param_value') ?: '',
            'param_description' => $this->input->post('param_description') ?: '',
            'club_id' => null,
        );

        $data = array(
            'controller'  => $this->controller,
            'form_mode'   => $mode,
            'form_action' => $mode === 'edit' ? site_url('forms_admin/config_update/' . $id) : site_url('forms_admin/config_store'),
            'section_id'  => $section_id,
            'section_name'=> $section_name,
            'param'       => $param,
            'error'       => $error_msg,
        );
        $this->render_view('forms_admin/bs_config_form', $data);
    }

    public function generate($slug = '') {
        $slug = trim((string) $slug);
        $form = $this->forms_model->get_by_public_slug($slug);
        if (!$form || $form['status'] !== 'published') {
            $this->session->set_flashdata('forms_error', $this->lang->line('forms_generate_error_not_found'));
            redirect('forms_admin');
            return;
        }

        $required_params = isset($form['required_params']) ? $form['required_params'] : 'none';
        $this->load->model('membres_model');

        $section_id = (int) $this->session->userdata('section');
        $pilot_selector      = $this->membres_model->get_selector($section_id);
        $instructor_selector = $this->membres_model->inst_selector($section_id);

        $data = array(
            'controller'          => $this->controller,
            'form'                => $form,
            'required_params'     => $required_params,
            'pilot_selector'      => $pilot_selector,
            'instructor_selector' => $instructor_selector,
            'error'               => $this->session->flashdata('forms_generate_error') ?: '',
        );

        $this->render_view('forms_admin/bs_generate', $data);
    }

    public function generate_submit($slug = '') {
        $slug = trim((string) $slug);
        $form = $this->forms_model->get_by_public_slug($slug);
        if (!$form || $form['status'] !== 'published') {
            redirect('forms_admin');
            return;
        }

        $required_params  = isset($form['required_params']) ? $form['required_params'] : 'none';
        $pilot_login      = trim((string) $this->input->post('pilot_login'));
        $instructor_login = trim((string) $this->input->post('instructor_login'));

        $errors = array();
        if (in_array($required_params, array('pilot', 'pilot+instructor'), true) && $pilot_login === '') {
            $errors[] = $this->lang->line('forms_generate_error_pilot');
        }
        if (in_array($required_params, array('instructor', 'pilot+instructor'), true) && $instructor_login === '') {
            $errors[] = $this->lang->line('forms_generate_error_instructor');
        }

        if (!empty($errors)) {
            $this->session->set_flashdata('forms_generate_error', implode('<br>', $errors));
            redirect('forms_admin/generate/' . rawurlencode($slug));
            return;
        }

        $params = array();
        if ($pilot_login !== '')      $params[] = 'pilot_login='      . rawurlencode($pilot_login);
        if ($instructor_login !== '') $params[] = 'instructor_login=' . rawurlencode($instructor_login);

        $url = site_url('forms/' . rawurlencode($slug));
        if (!empty($params)) {
            $url .= '?' . implode('&', $params);
        }

        redirect($url);
    }

    private function _unzip_to_tmpdir($uploaded_tmp_path) {
        $tmp_dir = sys_get_temp_dir() . '/gvv_zip_' . uniqid();
        mkdir($tmp_dir, 0700, true);

        exec('unzip -o ' . escapeshellarg($uploaded_tmp_path) . ' -d ' . escapeshellarg($tmp_dir), $output, $return_code);

        if ($return_code !== 0) {
            $this->_cleanup_tmpdir($tmp_dir);
            return false;
        }

        return $tmp_dir;
    }

    private function _cleanup_tmpdir($dir) {
        if (!is_dir($dir)) {
            return;
        }
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
        }
        rmdir($dir);
    }

    private function render_view($view, $data = array()) {
        load_bs_view('header', null, false);
        load_bs_view('menu', null, false);
        load_bs_view('banner', null, false);
        return load_last_view($view, $data);
    }
}