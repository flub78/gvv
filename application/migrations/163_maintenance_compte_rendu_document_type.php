<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Migration 163: Type de document pour les comptes rendus d'operation
 *
 * Manquant a la migration 162 (Phase 4, qui n'avait anticipe que le
 * programme d'entretien et le bulletin de service) : PRD EF4.2 requiert
 * de pouvoir deposer un compte rendu papier scanne/photographie lors
 * d'une operation de maintenance, via le systeme documentaire existant.
 *
 * @see doc/prds/maintenance_aeronefs_prd.md (EF4.2)
 * @see doc/plans/maintenance_aeronefs_plan.md (Etape 5.4)
 */
class Migration_Maintenance_compte_rendu_document_type extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 163;
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

    private function code_exists($code) {
        $c = $this->db->escape_str($code);
        $row = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM document_types WHERE code = '$c' AND section_id IS NULL"
        )->row_array();
        return isset($row['cnt']) && (int) $row['cnt'] > 0;
    }

    public function up() {
        $errors = 0;

        $sqls = array();
        if (!$this->code_exists('maintenance_compte_rendu')) {
            $sqls[] = "INSERT INTO `document_types`
                (`code`, `name`, `section_id`, `scope`, `required`, `has_expiration`, `storage_by_year`, `alert_days_before`, `active`, `display_order`)
                VALUES ('maintenance_compte_rendu', 'Compte rendu de maintenance', NULL, 'machine', 0, 0, 0, NULL, 1, 22)";
        }

        $errors += $this->run_queries($sqls);

        if ($errors > 0) {
            gvv_error("Migration " . $this->migration_number . ": $errors error(s) while seeding maintenance_compte_rendu document type");
            return false;
        }

        gvv_info("Migration database up to " . $this->migration_number);
        return true;
    }

    public function down() {
        $errors = 0;

        $sqls = array(
            "DELETE FROM `document_types` WHERE `code` = 'maintenance_compte_rendu'",
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");
        return !$errors;
    }
}

/* End of file 163_maintenance_compte_rendu_document_type.php */
/* Location: ./application/migrations/163_maintenance_compte_rendu_document_type.php */
