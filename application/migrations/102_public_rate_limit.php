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
 * Migration 102: Table de rate limiting pour les formulaires publics
 *
 * Crée la table `public_rate_limit` permettant de limiter le nombre de
 * soumissions du formulaire public par adresse IP sur une fenêtre glissante.
 *
 * Clé primaire composite (ip, endpoint) : une seule ligne par IP et par
 * point d'entrée. Le compteur `attempts` est incrémenté à chaque soumission.
 * La colonne `window_start` marque le début de la fenêtre courante.
 */
class Migration_Public_Rate_Limit extends CI_Migration {

	protected $migration_number;

	function __construct() {
		parent::__construct();
		$this->migration_number = 102;
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
			"CREATE TABLE public_rate_limit (
			    ip           VARCHAR(45)  NOT NULL,
			    endpoint     VARCHAR(50)  NOT NULL,
			    attempts     INT          NOT NULL DEFAULT 1,
			    window_start DATETIME     NOT NULL,
			    PRIMARY KEY (ip, endpoint)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8",
		);
		$errors += $this->run_queries($sqls);

		gvv_info("Migration database up to " . $this->migration_number . ", errors=$errors");
		return !$errors;
	}

	public function down() {
		$errors = 0;

		$sqls = array(
			"DROP TABLE IF EXISTS public_rate_limit",
		);

		$errors += $this->run_queries($sqls);
		gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");

		return !$errors;
	}
}
