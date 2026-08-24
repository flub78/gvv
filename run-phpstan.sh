#!/bin/bash
# GVV PHPStan Runner
# Runs PHPStan static analysis with a memory limit high enough to avoid
# the default 128M CLI limit crashing the parallel workers.

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

source "$SCRIPT_DIR/setenv-php8.sh"

php "$SCRIPT_DIR/vendor/bin/phpstan.phar" analyse --memory-limit=512M "$@"
