#!/bin/bash

# GVV Export Testing Script
# Runs comprehensive tests for all CSV and PDF export functionality

echo "╔════════════════════════════════════════╗"
echo "║  GVV Export Testing Suite             ║"  
echo "╚════════════════════════════════════════╝"
echo ""

# Check if we're in the right directory
if [ ! -f "setenv.sh" ]; then
    echo "Error: Please run this script from the GVV root directory"
    exit 1
fi

# Source environment
source setenv.sh

# Create build directories if they don't exist
mkdir -p build/logs

echo "Configuration:"
echo "  PHP: $(php --version | head -n1)"
echo "  pdftotext: $(which pdftotext || echo 'NOT FOUND')"
echo "  Test directory: application/tests/integration/exports/"
echo ""

# Check for pdftotext
if ! command -v pdftotext &> /dev/null; then
    echo "Warning: pdftotext not found. PDF content validation tests will be skipped."
    echo "Install with: sudo apt-get install poppler-utils"
    echo ""
fi

echo "═══════════════════════════════════════════════════"
echo "  Running Export Tests"
echo "═══════════════════════════════════════════════════"

# Run the export tests
./vendor/bin/phpunit --configuration phpunit_exports.xml

exit_code=$?

echo ""
echo "═══════════════════════════════════════════════════"
echo "  Export Test Results"
echo "═══════════════════════════════════════════════════"

if [ $exit_code -eq 0 ]; then
    echo "✓ All export tests passed!"
    echo ""
    echo "Test coverage report: build/logs/testdox_exports.txt"
    echo "JUnit report: build/logs/junit_exports.xml"
else
    echo "✗ Some export tests failed (exit code: $exit_code)"
    echo ""
    echo "Check the test output above for details"
fi

echo ""
echo "To run specific export test categories:"
echo "  Financial exports: ./vendor/bin/phpunit application/tests/integration/exports/FinancialExportsTest.php"
echo "  Flight data exports: ./vendor/bin/phpunit application/tests/integration/exports/FlightDataExportsTest.php"
echo ""

exit $exit_code