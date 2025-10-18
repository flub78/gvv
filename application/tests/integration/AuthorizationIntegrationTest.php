<?php

use PHPUnit\Framework\TestCase;

/**
 * Integration test for Authorization system with real database access
 *
 * Tests the complete authorization workflow:
 * - User role assignment
 * - Permission checking
 * - Data access rules
 * - Audit logging
 *
 * Requirements:
 * - Full CodeIgniter framework loaded
 * - Database connection configured
 * - InnoDB tables (for transaction support)
 * - Migrations 042 and 043 applied
 *
 * @see /doc/plans/2025_authorization_refactoring_plan.md
 */
class AuthorizationIntegrationTest extends TestCase
{
    /**
     * @var CI_Controller
     */
    private $CI;

    /**
     * @var Gvv_Authorization
     */
    private $auth;

    /**
     * @var Authorization_model
     */
    private $model;

    /**
     * Test user and role IDs for cleanup
     */
    private $test_user_id;
    private $test_admin_id;
    private $test_section_id;
    private $created_permissions = [];
    private $created_rules = [];

    /**
     * Set up test environment with database transaction
     */
    public function setUp(): void
    {
        // Get CodeIgniter instance
        $this->CI =& get_instance();

        // Load authorization library and model
        $this->CI->load->library('Gvv_Authorization');
        $this->CI->load->model('Authorization_model');

        $this->auth = $this->CI->gvv_authorization;
        $this->model = $this->CI->Authorization_model;

        // Start transaction for test isolation
        $this->CI->db->trans_start();

        // Verify database connection
        if (!$this->CI->db->conn_id) {
            $this->markTestSkipped('Database connection not available');
        }

        // Create test user and section
        $this->_create_test_data();
    }

    /**
     * Clean up after each test - rollback transaction
     */
    public function tearDown(): void
    {
        // Rollback transaction to restore database state
        $this->CI->db->trans_rollback();

        // Clear authorization cache
        $this->auth->clear_cache();
    }

    // ========================================================================
    // TEST DATA SETUP
    // ========================================================================

    /**
     * Create test user and section for testing
     */
    private function _create_test_data()
    {
        // Create test section
        $this->CI->db->insert('sections', array(
            'nom' => 'Test Section ' . time(),
            'acronyme' => 'TST'
        ));
        $this->test_section_id = $this->CI->db->insert_id();

        // Create test admin user (for granted_by foreign key)
        $this->CI->db->insert('users', array(
            'username' => 'test_admin_' . time(),
            'password' => md5('admin123'),
            'email' => 'admin' . time() . '@example.com',
            'role_id' => 10,
            'banned' => 0,
            'last_ip' => '127.0.0.1',
            'last_login' => date('Y-m-d H:i:s'),
            'created' => date('Y-m-d H:i:s')
        ));
        $this->test_admin_id = $this->CI->db->insert_id();

        // Create test user (using MD5 hashing like DX_Auth)
        $this->CI->db->insert('users', array(
            'username' => 'test_user_' . time(),
            'password' => md5('test123'),
            'email' => 'test' . time() . '@example.com',
            'role_id' => 1,
            'banned' => 0,
            'last_ip' => '127.0.0.1',
            'last_login' => date('Y-m-d H:i:s'),
            'created' => date('Y-m-d H:i:s')
        ));
        $this->test_user_id = $this->CI->db->insert_id();
    }

    // ========================================================================
    // ROLE MANAGEMENT TESTS
    // ========================================================================

    /**
     * Test granting and revoking roles
     */
    public function testGrantAndRevokeRole()
    {
        // Grant a role to user
        $result = $this->auth->grant_role(
            $this->test_user_id,
            5, // planchiste role
            $this->test_section_id,
            $this->test_admin_id
        );

        $this->assertTrue($result, 'Role should be granted successfully');

        // Verify role was granted
        $roles = $this->auth->get_user_roles($this->test_user_id, $this->test_section_id);
        $this->assertCount(1, $roles);
        $this->assertEquals(5, $roles[0]['types_roles_id']);

        // Try to grant same role again (should fail)
        $duplicate_result = $this->auth->grant_role(
            $this->test_user_id,
            5,
            $this->test_section_id,
            1
        );

        $this->assertFalse($duplicate_result, 'Duplicate role should not be granted');

        // Revoke the role
        $revoke_result = $this->auth->revoke_role(
            $this->test_user_id,
            5,
            $this->test_section_id,
            1
        );

        $this->assertTrue($revoke_result, 'Role should be revoked successfully');

        // Verify role was revoked
        $roles_after = $this->auth->get_user_roles($this->test_user_id, $this->test_section_id);
        $this->assertCount(0, $roles_after);
    }

