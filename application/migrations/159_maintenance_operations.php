<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Migration 159: Creation des tables maintenance_operations et maintenance_realisations
 *
 * maintenance_operations : evenement date rattache a un dossier
 * d'entretien, miroir de formation_seances. Deux modes de saisie
 * (directe / compte_rendu) sur un meme ecran (PRD EF4).
 *
 * maintenance_realisations : realisation d'une tache du programme lors
 * d'une operation (fait / non fait / non applicable), miroir exact de
 * formation_evaluations.
 *
 * @see doc/prds/maintenance_aeronefs_prd.md (EF4)
 * @see doc/plans/maintenance_aeronefs_plan.md (Etape 1.5)
 */
class Migration_Maintenance_operations extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 159;
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
            // Table: maintenance_operations - Evenement de maintenance (miroir de formation_seances)
            "CREATE TABLE IF NOT EXISTS `maintenance_operations` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `dossier_id` INT(11) NOT NULL COMMENT 'Dossier d entretien concerne',
                `date_operation` DATE NOT NULL,
                `mecano_id` VARCHAR(25) NOT NULL COMMENT 'Mecano ayant enregistre l operation (membres.mlogin)',
                `mode_saisie` ENUM('directe', 'compte_rendu') NOT NULL,
                `document_id` BIGINT(20) UNSIGNED NULL COMMENT 'Compte rendu joint (archived_documents) si mode_saisie = compte_rendu',
                `horametre_releve` DECIMAL(8,2) NULL,
                `nouvelle_echeance` DATE NULL,
                `commentaire` VARCHAR(500) NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `created_by` VARCHAR(25) NULL,
                `updated_by` VARCHAR(25) NULL,
                PRIMARY KEY (`id`),
                KEY `idx_maintenance_operations_dossier` (`dossier_id`),
                KEY `idx_maintenance_operations_date` (`date_operation`),
                KEY `idx_maintenance_operations_mecano` (`mecano_id`),
                KEY `idx_maintenance_operations_document` (`document_id`),
                CONSTRAINT `fk_maint_op_dossier` FOREIGN KEY (`dossier_id`)
                    REFERENCES `maintenance_dossiers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_maint_op_mecano` FOREIGN KEY (`mecano_id`)
                    REFERENCES `membres` (`mlogin`) ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT `fk_maint_op_document` FOREIGN KEY (`document_id`)
                    REFERENCES `archived_documents` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
            COMMENT='Operations de maintenance (miroir de formation_seances)'",

            // Table: maintenance_realisations - Realisation d'une tache lors d'une operation (miroir de formation_evaluations)
            "CREATE TABLE IF NOT EXISTS `maintenance_realisations` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `operation_id` INT(11) NOT NULL COMMENT 'Operation parente',
                `tache_id` INT(11) NOT NULL COMMENT 'Tache evaluee',
                `statut` ENUM('fait', 'non_fait', 'non_applicable') NOT NULL DEFAULT 'non_fait',
                `commentaire` VARCHAR(255) NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `created_by` VARCHAR(25) NULL,
                `updated_by` VARCHAR(25) NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_maintenance_realisations_op_tache` (`operation_id`, `tache_id`),
                KEY `idx_maintenance_realisations_operation` (`operation_id`),
                KEY `idx_maintenance_realisations_tache` (`tache_id`),
                KEY `idx_maintenance_realisations_statut` (`statut`),
                CONSTRAINT `fk_maint_real_op` FOREIGN KEY (`operation_id`)
                    REFERENCES `maintenance_operations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_maint_real_tache` FOREIGN KEY (`tache_id`)
                    REFERENCES `maintenance_taches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
            COMMENT='Realisations de taches lors d une operation (miroir de formation_evaluations)'",
        );

        $errors += $this->run_queries($sqls);

        if ($errors > 0) {
            gvv_error("Migration " . $this->migration_number . ": $errors error(s) while creating maintenance_operations tables");
            return false;
        }

        gvv_info("Migration database up to " . $this->migration_number);
        return true;
    }

    public function down() {
        $errors = 0;

        // Ordre inverse pour respecter les contraintes de cles etrangeres
        $sqls = array(
            "DROP TABLE IF EXISTS `maintenance_realisations`",
            "DROP TABLE IF EXISTS `maintenance_operations`",
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");
        return !$errors;
    }
}

/* End of file 159_maintenance_operations.php */
/* Location: ./application/migrations/159_maintenance_operations.php */
