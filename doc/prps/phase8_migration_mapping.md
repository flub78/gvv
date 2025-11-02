# Phase 8: Simple Controllers Migration Mapping

**Date**: 2025-10-25
**Status**: Completed
**Revision**: 2 (Fixed for dual-mode support)

## Overview

This document maps the authorization changes made during Phase 8 of the authorization refactoring plan. Seven simple controllers were migrated from legacy authorization mechanisms to the new code-based authorization system (v2.0) with **dual-mode support** for progressive migration.

## Migration Summary

All controllers were migrated to use the new `require_roles()` method **conditionally** - only for users who have been migrated to the new authorization system. Non-migrated users continue to use the legacy authorization mechanisms, ensuring a smooth progressive migration with no service interruption.

### Changes Applied

For each controller:
1. Added or updated constructor to include **conditional** `require_roles()` call
2. Added comment `// Authorization: Code-based (v2.0) - only for migrated users` in constructor
3. **Preserved** legacy authorization mechanisms for non-migrated users:
   - For `Gvv_Controller` subclasses: Check `$this->use_new_auth` flag before calling `require_roles()`
   - For `CI_Controller` subclasses: Manually check migration status via `authorization_model->get_migration_status()`
   - Kept `$modification_level` property where it existed originally
   - Restored method-level checks where they were removed

## Controller-by-Controller Details

### 1. Sections Controller

**File**: `application/controllers/sections.php`
**Base Class**: `Gvv_Controller`

**Old Authorization**:
- Method-level check in `export()`: `if (!$this->dx_auth->is_role('ca'))`

**New Authorization** (Dual-mode):
- **Constructor**: `if ($this->use_new_auth) { $this->require_roles(['ca']); }`
- **export() method**: Restored legacy check for non-migrated users: `if (!$this->use_new_auth && !$this->dx_auth->is_role('ca'))`

