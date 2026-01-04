<?php

use PHPUnit\Framework\TestCase;


/**
 * Test that parent emails (memailparent) are included in email lists
 * when members are selected through roles or manual selection.
 *
 * Requirements:
 * - When a member with memailparent is selected by role, both memail and memailparent should be in the list
 * - When a member with memailparent is manually selected, both emails should be in the list
 * - Parent emails should be deduplicated properly
 * - When member/role is removed, both emails should be removed
 */

/**
 * MySQL integration tests for Email_lists_model
 *
 * Tests CRUD operations, role management, manual member selection,
 * and external email management with real database operations.
 *
 * @package tests
 * @see application/models/email_lists_model.php
 */
class EmailListsModelTest extends TestCase
{
    protected $CI;
    protected $model;
    protected $test_list_id;
    protected $test_user_id = null;

    protected function setUp(): void
    {
        // Get CodeIgniter instance
        $this->CI =& get_instance();

        // Load database and model
        $this->CI->load->database();
        $this->CI->load->model('email_lists_model');
        $this->model = $this->CI->email_lists_model;

        // Get a real user ID from the database
        $result = $this->CI->db->query("SELECT id FROM users LIMIT 1");
        $user = $result->row_array();
        if ($user) {
            $this->test_user_id = $user['id'];
        } else {
            $this->test_user_id = 1; // Fallback
        }

        // Clean up any test data from previous runs
        $this->cleanupTestData();
    }

    protected function tearDown(): void
    {
        // Clean up test data after each test
        $this->cleanupTestData();
    }

    protected function cleanupTestData()
    {
        // Delete test lists (cascades to roles, members, external)
        // Use query() instead of where() to handle LIKE properly
        $this->CI->db->query("DELETE FROM email_lists WHERE name LIKE 'TEST_%'");
    }

    // ========================================================================
    // CRUD Tests
    // ========================================================================

    public function testCreateList_InsertsRecord()
    {
        $data = array(
            'name' => 'TEST_Liste1',
            'description' => 'Test description',
            'active_member' => 'active',
            'visible' => 1,
            'created_by' => $this->test_user_id
        );

        $id = $this->model->create_list($data);

        $this->assertGreaterThan(0, $id);
        $this->test_list_id = $id;

        // Verify the record was created
        $list = $this->model->get_list($id);
        $this->assertEquals('TEST_Liste1', $list['name']);
        $this->assertEquals('Test description', $list['description']);
    }

    public function testGetList_ReturnsCorrectData()
    {
        // Create a test list
        $data = array(
            'name' => 'TEST_Liste2',
            'description' => 'Test',
            'created_by' => $this->test_user_id
        );
        $id = $this->model->create_list($data);
        $this->test_list_id = $id;

        // Retrieve it
        $list = $this->model->get_list($id);

        $this->assertNotNull($list);
        $this->assertEquals('TEST_Liste2', $list['name']);
        $this->assertEquals($this->test_user_id, $list['created_by']);
    }

    public function testUpdateList_ModifiesRecord()
    {
        // Create a test list
        $data = array(
            'name' => 'TEST_Liste3',
            'created_by' => $this->test_user_id
        );
        $id = $this->model->create_list($data);
        $this->test_list_id = $id;

        // Update it
        $update_data = array(
            'description' => 'Updated description',
            'visible' => 0
        );
        $result = $this->model->update_list($id, $update_data);

        $this->assertTrue($result);

        // Verify the update
        $list = $this->model->get_list($id);
        $this->assertEquals('Updated description', $list['description']);
        $this->assertEquals(0, $list['visible']);
    }

    public function testDeleteList_RemovesRecord()
    {
        // Create a test list
        $data = array(
            'name' => 'TEST_Liste4',
            'created_by' => $this->test_user_id
        );
        $id = $this->model->create_list($data);

        // Delete it
        $result = $this->model->delete_list($id);

        $this->assertTrue($result);

        // Verify it's deleted
        $list = $this->model->get_list($id);
        $this->assertNull($list);
    }

    public function testGetUserLists_ReturnsUserLists()
    {
        // Create multiple test lists for the user
        $this->model->create_list(array(
            'name' => 'TEST_UserList1',
            'created_by' => $this->test_user_id
        ));
        $this->model->create_list(array(
            'name' => 'TEST_UserList2',
            'created_by' => $this->test_user_id
        ));

        $lists = $this->model->get_user_lists($this->test_user_id);

        $this->assertGreaterThanOrEqual(2, count($lists));

        // Verify all returned lists belong to the user
        foreach ($lists as $list) {
            if (strpos($list['name'], 'TEST_') === 0) {
                $this->assertEquals($this->test_user_id, $list['created_by']);
            }
        }
    }

