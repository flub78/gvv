#!/bin/bash
# PHPUnit Test Runner (Fast - No Coverage)
# For tests with coverage, use: ./run-coverage.sh

# Colors for output
GREEN='\033[0;32m'
NC='\033[0m' # No Color

echo "Running PHPUnit tests (fast mode - no coverage)..."
echo ""

# Use PHP 7.4 for compatibility
/usr/bin/php7.4 vendor/bin/phpunit "$@"

EXITCODE=$?

echo ""
if [ $EXITCODE -eq 0 ]; then
    echo -e "${GREEN}✓ All tests passed${NC}"
    echo ""
    echo "Tip: Run './run-coverage.sh' to generate code coverage report"
fi

exit $EXITCODE
