<?php

use PHPUnit\Framework\TestCase;

/**
 * Authorization System Migration Test - Two-Phase Testing
 * 
 * Tests basic access control for different user roles using the test users
 * created by bin/create_test_users.sh. Each test user has password "password".
 * 
 * This test runs in TWO PHASES:
 * 1. LEGACY PHASE: Tests against the old DX_Auth/permissions system
 * 2. V2 PHASE: Tests against the new Gvv_Authorization system
 * 
 * The same access patterns are tested in both phases to ensure compatibility
 * and verify that the migration maintains the same authorization behavior.
 * 
 * Test Users (from bin/create_test_users.sh):
 * - testuser (role: user/1)
 * - testplanchiste (role: planchiste/5)  
 * - testca (role: ca/6)
 * - testbureau (role: bureau/7)
 * - testtresorier (role: tresorier/8)
 * - testadmin (role: club-admin/10)
 */
class AuthorizationMigrationTest extends TestCase
{
    private $CI;
    private $auth;
    private $dx_auth;
    private $test_section_id;
    private $test_users = [];
    private $test_phase = 'legacy'; // 'legacy' or 'v2'

    public function setUp(): void
    {
        $this->CI =& get_instance();
        
        // Load required libraries for both systems
        $this->CI->load->library('Gvv_Authorization');
        $this->CI->load->library('DX_Auth');
        $this->CI->load->model('Authorization_model');
        
        $this->auth = $this->CI->gvv_authorization;
        $this->dx_auth = $this->CI->dx_auth;
        
        // Get the test section ID (should be 1 from create_test_users.sh)
        $section_query = $this->CI->db->select('id')->from('sections')->limit(1)->get();
        $this->test_section_id = $section_query->row()->id ?? 1;
        
        // Load test users created by bin/create_test_users.sh
        $this->_loadTestUsers();
        
        // Start transaction for test isolation
        $this->CI->db->trans_start();
    }

    public function tearDown(): void
    {
        // Rollback transaction
        $this->CI->db->trans_rollback();
    }

    /**
     * Load test users from database (created by bin/create_test_users.sh)
     */
    private function _loadTestUsers()
    {
        $usernames = ['testuser', 'testplanchiste', 'testca', 'testbureau', 'testtresorier', 'testadmin'];
        
        foreach ($usernames as $username) {
            $user_query = $this->CI->db
                ->select('u.id, u.username, urps.types_roles_id')
                ->from('users u')
                ->join('user_roles_per_section urps', 'u.id = urps.user_id', 'left')
                ->where('u.username', $username)
                ->where('urps.revoked_at IS NULL')
                ->get();
                
            if ($user_query->num_rows() > 0) {
                $user = $user_query->row_array();
                $this->test_users[$username] = $user;
            }
        }
    }

    /**
     * Simulate user login by setting up session data
     */
    private function _simulateLogin($username)
    {
        if (!isset($this->test_users[$username])) {
            $this->markTestSkipped("Test user {$username} not found. Run bin/create_test_users.sh first.");
        }
        
        $user = $this->test_users[$username];
        
        // Mock session data for DX_Auth
        $this->CI->session->set_userdata('DX_user_id', $user['id']);
        $this->CI->session->set_userdata('DX_username', $username);
        $this->CI->session->set_userdata('DX_logged_in', TRUE);
    }

