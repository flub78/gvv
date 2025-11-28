<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 054: Create email_list_sublists table
 *
 * This migration creates the email_list_sublists table to support sublists
 * in email lists. A list can include other lists as sublists, with automatic
 * deduplication of recipients.
 *
 * Constraints:
 * - A list cannot contain itself (validated in model)
 * - A sublist cannot contain sublists (depth = 1 only)
 * - Visibility coherence: public list can only contain public sublists
 * - ON DELETE CASCADE on parent_list_id: deleting parent removes references
 * - ON DELETE RESTRICT on child_list_id: cannot delete a list used as sublist
 */
class Migration_Create_email_list_sublists extends CI_Migration {

    public function up()
    {
        log_message('info', 'Migration 054: Starting email_list_sublists table creation');

        // Check if table already exists (idempotence) - use raw SQL to avoid cache issues
        $query = $this->db->query("SHOW TABLES LIKE 'email_list_sublists'");
        if ($query->num_rows() > 0) {
            log_message('info', 'Migration 054: email_list_sublists table already exists, skipping');
            return;
        }

        // Verify parent table exists
        if (!$this->db->table_exists('email_lists')) {
            log_message('error', 'Migration 054: Parent table email_lists does not exist');
            throw new Exception('Migration 054: Cannot create email_list_sublists without email_lists table');
        }
        log_message('info', 'Migration 054: Parent table email_lists verified');

        // Create the email_list_sublists table using raw SQL for compatibility
        log_message('info', 'Migration 054: Creating table structure');
        $sql = "CREATE TABLE `email_list_sublists` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `parent_list_id` int(11) NOT NULL COMMENT 'La liste parente qui contient des sous-listes',
            `child_list_id` int(11) NOT NULL COMMENT 'La liste simple incluse comme sous-liste',
            `added_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci";

        $result = $this->db->query($sql);

        if ($result === FALSE) {
            log_message('error', 'Migration 054: Failed to create table email_list_sublists');
            throw new Exception('Migration 054: Table creation failed');
        }

        // Clear CodeIgniter table cache to detect newly created table
        $this->db->data_cache = array();

        // Verify table was created using raw SQL (table_exists() may have cache issues)
        $verify = $this->db->query("SHOW TABLES LIKE 'email_list_sublists'");
        if ($verify->num_rows() === 0) {
            log_message('error', 'Migration 054: Table creation succeeded but table not found');
            throw new Exception('Migration 054: Table verification failed after creation');
        }
        log_message('info', 'Migration 054: Table created and verified');

        // Add foreign key constraints
        // FK parent: CASCADE - deleting parent list removes all its sublist references
        log_message('info', 'Migration 054: Adding foreign key constraint fk_email_list_sublists_parent');
        $result = $this->db->query('ALTER TABLE email_list_sublists
            ADD CONSTRAINT fk_email_list_sublists_parent
            FOREIGN KEY (parent_list_id) REFERENCES email_lists(id)
            ON DELETE CASCADE
            ON UPDATE CASCADE');

        if ($result === FALSE) {
            log_message('error', 'Migration 054: Failed to add FK parent');
            throw new Exception('Migration 054: Foreign key constraint (parent) failed');
        }

        // FK child: RESTRICT - cannot delete a list if it's used as sublist elsewhere
        log_message('info', 'Migration 054: Adding foreign key constraint fk_email_list_sublists_child');
        $result = $this->db->query('ALTER TABLE email_list_sublists
            ADD CONSTRAINT fk_email_list_sublists_child
            FOREIGN KEY (child_list_id) REFERENCES email_lists(id)
            ON DELETE RESTRICT
            ON UPDATE CASCADE');

        if ($result === FALSE) {
            log_message('error', 'Migration 054: Failed to add FK child');
            throw new Exception('Migration 054: Foreign key constraint (child) failed');
        }

        // Add unique constraint to prevent duplicate (parent, child) pairs
        log_message('info', 'Migration 054: Adding unique constraint unique_parent_child');
        $result = $this->db->query('ALTER TABLE email_list_sublists
            ADD CONSTRAINT unique_parent_child UNIQUE (parent_list_id, child_list_id)');

        if ($result === FALSE) {
            log_message('error', 'Migration 054: Failed to add unique constraint');
            throw new Exception('Migration 054: Unique constraint failed');
        }

        // Add indexes for performance
        log_message('info', 'Migration 054: Creating index idx_parent');
        $result = $this->db->query('CREATE INDEX idx_parent ON email_list_sublists(parent_list_id)');

        if ($result === FALSE) {
            log_message('error', 'Migration 054: Failed to create index idx_parent');
            throw new Exception('Migration 054: Index creation (parent) failed');
        }

        log_message('info', 'Migration 054: Creating index idx_child');
        $result = $this->db->query('CREATE INDEX idx_child ON email_list_sublists(child_list_id)');

        if ($result === FALSE) {
            log_message('error', 'Migration 054: Failed to create index idx_child');
            throw new Exception('Migration 054: Index creation (child) failed');
        }

        // Final verification
        $query = $this->db->query("SHOW CREATE TABLE email_list_sublists");
        if ($query->num_rows() === 0) {
            log_message('error', 'Migration 054: Final verification failed - table not found');
            throw new Exception('Migration 054: Final table verification failed');
        }

        log_message('info', 'Migration 054: email_list_sublists table created successfully with all constraints and indexes');
    }

    public function down()
    {
        log_message('info', 'Migration 054: Starting email_list_sublists table removal');

        // Check if table exists before attempting to drop
        if (!$this->db->table_exists('email_list_sublists')) {
            log_message('info', 'Migration 054: email_list_sublists table does not exist, nothing to drop');
            return;
        }

        // Drop foreign key constraints first
        log_message('info', 'Migration 054: Dropping foreign key constraints');

        // Try to drop FK constraints - don't fail if they don't exist
        @$this->db->query('ALTER TABLE email_list_sublists DROP FOREIGN KEY fk_email_list_sublists_parent');
        @$this->db->query('ALTER TABLE email_list_sublists DROP FOREIGN KEY fk_email_list_sublists_child');

        // Drop the table
        log_message('info', 'Migration 054: Dropping table email_list_sublists');
        $result = $this->db->query('DROP TABLE IF EXISTS `email_list_sublists`');

        if ($result === FALSE) {
            log_message('error', 'Migration 054: Failed to drop table email_list_sublists');
            throw new Exception('Migration 054: Table drop failed');
        }

        // Verify table was dropped
        if ($this->db->table_exists('email_list_sublists')) {
            log_message('error', 'Migration 054: Table drop succeeded but table still exists');
            throw new Exception('Migration 054: Table verification failed after drop');
        }

        log_message('info', 'Migration 054: email_list_sublists table dropped successfully');
    }
}
