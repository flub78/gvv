<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 042: Authorization System Refactoring
 *
 * This migration implements the new authorization system with:
 * - Enhanced types_roles table with scope and metadata
 * - New role_permissions table for structured URI permissions
 * - New data_access_rules table for row-level security
 * - Enhanced user_roles_per_section with audit fields
 * - New authorization_audit_log table for change tracking
 * - New authorization_migration_status table for progressive migration
 *
 * @see /doc/plans/2025_authorization_refactoring_plan.md
 */
class Migration_authorization_refactoring extends CI_Migration {

    public function up()
    {
        // 1. Enhance types_roles table
        $this->dbforge->add_column('types_roles', array(
            'scope' => array(
                'type' => 'ENUM',
                'constraint' => array('global', 'section'),
                'default' => 'section',
                'null' => FALSE,
                'after' => 'description'
            ),
            'is_system_role' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'null' => FALSE,
                'comment' => 'Cannot be deleted',
                'after' => 'scope'
            ),
            'display_order' => array(
                'type' => 'INT',
                'constraint' => 11,
                'default' => 100,
                'null' => FALSE,
                'after' => 'is_system_role'
            ),
            'translation_key' => array(
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => TRUE,
                'comment' => 'Language file key for role name',
                'after' => 'display_order'
            )
        ));

        // Update existing roles with proper scope and metadata
        $this->db->update('types_roles', array(
            'scope' => 'global',
            'is_system_role' => 1,
            'display_order' => 10,
            'translation_key' => 'role_club_admin'
        ), array('id' => 10)); // club-admin

        $this->db->update('types_roles', array(
            'scope' => 'global',
            'is_system_role' => 1,
            'display_order' => 20,
            'translation_key' => 'role_super_tresorier'
        ), array('id' => 9)); // super-tresorier

        $this->db->update('types_roles', array(
            'scope' => 'section',
            'is_system_role' => 1,
            'display_order' => 30,
            'translation_key' => 'role_bureau'
        ), array('id' => 7)); // bureau

        $this->db->update('types_roles', array(
            'scope' => 'section',
            'is_system_role' => 1,
            'display_order' => 40,
            'translation_key' => 'role_tresorier'
        ), array('id' => 8)); // tresorier

        $this->db->update('types_roles', array(
            'scope' => 'section',
            'is_system_role' => 1,
            'display_order' => 50,
            'translation_key' => 'role_ca'
        ), array('id' => 6)); // ca

        $this->db->update('types_roles', array(
            'scope' => 'section',
            'is_system_role' => 1,
            'display_order' => 60,
            'translation_key' => 'role_planchiste'
        ), array('id' => 5)); // planchiste

        $this->db->update('types_roles', array(
            'scope' => 'section',
            'is_system_role' => 1,
            'display_order' => 70,
            'translation_key' => 'role_auto_planchiste'
        ), array('id' => 2)); // auto_planchiste

        $this->db->update('types_roles', array(
            'scope' => 'section',
            'is_system_role' => 1,
            'display_order' => 80,
            'translation_key' => 'role_user'
        ), array('id' => 1)); // user