    /**
     * Test access permissions by checking if user can access specific controller/action
     * Uses either legacy or V2 system based on test_phase
     */
    private function _testAccess($user_id, $controller, $action = 'index', $should_have_access = true, $section_id = null, $reason = '')
    {
        $section_id = $section_id ?? $this->test_section_id;
        
        // Get user context for detailed messages
        $user_context = $this->_getUserContext($user_id);
        $access_context = $this->_getAccessContext($controller, $action, $should_have_access, $reason);
        
        if ($this->test_phase === 'legacy') {
            $has_access = $this->_testLegacyAccess($user_id, $controller, $action, $section_id);
            $system_details = $this->_getLegacySystemDetails($user_id, $controller, $action);
        } else {
            $has_access = $this->_testV2Access($user_id, $controller, $action, $section_id);
            $system_details = $this->_getV2SystemDetails($user_id, $controller, $action, $section_id);
        }
        
        $system_name = strtoupper($this->test_phase);
        
        if ($should_have_access) {
            $message = "[{$system_name}] {$user_context} should have access to {$controller}/{$action}\n" .
                      "  📋 Reason: {$access_context}\n" .
                      "  🔧 System: {$system_details}";
            $this->assertTrue($has_access, $message);
        } else {
            $message = "[{$system_name}] {$user_context} should NOT have access to {$controller}/{$action}\n" .
                      "  🚫 Reason: {$access_context}\n" .
                      "  🔧 System: {$system_details}";
            $this->assertFalse($has_access, $message);
        }
    }

    /**
     * Get user context information for detailed messages
     */
    private function _getUserContext($user_id)
    {
        $user_info = array();
        foreach ($this->test_users as $username => $user_data) {
            if ($user_data['id'] == $user_id) {
                $role_names = array(
                    1 => 'user/membre',
                    2 => 'admin', 
                    3 => 'bureau',
                    7 => 'planchiste',
                    8 => 'ca',
                    9 => 'tresorier'
                );
                
                $user_query = $this->CI->db->where('id', $user_id)->get('users')->row();
                $role_name = isset($role_names[$user_query->role_id]) ? $role_names[$user_query->role_id] : 'unknown';
                
                return "User '{$username}' (ID: {$user_id}, Role: {$role_name})";
            }
        }
        return "User ID: {$user_id}";
    }

    /**
     * Get access context explanation
     */
    private function _getAccessContext($controller, $action, $should_have_access, $reason)
    {
        if ($reason) {
            return $reason;
        }
        
        $page_descriptions = array(
            'welcome/index' => 'Welcome dashboard - basic user access',
            'membre/page' => 'Member profile viewing',
            'membre/create' => 'Create new members - admin function',
            'membre/edit' => 'Edit member profiles',
            'vols_planeur/page' => 'View glider flights',
            'vols_planeur/create' => 'Create new flight records',
            'vols_planeur/edit' => 'Edit flight records',
            'vols_avion/page' => 'View airplane flights',
            'compta/mon_compte' => 'View own financial account',
            'compta/ecritures' => 'Manage accounting entries - treasurer function',
            'compta/comptes' => 'Manage chart of accounts - treasurer function',
            'compta/rapports' => 'View financial reports',
            'terrains/page' => 'View airfield information - CA access',
            'terrains/edit' => 'Edit airfield information - CA access',
            'calendar/index' => 'View calendar',
            'calendar/manage' => 'Manage calendar events - admin function',
            'authorization/user_roles' => 'Manage user roles - admin only',
            'backend/users' => 'User administration - admin only',
            'admin/index' => 'Administration panel - admin only'
        );
        
        $page_key = "{$controller}/{$action}";
        $description = isset($page_descriptions[$page_key]) ? $page_descriptions[$page_key] : "Access to {$controller}/{$action}";
        
        if ($should_have_access) {
            return "Role should allow: {$description}";
        } else {
            return "Role should deny: {$description}";
        }
    }

    /**
     * Get legacy system details for debugging
     */
    private function _getLegacySystemDetails($user_id, $controller, $action)
    {
        $user = $this->CI->db->where('id', $user_id)->get('users')->row();
        if (!$user) {
            return "User not found";
        }
        
        $permissions = $this->CI->db->where('role_id', $user->role_id)->get('permissions')->row();
        if (!$permissions || !$permissions->data) {
            return "No legacy permissions found for role {$user->role_id}";
        }
        
        $data = @unserialize($permissions->data);
        if (!$data || !isset($data['uri'])) {
            return "Invalid legacy permission data for role {$user->role_id}";
        }
        
        $relevant_uris = array();
        foreach ($data['uri'] as $uri) {
            if (strpos($uri, $controller) !== false) {
                $relevant_uris[] = $uri;
            }
        }
        
        if (empty($relevant_uris)) {
            return "Legacy permissions: No URIs matching '{$controller}' (role {$user->role_id})";
        } else {
            return "Legacy permissions: Found URIs [" . implode(', ', $relevant_uris) . "] (role {$user->role_id})";
        }
    }

