<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 * Migration 098: Create cotisation_produits table
 *
 * cotisation_produits — catalogue des produits de cotisation par section,
 * utilisé par le pilote pour payer sa cotisation en ligne (UC3).
 */
class Migration_Cotisation_Produits extends CI_Migration {

	protected $migration_number;

	function __construct() {
		parent::__construct();
		$this->migration_number = 98;
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
			"CREATE TABLE IF NOT EXISTS `cotisation_produits` (
				`id`                   INT(11)        NOT NULL AUTO_INCREMENT,
				`section_id`           INT(11)        NOT NULL COMMENT 'Identifiant de la section',
				`libelle`              VARCHAR(150)   NOT NULL COMMENT 'Libellé du produit de cotisation',
				`montant`              DECIMAL(10,2)  NOT NULL COMMENT 'Montant en euros',
				`annee`                INT(4)         NOT NULL COMMENT 'Année de validité de la cotisation',
				`compte_cotisation_id` INT(11)        NOT NULL COMMENT 'Compte 417 à créditer',
				`actif`                TINYINT(1)     NOT NULL DEFAULT 1 COMMENT 'Produit actif (visible au pilote)',
				`created_at`           DATETIME       NULL DEFAULT NULL,
				`updated_at`           DATETIME       NULL DEFAULT NULL,
				`created_by`           VARCHAR(50)    NULL DEFAULT NULL,
				`updated_by`           VARCHAR(50)    NULL DEFAULT NULL,
				PRIMARY KEY (`id`),
				KEY `idx_section_actif` (`section_id`, `actif`),
				KEY `idx_section_annee` (`section_id`, `annee`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8",
		);

		$errors += $this->run_queries($sqls);
		gvv_info("Migration database up to " . $this->migration_number . ", errors=$errors");

		return !$errors;
	}

	public function down() {
		$errors = 0;

		$sqls = array(
			"DROP TABLE IF EXISTS `cotisation_produits`",
		);

		$errors += $this->run_queries($sqls);
		gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");

		return !$errors;
	}
}
