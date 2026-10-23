<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 181 : newsletter de veille documentaire
 *
 * - newsletters         : éditions (brouillon → relecture → validation → publié)
 * - newsletter_reviews  : retours des relecteurs et validateurs
 * - newsletter_subscribers : abonnés externes (opt-in)
 * - newsletter_sendings : journal des envois par abonné
 *
 * @see doc/prds/PRD_Veille_Securite_Automatisee.md
 * @see doc/design_notes/veille_documentaire_design.md
 * @see doc/plans/veille_documentaire_plan.md
 */
class Migration_Newsletter extends CI_Migration {

    public function up() {
        $ok = (bool) $this->db->query(
            "CREATE TABLE IF NOT EXISTS `newsletters` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `title` VARCHAR(255) NOT NULL,
                `period_start` DATE NOT NULL,
                `period_end` DATE NOT NULL,
                `status` ENUM('draft','reviewing','validating','approved','published') NOT NULL DEFAULT 'draft',
                `review_iteration` INT(11) UNSIGNED NOT NULL DEFAULT 0,
                `draft_path` VARCHAR(500) NULL COMMENT 'Chemin du fichier Markdown de travail',
                `pdf_path` VARCHAR(500) NULL COMMENT 'Chemin du PDF publié',
                `approved_by` VARCHAR(25) NULL,
                `approved_at` DATETIME NULL,
                `published_at` DATETIME NULL,
                `created_at` DATETIME NULL,
                `updated_at` DATETIME NULL,
                `created_by` VARCHAR(25) NULL,
                `updated_by` VARCHAR(25) NULL,
                PRIMARY KEY (`id`),
                KEY `idx_newsletters_status` (`status`),
                KEY `idx_newsletters_period` (`period_start`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Éditions de la newsletter de veille'"
        );
        if (!$ok) {
            log_message('error', 'Migration 181: création de newsletters échouée — ' . $this->db->_error_message());
            return FALSE;
        }

        $ok = (bool) $this->db->query(
            "CREATE TABLE IF NOT EXISTS `newsletter_reviews` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `newsletter_id` INT(11) UNSIGNED NOT NULL,
                `iteration` INT(11) UNSIGNED NOT NULL DEFAULT 0,
                `submitted_via` ENUM('email','gvv') NOT NULL DEFAULT 'gvv',
                `reviewer_email` VARCHAR(255) NOT NULL,
                `reviewer_name` VARCHAR(255) NULL,
                `content` TEXT NOT NULL,
                `has_attachments` TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` DATETIME NULL,
                `updated_at` DATETIME NULL,
                `created_by` VARCHAR(25) NULL,
                `updated_by` VARCHAR(25) NULL,
                PRIMARY KEY (`id`),
                KEY `idx_newsletter_reviews_newsletter` (`newsletter_id`),
                CONSTRAINT `fk_newsletter_reviews_newsletter` FOREIGN KEY (`newsletter_id`) REFERENCES `newsletters` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Retours des relecteurs et validateurs'"
        );
        if (!$ok) {
            log_message('error', 'Migration 181: création de newsletter_reviews échouée — ' . $this->db->_error_message());
            return FALSE;
        }

        $ok = (bool) $this->db->query(
            "CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `email` VARCHAR(255) NOT NULL,
                `first_name` VARCHAR(100) NOT NULL,
                `last_name` VARCHAR(100) NOT NULL,
                `confirmed` TINYINT(1) NOT NULL DEFAULT 0,
                `confirmation_token` VARCHAR(64) NULL,
                `unsubscribe_token` VARCHAR(64) NOT NULL,
                `confirmed_at` DATETIME NULL,
                `unsubscribed_at` DATETIME NULL,
                `created_at` DATETIME NULL,
                `updated_at` DATETIME NULL,
                `created_by` VARCHAR(25) NULL,
                `updated_by` VARCHAR(25) NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_newsletter_subscribers_email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Abonnés externes à la newsletter'"
        );
        if (!$ok) {
            log_message('error', 'Migration 181: création de newsletter_subscribers échouée — ' . $this->db->_error_message());
            return FALSE;
        }

        $ok = (bool) $this->db->query(
            "CREATE TABLE IF NOT EXISTS `newsletter_sendings` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `newsletter_id` INT(11) UNSIGNED NOT NULL,
                `subscriber_id` INT(11) UNSIGNED NOT NULL,
                `sent_at` DATETIME NOT NULL,
                `created_at` DATETIME NULL,
                `updated_at` DATETIME NULL,
                `created_by` VARCHAR(25) NULL,
                `updated_by` VARCHAR(25) NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_newsletter_sendings` (`newsletter_id`, `subscriber_id`),
                CONSTRAINT `fk_newsletter_sendings_newsletter` FOREIGN KEY (`newsletter_id`) REFERENCES `newsletters` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_newsletter_sendings_subscriber` FOREIGN KEY (`subscriber_id`) REFERENCES `newsletter_subscribers` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Journal des envois aux abonnés'"
        );
        if (!$ok) {
            log_message('error', 'Migration 181: création de newsletter_sendings échouée — ' . $this->db->_error_message());
            return FALSE;
        }

        log_message('info', 'Migration 181: newsletter appliquée');
        return TRUE;
    }

    public function down() {
        foreach (array('newsletter_sendings', 'newsletter_reviews', 'newsletter_subscribers', 'newsletters') as $table) {
            if ($this->db->table_exists($table)) {
                $this->db->query("DROP TABLE `$table`");
            }
        }
        log_message('info', 'Migration 181: newsletter retirée');
        return TRUE;
    }
}