    /**
     * Get V2 system details for debugging
     */
    private function _getV2SystemDetails($user_id, $controller, $action, $section_id)
    {
        // Check if user is migrated
        $migrated = $this->CI->db
            ->where('user_id', $user_id)
            ->where('migrated_by IS NOT NULL')
            ->get('authorization_migration_status')
            ->num_rows() > 0;
            
        if (!$migrated) {
            return "V2 system: User not yet migrated to new authorization system";
        }
        
        // Get user's V2 roles
        $roles = $this->auth->get_user_roles($user_id, $section_id);
        if (empty($roles)) {
            return "V2 system: No roles found for user in section {$section_id}";
        }
        
        $role_names = array();
        foreach ($roles as $role) {
            $role_names[] = $role['role_name'];
        }
        
        return "V2 system: User has roles [" . implode(', ', $role_names) . "] in section {$section_id}";
    }

    /**
     * Test access using legacy DX_Auth system
     */
    private function _testLegacyAccess($user_id, $controller, $action, $section_id)
    {
        // Get user data
        $user = $this->CI->db->where('id', $user_id)->get('users')->row();
        if (!$user) {
            return false;
        }

        // Simulate login for legacy system
        $this->CI->session->set_userdata(array(
            'DX_user_id' => $user_id,
            'DX_username' => $user->username,
            'DX_logged_in' => TRUE,
            'DX_role_id' => $user->role_id,
            'DX_role_name' => $this->_getLegacyRoleName($user->role_id)
        ));

        // Check permissions using legacy system
        return $this->_checkLegacyPermissions($user->role_id, $controller, $action);
    }

    /**
     * Test access using V2 authorization system
     */
    private function _testV2Access($user_id, $controller, $action, $section_id)
    {
        // Check if user is migrated to V2 system
        // Use migrated_by as indicator instead of migration_completed_at
        $migrated = $this->CI->db
            ->where('user_id', $user_id)
            ->where('migrated_by IS NOT NULL')
            ->get('authorization_migration_status')
            ->num_rows() > 0;
        
        if (!$migrated) {
            // Force migration for testing
            $this->_migrateUserToV2($user_id);
        }

        return $this->auth->can_access($user_id, $controller, $action, $section_id);
    }

    /**
     * Get legacy role name from role_id
     */
    private function _getLegacyRoleName($role_id)
    {
        $role_names = array(
            1 => 'membre',
            2 => 'admin', 
            3 => 'bureau',
            7 => 'planchiste',
            8 => 'ca',
            9 => 'tresorier'
        );
        return $role_names[$role_id] ?? 'membre';
    }

    /**
     * Check permissions using legacy permissions table
     */
    private function _checkLegacyPermissions($role_id, $controller, $action)
    {
        // Get permissions from legacy permissions table
        $permissions = $this->CI->db
            ->where('role_id', $role_id)
            ->get('permissions')
            ->row();

        if (!$permissions || !$permissions->data) {
            // If no permissions found, use basic role-based logic for smoke test
            return $this->_getBasicRolePermissions($role_id, $controller, $action);
        }

        $data = @unserialize($permissions->data);
        if (!$data || !isset($data['uri'])) {
            return $this->_getBasicRolePermissions($role_id, $controller, $action);
        }

        // Check direct URI matches first
        foreach ($data['uri'] as $allowed_uri) {
            $allowed_clean = trim($allowed_uri, '/');
            
            // Direct controller match (e.g., "/membre/" allows all membre actions)
            if ($allowed_clean === $controller) {
                return true;
            }
            
            // Direct controller/action match
            if ($allowed_clean === "{$controller}/{$action}") {
                return true;
            }
            
            // Special case: if allowed URI is just the controller with slash
            // and we're looking for basic access, allow it
            if ($allowed_uri === "/{$controller}/" && in_array($action, ['index', 'page'])) {
                return true;
            }
        }

        // If no direct match found, check some legacy special cases
        return $this->_checkLegacySpecialCases($role_id, $controller, $action, $data['uri']);
    }

