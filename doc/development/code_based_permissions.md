# Code-Based Permissions Guide (v2.0)

**Version**: 2.0
**Date**: 2025-01-24
**Phase**: 7 - Code-Based Permissions API
**Audience**: Developers migrating controllers to the new authorization system

---

## Table of Contents

1. [Introduction](#introduction)
2. [Concepts](#concepts)
3. [API Reference](#api-reference)
4. [Common Patterns](#common-patterns)
5. [Migration Guide](#migration-guide)
6. [Examples by Controller Type](#examples-by-controller-type)
7. [Testing](#testing)
8. [Troubleshooting](#troubleshooting)

---

## Introduction

### What Changed?

**Before (v1.0 - Database Permissions)**:
- Permissions stored in `role_permissions` table (~300 entries)
- Manual configuration required for each controller/action
- Difficult to maintain and error-prone

**After (v2.0 - Code-Based Permissions)**:
- Permissions declared directly in controller code
- Self-documenting and version-controlled
- Easier to understand and maintain

### Why Code-Based?

1. **Simplicity**: No database configuration needed
2. **Visibility**: Permissions visible in code reviews
3. **Maintainability**: Easy to update when requirements change
4. **Type Safety**: IDE auto-completion and error checking
5. **Version Control**: Permissions tracked in git

---

## Concepts

### Roles

GVV uses 8 predefined roles:

| Role | Scope | Description |
|------|-------|-------------|
| `club-admin` | Global | System administrator |
| `super-tresorier` | Global | Treasurer with cross-section access |
| `bureau` | Section | Board member |
| `tresorier` | Section | Section treasurer |
| `ca` | Section | Committee member (CA = Conseil d'Administration) |
| `planchiste` | Section | Flight recorder (full access to flights) |
| `auto_planchiste` | Section | Self-service flight recorder (own flights only) |
| `user` | Section | Regular member |

### Permission Types

1. **Controller-level permissions**: Required roles for entire controller
2. **Method-level permissions**: Override or add permissions for specific actions
3. **Row-level permissions**: Check ownership or section membership for data access

### Section Awareness

- **Global roles** (`club-admin`, `super-tresorier`): Access across all sections
- **Section roles**: Access limited to user's section (`$this->section_id`)

---

## API Reference

### `require_roles($roles, $section_id = NULL, $replace = TRUE)`

**Purpose**: Declare required roles for controller/action access.

**Parameters**:
- `$roles` (string|array): Role name(s) required (e.g., `'ca'`, `['planchiste', 'ca']`)
- `$section_id` (int|null): Section ID for section-scoped roles (NULL for global)
- `$replace` (bool): TRUE to replace previous requirements, FALSE to add to them

**Returns**: `bool` - TRUE if user has required role, FALSE otherwise (and denies access)

**Example**:
```php
// In controller constructor (default for all methods)
$this->require_roles(['ca', 'bureau'], $this->section_id);

// In specific method (override default)
public function public_action() {
    $this->require_roles('user', NULL, TRUE);  // All users allowed
    // ... method logic
}
```

**Behavior**:
- Automatically denies access if user lacks required role
- Logs access denial in audit log
- Redirects to login or access denied page

---

### `allow_roles($roles, $section_id = NULL)`

**Purpose**: Allow additional roles for specific methods (additive, doesn't replace base requirements).

**Parameters**:
- `$roles` (string|array): Role name(s) to allow additionally
- `$section_id` (int|null): Section ID for section-scoped roles

**Returns**: `bool` - TRUE if user has any allowed role, FALSE otherwise

**Example**:
```php
// Constructor sets base requirement
public function __construct() {
    parent::__construct();
    $this->require_roles('planchiste', $this->section_id);
}

// Method allows additional role
public function edit($id) {
    // Allow auto_planchiste in addition to planchiste
    if (!$this->allow_roles('auto_planchiste', $this->section_id)) {
        // Check if editing own flight
        $vol = $this->vols_model->get($id);
        if (!$this->can_edit_row('vols', $vol, 'edit')) {
            show_error('You can only edit your own flights');
        }
    }
    // ... edit logic
}
```

**Difference from `require_roles()`**:
- Does NOT deny access automatically
- Returns boolean for manual handling
- Used for conditional logic within methods

---

### `can_edit_row($table_name, $row_data, $access_type = 'edit', $user_id = NULL, $section_id = NULL)`

**Purpose**: Check row-level security based on ownership or section membership.

**Parameters**:
- `$table_name` (string): Database table name
- `$row_data` (array): Row data to check (must include ownership/section fields)
- `$access_type` (string): Type of access (`'view'`, `'edit'`, `'delete'`)
- `$user_id` (int|null): User ID (NULL for current user)
- `$section_id` (int|null): Section ID (NULL to use `$this->section_id`)

**Returns**: `bool` - TRUE if user can access the row, FALSE otherwise

**Example**:
```php
public function delete($id) {
    $vol = $this->vols_model->get($id);

    // Check if user can delete this flight
    if (!$this->can_edit_row('vols', $vol, 'delete')) {
        show_error('You can only delete your own flights');
    }

    // ... delete logic
}
```

**Row-Level Rules** (configured via UI in `authorization/data_access_rules`):
- `'own'`: User can only access their own data (checks `user_id` field)
- `'section'`: User can access section data (checks `section_id` field)
- `'all'`: User can access all data (no restrictions)

---

## Common Patterns

### Pattern 1: Simple Controller (Single Role)

**Use Case**: Controller accessible only by CA

```php
class Sections extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->require_roles('ca', $this->section_id);
    }

    // All methods require 'ca' role
    public function index() { /* ... */ }
    public function create() { /* ... */ }
    public function edit($id) { /* ... */ }
}
```

---

### Pattern 2: Multiple Roles (OR Logic)

**Use Case**: Controller accessible by CA or Bureau

```php
class Tarifs extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->require_roles(['ca', 'bureau'], $this->section_id);
    }

    // All methods require 'ca' OR 'bureau' role
}
```

---

### Pattern 3: Public Views + Protected Actions

**Use Case**: Everyone can view, only specific roles can create/edit

```php
class Calendar extends CI_Controller {
    public function __construct() {
        parent::__construct();
        // No role required by default
    }

    public function index() {
        // Public - anyone can view calendar
    }

    public function create() {
        $this->require_roles('ca', $this->section_id);
        // Only CA can create events
    }
}
```

---

### Pattern 4: Own vs All (Row-Level Security)

**Use Case**: Users can edit own data, planchiste can edit all

```php
class Vols_planeur extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->require_roles('planchiste', $this->section_id);
    }

    public function edit($id) {
        // Allow auto_planchiste for their own flights
        if (!$this->allow_roles('auto_planchiste', $this->section_id)) {
            // Planchiste - can edit all flights
            // ... proceed with edit
            return;
        }

        // Auto_planchiste - check ownership
        $vol = $this->vols_model->get($id);
        if (!$this->can_edit_row('vols', $vol, 'edit')) {
            show_error('You can only edit your own flights');
        }

        // ... proceed with edit
    }

    public function delete($id) {
        // Only planchiste can delete (no auto_planchiste)
        // Constructor already requires 'planchiste'
        // ... proceed with delete
    }
}
```

---

### Pattern 5: Global Roles

**Use Case**: System administration accessible by club-admin

```php
class Admin extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->require_roles('club-admin', NULL);  // NULL = global
    }

    // All methods require global 'club-admin' role
}
```

---

## Migration Guide

### Step 1: Identify Controller's Authorization Requirements

From PRD v2.0 Section 4.2, identify which roles should access your controller.

**Example**: `vols_planeur` controller
- Base access: `planchiste`
- Additional: `auto_planchiste` (own flights only)
- Delete restriction: Only `planchiste`

---

### Step 2: Add Constructor Declaration

```php
public function __construct() {
    parent::__construct();

    // Authorization: Code-based (v2.0)
    $this->require_roles('planchiste', $this->section_id);
}
```

---

### Step 3: Handle Method-Level Exceptions

```php
public function edit($id) {
    // Allow auto_planchiste for own flights
    if (!$this->allow_roles('auto_planchiste', $this->section_id)) {
        // Planchiste - unrestricted
        return $this->_edit($id);
    }

    // Auto_planchiste - check ownership
    $vol = $this->vols_model->get($id);
    if (!$this->can_edit_row('vols', $vol, 'edit')) {
        show_error('Vous ne pouvez modifier que vos propres vols');
    }

    return $this->_edit($id);
}
```

---

### Step 4: Remove Old Permission Checks

**Before (v1.0)**:
```php
if (!$this->dx_auth->is_role(['planchiste'], TRUE, TRUE)) {
    $this->dx_auth->deny_access();
}
```

**After (v2.0)**:
```php
// Handled by constructor - no manual check needed
```

---

### Step 5: Test

1. Test with user having required role → should access
2. Test with user lacking role → should deny access
3. Test row-level restrictions → should only access own data
4. Test method overrides → should allow exceptions

---

## Examples by Controller Type

### Simple Controllers (CA Only)

```php
// sections, terrains, alarmes, presences, licences, tarifs
public function __construct() {
    parent::__construct();
    $this->require_roles('ca', $this->section_id);
}
```

---

### User Controllers (All Members)

```php
// calendar (view only)
public function __construct() {
    parent::__construct();
    // No role required for viewing
}

public function create_event() {
    $this->require_roles('ca', $this->section_id);
    // ...
}
```

---

### Financial Controllers

```php
// compta
public function __construct() {
    parent::__construct();
    $this->require_roles('tresorier', $this->section_id);
}

public function mon_compte() {
    // Allow all users to view their own account
    $this->require_roles('user', $this->section_id, TRUE);  // Replace requirement
    // ...
}

public function journal_compte($id) {
    // Check if viewing own account
    if (!$this->allow_roles('user', $this->section_id)) {
        // Tresorier/bureau - can view all
        return;
    }

    // User - check ownership
    $user_id = $this->dx_auth->get_user_id();
    if ($id != $user_id) {
        show_error('Vous ne pouvez consulter que votre propre compte');
    }
}
```

---

### Member Management

```php
// membre
public function __construct() {
    parent::__construct();
    $this->require_roles('user', $this->section_id);  // Base: all members
}

public function edit($id) {
    $user_id = $this->dx_auth->get_user_id();

    // Allow editing own profile
    if ($id == $user_id) {
        return $this->_edit($id);
    }

    // Require CA for editing others
    $this->require_roles('ca', $this->section_id, TRUE);  // Replace requirement
    return $this->_edit($id);
}

public function register() {
    // Only CA can register new members
    $this->require_roles('ca', $this->section_id, TRUE);
    // ...
}
```

---

## Testing

### Unit Tests

Test code-based permissions in isolation:

```php
public function test_constructor_requires_ca_role() {
    // Mock user without CA role
    $this->dx_auth_mock->expects($this->once())
        ->method('get_user_id')
        ->willReturn(1);

    $this->model_mock->expects($this->once())
        ->method('get_user_roles')
        ->willReturn([]);  // No roles

    // Constructor should deny access
    $this->dx_auth_mock->expects($this->once())
        ->method('deny_access');

    $controller = new Sections();
}
```

---

### Integration Tests

Test with real database and roles:

```php
public function test_ca_can_access_sections_controller() {
    // Login as CA user
    $this->login_as_user_with_role('ca');

    $response = $this->get('/sections/index');

    $this->assertEquals(200, $response->status_code);
}

public function test_user_cannot_access_sections_controller() {
    // Login as regular user
    $this->login_as_user_with_role('user');

    $response = $this->get('/sections/index');

    $this->assertEquals(403, $response->status_code);  // Forbidden
}
```

---

### Manual Testing Checklist

- [ ] Constructor declarations present
- [ ] Required roles match PRD specifications
- [ ] Method-level overrides work correctly
- [ ] Row-level security enforced
- [ ] Error messages user-friendly
- [ ] Audit log entries created
- [ ] No regression (existing functionality works)

---

## Troubleshooting

### "Access Denied" for authorized users

**Problem**: User has correct role but still denied access

**Solution**: Check section ID

```php
// Wrong - using wrong section
$this->require_roles('ca', 1);  // Hardcoded section ID

// Correct - using user's section
$this->require_roles('ca', $this->section_id);
```

---

### "Call to undefined method"

**Problem**: `require_roles()` not found

**Solution**: Ensure controller extends `CI_Controller` and library is loaded

```php
public function __construct() {
    parent::__construct();  // IMPORTANT - loads libraries
    $this->require_roles(...);
}
```

---

### Row-level check always fails

**Problem**: `can_edit_row()` returns FALSE even for own data

**Solution**: Ensure data access rules configured in UI

1. Go to `authorization/data_access_rules`
2. Select role (e.g., `auto_planchiste`)
3. Add rule for table (e.g., `vols`)
4. Set scope to `'own'` with field `'user_id'`

---

### Mixed permissions (some work, some don't)

**Problem**: Some methods accessible, others not

**Solution**: Check for method-level overrides that might be interfering

```php
public function __construct() {
    parent::__construct();
    $this->require_roles('ca');  // Base: CA required
}

public function public_method() {
    // This accidentally requires BOTH 'ca' AND 'user'!
    $this->require_roles('user', NULL, FALSE);  // FALSE = add, don't replace

    // Fix: Use TRUE to replace
    $this->require_roles('user', NULL, TRUE);  // TRUE = replace
}
```

---

## Best Practices

1. **Always declare in constructor**: Set base permissions in `__construct()`
2. **Use meaningful comments**: Add `// Authorization: Code-based (v2.0)` marker
3. **Test both success and failure**: Verify access granted AND denied
4. **Keep it simple**: Prefer simple role checks over complex logic
5. **Document exceptions**: Comment why method-level overrides exist
6. **Use section-aware**: Always pass `$this->section_id` for section roles
7. **Consistent error messages**: Use French language strings from `language/french/`
8. **Audit logging**: Automatic - no manual calls needed

---

## FAQ

**Q: Can I use both database and code-based permissions?**
A: No. v2.0 removes database permissions entirely. All permissions are code-based.

**Q: How do I handle complex permission logic?**
A: Use `allow_roles()` for conditional checks, then handle logic manually.

**Q: What if I need dynamic permissions?**
A: Row-level security (`can_edit_row()`) provides dynamic checks based on data.

**Q: Can I create custom roles?**
A: Yes, via `authorization/roles` UI. Then reference by name in code.

**Q: Where is the audit log?**
A: `authorization/audit_log` - automatic logging of all access decisions.

**Q: How do I test in development?**
A: Use test users created by `bin/create_test_users.sh` with different roles.

---

## Reference Links

- **PRD v2.0**: `/doc/prds/2025_authorization_refactoring_prd.md`
- **Implementation Plan**: `/doc/plans_and_progress/2025_authorization_refactoring_plan.md`
- **API Source**: `/application/libraries/Gvv_Authorization.php` (lines 320-470)
- **Helper Source**: `/application/libraries/Gvv_Controller.php` (lines 939-1040)
- **Unit Tests**: `/application/tests/unit/libraries/Gvv_AuthorizationTest.php`

---

**Last Updated**: 2025-01-24
**Version**: 2.0 - Phase 7 Complete
