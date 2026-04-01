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
 * Migration 097: Create paiements_en_ligne and paiements_en_ligne_config tables
 *
 * paiements_en_ligne  — tracks each online payment transaction (HelloAsso, etc.)
 * paiements_en_ligne_config — stores per-section platform credentials (encrypted)
 */
class Migration_Paiements_En_Ligne extends CI_Migration {

	protected $migration_number;

	function __construct() {
		parent::__construct();
		$this->migration_number = 97;
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
			"CREATE TABLE IF NOT EXISTS `paiements_en_ligne` (
				`id`             INT(11)        NOT NULL AUTO_INCREMENT,
				`user_id`        INT(11)        NOT NULL,
				`montant`        DECIMAL(10,2)  NOT NULL,
				`plateforme`     VARCHAR(50)    NOT NULL,
				`transaction_id` VARCHAR(255)   NULL DEFAULT NULL,
				`ecriture_id`    INT(11)        NULL DEFAULT NULL,
				`statut`         ENUM('pending','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
				`date_demande`   DATETIME       NOT NULL,
				`date_paiement`  DATETIME       NULL DEFAULT NULL,
				`metadata`       TEXT           NULL DEFAULT NULL,
				`commission`     DECIMAL(10,2)  NULL DEFAULT 0.00,
				`club`           INT(11)        NOT NULL,
				`created_at`     DATETIME       NULL DEFAULT NULL,
				`updated_at`     DATETIME       NULL DEFAULT NULL,
				`created_by`     VARCHAR(50)    NULL DEFAULT NULL,
				`updated_by`     VARCHAR(50)    NULL DEFAULT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `uq_transaction_id` (`transaction_id`),
				KEY `idx_user_id` (`user_id`),
				KEY `idx_statut` (`statut`),
				KEY `idx_date_paiement` (`date_paiement`),
				KEY `idx_club` (`club`),
				CONSTRAINT `fk_pel_ecriture` FOREIGN KEY (`ecriture_id`)
					REFERENCES `ecritures` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
			) ENGINE=InnoDB DEFAULT CHARSET=utf8",

			"CREATE TABLE IF NOT EXISTS `paiements_en_ligne_config` (
				`id`          INT(11)      NOT NULL AUTO_INCREMENT,
				`plateforme`  VARCHAR(50)  NOT NULL,
				`param_key`   VARCHAR(100) NOT NULL,
				`param_value` TEXT         NULL DEFAULT NULL,
				`club`        INT(11)      NOT NULL,
				`created_at`  DATETIME     NULL DEFAULT NULL,
				`updated_at`  DATETIME     NULL DEFAULT NULL,
				`created_by`  VARCHAR(50)  NULL DEFAULT NULL,
				`updated_by`  VARCHAR(50)  NULL DEFAULT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `uq_config_key` (`plateforme`, `param_key`, `club`),
				KEY `idx_config_club` (`club`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8",
		);

		$errors += $this->run_queries($sqls);
		gvv_info("Migration database up to " . $this->migration_number . ", errors=$errors");

		return !$errors;
	}

	public function down() {
		$errors = 0;

		$sqls = array(
			"DROP TABLE IF EXISTS `paiements_en_ligne`",
			"DROP TABLE IF EXISTS `paiements_en_ligne_config`",
		);

		$errors += $this->run_queries($sqls);
		gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");

		return !$errors;
	}
}
