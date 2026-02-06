<?php
/**
 *    GVV Gestion vol a voile
 *    Copyright (C) 2011  Philippe Boissel & Frederic Peignot
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
 * Migration 068: Add validation status to archived documents
 *
 * Adds validation workflow columns:
 * - validation_status: pending, approved, rejected
 * - validated_by: who validated
 * - validated_at: when validated
 * - rejection_reason: reason for rejection
 */
class Migration_Add_validation_status extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 68;
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
            "ALTER TABLE `archived_documents`
                ADD COLUMN `validation_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved'
                COMMENT 'Statut de validation' AFTER `mime_type`",

            "ALTER TABLE `archived_documents`
                ADD COLUMN `validated_by` VARCHAR(25) NULL
                COMMENT 'Utilisateur ayant valide' AFTER `validation_status`",

            "ALTER TABLE `archived_documents`
                ADD COLUMN `validated_at` DATETIME NULL
                COMMENT 'Date de validation' AFTER `validated_by`",

            "ALTER TABLE `archived_documents`
                ADD COLUMN `rejection_reason` VARCHAR(255) NULL
                COMMENT 'Motif du refus' AFTER `validated_at`",

            "ALTER TABLE `archived_documents`
                ADD INDEX `idx_validation_status` (`validation_status`)"
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database up to " . $this->migration_number . ", errors=$errors");
        return !$errors;
    }

    public function down() {
        $errors = 0;

        $sqls = array(
            "ALTER TABLE `archived_documents` DROP INDEX `idx_validation_status`",
            "ALTER TABLE `archived_documents` DROP COLUMN `rejection_reason`",
            "ALTER TABLE `archived_documents` DROP COLUMN `validated_at`",
            "ALTER TABLE `archived_documents` DROP COLUMN `validated_by`",
            "ALTER TABLE `archived_documents` DROP COLUMN `validation_status`"
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");
        return !$errors;
    }
}

/* End of file 068_add_validation_status.php */
/* Location: ./application/migrations/068_add_validation_status.php */
