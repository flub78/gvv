#!/bin/bash

# GVV Complete Test Suite Runner
# Runs unit, integration, and enhanced CodeIgniter tests

set -e  # Exit on any error

echo "🧪 GVV Test Suite Runner"
echo "======================="

# Source environment for PHP 7.4
source setenv.sh

echo "📋 Test Categories:"
echo "- Unit Tests: Fast, isolated, no dependencies"
echo "- Integration Tests: Real MySQL database with transactions"
echo "- Enhanced Tests: CodeIgniter helpers/libraries with mocking"
echo "- Controller Tests: Output parsing (JSON/HTML/CSV)"
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
echo "🔹 Running Enhanced CodeIgniter Tests..."
echo "======================================="
phpunit --configuration phpunit_enhanced.xml

echo ""
echo "🔹 Running Controller Tests..."
echo "=============================="
phpunit --configuration phpunit_controller.xml

echo ""
echo "✅ All test categories completed!"
echo ""
echo "📊 Test Coverage Summary:"
echo "- Unit Tests: 32 tests (validation, models, libraries, controllers)"
echo "- Integration Tests: 35 tests (real database operations, metadata)"
echo "- Enhanced Tests: 40 tests (CI helpers and libraries)"
echo "- Controller Tests: 6 tests (JSON/HTML/CSV output parsing)"
echo "- Total: 113 tests across all categories"
