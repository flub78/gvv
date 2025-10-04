# GVV PHPUnit Test Plan

## 📊 Executive Summary

This document provides a comprehensive test plan for the GVV (Gestion Vol à voile) application, tracking current test coverage and prioritizing future test development to ensure code quality and reliability.

**Current Status:**
- ✅ **38 tests passing** across 220 assertions
- ✅ **4 test types** implemented: Unit, Integration, MySQL, Controllers
- ⚠️ **Code coverage**: Not yet configured (requires Xdebug or PCOV)
- 📈 **Test infrastructure**: Mature and functional

---

## 📋 Current Test Inventory

### 1. Unit Tests (24 tests)

#### 1.1 Helpers (11 tests)
- ✅ `ValidationHelperTest` (9 tests)
  - Date conversions (DB↔HT)
  - French date comparisons
  - Time conversions (minute/decimal)
  - Euro formatting
  - Email validation
- ✅ `DebugExampleTest` (2 tests)
  - Basic debugging
  - Helper debugging

#### 1.2 Models (6 tests)
- ✅ `ConfigurationModelTest` (6 tests)
  - Image method logic
  - Key validation
  - Value sanitization
  - Language parameters
  - Categories
  - Priority handling

#### 1.3 Libraries (9 tests)
- ✅ `BitfieldTest` (9 tests)
  - Constructor
  - String conversion
  - Bit operations
  - Type conversions
  - Serialization
  - Iterator
  - Edge cases
  - Complex scenarios

#### 1.4 i18n (6 tests)
- ✅ `LanguageCompletenessTest` (6 tests)
  - Directory structure
  - English completeness
  - Dutch completeness
  - Translation key coverage

### 2. Enhanced Unit Tests (0 tests - not yet in test suite)
Files exist but not activated in phpunit.xml:
- `AssetsHelperTest` (7 tests potential)
- `ButtonLibraryTest`
- `CsvHelperTest`
- `FormElementsHelperTest`
- `LogLibraryTest`
- `WidgetLibraryTest`

### 3. Integration Tests (0 tests - exist but not activated)
- `AssetsHelperIntegrationTest`
- `CategorieModelIntegrationTest` (5 tests potential)
- `FormElementsIntegrationTest`
- `GvvmetadataTest`
- `SmartAdjustorCorrelationIntegrationTest`

### 4. MySQL Tests (9 tests - not in current test suite)
- ✅ `ConfigurationModelMySqlTest` (9 tests)
  - CREATE operations
  - UPDATE operations
  - DELETE operations
  - get_param() method
  - image() method
  - Language/club priority
  - Transaction rollback
  - Multiple operations
  - select_page() method

### 5. Controller Tests (8 tests)
- ✅ `ControllerTest` (2 tests)
  - Load controller from subfolder
  - Load non-existent controller
- ✅ `ConfigurationControllerTest` (6 tests)
  - JSON output parsing
  - HTML output parsing
  - CSV output parsing
  - HTTP status codes
  - Response headers
  - Form validation logic

---

## 🎯 Test Coverage Gaps Analysis

### Application Components (Total: 48 controllers, 37 models, 17 helpers, 34 libraries)

#### Critical Components WITHOUT Tests:

**High Priority Models (0/37 tested):**
- ❌ `membres_model` - Core member management
- ❌ `vols_planeur_model` - Glider flights (core feature)
- ❌ `vols_avion_model` - Aircraft flights
- ❌ `facturation_model` - Billing system
- ❌ `achats_model` - Purchase tracking
- ❌ `comptes_model` - Account management
- ❌ `ecritures_model` - Accounting entries
- ❌ `tarifs_model` - Pricing rules
- ❌ `planeurs_model` - Glider fleet
- ❌ `avions_model` - Aircraft fleet
- ❌ `licences_model` - License management
- ❌ `tickets_model` - Ticket system

**High Priority Controllers (2/48 tested):**
- ❌ `membre.php` - Member CRUD
- ❌ `vols_planeur.php` - Flight recording
- ❌ `vols_avion.php` - Aircraft flights
- ❌ `facturation.php` - Billing
- ❌ `achats.php` - Purchases
- ❌ `comptes.php` - Accounts
- ❌ `compta.php` - Accounting
- ❌ `tarifs.php` - Pricing
- ❌ `auth.php` - Authentication
- ❌ `admin.php` - Administration

**Critical Helpers (3/17 tested):**
- ❌ `authorization_helper` - Access control
- ❌ `database_helper` - Database utilities
- ❌ `crypto_helper` - Encryption
- ❌ `form_elements_helper` - Form generation
- ❌ `csv_helper` - CSV import/export

