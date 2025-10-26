<?php
/**
 * Test script to verify use_new_authorization flag behavior
 * 
 * This script tests the three authorization modes:
 * 1. Legacy only (use_new_authorization = FALSE)
 * 2. Global migration (use_new_authorization = TRUE, progressive = FALSE)
 * 3. Progressive migration (use_new_authorization = TRUE, progressive = TRUE)
 */

// Bootstrap CI environment
define('BASEPATH', 'system/');
define('ENVIRONMENT', 'development');

require_once('index.php');

// Get CI instance
$CI =& get_instance();

echo "=================================================================\n";
echo "Testing Authorization Flag Behavior\n";
echo "=================================================================\n\n";

// Test 1: Check current configuration
echo "TEST 1: Current Configuration\n";
echo "-------------------------------------------------------------\n";
$CI->config->load('gvv_config', TRUE);
$use_new = $CI->config->item('use_new_authorization', 'gvv_config');
$progressive = $CI->config->item('authorization_progressive_migration', 'gvv_config');

echo "use_new_authorization: " . ($use_new ? 'TRUE' : 'FALSE') . "\n";
echo "authorization_progressive_migration: " . ($progressive ? 'TRUE' : 'FALSE') . "\n";

if (!$use_new) {
    echo "Expected behavior: ALL users will use LEGACY DX_Auth\n";
} else if ($progressive) {
    echo "Expected behavior: Check per-user migration status\n";
} else {
    echo "Expected behavior: ALL users will use NEW Gvv_Authorization\n";
}
echo "\n";

// Test 2: Check if a logged-in user would use new system
echo "TEST 2: Simulating User Authorization System Selection\n";
echo "-------------------------------------------------------------\n";

// Check if we can load the base controller
$CI->load->library('Gvv_Controller');

echo "Note: To fully test, log in as a user and check application logs:\n";
echo "  tail -f application/logs/log-*.php | grep 'GVV_Controller'\n";
echo "\n";

echo "Expected log messages based on config:\n";
if (!$use_new) {
    echo "  - 'User X using LEGACY authorization system (global flag disabled)'\n";
} else if ($progressive) {
    echo "  - 'User X using NEW authorization (progressive mode, ...)' OR\n";
    echo "  - 'User X using LEGACY authorization (progressive mode, not migrated)'\n";
} else {
    echo "  - 'User X using NEW authorization system (global mode)'\n";
}
echo "\n";

// Test 3: Verify Gvv_Authorization library is configured
echo "TEST 3: Verify Gvv_Authorization Library Configuration\n";
echo "-------------------------------------------------------------\n";
$CI->load->library('Gvv_Authorization');

if (method_exists($CI->gvv_authorization, 'use_new_system')) {
    $lib_uses_new = $CI->gvv_authorization->use_new_system();
    echo "Gvv_Authorization library use_new_system: " . ($lib_uses_new ? 'TRUE' : 'FALSE') . "\n";
    
    if ($use_new === $lib_uses_new) {
        echo "✓ Library configuration matches gvv_config setting\n";
    } else {
        echo "✗ WARNING: Library configuration does NOT match gvv_config!\n";
        echo "  This may indicate a caching or initialization issue.\n";
    }
} else {
    echo "✗ ERROR: use_new_system() method not found in Gvv_Authorization\n";
}
echo "\n";

// Test 4: Check database tables
echo "TEST 4: Check Required Database Tables\n";
echo "-------------------------------------------------------------\n";
$tables_to_check = array(
    'types_roles',
    'user_roles_per_section',
    'data_access_rules',
    'authorization_audit_log'
);

foreach ($tables_to_check as $table) {
    $query = $CI->db->query("SHOW TABLES LIKE '$table'");
    if ($query->num_rows() > 0) {
        echo "✓ Table '$table' exists\n";
    } else {
        echo "✗ Table '$table' NOT FOUND\n";
    }
}
echo "\n";

// Test 5: Check user roles setup
echo "TEST 5: Check User Roles Setup\n";
echo "-------------------------------------------------------------\n";
$query = $CI->db->query("
    SELECT s.nom as section, COUNT(DISTINCT urps.user_id) as users_with_roles
    FROM sections s
    LEFT JOIN user_roles_per_section urps ON s.id = urps.section_id AND urps.revoked_at IS NULL
    GROUP BY s.id, s.nom
    ORDER BY s.id
");

if ($query->num_rows() > 0) {
    echo "User roles distribution:\n";
    foreach ($query->result() as $row) {
        echo "  - {$row->section}: {$row->users_with_roles} users\n";
    }
} else {
    echo "✗ No sections found in database\n";
}
echo "\n";

// Summary
echo "=================================================================\n";
echo "SUMMARY\n";
echo "=================================================================\n";
echo "Current mode: ";
if (!$use_new) {
    echo "LEGACY ONLY\n";
    echo "Action: Keep flag FALSE until ready for migration\n";
} else if ($progressive) {
    echo "PROGRESSIVE MIGRATION\n";
    echo "Action: Migrate users individually via authorization_migration_status table\n";
} else {
    echo "GLOBAL MIGRATION\n";
    echo "Action: All users will use new system - monitor logs closely!\n";
}
echo "\n";

echo "To enable new system globally:\n";
echo "  1. Edit application/config/gvv_config.php\n";
echo "  2. Set: \$config['use_new_authorization'] = TRUE;\n";
echo "  3. Set: \$config['authorization_progressive_migration'] = FALSE;\n";
echo "  4. Monitor logs: tail -f application/logs/log-*.php\n";
echo "\n";

echo "To rollback to legacy system:\n";
echo "  1. Edit application/config/gvv_config.php\n";
echo "  2. Set: \$config['use_new_authorization'] = FALSE;\n";
echo "  3. Rollback time: < 1 minute\n";
echo "\n";

echo "=================================================================\n";
echo "Test complete!\n";
echo "=================================================================\n";
