<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 091: Add pilote_rem role
 *
 * - pilote_rem: Pilote remorqueur (section-scoped).
 */
class Migration_Pilote_rem_role extends CI_Migration
{
    public function up()
    {
        $this->db->query(
            "INSERT IGNORE INTO types_roles (nom, description, scope, is_system_role, display_order, translation_key)
             VALUES ('pilote_rem', 'Pilote remorqueur', 'section', 1, 97, 'role_pilote_rem')"
        );

        log_message('info', 'Migration 091: pilote_rem role created');
    }

    public function down()
    {
        $this->db->query(
            "DELETE urps FROM user_roles_per_section urps
             INNER JOIN types_roles tr ON tr.id = urps.types_roles_id
             WHERE tr.nom = 'pilote_rem'"
        );
        $this->db->query("DELETE FROM types_roles WHERE nom = 'pilote_rem'");

        log_message('info', 'Migration 091: pilote_rem role removed');
    }
}
