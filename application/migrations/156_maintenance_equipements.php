<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Migration 156: Creation de la table maintenance_equipements
 *
 * Modelise les equipements comme entites maintenables rattachees a un
 * aeronef (moteur, helice, parachute, radio, etc.). Un equipement est
 * rattache a un seul aeronef a la fois (aeronef_id, FK logique vers
 * machinesa.macimmat, non contrainte en base : le transfert d'un
 * equipement vers un autre aeronef ne doit jamais etre bloque par une
 * contrainte referentielle).
 *
 * @see doc/prds/maintenance_aeronefs_prd.md (EF1)
 * @see doc/plans/maintenance_aeronefs_plan.md (Etape 1.2)
 */
class Migration_Maintenance_equipements extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 156;
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
            "CREATE TABLE IF NOT EXISTS `maintenance_equipements` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `aeronef_id` VARCHAR(10) NOT NULL COMMENT 'Immatriculation aeronef de rattachement (machinesa.macimmat, FK logique)',
                `nom` VARCHAR(100) NOT NULL COMMENT 'Nom de l equipement (ex: Moteur, Helice, Parachute)',
                `description` VARCHAR(255) NULL,
                `actif` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Desactivation logique',
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `created_by` VARCHAR(25) NULL,
                `updated_by` VARCHAR(25) NULL,
                PRIMARY KEY (`id`),
                KEY `idx_maintenance_equipements_aeronef` (`aeronef_id`),
                KEY `idx_maintenance_equipements_actif` (`actif`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
            COMMENT='Equipements maintenables rattaches a un aeronef'",
        );

        $errors += $this->run_queries($sqls);

        if ($errors > 0) {
            gvv_error("Migration " . $this->migration_number . ": $errors error(s) while creating maintenance_equipements");
            return false;
        }

        gvv_info("Migration database up to " . $this->migration_number);
        return true;
    }

    public function down() {
        $errors = 0;

        $sqls = array(
            "DROP TABLE IF EXISTS `maintenance_equipements`",
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");
        return !$errors;
    }
}

/* End of file 156_maintenance_equipements.php */
/* Location: ./application/migrations/156_maintenance_equipements.php */