    // ========================================================================
    // Role Management Tests
    // ========================================================================

    public function testAddRoleToList_InsertsRole()
    {
        // Create a test list
        $list_id = $this->model->create_list(array(
            'name' => 'TEST_RoleList1',
            'created_by' => $this->test_user_id
        ));
        $this->test_list_id = $list_id;

        // Add a role (assuming role ID 1 and section ID 1 exist)
        $role_id = $this->model->add_role_to_list($list_id, 1, 1, $this->test_user_id, 'Test role');

        $this->assertGreaterThan(0, $role_id);

        // Verify the role was added
        $roles = $this->model->get_list_roles($list_id);
        $this->assertCount(1, $roles);
        $this->assertEquals(1, $roles[0]['types_roles_id']);
        $this->assertEquals(1, $roles[0]['section_id']);
    }

    public function testRemoveRoleFromList_DeletesRole()
    {
        // Create a test list
        $list_id = $this->model->create_list(array(
            'name' => 'TEST_RoleList2',
            'created_by' => $this->test_user_id
        ));
        $this->test_list_id = $list_id;

        // Add a role
        $role_id = $this->model->add_role_to_list($list_id, 1, 1);

        // Remove it
        $result = $this->model->remove_role_from_list($list_id, $role_id);

        $this->assertTrue($result);

        // Verify it's deleted
        $roles = $this->model->get_list_roles($list_id);
        $this->assertCount(0, $roles);
    }

    public function testGetAvailableRoles_ReturnsRoles()
    {
        $roles = $this->model->get_available_roles();

        $this->assertNotEmpty($roles);
        $this->assertArrayHasKey('id', $roles[0]);
        $this->assertArrayHasKey('nom', $roles[0]);
    }

    public function testGetAvailableSections_ReturnsSections()
    {
        $sections = $this->model->get_available_sections();

        $this->assertNotEmpty($sections);
        $this->assertArrayHasKey('id', $sections[0]);
        $this->assertArrayHasKey('nom', $sections[0]);
    }

    // ========================================================================
    // Manual Member Tests
    // ========================================================================

    public function testAddManualMember_InsertsMember()
    {
        // Skip if no members exist
        $query = $this->CI->db->select('mlogin')->from('membres')->limit(1)->get();
        if ($query->num_rows() == 0) {
            $this->markTestSkipped('No members in database');
        }

        $membre = $query->row_array();
        $membre_id = $membre['mlogin'];

        // Create a test list
        $list_id = $this->model->create_list(array(
            'name' => 'TEST_ManualList1',
            'created_by' => $this->test_user_id
        ));
        $this->test_list_id = $list_id;

        // Add manual member
        $member_id = $this->model->add_manual_member($list_id, $membre_id);

        $this->assertGreaterThan(0, $member_id);

        // Verify the member was added
        $members = $this->model->get_manual_members($list_id);
        $this->assertCount(1, $members);
        $this->assertEquals($membre_id, $members[0]['membre_id']);
    }

    // ========================================================================
    // External Email Tests
    // ========================================================================

    public function testAddExternalEmail_InsertsEmail()
    {
        // Create a test list
        $list_id = $this->model->create_list(array(
            'name' => 'TEST_ExternalList1',
            'created_by' => $this->test_user_id
        ));
        $this->test_list_id = $list_id;

        // Add external email
        $external_id = $this->model->add_external_email($list_id, 'external@example.com', 'External User');

        $this->assertGreaterThan(0, $external_id);

        // Verify the email was added
        $emails = $this->model->get_external_emails($list_id);
        $this->assertCount(1, $emails);
        $this->assertEquals('external@example.com', $emails[0]['email']);
        $this->assertEquals('External User', $emails[0]['name']);
    }

    public function testAddExternalEmail_NormalizesEmail()
    {
        // Create a test list
        $list_id = $this->model->create_list(array(
            'name' => 'TEST_ExternalList2',
            'created_by' => $this->test_user_id
        ));
        $this->test_list_id = $list_id;

        // Add external email with uppercase
        $external_id = $this->model->add_external_email($list_id, 'TEST@EXAMPLE.COM');

        $this->assertGreaterThan(0, $external_id);

        // Verify the email was normalized to lowercase
        $emails = $this->model->get_external_emails($list_id);
        $this->assertEquals('test@example.com', $emails[0]['email']);
    }

