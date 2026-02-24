<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 077: Add is_private flag to document_types
 *
 * Private document types restrict file access (download/preview/thumbnail)
 * to bureau members, admin users, and the document owner.
 * CA members (non-bureau) can only see metadata.
 * Only bureau members and admin users may approve/reject private documents.
 *
 * @see doc/plans/archivage_documentaire_plan.md
 */
class Migration_Document_types_private extends CI_Migration
{
    public function up()
    {
        $this->db->query(
            "ALTER TABLE `document_types`
             ADD COLUMN `is_private` TINYINT(1) NOT NULL DEFAULT 0
             COMMENT 'Acces aux fichiers restreint au bureau, admins et proprietaire'
             AFTER `active`"
        );

        log_message('info', 'Migration 077: is_private added to document_types');
    }

    public function down()
    {
        $this->db->query(
            "ALTER TABLE `document_types` DROP COLUMN `is_private`"
        );

        log_message('info', 'Migration 077: is_private dropped from document_types');
    }
}

/* End of file 077_document_types_private.php */
/* Location: ./application/migrations/077_document_types_private.php */
