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
            'link_token'      => isset($data['link_token']) && $data['link_token'] !== '' ? $data['link_token'] : null,
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
     * Returns the latest submitted submission carrying this link_token, or null.
     * Used to correlate a sub-form response (Lot 11) to its master before the
     * master itself is submitted — see get_current_for_subject() for the
     * equivalent lookup once the generic subject_type/subject_id pair applies.
     */
    public function get_by_link_token($link_token) {
        $link_token = trim((string) $link_token);
        if ($link_token === '') {
            return null;
        }

        $row = $this->db
            ->where('link_token', $link_token)
            ->where('status', 'submitted')
            ->order_by('created_at', 'desc')
            ->limit(1)
            ->get($this->table)
            ->row_array();

        return $row ?: null;
    }

    /**
     * Switch a sub-form submission's generic subject reference to the master
     * submission that just absorbed it (Lot 11), once the master's own id is
     * known. Only applies when the sub-form submission has no subject
     * reference of its own yet: if it is itself a category-3 form already
     * attached to a GVV entity (e.g. briefing_passager_ulm used standalone),
     * that attachment takes precedence and is left untouched — the link_token
     * on the row is enough on its own to prove the sub-form/master relationship.
     *
     * @return bool true if a row was actually updated.
     */
    public function backfill_subject_from_link_token($link_token, $subject_type, $subject_id) {
        $link_token = trim((string) $link_token);
        if ($link_token === '') {
            return false;
        }

        $this->db
            ->where('link_token', $link_token)
            ->where('subject_type', null)
            ->where('subject_id', null)
            ->update($this->table, array(
                'subject_type' => (string) $subject_type,
                'subject_id'   => (int) $subject_id,
                'updated_at'   => date('Y-m-d H:i:s'),
            ));

        return $this->db->affected_rows() > 0;
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

    /**
     * @param array $identifier_field_names field names flagged data-gvv-identifier
     *   in the form's HTML (parsed on demand by the caller via Forms_field_parser —
     *   the model has no access to file-based form content).
     * @param int $limit maximum rows, or 0 for no limit (full export)
     * @param string|null $date_from inclusive lower bound on submitted_at (Y-m-d)
     * @param string|null $date_to   inclusive upper bound on submitted_at (Y-m-d)
     */
    public function get_form_submissions($form_id, $limit = 100, $offset = 0, array $identifier_field_names = array(), $date_from = null, $date_to = null) {
        $where        = 's.form_id = ?';
        $where_params = array((int) $form_id);

        if (!empty($date_from)) {
            $where .= ' AND s.submitted_at >= ?';
            $where_params[] = $date_from . ' 00:00:00';
        }
        if (!empty($date_to)) {
            $where .= ' AND s.submitted_at <= ?';
            $where_params[] = $date_to . ' 23:59:59';
        }

        $limit_sql    = ((int) $limit > 0) ? ' LIMIT ? OFFSET ?' : '';
        $limit_params = ((int) $limit > 0) ? array((int) $limit, (int) $offset) : array();

        if (!empty($identifier_field_names)) {
            $placeholders = implode(',', array_fill(0, count($identifier_field_names), '?'));
            $sql = "SELECT s.*,
                      COALESCE(
                        (
                          SELECT GROUP_CONCAT(sv.value_text SEPARATOR ' ')
                          FROM form_submission_values sv
                          WHERE sv.submission_id = s.id AND sv.field_name IN ($placeholders)
                        ),
                        s.upload_comment
                      ) AS response_identifier
                    FROM {$this->table} s
                    WHERE $where
                    ORDER BY s.submitted_at DESC" . $limit_sql;
            $params = array_merge($identifier_field_names, $where_params, $limit_params);
        } else {
            $sql = "SELECT s.*, s.upload_comment AS response_identifier
                    FROM {$this->table} s
                    WHERE $where
                    ORDER BY s.submitted_at DESC" . $limit_sql;
            $params = array_merge($where_params, $limit_params);
        }
        return $this->db->query($sql, $params)->result_array();
    }

    /**
     * Values of the given fields for every submission of a form, as
     * [submission_id => [field_name => value_text]]. Used by the submissions
     * list to render one column per identifier field (fields flagged
     * data-gvv-identifier — resolved by the caller via Forms_field_parser).
     *
     * @param int $form_id
     * @param array $field_names field names to fetch (empty => empty result)
     * @return array
     */
    public function get_identifier_values($form_id, array $field_names) {
        if (empty($field_names)) {
            return array();
        }

        $rows = $this->db
            ->select('sv.submission_id, sv.field_name, sv.value_text')
            ->from($this->values_table . ' sv')
            ->join($this->table . ' s', 's.id = sv.submission_id')
            ->where('s.form_id', (int) $form_id)
            ->where_in('sv.field_name', $field_names)
            ->get()->result_array();

        $out = array();
        foreach ($rows as $row) {
            $out[(int) $row['submission_id']][$row['field_name']] = $row['value_text'];
        }
        return $out;
    }

    /**
     * Raw values for a submission, keyed by field_name (no label/type — the
     * model has no access to file-based form content to resolve those; the
     * caller enriches by matching field_name against Forms_field_parser output).
     */
    public function get_submission_values($submission_id) {
        return $this->db
            ->where('submission_id', (int) $submission_id)
            ->order_by('id', 'ASC')
            ->get($this->values_table)
            ->result_array();
    }

    /**
     * Read-only summary of a submission's field values, for display inside a
     * sub-form widget (Lot 11) or its status AJAX endpoint. Excludes file,
     * signature and subform fields (not meaningful as a flat label/value pair)
     * and empty values.
     *
     * @param array $fields form's field descriptors (Forms_field_parser::parse_form_pages()),
     *   used to resolve label/type and ordering — the model cannot parse HTML itself.
     */
    public function get_submission_summary($submission_id, array $fields = array()) {
        $values = $this->get_submission_values($submission_id);
        $fields_by_name = array();
        foreach ($fields as $f) {
            $fields_by_name[$f['name']] = $f;
        }

        $summary = array();
        foreach ($values as $row) {
            $name = (string) $row['field_name'];
            $field = isset($fields_by_name[$name]) ? $fields_by_name[$name] : null;
            $type = $field ? $field['field_type'] : 'text';
            if (in_array($type, array('file', 'signature', 'subform'), true)) {
                continue;
            }
            $text = trim((string) $row['value_text']);
            if ($text === '') {
                continue;
            }
            $summary[] = array(
                'label'      => $field && $field['label'] !== '' ? $field['label'] : $name,
                'value'      => $text,
                'sort_order' => $field ? $field['sort_order'] : PHP_INT_MAX,
            );
        }

        usort($summary, function ($a, $b) { return $a['sort_order'] <=> $b['sort_order']; });
        foreach ($summary as &$row) {
            unset($row['sort_order']);
        }
        unset($row);

        return $summary;
    }

    /**
     * Field_name => value_text map derived from a submission's values, for the
     * "export to a GVV creation form" button (Lot 12). Excludes file, signature
     * and subform fields (no exploitable value_text) and multi-valued fields
     * (JSON-array-shaped value_text, e.g. a <select multiple>) — no `champ[]=`
     * encoding in V1, see design notes § 18.
     *
     * @param array $fields form's field descriptors, see get_submission_summary().
     */
    public function get_export_query_params($submission_id, array $fields = array()) {
        $values = $this->get_submission_values($submission_id);
        $fields_by_name = array();
        foreach ($fields as $f) {
            $fields_by_name[$f['name']] = $f;
        }

        $params = array();
        foreach ($values as $row) {
            $name = isset($row['field_name']) ? trim((string) $row['field_name']) : '';
            if ($name === '') {
                continue;
            }
            $field = isset($fields_by_name[$name]) ? $fields_by_name[$name] : null;
            $type = $field ? $field['field_type'] : 'text';
            if (in_array($type, array('file', 'signature', 'subform'), true)) {
                continue;
            }
            $value_text = (string) $row['value_text'];
            if ($this->_looks_like_json_array($value_text)) {
                continue;
            }
            $params[$name] = $value_text;
        }

        return $params;
    }

    /**
     * Full export URL for a submission (Lot 12): $target_url (a relative
     * controller/method path, e.g. "membre/create", resolved via site_url() —
     * or an already-absolute URL, left as-is) with the query string above
     * appended. Returns the resolved target unchanged if there is nothing to append.
     *
     * @param array $fields form's field descriptors, see get_submission_summary().
     */
    public function build_export_url($target_url, $submission_id, array $fields = array()) {
        $target_url = trim((string) $target_url);
        $resolved = preg_match('#^https?://#i', $target_url) ? $target_url : site_url($target_url);

        $params = $this->get_export_query_params($submission_id, $fields);
        if (empty($params)) {
            return $resolved;
        }

        $separator = (strpos($resolved, '?') === false) ? '?' : '&';
        return $resolved . $separator . http_build_query($params);
    }

    private function _looks_like_json_array($value_text) {
        $trimmed = trim($value_text);
        if ($trimmed === '' || $trimmed[0] !== '[') {
            return false;
        }
        $decoded = json_decode($trimmed, true);
        return json_last_error() === JSON_ERROR_NONE && is_array($decoded);
    }

    public function save_submission_values($submission_id, array $values_by_field, $updated_by = null) {
        $submission = $this->get_by_id($submission_id);
        if (!$submission) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        foreach ($values_by_field as $field_name => $value) {
            $field_name = trim((string) $field_name);
            if ($field_name === '') {
                continue;
            }

            $value_text = $this->normalize_value($value);

            $existing = $this->db
                ->where('submission_id', (int) $submission_id)
                ->where('field_name', $field_name)
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
                    'field_name'    => $field_name,
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
            if (empty($file['widget_name']) || empty($file['storage_path']) || empty($file['stored_name'])) {
                continue;
            }

            $this->db->insert($this->files_table, array(
                'submission_id' => (int) $submission_id,
                'widget_name'   => (string) $file['widget_name'],
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

    /**
     * field_name/field_label in the result are aliases of widget_name and are
     * NOT resolved against form content — the caller enriches field_label by
     * matching field_name against Forms_field_parser output when needed
     * (the model has no access to file-based form content).
     */
    public function get_submission_files($submission_id) {
        return $this->db
            ->select('sf.id, sf.submission_id, sf.widget_name, sf.original_name, sf.stored_name, sf.mime_type, sf.size_bytes, sf.storage_path, sf.created_at, sf.updated_at, sf.widget_name as field_name', false)
            ->from($this->files_table . ' sf')
            ->where('sf.submission_id', (int) $submission_id)
            ->order_by('sf.id', 'ASC')
            ->get()
            ->result_array();
    }

    public function get_submission_file_by_id($file_id) {
        $row = $this->db
            ->select('sf.id, sf.submission_id, sf.widget_name, sf.original_name, sf.stored_name, sf.mime_type, sf.size_bytes, sf.storage_path, sf.created_at, sf.updated_at, s.form_id, sf.widget_name as field_name', false)
            ->from($this->files_table . ' sf')
            ->join($this->table . ' s', 's.id = sf.submission_id', 'inner')
            ->where('sf.id', (int) $file_id)
            ->get()
            ->row_array();

        return $row ?: false;
    }

    /**
     * Delete a single submission file: disk file first, then the DB row — mirrors the
     * order/pattern of delete_submission() but for one file (used when a file/signature
     * field is replaced during an in-place edit: the new file is saved and confirmed
     * first, then this removes the old one).
     */
    public function delete_submission_file($file_id) {
        $file = $this->db
            ->where('id', (int) $file_id)
            ->get($this->files_table)
            ->row_array();

        if (!$file) {
            return false;
        }

        $path = FCPATH . ltrim((string) $file['storage_path'], '/');
        if (is_file($path)) {
            @unlink($path);
        }

        $this->db->where('id', (int) $file_id)->delete($this->files_table);

        return true;
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