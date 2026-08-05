<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Migration 155: Ajout de la valeur 'machine' a document_types.scope
 *
 * Permet de rattacher un document (programme d'entretien, bulletin de
 * service) a une entite maintenable (aeronef ou equipement) plutot qu'a
 * un pilote, une section ou le club. La colonne archived_documents.machine_immat
 * existe deja (migration 076) mais n'etait pas exploitable tant que ce
 * scope n'existait pas.
 *
 * @see doc/prds/maintenance_aeronefs_prd.md
 * @see doc/plans/maintenance_aeronefs_plan.md
 */
class Migration_Document_types_scope_machine extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 155;
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
            "ALTER TABLE `document_types`
                MODIFY COLUMN `scope` ENUM('pilot', 'section', 'club', 'machine')
                NOT NULL DEFAULT 'pilot' COMMENT 'Portee du document'",
        );

        $errors += $this->run_queries($sqls);

        if ($errors > 0) {
            gvv_error("Migration " . $this->migration_number . ": $errors error(s) while adding scope machine");
            return false;
        }

        gvv_info("Migration database up to " . $this->migration_number);
        return true;
    }

    public function down() {
        $errors = 0;

        $sqls = array(
            "ALTER TABLE `document_types`
                MODIFY COLUMN `scope` ENUM('pilot', 'section', 'club')
                NOT NULL DEFAULT 'pilot' COMMENT 'Portee du document'",
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");
        return !$errors;
    }
}

/* End of file 155_document_types_scope_machine.php */
/* Location: ./application/migrations/155_document_types_scope_machine.php */
