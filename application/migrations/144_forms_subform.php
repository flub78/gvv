<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 144: sous-formulaires (formulaires imbriqués) — Lot 11
 *
 * Ajoute à form_submissions une colonne link_token, infrastructurelle comme
 * submission_uuid (sans signification métier), utilisée pour corréler une
 * soumission de sous-formulaire à son formulaire maître avant que celui-ci ne
 * soit lui-même soumis (le couple générique subject_type/subject_id ne peut
 * être écrit qu'une fois le maître soumis et son id connu).
 */
class Migration_Forms_subform extends CI_Migration {

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
            return (bool) $this->db->query("ALTER TABLE `$t` ROW_FORMAT=DYNAMIC, DROP COLUMN `$c`");
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
        $ok = $this->add_column_if_missing('form_submissions', 'link_token',
            "VARCHAR(64) NULL DEFAULT NULL COMMENT 'Jeton de correlation sous-formulaire -> maitre, sans signification metier'") && $ok;
        $ok = $this->add_index_if_missing('form_submissions', 'idx_link_token', '`link_token`') && $ok;

        log_message('info', 'Migration 144: forms_subform link_token column/index created');
        return $ok;
    }

    public function down() {
        $ok = TRUE;
        $ok = $this->drop_index_if_exists('form_submissions', 'idx_link_token') && $ok;
        $ok = $this->drop_column_if_exists('form_submissions', 'link_token') && $ok;

        log_message('info', 'Migration 144: forms_subform link_token column/index dropped');
        return $ok;
    }
}
