# Authorization Smoke Test - Dual-Phase Testing

## Overview

The `AuthorizationSmokeTest` is an integration test that verifies the basic functionality of both the legacy and V2 authorization systems. It runs in **two phases** to ensure compatibility and verify that the migration maintains the same authorization behavior.

**Phase 1: Legacy System** - Tests access control using the original DX_Auth/permissions system  
**Phase 2: V2 System** - Tests access control using the new Gvv_Authorization system

Each test case runs twice to verify both systems produce identical results.

## Purpose

This is a **smoke test** - it verifies that the core authorization mechanism works for typical use cases, not comprehensive edge cases. The dual-phase approach ensures that:

1. **Legacy system compatibility** - The old DX_Auth system works as expected
2. **V2 system functionality** - The new Gvv_Authorization system works correctly  
3. **Migration consistency** - Both systems produce identical authorization results
4. **Role-based access control** - Users with different roles have appropriate permissions
5. **Section-based permissions** - Access is properly scoped to sections
6. **Access denial** - Unauthorized functionality is properly blocked

## Test Users

The test uses users created by `bin/create_test_users.sh`:

| Username | Role | types_roles_id | Password |
|----------|------|----------------|----------|
| testuser | user | 1 | password |
| testplanchiste | planchiste | 5 | password |
| testca | ca | 6 | password |
| testbureau | bureau | 7 | password |
| testtresorier | tresorier | 8 | password |
| testadmin | club-admin | 10 | password |

## Test Coverage

### User Role (testuser)
- ✅ **Granted**: Basic pages (welcome, membre/page, calendar, own account)
- ❌ **Denied**: Flight creation, admin functions, financial data

### Planchiste Role (testplanchiste)  
- ✅ **Granted**: Flight management (create/edit), member management in section
- ❌ **Denied**: Financial functions, admin functions, terrain creation

### CA Role (testca)
- ✅ **Granted**: Terrain management, member viewing, calendar management, reports viewing
- ❌ **Denied**: Financial editing, user administration

### Bureau Role (testbureau)
- ✅ **Granted**: Member creation/editing, broad section management, financial reports
- ❌ **Denied**: System administration, user management

### Tresorier Role (testtresorier)
- ✅ **Granted**: Financial management (entries, accounts, reports, billing)
- ❌ **Denied**: System administration, user management, member deletion

### Admin Role (testadmin)
- ✅ **Granted**: Full system access (user management, all controllers, all actions)
- ❌ **Denied**: None (admin has full access)

## Running the Tests

### Automatic (Recommended)
```bash
./tmp_rovodev_run_auth_smoke_test.sh
```

This script will:
1. Set up the PHP environment
2. Check for test users and create them if missing
3. Run the dual-phase authorization smoke test
4. Display results for both legacy and V2 systems

### Manual
```bash
# 1. Set environment
source setenv.sh

# 2. Create test users (if not already done)
./bin/create_test_users.sh

# 3. Run the test
vendor/bin/phpunit \
    --configuration phpunit_integration.xml \
    --testsuite integration \
    --filter AuthorizationSmokeTest \
    --verbose \
    application/tests/integration/AuthorizationSmokeTest.php
```

## Prerequisites

1. **Database Setup**: GVV database with migrations 042 and 043 applied
2. **Test Users**: Created by `bin/create_test_users.sh`
3. **PHP Environment**: PHP 7.4 (use `source setenv.sh`)
4. **PHPUnit**: Integration test environment configured

## Test Structure

Each role has two main test methods:

1. **`test[Role]RoleGrantedAccess()`** - Verifies user can access appropriate functionality
2. **`test[Role]RoleDeniedAccess()`** - Verifies user cannot access restricted functionality

Additional tests:
- **`testRoleHierarchy()`** - Verifies role privilege escalation
- **`testSectionBasedAccess()`** - Verifies section-scoped permissions

## Technical Details

### Test Method Pattern
```php
private function _testAccess($user_id, $controller, $action = 'index', $should_have_access = true, $section_id = null)
```

This method uses the `Gvv_Authorization::can_access()` method to check permissions.

### Database Isolation
- Each test runs in a database transaction
- All changes are rolled back after each test
- No permanent changes to test data

### Session Simulation
Tests simulate user login by setting session data for DX_Auth compatibility.

## Expected Results

When all tests pass, you should see output like:
```
AuthorizationSmokeTest

  LEGACY PHASE: User Role - Granted Access
  V2 PHASE: User Role - Granted Access
 ✓ User role granted access

  LEGACY PHASE: User Role - Denied Access  
  V2 PHASE: User Role - Denied Access
 ✓ User role denied access  

  LEGACY PHASE: Planchiste Role - Granted Access
  V2 PHASE: Planchiste Role - Granted Access
 ✓ Planchiste role granted access

  [... continues for all roles ...]

  LEGACY PHASE: Role Hierarchy Verification
  V2 PHASE: Role Hierarchy Verification
 ✓ Role hierarchy

  LEGACY PHASE: Section-Based Access Verification
  V2 PHASE: Section-Based Access Verification
 ✓ Section based access

OK (14 tests, X assertions)
```

Each test method runs twice - once for the legacy system and once for the V2 system. All assertions must pass in both phases for the test to succeed.

## Troubleshooting

### Test Users Not Found
```
Error: testuser not found. Run bin/create_test_users.sh first.
```
**Solution**: Run `./bin/create_test_users.sh` to create test users.

### Database Connection Issues
```
Error: could not connect to database
```
**Solution**: Check database configuration in `application/config/database.php`

### Migration Issues
```
Error: table 'authorization_*' doesn't exist
```
**Solution**: Run database migrations:
```bash
php run_migrations.php
```

### PHP Version Issues
```
Error: syntax error or function not found
```
**Solution**: Ensure PHP 7.4 is active:
```bash
source setenv.sh
php --version  # Should show 7.4.x
```

## Integration with CI/CD

This test can be integrated into the continuous integration pipeline:

```bash
# In your CI script
source setenv.sh
./bin/create_test_users.sh
vendor/bin/phpunit --configuration phpunit_integration.xml --filter AuthorizationSmokeTest
```

## Next Steps

After smoke tests pass:

1. **Comprehensive Testing**: Develop detailed test cases for edge cases
2. **Performance Testing**: Test authorization system performance under load  
3. **Security Testing**: Test for privilege escalation vulnerabilities
4. **Migration Testing**: Test the progressive migration from v1 to v2

## Files

- `application/tests/integration/AuthorizationSmokeTest.php` - Main test file
- `tmp_rovodev_run_auth_smoke_test.sh` - Test runner script
- `bin/create_test_users.sh` - Test user creation script
- `tmp_rovodev_authorization_smoke_test_cases.md` - Detailed test case documentation