# GVV Authorization System Refactoring Plan

**Document Version:** 1.4
**Date:** 2025-01-08 (Updated: 2025-10-18)
**Status:** Phases 0-4 Complete, Phase 5 Unit Tests Passing, Phase 6+ Pending
**Author:** Claude Code Analysis

---

## Current Status Summary

### ✅ Completed Phases (0-4)

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

### Phase 5: Testing Framework 🔄 IN PROGRESS

**Completed**:
- [x] Unit tests: 26/26 passing (Gvv_Authorization: 12, Authorization_model: 14)
- [x] Integration test framework operational (AuthorizationIntegrationTest)
- [x] PHPUnit configurations created
- [x] Test bootstraps enhanced with CI constants and mocks
- [x] Database transaction isolation implemented

**Pending**:
- [ ] Fix remaining integration test failures
- [ ] Achieve 100% integration test pass rate
- [ ] Code coverage > 80%
- [ ] Phase 4 UI end-to-end testing

---

## Upcoming Phases

### Phase 6: Progressive Migration - Dual Mode (Week 9-10)

**Objectives**:
- Implement dual-mode authorization in Gvv_Controller
- Create migration dashboard and tracking
- Migrate users progressively by role groups

**Key Tasks**:
- [ ] `Authorization_migration` controller
- [ ] `Migration_status_model`
- [ ] Dual-mode implementation in Gvv_Controller
- [ ] Pilot user migration (2-3 users, 48h monitoring)
- [ ] Role group migrations: Planchistes → CA → Bureau → Tresoriers → Others → Admins
- [ ] Pre-cutover validation (100% users, 7-day stability)

### Phase 7: Full Deployment (Week 11)

**Cutover**:
- [ ] Set feature flag to TRUE globally
- [ ] Deploy to production
- [ ] 48-hour intensive monitoring
- [ ] User communication
- [ ] Rollback plan ready

### Phase 8: Cleanup & Documentation (Week 12)

**Finalization**:
- [ ] Rename old tables (preserve for rollback)
- [ ] Update all documentation
- [ ] Create admin user guide
- [ ] Administrator training
- [ ] Performance optimization
- [ ] Project retrospective

---

## Migration Status Dashboard

| Metric | Target | Current | Status |
|--------|--------|---------|--------|
| **Overall Progress** | 100% | ~50% | 🟡 In Progress |
| **Users Migrated** | 292 | 0 | 🔴 Phase 6 |
| **Code Complete** | 100% | 100% | 🟢 Complete |
| **UI Complete** | 100% | 100% | 🟢 Complete |
| **Tests Passing** | 100% | Unit: 100%, Integration: ~70% | 🟡 Phase 5 |
| **Production Deploy** | TRUE | FALSE | 🔴 Phase 7 |

---

## Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| **Permission mismatch after migration** | Medium | High | Dual-mode with comparison reports, pilot testing |
| **Access denial for valid users** | Low | Critical | Progressive rollout, immediate rollback capability |
| **Performance degradation** | Low | Medium | Load testing, query optimization, caching |
| **Data corruption during migration** | Very Low | Critical | Transaction isolation, tested rollback, backups |

**Current Risk Level**: 🟡 Medium - Awaiting Phase 5 test completion and Phase 6 pilot migration

---

## Key Files

### Application Code
```
application/
├── controllers/authorization.php           (445 lines)
├── libraries/Gvv_Authorization.php        (480 lines)
├── models/Authorization_model.php         (388 lines)
├── migrations/
│   ├── 042_authorization_refactoring.php  (Schema)
│   └── 043_populate_authorization_data.php (Data)
├── views/authorization/                   (8 files, 1,648 lines)
└── language/*/gvv_lang.php               (+207 keys total)
```

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
doc/
├── plans/2025_authorization_refactoring_plan.md (this file)
├── phase4_progress.md                           (UI implementation)
└── authorization_implementation_summary.md       (Architecture)
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
- Dual-mode: Not yet implemented (Phase 6)

---

## Next Immediate Actions

1. **Complete Phase 5 Testing**:
   - Fix remaining integration test failures
   - Achieve 100% integration test pass rate
   - Perform Phase 4 UI end-to-end testing

2. **Begin Phase 6 Planning**:
   - Design dual-mode architecture in Gvv_Controller
   - Identify 2-3 pilot users for initial migration
   - Create migration dashboard mockups

3. **Documentation**:
   - Create administrator user guide for new UI
   - Document migration procedures for Phase 6

---

## Success Criteria

### Phase 5 Exit Criteria
- ✅ All unit tests passing (26/26) - **ACHIEVED**
- ⏳ All integration tests passing
- ⏳ Code coverage > 80%
- ⏳ UI workflow testing complete

### Phase 6 Exit Criteria
- 100% of users successfully migrated
- No access denial issues for 7 consecutive days
- Dual-mode working correctly
- All comparison reports validated

### Phase 7 Exit Criteria
- Feature flag enabled globally
- 48-hour monitoring shows no issues
- Performance metrics acceptable
- User communication complete

### Project Completion Criteria
- Old system deprecated (tables renamed, not dropped)
- All documentation updated
- Administrator training complete
- Project retrospective conducted

---

**Document History**:
- v1.0 (2025-01-08): Initial plan
- v1.1 (2025-10-15): Phase 3 completion update
- v1.2 (2025-10-17): Unit tests fixed
- v1.3 (2025-10-18): Phase 4 completion
- v1.4 (2025-10-18): Compressed format, removed redundant details
