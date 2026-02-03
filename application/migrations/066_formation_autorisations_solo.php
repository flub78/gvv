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
 * Migration 066: Create Formation Autorisations Solo Table
 *
 * Creates table for solo flight authorizations:
 * - formation_autorisations_solo: Solo flight authorizations given by instructors to students
 *
 * @see doc/design_notes/autorisations_vol_solo_plan.md
 */
class Migration_Formation_autorisations_solo extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 66;
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
            // Table: formation_autorisations_solo - Solo flight authorizations
            "CREATE TABLE IF NOT EXISTS `formation_autorisations_solo` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `inscription_id` INT(11) NOT NULL COMMENT 'FK to formation_inscriptions',
                `eleve_id` VARCHAR(25) NOT NULL COMMENT 'Student member login',
                `instructeur_id` VARCHAR(25) NOT NULL COMMENT 'Instructor member login',
                `date_autorisation` DATE NOT NULL COMMENT 'Authorization date',
                `section_id` INT(11) NULL COMMENT 'Section ID',
                `machine_id` VARCHAR(10) NOT NULL COMMENT 'Aircraft registration',
                `consignes` TEXT NOT NULL COMMENT 'Instructions (min 250 chars)',
                `date_creation` DATETIME NOT NULL COMMENT 'Creation timestamp',
                `date_modification` DATETIME NULL COMMENT 'Last modification timestamp',
                PRIMARY KEY (`id`),
                KEY `idx_inscription` (`inscription_id`),
                KEY `idx_eleve` (`eleve_id`),
                KEY `idx_instructeur` (`instructeur_id`),
                KEY `idx_date` (`date_autorisation`),
                KEY `idx_section` (`section_id`),
                CONSTRAINT `fk_autosolo_inscription` FOREIGN KEY (`inscription_id`)
                    REFERENCES `formation_inscriptions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_autosolo_eleve` FOREIGN KEY (`eleve_id`)
                    REFERENCES `membres` (`mlogin`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_autosolo_instructeur` FOREIGN KEY (`instructeur_id`)
                    REFERENCES `membres` (`mlogin`) ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT `fk_autosolo_section` FOREIGN KEY (`section_id`)
                    REFERENCES `sections` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
            COMMENT='Solo flight authorizations for students'"
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database up to " . $this->migration_number . ", errors=$errors");
        return !$errors;
    }

    public function down() {
        $errors = 0;

        $sqls = array(
            "DROP TABLE IF EXISTS `formation_autorisations_solo`"
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");
        return !$errors;
    }
}

/* End of file 066_formation_autorisations_solo.php */
/* Location: ./application/migrations/066_formation_autorisations_solo.php */
