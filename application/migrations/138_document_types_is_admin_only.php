<?php

/**
 * Migration 138: Add is_admin_only flag to document_types
 *
 * Documents marked as admin-only are excluded from the pilot's
 * personal Documents view (archived_documents/my_documents).
 * They remain visible in the admin document management interface.
 *
 * briefing_passager documents are admin-only: they are signed
 * passenger briefings managed by VD administrators, not relevant
 * to regular pilots browsing their documents.
 */
class Migration_Document_types_is_admin_only extends CI_Migration {

    public function up() {
        $col_exists = $this->db->query("
            SELECT COUNT(*) as cnt
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'document_types'
              AND COLUMN_NAME = 'is_admin_only'
        ")->row_array();

        if (empty($col_exists['cnt'])) {
            $this->db->query("
                ALTER TABLE document_types
                ADD COLUMN is_admin_only TINYINT(1) NOT NULL DEFAULT 0
                    COMMENT 'If 1, hidden from pilot my_documents view, visible to admins only'
                    AFTER is_private
            ");
        }

        $this->db->query("
            UPDATE document_types
            SET is_admin_only = 1
            WHERE code = 'briefing_passager'
        ");
    }

    public function down() {
        $this->db->query("
            ALTER TABLE document_types
            DROP COLUMN is_admin_only
        ");
    }
}
