<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Migration 154: Ajout de la colonne `pdf_path` à `vols_decouverte`
 *
 * Chemin du PDF du bon généré et stocké à la vente (ou à la dernière
 * modification de l'enregistrement), plutôt que régénéré à chaque
 * impression/envoi. NULL pour les bons historiques générés par l'ancien
 * mécanisme (`vols_decouverte::generate_pdf()`), qui reste utilisé en
 * fallback tant que cette colonne est vide.
 *
 * @see doc/plans/configuration_bons_vols_decouverte_plan.md
 * @see doc/prds/configuration_bons_vols_decouverte_prd.md
 */
class Migration_Add_pdf_path_to_vols_decouverte extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 154;
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

    public function up() {
        $errors = 0;

        $sqls = array(
            "ALTER TABLE `vols_decouverte` ADD COLUMN `pdf_path` VARCHAR(255) NULL
                COMMENT 'Chemin du PDF stocké, généré à la vente/modification' AFTER `date_validite`",
        );

        $errors += $this->run_queries($sqls);

        if ($errors > 0) {
            gvv_error("Migration " . $this->migration_number . ": $errors error(s) while adding pdf_path");
            return false;
        }

        gvv_info("Migration database up to " . $this->migration_number);
        return true;
    }

    public function down() {
        $errors = 0;
        $sqls = array(
            "ALTER TABLE `vols_decouverte` DROP COLUMN `pdf_path`",
        );
        $errors += $this->run_queries($sqls);
        gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");
        return !$errors;
    }
}
