<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 089: Add foreign key from vols_decouverte.aerodrome to terrains(oaci)
 *
 * Resizes aerodrome to VARCHAR(10) to match terrains.oaci,
 * nullifies any values that do not match an existing terrain OACI code,
 * then adds the FK constraint.
 */
class Migration_Vols_decouverte_aerodrome_fk extends CI_Migration
{
    public function up()
    {
        // Nullify values that don't reference a valid terrain
        $this->db->query(
            "UPDATE `vols_decouverte` SET `aerodrome` = NULL
             WHERE `aerodrome` IS NOT NULL
               AND `aerodrome` NOT IN (SELECT `oaci` FROM `terrains`)"
        );

        // Resize column and match terrains.oaci collation (latin1_general_ci)
        $this->db->query(
            "ALTER TABLE `vols_decouverte`
             MODIFY COLUMN `aerodrome` VARCHAR(10) CHARACTER SET latin1 COLLATE latin1_general_ci NULL DEFAULT NULL"
        );

        // Add FK constraint
        $this->db->query(
            "ALTER TABLE `vols_decouverte`
             ADD CONSTRAINT `fk_vd_aerodrome`
             FOREIGN KEY (`aerodrome`) REFERENCES `terrains`(`oaci`)
             ON DELETE SET NULL ON UPDATE CASCADE"
        );

        log_message('info', 'Migration 089: FK vols_decouverte.aerodrome -> terrains.oaci added');
    }

    public function down()
    {
        $this->db->query(
            "ALTER TABLE `vols_decouverte` DROP FOREIGN KEY `fk_vd_aerodrome`"
        );

        $this->db->query(
            "ALTER TABLE `vols_decouverte`
             MODIFY COLUMN `aerodrome` VARCHAR(100) NULL DEFAULT NULL"
        );

        log_message('info', 'Migration 089: FK vols_decouverte.aerodrome removed');
    }
}
