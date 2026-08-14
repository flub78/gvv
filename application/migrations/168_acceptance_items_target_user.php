<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 168: acceptance_items.target_user_login (Lot 3c)
 *
 * Ajoute le ciblage d'un utilisateur individuel sur un élément d'acceptation,
 * en complément de target_roles (ciblage par catégorie). Le choix entre les
 * deux est exclusif côté formulaire admin : un élément cible soit un
 * utilisateur, soit une ou plusieurs catégories, jamais les deux.
 *
 * @see doc/plans/acceptations_reconnaissances_plan.md (Lot 3c)
 * @see doc/prds/approbation_de_documents_prd.md
 */
class Migration_Acceptance_items_target_user extends CI_Migration {

    private function column_exists($table, $column)
    {
        $t = $this->db->escape_str($table);
        $c = $this->db->escape_str($column);

        $row = $this->db->query(
            "SELECT COUNT(*) AS cnt
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t' AND COLUMN_NAME = '$c'"
        )->row_array();

        return isset($row['cnt']) && (int) $row['cnt'] > 0;
    }

    public function up() {
        $ok = TRUE;

        if (!$this->column_exists('acceptance_items', 'target_user_login')) {
            $ok = (bool) $this->db->query(
                "ALTER TABLE `acceptance_items`
                 ADD COLUMN `target_user_login` VARCHAR(25) NULL
                     COMMENT 'Membre individuel cible, alternative a target_roles' AFTER `target_roles`"
            ) && $ok;
        }

        if ($this->db->query(
            "SHOW INDEX FROM `acceptance_items` WHERE Key_name = 'idx_target_user_login'"
        )->num_rows() == 0) {
            $ok = (bool) $this->db->query(
                "ALTER TABLE `acceptance_items` ADD INDEX `idx_target_user_login` (`target_user_login`)"
            ) && $ok;
        }

        if ($this->db->query(
            "SELECT 1 FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME = 'acceptance_items'
               AND CONSTRAINT_NAME = 'fk_acceptance_items_target_user'"
        )->num_rows() == 0) {
            $ok = (bool) $this->db->query(
                "ALTER TABLE `acceptance_items`
                 ADD CONSTRAINT `fk_acceptance_items_target_user` FOREIGN KEY (`target_user_login`)
                     REFERENCES `membres` (`mlogin`) ON DELETE CASCADE"
            ) && $ok;
        }

        log_message('info', 'Migration 168: acceptance_items.target_user_login column/index/FK created');
        return $ok;
    }

    public function down() {
        $ok = TRUE;

        if ($this->db->query(
            "SELECT 1 FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME = 'acceptance_items'
               AND CONSTRAINT_NAME = 'fk_acceptance_items_target_user'"
        )->num_rows() > 0) {
            $ok = (bool) $this->db->query(
                "ALTER TABLE `acceptance_items` DROP FOREIGN KEY `fk_acceptance_items_target_user`"
            ) && $ok;
        }

        if ($this->db->query(
            "SHOW INDEX FROM `acceptance_items` WHERE Key_name = 'idx_target_user_login'"
        )->num_rows() > 0) {
            $ok = (bool) $this->db->query(
                "ALTER TABLE `acceptance_items` DROP INDEX `idx_target_user_login`"
            ) && $ok;
        }

        if ($this->column_exists('acceptance_items', 'target_user_login')) {
            $ok = (bool) $this->db->query(
                "ALTER TABLE `acceptance_items` DROP COLUMN `target_user_login`"
            ) && $ok;
        }

        log_message('info', 'Migration 168: acceptance_items.target_user_login column/index/FK dropped');
        return $ok;
    }
}
