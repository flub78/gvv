<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL integration tests for email_lists_model address resolution with sublists
 *
 * Tests textual_list() and detailed_list() methods to ensure:
 * - Sublists are resolved correctly
 * - Deduplication works across all sources
 * - Metadata is properly assigned
 *
 * @package tests
 * @see application/models/email_lists_model.php
 */
class EmailListsResolutionTest extends TestCase
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
            SELECT id FROM email_lists WHERE name LIKE 'TEST_RESOLUTION_%'
        )");

        // Delete external emails
        $this->db->query("DELETE FROM email_list_external WHERE email_list_id IN (
            SELECT id FROM email_lists WHERE name LIKE 'TEST_RESOLUTION_%'
        )");

        // Then delete lists
        $this->db->query("DELETE FROM email_lists WHERE name LIKE 'TEST_RESOLUTION_%'");
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
     * Helper method to add external emails to a list
     */
    protected function addExternalEmails($list_id, $emails)
    {
        foreach ($emails as $email) {
            $this->db->insert('email_list_external', [
                'email_list_id' => $list_id,
                'external_email' => $email,
                'external_name' => ''
            ]);
        }
    }

    /**
     * Test textual_list() with 1 sublist
     */
    public function testTextualList_WithOneSublist_ReturnsAllEmails()
    {
        // Create parent list
        $parent_id = $this->createTestList('TEST_RESOLUTION_PARENT_1');

        // Create child list with emails
        $child_id = $this->createTestList('TEST_RESOLUTION_CHILD_1');
        $this->addExternalEmails($child_id, ['child1@example.com', 'child2@example.com']);

        // Add sublist relationship
        $this->model->add_sublist($parent_id, $child_id);

        // Resolve emails
        $emails = $this->model->textual_list($parent_id);

        // Should contain emails from child list
        $this->assertContains('child1@example.com', $emails, 'Should contain child1@example.com');
        $this->assertContains('child2@example.com', $emails, 'Should contain child2@example.com');
        $this->assertCount(2, $emails, 'Should have 2 emails total');
    }

    /**
     * Test textual_list() with 3 sublists
     */
    public function testTextualList_WithThreeSublists_ReturnsAllEmails()
    {
        // Create parent list
        $parent_id = $this->createTestList('TEST_RESOLUTION_PARENT_3');

        // Create 3 child lists with different emails
        $child1_id = $this->createTestList('TEST_RESOLUTION_CHILD_3A');
        $this->addExternalEmails($child1_id, ['child1@example.com']);

        $child2_id = $this->createTestList('TEST_RESOLUTION_CHILD_3B');
        $this->addExternalEmails($child2_id, ['child2@example.com']);

        $child3_id = $this->createTestList('TEST_RESOLUTION_CHILD_3C');
        $this->addExternalEmails($child3_id, ['child3@example.com']);

        // Add sublist relationships
        $this->model->add_sublist($parent_id, $child1_id);
        $this->model->add_sublist($parent_id, $child2_id);
        $this->model->add_sublist($parent_id, $child3_id);

        // Resolve emails
        $emails = $this->model->textual_list($parent_id);

        // Should contain emails from all child lists
        $this->assertContains('child1@example.com', $emails);
        $this->assertContains('child2@example.com', $emails);
        $this->assertContains('child3@example.com', $emails);
        $this->assertCount(3, $emails, 'Should have 3 emails total');
    }

    /**
     * Test textual_list() with sublists + external emails
     */
    public function testTextualList_WithSublistsAndExternal_MergesAllSources()
    {
        // Create parent list with external emails
        $parent_id = $this->createTestList('TEST_RESOLUTION_PARENT_MIX');
        $this->addExternalEmails($parent_id, ['parent@example.com']);

        // Create child list with emails
        $child_id = $this->createTestList('TEST_RESOLUTION_CHILD_MIX');
        $this->addExternalEmails($child_id, ['child@example.com']);

        // Add sublist relationship
        $this->model->add_sublist($parent_id, $child_id);

        // Resolve emails
        $emails = $this->model->textual_list($parent_id);

        // Should contain emails from both sources
        $this->assertContains('parent@example.com', $emails, 'Should contain parent email');
        $this->assertContains('child@example.com', $emails, 'Should contain child email');
        $this->assertCount(2, $emails, 'Should have 2 emails total');
    }

    /**
     * Test deduplication between sources
     */
    public function testTextualList_WithDuplicateEmailsAcrossSources_Deduplicates()
    {
        // Create parent list with duplicate email
        $parent_id = $this->createTestList('TEST_RESOLUTION_PARENT_DUP');
        $this->addExternalEmails($parent_id, ['duplicate@example.com', 'unique@example.com']);

        // Create child list with same duplicate email
        $child_id = $this->createTestList('TEST_RESOLUTION_CHILD_DUP');
        $this->addExternalEmails($child_id, ['duplicate@example.com', 'child@example.com']);

        // Add sublist relationship
        $this->model->add_sublist($parent_id, $child_id);

        // Resolve emails
        $emails = $this->model->textual_list($parent_id);

        // Should deduplicate
        $this->assertCount(3, $emails, 'Should have 3 unique emails');
        $this->assertContains('duplicate@example.com', $emails);
        $this->assertContains('unique@example.com', $emails);
        $this->assertContains('child@example.com', $emails);

        // Count occurrences of duplicate email (should be 1)
        $duplicate_count = 0;
        foreach ($emails as $email) {
            if ($email === 'duplicate@example.com') {
                $duplicate_count++;
            }
        }
        $this->assertEquals(1, $duplicate_count, 'Duplicate email should appear only once');
    }

    /**
     * Test detailed_list() with sublists
     */
    public function testDetailedList_WithSublists_IncludesMetadata()
    {
        // Create parent list
        $parent_id = $this->createTestList('TEST_RESOLUTION_PARENT_DETAIL');

        // Create child list with name
        $child_id = $this->createTestList('TEST_RESOLUTION_CHILD_DETAIL');
        $this->addExternalEmails($child_id, ['child@example.com']);

        // Add sublist relationship
        $this->model->add_sublist($parent_id, $child_id);

        // Resolve emails with metadata
        $emails = $this->model->detailed_list($parent_id);

        // Should have 1 email
        $this->assertCount(1, $emails, 'Should have 1 email');

        // Check metadata
        $email_item = $emails[0];
        $this->assertEquals('child@example.com', $email_item['email'], 'Email should match');
        $this->assertStringContainsString('sublist:', $email_item['source'], 'Source should indicate sublist');
        $this->assertStringContainsString('TEST_RESOLUTION_CHILD_DETAIL', $email_item['source'], 'Source should include list name');
    }

    /**
     * Test count_members() with sublists
     */
    public function testCountMembers_WithSublists_CountsCorrectly()
    {
        // Create parent list
        $parent_id = $this->createTestList('TEST_RESOLUTION_PARENT_COUNT');

        // Create child list with 3 emails
        $child_id = $this->createTestList('TEST_RESOLUTION_CHILD_COUNT');
        $this->addExternalEmails($child_id, [
            'count1@example.com',
            'count2@example.com',
            'count3@example.com'
        ]);

        // Add sublist relationship
        $this->model->add_sublist($parent_id, $child_id);

        // Count members
        $count = $this->model->count_members($parent_id);

        $this->assertEquals(3, $count, 'Should count 3 members from sublist');
    }

    /**
     * Test deduplication: raw count vs deduplicated count
     */
    public function testTextualList_DeduplicationAcrossSublists_ReducesCount()
    {
        // Create parent list
        $parent_id = $this->createTestList('TEST_RESOLUTION_PARENT_DEDUP');

        // Create 2 child lists with overlapping emails
        $child1_id = $this->createTestList('TEST_RESOLUTION_CHILD_DEDUP1');
        $this->addExternalEmails($child1_id, ['overlap@example.com', 'unique1@example.com']);

        $child2_id = $this->createTestList('TEST_RESOLUTION_CHILD_DEDUP2');
        $this->addExternalEmails($child2_id, ['overlap@example.com', 'unique2@example.com']);

        // Add sublist relationships
        $this->model->add_sublist($parent_id, $child1_id);
        $this->model->add_sublist($parent_id, $child2_id);

        // Resolve emails
        $emails = $this->model->textual_list($parent_id);

        // Raw count would be 4 (2 + 2), but deduplicated should be 3
        $this->assertCount(3, $emails, 'Should deduplicate to 3 unique emails');
        $this->assertContains('overlap@example.com', $emails);
        $this->assertContains('unique1@example.com', $emails);
        $this->assertContains('unique2@example.com', $emails);
    }
}
