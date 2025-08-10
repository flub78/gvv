<?php

/**
 * GVV Migration
 * Script de migration de la base - associations_ecriture
 */
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Migration_Associations_ecriture extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 35;
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
            "CREATE TABLE `associations_ecriture` (
                `id` bigint(20) NOT NULL,
                `string_releve` varchar(180) NOT NULL,
                `id_compte_gvv` int(11) DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",
            "ALTER TABLE `associations_ecriture` ADD PRIMARY KEY (`id`)",
            "ALTER TABLE `associations_ecriture` MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT",
            "ALTER TABLE `associations_ecriture`
                ADD KEY `fk_associations_ecriture_comptes` (`id_compte_gvv`)",
            "ALTER TABLE `associations_ecriture`
                ADD CONSTRAINT `fk_associations_ecriture_comptes`
                FOREIGN KEY (`id_compte_gvv`) REFERENCES `comptes` (`id`)
                ON UPDATE CASCADE ON DELETE SET NULL"
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database up to " . $this->migration_number . ", errors=$errors");

        return !$errors;
    }

    public function down() {
        $errors = 0;
        $sqls = array(
            "DROP TABLE IF EXISTS associations_ecriture"
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");

        return !$errors;
    }
}