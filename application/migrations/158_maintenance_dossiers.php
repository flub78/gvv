<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Migration 158: Creation de la table maintenance_dossiers
 *
 * Association d'un programme d'entretien a une entite maintenable
 * (aeronef ou equipement), avec un cycle de vie (ouvert / suspendu /
 * cloture / abandonne) -- miroir exact de formation_inscriptions.
 *
 * entite_type/entite_id est une cle polymorphe (macimmat si 'aeronef',
 * maintenance_equipements.id si 'equipement') : aucune contrainte FK
 * native n'est possible en base. L'existence de l'entite est validee au
 * niveau applicatif (modele), couverte par des tests d'integration
 * dedies -- cf. tableau des risques du plan.
 *
 * @see doc/prds/maintenance_aeronefs_prd.md (EF3)
 * @see doc/plans/maintenance_aeronefs_plan.md (Etape 1.4)
 */
class Migration_Maintenance_dossiers extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 158;
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
            "CREATE TABLE IF NOT EXISTS `maintenance_dossiers` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `entite_type` ENUM('aeronef', 'equipement') NOT NULL COMMENT 'Type de l entite maintenable',
                `entite_id` VARCHAR(10) NOT NULL COMMENT 'macimmat si aeronef, maintenance_equipements.id si equipement',
                `programme_id` INT(11) NOT NULL COMMENT 'Programme d entretien suivi',
                `mecano_referent_id` VARCHAR(25) NULL COMMENT 'Mecano referent (membres.mlogin)',
                `statut` ENUM('ouvert', 'suspendu', 'cloture', 'abandonne') NOT NULL DEFAULT 'ouvert',
                `date_ouverture` DATE NOT NULL,
                `date_suspension` DATE NULL,
                `date_cloture` DATE NULL,
                `echeance_courante` DATE NULL COMMENT 'Calculee, mise a jour a chaque operation',
                `heures_restantes_courant` DECIMAL(8,2) NULL COMMENT 'Calculee, mise a jour a chaque operation',
                `commentaire` VARCHAR(255) NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `created_by` VARCHAR(25) NULL,
                `updated_by` VARCHAR(25) NULL,
                PRIMARY KEY (`id`),
                KEY `idx_maintenance_dossiers_entite` (`entite_type`, `entite_id`),
                KEY `idx_maintenance_dossiers_programme` (`programme_id`),
                KEY `idx_maintenance_dossiers_statut` (`statut`),
                KEY `idx_maintenance_dossiers_mecano` (`mecano_referent_id`),
                CONSTRAINT `fk_maint_dossier_prog` FOREIGN KEY (`programme_id`)
                    REFERENCES `maintenance_programmes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT `fk_maint_dossier_mecano` FOREIGN KEY (`mecano_referent_id`)
                    REFERENCES `membres` (`mlogin`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
            COMMENT='Dossiers d entretien (miroir de formation_inscriptions)'",
        );

        $errors += $this->run_queries($sqls);

        if ($errors > 0) {
            gvv_error("Migration " . $this->migration_number . ": $errors error(s) while creating maintenance_dossiers");
            return false;
        }

        gvv_info("Migration database up to " . $this->migration_number);
        return true;
    }

    public function down() {
        $errors = 0;

        $sqls = array(
            "DROP TABLE IF EXISTS `maintenance_dossiers`",
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");
        return !$errors;
    }
}

/* End of file 158_maintenance_dossiers.php */
/* Location: ./application/migrations/158_maintenance_dossiers.php */
