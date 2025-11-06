<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 051: Add source_file column to email_list_external
 *
 * Adds traceability for imported email addresses by tracking which file
 * they came from. Enables cascade deletion when a file is removed.
 *
 * Changes:
 * - Add source_file VARCHAR(255) NULL column
 * - Add composite index (email_list_id, source_file) for performance
 *
 * @see doc/prps/gestion_emails_plan.md Phase 3.7.1
 * @see doc/prds/gestion_emails.md Section 4.4.1
 */
class Migration_Add_source_file_to_email_list_external extends CI_Migration
{
    public function up()
    {
        // Check if table exists before trying to alter it
        if (!$this->db->table_exists('email_list_external')) {
            log_message('info', 'Migration 051: email_list_external table does not exist, skipping');
            return;
        }

        // Check if column already exists
        $fields = $this->db->field_data('email_list_external');
        $source_file_exists = false;
        foreach ($fields as $field) {
            if ($field->name === 'source_file') {
                $source_file_exists = true;
                break;
            }
        }

        if ($source_file_exists) {
            log_message('info', 'Migration 051: source_file column already exists, skipping');
            return;
        }

        // Add source_file column for file traceability
        $this->db->query("
            ALTER TABLE email_list_external
            ADD COLUMN source_file VARCHAR(255) NULL
            COMMENT 'Filename source if imported from file (NULL if manually added)'
            AFTER external_name
        ");

        // Add composite index for efficient queries and deletions by file
        $this->db->query('
            ALTER TABLE email_list_external
            ADD INDEX idx_source_file (email_list_id, source_file)
        ');

        log_message('info', 'Migration 051: Added source_file column and index to email_list_external');
    }

    public function down()
    {
        // Drop the composite index first
        $this->db->query('ALTER TABLE email_list_external DROP INDEX idx_source_file');

        // Drop the source_file column
        $this->db->query('ALTER TABLE email_list_external DROP COLUMN source_file');

        log_message('info', 'Migration 051: Removed source_file column and index from email_list_external');
    }
}
