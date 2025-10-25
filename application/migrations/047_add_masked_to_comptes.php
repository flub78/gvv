<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 047 - Add masked field to comptes table
 * 
 * This migration adds a boolean field 'masked' to the comptes table
 * to support hiding accounts from selectors and reports.
 * 
 * Masked accounts will not appear in:
 * - Account selectors (dropdowns)
 * - Account lists (balance sheets, detailed balance)
 * - Results reports
 * 
 * @date 2024-10-25
 */
class Migration_add_masked_to_comptes extends CI_Migration {

    public function up()
    {
        // Add 'masked' boolean field to comptes table
        // Default is 0 (false) - account is visible
        // Set to 1 (true) to hide the account
        $this->dbforge->add_column('comptes', array(
            'masked' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'null' => FALSE,
                'comment' => 'Account is hidden from selectors and reports when set to 1'
            )
        ));
        
        log_message('info', 'Migration 047: Added masked field to comptes table');
    }

    public function down()
    {
        // Remove the masked field if rolling back
        $this->dbforge->drop_column('comptes', 'masked');
        
        log_message('info', 'Migration 047: Rolled back - removed masked field from comptes table');
    }
}
