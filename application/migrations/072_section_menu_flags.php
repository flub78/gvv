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
 * Migration: Add menu visibility flags to sections table
 *
 * Adds three columns to sections:
 *   - gestion_planeurs (TINYINT): show glider flight submenu for this section
 *   - gestion_avions   (TINYINT): show aircraft/ULM flight submenu for this section
 *   - libelle_menu_avions (VARCHAR): custom label for the aircraft submenu
 *                                    (falls back to default translation when NULL/empty)
 *
 * All columns default to 0/NULL. After migration, the administrator must configure
 * each section via Admin → Sections to restore menu visibility.
 *
 * @see doc/design_notes/section_menu_visibility_plan.md
 */
class Migration_Section_Menu_Flags extends CI_Migration {

	protected $migration_number;

	function __construct() {
		parent::__construct();
		$this->migration_number = 72;
	}

	private function run_queries($sqls = array()) {
		$errors = 0;
		foreach ($sqls as $sql) {
			gvv_info("Migration sql: " . $sql);
			if (!$this->db->query($sql)) {
				$mysql_msg   = $this->db->_error_message();
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
			"ALTER TABLE sections
			   ADD COLUMN gestion_planeurs    TINYINT(1)  NOT NULL DEFAULT 0   AFTER ordre_affichage,
			   ADD COLUMN gestion_avions      TINYINT(1)  NOT NULL DEFAULT 0   AFTER gestion_planeurs,
			   ADD COLUMN libelle_menu_avions VARCHAR(64) NULL     DEFAULT NULL AFTER gestion_avions"
		);

		$errors += $this->run_queries($sqls);
		gvv_info("Migration database up to " . $this->migration_number . ", errors=$errors");

		return !$errors;
	}

	public function down() {
		$errors = 0;

		$sqls = array(
			"ALTER TABLE sections
			   DROP COLUMN libelle_menu_avions,
			   DROP COLUMN gestion_avions,
			   DROP COLUMN gestion_planeurs"
		);

		$errors += $this->run_queries($sqls);
		gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");

		return !$errors;
	}
}