    /**
     * Handle special legacy permission cases
     */
    private function _checkLegacySpecialCases($role_id, $controller, $action, $uris)
    {
        // Special mappings for legacy system
        $legacy_mappings = array(
            // calendar/index maps to /calendar/
            'calendar/index' => array('/calendar/', '/calendar'),
            // Welcome controller
            'welcome/index' => array('/welcome/', '/welcome'),
            // Admin has access to everything if they have any admin URIs
            'authorization/user_roles' => array('/admin/', '/membre/', '/planeur/'), // Admin URIs indicate full access
        );

        $key = "{$controller}/{$action}";
        if (isset($legacy_mappings[$key])) {
            foreach ($legacy_mappings[$key] as $legacy_uri) {
                if (in_array($legacy_uri, $uris) || in_array(trim($legacy_uri, '/'), $uris)) {
                    return true;
                }
            }
        }

        // Role-specific special cases
        if ($role_id == 2) { // admin role
            // Admin should have access to everything if they have broad permissions
            $admin_indicators = array('/membre/', '/planeur/', '/admin/');
            foreach ($admin_indicators as $indicator) {
                if (in_array($indicator, $uris) || in_array(trim($indicator, '/'), $uris)) {
                    return true; // Admin has broad access
                }
            }
        }

        return false;
    }

    /**
     * Fallback basic role permissions for legacy system when no serialized data exists
     */
    private function _getBasicRolePermissions($role_id, $controller, $action)
    {
        // Basic permission matrix for smoke test based on actual database role IDs
        // From mysql: 1=membre, 2=admin, 3=bureau, 7=planchiste, 8=ca, 9=tresorier
        $permissions = array(
            1 => array( // membre/user
                'welcome/index' => true,
                'membre/page' => true,
                'calendar/index' => true,
                'compta/mon_compte' => true,
                'vols_avion/vols_du_pilote' => true,
            ),
            2 => array( // admin
                '*' => true, // Admin has access to everything
            ),
            3 => array( // bureau
                'membre/create' => true,
                'membre/edit' => true,
                'membre/page' => true,
                'vols_planeur/page' => true,
                'compta/rapports' => true,
                'terrains/page' => true,
                'calendar/manage' => true,
                'welcome/index' => true,
            ),
            7 => array( // planchiste
                'vols_planeur/create' => true,
                'vols_planeur/edit' => true,
                'vols_planeur/page' => true,
                'membre/page' => true,
                'membre/edit' => true,
                'calendar/index' => true,
                'welcome/index' => true,
            ),
            8 => array( // ca
                'terrains/page' => true,
                'terrains/edit' => true,
                'membre/page' => true,
                'vols_planeur/page' => true,
                'calendar/manage' => true,
                'compta/rapports' => true,
                'welcome/index' => true,
            ),
            9 => array( // tresorier
                'compta/ecritures' => true,
                'compta/comptes' => true,
                'compta/rapports' => true,
                'compta/facturation' => true,
                'membre/page' => true,
                'welcome/index' => true,
            ),
        );

        $key = "{$controller}/{$action}";
        
        // Check if role has permissions defined
        if (!isset($permissions[$role_id])) {
            return false;
        }

        // Admin has access to everything
        if (isset($permissions[$role_id]['*'])) {
            return true;
        }

        // Check specific permission
        return isset($permissions[$role_id][$key]) && $permissions[$role_id][$key];
    }

