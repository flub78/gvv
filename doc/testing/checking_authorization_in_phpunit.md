# Checking User Authorization in PHPUnit Tests

This document explains the simplest and most effective methods for testing user authentication and authorization in PHPUnit tests within the GVV project.

## Overview

The GVV project uses a legacy DX_Auth system alongside a new Gvv_Authorization system. For PHPUnit testing, the most practical approach is to **simulate login by setting session data** rather than performing actual HTTP authentication.

## Simplest Method: Session Data Simulation

### Basic Pattern

```php
<?php

use PHPUnit\Framework\TestCase;

class SimpleLoginTest extends TestCase
{
    private $CI;
    
    public function setUp(): void
    {
        // Get CodeIgniter instance
        $this->CI =& get_instance();
    }
    
    /**
     * Test that a user can login (simplest method)
     */
    public function testUserCanLogin()
    {
        // 1. Get test user data from database
        $username = 'testuser';  // or 'testadmin', 'testplanchiste', etc.
        $user = $this->CI->db->where('username', $username)->get('users')->row();
        
        $this->assertNotNull($user, "Test user '{$username}' should exist");
        
        // 2. Simulate login by setting session data
        $this->CI->session->set_userdata(array(
            'DX_user_id' => $user->id,
            'DX_username' => $username,
            'DX_logged_in' => TRUE,
            'DX_role_id' => $user->role_id
        ));
        
        // 3. Verify user is "logged in"
        $this->assertEquals($user->id, $this->CI->session->userdata('DX_user_id'));
        $this->assertEquals($username, $this->CI->session->userdata('DX_username'));
        $this->assertTrue($this->CI->session->userdata('DX_logged_in'));
        
        // 4. Optional: Test that user can access basic protected functionality
        $this->assertTrue($this->CI->dx_auth->is_logged_in());
    }
}
```

### Reusable Login Simulation Function

For use across multiple tests, create a helper method:

```php
/**
 * Simulate user login by setting up session data
 * @param string $username Username to simulate login for
 */
private function simulateLogin($username)
{
    // Load user from database
    $user = $this->CI->db->where('username', $username)->get('users')->row_array();
    
    if (!$user) {
        $this->markTestSkipped("Test user {$username} not found. Run bin/create_test_users.sh first.");
    }
    
    // Set session data for DX_Auth
    $this->CI->session->set_userdata(array(
        'DX_user_id' => $user['id'],
        'DX_username' => $username,
        'DX_logged_in' => TRUE,
        'DX_role_id' => $user['role_id'],
        'DX_role_name' => $this->getRoleName($user['role_id'])
    ));
    
    return $user;
}

/**
 * Clear login session
 */
private function clearLogin()
{
    $this->CI->session->unset_userdata(array(
        'DX_user_id',
        'DX_username', 
        'DX_logged_in',
        'DX_role_id',
        'DX_role_name'
    ));
}
```

## Available Test Users

The project provides predefined test users (created by `bin/create_test_users.sh`):

| Username | Role | Role ID | Description |
|----------|------|---------|-------------|
| `testuser` | user | 1 | Basic user with limited permissions |
| `testplanchiste` | planchiste | 5 | Flight operator |
| `testca` | ca | 6 | Committee member |
| `testbureau` | bureau | 7 | Bureau/office member |
| `testtresorier` | tresorier | 8 | Treasurer |
| `testadmin` | club-admin | 10 | Full administrator |

All test users have the password `"password"`.

## Session Variables Used by DX_Auth

The authentication system relies on these session variables:

| Variable | Purpose | Required |
|----------|---------|----------|
| `DX_user_id` | User's database ID | Yes |
| `DX_username` | Username string | Yes |
| `DX_logged_in` | Boolean login status | Yes |
| `DX_role_id` | User's role ID | Recommended |
| `DX_role_name` | Role name string | Optional |

## Testing Different Authorization Scenarios

### Test Basic User Access

```php
public function testBasicUserAccess()
{
    // Simulate login as basic user
    $user = $this->simulateLogin('testuser');
    
    // Test access to allowed resources
    $this->assertTrue($this->hasAccessTo('welcome', 'index'));
    $this->assertTrue($this->hasAccessTo('membre', 'page'));
    
    // Test denial of admin resources
    $this->assertFalse($this->hasAccessTo('configuration', 'index'));
}
```

