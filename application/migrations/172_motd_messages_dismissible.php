<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 172: motd_messages.dismissible (Lot 3d)
 *
 * When dismissible = 0, a message cannot be hidden by the targeted user
 * (Motd_user_state_model::hide_message()/hide_all_messages() refuse it).
 * Used for messages linked to a mandatory acceptance_items (Lot 3d/4): the
 * message stays visible until the associated validation is done.
 *
 * @see doc/plans/acceptations_reconnaissances_plan.md (Lot 3d)
 * @see application/migrations/143_create_motd_tables.php
 */
class Migration_Motd_messages_dismissible extends CI_Migration {

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

        if (!$this->column_exists('motd_messages', 'dismissible')) {
            $ok = (bool) $this->db->query(
                "ALTER TABLE `motd_messages`
                 ADD COLUMN `dismissible` TINYINT(1) NOT NULL DEFAULT 1
                     COMMENT 'Masquable par l utilisateur cible' AFTER `origin`"
            ) && $ok;
        }

        log_message('info', 'Migration 172: motd_messages.dismissible created');
        return $ok;
    }

    public function down() {
        $ok = TRUE;

        if ($this->column_exists('motd_messages', 'dismissible')) {
            $ok = (bool) $this->db->query(
                "ALTER TABLE `motd_messages` DROP COLUMN `dismissible`"
            ) && $ok;
        }

        log_message('info', 'Migration 172: motd_messages.dismissible dropped');
        return $ok;
    }
}
