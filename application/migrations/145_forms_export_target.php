<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 145: export d'une réponse vers un formulaire de création GVV (Lot 12)
 *
 * Ajoute à forms deux colonnes optionnelles permettant de déclarer, par
 * formulaire, un formulaire de création GVV standard (ex. membre/create) à
 * pré-remplir depuis une réponse. NULL sur l'une ou l'autre = pas de bouton
 * d'export (comportement inchangé pour tous les formulaires existants).
 */
class Migration_Forms_export_target extends CI_Migration {

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

    public function up() {
        $ok = TRUE;
        $ok = $this->add_column_if_missing('forms', 'target_url',
            "VARCHAR(255) NULL DEFAULT NULL COMMENT 'URL du formulaire de creation GVV a prereplir depuis une reponse, NULL = pas de bouton export'") && $ok;
        $ok = $this->add_column_if_missing('forms', 'target_label',
            "VARCHAR(100) NULL DEFAULT NULL COMMENT 'Libelle du bouton export, affiche sur la liste des reponses'") && $ok;

        log_message('info', 'Migration 145: forms.target_url/target_label columns created');
        return $ok;
    }

    public function down() {
        $ok = TRUE;
        $ok = $this->drop_column_if_exists('forms', 'target_label') && $ok;
        $ok = $this->drop_column_if_exists('forms', 'target_url') && $ok;

        log_message('info', 'Migration 145: forms.target_url/target_label columns dropped');
        return $ok;
    }
}
