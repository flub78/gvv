<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 073: Mark all roles as system roles and add RP role
 *
 * All roles require code-level support to have any effect; roles defined
 * only in the database without corresponding application logic are meaningless.
 * This migration:
 *   - Sets is_system_role = 1 for 'instructeur' and 'mecano' (previously user-created)
 *   - Adds the 'RP' (Responsable pédagogique) role, section-scoped, system role
 */
class Migration_System_roles extends CI_Migration
{
    public function up()
    {
        // Mark instructeur and mecano as system roles
        $this->db->query(
            "UPDATE types_roles SET is_system_role = 1 WHERE nom IN ('instructeur', 'mecano')"
        );

        // Add RP (Responsable pédagogique) role
        $this->db->query(
            "INSERT INTO types_roles (nom, description, scope, is_system_role, display_order, translation_key)
             VALUES ('rp', 'Responsable pédagogique', 'section', 1, 90, 'role_rp')"
        );

        log_message('info', 'Migration 073: instructeur and mecano set as system roles; RP role added');
    }

    public function down()
    {
        // Remove RP role
        $this->db->query("DELETE FROM types_roles WHERE nom = 'rp'");

        // Revert instructeur and mecano to non-system
        $this->db->query(
            "UPDATE types_roles SET is_system_role = 0 WHERE nom IN ('instructeur', 'mecano')"
        );

        log_message('info', 'Migration 073: Reverted instructeur/mecano and removed RP role');
    }
}