**Important Libraries (1/34 tested):**
- ❌ `DX_Auth` - Authentication system
- ❌ `Facturation` - Billing engine
- ❌ `Gvvmetadata` - Metadata management
- ❌ `Widget` - UI widgets
- ❌ `Button*` - Button components
- ❌ `DataTable` - Data grids
- ❌ `MetaData` - Generic metadata

---

## 📈 Progressive Implementation Plan

### Phase 1: Foundation & Coverage Setup (Week 1-2)
**Priority: CRITICAL** ✅ **COMPLETE**

#### 1.1 Enable Code Coverage ✅
- [x] Install Xdebug or PCOV PHP extension - Xdebug 3.1.6 available in PHP 7.4
- [x] Create `phpunit-coverage.xml` with coverage configuration
- [x] Create helper scripts `run-tests.sh` and `run-coverage.sh`
- [x] Run baseline coverage: `./run-coverage.sh`
- [x] Document coverage baseline: **0.36% (3,882/1,091,140 lines)**
  - Bitfield library: 100% coverage ✅

#### 1.2 Activate Existing Tests
- [ ] Add enhanced tests to `phpunit.xml`:
  ```xml
  <directory>application/tests/unit/enhanced</directory>
  <directory>application/tests/integration</directory>
  <directory>application/tests/mysql</directory>
  ```
- [ ] Run full test suite to verify all tests pass
- [ ] Fix any failures in newly activated tests
- [ ] Update coverage report

**Expected Outcome:** ~47 tests passing, baseline coverage established

---

### Phase 2: Critical Model Tests (Week 3-4)
**Priority: HIGH - Core Business Logic**

Focus on CRUD operations and business logic for critical models.

#### 2.1 Member Management
- [ ] `MembresModelTest` (Unit)
  - Member creation/update/delete
  - Data validation
  - Role assignment logic
  - Section assignment
- [ ] `MembresModelMySqlTest` (Integration)
  - Database CRUD operations
  - Query methods (search, filters)
  - Relationship integrity

#### 2.2 Flight Operations
- [ ] `VolsPlaneurModelTest` (Unit)
  - Flight data validation
  - Calculation logic (duration, billing)
  - Flight type detection
- [ ] `VolsPlaneurModelMySqlTest` (Integration)
  - Flight record CRUD
  - Flight listing/filtering
  - Statistics calculations

#### 2.3 Fleet Management
- [ ] `PlaneursModelTest` (Unit)
  - Aircraft validation
  - Status management
- [ ] `AvionsModelTest` (Unit)
  - Similar to PlaneursModelTest

**Expected Outcome:** Core data models tested, ~60-70 tests total

---

### Phase 3: Financial System Tests (Week 5-6)
**Priority: HIGH - Financial Accuracy Critical**

#### 3.1 Billing System
- [ ] `FacturationModelTest` (Unit)
  - Billing calculation logic
  - Tariff application
  - Discount rules
- [ ] `TarifsModelTest` (Unit)
  - Pricing rule validation
  - Priority logic
  - Category-based pricing

#### 3.2 Account Management
- [ ] `ComptesModelTest` (Unit)
  - Account balance calculations
  - Transaction validation
- [ ] `EcrituresModelTest` (Integration)
  - Accounting entry creation
  - Double-entry validation
  - Balance verification

#### 3.3 Ticket System
- [ ] `TicketsModelTest` (Unit)
  - Ticket validation
  - Deduction logic
  - Balance tracking

**Expected Outcome:** Financial logic tested, ~85-95 tests total

---

### Phase 4: Helper & Library Tests (Week 7-8)
**Priority: MEDIUM - Supporting Infrastructure**

#### 4.1 Security & Authorization
- [ ] `AuthorizationHelperTest` (Unit)
  - Permission checks
  - Role validation
  - Access control logic
- [ ] `CryptoHelperTest` (Unit)
  - Encryption/decryption
  - Password hashing
  - Token generation

#### 4.2 Database & CSV Helpers
- [ ] `DatabaseHelperTest` (Unit)
  - Query building
  - Data sanitization
- [ ] `CsvHelperEnhancedTest` (Unit)
  - CSV export logic
  - CSV import parsing
  - Data transformation

#### 4.3 Critical Libraries
- [ ] `DXAuthTest` (Integration)
  - Login/logout flow
  - Session management
  - Password recovery
- [ ] `GvvmetadataTest` (Unit)
  - Metadata CRUD
  - Field validation

**Expected Outcome:** Helpers/libraries tested, ~110-125 tests total

---

