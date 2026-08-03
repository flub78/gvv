<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Migration 152: Création de la table `vols_decouverte_looks`
 *
 * Stocke les configurations de mise en page ("looks") des bons de vol de
 * découverte : fonds recto/verso et mise en page (JSON, même structure que
 * le layout des cartes de membre : variable_fields/static_fields, plus un
 * champ dédié qr_field). Plusieurs looks nommés peuvent coexister ; un seul
 * est marqué `is_default` (utilisé par les sections sans association
 * explicite, cf. migration 153).
 *
 * @see doc/plans/configuration_bons_vols_decouverte_plan.md
 * @see doc/prds/configuration_bons_vols_decouverte_prd.md
 */
class Migration_Create_vols_decouverte_looks_table extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 152;
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
            "CREATE TABLE IF NOT EXISTS `vols_decouverte_looks` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `nom` VARCHAR(64) NOT NULL COMMENT 'Nom du look',
                `layout_json` LONGTEXT NOT NULL COMMENT 'Mise en page recto/verso (JSON)',
                `fond_recto_path` VARCHAR(255) NULL COMMENT 'Image de fond recto (uploads/configuration/vd/)',
                `fond_verso_path` VARCHAR(255) NULL COMMENT 'Image de fond verso (uploads/configuration/vd/)',
                `is_default` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Look par défaut pour les sections non associées',
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `created_by` VARCHAR(25) NULL COMMENT 'Acteur ayant créé le look',
                `updated_by` VARCHAR(25) NULL COMMENT 'Acteur ayant modifié le look',
                PRIMARY KEY (`id`),
                KEY `idx_vols_decouverte_looks_default` (`is_default`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            COMMENT='Configurations de mise en page des bons de vol de découverte'",
        );

        $errors += $this->run_queries($sqls);

        if ($errors > 0) {
            gvv_error("Migration " . $this->migration_number . ": $errors error(s) while creating vols_decouverte_looks");
            return false;
        }

        gvv_info("Migration database up to " . $this->migration_number);
        return true;
    }

    public function down() {
        $errors = 0;
        $sqls = array(
            "DROP TABLE IF EXISTS `vols_decouverte_looks`",
        );
        $errors += $this->run_queries($sqls);
        gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");
        return !$errors;
    }
}
