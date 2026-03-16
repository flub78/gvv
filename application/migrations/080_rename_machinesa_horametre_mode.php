<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Migration_Rename_machinesa_horametre_mode extends CI_Migration {

    public function up() {
        if ($this->db->field_exists('horametre_en_minutes', 'machinesa') && !$this->db->field_exists('horametre_mode', 'machinesa')) {
            $this->db->query("\n                ALTER TABLE `machinesa`\n                CHANGE `horametre_en_minutes` `horametre_mode` INT(11) DEFAULT 0\n                COMMENT 'Format horamètre (0=1/100h, 1=heures/minutes, 2=1/10h)'\n            ");
        }
    }

    public function down() {
        if ($this->db->field_exists('horametre_mode', 'machinesa') && !$this->db->field_exists('horametre_en_minutes', 'machinesa')) {
            $this->db->query("\n                ALTER TABLE `machinesa`\n                CHANGE `horametre_mode` `horametre_en_minutes` INT(11) DEFAULT 0\n                COMMENT 'Horamètre en heures et minutes'\n            ");
        }
    }
}