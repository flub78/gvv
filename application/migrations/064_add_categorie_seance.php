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
 * Migration 064: Add categorie_seance to formation_seances
 *
 * Adds a configurable category field to training sessions for better
 * classification (Formation, Remise en vol, Réentrainement, Contrôle pilote VLD)
 */
class Migration_Add_categorie_seance extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 64;
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
            // Add categorie_seance column to formation_seances
            "ALTER TABLE `formation_seances`
             ADD COLUMN `categorie_seance` VARCHAR(100) NULL
             COMMENT 'Session category (Formation, Remise en vol, Réentrainement, Contrôle pilote VLD)'
             AFTER `programme_id`",

            // Add index for reporting queries
            "ALTER TABLE `formation_seances`
             ADD INDEX `idx_categorie` (`categorie_seance`)",

            // Insert default configuration for session categories
            "INSERT INTO `configuration` (`cle`, `valeur`, `lang`, `categorie`, `description`)
             VALUES ('formation.categories_seance', 'Formation, Remise en vol (REV), Réentrainement, Contrôle pilote VLD', NULL, 'formation', 'Liste des catégories de séances de formation (séparées par des virgules)')
             ON DUPLICATE KEY UPDATE `cle` = `cle`"
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database up to " . $this->migration_number . ", errors=$errors");
        return !$errors;
    }

    public function down() {
        $errors = 0;

        $sqls = array(
            // Remove index
            "ALTER TABLE `formation_seances` DROP INDEX `idx_categorie`",

            // Remove column
            "ALTER TABLE `formation_seances` DROP COLUMN `categorie_seance`",

            // Remove configuration
            "DELETE FROM `configuration` WHERE `cle` = 'formation.categories_seance'"
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");
        return !$errors;
    }
}

/* End of file 064_add_categorie_seance.php */
/* Location: ./application/migrations/064_add_categorie_seance.php */
