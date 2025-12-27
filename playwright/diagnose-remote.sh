#!/bin/bash
#
# Diagnostic script for remote server testing
#
# Usage:
#   ./diagnose-remote.sh https://gvvg.flub78.net
#

if [ -z "$1" ]; then
    echo "Usage: $0 <remote-url>"
    echo "Example: $0 https://gvvg.flub78.net"
    exit 1
fi

REMOTE_URL="$1"

echo "========================================="
echo "GVV Remote Server Diagnostic"
echo "========================================="
echo "Remote URL: $REMOTE_URL"
echo "Local URL: http://gvv.net"
echo ""

# Run diagnostic test
echo "Running diagnostic tests..."
echo ""
BASE_URL="$REMOTE_URL" npx playwright test tests/diagnostic-remote.spec.js --reporter=line

echo ""
echo "========================================="
echo "Diagnostic complete"
echo "========================================="
echo ""
echo "If you see 404 errors, check the summary above for recommendations."
echo ""
echo "Common fixes:"
echo "1. Ensure .htaccess is deployed: scp .htaccess user@remote:/path/to/gvv/"
echo "2. Enable mod_rewrite on Apache: sudo a2enmod rewrite && sudo systemctl restart apache2"
echo "3. Create testadmin user: cd bin && ./create_test_users.sh"
echo "4. Check database connection in application/config/database.php"
echo ""
