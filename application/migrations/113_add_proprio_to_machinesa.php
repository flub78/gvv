<?php
/**
 * Migration 113: Add proprio field to machinesa table
 *
 * Adds machinesa.proprio (mlogin of the owner), mirroring machinesp.proprio.
 * Used by the reservation system to apply the owner hourly rate (maprixproprio)
 * and enforce the correct balance check for private aircraft owners.
 */
class Migration_Add_proprio_to_machinesa extends CI_Migration {

    public function up() {
        $this->db->query("ALTER TABLE `machinesa` ADD COLUMN `proprio` VARCHAR(25) NULL DEFAULT NULL AFTER `maprixproprio`");
        log_message('info', 'Migration 113: Added proprio field to machinesa table');
    }

    public function down() {
        $this->db->query("ALTER TABLE `machinesa` DROP COLUMN `proprio`");
        log_message('info', 'Migration 113: Removed proprio field from machinesa table');
    }
}
