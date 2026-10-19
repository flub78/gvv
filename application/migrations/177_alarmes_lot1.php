<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 177 : alarmes, Lot 1 (alarmes sur les documents archivés)
 *
 * - alarm_definitions : catalogue des alarmes proposées aux membres
 * - alarm_subscriptions : abonnements individuels ou par catégorie (rôle), par
 *   alarme et par section (colonne de la matrice ; NULL = Club)
 * - alarm_subscription_optouts : cases retirées par un membre d'un abonnement de catégorie
 * - alarm_member_settings : canaux, rappel SMS et fréquence de chaque membre
 * - notification_history : un enregistrement par envoi
 * - rôle « eleve » et document_types.required_role_id (documents obligatoires
 *   pour un rôle) ; la visite médicale devient obligatoire pour les élèves
 *   seulement. down() supprime la restriction : la visite médicale reste
 *   alors obligatoire pour tous les pilotes.
 * - document_types.alarm_enabled (alarmes possibles sur le type), à 1 pour
 *   tous les types existants
 * - catalogue initial : une alarme par type de document actif, à expiration
 *   et à alarmes possibles, une pour les documents obligatoires manquants,
 *   une pour les visites médicales des élèves. Aucun abonnement n'est créé.
 *
 * @see doc/prds/gestion_alarmes_generiques_prd.md
 * @see doc/design_notes/gestion_alarmes_design.md
 * @see doc/plans/gestion_alarmes_plan.md
 */
class Migration_Alarmes_lot1 extends CI_Migration {

    const ROLE_ELEVE = 'eleve';

