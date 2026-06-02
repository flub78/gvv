<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 120: add gvv_role column to form_fields
 *
 * Supports data-gvv-role HTML attribute to map a field to a submission
 * metadata slot (submitter_email, submitter_name).
 */
class Migration_Form_fields_gvv_role extends CI_Migration {

    public function up()
    {
        $this->db->query("ALTER TABLE `form_fields`
            ADD COLUMN `gvv_role` VARCHAR(50) NULL DEFAULT NULL
            AFTER `validation_rules`");

        log_message('info', 'Migration 120: gvv_role added to form_fields');
    }

    public function down()
    {
        $this->db->query("ALTER TABLE `form_fields` DROP COLUMN `gvv_role`");
        log_message('info', 'Migration 120: gvv_role removed from form_fields');
    }
}
