<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Migration_Formation_seance_instructeur_nullable extends CI_Migration {

    public function up() {
        $this->db->query("
            ALTER TABLE `formation_seances`
                MODIFY `instructeur_id` VARCHAR(25) NULL
                    COMMENT 'NULL autorisé pour séances théoriques sans instructeur désigné'
        ");
    }

    public function down() {
        // Best-effort : peut échouer si des lignes ont instructeur_id NULL
        $this->db->query("
            UPDATE `formation_seances` SET `instructeur_id` = '' WHERE `instructeur_id` IS NULL
        ");
        $this->db->query("
            ALTER TABLE `formation_seances`
                MODIFY `instructeur_id` VARCHAR(25) NOT NULL
        ");
    }
}
