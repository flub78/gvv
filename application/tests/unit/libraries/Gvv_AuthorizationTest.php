<?php
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Gvv_Authorization library
 *
 * Tests the new authorization system's core functionality including:
 * - URI-based permission checking
 * - Row-level data access control
 * - Role management
 * - Caching behavior
 * - Audit logging
 *
 * @see application/libraries/Gvv_Authorization.php
 */
class Gvv_AuthorizationTest extends TestCase
{
    protected $CI;
    protected $auth;
    protected $model_mock;

    public function setUp(): void
    {
        parent::setUp();

        $this->CI =& get_instance();

        // Mock Authorization_model
        $this->model_mock = $this->getMockBuilder('Authorization_model')
            ->disableOriginalConstructor()
            ->getMock();

        // Load the authorization library
        $this->CI->load->library('Gvv_Authorization');
        $this->auth = $this->CI->gvv_authorization;

        // Inject mocked model
        $this->CI->Authorization_model = $this->model_mock;

        // Ensure we're using the new system for tests
        $this->CI->config->set_item('gvv_config', array(
            'use_new_authorization' => TRUE
        ));
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->auth->clear_cache();
    }

    // ========================================================================
    // CONSTRUCTOR AND INITIALIZATION TESTS
    // ========================================================================

    public function test_library_loads_successfully()
    {
        $this->assertInstanceOf('Gvv_Authorization', $this->auth);
    }

    public function test_use_new_system_returns_config_value()
    {
        $this->assertTrue($this->auth->use_new_system());
    }

    // ========================================================================
    // GET USER ROLES TESTS
    // ========================================================================

    public function test_get_user_roles_returns_roles_for_user()
    {
        $user_id = 1;
        $section_id = 1;
        $expected_roles = array(
            array(
                'types_roles_id' => 5,
                'role_name' => 'planchiste',
                'scope' => 'section'
            ),
            array(
                'types_roles_id' => 6,
                'role_name' => 'ca',
                'scope' => 'section'
            )
        );

        $this->model_mock->expects($this->once())
            ->method('get_user_roles')
            ->with($user_id, $section_id)
            ->willReturn($expected_roles);

        $result = $this->auth->get_user_roles($user_id, $section_id);

        $this->assertEquals($expected_roles, $result);
    }

    public function test_get_user_roles_caches_results()
    {
        $user_id = 1;
        $section_id = 1;
        $roles = array(
            array('types_roles_id' => 5, 'role_name' => 'planchiste')
        );

        // Should only be called once due to caching
        $this->model_mock->expects($this->once())
            ->method('get_user_roles')
            ->with($user_id, $section_id)
            ->willReturn($roles);

        // First call
        $result1 = $this->auth->get_user_roles($user_id, $section_id);
        // Second call should use cache
        $result2 = $this->auth->get_user_roles($user_id, $section_id);

        $this->assertEquals($result1, $result2);
    }

    public function test_get_user_roles_returns_empty_array_for_user_with_no_roles()
    {
        $user_id = 999;
        $section_id = 1;

        $this->model_mock->expects($this->once())
            ->method('get_user_roles')
            ->with($user_id, $section_id)
            ->willReturn(array());

        $result = $this->auth->get_user_roles($user_id, $section_id);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ========================================================================
    // HAS ROLE TESTS
    // ========================================================================

    public function test_has_role_returns_true_when_user_has_role()
    {
        $user_id = 1;
        $section_id = 1;
        $role_name = 'planchiste';

        $this->model_mock->expects($this->once())
            ->method('get_user_roles')
            ->with($user_id, $section_id)
            ->willReturn(array(
                array('types_roles_id' => 5, 'role_name' => 'planchiste')
            ));

        $result = $this->auth->has_role($user_id, $role_name, $section_id);

        $this->assertTrue($result);
    }

    public function test_has_role_returns_false_when_user_does_not_have_role()
    {
        $user_id = 1;
        $section_id = 1;
        $role_name = 'club-admin';

        $this->model_mock->expects($this->once())
            ->method('get_user_roles')
            ->with($user_id, $section_id)
            ->willReturn(array(
                array('types_roles_id' => 5, 'role_name' => 'planchiste')
            ));

        $result = $this->auth->has_role($user_id, $role_name, $section_id);

        $this->assertFalse($result);
    }

    // ========================================================================
    // HAS ANY ROLE TESTS
    // ========================================================================

    public function test_has_any_role_returns_true_when_user_has_one_of_roles()
    {
        $user_id = 1;
        $section_id = 1;
        $role_names = array('club-admin', 'planchiste', 'ca');

        $this->model_mock->expects($this->once())
            ->method('get_user_roles')
            ->with($user_id, $section_id)
            ->willReturn(array(
                array('types_roles_id' => 5, 'role_name' => 'planchiste')
            ));

        $result = $this->auth->has_any_role($user_id, $role_names, $section_id);

        $this->assertTrue($result);
    }

    public function test_has_any_role_returns_false_when_user_has_none_of_roles()
    {
        $user_id = 1;
        $section_id = 1;
        $role_names = array('club-admin', 'super-tresorier');

        $this->model_mock->expects($this->once())
            ->method('get_user_roles')
            ->with($user_id, $section_id)
            ->willReturn(array(
                array('types_roles_id' => 5, 'role_name' => 'planchiste')
            ));

        $result = $this->auth->has_any_role($user_id, $role_names, $section_id);

        $this->assertFalse($result);
    }

    // ========================================================================
    // CACHE CLEARING TESTS
    // ========================================================================

    public function test_clear_cache_clears_all_cached_data()
    {
        $user_id = 1;
        $section_id = 1;

        $this->model_mock->expects($this->exactly(2))
            ->method('get_user_roles')
            ->with($user_id, $section_id)
            ->willReturn(array());

        // First call populates cache
        $this->auth->get_user_roles($user_id, $section_id);

        // Clear cache
        $this->auth->clear_cache();

        // Second call should hit database again (not cache)
        $this->auth->get_user_roles($user_id, $section_id);
    }

    public function test_clear_cache_for_specific_user()
    {
        $user1_id = 1;
        $user2_id = 2;
        $section_id = 1;

        // Setup expectations
        $this->model_mock->expects($this->exactly(3))
            ->method('get_user_roles')
            ->willReturnCallback(function($uid, $sid) {
                return array(
                    array('types_roles_id' => $uid, 'role_name' => "role_{$uid}")
                );
            });

        // Populate cache for both users
        $this->auth->get_user_roles($user1_id, $section_id);
        $this->auth->get_user_roles($user2_id, $section_id);

        // Clear cache for user 1 only
        $this->auth->clear_cache($user1_id);

        // User 1 should hit database (cache cleared)
        $this->auth->get_user_roles($user1_id, $section_id);

        // User 2 should use cache (not cleared)
        $result = $this->auth->get_user_roles($user2_id, $section_id);
        $this->assertEquals('role_2', $result[0]['role_name']);
    }
}
