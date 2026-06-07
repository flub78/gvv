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
                  (
                    SELECT GROUP_CONCAT(sv.value_text ORDER BY ff.sort_order SEPARATOR ' ')
                    FROM form_submission_values sv
                    JOIN form_fields ff ON ff.id = sv.field_id
                    WHERE sv.submission_id = s.id AND ff.is_identifier = 1
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
            $field_id = isset($file['field_id']) ? (int) $file['field_id'] : 0;
            if ($field_id <= 0 || empty($file['storage_path']) || empty($file['stored_name'])) {
                continue;
            }

            $this->db->insert($this->files_table, array(
                'submission_id' => (int) $submission_id,
                'field_id'      => $field_id,
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
            ->select('sf.*, f.name as field_name, f.label as field_label')
            ->from($this->files_table . ' sf')
            ->join('form_fields f', 'f.id = sf.field_id', 'left')
            ->where('sf.submission_id', (int) $submission_id)
            ->order_by('sf.id', 'ASC')
            ->get()
            ->result_array();
    }

    public function get_submission_file_by_id($file_id) {
        $row = $this->db
            ->select('sf.*, s.form_id, f.name as field_name, f.label as field_label')
            ->from($this->files_table . ' sf')
            ->join($this->table . ' s', 's.id = sf.submission_id', 'inner')
            ->join('form_fields f', 'f.id = sf.field_id', 'left')
            ->where('sf.id', (int) $file_id)
            ->get()
            ->row_array();

        return $row ?: false;
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

        foreach ($files as $file) {
            $path = FCPATH . ltrim((string) $file['storage_path'], '/');
            if (is_file($path)) {
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