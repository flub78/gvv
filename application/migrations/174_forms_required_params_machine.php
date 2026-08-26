<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 174: forms.required_params - dimension "machine"
 *
 * Un formulaire public peut dépendre d'un pilote, d'un instructeur et/ou
 * d'une machine (ex. numéro d'identification ULM). Étend l'ENUM existant
 * (none/pilot/instructor/pilot+instructor, migration 125) aux 4 combinaisons
 * incluant la machine, pour rester cohérent avec le produit cartésien déjà
 * en place sur les deux premières dimensions.
 */
class Migration_Forms_required_params_machine extends CI_Migration {
    public function up() {
        $this->db->query(
            "ALTER TABLE `forms` MODIFY COLUMN `required_params`
             ENUM('none','pilot','instructor','machine','pilot+instructor','pilot+machine','instructor+machine','pilot+instructor+machine')
             NOT NULL DEFAULT 'none'"
        );
    }

    public function down() {
        // Aucune combinaison "machine" ne doit pouvoir subsister avant de rétrécir l'ENUM.
        $this->db->query("UPDATE `forms` SET `required_params` = 'pilot+instructor' WHERE `required_params` = 'pilot+instructor+machine'");
        $this->db->query("UPDATE `forms` SET `required_params` = 'instructor' WHERE `required_params` = 'instructor+machine'");
        $this->db->query("UPDATE `forms` SET `required_params` = 'pilot' WHERE `required_params` = 'pilot+machine'");
        $this->db->query("UPDATE `forms` SET `required_params` = 'none' WHERE `required_params` = 'machine'");
        $this->db->query(
            "ALTER TABLE `forms` MODIFY COLUMN `required_params`
             ENUM('none','pilot','instructor','pilot+instructor')
             NOT NULL DEFAULT 'none'"
        );
    }
}