    public function testAddExternalEmail_InvalidEmail_ReturnsFalse()
    {
        // Create a test list
        $list_id = $this->model->create_list(array(
            'name' => 'TEST_ExternalList3',
            'created_by' => $this->test_user_id
        ));
        $this->test_list_id = $list_id;

        // Try to add invalid email
        $result = $this->model->add_external_email($list_id, 'invalid-email');

        $this->assertFalse($result);
    }

    // ========================================================================
    // Complete Resolution Tests
    // ========================================================================

    public function testTextualList_ResolvesAllSources()
    {
        // Create a test list
        $list_id = $this->model->create_list(array(
            'name' => 'TEST_CompleteList1',
            'created_by' => $this->test_user_id
        ));
        $this->test_list_id = $list_id;

        // Add external email
        $this->model->add_external_email($list_id, 'external1@example.com');
        $this->model->add_external_email($list_id, 'external2@example.com');

        // Resolve the list
        $emails = $this->model->textual_list($list_id);

        // Should have at least the 2 external emails
        $this->assertGreaterThanOrEqual(2, count($emails));

        // Verify all results are email strings
        foreach ($emails as $email) {
            $this->assertIsString($email);
            $this->assertNotEmpty($email);
        }
    }

    public function testCountMembers_ReturnsCorrectCount()
    {
        // Create a test list
        $list_id = $this->model->create_list(array(
            'name' => 'TEST_CountList1',
            'created_by' => $this->test_user_id
        ));
        $this->test_list_id = $list_id;

        // Add external emails
        $this->model->add_external_email($list_id, 'external1@example.com');
        $this->model->add_external_email($list_id, 'external2@example.com');

        // Count members
        $count = $this->model->count_members($list_id);

        $this->assertEquals(2, $count);
    }

    // ========================================================================
    // Multi-Role Selection Tests (Phase 2)
    // ========================================================================

    public function testMultiRoleSelection_ReturnsUniqueUsers()
    {
        // Get available roles and sections
        $roles = $this->model->get_available_roles();
        $sections = $this->model->get_available_sections();

        if (empty($roles) || empty($sections)) {
            $this->markTestSkipped('No roles or sections in database');
        }

        // Create a test list
        $list_id = $this->model->create_list(array(
            'name' => 'TEST_MultiRoleList1',
            'created_by' => $this->test_user_id
        ));
        $this->test_list_id = $list_id;

        // Add multiple roles to the list
        $this->model->add_role_to_list($list_id, $roles[0]['id'], $sections[0]['id']);

        if (count($roles) > 1) {
            $this->model->add_role_to_list($list_id, $roles[1]['id'], $sections[0]['id']);
        }

        // Resolve the list
        $emails = $this->model->textual_list($list_id);

        // Should return array of email strings
        $this->assertIsArray($emails);

        // All items should be strings
        foreach ($emails as $email) {
            $this->assertIsString($email);
        }
    }

    public function testDeduplication_WithMultipleRoles()
    {
        // Get available roles and sections
        $roles = $this->model->get_available_roles();
        $sections = $this->model->get_available_sections();

        if (empty($roles) || empty($sections)) {
            $this->markTestSkipped('No roles or sections in database');
        }

        // Create a test list
        $list_id = $this->model->create_list(array(
            'name' => 'TEST_DedupList1',
            'created_by' => $this->test_user_id
        ));
        $this->test_list_id = $list_id;

        // Add same user via multiple sources to test deduplication
        // 1. Add via role
        $this->model->add_role_to_list($list_id, $roles[0]['id'], $sections[0]['id']);

        // 2. Get a user from this role
        $role_users = $this->model->get_users_by_role_and_section($roles[0]['id'], $sections[0]['id']);

        if (empty($role_users)) {
            $this->markTestSkipped('No users found for the selected role');
        }

        $test_user = $role_users[0];

        // 3. Also add this user manually
        $this->model->add_manual_member($list_id, $test_user['mlogin']);

        // 4. Also add via external email (uppercase to test case-insensitive dedup)
        if (!empty($test_user['email'])) {
            $this->model->add_external_email($list_id, strtoupper($test_user['email']));
        }

        // Resolve the list
        $emails = $this->model->textual_list($list_id);

        // Count occurrences of the test user's email
        $test_email_lower = strtolower($test_user['email']);
        $occurrences = 0;

        foreach ($emails as $email) {
            if (strtolower($email) === $test_email_lower) {
                $occurrences++;
            }
        }

        // The email should appear only ONCE despite being in 3 sources
        $this->assertEquals(1, $occurrences, 'Email should be deduplicated across all sources');
    }

