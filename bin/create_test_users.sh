#!/bin/bash

# Script to create test users for GVV system
# Users will have password "password" and appropriate roles
#
# Two categories of test users:
# 1. Legacy users (testuser, testadmin, etc.) - use DX_Auth legacy authorization
#    - Have a legacy role_id in users table
#    - Have ONE user_roles_per_section entry in default section
#    - Are NOT in use_new_authorization table
#
# 2. Gaulois users (asterix, obelix, etc.) - use new authorization system
#    - Have role_id=1 (membre) in users table
#    - Have full membres entries with mniveaux bits
#    - Have comptes (411) per section
#    - Have MULTIPLE user_roles_per_section entries per section
#    - ARE in use_new_authorization table

# Get database credentials from config
DB_HOST="${MYSQL_HOST:-localhost}"
DB_USER="${MYSQL_USER:-gvv_user}"
DB_PASS="${MYSQL_PASSWORD:-lfoyfgbj}"
DB_NAME="${MYSQL_DATABASE:-gvv2}"

# Common password hash for "password" (MD5 crypt)
PASSWORD_HASH='$1$wu3.3t2.$Wgk43dHPPi3PTv5atdpnz0'

# Section IDs
PLANEUR_SECTION=1
ULM_SECTION=2
AVION_SECTION=3
GENERAL_SECTION=4

# types_roles IDs
TR_USER=1
TR_AUTO_PLANCHISTE=2
TR_PLANCHISTE=5
TR_CA=6
TR_BUREAU=7
TR_TRESORIER=8
TR_CLUB_ADMIN=10
TR_INSTRUCTEUR=11

# Role bits from program.php (for mniveaux field)
BIT_TRESORIER=8          # 2**3
BIT_CA=64                # 2**6
BIT_REMORQUEUR=8192      # 2**13
BIT_IVV=65536            # 2**16
BIT_FI_AVION=131072      # 2**17

mysql_exec() {
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" "$@"
}

mysql_query() {
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -sN -e "$1"
}

# Get default section ID (usually 1)
SECTION_ID=$(mysql_query "SELECT id FROM sections LIMIT 1")

if [ -z "$SECTION_ID" ]; then
    echo "Error: No sections found in database. Please create a section first."
    exit 1
fi

echo "Using default section ID: $SECTION_ID"

# =========================================
# Helper: delete a user and all related data
# =========================================
delete_user() {
    local username=$1
    local user_id=$(mysql_query "SELECT id FROM users WHERE username='$username'")

    if [ -n "$user_id" ]; then
        mysql_exec -e "
            DELETE FROM use_new_authorization WHERE username='$username';
            DELETE FROM user_roles_per_section WHERE user_id=$user_id;
            DELETE FROM comptes WHERE pilote='$username' AND codec=411;
            DELETE FROM membres WHERE username='$username';
            DELETE FROM user_profile WHERE user_id=$user_id;
            DELETE FROM users WHERE id=$user_id;
        "
        echo "  Deleted existing user: $username"
    fi
}