    /**
     * Test getting user roles with global vs section scope
     */
    public function testGetUserRolesWithScope()
    {
        // Grant section-scoped role
        $this->auth->grant_role($this->test_user_id, 5, $this->test_section_id, $this->test_admin_id);

        // Grant global role (club-admin)
        $this->auth->grant_role($this->test_user_id, 10, NULL, $this->test_admin_id);

        // Get roles for specific section (should include global)
        $section_roles = $this->model->get_user_roles(
            $this->test_user_id,
            $this->test_section_id,
            TRUE  // include global
        );

        $this->assertGreaterThanOrEqual(2, count($section_roles));

        // Get roles excluding global
        $section_only = $this->model->get_user_roles(
            $this->test_user_id,
            $this->test_section_id,
            FALSE  // exclude global
        );

        $this->assertCount(1, $section_only);
        $this->assertEquals(5, $section_only[0]['types_roles_id']);
    }

    /**
     * Test has_role and has_any_role methods
     */
    public function testHasRoleMethods()
    {
        // Grant planchiste role
        $this->auth->grant_role($this->test_user_id, 5, $this->test_section_id, $this->test_admin_id);

        // Test has_role
        $this->assertTrue(
            $this->auth->has_role($this->test_user_id, 'planchiste', $this->test_section_id)
        );

        $this->assertFalse(
            $this->auth->has_role($this->test_user_id, 'club-admin', $this->test_section_id)
        );

        // Test has_any_role
        $this->assertTrue(
            $this->auth->has_any_role(
                $this->test_user_id,
                array('planchiste', 'ca', 'club-admin'),
                $this->test_section_id
            )
        );

        $this->assertFalse(
            $this->auth->has_any_role(
                $this->test_user_id,
                array('ca', 'club-admin'),
                $this->test_section_id
            )
        );
    }

    // ========================================================================
    // PERMISSION MANAGEMENT TESTS
    // ========================================================================

    /**
     * Test adding and removing permissions
     */
    public function testAddAndRemovePermissions()
    {
        // Add permission for planchiste role
        $result = $this->model->add_permission(
            5,  // planchiste
            'vols_planeur',
            'page',
            $this->test_section_id,
            'view'
        );

        $this->assertTrue($result);
        $this->created_permissions[] = $this->CI->db->insert_id();

        // Verify permission was added
        $permissions = $this->model->get_role_permissions(5, $this->test_section_id);
        $this->assertGreaterThan(0, count($permissions));

        // Try to add duplicate (should fail)
        $duplicate = $this->model->add_permission(
            5,
            'vols_planeur',
            'page',
            $this->test_section_id,
            'view'
        );

        $this->assertFalse($duplicate);

        // Remove permission
        $permission_id = $this->created_permissions[0];
        $remove_result = $this->model->remove_permission($permission_id);
        $this->assertTrue($remove_result);
    }

    /**
     * Test wildcard permissions (NULL action = all actions)
     */
    public function testWildcardPermissions()
    {
        // Add wildcard permission (all actions)
        $this->model->add_permission(
            5,
            'vols_planeur',
            NULL,  // NULL = all actions
            $this->test_section_id,
            'view'
        );

        // Grant role to user
        $this->auth->grant_role($this->test_user_id, 5, $this->test_section_id, $this->test_admin_id);

        // Check access to specific action (should be granted via wildcard)
        $has_access = $this->auth->can_access(
            $this->test_user_id,
            'vols_planeur',
            'edit',
            $this->test_section_id
        );

        $this->assertTrue($has_access, 'Wildcard permission should grant access to all actions');
    }

    // ========================================================================
    // DATA ACCESS RULES TESTS
    // ========================================================================

    /**
     * Test data access rules with different scopes
     */
    public function testDataAccessRulesWithScopes()
    {
        // Add "own" scope rule for user role
        $result = $this->model->add_data_access_rule(
            1,  // user role
            'volsp',
            'own',
            'pilote',  // field to check ownership
            'club',    // section field
            'Users can only view their own flights'
        );

        $this->assertTrue($result);

        // Add "section" scope rule for planchiste role
        $result2 = $this->model->add_data_access_rule(
            5,  // planchiste role
            'volsp',
            'section',
            NULL,
            'club',
            'Planchistes can view all flights in their section'
        );

        $this->assertTrue($result2);

        // Get rules for role
        $rules = $this->model->get_data_access_rules(5, 'volsp');
        $this->assertGreaterThan(0, count($rules));
    }

    /**
     * Test can_access_data method with row-level security
     */
    public function testCanAccessDataWithRowLevelSecurity()
    {
        // Create data access rule
        $this->model->add_data_access_rule(
            1,  // user role
            'membres',
            'own',
            'user_id',
            'club',
            'Users can only view their own member record'
        );

        // Grant user role
        $this->auth->grant_role($this->test_user_id, 1, $this->test_section_id, $this->test_admin_id);

        // Test with own data (should have access)
        $own_data = array(
            'user_id' => $this->test_user_id,
            'club' => $this->test_section_id
        );

        $has_access_own = $this->auth->can_access_data(
            $this->test_user_id,
            'membres',
            $own_data,
            $this->test_section_id
        );

        $this->assertTrue($has_access_own, 'User should have access to own data');

        // Test with other user's data (should NOT have access)
        $other_data = array(
            'user_id' => 999,
            'club' => $this->test_section_id
        );

        $has_access_other = $this->auth->can_access_data(
            $this->test_user_id,
            'membres',
            $other_data,
            $this->test_section_id
        );

        $this->assertFalse($has_access_other, 'User should NOT have access to other user data');
    }

