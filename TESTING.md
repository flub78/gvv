# GVV Testing Guide

Quick reference for running PHPUnit tests in the GVV project.

## 🚀 Quick Start

### Run Tests (Fast - No Coverage)
```bash
./run-tests.sh
```
- **Speed:** ~100-150ms
- **Use when:** Development, quick verification
- **Output:** Test results only

### Run Tests with Coverage (Slow)
```bash
./run-coverage.sh
```
- **Speed:** ~20 seconds
- **Use when:** Before commits, checking coverage
- **Output:** Test results + HTML coverage report

### View Coverage Report
```bash
# Open in browser
firefox build/coverage/index.html
# or
open build/coverage/index.html
```

## 📋 Test Commands

### Basic Commands

```bash
# All tests (fast)
./run-tests.sh

# All tests with coverage
./run-coverage.sh

# Specific test file
./run-tests.sh application/tests/unit/helpers/ValidationHelperTest.php

# Specific test method
./run-tests.sh --filter testEmailValidation
```

### Advanced Usage

```bash
# Run with detailed output
./run-tests.sh --testdox

# Run specific test suite
./run-tests.sh --testsuite WorkingTests

# Stop on first failure
./run-tests.sh --stop-on-failure

# Coverage for specific directory
./run-coverage.sh application/tests/unit/
```

## 📊 Current Test Status

- **Total Tests:** 38
- **Assertions:** 220
- **Coverage:** 0.36% (baseline)
- **Status:** ✅ All passing

### Test Categories

1. **Unit Tests** (24 tests)
   - Helpers: ValidationHelperTest, DebugExampleTest
   - Models: ConfigurationModelTest
   - Libraries: BitfieldTest
   - i18n: LanguageCompletenessTest

2. **Controller Tests** (8 tests)
   - ConfigurationControllerTest
   - ControllerTest

3. **Enhanced Tests** (Not yet activated)
   - AssetsHelperTest
   - ButtonLibraryTest
   - CsvHelperTest
   - FormElementsHelperTest
   - LogLibraryTest
   - WidgetLibraryTest

4. **Integration Tests** (Not yet activated)
   - CategorieModelIntegrationTest
   - FormElementsIntegrationTest
   - GvvmetadataTest
   - SmartAdjustorCorrelationIntegrationTest

5. **MySQL Tests** (Not yet activated)
   - ConfigurationModelMySqlTest

## 🔧 Configuration Files

- **phpunit.xml** - Regular tests (no coverage)
- **phpunit-coverage.xml** - Tests with code coverage
- **run-tests.sh** - Fast test runner
- **run-coverage.sh** - Coverage report generator

## 📈 Coverage Reports

### Report Locations
- **HTML Report:** `build/coverage/index.html`
- **Clover XML:** `build/logs/clover.xml`
- **JUnit XML:** `build/logs/junit.xml`
- **TestDox:** `build/logs/testdox.txt`

### Coverage Baseline
- **Overall:** 0.36% (3,882 / 1,091,140 lines)
- **Bitfield Library:** 100% ✅

### Known Limitations
Some legacy controllers are excluded from coverage due to method signature compatibility issues:
- `application/controllers/achats.php`
- `application/controllers/vols_planeur.php`
- `application/controllers/vols_avion.php`

These will be refactored in future updates.

## 🛠️ Technical Details

### PHP Environment
- **PHP Version:** 7.4.33 (via `/usr/bin/php7.4`)
- **PHPUnit Version:** 8.5.44
- **Coverage Driver:** Xdebug 3.1.6
- **Coverage Mode:** Set automatically by `run-coverage.sh`

### Performance
| Operation | Time | Notes |
|-----------|------|-------|
| Fast tests | ~100ms | No coverage, development use |
| Coverage tests | ~20s | Full analysis, pre-commit use |
| Coverage report | ~7s | HTML generation |

### Why Two Scripts?

**run-tests.sh (Fast)**
- No coverage analysis
- Quick feedback loop
- Use during development
- ~200x faster

**run-coverage.sh (Slow)**
- Full coverage analysis
- Detailed reports
- Use before commits
- Requires Xdebug

## 📝 Writing Tests

### Test Structure
```php
<?php

use PHPUnit\Framework\TestCase;

class MyComponentTest extends TestCase
{
    public function testSomething()
    {
        // Arrange
        $input = 'test';

        // Act
        $result = my_function($input);

        // Assert
        $this->assertEquals('expected', $result);
    }
}
```

### Test Locations
```
application/tests/
├── unit/              # Unit tests (no dependencies)
│   ├── helpers/
│   ├── models/
│   ├── libraries/
│   └── i18n/
├── controllers/       # Controller tests
├── integration/       # Integration tests (with dependencies)
├── mysql/            # Database integration tests
└── enhanced/         # Enhanced unit tests
```

## 🎯 Next Steps

See [doc/development/plan_test.md](doc/development/plan_test.md) for:
- Comprehensive test plan
- Coverage targets (75% goal)
- Progressive implementation roadmap
- Test development guidelines

### Immediate Next Steps
1. Activate enhanced test suites
2. Add MySQL integration tests to pipeline
3. Develop tests for critical models (Members, Flights, Billing)

## 🐛 Troubleshooting

### Tests Not Running
```bash
# Check PHP version
/usr/bin/php7.4 --version

# Verify PHPUnit
/usr/bin/php7.4 vendor/bin/phpunit --version

# Check Xdebug
/usr/bin/php7.4 -m | grep xdebug
```

### Coverage Not Generating
```bash
# Ensure Xdebug is loaded
/usr/bin/php7.4 -m | grep xdebug

# Run with explicit mode setting
XDEBUG_MODE=coverage /usr/bin/php7.4 vendor/bin/phpunit --configuration phpunit-coverage.xml
```

### Slow Test Performance
- Use `./run-tests.sh` for development (no coverage)
- Only run coverage when needed
- Consider running coverage nightly in CI/CD

## 📚 Resources

- **Test Plan:** [doc/development/plan_test.md](doc/development/plan_test.md)
- **Controller Testing:** [doc/development/controller_testing.md](doc/development/controller_testing.md)
- **PHPUnit Docs:** https://phpunit.de/
- **Xdebug Docs:** https://xdebug.org/

---

**Last Updated:** 2025-10-04
**Maintained by:** Development Team
