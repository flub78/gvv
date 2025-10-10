#!/usr/bin/env php
<?php
/**
 * Cleanup script for temporary attachment files
 *
 * Run daily via cron: 0 2 * * * php /path/to/scripts/cleanup_temp_attachments.php
 *
 * Deletes temporary attachment files older than 24 hours
 */

// Bootstrap CodeIgniter
define('BASEPATH', TRUE);
$_SERVER['REQUEST_URI'] = '/scripts/cleanup';
require_once __DIR__ . '/../index.php';

$temp_dir = './uploads/attachments/temp/';
$max_age = 86400; // 24 hours in seconds
$now = time();

$deleted_count = 0;
$deleted_size = 0;
$error_count = 0;

echo "=== Temporary Attachment Cleanup ===\n";
echo "Started: " . date('Y-m-d H:i:s') . "\n";
echo "Max age: " . ($max_age / 3600) . " hours\n\n";

if (!is_dir($temp_dir)) {
    echo "Temp directory not found: $temp_dir\n";
    exit(0);
}

// Iterate through session directories
$session_dirs = glob($temp_dir . '*', GLOB_ONLYDIR);

foreach ($session_dirs as $session_dir) {
    $session_id = basename($session_dir);

    // Check age of directory
    $dir_mtime = filemtime($session_dir);
    $age = $now - $dir_mtime;

    if ($age > $max_age) {
        echo "Processing session: $session_id (age: " . round($age / 3600, 1) . "h)\n";

        // Delete files in directory
        $files = glob($session_dir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                $size = filesize($file);
                if (unlink($file)) {
                    $deleted_count++;
                    $deleted_size += $size;
                    echo "  Deleted: " . basename($file) . " (" . round($size / 1024, 1) . " KB)\n";
                } else {
                    $error_count++;
                    echo "  ERROR: Could not delete " . basename($file) . "\n";
                }
            }
        }

        // Remove empty directory
        if (rmdir($session_dir)) {
            echo "  Removed session directory\n";
        } else {
            echo "  WARNING: Could not remove session directory\n";
        }
    }
}

echo "\n=== Summary ===\n";
echo "Files deleted: $deleted_count\n";
echo "Space freed: " . round($deleted_size / (1024 * 1024), 2) . " MB\n";
echo "Errors: $error_count\n";
echo "Completed: " . date('Y-m-d H:i:s') . "\n";

exit($error_count > 0 ? 1 : 0);
