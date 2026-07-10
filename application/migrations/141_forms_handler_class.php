<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 141: handler post-soumission optionnel (Lot 6, étape 6.3)
 *
 * Ajoute à forms une colonne handler_class permettant de déclarer, par
 * formulaire, une classe PHP (application/libraries/form_handlers/) invoquée
 * après création de la soumission. NULL = aucun handler (comportement
 * inchangé pour tous les formulaires existants).
 */
class Migration_Forms_handler_class extends CI_Migration {

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
        $ok = $this->add_column_if_missing('forms', 'handler_class',
            "VARCHAR(100) NULL DEFAULT NULL COMMENT 'Classe PHP du handler post-soumission, NULL = aucun'");

        log_message('info', 'Migration 141: forms.handler_class column created');
        return $ok;
    }

    public function down() {
        $ok = $this->drop_column_if_exists('forms', 'handler_class');

        log_message('info', 'Migration 141: forms.handler_class column dropped');
        return $ok;
    }
}
