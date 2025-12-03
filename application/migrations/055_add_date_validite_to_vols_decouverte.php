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
 *	Script de migration de la base
 */

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 * Migration: Add date_validite column to vols_decouverte table
 *
 * Adds an independent validity date field to discovery flights.
 * This allows correcting the sale date without affecting the validity date.
 *
 * @author Claude Code
 */
class Migration_Add_Date_Validite_To_Vols_Decouverte extends CI_Migration {

	protected $migration_number;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->migration_number = 55;
	}

	/**
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
			"ALTER TABLE vols_decouverte ADD COLUMN date_validite DATE NULL AFTER date_vente"
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
			"ALTER TABLE vols_decouverte DROP COLUMN date_validite"
		);

		$errors += $this->run_queries($sqls);
		gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");

		return !$errors;
	}
}
