<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 186 : synchronisation des réservations avec Google Agenda
 *
 * - agenda_google_liaisons  : liaison d'un membre avec son agenda Google
 *                             (état, compte affiché, jeton de renouvellement chiffré)
 * - agenda_google_evenements : événements créés par GVV, un par couple (liaison, réservation)
 * - agenda_google_file      : réservations à réconcilier avec les agendas
 * - agenda_google_bouchon   : état du bouchon qui remplace Google sur une plateforme
 *                             de test (codes, jetons, événements) ; vide en production
 *
 * @see doc/prds/synchronisation_agenda_google_reservations_prd.md
 * @see doc/design_notes/synchronisation_agenda_google_reservations_design.md
 */
class Migration_Agenda_google extends CI_Migration {

    private $tables = array('agenda_google_bouchon', 'agenda_google_file', 'agenda_google_evenements', 'agenda_google_liaisons');

    public function up() {
        $audit = "`created_at` DATETIME NULL,
                `updated_at` DATETIME NULL,
                `created_by` VARCHAR(50) NULL,
                `updated_by` VARCHAR(50) NULL,";

        $sqls = array(
            "CREATE TABLE IF NOT EXISTS `agenda_google_liaisons` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `mlogin` VARCHAR(25) NOT NULL COMMENT 'Membre propriétaire de la liaison',
                `compte_google` VARCHAR(255) NULL COMMENT 'Adresse du compte Google, pour affichage',
                `jeton_renouvellement` TEXT NULL COMMENT 'Jeton de renouvellement chiffré ; effacé à l''arrêt',
                `etat` ENUM('active','suspendue','arretee') NOT NULL DEFAULT 'active',
                `cause_suspension` VARCHAR(255) NULL,
                `echecs_consecutifs` INT(11) NOT NULL DEFAULT 0,
                `derniere_reussite_at` DATETIME NULL,
                $audit
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_agenda_google_liaisons_mlogin` (`mlogin`),
                KEY `idx_agenda_google_liaisons_etat` (`etat`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Liaisons des membres avec leur agenda Google'",

            "CREATE TABLE IF NOT EXISTS `agenda_google_evenements` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `liaison_id` INT(11) UNSIGNED NOT NULL,
                `reservation_id` BIGINT(20) UNSIGNED NOT NULL,
                `google_event_id` VARCHAR(255) NOT NULL,
                `empreinte` CHAR(64) NOT NULL COMMENT 'Empreinte du dernier contenu envoyé',
                $audit
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_agenda_google_evenements` (`liaison_id`, `reservation_id`),
                KEY `idx_agenda_google_evenements_reservation` (`reservation_id`),
                CONSTRAINT `fk_agenda_google_evenements_liaison` FOREIGN KEY (`liaison_id`)
                    REFERENCES `agenda_google_liaisons` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Événements Google créés par GVV'",

            "CREATE TABLE IF NOT EXISTS `agenda_google_file` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `reservation_id` BIGINT(20) UNSIGNED NOT NULL,
                `version` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Incrémentée à chaque nouvelle inscription',
                `tentatives` INT(11) NOT NULL DEFAULT 0,
                `prochain_essai_at` DATETIME NULL,
                `derniere_erreur` VARCHAR(255) NULL,
                $audit
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_agenda_google_file_reservation` (`reservation_id`),
                KEY `idx_agenda_google_file_essai` (`prochain_essai_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Réservations à réconcilier avec les agendas Google'",

            "CREATE TABLE IF NOT EXISTS `agenda_google_bouchon` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `type` ENUM('code','jeton','evenement') NOT NULL,
                `cle` VARCHAR(255) NOT NULL,
                `compte` VARCHAR(255) NULL,
                `contenu` MEDIUMTEXT NULL,
                `revoque` TINYINT(1) NOT NULL DEFAULT 0,
                $audit
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_agenda_google_bouchon` (`type`, `cle`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Bouchon Google des plateformes de test'",
        );

        foreach ($sqls as $sql) {
            if (!$this->db->query($sql)) {
                log_message('error', 'Migration 186: ' . $this->db->_error_message());
                return FALSE;
            }
        }
        return TRUE;
    }

    public function down() {
        foreach ($this->tables as $table) {
            if (!$this->db->query("DROP TABLE IF EXISTS `$table`")) {
                return FALSE;
            }
        }
        return TRUE;
    }
}
