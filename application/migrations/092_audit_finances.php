<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Migration 092: Lot 1 audit fields for finances and flights.
 *
 * Target tables:
 * - achats, ecritures, comptes, tickets, tarifs
 * - volsp, volsa
 * - vols_decouverte (created_by/updated_by only)
 */
class Migration_Audit_finances extends CI_Migration
{
    private $migration_number = 92;

    private function column_exists($table, $column)
    {
        $table = $this->db->escape_str($table);
        $column = $this->db->escape_str($column);

        $sql = "SELECT COUNT(*) AS cnt
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = '$table'
                AND COLUMN_NAME = '$column'";

        $row = $this->db->query($sql)->row_array();
        return isset($row['cnt']) && (int) $row['cnt'] > 0;
    }

    private function add_column_if_missing($table, $column, $definition)
    {
        if (!$this->column_exists($table, $column)) {
            $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
            gvv_info("Migration 092 add column: $sql");
            if (!$this->db->query($sql)) {
                gvv_error('Migration 092 add column failed: ' . $this->db->_error_message());
                return false;
            }
        }
        return true;
    }

    private function drop_column_if_exists($table, $column)
    {
        if ($this->column_exists($table, $column)) {
            $sql = "ALTER TABLE `$table` DROP COLUMN `$column`";
            gvv_info("Migration 092 drop column: $sql");
            if (!$this->db->query($sql)) {
                gvv_error('Migration 092 drop column failed: ' . $this->db->_error_message());
                return false;
            }
        }
        return true;
    }

