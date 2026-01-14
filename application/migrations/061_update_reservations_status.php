<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 061: Update reservations status values and make pilot optional
 *
 * Changes:
 * - Replace status ENUM values with: 'reservation', 'maintenance', 'unavailable'
 * - Make pilot_member_id nullable for maintenance/unavailable entries
 * - Update existing records to new status values
 */
class Migration_Update_reservations_status extends CI_Migration {

    public function up() {
        // Step 1: Add temporary column for new status
        $this->db->query("ALTER TABLE `reservations`
            ADD COLUMN `status_new` ENUM('reservation', 'maintenance', 'unavailable')
            DEFAULT 'reservation' AFTER `status`");

        // Step 2: Map old status values to new ones
        // pending -> reservation
        // confirmed -> reservation
        // completed -> reservation (past reservations)
        // cancelled -> reservation (will be deleted or kept as reservation)
        // no_show -> reservation
        $this->db->query("UPDATE `reservations` SET `status_new` = 'reservation' WHERE `status` IN ('pending', 'confirmed', 'completed', 'cancelled', 'no_show')");

        // Step 3: Drop old status column
        $this->db->query("ALTER TABLE `reservations` DROP COLUMN `status`");

        // Step 4: Rename new column to status
        $this->db->query("ALTER TABLE `reservations` CHANGE COLUMN `status_new` `status`
            ENUM('reservation', 'maintenance', 'unavailable')
            DEFAULT 'reservation'
            COMMENT 'Reservation status'");

        // Step 5: Make pilot_member_id nullable (for maintenance/unavailable)
        $this->db->query("ALTER TABLE `reservations`
            MODIFY COLUMN `pilot_member_id` VARCHAR(25) NULL
            COMMENT 'Pilot member login, optional for maintenance/unavailable (mlogin from membres table)'");

        log_message('info', 'Migration 061: Updated reservations status values and made pilot optional');
    }

    public function down() {
        // Step 1: Add back old status column
        $this->db->query("ALTER TABLE `reservations`
            ADD COLUMN `status_old` ENUM('pending', 'confirmed', 'completed', 'cancelled', 'no_show')
            DEFAULT 'pending' AFTER `status`");

        // Step 2: Map new status values back to old ones (all go to confirmed)
        $this->db->query("UPDATE `reservations` SET `status_old` = 'confirmed' WHERE `status` IN ('reservation', 'maintenance', 'unavailable')");

        // Step 3: Drop new status column
        $this->db->query("ALTER TABLE `reservations` DROP COLUMN `status`");

        // Step 4: Rename old column back to status
        $this->db->query("ALTER TABLE `reservations` CHANGE COLUMN `status_old` `status`
            ENUM('pending', 'confirmed', 'completed', 'cancelled', 'no_show')
            DEFAULT 'pending'
            COMMENT 'Reservation status'");

        // Step 5: Make pilot_member_id NOT NULL again
        // Set a default value for records that have NULL
        $this->db->query("UPDATE `reservations` SET `pilot_member_id` = 'admin' WHERE `pilot_member_id` IS NULL");

        $this->db->query("ALTER TABLE `reservations`
            MODIFY COLUMN `pilot_member_id` VARCHAR(25) NOT NULL
            COMMENT 'Pilot member login (mlogin from membres table)'");

        log_message('info', 'Migration 061: Reverted reservations status values and pilot field');
    }
}