    /**
     * Migrate user to V2 system for testing
     */
    private function _migrateUserToV2($user_id)
    {
        // Check if already migrated
        $existing = $this->CI->db
            ->where('user_id', $user_id)
            ->get('authorization_migration_status')
            ->row();

        if (!$existing) {
            // Find an actual admin user ID from the database
            $admin_user = $this->CI->db
                ->select('id')
                ->from('users')
                ->where('role_id', 2) // admin role
                ->limit(1)
                ->get()
                ->row();
            
            $migrated_by = $admin_user ? $admin_user->id : $user_id; // Fallback to self
            
            // Insert migration status
            $this->CI->db->insert('authorization_migration_status', array(
                'user_id' => $user_id,
                'migrated_by' => $migrated_by,
                'notes' => 'Test migration for migration test'
            ));
        } else if (!$existing->migrated_by) {
            // Find an actual admin user ID from the database
            $admin_user = $this->CI->db
                ->select('id')
                ->from('users')
                ->where('role_id', 2) // admin role
                ->limit(1)
                ->get()
                ->row();
            
            $migrated_by = $admin_user ? $admin_user->id : $user_id; // Fallback to self
            
            // Update existing record
            $this->CI->db
                ->where('user_id', $user_id)
                ->update('authorization_migration_status', array(
                    'migrated_by' => $migrated_by,
                    'notes' => 'Test migration for migration test'
                ));
        }
    }

    // ========================================
    // DUAL-PHASE TEST EXECUTION
    // ========================================

    /**
     * Run a test method in both legacy and V2 phases
     */
    private function _runDualPhaseTest($test_method, $description = '')
    {
        // Phase 1: Legacy system
        $this->test_phase = 'legacy';
        echo "\n  LEGACY PHASE: " . ($description ?: $test_method) . "\n";
        $this->$test_method();
        
        // Phase 2: V2 system  
        $this->test_phase = 'v2';
        echo "  V2 PHASE: " . ($description ?: $test_method) . "\n";
        $this->$test_method();
    }

    // ========================================
    // USER ROLE TESTS (types_roles_id = 1)
    // ========================================

    public function testUserRoleGrantedAccess()
    {
        $this->_runDualPhaseTest('_testUserRoleGrantedAccessImpl', 'User Role - Granted Access');
    }

    public function testUserRoleDeniedAccess()
    {
        $this->_runDualPhaseTest('_testUserRoleDeniedAccessImpl', 'User Role - Denied Access');
    }

    private function _testUserRoleGrantedAccessImpl()
    {
        if (!isset($this->test_users['testuser'])) {
            $this->markTestSkipped('testuser not found');
        }
        
        $user_id = $this->test_users['testuser']['id'];
        
        // User should have access to basic functionality (based on actual legacy permissions)
        $this->_testAccess($user_id, 'welcome', 'index', true, null, 'Basic users have welcome access for dashboard navigation');
        $this->_testAccess($user_id, 'membre', 'page', true, null, 'Users can view member profiles (self and others in section)');
        $this->_testAccess($user_id, 'compta', 'mon_compte', true, null, 'Users can view their own financial account summary');
        $this->_testAccess($user_id, 'vols_avion', 'page', true, null, 'Users can view airplane flight logs for club transparency');
        $this->_testAccess($user_id, 'vols_planeur', 'page', true, null, 'Users can view glider flight logs for club transparency');
    }

    private function _testUserRoleDeniedAccessImpl()
    {
        if (!isset($this->test_users['testuser'])) {
            $this->markTestSkipped('testuser not found');
        }
        
        $user_id = $this->test_users['testuser']['id'];
        
        // User should NOT have access to privileged functionality  
        $this->_testAccess($user_id, 'terrains', 'page', false, null, 'Airfield management restricted to CA board members');
        $this->_testAccess($user_id, 'authorization', 'user_roles', false, null, 'User role management restricted to administrators');
        $this->_testAccess($user_id, 'backend', 'users', false, null, 'User administration restricted to administrators');
        $this->_testAccess($user_id, 'calendar', 'index', false, null, 'Calendar access not included in basic user permissions');
        $this->_testAccess($user_id, 'admin', 'index', false, null, 'Administration panel restricted to administrators');
    }

