#!/bin/bash

# GVV Complete Test Suite Runner
# Runs unit, integration, and enhanced CodeIgniter tests

set -e  # Exit on any error

echo "🧪 GVV MySql Test Suite"
echo "======================="

# Source environment for PHP 7.4
source setenv.sh


# Run unit tests
echo ""
echo "🔹 Running MySQL Integration Test (Configuration Model)..."
echo "==========================================================="
# phpunit --configuration phpunit_mysql.xml application/tests/integration/ConfigurationModelMySqlTest.php
phpunit --configuration phpunit_mysql.xml application/tests/integration/AchatsModelMySqlTest.php

