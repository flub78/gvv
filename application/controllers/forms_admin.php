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

        $this->load->helper('views');
        $this->load->model('forms_model');
        $this->load->model('form_pages_model');
        $this->load->model('form_submissions_model');
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
        $filters = array(
            'section_context' => $section_id,
        );

        $data = array(
            'controller' => $this->controller,
            'forms'      => $this->forms_model->list_forms($filters),
            'section_id' => $section_id,
            'success'    => $this->session->flashdata('forms_success') ?: '',
            'error'      => $this->session->flashdata('forms_error') ?: '',
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
                'is_global'   => ($section_id <= 0) ? 1 : 0,
            ),
            'section_id' => $section_id,
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
            'controller' => $this->controller,
            'form_mode'  => 'edit',
            'form_action'=> site_url('forms_admin/update/' . $id),
            'submit_label' => 'Enregistrer',
            'form'       => $row,
            'section_id' => $section_id,
            'error'      => '',
        );

        $this->render_view('forms_admin/bs_form', $data);
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
                'error'      => validation_errors(),
            );
            $this->render_view('forms_admin/bs_form', $data);
            return;
        }

        $is_global = (int) $this->input->post('is_global');
        $club = ($section_id > 0 && !$is_global) ? $section_id : null;

        $id = $this->forms_model->create_form(array(
            'club'        => $club,
            'code'        => trim($this->input->post('code')),
            'title'       => trim($this->input->post('title')),
            'description' => trim($this->input->post('description')),
            'public_slug' => trim($this->input->post('public_slug')),
            'css_scope'   => trim($this->input->post('css_scope')),
            'global_css'  => (string) $this->input->post('global_css'),
            'created_by'  => $this->dx_auth->get_username(),
        ));

        if (!$id) {
            $data = array(
                'controller' => $this->controller,
                'form'       => $this->input->post(),
                'section_id' => $section_id,
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

        $this->form_validation->set_rules('title', 'Titre', 'required|max_length[255]');
        $this->form_validation->set_rules('public_slug', 'Lien public', 'max_length[100]');
        $this->form_validation->set_rules('css_scope', 'CSS scope', 'max_length[100]');
        $this->form_validation->set_rules('global_css', 'CSS global', 'max_length[65535]');

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
                'error'      => validation_errors(),
            );
            $this->render_view('forms_admin/bs_form', $data);
            return;
        }

        $is_global = (int) $this->input->post('is_global');
        $club = ($section_id > 0 && !$is_global) ? $section_id : null;

        $ok = $this->forms_model->update_form($id, array(
            'club'        => $club,
            'title'       => trim($this->input->post('title')),
            'description' => trim($this->input->post('description')),
            'public_slug' => trim($this->input->post('public_slug')),
            'css_scope'   => trim($this->input->post('css_scope')),
            'global_css'  => (string) $this->input->post('global_css'),
            'updated_by'  => $this->dx_auth->get_username(),
        ));

        if (!$ok) {
            $this->session->set_flashdata('forms_error', 'Impossible de modifier ce formulaire.');
            redirect('forms_admin/edit/' . $id);
            return;
        }

        $this->session->set_flashdata('forms_success', 'Formulaire mis a jour.');
        redirect('forms_admin');
    }

    public function delete($id = 0) {
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
        $first_page = !empty($pages) ? $pages[0] : array('title' => '', 'content_html' => '');

        $data = array(
            'controller' => $this->controller,
            'form'       => $form,
            'page'       => $first_page,
            'pages_count'=> count($pages),
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

        $ok = $this->form_pages_model->create_page(array(
            'form_id'      => (int) $form['id'],
            'page_number'  => (int) $this->input->post('page_number'),
            'title'        => trim((string) $this->input->post('title')),
            'content_html' => (string) $this->input->post('content_html'),
            'created_by'   => $this->dx_auth->get_username(),
        ));

        if (!$ok) {
            $this->session->set_flashdata('forms_error', 'Impossible d\'ajouter la page.');
            redirect('forms_admin/pages/' . (int) $form['id']);
            return;
        }

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

        $ok = $this->form_pages_model->update_page((int) $page['id'], array(
            'page_number'  => (int) $this->input->post('page_number'),
            'title'        => trim((string) $this->input->post('title')),
            'content_html' => (string) $this->input->post('content_html'),
            'updated_by'   => $this->dx_auth->get_username(),
        ));

        if (!$ok) {
            $this->session->set_flashdata('forms_error', 'Impossible de modifier la page.');
            redirect('forms_admin/pages/' . (int) $form['id']);
            return;
        }

        $this->session->set_flashdata('forms_success', 'Page mise a jour.');
        redirect('forms_admin/pages/' . (int) $form['id']);
    }

    public function page_delete($form_id = 0, $page_id = 0) {
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
        $this->form_validation->set_rules('import_format', 'Format', 'required');

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('forms_error', validation_errors());
            redirect('forms_admin/pages/' . (int) $form['id']);
            return;
        }

        $format = (string) $this->input->post('import_format');
        $raw_content = (string) $this->input->post('import_content');
        $content_html = $format === 'text'
            ? nl2br(html_escape($raw_content))
            : $raw_content;

        $ok = $this->form_pages_model->create_page(array(
            'form_id'      => (int) $form['id'],
            'page_number'  => $this->form_pages_model->next_page_number((int) $form['id']),
            'title'        => trim((string) $this->input->post('import_title')),
            'content_html' => $content_html,
            'created_by'   => $this->dx_auth->get_username(),
        ));

        if (!$ok) {
            $this->session->set_flashdata('forms_error', 'Impossible d\'importer la page.');
            redirect('forms_admin/pages/' . (int) $form['id']);
            return;
        }

        $this->session->set_flashdata('forms_success', 'Page importee.');
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

        $data = array(
            'controller'  => $this->controller,
            'form'        => $form,
            'submissions' => $this->form_submissions_model->get_form_submissions((int) $form['id'], 200, 0),
            'success'     => $this->session->flashdata('forms_success') ?: '',
            'error'       => $this->session->flashdata('forms_error') ?: '',
        );

        $this->render_view('forms_admin/bs_submissions', $data);
    }

    public function submission($form_id = 0, $submission_id = 0) {
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

        if ($inline_allowed) {
            header('Content-Disposition: inline; filename="' . $safe_name . '"');
        } else {
            header('Content-Disposition: attachment; filename="' . $safe_name . '"');
        }

        readfile($resolved);
    }

    private function load_form_or_redirect($form_id) {
        $form = $this->forms_model->get_by_id((int) $form_id);
        if (!$form) {
            $this->session->set_flashdata('forms_error', 'Formulaire introuvable.');
            redirect('forms_admin');
            return false;
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

    private function render_view($view, $data = array()) {
        load_bs_view('header', null, false);
        load_bs_view('menu', null, false);
        load_bs_view('banner', null, false);
        return load_last_view($view, $data);
    }
}