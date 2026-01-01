#!/usr/bin/env php
<?php
/**
 * CLI script to run database migrations
 *
 * Usage:
 *   php run_migration.php <version>
 *
 * Examples:
 *   php run_migration.php 25       - Migrate to version 25
 *   php run_migration.php current  - Migrate to current (latest) version
 */

// Set up environment
define('ENVIRONMENT', 'development');
$system_path = 'system';
$application_folder = 'application';

// Set correct directory for CLI
if (defined('STDIN')) {
    chdir(dirname(__FILE__));
}

if (realpath($system_path) !== FALSE) {
    $system_path = realpath($system_path) . '/';
}
$system_path = rtrim($system_path, '/') . '/';

define('BASEPATH', str_replace("\\", "/", $system_path));
define('APPPATH', $application_folder . '/');
define('FCPATH', dirname(__FILE__) . '/');
define('SYSDIR', trim(strrchr(trim(BASEPATH, '/'), '/'), '/'));
define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));
define('EXT', '.php');

// Bootstrap CodeIgniter
require_once BASEPATH . 'core/CodeIgniter.php';

// Get target version from command line
$target_version = isset($argv[1]) ? $argv[1] : null;

if ($target_version === null) {
    echo "Error: No migration version specified\n";
    echo "Usage: php run_migration.php <version|current>\n";
    exit(1);
}

// Load migration library
$CI =& get_instance();
$CI->load->library('migration');

// Run migration
echo "Running migration to version: $target_version\n";

try {
    if ($target_version === 'current') {
        $result = $CI->migration->current();
    } else {
        $result = $CI->migration->version((int)$target_version);
    }

    if ($result === FALSE) {
        echo "ERROR: Migration failed\n";
        echo "Error: " . $CI->migration->error_string() . "\n";
        exit(1);
    } else {
        echo "SUCCESS: Migration completed\n";

        // Get and display current version
        $current = $CI->db->query("SELECT version FROM migrations LIMIT 1")->row();
        if ($current) {
            echo "Current database version: " . $current->version . "\n";
        }
        exit(0);
    }
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
