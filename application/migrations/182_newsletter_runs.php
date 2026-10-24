<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 182 : consignes de l'agent, exécutions et suggestions de relecture
 *
 * - newsletter_contexts : versions successives des consignes de l'agent IA
 * - newsletter_runs     : exécutions de l'agent (bureau, calendrier, lancement manuel)
 * - newsletter_reviews  : devient la table des suggestions (nature, décision,
 *                         anti-doublon par Message-ID, report à une édition suivante)
 * - newsletters         : date de transmission aux relecteurs et date limite de réponse
 *
 * @see doc/prds/PRD_Veille_Securite_Automatisee.md
 * @see doc/design_notes/veille_documentaire_design.md
 * @see doc/plans/veille_documentaire_plan.md (P2.1)
 */
class Migration_Newsletter_runs extends CI_Migration {

    public function up() {
        $ok = (bool) $this->db->query(
            "CREATE TABLE IF NOT EXISTS `newsletter_contexts` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `version` INT(11) UNSIGNED NOT NULL,
                `content` MEDIUMTEXT NOT NULL,
                `comment` VARCHAR(255) NULL,
                `is_active` TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` DATETIME NULL,
                `updated_at` DATETIME NULL,
                `created_by` VARCHAR(25) NULL,
                `updated_by` VARCHAR(25) NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_newsletter_contexts_version` (`version`),
                KEY `idx_newsletter_contexts_active` (`is_active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Versions des consignes de l''agent de veille'"
        );
        if (!$ok) {
            log_message('error', 'Migration 182: création de newsletter_contexts échouée — ' . $this->db->_error_message());
            return FALSE;
        }

        $ok = (bool) $this->db->query(
            "CREATE TABLE IF NOT EXISTS `newsletter_runs` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `run_id` VARCHAR(40) NOT NULL,
                `origin` ENUM('desktop','schedule','manual') NOT NULL,
                `status` ENUM('requested','running','succeeded','failed') NOT NULL DEFAULT 'requested',
                `context_id` INT(11) UNSIGNED NULL,
                `newsletter_id` INT(11) UNSIGNED NULL,
                `started_at` DATETIME NULL,
                `finished_at` DATETIME NULL,
                `model` VARCHAR(100) NULL,
                `cost_usd` DECIMAL(8,2) NULL,
                `package_path` VARCHAR(500) NULL,
                `error` TEXT NULL,
                `created_at` DATETIME NULL,
                `updated_at` DATETIME NULL,
                `created_by` VARCHAR(25) NULL,
                `updated_by` VARCHAR(25) NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_newsletter_runs_run_id` (`run_id`),
                KEY `idx_newsletter_runs_status` (`status`),
                CONSTRAINT `fk_newsletter_runs_context` FOREIGN KEY (`context_id`) REFERENCES `newsletter_contexts` (`id`) ON DELETE SET NULL,
                CONSTRAINT `fk_newsletter_runs_newsletter` FOREIGN KEY (`newsletter_id`) REFERENCES `newsletters` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Exécutions de l''agent de veille'"
        );
        if (!$ok) {
            log_message('error', 'Migration 182: création de newsletter_runs échouée — ' . $this->db->_error_message());
            return FALSE;
        }

        $ok = (bool) $this->db->query(
            "ALTER TABLE `newsletter_reviews`
                MODIFY `newsletter_id` INT(11) UNSIGNED NULL,
                ADD `message_id` VARCHAR(255) NULL AFTER `submitted_via`,
                ADD `kind` ENUM('remarque','article') NOT NULL DEFAULT 'remarque' AFTER `reviewer_name`,
                ADD `decision` ENUM('en_attente','acceptee','modifiee','rejetee','reportee') NOT NULL DEFAULT 'en_attente' AFTER `content`,
                ADD `admin_text` TEXT NULL AFTER `decision`,
                ADD `decided_by` VARCHAR(25) NULL AFTER `admin_text`,
                ADD `decided_at` DATETIME NULL AFTER `decided_by`,
                ADD UNIQUE KEY `uk_newsletter_reviews_message_id` (`message_id`),
                ADD KEY `idx_newsletter_reviews_decision` (`decision`)"
        );
        if (!$ok) {
            log_message('error', 'Migration 182: modification de newsletter_reviews échouée — ' . $this->db->_error_message());
            return FALSE;
        }

        $ok = (bool) $this->db->query(
            "ALTER TABLE `newsletters`
                ADD `review_sent_at` DATETIME NULL COMMENT 'Dernière transmission aux relecteurs' AFTER `pdf_path`,
                ADD `review_deadline` DATE NULL COMMENT 'Date limite de réponse des relecteurs' AFTER `review_sent_at`"
        );
        if (!$ok) {
            log_message('error', 'Migration 182: modification de newsletters échouée — ' . $this->db->_error_message());
            return FALSE;
        }

        log_message('info', 'Migration 182: newsletter_runs appliquée');
        return TRUE;
    }

    public function down() {
        if ($this->db->field_exists('review_sent_at', 'newsletters')) {
            $this->db->query("ALTER TABLE `newsletters` DROP `review_sent_at`, DROP `review_deadline`");
        }
        if ($this->db->field_exists('decision', 'newsletter_reviews')) {
            // Les suggestions reportées n'ont pas d'édition : elles n'existent pas dans le schéma 181
            $this->db->query("DELETE FROM `newsletter_reviews` WHERE `newsletter_id` IS NULL");
            $this->db->query(
                "ALTER TABLE `newsletter_reviews`
                    DROP INDEX `uk_newsletter_reviews_message_id`,
                    DROP INDEX `idx_newsletter_reviews_decision`,
                    DROP `message_id`, DROP `kind`, DROP `decision`,
                    DROP `admin_text`, DROP `decided_by`, DROP `decided_at`,
                    MODIFY `newsletter_id` INT(11) UNSIGNED NOT NULL"
            );
        }
        foreach (array('newsletter_runs', 'newsletter_contexts') as $table) {
            if ($this->db->table_exists($table)) {
                $this->db->query("DROP TABLE `$table`");
            }
        }
        log_message('info', 'Migration 182: newsletter_runs retirée');
        return TRUE;
    }
}
