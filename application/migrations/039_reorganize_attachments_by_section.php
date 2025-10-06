<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Migration 039: Reorganize Attachments by Section
 *
 * This migration adds a backup column to the attachments table
 * to preserve original file paths before reorganizing files into
 * section-specific subdirectories.
 *
 * Directory structure change:
 * OLD: ./uploads/attachments/YYYY/filename
 * NEW: ./uploads/attachments/YYYY/SECTION/filename
 *
 * Note: The actual file movement is handled by a separate PHP script
 * (scripts/reorganize_attachments.php) that should be run after this migration.
 */
class Migration_Reorganize_Attachments_By_Section extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 39;
    }

    /**
     * Apply the migration
     *
     * Adds a backup column to store original file paths before reorganization
     */
    public function up() {
        $errors = 0;

        // Step 1: Add a backup column for original file paths
        $sql = "ALTER TABLE `attachments` ADD COLUMN `file_backup` VARCHAR(255) DEFAULT NULL COMMENT 'Backup of original file path before section reorganization'";
        gvv_info("Migration sql: " . $sql);
        if (!$this->db->query($sql)) {
            gvv_error("Migration error: " . $this->db->error()['message']);
            $errors++;
        }

        // Step 2: Backup all current file paths
        $sql = "UPDATE `attachments` SET `file_backup` = `file` WHERE `file` IS NOT NULL";
        gvv_info("Migration sql: " . $sql);
        if (!$this->db->query($sql)) {
            gvv_error("Migration error: " . $this->db->error()['message']);
            $errors++;
        }

        // Step 3: Log backup completion
        if ($errors === 0) {
            $count_result = $this->db->query("SELECT COUNT(*) as count FROM `attachments` WHERE `file_backup` IS NOT NULL");
            $count = $count_result ? $count_result->row()->count : 0;
            gvv_info("Migration: Backed up $count attachment file paths");
            gvv_info("Migration: Next step - Run scripts/reorganize_attachments.php to move files");
        }

        gvv_info("Migration database up to " . $this->migration_number . ", errors=$errors");
        return !$errors;
    }

    /**
     * Reverse the migration
     *
     * Restores original file paths and removes the backup column
     */
    public function down() {
        $errors = 0;

        // Step 1: Restore original file paths from backup
        $sql = "UPDATE `attachments` SET `file` = `file_backup` WHERE `file_backup` IS NOT NULL";
        gvv_info("Migration sql: " . $sql);
        if (!$this->db->query($sql)) {
            gvv_error("Migration error: " . $this->db->error()['message']);
            $errors++;
        }

        // Step 2: Remove backup column
        $sql = "ALTER TABLE `attachments` DROP COLUMN `file_backup`";
        gvv_info("Migration sql: " . $sql);
        if (!$this->db->query($sql)) {
            gvv_error("Migration error: " . $this->db->error()['message']);
            $errors++;
        }

        gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");
        return !$errors;
    }
}

/* End of file 039_reorganize_attachments_by_section.php */
/* Location: ./application/migrations/039_reorganize_attachments_by_section.php */