    // ========================================================================
    // AUDIT LOGGING TESTS
    // ========================================================================

    /**
     * Test that audit log records role grants
     */
    public function testAuditLogRecordsRoleGrants()
    {
        // Count existing audit logs
        $before_count = count($this->model->get_audit_log(array(), 1000));

        // Grant a role
        $this->auth->grant_role($this->test_user_id, 5, $this->test_section_id, $this->test_admin_id);

        // Check audit log
        $after_count = count($this->model->get_audit_log(array(), 1000));

        $this->assertGreaterThan($before_count, $after_count, 'Audit log should record role grant');

        // Get latest audit entry
        $latest = $this->model->get_audit_log(array(), 1, 0);
        $this->assertCount(1, $latest);
        $this->assertEquals('grant_role', $latest[0]['action_type']);
    }

    /**
     * Test audit log filtering
     */
    public function testAuditLogFiltering()
    {
        // Grant and revoke roles to create audit entries
        $this->auth->grant_role($this->test_user_id, 5, $this->test_section_id, $this->test_admin_id);
        $this->auth->revoke_role($this->test_user_id, 5, $this->test_section_id, $this->test_admin_id);

        // Filter by action type
        $grant_logs = $this->model->get_audit_log(array('action_type' => 'grant_role'));
        $revoke_logs = $this->model->get_audit_log(array('action_type' => 'revoke_role'));

        $this->assertGreaterThan(0, count($grant_logs));
        $this->assertGreaterThan(0, count($revoke_logs));

        // Verify all filtered logs match the action type
        foreach ($grant_logs as $log) {
            $this->assertEquals('grant_role', $log['action_type']);
        }
    }

    // ========================================================================
    // CACHING TESTS
    // ========================================================================

    /**
     * Test permission caching
     */
    public function testPermissionCaching()
    {
        // Grant role and add permission
        $this->auth->grant_role($this->test_user_id, 5, $this->test_section_id, $this->test_admin_id);
        $this->model->add_permission(5, 'test', 'action', $this->test_section_id, 'view');

        // First call (should cache)
        $roles1 = $this->auth->get_user_roles($this->test_user_id, $this->test_section_id);

        // Second call (should use cache - same object returned)
        $roles2 = $this->auth->get_user_roles($this->test_user_id, $this->test_section_id);

        $this->assertEquals($roles1, $roles2);

        // Clear cache
        $this->auth->clear_cache($this->test_user_id);

        // Third call (should re-query)
        $roles3 = $this->auth->get_user_roles($this->test_user_id, $this->test_section_id);

        $this->assertEquals($roles1, $roles3);
    }

    // ========================================================================
    // MIGRATION STATUS TESTS
    // ========================================================================

    /**
     * Test migration status tracking
     */
    public function testMigrationStatus()
    {
        // Set migration status
        $result = $this->model->set_migration_status(
            $this->test_user_id,
            'completed',
            TRUE,  // use new system
            $this->test_admin_id
        );

        $this->assertTrue($result);

        // Get migration status
        $status = $this->model->get_migration_status($this->test_user_id);

        $this->assertNotNull($status);
        $this->assertEquals('completed', $status['migration_status']);
        $this->assertEquals(1, $status['use_new_system']);
        $this->assertEquals($this->test_admin_id, $status['migrated_by']);
    }

    // ========================================================================
    // INTEGRATION WORKFLOW TESTS
    // ========================================================================

    /**
     * Test complete authorization workflow
     */
    public function testCompleteAuthorizationWorkflow()
    {
        // 1. Grant role to user
        $this->auth->grant_role($this->test_user_id, 5, $this->test_section_id, $this->test_admin_id);

        // 2. Add permission to role
        $this->model->add_permission(5, 'vols_planeur', 'page', $this->test_section_id, 'view');

        // 3. Add data access rule
        $this->model->add_data_access_rule(5, 'volsp', 'section', NULL, 'club', 'Section access');

        // 4. Check access (should be granted)
        $has_uri_access = $this->auth->can_access(
            $this->test_user_id,
            'vols_planeur',
            'page',
            $this->test_section_id
        );

        $this->assertTrue($has_uri_access);

        // 5. Check data access
        $flight_data = array('club' => $this->test_section_id);
        $has_data_access = $this->auth->can_access_data(
            $this->test_user_id,
            'volsp',
            $flight_data,
            $this->test_section_id
        );

        $this->assertTrue($has_data_access);

        // 6. Verify audit log has entries
        $audit = $this->model->get_audit_log(array(), 10);
        $this->assertGreaterThan(0, count($audit));
    }
}
