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
 * Passe les compteurs de tickets en décimal
 *    
 * @author frederic
 *
 */
class Migration_Associations_of extends CI_Migration {

	protected $migration_number;

	/**
	 *
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->migration_number = 32;
	}

	/*
	 * Execute an array of sql requests
	 */
	private function run_queries($sqls = array()) {
		$errors = 0;
		foreach ($sqls as $sql) {
			gvv_info("Migration sql: " . $sql);
			if (!$this->db->query($sql)) {
				gvv_error("Migration error: " . $this->db->error()['message']);
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
			"CREATE TABLE `associations_of` (
  				`id` bigint(20) UNSIGNED NOT NULL,
                `id_compte_of` int(11) NOT NULL,
  				`nom_of` varchar(60) NOT NULL,
                `id_compte_gvv` int(11) DEFAULT NULL

			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
			"ALTER TABLE `associations_of` ADD PRIMARY KEY (`id`)",
			"ALTER TABLE `associations_of`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT"
		);

		$errors += $this->run_queries($sqls);
		gvv_info("Migration database up to " . $this->migration_number . ", errors=$errors");

		return !$errors;
	}

	/**
	 * Reverse the migration
	 */
	public function down() {
		$errors = 0;
		$sqls = array(
			"DROP TABLE IF EXISTS associations_of"
		);

		$errors += $this->run_queries($sqls);
		gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");

		return !$errors;
	}
}
