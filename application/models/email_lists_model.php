<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Email_lists_model
 *
 * Model for managing email distribution lists with role-based,
 * manual, and external member selection.
 *
 * @package models
 * @see doc/design_notes/gestion_emails_design.md
 */
class Email_lists_model extends CI_Model {
    public $table = 'email_lists';
    protected $primary_key = 'id';

    public function __construct() {
        parent::__construct();
        $this->load->helper('email');
    }

    /**
     * Return the primary key name
     *
     * @return string
     */
    public function primary_key() {
        return $this->primary_key;
    }

    /**
     * Return the table name
     *
     * @return string
     */
    public function table() {
        return $this->table;
    }

    // ========================================================================
    // CRUD Operations for email_lists
    // ========================================================================

    /**
     * Create a new email list
     *
     * @param array $data List data (name, description, active_member, visible, created_by)
     * @return int|false New list ID or FALSE on failure
     */
    public function create_list($data) {
        if (empty($data['name']) || empty($data['created_by'])) {
            return FALSE;
        }

        $insert_data = array(
            'name' => $data['name'],
            'description' => isset($data['description']) ? $data['description'] : NULL,
            'active_member' => isset($data['active_member']) ? $data['active_member'] : 'active',
            'visible' => isset($data['visible']) ? $data['visible'] : 1,
            'created_by' => $data['created_by']
        );

        if ($this->db->insert($this->table, $insert_data)) {
            return $this->db->insert_id();
        }
        // Log the error
        gvv_error("sql error: " . $this->db->_error_message());
        gvv_error("sql last query: " . $this->db->last_query());
        return FALSE;
    }

    /**
     * Get a single email list by ID
     *
     * @param int $id List ID
     * @return array|null List data or NULL if not found
     */
    public function get_list($id) {
        $query = $this->db->get_where($this->table, array('id' => $id));
        return $query->row_array();
    }