        // 2. Create role_permissions table
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ),
            'types_roles_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'null' => FALSE,
                'comment' => 'FK to types_roles'
            ),
            'section_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'null' => TRUE,
                'comment' => 'NULL for global roles, specific section for section roles'
            ),
            'controller' => array(
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => FALSE,
                'comment' => 'Controller name (e.g., "membre", "vols_planeur")'
            ),
            'action' => array(
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => TRUE,
                'comment' => 'Action name, NULL means all actions'
            ),
            'permission_type' => array(
                'type' => 'ENUM',
                'constraint' => array('view', 'create', 'edit', 'delete', 'admin'),
                'default' => 'view',
                'null' => FALSE
            ),
            'created' => array(
                'type' => 'DATETIME',
                'null' => FALSE
            ),
            'modified' => array(
                'type' => 'TIMESTAMP',
                'null' => FALSE
            )
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key(array('types_roles_id', 'section_id'), FALSE, 'idx_role_section');
        $this->dbforge->add_key(array('controller', 'action'), FALSE, 'idx_controller_action');
        $this->dbforge->add_key(array('types_roles_id', 'controller', 'action'), FALSE, 'idx_permission_lookup');
        $this->dbforge->create_table('role_permissions', TRUE, array(
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COMMENT' => 'URI and action permissions per role'
        ));

        // Add foreign keys for role_permissions
        $this->db->query('ALTER TABLE `role_permissions`
            ADD CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`types_roles_id`) REFERENCES `types_roles` (`id`) ON DELETE CASCADE,
            ADD CONSTRAINT `fk_role_permissions_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE');

        // 3. Create data_access_rules table
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ),
            'types_roles_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'null' => FALSE
            ),
            'table_name' => array(
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => FALSE,
                'comment' => 'Table being accessed'
            ),
            'access_scope' => array(
                'type' => 'ENUM',
                'constraint' => array('own', 'section', 'all'),
                'default' => 'own',
                'null' => FALSE
            ),
            'field_name' => array(
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => TRUE,
                'comment' => 'Field to check for ownership (e.g., "user_id", "membre_id")'
            ),
            'section_field' => array(
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => TRUE,
                'comment' => 'Field containing section_id'
            ),
            'description' => array(
                'type' => 'TEXT',
                'null' => TRUE
            )
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('data_access_rules', TRUE, array(
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COMMENT' => 'Row-level data access rules'
        ));

        // Add foreign key and unique constraint for data_access_rules
        $this->db->query('ALTER TABLE `data_access_rules`
            ADD CONSTRAINT `fk_data_access_rules_role` FOREIGN KEY (`types_roles_id`) REFERENCES `types_roles` (`id`) ON DELETE CASCADE,
            ADD UNIQUE KEY `unique_rule` (`types_roles_id`, `table_name`, `access_scope`)');

        // 4. Enhance user_roles_per_section table
        $this->dbforge->add_column('user_roles_per_section', array(
            'granted_by' => array(
                'type' => 'INT',
                'constraint' => 11,
                'null' => TRUE,
                'comment' => 'User who granted this role',
                'after' => 'section_id'
            ),
            'granted_at' => array(
                'type' => 'DATETIME',
                'null' => FALSE,
                'after' => 'granted_by'
            ),
            'revoked_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
                'after' => 'granted_at'
            ),
            'notes' => array(
                'type' => 'TEXT',
                'null' => TRUE,
                'after' => 'revoked_at'
            )
        ));

        // Add foreign key for granted_by
        $this->db->query('ALTER TABLE `user_roles_per_section`
            ADD CONSTRAINT `fk_user_roles_granted_by` FOREIGN KEY (`granted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
            ADD INDEX `idx_user_section_active` (`user_id`, `section_id`, `revoked_at`)');

        // Set granted_at for existing records
        $this->db->query("UPDATE `user_roles_per_section` SET `granted_at` = NOW() WHERE `granted_at` IS NULL OR `granted_at` = '0000-00-00 00:00:00'");

        // 5. Create authorization_audit_log table
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ),
            'action_type' => array(
                'type' => 'ENUM',
                'constraint' => array('grant_role', 'revoke_role', 'modify_permission', 'access_denied', 'access_granted'),
                'null' => FALSE
            ),
            'actor_user_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'null' => TRUE,
                'comment' => 'User who performed the action'
            ),
            'target_user_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'null' => TRUE,
                'comment' => 'User affected by action'
            ),
            'types_roles_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'null' => TRUE
            ),
            'section_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'null' => TRUE
            ),
            'controller' => array(
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => TRUE
            ),
            'action' => array(
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => TRUE
            ),
            'ip_address' => array(
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => TRUE
            ),
            'details' => array(
                'type' => 'TEXT',
                'null' => TRUE,
                'comment' => 'JSON or text details'
            ),
            'created_at' => array(
                'type' => 'DATETIME',
                'null' => FALSE
            )
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('actor_user_id', FALSE, 'idx_actor');
        $this->dbforge->add_key('target_user_id', FALSE, 'idx_target');
        $this->dbforge->add_key('created_at', FALSE, 'idx_timestamp');
        $this->dbforge->create_table('authorization_audit_log', TRUE, array(
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COMMENT' => 'Audit log for authorization changes'
        ));

        // 6. Create authorization_migration_status table
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ),
            'user_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'null' => FALSE,
                'comment' => 'User being migrated'
            ),
            'migration_status' => array(
                'type' => 'ENUM',
                'constraint' => array('pending', 'in_progress', 'completed', 'failed', 'rolled_back'),
                'default' => 'pending',
                'null' => FALSE
            ),
            'use_new_system' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'null' => FALSE,
                'comment' => '1 = use new authorization, 0 = use legacy DX_Auth'
            ),
            'migrated_by' => array(
                'type' => 'INT',
                'constraint' => 11,
                'null' => TRUE,
                'comment' => 'Admin who initiated migration'
            ),
            'migrated_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE
            ),
            'completed_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE
            ),
            'error_message' => array(
                'type' => 'TEXT',
                'null' => TRUE
            ),
            'notes' => array(
                'type' => 'TEXT',
                'null' => TRUE
            )
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('authorization_migration_status', TRUE, array(
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COMMENT' => 'Track progressive migration of users to new authorization system'
        ));

        // Add foreign keys and unique constraint for migration_status
        $this->db->query('ALTER TABLE `authorization_migration_status`
            ADD CONSTRAINT `fk_auth_migration_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
            ADD CONSTRAINT `fk_auth_migration_migrator` FOREIGN KEY (`migrated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
            ADD UNIQUE KEY `unique_user` (`user_id`),
            ADD INDEX `idx_migration_status` (`migration_status`, `use_new_system`)');
    }

    public function down()
    {
        // Drop new tables (in reverse order due to foreign keys)
        $this->dbforge->drop_table('authorization_migration_status', TRUE);
        $this->dbforge->drop_table('authorization_audit_log', TRUE);

        // Drop foreign keys before dropping tables
        $this->db->query('ALTER TABLE `data_access_rules` DROP FOREIGN KEY `fk_data_access_rules_role`');
        $this->dbforge->drop_table('data_access_rules', TRUE);

        $this->db->query('ALTER TABLE `role_permissions` DROP FOREIGN KEY `fk_role_permissions_role`, DROP FOREIGN KEY `fk_role_permissions_section`');
        $this->dbforge->drop_table('role_permissions', TRUE);

        // Remove columns from user_roles_per_section
        $this->db->query('ALTER TABLE `user_roles_per_section` DROP FOREIGN KEY `fk_user_roles_granted_by`');
        $this->dbforge->drop_column('user_roles_per_section', 'granted_by');
        $this->dbforge->drop_column('user_roles_per_section', 'granted_at');
        $this->dbforge->drop_column('user_roles_per_section', 'revoked_at');
        $this->dbforge->drop_column('user_roles_per_section', 'notes');

        // Remove columns from types_roles
        $this->dbforge->drop_column('types_roles', 'scope');
        $this->dbforge->drop_column('types_roles', 'is_system_role');
        $this->dbforge->drop_column('types_roles', 'display_order');
        $this->dbforge->drop_column('types_roles', 'translation_key');
    }
}
