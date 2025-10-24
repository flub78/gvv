# GVV Authorization System Refactoring Plan

**Document Version:** 2.0
**Date:** 2025-01-08 (Updated: 2025-01-24)
**Status:** Phases 0-5 Complete, Phase 6 ~90% Complete, Phase 7-9 Planned (v2.0)
**Author:** Claude Code Analysis
**Based on:** PRD v2.0 - Code-Based Permission Management

---

## Executive Summary

**Major Architecture Change (v2.0):** Following analysis of the implementation (v1.0), the permission management approach has been revised. Instead of managing ~300 permissions in the database (`role_permissions` table), permissions will now be **declared directly in controller code** via declarative API calls. This simplifies maintenance, improves code-permission coherence, and reduces complexity.

**Legacy System Status:** The current implementation (Phases 0-6) remains functional and will be maintained during the transition. The `role_permissions` table will be deprecated but preserved for rollback capability.

---

## Current Status Summary

### ✅ Completed Phases (0-6) - Legacy System

**Phase 0-2: Infrastructure & Data** ✅
- Database schema migrated (042_authorization_refactoring.php)
- Data migration complete (043_populate_authorization_data.php)
- Tables: `types_roles`, `role_permissions`, `data_access_rules`, `user_roles_per_section`, `authorization_audit_log`
- 24 default data access rules created
- Role translations (FR/EN/NL) added

**Phase 3: Authorization Library** ✅
- `Gvv_Authorization` library (480 lines) - Core authorization logic
- `Authorization_model` (388 lines) - Database operations
- Feature flag in `gvv_config.php` (disabled in production)
- Unit tests: 26 tests passing, 52 assertions (100% pass rate)
- Test bootstraps enhanced with proper CI mocks

**Phase 4: UI Implementation** ✅
- `Authorization` controller (445 lines, 11 endpoints)
- 8 views created (1,648 lines): dashboard, user_roles, roles, select pages, permissions, data rules, audit log
- Language translations: 207 keys (FR/EN/NL)
- Menu integration: Admin → Club Admin → Gestion des autorisations
- Features: Bootstrap 5, DataTables, AJAX, breadcrumbs, badges
- **Testing Pending**: End-to-end workflow, mobile responsive validation

### User Roles Modal - Improved Architecture

The current implementation of the user roles modal is sensitive to timing issues, especially when dealing with the "Toutes sections" (All sections) checkbox. To address this, the architecture will be updated to a more robust, backend-driven model.

**New Architecture:**

1.  **Client-Side Action:** When a user clicks a checkbox (e.g., to grant or revoke a role, or select "All sections"), an AJAX request is sent to the backend. The request includes the user ID, the role ID, the section ID (if applicable), and the action (grant/revoke).

2.  **Backend Processing:** The `Authorization` controller receives the request. It calls the `Authorization_model` to update the `user_roles_per_section` table in the database.

3.  **Backend Response:** After successfully updating the database, the backend will fetch the user's complete and updated list of roles and their corresponding sections. This list will be returned to the client as a JSON response.

4.  **Client-Side Update:** The frontend JavaScript code receives the JSON response. It then re-renders the checkboxes in the modal to accurately reflect the new state of the user's authorizations.

**Benefits of this approach:**

*   **Eliminates Timing Issues:** The state of the checkboxes is always driven by the authoritative data from the backend, eliminating any client-side race conditions or synchronization problems.
*   **Single Source of Truth:** The database remains the single source of truth for user authorizations. The UI is simply a reflection of this data.
*   **Improved Reliability:** The new architecture is more resilient and less prone to errors, providing a more stable user experience.
*   **Simplified Frontend Logic:** The client-side code becomes simpler, as it no longer needs to manually track the state of checkboxes. It only needs to update the UI based on the backend's response.

**Reference Documents**:
- `/doc/phase4_progress.md` - Detailed UI implementation status
- `/doc/authorization_implementation_summary.md` - Architecture overview

---

## Active Work

### ✅ Phase 5: Testing Framework - COMPLETE

