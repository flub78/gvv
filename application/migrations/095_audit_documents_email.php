<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 095 - Audit columns for documents and email tables (Lot 3).
 *
 * - archived_documents: add updated_by (backfill from validated_by/uploaded_by)
 * - email_lists: add updated_by (backfill from created_by)
 * - document_types, attachments, mails: add created_by/created_at/updated_by/updated_at
 *
 * Lot 4 decision: delete audit remains in application logs (INFO), no dedicated DB table.
 */
class Migration_Audit_documents_email extends CI_Migration {

    private function column_exists($table, $column)
    {
        $t = $this->db->escape_str($table);
        $c = $this->db->escape_str($column);

        $row = $this->db->query(
            "SELECT COUNT(*) AS cnt
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t' AND COLUMN_NAME = '$c'"
        )->row_array();

        return isset($row['cnt']) && (int) $row['cnt'] > 0;
    }

    private function add_column_if_missing($table, $column, $definition)
    {
        if (!$this->column_exists($table, $column)) {
            $t = $this->db->escape_str($table);
            $c = $this->db->escape_str($column);
            return (bool) $this->db->query("ALTER TABLE `$t` ADD COLUMN `$c` $definition");
        }
        return TRUE;
    }

    private function drop_column_if_exists($table, $column)
    {
        if ($this->column_exists($table, $column)) {
            $t = $this->db->escape_str($table);
            $c = $this->db->escape_str($column);
            return (bool) $this->db->query("ALTER TABLE `$t` DROP COLUMN `$c`");
        }
        return TRUE;
    }

    public function up()
    {
        $ok = TRUE;

        // archived_documents: updated_by only
        $ok = $this->add_column_if_missing('archived_documents', 'updated_by', "VARCHAR(25) NULL COMMENT 'User who last updated metadata'") && $ok;

        // email_lists: updated_by only (same type family as created_by)
        $ok = $this->add_column_if_missing('email_lists', 'updated_by', "INT(11) NULL COMMENT 'User ID who last updated the list'") && $ok;

        // Full audit columns on these tables
        foreach (array('document_types', 'attachments', 'mails') as $table) {
            $ok = $this->add_column_if_missing($table, 'created_by', "VARCHAR(25) NULL COMMENT 'User who created the row'") && $ok;
            $ok = $this->add_column_if_missing($table, 'created_at', "DATETIME NULL COMMENT 'Creation timestamp'") && $ok;
            $ok = $this->add_column_if_missing($table, 'updated_by', "VARCHAR(25) NULL COMMENT 'User who last updated the row'") && $ok;
            $ok = $this->add_column_if_missing($table, 'updated_at', "DATETIME NULL COMMENT 'Last update timestamp'") && $ok;
        }

        // Backfills
        $this->db->query("UPDATE archived_documents SET updated_by = COALESCE(validated_by, uploaded_by) WHERE updated_by IS NULL");
        $this->db->query("UPDATE email_lists SET updated_by = created_by WHERE updated_by IS NULL AND created_by IS NOT NULL");

        // attachments can inherit creator from user_id
        if ($this->column_exists('attachments', 'user_id')) {
            $this->db->query("UPDATE attachments SET created_by = user_id WHERE created_by IS NULL AND user_id IS NOT NULL AND user_id != ''");
        }
        $this->db->query("UPDATE attachments SET updated_by = created_by WHERE updated_by IS NULL AND created_by IS NOT NULL");

        // mails can derive timestamps from send date
        if ($this->column_exists('mails', 'date_envoie')) {
            $this->db->query("UPDATE mails SET created_at = date_envoie WHERE created_at IS NULL AND date_envoie IS NOT NULL");
            $this->db->query("UPDATE mails SET updated_at = created_at WHERE updated_at IS NULL AND created_at IS NOT NULL");
        }

        // keep internal consistency for tables with full audit columns
        foreach (array('document_types', 'attachments', 'mails') as $table) {
            $this->db->query("UPDATE `$table` SET updated_by = created_by WHERE updated_by IS NULL AND created_by IS NOT NULL");
            $this->db->query("UPDATE `$table` SET updated_at = created_at WHERE updated_at IS NULL AND created_at IS NOT NULL");
        }

        return $ok;
    }

    public function down()
    {
        $ok = TRUE;

        $ok = $this->drop_column_if_exists('archived_documents', 'updated_by') && $ok;
        $ok = $this->drop_column_if_exists('email_lists', 'updated_by') && $ok;

        foreach (array('document_types', 'attachments', 'mails') as $table) {
            $ok = $this->drop_column_if_exists($table, 'updated_at') && $ok;
            $ok = $this->drop_column_if_exists($table, 'updated_by') && $ok;
            $ok = $this->drop_column_if_exists($table, 'created_at') && $ok;
            $ok = $this->drop_column_if_exists($table, 'created_by') && $ok;
        }

        return $ok;
    }
}
