<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 124: create form_config_params table
 *
 * Stores key/value configuration parameters used to pre-fill forms via the
 * config.* taxonomy. Each parameter has a global or section-level scope.
 * Section-level parameters override global ones with the same key.
 */
class Migration_Form_config_params extends CI_Migration {

    public function up() {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `form_config_params` (
                `id`                INT(11)      NOT NULL AUTO_INCREMENT,
                `club_id`           INT(11)      NULL DEFAULT NULL COMMENT 'NULL = portée globale',
                `param_key`         VARCHAR(100) NOT NULL,
                `param_value`       TEXT         NULL DEFAULT NULL,
                `param_label`       VARCHAR(255) NOT NULL,
                `param_description` TEXT         NULL DEFAULT NULL,
                `created_at`        DATETIME     NULL DEFAULT NULL,
                `updated_at`        DATETIME     NULL DEFAULT NULL,
                `created_by`        VARCHAR(50)  NULL DEFAULT NULL,
                `updated_by`        VARCHAR(50)  NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_form_config_key` (`club_id`, `param_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );

        $this->db->query(
            "INSERT IGNORE INTO `form_config_params`
                (`club_id`, `param_key`, `param_value`, `param_label`, `param_description`, `created_at`, `updated_at`)
             VALUES
                (NULL, 'organisme_formation', '', 'Organisme de formation',
                 'Nom et identification de l\'organisme de formation utilisé dans les attestations et certificats.',
                 NOW(), NOW())"
        );
    }

    public function down() {
        $this->db->query("DROP TABLE IF EXISTS `form_config_params`");
    }
}
