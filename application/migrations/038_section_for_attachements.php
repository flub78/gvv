<?php

/**
 * GVV Migration
 * Script de migration de la base - add_membre_fields
 */
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Migration_Section_For_Attachements extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 38;
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
            "ALTER TABLE `attachments` ADD `club` TINYINT NULL DEFAULT '0' COMMENT 'Commentaire gestion multi-section' AFTER `file`;",
            "UPDATE `attachments` SET `club` = 3 WHERE `user_id` = 'pmaignan';",
            "UPDATE `attachments` SET `club` = 4 WHERE `user_id` = 'calegre';"
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database up to " . $this->migration_number . ", errors=$errors");

        return !$errors;
    }

    public function down() {
        $errors = 0;
        $sqls = array(
            "ALTER TABLE `attachments` DROP `club`"
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");

        return !$errors;
    }
}
