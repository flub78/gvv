<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Public forms controller
 *
 * Provides anonymous access to published forms by public slug.
 */
class Forms_public extends CI_Controller {

    private $upload_base_dir = 'uploads/forms_submissions';
    private $_event_type_map_ulm = null;

    public function __construct() {
        parent::__construct();
        $this->load->helper('views');
        $this->load->model('forms_model');
        $this->load->model('form_pages_model');
        $this->load->model('form_submissions_model');
        $this->load->library('form_validation');
        $this->load->library('forms_validation');
        $this->load->library('forms_renderer');
        $this->load->library('forms_file_storage');
        $this->load->library('forms_field_parser');
        $this->load->library('upload');
    }

    /**
     * Overlays file-stored content onto DB-fetched rows (uploads/formulaires/
     * is the source of truth for form content — see doc/prds/remplissage_formulaires_prd.md
     * EF2-bis). Falls back to the DB value when the file is absent (defensive:
     * should not happen once migration 165 has run, kept for robustness).
     */
    private function _overlay_css_from_file(array $form) {
        $file_css = $this->forms_file_storage->read_css($form['code']);
        if ($file_css !== null) {
            $form['global_css'] = $this->forms_renderer->rewrite_shared_css_import($file_css);
        }
        return $form;
    }

    private function _overlay_pages_from_file($code, array $pages) {
        foreach ($pages as &$page) {
            $file_html = $this->forms_file_storage->read_page($code, (int) $page['page_number']);
            if ($file_html !== null) {
                $content = $this->forms_renderer->rewrite_local_image_urls($file_html, $code);
                $page['content_html'] = $this->forms_renderer->inject_required_markers($content);
            }
        }
        unset($page);
        return $pages;
    }

    /**
     * Parses every field of a form (all pages) given its numeric id — used
     * wherever a submission's form is only known by form_id (e.g. subform
     * status lookups), not by the slug already being visited.
     */
    private function _parse_form_fields_by_id($form_id) {
        $form = $this->forms_model->get_by_id((int) $form_id);
        if (!$form) {
            return array();
        }
        $pages = $this->form_pages_model->get_form_pages((int) $form_id);
        $pages = $this->_overlay_pages_from_file($form['code'], $pages);
        return $this->forms_field_parser->parse_form_pages($pages);
    }