# =========================================
# PART 1: Legacy test users (DX_Auth)
# =========================================
create_legacy_user() {
    local username=$1
    local password_hash=$2
    local email=$3
    local role_id=$4
    local types_roles_id=$5

    echo -n "  $username... "
    delete_user "$username"

    # 1. Create user with legacy role_id
    mysql_exec -e "
        INSERT INTO users (role_id, username, password, email, banned, last_ip, last_login, created)
        VALUES ($role_id, '$username', '$password_hash', '$email', 0, '127.0.0.1', NOW(), NOW());
    "
    local user_id=$(mysql_query "SELECT id FROM users WHERE username='$username'")

    # 2. Create membre entry (not for admin)
    if [ "$role_id" -ne 2 ]; then
        local nom=$(echo "$username" | sed 's/.*/\u&/')
        mysql_exec -e "
            INSERT INTO membres (mlogin, mnom, mprenom, memail, madresse, cp, ville, pays, msexe, mniveaux, macces, club, ext, actif, username, categorie)
            VALUES ('$username', '$nom', 'Test', '$email', '1 rue de Test', 75000, 'Paris', 'France', 'M', 0, 0, 0, 0, 1, '$username', '0');
        "

        # 3. Create 411 account in default section
        mysql_exec -e "
            INSERT INTO comptes (nom, pilote, \`desc\`, codec, actif, debit, credit, club, saisie_par)
            VALUES ('(411) $nom', '$username', 'Compte client 411 $nom', 411, 1, 0.0, 0.0, $SECTION_ID, 'testadmin');
        "
    fi

    # 4. Create user role per section (single role in default section)
    mysql_exec -e "
        INSERT INTO user_roles_per_section (user_id, types_roles_id, section_id, granted_at)
        VALUES ($user_id, $types_roles_id, $SECTION_ID, NOW());
    "

    # NOTE: Legacy users are NOT added to use_new_authorization

    echo "OK"
}

echo ""
echo "========================================="
echo "Creating legacy test users (DX_Auth)"
echo "========================================="

# Legacy password hashes for "password"
TESTUSER_HASH='$1$RMO5L0Z4$dK0agqu3OImkLjgIfi5BD1'
TESTADMIN_HASH='$1$uM1.f95.$AnUHH1W/xLS9fxDbt8RPo0'
TESTPLANCHISTE_HASH='$1$DT0.QJ1.$yXqRz6gf/jWC4MzY2D05Y.'
TESTCA_HASH='$1$9h..cY3.$NzkeKkCoSa2oxL7bQCq4v1'
TESTBUREAU_HASH='$1$NC0.SN5.$qwnSUxiPbyh6v2JrhA1fH1'
TESTTRESORIER_HASH='$1$8XMCm61f$CS0gO5YjH.xHm2ZyaZNQt/'

# Format: create_legacy_user username password_hash email legacy_role_id types_roles_id
# Legacy role_id: 1=membre, 2=admin, 3=bureau, 7=planchiste, 8=ca, 9=tresorier
create_legacy_user "testuser"       "$TESTUSER_HASH"       "testuser@free.fr"           1 $TR_USER
create_legacy_user "testadmin"      "$TESTADMIN_HASH"      "frederic.peignot@free.fr"   2 $TR_CLUB_ADMIN
create_legacy_user "testplanchiste" "$TESTPLANCHISTE_HASH" "testplanchiste@free.fr"     7 $TR_PLANCHISTE
create_legacy_user "testca"         "$TESTCA_HASH"         "testca@free.fr"             8 $TR_CA
create_legacy_user "testbureau"     "$TESTBUREAU_HASH"     "testbureau@free.fr"         3 $TR_BUREAU
create_legacy_user "testtresorier"  "$TESTTRESORIER_HASH"  "testresorier@free.fr"       9 $TR_TRESORIER

# =========================================
# PART 2: Gaulois test users (new authorization system)
# =========================================
create_gaulois_user() {
    local username=$1
    local nom=$2
    local prenom=$3
    local email=$4
    local adresse=$5
    local roles_bits=$6
    local is_admin=$7
    # sections and section_roles passed via global arrays

    echo -n "  $username... "
    delete_user "$username"

    # 1. Create user with role_id=1 (membre)
    mysql_exec -e "
        INSERT INTO users (role_id, username, password, email, banned, last_ip, last_login, created)
        VALUES (1, '$username', '$PASSWORD_HASH', '$email', 0, '127.0.0.1', NOW(), NOW());
    "
    local user_id=$(mysql_query "SELECT id FROM users WHERE username='$username'")

    # 2. Create membre entry
    mysql_exec -e "
        INSERT INTO membres (mlogin, mnom, mprenom, memail, madresse, cp, ville, pays, msexe, mniveaux, macces, club, ext, actif, username, categorie)
        VALUES ('$username', '$nom', '$prenom', '$email', '$adresse', 22000, 'Village gaulois', 'France', 'M', $roles_bits, 0, 0, 0, 1, '$username', '0');
    "

    # 3. Create 411 accounts and roles for each section
    for section_id in "${USER_SECTIONS[@]}"; do
        # Create 411 account
        local existing=$(mysql_query "SELECT COUNT(*) FROM comptes WHERE pilote='$username' AND codec=411 AND club=$section_id")
        if [ "$existing" -eq 0 ]; then
            mysql_exec -e "
                INSERT INTO comptes (nom, pilote, \`desc\`, codec, actif, debit, credit, club, saisie_par)
                VALUES ('(411) $nom $prenom', '$username', 'Compte client 411 $nom $prenom', 411, 1, 0.0, 0.0, $section_id, 'admin');
            "
        fi

        # Build roles list for this section
        # Always add 'user' role
        local roles="$TR_USER"

        # CA role applies to all sections
        if [ $((roles_bits & BIT_CA)) -ne 0 ]; then
            roles="$roles $TR_CA"
        fi

        # Treasurer role applies to all sections
        if [ $((roles_bits & BIT_TRESORIER)) -ne 0 ]; then
            roles="$roles $TR_TRESORIER"
        fi

        # Instructor roles for avion section
        if [ $((roles_bits & BIT_FI_AVION)) -ne 0 ] && [ "$section_id" -eq $AVION_SECTION ]; then
            roles="$roles $TR_INSTRUCTEUR"
        fi

        # Remorqueur for avion section
        if [ $((roles_bits & BIT_REMORQUEUR)) -ne 0 ] && [ "$section_id" -eq $AVION_SECTION ]; then
            roles="$roles $TR_INSTRUCTEUR"
        fi

        # Per-section roles from SECTION_ROLES_MAP
        local key="section_${section_id}"
        local extra_roles="${SECTION_ROLES_MAP[$key]}"
        if [ -n "$extra_roles" ]; then
            roles="$roles $extra_roles"
        fi

        # Insert unique roles
        local seen=""
        for role_id in $roles; do
            if [[ ! " $seen " =~ " $role_id " ]]; then
                mysql_exec -e "
                    INSERT INTO user_roles_per_section (user_id, types_roles_id, section_id, granted_at)
                    VALUES ($user_id, $role_id, $section_id, NOW());
                "
                seen="$seen $role_id"
            fi
        done
    done

    # 4. Add to new authorization system
    mysql_exec -e "
        INSERT INTO use_new_authorization (username, created_at, notes)
        VALUES ('$username', NOW(), 'Gaulois test user - created by create_test_users.sh');
    "

    echo "OK"
}

echo ""
echo "========================================="
echo "Creating Gaulois test users (new auth)"
echo "========================================="

# --- Asterix ---
declare -a USER_SECTIONS=($PLANEUR_SECTION $GENERAL_SECTION)
declare -A SECTION_ROLES_MAP=()
create_gaulois_user "asterix" "Asterix" "Le Gaulois" "asterix@gmail.com" "12 rue de Babaorum" 0 0

# --- Obelix ---
declare -a USER_SECTIONS=($PLANEUR_SECTION $ULM_SECTION $GENERAL_SECTION)
declare -A SECTION_ROLES_MAP=(
    ["section_${PLANEUR_SECTION}"]="$TR_PLANCHISTE"
    ["section_${ULM_SECTION}"]="$TR_AUTO_PLANCHISTE"
)
create_gaulois_user "obelix" "Obelix" "Le Gaulois" "obelix@gmail.com" "27 rue du Menhir" $BIT_REMORQUEUR 0

# --- Abraracourcix ---
declare -a USER_SECTIONS=($PLANEUR_SECTION $AVION_SECTION $ULM_SECTION $GENERAL_SECTION)
declare -A SECTION_ROLES_MAP=()
create_gaulois_user "abraracourcix" "Abraracourcix" "Le Gaulois" "abraracourcix@gmail.com" "3 rue du Menhir" $((BIT_REMORQUEUR + BIT_FI_AVION + BIT_CA)) 0

# --- Goudurix ---
declare -a USER_SECTIONS=($AVION_SECTION $GENERAL_SECTION)
declare -A SECTION_ROLES_MAP=(
    ["section_${AVION_SECTION}"]="$TR_AUTO_PLANCHISTE"
)
create_gaulois_user "goudurix" "Goudurix" "Le Gaulois" "goudurix@gmail.com" "3 rue du Menhir" $BIT_TRESORIER 0

# --- Panoramix (admin - club-admin in all sections) ---
echo -n "  panoramix... "
delete_user "panoramix"
mysql_exec -e "
    INSERT INTO users (role_id, username, password, email, banned, last_ip, last_login, created)
    VALUES (1, 'panoramix', '$PASSWORD_HASH', 'panoramix@gmail.com', 0, '127.0.0.1', NOW(), NOW());
"
PANORAMIX_USER_ID=$(mysql_query "SELECT id FROM users WHERE username='panoramix'")
mysql_exec -e "
    INSERT INTO membres (mlogin, mnom, mprenom, memail, madresse, cp, ville, pays, msexe, mniveaux, macces, club, ext, actif, username, categorie)
    VALUES ('panoramix', 'Panoramix', 'Le Gaulois', 'panoramix@gmail.com', '1 rue du Menhir', 22000, 'Village gaulois', 'France', 'M', 0, 0, 0, 0, 1, 'panoramix', '0');
"
# Grant user + club-admin roles in all sections
for section_id in $(mysql_query "SELECT id FROM sections ORDER BY id"); do
    mysql_exec -e "
        INSERT INTO user_roles_per_section (user_id, types_roles_id, section_id, granted_at)
        VALUES ($PANORAMIX_USER_ID, $TR_USER, $section_id, NOW()),
               ($PANORAMIX_USER_ID, $TR_CLUB_ADMIN, $section_id, NOW());
    "
done
mysql_exec -e "
    INSERT INTO use_new_authorization (username, created_at, notes)
    VALUES ('panoramix', NOW(), 'Gaulois test user - created by create_test_users.sh');
"
echo "OK (admin - club-admin in all sections)"

echo ""
echo "========================================="
echo "Test users creation completed"
echo "========================================="
echo ""
echo "All test users have password: password"
echo ""
echo "Legacy users (DX_Auth authorization):"
echo "  - testuser       (role: membre/user)"
echo "  - testadmin      (role: admin/club-admin)"
echo "  - testplanchiste (role: planchiste)"
echo "  - testca         (role: ca)"
echo "  - testbureau     (role: bureau)"
echo "  - testtresorier  (role: tresorier)"
echo ""
echo "Gaulois users (new authorization system):"
echo "  - asterix        (sections: planeur, general)"
echo "  - obelix         (planeur: planchiste, ULM: auto_planchiste, general: user)"
echo "  - abraracourcix  (planeur, avion, ULM, general + CA + instructeur)"
echo "  - goudurix       (avion: auto_planchiste + tresorier, general: user)"
echo "  - panoramix      (admin - no sections)"

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
    if grep -q "login failed\|incorrect password\|invalid credentials" /tmp/login_response_${username}.html 2>/dev/null; then
        echo "FAILED - Invalid credentials"
        return 1
    elif [ "$response" = "200" ] || [ "$response" = "302" ] || [ "$response" = "303" ]; then
        if grep -q 'name="username"' /tmp/login_response_${username}.html 2>/dev/null; then
            echo "FAILED - Still on login page"
            return 1
        else
            echo "SUCCESS"
            return 0
        fi
    else
        echo "FAILED - HTTP $response"
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
test_login "asterix"
test_login "obelix"
test_login "abraracourcix"
test_login "goudurix"
test_login "panoramix"

echo ""
echo "========================================="
echo "Login verification completed"
echo "========================================="
