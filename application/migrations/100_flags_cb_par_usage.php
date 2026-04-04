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
 * Migration 100: Flags CB par usage (Option B)
 *
 * Remplace le flag global `enabled` (qui bloquait ou autorisait tous les flux CB)
 * par deux flags fonctionnels indépendants stockés dans `sections` :
 *
 *   - has_vd_par_cb       : les vols de découverte peuvent être payés par CB
 *   - has_approvisio_par_cb : le provisionnement de compte peut être fait par CB
 *
 * Data migration : initialise les deux flags à 1 pour toutes les sections
 * qui ont actuellement `enabled = '1'` dans `paiements_en_ligne_config`,
 * afin de ne pas régresser les sections déjà en production.
 */
class Migration_Flags_Cb_Par_Usage extends CI_Migration {

	protected $migration_number;

	function __construct() {
		parent::__construct();
		$this->migration_number = 100;
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

		// Ajout des colonnes dans sections
		$sqls = array(
			"ALTER TABLE sections
			   ADD COLUMN has_vd_par_cb TINYINT(1) NOT NULL DEFAULT 0 AFTER bar_account_id",
			"ALTER TABLE sections
			   ADD COLUMN has_approvisio_par_cb TINYINT(1) NOT NULL DEFAULT 0 AFTER has_vd_par_cb",
		);
		$errors += $this->run_queries($sqls);

		// Data migration : activer les deux flags pour les sections déjà configurées
		// (sections qui avaient enabled = '1' dans paiements_en_ligne_config)
		if (!$errors) {
			$data_migration = "
				UPDATE sections s
				INNER JOIN paiements_en_ligne_config c ON c.club = s.id
				SET s.has_vd_par_cb = 1,
				    s.has_approvisio_par_cb = 1
				WHERE c.plateforme = 'helloasso'
				  AND c.param_key = 'enabled'
				  AND c.param_value = '1'
			";
			$errors += $this->run_queries(array($data_migration));
		}

		gvv_info("Migration database up to " . $this->migration_number . ", errors=$errors");
		return !$errors;
	}

	public function down() {
		$errors = 0;

		$sqls = array(
			"ALTER TABLE sections DROP COLUMN has_approvisio_par_cb",
			"ALTER TABLE sections DROP COLUMN has_vd_par_cb",
		);

		$errors += $this->run_queries($sqls);
		gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");

		return !$errors;
	}
}
