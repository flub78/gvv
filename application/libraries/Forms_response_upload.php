<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Stores a form response deposited as a single file (scan or photo of a paper
 * form) instead of being filled in online.
 *
 * Shared by the public upload endpoint (Forms_public::upload_submit) and by the
 * workflows that attach a deposited response to a GVV entity (Briefing_passager).
 * Access control and the forms.allow_upload_response opt-in belong to the callers:
 * the optional subject_type/subject_id must never come from an anonymous request.
 */
class Forms_response_upload {

    /** @var CI_Controller */
    private $CI;

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->model('form_submissions_model');
        $this->CI->lang->load('forms');
    }

    /**
     * Creates the submission and stores its file. On failure no submission is left behind.
     *
     * @param array  $form            Form row (id is used).
     * @param string $file_field      Name of the entry in $_FILES.
     * @param array  $submission_data Extra Form_submissions_model::create_submission() data
     *                                (upload_comment, subject_type, subject_id, ...).
     * @return array {submission_id: int|null, original_name: string|null, error: string|null}
     *               error is a translated message, ready to be displayed.
     */
    public function store(array $form, $file_field, array $submission_data = array()) {
        if (!isset($_FILES[$file_field]) || empty($_FILES[$file_field]['name'])) {
            return $this->_failure('forms_upload_error_no_file');
        }

        $relative_dir = 'uploads/reponses/' . (int) $form['id'];
        $absolute_dir = FCPATH . $relative_dir;
        if (!is_dir($absolute_dir)) {
            $old_umask = umask(0);
            $created = @mkdir($absolute_dir, 0775, true);
            umask($old_umask);
            if (!$created) {
                return $this->_failure('forms_upload_error_storage');
            }
        }

        $submission_id = $this->CI->form_submissions_model->create_submission(array_merge($submission_data, array(
            'form_id'           => (int) $form['id'],
            'status'            => 'submitted',
            'submission_method' => 'upload',
            'source_ip'         => $this->CI->input->ip_address(),
            'user_agent'        => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null,
        )));

        if (!$submission_id) {
            return $this->_failure('forms_upload_error_generic');
        }

        $original_name = (string) $_FILES[$file_field]['name'];
        $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $config = array(
            'upload_path'   => $absolute_dir,
            'allowed_types' => 'pdf|jpg|jpeg|png|gif|webp',
            'max_size'      => 10240,
            'file_name'     => 'reponse_' . $submission_id . ($ext !== '' ? '.' . $ext : ''),
            'overwrite'     => true,
        );
        $this->CI->load->library('upload', $config);
        $this->CI->upload->initialize($config);

        if (!$this->CI->upload->do_upload($file_field)) {
            $error = strip_tags($this->CI->upload->display_errors('', ''));
            $this->CI->form_submissions_model->delete_submission($submission_id);
            return $this->_failure('forms_upload_error_file_type', ' ' . $error);
        }

        $upload_data = $this->CI->upload->data();
        $file_path = $upload_data['full_path'];

        $this->CI->load->library('file_compressor');
        $compression_result = $this->CI->file_compressor->compress($file_path);
        if ($compression_result['success']) {
            $file_path = $compression_result['compressed_path'];
        }

        $mime = mime_content_type($file_path);
        if ($mime === 'application/pdf') {
            $this->CI->load->library('pdf_thumbnail');
            $this->CI->pdf_thumbnail->generate($file_path);
        }

        $client_name = isset($upload_data['client_name']) ? $upload_data['client_name'] : $upload_data['orig_name'];
        $saved = $this->CI->form_submissions_model->save_submission_files($submission_id, array(array(
            'widget_name'   => 'uploaded_response',
            'original_name' => $client_name,
            'stored_name'   => $upload_data['file_name'],
            'mime_type'     => $mime,
            'size_bytes'    => isset($upload_data['file_size']) ? (int) round($upload_data['file_size'] * 1024) : null,
            'storage_path'  => $relative_dir . '/' . $upload_data['file_name'],
        )));

        if (!$saved) {
            // The file is not recorded, so delete_submission() cannot find it.
            if ($mime === 'application/pdf') {
                $this->CI->pdf_thumbnail->delete_thumbnail($file_path);
            }
            @unlink($file_path);
            $this->CI->form_submissions_model->delete_submission($submission_id);
            return $this->_failure('forms_upload_error_generic');
        }

        return array('submission_id' => (int) $submission_id, 'original_name' => $client_name, 'error' => null);
    }

    private function _failure($lang_key, $detail = '') {
        return array(
            'submission_id' => null,
            'original_name' => null,
            'error'         => $this->CI->lang->line($lang_key) . $detail,
        );
    }
}

/* End of file Forms_response_upload.php */
/* Location: ./application/libraries/Forms_response_upload.php */
