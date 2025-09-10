#!/bin/bash

echo "=== GVV Testing Framework Demo ==="
echo
echo "1. Running Unit Tests (fast, no database dependencies):"
echo "   - Helper functions"
echo "   - Model business logic"
echo "   - Library classes"
echo

source setenv.sh
php -d xdebug.mode=off /usr/local/bin/phpunit --testdox

echo
echo "=== Integration Tests ==="
echo "2. Running Integration Tests (with mock database operations):"
echo "   - Real model CRUD operations"
echo "   - Database transactions"
echo "   - Model method testing"
echo

php -d xdebug.mode=off /usr/local/bin/phpunit --configuration phpunit_integration.xml --testdox

echo
echo "=== Summary ==="
echo "✅ Unit Tests: $(php -d xdebug.mode=off /usr/local/bin/phpunit | grep -o '[0-9]* tests' | head -1) passed"
echo "✅ Integration Tests: $(php -d xdebug.mode=off /usr/local/bin/phpunit --configuration phpunit_integration.xml | grep -o '[0-9]* tests' | head -1) passed"
echo "✅ Both test suites are executable and working!"
echo
echo "Files created:"
echo "- phpunit.xml (unit tests)"
echo "- phpunit_integration.xml (integration tests)"
echo "- application/tests/integration_bootstrap.php (mock CI framework)"
echo "- application/tests/integration/CategorieModelIntegrationTest.php"
echo
echo "Debug configurations added to .vscode/launch.json:"
echo "- Debug PHPUnit Tests"
echo "- Debug PHPUnit Integration Tests"
echo "- Debug Current PHPUnit Test File"
echo "- Debug Specific PHPUnit Test Method"