    public function up()
    {
        $ok = true;

        // Standard audit columns on Lot 1 tables.
        $tables = array('achats', 'ecritures', 'comptes', 'tickets', 'tarifs', 'volsp', 'volsa');
        foreach ($tables as $table) {
            $ok = $this->add_column_if_missing($table, 'created_by', "VARCHAR(25) NULL COMMENT 'User who created the row'") && $ok;
            $ok = $this->add_column_if_missing($table, 'created_at', "DATETIME NULL COMMENT 'Creation timestamp'") && $ok;
            $ok = $this->add_column_if_missing($table, 'updated_by', "VARCHAR(25) NULL COMMENT 'User who last updated the row'") && $ok;
            $ok = $this->add_column_if_missing($table, 'updated_at', "DATETIME NULL COMMENT 'Last update timestamp'") && $ok;
        }

        // vols_decouverte already has created_at/updated_at from migration 058.
        $ok = $this->add_column_if_missing('vols_decouverte', 'created_by', "VARCHAR(25) NULL COMMENT 'User who created the row'") && $ok;
        $ok = $this->add_column_if_missing('vols_decouverte', 'updated_by', "VARCHAR(25) NULL COMMENT 'User who last updated the row'") && $ok;

        // Backfill legacy creator fields.
        $this->db->query("UPDATE achats SET created_by = saisie_par WHERE created_by IS NULL AND saisie_par IS NOT NULL AND saisie_par <> ''");
        $this->db->query("UPDATE ecritures SET created_by = saisie_par WHERE created_by IS NULL AND saisie_par IS NOT NULL AND saisie_par <> ''");
        $this->db->query("UPDATE comptes SET created_by = saisie_par WHERE created_by IS NULL AND saisie_par IS NOT NULL AND saisie_par <> ''");
        $this->db->query("UPDATE tickets SET created_by = saisie_par WHERE created_by IS NULL AND saisie_par IS NOT NULL AND saisie_par <> ''");
        $this->db->query("UPDATE tarifs SET created_by = saisie_par WHERE created_by IS NULL AND saisie_par IS NOT NULL AND saisie_par <> ''");
        $this->db->query("UPDATE volsp SET created_by = saisie_par WHERE created_by IS NULL AND saisie_par IS NOT NULL AND saisie_par <> ''");
        $this->db->query("UPDATE volsa SET created_by = saisie_par WHERE created_by IS NULL AND saisie_par IS NOT NULL AND saisie_par <> ''");
        $this->db->query("UPDATE vols_decouverte SET created_by = saisie_par WHERE created_by IS NULL AND saisie_par IS NOT NULL AND saisie_par <> ''");

        // Mirror initial creator into updater when empty.
        $this->db->query("UPDATE achats SET updated_by = created_by WHERE updated_by IS NULL");
        $this->db->query("UPDATE ecritures SET updated_by = created_by WHERE updated_by IS NULL");
        $this->db->query("UPDATE comptes SET updated_by = created_by WHERE updated_by IS NULL");
        $this->db->query("UPDATE tickets SET updated_by = created_by WHERE updated_by IS NULL");
        $this->db->query("UPDATE tarifs SET updated_by = created_by WHERE updated_by IS NULL");
        $this->db->query("UPDATE volsp SET updated_by = created_by WHERE updated_by IS NULL");
        $this->db->query("UPDATE volsa SET updated_by = created_by WHERE updated_by IS NULL");
        $this->db->query("UPDATE vols_decouverte SET updated_by = created_by WHERE updated_by IS NULL");

        // Backfill timestamps from existing business dates where relevant.
        $this->db->query("UPDATE achats SET created_at = CONCAT(date, ' 00:00:00') WHERE created_at IS NULL AND date IS NOT NULL");
        $this->db->query("UPDATE ecritures SET created_at = CONCAT(date_creation, ' 00:00:00') WHERE created_at IS NULL AND date_creation IS NOT NULL");
        $this->db->query("UPDATE tickets SET created_at = CONCAT(date, ' 00:00:00') WHERE created_at IS NULL AND date IS NOT NULL");
        $this->db->query("UPDATE tarifs SET created_at = CONCAT(date, ' 00:00:00') WHERE created_at IS NULL AND date IS NOT NULL");
        $this->db->query("UPDATE volsp SET created_at = CONCAT(vpdate, ' 00:00:00') WHERE created_at IS NULL AND vpdate IS NOT NULL");
        $this->db->query("UPDATE volsa SET created_at = CONCAT(vadate, ' 00:00:00') WHERE created_at IS NULL AND vadate IS NOT NULL");
        $this->db->query("UPDATE vols_decouverte SET created_at = CONCAT(date_vente, ' 00:00:00') WHERE created_at IS NULL AND date_vente IS NOT NULL");

        // comptes has no reliable business date; keep NULL if unknown.

        $this->db->query("UPDATE achats SET updated_at = created_at WHERE updated_at IS NULL AND created_at IS NOT NULL");
        $this->db->query("UPDATE ecritures SET updated_at = created_at WHERE updated_at IS NULL AND created_at IS NOT NULL");
        $this->db->query("UPDATE comptes SET updated_at = created_at WHERE updated_at IS NULL AND created_at IS NOT NULL");
        $this->db->query("UPDATE tickets SET updated_at = created_at WHERE updated_at IS NULL AND created_at IS NOT NULL");
        $this->db->query("UPDATE tarifs SET updated_at = created_at WHERE updated_at IS NULL AND created_at IS NOT NULL");
        $this->db->query("UPDATE volsp SET updated_at = created_at WHERE updated_at IS NULL AND created_at IS NOT NULL");
        $this->db->query("UPDATE volsa SET updated_at = created_at WHERE updated_at IS NULL AND created_at IS NOT NULL");
        $this->db->query("UPDATE vols_decouverte SET updated_at = created_at WHERE updated_at IS NULL AND created_at IS NOT NULL");

        gvv_info('Migration database up to ' . $this->migration_number . ', success=' . ($ok ? 'true' : 'false'));
        return $ok;
    }

    public function down()
    {
        $ok = true;

        // Full rollback for tables where migration 092 introduced all 4 fields.
        $full_tables = array('achats', 'ecritures', 'comptes', 'tickets', 'tarifs', 'volsp', 'volsa');
        foreach ($full_tables as $table) {
            $ok = $this->drop_column_if_exists($table, 'updated_at') && $ok;
            $ok = $this->drop_column_if_exists($table, 'updated_by') && $ok;
            $ok = $this->drop_column_if_exists($table, 'created_at') && $ok;
            $ok = $this->drop_column_if_exists($table, 'created_by') && $ok;
        }

        // vols_decouverte already had created_at/updated_at before migration 092.
        $ok = $this->drop_column_if_exists('vols_decouverte', 'updated_by') && $ok;
        $ok = $this->drop_column_if_exists('vols_decouverte', 'created_by') && $ok;

        gvv_info('Migration database down to ' . ($this->migration_number - 1) . ', success=' . ($ok ? 'true' : 'false'));
        return $ok;
    }
}
