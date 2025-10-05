#!/bin/bash

# GVV Complete Test Suite Runner
# Runs unit, integration, and enhanced CodeIgniter tests

set -e  # Exit on any error

echo "🧪 GVV Test Suite Runner"
echo "======================="

# Source environment for PHP 7.4
source setenv.sh


# Run unit tests
echo "🔹 Running Unit Tests..."
echo "========================" 

# phpunit --configuration phpunit.xml application/tests/unit/helpers/BitfieldsHelperTest.php
phpunit --configuration phpunit.xml application/tests/unit/helpers/AssetsHelperTest.php

