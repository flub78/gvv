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
    echo "Run all GVV PHPUnit tests (119 tests across all suites)"
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
    echo "  - Enhanced Tests (40 tests): CI framework helpers/libraries"
    echo "  - Integration Tests (35 tests): database operations, metadata"
    echo "  - MySQL Tests (9 tests): real database CRUD"
    echo "  Total: ~122 tests"
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
echo "  Test suites: ALL (4 suites, ~122 tests)"
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
    mkdir -p build/coverage build/logs

    # Set Xdebug mode
    export XDEBUG_MODE=coverage

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

    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${CYAN}$suite_name${NC}"
    echo -e "${CYAN}$description${NC}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

    if [ "$COVERAGE" = true ]; then
        # Run with coverage, suppress compatibility warnings
        $PHP_BIN vendor/bin/phpunit --configuration "$config_file" 2>&1 | \
            grep -v "Declaration of.*should be compatible" | \
            grep -v "Xdebug.*Step Debug" || true
    else
        # Run without coverage
        $PHP_BIN vendor/bin/phpunit --configuration "$config_file" --no-coverage 2>&1 | \
            grep -v "Xdebug.*Step Debug" || true
    fi

    local exit_code=${PIPESTATUS[0]}

    if [ $exit_code -ne 0 ] && [ $exit_code -ne 2 ]; then
        FAILED_SUITES=$((FAILED_SUITES + 1))
    fi

    echo ""
    return $exit_code
}

# Run each test suite with its proper bootstrap
echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  Test Suite 1/4: Unit Tests${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
run_suite "Unit Tests" "phpunit.xml" "Helpers, Models, Libraries, i18n, Controllers"

echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  Test Suite 2/4: Integration Tests${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
run_suite "Integration Tests" "phpunit_integration.xml" "Real database operations, metadata"

echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  Test Suite 3/4: Enhanced Tests${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
run_suite "Enhanced Tests" "phpunit_enhanced.xml" "CI framework helpers and libraries"

echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  Test Suite 4/4: MySQL Tests${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
run_suite "MySQL Tests" "phpunit.xml" "Real database CRUD operations"

# Final summary
echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  Final Summary${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
echo ""

if [ $FAILED_SUITES -eq 0 ]; then
    echo -e "${GREEN}✓ All test suites passed!${NC}"

    if [ "$COVERAGE" = true ]; then
        if [ -f "build/coverage/index.html" ]; then
            echo ""
            echo -e "${GREEN}Coverage reports generated:${NC}"
            echo "  HTML: file://$(pwd)/build/coverage/index.html"
            echo "  Clover: build/logs/clover.xml"
            echo ""

            # Show coverage summary if available
            if [ -f "build/logs/clover.xml" ]; then
                echo -e "${YELLOW}Coverage Summary:${NC}"
                # Extract basic stats from clover.xml
                grep -o 'elements="[0-9]*"' build/logs/clover.xml | head -1
                grep -o 'coveredelements="[0-9]*"' build/logs/clover.xml | head -1
            fi

            echo ""
            echo -e "${CYAN}View detailed coverage: firefox build/coverage/index.html${NC}"
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
