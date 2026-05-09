<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Migration_Formation_seance_theorique extends CI_Migration {

    public function up() {
        if (!$this->db->field_exists('seance_theorique', 'formation_seances')) {
            $this->db->query("
                ALTER TABLE `formation_seances`
                    ADD COLUMN `seance_theorique` TINYINT(1) NOT NULL DEFAULT 0
                        COMMENT 'Séance uniquement théorique : aéronef/durée/atterrissages non requis'
                    AFTER `categorie_seance`
            ");
        }
    }

    public function down() {
        if ($this->db->field_exists('seance_theorique', 'formation_seances')) {
            $this->db->query("ALTER TABLE `formation_seances` DROP COLUMN `seance_theorique`");
        }
    }
}
