#!/bin/bash
#
# Test migration 049 with rollback capability
# This script runs the migration and provides rollback if needed
#

set -e

DB_HOST="localhost"
DB_USER="gvv_user"
DB_PASS="lfoyfgbj"
DB_NAME="gvv2"

MYSQL_CMD="mysql -h$DB_HOST -u$DB_USER -p$DB_PASS $DB_NAME"

echo ""
echo "=== Migration 049 Test with Rollback ==="
echo ""

# Function to check table exists
check_table() {
    local table=$1
    result=$($MYSQL_CMD -sN -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME' AND table_name='$table'" 2>/dev/null)
    echo $result
}

# Function to list all email_lists tables
list_tables() {
    echo "Tables status:"
    for table in email_lists email_list_roles email_list_members email_list_external; do
        count=$(check_table $table)
        if [ "$count" = "1" ]; then
            echo "  - $table: EXISTS"
        else
            echo "  - $table: NOT FOUND"
        fi
    done
}

# Function to run migration down
run_migration_down() {
    echo ""
    echo "=== Running Migration DOWN (rollback) ==="
    echo ""

    # Drop triggers
    echo "Dropping triggers..."
    $MYSQL_CMD -e "DROP TRIGGER IF EXISTS email_list_external_added_at" 2>/dev/null && echo "✓ Dropped: email_list_external_added_at" || echo "⚠ Warning: email_list_external_added_at"
    $MYSQL_CMD -e "DROP TRIGGER IF EXISTS email_list_members_added_at" 2>/dev/null && echo "✓ Dropped: email_list_members_added_at" || echo "⚠ Warning: email_list_members_added_at"
    $MYSQL_CMD -e "DROP TRIGGER IF EXISTS email_list_roles_granted_at" 2>/dev/null && echo "✓ Dropped: email_list_roles_granted_at" || echo "⚠ Warning: email_list_roles_granted_at"
    $MYSQL_CMD -e "DROP TRIGGER IF EXISTS email_lists_updated_at" 2>/dev/null && echo "✓ Dropped: email_lists_updated_at" || echo "⚠ Warning: email_lists_updated_at"
    $MYSQL_CMD -e "DROP TRIGGER IF EXISTS email_lists_created_at" 2>/dev/null && echo "✓ Dropped: email_lists_created_at" || echo "⚠ Warning: email_lists_created_at"

    # Drop tables
    echo ""
    echo "Dropping tables..."
    $MYSQL_CMD -e "DROP TABLE IF EXISTS email_list_external" 2>/dev/null && echo "✓ Dropped: email_list_external" || echo "⚠ Warning: email_list_external"
    $MYSQL_CMD -e "DROP TABLE IF EXISTS email_list_members" 2>/dev/null && echo "✓ Dropped: email_list_members" || echo "⚠ Warning: email_list_members"
    $MYSQL_CMD -e "DROP TABLE IF EXISTS email_list_roles" 2>/dev/null && echo "✓ Dropped: email_list_roles" || echo "⚠ Warning: email_list_roles"
    $MYSQL_CMD -e "DROP TABLE IF EXISTS email_lists" 2>/dev/null && echo "✓ Dropped: email_lists" || echo "⚠ Warning: email_lists"

    echo ""
    echo "✓ Rollback completed"
}

# Check initial state
echo "Checking initial state..."
echo ""
list_tables

echo ""
echo "================================================================"
read -p "Proceed with running migration UP? (y/N): " proceed

if [ "$proceed" != "y" ] && [ "$proceed" != "Y" ]; then
    echo "Aborted by user"
    exit 0
fi

# Source environment
echo ""
echo "Setting up environment..."
source setenv.sh

# Run migration
echo ""
echo "=== Running Migration UP via PHP CLI ==="
echo ""

# Capture output and exit code
set +e
php index.php migrate version 49 2>&1
EXIT_CODE=$?
set -e

echo ""

if [ $EXIT_CODE -ne 0 ]; then
    echo "✗ Migration failed with exit code $EXIT_CODE"
    echo ""
    read -p "Do you want to rollback changes? (Y/n): " rollback

    if [ "$rollback" != "n" ] && [ "$rollback" != "N" ]; then
        run_migration_down
        echo ""
        list_tables
    fi

    exit $EXIT_CODE
fi

# Check final state
echo "=== Verifying Migration Results ==="
echo ""
list_tables

# Check triggers
echo ""
echo "Triggers status:"
for trigger in email_lists_created_at email_lists_updated_at email_list_roles_granted_at email_list_members_added_at email_list_external_added_at; do
    count=$($MYSQL_CMD -sN -e "SELECT COUNT(*) FROM information_schema.triggers WHERE trigger_schema='$DB_NAME' AND trigger_name='$trigger'" 2>/dev/null)
    if [ "$count" = "1" ]; then
        echo "  - $trigger: EXISTS"
    else
        echo "  - $trigger: NOT FOUND"
    fi
done

echo ""
echo "================================================================"
read -p "Migration completed. Do you want to ROLLBACK? (y/N): " rollback

if [ "$rollback" = "y" ] || [ "$rollback" = "Y" ]; then
    run_migration_down

    echo ""
    echo "=== Verifying Rollback ==="
    echo ""
    list_tables
fi

echo ""
echo "=== SUCCESS ==="
echo ""