### Phase 5: Controller Integration Tests (Week 9-10)
**Priority: MEDIUM - User Interface Integration**

#### 5.1 Core Controllers
- [ ] `MembreControllerTest`
  - Member CRUD operations
  - Form validation
  - Output rendering (HTML/JSON)
- [ ] `VolsPlaneurControllerTest`
  - Flight entry forms
  - Flight list rendering
  - Data validation

#### 5.2 Financial Controllers
- [ ] `FacturationControllerTest`
  - Billing interface
  - Invoice generation
  - Payment recording
- [ ] `ComptaControllerTest`
  - Accounting interface
  - Report generation
  - Data export

#### 5.3 Admin Controllers
- [ ] `AdminControllerTest`
  - Configuration management
  - User administration
  - System tools
- [ ] `AuthControllerTest`
  - Login/logout
  - Password recovery
  - Session handling

**Expected Outcome:** Main user workflows tested, ~140-160 tests total

---

### Phase 6: Feature Tests & Edge Cases (Week 11-12)
**Priority: LOW - Comprehensive Coverage**

#### 6.1 Complex Workflows (Feature Tests)
- [ ] Complete flight recording workflow
  - From entry to billing to accounting
  - Multi-step validation
- [ ] Member registration to first flight
  - User creation → license validation → flight authorization
- [ ] Billing cycle end-to-end
  - Flight records → tariff application → invoice → payment → accounting

#### 6.2 Edge Cases & Error Handling
- [ ] Invalid data handling
- [ ] Concurrent access scenarios
- [ ] Database constraint violations
- [ ] Authentication edge cases
- [ ] Billing calculation edge cases

#### 6.3 Import/Export Functions
- [ ] FFVP integration tests
- [ ] GESASSO export tests
- [ ] Data migration tests
- [ ] Backup/restore tests

**Expected Outcome:** Comprehensive coverage, ~180-200+ tests total

---

## 🔧 Testing Infrastructure Improvements

### Code Coverage Monitoring

#### ✅ Coverage Setup - COMPLETE

**Status:** Coverage is now configured and working!

**Current Baseline:** 0.36% line coverage (3,882/1,091,140 lines)
- Bitfield library: 100% coverage (40/40 lines)

#### Quick Start:
```bash
# Run tests WITHOUT coverage (fast - ~100ms)
./run-tests.sh

# Run tests WITH coverage (slower - ~20 seconds)
./run-coverage.sh

# View coverage report
open build/coverage/index.html
# Or: firefox build/coverage/index.html
```

#### Setup Details:

1. **Coverage Driver:** ✅ Xdebug 3.1.6 installed (PHP 7.4)

2. **Configuration Files:**
   - `phpunit.xml` - Regular tests (fast, no coverage)
   - `phpunit-coverage.xml` - Coverage analysis (slower)
   - `run-tests.sh` - Quick test runner
   - `run-coverage.sh` - Coverage report generator

3. **PHP Version:**
   - Uses PHP 7.4 (via `/usr/bin/php7.4`)
   - PHPUnit 8.5.44 supports coverage on PHP 7.4
   - Xdebug mode automatically set to "coverage" by run-coverage.sh

4. **Known Limitations:**
   - Some legacy controllers excluded from coverage (method signature issues):
     - `achats.php`
     - `vols_planeur.php`
     - `vols_avion.php`
   - These will be fixed in future refactoring

5. **Coverage Reports Generated:**
   - HTML: `build/coverage/index.html` (detailed, browse by file)
   - Clover XML: `build/logs/clover.xml` (for CI/CD)
   - Text summary: displayed in terminal

#### Coverage Targets:
   - **Phase 1 (Current):** 0.36% baseline established ✅
   - **Phase 2:** 40% overall, 80% critical models
   - **Phase 3:** 55% overall, 90% financial code
   - **Phase 4:** 65% overall
   - **Phase 5:** 70% overall
   - **Phase 6:** 75%+ overall

#### Performance Impact:
   - **Without coverage:** ~100-150ms
   - **With coverage:** ~20 seconds (200x slower)
   - **Recommendation:** Use `./run-tests.sh` during development, `./run-coverage.sh` before commits

### Test Data Management

#### Database Test Strategy:
- **Unit Tests:** Mock data, no database
- **Integration Tests:** Test database with transactions
- **MySQL Tests:** Real database, transaction rollback
- **Feature Tests:** Complete database with seeded data

#### Test Database Setup:
```bash
# Create test database
mysql -u root -p -e "CREATE DATABASE gvv_test;"

# Configure in application/tests/mysql_bootstrap.php
# Run migrations on test DB
```

### CI/CD Integration