    /**
     * Update an email list
     *
     * @param int $id List ID
     * @param array $data Data to update
     * @return bool TRUE on success, FALSE on failure
     */
    public function update_list($id, $data) {
        if (empty($id)) {
            return FALSE;
        }

        $allowed_fields = array('name', 'description', 'active_member', 'visible');
        $update_data = array();

        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
            }
        }

        if (empty($update_data)) {
            return FALSE;
        }

        $this->db->where('id', $id);
        return $this->db->update($this->table, $update_data);
    }

    /**
     * Delete an email list (cascades to roles, members, external)
     *
     * @param int $id List ID
     * @return bool TRUE on success, FALSE on failure
     */
    public function delete_list($id) {
        if (empty($id)) {
            return FALSE;
        }

        $this->db->where('id', $id);
        return $this->db->delete($this->table);
    }

    /**
     * Get all email lists
     *
     * @return array Array of lists
     */
    public function get_user_lists() {
        $this->db->order_by('created_at', 'DESC');
        $query = $this->db->get($this->table);
        return $query->result_array();
    }

    /**
     * Get all visible lists
     *
     * @return array Array of lists
     */
    public function get_visible_lists() {
        $this->db->where('visible', 1);
        $this->db->order_by('name', 'ASC');
        $query = $this->db->get($this->table);
        return $query->result_array();
    }

    /**
     * Check if a list name already exists
     *
     * @param string $name List name to check
     * @param int $exclude_id Optional ID to exclude (for updates)
     * @return bool TRUE if name exists, FALSE otherwise
     */
    public function name_exists($name, $exclude_id = NULL) {
        $this->db->where('name', $name);
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        $query = $this->db->get($this->table);
        return $query->num_rows() > 0;
    }

    // ========================================================================
    // Role-based selection (email_list_roles)
    // ========================================================================

    /**
     * Add a role to a list
     *
     * @param int $list_id List ID
     * @param int $types_roles_id Role ID
     * @param int $section_id Section ID
     * @param int $granted_by User ID who grants the role (optional)
     * @param string $notes Optional notes
     * @return int|false New role ID or FALSE on failure
     */
    public function add_role_to_list($list_id, $types_roles_id, $section_id, $granted_by = NULL, $notes = NULL) {
        if (empty($list_id) || empty($types_roles_id)) {
            return FALSE;
        }

        // Check if already exists
        $this->db->where('email_list_id', $list_id);
        $this->db->where('types_roles_id', $types_roles_id);
        if ($section_id === NULL) {
            $this->db->where('section_id IS NULL', NULL, FALSE);
        } else {
            $this->db->where('section_id', $section_id);
        }
        $existing = $this->db->get('email_list_roles')->row_array();
        if ($existing) {
            return TRUE; // Already exists, treat as success
        }

        $data = array(
            'email_list_id' => $list_id,
            'types_roles_id' => $types_roles_id,
            'section_id' => $section_id,
            'granted_by' => $granted_by,
            'granted_at' => date('Y-m-d H:i:s'),
            'notes' => $notes
        );

        if ($this->db->insert('email_list_roles', $data)) {
            return $this->db->insert_id();
        }

        return FALSE;
    }

    /**
     * Add role by role_id and section_id (convenience wrapper)
     */
    public function add_role($list_id, $role_id, $section_id) {
        return $this->add_role_to_list($list_id, $role_id, $section_id);
    }

    /**
     * Remove a role from a list by database row ID
     *
     * @param int $list_id List ID
     * @param int $role_id Role entry ID (database row id)
     * @return bool TRUE on success, FALSE on failure
     */
    public function remove_role_from_list($list_id, $role_id) {
        if (empty($list_id) || empty($role_id)) {
            return FALSE;
        }

        $this->db->where('id', $role_id);
        $this->db->where('email_list_id', $list_id);
        return $this->db->delete('email_list_roles');
    }

    /**
     * Remove a role from a list by role_id and section_id
     *
     * @param int $list_id List ID
     * @param int $types_roles_id Role type ID
     * @param int|null $section_id Section ID (NULL for "All sections")
     * @return bool TRUE on success, FALSE on failure
     */
    public function remove_role($list_id, $types_roles_id, $section_id) {
        if (empty($list_id) || empty($types_roles_id)) {
            return FALSE;
        }

        $this->db->where('email_list_id', $list_id);
        $this->db->where('types_roles_id', $types_roles_id);
        if ($section_id === NULL) {
            $this->db->where('section_id IS NULL', NULL, FALSE);
        } else {
            $this->db->where('section_id', $section_id);
        }
        return $this->db->delete('email_list_roles');
    }

    /**
     * Get all roles for a list
     *
     * @param int $list_id List ID
     * @return array Array of roles with role and section names
     */
    public function get_list_roles($list_id) {
        $this->db->select('elr.*, tr.nom as role_name, s.nom as section_name');
        $this->db->from('email_list_roles elr');
        $this->db->join('types_roles tr', 'elr.types_roles_id = tr.id', 'left');
        $this->db->join('sections s', 'elr.section_id = s.id', 'left');
        $this->db->where('elr.email_list_id', $list_id);
        $this->db->where('elr.revoked_at IS NULL');
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Get all available roles
     *
     * @return array Array of roles with id, nom, description, scope
     */
    public function get_available_roles() {
        $this->db->select('id, nom, description, scope, is_system_role');
        $this->db->from('types_roles');
        $this->db->order_by('display_order', 'ASC');
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Get all available sections
     *
     * @return array Array of sections with id, nom, description
     */
    public function get_available_sections() {
        $this->db->select('id, nom, description, acronyme, couleur');
        $this->db->from('sections');
        $this->db->order_by('id', 'ASC');
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Get users by role and section
     *
     * @param int $types_roles_id Role ID
     * @param int $section_id Section ID
     * @param string $active_member Filter: 'active', 'inactive', 'all'
     * @return array Array of users with email addresses
     */
    public function get_users_by_role_and_section($types_roles_id, $section_id, $active_member = 'active') {
        $this->db->select('m.memail as email, m.mnom, m.mprenom, m.mlogin, m.actif');
        $this->db->from('user_roles_per_section urps');
        $this->db->join('users u', 'urps.user_id = u.id', 'inner');
        $this->db->join('membres m', 'u.username = m.mlogin', 'inner');
        $this->db->where('urps.types_roles_id', $types_roles_id);
        $this->db->where('urps.section_id', $section_id);
        $this->db->where('urps.revoked_at IS NULL');

        if ($active_member === 'active') {
            $this->db->where('m.actif', 1);
        } elseif ($active_member === 'inactive') {
            $this->db->where('m.actif', 0);
        }
        // 'all' - no filter

        $query = $this->db->get();
        return $query->result_array();
    }

    // ========================================================================
    // Manual member selection (email_list_members)
    // ========================================================================

    /**
     * Add a manual member to a list
     *
     * @param int $list_id List ID
     * @param string $membre_id Member login (FK to membres.mlogin)
     * @return int|false New member ID or FALSE on failure
     */
    public function add_manual_member($list_id, $membre_id) {
        if (empty($list_id) || empty($membre_id)) {
            return FALSE;
        }

        // Check if already exists
        $this->db->where('email_list_id', $list_id);
        $this->db->where('membre_id', $membre_id);
        $existing = $this->db->get('email_list_members')->row_array();
        if ($existing) {
            return TRUE; // Already exists, treat as success
        }

        $data = array(
            'email_list_id' => $list_id,
            'membre_id' => $membre_id,
            'added_at' => date('Y-m-d H:i:s')
        );

        if ($this->db->insert('email_list_members', $data)) {
            return $this->db->insert_id();
        }

        return FALSE;
    }

    /**
     * Remove a manual member from a list
     *
     * @param int $list_id List ID
     * @param string $membre_id Member login (FK to membres.mlogin)
     * @return bool TRUE on success, FALSE on failure
     */
    public function remove_manual_member($list_id, $membre_id) {
        if (empty($list_id) || empty($membre_id)) {
            return FALSE;
        }

        $this->db->where('membre_id', $membre_id);
        $this->db->where('email_list_id', $list_id);
        return $this->db->delete('email_list_members');
    }

    /**
     * Get all manual members for a list
     *
     * @param int $list_id List ID
     * @return array Array of members with email addresses
     */
    public function get_manual_members($list_id) {
        $this->db->select('elm.id, elm.membre_id, m.memail as email, m.mnom, m.mprenom, m.actif');
        $this->db->from('email_list_members elm');
        $this->db->join('membres m', 'elm.membre_id = m.mlogin', 'inner');
        $this->db->where('elm.email_list_id', $list_id);
        $query = $this->db->get();
        return $query->result_array();
    }

    // ========================================================================
    // External email addresses (email_list_external)
    // ========================================================================

    /**
     * Add an external email to a list
     *
     * @param int $list_id List ID
     * @param string $email External email address
     * @param string $name Optional display name
     * @return int|false New external email ID or FALSE on failure
     */
    public function add_external_email($list_id, $email, $name = NULL) {
        log_message('debug', "EMAIL_LISTS_MODEL add_external_email() called: list_id=$list_id, email=$email, name=$name");

        if (empty($list_id)) {
            log_message('debug', "EMAIL_LISTS_MODEL: list_id is empty");
            return FALSE;
        }

        if (empty($email)) {
            log_message('debug', "EMAIL_LISTS_MODEL: email is empty");
            return FALSE;
        }

        if (!validate_email($email)) {
            log_message('debug', "EMAIL_LISTS_MODEL: email validation failed for: $email");
            return FALSE;
        }

        $normalized_email = normalize_email($email);
        $data = array(
            'email_list_id' => $list_id,
            'external_email' => $normalized_email,
            'external_name' => $name,
            'added_at' => date('Y-m-d H:i:s')
        );

        log_message('debug', "EMAIL_LISTS_MODEL: Inserting data: " . print_r($data, TRUE));

        if ($this->db->insert('email_list_external', $data)) {
            $insert_id = $this->db->insert_id();
            log_message('debug', "EMAIL_LISTS_MODEL: Insert successful, ID=$insert_id");
            return $insert_id;
        }

        log_message('debug', "EMAIL_LISTS_MODEL: Insert failed, error: " . $this->db->_error_message());
        return FALSE;
    }

    /**
     * Remove an external email from a list
     *
     * @param int $list_id List ID
     * @param string $email Email address to remove
     * @return bool TRUE on success, FALSE on failure
     */
    public function remove_external_email($list_id, $email) {
        if (empty($list_id) || empty($email)) {
            return FALSE;
        }

        $normalized_email = normalize_email($email);
        $this->db->where('external_email', $normalized_email);
        $this->db->where('email_list_id', $list_id);
        return $this->db->delete('email_list_external');
    }

    /**
     * Get all external emails for a list
     *
     * @param int $list_id List ID
     * @return array Array of external emails
     */
    public function get_external_emails($list_id) {
        $this->db->select('id, external_email as email, external_name as name, source_file');
        $this->db->from('email_list_external');
        $this->db->where('email_list_id', $list_id);
        $query = $this->db->get();
        return $query->result_array();
    }

    // ========================================================================
    // File upload management (v1.3)
    // ========================================================================

    /**
     * Upload and process external email file
     *
     * Parses uploaded file, validates addresses, and inserts into database
     * with source_file tracking.
     *
     * @param int $list_id List ID
     * @param array $file PHP $_FILES array element
     * @return array Result with 'success', 'filename', 'valid_count', 'errors'
     */
    public function upload_external_file($list_id, $file) {
        $this->load->helper('email_helper');

        $result = array(
            'success' => FALSE,
            'filename' => NULL,
            'valid_count' => 0,
            'invalid_count' => 0,
            'errors' => array()
        );

        // Validate file upload
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $result['errors'][] = 'Invalid file upload';
            return $result;
        }

        // Check file extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, array('txt', 'csv'))) {
            $result['errors'][] = 'Invalid file format. Only .txt and .csv are accepted';
            return $result;
        }

        // Read file content
        $content = file_get_contents($file['tmp_name']);
        if ($content === FALSE) {
            $result['errors'][] = 'Failed to read file content';
            return $result;
        }

        // Parse based on extension
        if ($ext === 'txt') {
            $parsed = parse_text_emails($content);
        } else {
            // CSV with auto-detection of columns
            $config = array(
                'email_column' => 0,  // Will be auto-detected
                'name_column' => 1,
                'has_header' => TRUE
            );
            $parsed = parse_csv_emails($content, $config);
        }

        // Generate unique filename for storage
        $unique_filename = date('YmdHis') . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $file['name']);

        // Insert valid addresses with source_file
        $valid_count = 0;
        $invalid_count = 0;

        foreach ($parsed as $item) {
            if (empty($item['error'])) {
                $data = array(
                    'email_list_id' => $list_id,
                    'external_email' => normalize_email($item['email']),
                    'external_name' => isset($item['name']) ? $item['name'] : NULL,
                    'source_file' => $unique_filename
                );

                if ($this->db->insert('email_list_external', $data)) {
                    $valid_count++;
                } else {
                    $invalid_count++;
                    $result['errors'][] = 'Failed to insert: ' . $item['email'];
                }
            } else {
                $invalid_count++;
                $result['errors'][] = $item['error'];
            }
        }

        // Save physical file if we have valid addresses
        if ($valid_count > 0) {
            $upload_path = FCPATH . 'uploads/email_lists/' . $list_id . '/';
            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0755, TRUE);
            }

            if (move_uploaded_file($file['tmp_name'], $upload_path . $unique_filename)) {
                $result['success'] = TRUE;
                $result['filename'] = $unique_filename;
            } else {
                $result['errors'][] = 'Failed to save file';
            }
        }

        $result['valid_count'] = $valid_count;
        $result['invalid_count'] = $invalid_count;

        return $result;
    }

    /**
     * Get list of uploaded files for a list with metadata
     *
     * @param int $list_id List ID
     * @return array Array of files with metadata (filename, count, added_at)
     */
    public function get_uploaded_files($list_id) {
        $this->db->select('source_file as filename, COUNT(*) as address_count, MIN(added_at) as uploaded_at');
        $this->db->from('email_list_external');
        $this->db->where('email_list_id', $list_id);
        $this->db->where('source_file IS NOT NULL');
        $this->db->group_by('source_file');
        $this->db->order_by('uploaded_at', 'DESC');

        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Delete file and all its associated addresses (cascade delete)
     *
     * @param int $list_id List ID
     * @param string $filename Source filename
     * @return array Result with 'success', 'deleted_count', 'errors'
     */
    public function delete_file_and_addresses($list_id, $filename) {
        $result = array(
            'success' => FALSE,
            'deleted_count' => 0,
            'errors' => array()
        );

        if (empty($list_id) || empty($filename)) {
            $result['errors'][] = 'Invalid parameters';
            return $result;
        }

        // Get count before deletion
        $this->db->where('email_list_id', $list_id);
        $this->db->where('source_file', $filename);
        $count = $this->db->count_all_results('email_list_external');

        // Delete database records
        $this->db->where('email_list_id', $list_id);
        $this->db->where('source_file', $filename);

        if ($this->db->delete('email_list_external')) {
            $result['deleted_count'] = $count;

            // Delete physical file
            $file_path = FCPATH . 'uploads/email_lists/' . $list_id . '/' . $filename;
            if (file_exists($file_path)) {
                if (unlink($file_path)) {
                    $result['success'] = TRUE;
                } else {
                    $result['errors'][] = 'Database records deleted but failed to remove physical file';
                    $result['success'] = TRUE; // Still success for DB deletion
                }
            } else {
                $result['success'] = TRUE; // File already gone, DB deletion succeeded
            }
        } else {
            $result['errors'][] = 'Failed to delete database records';
        }

        return $result;
    }

    /**
     * Get statistics for a specific uploaded file
     *
     * @param int $list_id List ID
     * @param string $filename Source filename
     * @return array Statistics (count, uploaded_at, file_size, file_exists)
     */
    public function get_file_stats($list_id, $filename) {
        // Get count from database
        $this->db->select('COUNT(*) as address_count, MIN(added_at) as uploaded_at');
        $this->db->from('email_list_external');
        $this->db->where('email_list_id', $list_id);
        $this->db->where('source_file', $filename);

        $query = $this->db->get();
        $stats = $query->row_array();

        // Check physical file
        $file_path = FCPATH . 'uploads/email_lists/' . $list_id . '/' . $filename;
        $stats['file_exists'] = file_exists($file_path);
        $stats['file_size'] = $stats['file_exists'] ? filesize($file_path) : 0;
        $stats['filename'] = $filename;

        return $stats;
    }

    // ========================================================================
    // Complete list resolution with deduplication
    // ========================================================================

    /**
     * Resolve complete email list (roles + manual + external) with deduplication
     *
     * @param int $list_id List ID
     * @return array Deduplicated array of email addresses (strings only)
     */
    public function textual_list($list_id) {
        $list = $this->get_list($list_id);

        if (!$list) {
            return array();
        }

        $emails = array();

        // 1. Resolve members by roles (table email_list_roles)
        $roles = $this->get_list_roles($list_id);

        foreach ($roles as $role) {
            $role_members = $this->get_users_by_role_and_section(
                $role['types_roles_id'],
                $role['section_id'],
                $list['active_member']
            );
            $emails = array_merge($emails, $role_members);
        }

        // 2. Add manually selected members (table email_list_members)
        $manual_members = $this->get_manual_members($list_id);
        $emails = array_merge($emails, $manual_members);

        // 3. Add external emails (table email_list_external)
        $external_emails = $this->get_external_emails($list_id);
        $emails = array_merge($emails, $external_emails);

        // 4. Deduplicate
        $emails = deduplicate_emails($emails);

        // 5. Extract email strings only
        $email_strings = array();
        foreach ($emails as $item) {
            if (isset($item['email']) && !empty($item['email'])) {
                $email_strings[] = $item['email'];
            }
        }

        return $email_strings;
    }

    /**
     * Count total members in a list
     *
     * @param int $list_id List ID
     * @return int Number of unique members
     */
    public function count_members($list_id) {
        $emails = $this->textual_list($list_id);
        return count($emails);
    }

    /**
     * Resolve complete email list with metadata (emails, names, sources)
     * Similar to textual_list() but returns array of arrays with metadata
     *
     * @param int $list_id List ID
     * @return array Array of email objects with 'email', 'name', 'source' keys
     */
    public function detailed_list($list_id) {
        $list = $this->get_list($list_id);

        if (!$list) {
            return array();
        }

        $all_emails = array();
        $member_names = array(); // Map email -> name
        $external_emails_set = array(); // Set of external emails
        $external_with_names = array(); // Map email -> name for external

        // 1. Resolve members by roles (table email_list_roles)
        $roles = $this->get_list_roles($list_id);

        foreach ($roles as $role) {
            $role_members = $this->get_users_by_role_and_section(
                $role['types_roles_id'],
                $role['section_id'],
                $list['active_member']
            );
            foreach ($role_members as $user) {
                if (!empty($user['email'])) {
                    $email_lower = strtolower(trim($user['email']));
                    $all_emails[] = $email_lower;
                    // Store name from members
                    $name = trim($user['mnom'] . ' ' . $user['mprenom']);
                    if (!empty($name)) {
                        $member_names[$email_lower] = $name;
                    }
                }
            }
        }

        // 2. Add manually selected members (table email_list_members)
        $manual_members = $this->get_manual_members($list_id);
        foreach ($manual_members as $member) {
            if (!empty($member['email'])) {
                $email_lower = strtolower(trim($member['email']));
                $all_emails[] = $email_lower;
                // Store name from members
                $name = trim($member['mnom'] . ' ' . $member['mprenom']);
                if (!empty($name)) {
                    $member_names[$email_lower] = $name;
                }
            }
        }

        // 3. Add external emails (table email_list_external)
        $external_emails = $this->get_external_emails($list_id);
        foreach ($external_emails as $ext) {
            if (!empty($ext['email'])) {
                $email_lower = strtolower(trim($ext['email']));
                $all_emails[] = $email_lower;
                $external_emails_set[$email_lower] = true; // Mark as external
                // Store name if provided
                if (!empty($ext['name'])) {
                    $external_with_names[$email_lower] = $ext['name'];
                }
            }
        }

        // 4. Deduplicate
        $this->load->helper('email');
        $unique_emails = deduplicate_emails($all_emails);

        // 5. Build email list with metadata
        $emails_with_metadata = array();
        foreach ($unique_emails as $email) {
            $item = array(
                'email' => $email,
                'display' => $email,
                'is_external' => isset($external_emails_set[$email])
            );

            // Add name from external emails
            if (isset($external_with_names[$email])) {
                $item['name'] = $external_with_names[$email];
            }
            // Add name from members
            elseif (isset($member_names[$email])) {
                $item['name'] = $member_names[$email];
            }

            // Set source
            if (isset($external_emails_set[$email])) {
                $item['source'] = 'external';
            } else {
                $item['source'] = 'member';
            }

            $emails_with_metadata[] = $item;
        }

        return $emails_with_metadata;
    }
}
