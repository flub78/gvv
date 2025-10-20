<?php

use PHPUnit\Framework\TestCase;

/**
 * Test for Authorization Controller user roles functionality
 *
 * Tests the checkbox-based role management interface
 */
class AuthorizationControllerTest extends TestCase
{
    private $CI;

    public function setUp(): void
    {
        $this->CI =& get_instance();

        // Load required libraries and models
        $this->CI->load->library('Gvv_Authorization');
        $this->CI->load->model('Authorization_model');

        // Start transaction for test isolation
        $this->CI->db->trans_start();
    }

    public function tearDown(): void
    {
        // Rollback transaction
        $this->CI->db->trans_rollback();
    }

    /**
     * Test that the user_roles view renders with proper data structure
     */
    public function testUserRolesViewDataStructure()
    {
        // Get all users with their roles (simulating controller logic)
        $this->CI->db->select('u.id, u.username, u.email, m.mnom, m.mprenom, m.club as section_id, s.nom as section_name');
        $this->CI->db->from('users u');
        $this->CI->db->join('membres m', 'u.username = m.mlogin', 'left');
        $this->CI->db->join('sections s', 'm.club = s.id', 'left');
        $this->CI->db->order_by('u.username', 'ASC');
        $this->CI->db->limit(1);
        $query = $this->CI->db->get();
        $users = $query->result_array();

        // Verify we have test data
        $this->assertNotEmpty($users, 'Should have at least one user');

        $user = $users[0];

        // Verify user structure (without loading model)
        $this->assertArrayHasKey('id', $user);
        $this->assertArrayHasKey('username', $user);
        $this->assertArrayHasKey('section_id', $user);
        $this->assertNotNull($user['id']);
        $this->assertNotEmpty($user['username']);
    }

    /**
     * Test that all roles are properly categorized as global or section
     */
    public function testRolesCategorization()
    {
        // Query roles directly from database
        $all_roles = $this->CI->db->get('types_roles')->result_array();

        $this->assertNotEmpty($all_roles, 'Should have roles in database');

        $global_roles = array_filter($all_roles, function($r) {
            return $r['scope'] === 'global';
        });

        $section_roles = array_filter($all_roles, function($r) {
            return $r['scope'] === 'section';
        });

        // Verify we have both types
        $this->assertNotEmpty($global_roles, 'Should have global roles');
        $this->assertNotEmpty($section_roles, 'Should have section roles');

        // Verify total count matches
        $this->assertEquals(
            count($all_roles),
            count($global_roles) + count($section_roles),
            'All roles should be either global or section scoped'
        );
    }

    /**
     * Test that sections are available for the UI
     */
    public function testSectionsAvailable()
    {
        $sections = $this->CI->db->get('sections')->result_array();

        $this->assertNotEmpty($sections, 'Should have sections in database');

        // Verify section structure
        foreach ($sections as $section) {
            $this->assertArrayHasKey('id', $section);
            $this->assertArrayHasKey('nom', $section);
        }
    }

    /**
     * Test the checkbox state logic
     * This verifies that user roles can be properly encoded for the view
     */
    public function testUserRolesEncoding()
    {
        // Get a test user
        $this->CI->db->select('u.id, u.username, m.club as section_id');
        $this->CI->db->from('users u');
        $this->CI->db->join('membres m', 'u.username = m.mlogin', 'left');
        $this->CI->db->limit(1);
        $query = $this->CI->db->get();
        $user = $query->row_array();

        if (!$user) {
            $this->markTestSkipped('No test user available');
        }

        // Get user's roles directly from database
        $this->CI->db->select('urps.*, tr.nom as role_name, tr.scope');
        $this->CI->db->from('user_roles_per_section urps');
        $this->CI->db->join('types_roles tr', 'urps.types_roles_id = tr.id', 'inner');
        $this->CI->db->where('urps.user_id', $user['id']);
        $this->CI->db->where('urps.revoked_at IS NULL');
        $query = $this->CI->db->get();
        $roles = $query->result_array();

        // Verify roles can be JSON encoded (for data-user-roles attribute)
        $json = json_encode($roles);
        $this->assertNotFalse($json, 'Roles should be JSON encodable');

        // Verify roles can be decoded
        $decoded = json_decode($json, true);
        $this->assertEquals($roles, $decoded, 'Encoded roles should match original');
    }
}
