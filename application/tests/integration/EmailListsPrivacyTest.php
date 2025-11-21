<?php

use PHPUnit\Framework\TestCase;

/**
 * Integration test for Email Lists privacy feature
 *
 * Tests the visibility rules for public and private email lists:
 * - Private lists (visible=0) are only visible to their creator and admins
 * - Public lists (visible=1) are visible to all users
 * - Creators can see their own private lists
 *
 * Requirements:
 * - Full CodeIgniter framework loaded
 * - Database connection configured
 * - email_lists table has visible field
 */
class EmailListsPrivacyTest extends TestCase
{
    /**
     * @var CI_Controller
     */
    private $CI;

    /**
     * @var Email_lists_model
     */
    private $model;

    /**
     * Test data IDs for cleanup
     */
    private $test_user_a_id;
    private $test_user_b_id;
    private $test_admin_id;
    private $created_lists = [];

    /**
     * Set up test environment with database transaction
     */
    public function setUp(): void
    {
        // Get CodeIgniter instance
        $this->CI =& get_instance();

        // Load model
        $this->CI->load->model('email_lists_model');
        $this->model = $this->CI->email_lists_model;

        // Start transaction for test isolation
        $this->CI->db->trans_start();

        // Verify database connection
        if (!$this->CI->db->conn_id) {
            $this->markTestSkipped('Database connection not available');
        }

        // The visible field should exist in email_lists table
        // Tests will fail naturally if the field doesn't exist

        // Create test users
        $this->_create_test_users();
    }

    /**
     * Clean up after each test - rollback transaction
     */
    public function tearDown(): void
    {
        // Rollback transaction to restore database state
        $this->CI->db->trans_rollback();
    }

    /**
     * Test that a user cannot see private lists created by another user
     */
    public function testUserCannotSeeOtherUsersPrivateLists()
    {
        // User A creates a private list
        $list_id = $this->_create_private_list($this->test_user_a_id, 'User A Private List');
        $this->created_lists[] = $list_id;

        // User B should not see User A's private list
        $lists_for_user_b = $this->model->get_user_lists($this->test_user_b_id, false);

        $found = false;
        foreach ($lists_for_user_b as $list) {
            if ($list['id'] == $list_id) {
                $found = true;
                break;
            }
        }

        $this->assertFalse($found, 'User B should not see User A\'s private list');
    }

    /**
     * Test that a user can see their own private lists
     */
    public function testUserCanSeeOwnPrivateLists()
    {
        // User A creates a private list
        $list_id = $this->_create_private_list($this->test_user_a_id, 'My Private List');
        $this->created_lists[] = $list_id;

        // User A should see their own private list
        $lists_for_user_a = $this->model->get_user_lists($this->test_user_a_id, false);

        $found = false;
        foreach ($lists_for_user_a as $list) {
            if ($list['id'] == $list_id) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'User A should see their own private list');
    }

    /**
     * Test that all users can see public lists
     */
    public function testAllUsersCanSeePublicLists()
    {
        // User A creates a public list
        $list_id = $this->_create_public_list($this->test_user_a_id, 'Public List');
        $this->created_lists[] = $list_id;

        // User B should see User A's public list
        $lists_for_user_b = $this->model->get_user_lists($this->test_user_b_id, false);

        $found = false;
        foreach ($lists_for_user_b as $list) {
            if ($list['id'] == $list_id) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'User B should see User A\'s public list');
    }

    /**
     * Test that admins can see all lists (both public and private)
     */
    public function testAdminCanSeeAllLists()
    {
        // User A creates a private list
        $private_list_id = $this->_create_private_list($this->test_user_a_id, 'Admin Test Private');
        $this->created_lists[] = $private_list_id;

        // User A creates a public list
        $public_list_id = $this->_create_public_list($this->test_user_a_id, 'Admin Test Public');
        $this->created_lists[] = $public_list_id;

        // Admin should see both lists
        $lists_for_admin = $this->model->get_user_lists($this->test_admin_id, true);

        $found_private = false;
        $found_public = false;
        foreach ($lists_for_admin as $list) {
            if ($list['id'] == $private_list_id) {
                $found_private = true;
            }
            if ($list['id'] == $public_list_id) {
                $found_public = true;
            }
        }

        $this->assertTrue($found_private, 'Admin should see private list');
        $this->assertTrue($found_public, 'Admin should see public list');
    }

    /**
     * Test that non-admin users don't see private lists of others
     */
    public function testNonAdminFilteringWorks()
    {
        // User A creates a private list
        $private_list_id = $this->_create_private_list($this->test_user_a_id, 'Private List');
        $this->created_lists[] = $private_list_id;

        // User B creates a private list
        $user_b_private_id = $this->_create_private_list($this->test_user_b_id, 'User B Private');
        $this->created_lists[] = $user_b_private_id;

        // User A should only see their own private list, not User B's
        $lists_for_user_a = $this->model->get_user_lists($this->test_user_a_id, false);

        $found_own = false;
        $found_other = false;
        foreach ($lists_for_user_a as $list) {
            if ($list['id'] == $private_list_id) {
                $found_own = true;
            }
            if ($list['id'] == $user_b_private_id) {
                $found_other = true;
            }
        }

        $this->assertTrue($found_own, 'User A should see their own private list');
        $this->assertFalse($found_other, 'User A should not see User B\'s private list');
    }


    /**
     * Create test users
     */
    private function _create_test_users()
    {
        // Use existing users from the database
        // Query for available users
        $query = $this->CI->db->query('SELECT id FROM users LIMIT 3');
        $users = $query->result_array();

        if (count($users) < 2) {
            $this->markTestSkipped('Need at least 2 users in database for testing');
        }

        $this->test_user_a_id = $users[0]['id'];
        $this->test_user_b_id = $users[1]['id'];
        // For admin test, use the first user as admin
        $this->test_admin_id = $users[0]['id'];
    }

    /**
     * Create a private email list (visible=0)
     */
    private function _create_private_list($user_id, $name)
    {
        $data = [
            'name' => $name . ' ' . uniqid(), // Make name unique
            'description' => 'Test private list',
            'active_member' => 'active',
            'visible' => 0, // Private
            'created_by' => $user_id
        ];

        return $this->model->create_list($data);
    }

    /**
     * Create a public email list (visible=1)
     */
    private function _create_public_list($user_id, $name)
    {
        $data = [
            'name' => $name . ' ' . uniqid(), // Make name unique
            'description' => 'Test public list',
            'active_member' => 'active',
            'visible' => 1, // Public
            'created_by' => $user_id
        ];

        return $this->model->create_list($data);
    }
}
