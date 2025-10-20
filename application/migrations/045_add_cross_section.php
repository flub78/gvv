<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 045: Add cross-section entry for global roles
 *
 * Adds a special section with id=0 to represent cross-section (global) roles.
 * This allows global roles to be stored with section_id=0 while respecting
 * the foreign key constraint.
 */
class Migration_Add_cross_section extends CI_Migration
{
    public function up()
    {
        // Add special cross-section entry
        $this->db->query("
            INSERT INTO sections (id, nom, acronyme)
            VALUES (0, 'Toutes sections', 'ALL')
            ON DUPLICATE KEY UPDATE nom='Toutes sections', acronyme='ALL'
        ");

        log_message('info', 'Migration 045: Added cross-section entry');
    }

    public function down()
    {
        // Remove cross-section entry
        $this->db->where('id', 0)->delete('sections');

        log_message('info', 'Migration 045: Removed cross-section entry');
    }
}
