<?php

/**
 * GVV Migration
 * Script de migration de la base - add_membre_fields
 */
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Migration_Configuration_File extends CI_Migration {

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
            "ALTER TABLE `configuration` ADD `file` VARCHAR(255) DEFAULT NULL COMMENT 'Fichier de configuration'",
            "ALTER TABLE `configuration` CHANGE `valeur` `valeur` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;"
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database up to " . $this->migration_number . ", errors=$errors");

        return !$errors;
    }

    public function down() {
        $errors = 0;
        $sqls = array(
            "ALTER TABLE `configuration` DROP `file`",
            "ALTER TABLE `configuration` CHANGE `valeur` `valeur` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;"
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");

        return !$errors;
    }
}
