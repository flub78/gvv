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
 * Migration 101: Nombre maximum de passagers par produit VD
 *
 * Ajoute le champ `nb_personnes_max` à la table `tarifs`.
 * Ce champ indique le nombre maximum de passagers autorisé pour un produit
 * de vol de découverte (type_ticket = 1).
 *
 *   - Valeur 1 : vol solo (un seul bénéficiaire)
 *   - Valeur > 1 : vol multi-places
 *
 * Valeur par défaut : 1 (comportement conservé pour les produits existants).
 */
class Migration_Tarifs_Nb_Personnes_Max extends CI_Migration {

	protected $migration_number;

	function __construct() {
		parent::__construct();
		$this->migration_number = 101;
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
			"ALTER TABLE tarifs
			   ADD COLUMN nb_personnes_max TINYINT UNSIGNED NOT NULL DEFAULT 1
			   AFTER prix",
		);
		$errors += $this->run_queries($sqls);

		gvv_info("Migration database up to " . $this->migration_number . ", errors=$errors");
		return !$errors;
	}

	public function down() {
		$errors = 0;

		$sqls = array(
			"ALTER TABLE tarifs DROP COLUMN nb_personnes_max",
		);

		$errors += $this->run_queries($sqls);
		gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");

		return !$errors;
	}
}
