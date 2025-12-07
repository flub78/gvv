#!/bin/bash

###############################################################################
# run-playwright.sh
#
# Script to run Playwright tests for GVV with proper prerequisites:
# 1. Source PHP 7.4 environment
# 2. Create test users if they don't exist
# 3. Check for CAPTCHA on login page (from failed login attempts)
# 4. Run Playwright tests if no CAPTCHA is present
#
# Usage:
#   ./run-playwright.sh [playwright-args]
#
# Examples:
#   ./run-playwright.sh                    # Run all tests
#   ./run-playwright.sh --reporter=line    # Run with line reporter
#   ./run-playwright.sh tests/auth-login.spec.js  # Run specific test
###############################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Get the script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo "========================================="
echo "GVV Playwright Test Runner"
echo "========================================="
echo ""

# Step 1: Source environment to use PHP 7.4
if [ -f "setenv.sh" ]; then
    echo -e "${GREEN}✓${NC} Sourcing environment (PHP 7.4)..."
    source setenv.sh
else
    echo -e "${RED}✗ Error: setenv.sh not found${NC}"
    exit 1
fi

# Step 2: Create test users if they don't exist
echo ""
echo "========================================="
echo "Verifying test users"
echo "========================================="
if [ -f "bin/create_test_users.sh" ]; then
    bash bin/create_test_users.sh
    if [ $? -ne 0 ]; then
        echo -e "${RED}✗ Error: Failed to create test users${NC}"
        exit 1
    fi
else
    echo -e "${RED}✗ Error: bin/create_test_users.sh not found${NC}"
    exit 1
fi

# Step 3: Check for CAPTCHA on login page
echo ""
echo "========================================="
echo "Checking for CAPTCHA on login page"
echo "========================================="

# Get the login page
LOGIN_URL="http://gvv.net/auth/login"
LOGIN_PAGE=$(curl -s "$LOGIN_URL")

# Check if CAPTCHA image is present
# CAPTCHA images are served from /captcha/ directory with pattern: /captcha/TIMESTAMP.png
if echo "$LOGIN_PAGE" | grep -q 'src="http://gvv.net/captcha/[0-9]*\.[0-9]*\.png"'; then
    echo -e "${RED}✗ CAPTCHA DETECTED on login page!${NC}"
    echo ""
    echo "A CAPTCHA is currently displayed on the login page due to previous failed login attempts."
    echo "This prevents automated testing from running."
    echo ""
    echo "To clear the CAPTCHA:"
    echo "  1. Open http://gvv.net/auth/login in your browser"
    echo "  2. Complete the CAPTCHA manually"
    echo "  3. OR wait for the CAPTCHA to expire"
    echo "  4. OR clear the login attempt counter from the database:"
    echo ""
    echo "     mysql -h localhost -u gvv_user -plfoyfgbj gvv2 -e \\"
    echo "       \"UPDATE login_attempts SET login_attempts = 0 WHERE ip_address = '127.0.0.1'\""
    echo ""
    exit 1
elif echo "$LOGIN_PAGE" | grep -qi 'captcha'; then
    echo -e "${YELLOW}⚠${NC}  Warning: Word 'captcha' found in page, but no image detected"
    echo "     Proceeding with tests, but verify if needed..."
else
    echo -e "${GREEN}✓${NC} No CAPTCHA detected - safe to run tests"
fi

# Step 4: Run Playwright tests
echo ""
echo "========================================="
echo "Running Playwright tests"
echo "========================================="
echo ""

cd playwright

# Pass all arguments to playwright test command
# If no arguments provided, run all tests
if [ $# -eq 0 ]; then
    echo "Running all Playwright tests..."
    npx playwright test --reporter=line
else
    echo "Running Playwright tests with args: $@"
    npx playwright test "$@"
fi

TEST_EXIT_CODE=$?

cd ..

echo ""
echo "========================================="

if [ $TEST_EXIT_CODE -eq 0 ]; then
    echo -e "${GREEN}✓ All tests passed successfully${NC}"
    echo "========================================="
    exit 0
else
    echo -e "${RED}✗ Some tests failed (exit code: $TEST_EXIT_CODE)${NC}"
    echo "========================================="
    echo ""
    echo "To view the test report:"
    echo "  cd playwright && npx playwright show-report"
    exit $TEST_EXIT_CODE
fi
