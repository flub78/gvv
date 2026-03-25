<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 093 — Audit columns for members and fleet tables
 *
 * Adds created_by, created_at, updated_by, updated_at to:
 * membres, licences, machinesa, machinesp, terrains, planc, pompes
 *
 * Back-fills created_by from psaisipar for pompes.
 */
class Migration_Audit_membres_flotte extends CI_Migration {

    private $tables = array(
        'membres', 'licences', 'machinesa', 'machinesp', 'terrains', 'planc', 'pompes',
    );

    private function column_exists($table, $column)
    {
        $t = $this->db->escape_str($table);
        $c = $this->db->escape_str($column);
        $row = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t' AND COLUMN_NAME = '$c'"
        )->row_array();
        return isset($row['cnt']) && (int) $row['cnt'] > 0;
    }

    private function add_audit_columns($table)
    {
        $t = $this->db->escape_str($table);
        if (!$this->column_exists($table, 'created_by')) {
            $this->db->query("ALTER TABLE `$t` ADD COLUMN `created_by` VARCHAR(25) NULL");
        }
        if (!$this->column_exists($table, 'created_at')) {
            $this->db->query("ALTER TABLE `$t` ADD COLUMN `created_at` DATETIME NULL");
        }
        if (!$this->column_exists($table, 'updated_by')) {
            $this->db->query("ALTER TABLE `$t` ADD COLUMN `updated_by` VARCHAR(25) NULL");
        }
        if (!$this->column_exists($table, 'updated_at')) {
            $this->db->query("ALTER TABLE `$t` ADD COLUMN `updated_at` DATETIME NULL");
        }
    }

    private function drop_audit_columns($table)
    {
        $t = $this->db->escape_str($table);
        foreach (array('updated_at', 'updated_by', 'created_at', 'created_by') as $col) {
            if ($this->column_exists($table, $col)) {
                $this->db->query("ALTER TABLE `$t` DROP COLUMN `$col`");
            }
        }
    }

    public function up() {
        foreach ($this->tables as $table) {
            $this->add_audit_columns($table);
        }

        // Back-fill pompes: created_by from psaisipar
        $this->db->query("UPDATE pompes SET created_by = psaisipar WHERE created_by IS NULL AND psaisipar IS NOT NULL AND psaisipar != ''");
        $this->db->query("UPDATE pompes SET updated_by = created_by WHERE updated_by IS NULL AND created_by IS NOT NULL");

        // Back-fill pompes timestamps from pdatesaisie
        $this->db->query("UPDATE pompes SET created_at = DATE_FORMAT(pdatesaisie, '%Y-%m-%d 00:00:00') WHERE created_at IS NULL AND pdatesaisie IS NOT NULL");
        $this->db->query("UPDATE pompes SET updated_at = created_at WHERE updated_at IS NULL AND created_at IS NOT NULL");
    }

    public function down() {
        foreach ($this->tables as $table) {
            $this->drop_audit_columns($table);
        }
    }
}
