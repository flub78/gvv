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
 * Migration: Create Aircraft Reservations Table
 *
 * Creates the `reservations` table to store aircraft bookings with
 * start/end times, pilot information, and status tracking.
 * Supports FullCalendar v6 event display.
 *
 * @author Frédéric Peignot
 */
class Migration_Create_aircraft_reservations_table extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 59;
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
            "CREATE TABLE IF NOT EXISTS `reservations` (
                `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Unique reservation identifier',
                `aircraft_id` VARCHAR(10) NOT NULL COMMENT 'Aircraft registration (macimmat from machinesa table)',
                `start_datetime` DATETIME NOT NULL COMMENT 'Reservation start date/time',
                `end_datetime` DATETIME NOT NULL COMMENT 'Reservation end date/time',
                `pilot_member_id` VARCHAR(25) NOT NULL COMMENT 'Pilot member login (mlogin from membres table)',
                `instructor_member_id` VARCHAR(25) DEFAULT NULL COMMENT 'Instructor member login, optional (mlogin from membres table)',
                `purpose` VARCHAR(255) DEFAULT NULL COMMENT 'Purpose of reservation (e.g., training, cross-country)',
                `status` ENUM('pending', 'confirmed', 'completed', 'cancelled', 'no_show') DEFAULT 'pending' COMMENT 'Reservation status',
                `notes` TEXT DEFAULT NULL COMMENT 'Additional notes about the reservation',
                `section_id` INT(11) NOT NULL COMMENT 'Section ID (from sections.id)',
                `created_by` VARCHAR(25) DEFAULT NULL COMMENT 'User who created the reservation',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation timestamp',
                `updated_by` VARCHAR(25) DEFAULT NULL COMMENT 'User who last updated the reservation',
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
                PRIMARY KEY (`id`),
                KEY `idx_aircraft` (`aircraft_id`),
                KEY `idx_start_datetime` (`start_datetime`),
                KEY `idx_end_datetime` (`end_datetime`),
                KEY `idx_pilot` (`pilot_member_id`),
                KEY `idx_instructor` (`instructor_member_id`),
                KEY `idx_section` (`section_id`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Aircraft reservations for FullCalendar booking system'"
        );
        $errors += $this->run_queries($sqls);
        gvv_info("Migration database up to " . $this->migration_number . ", errors=$errors");
        return !$errors;
    }

    public function down() {
        $errors = 0;
        $sqls = array("DROP TABLE IF EXISTS `reservations`");
        $errors += $this->run_queries($sqls);
        gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");
        return !$errors;
    }
}

/* End of file 058_create_aircraft_reservations_table.php */
/* Location: ./application/migrations/058_create_aircraft_reservations_table.php */
