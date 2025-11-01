<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 049: Create email lists tables
 *
 * Creates the database schema for email lists management:
 * - email_lists: Main table for email lists
 * - email_list_roles: Role-based member selection
 * - email_list_members: Manually added internal members
 * - email_list_external: External email addresses
 *
 * @see doc/prds/gestion_emails.md
 * @see doc/design_notes/gestion_emails_design.md
 */
class Migration_Create_email_lists extends CI_Migration
{
    public function up()
    {
        // Table: email_lists
        // Main table for managing email distribution lists
        $this->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => FALSE,
                'comment' => 'Unique list name (case-sensitive)'
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => TRUE,
                'comment' => 'Optional description'
            ],
            'active_member' => [
                'type' => 'ENUM',
                'constraint' => ['active', 'inactive', 'all'],
                'default' => 'active',
                'null' => FALSE,
                'comment' => 'Member status filter'
            ],
            'visible' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
                'null' => TRUE,
                'comment' => 'List visibility in selections'
            ],
            'created_by' => [
                'type' => 'INT',
                'unsigned' => TRUE,
                'null' => FALSE,
                'comment' => 'User ID who created the list'
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => FALSE,
                'comment' => 'Creation timestamp'
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => FALSE,
                'comment' => 'Last update timestamp'
            ]
        ]);
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('email_lists', TRUE, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci'
        ]);

        // Add unique index on name (case-sensitive via COLLATE utf8_bin)
        $this->db->query('ALTER TABLE email_lists MODIFY name VARCHAR(100) NOT NULL COLLATE utf8_bin');
        $this->db->query('ALTER TABLE email_lists ADD UNIQUE INDEX idx_name (name)');
        $this->db->query('ALTER TABLE email_lists ADD INDEX idx_created_by (created_by)');

        // Add FK to users table
        $this->db->query('ALTER TABLE email_lists ADD CONSTRAINT fk_email_lists_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT');

        // Add triggers for automatic timestamp management
        $this->db->query("
            CREATE TRIGGER email_lists_created_at
            BEFORE INSERT ON email_lists
            FOR EACH ROW
            SET NEW.created_at = IFNULL(NEW.created_at, NOW())
        ");

        $this->db->query("
            CREATE TRIGGER email_lists_updated_at
            BEFORE UPDATE ON email_lists
            FOR EACH ROW
            SET NEW.updated_at = NOW()
        ");

        // Table: email_list_roles
        // Dynamic member selection based on roles and sections
        $this->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => TRUE,
                'null' => FALSE
            ],
            'email_list_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => FALSE,
                'comment' => 'FK to email_lists'
            ],
            'types_roles_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => FALSE,
                'comment' => 'FK to types_roles'
            ],
            'section_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => FALSE,
                'comment' => 'FK to sections'
            ],
            'granted_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => TRUE,
                'comment' => 'User ID who granted this role'
            ],
            'granted_at' => [
                'type' => 'DATETIME',
                'null' => FALSE,
                'comment' => 'When role was granted'
            ],
            'revoked_at' => [
                'type' => 'DATETIME',
                'null' => TRUE,
                'comment' => 'When role was revoked (NULL if active)'
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => TRUE,
                'comment' => 'Optional notes'
            ]
        ]);
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('email_list_roles', TRUE, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_general_ci'
        ]);

        // Add indexes for email_list_roles
        $this->db->query('ALTER TABLE email_list_roles ADD INDEX idx_email_list_id (email_list_id)');
        $this->db->query('ALTER TABLE email_list_roles ADD INDEX idx_types_roles_id (types_roles_id)');
        $this->db->query('ALTER TABLE email_list_roles ADD INDEX idx_section_id (section_id)');

        // Add foreign keys for email_list_roles
        $this->db->query('ALTER TABLE email_list_roles ADD CONSTRAINT fk_elr_email_list_id FOREIGN KEY (email_list_id) REFERENCES email_lists(id) ON DELETE CASCADE');
        $this->db->query('ALTER TABLE email_list_roles ADD CONSTRAINT fk_elr_types_roles_id FOREIGN KEY (types_roles_id) REFERENCES types_roles(id) ON DELETE RESTRICT');
        $this->db->query('ALTER TABLE email_list_roles ADD CONSTRAINT fk_elr_section_id FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE RESTRICT');

        // Add trigger for automatic timestamp
        $this->db->query("
            CREATE TRIGGER email_list_roles_granted_at
            BEFORE INSERT ON email_list_roles
            FOR EACH ROW
            SET NEW.granted_at = IFNULL(NEW.granted_at, NOW())
        ");

        // Table: email_list_members
        // Manually added internal members
        $this->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ],
            'email_list_id' => [
                'type' => 'INT',
                'unsigned' => TRUE,
                'null' => FALSE,
                'comment' => 'FK to email_lists'
            ],
            'membre_id' => [
                'type' => 'VARCHAR',
                'constraint' => 25,
                'null' => FALSE,
                'comment' => 'FK to membres.mlogin'
            ],
            'added_at' => [
                'type' => 'DATETIME',
                'null' => FALSE,
                'comment' => 'When member was added'
            ]
        ]);
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('email_list_members', TRUE, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci'
        ]);

        // Add indexes for email_list_members
        $this->db->query('ALTER TABLE email_list_members ADD INDEX idx_email_list_id (email_list_id)');
        $this->db->query('ALTER TABLE email_list_members ADD INDEX idx_membre_id (membre_id)');

        // Add foreign keys for email_list_members
        $this->db->query('ALTER TABLE email_list_members ADD CONSTRAINT fk_elm_email_list_id FOREIGN KEY (email_list_id) REFERENCES email_lists(id) ON DELETE CASCADE');
        $this->db->query('ALTER TABLE email_list_members ADD CONSTRAINT fk_elm_membre_id FOREIGN KEY (membre_id) REFERENCES membres(mlogin) ON DELETE CASCADE');

        // Add trigger for automatic timestamp
        $this->db->query("
            CREATE TRIGGER email_list_members_added_at
            BEFORE INSERT ON email_list_members
            FOR EACH ROW
            SET NEW.added_at = IFNULL(NEW.added_at, NOW())
        ");

        // Table: email_list_external
        // External email addresses
        $this->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ],
            'email_list_id' => [
                'type' => 'INT',
                'unsigned' => TRUE,
                'null' => FALSE,
                'comment' => 'FK to email_lists'
            ],
            'external_email' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => FALSE,
                'comment' => 'External email address'
            ],
            'external_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => TRUE,
                'comment' => 'Optional display name'
            ],
            'added_at' => [
                'type' => 'DATETIME',
                'null' => FALSE,
                'comment' => 'When email was added'
            ]
        ]);
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('email_list_external', TRUE, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci'
        ]);

        // Add index for email_list_external
        $this->db->query('ALTER TABLE email_list_external ADD INDEX idx_email_list_id (email_list_id)');

        // Add foreign key for email_list_external
        $this->db->query('ALTER TABLE email_list_external ADD CONSTRAINT fk_ele_email_list_id FOREIGN KEY (email_list_id) REFERENCES email_lists(id) ON DELETE CASCADE');

        // Add trigger for automatic timestamp
        $this->db->query("
            CREATE TRIGGER email_list_external_added_at
            BEFORE INSERT ON email_list_external
            FOR EACH ROW
            SET NEW.added_at = IFNULL(NEW.added_at, NOW())
        ");

        log_message('info', 'Migration 049: Created email lists tables (email_lists, email_list_roles, email_list_members, email_list_external)');
    }

    public function down()
    {
        // Drop triggers first
        $this->db->query('DROP TRIGGER IF EXISTS email_list_external_added_at');
        $this->db->query('DROP TRIGGER IF EXISTS email_list_members_added_at');
        $this->db->query('DROP TRIGGER IF EXISTS email_list_roles_granted_at');
        $this->db->query('DROP TRIGGER IF EXISTS email_lists_updated_at');
        $this->db->query('DROP TRIGGER IF EXISTS email_lists_created_at');

        // Drop tables in reverse order (to respect FK dependencies)
        $this->dbforge->drop_table('email_list_external', TRUE);
        $this->dbforge->drop_table('email_list_members', TRUE);
        $this->dbforge->drop_table('email_list_roles', TRUE);
        $this->dbforge->drop_table('email_lists', TRUE);

        log_message('info', 'Migration 049: Dropped email lists tables and triggers');
    }
}
