<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Migration 099: Add is_cotisation flag to tarifs table
 *
 * Permet de marquer un tarif comme produit de cotisation,
 * utilisé par paiements_en_ligne/cotisation (UC3) pour présenter
 * les produits de cotisation au pilote.
 */
class Migration_Tarifs_Is_Cotisation extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 99;
    }

    private function run_queries($sqls = array()) {
        $errors = 0;
        foreach ($sqls as $sql) {
            gvv_info("Migration sql: " . $sql);
            if (!$this->db->query($sql)) {
                $mysql_msg   = $this->db->_error_message();
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
            "ALTER TABLE `tarifs`
             ADD COLUMN `is_cotisation` TINYINT(1) NOT NULL DEFAULT 0
             COMMENT 'Produit de cotisation — présenté au pilote dans UC3'
             AFTER `type_ticket`",
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database up to " . $this->migration_number . ", errors=$errors");

        return !$errors;
    }

    public function down() {
        $errors = 0;

        $sqls = array(
            "ALTER TABLE `tarifs` DROP COLUMN `is_cotisation`",
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");

        return !$errors;
    }
}
