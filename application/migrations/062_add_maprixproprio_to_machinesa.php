<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 062: Add maprixproprio field to machinesa table
 *
 * Adds a new price field 'maprixproprio' (prix de l'heure propriétaire)
 * after the 'maprixdc' field, with the same type as other price fields.
 */
class Migration_Add_maprixproprio_to_machinesa extends CI_Migration {

    public function up() {
        $this->db->query("ALTER TABLE `machinesa`
            ADD COLUMN `maprixproprio` VARCHAR(32) NULL
            COMMENT 'Prix de l''heure propriétaire'
            AFTER `maprixdc`");

        log_message('info', 'Migration 062: Added maprixproprio field to machinesa table');
    }

    public function down() {
        $this->db->query("ALTER TABLE `machinesa` DROP COLUMN `maprixproprio`");

        log_message('info', 'Migration 062: Removed maprixproprio field from machinesa table');
    }
}