### Test Admin Access

```php
public function testAdminAccess()
{
    // Simulate login as admin
    $user = $this->simulateLogin('testadmin');
    
    // Admin should have access to everything
    $this->assertTrue($this->hasAccessTo('configuration', 'index'));
    $this->assertTrue($this->hasAccessTo('membre', 'edit'));
    $this->assertTrue($this->hasAccessTo('compta', 'rapports'));
}
```

### Test Role-Specific Access

```php
public function testPlanchisteRoleAccess()
{
    // Simulate login as flight operator
    $user = $this->simulateLogin('testplanchiste');
    
    // Should have access to flight operations
    $this->assertTrue($this->hasAccessTo('vols_planeur', 'page'));
    $this->assertTrue($this->hasAccessTo('vols_planeur', 'create'));
    
    // Should NOT have access to financial reports
    $this->assertFalse($this->hasAccessTo('compta', 'rapports'));
}
```

## Integration with Existing Patterns

### Using the AuthorizationSmokeTest Pattern

The `AuthorizationSmokeTest.php` provides a comprehensive example. You can extend or reference its methods:

```php
/**
 * Test access to specific controller/action
 */
private function testAccess($user_id, $controller, $action = 'index', $should_have_access = true, $reason = '')
{
    // Get user data
    $user = $this->CI->db->where('id', $user_id)->get('users')->row();
    
    // Simulate login
    $this->CI->session->set_userdata(array(
        'DX_user_id' => $user_id,
        'DX_username' => $user->username,
        'DX_logged_in' => TRUE,
        'DX_role_id' => $user->role_id
    ));
    
    // Check access using your authorization logic
    $has_access = $this->checkControllerAccess($controller, $action);
    
    if ($should_have_access) {
        $this->assertTrue($has_access, "User {$user->username} should have access to {$controller}/{$action}. {$reason}");
    } else {
        $this->assertFalse($has_access, "User {$user->username} should NOT have access to {$controller}/{$action}. {$reason}");
    }
}
```

## Best Practices

### 1. Test Setup and Teardown

```php
public function setUp(): void
{
    parent::setUp();
    $this->CI =& get_instance();
    
    // Ensure clean session state
    $this->clearLogin();
}

public function tearDown(): void
{
    // Clean up session after each test
    $this->clearLogin();
    parent::tearDown();
}
```

### 2. Skip Tests When Users Don't Exist

```php
public function testSpecificUserFeature()
{
    if (!$this->userExists('testuser')) {
        $this->markTestSkipped('Test users not created. Run: bin/create_test_users.sh');
    }
    
    $this->simulateLogin('testuser');
    // ... test logic
}

private function userExists($username)
{
    return $this->CI->db->where('username', $username)->get('users')->num_rows() > 0;
}
```

### 3. Test Both Legacy and New Authorization Systems

```php
/**
 * @dataProvider authorizationSystemProvider
 */
public function testUserAccessAcrossSystems($system_type)
{
    // Configure which system to test
    $this->configureAuthorizationSystem($system_type);
    
    $this->simulateLogin('testuser');
    
    // Test should pass regardless of which system is active
    $this->assertTrue($this->hasAccessTo('welcome', 'index'));
}

public function authorizationSystemProvider()
{
    return [
        'legacy' => ['legacy'],
        'new' => ['v2']
    ];
}
```

## Common Pitfalls to Avoid

1. **Don't forget to source environment**: Always run `source setenv.sh` before running tests
2. **Don't skip test user creation**: Ensure test users exist with `bin/create_test_users.sh`
3. **Clear session between tests**: Prevent test interference by clearing login state
4. **Use appropriate user roles**: Match test users to the permissions being tested
5. **Check for both systems**: Test compatibility with legacy DX_Auth and new Gvv_Authorization

## Example: Complete Test Class

