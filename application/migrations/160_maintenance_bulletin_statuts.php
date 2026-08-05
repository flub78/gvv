<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Migration 160: Creation de la table maintenance_bulletin_statuts
 *
 * Table compagnon legere associee 1--0..1 a archived_documents pour
 * suivre le statut applicatif d'un bulletin de service (a traiter /
 * traite / non applicable), sans ajouter de colonnes propres a la
 * maintenance dans archived_documents (qui reste generique et partagee
 * par de nombreux autres modules).
 *
 * @see doc/prds/maintenance_aeronefs_prd.md (EF6)
 * @see doc/plans/maintenance_aeronefs_plan.md (Etape 1.6)
 * @see doc/design_notes/maintenance_aeronefs_design.md
 */
class Migration_Maintenance_bulletin_statuts extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 160;
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
            "CREATE TABLE IF NOT EXISTS `maintenance_bulletin_statuts` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `archived_document_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'Bulletin de service concerne',
                `statut` ENUM('a_traiter', 'traite', 'non_applicable') NOT NULL DEFAULT 'a_traiter',
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `updated_by` VARCHAR(25) NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_maintenance_bulletin_statuts_document` (`archived_document_id`),
                CONSTRAINT `fk_maint_bulletin_document` FOREIGN KEY (`archived_document_id`)
                    REFERENCES `archived_documents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
            COMMENT='Statut applicatif des bulletins de service'",
        );

        $errors += $this->run_queries($sqls);

        if ($errors > 0) {
            gvv_error("Migration " . $this->migration_number . ": $errors error(s) while creating maintenance_bulletin_statuts");
            return false;
        }

        gvv_info("Migration database up to " . $this->migration_number);
        return true;
    }

    public function down() {
        $errors = 0;

        $sqls = array(
            "DROP TABLE IF EXISTS `maintenance_bulletin_statuts`",
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");
        return !$errors;
    }
}

/* End of file 160_maintenance_bulletin_statuts.php */
/* Location: ./application/migrations/160_maintenance_bulletin_statuts.php */
