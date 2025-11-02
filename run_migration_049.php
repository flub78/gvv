<?php
/**
 * Test migration 049 with rollback capability
 *
 * Note: MySQL DDL statements (CREATE TABLE, ALTER TABLE) are not transactional
 * and cannot be rolled back. Instead, we'll run the down() method if an error occurs.
 *
 * Usage: php run_migration_049.php
 */

define('BASEPATH', 'system/');
define('ENVIRONMENT', 'development');

$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';

// Capture the output
ob_start();

try {
    // Bootstrap CodeIgniter
    require_once('index.php');

    $CI =& get_instance();

    echo "\n=== Running Migration 049 ===\n\n";

    // Load migration library
    $CI->load->library('migration');

    // Check current version
    $current_version = $CI->migration->current();
    echo "Current migration version: " . ($current_version ?: 'unknown') . "\n\n";

    // Load the migration file
    require_once(APPPATH . 'migrations/049_create_email_lists.php');

    // Instantiate the migration
    $migration = new Migration_Create_email_lists();

    // Check if tables already exist
    echo "Checking existing tables...\n";
    $tables_exist = array();
    $check_tables = array('email_lists', 'email_list_roles', 'email_list_members', 'email_list_external');

    foreach ($check_tables as $table) {
        $exists = $CI->db->table_exists($table);
        $tables_exist[$table] = $exists;
        echo "  - $table: " . ($exists ? "EXISTS" : "NOT FOUND") . "\n";
    }

    echo "\n";

    // Run the migration
    echo "Running migration UP...\n";
    try {
        $migration->up();
        echo "✓ Migration UP completed successfully!\n\n";

        // Verify tables were created
        echo "Verifying tables...\n";
        foreach ($check_tables as $table) {
            $exists = $CI->db->table_exists($table);
            echo "  - $table: " . ($exists ? "✓ EXISTS" : "✗ NOT FOUND") . "\n";
        }

        echo "\n=== SUCCESS: Migration 049 completed ===\n";

        // Ask if user wants to rollback
        echo "\nDo you want to rollback? (y/N): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);

        if (trim(strtolower($line)) === 'y') {
            echo "\nRunning migration DOWN (rollback)...\n";
            $migration->down();
            echo "✓ Rollback completed\n\n";

            // Verify tables were dropped
            echo "Verifying tables removed...\n";
            foreach ($check_tables as $table) {
                $exists = $CI->db->table_exists($table);
                echo "  - $table: " . ($exists ? "✗ STILL EXISTS" : "✓ REMOVED") . "\n";
            }
        } else {
            echo "\n=== Migration changes kept ===\n";
        }

    } catch (Exception $e) {
        echo "\n✗ ERROR during migration UP:\n";
        echo "  Message: " . $e->getMessage() . "\n";
        echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";

        // Check if we need to rollback
        $any_table_created = false;
        foreach ($check_tables as $table) {
            if (!$tables_exist[$table] && $CI->db->table_exists($table)) {
                $any_table_created = true;
                break;
            }
        }

        if ($any_table_created) {
            echo "Attempting automatic rollback (running DOWN method)...\n";
            try {
                $migration->down();
                echo "✓ Rollback completed\n";
            } catch (Exception $e2) {
                echo "✗ ERROR during rollback:\n";
                echo "  Message: " . $e2->getMessage() . "\n";
                echo "  You may need to manually clean up the database\n";
            }
        }

        throw $e;
    }

    exit(0);

} catch (Exception $e) {
    echo "\n=== FATAL ERROR ===\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
