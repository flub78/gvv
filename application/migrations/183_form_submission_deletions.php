<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 183 : trace des suppressions de briefings sous délai de conservation
 *
 * Un briefing passager doit être conservé trois mois. Un administrateur peut
 * retirer une erreur avant l'échéance, en donnant un motif : cette table conserve
 * ce motif après la disparition de la réponse, donc sans clé étrangère vers elle.
 *
 * @see doc/prds/briefing_passager_prd.md
 * @see doc/design_notes/remplissage_formulaires_design.md
 */
class Migration_Form_submission_deletions extends CI_Migration {

    public function up() {
        $ok = (bool) $this->db->query(
            "CREATE TABLE IF NOT EXISTS `form_submission_deletions` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `form_id` INT(11) NOT NULL,
                `submission_id` INT(11) NOT NULL,
                `subject_type` VARCHAR(50) NULL,
                `subject_id` INT(11) NULL,
                `submitted_at` DATETIME NULL,
                `protected_until` DATE NOT NULL,
                `reason` TEXT NOT NULL,
                `created_at` DATETIME NULL,
                `updated_at` DATETIME NULL,
                `created_by` VARCHAR(50) NULL,
                `updated_by` VARCHAR(50) NULL,
                PRIMARY KEY (`id`),
                KEY `idx_form_submission_deletions_subject` (`subject_type`, `subject_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Suppressions de réponses avant la fin de leur conservation'"
        );
        if (!$ok) {
            log_message('error', 'Migration 183: création de form_submission_deletions échouée — ' . $this->db->_error_message());
            return FALSE;
        }
        return TRUE;
    }

    public function down() {
        return (bool) $this->db->query("DROP TABLE IF EXISTS `form_submission_deletions`");
    }
}
