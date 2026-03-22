<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 085: Add aerodrome field to vols_decouverte
 *
 * Adds the takeoff site (aerodrome) to discovery flights.
 */
class Migration_Vols_decouverte_aerodrome extends CI_Migration
{
    public function up()
    {
        $this->db->query(
            "ALTER TABLE `vols_decouverte` ADD COLUMN `aerodrome` VARCHAR(100) NULL DEFAULT NULL AFTER `airplane_immat`"
        );

        log_message('info', 'Migration 085: aerodrome added to vols_decouverte');
    }

    public function down()
    {
        $this->db->query(
            "ALTER TABLE `vols_decouverte` DROP COLUMN `aerodrome`"
        );

        log_message('info', 'Migration 085: aerodrome removed from vols_decouverte');
    }
}
