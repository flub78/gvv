<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 182 : cycle de relecture de la veille
 *
 * - newsletter_annotations : commentaires de l'administrateur insérés dans une
 *                            version du brouillon
 * - newsletter_reviews     : devient la table des remarques des relecteurs
 *                            (décision, anti-doublon par Message-ID, report à
 *                            une édition suivante)
 * - newsletters            : date de transmission aux relecteurs et date limite
 *
 * @see doc/prds/PRD_Veille_Securite_Automatisee.md
 * @see doc/design_notes/veille_documentaire_design.md
 * @see doc/plans/veille_documentaire_plan.md (P2.2)
 */
class Migration_Newsletter_review_cycle extends CI_Migration {

    public function up() {
        $ok = (bool) $this->db->query(
            "CREATE TABLE IF NOT EXISTS `newsletter_annotations` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `newsletter_id` INT(11) UNSIGNED NOT NULL,
                `version` INT(11) UNSIGNED NOT NULL,
                `block_index` INT(11) UNSIGNED NOT NULL,
                `anchor_text` VARCHAR(255) NULL COMMENT 'Début du bloc annoté',
                `label` VARCHAR(100) NOT NULL,
                `content` TEXT NOT NULL,
                `created_at` DATETIME NULL,
                `updated_at` DATETIME NULL,
                `created_by` VARCHAR(25) NULL,
                `updated_by` VARCHAR(25) NULL,
                PRIMARY KEY (`id`),
                KEY `idx_newsletter_annotations_version` (`newsletter_id`, `version`),
                CONSTRAINT `fk_newsletter_annotations_newsletter` FOREIGN KEY (`newsletter_id`) REFERENCES `newsletters` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Commentaires de l''administrateur sur un brouillon de veille'"
        );
        if (!$ok) {
            log_message('error', 'Migration 182: création de newsletter_annotations échouée — ' . $this->db->_error_message());
            return FALSE;
        }

        $ok = (bool) $this->db->query(
            "ALTER TABLE `newsletter_reviews`
                MODIFY `newsletter_id` INT(11) UNSIGNED NULL,
                ADD `message_id` VARCHAR(255) NULL AFTER `submitted_via`,
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

        log_message('info', 'Migration 182: newsletter_review_cycle appliquée');
        return TRUE;
    }

    public function down() {
        if ($this->db->field_exists('review_sent_at', 'newsletters')) {
            $this->db->query("ALTER TABLE `newsletters` DROP `review_sent_at`, DROP `review_deadline`");
        }
        if ($this->db->field_exists('decision', 'newsletter_reviews')) {
            // Les remarques reportées n'ont pas d'édition : elles n'existent pas dans le schéma 181
            $this->db->query("DELETE FROM `newsletter_reviews` WHERE `newsletter_id` IS NULL");
            $this->db->query(
                "ALTER TABLE `newsletter_reviews`
                    DROP INDEX `uk_newsletter_reviews_message_id`,
                    DROP INDEX `idx_newsletter_reviews_decision`,
                    DROP `message_id`, DROP `decision`,
                    DROP `admin_text`, DROP `decided_by`, DROP `decided_at`,
                    MODIFY `newsletter_id` INT(11) UNSIGNED NOT NULL"
            );
        }
        if ($this->db->table_exists('newsletter_annotations')) {
            $this->db->query("DROP TABLE `newsletter_annotations`");
        }
        log_message('info', 'Migration 182: newsletter_review_cycle retirée');
        return TRUE;
    }
}
