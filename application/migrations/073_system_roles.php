<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 073: Ensure system roles exist and add RP role
 *
 * All roles require code-level support to have any effect; roles defined
 * only in the database without corresponding application logic are meaningless.
 * This migration:
 *   - Creates 'instructeur' and 'mecano' if they don't exist, then marks them as system roles
 *   - Adds the 'RP' (Responsable pédagogique) role, section-scoped, system role
 */
class Migration_System_roles extends CI_Migration
{
    public function up()
    {
        // Create instructeur and mecano if they don't exist, then mark as system roles
        $this->db->query(
            "INSERT IGNORE INTO types_roles (nom, description, scope, is_system_role, display_order, translation_key)
             VALUES
               ('instructeur', 'Capacity to manage training sessions and student progress', 'section', 1, 80, 'role_instructeur'),
               ('mecano', 'Capacity to manage aircraft maintenance', 'section', 1, 85, 'role_mecano')"
        );
        $this->db->query(
            "UPDATE types_roles SET is_system_role = 1 WHERE nom IN ('instructeur', 'mecano')"
        );

        // Add RP (Responsable pédagogique) role
        $this->db->query(
            "INSERT IGNORE INTO types_roles (nom, description, scope, is_system_role, display_order, translation_key)
             VALUES ('rp', 'Responsable pédagogique', 'section', 1, 90, 'role_rp')"
        );

        log_message('info', 'Migration 073: instructeur, mecano and rp roles ensured as system roles');
    }

    public function down()
    {
        // Remove roles added by this migration (only if not assigned to any user)
        $this->db->query("DELETE FROM types_roles WHERE nom = 'rp'");

        // Revert instructeur and mecano to non-system (don't delete, may have been pre-existing)
        $this->db->query(
            "UPDATE types_roles SET is_system_role = 0 WHERE nom IN ('instructeur', 'mecano')"
        );

        log_message('info', 'Migration 073: Reverted instructeur/mecano and removed rp role');
    }
}
