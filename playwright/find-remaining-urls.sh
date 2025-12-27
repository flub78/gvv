#!/bin/bash
#
# Find any remaining URLs that might cause 404 issues
#

echo "Searching for potential URL issues..."
echo ""

echo "=== Template literals without index.php ==="
find tests/ -type f \( -name "*.js" -o -name "*.ts" \) ! -path "*/node_modules/*" -exec grep -Hn 'goto.*`/' {} \; | grep -v '/index\.php/' | grep -v '//.*goto'

echo ""
echo "=== String literals without index.php (double check) ==="
find tests/ -type f \( -name "*.js" -o -name "*.ts" \) ! -path "*/node_modules/*" -exec grep -Hn "goto.*'/" {} \; | grep -v '/index\.php/' | grep -v '//.*goto' | head -20

echo ""
echo "=== Constants that might be URLs ==="
find tests/ -type f \( -name "*.js" -o -name "*.ts" \) ! -path "*/node_modules/*" -exec grep -Hn "const.*=.*'/" {} \; | grep -v '/index\.php/' | grep -v '/tmp' | grep -v 'build/' | grep -v fixtures | grep -v screenshot

echo ""
echo "Done!"