    public function up() {
        $ok = TRUE;

        $ok = $ok && $this->_query(
            "CREATE TABLE IF NOT EXISTS `alarm_definitions` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `code` VARCHAR(64) NOT NULL COMMENT 'Identifiant stable de l''alarme',
                `label` VARCHAR(128) NOT NULL COMMENT 'Libellé de référence (affichage traduit par le modèle)',
                `description` TEXT NULL,
                `source` ENUM('document','missing_document','computed') NOT NULL,
                `document_type_id` INT(11) UNSIGNED NULL COMMENT 'FK document_types (source document)',
                `rule` VARCHAR(64) NULL COMMENT 'Règle calculée (source computed)',
                `section_id` INT(11) NULL COMMENT 'FK sections, NULL = toutes sections',
                `target_role_id` INT(11) NULL COMMENT 'Seulement les membres ayant ce rôle (FK types_roles), NULL = tous',
                `lead_days` INT(11) NOT NULL DEFAULT 30 COMMENT 'Délai d''anticipation (jours)',
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME NULL,
                `updated_at` DATETIME NULL,
                `created_by` VARCHAR(25) NULL,
                `updated_by` VARCHAR(25) NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_alarm_definitions_code` (`code`),
                KEY `idx_alarm_definitions_type` (`document_type_id`),
                CONSTRAINT `fk_alarm_definitions_type` FOREIGN KEY (`document_type_id`) REFERENCES `document_types` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_alarm_definitions_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_alarm_definitions_target_role` FOREIGN KEY (`target_role_id`) REFERENCES `types_roles` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Catalogue des alarmes'"
        );

        $ok = $ok && $this->_query(
            "CREATE TABLE IF NOT EXISTS `alarm_subscriptions` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `alarm_definition_id` INT(11) UNSIGNED NOT NULL,
                `subscriber_login` VARCHAR(25) NULL COMMENT 'Abonné individuel',
                `role_id` INT(11) NULL COMMENT 'Catégorie : FK types_roles',
                `section_id` INT(11) NULL COMMENT 'Colonne : section ; NULL = Club (individuel), sections du rôle + Club (catégorie)',
                `mandatory` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Non désabonnable par le membre',
                `created_at` DATETIME NULL,
                `updated_at` DATETIME NULL,
                `created_by` VARCHAR(25) NULL,
                `updated_by` VARCHAR(25) NULL,
                PRIMARY KEY (`id`),
                KEY `idx_alarm_subscriptions_def` (`alarm_definition_id`),
                KEY `idx_alarm_subscriptions_login` (`subscriber_login`),
                KEY `idx_alarm_subscriptions_role` (`role_id`, `section_id`),
                CONSTRAINT `fk_alarm_subscriptions_def` FOREIGN KEY (`alarm_definition_id`) REFERENCES `alarm_definitions` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_alarm_subscriptions_login` FOREIGN KEY (`subscriber_login`) REFERENCES `membres` (`mlogin`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_alarm_subscriptions_role` FOREIGN KEY (`role_id`) REFERENCES `types_roles` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_alarm_subscriptions_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Abonnements aux alarmes : un abonné ou une catégorie, par section'"
        );

        $ok = $ok && $this->_query(
            "CREATE TABLE IF NOT EXISTS `alarm_subscription_optouts` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `subscription_id` INT(11) UNSIGNED NOT NULL,
                `member_login` VARCHAR(25) NOT NULL,
                `section_id` INT(11) NULL COMMENT 'Case retirée : section, NULL = Club',
                `created_at` DATETIME NULL,
                `updated_at` DATETIME NULL,
                `created_by` VARCHAR(25) NULL,
                `updated_by` VARCHAR(25) NULL,
                PRIMARY KEY (`id`),
                KEY `idx_alarm_optouts` (`subscription_id`, `member_login`),
                CONSTRAINT `fk_alarm_optouts_subscription` FOREIGN KEY (`subscription_id`) REFERENCES `alarm_subscriptions` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_alarm_optouts_login` FOREIGN KEY (`member_login`) REFERENCES `membres` (`mlogin`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_alarm_optouts_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Cases retirées d''un abonnement de catégorie'"
        );

        $ok = $ok && $this->_query(
            "CREATE TABLE IF NOT EXISTS `alarm_member_settings` (
                `member_login` VARCHAR(25) NOT NULL,
                `channel_email` TINYINT(1) NOT NULL DEFAULT 0,
                `channel_motd` TINYINT(1) NOT NULL DEFAULT 0,
                `sms` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Rappel SMS le jour de synthèse',
                `frequency` ENUM('immediate','weekly','monthly') NOT NULL DEFAULT 'weekly',
                `created_at` DATETIME NULL,
                `updated_at` DATETIME NULL,
                `created_by` VARCHAR(25) NULL,
                `updated_by` VARCHAR(25) NULL,
                PRIMARY KEY (`member_login`),
                CONSTRAINT `fk_alarm_settings_login` FOREIGN KEY (`member_login`) REFERENCES `membres` (`mlogin`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Réglages des alarmes par membre'"
        );

        $ok = $ok && $this->_query(
            "CREATE TABLE IF NOT EXISTS `notification_history` (
                `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `recipient_login` VARCHAR(25) NOT NULL,
                `alarm_definition_id` INT(11) UNSIGNED NULL,
                `occurrence_key` VARCHAR(191) NULL,
                `occurrence_state` ENUM('upcoming','active') NULL,
                `channel` ENUM('email','motd','sms') NOT NULL,
                `kind` ENUM('trigger','digest') NOT NULL,
                `period_key` VARCHAR(32) NULL COMMENT 'Ex. 2026-W43, 2026-11',
                `status` ENUM('sent','failed') NOT NULL,
                `error` TEXT NULL,
                `sent_at` DATETIME NOT NULL,
                `created_at` DATETIME NULL,
                `updated_at` DATETIME NULL,
                `created_by` VARCHAR(25) NULL,
                `updated_by` VARCHAR(25) NULL,
                PRIMARY KEY (`id`),
                KEY `idx_notification_history_period` (`recipient_login`, `channel`, `kind`, `period_key`),
                KEY `idx_notification_history_occurrence` (`recipient_login`, `occurrence_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Historique des notifications d''alarmes'"
        );

        // Rôle élève
        if ($ok && $this->db->where('nom', self::ROLE_ELEVE)->count_all_results('types_roles') == 0) {
            $ok = (bool) $this->db->insert('types_roles', array(
                'nom'             => self::ROLE_ELEVE,
                'description'     => 'Élève (formation en cours)',
                'scope'           => 'section',
                'is_system_role'  => 1,
                'display_order'   => 99,
                'translation_key' => 'role_eleve',
            ));
        }

        // Documents obligatoires pour un rôle
        if ($ok && !$this->db->field_exists('required_role_id', 'document_types')) {
            $ok = $this->_query(
                "ALTER TABLE `document_types`
                    ADD COLUMN `required_role_id` INT(11) NULL COMMENT 'Obligatoire seulement pour le rôle (vide = tous les pilotes)' AFTER `required`,
                    ADD CONSTRAINT `fk_document_types_required_role` FOREIGN KEY (`required_role_id`) REFERENCES `types_roles` (`id`) ON DELETE SET NULL"
            );
        }

        // Alarmes possibles sur le type de document (tous les types existants)
        if ($ok && !$this->db->field_exists('alarm_enabled', 'document_types')) {
            $ok = $this->_query(
                "ALTER TABLE `document_types`
                    ADD COLUMN `alarm_enabled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Alarmes possibles sur ce type' AFTER `alert_days_before`"
            );
        }

        // Visite médicale obligatoire pour les élèves seulement
        if ($ok) {
            $eleve_id = $this->_eleve_role_id();
            $ok = $this->_query(
                "UPDATE `document_types` SET `required` = 1, `required_role_id` = " . (int) $eleve_id . "
                 WHERE `code` = 'medical' AND `scope` = 'pilot' AND `required_role_id` IS NULL"
            );
        }

        if ($ok) {
            $ok = $this->_seed_catalogue();
        }

        log_message('info', 'Migration 177: alarmes lot 1 ' . ($ok ? 'appliquée' : 'en échec'));
        return $ok;
    }

