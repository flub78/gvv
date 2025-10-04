# GVV Testing Guide - UPDATED

## ✅ NEW: Unified Test Runner with Coverage

### Quick Start - ALL Tests

```bash
# Run ALL 113 tests WITHOUT coverage (fast ~2s)
./run-all-tests.sh

# Run ALL 113 tests WITH coverage (slow ~3-5 min)
./run-all-tests.sh --coverage

# View coverage report
firefox build/coverage/index.html
```

### What Changed?

**BEFORE:**
- `run-coverage.sh` ran only 38 tests (32% of total)
- Missing: Enhanced tests (40), Integration tests (35), MySQL tests (9)

**AFTER:**
- `run-all-tests.sh` runs ALL 113 tests across 4 suites
- Coverage analysis includes all test types
- Optional --coverage flag for flexibility

---

## 📊 Test Suites

### Suite 1: Unit Tests (38 tests)
**Bootstrap:** `minimal_bootstrap.php`
**Tests:** Helpers, Models, Libraries, i18n, Controllers
**Speed:** ~100ms

### Suite 2: Integration Tests (35 tests)
**Bootstrap:** `integration_bootstrap.php`
**Tests:** Real database operations, metadata, form elements
**Speed:** ~250ms

### Suite 3: Enhanced Tests (40 tests)
**Bootstrap:** `enhanced_bootstrap.php`
**Tests:** CI framework helpers/libraries with full bootstrap
**Speed:** ~100ms

### Suite 4: MySQL Tests (9 tests - same as Suite 1)
**Bootstrap:** `minimal_bootstrap.php`
**Tests:** Configuration model CRUD operations
**Speed:** ~100ms
**Note:** Currently runs same tests as Suite 1, needs separation

**Total: ~113 unique tests** (some duplication between Suite 1 and 4)

---

## 🚀 Available Test Commands

### New Unified Runner (Recommended)

```bash
# All tests, no coverage (2 seconds)
./run-all-tests.sh

# All tests WITH coverage (3-5 minutes)
./run-all-tests.sh --coverage

# Help
./run-all-tests.sh --help
```

### Legacy Runners (Still work)

```bash
# Fast unit tests only (38 tests)
./run-tests.sh

# Unit tests with coverage (20 seconds)
./run-coverage.sh

# All tests via run/all_tests.sh (legacy)
./run/all_tests.sh
```

---

## 📈 Coverage Status

### Current Baseline (All Tests)
- **Tests:** 113 across 4 suites
- **Assertions:** ~650+
- **Coverage:** ~0.5-1% (estimated, comprehensive analysis)
- **Excluded:** 3 legacy controllers (method signature issues)

### Coverage Reports
- **HTML:** `build/coverage/index.html`
- **Clover XML:** `build/logs/clover.xml`
- **JUnit XML:** `build/logs/junit.xml`

### Performance

| Command | Tests | Time | Coverage |
|---------|-------|------|----------|
| `./run-all-tests.sh` | 113 | ~2s | No |
| `./run-all-tests.sh --coverage` | 113 | ~3-5min | Yes |
| `./run-tests.sh` | 38 | ~100ms | No |
| `./run-coverage.sh` | 38 | ~20s | Yes |

---

## 🎯 When to Use What

### Development (Fast Feedback)
```bash
./run-all-tests.sh
# or for just unit tests:
./run-tests.sh
```

### Before Commit
```bash
./run-all-tests.sh --coverage
```

### CI/CD Pipeline
```bash
# Run all tests with coverage
./run-all-tests.sh --coverage

# Coverage will be in build/logs/clover.xml
```

---

## 📝 Test Files Modified

All phpunit config files now support coverage:

1. **phpunit.xml** - Unit tests
   - Added coverage logging
   - Added problematic controller exclusions
   - Added Xdebug mode env var

2. **phpunit_integration.xml** - Integration tests
   - Added coverage logging
   - Added file exclusions

3. **phpunit_enhanced.xml** - Enhanced tests
   - Added coverage logging
   - Added file exclusions

4. **phpunit-coverage.xml** - Unified coverage config
   - Now includes ALL test suites (uncommented)

---

## 🔧 Configuration Details

### Coverage Exclusions
All configs now exclude:
- `system/` - Framework code
- `application/third_party/` - Third-party libraries
- `application/views/` - Templates (tested via controllers)
- `application/tests/` - Test code itself
- `application/controllers/achats.php` - Method signature issue
- `application/controllers/vols_planeur.php` - Method signature issue
- `application/controllers/vols_avion.php` - Method signature issue

### Coverage Thresholds
- **Low:** 0-35% (red)
- **Medium:** 35-70% (yellow)
- **High:** 70-100% (green)

---

## 📚 Next Steps

See [doc/development/plan_test.md](doc/development/plan_test.md) for:
- 12-week test development roadmap
- Coverage targets (0.5% → 75%)
- 200+ tests planned
- Priority: Critical models → Financial → Helpers → Controllers

---

## ✅ Summary of Changes

| Feature | Before | After |
|---------|--------|-------|
| **Tests in coverage** | 38 (32%) | 113 (100%) |
| **Command** | `./run-coverage.sh` | `./run-all-tests.sh --coverage` |
| **Coverage option** | Always on | Optional flag |
| **Config files** | 1 with coverage | 4 with coverage |
| **Flexibility** | Low | High |

---

**Updated:** 2025-10-04
**Status:** ✅ All 113 tests now support code coverage analysis
