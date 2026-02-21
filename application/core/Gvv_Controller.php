<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * GVV Base Controller
 *
 * Provides dual-mode authorization support for progressive migration
 * from DX_Auth to Gvv_Authorization system.
 *
 * Authorization system selection based on configuration:
 * - If use_new_authorization = FALSE: All users use legacy DX_Auth
 * - If use_new_authorization = TRUE && authorization_progressive_migration = FALSE:
 *   All users use new Gvv_Authorization system (global migration)
 * - If use_new_authorization = TRUE && authorization_progressive_migration = TRUE:
 *   Per-user migration based on authorization_migration_status table
 *
 * Part of Phase 6: Progressive Migration - Dual Mode
 * Updated: v2.2 - Feature flag support for global migration
 *
 * @package    GVV
 * @subpackage Core
 * @author     GVV Development Team
 * @date       2025-10-21 (Updated: 2025-10-26)
 */
class Gvv_Controller extends CI_Controller
{
    /**
     * Current user ID from session
     * @var int|null
     */
    protected $user_id;

    /**
     * Whether to use new authorization system for this user
     * @var bool
     */
    protected $use_new_auth = FALSE;

    /**
     * Migration status for current user
     * @var string|null pending|in_progress|completed|failed
     */
    protected $migration_status = NULL;

    /**
     * Constructor
     *
     * Initializes authentication libraries and determines which
     * authorization system to use based on user migration status.
     */
    public function __construct()
    {
        parent::__construct();

        // Note: dx_auth is autoloaded in application/config/autoload.php

        // Load new authorization system (always loaded for dual-mode comparison)
        $this->load->library('Gvv_Authorization');
        $this->load->model('authorization_model');

        // Initialize user authentication
        $this->_init_auth();
    }

    /**
     * Initialize authentication and determine which system to use
     *
     * Checks user login status via DX_Auth and then determines whether
     * to route authorization checks through the new or legacy system.
     *
     * Decision flow (Priority Order):
     * 1. Check if username exists in `use_new_authorization` table → NEW system
     * 2. Check global flag `use_new_authorization`:
     *    - If TRUE → NEW system for all users
     *    - If FALSE → LEGACY system for all users
     *
     * This enables progressive per-user migration:
     * - Phase M2-M3: Flag = FALSE, table has dev/pilot users → Only listed users use new system
     * - Phase M4: Flag = TRUE → All users use new system (table ignored)
     * - Rollback: Remove user from table (per-user) or set flag = FALSE (global)
     *
     * @see doc/prds/2025_authorization_refactoring_prd.md Section 6.1
     * @see doc/plans_and_progress/2025_authorization_refactoring_plan.md Phases M2-M5
     * @return void
     */
    private function _init_auth()
    {
        // Check if user is logged in (via DX_Auth session)
        if (!$this->dx_auth->is_logged_in()) {
            return; // Not logged in - handled by individual controllers
        }

        // Get user ID and username from session
        $this->user_id = $this->dx_auth->get_user_id();
        $username = $this->dx_auth->get_username();

        // Ensure section_selector is always available in session
        // This handles cases where session data is partially lost
        if (!$this->session->userdata('section_selector')) {
            $this->load->model('sections_model');
            $section_selector = $this->sections_model->selector_with_all();
            $this->session->set_userdata('section_selector', $section_selector);
            log_message('debug', "GVV_Controller: Reinitialized section_selector in session");
        }

        // Load config to check global authorization flag
        $this->config->load('gvv_config', TRUE);
        $use_new_authorization = $this->config->item('use_new_authorization', 'gvv_config');

        // Priority 1: Check if user is in per-user migration table
        if (!$use_new_authorization) {
            // Global flag is FALSE - check if this specific user should use new system
            log_message('debug', "GVV_Controller: Global flag is FALSE, checking per-user migration table for '$username'");

            try {
                $this->db->where('username', $username);
                $query = $this->db->get('use_new_authorization');
            } catch (Exception $e) {
                log_message('error', "GVV_Controller: Database error querying use_new_authorization table: " . $e->getMessage());
                $query = FALSE;
            }

            $row_count = $query ? $query->num_rows() : 0;
            log_message('debug', "GVV_Controller: Per-user table query returned {$row_count} rows");

            if ($query && $query->num_rows() > 0) {
                // User is in migration table - use NEW system
                $this->use_new_auth = TRUE;
                $this->migration_status = 'per_user_pilot';
                log_message('debug', "GVV_Controller: User '$username' (ID: {$this->user_id}) using NEW authorization (per-user migration)");

                // Check if user has 'user' role for current section (non-hierarchical: login requires 'user' role)
                $this->_check_login_permission();
                return;
            } else {
                log_message('debug', "GVV_Controller: User '$username' NOT in per-user migration table, will use legacy system");
            }
        } else {
            log_message('debug', "GVV_Controller: Global flag is TRUE, skipping per-user table check");
        }

        // Priority 2: Use global flag
        if ($use_new_authorization) {
            // Global flag enabled - all users use new system
            $this->use_new_auth = TRUE;
            $this->migration_status = 'global_enabled';
            log_message('debug', "GVV_Controller: User '$username' (ID: {$this->user_id}) using NEW authorization (global flag)");

            // Check if user has 'user' role for current section (non-hierarchical: login requires 'user' role)
            $this->_check_login_permission();
        } else {
            // Global flag disabled and user not in migration table - use legacy system
            $this->use_new_auth = FALSE;
            $this->migration_status = 'legacy';
            log_message('debug', "GVV_Controller: User '$username' (ID: {$this->user_id}) using LEGACY authorization");
        }
    }