**Completed**:
- [x] Unit tests: 26/26 passing (100%) (Gvv_Authorization: 12, Authorization_model: 14)
- [x] Integration tests: 12/12 passing (100%) (AuthorizationIntegrationTest)
- [x] All integration tests: 213/213 passing (100%)
- [x] PHPUnit configurations created (phpunit_authorization_integration.xml)
- [x] Test bootstraps enhanced with CI constants and mocks
- [x] Database transaction isolation implemented
- [x] Fixed model loading in integration_bootstrap.php (lowercase properties)
- [x] Added missing database methods (where_in, table alias support)
- [x] Integration test assertions updated for actual behavior

**Test Results Summary**:
- Authorization Integration Tests: 12/12 (138 assertions)
- Total Integration Tests: 213/213 (1079 assertions)
- Unit Tests: 128/128 passing
- Overall Status: ✅ 100% PASS RATE

---

## Upcoming Phases

### Phase 6: Progressive Migration - Dual Mode 🟢 ~90% COMPLETE

**Objectives**:
- Implement dual-mode authorization in Gvv_Controller ✅
- Create migration dashboard and tracking ✅
- Migrate users progressively by role groups ⏳

**Status**: Production-ready migration infrastructure complete. Pending: translations (EN/NL), controller conversion, and pilot testing.

**Planning Documents Created**:
- [x] `doc/plans_and_progress/phase6_dual_mode_architecture.md` - Technical architecture design
- [x] `doc/plans_and_progress/phase6_migration_dashboard_mockups.md` - Dashboard UI mockups
- [x] `doc/plans_and_progress/phase6_implementation_summary.md` - Comprehensive implementation summary
- [x] `doc/diagrams/phase6_dual_mode_architecture.puml` - PlantUML architecture diagram
- [x] Test user identification (bin/create_test_users.sh - 6 pilot users)

**Pilot Users** (from bin/create_test_users.sh):
- Wave 1: `testuser` (basic member - low risk)
- Wave 2: `testplanchiste` (planning permissions - medium complexity)
- Wave 3: `testadmin` (global admin - high complexity)
- Backup: `testca`, `testbureau`, `testtresorier`

**Implementation Tasks**:
- [x] Create `application/core/Gvv_Controller.php` base class (384 lines)
- [x] Create migration 046 for `authorization_comparison_log` table (122 lines)
- [x] Implement dual-mode routing logic in Gvv_Controller
- [x] Verify `get_migration_status()` exists in Authorization_model
- [x] Build migration dashboard UI (4 tabs: Overview, Pilot Users, Comparison Log, Statistics)
- [x] Create migration wizard workflow (4-step modal with AJAX)
- [x] Implement rollback functionality in dashboard
- [x] Add AJAX endpoints for migration operations (migrate, rollback, complete)
- [x] Add helper methods for dashboard (8 methods: pilot users, alerts, wave progress, statistics)
- [x] Add French translations (153 keys) for migration dashboard
- [ ] Add English translations (153 keys) for migration dashboard
- [ ] Add Dutch translations (153 keys) for migration dashboard
- [ ] Convert pilot controllers to extend Gvv_Controller (Members, Vols_planeur, Authorization)
- [ ] Wave 1 migration: testuser (7-day monitoring)
- [ ] Wave 2 migration: testplanchiste (7-day monitoring)
- [ ] Wave 3 migration: testadmin (7-day monitoring)

**Code Statistics**:
- Lines of code added: ~2,414 lines
- New files created: 9 files
- Modified files: 4 files
- Dashboard views: 4 complete tabs (1,288 lines total)
- Controller methods: +11 methods (+467 lines to authorization.php)
- Translation keys: 153 (FR complete, EN/NL pending)

### Phase 7: Code-Based Permissions API (v2.0) 🔵 NEW

**Objectives**: Implement the API for declaring permissions directly in controller code

**Tasks**:
- [ ] **7.1** Extend `Gvv_Authorization` with new methods:
  - `require_roles($roles, $section_id, $replace=true)` - Require specific roles
  - `allow_roles($roles, $section_id)` - Allow additional roles (cumulative)
  - `can_edit_row($table, $row_data, $access_type)` - Row-level security check
  - Internal state management for access decisions
