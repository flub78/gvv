<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Migration 162: Types de documents pour la maintenance
 *
 * Cree les document_types necessaires au module Maintenance (scope
 * 'machine', ajoute par la migration 155) :
 * - maintenance_programme : programme d'entretien (fichier markdown source,
 *   verse via le systeme documentaire existant, versionne)
 * - maintenance_bulletin : bulletin de service
 *
 * Note : allow_versioning n'existe plus sur document_types depuis la
 * migration 075 (le versioning est desormais toujours explicite via
 * l'action "Nouvelle version", jamais automatique) -- aucune valeur a
 * fixer ici pour ce comportement.
 *
 * @see doc/prds/maintenance_aeronefs_prd.md (EF2, EF6)
 * @see doc/plans/maintenance_aeronefs_plan.md (Etape 4.2)
 */
class Migration_Maintenance_document_types extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 162;
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

    // section_id est NULL pour ces deux types : la contrainte UNIQUE (code, section_id)
    // ne suffit pas a empecher les doublons (NULL n'est jamais egal a NULL en SQL),
    // d'ou la verification explicite d'existence avant insertion.
    private function code_exists($code) {
        $c = $this->db->escape_str($code);
        $row = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM document_types WHERE code = '$c' AND section_id IS NULL"
        )->row_array();
        return isset($row['cnt']) && (int) $row['cnt'] > 0;
    }

    public function up() {
        $errors = 0;

        $seeds = array(
            "('maintenance_programme', 'Programme entretien', NULL, 'machine', 0, 0, 0, NULL, 1, 20)" => 'maintenance_programme',
            "('maintenance_bulletin', 'Bulletin de service', NULL, 'machine', 0, 0, 0, NULL, 1, 21)" => 'maintenance_bulletin',
        );

        $sqls = array();
        foreach ($seeds as $values => $code) {
            if (!$this->code_exists($code)) {
                $sqls[] = "INSERT INTO `document_types`
                    (`code`, `name`, `section_id`, `scope`, `required`, `has_expiration`, `storage_by_year`, `alert_days_before`, `active`, `display_order`)
                    VALUES $values";
            }
        }

        $errors += $this->run_queries($sqls);

        if ($errors > 0) {
            gvv_error("Migration " . $this->migration_number . ": $errors error(s) while seeding maintenance document_types");
            return false;
        }

        gvv_info("Migration database up to " . $this->migration_number);
        return true;
    }

    public function down() {
        $errors = 0;

        $sqls = array(
            "DELETE FROM `document_types` WHERE `code` IN ('maintenance_programme', 'maintenance_bulletin')",
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");
        return !$errors;
    }
}

/* End of file 162_maintenance_document_types.php */
/* Location: ./application/migrations/162_maintenance_document_types.php */
