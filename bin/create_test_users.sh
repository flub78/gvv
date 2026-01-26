#!/bin/bash

# Script to create test users for GVV system
# Users will have password "password" and appropriate roles

# Get database credentials from config
DB_HOST="${MYSQL_HOST:-localhost}"
DB_USER="${MYSQL_USER:-gvv_user}"
DB_PASS="${MYSQL_PASSWORD:-lfoyfgbj}"
DB_NAME="${MYSQL_DATABASE:-gvv2}"

# Password hashes for "password" (working hashes from database)
TESTUSER_HASH='$1$RMO5L0Z4$dK0agqu3OImkLjgIfi5BD1'
TESTADMIN_HASH='$1$uM1.f95.$AnUHH1W/xLS9fxDbt8RPo0'
TESTPLANCHISTE_HASH='$1$DT0.QJ1.$yXqRz6gf/jWC4MzY2D05Y.'
TESTCA_HASH='$1$9h..cY3.$NzkeKkCoSa2oxL7bQCq4v1'
TESTBUREAU_HASH='$1$NC0.SN5.$qwnSUxiPbyh6v2JrhA1fH1'
TESTTRESORIER_HASH='$1$8XMCm61f$CS0gO5YjH.xHm2ZyaZNQt/'

# Get default section ID (usually 1)
SECTION_ID=$(mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -sN -e "SELECT id FROM sections LIMIT 1")

if [ -z "$SECTION_ID" ]; then
    echo "Error: No sections found in database. Please create a section first."
    exit 1
fi

echo "Using section ID: $SECTION_ID"

# Function to create or update user
create_user() {
    local username=$1
    local password_hash=$2
    local email=$3
    local role_id=$4
    local types_roles_id=$5

    # Check if user already exists
    user_exists=$(mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -sN -e \
        "SELECT COUNT(*) FROM users WHERE username='$username'")

    if [ "$user_exists" -eq 0 ]; then
        echo "Creating user: $username"

        # Insert user
        mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" <<EOF
INSERT INTO users (role_id, username, password, email, banned, last_ip, last_login, created)
VALUES ($role_id, '$username', '$password_hash', '$email', 0, '127.0.0.1', NOW(), NOW());

SET @user_id = LAST_INSERT_ID();

-- Insert user role per section
INSERT INTO user_roles_per_section (user_id, types_roles_id, section_id, granted_at)
VALUES (@user_id, $types_roles_id, $SECTION_ID, NOW());
EOF

        if [ $? -eq 0 ]; then
            echo "✓ User $username created successfully"
        else
            echo "✗ Error creating user $username"
        fi
    else
        echo "User $username already exists, updating password..."
        
        # Update password for existing user
        mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e \
            "UPDATE users SET password='$password_hash' WHERE username='$username';"
        
        if [ $? -eq 0 ]; then
            echo "✓ Password updated for user $username"
        else
            echo "✗ Error updating password for user $username"
        fi
    fi
}

echo "========================================="
echo "Creating test users for GVV system"
echo "========================================="
echo ""

# Create test users with their respective roles
# Format: create_user username password_hash email role_id types_roles_id
#
# roles table: id, parent_id, name
#   1 = membre, 2 = admin, 3 = bureau, 7 = planchiste, 8 = ca, 9 = tresorier
#
# types_roles table: id, nom, description
#   1 = user, 5 = planchiste, 6 = ca, 7 = bureau, 8 = tresorier, 10 = club-admin

create_user "testuser" "$TESTUSER_HASH" "testuser@free.fr" 1 1
create_user "testadmin" "$TESTADMIN_HASH" "frederic.peignot@free.fr" 2 10
create_user "testplanchiste" "$TESTPLANCHISTE_HASH" "testplanchiste@free.fr" 7 5
create_user "testca" "$TESTCA_HASH" "testca@free.fr" 8 6
create_user "testbureau" "$TESTBUREAU_HASH" "testbureau@free.fr" 3 7
create_user "testtresorier" "$TESTTRESORIER_HASH" "testresorier@free.fr" 9 8

echo ""
echo "========================================="
echo "Test users creation completed"
echo "========================================="
echo ""
echo "All test users have password: password"
echo ""
echo "Users created:"
echo "  - testuser       (role: membre/user)"
echo "  - testadmin      (role: admin/club-admin)"
echo "  - testplanchiste (role: planchiste)"
echo "  - testca         (role: ca)"
echo "  - testbureau     (role: bureau)"
echo "  - testtresorier  (role: tresorier)"
echo ""
echo "========================================="
echo "Verifying user logins"
echo "========================================="

# Function to test login
test_login() {
    local username=$1
    local password="password"

    echo -n "Testing login for $username... "

    # Attempt login using curl
    # The GVV login form posts to /auth/login
    response=$(curl -s -X POST "http://gvv.net/auth/login" \
        -d "username=$username" \
        -d "password=$password" \
        -c /tmp/cookies_${username}.txt \
        -L \
        -w "%{http_code}" \
        -o /tmp/login_response_${username}.html)

    # Check if login was successful
    # A successful login typically redirects (302/303) or returns 200
    # and the response should NOT contain the login form again
    if grep -q "login failed\|incorrect password\|invalid credentials" /tmp/login_response_${username}.html 2>/dev/null; then
        echo "✗ FAILED - Invalid credentials"
        return 1
    elif [ "$response" = "200" ] || [ "$response" = "302" ] || [ "$response" = "303" ]; then
        # Check if we're still on login page (which would indicate failure)
        if grep -q 'name="username"' /tmp/login_response_${username}.html 2>/dev/null; then
            echo "✗ FAILED - Still on login page"
            return 1
        else
            echo "✓ SUCCESS"
            return 0
        fi
    else
        echo "✗ FAILED - HTTP $response"
        return 1
    fi

    # Cleanup
    rm -f /tmp/cookies_${username}.txt /tmp/login_response_${username}.html
}

# Test all created users
test_login "testuser"
test_login "testadmin"
test_login "testplanchiste"
test_login "testca"
test_login "testbureau"
test_login "testtresorier"

echo ""
echo "========================================="
echo "Login verification completed"
echo "========================================="
