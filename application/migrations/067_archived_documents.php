<?php
/**
 *    GVV Gestion vol a voile
 *    Copyright (C) 2011  Philippe Boissel & Frederic Peignot
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Migration 067: Create Archived Documents Tables
 *
 * Creates tables for document archiving:
 * - document_types: Document type definitions and rules
 * - archived_documents: Archived documents with expiration tracking
 *
 * @see doc/plans/archivage_documentaire_plan.md
 */
class Migration_Archived_documents extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 67;
    }

    private function run_queries($sqls = array()) {
        $errors = 0;
        foreach ($sqls as $sql) {
            gvv_info("Migration sql: " . $sql);
            if (!$this->db->query($sql)) {
                $mysql_msg = $this->db->_error_message();
                $mysql_error = $this->db->_error_number();
                gvv_error("Migration error: code=$mysql_error, msg=$mysql_msg");
                $errors += 1;
            }
        }
        return $errors;
    }

    public function up() {
        $errors = 0;

        $sqls = array(
            // Table: document_types - Document type definitions and rules
            "CREATE TABLE IF NOT EXISTS `document_types` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `code` VARCHAR(32) NOT NULL COMMENT 'Code unique (ex: medical, insurance, license)',
                `name` VARCHAR(128) NOT NULL COMMENT 'Libelle affiche',
                `section_id` INT(11) NULL COMMENT 'Section specifique (NULL = toutes sections)',
                `scope` ENUM('pilot', 'section', 'club') NOT NULL DEFAULT 'pilot' COMMENT 'Portee du document',
                `required` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Document obligatoire',
                `has_expiration` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Document avec date expiration',
                `allow_versioning` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Autorise le versionning',
                `storage_by_year` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Stockage organise par annee',
                `alert_days_before` INT(11) NULL DEFAULT 30 COMMENT 'Jours avant expiration pour alerte',
                `active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Type actif',
                `display_order` INT(11) NOT NULL DEFAULT 0 COMMENT 'Ordre affichage',
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_code_section` (`code`, `section_id`),
                KEY `idx_section` (`section_id`),
                CONSTRAINT `fk_document_types_section` FOREIGN KEY (`section_id`)
                    REFERENCES `sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
            COMMENT='Types de documents et regles associees'",

            // Table: archived_documents - Archived documents with expiration
            "CREATE TABLE IF NOT EXISTS `archived_documents` (
                `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `document_type_id` INT(11) UNSIGNED NOT NULL COMMENT 'Type de document',
                `pilot_login` VARCHAR(25) NULL COMMENT 'Pilote associe (NULL si club/section)',
                `section_id` INT(11) NULL COMMENT 'Section associee',
                `file_path` VARCHAR(255) NOT NULL COMMENT 'Chemin du fichier',
                `original_filename` VARCHAR(255) NOT NULL COMMENT 'Nom fichier original',
                `description` VARCHAR(255) NULL COMMENT 'Description libre',
                `uploaded_by` VARCHAR(25) NOT NULL COMMENT 'Utilisateur ayant uploade',
                `uploaded_at` DATETIME NOT NULL COMMENT 'Date upload',
                `valid_from` DATE NULL COMMENT 'Date debut validite',
                `valid_until` DATE NULL COMMENT 'Date fin validite',
                `alarm_disabled` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Alerte desactivee par admin',
                `previous_version_id` BIGINT(20) UNSIGNED NULL COMMENT 'Lien vers version precedente',
                `is_current_version` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Version courante',
                `file_size` INT(11) UNSIGNED NULL COMMENT 'Taille fichier en octets',
                `mime_type` VARCHAR(64) NULL COMMENT 'Type MIME',
                PRIMARY KEY (`id`),
                KEY `idx_pilot` (`pilot_login`),
                KEY `idx_section` (`section_id`),
                KEY `idx_type` (`document_type_id`),
                KEY `idx_expiration` (`valid_until`),
                KEY `idx_current` (`is_current_version`),
                KEY `idx_alarm` (`alarm_disabled`),
                CONSTRAINT `fk_archived_documents_type` FOREIGN KEY (`document_type_id`)
                    REFERENCES `document_types` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT `fk_archived_documents_pilot` FOREIGN KEY (`pilot_login`)
                    REFERENCES `membres` (`mlogin`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_archived_documents_section` FOREIGN KEY (`section_id`)
                    REFERENCES `sections` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk_archived_documents_prev` FOREIGN KEY (`previous_version_id`)
                    REFERENCES `archived_documents` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
            COMMENT='Documents archives avec expiration'",

            // Insert initial document types
            "INSERT INTO `document_types` (`code`, `name`, `section_id`, `scope`, `required`, `has_expiration`, `allow_versioning`, `storage_by_year`, `alert_days_before`, `active`, `display_order`) VALUES
                ('medical', 'Visite medicale', NULL, 'pilot', 1, 1, 1, 0, 30, 1, 1),
                ('insurance', 'Assurance', NULL, 'pilot', 1, 1, 1, 0, 30, 1, 2),
                ('license', 'Brevet/Licence', NULL, 'pilot', 0, 1, 1, 0, 30, 1, 3),
                ('club_doc', 'Document club', NULL, 'club', 0, 0, 1, 0, NULL, 1, 10),
                ('signature', 'Signature membre', NULL, 'pilot', 0, 1, 0, 0, 30, 1, 4),
                ('ci', 'Carte identite', NULL, 'pilot', 0, 1, 1, 0, 30, 1, 5),
                ('parental', 'Autorisation parentale', NULL, 'pilot', 0, 0, 0, 0, NULL, 1, 6),
                ('bia', 'Brevet Initiation Aeronautique', NULL, 'pilot', 0, 0, 0, 0, NULL, 1, 7)"
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database up to " . $this->migration_number . ", errors=$errors");
        return !$errors;
    }

    public function down() {
        $errors = 0;

        $sqls = array(
            // Drop tables in reverse order (foreign key dependencies)
            "DROP TABLE IF EXISTS `archived_documents`",
            "DROP TABLE IF EXISTS `document_types`"
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");
        return !$errors;
    }
}

/* End of file 067_archived_documents.php */
/* Location: ./application/migrations/067_archived_documents.php */