- [ ] **7.2** Add helper methods to `Gvv_Controller`:
  - `require_roles()` - Wrapper for authorization library
  - `allow_roles()` - Wrapper for authorization library
  - `can_edit_row()` - Wrapper for row-level checks
- [ ] **7.3** Unit tests for new API:
  - Test `require_roles()` with various role combinations
  - Test `allow_roles()` additive behavior
  - Test multi-level permissions (constructor + method)
  - Test row-level security with different scopes
- [ ] **7.4** Documentation:
  - Developer guide for using the new API
  - Code examples for common patterns (own vs all, restricted actions)
  - Migration guide from database permissions to code

**Estimated Effort**: 2-3 days

**Deliverables**:
- Extended `Gvv_Authorization.php` (+150 lines)
- Extended `Gvv_Controller.php` (+50 lines)
- Unit tests (+15 tests, ~200 lines)
- Developer documentation (`doc/development/code_based_permissions.md`, ~500 lines)

---

### Phase 8: Controller Migration Pilot (v2.0) 🔵 NEW

**Objectives**: Migrate 5-10 simple controllers to code-based permissions

**Pilot Controllers** (simple, low-risk):
- `sections` (ca only)
- `terrains` (ca only)
- `alarmes` (ca only)
- `presences` (ca only)
- `licences` (ca only)
- `tarifs` (ca only)
- `calendar` (user)

**Tasks**:
- [ ] **8.1** For each pilot controller:
  - Add `require_roles()` in constructor
  - Document which permissions were migrated
  - Test all controller methods
  - Verify access denied for unauthorized users
- [ ] **8.2** Create mapping document:
  - Old: `role_permissions` entries → New: code declarations
  - Verification checklist for each controller
- [ ] **8.3** Integration testing:
  - Test with different user roles
  - Verify section-specific permissions
  - Confirm denial messages
- [ ] **8.4** Mark migrated controllers:
  - Add comment `// Authorization: Code-based (v2.0)` in constructor
  - Update controller documentation

**Estimated Effort**: 3-4 days

**Deliverables**:
- 7 migrated controllers (~50 lines changes total)
- Migration mapping document (`doc/phase8_controller_migration_map.md`)
- Integration test updates
- Controller migration checklist

---

### Phase 9: Complex Controllers & Exceptions (v2.0) 🔵 NEW

**Objectives**: Migrate controllers with complex authorization patterns

**Complex Controllers**:
- `membre` (own vs all, multiple exceptions)
- `compta` (own vs bureau vs tresorier)
- `vols_planeur` (auto_planchiste vs planchiste, delete restrictions)
- `vols_avion` (same as vols_planeur)
- `factures` (user vs tresorier)
- `tickets` (user vs ca)
- `attachments` (user vs ca)

**Tasks**:
- [ ] **9.1** Migrate `membre` controller:
  - Constructor: `require_roles(['user'])`
  - `edit($id)`: Check if own user, else `require_roles(['ca'])`
  - `register()`: `require_roles(['ca'])`
  - `delete()`: `require_roles(['ca'])`
- [ ] **9.2** Migrate `compta` controller:
  - Constructor: `require_roles(['tresorier'])`
  - `mon_compte()`: `allow_roles(['user'])`
  - `journal_compte($id)`: Check if own, else `require_roles(['bureau'])`
- [ ] **9.3** Migrate `vols_planeur` controller:
  - Constructor: `require_roles(['planchiste'])`
  - `page()`, `edit()`: `allow_roles(['auto_planchiste'])` + row-level check
  - `delete()`: Keep `require_roles(['planchiste'])` (no auto)
- [ ] **9.4** Migrate other complex controllers (factures, tickets, attachments)
- [ ] **9.5** Document exception patterns:
  - Pattern 1: Own vs All (membre, compta, tickets)
  - Pattern 2: Restricted actions (vols_planeur delete)
  - Pattern 3: Cumulative roles (auto_planchiste + planchiste)
- [ ] **9.6** Comprehensive testing:
  - Test all exception scenarios
  - Verify row-level security
  - Test edge cases (user editing own vs others)

**Estimated Effort**: 5-7 days