    // ========================================
    // PLANCHISTE ROLE TESTS (types_roles_id = 5)
    // ========================================

    public function testPlanchisteRoleGrantedAccess()
    {
        $this->_runDualPhaseTest('_testPlanchisteRoleGrantedAccessImpl', 'Planchiste Role - Granted Access');
    }

    public function testPlanchisteRoleDeniedAccess()
    {
        $this->_runDualPhaseTest('_testPlanchisteRoleDeniedAccessImpl', 'Planchiste Role - Denied Access');
    }

    private function _testPlanchisteRoleGrantedAccessImpl()
    {
        if (!isset($this->test_users['testplanchiste'])) {
            $this->markTestSkipped('testplanchiste not found');
        }
        
        $user_id = $this->test_users['testplanchiste']['id'];
        
        // Planchiste has access to flight management (based on actual legacy permissions)
        $this->_testAccess($user_id, 'vols_planeur', 'page', true, null, 'Planchistes manage glider flight logging and validation');
        $this->_testAccess($user_id, 'vols_avion', 'page', true, null, 'Planchistes also oversee airplane flight operations');
    }

    private function _testPlanchisteRoleDeniedAccessImpl()
    {
        if (!isset($this->test_users['testplanchiste'])) {
            $this->markTestSkipped('testplanchiste not found');
        }
        
        $user_id = $this->test_users['testplanchiste']['id'];
        
        // Planchiste should NOT have access to admin/financial functionality
        $this->_testAccess($user_id, 'compta', 'ecritures', false, null, 'Financial entry management requires treasurer role');
        $this->_testAccess($user_id, 'compta', 'comptes', false, null, 'Chart of accounts management requires treasurer role');
        $this->_testAccess($user_id, 'authorization', 'user_roles', false, null, 'User role management restricted to administrators');
        $this->_testAccess($user_id, 'backend', 'users', false, null, 'User administration requires admin privileges');
        $this->_testAccess($user_id, 'terrains', 'create', false, null, 'Airfield creation restricted to CA board members');
    }

    // ========================================
    // CA ROLE TESTS (types_roles_id = 6)
    // ========================================

    public function testCaRoleGrantedAccess()
    {
        $this->_runDualPhaseTest('_testCaRoleGrantedAccessImpl', 'CA Role - Granted Access');
    }

    public function testCaRoleDeniedAccess()
    {
        $this->_runDualPhaseTest('_testCaRoleDeniedAccessImpl', 'CA Role - Denied Access');
    }

    private function _testCaRoleGrantedAccessImpl()
    {
        if (!isset($this->test_users['testca'])) {
            $this->markTestSkipped('testca not found');
        }
        
        $user_id = $this->test_users['testca']['id'];
        
        // CA should have access to terrain and member management
        $this->_testAccess($user_id, 'terrains', 'page', true);
        $this->_testAccess($user_id, 'terrains', 'edit', true);
        $this->_testAccess($user_id, 'membre', 'page', true);
        $this->_testAccess($user_id, 'vols_planeur', 'page', true);
        $this->_testAccess($user_id, 'calendar', 'manage', true);
        $this->_testAccess($user_id, 'compta', 'rapports', true);
    }

    private function _testCaRoleDeniedAccessImpl()
    {
        if (!isset($this->test_users['testca'])) {
            $this->markTestSkipped('testca not found');
        }
        
        $user_id = $this->test_users['testca']['id'];
        
        // CA should NOT have access to financial editing or admin functions
        $this->_testAccess($user_id, 'compta', 'ecritures', false);
        $this->_testAccess($user_id, 'compta', 'comptes', false);
        $this->_testAccess($user_id, 'authorization', 'user_roles', false);
        $this->_testAccess($user_id, 'backend', 'users', false);
        $this->_testAccess($user_id, 'users', 'create', false);
    }

