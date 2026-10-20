<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 180 : alarmes, Lot 4 (désactivation des occurrences)
 *
 * - alarm_dismissals : désactivation d'une occurrence (clé AlarmItem.key)
 *   pour un membre ou pour tout le monde (member_login NULL), définitive ou
 *   jusqu'à une date, avec l'état et l'échéance de l'occurrence au moment de
 *   la désactivation (réactivation automatique s'ils changent). Les lignes
 *   réactivées sont conservées pour l'historique.
 *
 * @see doc/prds/gestion_alarmes_generiques_prd.md (exigence 13)
 * @see doc/design_notes/gestion_alarmes_design.md
 * @see doc/plans/gestion_alarmes_plan.md
 */
class Migration_Alarmes_lot4 extends CI_Migration {

    public function up() {
        $ok = (bool) $this->db->query(
            "CREATE TABLE IF NOT EXISTS `alarm_dismissals` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `alarm_definition_id` INT(11) UNSIGNED NOT NULL,
                `alarm_key` VARCHAR(191) NOT NULL COMMENT 'Clé de l''occurrence (AlarmItem.key)',
                `label` VARCHAR(255) NULL COMMENT 'Libellé de l''occurrence à la désactivation',
                `member_login` VARCHAR(25) NULL COMMENT 'Membre pour qui elle est désactivée, NULL = pour tout le monde',
                `until_date` DATE NULL COMMENT 'Désactivée jusqu''à cette date incluse, NULL = définitive',
                `reason` VARCHAR(255) NULL,
                `state_snapshot` ENUM('upcoming','active') NOT NULL,
                `due_date_snapshot` DATE NULL,
                `reactivated_at` DATETIME NULL COMMENT 'NULL = désactivation en cours',
                `reactivated_by` VARCHAR(25) NULL COMMENT 'NULL = réactivation automatique',
                `created_at` DATETIME NULL,
                `updated_at` DATETIME NULL,
                `created_by` VARCHAR(25) NULL,
                `updated_by` VARCHAR(25) NULL,
                PRIMARY KEY (`id`),
                KEY `idx_alarm_dismissals_key` (`alarm_key`),
                KEY `idx_alarm_dismissals_current` (`reactivated_at`),
                CONSTRAINT `fk_alarm_dismissals_definition` FOREIGN KEY (`alarm_definition_id`) REFERENCES `alarm_definitions` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_alarm_dismissals_login` FOREIGN KEY (`member_login`) REFERENCES `membres` (`mlogin`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Désactivations d''occurrences d''alarmes'"
        );
        if (!$ok) {
            log_message('error', 'Migration 180: ' . $this->db->_error_message());
            return FALSE;
        }
        log_message('info', 'Migration 180: alarmes lot 4 appliquée');
        return TRUE;
    }

    public function down() {
        if ($this->db->table_exists('alarm_dismissals')) {
            $this->db->query("DROP TABLE `alarm_dismissals`");
        }
        log_message('info', 'Migration 180: alarmes lot 4 retirée');
        return TRUE;
    }
}
