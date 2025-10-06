<?php
/**
 * Script to reorganize attachments by section
 *
 * This script moves existing attachment files from the old directory structure
 * (uploads/attachments/YYYY/filename) to the new section-based structure
 * (uploads/attachments/YYYY/SECTION/filename) and updates the database paths.
 *
 * Usage: php scripts/reorganize_attachments.php [--dry-run] [--verbose]
 *
 * Options:
 *   --dry-run   : Preview changes without actually moving files or updating database
 *   --verbose   : Show detailed progress for each file
 *
 * Prerequisites:
 *   - Migration 039 must be run first (adds file_backup column)
 *   - Database credentials must be configured in application/config/database.php
 */

// Bootstrap CodeIgniter
$_SERVER['REQUEST_METHOD'] = 'CLI';
define('BASEPATH', TRUE);
require_once __DIR__ . '/../index.php';

class AttachmentsReorganizer {

    private $CI;
    private $dry_run = false;
    private $verbose = false;
    private $stats = [
        'total' => 0,
        'moved' => 0,
        'errors' => 0,
        'skipped' => 0
    ];

    public function __construct($dry_run = false, $verbose = false) {
        $this->CI =& get_instance();
        $this->CI->load->model('attachments_model');
        $this->CI->load->model('sections_model');
        $this->dry_run = $dry_run;
        $this->verbose = $verbose;
    }

    public function run() {
        echo "=== Attachments Reorganization Script ===\n";
        echo "Mode: " . ($this->dry_run ? "DRY RUN" : "LIVE") . "\n\n";

        // Get all attachments
        $this->CI->db->select('id, file, club');
        $this->CI->db->from('attachments');
        $this->CI->db->where('file IS NOT NULL');
        $this->CI->db->where("file != ''");
        $query = $this->CI->db->get();

        if (!$query) {
            echo "Error: Could not fetch attachments\n";
            return false;
        }

        $attachments = $query->result_array();
        $this->stats['total'] = count($attachments);

        echo "Found {$this->stats['total']} attachments to process\n\n";

        // Get sections mapping
        $sections = $this->get_sections_map();
        echo "Loaded " . count($sections) . " sections\n\n";

        // Process each attachment
        foreach ($attachments as $attachment) {
            $this->process_attachment($attachment, $sections);
        }

        // Print summary
        $this->print_summary();

        return $this->stats['errors'] == 0;
    }

    private function get_sections_map() {
        $this->CI->db->select('id, nom');
        $this->CI->db->from('sections');
        $query = $this->CI->db->get();

        $map = [];
        if ($query) {
            foreach ($query->result_array() as $row) {
                $map[$row['id']] = $row['nom'];
            }
        }
        return $map;
    }

    private function process_attachment($attachment, $sections) {
        $id = $attachment['id'];
        $old_path = $attachment['file'];
        $club_id = $attachment['club'];

        // Skip if already in new format (contains section subdirectory)
        if (preg_match('#/attachments/\d{4}/[^/]+/[^/]+$#', $old_path)) {
            if ($this->verbose) {
                echo "SKIP: ID $id already in new format\n";
            }
            $this->stats['skipped']++;
            return;
        }

        // Determine section name
        $section_name = isset($sections[$club_id]) ? $sections[$club_id] : 'Unknown';

        // Sanitize section name (replace spaces with underscores for directory names)
        $section_name = str_replace(' ', '_', $section_name);

        // Extract year and filename from old path
        if (!preg_match('#/attachments/(\d{4})/([^/]+)$#', $old_path, $matches)) {
            echo "ERROR: ID $id - Invalid path format: $old_path\n";
            $this->stats['errors']++;
            return;
        }

        $year = $matches[1];
        $filename = $matches[2];

        // Build new path
        $new_path = "./uploads/attachments/{$year}/{$section_name}/{$filename}";

        // Check if source file exists
        if (!file_exists($old_path)) {
            echo "ERROR: ID $id - Source file not found: $old_path\n";
            $this->stats['errors']++;
            return;
        }

        if ($this->verbose) {
            echo "Processing ID $id: $old_path -> $new_path\n";
        }

        if (!$this->dry_run) {
            // Create target directory
            $target_dir = "./uploads/attachments/{$year}/{$section_name}";
            if (!is_dir($target_dir)) {
                if (!mkdir($target_dir, 0777, true)) {
                    echo "ERROR: ID $id - Failed to create directory: $target_dir\n";
                    $this->stats['errors']++;
                    return;
                }
                // Explicitly set permissions to override umask
                chmod($target_dir, 0777);
            }

            // Move file
            if (!rename($old_path, $new_path)) {
                echo "ERROR: ID $id - Failed to move file\n";
                $this->stats['errors']++;
                return;
            }

            // Update database
            $this->CI->db->where('id', $id);
            if (!$this->CI->db->update('attachments', ['file' => $new_path])) {
                echo "ERROR: ID $id - Failed to update database\n";
                // Try to move file back
                rename($new_path, $old_path);
                $this->stats['errors']++;
                return;
            }
        }

        $this->stats['moved']++;
    }

    private function print_summary() {
        echo "\n=== Summary ===\n";
        echo "Total attachments: {$this->stats['total']}\n";
        echo "Successfully moved: {$this->stats['moved']}\n";
        echo "Skipped (already migrated): {$this->stats['skipped']}\n";
        echo "Errors: {$this->stats['errors']}\n";

        if ($this->dry_run) {
            echo "\nThis was a DRY RUN - no changes were made\n";
        } else {
            echo "\nFiles have been moved and database updated\n";
            echo "Original paths preserved in file_backup column\n";
        }
    }
}

// Parse command line arguments
$dry_run = in_array('--dry-run', $argv);
$verbose = in_array('--verbose', $argv);

// Run reorganization
$reorganizer = new AttachmentsReorganizer($dry_run, $verbose);
$success = $reorganizer->run();

exit($success ? 0 : 1);