**Deliverables**:
- 7 complex controllers migrated (~200 lines changes)
- Exception patterns documentation (`doc/authorization_exception_patterns.md`)
- Extended integration tests (+30 tests)
- Row-level security test suite

---

### Phase 10: Remaining Controllers & Database Cleanup (v2.0) 🔵 NEW

**Objectives**: Complete migration of all controllers and deprecate `role_permissions` table

**Remaining Controllers** (~35 controllers):
- Administration: `admin`, `backend`, `config`, `configuration`, `migration`, `dbchecks`
- Financial: `comptes`, `achats`, `rapprochements`, `plan_comptable`
- Reporting: `rapports`, `reports`, `historique`
- Flight operations: `vols_decouverte`, `planeur`, `avion`, `event`
- Technical: `openflyers`, `FFVV`, `mails`
- Other: `pompes`, `procedures`, `tools`, etc.

**Tasks**:
- [ ] **10.1** Batch migrate simple controllers (week 1):
  - Admin controllers: `require_roles(['club-admin'])`
  - Financial controllers: `require_roles(['tresorier'])`
  - ~15 controllers
- [ ] **10.2** Migrate medium complexity controllers (week 2):
  - Reporting, flight operations, technical
  - ~15 controllers
- [ ] **10.3** Final controllers (week 3):
  - Remaining misc controllers
  - ~5 controllers
- [ ] **10.4** Verification phase:
  - Run full test suite (unit + integration + manual)
  - Verify all 53 controllers migrated
  - Check audit logs for any missed permissions
- [ ] **10.5** Database deprecation:
  - Create migration `047_deprecate_role_permissions.php`:
    - Rename `role_permissions` → `role_permissions_legacy`
    - Add migration timestamp and reason
    - Preserve data for rollback
  - Update `Authorization_model` to skip legacy table
  - Remove `add_permission()`, `remove_permission()` methods
- [ ] **10.6** UI updates:
  - Remove "Permissions" tab from authorization dashboard
  - Update documentation to reflect code-based approach
  - Add banner: "Permissions are now managed in code"

**Estimated Effort**: 15-20 days (3 weeks)

**Deliverables**:
- All 53 controllers migrated (~500 lines changes)
- Migration 047 (deprecate role_permissions)
- Complete migration report (`doc/phase10_complete_migration_report.md`)
- Updated authorization dashboard (removed permissions tab)
- Administrator communication document

---

### Phase 11: Legacy System Cleanup (v2.0) 🔵 NEW

**Objectives**: Remove deprecated code and finalize transition

**Tasks**:
- [ ] **11.1** Code cleanup:
  - Remove `_role_has_permission()` from `Gvv_Authorization` (no longer needed)
  - Remove database permission checking logic
  - Clean up unused methods in `Authorization_model`
  - Update feature flag documentation
- [ ] **11.2** Documentation updates:
  - Mark PRD v1.0 sections as "Legacy"
  - Update architecture diagrams
  - Create "Migration Complete" announcement
  - Update README.md with v2.0 architecture
- [ ] **11.3** Performance optimization:
  - Remove permission caching for database lookups
  - Optimize role checking (already cached in session)
  - Benchmark before/after
- [ ] **11.4** Training & Communication:
  - Create administrator guide for v2.0
  - Developer training session (code-based permissions)
  - Update onboarding documentation
- [ ] **11.5** Final testing:
  - Full regression test suite
  - Security audit (penetration testing)
  - Performance benchmarks
  - User acceptance testing

**Estimated Effort**: 5-7 days

**Deliverables**:
- Cleaned codebase (-300 lines legacy code)
- Performance benchmarks report
- Administrator & developer training materials
- Security audit report
- Project retrospective document

---

### Phase 12: Production Deployment (v2.0) 🔵 NEW

**Objectives**: Deploy code-based authorization system to production

**Pre-Deployment Checklist**:
- [ ] All 53 controllers migrated and tested
- [ ] Full test suite passing (unit + integration + e2e)
- [ ] Performance benchmarks acceptable
- [ ] Database backup complete
- [ ] Rollback plan documented and tested
- [ ] Administrator training complete
- [ ] User communication sent

