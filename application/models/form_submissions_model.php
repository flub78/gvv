<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Form submissions model
 *
 * Handles submissions and per-field values for public form responses.
 */
class Form_submissions_model extends CI_Model {

    public $table = 'form_submissions';
    public $values_table = 'form_submission_values';
    public $files_table = 'form_submission_files';

    public function __construct() {
        parent::__construct();
    }

    public function create_submission(array $data) {
        if (empty($data['form_id'])) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $uuid = isset($data['submission_uuid']) && $data['submission_uuid'] !== ''
            ? $data['submission_uuid']
            : $this->generate_submission_uuid();

        $row = array(
            'form_id'         => (int) $data['form_id'],
            'submission_uuid' => $uuid,
            'status'          => isset($data['status']) ? $data['status'] : 'submitted',
            'submission_method' => isset($data['submission_method']) ? $data['submission_method'] : 'online',
            'upload_comment'  => isset($data['upload_comment']) ? $data['upload_comment'] : null,
            'subject_type'    => isset($data['subject_type']) ? $data['subject_type'] : null,
            'subject_id'      => isset($data['subject_id']) ? $data['subject_id'] : null,
            'submitter_email' => isset($data['submitter_email']) ? $data['submitter_email'] : null,
            'submitter_name'  => isset($data['submitter_name']) ? $data['submitter_name'] : null,
            'source_ip'       => isset($data['source_ip']) ? $data['source_ip'] : null,
            'user_agent'      => isset($data['user_agent']) ? $data['user_agent'] : null,
            'submitted_at'    => isset($data['submitted_at']) ? $data['submitted_at'] : $now,
            'created_at'      => $now,
            'updated_at'      => $now,
            'created_by'      => isset($data['created_by']) ? $data['created_by'] : null,
            'updated_by'      => isset($data['created_by']) ? $data['created_by'] : null,
        );

        $this->db->insert($this->table, $row);
        $id = $this->db->insert_id();

        if (!$id) {
            return false;
        }

        if (isset($data['values']) && is_array($data['values'])) {
            if (!$this->save_submission_values($id, $data['values'], isset($data['created_by']) ? $data['created_by'] : null)) {
                return false;
            }
        }

        return $id;
    }

    public function get_by_id($id) {
        $row = $this->db
            ->where('id', (int) $id)
            ->get($this->table)
            ->row_array();

        return $row ?: false;
    }

    public function get_by_uuid($submission_uuid) {
        $row = $this->db
            ->where('submission_uuid', $submission_uuid)
            ->get($this->table)
            ->row_array();

        return $row ?: false;
    }

    /**
     * Returns the current (latest submitted) submission for a generic subject
     * reference (subject_type / subject_id), or null if none exists.
     * Same lookup logic as archived_documents_model::get_briefing_by_vld().
     *
     * @param string   $subject_type
     * @param int      $subject_id
     * @param int|null $form_id Optional filter on a specific form.
     * @return array|null
     */
    public function get_current_for_subject($subject_type, $subject_id, $form_id = null) {
        $this->db->where('subject_type', (string) $subject_type);
        $this->db->where('subject_id', (int) $subject_id);
        $this->db->where('status', 'submitted');
        if ($form_id !== null) {
            $this->db->where('form_id', (int) $form_id);
        }
        $this->db->order_by('created_at', 'desc');
        $this->db->limit(1);
        $row = $this->db->get($this->table)->row_array();

        return $row ?: null;
    }

    /**
     * Returns briefing-passager-ulm submissions from the last N days, joined
     * with the vols_decouverte they are attached to (subject_type/subject_id).
     * Same shape/purpose as archived_documents_model::get_briefings_recent(),
     * for the new forms-based briefing mechanism (Lot 6, étape 6.6).
     * @param int $days
     * @return array
     */
    public function get_briefing_submissions_recent($days = 90) {
        $this->db->select(
            'form_submissions.id, form_submissions.created_at,
             vols_decouverte.date_vol, vols_decouverte.aerodrome,
             vols_decouverte.airplane_immat, vols_decouverte.beneficiaire,
             vols_decouverte.pilote'
        );
        $this->db->from('form_submissions');
        $this->db->join('forms', 'form_submissions.form_id = forms.id', 'inner');
        $this->db->join('vols_decouverte',
            "form_submissions.subject_id = vols_decouverte.id AND form_submissions.subject_type = 'vols_decouverte'",
            'inner');
        $this->db->where('forms.public_slug', 'briefing-passager-ulm');
        $this->db->where('form_submissions.status', 'submitted');
        $this->db->where("form_submissions.created_at >= DATE_SUB(NOW(), INTERVAL " . (int) $days . " DAY)", null, false);
        $this->db->order_by('form_submissions.created_at', 'desc');
        return $this->db->get()->result_array();
    }

