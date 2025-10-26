<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 048: Add use_new_authorization table for per-user progressive migration
 *
 * Purpose: Enable granular testing of new authorization system with specific users
 *          before global rollout. This table lists usernames that should use the
 *          new authorization system while others remain on legacy system.
 *
 * Migration Strategy:
 * - Phase M2: Add 2-3 dev users for development testing
 * - Phase M3: Add 5-10 pilot users for production testing
 * - Phase M4: Global flag = TRUE (table ignored, all users on new system)
 * - Phase M5: Drop this table after successful global migration
 *
 * Rollback Strategy:
 * - Per-user: DELETE FROM use_new_authorization WHERE username = 'user'
 * - Full pilot: TRUNCATE use_new_authorization
 * - Global: Set $config['use_new_authorization'] = FALSE
 *
 * @see doc/prds/2025_authorization_refactoring_prd.md Section 6.1
 * @see doc/plans_and_progress/2025_authorization_refactoring_plan.md Phases M2-M5
 */
class Migration_Add_user_migration_table extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ],
            'username' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => FALSE,
                'comment' => 'Username to migrate to new authorization system'
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => TRUE,
                'comment' => 'When user was added to migration list'
            ],
            'notes' => [
                'type' => 'VARCHAR',
                'constraint' => '500',
                'null' => TRUE,
                'comment' => 'Optional notes (e.g. "Phase M2 dev testing", "Pilot user")'
            ]
        ]);

        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('username', FALSE, TRUE); // UNIQUE key

        $this->dbforge->create_table('use_new_authorization', TRUE, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
            'COMMENT' => 'Per-user progressive migration to new authorization system'
        ]);

        // Set default CURRENT_TIMESTAMP for created_at using trigger
        // (DATETIME with default CURRENT_TIMESTAMP requires MySQL 5.6.5+, so we use a trigger for compatibility)
        $this->db->query("
            CREATE TRIGGER trg_use_new_authorization_created_at
            BEFORE INSERT ON use_new_authorization
            FOR EACH ROW
            SET NEW.created_at = IFNULL(NEW.created_at, NOW())
        ");

        log_message('info', 'Migration 048: Created use_new_authorization table for per-user migration');
    }

    public function down()
    {
        // Drop trigger first
        $this->db->query("DROP TRIGGER IF EXISTS trg_use_new_authorization_created_at");

        // Drop table
        $this->dbforge->drop_table('use_new_authorization', TRUE);

        log_message('info', 'Migration 048: Dropped use_new_authorization table and trigger');
    }
}