```php
<?php

use PHPUnit\Framework\TestCase;

/**
 * Test user authentication and authorization
 */
class UserAuthorizationTest extends TestCase
{
    private $CI;
    
    public function setUp(): void
    {
        parent::setUp();
        $this->CI =& get_instance();
        $this->clearLogin();
    }
    
    public function tearDown(): void
    {
        $this->clearLogin();
        parent::tearDown();
    }
    
    public function testBasicUserLogin()
    {
        $user = $this->simulateLogin('testuser');
        
        $this->assertNotNull($user);
        $this->assertTrue($this->CI->dx_auth->is_logged_in());
        $this->assertEquals($user['id'], $this->CI->session->userdata('DX_user_id'));
    }
    
    public function testUserCanAccessWelcomePage()
    {
        $this->simulateLogin('testuser');
        
        // Test that user can access basic welcome functionality
        $this->assertTrue($this->hasBasicAccess());
    }
    
    public function testUserCannotAccessAdminPages()
    {
        $this->simulateLogin('testuser');
        
        // Basic users should not have admin access
        $this->assertFalse($this->hasAdminAccess());
    }
    
    public function testAdminCanAccessEverything()
    {
        $this->simulateLogin('testadmin');
        
        $this->assertTrue($this->hasBasicAccess());
        $this->assertTrue($this->hasAdminAccess());
    }
    
    // Helper methods
    private function simulateLogin($username) { /* ... */ }
    private function clearLogin() { /* ... */ }
    private function hasBasicAccess() { /* ... */ }
    private function hasAdminAccess() { /* ... */ }
}
```

This approach provides fast, reliable testing of authentication and authorization without the complexity of HTTP requests or form submissions.

## Testing Page Authorization After Login

### Simplest Method: Simulate Login + Direct Authorization Check

Once you have a user logged in via session simulation, testing page authorization is straightforward:

```php
<?php

use PHPUnit\Framework\TestCase;

class PageAuthorizationTest extends TestCase
{
    private $CI;
    
    public function setUp(): void
    {
        $this->CI =& get_instance();
    }
    
    /**
     * Test that a user can access a specific page after login
     */
    public function testUserCanAccessMemberPage()
    {
        // 1. Simulate login
        $this->simulateLogin('testuser');
        
        // 2. Test page authorization
        $this->assertPageIsAccessible('membre', 'page');
    }
    
    /**
     * Test that a user cannot access admin pages
     */
    public function testUserCannotAccessAdminPage()
    {
        // 1. Simulate login as regular user
        $this->simulateLogin('testuser');
        
        // 2. Test page is NOT accessible
        $this->assertPageIsNotAccessible('configuration', 'index');
    }
    
    // Helper methods
    private function simulateLogin($username)
    {
        $user = $this->CI->db->where('username', $username)->get('users')->row();
        $this->assertNotNull($user, "Test user {$username} should exist");
        
        $this->CI->session->set_userdata(array(
            'DX_user_id' => $user->id,
            'DX_username' => $username,
            'DX_logged_in' => TRUE,
            'DX_role_id' => $user->role_id
        ));
    }
    
    private function assertPageIsAccessible($controller, $action = 'index')
    {
        $has_access = $this->checkPageAccess($controller, $action);
        $this->assertTrue($has_access, "User should have access to {$controller}/{$action}");
    }
    
    private function assertPageIsNotAccessible($controller, $action = 'index')
    {
        $has_access = $this->checkPageAccess($controller, $action);
        $this->assertFalse($has_access, "User should NOT have access to {$controller}/{$action}");
    }
    
    /**
     * Check if current user can access a controller/action
     */
    private function checkPageAccess($controller, $action)
    {
        // Method 1: Use the authorization library directly
        if ($this->CI->load->is_loaded('gvv_authorization')) {
            return $this->CI->gvv_authorization->can_access($controller, $action);
        }
        
        // Method 2: Use DX_Auth for legacy system
        if ($this->CI->load->is_loaded('dx_auth')) {
            // Check if user is logged in first
            if (!$this->CI->dx_auth->is_logged_in()) {
                return false;
            }
            
            // For legacy system, you might need custom logic here
            return $this->checkLegacyPermissions($controller, $action);
        }
        
        return false;
    }
    
    private function checkLegacyPermissions($controller, $action)
    {
        $user_id = $this->CI->session->userdata('DX_user_id');
        $role_id = $this->CI->session->userdata('DX_role_id');
        
        // Simple role-based check (customize based on your needs)
        $basic_pages = ['welcome', 'membre', 'vols_planeur', 'vols_avion'];
        $admin_pages = ['configuration', 'administration', 'users'];
        
        if ($role_id == 10) { // admin role
            return true; // admins can access everything
        }
        
        if (in_array($controller, $admin_pages)) {
            return false; // non-admins cannot access admin pages
        }
        
        if (in_array($controller, $basic_pages)) {
            return true; // basic users can access basic pages
        }
        
        return false;
    }
}
```

