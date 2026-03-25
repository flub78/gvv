<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 094 — Audit columns for training tables
 *
 * Adds created_by, created_at, updated_by, updated_at to all formation_* tables.
 *
 * formation_autorisations_solo and formation_programmes already have date_creation
 * and date_modification — these are back-filled into created_at / updated_at.
 */
class Migration_Audit_formation extends CI_Migration {

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

    // Tables that have no pre-existing timestamp columns
    private $plain_tables = array(
        'formation_chapitres',
        'formation_evaluations',
        'formation_inscriptions',
        'formation_item',
        'formation_lecons',
        'formation_progres',
        'formation_seances',
        'formation_seances_participants',
        'formation_sujets',
        'formation_types_seance',
    );

    // Tables that already have date_creation / date_modification
    private $ts_tables = array(
        'formation_autorisations_solo',
        'formation_programmes',
    );

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
        $all_tables = array_merge($this->plain_tables, $this->ts_tables);

        foreach ($all_tables as $table) {
            $this->add_audit_columns($table);
        }

        // Back-fill from date_creation / date_modification for tables that have them
        foreach ($this->ts_tables as $table) {
            $this->db->query("UPDATE `{$table}` SET created_at = date_creation WHERE created_at IS NULL AND date_creation IS NOT NULL");
            $this->db->query("UPDATE `{$table}` SET updated_at = COALESCE(date_modification, date_creation) WHERE updated_at IS NULL");
        }
    }

    public function down() {
        $all_tables = array_merge($this->plain_tables, $this->ts_tables);

        foreach ($all_tables as $table) {
            $this->drop_audit_columns($table);
        }
    }
}