#### Automated Testing:
```bash
# Pre-commit hook
phpunit --testsuite WorkingTests

# CI pipeline (GitHub Actions / GitLab CI)
phpunit --coverage-text --coverage-clover coverage.xml

# Nightly extended tests
phpunit --testsuite AllTests
```

---

## 📊 Progress Tracking

### Test Metrics Dashboard

| Metric | Current | Phase 1 | Phase 2 | Phase 3 | Phase 4 | Phase 5 | Phase 6 |
|--------|---------|---------|---------|---------|---------|---------|---------|
| **Total Tests** | 38 | 47 | 70 | 95 | 125 | 160 | 200+ |
| **Total Assertions** | 220 | 280 | 420 | 570 | 750 | 960 | 1200+ |
| **Code Coverage** | 0.36% | ✅ 0.36% | 40% | 55% | 65% | 70% | 75% |
| **Models Tested** | 1 | 1 | 8 | 12 | 15 | 18 | 25+ |
| **Controllers Tested** | 2 | 2 | 2 | 2 | 8 | 15 | 20+ |
| **Helpers Tested** | 3 | 10 | 12 | 15 | 17 | 17 | 17 |
| **Libraries Tested** | 1 | 1 | 1 | 1 | 5 | 8 | 12+ |

### Weekly Milestones

#### Week 1-2 (Foundation) ✅ COMPLETE
- [x] Test infrastructure analysis
- [x] Code coverage setup (Xdebug configured)
- [x] Coverage baseline established: 0.36%
- [ ] Activate all existing tests (next step)

#### Week 3-4 (Core Models)
- [ ] Members model tests
- [ ] Flights model tests
- [ ] Fleet model tests
- [ ] Target: 70 tests, 40% coverage

#### Week 5-6 (Financial)
- [ ] Billing tests
- [ ] Accounting tests
- [ ] Tariffs tests
- [ ] Target: 95 tests, 55% coverage

#### Week 7-8 (Helpers/Libraries)
- [ ] Authorization tests
- [ ] Security tests
- [ ] Metadata tests
- [ ] Target: 125 tests, 65% coverage

#### Week 9-10 (Controllers)
- [ ] Core controller tests
- [ ] Financial controller tests
- [ ] Admin controller tests
- [ ] Target: 160 tests, 70% coverage

#### Week 11-12 (Features)
- [ ] End-to-end workflows
- [ ] Edge cases
- [ ] Import/export
- [ ] Target: 200+ tests, 75% coverage

---

## 🎯 Test Development Guidelines

### Test Naming Conventions
- **Unit Tests:** `{Component}Test.php` (e.g., `MembresModelTest.php`)
- **Integration Tests:** `{Component}IntegrationTest.php`
- **MySQL Tests:** `{Component}MySqlTest.php`
- **Controller Tests:** `{Controller}ControllerTest.php`
- **Feature Tests:** `{Feature}FeatureTest.php`

### Test Method Naming
- Use descriptive names: `testMemberCreationWithValidData()`
- Test one thing: `testEmailValidation()`, `testDateConversion()`
- Include edge cases: `testEmptyInput()`, `testNullValue()`

### Test Structure (AAA Pattern)
```php
public function testMethodName()
{
    // Arrange - Setup test data and dependencies
    $data = ['field' => 'value'];

    // Act - Execute the method under test
    $result = $this->model->method($data);

    // Assert - Verify expected outcome
    $this->assertEquals('expected', $result);
}
```

### Test Isolation
- Use `setUp()` for common initialization
- Use `tearDown()` for cleanup
- Use database transactions for data tests
- Mock external dependencies
- Avoid test interdependencies

### Coverage Goals by Component Type
- **Models:** 90%+ (core business logic)
- **Controllers:** 70%+ (user interactions)
- **Helpers:** 85%+ (utility functions)
- **Libraries:** 80%+ (reusable components)
- **Views:** 0% (tested via controller/feature tests)

---

## 📝 Test Documentation

### Test Suite Documentation
Each test file should include:
- Class-level PHPDoc with purpose
- Test requirements (database, dependencies)
- Setup/teardown explanation
- Individual test descriptions

### Running Tests

#### Full Test Suite
```bash
phpunit
```

#### Specific Test Suites
```bash
phpunit --testsuite WorkingTests
phpunit application/tests/unit/
phpunit application/tests/integration/
phpunit application/tests/mysql/
phpunit application/tests/controllers/
```

#### Specific Test File
```bash
phpunit application/tests/unit/models/MembresModelTest.php
```

#### Specific Test Method
```bash
phpunit --filter testMemberCreation application/tests/unit/models/MembresModelTest.php
```