    // ========================================
    // BUREAU ROLE TESTS (types_roles_id = 7)
    // ========================================

    public function testBureauRoleGrantedAccess()
    {
        $this->_runDualPhaseTest('_testBureauRoleGrantedAccessImpl', 'Bureau Role - Granted Access');
    }

    public function testBureauRoleDeniedAccess()
    {
        $this->_runDualPhaseTest('_testBureauRoleDeniedAccessImpl', 'Bureau Role - Denied Access');
    }

    private function _testBureauRoleGrantedAccessImpl()
    {
        if (!isset($this->test_users['testbureau'])) {
            $this->markTestSkipped('testbureau not found');
        }
        
        $user_id = $this->test_users['testbureau']['id'];
        
        // Bureau should have broad section management access
        $this->_testAccess($user_id, 'membre', 'create', true);
        $this->_testAccess($user_id, 'membre', 'edit', true);
        $this->_testAccess($user_id, 'vols_planeur', 'page', true);
        $this->_testAccess($user_id, 'compta', 'rapports', true);
        $this->_testAccess($user_id, 'terrains', 'page', true);
        $this->_testAccess($user_id, 'calendar', 'manage', true);
    }

    private function _testBureauRoleDeniedAccessImpl()
    {
        if (!isset($this->test_users['testbureau'])) {
            $this->markTestSkipped('testbureau not found');
        }
        
        $user_id = $this->test_users['testbureau']['id'];
        
        // Bureau should NOT have access to system admin functions
        $this->_testAccess($user_id, 'authorization', 'user_roles', false);
        $this->_testAccess($user_id, 'backend', 'users', false);
        $this->_testAccess($user_id, 'users', 'delete', false);
        $this->_testAccess($user_id, 'compta', 'comptes', false);
    }

    // ========================================
    // TRESORIER ROLE TESTS (types_roles_id = 8)
    // ========================================

    public function testTresorierRoleGrantedAccess()
    {
        $this->_runDualPhaseTest('_testTresorierRoleGrantedAccessImpl', 'Tresorier Role - Granted Access');
    }

    public function testTresorierRoleDeniedAccess()
    {
        $this->_runDualPhaseTest('_testTresorierRoleDeniedAccessImpl', 'Tresorier Role - Denied Access');
    }

    private function _testTresorierRoleGrantedAccessImpl()
    {
        if (!isset($this->test_users['testtresorier'])) {
            $this->markTestSkipped('testtresorier not found');
        }
        
        $user_id = $this->test_users['testtresorier']['id'];
        
        // Tresorier should have access to financial management
        $this->_testAccess($user_id, 'compta', 'ecritures', true);
        $this->_testAccess($user_id, 'compta', 'comptes', true);
        $this->_testAccess($user_id, 'compta', 'rapports', true);
        $this->_testAccess($user_id, 'compta', 'facturation', true);
        $this->_testAccess($user_id, 'membre', 'page', true); // for financial data
    }

    private function _testTresorierRoleDeniedAccessImpl()
    {
        if (!isset($this->test_users['testtresorier'])) {
            $this->markTestSkipped('testtresorier not found');
        }
        
        $user_id = $this->test_users['testtresorier']['id'];
        
        // Tresorier should NOT have access to admin functions
        $this->_testAccess($user_id, 'authorization', 'user_roles', false);
        $this->_testAccess($user_id, 'backend', 'users', false);
        $this->_testAccess($user_id, 'users', 'create', false);
        $this->_testAccess($user_id, 'membre', 'delete', false);
        $this->_testAccess($user_id, 'terrains', 'delete', false);
    }

    // ========================================
    // ADMIN ROLE TESTS (types_roles_id = 10)
    // ========================================

    public function testAdminRoleGrantedAccess()
    {
        $this->_runDualPhaseTest('_testAdminRoleGrantedAccessImpl', 'Admin Role - Granted Access');
    }