    /**
     * Check if user has login permission (non-hierarchical: requires 'user' role)
     *
     * In the new non-hierarchical authorization system, login access is separate from
     * other permissions. A user MUST have at least the 'user' role for the current
     * section to be allowed to login.
     *
     * This enforces the principle: "You have a right to login AND you have a right to
     * edit flights - they are unrelated."
     *
     * @return void Dies with error message if user lacks 'user' role
     */
    private function _check_login_permission()
    {
        // Get current section
        $section_id = $this->session->userdata('section');

        log_message('debug', "GVV_Controller: _check_login_permission called for user_id={$this->user_id}, section_id={$section_id}");

        // If no section is set, auto-select first section where user has 'user' role
        if (!$section_id) {
            log_message('debug', "GVV_Controller: No section in session, auto-selecting first available section for user");

            $this->db->where('user_id', $this->user_id);
            $this->db->where('types_roles_id', 1); // 'user' role
            $this->db->where('revoked_at IS NULL');
            $this->db->order_by('section_id', 'ASC');
            $this->db->limit(1);
            $query = $this->db->get('user_roles_per_section');

            if ($query && $query->num_rows() > 0) {
                $section_id = $query->row()->section_id;
                $this->session->set_userdata('section', $section_id);
                log_message('debug', "GVV_Controller: Auto-selected section {$section_id} for user");
            } else {
                // User has no 'user' role in any section - deny login
                $username = $this->dx_auth->get_username();
                log_message('error', "GVV_Controller: User '$username' (ID: {$this->user_id}) has no 'user' role in ANY section");
                $this->dx_auth->logout();
                redirect('auth/login?error=no_user_role');
            }
        }

        // Check if user has the 'user' role (role_id = 1) for this section
        // Non-hierarchical: planchiste (5), ca (6), etc. do NOT imply 'user' role
        // User must explicitly have role_id = 1 to login
        $this->db->where('user_id', $this->user_id);
        $this->db->where('section_id', $section_id);
        $this->db->where('types_roles_id', 1); // Specifically 'user' role (id=1)
        $this->db->where('revoked_at IS NULL'); // IS NULL check - must use string form
        $query = $this->db->get('user_roles_per_section');

        // Log the generated SQL query for debugging
        log_message('debug', "GVV_Controller: SQL query: " . $this->db->last_query());

        $has_user_role = ($query && $query->num_rows() > 0);
        log_message('debug', "GVV_Controller: Query returned " . ($query ? $query->num_rows() : 0) . " rows, has_user_role=" . ($has_user_role ? 'true' : 'false'));

        if (!$has_user_role) {
            // User does NOT have 'user' role for this section - deny login
            $username = $this->dx_auth->get_username();
            log_message('error', "GVV_Controller: User '$username' (ID: {$this->user_id}) denied login - no 'user' role (id=1) for section {$section_id}");

            // Log what roles they DO have for debugging
            $this->db->where('user_id', $this->user_id);
            $this->db->where('section_id', $section_id);
            $this->db->where('revoked_at IS NULL');
            $this->db->select('types_roles_id');
            $other_roles = $this->db->get('user_roles_per_section');
            if ($other_roles && $other_roles->num_rows() > 0) {
                $role_ids = array();
                foreach ($other_roles->result() as $row) {
                    $role_ids[] = $row->types_roles_id;
                }
                log_message('error', "GVV_Controller: User has other roles: " . implode(', ', $role_ids) . " but NOT 'user' role (1)");
            } else {
                log_message('error', "GVV_Controller: User has NO roles at all for section {$section_id}");
            }

            // Log out the user (destroys session)
            $this->dx_auth->logout();

            // Redirect to login page with error parameter
            // Use URL parameter since session is destroyed by logout
            redirect('auth/login?error=no_user_role');
        }

        log_message('debug', "GVV_Controller: User (ID: {$this->user_id}) login authorized for section {$section_id} - has 'user' role (id=1)");
    }

