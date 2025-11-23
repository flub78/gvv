<?php

use PHPUnit\Framework\TestCase;

/**
 * Authorization System Migration Test - Placeholder
 *
 * This test suite will validate the migration from DX_Auth to Gvv_Authorization system.
 * Tests will verify that access control behavior remains consistent across both systems.
 *
 * TODO: Implement comprehensive authorization migration tests
 * - Test user role permissions (user, planchiste, ca, bureau, tresorier, admin)
 * - Test legacy DX_Auth system behavior
 * - Test new Gvv_Authorization system behavior
 * - Test migration process and data consistency
 * - Test section-based access control
 *
 * Test users (from bin/create_test_users.sh):
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

    public function setUp(): void
    {
        $this->CI =& get_instance();

        // Start transaction for test isolation
        $this->CI->db->trans_start();
    }

    public function tearDown(): void
    {
        // Rollback transaction
        $this->CI->db->trans_rollback();
    }

    /**
     * Test that user roles can be granted for specific sections
     * and that access control works correctly per section
     */
    public function testGrantUserRolePerSection()
    {
        $this->_grantRoleAndVerifyAccess('testuser', 'user', [1, 4], 'user', [2, 3]);
    }

    /**
     * Helper to grant roles and verify access
     * 
     * @param string $username
     * @param string $grant_role_name Role to grant
     * @param array $grant_sections Sections to grant the role in
     * @param string $deny_role_name Role to verify is NOT granted
     * @param array $deny_sections Sections to verify the role is NOT in
     */
    protected function _grantRoleAndVerifyAccess($username, $grant_role_name, $grant_sections, $deny_role_name, $deny_sections)
    {
        // Load library if not loaded
        if (!isset($this->CI->gvv_authorization)) {
            $this->CI->load->library('Gvv_Authorization');
        } else {
            // Clear cache to ensure fresh data
            $this->CI->gvv_authorization->clear_cache();
        }

        $user = $this->CI->db->get_where('users', ['username' => $username])->row();
        $this->assertNotNull($user, "User $username should exist");

        // Handle Grant
        if (!empty($grant_sections)) {
            $grant_role = $this->CI->db->get_where('types_roles', ['nom' => $grant_role_name])->row();
            $this->assertNotNull($grant_role, "Role $grant_role_name should exist");

            foreach ($grant_sections as $section_id) {
                // Grant role
                $data = [
                    'user_id' => $user->id,
                    'types_roles_id' => $grant_role->id,
                    'section_id' => $section_id,
                    'granted_by' => $user->id,
                    'granted_at' => date('Y-m-d H:i:s'),
                    'notes' => "Test grant $grant_role_name section $section_id"
                ];
                $this->CI->db->insert('user_roles_per_section', $data);

                // Grant permission (welcome/index)
                $perm_data = [
                    'types_roles_id' => $grant_role->id,
                    'section_id' => $section_id,
                    'controller' => 'welcome',
                    'action' => 'index',
                    'permission_type' => 'view',
                    'created' => date('Y-m-d H:i:s'),
                    'modified' => date('Y-m-d H:i:s')
                ];
                $this->CI->db->insert('role_permissions', $perm_data);

                // Verify has_role
                $this->assertTrue(
                    $this->CI->gvv_authorization->has_role($user->id, $grant_role_name, $section_id),
                    "User should have $grant_role_name in section $section_id"
                );

                // Verify can_access
                $this->_simulateLogin($user->id, $section_id);
                $this->assertTrue(
                    $this->CI->gvv_authorization->can_access($user->id, 'welcome', 'index', $section_id),
                    "User should access welcome/index in section $section_id"
                );

                // Verify DB
                $exists = $this->CI->db->get_where('user_roles_per_section', [
                    'user_id' => $user->id,
                    'types_roles_id' => $grant_role->id,
                    'section_id' => $section_id,
                    'revoked_at' => NULL
                ])->row();
                $this->assertNotNull($exists, "DB record should exist for $grant_role_name in section $section_id");
            }
        }

        // Handle Deny
        if (!empty($deny_sections)) {
            $deny_role = $this->CI->db->get_where('types_roles', ['nom' => $deny_role_name])->row();
            $this->assertNotNull($deny_role, "Role $deny_role_name should exist");

            foreach ($deny_sections as $section_id) {
                // Verify !has_role
                $this->assertFalse(
                    $this->CI->gvv_authorization->has_role($user->id, $deny_role_name, $section_id),
                    "User should NOT have $deny_role_name in section $section_id"
                );

                // Verify !can_access
                $this->_simulateLogin($user->id, $section_id);
                $this->assertFalse(
                    $this->CI->gvv_authorization->can_access($user->id, 'welcome', 'index', $section_id),
                    "User should NOT access welcome/index in section $section_id"
                );

                // Verify DB (absence)
                $exists = $this->CI->db->get_where('user_roles_per_section', [
                    'user_id' => $user->id,
                    'types_roles_id' => $deny_role->id,
                    'section_id' => $section_id,
                    'revoked_at' => NULL
                ])->row();
                $this->assertNull($exists, "DB record should NOT exist for $deny_role_name in section $section_id");
            }
        }
    }

    /**
     * Test that enabling new authorization for a user bypasses legacy checks
     */
    public function testNewAuthorizationBypassesLegacy()
    {
        // Load library
        $this->CI->load->library('Gvv_Authorization');

        // 1. Ensure testuser has legacy 'user' role
        // In legacy system, roles are in 'users' table or 'users_roles' table depending on implementation
        // For this test, we assume testuser is set up correctly as per comments

        // 2. Enable new authorization for testuser
        $this->_setNewAuthorizationForUser('testuser', TRUE);

        // 3. Verify legacy access is ignored (should fail without new roles)
        // We check section 2 where we haven't granted any new roles yet
        $section_id = 2;

        // Get testuser ID
        $testuser = $this->CI->db->get_where('users', ['username' => 'testuser'])->row();
        $this->assertNotNull($testuser, "Test user 'testuser' should exist");

        $this->_simulateLogin($testuser->id, $section_id); // 1 is testuser id

        $can_access = $this->CI->gvv_authorization->can_access(
            $testuser->id, // testuser id
            'welcome',
            'index',
            $section_id
        );
        $this->assertFalse($can_access, "Access should be DENIED in section $section_id when new auth is enabled but no roles granted (legacy ignored)");

        // 4. Grant new role for section 2
        $this->_grantRoleAndVerifyAccess('testuser', 'user', [$section_id], 'user', []);

        // 5. Verify access is now GRANTED
        $can_access_after = $this->CI->gvv_authorization->can_access(
            $testuser->id,
            'welcome',
            'index',
            $section_id
        );
        $this->assertTrue($can_access_after, "Access should be GRANTED in section $section_id after granting new role");

        // 6. Clean up - Disable new authorization
        $this->_setNewAuthorizationForUser('testuser', FALSE);
    }

    /**
     * Helper to enable/disable new authorization for a user
     * 
     * @param string $username
     * @param bool $enable
     */
    protected function _setNewAuthorizationForUser($username, $enable)
    {
        if ($enable) {
            // Add to table if not exists
            $username_safe = "'" . addslashes($username) . "'";
            $sql = "INSERT IGNORE INTO use_new_authorization (username, notes) VALUES ($username_safe, 'Test enablement')";
            $this->CI->db->query($sql);
        } else {
            // Remove from table
            $this->CI->db->delete('use_new_authorization', ['username' => $username]);
        }
    }

    /**
     * Simulate user login by setting up session data
     */
    private function _simulateLogin($user_id, $section_id)
    {
        $user = $this->CI->db->get_where('users', ['id' => $user_id])->row();

        $this->CI->session->set_userdata([
            'DX_user_id' => $user->id,
            'DX_username' => $user->username,
            'DX_logged_in' => TRUE,
            'section' => $section_id
        ]);
    }
}
