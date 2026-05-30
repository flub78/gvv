<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 119: add files support for form submissions
 */
class Migration_Forms_files extends CI_Migration {

    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `form_submission_files` (
            `id`               INT(11)      NOT NULL AUTO_INCREMENT,
            `submission_id`    INT(11)      NOT NULL,
            `field_id`         INT(11)      NOT NULL,
            `original_name`    VARCHAR(255) NOT NULL,
            `stored_name`      VARCHAR(255) NOT NULL,
            `mime_type`        VARCHAR(100) NULL DEFAULT NULL,
            `size_bytes`       INT(11)      NULL DEFAULT NULL,
            `storage_path`     VARCHAR(500) NOT NULL,
            `created_at`       DATETIME     NULL DEFAULT NULL,
            `updated_at`       DATETIME     NULL DEFAULT NULL,
            `created_by`       VARCHAR(50)  NULL DEFAULT NULL,
            `updated_by`       VARCHAR(50)  NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_form_submission_files_submission` (`submission_id`),
            KEY `idx_form_submission_files_field` (`field_id`),
            CONSTRAINT `fk_form_submission_files_submission` FOREIGN KEY (`submission_id`)
                REFERENCES `form_submissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT `fk_form_submission_files_field` FOREIGN KEY (`field_id`)
                REFERENCES `form_fields` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

        log_message('info', 'Migration 119: form_submission_files created');
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS `form_submission_files`");
        log_message('info', 'Migration 119: form_submission_files dropped');
    }
}
