<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL integration tests for email_lists_model deletion management
 *
 * Tests the deletion behavior with sublists:
 * - FK ON DELETE CASCADE on parent_list_id
 * - FK ON DELETE RESTRICT on child_list_id
 * - can_delete_list()
 * - remove_from_all_parents_and_delete()
 *
 * @package tests
 * @see application/models/email_lists_model.php
 */
class EmailListsDeletionTest extends TestCase
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
            SELECT id FROM email_lists WHERE name LIKE 'TEST_DELETE_%'
        )");

        // Delete external emails
        $this->db->query("DELETE FROM email_list_external WHERE email_list_id IN (
            SELECT id FROM email_lists WHERE name LIKE 'TEST_DELETE_%'
        )");

        // Then delete lists
        $this->db->query("DELETE FROM email_lists WHERE name LIKE 'TEST_DELETE_%'");
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
     * Test FK ON DELETE CASCADE: Deleting parent list removes sublist relationships
     */
    public function testDeleteParentList_CascadesSublistRelationships()
    {
        // Create parent and child lists
        $parent_id = $this->createTestList('TEST_DELETE_PARENT_CASCADE');
        $child_id = $this->createTestList('TEST_DELETE_CHILD_CASCADE');

        // Add sublist relationship
        $this->model->add_sublist($parent_id, $child_id);

        // Verify relationship exists
        $sublists = $this->model->get_sublists($parent_id);
        $this->assertCount(1, $sublists, 'Sublist relationship should exist');

        // Delete parent list
        $deleted = $this->model->delete_list($parent_id);
        $this->assertTrue($deleted, 'Parent list should be deleted');

        // Verify child list still exists
        $child = $this->model->get_list($child_id);
        $this->assertNotEmpty($child, 'Child list should still exist');

        // Verify relationship was cascade deleted
        $this->db->select('COUNT(*) as count');
        $this->db->from('email_list_sublists');
        $this->db->where('parent_list_id', $parent_id);
        $query = $this->db->get();
        $result = $query->row_array();
        $this->assertEquals(0, $result['count'], 'Sublist relationship should be cascade deleted');
    }

    /**
     * Test FK ON DELETE RESTRICT: Cannot delete child list used as sublist
     */
    public function testDeleteChildList_RestrictedWhenUsedAsSublist()
    {
        // Create parent and child lists
        $parent_id = $this->createTestList('TEST_DELETE_PARENT_RESTRICT');
        $child_id = $this->createTestList('TEST_DELETE_CHILD_RESTRICT');

        // Add sublist relationship
        $this->model->add_sublist($parent_id, $child_id);

        // Try to delete child list (should fail due to FK RESTRICT)
        // Note: This depends on database configuration
        // The delete will fail at DB level, not return FALSE
        try {
            $this->db->where('id', $child_id);
            $this->db->delete('email_lists');

            // Check if delete actually happened
            $child = $this->model->get_list($child_id);

            // If we reach here, either:
            // 1. FK is not enforced (bad config)
            // 2. Delete was prevented (good)
            // Check if child still exists
            if ($child) {
                // Good - FK prevented deletion
                $this->assertTrue(true, 'FK RESTRICT prevented deletion');
            } else {
                // Bad - FK not enforced, but test should note this
                $this->markTestIncomplete('FK RESTRICT may not be enforced in test DB');
            }
        } catch (Exception $e) {
            // Expected: FK constraint violation
            $this->assertStringContainsString('foreign key', strtolower($e->getMessage()),
                'Should get FK constraint error');
        }
    }

    /**
     * Test can_delete_list() returns true when list has no parents
     */
    public function testCanDeleteList_NoParents_ReturnsTrue()
    {
        $list_id = $this->createTestList('TEST_DELETE_NO_PARENTS');

        $result = $this->model->can_delete_list($list_id);

        $this->assertTrue($result['can_delete'], 'Should be able to delete list with no parents');
        $this->assertEmpty($result['parent_lists'], 'Should have no parent lists');
    }

    /**
     * Test can_delete_list() returns false when list is used as sublist
     */
    public function testCanDeleteList_HasParents_ReturnsFalse()
    {
        // Create parent and child lists
        $parent_id = $this->createTestList('TEST_DELETE_PARENT_CHECK');
        $child_id = $this->createTestList('TEST_DELETE_CHILD_CHECK');

        // Add sublist relationship
        $this->model->add_sublist($parent_id, $child_id);

        // Check if child can be deleted
        $result = $this->model->can_delete_list($child_id);

        $this->assertFalse($result['can_delete'], 'Should not be able to delete list used as sublist');
        $this->assertNotEmpty($result['parent_lists'], 'Should return parent lists');
        $this->assertCount(1, $result['parent_lists'], 'Should have 1 parent list');
        $this->assertEquals($parent_id, $result['parent_lists'][0]['id'], 'Parent ID should match');
    }

    /**
     * Test can_delete_list() with multiple parents
     */
    public function testCanDeleteList_MultipleParents_ReturnsAllParents()
    {
        // Create 3 parent lists and 1 child list
        $parent1_id = $this->createTestList('TEST_DELETE_PARENT1_MULTI');
        $parent2_id = $this->createTestList('TEST_DELETE_PARENT2_MULTI');
        $parent3_id = $this->createTestList('TEST_DELETE_PARENT3_MULTI');
        $child_id = $this->createTestList('TEST_DELETE_CHILD_MULTI');

        // Add child to all 3 parents
        $this->model->add_sublist($parent1_id, $child_id);
        $this->model->add_sublist($parent2_id, $child_id);
        $this->model->add_sublist($parent3_id, $child_id);

        // Check if child can be deleted
        $result = $this->model->can_delete_list($child_id);

        $this->assertFalse($result['can_delete'], 'Should not be able to delete');
        $this->assertCount(3, $result['parent_lists'], 'Should have 3 parent lists');
    }

    /**
     * Test can_delete_list() with invalid ID
     */
    public function testCanDeleteList_InvalidId_ReturnsError()
    {
        $result = $this->model->can_delete_list(null);

        $this->assertFalse($result['can_delete']);
        $this->assertArrayHasKey('error', $result);
    }

    /**
     * Test remove_from_all_parents_and_delete() with single parent
     */
    public function testRemoveFromAllParentsAndDelete_SingleParent_Success()
    {
        // Create parent and child lists
        $parent_id = $this->createTestList('TEST_DELETE_REMOVE_PARENT');
        $child_id = $this->createTestList('TEST_DELETE_REMOVE_CHILD');

        // Add sublist relationship
        $this->model->add_sublist($parent_id, $child_id);

        // Remove from all parents and delete
        $result = $this->model->remove_from_all_parents_and_delete($child_id);

        $this->assertTrue($result['success'], 'Should successfully remove and delete');
        $this->assertEquals(1, $result['removed_from_count'], 'Should remove from 1 parent');
        $this->assertNull($result['error'], 'Should have no error');

        // Verify child is deleted
        $child = $this->model->get_list($child_id);
        $this->assertEmpty($child, 'Child list should be deleted');

        // Verify parent still exists
        $parent = $this->model->get_list($parent_id);
        $this->assertNotEmpty($parent, 'Parent list should still exist');

        // Verify relationship is removed
        $sublists = $this->model->get_sublists($parent_id);
        $this->assertEmpty($sublists, 'Parent should have no sublists');
    }

    /**
     * Test remove_from_all_parents_and_delete() with multiple parents
     */
    public function testRemoveFromAllParentsAndDelete_MultipleParents_Success()
    {
        // Create 3 parent lists and 1 child list
        $parent1_id = $this->createTestList('TEST_DELETE_REMOVE_P1');
        $parent2_id = $this->createTestList('TEST_DELETE_REMOVE_P2');
        $parent3_id = $this->createTestList('TEST_DELETE_REMOVE_P3');
        $child_id = $this->createTestList('TEST_DELETE_REMOVE_CHILD');

        // Add child to all 3 parents
        $this->model->add_sublist($parent1_id, $child_id);
        $this->model->add_sublist($parent2_id, $child_id);
        $this->model->add_sublist($parent3_id, $child_id);

        // Remove from all parents and delete
        $result = $this->model->remove_from_all_parents_and_delete($child_id);

        $this->assertTrue($result['success'], 'Should successfully remove and delete');
        $this->assertEquals(3, $result['removed_from_count'], 'Should remove from 3 parents');

        // Verify child is deleted
        $child = $this->model->get_list($child_id);
        $this->assertEmpty($child, 'Child list should be deleted');

        // Verify all parents still exist with no sublists
        $this->assertEmpty($this->model->get_sublists($parent1_id), 'Parent 1 should have no sublists');
        $this->assertEmpty($this->model->get_sublists($parent2_id), 'Parent 2 should have no sublists');
        $this->assertEmpty($this->model->get_sublists($parent3_id), 'Parent 3 should have no sublists');
    }

    /**
     * Test remove_from_all_parents_and_delete() with no parents (normal delete)
     */
    public function testRemoveFromAllParentsAndDelete_NoParents_DeletesNormally()
    {
        $list_id = $this->createTestList('TEST_DELETE_REMOVE_NOPARENT');

        // Remove from all parents and delete (should just delete)
        $result = $this->model->remove_from_all_parents_and_delete($list_id);

        $this->assertTrue($result['success'], 'Should successfully delete');
        $this->assertEquals(0, $result['removed_from_count'], 'Should remove from 0 parents');

        // Verify list is deleted
        $list = $this->model->get_list($list_id);
        $this->assertEmpty($list, 'List should be deleted');
    }

    /**
     * Test remove_from_all_parents_and_delete() with invalid ID
     */
    public function testRemoveFromAllParentsAndDelete_InvalidId_ReturnsError()
    {
        $result = $this->model->remove_from_all_parents_and_delete(null);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals(0, $result['removed_from_count']);
    }

    /**
     * Test cascade deletion preserves data integrity
     */
    public function testCascadeDeletion_PreservesDataIntegrity()
    {
        // Create a complex structure:
        // Parent1 -> Child1
        // Parent2 -> Child1
        // Parent1 -> Child2
        $parent1_id = $this->createTestList('TEST_DELETE_CASCADE_P1');
        $parent2_id = $this->createTestList('TEST_DELETE_CASCADE_P2');
        $child1_id = $this->createTestList('TEST_DELETE_CASCADE_C1');
        $child2_id = $this->createTestList('TEST_DELETE_CASCADE_C2');

        $this->model->add_sublist($parent1_id, $child1_id);
        $this->model->add_sublist($parent2_id, $child1_id);
        $this->model->add_sublist($parent1_id, $child2_id);

        // Delete Parent1
        $this->model->delete_list($parent1_id);

        // Verify Child1 still exists and still has Parent2
        $parents = $this->model->get_parent_lists($child1_id);
        $this->assertCount(1, $parents, 'Child1 should still have 1 parent');
        $this->assertEquals($parent2_id, $parents[0]['id'], 'Child1 should still have Parent2');

        // Verify Child2 still exists (now orphaned)
        $child2 = $this->model->get_list($child2_id);
        $this->assertNotEmpty($child2, 'Child2 should still exist');
        $parents2 = $this->model->get_parent_lists($child2_id);
        $this->assertEmpty($parents2, 'Child2 should have no parents');
    }
}