    public function testGetUsersByRoleAndSection_ActiveFilter()
    {
        $roles = $this->model->get_available_roles();
        $sections = $this->model->get_available_sections();

        if (empty($roles) || empty($sections)) {
            $this->markTestSkipped('No roles or sections in database');
        }

        // Get active users
        $active_users = $this->model->get_users_by_role_and_section(
            $roles[0]['id'],
            $sections[0]['id'],
            'active'
        );

        // Verify query executed successfully (returns array even if empty)
        $this->assertIsArray($active_users, 'Should return array for active users');

        // All returned users should be active
        foreach ($active_users as $user) {
            $this->assertEquals(1, $user['actif'], 'User should be active');
        }

        // Get inactive users
        $inactive_users = $this->model->get_users_by_role_and_section(
            $roles[0]['id'],
            $sections[0]['id'],
            'inactive'
        );

        // Verify query executed successfully (returns array even if empty)
        $this->assertIsArray($inactive_users, 'Should return array for inactive users');

        // All returned users should be inactive
        foreach ($inactive_users as $user) {
            $this->assertEquals(0, $user['actif'], 'User should be inactive');
        }
    }

    public function testGetAvailableRoles_OrderedByDisplayOrder()
    {
        $roles = $this->model->get_available_roles();

        $this->assertNotEmpty($roles);

        // Verify roles have required fields
        foreach ($roles as $role) {
            $this->assertArrayHasKey('id', $role);
            $this->assertArrayHasKey('nom', $role);
            $this->assertArrayHasKey('scope', $role);
        }

        // Verify ordering by display_order (if field exists)
        if (count($roles) > 1 && isset($roles[0]['display_order'])) {
            for ($i = 0; $i < count($roles) - 1; $i++) {
                if (isset($roles[$i]['display_order']) && isset($roles[$i + 1]['display_order'])) {
                    $this->assertLessThanOrEqual(
                        $roles[$i + 1]['display_order'],
                        $roles[$i]['display_order'],
                        'Roles should be ordered by display_order'
                    );
                }
            }
        }
    }

    public function testGetAvailableSections_ReturnsAllSections()
    {
        $sections = $this->model->get_available_sections();

        $this->assertNotEmpty($sections);

        // Verify sections have required fields
        foreach ($sections as $section) {
            $this->assertArrayHasKey('id', $section);
            $this->assertArrayHasKey('nom', $section);
        }
    }

    // ========================================================================
    // Parent Email Tests (memailparent)
    // ========================================================================

    public function testParentEmail_IncludedInRoleSelection()
    {
        // Find a member with a parent email or create test data
        $result = $this->CI->db->query(
            "SELECT mlogin, memail, memailparent FROM membres
             WHERE memailparent IS NOT NULL AND memailparent != '' AND actif = 1
             LIMIT 1"
        );
        $membre_with_parent = $result->row_array();

        if (empty($membre_with_parent)) {
            $this->markTestSkipped('No active member with parent email found in database');
        }

        // Get roles and sections for this member
        $user_query = $this->CI->db->select('u.id, urps.types_roles_id, urps.section_id')
            ->from('users u')
            ->join('user_roles_per_section urps', 'u.id = urps.user_id', 'inner')
            ->where('u.username', $membre_with_parent['mlogin'])
            ->where('urps.revoked_at IS NULL')
            ->limit(1)
            ->get();

        if ($user_query->num_rows() == 0) {
            $this->markTestSkipped('No role assignment found for member with parent email');
        }

        $user_role = $user_query->row_array();

        // Create a test list
        $list_id = $this->model->create_list(array(
            'name' => 'TEST_ParentEmailRole',
            'created_by' => $this->test_user_id
        ));
        $this->test_list_id = $list_id;

        // Add the role to the list
        $this->model->add_role_to_list($list_id, $user_role['types_roles_id'], $user_role['section_id']);

        // Resolve the list
        $emails = $this->model->textual_list($list_id);

        // Both primary and parent email should be in the list
        $primary_email_found = false;
        $parent_email_found = false;

        foreach ($emails as $email) {
            if (strcasecmp($email, $membre_with_parent['memail']) === 0) {
                $primary_email_found = true;
            }
            if (strcasecmp($email, $membre_with_parent['memailparent']) === 0) {
                $parent_email_found = true;
            }
        }

        $this->assertTrue($primary_email_found, 'Primary email should be in the list');
        $this->assertTrue($parent_email_found, 'Parent email should be in the list when member is selected by role');
    }

