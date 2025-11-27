<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL integration tests for email_lists_model sublist methods
 *
 * Tests all CRUD operations for sublist management including:
 * - add_sublist() with all validations
 * - remove_sublist()
 * - get_sublists()
 * - has_sublists()
 * - get_parent_lists()
 * - get_available_sublists()
 *
 * @package tests
 * @see application/models/email_lists_model.php
 */
class EmailListsSublistsModelTest extends TestCase
{
    protected $CI;
    protected $db;
    protected $model;
    protected $test_user_id;

    protected function setUp(): void
    {
        // Get CodeIgniter instance
        $this->CI =& get_instance();
        $this->db = $this->CI->db;

        // Load the email_lists_model
        $this->CI->load->model('email_lists_model');
        $this->model = $this->CI->email_lists_model;

        // Get a real user ID for testing
        $user_query = $this->db->query("SELECT id FROM users LIMIT 1");
        $user = $user_query->row_array();
        $this->test_user_id = $user ? $user['id'] : 1;

        // Cleanup any leftover test data
        $this->cleanupTestData();
    }

    protected function tearDown(): void
    {
        // Cleanup after each test
        $this->cleanupTestData();
    }

    /**
     * Helper method to clean up test data
     */
    protected function cleanupTestData()
    {
        // Delete sublists first (FK constraint)
        $this->db->query("DELETE FROM email_list_sublists WHERE parent_list_id IN (
            SELECT id FROM email_lists WHERE name LIKE 'TEST_SUBLIST_%'
        )");

        // Then delete lists
        $this->db->query("DELETE FROM email_lists WHERE name LIKE 'TEST_SUBLIST_%'");
    }

    /**
     * Helper method to create a test list
     */
    protected function createTestList($name, $visible = 1)
    {
        return $this->model->create_list([
            'name' => $name,
            'created_by' => $this->test_user_id,
            'visible' => $visible
        ]);
    }

