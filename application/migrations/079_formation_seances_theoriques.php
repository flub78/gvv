<?php
/**
 * GVV Gestion vol à voile
 * Migration 079 – Séances théoriques multi-participants
 *
 * - Crée formation_seances_participants
 * - Rend nullable : pilote_id, machine_id, duree, nb_atterrissages
 * - Ajoute lieu sur formation_seances
 */

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Migration_Formation_seances_theoriques extends CI_Migration {

    public function up() {
        // Table des participants aux séances théoriques
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `formation_seances_participants` (
                `id`        INT(11)     NOT NULL AUTO_INCREMENT,
                `seance_id` INT(11)     NOT NULL,
                `pilote_id` VARCHAR(25) NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_seance_pilote` (`seance_id`, `pilote_id`),
                CONSTRAINT `fk_part_seance` FOREIGN KEY (`seance_id`)
                    REFERENCES `formation_seances`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_part_pilote` FOREIGN KEY (`pilote_id`)
                    REFERENCES `membres`(`mlogin`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8
        ");

        // Assouplissement du modèle formation_seances pour les séances théoriques
        $this->db->query("
            ALTER TABLE `formation_seances`
                MODIFY `pilote_id`        VARCHAR(25) NULL
                    COMMENT 'NULL pour séances théoriques (participants dans table dédiée)',
                MODIFY `machine_id`       VARCHAR(10) NULL
                    COMMENT 'NULL pour séances théoriques',
                MODIFY `duree`            TIME        NULL
                    COMMENT 'NULL si non renseigné',
                MODIFY `nb_atterrissages` INT(11)     NULL
                    COMMENT 'NULL pour séances théoriques'
        ");

        // Colonne lieu (salle, aérodrome, etc.)
        if (!$this->db->field_exists('lieu', 'formation_seances')) {
            $this->db->query("
                ALTER TABLE `formation_seances`
                    ADD COLUMN `lieu` VARCHAR(255) NULL AFTER `meteo`
            ");
        }
    }

    public function down() {
        // Suppression de la colonne lieu
        if ($this->db->field_exists('lieu', 'formation_seances')) {
            $this->db->query("ALTER TABLE `formation_seances` DROP COLUMN `lieu`");
        }

        // Restauration des contraintes NOT NULL (best-effort, peut échouer si données NULL existent)
        $this->db->query("
            ALTER TABLE `formation_seances`
                MODIFY `pilote_id`        VARCHAR(25) NOT NULL,
                MODIFY `machine_id`       VARCHAR(10) NOT NULL,
                MODIFY `duree`            TIME        NOT NULL,
                MODIFY `nb_atterrissages` INT(11)     NOT NULL DEFAULT 0
        ");

        // Suppression de la table participants (CASCADE supprime les FK)
        $this->db->query("DROP TABLE IF EXISTS `formation_seances_participants`");
    }
}