**Required Role**: `ca` (Conseil d'Administration)

---

### 2. Terrains Controller

**File**: `application/controllers/terrains.php`
**Base Class**: `Gvv_Controller`

**Old Authorization**:
- Property: `protected $modification_level = 'ca'` (legacy mechanism)

**New Authorization** (Dual-mode):
- **Constructor**: `if ($this->use_new_auth) { $this->require_roles(['ca']); }`
- **Preserved**: `protected $modification_level = 'ca'` for non-migrated users

**Required Role**: `ca`

---

### 3. Alarmes Controller

**File**: `application/controllers/alarmes.php`
**Base Class**: `Gvv_Controller`

**Old Authorization**:
- Property: `protected $modification_level = 'ca'` (legacy mechanism)

**New Authorization** (Dual-mode):
- **Constructor**: `if ($this->use_new_auth) { $this->require_roles(['ca']); }`
- **Preserved**: `protected $modification_level = 'ca'` for non-migrated users

**Required Role**: `ca`

**Note**: Controller manages pilot experience conditions and warnings (licence, medical, brevet, recent experience)

---

### 4. Presences Controller

**File**: `application/controllers/presences.php`
**Base Class**: `CI_Controller` (NOT `Gvv_Controller`)

**Old Authorization**:
- Method call in constructor: `$this->dx_auth->check_uri_permissions()` (legacy mechanism)
- Additional method-level checks in `modification_allowed()` and `creation_allowed()`

**New Authorization** (Dual-mode):
- **Constructor**: Manual migration status check (since not extending `Gvv_Controller`):
  ```php
  $migration = $this->authorization_model->get_migration_status($user_id);
  if ($migration && $migration['use_new_system'] == 1) {
      $this->dx_auth->require_roles(['ca']);
  } else {
      $this->dx_auth->check_uri_permissions(); // Legacy
  }
  ```
- **Preserved**: Method-level authorization logic in `modification_allowed()` and `creation_allowed()`

**Required Role**: `ca`

**Note**: This controller manages presence/calendar events. Extends `CI_Controller` so requires manual migration status checking.

---

### 5. Licences Controller

**File**: `application/controllers/licences.php`
**Base Class**: `Gvv_Controller`

**Old Authorization**:
- Property: `protected $modification_level = 'ca'` (legacy mechanism)

**New Authorization** (Dual-mode):
- **Constructor**: `if ($this->use_new_auth) { $this->require_roles(['ca']); }`
- **Preserved**: `protected $modification_level = 'ca'` for non-migrated users

**Required Role**: `ca`

---

### 6. Tarifs Controller

**File**: `application/controllers/tarifs.php`
**Base Class**: `Gvv_Controller`

**Old Authorization**:
- Inherited from parent controller (no explicit authorization)

**New Authorization** (Dual-mode):
- **Constructor**: `if ($this->use_new_auth) { $this->require_roles(['ca']); }`
- **No legacy property** (controller didn't have explicit authorization before)

**Required Role**: `ca`

---

### 7. Calendar Controller

**File**: `application/controllers/calendar.php`
**Base Class**: `CI_Controller` (NOT `Gvv_Controller`)

**Old Authorization**:
- Constructor check: Login required only (no role-based authorization)

**New Authorization** (Dual-mode):
- **Constructor**: Manual migration status check (since not extending `Gvv_Controller`):
  ```php
  $migration = $this->authorization_model->get_migration_status($user_id);
  if ($migration && $migration['use_new_system'] == 1) {
      $this->dx_auth->require_roles(['user']);
  }
  // else: Legacy system - login check only
  ```

**Required Role**: `user` (any logged-in user)

**Note**: This is the only controller in Phase 8 that allows access to regular users (not just ca). Extends `CI_Controller` so requires manual migration status checking.

---

## Role Hierarchy

The GVV authorization system uses the following role hierarchy (least to most privileged):

1. `user` - Regular logged-in user
2. `ca` - Conseil d'Administration (board member)
3. `admin` - System administrator

## Dual-Mode Implementation Pattern

### For Gvv_Controller Subclasses

Controllers extending `Gvv_Controller` use the `$this->use_new_auth` flag:

```php
function __construct() {
    parent::__construct();

    // Authorization: Code-based (v2.0) - only for migrated users
    if ($this->use_new_auth) {
        $this->require_roles(['ca']);
    }
    // else: Legacy system uses $modification_level property
}
```

### For CI_Controller Subclasses

Controllers extending `CI_Controller` must manually check migration status:

```php
function __construct() {
    parent::__construct();

    // Check login
    if (!$this->dx_auth->is_logged_in()) {
        redirect("auth/login");
    }

    // Authorization: Code-based (v2.0) - only for migrated users
    $this->load->model('Authorization_model');
    $user_id = $this->dx_auth->get_user_id();
    $migration = $this->authorization_model->get_migration_status($user_id);

    if ($migration && $migration['use_new_system'] == 1) {
        $this->dx_auth->require_roles(['ca']); // New system
    } else {
        $this->dx_auth->check_uri_permissions(); // Legacy system
    }
}
```

## Testing Checklist

For each controller, verify:

- [x] Constructor syntax is valid (PHP linting passed)
- [x] All test suites pass (553 tests)
- [ ] **Manual Testing Required:**
  - [ ] Migrated users: Access is controlled by v2.0 roles
  - [ ] Non-migrated users: Access is controlled by legacy system
  - [ ] Access is denied for users without required role
  - [ ] Access is granted for users with required role
  - [ ] Access is granted for users with higher roles (admin can access ca-only controllers)
  - [ ] All controller methods function correctly
  - [ ] Error messages are appropriate when access is denied

## Files Modified

All changes implement **dual-mode authorization** supporting both migrated and non-migrated users:

1. `application/controllers/sections.php` - Conditional authorization in constructor, legacy check restored in export()
2. `application/controllers/terrains.php` - Conditional authorization in constructor, $modification_level preserved
3. `application/controllers/alarmes.php` - Conditional authorization in constructor, $modification_level preserved
4. `application/controllers/presences.php` - Manual migration check (CI_Controller), legacy check_uri_permissions() preserved
5. `application/controllers/licences.php` - Conditional authorization in constructor, $modification_level preserved
6. `application/controllers/tarifs.php` - Conditional authorization in constructor
7. `application/controllers/calendar.php` - Manual migration check (CI_Controller), legacy login-only preserved

## Related Documentation

- Main plan: `doc/plans_and_progress/2025_authorization_refactoring_plan.md`
- Authorization library: `application/libraries/DX_Auth.php`
- Base controller: `application/libraries/Gvv_Controller.php`

## Next Steps (Phase 9)

After Phase 8 completion and testing:
- Phase 9: Migrate medium complexity controllers (vols_planeur, vols_avion, avion, planeur, ecritures)
- Continue systematic migration of remaining controllers
- Update documentation with authorization patterns
