<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 118: add global CSS support for forms
 */
class Migration_Forms_global_css extends CI_Migration {

    public function up()
    {
        $this->db->query("ALTER TABLE `forms` ADD COLUMN `global_css` MEDIUMTEXT NULL DEFAULT NULL AFTER `css_scope`");
        log_message('info', 'Migration 118: forms.global_css added');
    }

    public function down()
    {
        $this->db->query("ALTER TABLE `forms` DROP COLUMN `global_css`");
        log_message('info', 'Migration 118: forms.global_css dropped');
    }
}
