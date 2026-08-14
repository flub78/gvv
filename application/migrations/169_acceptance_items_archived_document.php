<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 169: acceptance_items.archived_document_id (Lot 4)
 *
 * Permet a un element d'acceptation de categorie 'document' de referencer un
 * document deja archive (archived_documents.id) plutot que de televerser un
 * nouveau PDF propre a l'acceptation. pdf_path (Lot 1) reste utilisable pour
 * les autres categories qui n'ont pas de document archive source.
 *
 * @see doc/plans/acceptations_reconnaissances_plan.md (Lot 4, note "Tranche")
 * @see doc/prds/approbation_de_documents_prd.md
 */
class Migration_Acceptance_items_archived_document extends CI_Migration {

    private function column_exists($table, $column)
    {
        $t = $this->db->escape_str($table);
        $c = $this->db->escape_str($column);

        $row = $this->db->query(
            "SELECT COUNT(*) AS cnt
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t' AND COLUMN_NAME = '$c'"
        )->row_array();

        return isset($row['cnt']) && (int) $row['cnt'] > 0;
    }

    public function up() {
        $ok = TRUE;

        if (!$this->column_exists('acceptance_items', 'archived_document_id')) {
            $ok = (bool) $this->db->query(
                "ALTER TABLE `acceptance_items`
                 ADD COLUMN `archived_document_id` BIGINT(20) UNSIGNED NULL
                     COMMENT 'Document deja archive reference (archived_documents.id)' AFTER `pdf_path`"
            ) && $ok;
        }

        if ($this->db->query(
            "SHOW INDEX FROM `acceptance_items` WHERE Key_name = 'idx_archived_document_id'"
        )->num_rows() == 0) {
            $ok = (bool) $this->db->query(
                "ALTER TABLE `acceptance_items` ADD INDEX `idx_archived_document_id` (`archived_document_id`)"
            ) && $ok;
        }

        if ($this->db->query(
            "SELECT 1 FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME = 'acceptance_items'
               AND CONSTRAINT_NAME = 'fk_acceptance_items_archived_document'"
        )->num_rows() == 0) {
            $ok = (bool) $this->db->query(
                "ALTER TABLE `acceptance_items`
                 ADD CONSTRAINT `fk_acceptance_items_archived_document` FOREIGN KEY (`archived_document_id`)
                     REFERENCES `archived_documents` (`id`) ON DELETE SET NULL"
            ) && $ok;
        }

        log_message('info', 'Migration 169: acceptance_items.archived_document_id column/index/FK created');
        return $ok;
    }

    public function down() {
        $ok = TRUE;

        if ($this->db->query(
            "SELECT 1 FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME = 'acceptance_items'
               AND CONSTRAINT_NAME = 'fk_acceptance_items_archived_document'"
        )->num_rows() > 0) {
            $ok = (bool) $this->db->query(
                "ALTER TABLE `acceptance_items` DROP FOREIGN KEY `fk_acceptance_items_archived_document`"
            ) && $ok;
        }

        if ($this->db->query(
            "SHOW INDEX FROM `acceptance_items` WHERE Key_name = 'idx_archived_document_id'"
        )->num_rows() > 0) {
            $ok = (bool) $this->db->query(
                "ALTER TABLE `acceptance_items` DROP INDEX `idx_archived_document_id`"
            ) && $ok;
        }

        if ($this->column_exists('acceptance_items', 'archived_document_id')) {
            $ok = (bool) $this->db->query(
                "ALTER TABLE `acceptance_items` DROP COLUMN `archived_document_id`"
            ) && $ok;
        }

        log_message('info', 'Migration 169: acceptance_items.archived_document_id column/index/FK dropped');
        return $ok;
    }
}
