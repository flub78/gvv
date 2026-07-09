<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 140: référence générique au sujet (subject_type / subject_id)
 *
 * Ajoute à form_submissions un couple générique (subject_type, subject_id)
 * permettant d'indexer une soumission sur n'importe quel enregistrement GVV
 * (ex: vols_decouverte), sans dépendre d'archived_documents. Remplace
 * l'approche context_params JSON initialement envisagée (Lot 6, étape 6.2).
 */
class Migration_Forms_subject_reference extends CI_Migration {

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

    private function index_exists($table, $index)
    {
        $t = $this->db->escape_str($table);
        $i = $this->db->escape_str($index);

        $row = $this->db->query(
            "SELECT COUNT(*) AS cnt
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t' AND INDEX_NAME = '$i'"
        )->row_array();

        return isset($row['cnt']) && (int) $row['cnt'] > 0;
    }

    private function add_column_if_missing($table, $column, $definition)
    {
        if (!$this->column_exists($table, $column)) {
            $t = $this->db->escape_str($table);
            $c = $this->db->escape_str($column);
            return (bool) $this->db->query("ALTER TABLE `$t` ADD COLUMN `$c` $definition");
        }
        return TRUE;
    }

    private function drop_column_if_exists($table, $column)
    {
        if ($this->column_exists($table, $column)) {
            $t = $this->db->escape_str($table);
            $c = $this->db->escape_str($column);
            return (bool) $this->db->query("ALTER TABLE `$t` DROP COLUMN `$c`");
        }
        return TRUE;
    }

    private function add_index_if_missing($table, $index, $columns_sql)
    {
        if (!$this->index_exists($table, $index)) {
            $t = $this->db->escape_str($table);
            $i = $this->db->escape_str($index);
            return (bool) $this->db->query("ALTER TABLE `$t` ADD INDEX `$i` ($columns_sql)");
        }
        return TRUE;
    }

    private function drop_index_if_exists($table, $index)
    {
        if ($this->index_exists($table, $index)) {
            $t = $this->db->escape_str($table);
            $i = $this->db->escape_str($index);
            return (bool) $this->db->query("ALTER TABLE `$t` DROP INDEX `$i`");
        }
        return TRUE;
    }

    public function up() {
        $ok = TRUE;
        $ok = $this->add_column_if_missing('form_submissions', 'subject_type',
            "VARCHAR(50) NULL DEFAULT NULL COMMENT 'Type generique du sujet lie (ex: vols_decouverte)'") && $ok;
        $ok = $this->add_column_if_missing('form_submissions', 'subject_id',
            "INT NULL DEFAULT NULL COMMENT 'Identifiant generique du sujet lie'") && $ok;
        $ok = $this->add_index_if_missing('form_submissions', 'idx_subject', '`subject_type`, `subject_id`') && $ok;

        log_message('info', 'Migration 140: forms_subject_reference columns/index created');
        return $ok;
    }

    public function down() {
        $ok = TRUE;
        $ok = $this->drop_index_if_exists('form_submissions', 'idx_subject') && $ok;
        $ok = $this->drop_column_if_exists('form_submissions', 'subject_id') && $ok;
        $ok = $this->drop_column_if_exists('form_submissions', 'subject_type') && $ok;

        log_message('info', 'Migration 140: forms_subject_reference columns/index dropped');
        return $ok;
    }
}
