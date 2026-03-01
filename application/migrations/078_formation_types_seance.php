<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 078: Add formation session types
 *
 * Creates the formation_types_seance table for categorizing training sessions
 * (in-flight vs. ground/theoretical), and adds a type_seance_id reference
 * on formation_seances.
 *
 * @see doc/prds/gestion_des_seances_theoriques.md
 * @see doc/plans/seances_theoriques_plan.md Phase 1
 */
class Migration_Formation_types_seance extends CI_Migration
{
    public function up()
    {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `formation_types_seance` (
                `id`                    INT(11)                 NOT NULL AUTO_INCREMENT,
                `nom`                   VARCHAR(100)            NOT NULL COMMENT 'Libellé court du type de séance',
                `nature`                ENUM('vol','theorique') NOT NULL COMMENT 'vol = séance en vol, theorique = cours au sol',
                `description`           TEXT                    NULL     COMMENT 'Description détaillée',
                `periodicite_max_jours` INT(11)                 NULL     COMMENT 'Délai max en jours entre deux séances de ce type pour un même élève (NULL = sans contrainte)',
                `actif`                 TINYINT(1)              NOT NULL DEFAULT 1 COMMENT '1 = utilisable lors de la saisie',
                PRIMARY KEY (`id`),
                KEY `idx_nature` (`nature`),
                KEY `idx_actif`  (`actif`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
            COMMENT='Types de séances de formation (vol ou sol)'"
        );

        // Default session types
        $defaults = array(
            array('nom' => "Vol biplace d'instruction", 'nature' => 'vol',       'periodicite_max_jours' => null, 'actif' => 1),
            array('nom' => "Vol solo supervisé",        'nature' => 'vol',       'periodicite_max_jours' => null, 'actif' => 1),
            array('nom' => "Cours sol – Général",       'nature' => 'theorique', 'periodicite_max_jours' => 365,  'actif' => 1),
            array('nom' => "Briefing de groupe",        'nature' => 'theorique', 'periodicite_max_jours' => null, 'actif' => 1),
        );
        foreach ($defaults as $row) {
            $this->db->insert('formation_types_seance', $row);
        }

        // Add type_seance_id reference on formation_seances
        $this->db->query(
            "ALTER TABLE `formation_seances`
             ADD COLUMN `type_seance_id` INT(11) NULL
             COMMENT 'Type de séance (formation_types_seance.id)'
             AFTER `id`"
        );
        $this->db->query(
            "ALTER TABLE `formation_seances`
             ADD CONSTRAINT `fk_seance_type`
             FOREIGN KEY (`type_seance_id`)
             REFERENCES `formation_types_seance`(`id`)
             ON DELETE SET NULL ON UPDATE CASCADE"
        );

        log_message('info', 'Migration 078: formation_types_seance created, type_seance_id added to formation_seances');
    }

    public function down()
    {
        $this->db->query("ALTER TABLE `formation_seances` DROP FOREIGN KEY `fk_seance_type`");
        $this->db->query("ALTER TABLE `formation_seances` DROP COLUMN `type_seance_id`");
        $this->db->query("DROP TABLE IF EXISTS `formation_types_seance`");

        log_message('info', 'Migration 078: formation_types_seance dropped');
    }
}

/* End of file 078_formation_types_seance.php */
/* Location: ./application/migrations/078_formation_types_seance.php */