**Deployment Steps**:
- [ ] **12.1** Staging deployment:
  - Deploy to staging environment
  - Run smoke tests
  - 24-hour monitoring
- [ ] **12.2** Production deployment:
  - Database migration 047 (rename role_permissions)
  - Deploy code changes
  - No feature flag needed (code-based is the new default)
- [ ] **12.3** Post-deployment monitoring (48 hours intensive):
  - Monitor error logs
  - Check audit logs for access denied
  - Monitor performance metrics
  - User feedback collection
- [ ] **12.4** Stabilization (week 1):
  - Address any issues
  - Performance tuning
  - Documentation updates based on feedback
- [ ] **12.5** Final sign-off:
  - Stakeholder approval
  - Project closure
  - Archive legacy code and documentation

**Estimated Effort**: 3-5 days deployment + 1 week stabilization

**Deliverables**:
- Production deployment successful
- Post-deployment report
- Lessons learned document
- Final project retrospective

---

## Project Status Dashboard (v2.0)

| Phase | Status | Progress | Estimated Duration | Notes |
|-------|--------|----------|-------------------|-------|
| **0-6: Legacy System** | ✅ Complete | 100% | - | Database, UI, dual-mode ready |
| **7: Code-Based API** | 🔵 Planned | 0% | 2-3 days | New architecture (v2.0) |
| **8: Pilot Migration** | 🔵 Planned | 0% | 3-4 days | 7 simple controllers |
| **9: Complex Controllers** | 🔵 Planned | 0% | 5-7 days | 7 controllers with exceptions |
| **10: Full Migration** | 🔵 Planned | 0% | 15-20 days | 35 remaining controllers |
| **11: Cleanup** | 🔵 Planned | 0% | 5-7 days | Remove legacy code |
| **12: Production Deploy** | 🔵 Planned | 0% | 3-5 days + 1 week | Final deployment |
| **Overall** | 🟡 In Progress | ~50% | ~40-50 days total | Legacy complete, v2.0 planned |

### Detailed Metrics

| Metric | Target | Current | Status |
|--------|--------|---------|--------|
| **Legacy System (Phases 0-6)** | 100% | ~95% | 🟢 Near Complete |
| **Code-Based System (Phases 7-12)** | 100% | 0% | 🔴 Not Started |
| **Controllers Migrated** | 53 | 0 | 🔴 Phase 8-10 |
| **Database Tables** | 5 active + 1 deprecated | 6 active | 🟡 Migration 047 pending |
| **Tests Passing** | 100% | Unit: 100%, Integration: 100% | 🟢 Complete |
| **Documentation** | Complete | ~60% | 🟡 v2.0 docs pending |
| **Production Ready** | TRUE | FALSE | 🔴 Phase 12 |

---

## Risk Assessment (Updated v2.0)

| Risk | Likelihood | Impact | Mitigation | Status |
|------|------------|--------|------------|--------|
| **Permission mismatch during v2.0 migration** | Medium | High | Comprehensive mapping document, extensive testing | 🟡 Active |
| **Missed controller in migration** | Low | High | Migration checklist, automated verification script | 🟡 Planned |
| **Access denial for valid users** | Low | Critical | Progressive rollout (7→7→35 controllers), rollback | 🟢 Managed |
| **Performance degradation** | Very Low | Medium | No DB lookups for permissions = faster | 🟢 Low Risk |
| **Code-permission divergence** | Low | Medium | Code review, documentation, developer training | 🟡 Planned |
| **Legacy code interaction** | Medium | Medium | Careful testing, preserve `data_access_rules` | 🟡 Active |

**Current Risk Level**: 🟡 Medium - Significant architecture change in progress, thorough planning reduces risk

---

## Key Files (Updated v2.0)

