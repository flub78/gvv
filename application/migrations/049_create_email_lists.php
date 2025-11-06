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
    /**
     * Check if an index exists on a table
     *
     * @param string $table_name Table name
     * @param string $index_name Index name
     * @return bool True if index exists
     */
    private function index_exists($table_name, $index_name)
    {
        $query = $this->db->query("
            SELECT COUNT(*) as count
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
            AND table_name = ?
            AND index_name = ?
        ", array($table_name, $index_name));

        $result = $query->row_array();
        return $result['count'] > 0;
    }

    public function up()
    {
        // Check if all tables already exist (skip if they do)
        if ($this->db->table_exists('email_lists') && 
            $this->db->table_exists('email_list_roles') &&
            $this->db->table_exists('email_list_members') &&
            $this->db->table_exists('email_list_external')) {
            log_message('info', 'Migration 049: all email lists tables already exist, skipping');
            return;
        }

        try {
            // Table: email_lists
            // Main table for managing email distribution lists
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
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
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'default' => 'active',
                    'null' => FALSE,
                    'comment' => 'Member status filter (active/inactive/all)'
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
                    'constraint' => 11,
                    'unsigned' => FALSE,
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

            // Fix created_by column type to match users.id (signed INT)
            $this->db->query('ALTER TABLE email_lists MODIFY created_by INT(11) NOT NULL COMMENT "User ID who created the list"');

            // Add unique index on name (case-sensitive via COLLATE utf8_bin)
            $this->db->query('ALTER TABLE email_lists MODIFY name VARCHAR(100) NOT NULL COLLATE utf8_bin');

            if (!$this->index_exists('email_lists', 'idx_name')) {
                $this->db->query('ALTER TABLE email_lists ADD UNIQUE INDEX idx_name (name)');
            }

            if (!$this->index_exists('email_lists', 'idx_created_by')) {
                $this->db->query('ALTER TABLE email_lists ADD INDEX idx_created_by (created_by)');
            }

            // Convert active_member to ENUM after table creation
            $this->db->query("ALTER TABLE email_lists MODIFY active_member ENUM('active', 'inactive', 'all') DEFAULT 'active' NOT NULL");

            // Add FK to users table (wrapped in try-catch for production compatibility)
            try {
                $this->db->query('ALTER TABLE email_lists ADD CONSTRAINT fk_email_lists_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT');
            } catch (Exception $e) {
                log_message('error', 'Migration 049: Could not add FK fk_email_lists_created_by: ' . $e->getMessage());
            }

            // Set up automatic timestamp management for updated_at (MySQL 5.6.5+)
            try {
                $this->db->query('ALTER TABLE email_lists MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT "Creation timestamp"');
                $this->db->query('ALTER TABLE email_lists MODIFY updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT "Last update timestamp"');
            } catch (Exception $e) {
                log_message('error', 'Migration 049: Could not set CURRENT_TIMESTAMP (MySQL <5.6.5?): ' . $e->getMessage());
                // Fallback for older MySQL versions
                $this->db->query('ALTER TABLE email_lists MODIFY created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT "Creation timestamp"');
                $this->db->query('ALTER TABLE email_lists MODIFY updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT "Last update timestamp"');
            }

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
        $result = $this->dbforge->create_table('email_list_roles', TRUE, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_general_ci'
        ]);
        
        if ($result === FALSE) {
            throw new Exception('Failed to create table email_list_roles');
        }
        
        // Set default for granted_at
        $this->db->query('ALTER TABLE email_list_roles MODIFY granted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT "When role was granted"');

        // Add indexes for email_list_roles
        if (!$this->index_exists('email_list_roles', 'idx_email_list_id')) {
            $this->db->query('ALTER TABLE email_list_roles ADD INDEX idx_email_list_id (email_list_id)');
        }
        if (!$this->index_exists('email_list_roles', 'idx_types_roles_id')) {
            $this->db->query('ALTER TABLE email_list_roles ADD INDEX idx_types_roles_id (types_roles_id)');
        }
        if (!$this->index_exists('email_list_roles', 'idx_section_id')) {
            $this->db->query('ALTER TABLE email_list_roles ADD INDEX idx_section_id (section_id)');
        }

        // Add foreign keys for email_list_roles
        $this->db->query('ALTER TABLE email_list_roles ADD CONSTRAINT fk_elr_email_list_id FOREIGN KEY (email_list_id) REFERENCES email_lists(id) ON DELETE CASCADE');
        $this->db->query('ALTER TABLE email_list_roles ADD CONSTRAINT fk_elr_types_roles_id FOREIGN KEY (types_roles_id) REFERENCES types_roles(id) ON DELETE RESTRICT');
        $this->db->query('ALTER TABLE email_list_roles ADD CONSTRAINT fk_elr_section_id FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE RESTRICT');

        // Table: email_list_members
        // Manually added internal members
        $this->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => TRUE
            ],
            'email_list_id' => [
                'type' => 'INT',
                'constraint' => 11,
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
        $result = $this->dbforge->create_table('email_list_members', TRUE, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci'
        ]);
        
        if ($result === FALSE) {
            throw new Exception('Failed to create table email_list_members');
        }
        
        // Set default for added_at
        $this->db->query('ALTER TABLE email_list_members MODIFY added_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT "When member was added"');

        // Add indexes for email_list_members
        if (!$this->index_exists('email_list_members', 'idx_email_list_id')) {
            $this->db->query('ALTER TABLE email_list_members ADD INDEX idx_email_list_id (email_list_id)');
        }
        if (!$this->index_exists('email_list_members', 'idx_membre_id')) {
            $this->db->query('ALTER TABLE email_list_members ADD INDEX idx_membre_id (membre_id)');
        }

        // Add foreign keys for email_list_members
        $this->db->query('ALTER TABLE email_list_members ADD CONSTRAINT fk_elm_email_list_id FOREIGN KEY (email_list_id) REFERENCES email_lists(id) ON DELETE CASCADE');
        $this->db->query('ALTER TABLE email_list_members ADD CONSTRAINT fk_elm_membre_id FOREIGN KEY (membre_id) REFERENCES membres(mlogin) ON DELETE CASCADE');

        // Table: email_list_external
        // External email addresses
        $this->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => TRUE
            ],
            'email_list_id' => [
                'type' => 'INT',
                'constraint' => 11,
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
        $result = $this->dbforge->create_table('email_list_external', TRUE, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci'
        ]);
        
        if ($result === FALSE) {
            throw new Exception('Failed to create table email_list_external');
        }
        
        // Set default for added_at
        $this->db->query('ALTER TABLE email_list_external MODIFY added_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT "When email was added"');

        // Add index for email_list_external
        if (!$this->index_exists('email_list_external', 'idx_email_list_id')) {
            $this->db->query('ALTER TABLE email_list_external ADD INDEX idx_email_list_id (email_list_id)');
        }

            // Add foreign key for email_list_external
            try {
                $this->db->query('ALTER TABLE email_list_external ADD CONSTRAINT fk_ele_email_list_id FOREIGN KEY (email_list_id) REFERENCES email_lists(id) ON DELETE CASCADE');
            } catch (Exception $e) {
                log_message('error', 'Migration 049: Could not add FK fk_ele_email_list_id: ' . $e->getMessage());
            }

            log_message('info', 'Migration 049: Created email lists tables (email_lists, email_list_roles, email_list_members, email_list_external)');

        } catch (Exception $e) {
            log_message('error', 'Migration 049 FAILED: ' . $e->getMessage());
            log_message('error', 'Migration 049 Error trace: ' . $e->getTraceAsString());
            throw $e;  // Re-throw so migration system knows it failed
        }
    }

    public function down()
    {
        // Drop tables in reverse order (to respect FK dependencies)
        $this->dbforge->drop_table('email_list_external', TRUE);
        $this->dbforge->drop_table('email_list_members', TRUE);
        $this->dbforge->drop_table('email_list_roles', TRUE);
        $this->dbforge->drop_table('email_lists', TRUE);

        log_message('info', 'Migration 049: Dropped email lists tables');
    }
}
