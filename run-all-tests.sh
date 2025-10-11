#!/bin/bash
# GVV Unified Test Runner
# Runs all PHPUnit tests with optional code coverage

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Default values
COVERAGE=false
PHP_BIN="/usr/bin/php7.4"

# Parse command line arguments
show_help() {
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Run all GVV PHPUnit tests (125 tests across all suites)"
    echo ""
    echo "Options:"
    echo "  -c, --coverage     Generate code coverage report (slower ~60s)"
    echo "  -h, --help         Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0                 Run all tests without coverage (fast ~2s)"
    echo "  $0 --coverage      Run all tests with coverage (slow ~60s)"
    echo ""
    echo "Test Suites Included:"
    echo "  - Unit Tests (38 tests): helpers, models, libraries, i18n, controllers"
    echo "  - Integration Tests (35 tests): database operations, metadata"
    echo "  - Enhanced Tests (40 tests): CI framework helpers/libraries"
    echo "  - Controller Tests (6 tests): JSON/HTML/CSV output parsing"
    echo "  - MySQL Tests (9 tests): real database CRUD"
    echo "  Total: ~128 tests"
    exit 0
}

# Parse arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        -c|--coverage)
            COVERAGE=true
            shift
            ;;
        -h|--help)
            show_help
            ;;
        *)
            echo -e "${RED}Unknown option: $1${NC}"
            show_help
            ;;
    esac
done

# Header
echo -e "${BLUE}╔════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║  GVV Complete PHPUnit Test Suite      ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════╝${NC}"
echo ""

# Check PHP 7.4
if [ ! -f "$PHP_BIN" ]; then
    echo -e "${RED}Error: PHP 7.4 not found at $PHP_BIN${NC}"
    exit 1
fi

# Display configuration
echo -e "${YELLOW}Configuration:${NC}"
echo "  PHP: 7.4.33"
echo "  Coverage: $([ "$COVERAGE" = true ] && echo 'ENABLED' || echo 'DISABLED')"
echo "  Test suites: ALL (5 suites, ~128 tests)"
echo ""

# Track results
TOTAL_TESTS=0
TOTAL_ASSERTIONS=0
FAILED_SUITES=0

# Coverage setup
if [ "$COVERAGE" = true ]; then
    # Check Xdebug
    if ! $PHP_BIN -m | grep -q xdebug; then
        echo -e "${RED}Error: Xdebug extension required for coverage${NC}"
        exit 1
    fi

    # Create output directories
    mkdir -p build/coverage build/logs build/coverage-data

    # Set Xdebug mode
    export XDEBUG_MODE=coverage

    # Clean up old coverage data files
    rm -f build/coverage-data/*.cov

    echo -e "${YELLOW}Running ALL tests WITH code coverage...${NC}"
    echo -e "${YELLOW}(This will take 60-90 seconds)${NC}"
    echo ""
else
    echo -e "${YELLOW}Running ALL tests WITHOUT coverage (fast mode)...${NC}"
    echo ""
fi

# Function to run a test suite
run_suite() {
    local suite_name=$1
    local config_file=$2
    local description=$3
    local suite_id=$4

    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${CYAN}$suite_name${NC}"
    echo -e "${CYAN}$description${NC}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

    if [ "$COVERAGE" = true ]; then
        # Run with coverage, save to individual .cov file
        # Note: We still generate temp reports via logging config, but merge later
        $PHP_BIN vendor/bin/phpunit --configuration "$config_file" \
            --coverage-php "build/coverage-data/${suite_id}.cov" 2>&1 | \
            tee /tmp/phpunit_${suite_id}_output.txt | \
            grep -v "Declaration of.*should be compatible" | \
            grep -v "Xdebug.*Step Debug" | \
            grep -v "Generating code coverage report"
        local exit_code=${PIPESTATUS[0]}
    else
        # Run without coverage
        $PHP_BIN vendor/bin/phpunit --configuration "$config_file" --no-coverage 2>&1 | \
            tee /tmp/phpunit_${suite_id}_output.txt | \
            grep -v "Xdebug.*Step Debug"
        local exit_code=${PIPESTATUS[0]}
    fi

    # Check for test failures or errors
    # PHPUnit returns 0 for success, 1 for test failures, 2 for errors
    if [ $exit_code -ne 0 ]; then
        FAILED_SUITES=$((FAILED_SUITES + 1))
        echo -e "${RED}✗ Suite failed with exit code $exit_code${NC}"
    fi

    echo ""
    return $exit_code
}

# Run each test suite with its proper bootstrap
echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  Test Suite 1/5: Unit Tests${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
run_suite "Unit Tests" "phpunit.xml" "Helpers, Models, Libraries, i18n, Controllers" "unit"

echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  Test Suite 2/5: Integration Tests${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
run_suite "Integration Tests" "phpunit_integration.xml" "Real database operations, metadata" "integration"

echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  Test Suite 3/5: Enhanced Tests${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
run_suite "Enhanced Tests" "phpunit_enhanced.xml" "CI framework helpers and libraries" "enhanced"

echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  Test Suite 4/5: Controller Tests${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
run_suite "Controller Tests" "phpunit_controller.xml" "JSON/HTML/CSV output parsing" "controller"

echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  Test Suite 5/5: MySQL Tests${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
run_suite "MySQL Tests" "phpunit.xml" "Real database CRUD operations" "mysql"

# Final summary
echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  Final Summary${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
echo ""

if [ $FAILED_SUITES -eq 0 ]; then
    echo -e "${GREEN}✓ All test suites passed!${NC}"

    if [ "$COVERAGE" = true ]; then
        echo ""
        echo -e "${YELLOW}Merging coverage data from all test suites...${NC}"

        # Merge coverage using PHP script
        $PHP_BIN merge-coverage.php

        if [ $? -eq 0 ]; then
            echo ""
            echo -e "${GREEN}Coverage reports generated:${NC}"
            echo "  HTML: file://$(pwd)/build/coverage/index.html"
            echo "  Clover: build/logs/clover.xml"
            echo ""
            echo -e "${CYAN}View detailed coverage: firefox build/coverage/index.html${NC}"
        else
            echo -e "${RED}Error: Failed to merge coverage data${NC}"
        fi
    else
        echo ""
        echo -e "${YELLOW}Tip: Run with --coverage to generate coverage report${NC}"
    fi

    exit 0
else
    echo -e "${RED}✗ $FAILED_SUITES test suite(s) failed${NC}"
    exit 1
fi
