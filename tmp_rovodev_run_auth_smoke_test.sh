#!/bin/bash

# Run the Authorization Smoke Test
# This script sets up the environment and runs the authorization smoke tests

echo "========================================="
echo "Authorization Smoke Test Runner"
echo "DUAL-PHASE: Legacy + V2 Systems"
echo "========================================="
echo ""

# Set environment (PHP 7.4)
source setenv.sh

# Check if test users exist
echo "Checking for test users..."
DB_HOST="${MYSQL_HOST:-localhost}"
DB_USER="${MYSQL_USER:-gvv_user}"
DB_PASS="${MYSQL_PASSWORD:-lfoyfgbj}"
DB_NAME="${MYSQL_DATABASE:-gvv2}"

# Check if testuser exists
USER_COUNT=$(mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -sN -e \
    "SELECT COUNT(*) FROM users WHERE username IN ('testuser', 'testadmin', 'testplanchiste', 'testca', 'testbureau', 'testtresorier')")

if [ "$USER_COUNT" -lt 6 ]; then
    echo "⚠️  Not all test users found ($USER_COUNT/6). Creating missing test users..."
    ./bin/create_test_users.sh
    echo ""
else
    echo "✓ All test users found ($USER_COUNT/6)"
    echo ""
fi

# Run the authorization smoke test
echo "Running Authorization Smoke Test..."
echo "========================================="

# Run the authorization smoke test with dedicated configuration
vendor/bin/phpunit \
    --configuration phpunit_authorization_smoke.xml \
    --testsuite AuthorizationSmokeTests \
    --verbose

echo ""
echo "========================================="
echo "Authorization Smoke Test completed"
echo "========================================="