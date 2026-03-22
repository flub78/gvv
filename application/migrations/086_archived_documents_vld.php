<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 086: Add vld_id to archived_documents
 *
 * Links an archived document to a discovery flight (vol de découverte).
 * Used by the passenger briefing feature to attach the signed document
 * to the corresponding flight record.
 */
class Migration_Archived_documents_vld extends CI_Migration
{
    public function up()
    {
        $this->db->query(
            "ALTER TABLE `archived_documents`
             ADD COLUMN `vld_id` INT NULL DEFAULT NULL AFTER `section_id`,
             ADD CONSTRAINT `fk_archived_documents_vld`
                 FOREIGN KEY (`vld_id`) REFERENCES `vols_decouverte`(`id`) ON DELETE SET NULL"
        );

        log_message('info', 'Migration 086: vld_id added to archived_documents');
    }

    public function down()
    {
        $this->db->query(
            "ALTER TABLE `archived_documents`
             DROP FOREIGN KEY `fk_archived_documents_vld`,
             DROP COLUMN `vld_id`"
        );

        log_message('info', 'Migration 086: vld_id removed from archived_documents');
    }
}
