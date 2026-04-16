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
 * Migration 103: Agrandissement de la colonne string_releve
 *
 * La colonne string_releve de la table associations_ecriture était varchar(256).
 * Pour les relevés avec de nombreuses lignes de commentaires (virements avec
 * références longues), le str_releve généré dépassait 256 caractères et était
 * tronqué silencieusement par MySQL. Cela empêchait la reconnaissance des
 * rapprochements existants lors des consultations ultérieures.
 *
 * La colonne est étendue à varchar(512).
 */
class Migration_Associations_Ecriture_String_Releve extends CI_Migration {

	protected $migration_number;

	function __construct() {
		parent::__construct();
		$this->migration_number = 103;
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
			"ALTER TABLE associations_ecriture MODIFY COLUMN string_releve VARCHAR(512) NOT NULL",
		);
		$errors += $this->run_queries($sqls);

		gvv_info("Migration database up to " . $this->migration_number . ", errors=$errors");
		return !$errors;
	}

	public function down() {
		$errors = 0;

		$sqls = array(
			"ALTER TABLE associations_ecriture MODIFY COLUMN string_releve VARCHAR(256) NOT NULL",
		);

		$errors += $this->run_queries($sqls);
		gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");

		return !$errors;
	}
}
