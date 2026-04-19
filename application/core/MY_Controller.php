<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * GVV Base Controller
 *
 * Common ancestor for all GVV controllers. Provides dual-mode authorization
 * support for the progressive migration from DX_Auth to Gvv_Authorization.
 *
 * Authorization system selection based on configuration:
 * - If use_new_authorization = FALSE: All users use legacy DX_Auth
 * - If use_new_authorization = TRUE: All users use new Gvv_Authorization system
 * - Per-user migration via use_new_authorization table (when global flag is FALSE)
 *
 * @package    GVV
 * @subpackage Core
 */
class MY_Controller extends CI_Controller
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
     * @var string|null
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

        $this->load->library('Gvv_Authorization');

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
            log_message('debug', "MY_Controller: Reinitialized section_selector in session");
        }

        // Load config to check global authorization flag
        $this->config->load('gvv_config', TRUE);
        $use_new_authorization = $this->config->item('use_new_authorization', 'gvv_config');

        // Priority 1: Check if user is in per-user migration table
        if (!$use_new_authorization) {
            log_message('debug', "MY_Controller: Global flag is FALSE, checking per-user migration table for '$username'");

            try {
                $this->db->where('username', $username);
                $query = $this->db->get('use_new_authorization');
            } catch (Exception $e) {
                log_message('error', "MY_Controller: Database error querying use_new_authorization table: " . $e->getMessage());
                $query = FALSE;
            }

            $row_count = $query ? $query->num_rows() : 0;
            log_message('debug', "MY_Controller: Per-user table query returned {$row_count} rows");

            if ($row_count > 0) {
                $this->use_new_auth = TRUE;
                $this->migration_status = 'per_user_pilot';
                log_message('debug', "MY_Controller: User '$username' (ID: {$this->user_id}) using NEW authorization (per-user migration)");

                $this->session->set_userdata('use_new_auth', TRUE);

                // Check if user has 'user' role for current section
                $this->_check_login_permission();
                return;
            } else {
                log_message('debug', "MY_Controller: User '$username' NOT in per-user migration table, will use legacy system");
            }
        } else {
            log_message('debug', "MY_Controller: Global flag is TRUE, skipping per-user table check");
        }

        // Priority 2: Use global flag
        if ($use_new_authorization) {
            $this->use_new_auth = TRUE;
            $this->migration_status = 'global_enabled';
            log_message('debug', "MY_Controller: User '$username' (ID: {$this->user_id}) using NEW authorization (global flag)");

            // Check if user has 'user' role for current section
            $this->_check_login_permission();
        } else {
            $this->use_new_auth = FALSE;
            $this->migration_status = 'legacy';
            log_message('debug', "MY_Controller: User '$username' (ID: {$this->user_id}) using LEGACY authorization");
        }

        $this->session->set_userdata('use_new_auth', $this->use_new_auth);
    }

    /**
     * Check if user has login permission (non-hierarchical: requires 'user' role)
     *
     * A user MUST have at least the 'user' role for the current section
     * to be allowed to login in the new authorization system.
     *
     * @return void Redirects to login if user lacks 'user' role
     */
    private function _check_login_permission()
    {
        $section_id = $this->session->userdata('section');

        // "Toutes" : section_id does not correspond to a real section — skip check.
        if ($section_id) {
            $q = $this->db->where('id', (int) $section_id)->get('sections');
            if ($q->num_rows() === 0) {
                log_message('debug', "MY_Controller: section_id={$section_id} is not a real section (Toutes mode), skipping login permission check");
                return;
            }
        }

        log_message('debug', "MY_Controller: _check_login_permission called for user_id={$this->user_id}, section_id={$section_id}");

        // If no section is set, auto-select first section where user has 'user' role
        if (!$section_id) {
            log_message('debug', "MY_Controller: No section in session, auto-selecting first available section for user");

            $this->db->where('user_id', $this->user_id);
            $this->db->where('types_roles_id', 1); // 'user' role
            $this->db->where('revoked_at IS NULL');
            $this->db->order_by('section_id', 'ASC');
            $this->db->limit(1);
            $query = $this->db->get('user_roles_per_section');

            if ($query && $query->num_rows() > 0) {
                $section_id = $query->row()->section_id;
                $this->session->set_userdata('section', $section_id);
                log_message('debug', "MY_Controller: Auto-selected section {$section_id} for user");
            } else {
                $username = $this->dx_auth->get_username();
                log_message('error', "MY_Controller: User '$username' (ID: {$this->user_id}) has no 'user' role in ANY section");
                $this->dx_auth->logout();
                redirect('auth/login?error=no_user_role');
            }
        }

        // Check if user has the 'user' role (role_id = 1) for this section
        $this->db->where('user_id', $this->user_id);
        $this->db->where('section_id', $section_id);
        $this->db->where('types_roles_id', 1);
        $this->db->where('revoked_at IS NULL');
        $query = $this->db->get('user_roles_per_section');

        log_message('debug', "MY_Controller: SQL query: " . $this->db->last_query());

        $has_user_role = ($query && $query->num_rows() > 0);
        log_message('debug', "MY_Controller: Query returned " . ($query ? $query->num_rows() : 0) . " rows, has_user_role=" . ($has_user_role ? 'true' : 'false'));

        if (!$has_user_role) {
            $username = $this->dx_auth->get_username();
            log_message('error', "MY_Controller: User '$username' (ID: {$this->user_id}) denied login - no 'user' role (id=1) for section {$section_id}");

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
                log_message('error', "MY_Controller: User has other roles: " . implode(', ', $role_ids) . " but NOT 'user' role (1)");
            } else {
                log_message('error', "MY_Controller: User has NO roles at all for section {$section_id}");
            }

            $this->dx_auth->logout();
            redirect('auth/login?error=no_user_role');
        }

        log_message('debug', "MY_Controller: User (ID: {$this->user_id}) login authorized for section {$section_id} - has 'user' role (id=1)");
    }

    /**
     * Require specific roles for controller/action access
     *
     * @param array|string $roles Role name(s) required
     * @param int $section_id Section ID (NULL defaults to session section)
     * @param bool $replace TRUE to replace previous requirements
     * @return bool TRUE if user has required role
     */
    protected function require_roles($roles, $section_id = NULL, $replace = TRUE)
    {
        if (!isset($this->gvv_authorization)) {
            $this->load->library('Gvv_Authorization');
        }

        if ($section_id === NULL) {
            $section_id = $this->session->userdata('section');
        }

        // Mode "Toutes" : section_id ne correspond pas à une vraie section → vérification globale (toutes sections)
        if ($section_id) {
            $q = $this->db->where('id', (int) $section_id)->get('sections');
            if ($q->num_rows() === 0) {
                $section_id = NULL;
            }
        }

        return $this->gvv_authorization->require_roles($roles, $section_id, $replace);
    }

    /**
     * Deny access to current request
     *
     * @param string $redirect_to Optional redirect destination
     */
    protected function _deny_access($redirect_to = '')
    {
        if ($this->use_new_auth) {
            $this->load->view('authorization/access_denied');
        } else {
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
     * @param string $role_name Role name to check
     * @return bool TRUE if user has role
     */
    protected function _has_role($role_name)
    {
        if ($this->use_new_auth) {
            $section_id = $this->session->userdata('section');
            return $this->gvv_authorization->user_has_role(
                $this->user_id,
                $role_name,
                $section_id
            );
        } else {
            return $this->dx_auth->is_role($role_name);
        }
    }

    /**
     * Check if current user uses the new authorization system
     *
     * @return bool
     */
    public function uses_new_auth()
    {
        return $this->use_new_auth;
    }

    /**
     * Push current URL onto the return-URL stack so pop_return_url() can go back here.
     * Call this at the start of every "main" page (lists, dashboards, journals).
     */
    function push_return_url($context) {
        $this->session->set_userdata('back_url', current_url());
        gvv_debug("push back_url  $context: " . current_url());

        $url_stack = $this->session->userdata('return_url_stack');
        if (!is_array($url_stack)) {
            $url_stack = array();
        }

        $url = current_url();
        if ($this->validate_return_url($url)) {
            array_push($url_stack, $url);
            $this->session->set_userdata('return_url_stack', $url_stack);
            $this->session->set_userdata('url_stack_time', time());
        }
    }

    /**
     * Redirect back to the most recent URL pushed by push_return_url().
     * Falls back to <controller>/page if the stack is empty.
     */
    function pop_return_url($skip = 0) {
        $this->clean_old_url_stack();

        $url_stack = $this->session->userdata('return_url_stack');

        if (!empty($url_stack) && $skip && $skip < count($url_stack)) {
            array_pop($url_stack);
            $this->session->set_userdata('return_url_stack', $url_stack);
        }

        while (!empty($url_stack)) {
            $url = array_pop($url_stack);
            $this->session->set_userdata('return_url_stack', $url_stack);
            if ($url != current_url()) {
                redirect($url);
            }
        }

        $controller = isset($this->controller) ? $this->controller : $this->router->fetch_class();
        gvv_debug("pop default back_url $controller/page");
        redirect($controller . "/page");
    }

    /**
     * Validate that a return URL is safe (internal only).
     */
    protected function validate_return_url($url) {
        return (strpos($url, base_url()) === 0);
    }

    /**
     * Wipe the URL stack if it is older than 1 hour (prevents stale redirects).
     */
    protected function clean_old_url_stack() {
        $stack_time = $this->session->userdata('url_stack_time');
        if (!$stack_time || (time() - $stack_time > 3600)) {
            $this->session->unset_userdata('return_url_stack');
            $this->session->unset_userdata('url_stack_time');
        }
    }
}
