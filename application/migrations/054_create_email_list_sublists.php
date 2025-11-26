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
        // Check if table already exists (idempotence)
        if ($this->db->table_exists('email_list_sublists')) {
            log_message('info', 'Migration 054: email_list_sublists table already exists, skipping');
            return;
        }

        // Create the email_list_sublists table
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => TRUE
            ),
            'parent_list_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'null' => FALSE,
                'comment' => 'La liste parente qui contient des sous-listes'
            ),
            'child_list_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'null' => FALSE,
                'comment' => 'La liste simple incluse comme sous-liste'
            ),
            'added_at' => array(
                'type' => 'DATETIME',
                'null' => FALSE,
                'default' => 'CURRENT_TIMESTAMP'
            )
        ));

        $this->dbforge->add_key('id', TRUE); // Primary key

        $this->dbforge->create_table('email_list_sublists', TRUE, array(
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb3',
            'COLLATE' => 'utf8mb3_general_ci'
        ));

        // Add foreign key constraints
        // FK parent: CASCADE - deleting parent list removes all its sublist references
        $this->db->query('ALTER TABLE email_list_sublists
            ADD CONSTRAINT fk_email_list_sublists_parent
            FOREIGN KEY (parent_list_id) REFERENCES email_lists(id)
            ON DELETE CASCADE
            ON UPDATE CASCADE');

        // FK child: RESTRICT - cannot delete a list if it's used as sublist elsewhere
        $this->db->query('ALTER TABLE email_list_sublists
            ADD CONSTRAINT fk_email_list_sublists_child
            FOREIGN KEY (child_list_id) REFERENCES email_lists(id)
            ON DELETE RESTRICT
            ON UPDATE CASCADE');

        // Add unique constraint to prevent duplicate (parent, child) pairs
        $this->db->query('ALTER TABLE email_list_sublists
            ADD CONSTRAINT unique_parent_child UNIQUE (parent_list_id, child_list_id)');

        // Add indexes for performance
        $this->db->query('CREATE INDEX idx_parent ON email_list_sublists(parent_list_id)');
        $this->db->query('CREATE INDEX idx_child ON email_list_sublists(child_list_id)');

        log_message('info', 'Migration 054: email_list_sublists table created successfully');
    }

    public function down()
    {
        // Drop foreign key constraints first
        if ($this->db->table_exists('email_list_sublists')) {
            $this->db->query('ALTER TABLE email_list_sublists DROP FOREIGN KEY fk_email_list_sublists_parent');
            $this->db->query('ALTER TABLE email_list_sublists DROP FOREIGN KEY fk_email_list_sublists_child');
        }

        // Drop the table
        $this->dbforge->drop_table('email_list_sublists', TRUE);

        log_message('info', 'Migration 054: email_list_sublists table dropped successfully');
    }
}
