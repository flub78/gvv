<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 050: Add index on users.username
 *
 * Optimizes performance of joins between users and membres tables
 * for email list resolution queries.
 *
 * @see doc/plans_and_progress/gestion_emails_plan.md Phase 2.5
 */
class Migration_Add_users_username_index extends CI_Migration
{
    public function up()
    {
        // Add index on username for better join performance with membres table
        $this->db->query('ALTER TABLE users ADD INDEX idx_username (username)');

        log_message('info', 'Migration 050: Added index on users.username');
    }

    public function down()
    {
        // Drop the index
        $this->db->query('ALTER TABLE users DROP INDEX idx_username');

        log_message('info', 'Migration 050: Dropped index on users.username');
    }
}
