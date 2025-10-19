#!/bin/bash
# =====================================================
# Rollback Script: Reset Database to Migration 041
# =====================================================
# This script removes all changes from migrations 042 and 043
# Usage: ./rollback_to_041.sh
# =====================================================

set -e  # Exit on error

# Source database credentials from config
source setenv.sh

# Database credentials - edit these or source from config
DB_HOST="${MYSQL_HOST:-localhost}"
DB_USER="${MYSQL_USER:-gvv_user}"
DB_PASS="${MYSQL_PASSWORD:-lfoyfgbj}"
DB_NAME="${MYSQL_DATABASE:-gvv2}"

echo "=========================================="
echo "Rollback to Migration 041"
echo "=========================================="
echo "Database: $DB_NAME"
echo "Host: $DB_HOST"
echo ""
read -p "This will DROP tables and columns created by migrations 042 and 043. Continue? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    echo "Rollback cancelled."
    exit 0
fi

echo ""
echo "Creating backup before rollback..."
BACKUP_FILE="backup_before_rollback_$(date +%Y%m%d_%H%M%S).sql"
mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_FILE"
echo "Backup saved to: $BACKUP_FILE"

echo ""
echo "Executing rollback SQL script..."
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < doc/rollback_to_041.sql

echo ""
echo "=========================================="
echo "Rollback Complete!"
echo "=========================================="
echo ""
echo "Verification:"
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT version FROM migrations; SHOW TABLES LIKE 'authorization%';"

echo ""
echo "Backup file: $BACKUP_FILE"
echo "If you need to restore: mysql -u $DB_USER -p $DB_NAME < $BACKUP_FILE"
