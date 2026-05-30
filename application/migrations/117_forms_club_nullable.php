<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 117: allow global forms (no section/club binding)
 *
 * Forms can now belong to a section (`club`) or be global (`club` NULL).
 */
class Migration_Forms_club_nullable extends CI_Migration {

    public function up()
    {
        $this->db->query("ALTER TABLE `forms` MODIFY `club` INT(11) NULL DEFAULT NULL");
        log_message('info', 'Migration 117: forms.club changed to NULLABLE');
    }

    public function down()
    {
        // Ensure rollback does not fail if NULL values are present.
        $this->db->query("UPDATE `forms` SET `club` = 0 WHERE `club` IS NULL");
        $this->db->query("ALTER TABLE `forms` MODIFY `club` INT(11) NOT NULL");
        log_message('info', 'Migration 117: forms.club changed back to NOT NULL');
    }
}