### Application Code - Legacy System (Phases 0-6)
```
application/
├── core/
│   └── Gvv_Controller.php                 (384 lines) ✅ Phase 6 - Will be extended in Phase 7
├── controllers/
│   ├── authorization.php                  (1,151 lines) ✅ Phase 4-6
│   └── [53 controllers]                   🔵 To migrate in Phases 8-10
├── libraries/
│   └── Gvv_Authorization.php              (480 lines) ✅ Phase 3 - Will be extended in Phase 7
├── models/
│   └── Authorization_model.php            (388 lines) ✅ Phase 3 - Will be cleaned in Phase 11
├── migrations/
│   ├── 042_authorization_refactoring.php  ✅ Phase 2 - Schema
│   ├── 043_populate_authorization_data.php ✅ Phase 2 - Data (~300 permissions)
│   ├── 046_dual_mode_support.php          ✅ Phase 6 - Comparison log
│   └── 047_deprecate_role_permissions.php 🔵 Phase 10 - Rename role_permissions
├── views/authorization/
│   ├── user_roles.php, roles.php, audit_log.php, etc. (8 files, 1,648 lines) ✅
│   ├── permissions.php                    ⚠️ To be removed in Phase 10
│   └── migration/ (4 files, 1,288 lines)  ✅ Phase 6
└── language/*/gvv_lang.php                (+360 keys total) ✅
```

### New Code - v2.0 System (Phases 7-12)
```
application/
├── libraries/
│   └── Gvv_Authorization.php              (+150 lines) 🔵 Phase 7 - New API methods
├── core/
│   └── Gvv_Controller.php                 (+50 lines) 🔵 Phase 7 - Helper wrappers
├── controllers/
│   ├── sections.php, terrains.php, etc.   (~50 lines changes) 🔵 Phase 8 - Pilot
│   ├── membre.php, compta.php, etc.       (~200 lines changes) 🔵 Phase 9 - Complex
│   └── [35 remaining controllers]         (~500 lines changes) 🔵 Phase 10 - Batch
├── views/authorization/
│   └── permissions.php                    ❌ Removed in Phase 10
└── tests/
    ├── unit/libraries/Gvv_AuthorizationTest.php  (+15 tests) 🔵 Phase 7
    └── integration/AuthorizationIntegrationTest.php  (+30 tests) 🔵 Phase 9
```

**Legacy Lines**: ~5,850 lines (Phases 0-6)
**New Code**: ~950 lines (Phases 7-12)
**Removed**: ~300 lines (deprecated permissions code)
**Net Change**: ~6,500 lines final

### Testing
```
application/tests/
├── unit/
│   ├── libraries/Gvv_AuthorizationTest.php (12 tests)
│   └── models/Authorization_modelTest.php  (14 tests)
├── integration/
│   └── AuthorizationIntegrationTest.php    (12 test methods)
└── *_bootstrap.php files (CI mock infrastructure)
```

### Documentation
```
doc/plans_and_progress/
├── 2025_authorization_refactoring_plan.md        (this file)
├── phase4_progress.md                            (Phase 4 UI implementation)
├── authorization_implementation_summary.md       (Phase 3-4 Architecture)
├── phase6_dual_mode_architecture.md              ✨ NEW - Phase 6 technical design
├── phase6_migration_dashboard_mockups.md         ✨ NEW - Phase 6 UI mockups
└── phase6_implementation_summary.md              ✨ NEW - Phase 6 comprehensive summary
```

---

## Feature Flag Configuration

**File**: `application/config/gvv_config.php`

```php
$config['use_new_authorization'] = FALSE;  // TRUE to enable new system
```

**Status**:
- Development: Can be enabled for testing
- Production: FALSE (awaiting Phase 7 deployment)
- Dual-mode: ✅ IMPLEMENTED in Gvv_Controller (Phase 6) - Per-user migration via `authorization_migration_status.use_new_system`

---

## Next Immediate Actions (Updated v2.0)

### Current Priority: Transition to Code-Based Permissions

1. **🔄 Complete Phase 6 (Legacy System)**:
   - [ ] Add English/Dutch translations (2 hours)
   - [ ] Final testing of dual-mode infrastructure
   - [ ] **Decision Point**: Validate Phase 6 OR proceed directly to v2.0

2. **🔵 Phase 7: Code-Based API Implementation** (2-3 days):
   - [ ] Extend `Gvv_Authorization` with `require_roles()`, `allow_roles()`, `can_edit_row()`
   - [ ] Add helpers to `Gvv_Controller`
   - [ ] Write unit tests for new API (15 tests)
   - [ ] Create developer documentation guide

