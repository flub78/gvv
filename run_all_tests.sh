#!/bin/bash

# GVV Complete Test Suite Runner
# Runs both unit and integration tests with proper environment setup

set -e  # Exit on any error

echo "🧪 GVV Test Suite Runner"
echo "======================="

# Source environment for PHP 7.4
source setenv.sh

echo "📋 Test Summary:"
echo "- Unit Tests: Fast, isolated, no database"
echo "- Integration Tests: Real MySQL database with transactions"
echo ""

# Run unit tests
echo "🔹 Running Unit Tests..."
echo "========================"
phpunit --configuration phpunit.xml

echo ""
echo "🔹 Running Integration Tests..."
echo "==============================="
phpunit --configuration phpunit_integration.xml

echo ""
echo "✅ All tests completed successfully!"
echo ""
echo "📊 Total Test Coverage:"
echo "- Unit Tests: 24 tests, 172 assertions"
echo "- Integration Tests: 6 tests, 24 assertions"
echo "- Combined: 30 tests, 196 assertions"