    public function down() {
        $ok = TRUE;

        foreach (array('notification_history', 'alarm_member_settings', 'alarm_subscription_optouts',
                       'alarm_subscriptions', 'alarm_definitions') as $table) {
            if ($this->db->table_exists($table)) {
                $ok = $ok && $this->_query("DROP TABLE `$table`");
            }
        }

        if ($ok && $this->db->field_exists('alarm_enabled', 'document_types')) {
            $ok = $this->_query("ALTER TABLE `document_types` DROP COLUMN `alarm_enabled`");
        }

        if ($ok && $this->db->field_exists('required_role_id', 'document_types')) {
            $ok = $this->_query(
                "ALTER TABLE `document_types`
                    DROP FOREIGN KEY `fk_document_types_required_role`,
                    DROP COLUMN `required_role_id`"
            );
        }

        if ($ok) {
            $eleve_id = $this->_eleve_role_id();
            if ($eleve_id) {
                $this->db->where('types_roles_id', $eleve_id)->delete('user_roles_per_section');
                $this->db->where('id', $eleve_id)->delete('types_roles');
            }
        }

        log_message('info', 'Migration 177: alarmes lot 1 ' . ($ok ? 'retirée' : 'retrait en échec'));
        return $ok;
    }

    /**
     * Catalogue initial : une alarme par type de document actif à expiration,
     * la visite médicale des élèves, les documents obligatoires manquants.
     * Un abonné reçoit ses propres documents et ceux qu'il peut superviser.
     */
    private function _seed_catalogue() {
        $now = date('Y-m-d H:i:s');
        $types = $this->db->where('active', 1)->where('has_expiration', 1)->where('alarm_enabled', 1)
            ->order_by('display_order')->order_by('id')->get('document_types')->result_array();

        $rows = array();
        foreach ($types as $type) {
            $rows[] = array(
                'code'             => 'document_' . $type['id'],
                'label'            => $type['name'],
                'source'           => 'document',
                'document_type_id' => $type['id'],
                'section_id'       => $type['section_id'],
                'lead_days'        => ($type['alert_days_before'] !== NULL) ? (int) $type['alert_days_before'] : 30,
                'active'           => 1,
                'created_at'       => $now,
                'created_by'       => 'migration',
            );
        }
        $medical = $this->db->where('code', 'medical')->where('scope', 'pilot')
            ->where('active', 1)->where('has_expiration', 1)->where('alarm_enabled', 1)
            ->get('document_types')->row_array();
        if ($medical) {
            $rows[] = array(
                'code'             => 'document_' . $medical['id'] . '_eleve',
                'label'            => $medical['name'] . ' — élèves',
                'source'           => 'document',
                'document_type_id' => $medical['id'],
                'target_role_id'   => $this->_eleve_role_id(),
                'lead_days'        => ($medical['alert_days_before'] !== NULL) ? (int) $medical['alert_days_before'] : 30,
                'active'           => 1,
                'created_at'       => $now,
                'created_by'       => 'migration',
            );
        }
        $rows[] = array(
            'code'       => 'missing_document',
            'label'      => 'Documents obligatoires manquants',
            'source'     => 'missing_document',
            'lead_days'  => 0,
            'active'     => 1,
            'created_at' => $now,
            'created_by' => 'migration',
        );

        foreach ($rows as $row) {
            if ($this->db->where('code', $row['code'])->count_all_results('alarm_definitions') == 0) {
                if (!$this->db->insert('alarm_definitions', $row)) {
                    return FALSE;
                }
            }
        }
        return TRUE;
    }

    private function _eleve_role_id() {
        $row = $this->db->select('id')->where('nom', self::ROLE_ELEVE)->get('types_roles')->row_array();
        return $row ? (int) $row['id'] : 0;
    }

    private function _query($sql) {
        $ok = (bool) $this->db->query($sql);
        if (!$ok) {
            log_message('error', 'Migration 177: ' . $this->db->_error_message());
        }
        return $ok;
    }
}
