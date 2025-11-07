<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * GVV Authorization Library
 *
 * Modern role-based access control system with row-level security.
 * Replaces legacy DX_Auth serialized permission system with structured
 * database-backed authorization.
 *
 * Features:
 * - URI-based permissions (controller/action access)
 * - Row-level data access rules (own/section/all scopes)
 * - Role hierarchy and inheritance
 * - Section-based authorization
 * - Audit logging
 * - Dual-mode operation (feature flag for progressive migration)
 *
 * @see /doc/plans/2025_authorization_refactoring_plan.md
 */
class Gvv_Authorization {

    protected $CI;
    protected $use_new_system = FALSE; // Feature flag
    protected $cache = array(); // Runtime cache for permissions

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->CI->load->model('authorization_model');

        // Load feature flag from config
        $this->CI->config->load('gvv_config', TRUE);
        $config = $this->CI->config->item('gvv_config');
        $this->use_new_system = isset($config['use_new_authorization']) ? $config['use_new_authorization'] : FALSE;

        log_message('debug', 'Gvv_Authorization library initialized (new_system=' . ($this->use_new_system ? 'true' : 'false') . ')');
    }

    /**
     * Check if user can access a controller/action
     *
     * @param int $user_id User ID
     * @param string $controller Controller name
     * @param string $action Action name (NULL for any action)
     * @param int $section_id Section ID (NULL for global check)
     * @return bool TRUE if access granted
     */
    public function can_access($user_id, $controller, $action = NULL, $section_id = NULL)
    {
        // Cache key
        $cache_key = "access_{$user_id}_{$controller}_{$action}_{$section_id}";
        if (isset($this->cache[$cache_key])) {
            return $this->cache[$cache_key];
        }

        // Get user's roles for this section
        $roles = $this->get_user_roles($user_id, $section_id);

        if (empty($roles)) {
            $this->cache[$cache_key] = FALSE;
            $this->_log_access_denied($user_id, $controller, $action, $section_id, 'No roles');
            return FALSE;
        }

        // Check if any role grants access
        foreach ($roles as $role) {
            if ($this->_role_has_permission($role['types_roles_id'], $controller, $action, $section_id)) {
                $this->cache[$cache_key] = TRUE;
                $this->_log_access_granted($user_id, $controller, $action, $section_id, $role['role_name']);
                return TRUE;
            }
        }

        $this->cache[$cache_key] = FALSE;
        $this->_log_access_denied($user_id, $controller, $action, $section_id, 'Permission denied');
        return FALSE;
    }

    /**
     * Check if user can access specific data row
     *
     * @param int $user_id User ID
     * @param string $table_name Table name
     * @param array $row_data Row data to check
     * @param int $section_id Section ID
     * @param string $access_type Type of access (view, edit, delete)
     * @return bool TRUE if access granted
     */
    public function can_access_data($user_id, $table_name, $row_data, $section_id, $access_type = 'view')
    {
        // Get user's roles for this section
        $roles = $this->get_user_roles($user_id, $section_id);

        if (empty($roles)) {
            return FALSE;
        }

        // Check data access rules for each role
        foreach ($roles as $role) {
            $rules = $this->CI->authorization_model->get_data_access_rules($role['types_roles_id'], $table_name);

            foreach ($rules as $rule) {
                if ($this->_check_data_access_rule($rule, $row_data, $user_id, $section_id)) {
                    return TRUE;
                }
            }
        }

        return FALSE;
    }

    /**
     * Get user's roles for a section
     *
     * @param int $user_id User ID
     * @param int $section_id Section ID (NULL for all sections)
     * @return array Array of roles with metadata
     */
    public function get_user_roles($user_id, $section_id = NULL)
    {
        // Cache key
        $cache_key = "roles_{$user_id}_{$section_id}";
        if (isset($this->cache[$cache_key])) {
            return $this->cache[$cache_key];
        }

        $roles = $this->CI->authorization_model->get_user_roles($user_id, $section_id);
        $this->cache[$cache_key] = $roles;

        return $roles;
    }

    /**
     * Check if user has a specific role
     *
     * @param int $user_id User ID
     * @param string $role_name Role name (e.g., 'club-admin', 'planchiste')
     * @param int $section_id Section ID (NULL for global roles)
     * @return bool TRUE if user has the role
     */
    public function has_role($user_id, $role_name, $section_id = NULL)
    {
        $roles = $this->get_user_roles($user_id, $section_id);

        foreach ($roles as $role) {
            if ($role['role_name'] === $role_name) {
                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * Check if user has any of the specified roles
     *
     * @param int $user_id User ID
     * @param array $role_names Array of role names
     * @param int $section_id Section ID (NULL for global roles)
     * @return bool TRUE if user has any of the roles
     */
    public function has_any_role($user_id, $role_names, $section_id = NULL)
    {
        foreach ($role_names as $role_name) {
            if ($this->has_role($user_id, $role_name, $section_id)) {
                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * Grant a role to a user
     *
     * @param int $user_id User ID to grant role to
     * @param int $types_roles_id Role ID
     * @param int $section_id Section ID
     * @param int $granted_by User ID who is granting the role
     * @param string $notes Optional notes
     * @return bool TRUE on success
     */
    public function grant_role($user_id, $types_roles_id, $section_id, $granted_by, $notes = NULL)
    {
        // Ensure all IDs are integers, but allow NULL for section_id
        $user_id = (int)$user_id;
        $types_roles_id = (int)$types_roles_id;
        $section_id = ($section_id !== NULL) ? (int)$section_id : NULL;
        $granted_by = (int)$granted_by;

        // Log parameters for debugging
        log_message('info', 'grant_role: user_id=' . $user_id . ', types_roles_id=' . $types_roles_id . ', section_id=' . ($section_id ?? 'NULL') . ', granted_by=' . $granted_by);

        // Check if role is already granted and active
        $this->CI->db
            ->where('user_id', $user_id)
            ->where('types_roles_id', $types_roles_id)
            ->where('revoked_at IS NULL');
        
        if ($section_id === NULL) {
            $this->CI->db->where('section_id IS NULL');
        } else {
            $this->CI->db->where('section_id', $section_id);
        }
        
        $existing = $this->CI->db->get('user_roles_per_section')->row_array();

        if ($existing) {
            log_message('info', 'grant_role: Role already exists for user=' . $user_id . ', role=' . $types_roles_id . ', section=' . ($section_id ?? 'NULL'));
            return 'EXISTS'; // Already has this role - not an error
        }

        // Insert new role assignment
        $data = array(
            'user_id' => $user_id,
            'types_roles_id' => $types_roles_id,
            'section_id' => $section_id,
            'granted_by' => $granted_by,
            'granted_at' => date('Y-m-d H:i:s'),
            'notes' => $notes
        );

        log_message('info', 'grant_role: Inserting role assignment: ' . json_encode($data));

        $result = $this->CI->db->insert('user_roles_per_section', $data);

        if (!$result) {
            $error_num = $this->CI->db->_error_number();
            $error_msg = $this->CI->db->_error_message();
            log_message('error', 'grant_role: Database insert FAILED. Error code=' . $error_num . ', message=' . $error_msg);
            return FALSE;
        }

        // Success - clear cache and audit
        log_message('info', 'grant_role: SUCCESS - Role granted to user=' . $user_id);
        $this->clear_cache($user_id);
        $this->_audit_log('grant_role', $granted_by, $user_id, $types_roles_id, $section_id, $notes);

        return TRUE;
    }

    /**
     * Revoke a role from a user
     *
     * @param int $user_id User ID
     * @param int $types_roles_id Role ID
     * @param int $section_id Section ID
     * @param int $revoked_by User ID who is revoking the role
     * @return bool TRUE on success
     */
    public function revoke_role($user_id, $types_roles_id, $section_id, $revoked_by)
    {
        // Ensure all IDs are integers, but allow NULL for section_id
        $user_id = (int)$user_id;
        $types_roles_id = (int)$types_roles_id;
        $section_id = ($section_id !== NULL) ? (int)$section_id : NULL;
        $revoked_by = (int)$revoked_by;

        log_message('info', 'revoke_role: user_id=' . $user_id . ', types_roles_id=' . $types_roles_id . ', section_id=' . ($section_id ?? 'NULL') . ', revoked_by=' . $revoked_by);

        $this->CI->db
            ->where('user_id', $user_id)
            ->where('types_roles_id', $types_roles_id)
            ->where('revoked_at IS NULL');

        if ($section_id === NULL) {
            $this->CI->db->where('section_id IS NULL');
        } else {
            $this->CI->db->where('section_id', $section_id);
        }

        $result = $this->CI->db->update('user_roles_per_section', array(
                'revoked_at' => date('Y-m-d H:i:s')
            ));

        if ($result) {
            log_message('info', 'revoke_role: SUCCESS - Role revoked from user=' . $user_id);
            $this->clear_cache($user_id);
            $this->_audit_log('revoke_role', $revoked_by, $user_id, $types_roles_id, $section_id, NULL);
            return TRUE;
        }

        log_message('error', 'revoke_role: FAILED - Role not found or already revoked for user=' . $user_id);
        return FALSE;
    }

    /**
     * Check if new authorization system should be used
     *
     * @return bool TRUE if new system is enabled
     */
    public function use_new_system()
    {
        return $this->use_new_system;
    }

    /**
     * Clear permission cache
     *
     * @param int $user_id Optional user ID to clear specific user cache
     */
    public function clear_cache($user_id = NULL)
    {
        if ($user_id === NULL) {
            $this->cache = array();
        } else {
            // Clear only this user's cache entries
            foreach (array_keys($this->cache) as $key) {
                if (strpos($key, "_{$user_id}_") !== FALSE || strpos($key, "roles_{$user_id}") !== FALSE) {
                    unset($this->cache[$key]);
                }
            }
        }
    }

    // ========================================================================
    // CODE-BASED PERMISSIONS API (v2.0 - Phase 7)
    // ========================================================================

    /**
     * Require specific roles for controller access (code-based permissions)
     *
     * This method declares which roles are required to access a controller or action.
     * Called in controller constructor for default permissions, or in specific methods
     * for method-level overrides.
     *
     * @param array|string $roles Role name(s) required (e.g., 'ca', ['planchiste', 'ca'])
     * @param int $section_id Section ID for section-scoped roles (NULL for global)
     * @param bool $replace TRUE to replace previous requirements, FALSE to add to them
     * @return bool TRUE if user has required role, FALSE otherwise
     *
     * @example
     * // In controller constructor (default for all methods)
     * $this->gvv_authorization->require_roles(['ca', 'bureau'], $this->section_id);
     *
     * // In specific method (override default)
     * $this->gvv_authorization->require_roles('user', NULL, TRUE);
     */
    public function require_roles($roles, $section_id = NULL, $replace = TRUE)
    {
        // Normalize to array
        if (!is_array($roles)) {
            $roles = array($roles);
        }

        // Get current user ID
        if (!isset($this->CI->dx_auth)) {
            log_message('error', 'GVV_Auth: DX_Auth not loaded, cannot check roles');
            return FALSE;
        }

        $user_id = $this->CI->dx_auth->get_user_id();
        if (!$user_id) {
            log_message('info', 'GVV_Auth: No user logged in for require_roles check');
            $this->CI->dx_auth->deny_access();
            return FALSE;
        }

        // Check if user has any of the required roles
        foreach ($roles as $role_name) {
            if ($this->has_role($user_id, $role_name, $section_id)) {
                log_message('debug', "GVV_Auth: User {$user_id} has required role '{$role_name}'");
                return TRUE;
            }
        }

        // User doesn't have any required role - deny access
        log_message('info', "GVV_Auth: User {$user_id} does not have required roles: " . implode(', ', $roles));
        $this->_audit_log('access_denied', NULL, $user_id, NULL, $section_id, json_encode(array(
            'required_roles' => $roles,
            'reason' => 'Missing required role'
        )));

        $this->CI->dx_auth->deny_access();
        return FALSE;
    }

    /**
     * Allow additional roles for controller/action access (additive)
     *
     * This method adds additional roles to the allowed set without replacing
     * the base requirements. Useful for method-level exceptions.
     *
     * @param array|string $roles Role name(s) to allow additionally
     * @param int $section_id Section ID for section-scoped roles (NULL for global)
     * @return bool TRUE if user has any allowed role, FALSE otherwise
     *
     * @example
     * // In controller constructor
     * $this->gvv_authorization->require_roles(['planchiste'], $this->section_id);
     *
     * // In specific method (allow auto_planchiste in addition to planchiste)
     * if (!$this->gvv_authorization->allow_roles('auto_planchiste', $this->section_id)) {
     *     show_error('Access denied');
     * }
     */
    public function allow_roles($roles, $section_id = NULL)
    {
        // Normalize to array
        if (!is_array($roles)) {
            $roles = array($roles);
        }

        // Get current user ID
        if (!isset($this->CI->dx_auth)) {
            log_message('error', 'GVV_Auth: DX_Auth not loaded, cannot check roles');
            return FALSE;
        }

        $user_id = $this->CI->dx_auth->get_user_id();
        if (!$user_id) {
            log_message('debug', 'GVV_Auth: No user logged in for allow_roles check');
            return FALSE;
        }

        // Check if user has any of the allowed roles
        foreach ($roles as $role_name) {
            if ($this->has_role($user_id, $role_name, $section_id)) {
                log_message('debug', "GVV_Auth: User {$user_id} has allowed role '{$role_name}'");
                return TRUE;
            }
        }

        log_message('debug', "GVV_Auth: User {$user_id} does not have any of the allowed roles: " . implode(', ', $roles));
        return FALSE;
    }

    /**
     * Check if user can edit/access a specific data row (row-level security)
     *
     * This method combines user role information with row-level data access rules
     * to determine if a user can access a specific database row.
     *
     * @param int $user_id User ID (NULL to use current user)
     * @param string $table_name Database table name
     * @param array $row_data Row data to check (must include ownership/section fields)
     * @param int $section_id Section ID for the check
     * @param string $access_type Type of access (view, edit, delete)
     * @return bool TRUE if user can access the row, FALSE otherwise
     *
     * @example
     * // Check if user can edit their own flight
     * $vol = $this->vols_model->get($id);
     * if (!$this->gvv_authorization->can_edit_row(NULL, 'vols', $vol, $this->section_id, 'edit')) {
     *     show_error('You can only edit your own flights');
     * }
     */
    public function can_edit_row($user_id, $table_name, $row_data, $section_id, $access_type = 'edit')
    {
        // Use current user if not specified
        if ($user_id === NULL) {
            if (!isset($this->CI->dx_auth)) {
                log_message('error', 'GVV_Auth: DX_Auth not loaded, cannot check row access');
                return FALSE;
            }
            $user_id = $this->CI->dx_auth->get_user_id();
        }

        if (!$user_id) {
            log_message('debug', 'GVV_Auth: No user ID for can_edit_row check');
            return FALSE;
        }

        // Use the existing can_access_data method
        return $this->can_access_data($user_id, $table_name, $row_data, $section_id, $access_type);
    }

    // ========================================================================
    // PRIVATE HELPER METHODS
    // ========================================================================

    /**
     * Check if a role has permission for controller/action
     *
     * @param int $types_roles_id Role ID
     * @param string $controller Controller name
     * @param string $action Action name (NULL for any)
     * @param int $section_id Section ID
     * @return bool TRUE if permission exists
     */
    private function _role_has_permission($types_roles_id, $controller, $action, $section_id)
    {
        // Check exact match (controller + action)
        if ($action !== NULL) {
            $exact = $this->CI->db
                ->where('types_roles_id', $types_roles_id)
                ->where('controller', $controller)
                ->where('action', $action)
                ->where('section_id', $section_id)
                ->get('role_permissions')
                ->row_array();

            if ($exact) {
                return TRUE;
            }
        }

        // Check wildcard (controller + NULL action means all actions)
        $wildcard = $this->CI->db
            ->where('types_roles_id', $types_roles_id)
            ->where('controller', $controller)
            ->where('action IS NULL')
            ->where('section_id', $section_id)
            ->get('role_permissions')
            ->row_array();

        return !empty($wildcard);
    }

    /**
     * Check data access rule against row data
     *
     * @param array $rule Data access rule
     * @param array $row_data Row data to check
     * @param int $user_id User ID
     * @param int $section_id Section ID
     * @return bool TRUE if access granted by this rule
     */
    private function _check_data_access_rule($rule, $row_data, $user_id, $section_id)
    {
        switch ($rule['access_scope']) {
            case 'all':
                // Access to all data
                return TRUE;

            case 'section':
                // Check section field matches
                if ($rule['section_field'] && isset($row_data[$rule['section_field']])) {
                    return $row_data[$rule['section_field']] == $section_id;
                }
                return FALSE;

            case 'own':
                // Check ownership field matches user
                if ($rule['field_name'] && isset($row_data[$rule['field_name']])) {
                    return $row_data[$rule['field_name']] == $user_id;
                }
                return FALSE;

            default:
                return FALSE;
        }
    }

    /**
     * Log access granted event
     *
     * @param int $user_id User ID
     * @param string $controller Controller name
     * @param string $action Action name
     * @param int $section_id Section ID
     * @param string $role_name Role that granted access
     */
    private function _log_access_granted($user_id, $controller, $action, $section_id, $role_name)
    {
        // Only log in debug mode to avoid excessive logging
        if (ENVIRONMENT === 'development') {
            log_message('debug', "GVV_Auth: Access granted - user={$user_id}, controller={$controller}, action={$action}, section={$section_id}, role={$role_name}");
        }
    }

    /**
     * Log access denied event
     *
     * @param int $user_id User ID
     * @param string $controller Controller name
     * @param string $action Action name
     * @param int $section_id Section ID
     * @param string $reason Reason for denial
     */
    private function _log_access_denied($user_id, $controller, $action, $section_id, $reason)
    {
        log_message('info', "GVV_Auth: Access denied - user={$user_id}, controller={$controller}, action={$action}, section={$section_id}, reason={$reason}");

        // Log to audit table
        $this->_audit_log('access_denied', NULL, $user_id, NULL, $section_id, json_encode(array(
            'controller' => $controller,
            'action' => $action,
            'reason' => $reason
        )));
    }

    /**
     * Write to authorization audit log
     *
     * @param string $action_type Action type
     * @param int $actor_user_id User performing action
     * @param int $target_user_id User affected by action
     * @param int $types_roles_id Role ID
     * @param int $section_id Section ID
     * @param string $details Additional details (JSON or text)
     */
    private function _audit_log($action_type, $actor_user_id, $target_user_id, $types_roles_id, $section_id, $details)
    {
        $data = array(
            'action_type' => $action_type,
            'actor_user_id' => $actor_user_id,
            'target_user_id' => $target_user_id,
            'types_roles_id' => $types_roles_id,
            'section_id' => $section_id,
            'ip_address' => $this->CI->input->ip_address(),
            'details' => $details,
            'created_at' => date('Y-m-d H:i:s')
        );

        $this->CI->db->insert('authorization_audit_log', $data);
    }
}
