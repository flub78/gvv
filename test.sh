#!/bin/bash
# test.sh - Run all GVV tests and update the dashboard

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

START=$(date +%s)

source setenv.sh

./run-all-tests.sh "$@"
PHPUNIT_RC=$?

cd playwright && npm test
PLAYWRIGHT_RC=$?
cd "$SCRIPT_DIR"

./test-status.sh

ELAPSED=$(( $(date +%s) - START ))
printf "\nTemps total : %dm%02ds\n" $((ELAPSED / 60)) $((ELAPSED % 60))

[ $PHPUNIT_RC -ne 0 ] && exit $PHPUNIT_RC
exit $PLAYWRIGHT_RC
