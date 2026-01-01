<?php

/**
 * GVV Migration
 * Script de migration de la base - extend_membre_name_fields
 * 
 * Augmente la taille des champs mnom et mprenom de la table membres de 25 à 80 caractères
 * pour permettre des noms et prénoms plus longs.
 */
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Migration_Extend_membre_name_fields extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 40;
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
            "ALTER TABLE `membres` CHANGE `mnom` `mnom` VARCHAR(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'Nom du membre'",
            "ALTER TABLE `membres` CHANGE `mprenom` `mprenom` VARCHAR(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'Prénom du membre'"
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database up to " . $this->migration_number . ", errors=$errors");

        return !$errors;
    }

    public function down() {
        $errors = 0;
        $sqls = array(
            "ALTER TABLE `membres` CHANGE `mnom` `mnom` VARCHAR(25) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'Nom du membre'",
            "ALTER TABLE `membres` CHANGE `mprenom` `mprenom` VARCHAR(25) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'Prénom du membre'"
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");

        return !$errors;
    }
}