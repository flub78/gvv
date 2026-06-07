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
        $this->load->model('form_fields_model');
        $this->load->model('form_submissions_model');
        $this->load->library('form_validation');
        $this->load->library('forms_validation');
        $this->load->library('forms_renderer');
        $this->load->library('upload');
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

        $pages = $this->form_pages_model->get_form_pages((int) $form['id']);
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

        $fields = $this->form_fields_model->get_page_fields((int) $current_page['id']);
        $old_values = $this->session->flashdata('forms_public_old_values') ?: array();
        $render_fields = $this->forms_renderer->normalize_fields_for_view(
            $fields,
            $old_values
        );

        $session_key_pilot      = 'forms_gvv_pilot_'      . md5($slug);
        $session_key_instructor = 'forms_gvv_instructor_'  . md5($slug);

        $get_pilot      = trim((string) $this->input->get('pilot_login'));
        $get_instructor = trim((string) $this->input->get('instructor_login'));
        if ($get_pilot      !== '') $this->session->set_userdata($session_key_pilot, $get_pilot);
        if ($get_instructor !== '') $this->session->set_userdata($session_key_instructor, $get_instructor);

        $pilot_login      = $this->session->userdata($session_key_pilot)      ?: '';
        $instructor_login = $this->session->userdata($session_key_instructor) ?: '';

        // Inject signature widgets and apply GVV prefill into page HTML.
        // The view applies html_entity_decode to content_html before rendering,
        // so we work on raw HTML here and store raw HTML back.
        $has_signature_widget = false;
        if (!empty($current_page['content_html'])) {
            $raw = html_entity_decode((string) $current_page['content_html'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $injected = $this->forms_renderer->inject_signature_widgets($raw, $has_signature_widget);
            $club_id = isset($form['club']) && $form['club'] !== null ? (int) $form['club'] : null;
            list($injected, ) = $this->_apply_gvv_prefill($injected, $pilot_login, $instructor_login, $club_id);
            $current_page['content_html'] = $injected;
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
        $page = $this->find_page_by_number($pages, $page_number);
        if (!$page) {
            show_error('Page de formulaire introuvable.', 404);
            return;
        }

        $fields = $this->form_fields_model->get_page_fields((int) $page['id']);
        $submitted_values = array();
        $file_field_keys = array();
        $signature_canvas_data = array(); // field_id => base64 string for canvas/text modes

        foreach ($fields as $field) {
            $key        = (string) $field['name'];
            $field_type = isset($field['field_type']) ? $field['field_type'] : 'text';

            if ($field_type === 'signature') {
                $sig_type = trim((string) $this->input->post($key . '_type'));
                if (!in_array($sig_type, array('canvas', 'text', 'file'), true)) {
                    $sig_type = 'canvas';
                }

                if ($sig_type === 'file') {
                    $file_field_keys[(int) $field['id']] = $key . '_file';
                    $uploaded_name = '';
                    if (isset($_FILES[$key . '_file']) && !empty($_FILES[$key . '_file']['name'])) {
                        $uploaded_name = (string) $_FILES[$key . '_file']['name'];
                    }
                    $submitted_values[(int) $field['id']] = $uploaded_name;
                } else {
                    $base64 = trim((string) $this->input->post($key));
                    $submitted_values[(int) $field['id']] = ($base64 !== '') ? '[signature]' : '';
                    if ($base64 !== '') {
                        $signature_canvas_data[(int) $field['id']] = $base64;
                    }
                }
                continue;
            }

            if ($field_type === 'file') {
                $file_field_keys[(int) $field['id']] = $key;
                $uploaded_name = '';
                if (isset($_FILES[$key]) && isset($_FILES[$key]['name']) && $_FILES[$key]['name'] !== '') {
                    $uploaded_name = (string) $_FILES[$key]['name'];
                }
                $submitted_values[(int) $field['id']] = $uploaded_name;
                continue;
            }

            $value = $this->input->post($key);
            if (is_array($value)) {
                $value = array_values($value);
            }
            $submitted_values[(int) $field['id']] = $value;
        }

        // Apply server-side lock: override submitted values for GVV-prefilled locked fields.
        $session_key_pilot      = 'forms_gvv_pilot_'      . md5($slug);
        $session_key_instructor = 'forms_gvv_instructor_'  . md5($slug);
        $pilot_login      = $this->session->userdata($session_key_pilot)      ?: '';
        $instructor_login = $this->session->userdata($session_key_instructor) ?: '';

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
                    $submitted_values[(int) $field['id']] = $locked_config[$fname];
                }
            }
        }

        $errors = $this->forms_validation->validate_fields($fields, $submitted_values);

        if (!empty($errors)) {
            $this->session->set_flashdata('forms_public_error', implode('<br>', $errors));
            $this->session->set_flashdata('forms_public_old_values', $submitted_values);
            redirect('forms/' . rawurlencode($slug) . '?page=' . (int) $page_number . $gvv_params);
            return;
        }

        $uploaded_files = array();

        // Process canvas/text signature fields (base64 → PNG file)
        foreach ($signature_canvas_data as $field_id => $base64) {
            $result = $this->save_signature_canvas((int) $field_id, $base64);
            if ($result) {
                $uploaded_files[] = $result;
                $submitted_values[$field_id] = $result['original_name'];
            }
        }

        if (!empty($file_field_keys)) {
            $upload_result = $this->process_uploaded_files($form, $file_field_keys);
            if (!empty($upload_result['errors'])) {
                $this->session->set_flashdata('forms_public_error', implode('<br>', $upload_result['errors']));
                $this->session->set_flashdata('forms_public_old_values', $submitted_values);
                redirect('forms/' . rawurlencode($slug) . '?page=' . (int) $page_number . $gvv_params);
                return;
            }

            $uploaded_files = array_merge($uploaded_files, $upload_result['files']);
            foreach ($upload_result['files'] as $uploaded_file) {
                $field_id = (int) $uploaded_file['field_id'];
                $submitted_values[$field_id] = $uploaded_file['original_name'];
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

        $this->db->trans_start();

        $submission_id = $this->form_submissions_model->create_submission(array(
            'form_id'         => (int) $form['id'],
            'status'          => 'submitted',
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

        $submission = $this->form_submissions_model->get_by_id((int) $submission_id);
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

    private function find_page_by_number(array $pages, $page_number) {
        foreach ($pages as $page) {
            if ((int) $page['page_number'] === (int) $page_number) {
                return $page;
            }
        }
        return false;
    }

    private function process_uploaded_files($form, array $file_field_keys) {
        $errors = array();
        $saved_files = array();

        $relative_dir = $this->upload_base_dir . '/' . date('Y/m');
        $absolute_dir = FCPATH . $relative_dir;

        if (!is_dir($absolute_dir) && !@mkdir($absolute_dir, 0775, true)) {
            return array(
                'files'   => array(),
                'errors'  => array('Impossible de preparer le repertoire de televersement.'),
            );
        }

        foreach ($file_field_keys as $field_id => $field_key) {
            if (!isset($_FILES[$field_key]) || empty($_FILES[$field_key]['name'])) {
                continue;
            }

            $config = array(
                'upload_path'   => $absolute_dir,
                'allowed_types' => 'pdf|jpg|jpeg|png|gif|webp|txt|csv|doc|docx|odt',
                'max_size'      => 10240,
                'encrypt_name'  => true,
            );

            $this->upload->initialize($config);
            if (!$this->upload->do_upload($field_key)) {
                $errors[] = html_escape('Upload impossible pour le champ fichier: ' . strip_tags($this->upload->display_errors('', '')));
                continue;
            }

            $data = $this->upload->data();
            $saved_files[] = array(
                'field_id'      => (int) $field_id,
                'original_name' => isset($data['client_name']) ? $data['client_name'] : $data['orig_name'],
                'stored_name'   => $data['file_name'],
                'mime_type'     => isset($data['file_type']) ? $data['file_type'] : null,
                'size_bytes'    => isset($data['file_size']) ? (int) round($data['file_size'] * 1024) : null,
                'storage_path'  => $relative_dir . '/' . $data['file_name'],
            );
        }

        return array(
            'files'  => $saved_files,
            'errors' => $errors,
        );
    }

    /**
     * Decode a base64 PNG string and save it as a file in the signatures upload dir.
     * Returns a file descriptor array compatible with save_submission_files(), or null on failure.
     */
    private function save_signature_canvas($field_id, $base64) {
        $png_data = @base64_decode($base64, true);
        if ($png_data === false || strlen($png_data) < 67) { // 67 bytes = minimal valid PNG header
            return null;
        }

        // Verify PNG magic bytes
        if (substr($png_data, 0, 8) !== "\x89PNG\r\n\x1a\n") {
            return null;
        }

        $relative_dir  = $this->upload_base_dir . '/' . date('Y/m');
        $absolute_dir  = FCPATH . $relative_dir;

        if (!is_dir($absolute_dir) && !@mkdir($absolute_dir, 0775, true)) {
            return null;
        }

        $stored_name   = 'sig_' . uniqid('', true) . '.png';
        $absolute_path = $absolute_dir . '/' . $stored_name;

        if (@file_put_contents($absolute_path, $png_data) === false) {
            return null;
        }

        return array(
            'field_id'      => (int) $field_id,
            'original_name' => 'signature.png',
            'stored_name'   => $stored_name,
            'mime_type'     => 'image/png',
            'size_bytes'    => strlen($png_data),
            'storage_path'  => $relative_dir . '/' . $stored_name,
        );
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
                ->where_in('name', array('FI ULM', 'FE ULM'))->get()->result_array();
            foreach ($rows as $row) {
                if ($row['name'] === 'FI ULM') $this->_event_type_map_ulm['fi_ulm'] = (int) $row['id'];
                if ($row['name'] === 'FE ULM') $this->_event_type_map_ulm['fe_ulm'] = (int) $row['id'];
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