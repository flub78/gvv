# Phase 6: Dual-Mode Architecture Design

**Document Version:** 1.0
**Date:** 2025-10-21
**Status:** Planning
**Author:** Claude Code

---

## Overview

Phase 6 implements a **dual-mode architecture** allowing progressive migration from the legacy DX_Auth permission system to the new Gvv_Authorization system on a per-user basis. This enables testing and validation with pilot users before full deployment.

---

## Architecture Design

### 1. Dual-Mode Decision Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    User Requests Resource                    │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│              Gvv_Controller::_check_access()                 │
│  ┌───────────────────────────────────────────────────────┐  │
│  │  1. Get user_id from session                          │  │
│  │  2. Check migration status in user_authorization_     │  │
│  │     migration table                                   │  │
│  │  3. Route to appropriate system                       │  │
│  └───────────────────────────────────────────────────────┘  │
└──────────────────┬───────────────────────┬──────────────────┘
                   │                       │
         use_new_system = TRUE   use_new_system = FALSE
                   │                       │
                   ▼                       ▼
    ┌──────────────────────────┐   ┌─────────────────────┐
    │  Gvv_Authorization       │   │  DX_Auth (Legacy)   │
    │  - Role-based checks     │   │  - Serialized perms │
    │  - Row-level security    │   │  - Role checks      │
    │  - Audit logging         │   │                     │
    └──────────────────────────┘   └─────────────────────┘
