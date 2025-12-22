# GVV Authorization System - Code Audit

**Date:** October 17, 2025
**Purpose:** Document current authorization implementation before refactoring

---

## Current Authorization Architecture

### Core Components

1. **DX_Auth Library** (`application/libraries/DX_Auth.php`)
   - Third-party authentication library
   - Handles login, logout, registration
   - URI-based permission checking
   - Role hierarchy management

2. **Gvv_Controller** (`application/libraries/Gvv_Controller.php`)
   - Base controller for all GVV controllers
   - Line 68: Calls `$this->dx_auth->check_login()` in constructor
   - Line 639: Uses `$this->dx_auth->is_role($this->modification_level, true, true)` for authorization

### Authorization Flow

```
1. User accesses controller
2. Gvv_Controller::__construct() called
3. DX_Auth::check_login() invoked (line 68)
4. DX_Auth::check_uri_permissions() called (line 497)
5. Gets controller and action from URI (lines 417-422)
6. Retrieves 'uri' permissions from session (line 426)
7. Checks if controller/action in allowed URIs (lines 434-443)
8. Denies access if not authorized (line 451)
```

### Permission Storage

**Database Tables:**
- `permissions`: Stores PHP-serialized arrays with 'uri' key
- `roles`: Role hierarchy (parent_id relationships)
- `types_roles`: Role definitions
- `user_roles_per_section`: User-role-section assignments

**Session Storage:**
- `DX_user_id`: Current user ID
- `DX_role_id`: Current role ID
- `DX_role_name`: Current role name
- `DX_permission`: Current user permissions (array)
- `DX_parent_permissions`: Parent role permissions (array)
- `DX_parent_roles_id`: Array of parent role IDs
- `DX_parent_roles_name`: Array of parent role names

### Key Methods

#### DX_Auth::check_uri_permissions()
**Location:** `application/libraries/DX_Auth.php`:411-458

**Logic:**
1. Checks if user is logged in
2. Skips check if user is admin
3. Extracts controller and action from URI
4. Gets 'uri' permissions from role + parents
5. Checks if '/', controller, or action in allowed URIs
6. Denies access if not found

#### DX_Auth::is_role()
**Location:** `application/libraries/DX_Auth.php`:189-237

**Logic:**
1. Builds check array from current role + parents
2. Converts to lowercase for case-insensitive checking
3. Checks if requested role in user's roles
4. Returns TRUE if match found

#### DX_Auth::get_permissions_value()
**Location:** `application/libraries/DX_Auth.php`:100-146

**Logic:**
1. Gets permission key from session
2. Checks current role permissions
3. Checks parent role permissions
4. Returns array of all permission values

### Authorization Patterns in Controllers

#### Pattern 1: Modification Level Check
**Example:** `Gvv_Controller::page()` line 639
```php
$this->data['has_modification_rights'] = (
    !isset($this->modification_level) ||
    $this->dx_auth->is_role($this->modification_level, true, true)
);
```

#### Pattern 2: URI Permission Check
**Automatic:** Runs in `Gvv_Controller::__construct()`
- All controllers extending Gvv_Controller get URI checking
- Admin users bypass URI checks

### Current Limitations

1. **No row-level security**: All permissions are URI-based
2. **No data access rules**: Can't restrict by owner, section, etc.
3. **Hardcoded admin check**: `is_admin()` checks if role_name == 'admin'
4. **PHP-serialized permissions**: Difficult to query/modify
5. **Session-based permissions**: Must reload session after permission changes
6. **No audit trail**: Changes not logged
7. **No section context in permissions**: Section filtering done manually
8. **Role hierarchy complexity**: Parent-child relationships hard to manage

### Global vs. Section Roles

**Current Behavior:**
- Roles stored in `types_roles` have no scope indicator
- `user_roles_per_section` assigns roles to users per section
- Some roles (club-admin, super-tresorier) span all sections implicitly
- No formal distinction between global and section-scoped roles

### Permission Inheritance

**Current Implementation:**
- DX_Auth traverses parent_id chain in `roles` table
- Permissions inherited from all parent roles
- Checked via `_get_role_data()` during login
- Stored in session as arrays

### Security Considerations

1. **URI-based only**: No protection for direct model/data access
2. **Admin bypass**: Admin users skip all permission checks
3. **Session tampering**: Permissions stored in session (encrypted by CI)
4. **No rate limiting**: On permission checks (but on login attempts)
5. **No permission expiry**: Permissions valid until logout

---

## Refactoring Goals

Based on this audit, the refactoring will address:

1. ✅ **Row-level security** - Add `data_access_rules` table
2. ✅ **Section context** - Add scope to roles (global vs section)
3. ✅ **Structured permissions** - Replace serialized data with `role_permissions` table
4. ✅ **Audit trail** - Add `authorization_audit_log` table
5. ✅ **Modern API** - Create `Gvv_Authorization` library
6. ✅ **Dual-mode** - Progressive migration with fallback to DX_Auth
7. ✅ **Better UX** - Admin UI for role/permission management

---

## Files to Modify

### Phase 1 (Database):
- Create migration 042

### Phase 2 (Data):
- Populate new tables from existing data

### Phase 3 (Library):
- `application/libraries/Gvv_Authorization.php` (NEW)
- `application/models/Authorization_model.php` (NEW)

### Phase 4 (UI):
- `application/controllers/Authorization.php` (NEW)
- `application/views/authorization/*` (NEW)

### Phase 5 (Testing):
- `application/tests/unit/libraries/AuthorizationTest.php` (NEW)
- `application/tests/integration/AuthorizationIntegrationTest.php` (NEW)

### Phase 6 (Migration):
- `application/libraries/Gvv_Controller.php` (MODIFY - add dual-mode)
- `application/config/config.php` (MODIFY - add feature flag)

---

## Migration Strategy

1. **Preserve DX_Auth**: Keep existing library intact
2. **Dual-mode operation**: Run both systems in parallel
3. **Feature flag**: `$config['use_new_authorization'] = FALSE;`
4. **Progressive migration**: Migrate users one-by-one or by group
5. **Fallback mechanism**: If new system fails, fall back to DX_Auth
6. **Monitoring**: Track permission checks via audit log

---

## Next Steps

1. ✅ Export current permissions (DONE)
2. ✅ Validate test environment (DONE)
3. ✅ Complete code audit (DONE)
4. ➡️ Create migration 042 for database schema
5. Create data migration scripts
6. Implement Gvv_Authorization library
7. Build admin UI
8. Write tests
9. Progressive deployment

---

**End of Code Audit**
