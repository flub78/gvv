<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 075: Document types class/instance model
 *
 * Aligns the schema with the class/instance model defined in the PRD:
 * - A document type is a class of rules, not a unique slot per pilot.
 * - Multiple documents of the same type can coexist for the same entity.
 * - Removes `allow_versioning` (obsolete: versioning is now always explicit
 *   via the "Nouvelle version" action, never automatic).
 *
 * @see doc/plans/archivage_documentaire_plan.md
 * @see doc/prds/archivage_documentaire_prd.md
 */
class Migration_Document_types_class_instance extends CI_Migration
{
    public function up()
    {
        $this->db->query(
            "ALTER TABLE `document_types` DROP COLUMN `allow_versioning`"
        );

        log_message('info', 'Migration 075: allow_versioning dropped from document_types');
    }

    public function down()
    {
        $this->db->query(
            "ALTER TABLE `document_types`
             ADD COLUMN `allow_versioning` TINYINT(1) NOT NULL DEFAULT 1
             COMMENT 'Autorise le versionning'
             AFTER `has_expiration`"
        );

        log_message('info', 'Migration 075: allow_versioning restored in document_types');
    }
}

/* End of file 075_document_types_class_instance.php */
/* Location: ./application/migrations/075_document_types_class_instance.php */
