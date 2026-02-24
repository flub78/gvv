<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Model for archived_documents table
 *
 * Manages archived documents with versioning, expiration tracking, and alarm management.
 *
 * Expiration statuses:
 * - 'active': Document is valid (valid_until > today + alert_days OR no expiration)
 * - 'expiring_soon': Document expires within alert_days_before days
 * - 'expired': Document has expired (valid_until < today)
 * - 'missing': Required document type has no valid document for this pilot
 *
 * @package models
 * @see application/migrations/067_archived_documents.php
 */
class Archived_documents_model extends Common_Model {
    public $table = 'archived_documents';
    protected $primary_key = 'id';

    // Expiration status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRING_SOON = 'expiring_soon';
    const STATUS_EXPIRED = 'expired';
    const STATUS_MISSING = 'missing';

    // Validation status constants
    const STATUS_PENDING = 'pending';
    const STATUS_REJECTED = 'rejected';

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();
        $this->load->model('document_types_model');
    }

    /**
     * Returns paginated list for display
     * @param int $per_page Number of items per page
     * @param int $premier Offset
     * @param array $selection Filter criteria
     * @return array
     */
    public function select_page($per_page = 0, $premier = 0, $selection = []) {
        $this->db->select('archived_documents.id, archived_documents.pilot_login,
            archived_documents.original_filename, archived_documents.description,
            archived_documents.uploaded_at, archived_documents.valid_from,
            archived_documents.valid_until, archived_documents.alarm_disabled,
            archived_documents.is_current_version,
            archived_documents.file_path, archived_documents.mime_type,
            archived_documents.machine_immat,
            document_types.name as type_name, document_types.code as type_code,
            membres.mnom as pilot_nom, membres.mprenom as pilot_prenom,
            sections.nom as section_name');
        $this->db->from($this->table);
            $this->db->join('document_types', 'archived_documents.document_type_id = document_types.id', 'left');
        $this->db->join('membres', 'archived_documents.pilot_login = membres.mlogin', 'left');
        $this->db->join('sections', 'archived_documents.section_id = sections.id', 'left');

        if (!empty($selection)) {
            $this->db->where($selection);
        }

        // By default, show only current versions
        if (!isset($selection['is_current_version'])) {
            $this->db->where('archived_documents.is_current_version', 1);
        }

        $this->db->order_by('archived_documents.uploaded_at', 'desc');

        $query = $this->db->get();
        $select = $this->get_to_array($query);

        // Add computed expiration status
        foreach ($select as &$row) {
            $row['expiration_status'] = $this->compute_expiration_status($row);
        }

        $this->gvvmetadata->store_table("vue_archived_documents", $select);
        return $select;
    }

    /**
     * Returns filtered documents for alternate view
     * @param array $filters
     * @return array
     */
    public function get_filtered_documents($filters = array()) {
        $this->db->select('archived_documents.id, archived_documents.pilot_login,
            archived_documents.original_filename, archived_documents.description,
            archived_documents.uploaded_at, archived_documents.valid_from,
            archived_documents.valid_until, archived_documents.alarm_disabled,
            archived_documents.is_current_version,
            archived_documents.file_path, archived_documents.mime_type,
            archived_documents.validation_status,
            archived_documents.machine_immat,
            document_types.name as type_name, document_types.code as type_code,
            membres.mnom as pilot_nom, membres.mprenom as pilot_prenom,
            sections.nom as section_name');
        $this->db->from($this->table);
        $this->db->join('document_types', 'archived_documents.document_type_id = document_types.id', 'left');
        $this->db->join('membres', 'archived_documents.pilot_login = membres.mlogin', 'left');
        $this->db->join('sections', 'archived_documents.section_id = sections.id', 'left');

        $this->db->where('archived_documents.is_current_version', 1);

        if (!empty($filters['pilot_login'])) {
            $this->db->where('archived_documents.pilot_login', $filters['pilot_login']);
        }

        if (!empty($filters['section_id'])) {
            $section_id = (int)$filters['section_id'];
            // Le filtre section ne s'applique qu'aux documents de section.
            // Les documents pilotes (scope=pilot) et club (scope=club) sont toujours visibles.
            $this->db->where("(document_types.scope = 'pilot' OR document_types.scope = 'club' OR archived_documents.section_id = {$section_id})", null, false);
        }

        if (!empty($filters['document_type_id'])) {
            $this->db->where('archived_documents.document_type_id', $filters['document_type_id']);
        }

        if (!empty($filters['machine_immat'])) {
            $this->db->where('archived_documents.machine_immat', $filters['machine_immat']);
        }

        $expired = !empty($filters['expired']);
        $pending = !empty($filters['pending']);

        if ($expired && $pending) {
            $this->db->where("(archived_documents.validation_status = 'pending' OR (archived_documents.validation_status = 'approved' AND archived_documents.valid_until IS NOT NULL AND archived_documents.valid_until < CURDATE()))", null, false);
        } elseif ($pending) {
            $this->db->where('archived_documents.validation_status', 'pending');
        } elseif ($expired) {
            $this->db->where('archived_documents.validation_status', 'approved');
            $this->db->where('archived_documents.valid_until IS NOT NULL', null, false);
            $this->db->where('archived_documents.valid_until <', date('Y-m-d'));
        }

        $this->db->order_by('archived_documents.uploaded_at', 'desc');

        $query = $this->db->get();
        $results = $this->get_to_array($query);

        foreach ($results as &$row) {
            $row['expiration_status'] = $this->compute_expiration_status($row);
        }

        $this->gvvmetadata->store_table("vue_archived_documents", $results);
        return $results;
    }

    /**
     * Returns documents for a specific pilot
     * @param string $pilot_login Pilot login
     * @param bool $current_only Only return current versions
     * @return array
     */
    public function get_pilot_documents($pilot_login, $current_only = true) {
        $this->db->select('archived_documents.*, document_types.name as type_name,
            document_types.code as type_code, document_types.required,
            document_types.has_expiration, document_types.alert_days_before');
        $this->db->from($this->table);
        $this->db->join('document_types', 'archived_documents.document_type_id = document_types.id', 'left');
        $this->db->where('archived_documents.pilot_login', $pilot_login);

        if ($current_only) {
            $this->db->where('archived_documents.is_current_version', 1);
        }

        $this->db->order_by('document_types.display_order', 'asc');
        $this->db->order_by('archived_documents.uploaded_at', 'desc');

        $query = $this->db->get();
        $results = $this->get_to_array($query);

        // Add computed expiration status
        foreach ($results as &$row) {
            $row['expiration_status'] = $this->compute_expiration_status($row);
        }

        return $results;
    }

    /**
     * Returns documents for a specific section
     * @param int $section_id Section ID
     * @param bool $current_only Only return current versions
     * @return array
     */
    public function get_section_documents($section_id, $current_only = true) {
        $this->db->select('archived_documents.*, document_types.name as type_name,
            document_types.code as type_code');
        $this->db->from($this->table);
            $this->db->join('document_types', 'archived_documents.document_type_id = document_types.id', 'left');
        $this->db->where('archived_documents.section_id', $section_id);
            $this->db->where('(document_types.scope = "section" OR archived_documents.document_type_id IS NULL)', null, false);

        if ($current_only) {
            $this->db->where('archived_documents.is_current_version', 1);
        }

        $this->db->order_by('document_types.display_order', 'asc');

        $query = $this->db->get();
        $results = $this->get_to_array($query);

        foreach ($results as &$row) {
            $row['expiration_status'] = $this->compute_expiration_status($row);
        }

        return $results;
    }

    /**
     * Returns section documents for all sections (used when no active section is selected)
     * @param bool $current_only Only return current versions
     * @return array
     */
    public function get_all_section_documents($current_only = true) {
        $this->db->select('archived_documents.*, document_types.name as type_name,
            document_types.code as type_code, sections.nom as section_name');
        $this->db->from($this->table);
        $this->db->join('document_types', 'archived_documents.document_type_id = document_types.id', 'left');
        $this->db->join('sections', 'archived_documents.section_id = sections.id', 'left');
        $this->db->where('document_types.scope', 'section');

        if ($current_only) {
            $this->db->where('archived_documents.is_current_version', 1);
        }

        $this->db->order_by('sections.nom', 'asc');
        $this->db->order_by('document_types.display_order', 'asc');

        $query = $this->db->get();
        $results = $this->get_to_array($query);

        foreach ($results as &$row) {
            $row['expiration_status'] = $this->compute_expiration_status($row);
        }

        return $results;
    }

    /**
     * Returns club documents
     * @param bool $current_only Only return current versions
     * @return array
     */
    public function get_club_documents($current_only = true) {
        $this->db->select('archived_documents.*, document_types.name as type_name,
            document_types.code as type_code');
        $this->db->from($this->table);
            $this->db->join('document_types', 'archived_documents.document_type_id = document_types.id', 'left');
            $this->db->where('(document_types.scope = "club" OR archived_documents.document_type_id IS NULL)', null, false);

        if ($current_only) {
            $this->db->where('archived_documents.is_current_version', 1);
        }

        $this->db->order_by('document_types.display_order', 'asc');

        $query = $this->db->get();
        $results = $this->get_to_array($query);

        foreach ($results as &$row) {
            $row['expiration_status'] = $this->compute_expiration_status($row);
        }

        return $results;
    }

    /**
     * Returns documents not associated with any pilot (pilot_login IS NULL)
     * These are club or section documents without pilot assignment
     * @param bool $current_only Only return current versions
     * @return array
     */
    public function get_unassociated_documents($current_only = true) {
        $this->db->select('archived_documents.*, document_types.name as type_name,
            document_types.code as type_code, sections.nom as section_name');
        $this->db->from($this->table);
        $this->db->join('document_types', 'archived_documents.document_type_id = document_types.id', 'left');
        $this->db->join('sections', 'archived_documents.section_id = sections.id', 'left');
        $this->db->where('archived_documents.pilot_login IS NULL');

        if ($current_only) {
            $this->db->where('archived_documents.is_current_version', 1);
        }

        $this->db->order_by('document_types.display_order', 'asc');
        $this->db->order_by('archived_documents.uploaded_at', 'desc');

        $query = $this->db->get();
        $results = $this->get_to_array($query);

        foreach ($results as &$row) {
            $row['expiration_status'] = $this->compute_expiration_status($row);
        }

        return $results;
    }

    /**
     * Returns expired documents (for admin list)
     * Excludes documents with disabled alarms and non-approved documents
     * @return array
     */
    public function get_expired_documents() {
        $this->db->select('archived_documents.*, document_types.name as type_name,
            document_types.code as type_code, document_types.alert_days_before,
            membres.mnom as pilot_nom, membres.mprenom as pilot_prenom, membres.memail');
        $this->db->from($this->table);
        $this->db->join('document_types', 'archived_documents.document_type_id = document_types.id');
        $this->db->join('membres', 'archived_documents.pilot_login = membres.mlogin', 'left');
        $this->db->where('archived_documents.is_current_version', 1);
        $this->db->where('archived_documents.alarm_disabled', 0);
        $this->db->where('archived_documents.validation_status', 'approved');
        $this->db->where('archived_documents.valid_until IS NOT NULL', null, false);
        $this->db->where('archived_documents.valid_until <', date('Y-m-d'));
        $this->db->order_by('archived_documents.valid_until', 'asc');

        $query = $this->db->get();
        $results = $this->get_to_array($query);

        foreach ($results as &$row) {
            $row['expiration_status'] = self::STATUS_EXPIRED;
        }

        return $results;
    }

    /**
     * Returns documents expiring soon (for notifications)
     * Excludes documents with disabled alarms and non-approved documents
     * @return array
     */
    public function get_expiring_soon_documents() {
        $sql = "SELECT ad.*, dt.name as type_name, dt.code as type_code,
                dt.alert_days_before, m.mnom as pilot_nom, m.mprenom as pilot_prenom, m.memail
                FROM {$this->table} ad
                JOIN document_types dt ON ad.document_type_id = dt.id
                LEFT JOIN membres m ON ad.pilot_login = m.mlogin
                WHERE ad.is_current_version = 1
                AND ad.alarm_disabled = 0
                AND ad.validation_status = 'approved'
                AND ad.valid_until IS NOT NULL
                AND ad.valid_until >= CURDATE()
                AND ad.valid_until <= DATE_ADD(CURDATE(), INTERVAL dt.alert_days_before DAY)
                ORDER BY ad.valid_until ASC";

        $query = $this->db->query($sql);
        $results = $this->get_to_array($query);

        foreach ($results as &$row) {
            $row['expiration_status'] = self::STATUS_EXPIRING_SOON;
        }

        return $results;
    }

    /**
     * Returns required document types for which the pilot has no valid document.
     *
     * A type is considered "missing" if the pilot has no current, non-expired,
     * approved document of that type. Multiple instances of the same type
     * (unique_per_entity=0) are all checked: at least one valid instance
     * is sufficient to satisfy the requirement.
     *
     * @param string $pilot_login Pilot login
     * @param int|null $section_id Section ID for section-specific required types
     * @return array Missing document types
     */
    public function get_missing_documents($pilot_login, $section_id = null) {
        // Get required types
        $required_types = $this->document_types_model->get_required_pilot_types($section_id);

        // Get pilot's current documents (is_current_version=1, all instances)
        $pilot_docs = $this->get_pilot_documents($pilot_login, true);

        // For each type, track whether at least one valid and approved instance exists
        $covered_type_ids = array();
        foreach ($pilot_docs as $doc) {
            $is_approved = !isset($doc['validation_status']) || $doc['validation_status'] === 'approved';
            if ($doc['expiration_status'] !== self::STATUS_EXPIRED && $is_approved) {
                $covered_type_ids[$doc['document_type_id']] = true;
            }
        }

        // A required type is missing if no valid instance covers it
        $missing = array();
        foreach ($required_types as $type) {
            if (!isset($covered_type_ids[$type['id']])) {
                $type['expiration_status'] = self::STATUS_MISSING;
                $missing[] = $type;
            }
        }

        return $missing;
    }

    /**
     * Returns complete document status for a pilot (documents + missing)
     * @param string $pilot_login Pilot login
     * @param int|null $section_id Section ID
     * @return array With 'documents' and 'missing' keys
     */
    public function get_pilot_document_status($pilot_login, $section_id = null) {
        return array(
            'documents' => $this->get_pilot_documents($pilot_login, true),
            'missing' => $this->get_missing_documents($pilot_login, $section_id)
        );
    }

    /**
     * Computes expiration status for a document
     * @param array $doc Document data (must include valid_until and optionally alert_days_before)
     * @return string One of STATUS_* constants
     */
    public function compute_expiration_status($doc) {
        // Check validation status first
        if (isset($doc['validation_status'])) {
            if ($doc['validation_status'] === 'pending') {
                return self::STATUS_PENDING;
            }
            if ($doc['validation_status'] === 'rejected') {
                return self::STATUS_REJECTED;
            }
        }

        // No expiration date = always active
        if (empty($doc['valid_until'])) {
            return self::STATUS_ACTIVE;
        }

        $valid_until = strtotime($doc['valid_until']);
        $today = strtotime(date('Y-m-d'));

        // Expired
        if ($valid_until < $today) {
            return self::STATUS_EXPIRED;
        }

        // Check if expiring soon
        $alert_days = isset($doc['alert_days_before']) ? (int)$doc['alert_days_before'] : 30;
        $alert_date = strtotime("+{$alert_days} days", $today);

        if ($valid_until <= $alert_date) {
            return self::STATUS_EXPIRING_SOON;
        }

        return self::STATUS_ACTIVE;
    }

    /**
     * Creates a new document. If previous_version_id is set in $data,
     * marks the previous document as no longer current.
     * @param array $data Document data
     * @return int|false New document ID or false on failure
     */
    public function create_document($data) {
        // If creating a new version of an existing document, mark the old as non-current
        if (!empty($data['previous_version_id'])) {
            $this->db->where('id', $data['previous_version_id']);
            $this->db->update($this->table, array('is_current_version' => 0));
        }

        $data['is_current_version'] = 1;
        $data['uploaded_at'] = date('Y-m-d H:i:s');

        if (!isset($data['validation_status'])) {
            $data['validation_status'] = 'pending';
        }

        return $this->create($data);
    }

    /**
     * Updates meta fields of an existing document (label, description, dates, file).
     * Does not create a new version.
     * @param int   $id   Document ID
     * @param array $data Fields to update
     * @return bool
     */
    public function update_document($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }

    /**
     * Returns version history for a document
     * @param int $document_id Current document ID
     * @return array Version history (newest to oldest)
     */
    public function get_version_history($document_id) {
        $versions = array();
        $current_id = $document_id;

        while ($current_id) {
            $doc = $this->get_by_id('id', $current_id);
            if (!$doc) break;

            $versions[] = $doc;
            $current_id = $doc['previous_version_id'];
        }

        return $versions;
    }

    /**
     * Disables alarm for a document
     * @param int $document_id Document ID
     * @return bool Success
     */
    public function disable_alarm($document_id) {
        $this->db->where('id', $document_id);
        return $this->db->update($this->table, array('alarm_disabled' => 1));
    }

    /**
     * Enables alarm for a document
     * @param int $document_id Document ID
     * @return bool Success
     */
    public function enable_alarm($document_id) {
        $this->db->where('id', $document_id);
        return $this->db->update($this->table, array('alarm_disabled' => 0));
    }

    /**
     * Toggles alarm for a document
     * @param int $document_id Document ID
     * @return bool New alarm_disabled state
     */
    public function toggle_alarm($document_id) {
        $doc = $this->get_by_id('id', $document_id);
        if (!$doc) return false;

        $new_state = $doc['alarm_disabled'] ? 0 : 1;
        $this->db->where('id', $document_id);
        $this->db->update($this->table, array('alarm_disabled' => $new_state));
        return $new_state;
    }

    /**
     * Deletes a document (only if pilot owns it or user is admin)
     * @param int $document_id Document ID
     * @param string $user_login Current user login
     * @param bool $is_admin Whether user is admin
     * @return bool Success
     */
    public function delete_document($document_id, $user_login, $is_admin = false) {
        $doc = $this->get_by_id('id', $document_id);
        if (!$doc) return false;

        // Check ownership (pilot can delete their own, admin can delete all)
        if (!$is_admin && $doc['pilot_login'] !== $user_login) {
            return false;
        }

        // If this is current version and has previous, restore previous as current
        if ($doc['is_current_version'] && $doc['previous_version_id']) {
            $this->db->where('id', $doc['previous_version_id']);
            $this->db->update($this->table, array('is_current_version' => 1));
        }

        // Update any documents that point to this one
        $this->db->where('previous_version_id', $document_id);
        $this->db->update($this->table, array('previous_version_id' => $doc['previous_version_id']));

        $this->delete(array('id' => $document_id));
        return $this->db->affected_rows() > 0;
    }

    /**
     * Approve a pending document
     * @param int $id Document ID
     * @param string $validated_by Login of the user who approved
     * @return bool Success
     */
    public function approve_document($id, $validated_by) {
        $this->db->where('id', $id);
        $this->db->where('validation_status', 'pending');
        return $this->db->update($this->table, array(
            'validation_status' => 'approved',
            'validated_by' => $validated_by,
            'validated_at' => date('Y-m-d H:i:s')
        ));
    }

    /**
     * Reject a pending document
     * @param int $id Document ID
     * @param string $validated_by Login of the user who rejected
     * @param string $reason Rejection reason
     * @return bool Success
     */
    public function reject_document($id, $validated_by, $reason = '') {
        $this->db->where('id', $id);
        $this->db->where('validation_status', 'pending');
        return $this->db->update($this->table, array(
            'validation_status' => 'rejected',
            'validated_by' => $validated_by,
            'validated_at' => date('Y-m-d H:i:s'),
            'rejection_reason' => $reason
        ));
    }

    /**
     * Returns all pending documents (for admin validation list)
     * @return array
     */
    public function get_pending_documents() {
        $this->db->select('archived_documents.*, document_types.name as type_name,
            document_types.code as type_code,
            membres.mnom as pilot_nom, membres.mprenom as pilot_prenom');
        $this->db->from($this->table);
        $this->db->join('document_types', 'archived_documents.document_type_id = document_types.id', 'left');
        $this->db->join('membres', 'archived_documents.pilot_login = membres.mlogin', 'left');
        $this->db->where('archived_documents.validation_status', 'pending');
        $this->db->where('archived_documents.is_current_version', 1);
        $this->db->order_by('archived_documents.uploaded_at', 'asc');

        $query = $this->db->get();
        $results = $this->get_to_array($query);

        foreach ($results as &$row) {
            $row['expiration_status'] = self::STATUS_PENDING;
        }

        return $results;
    }

    /**
     * Returns a human-readable representation of a document
     * @param mixed $key Document ID
     * @return string
     */
    public function image($key) {
        if ($key == "")
            return "";
        $vals = $this->get_by_id('id', $key);
        if ($vals && array_key_exists('original_filename', $vals)) {
            return $vals['original_filename'];
        } else {
            return "document inconnu $key";
        }
    }

    /**
     * Returns CSS class for expiration status badge
     * @param string $status Expiration status
     * @return string Bootstrap badge class
     */
    public static function status_badge_class($status) {
        switch ($status) {
            case self::STATUS_ACTIVE:
                return 'bg-success';
            case self::STATUS_EXPIRING_SOON:
                return 'bg-warning text-dark';
            case self::STATUS_EXPIRED:
                return 'bg-danger';
            case self::STATUS_MISSING:
                return 'bg-secondary';
            case self::STATUS_PENDING:
                return 'bg-info text-dark';
            case self::STATUS_REJECTED:
                return 'bg-danger';
            default:
                return 'bg-light text-dark';
        }
    }

    /**
     * Returns label for expiration status
     * @param string $status Expiration status
     * @return string
     */
    public static function status_label($status) {
        $CI =& get_instance();
        $lang_keys = array(
            self::STATUS_ACTIVE => 'archived_documents_status_active',
            self::STATUS_EXPIRING_SOON => 'archived_documents_status_expiring_soon',
            self::STATUS_EXPIRED => 'archived_documents_status_expired',
            self::STATUS_MISSING => 'archived_documents_status_missing',
            self::STATUS_PENDING => 'archived_documents_status_pending',
            self::STATUS_REJECTED => 'archived_documents_status_rejected',
        );
        $fallbacks = array(
            self::STATUS_ACTIVE => 'Valide',
            self::STATUS_EXPIRING_SOON => 'Expire bientot',
            self::STATUS_EXPIRED => 'Expire',
            self::STATUS_MISSING => 'Manquant',
            self::STATUS_PENDING => 'En attente',
            self::STATUS_REJECTED => 'Refuse',
        );

        if (!isset($lang_keys[$status])) {
            return 'Inconnu';
        }

        $translated = $CI->lang->line($lang_keys[$status]);
        // lang->line returns FALSE or the key itself when not found
        if ($translated && $translated !== $lang_keys[$status]) {
            return $translated;
        }
        return $fallbacks[$status];
    }
}

/* End of file archived_documents_model.php */
/* Location: ./application/models/archived_documents_model.php */
