#!/bin/bash

# Script to delete test users created by create_test_users.sh
# ⚠️  Keep this list synchronized with create_test_users.sh

# Get database credentials from config
DB_HOST="${MYSQL_HOST:-localhost}"
DB_USER="${MYSQL_USER:-gvv_user}"
DB_PASS="${MYSQL_PASSWORD:-lfoyfgbj}"
DB_NAME="${MYSQL_DATABASE:-gvv2}"

mysql_exec() {
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" "$@"
}

mysql_query() {
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -sN -e "$1"
}

delete_user() {
    local username=$1
    local count=$(mysql_query "SELECT COUNT(*) FROM users WHERE username='$username'")

    if [ -n "$count" ] && [ "$count" -gt 0 ]; then
        mysql_exec -e "
            DELETE FROM use_new_authorization WHERE username='$username';
            DELETE urps FROM user_roles_per_section urps JOIN users u ON urps.user_id=u.id WHERE u.username='$username';
            DELETE FROM comptes WHERE pilote='$username' AND codec=411;
            DELETE FROM membres WHERE username='$username';
            DELETE up FROM user_profile up JOIN users u ON up.user_id=u.id WHERE u.username='$username';
            DELETE FROM users WHERE username='$username';
        "
        echo "  Deleted: $username"
    else
        echo "  Not found: $username (skipped)"
    fi
}

echo "========================================="
echo "Deleting legacy test users"
echo "========================================="
for user in testuser testadmin testplanchiste testca testbureau testtresorier idefix agecanonix; do
    delete_user "$user"
done

echo ""
echo "========================================="
echo "Deleting Gaulois test users"
echo "========================================="
for user in asterix obelix abraracourcix goudurix panoramix; do
    delete_user "$user"
done

echo ""
echo "========================================="
echo "Done"
echo "========================================="
