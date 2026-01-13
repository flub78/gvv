<?php

/**
 *    GVV Gestion vol à voile
 *    Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Migration: Refactor Calendar Table for FullCalendar v6
 *
 * Refactors the `calendar` table to support FullCalendar v6 with:
 * - Start/end datetime fields for precise date/time ranges
 * - full_day boolean for all-day events (default = 1)
 * - Status tracking (confirmed, pending, completed, cancelled)
 * - Audit fields (created_by, updated_by, timestamps)
 * - Optimized indexes for date range queries
 *
 * Note: This migration does NOT migrate existing data. The table is expected
 * to be truncated or existing data will coexist with the new structure.
 *
 * @author Claude Sonnet 4.5
 */
class Migration_Refactor_calendar_table extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 60;
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
            // Modify existing date column to be nullable (legacy field)
            "ALTER TABLE `calendar` MODIFY COLUMN `date` DATE NULL COMMENT 'Deprecated - use start_datetime/end_datetime'",

            // Add new datetime fields
            "ALTER TABLE `calendar` ADD COLUMN `start_datetime` DATETIME NOT NULL COMMENT 'Presence start date/time' AFTER `date`",
            "ALTER TABLE `calendar` ADD COLUMN `end_datetime` DATETIME NOT NULL COMMENT 'Presence end date/time' AFTER `start_datetime`",

            // Add full_day boolean (default = 1 for all-day events)
            "ALTER TABLE `calendar` ADD COLUMN `full_day` TINYINT(1) NOT NULL DEFAULT 1
                COMMENT 'True = journée complète (00:00-23:59), False = heures spécifiques'
                AFTER `end_datetime`",

            // Add status field
            "ALTER TABLE `calendar` ADD COLUMN `status` ENUM('confirmed', 'pending', 'completed', 'cancelled')
                DEFAULT 'confirmed'
                COMMENT 'Presence status'
                AFTER `commentaire`",

            // Add audit fields
            "ALTER TABLE `calendar` ADD COLUMN `created_by` VARCHAR(64) NULL COMMENT 'User who created the presence' AFTER `status`",
            "ALTER TABLE `calendar` ADD COLUMN `updated_by` VARCHAR(64) NULL COMMENT 'User who last updated the presence' AFTER `created_by`",
            "ALTER TABLE `calendar` ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation timestamp' AFTER `updated_by`",
            "ALTER TABLE `calendar` ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                COMMENT 'Last update timestamp'
                AFTER `created_at`",

            // Add indexes for performance
            "ALTER TABLE `calendar` ADD INDEX `idx_date_range` (`start_datetime`, `end_datetime`)",
            "ALTER TABLE `calendar` ADD INDEX `idx_mlogin` (`mlogin`)",
            "ALTER TABLE `calendar` ADD INDEX `idx_status` (`status`)",
            "ALTER TABLE `calendar` ADD INDEX `idx_full_day` (`full_day`)"
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database up to " . $this->migration_number . ", errors=$errors");
        return !$errors;
    }

    public function down() {
        $errors = 0;
        $sqls = array(
            // Remove indexes
            "ALTER TABLE `calendar` DROP INDEX IF EXISTS `idx_date_range`",
            "ALTER TABLE `calendar` DROP INDEX IF EXISTS `idx_mlogin`",
            "ALTER TABLE `calendar` DROP INDEX IF EXISTS `idx_status`",
            "ALTER TABLE `calendar` DROP INDEX IF EXISTS `idx_full_day`",

            // Remove audit fields
            "ALTER TABLE `calendar` DROP COLUMN IF EXISTS `updated_at`",
            "ALTER TABLE `calendar` DROP COLUMN IF EXISTS `created_at`",
            "ALTER TABLE `calendar` DROP COLUMN IF EXISTS `updated_by`",
            "ALTER TABLE `calendar` DROP COLUMN IF EXISTS `created_by`",

            // Remove status field
            "ALTER TABLE `calendar` DROP COLUMN IF EXISTS `status`",

            // Remove full_day field
            "ALTER TABLE `calendar` DROP COLUMN IF EXISTS `full_day`",

            // Remove datetime fields
            "ALTER TABLE `calendar` DROP COLUMN IF EXISTS `end_datetime`",
            "ALTER TABLE `calendar` DROP COLUMN IF EXISTS `start_datetime`",

            // Restore date column to NOT NULL
            "ALTER TABLE `calendar` MODIFY COLUMN `date` DATE NOT NULL"
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");
        return !$errors;
    }
}

/* End of file 060_refactor_calendar_table.php */
/* Location: ./application/migrations/060_refactor_calendar_table.php */
