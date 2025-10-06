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
$command = isset($argv[1]) ? $argv[1] : 'up';

echo "=== Migration 039 Test ===\n";
echo "Command: $command\n\n";

// Get current version
$current_version = $CI->migration->get_version();
echo "Current database version: $current_version\n";

if ($command === 'up') {
    echo "\nRunning migration UP to version 39...\n\n";
    if ($CI->migration->version(39)) {
        echo "✅ Migration UP successful!\n\n";

        // Verify the column was created
        echo "Verifying file_backup column...\n";
        $result = $CI->db->query("SHOW COLUMNS FROM attachments LIKE 'file_backup'");
        if ($result->num_rows() > 0) {
            echo "✅ file_backup column exists\n";
        } else {
            echo "❌ file_backup column NOT found\n";
        }

        // Check how many backups were created
        $count_result = $CI->db->query("SELECT COUNT(*) as count FROM attachments WHERE file_backup IS NOT NULL");
        if ($count_result) {
            $count = $count_result->row()->count;
            echo "✅ Backed up $count file paths\n";
        }

        // Show new version
        $new_version = $CI->migration->get_version();
        echo "\nNew database version: $new_version\n";
    } else {
        echo "❌ Migration UP failed!\n";
        echo "Error: " . $CI->migration->error_string() . "\n";
        exit(1);
    }
} elseif ($command === 'down') {
    echo "\nRunning migration DOWN to version 38...\n\n";
    if ($CI->migration->version(38)) {
        echo "✅ Migration DOWN successful!\n\n";

        // Verify the column was removed
        echo "Verifying file_backup column was removed...\n";
        $result = $CI->db->query("SHOW COLUMNS FROM attachments LIKE 'file_backup'");
        if ($result->num_rows() === 0) {
            echo "✅ file_backup column removed\n";
        } else {
            echo "❌ file_backup column still exists\n";
        }

        // Show new version
        $new_version = $CI->migration->get_version();
        echo "\nNew database version: $new_version\n";
    } else {
        echo "❌ Migration DOWN failed!\n";
        echo "Error: " . $CI->migration->error_string() . "\n";
        exit(1);
    }
} else {
    echo "Invalid command. Use: up or down\n";
    exit(1);
}

echo "\n=== Test Complete ===\n";
exit(0);