    /**
     * Require specific roles for controller/action access (helper)
     *
     * Wrapper for Gvv_Authorization::require_roles(). Automatically loads
     * the authorization library if not already loaded.
     *
     * @param array|string $roles Role name(s) required
     * @param int $section_id Section ID (NULL for global, defaults to session section)
     * @param bool $replace TRUE to replace previous requirements
     * @return bool TRUE if user has required role
     */
    protected function require_roles($roles, $section_id = NULL, $replace = TRUE)
    {
        // Load authorization library if not loaded
        if (!isset($this->gvv_authorization)) {
            $this->load->library('Gvv_Authorization');
        }

        // Use session section_id if available and not specified
        if ($section_id === NULL) {
            $section_id = $this->session->userdata('section');
        }

        return $this->gvv_authorization->require_roles($roles, $section_id, $replace);
    }

    /**
     * Check if user can access a controller/action
     *
     * Routes authorization check to appropriate system (new or legacy)
     * based on user's migration status. Also performs comparison logging
     * for pilot users to detect authorization discrepancies.
     *
     * @param string $controller Controller name (defaults to current)
     * @param string $action     Action name (defaults to current)
     * @return bool TRUE if access granted, FALSE otherwise
     */
    protected function _check_access($controller = NULL, $action = NULL)
    {
        // Ensure user is logged in
        if (!$this->user_id) {
            return FALSE;
        }

        // Default to current controller/method
        if ($controller === NULL) {
            $controller = $this->router->class;
        }
        if ($action === NULL) {
            $action = $this->router->method;
        }

        // Get current section from session
        $section_id = $this->session->userdata('section');

        if ($this->use_new_auth) {
            // Use new authorization system
            $has_access = $this->gvv_authorization->can_access(
                $this->user_id,
                $controller,
                $action,
                $section_id
            );

            // Log comparison in dual-mode for validation
            if ($this->_is_dual_mode_logging_enabled()) {
                $legacy_access = $this->_check_legacy_access($controller, $action);
                $this->_log_authorization_comparison(
                    $controller,
                    $action,
                    $section_id,
                    $has_access,
                    $legacy_access
                );
            }

            return $has_access;
        } else {
            // Use legacy DX_Auth system
            return $this->_check_legacy_access($controller, $action);
        }
    }

    /**
     * Check access using legacy DX_Auth permissions
     *
     * This method replicates the legacy authorization logic found across
     * the codebase, using DX_Auth role checks. It serves as the baseline
     * for comparison during dual-mode migration.
     *
     * Legacy patterns observed:
     * - is_role('role_name') for specific role checks
     * - is_admin() for admin access
     * - Custom controller-specific logic
     *
     * @param string $controller Controller name
     * @param string $action     Action name
     * @return bool TRUE if access granted
     */
    private function _check_legacy_access($controller, $action)
    {
        // Legacy authorization logic based on observed patterns
        // This is a simplified implementation - real logic varies by controller

        // Map controller/action patterns to required roles
        $role_requirements = array(
            'vols_planeur/create'       => 'planchiste',
            'vols_planeur/edit'         => 'planchiste',
            'vols_planeur/delete'       => 'planchiste',
            'vols_planeur/plancheauto'  => 'planchiste',
            'vols_avion/create'         => 'planchiste',
            'vols_avion/edit'           => 'planchiste',
            'vols_avion/delete'         => 'planchiste',
            'welcome/compta'            => 'tresorier',
            'welcome/ca'                => 'ca',
            'membre/export'             => 'ca',
            'sections/export'           => 'ca',
            'planeur/create'            => 'ca',
            'planeur/export'            => 'ca',
            'avion/create'              => 'ca',
            'avion/export'              => 'ca',
            'procedures/delete'         => 'admin',
            'vols_decouverte/export'    => 'ca',
        );

        $key = strtolower($controller . '/' . $action);

        // Check if specific role is required
        if (isset($role_requirements[$key])) {
            $required_role = $role_requirements[$key];
            return $this->dx_auth->is_role($required_role);
        }

        // Default: grant access if logged in (legacy behavior)
        // Many controllers just check is_logged_in() without specific role checks
        return TRUE;
    }

