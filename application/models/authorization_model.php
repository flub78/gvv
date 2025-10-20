<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Authorization Model
 *
 * Data access layer for the new authorization system.
 * Provides methods to query roles, permissions, and data access rules.
 *
 * @see /doc/plans/2025_authorization_refactoring_plan.md
 */
class Authorization_model extends CI_Model {

    /**
     * Get user's roles for a specific section
     *
     * @param int $user_id User ID
     * @param int $section_id Section ID (NULL for all sections)
     * @param bool $include_global Include global roles
     * @return array Array of roles with metadata
     */
    public function get_user_roles($user_id, $section_id = NULL, $include_global = TRUE)
    {
        $this->db->select('urps.*, tr.nom as role_name, tr.description, tr.scope, tr.translation_key, s.couleur as section_color')
            ->from('user_roles_per_section urps')
            ->join('types_roles tr', 'urps.types_roles_id = tr.id', 'inner')
            ->join('sections s', 'urps.section_id = s.id', 'left')
            ->where('urps.user_id', $user_id)
            ->where('urps.revoked_at IS NULL');

        if ($section_id !== NULL) {
            // Get roles for specific section
            if ($include_global) {
                // Include both section-specific roles and global roles (section_id = 0)
                // Use where_in for CodeIgniter 2.x compatibility
                $this->db->where_in('urps.section_id', array($section_id, 0));
            } else {
                $this->db->where('urps.section_id', $section_id);
            }
        }

        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Get all roles with their metadata
     *
     * @param string $scope Filter by scope (NULL for all, 'global', 'section')
     * @return array Array of roles
     */
    public function get_all_roles($scope = NULL)
    {
        $this->db->select('*')
            ->from('types_roles')
            ->order_by('display_order', 'ASC');

        if ($scope !== NULL) {
            $this->db->where('scope', $scope);
        }

        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Get role by ID
     *
     * @param int $types_roles_id Role ID
     * @return array|null Role data or NULL
     */
    public function get_role($types_roles_id)
    {
        $query = $this->db
            ->where('id', $types_roles_id)
            ->get('types_roles');

        return $query->row_array();
    }

    /**
     * Get role by name
     *
     * @param string $role_name Role name (e.g., 'club-admin')
     * @return array|null Role data or NULL
     */
    public function get_role_by_name($role_name)
    {
        $query = $this->db
            ->where('nom', $role_name)
            ->get('types_roles');

        return $query->row_array();
    }

    /**
     * Get permissions for a role
     *
     * @param int $types_roles_id Role ID
     * @param int $section_id Section ID (NULL for all sections)
     * @return array Array of permissions
     */
    public function get_role_permissions($types_roles_id, $section_id = NULL)
    {
        $this->db->select('*')
            ->from('role_permissions')
            ->where('types_roles_id', $types_roles_id);

        if ($section_id !== NULL) {
            $this->db->where('section_id', $section_id);
        }

        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Get data access rules for a role and table
     *
     * @param int $types_roles_id Role ID
     * @param string $table_name Table name (use '*' for all tables)
     * @return array Array of data access rules
     */
    public function get_data_access_rules($types_roles_id, $table_name)
    {
        $this->db->select('*')
            ->from('data_access_rules')
            ->where('types_roles_id', $types_roles_id)
            ->where_in('table_name', array($table_name, '*'));

        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Add a permission to a role
     *
     * @param int $types_roles_id Role ID
     * @param string $controller Controller name
     * @param string $action Action name (NULL for all actions)
     * @param int $section_id Section ID (NULL for global)
     * @param string $permission_type Permission type (view, create, edit, delete, admin)
     * @return bool TRUE on success
     */
    public function add_permission($types_roles_id, $controller, $action, $section_id, $permission_type = 'view')
    {
        // Use 0 for global permissions (NULL section_id)
        if ($section_id === NULL) {
            $section_id = 0;
        }

        // Check if permission already exists
        $existing = $this->db
            ->where('types_roles_id', $types_roles_id)
            ->where('controller', $controller)
            ->where('action', $action)
            ->where('section_id', $section_id)
            ->get('role_permissions')
            ->row_array();

        if ($existing) {
            return FALSE; // Already exists
        }

        $data = array(
            'types_roles_id' => $types_roles_id,
            'section_id' => $section_id,
            'controller' => $controller,
            'action' => $action,
            'permission_type' => $permission_type,
            'created' => date('Y-m-d H:i:s'),
            'modified' => date('Y-m-d H:i:s')
        );

        $result = $this->db->insert('role_permissions', $data);
        return $result ? TRUE : FALSE;
    }

    /**
     * Remove a permission from a role
     *
     * @param int $permission_id Permission ID
     * @return bool TRUE on success
     */
    public function remove_permission($permission_id)
    {
        return $this->db
            ->where('id', $permission_id)
            ->delete('role_permissions');
    }

    /**
     * Add a data access rule
     *
     * @param int $types_roles_id Role ID
     * @param string $table_name Table name
     * @param string $access_scope Scope (own, section, all)
     * @param string $field_name Field name for ownership check
     * @param string $section_field Section field name
     * @param string $description Description
     * @return bool TRUE on success
     */
    public function add_data_access_rule($types_roles_id, $table_name, $access_scope, $field_name = NULL, $section_field = NULL, $description = NULL)
    {
        // Check if rule already exists
        $existing = $this->db
            ->where('types_roles_id', $types_roles_id)
            ->where('table_name', $table_name)
            ->where('access_scope', $access_scope)
            ->get('data_access_rules')
            ->row_array();

        if ($existing) {
            return FALSE; // Already exists
        }

        $data = array(
            'types_roles_id' => $types_roles_id,
            'table_name' => $table_name,
            'access_scope' => $access_scope,
            'field_name' => $field_name,
            'section_field' => $section_field,
            'description' => $description
        );

        $result = $this->db->insert('data_access_rules', $data);
        return $result ? TRUE : FALSE;
    }

    /**
     * Remove a data access rule
     *
     * @param int $rule_id Rule ID
     * @return bool TRUE on success
     */
    public function remove_data_access_rule($rule_id)
    {
        return $this->db
            ->where('id', $rule_id)
            ->delete('data_access_rules');
    }

    /**
     * Get users with a specific role
     *
     * @param int $types_roles_id Role ID
     * @param int $section_id Section ID (NULL for all sections)
     * @param bool $active_only Only return active (non-revoked) assignments
     * @return array Array of user assignments
     */
    public function get_users_with_role($types_roles_id, $section_id = NULL, $active_only = TRUE)
    {
        $this->db->select('urps.*, u.username, u.email, m.mnom as nom, m.mprenom as prenom')
            ->from('user_roles_per_section urps')
            ->join('users u', 'urps.user_id = u.id', 'inner')
            ->join('membres m', 'u.username = m.mlogin', 'left')
            ->where('urps.types_roles_id', $types_roles_id);

        if ($section_id !== NULL) {
            $this->db->where('urps.section_id', $section_id);
        }

        if ($active_only) {
            $this->db->where('urps.revoked_at IS NULL');
        }

        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Get authorization audit log
     *
     * @param array $filters Filters (action_type, actor_user_id, target_user_id, date_from, date_to)
     * @param int $limit Limit results
     * @param int $offset Offset for pagination
     * @return array Array of audit log entries
     */
    public function get_audit_log($filters = array(), $limit = 100, $offset = 0)
    {
        $this->db->select('aal.*, u1.username as actor_username, u2.username as target_username, tr.nom as role_name')
            ->from('authorization_audit_log aal')
            ->join('users u1', 'aal.actor_user_id = u1.id', 'left')
            ->join('users u2', 'aal.target_user_id = u2.id', 'left')
            ->join('types_roles tr', 'aal.types_roles_id = tr.id', 'left')
            ->order_by('aal.created_at', 'DESC')
            ->limit($limit, $offset);

        // Apply filters
        if (isset($filters['action_type'])) {
            $this->db->where('aal.action_type', $filters['action_type']);
        }

        if (isset($filters['actor_user_id'])) {
            $this->db->where('aal.actor_user_id', $filters['actor_user_id']);
        }

        if (isset($filters['target_user_id'])) {
            $this->db->where('aal.target_user_id', $filters['target_user_id']);
        }

        if (isset($filters['date_from'])) {
            $this->db->where('aal.created_at >=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $this->db->where('aal.created_at <=', $filters['date_to']);
        }

        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Create a new role
     *
     * @param string $nom Role name
     * @param string $description Role description
     * @param string $scope Role scope ('global' or 'section')
     * @param string $translation_key Translation key (optional)
     * @return int|bool Role ID on success, FALSE on failure
     */
    public function create_role($nom, $description, $scope, $translation_key = NULL)
    {
        $data = array(
            'nom' => $nom,
            'description' => $description,
            'scope' => $scope,
            'translation_key' => $translation_key,
            'is_system_role' => 0
        );
        
        $result = $this->db->insert('types_roles', $data);
        return $result ? $this->db->insert_id() : FALSE;
    }
    
    /**
     * Update an existing role
     *
     * @param int $types_roles_id Role ID
     * @param string $nom Role name
     * @param string $description Role description
     * @param string $scope Role scope ('global' or 'section')
     * @param string $translation_key Translation key (optional)
     * @return bool TRUE on success, FALSE on failure
     */
    public function update_role($types_roles_id, $nom, $description, $scope, $translation_key = NULL)
    {
        $data = array(
            'nom' => $nom,
            'description' => $description,
            'scope' => $scope,
            'translation_key' => $translation_key
        );
        
        $this->db->where('id', $types_roles_id);
        $this->db->where('is_system_role', 0);
        $result = $this->db->update('types_roles', $data);
        return $result ? TRUE : FALSE;
    }
    
    /**
     * Delete a role
     *
     * @param int $types_roles_id Role ID
     * @return bool TRUE on success, FALSE on failure
     */
    public function delete_role($types_roles_id)
    {
        // First delete associated permissions
        $this->db->where('types_roles_id', $types_roles_id);
        $this->db->delete('role_permissions');
        
        // Delete associated data access rules
        $this->db->where('types_roles_id', $types_roles_id);
        $this->db->delete('data_access_rules');
        
        // Delete role assignments
        $this->db->where('types_roles_id', $types_roles_id);
        $this->db->delete('user_roles_per_section');
        
        // Delete the role (only if not a system role)
        $this->db->where('id', $types_roles_id);
        $this->db->where('is_system_role', 0);
        $result = $this->db->delete('types_roles');
        
        return $result ? TRUE : FALSE;
    }

    /**
     * Get migration status for a user
     *
     * @param int $user_id User ID
     * @return array|null Migration status or NULL
     */
    public function get_migration_status($user_id)
    {
        $query = $this->db
            ->where('user_id', $user_id)
            ->get('authorization_migration_status');

        return $query->row_array();
    }

    /**
     * Set migration status for a user
     *
     * @param int $user_id User ID
     * @param string $status Migration status
     * @param bool $use_new_system Use new authorization system
     * @param int $migrated_by User who initiated migration
     * @return bool TRUE on success
     */
    public function set_migration_status($user_id, $status, $use_new_system, $migrated_by)
    {
        $existing = $this->get_migration_status($user_id);

        $data = array(
            'migration_status' => $status,
            'use_new_system' => $use_new_system ? 1 : 0,
            'migrated_by' => $migrated_by
        );

        if ($status === 'completed') {
            $data['completed_at'] = date('Y-m-d H:i:s');
        }

        if ($existing) {
            // Update existing record
            $result = $this->db
                ->where('user_id', $user_id)
                ->update('authorization_migration_status', $data);
            return $result ? TRUE : FALSE;
        } else {
            // Insert new record
            $data['user_id'] = $user_id;
            $data['migrated_at'] = date('Y-m-d H:i:s');
            $result = $this->db->insert('authorization_migration_status', $data);
            return $result ? TRUE : FALSE;
        }
    }
}
