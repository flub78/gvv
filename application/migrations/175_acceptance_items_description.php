<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 175: acceptance_items.description
 *
 * Adds a free-text description shown alongside the item title in the
 * message du jour alert and in the member's "mes documents" table, so
 * members get more context than the title alone before opening the item.
 */
class Migration_Acceptance_items_description extends CI_Migration {

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

    public function up() {
        $ok = TRUE;

        if (!$this->column_exists('acceptance_items', 'description')) {
            $ok = (bool) $this->db->query(
                "ALTER TABLE `acceptance_items`
                 ADD COLUMN `description` TEXT NULL COMMENT 'Description complementaire au titre' AFTER `title`"
            ) && $ok;
        }

        log_message('info', 'Migration 175: acceptance_items.description created');
        return $ok;
    }

    public function down() {
        $ok = TRUE;

        if ($this->column_exists('acceptance_items', 'description')) {
            $ok = (bool) $this->db->query(
                "ALTER TABLE `acceptance_items` DROP COLUMN `description`"
            ) && $ok;
        }

        log_message('info', 'Migration 175: acceptance_items.description dropped');
        return $ok;
    }
}
