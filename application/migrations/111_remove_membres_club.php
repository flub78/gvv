<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Migration_Remove_membres_club extends CI_Migration {

    public function up() {
        $this->db->query("ALTER TABLE `membres` DROP COLUMN `club`");
    }

    public function down() {
        $this->db->query("
            ALTER TABLE `membres`
                ADD COLUMN `club` TINYINT(1) NULL DEFAULT 0
                    COMMENT 'Gestion multi-club'
                AFTER `macces`
        ");
    }
}