### Using Existing AuthorizationSmokeTest Pattern

The GVV project already has this pattern in `AuthorizationSmokeTest.php`. You can leverage it:

```php
/**
 * Simplified version of the pattern from AuthorizationSmokeTest
 */
private function testAccess($user_id, $controller, $action, $should_have_access, $reason)
{
    // Simulate login
    $user = $this->CI->db->where('id', $user_id)->get('users')->row();
    
    $this->CI->session->set_userdata(array(
        'DX_user_id' => $user_id,
        'DX_username' => $user->username,
        'DX_logged_in' => TRUE,
        'DX_role_id' => $user->role_id
    ));
    
    // Check access (customize this based on your authorization system)
    $has_access = $this->checkUserCanAccess($controller, $action, $user->role_id);
    
    if ($should_have_access) {
        $this->assertTrue($has_access, "{$user->username} should have access to {$controller}/{$action}. {$reason}");
    } else {
        $this->assertFalse($has_access, "{$user->username} should NOT have access to {$controller}/{$action}. {$reason}");
    }
}

// Usage
public function testUserPageAccess()
{
    $user_id = $this->test_users['testuser']['id'];
    $this->testAccess($user_id, 'membre', 'page', true, 'Users should be able to view member profiles');
    $this->testAccess($user_id, 'configuration', 'index', false, 'Basic users should not access configuration');
}
```

### One-Liner Tests (Ultra Simple)

For quick verification of basic access patterns:

```php
public function testBasicPageAccess()
{
    // Test user access
    $this->simulateLoginAndAssertAccess('testuser', 'membre', 'page', true);
    $this->simulateLoginAndAssertAccess('testuser', 'configuration', 'index', false);
    
    // Test admin access  
    $this->simulateLoginAndAssertAccess('testadmin', 'membre', 'page', true);
    $this->simulateLoginAndAssertAccess('testadmin', 'configuration', 'index', true);
}

private function simulateLoginAndAssertAccess($username, $controller, $action, $should_have_access)
{
    // Login
    $user = $this->CI->db->where('username', $username)->get('users')->row();
    $this->CI->session->set_userdata([
        'DX_user_id' => $user->id,
        'DX_username' => $username, 
        'DX_logged_in' => TRUE,
        'DX_role_id' => $user->role_id
    ]);
    
    // Check access and assert
    $has_access = $this->checkPageAccess($controller, $action);
    $this->assertEquals($should_have_access, $has_access, 
        "{$username} access to {$controller}/{$action}");
        
    // Clean up
    $this->CI->session->sess_destroy();
}
```

### Common Page Authorization Test Scenarios