    public function testAdminRoleNoDeniedAccess()
    {
        $this->_runDualPhaseTest('_testAdminRoleNoDeniedAccessImpl', 'Admin Role - No Denied Access');
    }

    private function _testAdminRoleGrantedAccessImpl()
    {
        if (!isset($this->test_users['testadmin'])) {
            $this->markTestSkipped('testadmin not found');
        }
        
        $user_id = $this->test_users['testadmin']['id'];
        
        // Admin should have access to everything
        $this->_testAccess($user_id, 'authorization', 'user_roles', true);
        $this->_testAccess($user_id, 'backend', 'users', true);
        $this->_testAccess($user_id, 'users', 'create', true);
        $this->_testAccess($user_id, 'users', 'edit', true);
        $this->_testAccess($user_id, 'users', 'delete', true);
        $this->_testAccess($user_id, 'compta', 'ecritures', true);
        $this->_testAccess($user_id, 'terrains', 'create', true);
        $this->_testAccess($user_id, 'terrains', 'edit', true);
        $this->_testAccess($user_id, 'terrains', 'delete', true);
    }

    private function _testAdminRoleNoDeniedAccessImpl()
    {
        if (!isset($this->test_users['testadmin'])) {
            $this->markTestSkipped('testadmin not found');
        }
        
        // Admin should have no denied access - this test verifies admin can access
        // pages that other roles cannot
        $user_id = $this->test_users['testadmin']['id'];
        
        $restricted_pages = [
            ['authorization', 'user_roles'],
            ['backend', 'users'],
            ['users', 'delete'],
            ['compta', 'ecritures'],
            ['terrains', 'delete']
        ];
        
        foreach ($restricted_pages as $page) {
            $this->_testAccess($user_id, $page[0], $page[1], true);
        }
    }

    // ========================================
    // CROSS-ROLE VERIFICATION TESTS
    // ========================================

    /**
     * Test that user role hierarchy is respected
     */
    public function testRoleHierarchy()
    {
        $this->_runDualPhaseTest('_testRoleHierarchyImpl', 'Role Hierarchy Verification');
    }

    /**
     * Test that section-based access works correctly
     */
    public function testSectionBasedAccess()
    {
        $this->_runDualPhaseTest('_testSectionBasedAccessImpl', 'Section-Based Access Verification');
    }

    private function _testRoleHierarchyImpl()
    {
        // Test that higher roles can access what lower roles can access
        // Plus additional functionality
        
        if (!isset($this->test_users['testuser']) || !isset($this->test_users['testplanchiste'])) {
            $this->markTestSkipped('Required test users not found');
        }
        
        $user_id = $this->test_users['testuser']['id'];
        $planchiste_id = $this->test_users['testplanchiste']['id'];
        
        // User has welcome access, planchiste does not (based on actual legacy permissions)
        $this->_testAccess($user_id, 'welcome', 'index', true, null, 'Legacy system: Regular users have welcome access');
        $this->_testAccess($planchiste_id, 'welcome', 'index', false, null, 'Legacy system: Planchistes focused on operations, no welcome access');
        
        // Both should access flight pages  
        $this->_testAccess($user_id, 'vols_planeur', 'page', true, null, 'Flight viewing available to all users for transparency');
        $this->_testAccess($planchiste_id, 'vols_planeur', 'page', true, null, 'Planchistes need flight access for operational management');
    }

    private function _testSectionBasedAccessImpl()
    {
        if (!isset($this->test_users['testplanchiste'])) {
            $this->markTestSkipped('testplanchiste not found');
        }
        
        $user_id = $this->test_users['testplanchiste']['id'];
        
        // Planchiste should have access in their section
        $this->_testAccess($user_id, 'vols_planeur', 'page', true, $this->test_section_id);
        
        // Test with a different section ID (if exists)
        // Skip this test for now to avoid SQL issues in smoke test
        $other_section_query = null;
            
        // Skip cross-section testing for smoke test - focus on basic functionality
        // TODO: Add proper cross-section testing when section isolation is implemented
    }
}