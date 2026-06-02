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

        $data = array(
            'form'                => $form,
            'pages'               => $pages,
            'current_page'        => $current_page,
            'current_page_number' => $current_page_number,
            'page_count'          => $page_count,
            'fields'              => $fields,
            'render_fields'       => $render_fields,
            'error'               => $this->session->flashdata('forms_public_error') ?: '',
            'old_values'          => $old_values,
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

        foreach ($fields as $field) {
            $key        = (string) $field['name'];
            $field_type = isset($field['field_type']) ? $field['field_type'] : 'text';

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

        $errors = $this->forms_validation->validate_fields($fields, $submitted_values);

        if (!empty($errors)) {
            $this->session->set_flashdata('forms_public_error', implode('<br>', $errors));
            $this->session->set_flashdata('forms_public_old_values', $submitted_values);
            redirect('forms/' . rawurlencode($slug) . '?page=' . (int) $page_number);
            return;
        }

        $uploaded_files = array();
        if (!empty($file_field_keys)) {
            $upload_result = $this->process_uploaded_files($form, $file_field_keys);
            if (!empty($upload_result['errors'])) {
                $this->session->set_flashdata('forms_public_error', implode('<br>', $upload_result['errors']));
                $this->session->set_flashdata('forms_public_old_values', $submitted_values);
                redirect('forms/' . rawurlencode($slug) . '?page=' . (int) $page_number);
                return;
            }

            $uploaded_files = $upload_result['files'];
            foreach ($uploaded_files as $uploaded_file) {
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

        $submission_id = $this->form_submissions_model->create_submission(array(
            'form_id'         => (int) $form['id'],
            'status'          => 'submitted',
            'submitter_email' => $submitter_email,
            'submitter_name'  => $submitter_name,
            'source_ip'       => $this->input->ip_address(),
            'user_agent'      => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null,
            'values'          => $submitted_values,
        ));

        if (!$submission_id) {
            $this->session->set_flashdata('forms_public_error', 'Impossible d\'enregistrer votre reponse pour le moment.');
            $this->session->set_flashdata('forms_public_old_values', $submitted_values);
            redirect('forms/' . rawurlencode($slug) . '?page=' . (int) $page_number);
            return;
        }

        if (!empty($uploaded_files)) {
            $this->form_submissions_model->save_submission_files($submission_id, $uploaded_files);
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

    private function render_view($view, $data = array()) {
        load_bs_view('header', null, false);
        load_bs_view('menu', null, false);
        load_bs_view('banner', null, false);
        return load_last_view($view, $data);
    }

}