```php
class CommonPageAuthTests extends TestCase
{
    public function testWelcomePageAccessible()
    {
        $this->simulateLogin('testuser');
        $this->assertPageIsAccessible('welcome', 'index');
    }
    
    public function testMemberPagesForDifferentRoles()
    {
        // Basic user can view member list
        $this->simulateLogin('testuser');
        $this->assertPageIsAccessible('membre', 'page');
        $this->assertPageIsNotAccessible('membre', 'edit');
        
        $this->clearLogin();
        
        // Admin can both view and edit
        $this->simulateLogin('testadmin');
        $this->assertPageIsAccessible('membre', 'page');
        $this->assertPageIsAccessible('membre', 'edit');
    }
    
    public function testFlightOperationsAccess()
    {
        // Regular user has read access
        $this->simulateLogin('testuser');
        $this->assertPageIsAccessible('vols_planeur', 'page');
        $this->assertPageIsNotAccessible('vols_planeur', 'create');
        
        $this->clearLogin();
        
        // Planchiste can create flights
        $this->simulateLogin('testplanchiste');
        $this->assertPageIsAccessible('vols_planeur', 'page');
        $this->assertPageIsAccessible('vols_planeur', 'create');
    }
    
    public function testFinancialPagesAccess()
    {
        // Basic user can see own account
        $this->simulateLogin('testuser');
        $this->assertPageIsAccessible('compta', 'mon_compte');
        $this->assertPageIsNotAccessible('compta', 'rapports');
        
        $this->clearLogin();
        
        // Treasurer has full financial access
        $this->simulateLogin('testtresorier');
        $this->assertPageIsAccessible('compta', 'mon_compte');
        $this->assertPageIsAccessible('compta', 'rapports');
        $this->assertPageIsAccessible('compta', 'ecritures');
    }
    
    public function testConfigurationAccess()
    {
        // Only admins can access configuration
        foreach (['testuser', 'testplanchiste', 'testca'] as $username) {
            $this->simulateLogin($username);
            $this->assertPageIsNotAccessible('configuration', 'index');
            $this->clearLogin();
        }
        
        // Admin has access
        $this->simulateLogin('testadmin');
        $this->assertPageIsAccessible('configuration', 'index');
    }
}
```

### Key Benefits of This Approach

1. **No HTTP overhead**: Direct session simulation is fast
2. **Precise control**: Test exact authorization logic without UI complications
3. **Multiple scenarios**: Easy to test various user roles and permissions
4. **Follows GVV patterns**: Based on existing `AuthorizationSmokeTest.php`
5. **Comprehensive coverage**: Can test both positive and negative cases

### Testing Tips

1. **Always clear session between tests** to avoid interference
2. **Test both access granted AND denied scenarios** for complete coverage
3. **Use descriptive test names** like `testPlanchisteCanCreateFlights()`
4. **Group related tests** using test classes or data providers
5. **Leverage existing test users** rather than creating new ones

This method provides the simplest and most reliable way to test page authorization in PHPUnit without the complexity of HTTP requests or form submissions.

## Existing PHPUnit Authorization Tests in GVV

**Yes, the GVV project already has comprehensive PHPUnit tests that perform these authorization checks!** Here are the existing test files:

### 1. AuthorizationSmokeTest.php (`application/tests/integration/`)

This is the **main authorization test suite** that tests page access for different user roles. It already implements the patterns described above:

**Features:**
- **Two-phase testing**: Tests both legacy DX_Auth and new Gvv_Authorization systems
- **Complete user role coverage**: Tests all user types (testuser, testplanchiste, testca, testbureau, testtresorier, testadmin)
- **Page access validation**: Tests actual controller/action access patterns
- **Session simulation**: Uses the exact `_simulateLogin()` pattern shown above

**Example tests from this file:**
```php
// Tests user access to basic pages
$this->_testAccess($user_id, 'welcome', 'index', true, null, 'Basic users have welcome access');
$this->_testAccess($user_id, 'membre', 'page', true, null, 'Users can view member profiles');
$this->_testAccess($user_id, 'compta', 'mon_compte', true, null, 'Users can view own financial account');

// Tests admin access to everything
$this->_testAccess($user_id, 'membre', 'create', true);
$this->_testAccess($user_id, 'compta', 'ecritures', true);
$this->_testAccess($user_id, 'compta', 'rapports', true);

// Tests role-specific denials
$this->_testAccess($user_id, 'compta', 'ecritures', false, null, 'Financial entry management requires treasurer role');
```

**Test methods available:**
- `testUserRoleGrantedAccess()` / `testUserRoleDeniedAccess()`
- `testPlanchisteRoleGrantedAccess()` / `testPlanchisteRoleDeniedAccess()`
- `testCaRoleGrantedAccess()` / `testCaRoleDeniedAccess()`
- `testBureauRoleGrantedAccess()` / `testBureauRoleDeniedAccess()`
- `testTresorierRoleGrantedAccess()` / `testTresorierRoleDeniedAccess()`
- `testAdminRoleGrantedAccess()` / `testAdminRoleNoDeniedAccess()`