    public function count_by_form(array $form_ids) {
        if (empty($form_ids)) {
            return array();
        }
        $rows = $this->db
            ->select('form_id, COUNT(*) as cnt')
            ->where_in('form_id', $form_ids)
            ->group_by('form_id')
            ->get($this->table)
            ->result_array();

        $counts = array();
        foreach ($rows as $row) {
            $counts[(int) $row['form_id']] = (int) $row['cnt'];
        }
        return $counts;
    }

    public function get_form_submissions($form_id, $limit = 100, $offset = 0) {
        $sql = "SELECT s.*,
                  COALESCE(
                    (
                      SELECT GROUP_CONCAT(sv.value_text ORDER BY ff.sort_order SEPARATOR ' ')
                      FROM form_submission_values sv
                      JOIN form_fields ff ON ff.id = sv.field_id
                      WHERE sv.submission_id = s.id AND ff.is_identifier = 1
                    ),
                    s.upload_comment
                  ) AS response_identifier
                FROM {$this->table} s
                WHERE s.form_id = ?
                ORDER BY s.submitted_at DESC
                LIMIT ? OFFSET ?";
        return $this->db->query($sql, array((int) $form_id, (int) $limit, (int) $offset))->result_array();
    }

    public function get_submission_values($submission_id) {
        return $this->db
            ->select('v.*, f.name as field_name, f.label as field_label, f.field_type')
            ->from($this->values_table . ' v')
            ->join('form_fields f', 'f.id = v.field_id', 'left')
            ->where('v.submission_id', (int) $submission_id)
            ->order_by('f.page_id', 'ASC')
            ->order_by('f.sort_order', 'ASC')
            ->order_by('v.id', 'ASC')
            ->get()
            ->result_array();
    }

    public function save_submission_values($submission_id, array $values_by_field, $updated_by = null) {
        $submission = $this->get_by_id($submission_id);
        if (!$submission) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        foreach ($values_by_field as $field_id => $value) {
            $field_id = (int) $field_id;
            if ($field_id <= 0) {
                continue;
            }

            $value_text = $this->normalize_value($value);

            $existing = $this->db
                ->where('submission_id', (int) $submission_id)
                ->where('field_id', $field_id)
                ->get($this->values_table)
                ->row_array();

            if ($existing) {
                $this->db
                    ->where('id', (int) $existing['id'])
                    ->update($this->values_table, array(
                        'value_text'  => $value_text,
                        'updated_at'  => $now,
                        'updated_by'  => $updated_by,
                    ));
            } else {
                $this->db->insert($this->values_table, array(
                    'submission_id' => (int) $submission_id,
                    'field_id'      => $field_id,
                    'value_text'    => $value_text,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                    'created_by'    => $updated_by,
                    'updated_by'    => $updated_by,
                ));
            }
        }

        $this->db
            ->where('id', (int) $submission_id)
            ->update($this->table, array(
                'updated_at' => $now,
                'updated_by' => $updated_by,
            ));

        return true;
    }

    public function set_submission_status($submission_id, $status, $updated_by = null) {
        if (!in_array($status, array('started', 'submitted', 'archived'), true)) {
            return false;
        }

        $update = array(
            'status'     => $status,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $updated_by,
        );

        if ($status === 'submitted') {
            $update['submitted_at'] = date('Y-m-d H:i:s');
        }

        $this->db->where('id', (int) $submission_id)->update($this->table, $update);
        return $this->db->affected_rows() >= 0;
    }