    public function index($slug = '') {
        $slug = trim((string) $slug);
        if ($slug === '') {
            show_404();
            return;
        }

        $form = $this->forms_model->get_by_public_slug($slug);
        if (!$form || $form['status'] !== 'published') {
            show_404();
            return;
        }
        $form = $this->_overlay_css_from_file($form);

        $pages = $this->form_pages_model->get_form_pages((int) $form['id']);
        if (empty($pages)) {
            show_error('Ce formulaire ne contient aucune page publiee.', 404);
            return;
        }
        $pages = $this->_overlay_pages_from_file($form['code'], $pages);

        $page_count = count($pages);
        $current_page_number = (int) $this->input->get('page');
        if ($current_page_number <= 0) {
            $current_page_number = 1;
        }
        if ($current_page_number > $page_count) {
            $current_page_number = $page_count;
        }

        $current_page = null;
        foreach ($pages as $page) {
            if ((int) $page['page_number'] === $current_page_number) {
                $current_page = $page;
                break;
            }
        }
        if (!$current_page) {
            $current_page = $pages[0];
            $current_page_number = (int) $current_page['page_number'];
        }

        $fields = $this->forms_field_parser->parse_fields((string) $current_page['content_html']);
        $old_values     = $this->session->flashdata('forms_public_old_values') ?: array();
        $sig_canvas_data = $this->session->flashdata('forms_public_sig_canvas')  ?: array();
        $render_fields = $this->forms_renderer->normalize_fields_for_view(
            $fields,
            $old_values
        );

        $session_key_pilot        = 'forms_gvv_pilot_'      . md5($slug);
        $session_key_instructor   = 'forms_gvv_instructor_'  . md5($slug);
        $session_key_b_prefill    = 'forms_b_prefill_'       . md5($slug);
        $session_key_b_lock       = 'forms_b_lock_'           . md5($slug);
        $session_key_subject_type = 'forms_subject_type_'     . md5($slug);
        $session_key_subject_id   = 'forms_subject_id_'       . md5($slug);
        $session_key_link_token   = 'forms_link_token_'       . md5($slug);
        $session_key_subform_tokens = 'forms_subform_tokens_' . md5($slug);

        // Mechanism A — pilot/instructor login
        $get_pilot      = trim((string) $this->input->get('pilot_login'));
        $get_instructor = trim((string) $this->input->get('instructor_login'));
        if ($get_pilot      !== '') $this->session->set_userdata($session_key_pilot, $get_pilot);
        if ($get_instructor !== '') $this->session->set_userdata($session_key_instructor, $get_instructor);

        $pilot_login      = $this->session->userdata($session_key_pilot)      ?: '';
        $instructor_login = $this->session->userdata($session_key_instructor) ?: '';

        // Generic subject reference (subject_type / subject_id) — same pattern as pilot/instructor login.
        $get_subject_type = trim((string) $this->input->get('subject_type'));
        $get_subject_id   = trim((string) $this->input->get('subject_id'));
        if ($get_subject_type !== '') $this->session->set_userdata($session_key_subject_type, $get_subject_type);
        if ($get_subject_id   !== '') $this->session->set_userdata($session_key_subject_id, $get_subject_id);

        // Sub-form correlation token (Lot 11): present when this form is itself opened
        // as a sub-form of another master form. Stored per-slug like subject_type/id so
        // it survives page navigation and is written onto this form's own submission.
        $get_link_token = trim((string) $this->input->get('link_token'));
        if ($get_link_token !== '') $this->session->set_userdata($session_key_link_token, $get_link_token);

        // Mechanism B — arbitrary field values from URL query string
        // Reserved names that are never injected as field values.
        $b_reserved = array('page', 'token', 'subject_type', 'subject_id', 'link_token', 'lock', 'pilot_login', 'instructor_login');
        $all_get    = $this->input->get();
        if (is_array($all_get)) {
            $new_prefill = array();
            $new_lock    = array();
            foreach ($all_get as $key => $val) {
                $key = (string) $key;
                if (in_array($key, $b_reserved, true)) continue;
                $new_prefill[$key] = (string) $val;
            }
            $lock_raw = $this->input->get('lock');
            if ($lock_raw !== false && $lock_raw !== null) {
                $new_lock = is_array($lock_raw) ? array_values($lock_raw) : array((string) $lock_raw);
            }
            if (!empty($new_prefill)) $this->session->set_userdata($session_key_b_prefill, $new_prefill);
            if (!empty($new_lock))    $this->session->set_userdata($session_key_b_lock,    $new_lock);
        }

        $b_prefill = $this->session->userdata($session_key_b_prefill) ?: array();
        $b_lock    = $this->session->userdata($session_key_b_lock)    ?: array();

        // Inject signature widgets and apply GVV prefill into page HTML.
        // The view applies html_entity_decode to content_html before rendering,
        // so we work on raw HTML here and store raw HTML back.
        $club_id = isset($form['club']) && $form['club'] !== null ? (int) $form['club'] : null;
        $has_signature_widget = false;
        $has_subform_widget = false;
        $subform_tokens = $this->session->userdata($session_key_subform_tokens) ?: array();
        if (!empty($current_page['content_html'])) {
            $raw = html_entity_decode((string) $current_page['content_html'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            // GVV-sourced signature prefill; flash data (validation error) takes priority.
            $gvv_sig_prefill = $this->_collect_gvv_sig_prefill($raw, $pilot_login, $instructor_login, $club_id);
            $merged_sig = array_merge($gvv_sig_prefill, $sig_canvas_data);
            $injected = $this->forms_renderer->inject_signature_widgets($raw, $has_signature_widget, $merged_sig);
            list($injected, $subform_tokens) = $this->_apply_subform_widgets($injected, $slug, $subform_tokens, $has_subform_widget);
            $injected = $this->forms_renderer->inject_validation_script($injected);
            list($injected, ) = $this->_apply_gvv_prefill($injected, $pilot_login, $instructor_login, $club_id);
            if (!empty($b_prefill)) {
                $injected = $this->forms_renderer->inject_prefill_by_name($injected, $b_prefill, $b_lock);
            }
            if (!empty($old_values)) {
                $injected = $this->forms_renderer->repopulate_html_fields($injected, $fields, $old_values);
            }
            $current_page['content_html'] = $injected;
        }
        if (!empty($subform_tokens)) {
            $this->session->set_userdata($session_key_subform_tokens, $subform_tokens);
        }

        $data = array(
            'form'                   => $form,
            'pages'                  => $pages,
            'current_page'           => $current_page,
            'current_page_number'    => $current_page_number,
            'page_count'             => $page_count,
            'fields'                 => $fields,
            'render_fields'          => $render_fields,
            'error'                  => $this->session->flashdata('forms_public_error') ?: '',
            'old_values'             => $old_values,
            'has_signature_widget'   => $has_signature_widget,
            'pilot_login'            => $pilot_login,
            'instructor_login'       => $instructor_login,
            'has_pdf_template'       => $this->forms_file_storage->has_pdf_template($form['code']),
        );

        $this->render_view('forms_public/bs_show', $data);
    }

    public function submit($slug = '') {
        $slug = trim((string) $slug);
        if ($slug === '') {
            show_404();
            return;
        }

        $form = $this->forms_model->get_by_public_slug($slug);
        if (!$form || $form['status'] !== 'published') {
            show_404();
            return;
        }

        $page_number = (int) $this->input->post('page_number');
        if ($page_number <= 0) {
            $page_number = 1;
        }

        $pages = $this->form_pages_model->get_form_pages((int) $form['id']);
        $pages = $this->_overlay_pages_from_file($form['code'], $pages);
        $page = $this->find_page_by_number($pages, $page_number);
        if (!$page) {
            show_error('Page de formulaire introuvable.', 404);
            return;
        }

        $fields = $this->forms_field_parser->parse_fields((string) $page['content_html']);
        $submitted_values = array();
        $file_field_keys = array();          // field_name => $_FILES key
        $signature_canvas_data = array();    // field_name => base64 (for file saving on success)

        foreach ($fields as $field) {
            $key        = (string) $field['name'];
            $field_type = isset($field['field_type']) ? $field['field_type'] : 'text';

            if ($field_type === 'signature') {
                $sig_type = trim((string) $this->input->post($key . '_type'));
                if (!in_array($sig_type, array('canvas', 'text', 'file'), true)) {
                    $sig_type = 'canvas';
                }

                if ($sig_type === 'file') {
                    $file_field_keys[$key] = $key . '_file';
                    $uploaded_name = '';
                    if (isset($_FILES[$key . '_file']) && !empty($_FILES[$key . '_file']['name'])) {
                        $uploaded_name = (string) $_FILES[$key . '_file']['name'];
                    }
                    $submitted_values[$key] = $uploaded_name;
                } else {
                    $base64 = trim((string) $this->input->post($key));
                    $submitted_values[$key] = ($base64 !== '') ? '[signature]' : '';
                    if ($base64 !== '') {
                        $signature_canvas_data[$key] = $base64;
                    }
                }
                continue;
            }

            if ($field_type === 'file') {
                $file_field_keys[$key] = $key;
                $uploaded_name = '';
                if (isset($_FILES[$key]) && isset($_FILES[$key]['name']) && $_FILES[$key]['name'] !== '') {
                    $uploaded_name = (string) $_FILES[$key]['name'];
                }
                $submitted_values[$key] = $uploaded_name;
                continue;
            }

            $value = $this->input->post($key);
            if (is_array($value)) {
                $value = array_values($value);
            }
            $submitted_values[$key] = $value;
        }

        // Apply server-side lock: override submitted values for GVV-prefilled locked fields.
        $session_key_pilot      = 'forms_gvv_pilot_'      . md5($slug);
        $session_key_instructor = 'forms_gvv_instructor_'  . md5($slug);
        $pilot_login      = $this->session->userdata($session_key_pilot)      ?: '';
        $instructor_login = $this->session->userdata($session_key_instructor) ?: '';

        // Generic subject reference (subject_type / subject_id), set in index() from the URL.
        $subject_type = $this->session->userdata('forms_subject_type_' . md5($slug)) ?: null;
        $subject_id   = $this->session->userdata('forms_subject_id_'   . md5($slug)) ?: null;

        // Fallback: read from hidden POST inputs (set by bs_show.php) and refresh session.
        $post_pilot      = trim((string) $this->input->post('gvv_pilot_login'));
        $post_instructor = trim((string) $this->input->post('gvv_instructor_login'));
        if ($pilot_login === '' && $post_pilot !== '') {
            $pilot_login = $post_pilot;
            $this->session->set_userdata($session_key_pilot, $pilot_login);
        }
        if ($instructor_login === '' && $post_instructor !== '') {
            $instructor_login = $post_instructor;
            $this->session->set_userdata($session_key_instructor, $instructor_login);
        }

        $gvv_params = '';
        if ($pilot_login      !== '') $gvv_params .= '&pilot_login='      . rawurlencode($pilot_login);
        if ($instructor_login !== '') $gvv_params .= '&instructor_login=' . rawurlencode($instructor_login);

        $club_id = isset($form['club']) && $form['club'] !== null ? (int) $form['club'] : null;
        $raw_page_html = html_entity_decode((string) $page['content_html'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $locked_config = $this->_collect_locked_gvv_fields($raw_page_html, $pilot_login, $instructor_login, $club_id);
        if (!empty($locked_config)) {
            foreach ($fields as $field) {
                $fname = (string) $field['name'];
                if (isset($locked_config[$fname])) {
                    $submitted_values[$fname] = $locked_config[$fname];
                }
            }
        }

        // Mechanism B server-side lock: override submitted values for URL-prefilled locked fields.
        $b_prefill = $this->session->userdata('forms_b_prefill_' . md5($slug)) ?: array();
        $b_lock    = $this->session->userdata('forms_b_lock_'    . md5($slug)) ?: array();
        if (!empty($b_prefill) && !empty($b_lock)) {
            $b_lock_set = array_flip($b_lock);
            foreach ($fields as $field) {
                $fname = (string) $field['name'];
                if (isset($b_lock_set[$fname]) && array_key_exists($fname, $b_prefill)) {
                    $submitted_values[$fname] = $b_prefill[$fname];
                }
            }
        }

        $errors = $this->forms_validation->validate_fields($fields, $submitted_values);

        // Required sub-form widgets (Lot 11) can live on any page of a multi-page
        // master, not just the one being submitted here — check across all pages.
        $subform_tokens = $this->session->userdata('forms_subform_tokens_' . md5($slug)) ?: array();
        $errors = array_merge($errors, $this->_validate_required_subforms($pages, $subform_tokens));

        if (!empty($errors)) {
            $this->session->set_flashdata('forms_public_error', implode('<br>', $errors));
            $this->session->set_flashdata('forms_public_old_values', $submitted_values);
            if (!empty($signature_canvas_data)) {
                $this->session->set_flashdata('forms_public_sig_canvas', $signature_canvas_data);
            }
            redirect('forms/' . rawurlencode($slug) . '?page=' . (int) $page_number . $gvv_params);
            return;
        }

        $uploaded_files = array();

        // Process canvas/text signature fields (base64 → PNG file)
        foreach ($signature_canvas_data as $field_name => $base64) {
            $result = $this->forms_renderer->make_signature_file($base64, $this->upload_base_dir, $field_name);
            if ($result) {
                $uploaded_files[] = $result;
                $submitted_values[$field_name] = $result['original_name'];
            }
        }

        if (!empty($file_field_keys)) {
            $upload_result = $this->forms_renderer->upload_submitted_files($file_field_keys, $this->upload_base_dir);
            if (!empty($upload_result['errors'])) {
                $this->session->set_flashdata('forms_public_error', implode('<br>', $upload_result['errors']));
                $this->session->set_flashdata('forms_public_old_values', $submitted_values);
                redirect('forms/' . rawurlencode($slug) . '?page=' . (int) $page_number . $gvv_params);
                return;
            }

            $uploaded_files = array_merge($uploaded_files, $upload_result['files']);
            foreach ($upload_result['files'] as $uploaded_file) {
                $submitted_values[(string) $uploaded_file['widget_name']] = $uploaded_file['original_name'];
            }
        }

        $submitter_email = '';
        $submitter_name  = '';
        foreach ($fields as $field) {
            $role = isset($field['gvv_role']) ? (string) $field['gvv_role'] : '';
            if ($role === 'submitter_email' && $submitter_email === '') {
                $submitter_email = trim((string) $this->input->post((string) $field['name']));
            } elseif ($role === 'submitter_name' && $submitter_name === '') {
                $submitter_name = trim((string) $this->input->post((string) $field['name']));
            }
        }

        if ($this->dx_auth->is_logged_in() && ($submitter_name === '' || $submitter_email === '')) {
            $mlogin = $this->dx_auth->get_username();
            $membre = $this->db
                ->select('mnom, mprenom, memail')
                ->where('mlogin', $mlogin)
                ->get('membres')
                ->row_array();
            if ($membre) {
                if ($submitter_name === '') {
                    $submitter_name = trim($membre['mprenom'] . ' ' . $membre['mnom']);
                }
                if ($submitter_email === '') {
                    $submitter_email = (string) $membre['memail'];
                }
            }
        }

        // This form's own correlation token (Lot 11), set in index() when it is
        // opened as a sub-form of another master — written onto its own submission
        // so the master can find it back via Form_submissions_model::get_by_link_token().
        $own_link_token = $this->session->userdata('forms_link_token_' . md5($slug)) ?: null;

        $this->db->trans_start();

        $submission_id = $this->form_submissions_model->create_submission(array(
            'form_id'         => (int) $form['id'],
            'status'          => 'submitted',
            'subject_type'    => $subject_type,
            'subject_id'      => $subject_id !== null ? (int) $subject_id : null,
            'link_token'      => $own_link_token,
            'submitter_email' => $submitter_email,
            'submitter_name'  => $submitter_name,
            'source_ip'       => $this->input->ip_address(),
            'user_agent'      => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null,
            'values'          => $submitted_values,
        ));

        if (!empty($submission_id) && !empty($uploaded_files)) {
            $this->form_submissions_model->save_submission_files($submission_id, $uploaded_files);
        }

        $this->db->trans_complete();

        if (!$submission_id || $this->db->trans_status() === FALSE) {
            foreach ($uploaded_files as $uf) {
                $fp = FCPATH . ltrim((string) $uf['storage_path'], '/');
                if (is_file($fp)) {
                    @unlink($fp);
                }
            }
            $this->session->set_flashdata('forms_public_error', 'Impossible d\'enregistrer votre réponse pour le moment.');
            $this->session->set_flashdata('forms_public_old_values', $submitted_values);
            redirect('forms/' . rawurlencode($slug) . '?page=' . (int) $page_number . $gvv_params);
            return;
        }

        // Clear mechanism B session after successful submission.
        $this->session->unset_userdata('forms_b_prefill_' . md5($slug));
        $this->session->unset_userdata('forms_b_lock_'    . md5($slug));

        // Sub-form backfill (Lot 11): this master submission now exists, switch every
        // linked sub-form submission's subject_type/subject_id to point at it — unless
        // that sub-form submission already carries its own subject reference (it is
        // itself a category-3 form used standalone), in which case it is left untouched.
        $subform_tokens = $this->session->userdata('forms_subform_tokens_' . md5($slug)) ?: array();
        foreach ($subform_tokens as $subform_token) {
            $this->form_submissions_model->backfill_subject_from_link_token(
                $subform_token,
                'form_submission',
                (int) $submission_id
            );
        }
        $this->session->unset_userdata('forms_subform_tokens_' . md5($slug));

        $submission = $this->form_submissions_model->get_by_id((int) $submission_id);

        $handler_result = $this->_dispatch_handler(
            $form,
            (int) $submission_id,
            $subject_type,
            $subject_id !== null ? (int) $subject_id : null
        );
        if (!empty($handler_result['redirect_url'])) {
            redirect($handler_result['redirect_url']);
            return;
        }

        $uploaded_names = array();
        foreach ($uploaded_files as $uploaded_file) {
            if (!empty($uploaded_file['original_name'])) {
                $uploaded_names[] = $uploaded_file['original_name'];
            }
        }

        $data = array(
            'form'               => $form,
            'submission'         => $submission,
            'uploaded_file_names'=> $uploaded_names,
            'uploaded_files_count' => count($uploaded_names),
        );

        $this->render_view('forms_public/bs_thanks', $data);
    }

    public function upload_submit($slug = '') {
        $slug = trim((string) $slug);
        if ($slug === '') {
            show_404();
            return;
        }

        $form = $this->forms_model->get_by_public_slug($slug);
        if (!$form || $form['status'] !== 'published') {
            show_404();
            return;
        }

        $this->lang->load('forms');

        if (empty($form['allow_upload_response'])) {
            $this->session->set_flashdata('forms_public_error', $this->lang->line('forms_upload_error_disabled'));
            redirect('forms/' . rawurlencode($slug));
            return;
        }

        if (!isset($_FILES['upload_response_file']) || empty($_FILES['upload_response_file']['name'])) {
            $this->session->set_flashdata('forms_public_error', $this->lang->line('forms_upload_error_no_file'));
            redirect('forms/' . rawurlencode($slug));
            return;
        }

        $comment = trim((string) $this->input->post('upload_comment'));

        $relative_dir = 'uploads/reponses/' . (int) $form['id'];
        $absolute_dir = FCPATH . $relative_dir;
        if (!is_dir($absolute_dir)) {
            $old_umask = umask(0);
            $created = @mkdir($absolute_dir, 0775, true);
            umask($old_umask);
            if (!$created) {
                $this->session->set_flashdata('forms_public_error', $this->lang->line('forms_upload_error_storage'));
                redirect('forms/' . rawurlencode($slug));
                return;
            }
        }

        $submission_id = $this->form_submissions_model->create_submission(array(
            'form_id'           => (int) $form['id'],
            'status'            => 'submitted',
            'submission_method' => 'upload',
            'upload_comment'    => $comment !== '' ? $comment : null,
            'source_ip'         => $this->input->ip_address(),
            'user_agent'        => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null,
        ));

        if (!$submission_id) {
            $this->session->set_flashdata('forms_public_error', $this->lang->line('forms_upload_error_generic'));
            redirect('forms/' . rawurlencode($slug));
            return;
        }

        $original_name = (string) $_FILES['upload_response_file']['name'];
        $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $config = array(
            'upload_path'   => $absolute_dir,
            'allowed_types' => 'pdf|jpg|jpeg|png|gif|webp',
            'max_size'      => 10240,
            'file_name'     => 'reponse_' . $submission_id . ($ext !== '' ? '.' . $ext : ''),
            'overwrite'     => true,
        );
        $this->load->library('upload', $config);
        $this->upload->initialize($config);

        if (!$this->upload->do_upload('upload_response_file')) {
            $error = strip_tags($this->upload->display_errors('', ''));
            $this->form_submissions_model->delete_submission($submission_id);
            $this->session->set_flashdata('forms_public_error', $this->lang->line('forms_upload_error_file_type') . ' ' . $error);
            redirect('forms/' . rawurlencode($slug));
            return;
        }

        $upload_data = $this->upload->data();
        $file_path = $upload_data['full_path'];

        $this->load->library('file_compressor');
        $compression_result = $this->file_compressor->compress($file_path);
        if ($compression_result['success']) {
            $file_path = $compression_result['compressed_path'];
        }

        $mime = mime_content_type($file_path);
        if ($mime === 'application/pdf') {
            $this->load->library('pdf_thumbnail');
            $this->pdf_thumbnail->generate($file_path);
        }

        $this->form_submissions_model->save_submission_files($submission_id, array(array(
            'widget_name'   => 'uploaded_response',
            'original_name' => isset($upload_data['client_name']) ? $upload_data['client_name'] : $upload_data['orig_name'],
            'stored_name'   => $upload_data['file_name'],
            'mime_type'     => $mime,
            'size_bytes'    => isset($upload_data['file_size']) ? (int) round($upload_data['file_size'] * 1024) : null,
            'storage_path'  => $relative_dir . '/' . $upload_data['file_name'],
        )));

        $submission = $this->form_submissions_model->get_by_id($submission_id);
        $data = array(
            'form'                 => $form,
            'submission'           => $submission,
            'uploaded_file_names'  => array(isset($upload_data['client_name']) ? $upload_data['client_name'] : $upload_data['orig_name']),
            'uploaded_files_count' => 1,
        );

        $this->render_view('forms_public/bs_thanks', $data);
    }

    /**
     * Public AJAX endpoint (Lot 11 — sous-formulaires): returns whether a
     * submission exists for a given link_token, and a read-only summary of its
     * values if so. Same exposure level as any other public form link — no
     * authentication, the token itself is the only secret.
     */
    public function subform_status($token = '') {
        $token = trim((string) $token);

        header('Content-Type: application/json; charset=utf-8');

        if ($token === '' || !preg_match('/^[a-f0-9]{16,64}$/', $token)) {
            echo json_encode(array('found' => false));
            return;
        }

        $submission = $this->form_submissions_model->get_by_link_token($token);
        if (!$submission) {
            echo json_encode(array('found' => false));
            return;
        }

        echo json_encode(array(
            'found'   => true,
            'summary' => $this->form_submissions_model->get_submission_summary(
                (int) $submission['id'],
                $this->_parse_form_fields_by_id((int) $submission['form_id'])
            ),
        ));
    }

    /**
     * "Remplir à nouveau" (Lot 11): mints a fresh link_token for this widget on the
     * master form and redirects straight to the sub-form with it — a resubmission is
     * a brand new, independent sub-form submission, never an edit of the previous one.
     */
    public function subform_reset($master_slug = '', $widget_name = '') {
        $master_slug = trim((string) $master_slug);
        $widget_name = trim((string) $widget_name);
        $sub_slug    = trim((string) $this->input->get('form_slug'));

        if ($master_slug === '' || $widget_name === '' || $sub_slug === '') {
            show_404();
            return;
        }

        $session_key = 'forms_subform_tokens_' . md5($master_slug);
        $tokens = $this->session->userdata($session_key) ?: array();
        $tokens[$widget_name] = $this->_generate_link_token();
        $this->session->set_userdata($session_key, $tokens);

        redirect('forms/' . rawurlencode($sub_slug) . '?link_token=' . rawurlencode($tokens[$widget_name]));
    }

    /**
     * Serves an image asset stored under uploads/formulaires/{code}/images/.
     * The form directory denies direct static access (.htaccess), so images
     * referenced from a form's HTML (e.g. a logo) must go through this route.
     * No publish-status check: form content itself is meant to be openable as
     * a static artifact (EF2-bis), images follow the same rule.
     */
    public function image($code = '', $filename = '') {
        $code     = trim((string) $code);
        $filename = trim((string) $filename);
        if ($code === '' || $filename === '') {
            show_404();
            return;
        }

        $path       = $this->forms_file_storage->image_path($code, $filename);
        $images_dir = realpath($this->forms_file_storage->images_dir($code));
        $resolved   = realpath($path);

        if ($resolved === false || $images_dir === false || strpos($resolved, $images_dir) !== 0 || !is_file($resolved)) {
            show_404();
            return;
        }

        $info = getimagesize($resolved);
        $mime = $info ? $info['mime'] : 'application/octet-stream';

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($resolved));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: public, max-age=86400');
        readfile($resolved);
    }

    /**
     * Serves a form's blank PDF template (Lot 16 / EF18) —
     * uploads/formulaires/{code}/template.pdf — offered as a download on the
     * public page when allow_upload_response is enabled and a template has
     * been uploaded. Never served statically: same realpath containment
     * check as image(), the storage directory stays protected by its
     * deny-all .htaccess.
     */
    public function pdf_template($code = '') {
        $code = trim((string) $code);
        if ($code === '') {
            show_404();
            return;
        }

        $path      = $this->forms_file_storage->pdf_template_path($code);
        $form_dir  = realpath($this->forms_file_storage->form_dir($code));
        $resolved  = realpath($path);

        if ($resolved === false || $form_dir === false || strpos($resolved, $form_dir) !== 0 || !is_file($resolved)) {
            show_404();
            return;
        }

        header('Content-Type: application/pdf');
        header('Content-Length: ' . filesize($resolved));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: public, max-age=86400');
        readfile($resolved);
    }

    /**
     * Serves the CSS shared across several forms (uploads/formulaires/.commun/style.css).
     * A form's own style.css references it as the relative path
     * `.commun/style.css`, rewritten to this route by
     * Forms_renderer::rewrite_shared_css_import() at render time — see
     * "Ressources locales et partagées" in the design notes.
     */
    public function shared_css() {
        $css = $this->forms_file_storage->read_shared_css();

        header('Content-Type: text/css; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        echo $css !== null ? $css : '';
    }

    /**
     * Serves an image shared across several forms (uploads/formulaires/.commun/images/{filename}).
     * Referenced in a form's HTML as the relative path
     * `.commun/images/{filename}`, rewritten to this route by
     * Forms_renderer::rewrite_local_image_urls() at render time.
     */
    public function shared_image($filename = '') {
        $filename = trim((string) $filename);
        if ($filename === '') {
            show_404();
            return;
        }

        $path        = $this->forms_file_storage->shared_image_path($filename);
        $images_dir  = realpath($this->forms_file_storage->shared_images_dir());
        $resolved    = realpath($path);

        if ($resolved === false || $images_dir === false || strpos($resolved, $images_dir) !== 0 || !is_file($resolved)) {
            show_404();
            return;
        }

        $info = getimagesize($resolved);
        $mime = $info ? $info['mime'] : 'application/octet-stream';

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($resolved));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: public, max-age=86400');
        readfile($resolved);
    }

    /**
     * Instantiate and invoke the optional post-submission handler declared on
     * forms.handler_class (Lot 6, étape 6.3). No-op if handler_class is empty.
     * Errors (missing class, wrong interface, exception) are logged and
     * swallowed: the submission is already saved and stays reachable from admin.
     *
     * @return array|null ['redirect_url' => string|null, 'error' => string|null]
     */
    private function _dispatch_handler($form, $submission_id, $subject_type, $subject_id) {
        $class = isset($form['handler_class']) ? trim((string) $form['handler_class']) : '';
        if ($class === '') {
            return null;
        }

        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $class)) {
            log_message('error', 'forms_public: invalid handler_class "' . $class . '" for form ' . $form['id']);
            return null;
        }

        $interface_path = APPPATH . 'libraries/form_handlers/GvvFormHandlerInterface.php';
        $handler_path   = APPPATH . 'libraries/form_handlers/' . $class . '.php';

        if (!is_file($interface_path) || !is_file($handler_path)) {
            log_message('error', 'forms_public: handler class file not found for "' . $class . '" (form ' . $form['id'] . ')');
            return null;
        }

        require_once $interface_path;
        require_once $handler_path;

        if (!class_exists($class, false) || !in_array('GvvFormHandlerInterface', class_implements($class), true)) {
            log_message('error', 'forms_public: handler class "' . $class . '" does not implement GvvFormHandlerInterface');
            return null;
        }

        try {
            $handler = new $class();
            $result = $handler->after_submit((int) $submission_id, $subject_type, $subject_id);
        } catch (\Throwable $e) {
            log_message('error', 'forms_public: handler "' . $class . '" threw an exception: ' . $e->getMessage());
            return null;
        }

        if (!empty($result['error'])) {
            log_message('error', 'forms_public: handler "' . $class . '" reported an error for submission ' . $submission_id . ': ' . $result['error']);
        }

        return is_array($result) ? $result : null;
    }

    private function find_page_by_number(array $pages, $page_number) {
        foreach ($pages as $page) {
            if ((int) $page['page_number'] === (int) $page_number) {
                return $page;
            }
        }
        return false;
    }


    /**
     * Resolve config.* data-gvv-source attributes in page HTML and inject values.
     *
     * Returns [modified_html, locked_fields_map].
     * locked_fields_map: field_name => resolved_value for data-gvv-lock="true" fields.
     * Also strips data-gvv-* attributes from the rendered output and adds readonly on locked inputs.
     */
    private function _apply_config_prefill($html, $club_id) {
        if (strpos($html, 'data-gvv-source') === false) {
            return array($html, array());
        }

        $this->load->model('form_config_params_model');
        $locked_fields = array();

        $result = preg_replace_callback(
            '/<input(\s[^>]*)>/is',
            function ($m) use ($club_id, &$locked_fields) {
                $attrs = $m[1];

                if (!preg_match('/\bdata-gvv-source=["\']config\.([a-zA-Z0-9_]+)["\']/', $attrs, $src)) {
                    return $m[0];
                }
                $param_key = $src[1];

                $value = $this->form_config_params_model->resolve($param_key, $club_id);
                if ($value === null) {
                    $value = '';
                }

                $field_name = '';
                if (preg_match('/\bname=["\']([^"\']+)["\']/', $attrs, $nm)) {
                    $field_name = $nm[1];
                }

                $lock = (bool) preg_match('/\bdata-gvv-lock=["\']true["\']/', $attrs);
                if ($lock && $field_name !== '') {
                    $locked_fields[$field_name] = $value;
                }

                // Strip all data-gvv-* attributes
                $clean = preg_replace('/\s+data-gvv-[a-z-]+=["\'][^"\']*["\']/', '', $attrs);
                // Remove any pre-existing value attribute to avoid duplication
                $clean = preg_replace('/\s+value=["\'][^"\']*["\']/', '', $clean);

                if ($lock) {
                    $clean .= ' readonly';
                }

                $esc = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                return '<input' . $clean . ' value="' . $esc . '">';
            },
            $html
        );

        return array($result !== null ? $result : $html, $locked_fields);
    }

    /**
     * Parse page HTML and return a map of field_name => resolved_value
     * for every input that has both data-gvv-source="config.*" and data-gvv-lock="true".
     * Used server-side on submit to override the submitted value regardless of what the browser sent.
     */
    private function _collect_locked_config_fields($html, $club_id) {
        $locked = array();
        if (strpos($html, 'data-gvv-source') === false || strpos($html, 'data-gvv-lock') === false) {
            return $locked;
        }

        $this->load->model('form_config_params_model');

        preg_match_all('/<input(\s[^>]*)>/is', $html, $inputs);
        foreach ($inputs[1] as $attrs) {
            if (!preg_match('/\bdata-gvv-lock=["\']true["\']/', $attrs)) {
                continue;
            }
            if (!preg_match('/\bdata-gvv-source=["\']config\.([a-zA-Z0-9_]+)["\']/', $attrs, $src)) {
                continue;
            }
            if (!preg_match('/\bname=["\']([^"\']+)["\']/', $attrs, $nm)) {
                continue;
            }

            $value = $this->form_config_params_model->resolve($src[1], $club_id);
            $locked[$nm[1]] = $value !== null ? $value : '';
        }

        return $locked;
    }

    /**
     * Apply all GVV data-gvv-source prefill to page HTML.
     * Handles config.*, club.*, date.*, member.*, instructor.*, member.event.*, instructor.event.*
     * Returns [modified_html, locked_fields_map].
     */
    private function _apply_gvv_prefill($html, $pilot_login, $instructor_login, $club_id) {
        if (strpos($html, 'data-gvv-source') === false) {
            return array($html, array());
        }

        $this->load->model('form_config_params_model');
        $locked_fields = array();

        $result = preg_replace_callback(
            '/<input(\s[^>]*)>/is',
            function ($m) use ($pilot_login, $instructor_login, $club_id, &$locked_fields) {
                $attrs = $m[1];
                if (!preg_match('/\bdata-gvv-source=["\']([^"\']+)["\']/', $attrs, $src)) {
                    return $m[0];
                }
                $source = $src[1];

                $value = $this->_resolve_gvv_source($source, $pilot_login, $instructor_login, $club_id);
                if ($value === null) {
                    return $m[0];
                }

                $field_name = '';
                if (preg_match('/\bname=["\']([^"\']+)["\']/', $attrs, $nm)) {
                    $field_name = $nm[1];
                }

                $lock = (bool) preg_match('/\bdata-gvv-lock=["\']true["\']/', $attrs);
                if ($lock && $field_name !== '') {
                    $locked_fields[$field_name] = $value;
                }

                $clean = preg_replace('/\s+data-gvv-[a-z-]+=["\'][^"\']*["\']/', '', $attrs);
                $clean = preg_replace('/\s+value=["\'][^"\']*["\']/', '', $clean);

                if ($lock) {
                    $clean .= ' readonly';
                }

                $esc = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
                return '<input' . $clean . ' value="' . $esc . '">';
            },
            $html
        );

        return array($result !== null ? $result : $html, $locked_fields);
    }

    /**
     * Collect locked GVV fields for server-side enforcement on submit.
     */
    private function _collect_locked_gvv_fields($html, $pilot_login, $instructor_login, $club_id) {
        $locked = array();
        if (strpos($html, 'data-gvv-source') === false || strpos($html, 'data-gvv-lock') === false) {
            return $locked;
        }

        $this->load->model('form_config_params_model');

        preg_match_all('/<input(\s[^>]*)>/is', $html, $inputs);
        foreach ($inputs[1] as $attrs) {
            if (!preg_match('/\bdata-gvv-lock=["\']true["\']/', $attrs)) continue;
            if (!preg_match('/\bdata-gvv-source=["\']([^"\']+)["\']/', $attrs, $src)) continue;
            if (!preg_match('/\bname=["\']([^"\']+)["\']/', $attrs, $nm)) continue;

            $value = $this->_resolve_gvv_source($src[1], $pilot_login, $instructor_login, $club_id);
            if ($value !== null) {
                $locked[$nm[1]] = (string) $value;
            }
        }

        return $locked;
    }

    /**
     * Collect GVV-sourced signature prefill values for signature widgets.
     *
     * Finds <div data-gvv-type="signature" data-gvv-name="..." data-gvv-source="...">
     * elements in raw page HTML, resolves the source to a file path, reads the file
     * from disk, and returns array(field_name => base64_string).
     */
    private function _collect_gvv_sig_prefill($raw_html, $pilot_login, $instructor_login, $club_id) {
        $sig_prefill = array();
        if (strpos($raw_html, 'data-gvv-source') === false) {
            return $sig_prefill;
        }

        $this->load->model('form_config_params_model');

        preg_match_all('/<div([^>]*\bdata-gvv-type=["\']signature["\'][^>]*)>/i', $raw_html, $matches);
        foreach ($matches[1] as $attrs) {
            if (!preg_match('/\bdata-gvv-source=["\']([^"\']+)["\']/', $attrs, $src)) continue;
            if (!preg_match('/\bdata-gvv-name=["\']([^"\']+)["\']/', $attrs, $nm)) continue;

            $path = $this->_resolve_gvv_source($src[1], $pilot_login, $instructor_login, $club_id);
            if ($path === null || $path === '') continue;

            $abs_path = FCPATH . ltrim((string) $path, '/');
            if (!file_exists($abs_path)) continue;

            $data = @file_get_contents($abs_path);
            if ($data === false || $data === '') continue;

            $sig_prefill[$nm[1]] = base64_encode($data);
        }

        return $sig_prefill;
    }

    /**
     * Resolve/render sub-form widgets (Lot 11 — sous-formulaires) found in a master
     * page's HTML. Mints or reuses a link_token per widget name (persisted in the
     * caller's session token map), looks up whether a linked sub-form submission
     * already exists, and delegates the actual HTML swap to Forms_renderer.
     *
     * Returns [modified_html, updated_tokens_map].
     */
    private function _apply_subform_widgets($html, $master_slug, array $tokens, &$has_widget) {
        $has_widget = false;
        if (strpos($html, 'data-gvv-type') === false || strpos($html, 'subform') === false) {
            return array($html, $tokens);
        }

        preg_match_all('/<div([^>]*)\bdata-gvv-type=["\']subform["\']([^>]*)>/i', $html, $divs, PREG_SET_ORDER);
        if (empty($divs)) {
            return array($html, $tokens);
        }

        $widget_state = array();
        foreach ($divs as $div) {
            $attrs = $div[1] . $div[2];

            if (!preg_match('/\bdata-gvv-name=["\']([^"\']+)["\']/', $attrs, $nm)) continue;
            $name = trim($nm[1]);
            if ($name === '' || isset($widget_state[$name])) continue;

            if (!preg_match('/\bdata-gvv-form-slug=["\']([^"\']+)["\']/', $attrs, $sl)) continue;
            $sub_slug = trim($sl[1]);
            if ($sub_slug === '') continue;

            if (empty($tokens[$name])) {
                $tokens[$name] = $this->_generate_link_token();
            }
            $token = $tokens[$name];

            $submission = $this->form_submissions_model->get_by_link_token($token);
            $status  = $submission ? 'submitted' : 'empty';
            $summary = $submission
                ? $this->form_submissions_model->get_submission_summary(
                    (int) $submission['id'],
                    $this->_parse_form_fields_by_id((int) $submission['form_id'])
                  )
                : array();

            $widget_state[$name] = array(
                'sub_url'    => site_url('forms/' . rawurlencode($sub_slug)) . '?link_token=' . rawurlencode($token),
                'verify_url' => site_url('forms/subform-status/' . rawurlencode($token)),
                'reset_url'  => site_url('forms/subform-reset/' . rawurlencode($master_slug) . '/' . rawurlencode($name))
                                . '?form_slug=' . rawurlencode($sub_slug),
                'status'     => $status,
                'summary'    => $summary,
            );
        }

        if (empty($widget_state)) {
            return array($html, $tokens);
        }

        $injected = $this->forms_renderer->inject_subform_widgets($html, $has_widget, $widget_state);
        return array($injected !== null ? $injected : $html, $tokens);
    }

    private function _generate_link_token() {
        return bin2hex(random_bytes(16));
    }

    /**
     * Server-side enforcement of required sub-form widgets (Lot 11), across every
     * page of the master — a required widget on an earlier page than the one being
     * submitted must still block the final submission.
     */
    private function _validate_required_subforms(array $pages, array $tokens) {
        $errors = array();

        foreach ($pages as $page) {
            $html = html_entity_decode((string) $page['content_html'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (strpos($html, 'data-gvv-type') === false || strpos($html, 'subform') === false) {
                continue;
            }

            preg_match_all('/<div([^>]*)\bdata-gvv-type=["\']subform["\']([^>]*)>(.*?)<\/div>/is', $html, $divs, PREG_SET_ORDER);
            foreach ($divs as $div) {
                $attrs = $div[1] . $div[2];
                if (!preg_match('/\bdata-gvv-required=["\']?true["\']?/i', $attrs)) continue;
                if (!preg_match('/\bdata-gvv-name=["\']([^"\']+)["\']/', $attrs, $nm)) continue;
                $name = trim($nm[1]);
                if ($name === '') continue;

                $label = trim(strip_tags($div[3]));
                if ($label === '') $label = $name;

                $token = isset($tokens[$name]) ? $tokens[$name] : '';
                $submission = $token !== '' ? $this->form_submissions_model->get_by_link_token($token) : null;
                if (!$submission) {
                    $errors[] = 'Le sous-formulaire "' . html_escape($label) . '" doit être rempli et vérifié.';
                }
            }
        }

        return $errors;
    }

    /**
     * Dispatch a data-gvv-source string to the correct resolver.
     */
    private function _resolve_gvv_source($source, $pilot_login, $instructor_login, $club_id) {
        $parts = explode('.', $source, 4);
        $ns = isset($parts[0]) ? $parts[0] : '';

        switch ($ns) {
            case 'config':
                if (empty($parts[1])) return null;
                return $this->form_config_params_model->resolve($parts[1], $club_id);

            case 'club':
                return $this->_resolve_club_source(isset($parts[1]) ? $parts[1] : '');

            case 'date':
                return $this->_resolve_date_source(isset($parts[1]) ? $parts[1] : '');

            case 'user':
                $login = $this->dx_auth->get_username();
                if (isset($parts[1]) && $parts[1] === 'event') {
                    return $this->_resolve_event_source(isset($parts[2]) ? $parts[2] : '', isset($parts[3]) ? $parts[3] : '', $login);
                }
                return $this->_resolve_member_source(isset($parts[1]) ? $parts[1] : '', $login);

            case 'member':
                if (empty($pilot_login)) return null;
                if (isset($parts[1]) && $parts[1] === 'event') {
                    return $this->_resolve_event_source(isset($parts[2]) ? $parts[2] : '', isset($parts[3]) ? $parts[3] : '', $pilot_login);
                }
                return $this->_resolve_member_source(isset($parts[1]) ? $parts[1] : '', $pilot_login);

            case 'instructor':
                if (empty($instructor_login)) return null;
                if (isset($parts[1]) && $parts[1] === 'event') {
                    return $this->_resolve_event_source(isset($parts[2]) ? $parts[2] : '', isset($parts[3]) ? $parts[3] : '', $instructor_login);
                }
                return $this->_resolve_member_source(isset($parts[1]) ? $parts[1] : '', $instructor_login);
        }

        return null;
    }

    private function _resolve_member_source($field, $login) {
        if (empty($login)) return null;

        static $cache = array();
        if (!isset($cache[$login])) {
            $row = $this->db->select('mnom, mprenom, memail, mtelf, mtelm, madresse, cp, ville, mdaten, place_of_birth, signature_path')
                ->from('membres')
                ->where('mlogin', $login)
                ->get()->row_array();
            $cache[$login] = $row ?: false;
        }
        $m = $cache[$login];
        if (!$m) return null;

        switch ($field) {
            case 'nom':               return $m['mnom'];
            case 'prenom':            return $m['mprenom'];
            case 'nom_prenom':        return trim($m['mnom'] . ' ' . $m['mprenom']);
            case 'email':             return $m['memail'];
            case 'telephone':         return !empty($m['mtelf']) ? $m['mtelf'] : $m['mtelm'];
            case 'adresse':           return $m['madresse'];
            case 'code_postal':       return (string) $m['cp'];
            case 'ville':             return $m['ville'];
            case 'adresse_complete':  return trim($m['madresse'] . ', ' . $m['cp'] . ' ' . $m['ville']);
            case 'date_naissance':    return (!empty($m['mdaten']) && $m['mdaten'] !== '0000-00-00') ? date('d/m/Y', strtotime($m['mdaten'])) : '';
            case 'lieu_naissance':    return $m['place_of_birth'];
            case 'date_lieu_naissance':
                $d = (!empty($m['mdaten']) && $m['mdaten'] !== '0000-00-00') ? date('d/m/Y', strtotime($m['mdaten'])) : '';
                return $d . (!empty($m['place_of_birth']) ? ' à ' . $m['place_of_birth'] : '');
            case 'signature':         return $m['signature_path'];
        }
        return null;
    }

    private function _get_event_type_id($type_key) {
        static $static_map = array(
            'itp'                 => 43,
            'itv'                 => 44,
            'fi_spl'              => 51,
            'fe_spl'              => 52,
            'controle_competence' => 30,
            'visite_medicale'     => 26,
            'bpp'                 => 27,
            'spl'                 => 50,
        );
        if (isset($static_map[$type_key])) return $static_map[$type_key];

        if ($this->_event_type_map_ulm === null) {
            $this->_event_type_map_ulm = array();
            $rows = $this->db->select('id, name')->from('events_types')
                ->where_in('name', array('FI ULM', 'FE ULM', 'Test brevet ULM'))->get()->result_array();
            foreach ($rows as $row) {
                if ($row['name'] === 'FI ULM') $this->_event_type_map_ulm['fi_ulm'] = (int) $row['id'];
                if ($row['name'] === 'FE ULM') $this->_event_type_map_ulm['fe_ulm'] = (int) $row['id'];
                if ($row['name'] === 'Test brevet ULM') $this->_event_type_map_ulm['test_brevet_ulm'] = (int) $row['id'];
            }
        }
        return isset($this->_event_type_map_ulm[$type_key]) ? $this->_event_type_map_ulm[$type_key] : null;
    }

    private function _resolve_event_source($type_key, $field, $login) {
        if (empty($login) || empty($type_key) || empty($field)) return null;
        $etype_id = $this->_get_event_type_id($type_key);
        if ($etype_id === null) return null;

        $row = $this->db->select('ecomment, edate, date_expiration, signature_path')
            ->from('events')
            ->where('emlogin', $login)
            ->where('etype', $etype_id)
            ->order_by('edate', 'DESC')
            ->limit(1)
            ->get()->row_array();
        if (!$row) return null;

        switch ($field) {
            case 'numero':    return $row['ecomment'];
            case 'date':      return (!empty($row['edate'])           && $row['edate']           !== '0000-00-00') ? date('d/m/Y', strtotime($row['edate']))           : '';
            case 'expiry':    return (!empty($row['date_expiration']) && $row['date_expiration'] !== '0000-00-00') ? date('d/m/Y', strtotime($row['date_expiration'])) : '';
            case 'signature': return $row['signature_path'];
        }
        return null;
    }

    private function _resolve_club_source($field) {
        switch ($field) {
            case 'nom':     return $this->config->item('nom_club');
            case 'sigle':   return $this->config->item('sigle_club');
            case 'adresse': return $this->config->item('adresse_club');
            case 'ville':   return $this->config->item('ville_club');
            case 'email':   return $this->config->item('email_club');
        }
        return null;
    }

    private function _resolve_date_source($field) {
        switch ($field) {
            case 'today':    return date('Y-m-d');
            case 'today_fr': return date('d/m/Y');
            case 'year':     return date('Y');
        }
        return null;
    }

    private function render_view($view, $data = array()) {
        load_bs_view('header', null, false);
        load_bs_view('menu', null, false);
        load_bs_view('banner', null, false);
        return load_last_view($view, $data);
    }

}