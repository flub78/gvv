#!/usr/bin/env php
<?php
/**
 * Isolated CLI script to run database migrations
 * This is a wrapper that runs migration in a fresh PHP process
 *
 * Usage:
 *   php run_migration_isolated.php <version>
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
    echo "Usage: php run_migration_isolated.php <version|current>\n";
    exit(1);
}

// Load migration library
$CI =& get_instance();
$CI->load->library('migration');

// Determine target version
if ($target_version === 'current') {
    // Get current migration version from database
    $CI->db->select('version');
    $result = $CI->db->get('migrations')->result();
    $current = !empty($result) ? $result[0]->version : 0;
    
    // Find latest migration file
    $migration_path = APPPATH . 'migrations/';
    $files = scandir($migration_path);
    $latest = 0;
    foreach ($files as $file) {
        if (preg_match('/^(\d+)_/', $file, $matches)) {
            $version = intval($matches[1]);
            if ($version > $latest) {
                $latest = $version;
            }
        }
    }
    $target_version = $latest;
} else {
    $target_version = intval($target_version);
}

// Perform migration
try {
    $result = $CI->migration->version($target_version);
    
    if ($result === TRUE) {
        echo "✓ Migration to version $target_version successful\n";
        exit(0);
    } else {
        echo "✗ Migration failed: " . $CI->migration->error_string() . "\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ Migration error: " . $e->getMessage() . "\n";
    exit(1);
}
