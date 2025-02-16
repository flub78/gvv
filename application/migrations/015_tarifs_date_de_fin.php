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
class Migration_Tarifs_Date_De_Fin extends CI_Migration {

	protected $number;

	/**
	 *
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->number = 15;
	}

	/*
	 * Execute an array of sql requests
	 */
	private function run_queries($sqls = array()) {
		$errors = 0;
		foreach ($sqls as $sql) {
			// echo $sql . br();
			if (!$this->db->query($sql)) {
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
			"ALTER TABLE `tarifs` ADD `date_fin` DATE NULL DEFAULT '2099-12-31' COMMENT 'Date de fin' AFTER `date` ;",
			"ALTER TABLE `tarifs` ADD `public` TINYINT NULL DEFAULT '1' COMMENT 'Permet le filtrage sur l''impression';"
		);

		$errors += $this->run_queries($sqls);
		gvv_info("Migration database up to " . $this->number . ", errors=$errors");

		return !$errors;
	}

	/**
	 * Reverse the migration
	 */
	public function down() {
		$errors = 0;
		$sqls = array(
			"ALTER TABLE `tarifs` DROP `date_fin`;",
			"ALTER TABLE `tarifs` DROP `public`;"
		);

		$errors += $this->run_queries($sqls);
		gvv_info("Migration database down to " . $this->number - 1 . ", errors=$errors");

		return !$errors;
	}
}