### 2. AuthorizationIntegrationTest.php (`application/tests/integration/`)

Tests the complete authorization workflow including page access:

**Features:**
- **Full authorization workflow**: Role grants, permission checks, data access
- **Page access testing**: Uses `can_access()` method to test controller/action access
- **Wildcard permissions**: Tests access to multiple actions via wildcard permissions
- **Data-level security**: Tests row-level access control

**Example page access tests:**
```php
// Test wildcard permissions for controller access
$has_access = $this->auth->can_access(
    $this->test_user_id,
    'vols_planeur',
    'edit',
    $this->test_section_id
);
$this->assertTrue($has_access, 'Wildcard permission should grant access to all actions');

// Test complete workflow including page access
$has_uri_access = $this->auth->can_access(
    $this->test_user_id,
    'vols_planeur',
    'page',
    $this->test_section_id
);
$this->assertTrue($has_uri_access);
```

### 3. Gvv_AuthorizationTest.php (`application/tests/unit/libraries/`)

Unit tests for the authorization library itself:

**Features:**
- **Role checking methods**: `has_role()`, `has_any_role()`, `require_roles()`, `allow_roles()`
- **Caching tests**: Permission and role caching functionality
- **Data access delegation**: `can_edit_row()` delegation to `can_access_data()`

### 4. Authorization_modelTest.php (`application/tests/unit/models/`)

Tests the underlying authorization model functions.

### 5. AuthorizationControllerTest.php (`application/tests/integration/`)

Tests the authorization management controller interface.

## How to Use the Existing Tests

### Run Authorization Tests

```bash
# Run all authorization tests
./run-tests.sh --filter Authorization

# Run specific test suite
./run-tests.sh application/tests/integration/AuthorizationSmokeTest.php

# Run with coverage
./run-coverage.sh --filter Authorization
```

### Extend Existing Tests

To add new authorization checks, you can:

**1. Add to AuthorizationSmokeTest.php:**
```php
// Add to existing role test methods
private function _testUserRoleGrantedAccessImpl()
{
    $user_id = $this->test_users['testuser']['id'];
    
    // Add your new page access test
    $this->_testAccess($user_id, 'your_controller', 'your_action', true, null, 'Reason why user should have access');
}
```

**2. Add new test methods:**
```php
public function testNewFeatureAccess()
{
    $this->_runDualPhaseTest('_testNewFeatureAccessImpl', 'New Feature Access');
}

private function _testNewFeatureAccessImpl()
{
    if (!isset($this->test_users['testuser'])) {
        $this->markTestSkipped('testuser not found');
    }
    
    $user_id = $this->test_users['testuser']['id'];
    $this->_testAccess($user_id, 'new_controller', 'new_action', true);
}
```

**3. Create standalone authorization test:**
```php
// Follow the patterns from AuthorizationSmokeTest.php
class MyFeatureAuthTest extends TestCase
{
    use AuthorizationTestTrait; // If you create a trait with common methods
    
    public function testMyFeatureAccess()
    {
        $this->simulateLogin('testuser');
        $this->assertPageIsAccessible('my_controller', 'my_action');
    }
}
```

## Key Insights

1. **The patterns in this document are already implemented** in `AuthorizationSmokeTest.php`
2. **Comprehensive coverage exists** for all user roles and major controllers
3. **Both authorization systems are tested** (legacy DX_Auth and new Gvv_Authorization)
4. **The `_testAccess()` method** in AuthorizationSmokeTest.php is exactly what this document describes
5. **Test users are already set up** and extensively used in existing tests

## Recommendation

**Instead of creating new authorization tests from scratch**, consider:

1. **Review existing tests** in `AuthorizationSmokeTest.php` to see if your use case is covered
2. **Extend existing test methods** by adding new `_testAccess()` calls
3. **Add new test methods** following the established dual-phase pattern
4. **Run existing tests** to verify your authorization changes don't break existing functionality

The existing test suite provides excellent examples and proven patterns for authorization testing in the GVV project.