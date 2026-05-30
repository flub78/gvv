<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 116: create core tables for the forms module
 *
 * This first migration only creates the standalone forms core used by the
 * initial Google-Forms-like delivery: forms, pages, fields, submissions and
 * submission values.
 */
class Migration_Forms_core extends CI_Migration {

	protected $migration_number;

	function __construct() {
		parent::__construct();
		$this->migration_number = 116;
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
			"CREATE TABLE IF NOT EXISTS `forms` (
				`id`            INT(11)      NOT NULL AUTO_INCREMENT,
				`club`          INT(11)      NOT NULL,
				`code`          VARCHAR(50)  NOT NULL,
				`title`         VARCHAR(255) NOT NULL,
				`description`   TEXT         NULL DEFAULT NULL,
				`status`        ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
				`public_slug`   VARCHAR(100) NOT NULL,
				`css_scope`     VARCHAR(100) NULL DEFAULT NULL,
				`created_at`    DATETIME     NULL DEFAULT NULL,
				`updated_at`    DATETIME     NULL DEFAULT NULL,
				`created_by`    VARCHAR(50)  NULL DEFAULT NULL,
				`updated_by`    VARCHAR(50)  NULL DEFAULT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `uq_forms_code` (`code`),
				UNIQUE KEY `uq_forms_public_slug` (`public_slug`),
				KEY `idx_forms_club` (`club`),
				KEY `idx_forms_status` (`status`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8",

			"CREATE TABLE IF NOT EXISTS `form_pages` (
				`id`            INT(11)      NOT NULL AUTO_INCREMENT,
				`form_id`       INT(11)      NOT NULL,
				`page_number`   INT(11)      NOT NULL,
				`title`         VARCHAR(255) NULL DEFAULT NULL,
				`content_html`  MEDIUMTEXT   NULL DEFAULT NULL,
				`created_at`    DATETIME     NULL DEFAULT NULL,
				`updated_at`    DATETIME     NULL DEFAULT NULL,
				`created_by`    VARCHAR(50)  NULL DEFAULT NULL,
				`updated_by`    VARCHAR(50)  NULL DEFAULT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `uq_form_pages_number` (`form_id`, `page_number`),
				CONSTRAINT `fk_form_pages_form` FOREIGN KEY (`form_id`)
					REFERENCES `forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
			) ENGINE=InnoDB DEFAULT CHARSET=utf8",

			"CREATE TABLE IF NOT EXISTS `form_fields` (
				`id`               INT(11)      NOT NULL AUTO_INCREMENT,
				`form_id`          INT(11)      NOT NULL,
				`page_id`          INT(11)      NOT NULL,
				`name`             VARCHAR(100) NOT NULL,
				`label`            VARCHAR(255) NOT NULL,
				`field_type`       ENUM('text','email','date','number','textarea','select','radio','checkbox','file') NOT NULL,
				`is_required`      TINYINT(1)   NOT NULL DEFAULT 0,
				`sort_order`       INT(11)      NOT NULL DEFAULT 0,
				`options_json`     TEXT         NULL DEFAULT NULL,
				`validation_rules` TEXT         NULL DEFAULT NULL,
				`created_at`       DATETIME     NULL DEFAULT NULL,
				`updated_at`       DATETIME     NULL DEFAULT NULL,
				`created_by`       VARCHAR(50)  NULL DEFAULT NULL,
				`updated_by`       VARCHAR(50)  NULL DEFAULT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `uq_form_fields_name` (`form_id`, `name`),
				KEY `idx_form_fields_page` (`page_id`),
				CONSTRAINT `fk_form_fields_form` FOREIGN KEY (`form_id`)
					REFERENCES `forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
				CONSTRAINT `fk_form_fields_page` FOREIGN KEY (`page_id`)
					REFERENCES `form_pages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
			) ENGINE=InnoDB DEFAULT CHARSET=utf8",

			"CREATE TABLE IF NOT EXISTS `form_submissions` (
				`id`                 INT(11)       NOT NULL AUTO_INCREMENT,
				`form_id`            INT(11)       NOT NULL,
				`submission_uuid`    VARCHAR(64)   NOT NULL,
				`status`             ENUM('started','submitted','archived') NOT NULL DEFAULT 'submitted',
				`submitter_email`    VARCHAR(255)  NULL DEFAULT NULL,
				`submitter_name`     VARCHAR(255)  NULL DEFAULT NULL,
				`source_ip`          VARCHAR(45)   NULL DEFAULT NULL,
				`user_agent`         VARCHAR(255)  NULL DEFAULT NULL,
				`submitted_at`       DATETIME      NULL DEFAULT NULL,
				`created_at`         DATETIME      NULL DEFAULT NULL,
				`updated_at`         DATETIME      NULL DEFAULT NULL,
				`created_by`         VARCHAR(50)   NULL DEFAULT NULL,
				`updated_by`         VARCHAR(50)   NULL DEFAULT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `uq_form_submissions_uuid` (`submission_uuid`),
				KEY `idx_form_submissions_form` (`form_id`),
				KEY `idx_form_submissions_status` (`status`),
				CONSTRAINT `fk_form_submissions_form` FOREIGN KEY (`form_id`)
					REFERENCES `forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
			) ENGINE=InnoDB DEFAULT CHARSET=utf8",

			"CREATE TABLE IF NOT EXISTS `form_submission_values` (
				`id`               INT(11)      NOT NULL AUTO_INCREMENT,
				`submission_id`    INT(11)      NOT NULL,
				`field_id`         INT(11)      NOT NULL,
				`value_text`       MEDIUMTEXT   NULL DEFAULT NULL,
				`created_at`       DATETIME     NULL DEFAULT NULL,
				`updated_at`       DATETIME     NULL DEFAULT NULL,
				`created_by`       VARCHAR(50)  NULL DEFAULT NULL,
				`updated_by`       VARCHAR(50)  NULL DEFAULT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `uq_form_submission_value` (`submission_id`, `field_id`),
				KEY `idx_form_submission_values_field` (`field_id`),
				CONSTRAINT `fk_form_submission_values_submission` FOREIGN KEY (`submission_id`)
					REFERENCES `form_submissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
				CONSTRAINT `fk_form_submission_values_field` FOREIGN KEY (`field_id`)
					REFERENCES `form_fields` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
			) ENGINE=InnoDB DEFAULT CHARSET=utf8",
		);

		$errors += $this->run_queries($sqls);
		gvv_info("Migration database up to " . $this->migration_number . ", errors=$errors");

		return !$errors;
	}

	public function down() {
		$errors = 0;

		$sqls = array(
			"DROP TABLE IF EXISTS `form_submission_values`",
			"DROP TABLE IF EXISTS `form_submissions`",
			"DROP TABLE IF EXISTS `form_fields`",
			"DROP TABLE IF EXISTS `form_pages`",
			"DROP TABLE IF EXISTS `forms`",
		);

		$errors += $this->run_queries($sqls);
		gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");

		return !$errors;
	}
}