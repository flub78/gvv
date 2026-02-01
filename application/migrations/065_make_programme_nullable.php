<?php
/**
 *    GVV Gestion vol a voile
 *    Migration 065: Allow NULL in formation_seances.programme_id
 *
 *    Makes `programme_id` nullable to allow free (libre) sessions without a
 *    linked training programme. Reversible migration.
 */

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Migration_Make_programme_nullable extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 65;
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
            // Allow NULL for programme_id to support free sessions without a programme
            "ALTER TABLE `formation_seances` MODIFY `programme_id` INT(11) NULL"
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database up to " . $this->migration_number . ", errors=$errors");
        return !$errors;
    }

    public function down() {
        $errors = 0;

        $sqls = array(
            // Revert to NOT NULL. This will fail if NULL values exist; ensure data cleaned before rollback.
            "ALTER TABLE `formation_seances` MODIFY `programme_id` INT(11) NOT NULL"
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");
        return !$errors;
    }
}

/* End of file 065_make_programme_nullable.php */
/* Location: ./application/migrations/065_make_programme_nullable.php */