```

### 2. Migration Status Table

Already exists from migration 042:

```sql
CREATE TABLE IF NOT EXISTS user_authorization_migration (
    user_id INT NOT NULL,
    migration_status ENUM('pending', 'in_progress', 'completed', 'failed') DEFAULT 'pending',
    use_new_system TINYINT(1) DEFAULT 0,
    migrated_at DATETIME NULL,
    migrated_by INT NULL,
    old_permissions TEXT NULL COMMENT 'Backup of old serialized permissions',
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (migrated_by) REFERENCES users(id) ON DELETE SET NULL
);
```

**Key Fields**:
- `use_new_system`: Feature flag per user (0 = legacy, 1 = new)
- `migration_status`: Track migration progress
- `old_permissions`: Backup for rollback capability

### 3. Gvv_Controller Enhancement

**Current State**: No base controller exists in `application/core/`

**Solution**: Create `application/core/Gvv_Controller.php`

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * GVV Base Controller
 *
 * Provides dual-mode authorization support for progressive migration
 * from DX_Auth to Gvv_Authorization system.
 */
class Gvv_Controller extends CI_Controller
{
    protected $user_id;
    protected $use_new_auth = FALSE;
    protected $migration_status = NULL;

    public function __construct()
    {
        parent::__construct();

        // Load authentication libraries
        $this->load->library('dx_auth');
        $this->load->library('Gvv_Authorization');
        $this->load->model('Authorization_model');

        // Initialize user authentication
        $this->_init_auth();
    }

    /**
     * Initialize authentication and determine which system to use
     */
    private function _init_auth()
    {
        // Check if user is logged in (via DX_Auth session)
        if (!$this->dx_auth->is_logged_in()) {
            return; // Not logged in - handled by individual controllers
        }

        // Get user ID from session
        $this->user_id = $this->dx_auth->get_user_id();

        // Check migration status for this user
        $migration = $this->authorization_model->get_migration_status($this->user_id);

        if ($migration && $migration['use_new_system'] == 1) {
            $this->use_new_auth = TRUE;
            $this->migration_status = $migration['migration_status'];
            log_message('debug', "User {$this->user_id} using NEW authorization system");
        } else {
            $this->use_new_auth = FALSE;
            log_message('debug', "User {$this->user_id} using LEGACY authorization system");
        }
    }

    /**
     * Check if user can access a controller/action
     *
     * @param string $controller Controller name (defaults to current)
     * @param string $action Action name (defaults to current)
     * @return bool TRUE if access granted
     */
    protected function _check_access($controller = NULL, $action = NULL)
    {
        // Default to current controller/method
        if ($controller === NULL) {
            $controller = $this->router->class;
        }
        if ($action === NULL) {
            $action = $this->router->method;
        }

        // Get current section from session
        $section_id = $this->session->userdata('section');

        if ($this->use_new_auth) {
            // Use new authorization system
            $has_access = $this->gvv_authorization->can_access(
                $this->user_id,
                $controller,
                $action,
                $section_id
            );

            // Log comparison in dual-mode for validation
            if ($this->_is_dual_mode_logging_enabled()) {
                $legacy_access = $this->_check_legacy_access($controller, $action);
                $this->_log_authorization_comparison($controller, $action, $has_access, $legacy_access);
            }

            return $has_access;
        } else {
            // Use legacy DX_Auth system
            return $this->_check_legacy_access($controller, $action);
        }
    }

    /**
     * Check access using legacy DX_Auth permissions
     *
     * @param string $controller Controller name
     * @param string $action Action name
     * @return bool TRUE if access granted
     */
    private function _check_legacy_access($controller, $action)
    {
        // Legacy permission check using DX_Auth roles and permissions
        // This preserves existing behavior during migration

        // Example legacy checks:
        // 1. Check if user has required role
        // 2. Check serialized permissions array

        // For now, return TRUE to maintain existing access patterns
        // Real implementation would call DX_Auth permission methods
        return TRUE; // Placeholder - implement based on existing DX_Auth usage
    }

    /**
     * Check if dual-mode comparison logging is enabled
     */
    private function _is_dual_mode_logging_enabled()
    {
        return $this->migration_status === 'in_progress' ||
               $this->migration_status === 'completed';
    }

    /**
     * Log authorization comparison for validation
     */
    private function _log_authorization_comparison($controller, $action, $new_result, $legacy_result)
    {
        if ($new_result !== $legacy_result) {
            log_message('warning',
                "Authorization mismatch for user {$this->user_id}: " .
                "controller={$controller}, action={$action}, " .
                "new={$new_result}, legacy={$legacy_result}"
            );

            // Store comparison in database for dashboard
            $this->db->insert('authorization_comparison_log', array(
                'user_id' => $this->user_id,
                'controller' => $controller,
                'action' => $action,
                'new_system_result' => $new_result ? 1 : 0,
                'legacy_system_result' => $legacy_result ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s')
            ));
        }
    }

    /**
     * Deny access to current request
     */
    protected function _deny_access()
    {
        if ($this->use_new_auth) {
            // Use new authorization deny view
            $this->load->view('authorization/access_denied');
        } else {
            // Use legacy DX_Auth deny view
            $this->dx_auth->deny_access();
        }
    }
}
```

### 4. Comparison Logging Table

Create table to track authorization discrepancies during migration:

