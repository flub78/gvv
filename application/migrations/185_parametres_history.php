<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 185 : historique des modifications de la page Configuration
 *
 * Chaque modification d'un paramètre y est tracée : qui, quand, quel onglet,
 * quel paramètre, ancienne et nouvelle valeur. Pour un secret, seul le fait
 * qu'il a été modifié est conservé, jamais sa valeur.
 *
 * @see doc/prds/configuration_unifiee_prd.md (EX-HIS-01 à 03)
 * @see doc/design_notes/configuration_unifiee_design.md
 */
class Migration_Parametres_history extends CI_Migration {

    public function up() {
        $ok = (bool) $this->db->query(
            "CREATE TABLE IF NOT EXISTS `parametres_history` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `cle` VARCHAR(100) NOT NULL,
                `onglet` VARCHAR(50) NOT NULL,
                `section` INT(11) NULL,
                `lang` VARCHAR(20) NULL,
                `ancienne_valeur` MEDIUMTEXT NULL,
                `nouvelle_valeur` MEDIUMTEXT NULL,
                `secret` TINYINT(1) NOT NULL DEFAULT 0,
                `action` VARCHAR(20) NOT NULL DEFAULT 'modification',
                `created_at` DATETIME NULL,
                `updated_at` DATETIME NULL,
                `created_by` VARCHAR(50) NULL,
                `updated_by` VARCHAR(50) NULL,
                PRIMARY KEY (`id`),
                KEY `idx_parametres_history_onglet` (`onglet`, `created_at`),
                KEY `idx_parametres_history_cle` (`cle`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Historique des modifications de la page Configuration'"
        );
        if (!$ok) {
            log_message('error', 'Migration 185: création de parametres_history échouée — ' . $this->db->_error_message());
            return FALSE;
        }
        return TRUE;
    }

    public function down() {
        return (bool) $this->db->query("DROP TABLE IF EXISTS `parametres_history`");
    }
}
