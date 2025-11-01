<?php

use PHPUnit\Framework\TestCase;

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
    protected $test_user_id = 1; // Assuming user ID 1 exists

    protected function setUp(): void
    {
        // Get CodeIgniter instance
        $this->CI =& get_instance();

        // Load database and model
        $this->CI->load->database();
        $this->CI->load->model('email_lists_model');
        $this->model = $this->CI->email_lists_model;

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
        $this->CI->db->where('name LIKE', 'TEST_%');
        $this->CI->db->delete('email_lists');
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
}
