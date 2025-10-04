# PHPUnit Test Coverage Comparison

## Question: What tests are run by `run-coverage.sh` vs `run/all_tests.sh`?

### Summary

**`run-coverage.sh`** runs **ONLY 38 tests** (same as `phpunit.xml`)  
**`run/all_tests.sh`** runs **ALL 119 tests** (all test suites combined)

❌ **NO** - `run-coverage.sh` does NOT cover all tests from `run/all_tests.sh`

---

## Detailed Breakdown

### 1️⃣ Tests run by `./run-coverage.sh`

**Config:** `phpunit-coverage.xml`  
**Tests:** 38 tests, 220 assertions

#### Test Suites Included:
- ✅ `application/tests/unit/helpers` (11 tests)
  - ValidationHelperTest (9)
  - DebugExampleTest (2)
  
- ✅ `application/tests/unit/models` (6 tests)
  - ConfigurationModelTest (6)
  
- ✅ `application/tests/unit/libraries` (9 tests)
  - BitfieldTest (9)
  
- ✅ `application/tests/unit/i18n` (6 tests)
  - LanguageCompletenessTest (6)
  
- ✅ `application/tests/controllers` (8 tests)
  - ConfigurationControllerTest (6)
  - ControllerTest (2)

#### Test Suites EXCLUDED (commented out):
- ❌ `application/tests/unit/enhanced` (40 tests) - NOT included
- ❌ `application/tests/integration` (35 tests) - NOT included  
- ❌ `application/tests/mysql` (9 tests) - NOT included

---

### 2️⃣ Tests run by `./run/all_tests.sh`

**Configs:** 4 separate configurations  
**Tests:** 119 tests total (estimated from script)

#### Test Suite 1: Unit Tests (`phpunit.xml`)
**38 tests** - Same as run-coverage.sh
- Unit helpers (11)
- Unit models (6)
- Unit libraries (9)
- i18n (6)
- Controllers (8)

#### Test Suite 2: Integration Tests (`phpunit_integration.xml`)
**35 tests** - Real database operations
- AssetsHelperIntegrationTest (7)
- CategorieModelIntegrationTest (6)
- FormElementsIntegrationTest (6)
- GvvmetadataTest (10)
- SmartAdjustorCorrelationIntegrationTest (6)

#### Test Suite 3: Enhanced Tests (`phpunit_enhanced.xml`)
**40 tests** - CI helpers/libraries with full bootstrap
- AssetsHelperTest (7)
- ButtonLibraryTest (8)
- CsvHelperTest (3)
- FormElementsHelperTest (6)
- LogLibraryTest (8)
- WidgetLibraryTest (8)

#### Test Suite 4: Controller Tests (`phpunit_controller.xml`)
**6 tests** - Subset already in coverage
- ConfigurationControllerTest (6)

**Total: ~119 tests** across all categories

---

## 🔍 What's Missing from Coverage?

### Tests NOT covered by `run-coverage.sh`:

1. **Enhanced Tests (40 tests)** 📦
   - Full CodeIgniter bootstrap
   - Real helper/library instances
   - More comprehensive than unit tests

2. **Integration Tests (35 tests)** 🔗
   - Real MySQL database operations
   - Transaction rollback cleanup
   - Full framework integration
   - Metadata system tests
   - Image correlation tests

3. **MySQL Tests (9 tests)** 🗄️
   - ConfigurationModelMySqlTest
   - Real CRUD operations
   - Database constraints validation

**Total Missing: 84 tests (70% of all tests!)**

---

## 📊 Comparison Table

| Test Category | run-coverage.sh | run/all_tests.sh | Missing |
|---------------|-----------------|------------------|---------|
| Unit Helpers | ✅ 11 | ✅ 11 | - |
| Unit Models | ✅ 6 | ✅ 6 | - |
| Unit Libraries | ✅ 9 | ✅ 9 | - |
| i18n Tests | ✅ 6 | ✅ 6 | - |
| Controller Tests (basic) | ✅ 8 | ✅ 8 | - |
| **Enhanced Tests** | ❌ 0 | ✅ 40 | **40** |
| **Integration Tests** | ❌ 0 | ✅ 35 | **35** |
| **MySQL Tests** | ❌ 0 | ✅ 9 | **9** |
| **TOTAL** | **38** | **119** | **84** |

---

## 🎯 Recommendation: Enable All Tests in Coverage

### Option 1: Activate commented test suites in `phpunit-coverage.xml`

**Edit lines 13-23 in phpunit-coverage.xml:**

```xml
<!-- BEFORE: Commented out -->
<!-- Additional test suites to activate progressively
<testsuite name="EnhancedTests">
    <directory>application/tests/unit/enhanced</directory>
</testsuite>
<testsuite name="IntegrationTests">
    <directory>application/tests/integration</directory>
</testsuite>
<testsuite name="MySqlTests">
    <directory>application/tests/mysql</directory>
</testsuite>
-->

<!-- AFTER: Activated -->
<testsuite name="EnhancedTests">
    <directory>application/tests/unit/enhanced</directory>
</testsuite>
<testsuite name="IntegrationTests">
    <directory>application/tests/integration</directory>
</testsuite>
<testsuite name="MySqlTests">
    <directory>application/tests/mysql</directory>
</testsuite>
```

**Result:** Coverage will analyze ALL 119 tests  
**Tradeoff:** Slower execution (~40-60 seconds instead of 20)

### Option 2: Create separate coverage configs

Keep current setup for fast iteration, create comprehensive coverage:

```bash
# Fast coverage (38 tests) - current
./run-coverage.sh

# Full coverage (119 tests) - new script
./run-coverage-full.sh
```

---

## 🚀 Impact on Coverage Percentage

### Current Coverage (38 tests only):
- **0.36%** line coverage
- Only testing: helpers, basic models, libraries, i18n, basic controllers

### Expected Coverage (119 tests):
- **~5-8%** estimated line coverage
- Would include:
  - Real database operations
  - Full CI framework integration
  - More comprehensive helper/library tests
  - Complete controller workflows

### Why Such Low Coverage?

The codebase has **1,091,140 lines**, including:
- 48 controllers (mostly untested)
- 37 models (mostly untested)
- Complex billing/accounting logic (untested)
- Flight management system (untested)
- Member management (untested)

To reach 75% target, need 200+ tests covering critical business logic.

---

## ✅ Action Items

### Immediate (Phase 1.2 - Week 1-2)
- [ ] Uncomment test suites in `phpunit-coverage.xml`
- [ ] Run `./run-coverage.sh` to verify all 119 tests pass
- [ ] Update coverage baseline in test plan
- [ ] Accept slower execution (~40-60s) for comprehensive coverage

### Alternative (if speed is critical)
- [ ] Keep `run-coverage.sh` as-is (fast, 38 tests)
- [ ] Create `run-coverage-full.sh` for complete analysis
- [ ] Use fast coverage during development
- [ ] Use full coverage before commits/CI/CD

---

## 📝 Files to Modify

1. **phpunit-coverage.xml** (lines 13-23)
   - Uncomment EnhancedTests
   - Uncomment IntegrationTests  
   - Uncomment MySqlTests

2. **doc/development/plan_test.md** (Phase 1.2)
   - Mark "Activate existing tests" as complete
   - Update coverage baseline from 0.36% to actual %

3. **TESTING.md** (optional)
   - Update test count from 38 to 119
   - Note performance impact

---

**Generated:** 2025-10-04  
**Current Status:** run-coverage.sh covers 32% of available tests (38/119)
