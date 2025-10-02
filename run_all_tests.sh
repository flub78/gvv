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
echo "🔹 Running MySQL Integration Test (Configuration Model)..."
echo "==========================================================="
phpunit --configuration phpunit_mysql.xml

echo ""
echo "✅ All test categories completed!"
echo ""
echo "📊 Test Coverage Summary:"
echo "- Unit Tests: 24 tests (validation, models, libraries)"
echo "- Integration Tests: 6 tests (real database operations)"
echo "- Enhanced Tests: 41 tests (CI helpers and libraries)"
echo "- MySQL Integration: 9 tests (Configuration model with real MySQL)"
echo "- Total: 80 tests across all categories"
