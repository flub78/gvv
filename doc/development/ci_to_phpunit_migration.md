# CodeIgniter Unit_test to PHPUnit Migration Plan

## Executive Summary

This document outlines the strategy for migrating from CodeIgniter's built-in Unit_test library to PHPUnit tests.

**Current State:**
- 32 controllers with CI Unit_test methods
- 5 models with test() methods returning test results
- 1 model with complete PHPUnit coverage (Configuration_model)
- 4 models requiring PHPUnit migration

## Inventory

### Controllers with CI Unit Tests (32 total)

| Controller | Test Methods | Assertions | Notes |
|-----------|-------------|-----------|-------|
| achats | 1 | 3 | |
| admin | 1 | 2 | |
| associations_ecriture | 1 | 1 | |
| associations_of | 1 | 1 | |
| associations_releve | 1 | 1 | |
| attachments | 1 | 3 | |
| categorie | 1 | 1 | |
| compta | 1 | 1 | |
| comptes | 1 | 1 | |
| configuration | 2 | 8 | ✅ Has PHPUnit tests |
| event | 1 | 1 | |
| events_types | 1 | 1 | |
| facturation | 2 | 1 | |
| historique | 2 | 1 | |
| licences | 1 | 1 | |
| mails | 6 | 2 | |
| membre | 1 | 1 | |
| plan_comptable | 1 | 1 | |
| planeur | 1 | 1 | |
| pompes | 1 | 1 | |
| presences | 1 | 1 | |
| rapports | 1 | 1 | |
| reports | 2 | 1 | |
| sections | 1 | 3 | Model has test() method |
| tarifs | 1 | 1 | |
| terrains | 1 | 1 | |
| tickets | 1 | 1 | |
| types_ticket | 1 | 1 | |
| user_roles_per_section | 1 | 3 | Model has test() method |
| vols_avion | 1 | 1 | |
| vols_decouverte | 1 | 1 | |
| vols_planeur | 1 | 4 | |

### Models with test() Methods (5 total)

| Model | PHPUnit Status | Test Pattern |
|-------|---------------|-------------|
| Configuration_model | ✅ Complete | Unit + MySQL Integration |
| sections_model | ❌ Missing | CRUD + count |
| attachments_model | ❌ Missing | CRUD + count |
| achats_model | ❌ Missing | CRUD + count |
| types_roles_model | ❌ Missing | CRUD + count (was types_roles, appears to be sections) |
| user_roles_per_section_model | ❌ Missing | Complex: create section + user, test roles |

## Test Patterns

### Pattern 1: Simple CRUD Test
**Models:** sections_model, types_roles_model

```php
public function test() {
    // 1. Count initial records
    // 2. Insert test record
    // 3. Verify insertion (count + get_by_id)
    // 4. Delete test record
    // 5. Verify deletion (count back to initial)
}
```

**PHPUnit Equivalent:** MySQL Integration Test
- Use transactions (trans_start/trans_rollback)
- Test create(), get_by_id(), delete()
- Verify database state changes

### Pattern 2: CRUD with Field Validation
**Models:** attachments_model, achats_model

Same as Pattern 1, but also:
- Verify specific field values after insertion
- Test multiple fields

### Pattern 3: Complex Relationships
**Models:** user_roles_per_section_model

```php
public function test() {
    // 1. Create dependent records (section, user)
    // 2. Test model-specific methods
    // 3. Verify relationships work
    // 4. Clean up all created records
}
```

**PHPUnit Equivalent:** MySQL Integration Test with dependencies
- Create all dependent records in setUp() or test
- Test relationship methods
- Clean up via transaction rollback

## Migration Strategy

### Phase 1: Model Testing (Priority)
**Goal:** Create PHPUnit MySQL integration tests for all 4 models

| Model | Template | Complexity |
|-------|----------|-----------|
| sections_model | ConfigurationModelMySqlTest | Low |
| attachments_model | ConfigurationModelMySqlTest | Low |
| achats_model | ConfigurationModelMySqlTest | Low |
| user_roles_per_section_model | Custom | Medium (dependencies) |

**Benefits:**
- Models are core business logic
- Clean migration path (1:1 mapping)
- Can be automated with template
- Provides better isolation and speed

### Phase 2: Controller Testing (Future)
**Goal:** Decide on controller test strategy

**Options:**