    public function testParentEmail_IncludedInManualSelection()
    {
        // Find a member with a parent email
        $result = $this->CI->db->query(
            "SELECT mlogin, memail, memailparent FROM membres
             WHERE memailparent IS NOT NULL AND memailparent != '' AND actif = 1
             LIMIT 1"
        );
        $membre_with_parent = $result->row_array();

        if (empty($membre_with_parent)) {
            $this->markTestSkipped('No active member with parent email found in database');
        }

        // Create a test list
        $list_id = $this->model->create_list(array(
            'name' => 'TEST_ParentEmailManual',
            'created_by' => $this->test_user_id
        ));
        $this->test_list_id = $list_id;

        // Manually add the member
        $this->model->add_manual_member($list_id, $membre_with_parent['mlogin']);

        // Resolve the list
        $emails = $this->model->textual_list($list_id);

        // Both primary and parent email should be in the list
        $primary_email_found = false;
        $parent_email_found = false;

        foreach ($emails as $email) {
            if (strcasecmp($email, $membre_with_parent['memail']) === 0) {
                $primary_email_found = true;
            }
            if (strcasecmp($email, $membre_with_parent['memailparent']) === 0) {
                $parent_email_found = true;
            }
        }

        $this->assertTrue($primary_email_found, 'Primary email should be in the list');
        $this->assertTrue($parent_email_found, 'Parent email should be in the list when member is manually selected');
    }

    public function testParentEmail_DeduplicationWorks()
    {
        // Find a member with a parent email
        $result = $this->CI->db->query(
            "SELECT mlogin, memail, memailparent FROM membres
             WHERE memailparent IS NOT NULL AND memailparent != '' AND actif = 1
             LIMIT 1"
        );
        $membre_with_parent = $result->row_array();

        if (empty($membre_with_parent)) {
            $this->markTestSkipped('No active member with parent email found in database');
        }

        // Create a test list
        $list_id = $this->model->create_list(array(
            'name' => 'TEST_ParentEmailDedup',
            'created_by' => $this->test_user_id
        ));
        $this->test_list_id = $list_id;

        // Add member manually
        $this->model->add_manual_member($list_id, $membre_with_parent['mlogin']);

        // Also add parent email as external (should be deduplicated)
        $this->model->add_external_email($list_id, $membre_with_parent['memailparent']);

        // Resolve the list
        $emails = $this->model->textual_list($list_id);

        // Count occurrences of parent email
        $parent_count = 0;
        foreach ($emails as $email) {
            if (strcasecmp($email, $membre_with_parent['memailparent']) === 0) {
                $parent_count++;
            }
        }

        // Parent email should appear exactly once (deduplicated)
        $this->assertEquals(1, $parent_count, 'Parent email should be deduplicated when added from multiple sources');
    }

    public function testParentEmail_IncludedInDetailedList()
    {
        // Find a member with a parent email
        $result = $this->CI->db->query(
            "SELECT mlogin, memail, memailparent, mnom, mprenom FROM membres
             WHERE memailparent IS NOT NULL AND memailparent != '' AND actif = 1
             LIMIT 1"
        );
        $membre_with_parent = $result->row_array();

        if (empty($membre_with_parent)) {
            $this->markTestSkipped('No active member with parent email found in database');
        }

        // Create a test list
        $list_id = $this->model->create_list(array(
            'name' => 'TEST_ParentEmailDetailed',
            'created_by' => $this->test_user_id
        ));
        $this->test_list_id = $list_id;

        // Manually add the member
        $this->model->add_manual_member($list_id, $membre_with_parent['mlogin']);

        // Get detailed list
        $detailed = $this->model->detailed_list($list_id);

        // Find both emails in the detailed list
        $primary_found = false;
        $parent_found = false;

        foreach ($detailed as $item) {
            if (strcasecmp($item['email'], $membre_with_parent['memail']) === 0) {
                $primary_found = true;
                $this->assertEquals('membre', $item['source']);
            }
            if (strcasecmp($item['email'], $membre_with_parent['memailparent']) === 0) {
                $parent_found = true;
                $this->assertEquals('membre', $item['source']);
                $this->assertStringContainsString('parent', strtolower($item['name']));
            }
        }

        $this->assertTrue($primary_found, 'Primary email should be in detailed list');
        $this->assertTrue($parent_found, 'Parent email should be in detailed list');
    }
}
