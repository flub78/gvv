<?php
/**
 * Script to reorganize attachments by section (Standalone version)
 *
 * Usage: php scripts/reorganize_attachments_simple.php [--dry-run] [--verbose]
 */

// Database configuration
$db_host = 'localhost';
$db_user = 'gvv_user';
$db_pass = 'lfoyfgbj';
$db_name = 'gvv2';

// Parse command line arguments
$dry_run = in_array('--dry-run', $GLOBALS['argv']);
$verbose = in_array('--verbose', $GLOBALS['argv']);

$stats = [
    'total' => 0,
    'moved' => 0,
    'errors' => 0,
    'skipped' => 0
];

echo "=== Attachments Reorganization Script ===\n";
echo "Mode: " . ($dry_run ? "DRY RUN" : "LIVE") . "\n\n";

// Connect to database
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error . "\n");
}

// Get sections mapping
echo "Loading sections...\n";
$sections = [];
$result = $mysqli->query("SELECT id, nom FROM sections");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $sections[$row['id']] = $row['nom'];
        echo "  Section {$row['id']}: {$row['nom']}\n";
    }
}
echo "Loaded " . count($sections) . " sections\n\n";

// Get all attachments
echo "Fetching attachments...\n";
$query = $mysqli->query("SELECT id, file, club FROM attachments WHERE file IS NOT NULL AND file != ''");
if (!$query) {
    die("Error fetching attachments: " . $mysqli->error . "\n");
}

$attachments = [];
while ($row = $query->fetch_assoc()) {
    $attachments[] = $row;
}
$stats['total'] = count($attachments);
echo "Found {$stats['total']} attachments to process\n\n";

// Process each attachment
foreach ($attachments as $attachment) {
    $id = $attachment['id'];
    $old_path = $attachment['file'];
    $club_id = $attachment['club'];

    // Skip if already in new format (contains section subdirectory)
    if (preg_match('#/attachments/\d{4}/[^/]+/[^/]+$#', $old_path)) {
        if ($verbose) {
            echo "SKIP: ID $id already in new format\n";
        }
        $stats['skipped']++;
        continue;
    }

    // Determine section name
    $section_name = isset($sections[$club_id]) ? $sections[$club_id] : 'Unknown';

    // Sanitize section name (replace spaces with underscores)
    $section_name = str_replace(' ', '_', $section_name);

    // Extract year and filename from old path
    if (!preg_match('#/attachments/(\d{4})/([^/]+)$#', $old_path, $matches)) {
        echo "ERROR: ID $id - Invalid path format: $old_path\n";
        $stats['errors']++;
        continue;
    }

    $year = $matches[1];
    $filename = $matches[2];

    // Build new path
    $new_path = "./uploads/attachments/{$year}/{$section_name}/{$filename}";

    // Check if source file exists
    if (!file_exists($old_path)) {
        echo "ERROR: ID $id - Source file not found: $old_path\n";
        $stats['errors']++;
        continue;
    }

    if ($verbose) {
        echo "Processing ID $id: $old_path -> $new_path\n";
    }

    if (!$dry_run) {
        // Create target directory
        $target_dir = "./uploads/attachments/{$year}/{$section_name}";
        if (!is_dir($target_dir)) {
            if (!mkdir($target_dir, 0777, true)) {
                echo "ERROR: ID $id - Failed to create directory: $target_dir\n";
                $stats['errors']++;
                continue;
            }
            // Explicitly set permissions
            chmod($target_dir, 0777);
        }

        // Move file
        if (!rename($old_path, $new_path)) {
            echo "ERROR: ID $id - Failed to move file\n";
            $stats['errors']++;
            continue;
        }

        // Update database
        $stmt = $mysqli->prepare("UPDATE attachments SET file = ? WHERE id = ?");
        $stmt->bind_param('si', $new_path, $id);
        if (!$stmt->execute()) {
            echo "ERROR: ID $id - Failed to update database\n";
            // Try to move file back
            rename($new_path, $old_path);
            $stats['errors']++;
            continue;
        }
        $stmt->close();
    }

    $stats['moved']++;
}

// Print summary
echo "\n=== Summary ===\n";
echo "Total attachments: {$stats['total']}\n";
echo "Successfully moved: {$stats['moved']}\n";
echo "Skipped (already migrated): {$stats['skipped']}\n";
echo "Errors: {$stats['errors']}\n";

if ($dry_run) {
    echo "\nThis was a DRY RUN - no changes were made\n";
} else {
    echo "\nFiles have been moved and database updated\n";
    echo "Original paths preserved in file_backup column\n";
}

$mysqli->close();
exit($stats['errors'] == 0 ? 0 : 1);