1. **Keep CI Built-in Tests**
   - ✅ Already working
   - ✅ Test full stack with CI framework
   - ✅ Easy to run via URL (http://localhost/gvv2/controller/test)
   - ❌ Not integrated in CI/CD
   - ❌ Manual execution
   - ❌ No coverage reports

2. **Migrate to PHPUnit Integration Tests**
   - ✅ Automated execution
   - ✅ Coverage reports
   - ✅ CI/CD integration
   - ❌ Complex CI framework bootstrapping
   - ❌ Time-consuming to migrate 32 controllers

3. **Hybrid Approach (RECOMMENDED)**
   - Keep CI tests for development/debugging
   - Create PHPUnit tests for critical controllers only
   - Focus PHPUnit tests on business logic, not full stack
   - Use controller output parsing tests (like ConfigurationControllerTest)

## Implementation Plan

### Step 1: Create Model Tests (This Phase)

Create PHPUnit MySQL integration tests for:

1. **sections_model** → SectionsModelMySqlTest.php
   - Tests: CREATE, READ, UPDATE, DELETE, count, image()
   - Template: Copy ConfigurationModelMySqlTest.php structure
   - Assertions: ~8-10

2. **attachments_model** → AttachmentsModelMySqlTest.php
   - Tests: CREATE, READ, UPDATE, DELETE, count
   - Verify referenced_table, referenced_id fields
   - Assertions: ~10-12

3. **achats_model** → AchatsModelMySqlTest.php
   - Tests: CREATE, READ, UPDATE, DELETE, count
   - Verify produit, quantite, prix fields
   - Assertions: ~10-12

4. **user_roles_per_section_model** → UserRolesPerSectionModelMySqlTest.php
   - Tests: get_user_roles_for_section() with/without roles
   - Complex: needs section + user creation
   - Assertions: ~6-8

**Total:** 4 new test files, ~40-45 new assertions

### Step 2: Update Test Suite

1. Exclude new tests from phpunit_integration.xml (to prevent duplicates)
2. Add new tests to phpunit_mysql.xml
3. Update run_all_tests.sh count summary
4. Verify all tests pass

### Step 3: Documentation

1. Document which CI tests can be replaced by PHPUnit
2. Update controller_testing.md if needed
3. Create migration completion report

## Test File Structure

```
application/tests/
├── integration/
│   ├── ConfigurationModelMySqlTest.php  ✅ Exists
│   ├── SectionsModelMySqlTest.php       ⬜ To create
│   ├── AttachmentsModelMySqlTest.php    ⬜ To create
│   ├── AchatsModelMySqlTest.php         ⬜ To create
│   └── UserRolesPerSectionModelMySqlTest.php ⬜ To create
```

## Expected Outcomes

**After Phase 1 completion:**
- ✅ 5/5 models with test() methods have PHPUnit coverage
- ✅ All model tests automated in CI/CD
- ✅ ~40-45 new assertions
- ✅ Total test count: 108-110 tests (from current 104)
- ✅ MySQL integration: 13-14 tests (from current 9)

**CI Unit Tests Status:**
- Model tests: Can be replaced by PHPUnit ✅
- Controller tests: Keep for now, evaluate later 🤔

## Replacement Strategy

### Models: Direct Replacement ✅

Once PHPUnit tests exist for a model:

1. Model's `test()` method can be kept (backwards compatibility)
2. Controller's test can still call model test
3. **Recommended:** Add comment in model test() pointing to PHPUnit test
4. Future: Could deprecate model test() methods

Example comment to add:

```php
/**
 * Legacy CodeIgniter Unit test
 * For automated testing, see: application/tests/integration/SectionsModelMySqlTest.php
 * @deprecated Use PHPUnit tests instead
 */
public function test() {
    // existing code...
}
```

### Controllers: Keep for Now 🔄

Controller `test($format)` methods:

1. **Keep** for manual development testing
2. **Keep** URL access: http://localhost/gvv2/controller/test
3. **Add** PHPUnit tests only for critical business logic
4. **Document** that PHPUnit is the automated test suite

## Benefits of Migration

### Immediate Benefits
1. ✅ Automated test execution (CI/CD)
2. ✅ Transaction-based isolation (no DB pollution)
3. ✅ Better test organization
4. ✅ Code coverage reporting
5. ✅ Faster execution (parallel possible)

### Long-term Benefits
1. ✅ Modern testing practices
2. ✅ Better IDE integration
3. ✅ Easier refactoring
4. ✅ Industry-standard tooling
5. ✅ Easier onboarding for new developers

## Risks & Mitigation

| Risk | Impact | Mitigation |
|------|--------|-----------|
| Breaking existing CI tests | Medium | Keep CI tests intact, add PHPUnit alongside |
| Test duplication | Low | Document which tests replace which |
| Database schema changes affect tests | Medium | Use transactions, verify in setUp() |
| Complex dependencies hard to test | High | Start with simple models, learn patterns |

## Timeline Estimate

- ✅ **Analysis & Planning:** 1 hour (DONE)
- ⬜ **sections_model test:** 30 minutes
- ⬜ **attachments_model test:** 30 minutes
- ⬜ **achats_model test:** 30 minutes
- ⬜ **user_roles_per_section_model test:** 1 hour (complex)
- ⬜ **Testing & debugging:** 30 minutes
- ⬜ **Documentation:** 30 minutes

**Total:** ~4-5 hours

## Next Actions

1. ✅ Complete this migration plan document
2. ✅ Create SectionsModelMySqlTest.php (9 tests)
3. ✅ Create AttachmentsModelMySqlTest.php (9 tests)
4. ✅ Create AchatsModelMySqlTest.php (10 tests)
5. ✅ Create UserRolesPerSectionModelMySqlTest.php (7 tests)
6. ✅ Update phpunit_mysql.xml configuration
7. ⚠️ Run full test suite - Tests created but need refinement (see status below)
8. ⬜ Fix remaining test issues
9. ⬜ Document completion status

---

## Current Implementation Status

### ✅ Completed Tasks

1. **Test Files Created:** All 4 model test files created
   - SectionsModelMySqlTest.php (9 tests, 47 assertions planned)
   - AttachmentsModelMySqlTest.php (9 tests, 51 assertions planned)
   - AchatsModelMySqlTest.php (10 tests, 47 assertions planned)
   - UserRolesPerSectionModelMySqlTest.php (7 tests, 37 assertions planned)

2. **Configuration Updated:**
   - phpunit_mysql.xml includes all 5 model tests
   - mysql_bootstrap.php loads all required models

3. **Migration Plan:** Comprehensive document created

### ⚠️ Known Issues (Remaining Work)

**Bootstrap Issues:**
- `count_all_results()` method missing in RealMySQLDatabase (affects 5 tests)
- `join()` method missing in RealMySQLDatabase (affects 1 test)

**Test Issues:**
- Foreign key constraints on achats (needs valid pilote from membres table - affects 5 tests)
- Foreign key constraints on sections (can't delete with user_roles - affects 1 test)
- Password/username field length in user creation (affects 4 tests)
- update() method signature fixes needed (2 fixed, may be more)
- Minor field comparison issues (decimal precision)

**Current Test Status:**
- Configuration model: 9/9 passing ✅
- Sections model: 5/9 passing (4 errors)
- Attachments model: 4/9 passing (4 errors, 1 failure)
- Achats model: 1/10 passing (8 errors, 1 failure)
- User roles: 0/7 passing (7 errors)

**Total: 19/44 MySQL integration tests passing (43%)**

### 🔧 Required Fixes

1. **Add missing methods to RealMySQLDatabase:**
   - `count_all_results()` - for model count() operations
   - `join()` - for complex queries

2. **Fix test data to respect foreign keys:**
   - Achats tests: Create valid membre record or use existing pilot
   - Sections tests: Handle foreign key cascade or use different test approach

3. **Fix helper method issues:**
   - createTestUser(): Shorter password (max 32 chars), shorter username
   - update() calls: Use proper signature throughout

4. **Minor fixes:**
   - Field type comparisons (handle decimal precision)
   - Delete return value handling

### 📊 Overall Impact

**If all tests were passing:**
- Total tests: 104 + 35 = 139 tests
- MySQL integration: 9 + 35 = 44 tests
- Significant increase in model test coverage

**Current working state:**
- Core test framework: ✅ Working
- Configuration model: ✅ Fully tested
- Other models: ⚠️ Partially tested, need fixes

---

**Document Status:** ⚠️ In Progress - Tests created, refinement needed
**Last Updated:** 2025-10-02 (Updated with implementation status)
**Author:** Claude Code
**Related Docs:**
- doc/development/controller_testing.md
- application/tests/integration/ConfigurationModelMySqlTest.php (reference template)
- application/tests/integration/SectionsModelMySqlTest.php
- application/tests/integration/AttachmentsModelMySqlTest.php
- application/tests/integration/AchatsModelMySqlTest.php
- application/tests/integration/UserRolesPerSectionModelMySqlTest.php
