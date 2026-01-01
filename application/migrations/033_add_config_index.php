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
 *
 *	  Script de migration de la base
 */

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 * Add index to configuration table
 *    
 * @author frederic
 *
 */
class Migration_Add_config_index extends CI_Migration {

	protected $migration_number;

	/**
	 *
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->migration_number = 33;
	}

	/*
	 * Execute an array of sql requests
	 */
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


	/**
	 * Apply the migration
	 */
	public function up() {
		$errors = 0;

		$sqls = array(
			"ALTER TABLE `configuration` ADD UNIQUE INDEX `idx_cle_lang_club` (`cle`, `lang`, `club`);",
			"ALTER TABLE `configuration` CHANGE `valeur` `valeur` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;",
			"ALTER TABLE `configuration` CHANGE `description` `description` VARCHAR(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;",
			"ALTER TABLE `configuration` CHANGE `lang` `lang` VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL;"
		);

		$errors += $this->run_queries($sqls);
		gvv_info("Migration database up to " . $this->migration_number . ", errors=$errors");

		return !$errors;
	}

	/**
	 * Reverse the migration
	 * 
	 * I am not sure that there is any need to reverse the migration.
	 * The only effect would be to truncate existing data.
	 * I just keep it in comment in case we need it.
	 */
	public function down() {
		$errors = 0;
		$sqls = array(
			"ALTER TABLE `configuration` DROP INDEX `idx_cle_lang_club`;",
			"ALTER TABLE `configuration` CHANGE `valeur` `valeur` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;"
		);

		$errors += $this->run_queries($sqls);
		gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");

		return !$errors;
	}
}