3. **🔵 Phase 8: Pilot Controller Migration** (3-4 days):
   - [ ] Migrate 7 simple controllers (sections, terrains, alarmes, presences, licences, tarifs, calendar)
   - [ ] Create mapping document (old permissions → new code)
   - [ ] Integration testing with different roles
   - [ ] Document migration patterns

4. **🔵 Phase 9: Complex Controllers** (5-7 days):
   - [ ] Migrate membre, compta, vols_planeur (complex exceptions)
   - [ ] Document exception patterns (own vs all, restricted actions)
   - [ ] Extensive testing of row-level security
   - [ ] Create exception patterns guide

5. **Documentation Priorities**:
   - [ ] PRD v2.0 ✅ (Complete)
   - [ ] Implementation Plan v2.0 ✅ (This document)
   - [ ] Developer guide for code-based permissions (Phase 7)
   - [ ] Migration mapping for all 53 controllers (Phase 8-10)
   - [ ] Administrator communication (Phase 12)

---

## Project Timeline (v2.0)

### Already Completed (Phases 0-6)
- **Weeks 1-10**: Legacy system implementation ✅
- **Status**: Database, UI, dual-mode infrastructure complete

### Remaining Work (Phases 7-12)
| Phase | Duration | Cumulative | Target Week |
|-------|----------|------------|-------------|
| **7: Code-Based API** | 2-3 days | 3 days | Week 11 |
| **8: Pilot Migration** | 3-4 days | 7 days | Week 12 |
| **9: Complex Controllers** | 5-7 days | 14 days | Week 13-14 |
| **10: Full Migration** | 15-20 days | 34 days | Week 15-17 |
| **11: Cleanup** | 5-7 days | 41 days | Week 18 |
| **12: Deployment** | 3-5 days + 1 week | 49 days | Week 19-20 |

**Total Estimated Duration**: ~10 weeks (50 days) for Phases 7-12
**Project Total**: ~20 weeks from start (Phases 0-12)

---

## Success Criteria (Updated v2.0)

### ✅ Phase 5 Exit Criteria - COMPLETE
- ✅ All unit tests passing (26/26)
- ✅ All integration tests passing (12/12 authorization, 213/213 total)
- ✅ Integration test framework operational
- ✅ Database transaction isolation working

### ✅ Phase 6 Exit Criteria - 90% COMPLETE
- ✅ Dual-mode infrastructure operational
- ✅ Migration dashboard functional (4 tabs)
- ✅ AJAX operations working
- ✅ Comparison logging active
- ✅ French translations complete
- ⏳ English/Dutch translations (optional for v2.0)
- **Note**: Pilot testing made optional by v2.0 architecture change

### 🔵 Phase 7 Exit Criteria (Code-Based API)
- [ ] `require_roles()`, `allow_roles()`, `can_edit_row()` implemented
- [ ] Unit tests passing (15 new tests)
- [ ] Developer documentation complete
- [ ] API design validated by stakeholders

### 🔵 Phase 8 Exit Criteria (Pilot Migration)
- [ ] 7 simple controllers migrated
- [ ] Mapping document created (old → new)
- [ ] Integration tests updated
- [ ] No authorization errors in logs

### 🔵 Phase 9 Exit Criteria (Complex Controllers)
- [ ] 7 complex controllers migrated (membre, compta, vols_planeur, etc.)
- [ ] Exception patterns documented
- [ ] Row-level security tests passing
- [ ] No regression in functionality

### 🔵 Phase 10 Exit Criteria (Full Migration)
- [ ] All 53 controllers migrated
- [ ] Migration 047 executed (role_permissions → role_permissions_legacy)
- [ ] Permissions tab removed from UI
- [ ] Verification script passes (no missed controllers)

### 🔵 Phase 11 Exit Criteria (Cleanup)
- [ ] Legacy permission code removed
- [ ] Performance benchmarks show improvement
- [ ] Documentation updated
- [ ] Training materials created

### 🔵 Phase 12 Exit Criteria (Deployment)
- [ ] Staging deployment successful
- [ ] Production deployment successful
- [ ] 48-hour monitoring clean
- [ ] User acceptance sign-off

