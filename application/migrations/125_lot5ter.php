<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Lot5ter extends CI_Migration {
    public function up() {
        // 1. events.signature_path
        $this->db->query(
            "ALTER TABLE `events` ADD COLUMN `signature_path` VARCHAR(255) NULL
             COMMENT 'Chemin vers la signature image associée à cet événement'
             AFTER `date_expiration`"
        );
        // 2. ULM event types
        $this->db->query(
            "INSERT IGNORE INTO `events_types` (`name`, `activite`, `en_vol`, `multiple`, `expirable`, `ordre`, `annual`)
             VALUES ('FI ULM', 2, 0, 0, 1, 90, 0), ('FE ULM', 2, 0, 0, 1, 91, 0)"
        );
        // 3. forms.required_params
        $this->db->query(
            "ALTER TABLE `forms` ADD COLUMN `required_params`
             ENUM('none','pilot','instructor','pilot+instructor') NOT NULL DEFAULT 'none'
             AFTER `global_css`"
        );
    }

    public function down() {
        $this->db->query("ALTER TABLE `forms` DROP COLUMN `required_params`");
        $this->db->query("DELETE FROM `events_types` WHERE `name` IN ('FI ULM', 'FE ULM')");
        $this->db->query("ALTER TABLE `events` DROP COLUMN `signature_path`");
    }
}
