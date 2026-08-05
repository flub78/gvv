<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Migration 161: Ajout de la colonne actif sur maintenance_programme_sections
 * et maintenance_taches
 *
 * Necessaire pour la desactivation logique lors du re-parsing d'une
 * nouvelle version de programme (Etape 4.2) : une section/tache retiree
 * du markdown mais deja referencee par une maintenance_realisation ne
 * peut pas etre supprimee (l'historique doit rester consultable) -- elle
 * est simplement desactivee (actif = 0) plutot que supprimee.
 *
 * @see doc/plans/maintenance_aeronefs_plan.md (Etape 4.2)
 * @see doc/design_notes/maintenance_aeronefs_design.md
 */
class Migration_Maintenance_actif_column extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 161;
    }

    private function run_queries($sqls = array()) {
        $errors = 0;
        foreach ($sqls as $sql) {
            gvv_info("Migration sql: " . $sql);
            if (!$this->db->query($sql)) {
                $mysql_msg = $this->db->_error_message();
                $mysql_error = $this->db->_error_number();
                gvv_error("Migration error: code=$mysql_error, msg=$mysql_msg");
                $errors += 1;
            }
        }
        return $errors;
    }

    private function column_exists($table, $column) {
        $t = $this->db->escape_str($table);
        $c = $this->db->escape_str($column);
        $row = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t' AND COLUMN_NAME = '$c'"
        )->row_array();
        return isset($row['cnt']) && (int) $row['cnt'] > 0;
    }

    public function up() {
        $errors = 0;

        $sqls = array();
        if (!$this->column_exists('maintenance_programme_sections', 'actif')) {
            $sqls[] = "ALTER TABLE `maintenance_programme_sections`
                ADD COLUMN `actif` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Desactivation logique (version obsolete encore referencee)'
                AFTER `titre`";
        }
        if (!$this->column_exists('maintenance_taches', 'actif')) {
            $sqls[] = "ALTER TABLE `maintenance_taches`
                ADD COLUMN `actif` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Desactivation logique (version obsolete encore referencee)'
                AFTER `description`";
        }

        $errors += $this->run_queries($sqls);

        if ($errors > 0) {
            gvv_error("Migration " . $this->migration_number . ": $errors error(s) while adding actif columns");
            return false;
        }

        gvv_info("Migration database up to " . $this->migration_number);
        return true;
    }

    public function down() {
        $errors = 0;

        $sqls = array();
        if ($this->column_exists('maintenance_taches', 'actif')) {
            $sqls[] = "ALTER TABLE `maintenance_taches` DROP COLUMN `actif`";
        }
        if ($this->column_exists('maintenance_programme_sections', 'actif')) {
            $sqls[] = "ALTER TABLE `maintenance_programme_sections` DROP COLUMN `actif`";
        }

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");
        return !$errors;
    }
}

/* End of file 161_maintenance_actif_column.php */
/* Location: ./application/migrations/161_maintenance_actif_column.php */
