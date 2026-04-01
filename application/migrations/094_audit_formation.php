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

    private function is_row_size_too_large_error($error_message)
    {
        return stripos($error_message, 'Row size too large') !== false;
    }

    private function run_alter_with_rebuild_retry($table, $alter_sql)
    {
        // In CI2, query() often returns FALSE on error; in tests, it may throw.
        $result = FALSE;
        $error_code = 0;
        $error_msg = '';
        try {
            $result = $this->db->query($alter_sql);
        } catch (Throwable $e) {
            $result = FALSE;
            $error_msg = $e->getMessage();
        }
        if ($result !== FALSE) {
            return;
        }

        // If no exception message was captured, retrieve DB error details.
        if ($error_msg === '') {
            if (method_exists($this->db, 'error')) {
                $error      = $this->db->error();
                $error_code = isset($error['code']) ? $error['code'] : 0;
                $error_msg  = isset($error['message']) ? $error['message'] : '';
            } else {
                $error_code = method_exists($this->db, '_error_number') ? $this->db->_error_number() : 0;
                $error_msg  = method_exists($this->db, '_error_message') ? $this->db->_error_message() : '';
            }
        }

        if (!$this->is_row_size_too_large_error($error_msg)) {
            throw new RuntimeException("Database error {$error_code} while running ALTER: {$error_msg}");
        }

        $t = $this->db->escape_str($table);
        // Some legacy InnoDB tables need a physical rebuild before new columns can be added.
        $force_result = FALSE;
        $force_error_code = 0;
        $force_error_msg = '';
        try {
            $force_result = $this->db->query("ALTER TABLE `$t` FORCE");
        } catch (Throwable $e) {
            $force_result = FALSE;
            $force_error_msg = $e->getMessage();
        }
        if ($force_result === FALSE) {
            if ($force_error_msg === '') {
                if (method_exists($this->db, 'error')) {
                    $force_error      = $this->db->error();
                    $force_error_code = isset($force_error['code']) ? $force_error['code'] : 0;
                    $force_error_msg  = isset($force_error['message']) ? $force_error['message'] : '';
                } else {
                    $force_error_code = method_exists($this->db, '_error_number') ? $this->db->_error_number() : 0;
                    $force_error_msg  = method_exists($this->db, '_error_message') ? $this->db->_error_message() : '';
                }
            }
            throw new RuntimeException("Database error {$force_error_code} while running ALTER FORCE: {$force_error_msg}");
        }

        $retry_result = FALSE;
        $retry_error_code = 0;
        $retry_error_msg = '';
        try {
            $retry_result = $this->db->query($alter_sql);
        } catch (Throwable $e) {
            $retry_result = FALSE;
            $retry_error_msg = $e->getMessage();
        }
        if ($retry_result === FALSE) {
            if ($retry_error_msg === '') {
                if (method_exists($this->db, 'error')) {
                    $retry_error      = $this->db->error();
                    $retry_error_code = isset($retry_error['code']) ? $retry_error['code'] : 0;
                    $retry_error_msg  = isset($retry_error['message']) ? $retry_error['message'] : '';
                } else {
                    $retry_error_code = method_exists($this->db, '_error_number') ? $this->db->_error_number() : 0;
                    $retry_error_msg  = method_exists($this->db, '_error_message') ? $this->db->_error_message() : '';
                }
            }
            throw new RuntimeException("Database error {$retry_error_code} while retrying ALTER: {$retry_error_msg}");
        }
    }

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
            $this->run_alter_with_rebuild_retry(
                $table,
                "ALTER TABLE `$t` ADD COLUMN `created_by` VARCHAR(25) NULL"
            );
        }
        if (!$this->column_exists($table, 'created_at')) {
            $this->run_alter_with_rebuild_retry(
                $table,
                "ALTER TABLE `$t` ADD COLUMN `created_at` DATETIME NULL"
            );
        }
        if (!$this->column_exists($table, 'updated_by')) {
            $this->run_alter_with_rebuild_retry(
                $table,
                "ALTER TABLE `$t` ADD COLUMN `updated_by` VARCHAR(25) NULL"
            );
        }
        if (!$this->column_exists($table, 'updated_at')) {
            $this->run_alter_with_rebuild_retry(
                $table,
                "ALTER TABLE `$t` ADD COLUMN `updated_at` DATETIME NULL"
            );
        }
    }

    private function drop_audit_columns($table)
    {
        $t = $this->db->escape_str($table);
        foreach (array('updated_at', 'updated_by', 'created_at', 'created_by') as $col) {
            if ($this->column_exists($table, $col)) {
                $this->run_alter_with_rebuild_retry(
                    $table,
                    "ALTER TABLE `$t` DROP COLUMN `$col`"
                );
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
