<?php
/**
 * Test script to run migration 039
 *
 * Usage: source setenv.sh && php test_migration.php [up|down]
 */

// Prevent direct access
define('BASEPATH', TRUE);

// Load CodeIgniter
$_SERVER['REQUEST_METHOD'] = 'CLI';
require_once('index.php');

$CI =& get_instance();
$CI->load->library('migration');

// Get command (up or down)
echo "=== Migration 042/043 Authorization Refactoring ===\n\n";

// Get current version
$current_version = $CI->migration->get_version();
echo "Current database version: $current_version\n";
echo "Target version: 43\n\n";

echo "Running migrations to version 43...\n\n";
if ($CI->migration->current()) {
    echo "✅ Migrations successful!\n\n";

    // Verify tables were created
    echo "Verifying authorization tables...\n";
    $tables = array(
        'role_permissions',
        'data_access_rules',
        'authorization_audit_log',
        'authorization_migration_status'
    );

    foreach ($tables as $table) {
        $result = $CI->db->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows() > 0) {
            echo "✅ $table exists\n";
        } else {
            echo "❌ $table NOT found\n";
        }
    }

    // Verify columns were added to types_roles
    echo "\nVerifying types_roles columns...\n";
    $columns = array('scope', 'is_system_role', 'display_order', 'translation_key');
    foreach ($columns as $column) {
        $result = $CI->db->query("SHOW COLUMNS FROM types_roles LIKE '$column'");
        if ($result->num_rows() > 0) {
            echo "✅ types_roles.$column exists\n";
        } else {
            echo "❌ types_roles.$column NOT found\n";
        }
    }

    // Show new version
    $new_version = $CI->migration->get_version();
    echo "\nNew database version: $new_version\n";
} else {
    echo "❌ Migration failed!\n";
    echo "Error: " . $CI->migration->error_string() . "\n";
    exit(1);
}

echo "\n=== Test Complete ===\n";
exit(0);