    /**
     * Test add_sublist() - nominal case
     */
    public function testAddSublist_Nominal_Success()
    {
        // Create parent and child lists
        $parent_id = $this->createTestList('TEST_SUBLIST_PARENT_1', 1);
        $child_id = $this->createTestList('TEST_SUBLIST_CHILD_1', 1);

        // Add sublist relationship
        $result = $this->model->add_sublist($parent_id, $child_id);

        $this->assertTrue($result['success'], 'add_sublist should succeed');
        $this->assertNull($result['error'], 'No error should be returned');

        // Verify in database
        $query = $this->db->query("SELECT COUNT(*) as count FROM email_list_sublists
                                   WHERE parent_list_id = $parent_id AND child_list_id = $child_id");
        $count = $query->row_array()['count'];
        $this->assertEquals(1, $count, 'Sublist relationship should exist in database');
    }

    /**
     * Test add_sublist() - self-reference (should fail)
     */
    public function testAddSublist_SelfReference_Fails()
    {
        // Create a list
        $list_id = $this->createTestList('TEST_SUBLIST_SELF', 1);

        // Try to add itself as sublist
        $result = $this->model->add_sublist($list_id, $list_id);

        $this->assertFalse($result['success'], 'add_sublist should fail for self-reference');
        $this->assertStringContainsString('elle-même', $result['error'], 'Error message should mention self-reference');
    }

    /**
     * Test add_sublist() - depth > 1 (should fail)
     */
    public function testAddSublist_DepthGreaterThanOne_Fails()
    {
        // Create three lists: A, B, C
        $list_a = $this->createTestList('TEST_SUBLIST_A', 1);
        $list_b = $this->createTestList('TEST_SUBLIST_B', 1);
        $list_c = $this->createTestList('TEST_SUBLIST_C', 1);

        // A contains B
        $result = $this->model->add_sublist($list_a, $list_b);
        $this->assertTrue($result['success'], 'First sublist should be added');

        // Try to add C as sublist of B (depth would be 2)
        $result = $this->model->add_sublist($list_b, $list_c);

        $this->assertFalse($result['success'], 'add_sublist should fail when child already has sublists');
        $this->assertStringContainsString('sous-listes', $result['error'], 'Error message should mention sublists');
    }

    /**
     * Test add_sublist() - duplicate (should fail)
     */
    public function testAddSublist_Duplicate_Fails()
    {
        // Create parent and child
        $parent_id = $this->createTestList('TEST_SUBLIST_PARENT_DUP', 1);
        $child_id = $this->createTestList('TEST_SUBLIST_CHILD_DUP', 1);

        // Add sublist first time
        $result = $this->model->add_sublist($parent_id, $child_id);
        $this->assertTrue($result['success'], 'First add should succeed');

        // Try to add again
        $result = $this->model->add_sublist($parent_id, $child_id);

        $this->assertFalse($result['success'], 'add_sublist should fail for duplicate');
        $this->assertStringContainsString('déjà incluse', $result['error'], 'Error message should mention duplicate');
    }

    /**
     * Test add_sublist() - visibility incoherence (should fail)
     */
    public function testAddSublist_VisibilityIncoherence_Fails()
    {
        // Create public parent and private child
        $parent_id = $this->createTestList('TEST_SUBLIST_PUBLIC_PARENT', 1);
        $child_id = $this->createTestList('TEST_SUBLIST_PRIVATE_CHILD', 0);

        // Try to add private child to public parent
        $result = $this->model->add_sublist($parent_id, $child_id);

        $this->assertFalse($result['success'], 'add_sublist should fail for visibility incoherence');
        $this->assertStringContainsString('privée', $result['error'], 'Error message should mention private');
        $this->assertStringContainsString('publique', $result['error'], 'Error message should mention public');
    }

    /**
     * Test add_sublist() - private parent with public child (should succeed)
     */
    public function testAddSublist_PrivateParentPublicChild_Success()
    {
        // Create private parent and public child
        $parent_id = $this->createTestList('TEST_SUBLIST_PRIVATE_PARENT', 0);
        $child_id = $this->createTestList('TEST_SUBLIST_PUBLIC_CHILD', 1);

        // Private parent can have public child
        $result = $this->model->add_sublist($parent_id, $child_id);

        $this->assertTrue($result['success'], 'add_sublist should succeed for private parent with public child');
        $this->assertNull($result['error'], 'No error should be returned');
    }

    /**
     * Test add_sublist() - private parent with private child (should succeed)
     */
    public function testAddSublist_PrivateParentPrivateChild_Success()
    {
        // Create private parent and private child
        $parent_id = $this->createTestList('TEST_SUBLIST_PRIVATE_PARENT2', 0);
        $child_id = $this->createTestList('TEST_SUBLIST_PRIVATE_CHILD2', 0);

        // Private parent can have private child
        $result = $this->model->add_sublist($parent_id, $child_id);

        $this->assertTrue($result['success'], 'add_sublist should succeed for private parent with private child');
        $this->assertNull($result['error'], 'No error should be returned');
    }

    /**
     * Test add_sublist() - nonexistent parent (should fail)
     */
    public function testAddSublist_NonexistentParent_Fails()
    {
        $child_id = $this->createTestList('TEST_SUBLIST_ORPHAN', 1);

        // Try to add with non-existent parent
        $result = $this->model->add_sublist(999999, $child_id);

        $this->assertFalse($result['success'], 'add_sublist should fail for nonexistent parent');
        $this->assertStringContainsString('introuvable', $result['error'], 'Error message should mention not found');
    }

    /**
     * Test add_sublist() - nonexistent child (should fail)
     */
    public function testAddSublist_NonexistentChild_Fails()
    {
        $parent_id = $this->createTestList('TEST_SUBLIST_LONELY', 1);

        // Try to add with non-existent child
        $result = $this->model->add_sublist($parent_id, 999999);

        $this->assertFalse($result['success'], 'add_sublist should fail for nonexistent child');
        $this->assertStringContainsString('introuvable', $result['error'], 'Error message should mention not found');
    }

    /**
     * Test remove_sublist() - nominal case
     */
    public function testRemoveSublist_Nominal_Success()
    {
        // Create and add sublist
        $parent_id = $this->createTestList('TEST_SUBLIST_PARENT_REMOVE', 1);
        $child_id = $this->createTestList('TEST_SUBLIST_CHILD_REMOVE', 1);
        $this->model->add_sublist($parent_id, $child_id);

        // Remove it
        $result = $this->model->remove_sublist($parent_id, $child_id);

        $this->assertTrue($result['success'], 'remove_sublist should succeed');
        $this->assertNull($result['error'], 'No error should be returned');

        // Verify removed from database
        $query = $this->db->query("SELECT COUNT(*) as count FROM email_list_sublists
                                   WHERE parent_list_id = $parent_id AND child_list_id = $child_id");
        $count = $query->row_array()['count'];
        $this->assertEquals(0, $count, 'Sublist relationship should be removed from database');
    }

    /**
     * Test remove_sublist() - nonexistent relationship
     */
    public function testRemoveSublist_NonexistentRelationship_Success()
    {
        // Create lists but no relationship
        $parent_id = $this->createTestList('TEST_SUBLIST_PARENT_NOREL', 1);
        $child_id = $this->createTestList('TEST_SUBLIST_CHILD_NOREL', 1);

        // Try to remove non-existent relationship (should succeed with 0 rows affected)
        $result = $this->model->remove_sublist($parent_id, $child_id);

        $this->assertTrue($result['success'], 'remove_sublist should succeed even if relationship does not exist');
    }

    /**
     * Test get_sublists() - list with sublists
     */
    public function testGetSublists_WithSublists_ReturnsData()
    {
        // Create parent with two sublists
        $parent_id = $this->createTestList('TEST_SUBLIST_PARENT_GET', 1);
        $child1_id = $this->createTestList('TEST_SUBLIST_CHILD_GET1', 1);
        $child2_id = $this->createTestList('TEST_SUBLIST_CHILD_GET2', 1);

        $this->model->add_sublist($parent_id, $child1_id);
        $this->model->add_sublist($parent_id, $child2_id);

        // Get sublists
        $sublists = $this->model->get_sublists($parent_id);

        $this->assertIsArray($sublists, 'get_sublists should return array');
        $this->assertCount(2, $sublists, 'Should return 2 sublists');

        // Check that sublists contain expected data
        $child_ids = array_column($sublists, 'id');
        $this->assertContains($child1_id, $child_ids, 'Should contain first child');
        $this->assertContains($child2_id, $child_ids, 'Should contain second child');

        // Check that each sublist has expected fields
        foreach ($sublists as $sublist) {
            $this->assertArrayHasKey('id', $sublist);
            $this->assertArrayHasKey('name', $sublist);
            $this->assertArrayHasKey('visible', $sublist);
            $this->assertArrayHasKey('added_at', $sublist);
            $this->assertArrayHasKey('recipient_count', $sublist);
        }
    }

    /**
     * Test get_sublists() - list without sublists
     */
    public function testGetSublists_WithoutSublists_ReturnsEmptyArray()
    {
        // Create list with no sublists
        $list_id = $this->createTestList('TEST_SUBLIST_EMPTY_GET', 1);

        // Get sublists
        $sublists = $this->model->get_sublists($list_id);

        $this->assertIsArray($sublists, 'get_sublists should return array');
        $this->assertCount(0, $sublists, 'Should return empty array');
    }

    /**
     * Test has_sublists() - list with sublists returns TRUE
     */
    public function testHasSublists_WithSublists_ReturnsTrue()
    {
        // Create parent with sublist
        $parent_id = $this->createTestList('TEST_SUBLIST_PARENT_HAS', 1);
        $child_id = $this->createTestList('TEST_SUBLIST_CHILD_HAS', 1);
        $this->model->add_sublist($parent_id, $child_id);

        // Check has_sublists
        $result = $this->model->has_sublists($parent_id);

        $this->assertTrue($result, 'has_sublists should return TRUE when list has sublists');
    }

    /**
     * Test has_sublists() - list without sublists returns FALSE
     */
    public function testHasSublists_WithoutSublists_ReturnsFalse()
    {
        // Create list with no sublists
        $list_id = $this->createTestList('TEST_SUBLIST_NO_HAS', 1);

        // Check has_sublists
        $result = $this->model->has_sublists($list_id);

        $this->assertFalse($result, 'has_sublists should return FALSE when list has no sublists');
    }

    /**
     * Test get_parent_lists() - list used as sublist
     */
    public function testGetParentLists_UsedAsSublist_ReturnsParents()
    {
        // Create child used in two parent lists
        $child_id = $this->createTestList('TEST_SUBLIST_CHILD_PARENTS', 1);
        $parent1_id = $this->createTestList('TEST_SUBLIST_PARENT1_GET', 1);
        $parent2_id = $this->createTestList('TEST_SUBLIST_PARENT2_GET', 1);

        $this->model->add_sublist($parent1_id, $child_id);
        $this->model->add_sublist($parent2_id, $child_id);

        // Get parent lists
        $parents = $this->model->get_parent_lists($child_id);

        $this->assertIsArray($parents, 'get_parent_lists should return array');
        $this->assertCount(2, $parents, 'Should return 2 parents');

        // Check parent IDs
        $parent_ids = array_column($parents, 'id');
        $this->assertContains($parent1_id, $parent_ids, 'Should contain first parent');
        $this->assertContains($parent2_id, $parent_ids, 'Should contain second parent');
    }

    /**
     * Test get_parent_lists() - list not used as sublist
     */
    public function testGetParentLists_NotUsedAsSublist_ReturnsEmptyArray()
    {
        // Create list not used as sublist
        $list_id = $this->createTestList('TEST_SUBLIST_NO_PARENTS', 1);

        // Get parent lists
        $parents = $this->model->get_parent_lists($list_id);

        $this->assertIsArray($parents, 'get_parent_lists should return array');
        $this->assertCount(0, $parents, 'Should return empty array');
    }

    /**
     * Test get_available_sublists() - correct filtering
     */
    public function testGetAvailableSublists_CorrectFiltering()
    {
        // Create various lists
        $public_list = $this->createTestList('TEST_SUBLIST_AVAILABLE_PUBLIC', 1);
        $private_list = $this->createTestList('TEST_SUBLIST_AVAILABLE_PRIVATE', 0);
        $exclude_list = $this->createTestList('TEST_SUBLIST_AVAILABLE_EXCLUDE', 1);
        $has_sublists = $this->createTestList('TEST_SUBLIST_AVAILABLE_PARENT', 1);
        $child_of_parent = $this->createTestList('TEST_SUBLIST_AVAILABLE_CHILD', 1);

        // Make has_sublists actually have sublists
        $this->model->add_sublist($has_sublists, $child_of_parent);

        // Get available sublists (excluding exclude_list)
        $available = $this->model->get_available_sublists($this->test_user_id, false, $exclude_list);

        $this->assertIsArray($available, 'get_available_sublists should return array');

        // Check that public_list and private_list are included
        $available_ids = array_column($available, 'id');
        $this->assertContains($public_list, $available_ids, 'Should include public list');

        // Check that excluded list is not included
        $this->assertNotContains($exclude_list, $available_ids, 'Should not include excluded list');

        // Check that list with sublists is not included (depth constraint)
        $this->assertNotContains($has_sublists, $available_ids, 'Should not include list that has sublists');
    }

    /**
     * Test get_available_sublists() - admin sees all
     */
    public function testGetAvailableSublists_AdminSeesAll()
    {
        // Create public and private lists
        $public_list = $this->createTestList('TEST_SUBLIST_ADMIN_PUBLIC', 1);
        $private_list = $this->createTestList('TEST_SUBLIST_ADMIN_PRIVATE', 0);

        // Get available sublists as admin
        $available = $this->model->get_available_sublists($this->test_user_id, true, null);

        $this->assertIsArray($available, 'get_available_sublists should return array');

        $available_ids = array_column($available, 'id');
        $this->assertContains($public_list, $available_ids, 'Admin should see public list');
        $this->assertContains($private_list, $available_ids, 'Admin should see private list');
    }
}