#### With Coverage
```bash
phpunit --coverage-html build/coverage
```

#### Verbose Output
```bash
phpunit --testdox
phpunit --verbose
```

---

## 🚀 Quick Start for New Tests

### 1. Create Unit Test Template
```php
<?php

use PHPUnit\Framework\TestCase;

class MyComponentTest extends TestCase
{
    private $component;

    public function setUp(): void
    {
        // Initialize component
        $this->component = new MyComponent();
    }

    public function testBasicFunctionality()
    {
        $result = $this->component->method();
        $this->assertEquals('expected', $result);
    }
}
```

### 2. Create MySQL Integration Test Template
```php
<?php

use PHPUnit\Framework\TestCase;

class MyModelMySqlTest extends TestCase
{
    private $CI;
    private $model;

    public function setUp(): void
    {
        $this->CI =& get_instance();
        $this->model = new My_model();
        $this->CI->db->trans_start();
    }

    public function tearDown(): void
    {
        $this->CI->db->trans_rollback();
    }

    public function testDatabaseOperation()
    {
        $id = $this->model->create(['field' => 'value']);
        $this->assertGreaterThan(0, $id);
    }
}
```

### 3. Create Controller Test Template
```php
<?php

use PHPUnit\Framework\TestCase;

class MyControllerTest extends TestCase
{
    public function testControllerOutput()
    {
        ob_start();
        $controller = new My_controller();
        $controller->method();
        $output = ob_get_clean();

        $this->assertStringContainsString('expected', $output);
    }
}
```

---

## 📅 Implementation Schedule

| Phase | Weeks | Focus Area | Tests Added | Coverage Target |
|-------|-------|------------|-------------|-----------------|
| **Phase 1** | 1-2 | Foundation & Setup | +9 | Baseline + setup |
| **Phase 2** | 3-4 | Critical Models | +23 | 40% |
| **Phase 3** | 5-6 | Financial System | +25 | 55% |
| **Phase 4** | 7-8 | Helpers & Libraries | +30 | 65% |
| **Phase 5** | 9-10 | Controllers | +35 | 70% |
| **Phase 6** | 11-12 | Features & Edge Cases | +40 | 75%+ |

**Total Duration:** 12 weeks
**Final Target:** 200+ tests, 75%+ code coverage

---

## ✅ Definition of Done

A test implementation phase is complete when:

- [ ] All planned tests are written and passing
- [ ] Code coverage target for the phase is achieved
- [ ] All tests follow naming conventions and AAA pattern
- [ ] Test documentation is complete
- [ ] No skipped or incomplete tests
- [ ] CI/CD pipeline runs successfully
- [ ] Code review completed
- [ ] Test results documented in this plan

---

## 🔄 Maintenance & Updates

### Regular Activities
- **Weekly:** Run full test suite, update progress metrics
- **Per Feature:** Add tests before merging new code
- **Monthly:** Review coverage reports, identify gaps
- **Quarterly:** Update test plan based on application changes

### Test Maintenance
- Update tests when requirements change
- Refactor tests to reduce duplication
- Archive obsolete tests
- Document known limitations

---

## 📚 Resources

### Documentation
- [Controller Testing Guide](controller_testing.md)
- PHPUnit Documentation: https://phpunit.de/
- CodeIgniter Testing: https://codeigniter.com/user_guide/testing/

### Test Data
- Sample data in `application/tests/data/`
- Mock objects in `application/tests/mocks/`
- Test databases configuration in `application/tests/*_bootstrap.php`

### Commands Reference
```bash
# Run all tests
phpunit

# Run with coverage
phpunit --coverage-html build/coverage

# Run specific suite
phpunit --testsuite WorkingTests

# Run with detailed output
phpunit --testdox

# Run specific test
phpunit --filter testMethodName
```

---

## 📈 Success Metrics

### Key Performance Indicators (KPIs)

1. **Test Coverage**
   - Target: 75% overall code coverage
   - Critical paths: 90% coverage
   - New code: 80% coverage required

2. **Test Quality**
   - All tests passing in CI/CD
   - Test execution time < 2 minutes
   - No skipped tests in main suite

3. **Bug Detection**
   - Regression bugs caught by tests: 90%+
   - Critical bugs caught before production: 100%
   - Test-driven bug fixes: Track all bugs fixed with tests

4. **Development Velocity**
   - Time to write tests decreases over time
   - Confidence in refactoring increases
   - Feature delivery with tests from day 1

---

**Document Status:** 🟢 Active
**Last Updated:** 2025-10-04
**Next Review:** After Phase 1 completion
**Owner:** Development Team