```sql
CREATE TABLE IF NOT EXISTS authorization_comparison_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    controller VARCHAR(100) NOT NULL,
    action VARCHAR(100) NOT NULL,
    new_system_result TINYINT(1) NOT NULL,
    legacy_system_result TINYINT(1) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## Pilot User Migration Strategy

### Test Users (from bin/create_test_users.sh)

**Available Test Users**:
1. `testuser` - Basic member (role: user/membre)
2. `testadmin` - Club administrator (role: club-admin)
3. `testplanchiste` - Flight planning (role: planchiste)
4. `testca` - Council administrator (role: ca)
5. `testbureau` - Bureau member (role: bureau)
6. `testtresorier` - Treasurer (role: tresorier)

**Recommended Pilot Sequence**:

**Wave 1 (Week 1)**: Low-risk, basic permissions
- `testuser` (user role only)
- Validate: Login, view own data, basic navigation

**Wave 2 (Week 2)**: Medium complexity
- `testplanchiste` (planning permissions)
- Validate: Planning pages, flight data access, section-scoped data

**Wave 3 (Week 3)**: High complexity
- `testadmin` (global admin)
- Validate: All admin functions, cross-section access, user management

**Rollback Plan**:
- Set `use_new_system = 0` for user
- Permissions restore from `old_permissions` backup
- Zero downtime (instant switchback)

---

## Implementation Steps

### Step 1: Create Gvv_Controller Base Class
**File**: `application/core/Gvv_Controller.php`
**Status**: To be created
**Testing**: Unit tests for auth routing logic

### Step 2: Create Comparison Logging Table
**Migration**: `044_dual_mode_support.php`
**Status**: To be created

### Step 3: Update Existing Controllers
**Strategy**: Progressive conversion
- Identify controllers using DX_Auth checks
- Extend Gvv_Controller instead of CI_Controller
- Replace `$this->dx_auth->deny_access()` with `$this->_check_access()`

**Priority Controllers**:
1. `Members` - User data access
2. `Vols_planeur` - Flight planning (testplanchiste use case)
3. `Authorization` - Admin functions (testadmin use case)

### Step 4: Create Migration Dashboard
See `phase6_migration_dashboard_mockups.md`

---

## Migration Workflow

### Administrator Actions

**1. Enable Pilot User**
```sql
-- Set user to new system
UPDATE user_authorization_migration
SET use_new_system = 1,
    migration_status = 'in_progress',
    migrated_at = NOW(),
    migrated_by = <admin_user_id>
WHERE user_id = <pilot_user_id>;
```

**2. Monitor Comparison Logs**
```sql
-- Check for authorization mismatches
SELECT * FROM authorization_comparison_log
WHERE user_id = <pilot_user_id>
AND new_system_result != legacy_system_result
ORDER BY created_at DESC;
```

**3. Complete Migration**
```sql
-- Mark migration complete after 7 days with no issues
UPDATE user_authorization_migration
SET migration_status = 'completed'
WHERE user_id = <pilot_user_id>
AND use_new_system = 1;
```

**4. Rollback (if needed)**
```sql
-- Revert to legacy system
UPDATE user_authorization_migration
SET use_new_system = 0,
    migration_status = 'failed',
    notes = 'Rolled back due to: <reason>'
WHERE user_id = <pilot_user_id>;
```

---

## Success Metrics

### Per-User Validation (7 days each)
- ✅ Zero authorization mismatches in comparison log
- ✅ No user-reported access issues
- ✅ All audit log entries valid
- ✅ Performance acceptable (< 50ms overhead)

### Phase 6 Exit Criteria
- ✅ All 6 test users successfully migrated
- ✅ 42 days (6 users × 7 days) of monitoring complete
- ✅ Migration dashboard functional
- ✅ Rollback tested and validated
- ✅ Documentation complete

---

## Risk Mitigation

### Technical Risks

**Risk**: Authorization mismatch causes access denial
- **Mitigation**: Comparison logging + immediate rollback capability
- **Detection**: Real-time monitoring in migration dashboard

**Risk**: Performance degradation from dual checks
- **Mitigation**: Comparison logging only for pilot users
- **Detection**: Response time monitoring

**Risk**: Session/cache inconsistencies
- **Mitigation**: Clear user cache on migration status change
- **Detection**: Automated tests for cache invalidation

### Operational Risks

**Risk**: Administrator error in migration process
- **Mitigation**: Step-by-step wizard UI, confirmation dialogs
- **Detection**: Audit log of all migration actions

**Risk**: Incomplete legacy permission backup
- **Mitigation**: Automatic backup before migration, validation checks
- **Detection**: Pre-migration validation script

---

## Next Steps

1. ✅ Complete Phase 5 testing (100% integration tests passing)
2. 🔄 Review and approve this architecture document
3. 📝 Create migration dashboard mockups (separate document)
4. 💻 Implement Gvv_Controller base class
5. 🗄️ Create migration 044 for comparison logging table
6. 🧪 Convert pilot controllers to use Gvv_Controller
7. 🎨 Build migration dashboard UI
8. 🧑‍✈️ Migrate Wave 1 pilot user (testuser)

---

**Document History**:
- v1.0 (2025-10-21): Initial architecture design
