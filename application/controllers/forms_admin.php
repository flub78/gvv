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
        $this->load->model('form_fields_model');
        $this->load->model('form_submissions_model');
        $this->load->library('form_validation');
        $this->load->library('forms_renderer');
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
            'nav_back_url'   => 'forms_admin',
            'nav_back_label' => 'Liste des formulaires',
        ]);
    }

    public function index() {
        $section_id = (int) $this->session->userdata('section');
        $forms      = $this->forms_model->list_forms(array('section_context' => $section_id));
        $form_ids   = array_column($forms, 'id');
        $counts     = $this->form_submissions_model->count_by_form($form_ids);

        $data = array(
            'controller'       => $this->controller,
            'forms'            => $forms,
            'submission_counts'=> $counts,
            'section_id'       => $section_id,
            'success'          => $this->session->flashdata('forms_success') ?: '',
            'error'            => $this->session->flashdata('forms_error') ?: '',
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

        $ok = $this->forms_model->update_form($id, array(
            'club'        => $club,
            'title'       => trim($this->input->post('title')),
            'description' => trim($this->input->post('description')),
            'public_slug' => trim($this->input->post('public_slug')),
            'css_scope'   => trim($this->input->post('css_scope')),
            'global_css'  => (string) $this->input->post('global_css'),
            'status'      => $status,
            'updated_by'  => $this->dx_auth->get_username(),
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

    public function submission_view($form_id = 0, $submission_id = 0) {
        $form = $this->load_form_or_redirect($form_id);
        if (!$form) {
            return;
        }

        $submission = $this->form_submissions_model->get_by_id((int) $submission_id);
        if (!$submission || (int) $submission['form_id'] !== (int) $form['id']) {
            show_404();
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
        foreach ($files_raw as $f) {
            $files_by_name[(string) $f['field_name']] = (string) $f['original_name'];
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

            $body_parts[] = $this->_fill_html_values_readonly($raw, $values_by_name, $files_by_name);
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

        $values_raw = $this->form_submissions_model->get_submission_values((int) $submission['id']);
        $files_raw  = $this->form_submissions_model->get_submission_files((int) $submission['id']);
        $pages      = $this->form_pages_model->get_form_pages((int) $form['id']);

        $values_by_name = array();
        foreach ($values_raw as $v) {
            $values_by_name[(string) $v['field_name']] = (string) $v['value_text'];
        }
        $files_by_name = array();
        foreach ($files_raw as $f) {
            $files_by_name[(string) $f['field_name']] = (string) $f['original_name'];
        }

        $css = $this->_make_css_tcpdf_compatible(
            !empty($form['global_css']) ? (string) $form['global_css'] : ''
        );

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

            $body_parts[] = $this->_fill_html_values($raw, $values_by_name, $files_by_name);
        }

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>' . $css . '</style></head><body>'
              . implode('<p style="page-break-after:always;"></p>', $body_parts)
              . '</body></html>';

        include_once(APPPATH . '/third_party/tcpdf/tcpdf.php');

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator($this->config->item('nom_club') ?: 'GVV');
        $pdf->SetAuthor($this->config->item('nom_club') ?: 'GVV');
        $pdf->SetTitle($form['title']);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');

        $safe_title = preg_replace('/[^a-z0-9_\-]/i', '_', $form['title']);
        $filename   = 'reponse_' . (int) $submission['id'] . '_' . $safe_title . '.pdf';
        $pdf->Output($filename, 'I');
        exit;
    }

    private function _fill_html_values_readonly($html, array $values_by_name, array $files_by_name) {
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
                $checked_value = $input->getAttribute('value');
                $submitted     = isset($values_by_name[$base_name]) ? $values_by_name[$base_name] : '';
                $is_checked    = ($submitted === $checked_value)
                              || (strpos(',' . $submitted . ',', ',' . $checked_value . ',') !== false);
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

        $body   = $dom->getElementsByTagName('body')->item(0);
        $result = '';
        foreach ($body->childNodes as $child) {
            $result .= $dom->saveHTML($child);
        }

        return $result;
    }

    private function _fill_html_values($html, array $values_by_name, array $files_by_name) {
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

            if ($type === 'file') {
                $display = isset($files_by_name[$base_name]) ? $files_by_name[$base_name] : '—';
            } elseif ($type === 'checkbox') {
                $checked_value = $input->getAttribute('value');
                $submitted     = isset($values_by_name[$base_name]) ? $values_by_name[$base_name] : '';
                $is_checked    = ($submitted === $checked_value)
                              || (strpos(',' . $submitted . ',', ',' . $checked_value . ',') !== false);
                $display = ($is_checked ? '[x] ' : '[ ] ') . $checked_value;
            } elseif ($type === 'radio') {
                $radio_value = $input->getAttribute('value');
                $submitted   = isset($values_by_name[$base_name]) ? $values_by_name[$base_name] : '';
                $display     = ($submitted === $radio_value ? '(o) ' : '( ) ') . $radio_value;
            } else {
                $display = isset($values_by_name[$base_name]) ? $values_by_name[$base_name] : '';
            }

            $span = $dom->createElement('span');
            $span->setAttribute('style', 'border-bottom:1px solid #7f8c8d; display:inline-block; min-width:80px; padding:1px 3px;');
            $span->appendChild($dom->createTextNode($display));
            $input->parentNode->replaceChild($span, $input);
        }

        $textareas = iterator_to_array($xpath->query('//textarea[@name]'));
        foreach ($textareas as $textarea) {
            $name    = $textarea->getAttribute('name');
            $display = isset($values_by_name[$name]) ? $values_by_name[$name] : '';
            $div     = $dom->createElement('div');
            $div->setAttribute('style', 'border:1px solid #7f8c8d; padding:4px; min-height:40px; width:100%;');
            $div->appendChild($dom->createTextNode($display));
            $textarea->parentNode->replaceChild($div, $textarea);
        }

        $selects = iterator_to_array($xpath->query('//select[@name]'));
        foreach ($selects as $select) {
            $name    = $select->getAttribute('name');
            $display = isset($values_by_name[$name]) ? $values_by_name[$name] : '';
            $span    = $dom->createElement('span');
            $span->setAttribute('style', 'border-bottom:1px solid #7f8c8d; display:inline-block; min-width:80px;');
            $span->appendChild($dom->createTextNode($display));
            $select->parentNode->replaceChild($span, $select);
        }

        $body   = $dom->getElementsByTagName('body')->item(0);
        $result = '';
        foreach ($body->childNodes as $child) {
            $result .= $dom->saveHTML($child);
        }

        return $result;
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

        // Remove unsupported properties
        $css = preg_replace('/box-shadow\s*:[^;]+;/i', '', $css);
        $css = preg_replace('/transition\s*:[^;]+;/i', '', $css);
        $css = preg_replace('/gap\s*:[^;]+;/i', '', $css);
        $css = preg_replace('/flex(?:-[a-z]+)?\s*:[^;]+;/i', '', $css);

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

        $this->form_fields_model->update_field((int) $field['id'], array(
            'label'            => trim($this->input->post('label')),
            'name'             => trim($this->input->post('name')),
            'field_type'       => trim($this->input->post('field_type')),
            'is_required'      => (int) (bool) $this->input->post('is_required'),
            'sort_order'       => (int) $this->input->post('sort_order') ?: $field['sort_order'],
            'options_json'     => $options_json,
            'updated_by'       => $this->dx_auth->get_username(),
        ));

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

    private function render_view($view, $data = array()) {
        load_bs_view('header', null, false);
        load_bs_view('menu', null, false);
        load_bs_view('banner', null, false);
        return load_last_view($view, $data);
    }
}