    public function save_submission_files($submission_id, array $files, $updated_by = null) {
        $submission = $this->get_by_id($submission_id);
        if (!$submission) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        foreach ($files as $file) {
            $field_id = isset($file['field_id']) && $file['field_id'] !== null ? (int) $file['field_id'] : null;
            if ($field_id !== null && $field_id <= 0) {
                continue;
            }
            if (empty($file['storage_path']) || empty($file['stored_name'])) {
                continue;
            }

            $this->db->insert($this->files_table, array(
                'submission_id' => (int) $submission_id,
                'field_id'      => $field_id,
                'widget_name'   => isset($file['widget_name']) ? (string) $file['widget_name'] : null,
                'original_name' => isset($file['original_name']) ? $file['original_name'] : '',
                'stored_name'   => $file['stored_name'],
                'mime_type'     => isset($file['mime_type']) ? $file['mime_type'] : null,
                'size_bytes'    => isset($file['size_bytes']) ? (int) $file['size_bytes'] : null,
                'storage_path'  => $file['storage_path'],
                'created_at'    => $now,
                'updated_at'    => $now,
                'created_by'    => $updated_by,
                'updated_by'    => $updated_by,
            ));
        }

        $this->db
            ->where('id', (int) $submission_id)
            ->update($this->table, array(
                'updated_at' => $now,
                'updated_by' => $updated_by,
            ));

        return true;
    }

    public function get_submission_files($submission_id) {
        return $this->db
            ->select('sf.id, sf.submission_id, sf.field_id, sf.widget_name, sf.original_name, sf.stored_name, sf.mime_type, sf.size_bytes, sf.storage_path, sf.created_at, sf.updated_at, COALESCE(f.name, sf.widget_name) as field_name, f.label as field_label', false)
            ->from($this->files_table . ' sf')
            ->join('form_fields f', 'f.id = sf.field_id', 'left')
            ->where('sf.submission_id', (int) $submission_id)
            ->order_by('sf.id', 'ASC')
            ->get()
            ->result_array();
    }

    public function get_submission_file_by_id($file_id) {
        $row = $this->db
            ->select('sf.id, sf.submission_id, sf.field_id, sf.widget_name, sf.original_name, sf.stored_name, sf.mime_type, sf.size_bytes, sf.storage_path, sf.created_at, sf.updated_at, s.form_id, COALESCE(f.name, sf.widget_name) as field_name, f.label as field_label', false)
            ->from($this->files_table . ' sf')
            ->join($this->table . ' s', 's.id = sf.submission_id', 'inner')
            ->join('form_fields f', 'f.id = sf.field_id', 'left')
            ->where('sf.id', (int) $file_id)
            ->get()
            ->row_array();

        return $row ?: false;
    }

    /**
     * Return the single uploaded-response file for a submission (widget_name = 'uploaded_response'),
     * or false if this submission has no such file.
     */
    public function get_uploaded_response_file($submission_id) {
        $row = $this->db
            ->where('submission_id', (int) $submission_id)
            ->where('widget_name', 'uploaded_response')
            ->get($this->files_table)
            ->row_array();

        return $row ?: false;
    }

    /**
     * Return uploaded-response files (widget_name='uploaded_response') for several
     * submissions at once, keyed by submission_id. Used by the admin submissions
     * list to render thumbnails without one query per row.
     */
    public function get_uploaded_response_files_for_submissions(array $submission_ids) {
        if (empty($submission_ids)) {
            return array();
        }

        $rows = $this->db
            ->where_in('submission_id', $submission_ids)
            ->where('widget_name', 'uploaded_response')
            ->get($this->files_table)
            ->result_array();

        $by_submission = array();
        foreach ($rows as $row) {
            $by_submission[(int) $row['submission_id']] = $row;
        }
        return $by_submission;
    }

    public function delete_submission($submission_id) {
        $submission_id = (int) $submission_id;
        $submission = $this->get_by_id($submission_id);
        if (!$submission) {
            return false;
        }

        $files = $this->db
            ->where('submission_id', $submission_id)
            ->get($this->files_table)
            ->result_array();

        $CI =& get_instance();
        $CI->load->library('pdf_thumbnail');

        foreach ($files as $file) {
            $path = FCPATH . ltrim((string) $file['storage_path'], '/');
            if (is_file($path)) {
                if ($file['widget_name'] === 'uploaded_response') {
                    $CI->pdf_thumbnail->delete_thumbnail($path);
                }
                @unlink($path);
            }
        }

        $this->db->where('submission_id', $submission_id)->delete($this->files_table);
        $this->db->where('submission_id', $submission_id)->delete($this->values_table);
        $this->db->where('id', $submission_id)->delete($this->table);

        return $this->db->affected_rows() >= 0;
    }

    private function normalize_value($value) {
        if (is_array($value)) {
            return json_encode($value);
        }
        if ($value === null) {
            return null;
        }
        return (string) $value;
    }

    private function generate_submission_uuid() {
        return uniqid('sub_', true);
    }
}