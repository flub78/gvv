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
 * Migration: Add ordre_affichage column to sections table
 *
 * Adds a display order field to sections to control their display order.
 * The field is nullable to allow sections without a specific display order.
 *
 * @author Claude Code
 */
class Migration_Add_Ordre_Affichage_To_Sections extends CI_Migration {

	protected $migration_number;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->migration_number = 56;
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
			"ALTER TABLE sections ADD COLUMN ordre_affichage INT NULL AFTER couleur"
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
			"ALTER TABLE sections DROP COLUMN ordre_affichage"
		);

		$errors += $this->run_queries($sqls);
		gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");

		return !$errors;
	}
}
