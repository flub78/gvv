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

    /**
     * Same storage location/convention as Forms_public::$upload_base_dir — a submission
     * edited in place must store its replacement files exactly like a fresh submission.
     */
    private $upload_base_dir = 'uploads/forms_submissions';

    /** Extensions accepted for form content images (logos, etc.). */
    private $image_allowed_mimes = array('image/png', 'image/jpeg', 'image/gif', 'image/webp');
    private $image_max_bytes = 2097152; // 2 Mo

    /** Blank PDF template (Lot 16 / EF18) — same size ceiling as the scanned-response upload (EF12). */
    private $pdf_template_max_bytes = 10485760; // 10 Mo

    public function __construct() {
        parent::__construct();

        $this->load->helper('views');
        $this->load->model('forms_model');
        $this->load->model('form_pages_model');
        $this->load->model('form_submissions_model');
        $this->load->model('sections_model');
        $this->load->library('form_validation');
        $this->load->library('forms_validation');
        $this->load->library('forms_renderer');
        $this->load->library('forms_file_storage');
        $this->load->library('forms_field_parser');
        $this->lang->load('gvv');
        $this->lang->load('forms');

        if (!$this->dx_auth->is_logged_in()) {
            redirect('auth/login');
        }

        if (!$this->user_has_role('ca') && !$this->user_has_role('club-admin') && !$this->_can_access_workflow_form()) {
            show_error('Acces reserve aux administrateurs.', 403);
        }

        $this->lang->load('tableaux_de_bord');
        $this->load->vars([
            'nav_back_url'   => 'forms_admin',
            'nav_back_label' => 'Liste des formulaires',
        ]);
    }

    /**
     * Narrow bypass of the ca/club-admin guard for instructeur/pilote_vd, restricted
     * to workflow forms (see $workflow_form_slugs):
     * - submission_pdf: the "briefing_vd" icon on vols_decouverte (see MetaData::action())
     *   links straight there.
     * - submissions: the passenger-briefing menu entry (visible to the same
     *   roles via has_briefing_admin_role()) links to forms_admin/submissions/briefing_passager_ulm.
     * Restricted to these methods/form list, not a general relaxation.
     */
    private function _can_access_workflow_form() {
        $method = $this->router->fetch_method();
        if (!in_array($method, array('submission_pdf', 'submissions'), true)) {
            return false;
        }
        if (!$this->user_has_role('instructeur') && !$this->user_has_role('pilote_vd')) {
            return false;
        }
        $form_id = $this->_resolve_form_stub($this->uri->segment(3));
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
        $this->session->set_userdata('nav_from_url',   'forms_admin');
        $this->session->set_userdata('nav_from_label', 'Liste des formulaires');
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
        $section_name = $section_id > 0 ? $this->sections_model->image($section_id) : '';
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
                'target_url'   => '',
                'target_label' => '',
                'club'        => ($section_id > 0) ? $section_id : '',
            ),
            'section_id' => $section_id,
            'section_name' => $section_name,
            'section_selector' => $this->sections_model->section_selector_with_null(),
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
        $row = $this->_overlay_css_from_file($row);

        $this->session->set_userdata('nav_from_url',   'forms_admin/edit/' . $id);
        $this->session->set_userdata('nav_from_label', $row['title']);

        $section_id = (int) $this->session->userdata('section');
        $section_name = $section_id > 0 ? $this->sections_model->image($section_id) : '';
        $row['club'] = ($row['club'] !== null) ? (int) $row['club'] : '';

        $data = array(
            'controller'       => $this->controller,
            'form_mode'        => 'edit',
            'form_action'      => site_url('forms_admin/update/' . $id),
            'submit_label'     => 'Enregistrer',
            'form'             => $row,
            'section_id'       => $section_id,
            'section_name'     => $section_name,
            'section_selector' => $this->sections_model->section_selector_with_null(),
            'handler_classes'  => $this->_available_handler_classes(),
            'is_workflow_form' => in_array($row['public_slug'], $this->workflow_form_slugs, true),
            'is_currently_published' => $row['status'] === 'published',
            'images'           => $this->forms_file_storage->list_images($row['code']),
            'has_pdf_template' => $this->forms_file_storage->has_pdf_template($row['code']),
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

    /**
     * Only accept a club (section) value that matches an existing section
     * (or empty, for a global form) — the select is the only intended input
     * path, this guards against a forged POST setting an arbitrary id.
     */
    private function _validated_club($value) {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        $value = (int) $value;
        return ($this->db->where('id', $value)->count_all_results('sections') > 0) ? $value : null;
    }

    public function store() {
        $section_id = (int) $this->session->userdata('section');
        $section_name = $section_id > 0 ? $this->sections_model->image($section_id) : '';

        $this->form_validation->set_rules('code', 'Code', 'required|max_length[50]|alpha_dash');
        $this->form_validation->set_rules('title', 'Titre', 'required|max_length[255]');
        $this->form_validation->set_rules('public_slug', 'Lien public', 'max_length[100]');
        $this->form_validation->set_rules('css_scope', 'CSS scope', 'max_length[100]');
        $this->form_validation->set_rules('target_url', 'URL cible', 'max_length[255]');
        $this->form_validation->set_rules('target_label', 'Libellé du bouton', 'max_length[100]');

        if ($this->form_validation->run() === FALSE) {
            $data = array(
                'controller' => $this->controller,
                'form'       => $this->input->post(),
                'section_id' => $section_id,
                'section_name' => $section_name,
                'section_selector' => $this->sections_model->section_selector_with_null(),
                'handler_classes' => $this->_available_handler_classes(),
                'error'      => validation_errors(),
            );
            $this->render_view('forms_admin/bs_form', $data);
            return;
        }

        $club = $this->_validated_club($this->input->post('club'));
        $code = trim($this->input->post('code'));

        $id = $this->forms_model->create_form(array(
            'club'            => $club,
            'code'            => $code,
            'title'           => trim($this->input->post('title')),
            'description'     => trim($this->input->post('description')),
            'public_slug'     => trim($this->input->post('public_slug')),
            'css_scope'       => trim($this->input->post('css_scope')),
            'required_params' => $this->input->post('required_params') ?: 'none',
            'allow_upload_response' => (int) $this->input->post('allow_upload_response'),
            'handler_class'   => $this->_validated_handler_class($this->input->post('handler_class')),
            'target_url'      => trim((string) $this->input->post('target_url')),
            'target_label'    => trim((string) $this->input->post('target_label')),
            'created_by'      => $this->dx_auth->get_username(),
        ));

        if (!$id) {
            $data = array(
                'controller' => $this->controller,
                'form'       => $this->input->post(),
                'section_id' => $section_id,
                'section_name' => $section_name,
                'section_selector' => $this->sections_model->section_selector_with_null(),
                'handler_classes' => $this->_available_handler_classes(),
                'error'      => 'Impossible de creer le formulaire.',
            );
            $this->render_view('forms_admin/bs_form', $data);
            return;
        }

        $this->forms_file_storage->ensure_dir($code);
        $this->_sync_meta_file($id);

        $this->session->set_flashdata('forms_success', 'Formulaire cree. Déposez une archive pour ajouter son contenu (pages, CSS, images).');
        redirect('forms_admin/edit/' . (int) $id);
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
        $section_name = $section_id > 0 ? $this->sections_model->image($section_id) : '';

        $this->form_validation->set_rules('code', 'Code', 'required|max_length[50]|alpha_dash');
        $this->form_validation->set_rules('title', 'Titre', 'required|max_length[255]');
        $this->form_validation->set_rules('public_slug', 'Lien public', 'max_length[100]');
        $this->form_validation->set_rules('css_scope', 'CSS scope', 'max_length[100]');
        $this->form_validation->set_rules('target_url', 'URL cible', 'max_length[255]');
        $this->form_validation->set_rules('target_label', 'Libellé du bouton', 'max_length[100]');
        $this->form_validation->set_rules('status', 'Statut', 'in_list[draft,published,archived]');

        if ($this->form_validation->run() === FALSE) {
            $form = array_merge($current, $this->input->post());

            $data = array(
                'controller' => $this->controller,
                'form_mode'  => 'edit',
                'form_action'=> site_url('forms_admin/update/' . $id),
                'submit_label' => 'Enregistrer',
                'form'       => $form,
                'section_id' => $section_id,
                'section_name' => $section_name,
                'section_selector' => $this->sections_model->section_selector_with_null(),
                'handler_classes' => $this->_available_handler_classes(),
                'is_workflow_form' => in_array($current['public_slug'], $this->workflow_form_slugs, true),
                'is_currently_published' => $current['status'] === 'published',
                'error'      => validation_errors(),
            );
            $this->render_view('forms_admin/bs_form', $data);
            return;
        }

        $club = $this->_validated_club($this->input->post('club'));

        $new_status = $this->input->post('status');
        $allowed    = array('draft', 'published', 'archived');
        $status     = in_array($new_status, $allowed, true) ? $new_status : $current['status'];

        $new_code = trim($this->input->post('code'));
        if ($new_code !== $current['code'] && $this->forms_model->code_exists($new_code, $id)) {
            $form = array_merge($current, $this->input->post());
            $data = array(
                'controller'   => $this->controller,
                'form_mode'    => 'edit',
                'form_action'  => site_url('forms_admin/update/' . $id),
                'submit_label' => 'Enregistrer',
                'form'         => $form,
                'section_id'   => $section_id,
                'section_name' => $section_name,
                'section_selector' => $this->sections_model->section_selector_with_null(),
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
            'required_params' => $this->input->post('required_params') ?: $current['required_params'],
            'allow_upload_response' => (int) $this->input->post('allow_upload_response'),
            'handler_class'   => $this->_validated_handler_class($this->input->post('handler_class')),
            'target_url'      => trim((string) $this->input->post('target_url')),
            'target_label'    => trim((string) $this->input->post('target_label')),
            'status'          => $status,
            'updated_by'      => $this->dx_auth->get_username(),
        ));

        if (!$ok) {
            $this->session->set_flashdata('forms_error', 'Impossible de modifier ce formulaire.');
            redirect('forms_admin/edit/' . $id);
            return;
        }

        if ($new_code !== $current['code']) {
            $this->forms_file_storage->rename_form_dir($current['code'], $new_code);
        }
        $this->_sync_meta_file($id);

        $this->session->set_flashdata('forms_success', 'Formulaire « ' . trim($this->input->post('title')) . ' » mis à jour.');
        redirect('forms_admin');
    }

    public function delete($id = 0) {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            show_error('Méthode non autorisée.', 405);
            return;
        }
        $id = (int) $id;
        $current = $id > 0 ? $this->forms_model->get_by_id($id) : null;
        if (!$current || !$this->forms_model->delete_form($id)) {
            $this->session->set_flashdata('forms_error', 'Impossible de supprimer ce formulaire.');
            redirect('forms_admin');
            return;
        }

        $this->forms_file_storage->delete_form_dir($current['code']);

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

        $source = $this->forms_model->get_by_id($id);
        $new_id = $this->forms_model->duplicate_form($id, $this->dx_auth->get_username());
        if (!$new_id) {
            $this->session->set_flashdata('forms_error', 'Impossible de dupliquer ce formulaire.');
            redirect('forms_admin');
            return;
        }

        if ($source) {
            $new_form = $this->forms_model->get_by_id($new_id);
            if ($new_form) {
                $this->forms_file_storage->copy_form_dir($source['code'], $new_form['code']);
                // Regenerate meta.json under the new code: the copied one still
                // carries the source form's title (duplicate_form() appends " (copie)").
                $this->_sync_meta_file($new_id);
            }
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
        $form = $this->_overlay_css_from_file($form);

        $pages = $this->form_pages_model->get_form_pages((int) $form['id']);
        $pages = $this->_overlay_pages_from_file($form['code'], $pages);
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
            $fields = $this->forms_field_parser->parse_fields((string) $current_page['content_html']);
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

        $pages = $this->form_pages_model->get_form_pages((int) $form['id']);
        $pages = $this->_overlay_pages_from_file($form['code'], $pages);

        $data = array(
            'controller' => $this->controller,
            'form'       => $form,
            'pages'      => $pages,
            'success'    => $this->session->flashdata('forms_success') ?: '',
            'error'      => $this->session->flashdata('forms_error') ?: '',
        );

        $this->render_view('forms_admin/bs_pages', $data);
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

        $this->forms_file_storage->delete_page($form['code'], (int) $page['page_number']);
        $this->_sync_meta_file((int) $form['id']);

        $this->session->set_flashdata('forms_success', 'Page supprimee.');
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
        $form = $this->load_form_or_redirect($this->_resolve_form_stub($form_id));
        if (!$form) {
            return;
        }

        $this->load->vars([
            'nav_back_url'   => $this->session->userdata('nav_from_url')   ?: 'forms_admin',
            'nav_back_label' => $this->session->userdata('nav_from_label') ?: 'Liste des formulaires',
        ]);

        $fields = $this->_parse_form_fields($form);
        $identifier_names = array();
        foreach ($fields as $f) {
            if (!empty($f['is_identifier'])) {
                $identifier_names[] = $f['name'];
            }
        }

        $submissions = $this->form_submissions_model->get_form_submissions((int) $form['id'], 200, 0, $identifier_names);

        $upload_submission_ids = array();
        foreach ($submissions as $submission) {
            if ($submission['submission_method'] === 'upload') {
                $upload_submission_ids[] = (int) $submission['id'];
            }
        }

        // Export to a GVV creation form (Lot 12) — button only when both are configured.
        $export_urls = array();
        if (!empty($form['target_url']) && !empty($form['target_label'])) {
            foreach ($submissions as $submission) {
                if ($submission['submission_method'] === 'upload') {
                    continue;
                }
                $export_urls[(int) $submission['id']] = $this->form_submissions_model->build_export_url(
                    $form['target_url'],
                    (int) $submission['id'],
                    $fields
                );
            }
        }

        $data = array(
            'controller'    => $this->controller,
            'form'          => $form,
            'submissions'   => $submissions,
            'upload_files'  => $this->form_submissions_model->get_uploaded_response_files_for_submissions($upload_submission_ids),
            'export_urls'   => $export_urls,
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

        $fields_by_name = $this->forms_field_parser->fields_by_name($this->_parse_form_fields($form));

        $values = $this->form_submissions_model->get_submission_values((int) $submission['id']);
        foreach ($values as &$v) {
            $fname = (string) $v['field_name'];
            $v['field_label'] = isset($fields_by_name[$fname]) ? $fields_by_name[$fname]['label'] : $fname;
            $v['field_type']  = isset($fields_by_name[$fname]) ? $fields_by_name[$fname]['field_type'] : '';
        }
        unset($v);

        $files = $this->form_submissions_model->get_submission_files((int) $submission['id']);
        foreach ($files as &$f) {
            $fname = (string) $f['field_name'];
            $f['field_label'] = isset($fields_by_name[$fname]) ? $fields_by_name[$fname]['label'] : null;
        }
        unset($f);

        $data = array(
            'controller' => $this->controller,
            'form'       => $form,
            'submission' => $submission,
            'values'     => $values,
            'files'      => $files,
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
        $pages      = $this->_overlay_pages_from_file($form['code'], $pages);

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

        $scope_class  = $this->_scope_class_for_form($form);
        $global_css   = !empty($form['global_css']) ? (string) $form['global_css'] : '';
        $global_css   = $this->forms_renderer->scope_css($global_css, $scope_class);
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
              . '<div class="' . html_escape($scope_class) . '">' . "\n"
              . $content . "\n"
              . '</div>' . "\n"
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
        $form = $this->load_form_or_redirect($form_id, true);
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
        $pages      = $this->_overlay_pages_from_file($form['code'], $pages);

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

        $scope_class = $this->_scope_class_for_form($form);
        $css = !empty($form['global_css']) ? (string) $form['global_css'] : '';
        $css = $this->forms_renderer->scope_css($css, $scope_class);

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
              . '<div class="' . html_escape($scope_class) . '">'
              . implode('<p style="page-break-after:always;"></p>', $body_parts)
              . '</div>'
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
            // Images deposited via the "Images" admin card (Lot 2-bis) are served
            // through forms_public/image/{code}/{filename}, a controller route —
            // the URL path does not mirror a filesystem path like other assets, so
            // it needs its own resolution via Forms_file_storage rather than the
            // generic base_url-relative mapping below. Matched whether the src is
            // root-relative or prefixed with the full base_url.
            if (preg_match('~^(?:https?://[^/]+)?/forms_public/image/([^/]+)/([^/?#]+)~i', $src, $m)) {
                $abs_path = $this->forms_file_storage->image_path(rawurldecode($m[1]), rawurldecode($m[2]));
                if (!file_exists($abs_path) || !is_readable($abs_path)) {
                    return null;
                }
                $info = getimagesize($abs_path);
                $mime = $info ? $info['mime'] : 'image/jpeg';
                return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($abs_path));
            }

            // Same reasoning for images shared across forms (Lot 2-quater) —
            // served through forms_public/shared_image/{filename}.
            if (preg_match('~^(?:https?://[^/]+)?/forms_public/shared_image/([^/?#]+)~i', $src, $m)) {
                $abs_path = $this->forms_file_storage->shared_image_path(rawurldecode($m[1]));
                if (!file_exists($abs_path) || !is_readable($abs_path)) {
                    return null;
                }
                $info = getimagesize($abs_path);
                $mime = $info ? $info['mime'] : 'image/jpeg';
                return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($abs_path));
            }

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

    /**
     * Load and validate an "online" submission for editing, shared by submission_edit()
     * and submission_edit_submit(). Returns the submission array, or false after already
     * redirecting (same convention as load_form_or_redirect()).
     */
    private function _load_editable_submission_or_redirect($form, $submission_id) {
        $submission = $this->form_submissions_model->get_by_id((int) $submission_id);
        if (!$submission || (int) $submission['form_id'] !== (int) $form['id']) {
            $this->session->set_flashdata('forms_error', 'Soumission introuvable pour ce formulaire.');
            redirect('forms_admin/submissions/' . (int) $form['id']);
            return false;
        }

        if ($submission['submission_method'] !== 'online') {
            $this->session->set_flashdata('forms_error', 'Cette réponse ne peut pas être modifiée (soumise par téléchargement).');
            redirect('forms_admin/submissions/' . (int) $form['id']);
            return false;
        }

        return $submission;
    }

    private function _find_page_by_number(array $pages, $page_number) {
        foreach ($pages as $page) {
            if ((int) $page['page_number'] === (int) $page_number) {
                return $page;
            }
        }
        return false;
    }

    /**
     * Split a submission's existing files (Form_submissions_model::get_submission_files())
     * into the shapes needed to render/persist an edit:
     * - $sig_existing:    field_name => preview URL, fed to Forms_renderer::inject_signature_widgets()
     * - $file_existing:   field_name => ['original_name', 'preview_url'], fed to inject_existing_file_hints()
     * - $by_field_name:   field_name => file row.
     */
    private function _split_existing_files($form, $submission, array $existing_files, array $fields_by_name) {
        $sig_existing   = array();
        $file_existing  = array();
        $by_field_name  = array();

        foreach ($existing_files as $f) {
            $field_name = (string) $f['field_name'];
            if ($field_name === '') {
                continue;
            }

            $preview_url = site_url('forms_admin/submission_file/' . (int) $form['id'] . '/' . (int) $submission['id'] . '/' . (int) $f['id']) . '?inline=1';

            $is_signature = isset($fields_by_name[$field_name]) && $fields_by_name[$field_name]['field_type'] === 'signature';

            if ($is_signature) {
                $sig_existing[$field_name] = $preview_url;
            } else {
                $file_existing[$field_name] = array(
                    'original_name' => $f['original_name'],
                    'preview_url'   => $preview_url,
                );
            }

            $by_field_name[$field_name] = $f;
        }

        return array($sig_existing, $file_existing, $by_field_name);
    }

    /**
     * Build the old_values map (field_name => value_text) expected by
     * Forms_renderer::normalize_fields_for_view() / repopulate_html_fields(), from a
     * submission's already-saved values.
     *
     * Checkbox values: Form_submissions_model::normalize_value() JSON-encodes arrays, used
     * when a field genuinely received several values (e.g. <select multiple>, or in theory
     * several checkboxes sharing one name — though Forms_field_parser dedupes by exact
     * name, so that pattern can never actually produce more than one field in practice).
     * In real forms, a "checkbox" field is one single toggle and value_text is the plain
     * string the browser submits: "on" (or the input's own value=) when checked, "" when
     * not — never JSON. Only decode when it actually looks like a JSON array; otherwise
     * keep the raw scalar so repopulate_html_fields() can check it as a boolean.
     */
    private function _old_values_from_submission_values(array $submission_values, array $fields_by_name) {
        $old_values = array();
        foreach ($submission_values as $v) {
            $field_name = (string) $v['field_name'];
            if ($field_name === '') {
                continue;
            }
            $value = $v['value_text'];
            $type = isset($fields_by_name[$field_name]) ? $fields_by_name[$field_name]['field_type'] : '';
            if ($type === 'checkbox' && is_string($value) && substr(trim($value), 0, 1) === '[') {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    $value = $decoded;
                }
            }
            $old_values[$field_name] = $value;
        }
        return $old_values;
    }

    /**
     * Same identifier a submission would show in the "Identification" column of the
     * submissions list (fields flagged data-gvv-identifier, concatenated) — used to
     * replace the bare "#<id>" on single-submission admin pages (edit, delete confirm)
     * with something a human can recognize. Falls back to upload_comment, then to ''
     * (caller falls back further to "#<id>").
     */
    private function _response_identifier($form, $submission) {
        $identifier_names = array();
        foreach ($this->_parse_form_fields($form) as $f) {
            if (!empty($f['is_identifier'])) {
                $identifier_names[] = (string) $f['name'];
            }
        }

        $ident = '';
        if (!empty($identifier_names)) {
            $values = $this->form_submissions_model->get_submission_values((int) $submission['id']);
            $parts = array();
            foreach ($values as $v) {
                if (in_array((string) $v['field_name'], $identifier_names, true)) {
                    $parts[] = (string) $v['value_text'];
                }
            }
            $ident = trim(implode(' ', $parts));
        }

        return $ident !== '' ? $ident : trim((string) ($submission['upload_comment'] ?? ''));
    }

    /**
     * Display an existing "online" submission pre-filled for editing (EF16 — modification
     * en place d'une réponse déjà soumise). Reuses the same rendering/validation engine as
     * the public submission flow (Forms_renderer, Forms_validation, per-page navigation);
     * only the prefill source differs (existing submission values/files instead of
     * flashdata). GVV prefill mechanisms A/B (data-gvv-source, URL params/lock[]) are not
     * re-applied here — this edits already-collected values, it does not re-run the
     * original generation context.
     */
    public function submission_edit($form_id = 0, $submission_id = 0) {
        $form = $this->load_form_or_redirect($form_id);
        if (!$form) {
            return;
        }

        $submission = $this->_load_editable_submission_or_redirect($form, $submission_id);
        if (!$submission) {
            return;
        }

        $this->load->vars([
            'nav_back_url'   => 'forms_admin/submissions/' . (int) $form['id'],
            'nav_back_label' => 'Réponses',
        ]);

        $pages = $this->form_pages_model->get_form_pages((int) $form['id']);
        $pages = $this->_overlay_pages_from_file($form['code'], $pages);
        if (empty($pages)) {
            show_error('Ce formulaire ne contient aucune page publiee.', 404);
            return;
        }

        $page_count = count($pages);
        $current_page_number = (int) $this->input->get('page');
        if ($current_page_number <= 0) {
            $current_page_number = 1;
        }
        if ($current_page_number > $page_count) {
            $current_page_number = $page_count;
        }

        $current_page = $this->_find_page_by_number($pages, $current_page_number);
        if (!$current_page) {
            $current_page = $pages[0];
            $current_page_number = (int) $current_page['page_number'];
        }

        $fields = $this->forms_field_parser->parse_fields((string) $current_page['content_html']);
        $fields_by_name = $this->forms_field_parser->fields_by_name($fields);

        // Prefill priority: a failed resubmission attempt (flashdata) beats the
        // already-saved values, same convention as the public "old values on error" flow.
        $flash_old_values = $this->session->flashdata('forms_edit_old_values');
        if (is_array($flash_old_values)) {
            $old_values = $flash_old_values;
        } else {
            $submission_values = $this->form_submissions_model->get_submission_values((int) $submission['id']);
            $old_values = $this->_old_values_from_submission_values($submission_values, $fields_by_name);
        }

        $render_fields = $this->forms_renderer->normalize_fields_for_view($fields, $old_values);

        $existing_files = $this->form_submissions_model->get_submission_files((int) $submission['id']);
        list($sig_existing, $file_existing, ) = $this->_split_existing_files($form, $submission, $existing_files, $fields_by_name);

        $has_signature_widget = false;
        if (!empty($current_page['content_html'])) {
            $raw = html_entity_decode((string) $current_page['content_html'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $injected = $this->forms_renderer->inject_signature_widgets($raw, $has_signature_widget, array(), $sig_existing);
            $injected = $this->forms_renderer->inject_validation_script($injected);
            $injected = $this->forms_renderer->inject_existing_file_hints($injected, $file_existing);
            if (!empty($old_values)) {
                $injected = $this->forms_renderer->repopulate_html_fields($injected, $fields, $old_values);
            }
            $current_page['content_html'] = $injected;
        }

        $data = array(
            'form'                 => $form,
            'submission'           => $submission,
            'pages'                => $pages,
            'current_page'         => $current_page,
            'current_page_number'  => $current_page_number,
            'page_count'           => $page_count,
            'fields'               => $fields,
            'render_fields'        => $render_fields,
            'error'                => $this->session->flashdata('forms_error') ?: '',
            'has_signature_widget' => $has_signature_widget,
            'response_identifier'  => $this->_response_identifier($form, $submission),
        );

        $this->render_view('forms_admin/bs_submission_edit', $data);
    }

    /**
     * Validate and persist a resubmitted page of an existing submission (EF16). Updates
     * the submission in place: id and submission_uuid never change, submitted_at /
     * subject_type / subject_id / submission_method are never rewritten. A file or
     * signature field left empty keeps its existing value; the previous file is only
     * deleted from storage after its replacement has been written successfully.
     */
    public function submission_edit_submit($form_id = 0, $submission_id = 0) {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            redirect('forms_admin/submission_edit/' . (int) $form_id . '/' . (int) $submission_id);
            return;
        }

        $form = $this->load_form_or_redirect($form_id);
        if (!$form) {
            return;
        }

        $submission = $this->_load_editable_submission_or_redirect($form, $submission_id);
        if (!$submission) {
            return;
        }

        $page_number = (int) $this->input->post('page_number');
        if ($page_number <= 0) {
            $page_number = 1;
        }

        $pages = $this->form_pages_model->get_form_pages((int) $form['id']);
        $pages = $this->_overlay_pages_from_file($form['code'], $pages);
        $page = $this->_find_page_by_number($pages, $page_number);
        if (!$page) {
            show_error('Page de formulaire introuvable.', 404);
            return;
        }

        $fields = $this->forms_field_parser->parse_fields((string) $page['content_html']);
        $fields_by_name = $this->forms_field_parser->fields_by_name($fields);

        $existing_files = $this->form_submissions_model->get_submission_files((int) $submission['id']);
        list(, , $existing_by_name) = $this->_split_existing_files($form, $submission, $existing_files, $fields_by_name);

        $submitted_values = array();
        $file_field_keys  = array(); // field_name => $_FILES key, for fields that actually got a new file
        $signature_canvas_data = array(); // field_name => base64, for fields that actually got a new drawn/typed signature

        foreach ($fields as $field) {
            $key        = (string) $field['name'];
            $field_type = isset($field['field_type']) ? $field['field_type'] : 'text';

            if ($field_type === 'signature') {
                $sig_type = trim((string) $this->input->post($key . '_type'));
                if (!in_array($sig_type, array('canvas', 'text', 'file'), true)) {
                    $sig_type = 'canvas';
                }

                if ($sig_type === 'file' && isset($_FILES[$key . '_file']) && !empty($_FILES[$key . '_file']['name'])) {
                    $file_field_keys[$key] = $key . '_file';
                    $submitted_values[$key] = (string) $_FILES[$key . '_file']['name'];
                    continue;
                }

                $base64 = ($sig_type !== 'file') ? trim((string) $this->input->post($key)) : '';
                if ($base64 !== '') {
                    $signature_canvas_data[$key] = $base64;
                    $submitted_values[$key] = '[signature]';
                    continue;
                }

                // Nothing new submitted: keep the existing signature (if any) untouched.
                $submitted_values[$key] = isset($existing_by_name[$key])
                    ? $existing_by_name[$key]['original_name']
                    : '';
                continue;
            }

            if ($field_type === 'file') {
                if (isset($_FILES[$key]) && !empty($_FILES[$key]['name'])) {
                    $file_field_keys[$key] = $key;
                    $submitted_values[$key] = (string) $_FILES[$key]['name'];
                    continue;
                }

                // Nothing new submitted: keep the existing file (if any) untouched.
                $submitted_values[$key] = isset($existing_by_name[$key])
                    ? $existing_by_name[$key]['original_name']
                    : '';
                continue;
            }

            $value = $this->input->post($key);
            if (is_array($value)) {
                $value = array_values($value);
            }
            $submitted_values[$key] = $value;
        }

        $errors = $this->forms_validation->validate_fields($fields, $submitted_values);
        if (!empty($errors)) {
            $this->session->set_flashdata('forms_error', implode('<br>', $errors));
            $this->session->set_flashdata('forms_edit_old_values', $submitted_values);
            redirect('forms_admin/submission_edit/' . (int) $form['id'] . '/' . (int) $submission['id'] . '?page=' . (int) $page_number);
            return;
        }

        $new_files = array(); // field_name => file descriptor, only for fields actually replaced

        foreach ($signature_canvas_data as $field_name => $base64) {
            $result = $this->forms_renderer->make_signature_file($base64, $this->upload_base_dir, $field_name);
            if ($result) {
                $new_files[$field_name] = $result;
            }
        }

        if (!empty($file_field_keys)) {
            $upload_result = $this->forms_renderer->upload_submitted_files($file_field_keys, $this->upload_base_dir);
            if (!empty($upload_result['errors'])) {
                $this->session->set_flashdata('forms_error', implode('<br>', $upload_result['errors']));
                $this->session->set_flashdata('forms_edit_old_values', $submitted_values);
                redirect('forms_admin/submission_edit/' . (int) $form['id'] . '/' . (int) $submission['id'] . '?page=' . (int) $page_number);
                return;
            }
            foreach ($upload_result['files'] as $uf) {
                $new_files[(string) $uf['widget_name']] = $uf;
            }
        }

        $updated_by = $this->dx_auth->get_username();

        $this->db->trans_start();
        $this->form_submissions_model->save_submission_values((int) $submission['id'], $submitted_values, $updated_by);
        if (!empty($new_files)) {
            $this->form_submissions_model->save_submission_files((int) $submission['id'], array_values($new_files), $updated_by);
        }
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            foreach ($new_files as $uf) {
                $fp = FCPATH . ltrim((string) $uf['storage_path'], '/');
                if (is_file($fp)) {
                    @unlink($fp);
                }
            }
            $this->session->set_flashdata('forms_error', 'Impossible d\'enregistrer les modifications pour le moment.');
            $this->session->set_flashdata('forms_edit_old_values', $submitted_values);
            redirect('forms_admin/submission_edit/' . (int) $form['id'] . '/' . (int) $submission['id'] . '?page=' . (int) $page_number);
            return;
        }

        // Only delete the previous file/signature once its replacement is confirmed saved.
        foreach ($new_files as $field_name => $uf) {
            if (isset($existing_by_name[$field_name])) {
                $this->form_submissions_model->delete_submission_file((int) $existing_by_name[$field_name]['id']);
            }
        }

        $this->session->set_flashdata('forms_success', 'Réponse #' . (int) $submission['id'] . ' modifiée.');
        redirect('forms_admin/submission/' . (int) $form['id'] . '/' . (int) $submission['id']);
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

    /**
     * Read-only list of a page's fields, parsed on demand from its HTML content
     * (uploads/formulaires/) — form_fields no longer exists (migration 166),
     * so there is no separate structured field editor: fields are edited by
     * editing the page's HTML (name/type/required are native HTML; validation
     * rules and "identifier" flag are set via data-gvv-validation/
     * data-gvv-identifier attributes — see Forms_field_parser).
     */
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
            'fields'     => $this->forms_field_parser->parse_fields((string) $page['content_html']),
            'success'    => $this->session->flashdata('forms_success') ?: '',
            'error'      => $this->session->flashdata('forms_error') ?: '',
        );

        $this->render_view('forms_admin/bs_fields', $data);
    }

    /**
     * Thin wrapper kept for call-site compatibility: field parsing itself now
     * lives in Forms_field_parser (shared with forms_public.php), since
     * form_fields no longer exists to sync to (migration 166).
     */
    private function extract_html_fields($html) {
        return $this->forms_field_parser->parse_fields($html);
    }

    /**
     * Cross-page field-name collision check, now parsing every other page's
     * HTML on demand instead of querying the (removed) form_fields table.
     */
    private function validate_html_field_names($form, $exclude_page_id, array $names) {
        if (empty($names)) {
            return null;
        }

        $other_pages = $this->db
            ->select('page_number, content_html')
            ->from('form_pages')
            ->where('form_id', (int) $form['id'])
            ->where('id !=', (int) $exclude_page_id)
            ->get()
            ->result_array();
        $other_pages = $this->_overlay_pages_from_file($form['code'], $other_pages);

        $other_names = array();
        foreach ($other_pages as $p) {
            foreach ($this->forms_field_parser->parse_fields((string) $p['content_html']) as $f) {
                $other_names[] = $f['name'];
            }
        }

        $conflicts = array_intersect($names, $other_names);
        if (!empty($conflicts)) {
            return 'Noms de champs déjà utilisés dans une autre page : ' . implode(', ', $conflicts);
        }

        return null;
    }

    /**
     * $allow_workflow_bypass: skip the club-scoping check for workflow forms
     * (see $workflow_form_slugs). These are shared across all clubs by design
     * (e.g. briefing-passager-ulm can be attached to a VLD of any section), so
     * viewing their submission_pdf must not be restricted to the form's own
     * club, unlike the regular admin actions (edit/delete/list).
     */
    /**
     * forms_admin/submissions accepts either the numeric form id or the form's
     * code as a stable stand-in for it (e.g. "briefing_passager_ulm" for id 2).
     */
    private function _resolve_form_stub($form_id) {
        if (ctype_digit((string) $form_id)) {
            return (int) $form_id;
        }
        $form = $this->forms_model->get_by_code((string) $form_id);
        return $form ? (int) $form['id'] : 0;
    }

    private function load_form_or_redirect($form_id, $allow_workflow_bypass = false) {
        $form = $this->forms_model->get_by_id((int) $form_id);
        if (!$form) {
            $this->session->set_flashdata('forms_error', 'Formulaire introuvable.');
            redirect('forms_admin');
            return false;
        }
        $form = $this->_overlay_css_from_file($form);

        if ($allow_workflow_bypass && in_array($form['public_slug'], $this->workflow_form_slugs, true)) {
            return $form;
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
        return $this->_overlay_page_from_file($form['code'], $page);
    }

    /**
     * uploads/formulaires/{code}/ is the source of truth for form content
     * (EF2-bis) — it can be edited directly on disk, not only through the
     * admin's textarea (Forms_file_storage::write_page()/write_css()). These
     * helpers overlay the file's content onto the DB-fetched row, mirroring
     * Forms_public::_overlay_css_from_file()/_overlay_pages_from_file() so the
     * admin side never displays or acts on content stale relative to the file.
     */
    private function _overlay_css_from_file(array $form) {
        $file_css = $this->forms_file_storage->read_css($form['code']);
        if ($file_css !== null) {
            $form['global_css'] = $this->forms_renderer->rewrite_shared_css_import($file_css);
        }
        return $form;
    }

    private function _overlay_page_from_file($code, array $page) {
        $file_html = $this->forms_file_storage->read_page($code, (int) $page['page_number']);
        if ($file_html !== null) {
            $content = $this->forms_renderer->rewrite_local_image_urls($file_html, $code);
            $page['content_html'] = $this->forms_renderer->inject_required_markers($content);
        }
        return $page;
    }

    /**
     * Builds the scope class a form's content must be wrapped in for its
     * global_css (always authored against .forms-public-root, optionally
     * extended by a per-form css_scope) to apply — see
     * doc/design_notes/formulaires_css_isolation_design.md. Mirrors the
     * logic in views/forms_admin/bs_submission_edit.php.
     */
    private function _scope_class_for_form(array $form) {
        $scope_class = 'forms-public-root';
        $css_scope   = trim(isset($form['css_scope']) ? (string) $form['css_scope'] : '');
        if ($css_scope !== '') {
            $scope_class .= ' ' . preg_replace('/[^a-zA-Z0-9_-]+/', '-', $css_scope);
        }
        return $scope_class;
    }

    private function _overlay_pages_from_file($code, array $pages) {
        foreach ($pages as &$page) {
            $page = $this->_overlay_page_from_file($code, $page);
        }
        unset($page);
        return $pages;
    }

    /**
     * Writes meta.json from the current DB row + page list — called after
     * every mutation of a form's metadata or page list, so the directory
     * stays self-describing at all times, not only at export (EF2-quater).
     * Deliberately excludes code/status/public_slug/section: those stay
     * admin-only and are never carried by an archive (see form_restore()).
     */
    private function _sync_meta_file($form_id) {
        $form = $this->forms_model->get_by_id((int) $form_id);
        if (!$form) {
            return;
        }

        $pages = $this->form_pages_model->get_form_pages((int) $form_id);
        $meta_pages = array();
        foreach ($pages as $page) {
            $meta_pages[] = array(
                'page_number' => (int) $page['page_number'],
                'title'       => (string) $page['title'],
            );
        }

        $meta = array(
            'title'                 => (string) $form['title'],
            'description'           => (string) $form['description'],
            'css_scope'             => (string) $form['css_scope'],
            'required_params'       => (string) $form['required_params'],
            'allow_upload_response' => !empty($form['allow_upload_response']),
            'pdf_template'          => $this->forms_file_storage->has_pdf_template($form['code']),
            'handler_class'         => !empty($form['handler_class']) ? (string) $form['handler_class'] : null,
            'target_url'            => !empty($form['target_url']) ? (string) $form['target_url'] : null,
            'target_label'          => !empty($form['target_label']) ? (string) $form['target_label'] : null,
            'pages'                 => $meta_pages,
        );

        $this->forms_file_storage->write_meta($form['code'], $meta);
    }

    /**
     * Parses every page of a form into one flat list of field descriptors —
     * form_fields no longer exists (migration 166), field metadata is always
     * derived on demand from the HTML content.
     */
    private function _parse_form_fields($form) {
        $pages = $this->form_pages_model->get_form_pages((int) $form['id']);
        $pages = $this->_overlay_pages_from_file($form['code'], $pages);
        return $this->forms_field_parser->parse_form_pages($pages);
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

        $this->forms_file_storage->write_css($code, $global_css);

        $page_id = $this->form_pages_model->create_page(array(
            'form_id'      => (int) $form_id,
            'page_number'  => 1,
            'title'        => '',
            'content_html' => $content_html,
            'created_by'   => $by,
        ));

        if ($page_id) {
            $this->forms_file_storage->write_page($code, 1, $content_html);
        }

        $this->_sync_meta_file($form_id);

        $this->session->set_flashdata('forms_success', 'Formulaire « ' . html_escape($html_title) . ' » créé depuis le fichier HTML.');
        redirect('forms_admin/edit/' . (int) $form_id);
    }

    public function image_upload($form_id = 0) {
        $form = $this->load_form_or_redirect($form_id);
        if (!$form) {
            return;
        }

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            redirect('forms_admin/edit/' . (int) $form['id']);
            return;
        }

        if (empty($_FILES['image']['tmp_name']) || (int) $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $this->session->set_flashdata('forms_error', 'Aucune image valide reçue.');
            redirect('forms_admin/edit/' . (int) $form['id']);
            return;
        }

        if ((int) $_FILES['image']['size'] > $this->image_max_bytes) {
            $this->session->set_flashdata('forms_error', 'Image trop volumineuse (2 Mo maximum).');
            redirect('forms_admin/edit/' . (int) $form['id']);
            return;
        }

        $info = @getimagesize($_FILES['image']['tmp_name']);
        if ($info === false || !in_array($info['mime'], $this->image_allowed_mimes, true)) {
            $this->session->set_flashdata('forms_error', 'Le fichier doit être une image (PNG, JPEG, GIF ou WEBP).');
            redirect('forms_admin/edit/' . (int) $form['id']);
            return;
        }

        $stored_name = $this->forms_file_storage->write_image(
            $form['code'],
            $_FILES['image']['name'],
            file_get_contents($_FILES['image']['tmp_name'])
        );

        $url = site_url('forms_public/image/' . $form['code'] . '/' . $stored_name);
        $this->session->set_flashdata(
            'forms_success',
            'Image envoyée : ' . html_escape($stored_name) . '. URL à utiliser dans le HTML : ' . html_escape($url)
        );
        redirect('forms_admin/edit/' . (int) $form['id']);
    }

    public function image_delete($form_id = 0, $filename = '') {
        $form = $this->load_form_or_redirect($form_id);
        if (!$form) {
            return;
        }

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            redirect('forms_admin/edit/' . (int) $form['id']);
            return;
        }

        $this->forms_file_storage->delete_image($form['code'], $filename);
        $this->session->set_flashdata('forms_success', 'Image supprimée.');
        redirect('forms_admin/edit/' . (int) $form['id']);
    }

    /**
     * Blank PDF template (Lot 16 / EF18) — a single fixed file per form,
     * stored at the root of the form's directory (Forms_file_storage::
     * write_pdf_template()). A new upload simply overwrites the previous
     * one, so there is never an orphaned file to clean up.
     */
    public function pdf_template_upload($form_id = 0) {
        $form = $this->load_form_or_redirect($form_id);
        if (!$form) {
            return;
        }

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            redirect('forms_admin/edit/' . (int) $form['id']);
            return;
        }

        if (empty($_FILES['pdf_template']['tmp_name']) || (int) $_FILES['pdf_template']['error'] !== UPLOAD_ERR_OK) {
            $this->session->set_flashdata('forms_error', $this->lang->line('forms_error_pdf_template_missing'));
            redirect('forms_admin/edit/' . (int) $form['id']);
            return;
        }

        if ((int) $_FILES['pdf_template']['size'] > $this->pdf_template_max_bytes) {
            $this->session->set_flashdata('forms_error', $this->lang->line('forms_error_pdf_template_too_large'));
            redirect('forms_admin/edit/' . (int) $form['id']);
            return;
        }

        $tmp_name = $_FILES['pdf_template']['tmp_name'];
        $mime     = @mime_content_type($tmp_name);
        $header   = @file_get_contents($tmp_name, false, null, 0, 5);
        if ($mime !== 'application/pdf' || $header !== '%PDF-') {
            $this->session->set_flashdata('forms_error', $this->lang->line('forms_error_pdf_template_invalid'));
            redirect('forms_admin/edit/' . (int) $form['id']);
            return;
        }

        $this->forms_file_storage->write_pdf_template($form['code'], file_get_contents($tmp_name));
        $this->_sync_meta_file((int) $form['id']);

        $this->session->set_flashdata('forms_success', $this->lang->line('forms_success_pdf_template_uploaded'));
        redirect('forms_admin/edit/' . (int) $form['id']);
    }

    public function pdf_template_delete($form_id = 0) {
        $form = $this->load_form_or_redirect($form_id);
        if (!$form) {
            return;
        }

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            redirect('forms_admin/edit/' . (int) $form['id']);
            return;
        }

        $this->forms_file_storage->delete_pdf_template($form['code']);
        $this->_sync_meta_file((int) $form['id']);

        $this->session->set_flashdata('forms_success', $this->lang->line('forms_success_pdf_template_deleted'));
        redirect('forms_admin/edit/' . (int) $form['id']);
    }

    /**
     * The exported archive is a direct mirror of uploads/formulaires/{code}/
     * (same pageNN.html files already wrapped as standalone HTML5 documents,
     * same style.css, same meta.json) — no separate archive format to
     * maintain (EF2-quater). meta.json is refreshed first so the archive
     * reflects the current DB-held metadata even if it drifted. Shared
     * resources (.commun/) are bundled too, under a .commun/ subfolder in
     * the archive, so it stays self-contained and viewable outside GVV —
     * never restored on import, see form_import_zip()/form_restore() and
     * "Ressources locales et partagées" in the design notes.
     */
    public function form_backup($form_id = 0) {
        $form = $this->load_form_or_redirect($form_id);
        if (!$form) {
            return;
        }

        $this->_sync_meta_file((int) $form['id']);

        $code      = $form['code'];
        $dir       = $this->forms_file_storage->form_dir($code);
        $safe_code = $this->forms_file_storage->safe_code($code);
        $zip_path  = sys_get_temp_dir() . '/' . $safe_code . '_' . uniqid() . '.zip';

        $original_dir = getcwd();
        chdir($dir);
        exec('zip -r ' . escapeshellarg($zip_path) . ' . -x .htaccess', $output, $return_code);

        $shared_dir = $this->forms_file_storage->shared_dir();
        if ($return_code === 0 && is_dir($shared_dir)) {
            chdir(dirname($shared_dir));
            exec(
                'zip -r ' . escapeshellarg($zip_path) . ' ' . escapeshellarg(basename($shared_dir))
                . ' -x ' . escapeshellarg(basename($shared_dir) . '/.htaccess'),
                $output,
                $return_code
            );
        }
        chdir($original_dir);

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
        if (!file_exists($meta_path) || empty(glob($tmp_dir . '/page*.html'))) {
            $this->_cleanup_tmpdir($tmp_dir);
            $this->session->set_flashdata('forms_error', 'meta.json ou pages HTML absents de l\'archive — ce n\'est pas une archive de formulaire GVV valide.');
            redirect('forms_admin');
            return;
        }

        $meta = json_decode(file_get_contents($meta_path), true);
        if (!is_array($meta)) {
            $this->_cleanup_tmpdir($tmp_dir);
            $this->session->set_flashdata('forms_error', 'meta.json invalide ou mal formé.');
            redirect('forms_admin');
            return;
        }

        // Build a unique code: from the optional form field, else the archive's own file name
        $code_input = trim((string) $this->input->post('import_code'));
        $base_code  = $code_input !== '' ? $code_input : pathinfo((string) $_FILES['import_zip']['name'], PATHINFO_FILENAME);
        $base_code  = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $base_code);
        $base_code  = trim($base_code, '_');
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
            'allow_upload_response' => !empty($meta['allow_upload_response']),
            'handler_class'   => isset($meta['handler_class']) ? $this->_validated_handler_class($meta['handler_class']) : null,
            'target_url'      => isset($meta['target_url'])   ? (string) $meta['target_url']   : '',
            'target_label'    => isset($meta['target_label']) ? (string) $meta['target_label'] : '',
            'created_by'      => $by,
        ));

        if (!$form_id) {
            $this->_cleanup_tmpdir($tmp_dir);
            $this->session->set_flashdata('forms_error', 'Impossible de créer le formulaire depuis l\'archive.');
            redirect('forms_admin');
            return;
        }

        $this->forms_file_storage->replace_all_from_dir($code, $tmp_dir);
        $image_count = $this->_import_images_from_tmpdir($tmp_dir, $code);
        $page_count  = $this->_sync_pages_from_files($form_id, $code);
        $this->_sync_meta_file($form_id);

        $this->_cleanup_tmpdir($tmp_dir);

        $title = isset($meta['title']) ? (string) $meta['title'] : $code;
        $message = 'Formulaire « ' . html_escape($title) . ' » importé depuis l\'archive (' . $page_count . ' page(s)';
        $message .= $image_count > 0 ? ', ' . $image_count . ' image(s)).' : ').';
        $this->session->set_flashdata('forms_success', $message);
        redirect('forms_admin/edit/' . (int) $form_id);
    }

    /**
     * Rebuilds the form_pages DB mirror from the files actually on disk —
     * called after replace_all_from_dir() has installed an archive's pages,
     * since the DB row set must match the new file set exactly (old rows for
     * pages removed by the archive must not linger). Page titles come from
     * meta.json (already in place on disk at this point); content from
     * read_page(), which un-wraps the stored HTML5 document back to a bare
     * fragment. Shared by form_import_zip() and form_restore(). Returns the
     * number of pages created.
     */
    private function _sync_pages_from_files($form_id, $code) {
        foreach ($this->form_pages_model->get_form_pages((int) $form_id) as $existing) {
            $this->form_pages_model->delete_page((int) $existing['id']);
        }

        $meta = $this->forms_file_storage->read_meta($code);
        $titles_by_number = array();
        if ($meta && !empty($meta['pages']) && is_array($meta['pages'])) {
            foreach ($meta['pages'] as $pm) {
                $titles_by_number[(int) $pm['page_number']] = isset($pm['title']) ? (string) $pm['title'] : '';
            }
        }

        $by    = $this->dx_auth->get_username();
        $count = 0;
        foreach ($this->forms_file_storage->page_numbers($code) as $num) {
            $page_id = $this->form_pages_model->create_page(array(
                'form_id'      => (int) $form_id,
                'page_number'  => $num,
                'title'        => isset($titles_by_number[$num]) ? $titles_by_number[$num] : '',
                'content_html' => (string) $this->forms_file_storage->read_page($code, $num),
                'created_by'   => $by,
            ));
            if ($page_id) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Extracts an archive's optional images/ folder into the form's file
     * storage. Shared by form_import_zip() and form_restore(). Returns the
     * number of image files copied.
     */
    private function _import_images_from_tmpdir($tmp_dir, $code) {
        $images_src = $tmp_dir . '/images';
        if (!is_dir($images_src)) {
            return 0;
        }
        $count = 0;
        foreach (glob($images_src . '/*') as $file) {
            if (is_file($file)) {
                $this->forms_file_storage->write_image($code, basename($file), file_get_contents($file));
                $count++;
            }
        }
        return $count;
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
        if (!file_exists($meta_path) || empty(glob($tmp_dir . '/page*.html'))) {
            $this->_cleanup_tmpdir($tmp_dir);
            $this->session->set_flashdata('forms_error', 'meta.json ou pages HTML absents de l\'archive — ce n\'est pas une archive de formulaire GVV valide.');
            redirect('forms_admin/edit/' . (int) $form['id']);
            return;
        }

        $meta = json_decode(file_get_contents($meta_path), true);
        if (!is_array($meta)) {
            $this->_cleanup_tmpdir($tmp_dir);
            $this->session->set_flashdata('forms_error', 'meta.json invalide ou mal formé.');
            redirect('forms_admin/edit/' . (int) $form['id']);
            return;
        }

        // Update metadata — code, status and public_slug are never overwritten by an archive
        $this->forms_model->update_form((int) $form['id'], array(
            'title'           => isset($meta['title'])           ? (string) $meta['title']           : $form['title'],
            'description'     => isset($meta['description'])     ? (string) $meta['description']     : '',
            'css_scope'       => isset($meta['css_scope'])       ? (string) $meta['css_scope']       : '',
            'required_params' => isset($meta['required_params']) ? (string) $meta['required_params'] : $form['required_params'],
            'allow_upload_response' => !empty($meta['allow_upload_response']),
            'handler_class'   => isset($meta['handler_class']) ? $this->_validated_handler_class($meta['handler_class']) : null,
            'target_url'      => isset($meta['target_url'])   ? (string) $meta['target_url']   : '',
            'target_label'    => isset($meta['target_label']) ? (string) $meta['target_label'] : '',
            'updated_by'      => $this->dx_auth->get_username(),
        ));

        // Remove all existing images — the archive's images/ folder is the new full set
        foreach ($this->forms_file_storage->list_images($form['code']) as $existing_image) {
            $this->forms_file_storage->delete_image($form['code'], $existing_image);
        }

        $this->forms_file_storage->replace_all_from_dir($form['code'], $tmp_dir);
        $image_count = $this->_import_images_from_tmpdir($tmp_dir, $form['code']);
        $page_count  = $this->_sync_pages_from_files((int) $form['id'], $form['code']);
        $this->_sync_meta_file((int) $form['id']);

        $this->_cleanup_tmpdir($tmp_dir);

        $message = 'Contenu du formulaire remplacé : ' . $page_count . ' page(s)';
        $message .= $image_count > 0 ? ', ' . $image_count . ' image(s) importée(s).' : ' importée(s).';
        $this->session->set_flashdata('forms_success', $message);
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