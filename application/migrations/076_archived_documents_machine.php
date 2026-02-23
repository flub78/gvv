<?php
/**
 * Migration 076: Add machine_immat field to archived_documents
 */
class Migration_Archived_documents_machine extends CI_Migration {
    public function up() {
        $this->db->query('ALTER TABLE archived_documents ADD COLUMN machine_immat VARCHAR(20) NULL DEFAULT NULL AFTER section_id');
    }

    public function down() {
        $this->db->query('ALTER TABLE archived_documents DROP COLUMN machine_immat');
    }
}
