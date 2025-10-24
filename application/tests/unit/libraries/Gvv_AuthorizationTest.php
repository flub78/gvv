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

        // Load the real Authorization_model class file BEFORE mocking
        // This prevents "Cannot redeclare class" errors
        if (!class_exists('Authorization_model')) {
            require_once APPPATH . 'models/authorization_model.php';
        }

        // Mock Authorization_model
        $this->model_mock = $this->getMockBuilder('Authorization_model')
            ->disableOriginalConstructor()
            ->getMock();

        // Load the authorization library
        $this->CI->load->library('Gvv_Authorization');
        $this->auth = $this->CI->gvv_authorization;

        // Inject mocked model (lowercase property name)
        $this->CI->authorization_model = $this->model_mock;

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

    // ========================================================================
    // CODE-BASED PERMISSIONS API TESTS (v2.0 - Phase 7)
    // ========================================================================

    public function test_require_roles_returns_true_when_user_has_required_role()
    {
        $user_id = 1;
        $section_id = 1;
        $required_roles = array('planchiste');

        // Mock DX_Auth to return user ID
        $dx_auth_mock = $this->getMockBuilder('stdClass')
            ->addMethods(['get_user_id', 'deny_access'])
            ->getMock();
        $dx_auth_mock->expects($this->once())
            ->method('get_user_id')
            ->willReturn($user_id);
        $this->CI->dx_auth = $dx_auth_mock;

        // Mock model to return roles
        $this->model_mock->expects($this->once())
            ->method('get_user_roles')
            ->with($user_id, $section_id)
            ->willReturn(array(
                array('types_roles_id' => 5, 'role_name' => 'planchiste')
            ));

        $result = $this->auth->require_roles($required_roles, $section_id);

        $this->assertTrue($result);
    }

    public function test_require_roles_denies_access_when_user_lacks_required_role()
    {
        $user_id = 1;
        $section_id = 1;
        $required_roles = array('club-admin');

        // Mock DX_Auth to return user ID and expect deny_access call
        $dx_auth_mock = $this->getMockBuilder('stdClass')
            ->addMethods(['get_user_id', 'deny_access'])
            ->getMock();
        $dx_auth_mock->expects($this->once())
            ->method('get_user_id')
            ->willReturn($user_id);
        $dx_auth_mock->expects($this->once())
            ->method('deny_access');
        $this->CI->dx_auth = $dx_auth_mock;

        // Mock model to return roles without the required one
        $this->model_mock->expects($this->once())
            ->method('get_user_roles')
            ->with($user_id, $section_id)
            ->willReturn(array(
                array('types_roles_id' => 5, 'role_name' => 'planchiste')
            ));

        $result = $this->auth->require_roles($required_roles, $section_id);

        $this->assertFalse($result);
    }

    public function test_require_roles_accepts_string_or_array()
    {
        $user_id = 1;
        $section_id = 1;

        // Mock DX_Auth
        $dx_auth_mock = $this->getMockBuilder('stdClass')
            ->addMethods(['get_user_id', 'deny_access'])
            ->getMock();
        $dx_auth_mock->expects($this->exactly(2))
            ->method('get_user_id')
            ->willReturn($user_id);
        $this->CI->dx_auth = $dx_auth_mock;

        // Mock model
        $this->model_mock->expects($this->exactly(2))
            ->method('get_user_roles')
            ->with($user_id, $section_id)
            ->willReturn(array(
                array('types_roles_id' => 5, 'role_name' => 'planchiste')
            ));

        // Test with string
        $result1 = $this->auth->require_roles('planchiste', $section_id);
        $this->assertTrue($result1);

        // Clear cache for second call
        $this->auth->clear_cache();

        // Test with array
        $result2 = $this->auth->require_roles(array('planchiste'), $section_id);
        $this->assertTrue($result2);
    }

    public function test_require_roles_accepts_multiple_roles()
    {
        $user_id = 1;
        $section_id = 1;
        $required_roles = array('club-admin', 'planchiste', 'ca');

        // Mock DX_Auth
        $dx_auth_mock = $this->getMockBuilder('stdClass')
            ->addMethods(['get_user_id', 'deny_access'])
            ->getMock();
        $dx_auth_mock->expects($this->once())
            ->method('get_user_id')
            ->willReturn($user_id);
        $this->CI->dx_auth = $dx_auth_mock;

        // User has 'planchiste' which is in the required roles
        $this->model_mock->expects($this->once())
            ->method('get_user_roles')
            ->with($user_id, $section_id)
            ->willReturn(array(
                array('types_roles_id' => 5, 'role_name' => 'planchiste')
            ));

        $result = $this->auth->require_roles($required_roles, $section_id);

        $this->assertTrue($result);
    }

    public function test_allow_roles_returns_true_when_user_has_allowed_role()
    {
        $user_id = 1;
        $section_id = 1;
        $allowed_roles = array('auto_planchiste');

        // Mock DX_Auth
        $dx_auth_mock = $this->getMockBuilder('stdClass')
            ->addMethods(['get_user_id'])
            ->getMock();
        $dx_auth_mock->expects($this->once())
            ->method('get_user_id')
            ->willReturn($user_id);
        $this->CI->dx_auth = $dx_auth_mock;

        // Mock model
        $this->model_mock->expects($this->once())
            ->method('get_user_roles')
            ->with($user_id, $section_id)
            ->willReturn(array(
                array('types_roles_id' => 7, 'role_name' => 'auto_planchiste')
            ));

        $result = $this->auth->allow_roles($allowed_roles, $section_id);

        $this->assertTrue($result);
    }

    public function test_allow_roles_returns_false_when_user_lacks_allowed_role()
    {
        $user_id = 1;
        $section_id = 1;
        $allowed_roles = array('auto_planchiste');

        // Mock DX_Auth
        $dx_auth_mock = $this->getMockBuilder('stdClass')
            ->addMethods(['get_user_id'])
            ->getMock();
        $dx_auth_mock->expects($this->once())
            ->method('get_user_id')
            ->willReturn($user_id);
        $this->CI->dx_auth = $dx_auth_mock;

        // User has different role
        $this->model_mock->expects($this->once())
            ->method('get_user_roles')
            ->with($user_id, $section_id)
            ->willReturn(array(
                array('types_roles_id' => 8, 'role_name' => 'user')
            ));

        $result = $this->auth->allow_roles($allowed_roles, $section_id);

        $this->assertFalse($result);
    }

    public function test_can_edit_row_delegates_to_can_access_data()
    {
        $user_id = 1;
        $table_name = 'vols';
        $row_data = array('user_id' => 1, 'section_id' => 1);
        $section_id = 1;
        $access_type = 'edit';

        // Mock DX_Auth
        $dx_auth_mock = $this->getMockBuilder('stdClass')
            ->addMethods(['get_user_id'])
            ->getMock();
        $dx_auth_mock->expects($this->once())
            ->method('get_user_id')
            ->willReturn($user_id);
        $this->CI->dx_auth = $dx_auth_mock;

        // Mock model for get_user_roles (called by can_access_data)
        $this->model_mock->expects($this->once())
            ->method('get_user_roles')
            ->with($user_id, $section_id)
            ->willReturn(array(
                array('types_roles_id' => 7, 'role_name' => 'auto_planchiste')
            ));

        // Mock model for get_data_access_rules
        $this->model_mock->expects($this->once())
            ->method('get_data_access_rules')
            ->with(7, $table_name)
            ->willReturn(array(
                array(
                    'access_scope' => 'own',
                    'field_name' => 'user_id',
                    'section_field' => NULL
                )
            ));

        $result = $this->auth->can_edit_row(NULL, $table_name, $row_data, $section_id, $access_type);

        $this->assertTrue($result);
    }

    public function test_can_edit_row_uses_current_user_when_null()
    {
        $current_user_id = 1;
        $table_name = 'vols';
        $row_data = array('user_id' => 1);
        $section_id = 1;

        // Mock DX_Auth to return current user
        $dx_auth_mock = $this->getMockBuilder('stdClass')
            ->addMethods(['get_user_id'])
            ->getMock();
        $dx_auth_mock->expects($this->once())
            ->method('get_user_id')
            ->willReturn($current_user_id);
        $this->CI->dx_auth = $dx_auth_mock;

        // Mock model
        $this->model_mock->expects($this->once())
            ->method('get_user_roles')
            ->willReturn(array());

        $result = $this->auth->can_edit_row(NULL, $table_name, $row_data, $section_id);

        $this->assertFalse($result); // No roles = no access
    }
}
