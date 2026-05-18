<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Migration_add_color_to_calendar extends CI_Migration {
    public function up() {
        $this->db->query(
            "ALTER TABLE `calendar` ADD COLUMN `color` VARCHAR(7) NULL DEFAULT NULL AFTER `commentaire`"
        );
    }

    public function down() {
        $this->db->query("ALTER TABLE `calendar` DROP COLUMN `color`");
    }
}
