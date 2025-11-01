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
     * Get all lists created by a user
     *
     * @param int $user_id User ID
     * @return array Array of lists
     */
    public function get_user_lists($user_id) {
        $this->db->where('created_by', $user_id);
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
        if (empty($list_id) || empty($types_roles_id) || empty($section_id)) {
            return FALSE;
        }

        $data = array(
            'email_list_id' => $list_id,
            'types_roles_id' => $types_roles_id,
            'section_id' => $section_id,
            'granted_by' => $granted_by,
            'notes' => $notes
        );

        if ($this->db->insert('email_list_roles', $data)) {
            return $this->db->insert_id();
        }

        return FALSE;
    }

    /**
     * Remove a role from a list
     *
     * @param int $list_id List ID
     * @param int $role_id Role entry ID
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

        $data = array(
            'email_list_id' => $list_id,
            'membre_id' => $membre_id
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
     * @param int $member_id Member entry ID
     * @return bool TRUE on success, FALSE on failure
     */
    public function remove_manual_member($list_id, $member_id) {
        if (empty($list_id) || empty($member_id)) {
            return FALSE;
        }

        $this->db->where('id', $member_id);
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
        if (empty($list_id) || empty($email) || !validate_email($email)) {
            return FALSE;
        }

        $data = array(
            'email_list_id' => $list_id,
            'external_email' => normalize_email($email),
            'external_name' => $name
        );

        if ($this->db->insert('email_list_external', $data)) {
            return $this->db->insert_id();
        }

        return FALSE;
    }

    /**
     * Remove an external email from a list
     *
     * @param int $list_id List ID
     * @param int $external_id External email entry ID
     * @return bool TRUE on success, FALSE on failure
     */
    public function remove_external_email($list_id, $external_id) {
        if (empty($list_id) || empty($external_id)) {
            return FALSE;
        }

        $this->db->where('id', $external_id);
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
        $this->db->select('id, external_email as email, external_name as name');
        $this->db->from('email_list_external');
        $this->db->where('email_list_id', $list_id);
        $query = $this->db->get();
        return $query->result_array();
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
}