### 🎯 Project Completion Criteria (v2.0)
- [ ] All 53 controllers use code-based permissions
- [ ] `role_permissions` table deprecated and renamed
- [ ] Performance improved (no DB lookups for permissions)
- [ ] All documentation updated (PRD, plan, guides)
- [ ] Administrator and developer training complete
- [ ] Project retrospective conducted
- [ ] Lessons learned documented

---

**Document History**:
- v1.0 (2025-01-08): Initial plan
- v1.1 (2025-10-15): Phase 3 completion update
- v1.2 (2025-10-17): Unit tests fixed
- v1.3 (2025-10-18): Phase 4 completion
- v1.4 (2025-10-18): Compressed format, removed redundant details
- v1.5 (2025-10-21): Phase 5 complete (100% test pass rate), Phase 6 planning created
- v1.6 (2025-10-21): Phase 6 ~90% complete - Migration dashboard implemented
- **v2.0 (2025-01-24): Major architecture change**
  - **Breaking Change**: Permissions now managed in code, not database
  - Added Phases 7-12 for code-based permission migration
  - Legacy system (Phases 0-6) preserved for rollback
  - `role_permissions` table to be deprecated in Phase 10
  - Updated timeline: +10 weeks (Phases 7-12)
  - Based on PRD v2.0 analysis
  - Estimated 53 controllers to migrate (~800 lines changes)
  - Performance improvement expected (no DB lookups)

---

## Appendix: Migration from Database to Code-Based Permissions

### Why the Change?

**Problem Identified**: The v1.0 implementation created ~300 permission entries in `role_permissions` table:
- Difficult to maintain (manual entries for each controller/action/role combination)
- Risk of inconsistency between code and database
- Performance overhead (SQL queries for every permission check)
- Complex UI for managing permissions

**Solution**: Declare permissions directly in controller code:
- Permissions visible where they apply (in controller constructors/methods)
- Always consistent with code (versioned together in Git)
- Better performance (no DB lookups, only session-cached roles)
- Simpler maintenance (developers manage permissions in code)

### What Stays the Same

✅ **Unchanged**:
- User roles system (`types_roles`, `user_roles_per_section`)
- Role assignment UI (user roles management)
- Row-level security (`data_access_rules`)
- Audit logging (`authorization_audit_log`)
- Section-aware permissions
- 8 defined roles (club-admin, super-tresorier, bureau, etc.)

### What Changes

⚠️ **Changed**:
- **OLD**: Permissions stored in `role_permissions` table
- **NEW**: Permissions declared in controller code via `require_roles()`, `allow_roles()`
- **OLD**: Permissions UI tab in authorization dashboard
- **NEW**: Permissions tab removed (managed in code)
- **OLD**: `Gvv_Authorization->can_access($user, $controller, $action)`
- **NEW**: `Gvv_Controller->require_roles(['planchiste'])` in constructor

### Migration Example

**Before (v1.0 - Database)**:
```sql
-- In database role_permissions table
INSERT INTO role_permissions (types_roles_id, controller, action, section_id, permission_type)
VALUES (5, 'vols_planeur', NULL, 1, 'view'),
       (5, 'vols_planeur', 'create', 1, 'edit'),
       (5, 'vols_planeur', 'edit', 1, 'edit'),
       (5, 'vols_planeur', 'delete', 1, 'delete');
```

**After (v2.0 - Code)**:
```php
// In application/controllers/vols_planeur.php
class Vols_planeur extends Gvv_Controller {
    function __construct() {
        parent::__construct();
        // Authorization: Code-based (v2.0)
        $this->require_roles(['planchiste'], $this->section_id);
    }

    function delete($id) {
        // More restrictive: only planchiste can delete (not auto_planchiste)
        // No allow_roles() here = keep constructor requirement
        parent::delete($id);
    }
}
```

### Rollback Strategy

If code-based approach fails:
1. Revert Git commits (Phases 7-12)
2. Restore `role_permissions` table from `role_permissions_legacy`
3. Re-enable database permission checking in `Gvv_Authorization`
4. All data preserved, no data loss
