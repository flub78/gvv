<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Migration 157: Creation des tables du programme d'entretien
 *
 * Modelise le programme d'entretien a trois niveaux, exactement sur le
 * modele formation_programmes / formation_lecons / formation_sujets :
 * - maintenance_programmes : racine du programme (regle de butee,
 *   document markdown source)
 * - maintenance_programme_sections : niveau intermediaire (miroir de
 *   formation_lecons). Nomme volontairement maintenance_programme_sections
 *   et non maintenance_sections pour ne jamais etre confondu avec la
 *   table sections existante (clubs/activites planeur/avion/ULM).
 * - maintenance_taches : point de controle elementaire (miroir de
 *   formation_sujets)
 *
 * @see doc/prds/maintenance_aeronefs_prd.md (EF2)
 * @see doc/plans/maintenance_aeronefs_plan.md (Etape 1.3)
 * @see doc/design_notes/maintenance_aeronefs_design.md
 */
class Migration_Maintenance_programmes extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 157;
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
            // Table: maintenance_programmes - Racine du programme d'entretien
            "CREATE TABLE IF NOT EXISTS `maintenance_programmes` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `code` VARCHAR(50) NOT NULL COMMENT 'Code du programme (ex: VISITE100H)',
                `titre` VARCHAR(255) NOT NULL COMMENT 'Titre du programme',
                `section_id` INT(11) NULL COMMENT 'Section de rattachement (NULL = toutes sections)',
                `document_id` BIGINT(20) UNSIGNED NULL COMMENT 'Document markdown source (archived_documents), version courante',
                `regle_butee_date` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Butee calendaire active',
                `regle_butee_heures` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Butee horaire active',
                `seuil_heures` DECIMAL(8,2) NULL COMMENT 'Seuil heures de vol si regle_butee_heures = 1',
                `periodicite_mois` INT(11) NULL COMMENT 'Periodicite calendaire en mois si regle_butee_date = 1',
                `statut` ENUM('actif', 'inactif') NOT NULL DEFAULT 'actif',
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `created_by` VARCHAR(25) NULL,
                `updated_by` VARCHAR(25) NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_maintenance_programmes_code` (`code`),
                KEY `idx_maintenance_programmes_section` (`section_id`),
                KEY `idx_maintenance_programmes_statut` (`statut`),
                KEY `idx_maintenance_programmes_document` (`document_id`),
                CONSTRAINT `fk_maint_prog_section` FOREIGN KEY (`section_id`)
                    REFERENCES `sections` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk_maint_prog_document` FOREIGN KEY (`document_id`)
                    REFERENCES `archived_documents` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
            COMMENT='Programmes d entretien (miroir de formation_programmes)'",

            // Table: maintenance_programme_sections - Niveau intermediaire (miroir de formation_lecons)
            "CREATE TABLE IF NOT EXISTS `maintenance_programme_sections` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `programme_id` INT(11) NOT NULL COMMENT 'Programme parent',
                `ordre` INT(11) NOT NULL COMMENT 'Ordre d affichage',
                `titre` VARCHAR(255) NOT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `created_by` VARCHAR(25) NULL,
                `updated_by` VARCHAR(25) NULL,
                PRIMARY KEY (`id`),
                KEY `idx_maintenance_programme_sections_programme` (`programme_id`),
                KEY `idx_maintenance_programme_sections_ordre` (`programme_id`, `ordre`),
                CONSTRAINT `fk_maint_progsec_prog` FOREIGN KEY (`programme_id`)
                    REFERENCES `maintenance_programmes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
            COMMENT='Sections d un programme d entretien (miroir de formation_lecons, sans rapport avec la table sections)'",

            // Table: maintenance_taches - Point de controle elementaire (miroir de formation_sujets)
            "CREATE TABLE IF NOT EXISTS `maintenance_taches` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `programme_section_id` INT(11) NOT NULL COMMENT 'Section parente',
                `ordre` INT(11) NOT NULL COMMENT 'Ordre d affichage',
                `titre` VARCHAR(255) NOT NULL,
                `description` TEXT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `created_by` VARCHAR(25) NULL,
                `updated_by` VARCHAR(25) NULL,
                PRIMARY KEY (`id`),
                KEY `idx_maintenance_taches_section` (`programme_section_id`),
                KEY `idx_maintenance_taches_ordre` (`programme_section_id`, `ordre`),
                CONSTRAINT `fk_maint_tache_progsec` FOREIGN KEY (`programme_section_id`)
                    REFERENCES `maintenance_programme_sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
            COMMENT='Taches d un programme d entretien (miroir de formation_sujets)'",
        );

        $errors += $this->run_queries($sqls);

        if ($errors > 0) {
            gvv_error("Migration " . $this->migration_number . ": $errors error(s) while creating maintenance_programmes tables");
            return false;
        }

        gvv_info("Migration database up to " . $this->migration_number);
        return true;
    }

    public function down() {
        $errors = 0;

        // Ordre inverse pour respecter les contraintes de cles etrangeres
        $sqls = array(
            "DROP TABLE IF EXISTS `maintenance_taches`",
            "DROP TABLE IF EXISTS `maintenance_programme_sections`",
            "DROP TABLE IF EXISTS `maintenance_programmes`",
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");
        return !$errors;
    }
}

/* End of file 157_maintenance_programmes.php */
/* Location: ./application/migrations/157_maintenance_programmes.php */
