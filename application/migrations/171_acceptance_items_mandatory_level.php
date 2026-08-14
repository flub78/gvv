<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 171: acceptance_items.mandatory_level (Lot 3d)
 *
 * Replaces the boolean `mandatory` (Lot 1) with a three-level
 * ENUM('optional','mandatory_soft','mandatory_hard'):
 * - optional: l'utilisateur peut accepter, refuser, ignorer ou reporter.
 * - mandatory_soft: le message du jour associe ne peut pas etre masque tant
 *   que la validation n'a pas ete faite, mais le reste de GVV reste utilisable.
 * - mandatory_hard: comme mandatory_soft, mais l'ensemble de l'application
 *   est bloque tant que l'element n'est pas valide (sauf deconnexion et page
 *   de validation, cf. Lot 3d/4).
 *
 * Backfill: mandatory=1 -> mandatory_hard (le seul niveau obligatoire qui
 * existait avant ce lot), mandatory=0 -> optional.
 *
 * @see doc/plans/acceptations_reconnaissances_plan.md (Lot 3d)
 * @see doc/prds/approbation_de_documents_prd.md (Canal de notification et niveaux d'obligation)
 */
class Migration_Acceptance_items_mandatory_level extends CI_Migration {

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

        if (!$this->column_exists('acceptance_items', 'mandatory_level')) {
            $ok = (bool) $this->db->query(
                "ALTER TABLE `acceptance_items`
                 ADD COLUMN `mandatory_level` ENUM('optional','mandatory_soft','mandatory_hard') NOT NULL DEFAULT 'optional'
                     COMMENT 'Niveau d obligation' AFTER `mandatory`"
            ) && $ok;

            if ($this->column_exists('acceptance_items', 'mandatory')) {
                $ok = (bool) $this->db->query(
                    "UPDATE `acceptance_items`
                     SET `mandatory_level` = IF(`mandatory` = 1, 'mandatory_hard', 'optional')"
                ) && $ok;
            }
        }

        if ($this->column_exists('acceptance_items', 'mandatory')) {
            $ok = (bool) $this->db->query(
                "ALTER TABLE `acceptance_items` DROP COLUMN `mandatory`"
            ) && $ok;
        }

        log_message('info', 'Migration 171: acceptance_items.mandatory_level created, mandatory dropped');
        return $ok;
    }

    public function down() {
        $ok = TRUE;

        if (!$this->column_exists('acceptance_items', 'mandatory')) {
            $ok = (bool) $this->db->query(
                "ALTER TABLE `acceptance_items`
                 ADD COLUMN `mandatory` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Obligatoire' AFTER `version_date`"
            ) && $ok;

            if ($this->column_exists('acceptance_items', 'mandatory_level')) {
                $ok = (bool) $this->db->query(
                    "UPDATE `acceptance_items`
                     SET `mandatory` = IF(`mandatory_level` = 'optional', 0, 1)"
                ) && $ok;
            }
        }

        if ($this->column_exists('acceptance_items', 'mandatory_level')) {
            $ok = (bool) $this->db->query(
                "ALTER TABLE `acceptance_items` DROP COLUMN `mandatory_level`"
            ) && $ok;
        }

        log_message('info', 'Migration 171: acceptance_items.mandatory_level dropped, mandatory restored');
        return $ok;
    }
}
