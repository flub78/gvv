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
class Email_lists_model extends CI_Model
{
    public $table = 'email_lists';
    protected $primary_key = 'id';

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('email');
    }

    /**
     * Return the primary key name
     *
     * @return string
     */
    public function primary_key()
    {
        return $this->primary_key;
    }

    /**
     * Return the table name
     *
     * @return string
     */
    public function table()
    {
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
    public function create_list($data)
    {
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
    public function get_list($id)
    {
        $query = $this->db->get_where($this->table, array('id' => $id));
        if ($query === FALSE) {
            return NULL;
        }
        $result = $query->row_array();
        // row_array() returns NULL when no record found, which is correct for single record retrieval
        return $result;
    }

    /**
     * Update an email list
     *
     * @param int $id List ID
     * @param array $data Data to update
     * @return bool TRUE on success, FALSE on failure
     */
    public function update_list($id, $data)
    {
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
    public function delete_list($id)
    {
        if (empty($id)) {
            return FALSE;
        }

        $this->db->where('id', $id);
        return $this->db->delete($this->table);
    }

    /**
     * Get email lists visible to a specific user
     *
     * For admins: returns all lists
     * For non-admins: returns only public lists (visible=1) + private lists (visible=0) they created
     *
     * @param int $user_id User ID
     * @param bool $is_admin Whether user is admin
     * @return array Array of lists
     */
    public function get_user_lists($user_id, $is_admin = false)
    {
        // Use raw SQL query for CodeIgniter 2.x compatibility
        $safe_user_id = (int) $user_id;

        if ($is_admin) {
            // Admins see all lists with owner information
            $sql = "SELECT el.*, u.username as owner_username, CONCAT(m.mprenom, ' ', m.mnom) as owner_name
                    FROM {$this->table} el
                    LEFT JOIN users u ON el.created_by = u.id
                    LEFT JOIN membres m ON u.username = m.mlogin
                    ORDER BY el.created_at DESC";
        } else {
            // Non-admins see only:
            // 1. Public lists (visible = 1)
            // 2. Private lists they created (visible = 0 AND created_by = user_id)
            $sql = "SELECT el.*, u.username as owner_username, CONCAT(m.mprenom, ' ', m.mnom) as owner_name
                    FROM {$this->table} el
                    LEFT JOIN users u ON el.created_by = u.id
                    LEFT JOIN membres m ON u.username = m.mlogin
                    WHERE (el.visible = 1 OR (el.visible = 0 AND el.created_by = {$safe_user_id}))
                    ORDER BY el.created_at DESC";
        }

        $query = $this->db->query($sql);

        if ($query === FALSE) {
            return array();
        }
        return $query->result_array();
    }

    /**
     * Get all visible lists
     *
     * @return array Array of lists
     */
    public function get_visible_lists()
    {
        $this->db->where('visible', 1);
        $this->db->order_by('name', 'ASC');
        $query = $this->db->get($this->table);
        if ($query === FALSE) {
            return array();
        }
        return $query->result_array();
    }

    /**
     * Check if a list name already exists
     *
     * @param string $name List name to check
     * @param int $exclude_id Optional ID to exclude (for updates)
     * @return bool TRUE if name exists, FALSE otherwise
     */
    public function name_exists($name, $exclude_id = NULL)
    {
        $this->db->where('name', $name);
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        $query = $this->db->get($this->table);
        if ($query === FALSE) {
            return FALSE;
        }
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
    public function add_role_to_list($list_id, $types_roles_id, $section_id, $granted_by = NULL, $notes = NULL)
    {
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
        $query = $this->db->get('email_list_roles');
        if ($query === FALSE) {
            return FALSE;
        }
        $existing = $query->row_array();
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
    public function add_role($list_id, $role_id, $section_id)
    {
        return $this->add_role_to_list($list_id, $role_id, $section_id);
    }

    /**
     * Remove a role from a list by database row ID
     *
     * @param int $list_id List ID
     * @param int $role_id Role entry ID (database row id)
     * @return bool TRUE on success, FALSE on failure
     */
    public function remove_role_from_list($list_id, $role_id)
    {
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
    public function remove_role($list_id, $types_roles_id, $section_id)
    {
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
    public function get_list_roles($list_id)
    {
        $this->db->select('elr.*, tr.nom as role_name, s.nom as section_name');
        $this->db->from('email_list_roles elr');
        $this->db->join('types_roles tr', 'elr.types_roles_id = tr.id', 'left');
        $this->db->join('sections s', 'elr.section_id = s.id', 'left');
        $this->db->where('elr.email_list_id', $list_id);
        $this->db->where('elr.revoked_at IS NULL');
        $query = $this->db->get();
        if ($query === FALSE) {
            return array();
        }
        return $query->result_array();
    }

    /**
     * Get all available roles
     *
     * @return array Array of roles with id, nom, description, scope, translation_key
     */
    public function get_available_roles()
    {
        $this->db->select('id, nom, description, scope, is_system_role, translation_key');
        $this->db->from('types_roles');
        $this->db->order_by('display_order', 'ASC');
        $query = $this->db->get();
        if ($query === FALSE) {
            return array();
        }
        return $query->result_array();
    }

    /**
     * Get all available sections
     *
     * @return array Array of sections with id, nom, description
     */
    public function get_available_sections()
    {
        $this->db->select('id, nom, description, acronyme, couleur');
        $this->db->from('sections');
        $this->db->order_by('id', 'ASC');
        $query = $this->db->get();
        if ($query === FALSE) {
            return array();
        }
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
    public function get_users_by_role_and_section($types_roles_id, $section_id, $active_member = 'active')
    {
        $this->db->select('m.memail as email, m.memailparent, m.mnom, m.mprenom, m.mlogin, m.actif');
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
        if ($query === FALSE) {
            return array();
        }
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
    public function add_manual_member($list_id, $membre_id)
    {
        if (empty($list_id) || empty($membre_id)) {
            return FALSE;
        }

        // Check if already exists
        $this->db->where('email_list_id', $list_id);
        $this->db->where('membre_id', $membre_id);
        $query = $this->db->get('email_list_members');
        if ($query === FALSE) {
            return FALSE;
        }
        $existing = $query->row_array();
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
    public function remove_manual_member($list_id, $membre_id)
    {
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
    public function get_manual_members($list_id)
    {
        $this->db->select('elm.id, elm.membre_id, m.memail as email, m.memailparent, m.mnom, m.mprenom, m.actif');
        $this->db->from('email_list_members elm');
        $this->db->join('membres m', 'elm.membre_id = m.mlogin', 'inner');
        $this->db->where('elm.email_list_id', $list_id);
        $query = $this->db->get();
        if ($query === FALSE) {
            return array();
        }
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
    public function add_external_email($list_id, $email, $name = NULL)
    {
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
    public function remove_external_email($list_id, $email)
    {
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
    public function get_external_emails($list_id)
    {
        $this->db->select('id, external_email as email, external_name as name');
        $this->db->from('email_list_external');
        $this->db->where('email_list_id', $list_id);
        $query = $this->db->get();
        if ($query === FALSE) {
            return array();
        }
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
    public function upload_external_file($list_id, $file)
    {
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

        // Check file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            $result['errors'][] = 'File too large. Maximum size is 5MB';
            return $result;
        }

        // Read file content
        $content = file_get_contents($file['tmp_name']);
        if ($content === FALSE) {
            $result['errors'][] = 'Failed to read file content';
            return $result;
        }

        // Use unified parser - it will auto-detect format (CSV vs plain text)
        $options = array(
            'allow_csv' => true,
            'delimiter' => null  // Auto-detect
        );
        $parsed = parse_email_string($content, $options);

        // Generate unique filename for storage
        $unique_filename = date('YmdHis') . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $file['name']);

        // Insert valid addresses with source_file
        $valid_count = 0;
        $invalid_count = 0;

        foreach ($parsed as $item) {
            if (empty($item['error'])) {
                // Use display_name for CSV (firstname + lastname), otherwise use name
                $name = '';
                if (isset($item['display_name']) && !empty($item['display_name'])) {
                    $name = $item['display_name'];
                } elseif (isset($item['name']) && !empty($item['name'])) {
                    $name = $item['name'];
                }

                $data = array(
                    'email_list_id' => $list_id,
                    'external_email' => normalize_email($item['email']),
                    'external_name' => $name,
                    'source_file' => $unique_filename
                );

                if ($this->db->insert('email_list_external', $data)) {
                    $valid_count++;
                } else {
                    $invalid_count++;
                    // CodeIgniter 2.x compatible error handling
                    $error_msg = mysqli_error($this->db->conn_id);
                    $error_code = mysqli_errno($this->db->conn_id);
                    $result['errors'][] = 'Failed to insert: ' . $item['email'] .
                        ' - ' . $error_msg . ' (code: ' . $error_code . ')';
                }
            } else {
                $invalid_count++;
                $result['errors'][] = $item['error'];
            }
        }

        // Save physical file if we have valid addresses
        if ($valid_count > 0) {
            $upload_path = FCPATH . 'uploads/email_lists/' . $list_id . '/';

            // Create directory if it doesn't exist
            if (!is_dir($upload_path)) {
                if (!mkdir($upload_path, 0755, TRUE)) {
                    $result['errors'][] = 'Failed to create upload directory';
                    return $result;
                }
            }

            // Ensure target file doesn't already exist
            $target_file = $upload_path . $unique_filename;
            if (file_exists($target_file)) {
                $result['errors'][] = 'File already exists';
                return $result;
            }

            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                $result['success'] = TRUE;
                $result['filename'] = $unique_filename;

                // Log successful upload
                log_message('info', "Email file uploaded: list_id={$list_id}, file={$unique_filename}, valid_emails={$valid_count}");
            } else {
                $result['errors'][] = 'Failed to save file to uploads directory';

                // Cleanup database entries since file save failed
                $this->db->where('email_list_id', $list_id);
                $this->db->where('source_file', $unique_filename);
                $this->db->delete('email_list_external');
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
    public function get_uploaded_files($list_id)
    {
        $this->db->select('source_file as filename, COUNT(*) as address_count, MIN(added_at) as uploaded_at');
        $this->db->from('email_list_external');
        $this->db->where('email_list_id', $list_id);
        $this->db->where('source_file IS NOT NULL');
        $this->db->group_by('source_file');
        $this->db->order_by('uploaded_at', 'DESC');

        $query = $this->db->get();
        if ($query === FALSE) {
            return array();
        }
        return $query->result_array();
    }

    /**
     * Delete file and all its associated addresses (cascade delete)
     *
     * @param int $list_id List ID
     * @param string $filename Source filename
     * @return array Result with 'success', 'deleted_count', 'errors'
     */
    public function delete_file_and_addresses($list_id, $filename)
    {
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
                    log_message('info', "Email file deleted: list_id={$list_id}, file={$filename}, emails_removed={$count}");
                } else {
                    $result['errors'][] = 'Database records deleted but failed to remove physical file';
                    $result['success'] = TRUE; // Still success for DB deletion
                    log_message('error', "Failed to delete physical file: {$file_path}");
                }
            } else {
                $result['success'] = TRUE; // File already gone, DB deletion succeeded
                log_message('info', "Email file deleted from DB: list_id={$list_id}, file={$filename}, emails_removed={$count} (file was already missing)");
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
    public function get_file_stats($list_id, $filename)
    {
        // Get count from database
        $this->db->select('COUNT(*) as address_count, MIN(added_at) as uploaded_at');
        $this->db->from('email_list_external');
        $this->db->where('email_list_id', $list_id);
        $this->db->where('source_file', $filename);

        $query = $this->db->get();
        if ($query === FALSE) {
            return array(
                'address_count' => 0,
                'uploaded_at' => NULL,
                'file_exists' => FALSE,
                'file_size' => 0,
                'filename' => $filename
            );
        }
        $stats = $query->row_array();

        // Handle case where no records found
        if ($stats === NULL) {
            $stats = array(
                'address_count' => 0,
                'uploaded_at' => NULL
            );
        }

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
    public function textual_list($list_id)
    {
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
            foreach ($role_members as $member) {
                // Add primary email
                if (!empty($member['email'])) {
                    $emails[] = array('email' => $member['email']);
                }
                // Add parent email if present
                if (!empty($member['memailparent'])) {
                    $emails[] = array('email' => $member['memailparent']);
                }
            }
        }

        // 2. Add manually selected members (table email_list_members)
        $manual_members = $this->get_manual_members($list_id);
        foreach ($manual_members as $member) {
            // Add primary email
            if (!empty($member['email'])) {
                $emails[] = array('email' => $member['email']);
            }
            // Add parent email if present
            if (!empty($member['memailparent'])) {
                $emails[] = array('email' => $member['memailparent']);
            }
        }

        // 3. Add external emails (table email_list_external)
        $external_emails = $this->get_external_emails($list_id);
        $emails = array_merge($emails, $external_emails);

        // 4. Add sublists (table email_list_sublists) - Source 4
        $sublists = $this->get_sublists($list_id);
        foreach ($sublists as $sublist) {
            // Recursively get emails from sublist
            // No risk of infinite recursion due to depth=1 validation in add_sublist()
            $sublist_emails = $this->textual_list($sublist['id']);
            foreach ($sublist_emails as $email) {
                $emails[] = array('email' => $email);
            }
        }

        // 5. Deduplicate
        $emails = deduplicate_emails($emails);

        // 6. Extract email strings only
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
    public function count_members($list_id)
    {
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
    public function detailed_list($list_id)
    {
        $list = $this->get_list($list_id);

        if (!$list) {
            return array();
        }

        $all_emails = array();
        $member_names = array(); // Map email -> name
        $email_sources = array(); // Map email -> source (role name, manual, external)
        $external_emails_set = array(); // Set of external emails
        $external_with_names = array(); // Map email -> name for external
        $manual_emails_set = array(); // Set of manual emails

        // 1. Resolve members by roles (table email_list_roles)
        $roles = $this->get_list_roles($list_id);

        foreach ($roles as $role) {
            $role_members = $this->get_users_by_role_and_section(
                $role['types_roles_id'],
                $role['section_id'],
                $list['active_member']
            );
            foreach ($role_members as $user) {
                $name = trim($user['mnom'] . ' ' . $user['mprenom']);

                // Add primary email
                if (!empty($user['email'])) {
                    $email_lower = strtolower(trim($user['email']));
                    $all_emails[] = $email_lower;
                    // Store name from members
                    if (!empty($name)) {
                        $member_names[$email_lower] = $name;
                    }
                    // Store role name as source
                    $email_sources[$email_lower] = $role['role_name'];
                }

                // Add parent email
                if (!empty($user['memailparent'])) {
                    $parent_email_lower = strtolower(trim($user['memailparent']));
                    $all_emails[] = $parent_email_lower;
                    // Store name from members (parent email linked to same member)
                    if (!empty($name)) {
                        $member_names[$parent_email_lower] = $name . ' (parent)';
                    }
                    // Store role name as source
                    $email_sources[$parent_email_lower] = $role['role_name'];
                }
            }
        }

        // 2. Add manually selected members (table email_list_members)
        $manual_members = $this->get_manual_members($list_id);
        foreach ($manual_members as $member) {
            $name = trim($member['mnom'] . ' ' . $member['mprenom']);

            // Add primary email
            if (!empty($member['email'])) {
                $email_lower = strtolower(trim($member['email']));
                $all_emails[] = $email_lower;
                $manual_emails_set[$email_lower] = true; // Mark as manual
                // Store name from members
                if (!empty($name)) {
                    $member_names[$email_lower] = $name;
                }
                // Only set source if not already set by role
                if (!isset($email_sources[$email_lower])) {
                    $email_sources[$email_lower] = 'membre';
                }
            }

            // Add parent email
            if (!empty($member['memailparent'])) {
                $parent_email_lower = strtolower(trim($member['memailparent']));
                $all_emails[] = $parent_email_lower;
                $manual_emails_set[$parent_email_lower] = true; // Mark as manual
                // Store name from members (parent email linked to same member)
                if (!empty($name)) {
                    $member_names[$parent_email_lower] = $name . ' (parent)';
                }
                // Only set source if not already set by role
                if (!isset($email_sources[$parent_email_lower])) {
                    $email_sources[$parent_email_lower] = 'membre';
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
                // Set source as externe
                $email_sources[$email_lower] = 'externe';
            }
        }

        // 4. Add sublists (table email_list_sublists) - Source 4
        $sublists = $this->get_sublists($list_id);
        foreach ($sublists as $sublist) {
            // Get emails from sublist (using textual_list for simplicity)
            // No risk of infinite recursion due to depth=1 validation in add_sublist()
            $sublist_emails = $this->textual_list($sublist['id']);
            foreach ($sublist_emails as $email) {
                $email_lower = strtolower(trim($email));
                $all_emails[] = $email_lower;
                // Set source as sublist with list name
                // Only set if not already set by a more specific source
                if (!isset($email_sources[$email_lower])) {
                    $email_sources[$email_lower] = 'sublist:' . $sublist['name'];
                }
            }
        }

        // 5. Deduplicate
        $this->load->helper('email');
        $unique_emails = deduplicate_emails($all_emails);

        // 6. Build email list with metadata
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

            // Set source from our tracking
            if (isset($email_sources[$email])) {
                $item['source'] = $email_sources[$email];
            } else {
                $item['source'] = 'unknown'; // Fallback, should not happen
            }

            $emails_with_metadata[] = $item;
        }

        return $emails_with_metadata;
    }

    // ========================================================================
    // Sublist Management (Migration 054)
    // ========================================================================

    /**
     * Add a sublist to a parent list
     *
     * Validates:
     * - Both lists exist
     * - No self-reference (parent != child)
     * - Child list doesn't contain sublists (depth = 1 only)
     * - No duplicate (parent, child) pair
     * - Visibility coherence (public list can only contain public sublists)
     *
     * @param int $parent_list_id ID of the parent list
     * @param int $child_list_id ID of the list to include as sublist
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function add_sublist($parent_list_id, $child_list_id)
    {
        // Validation 1: Both lists must exist
        $parent = $this->get_list($parent_list_id);
        if (!$parent) {
            return array('success' => FALSE, 'error' => 'Liste parente introuvable');
        }

        $child = $this->get_list($child_list_id);
        if (!$child) {
            return array('success' => FALSE, 'error' => 'Liste enfant introuvable');
        }

        // Validation 2: No self-reference
        if ($parent_list_id == $child_list_id) {
            return array('success' => FALSE, 'error' => 'Une liste ne peut pas se contenir elle-même');
        }

        // Validation 3: Child must not contain sublists (depth = 1 only)
        if ($this->has_sublists($child_list_id)) {
            return array('success' => FALSE, 'error' => 'Cette liste contient déjà des sous-listes et ne peut pas être incluse');
        }

        // Validation 3b: Parent must not be a sublist elsewhere (depth = 1 only)
        $parent_lists = $this->get_parent_lists($parent_list_id);
        if (!empty($parent_lists)) {
            return array('success' => FALSE, 'error' => 'Cette liste est déjà utilisée comme sous-liste et ne peut pas contenir de sous-listes');
        }

        // Validation 4: Check for duplicate
        $existing = $this->db->get_where('email_list_sublists', array(
            'parent_list_id' => $parent_list_id,
            'child_list_id' => $child_list_id
        ));
        if ($existing->num_rows() > 0) {
            return array('success' => FALSE, 'error' => 'Cette sous-liste est déjà incluse');
        }

        // Validation 5: Visibility coherence
        // Public parent can only contain public children
        if ($parent['visible'] == 1 && $child['visible'] == 0) {
            return array('success' => FALSE, 'error' => 'Impossible d\'ajouter une sous-liste privée à une liste publique');
        }

        // Insert the sublist relationship
        $data = array(
            'parent_list_id' => $parent_list_id,
            'child_list_id' => $child_list_id
        );

        if ($this->db->insert('email_list_sublists', $data)) {
            return array('success' => TRUE, 'error' => NULL);
        }

        return array('success' => FALSE, 'error' => 'Erreur lors de l\'ajout de la sous-liste');
    }

    /**
     * Remove a sublist from a parent list
     *
     * @param int $parent_list_id ID of the parent list
     * @param int $child_list_id ID of the sublist to remove
     * @return array Array with 'success' and 'error' keys
     */
    public function remove_sublist($parent_list_id, $child_list_id)
    {
        $this->db->where('parent_list_id', $parent_list_id);
        $this->db->where('child_list_id', $child_list_id);
        $this->db->delete('email_list_sublists');

        // Always return success - deleting a non-existent relationship is idempotent
        // Only a true database error would be a problem
        if ($this->db->_error_message()) {
            return array('success' => FALSE, 'error' => 'Erreur lors de la suppression de la sous-liste');
        }

        return array('success' => TRUE, 'error' => NULL);
    }

    /**
     * Check if a list can be deleted
     * A list cannot be deleted if it's used as a sublist in other lists (FK RESTRICT)
     *
     * @param int $list_id List ID to check
     * @return array ['can_delete' => bool, 'parent_lists' => array]
     */
    public function can_delete_list($list_id)
    {
        if (empty($list_id)) {
            return array(
                'can_delete' => FALSE,
                'parent_lists' => array(),
                'error' => 'ID de liste invalide'
            );
        }

        // Check if this list is used as a sublist
        $parent_lists = $this->get_parent_lists($list_id);

        if (empty($parent_lists)) {
            // No parents, can delete freely
            return array(
                'can_delete' => TRUE,
                'parent_lists' => array()
            );
        } else {
            // Has parents, cannot delete directly
            return array(
                'can_delete' => FALSE,
                'parent_lists' => $parent_lists
            );
        }
    }

    /**
     * Remove a list from all its parent lists, then delete it
     * This is used when a list is used as a sublist and needs to be deleted
     *
     * @param int $list_id List ID to delete
     * @return array ['success' => bool, 'removed_from_count' => int, 'error' => string|null]
     */
    public function remove_from_all_parents_and_delete($list_id)
    {
        if (empty($list_id)) {
            return array(
                'success' => FALSE,
                'error' => 'ID de liste invalide',
                'removed_from_count' => 0
            );
        }

        // Get all parent lists
        $parent_lists = $this->get_parent_lists($list_id);
        $removed_count = 0;

        // Remove from each parent list
        foreach ($parent_lists as $parent) {
            $result = $this->remove_sublist($parent['id'], $list_id);
            if ($result['success']) {
                $removed_count++;
            } else {
                // If removal fails, return error
                return array(
                    'success' => FALSE,
                    'error' => 'Erreur lors du retrait de la liste parente: ' . $parent['name'],
                    'removed_from_count' => $removed_count
                );
            }
        }

        // Now delete the list itself
        $deleted = $this->delete_list($list_id);

        if ($deleted) {
            return array(
                'success' => TRUE,
                'error' => NULL,
                'removed_from_count' => $removed_count
            );
        } else {
            return array(
                'success' => FALSE,
                'error' => 'Erreur lors de la suppression de la liste',
                'removed_from_count' => $removed_count
            );
        }
    }

    /**
     * Get all sublists of a parent list
     *
     * @param int $parent_list_id ID of the parent list
     * @return array Array of sublists with metadata (id, name, visible, recipient_count)
     */
    public function get_sublists($parent_list_id)
    {
        $this->db->select('els.id as sublist_relation_id, el.id, el.name, el.visible, el.description, els.added_at');
        $this->db->from('email_list_sublists els');
        $this->db->join('email_lists el', 'els.child_list_id = el.id');
        $this->db->where('els.parent_list_id', $parent_list_id);
        $this->db->order_by('els.added_at', 'ASC');

        $query = $this->db->get();
        if ($query === FALSE) {
            return array();
        }

        $sublists = $query->result_array();

        // Add recipient count for each sublist
        foreach ($sublists as &$sublist) {
            $sublist['recipient_count'] = $this->count_members($sublist['id']);
        }

        return $sublists;
    }

    /**
     * Check if a list contains sublists
     *
     * @param int $list_id ID of the list
     * @return bool TRUE if the list contains sublists, FALSE otherwise
     */
    public function has_sublists($list_id)
    {
        $this->db->select('COUNT(*) as count');
        $this->db->from('email_list_sublists');
        $this->db->where('parent_list_id', $list_id);
        $query = $this->db->get();

        if ($query === FALSE) {
            return FALSE;
        }

        $result = $query->row_array();
        return $result && $result['count'] > 0;
    }

    /**
     * Get all parent lists that contain a given list as sublist
     *
     * @param int $child_list_id ID of the list
     * @return array Array of parent lists with metadata
     */
    public function get_parent_lists($child_list_id)
    {
        $this->db->select('el.id, el.name, el.visible, el.description');
        $this->db->from('email_list_sublists els');
        $this->db->join('email_lists el', 'els.parent_list_id = el.id');
        $this->db->where('els.child_list_id', $child_list_id);
        $this->db->order_by('el.name', 'ASC');

        $query = $this->db->get();
        if ($query === FALSE) {
            return array();
        }

        $parents = $query->result_array();

        // Add recipient count for each parent list
        foreach ($parents as &$parent) {
            $parent['recipient_count'] = $this->count_members($parent['id']);
        }

        return $parents;
    }

    /**
     * Get all lists that can be used as sublists
     *
     * A list can be used as sublist if:
     * - It doesn't contain sublists itself (depth = 1 constraint)
     * - User has permission to see it
     * - It's not the list being edited (to avoid self-reference)
     *
     * @param int $user_id ID of the user
     * @param bool $is_admin Whether the user is admin
     * @param int|null $exclude_list_id List ID to exclude (typically the list being edited)
     * @return array Array of available lists
     */
    public function get_available_sublists($user_id, $is_admin = FALSE, $exclude_list_id = NULL, $parent_visible = NULL)
    {
        // Get all lists visible to the user
        $all_lists = $this->get_user_lists($user_id, $is_admin);

        $available = array();

        foreach ($all_lists as $list) {
            // Exclude the list being edited
            if ($exclude_list_id && $list['id'] == $exclude_list_id) {
                continue;
            }

            // Exclude lists that contain sublists (depth = 1 constraint)
            if ($this->has_sublists($list['id'])) {
                continue;
            }

            // If parent list is public (visible=1), only show public sublists
            if ($parent_visible == 1 && $list['visible'] == 0) {
                continue;
            }

            // Add recipient count
            $list['recipient_count'] = $this->count_members($list['id']);

            $available[] = $list;
        }

        return $available;
    }
}
