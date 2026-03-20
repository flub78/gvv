<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 082: Add gestion_vd and pilote_vd roles
 *
 * - gestion_vd: Gestion des vols de découverte (section-scoped). Grants CRUD access
 *   on discovery flights without needing the full 'ca' role.
 * - pilote_vd: Pilote vols de découverte (section-scoped). Members with this role
 *   appear in the pilot selector of the VD edit form. Implies all gestion_vd permissions.
 */
class Migration_Gestion_vd_role extends CI_Migration
{
    public function up()
    {
        $this->db->query(
            "INSERT IGNORE INTO types_roles (nom, description, scope, is_system_role, display_order, translation_key)
             VALUES
               ('gestion_vd', 'Gestion des vols de découverte', 'section', 1, 95, 'role_gestion_vd'),
               ('pilote_vd',  'Pilote vols de découverte',      'section', 1, 96, 'role_pilote_vd')"
        );

        log_message('info', 'Migration 082: gestion_vd and pilote_vd roles created');
    }

    public function down()
    {
        $this->db->query("DELETE FROM types_roles WHERE nom IN ('gestion_vd', 'pilote_vd')");

        log_message('info', 'Migration 082: gestion_vd and pilote_vd roles removed');
    }
}
