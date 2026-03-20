<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 084: Add backup_db role
 *
 * - backup_db: global-scoped role granting access to backup, restore, and
 *   database migration pages without requiring full admin privileges.
 */
class Migration_Backup_db_role extends CI_Migration
{
    public function up()
    {
        $this->db->query(
            "INSERT IGNORE INTO types_roles (nom, description, scope, is_system_role, display_order, translation_key)
             VALUES ('backup_db', 'Sauvegarde et restauration', 'global', 1, 15, 'role_backup_db')"
        );

        log_message('info', 'Migration 084: backup_db role created');
    }

    public function down()
    {
        // Remove role assignments before deleting the role (FK constraint)
        $this->db->query(
            "DELETE urps FROM user_roles_per_section urps
             INNER JOIN types_roles tr ON tr.id = urps.types_roles_id
             WHERE tr.nom = 'backup_db'"
        );
        $this->db->query("DELETE FROM types_roles WHERE nom = 'backup_db'");

        log_message('info', 'Migration 084: backup_db role removed');
    }
}
