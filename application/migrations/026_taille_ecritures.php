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
 * Augmente la taille des montants des écritures
 *    
 * @author frederic
 *
 */
class Migration_Taille_ecritures extends CI_Migration {

	protected $migration_number;

	/**
	 *
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->migration_number = 26;
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
			"ALTER TABLE `comptes` CHANGE `debit` `debit` DECIMAL(14,2) NOT NULL DEFAULT '0.00' COMMENT 'Débit'",
			"ALTER TABLE `comptes` CHANGE `credit` `credit` DECIMAL(14,2) NOT NULL DEFAULT '0.00' COMMENT 'Crédit'",
			"ALTER TABLE `ecritures` CHANGE `montant` `montant` DECIMAL(14,2) NOT NULL COMMENT 'Montant de l\'écriture'",
			"ALTER TABLE `ecritures` CHANGE `description` `description` VARCHAR(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL COMMENT 'Libellé'"
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
			// "ALTER TABLE `comptes` CHANGE `debit` `debit` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Débit'",
			// "ALTER TABLE `comptes` CHANGE `credit` `credit` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Crédit'",
			// "ALTER TABLE `ecritures` CHANGE `montant` `montant` DECIMAL(10,2) NOT NULL COMMENT 'Montant de l\'écriture'",
			"ALTER TABLE `ecritures` CHANGE `description` `description` VARCHAR(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'Libellé'"
		);

		$errors += $this->run_queries($sqls);
		gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");

		return !$errors;
	}
}
