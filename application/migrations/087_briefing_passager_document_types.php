<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 087: Document types for passenger briefing
 *
 * - briefing_passager: corrects scope from 'club' to 'section' and updates name
 * - consignes_securite: new type for section safety instructions (UC2)
 */
class Migration_Briefing_passager_document_types extends CI_Migration
{
    public function up()
    {
        // Correct briefing_passager scope and name (may already exist with scope=club)
        $existing = $this->db->query(
            "SELECT id FROM document_types WHERE code='briefing_passager' AND section_id IS NULL"
        )->row_array();

        if ($existing) {
            $this->db->query("
                UPDATE document_types SET name='Briefing passager VLD', scope='section'
                WHERE code='briefing_passager' AND section_id IS NULL
            ");
        } else {
            $this->db->query("
                INSERT INTO document_types (code, name, scope, required, has_expiration, active, display_order)
                VALUES ('briefing_passager', 'Briefing passager VLD', 'section', 0, 0, 1, 0)
            ");
        }

        // Create consignes_securite type for section safety instructions PDF
        $exists = $this->db->query(
            "SELECT id FROM document_types WHERE code='consignes_securite' AND section_id IS NULL"
        )->row_array();
        if (!$exists) {
            $this->db->query("
                INSERT INTO document_types (code, name, scope, required, has_expiration, active, display_order)
                VALUES ('consignes_securite', 'Consignes de sécurité', 'section', 0, 0, 1, 0)
            ");
        }

        log_message('info', 'Migration 087: briefing_passager and consignes_securite document types set');
    }

    public function down()
    {
        // Restore briefing_passager to its original values
        $this->db->query("
            UPDATE document_types SET scope = 'club', name = 'Fiche de briefing passage VLD'
            WHERE code = 'briefing_passager'
        ");

        $this->db->query("DELETE FROM document_types WHERE code = 'consignes_securite'");

        log_message('info', 'Migration 087: briefing_passager and consignes_securite document types reverted');
    }
}