    /**
     * Check if dual-mode comparison logging is enabled
     *
     * Logging is enabled for pilot users in 'in_progress' or 'completed'
     * migration status to capture authorization discrepancies.
     *
     * @return bool TRUE if logging should occur
     */
    private function _is_dual_mode_logging_enabled()
    {
        return $this->migration_status === 'in_progress' ||
               $this->migration_status === 'completed';
    }

    /**
     * Log authorization comparison for validation
     *
     * Records authorization checks to database for analysis. Divergences
     * (new != legacy) are highlighted in logs and dashboard for review.
     *
     * @param string $controller       Controller name
     * @param string $action           Action name
     * @param int    $section_id       Section context
     * @param bool   $new_result       Result from new system
     * @param bool   $legacy_result    Result from legacy system
     * @return void
     */
    private function _log_authorization_comparison($controller, $action, $section_id, $new_result, $legacy_result)
    {
        // Get detailed information for logging
        $new_details = $this->_get_new_system_details($controller, $action, $section_id);
        $legacy_details = $this->_get_legacy_system_details($controller, $action);

        // Log to application log if there's a divergence
        if ($new_result !== $legacy_result) {
            log_message('warning',
                "GVV_Controller: Authorization mismatch for user {$this->user_id}: " .
                "controller={$controller}, action={$action}, " .
                "section={$section_id}, new={$new_result}, legacy={$legacy_result}"
            );
        }

        // Store comparison in database for dashboard
        $this->db->insert('authorization_comparison_log', array(
            'user_id'                 => $this->user_id,
            'controller'              => $controller,
            'action'                  => $action,
            'section_id'              => $section_id,
            'new_system_result'       => $new_result ? 1 : 0,
            'legacy_system_result'    => $legacy_result ? 1 : 0,
            'new_system_details'      => json_encode($new_details),
            'legacy_system_details'   => json_encode($legacy_details),
            'created_at'              => date('Y-m-d H:i:s')
        ));
    }

    /**
     * Get detailed information from new authorization system
     *
     * @param string $controller  Controller name
     * @param string $action      Action name
     * @param int    $section_id  Section context
     * @return array Details array for JSON storage
     */
    private function _get_new_system_details($controller, $action, $section_id)
    {
        // Get user roles in current section
        $roles = $this->authorization_model->get_user_roles($this->user_id, $section_id);

        // Get permissions for controller/action
        $permission_id = $this->authorization_model->get_permission_id($controller, $action);

        return array(
            'roles'         => $roles,
            'controller'    => $controller,
            'action'        => $action,
            'section_id'    => $section_id,
            'permission_id' => $permission_id,
            'system'        => 'Gvv_Authorization'
        );
    }

    /**
     * Get detailed information from legacy authorization system
     *
     * @param string $controller Controller name
     * @param string $action     Action name
     * @return array Details array for JSON storage
     */
    private function _get_legacy_system_details($controller, $action)
    {
        return array(
            'current_role'  => $this->dx_auth->get_role_name(),
            'is_admin'      => $this->dx_auth->is_admin(),
            'controller'    => $controller,
            'action'        => $action,
            'system'        => 'DX_Auth'
        );
    }

    /**
     * Deny access to current request
     *
     * Routes to appropriate access denial view based on authorization system.
     *
     * @param string $redirect_to Optional redirect destination
     * @return void
     */
    protected function _deny_access($redirect_to = '')
    {
        if ($this->use_new_auth) {
            // Use new authorization deny view
            $this->load->view('authorization/access_denied');
        } else {
            // Use legacy DX_Auth deny view
            if ($redirect_to) {
                $this->dx_auth->deny_access($redirect_to);
            } else {
                $this->dx_auth->deny_access();
            }
        }
    }

    /**
     * Check if user has a specific role (compatibility wrapper)
     *
     * Provides backward compatibility with existing controller code
     * that uses $this->dx_auth->is_role()
     *
     * @param string $role_name Role name to check
     * @return bool TRUE if user has role
     */
    protected function _has_role($role_name)
    {
        if ($this->use_new_auth) {
            // Use new system: check if user has role in current section
            $section_id = $this->session->userdata('section');
            return $this->gvv_authorization->user_has_role(
                $this->user_id,
                $role_name,
                $section_id
            );
        } else {
            // Use legacy system
            return $this->dx_auth->is_role($role_name);
        }
    }

    /**
     * Check if current user uses the new authorization system
     *
     * Public accessor for views and helper functions.
     *
     * @return bool TRUE if new authorization system is active for this user
     */
    public function uses_new_auth()
    {
        return $this->use_new_auth;
    }
}
