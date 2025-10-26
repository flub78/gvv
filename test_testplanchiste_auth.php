<?php
/**
 * Test script to verify testplanchiste authorization logic
 *
 * This script simulates what happens when testplanchiste logs in:
 * 1. Check if testplanchiste is in use_new_authorization table
 * 2. Check if testplanchiste has 'user' role (id=1) for section 1
 * 3. Expected: Should be DENIED login (no user role)
 */

// Database connection
$mysqli = new mysqli('localhost', 'gvv_user', 'lfoyfgbj', 'gvv2');

if ($mysqli->connect_error) {
    die('Connect Error: ' . $mysqli->connect_error);
}

echo "=== Testing testplanchiste Authorization Logic ===\n\n";

// Step 1: Check if user is in migration table
$username = 'testplanchiste';
$stmt = $mysqli->prepare("SELECT id FROM use_new_authorization WHERE username = ?");
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "✓ Step 1: testplanchiste IS in use_new_authorization table\n";
    echo "  → Should use NEW authorization system\n\n";
    $use_new_auth = true;
} else {
    echo "✗ Step 1: testplanchiste NOT in use_new_authorization table\n";
    echo "  → Should use LEGACY authorization system\n\n";
    $use_new_auth = false;
}

// Step 2: Get user_id
$stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$user_id = $user['id'];

echo "  User ID: $user_id\n\n";

// Step 3: Check for 'user' role (id=1) for section 1
$section_id = 1;
$stmt = $mysqli->prepare("
    SELECT types_roles_id
    FROM user_roles_per_section
    WHERE user_id = ?
    AND section_id = ?
    AND types_roles_id = 1
    AND revoked_at IS NULL
");
$stmt->bind_param('ii', $user_id, $section_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "✓ Step 2: testplanchiste HAS 'user' role (id=1) for section $section_id\n";
    echo "  → Login should be ALLOWED\n\n";
    $has_user_role = true;
} else {
    echo "✗ Step 2: testplanchiste does NOT have 'user' role (id=1) for section $section_id\n";
    echo "  → Login should be DENIED\n\n";
    $has_user_role = false;
}

// Step 4: Show what roles testplanchiste DOES have
$stmt = $mysqli->prepare("
    SELECT r.types_roles_id, tr.nom as role_name
    FROM user_roles_per_section r
    LEFT JOIN types_roles tr ON r.types_roles_id = tr.id
    WHERE r.user_id = ?
    AND r.section_id = ?
    AND r.revoked_at IS NULL
");
$stmt->bind_param('ii', $user_id, $section_id);
$stmt->execute();
$result = $stmt->get_result();

echo "  Roles testplanchiste DOES have for section $section_id:\n";
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "    - Role ID {$row['types_roles_id']}: {$row['role_name']}\n";
    }
} else {
    echo "    - NO ROLES AT ALL\n";
}

echo "\n";

// Final verdict
echo "=== FINAL VERDICT ===\n";
if ($use_new_auth) {
    echo "Authorization System: NEW (per-user migration)\n";
    if ($has_user_role) {
        echo "Login Status: ALLOWED (has 'user' role)\n";
    } else {
        echo "Login Status: DENIED (missing 'user' role)\n";
        echo "\nExpected behavior:\n";
        echo "- Gvv_Controller::_check_login_permission() should call die()\n";
        echo "- User should see application/views/authorization/login_denied.php\n";
        echo "- Log should show: \"User 'testplanchiste' denied login - no 'user' role (id=1)\"\n";
    }
} else {
    echo "Authorization System: LEGACY (DX_Auth)\n";
    echo "Login Status: Based on legacy is_logged_in() check\n";
}

$mysqli->close();
?>
