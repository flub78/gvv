<?php

/**
 * GVV Migration
 * Script de migration de la base - add_membre_fields
 */
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Migration_Add_membre_fields extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 36;
    }

    private function run_queries($sqls = array()) {
        $errors = 0;
        foreach ($sqls as $sql) {
            gvv_info("Migration sql: " . $sql);
            if (!$this->db->query($sql)) {
                gvv_error("Migration error: " . $this->db->error()['message']);
                $errors += 1;
            }
        }
        return $errors;
    }

    public function up() {
        $errors = 0;

        $sqls = array(
            "ALTER TABLE `membres` ADD `place_of_birth` VARCHAR(128) NULL COMMENT 'Lieu de naissance'",
            "ALTER TABLE `membres` ADD `inscription_date` DATE NULL COMMENT 'Date d''inscription'",
            "ALTER TABLE `membres` ADD `validation_date` DATE NULL COMMENT 'Date de validation de l''adhésion'",
            "UPDATE `membres` SET `inscription_date` = '2011-01-01', `validation_date` = '2011-01-01' WHERE `inscription_date` IS NULL"
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database up to " . $this->migration_number . ", errors=$errors");

        return !$errors;
    }

    public function down() {
        $errors = 0;
        $sqls = array(
            "ALTER TABLE `membres` DROP `place_of_birth`",
            "ALTER TABLE `membres` DROP `inscription_date`",
            "ALTER TABLE `membres` DROP `validation_date`"
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");

        return !$errors;
    }
}
