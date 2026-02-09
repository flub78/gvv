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
 * Migration 069: Create preparation_cards table
 *
 * Stores dashboard cards for the “Météo & préparation des vols” page.
 *
 * @see doc/prds/meteo_prd.md
 * @see doc/plans/meteo_plan.md
 */
class Migration_Create_preparation_cards extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 69;
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
            "CREATE TABLE IF NOT EXISTS `preparation_cards` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `title` VARCHAR(255) NOT NULL COMMENT 'Titre de la carte',
                `type` ENUM('html','link','iframe') NOT NULL DEFAULT 'html' COMMENT 'Type de carte',
                `html_fragment` MEDIUMTEXT NULL COMMENT 'Snippet HTML tiers (optionnel)',
                `image_url` VARCHAR(255) NULL COMMENT 'URL miniature',
                `link_url` VARCHAR(255) NULL COMMENT 'URL de redirection',
                `category` VARCHAR(128) NULL COMMENT 'Categorie libre',
                `display_order` INT(11) NOT NULL DEFAULT 0 COMMENT 'Ordre affichage',
                `visible` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Visibilite',
                `created_at` DATETIME NOT NULL COMMENT 'Date creation',
                `updated_at` DATETIME NULL COMMENT 'Date mise a jour',
                PRIMARY KEY (`id`),
                KEY `idx_type` (`type`),
                KEY `idx_visible` (`visible`),
                KEY `idx_display_order` (`display_order`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
            COMMENT='Cartes de preparation des vols'"
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database up to " . $this->migration_number . ", errors=$errors");
        return !$errors;
    }

    public function down() {
        $errors = 0;

        $sqls = array(
            "DROP TABLE IF EXISTS `preparation_cards`"
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");
        return !$errors;
    }
}

/* End of file 069_create_preparation_cards.php */
/* Location: ./application/migrations/069_create_preparation_cards.php */
