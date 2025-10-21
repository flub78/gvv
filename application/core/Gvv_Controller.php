<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * GVV Base Controller
 *
 * Provides dual-mode authorization support for progressive migration
 * from DX_Auth to Gvv_Authorization system.
 *
 * Part of Phase 6: Progressive Migration - Dual Mode
 *
 * @package    GVV
 * @subpackage Core
 * @author     GVV Development Team
 * @date       2025-10-21
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

        // Load authentication libraries
        $this->load->library('dx_auth');

        // Load new authorization system (always loaded for dual-mode comparison)
        $this->load->library('Gvv_Authorization');
        $this->load->model('Authorization_model');

        // Initialize user authentication
        $this->_init_auth();
    }

    /**
     * Initialize authentication and determine which system to use
     *
     * Checks user login status via DX_Auth and then determines whether
     * to route authorization checks through the new or legacy system
     * based on the user's migration status.
     *
     * @return void
     */
    private function _init_auth()
    {
        // Check if user is logged in (via DX_Auth session)
        if (!$this->dx_auth->is_logged_in()) {
            return; // Not logged in - handled by individual controllers
        }

        // Get user ID from session
        $this->user_id = $this->dx_auth->get_user_id();

        // Check migration status for this user
        $migration = $this->authorization_model->get_migration_status($this->user_id);

        if ($migration && $migration['use_new_system'] == 1) {
            $this->use_new_auth = TRUE;
            $this->migration_status = $migration['migration_status'];
            log_message('debug', "GVV_Controller: User {$this->user_id} using NEW authorization system (status: {$this->migration_status})");
        } else {
            $this->use_new_auth = FALSE;
            log_message('debug', "GVV_Controller: User {$this->user_id} using LEGACY authorization system");
        }
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
}
