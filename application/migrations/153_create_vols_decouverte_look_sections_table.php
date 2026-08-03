<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Migration 153: Création de la table `vols_decouverte_look_sections`
 *
 * Associe une section (club) à un look de bon de vol de découverte
 * (`vols_decouverte_looks`). Une section sans ligne associée utilise le look
 * marqué `is_default` (résolu par le modèle, pas par la base). Une section
 * n'a jamais plus d'un look actif : contrainte UNIQUE sur `section_id`.
 *
 * @see doc/plans/configuration_bons_vols_decouverte_plan.md
 * @see doc/prds/configuration_bons_vols_decouverte_prd.md
 */
class Migration_Create_vols_decouverte_look_sections_table extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 153;
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
            "CREATE TABLE IF NOT EXISTS `vols_decouverte_look_sections` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `section_id` INT(11) NOT NULL COMMENT 'Section associée (sections.id)',
                `look_id` INT(11) UNSIGNED NOT NULL COMMENT 'Look associé (vols_decouverte_looks.id)',
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `created_by` VARCHAR(25) NULL COMMENT 'Acteur ayant créé l''association',
                `updated_by` VARCHAR(25) NULL COMMENT 'Acteur ayant modifié l''association',
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_vols_decouverte_look_sections_section` (`section_id`),
                KEY `idx_vols_decouverte_look_sections_look` (`look_id`),
                CONSTRAINT `fk_vd_look_sections_section` FOREIGN KEY (`section_id`)
                    REFERENCES `sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_vd_look_sections_look` FOREIGN KEY (`look_id`)
                    REFERENCES `vols_decouverte_looks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            COMMENT='Association section -> look de bon de vol de découverte'",
        );

        $errors += $this->run_queries($sqls);

        if ($errors > 0) {
            gvv_error("Migration " . $this->migration_number . ": $errors error(s) while creating vols_decouverte_look_sections");
            return false;
        }

        gvv_info("Migration database up to " . $this->migration_number);
        return true;
    }

    public function down() {
        $errors = 0;
        $sqls = array(
            "DROP TABLE IF EXISTS `vols_decouverte_look_sections`",
        );
        $errors += $this->run_queries($sqls);
        gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");
        return !$errors;
    }
}
