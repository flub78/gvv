#!/bin/bash

# Script to run migration 056 and verify the ordre_affichage field

source setenv.sh

echo "=== Testing Migration 056 ==="
echo ""

# Run the migration
echo "1. Running migration..."
php index.php migrate migrate

if [ $? -eq 0 ]; then
    echo "✓ Migration completed successfully"
else
    echo "✗ Migration failed"
    exit 1
fi

echo ""
echo "2. Checking if ordre_affichage field exists in sections table..."

# Check if the field was added
mysql -h localhost -u gvv_user -plfoyfgbj gvv2 -e "DESCRIBE sections;" | grep ordre_affichage

if [ $? -eq 0 ]; then
    echo "✓ Field ordre_affichage exists in sections table"
else
    echo "✗ Field ordre_affichage not found in sections table"
    exit 1
fi

echo ""
echo "3. Testing sections model..."
php index.php sections test xml > /dev/null 2>&1

if [ $? -eq 0 ]; then
    echo "✓ Sections model tests passed"
else
    echo "✗ Sections model tests failed"
fi

echo ""
echo "=== Migration 056 test completed ==="
