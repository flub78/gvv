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
 * Migration 068: Create Acceptance System Tables
 *
 * Creates tables for the acceptance and acknowledgment system:
 * - acceptance_items: Elements to be accepted (documents, training, briefings)
 * - acceptance_records: Acceptance/refusal records per person
 * - acceptance_signatures: External signatures (tactile, paper uploads)
 * - acceptance_tokens: Temporary links for external signatures
 *
 * @see doc/plans/acceptations_reconnaissances_plan.md
 * @see doc/prds/approbation_de_documents_prd.md
 */
class Migration_Acceptance_system extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 68;
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
            // Table: acceptance_items - Elements to be accepted
            "CREATE TABLE IF NOT EXISTS `acceptance_items` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `title` VARCHAR(255) NOT NULL COMMENT 'Titre de l element',
                `category` ENUM('document','formation','controle','briefing','autorisation') NOT NULL COMMENT 'Categorie acceptation',
                `pdf_path` VARCHAR(255) NULL COMMENT 'Chemin fichier PDF',
                `target_type` ENUM('internal','external') NOT NULL DEFAULT 'internal' COMMENT 'Interne ou externe',
                `version_date` DATE NULL COMMENT 'Date de creation/version',
                `mandatory` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Obligatoire',
                `deadline` DATE NULL COMMENT 'Date limite acceptation',
                `dual_validation` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Necessite double validation',
                `role_1` VARCHAR(64) NULL COMMENT 'Premier role (ex: instructeur)',
                `role_2` VARCHAR(64) NULL COMMENT 'Second role (ex: eleve)',
                `target_roles` VARCHAR(255) NULL COMMENT 'Roles cibles separes par virgule',
                `active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Element actif',
                `created_by` VARCHAR(25) NOT NULL COMMENT 'Administrateur createur',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NULL,
                PRIMARY KEY (`id`),
                KEY `idx_category` (`category`),
                KEY `idx_active` (`active`),
                KEY `idx_deadline` (`deadline`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
            COMMENT='Elements a faire accepter'",

            // Table: acceptance_records - Acceptance/refusal records
            "CREATE TABLE IF NOT EXISTS `acceptance_records` (
                `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `item_id` INT(11) UNSIGNED NOT NULL COMMENT 'Element concerne',
                `user_login` VARCHAR(25) NULL COMMENT 'Membre (NULL si externe)',
                `external_name` VARCHAR(128) NULL COMMENT 'Nom complet personne externe',
                `status` ENUM('pending','accepted','refused') NOT NULL DEFAULT 'pending',
                `validation_role` VARCHAR(64) NULL COMMENT 'Role dans double validation',
                `partner_record_id` BIGINT(20) UNSIGNED NULL COMMENT 'Enregistrement partenaire (double validation)',
                `formula_text` TEXT NULL COMMENT 'Formule enregistree',
                `acted_at` DATETIME NULL COMMENT 'Date action',
                `created_at` DATETIME NOT NULL,
                `initiated_by` VARCHAR(25) NULL COMMENT 'Responsable ayant initie (si externe)',
                `signature_mode` ENUM('direct','link','qrcode','paper') NULL COMMENT 'Mode signature externe',
                `linked_pilot_login` VARCHAR(25) NULL COMMENT 'Pilote rattache ulterieurement',
                `linked_by` VARCHAR(25) NULL COMMENT 'Utilisateur ayant effectue le rattachement',
                `linked_at` DATETIME NULL COMMENT 'Date du rattachement',
                PRIMARY KEY (`id`),
                KEY `idx_item` (`item_id`),
                KEY `idx_user` (`user_login`),
                KEY `idx_status` (`status`),
                KEY `idx_partner` (`partner_record_id`),
                KEY `idx_linked_pilot` (`linked_pilot_login`),
                CONSTRAINT `fk_acceptance_records_item` FOREIGN KEY (`item_id`)
                    REFERENCES `acceptance_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_acceptance_records_user` FOREIGN KEY (`user_login`)
                    REFERENCES `membres` (`mlogin`) ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk_acceptance_records_partner` FOREIGN KEY (`partner_record_id`)
                    REFERENCES `acceptance_records` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk_acceptance_records_linked_pilot` FOREIGN KEY (`linked_pilot_login`)
                    REFERENCES `membres` (`mlogin`) ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk_acceptance_records_linked_by` FOREIGN KEY (`linked_by`)
                    REFERENCES `membres` (`mlogin`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
            COMMENT='Enregistrements acceptation/refus'",

            // Table: acceptance_signatures - External signatures
            "CREATE TABLE IF NOT EXISTS `acceptance_signatures` (
                `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `record_id` BIGINT(20) UNSIGNED NOT NULL,
                `signer_first_name` VARCHAR(64) NOT NULL,
                `signer_last_name` VARCHAR(64) NOT NULL,
                `signer_quality` VARCHAR(64) NULL COMMENT 'pere, mere, tuteur legal (pour autorisation)',
                `beneficiary_first_name` VARCHAR(64) NULL COMMENT 'Prenom mineur (pour autorisation)',
                `beneficiary_last_name` VARCHAR(64) NULL COMMENT 'Nom mineur (pour autorisation)',
                `signature_type` ENUM('tactile','upload') NOT NULL,
                `signature_data` MEDIUMTEXT NULL COMMENT 'Donnees base64 signature tactile',
                `file_path` VARCHAR(255) NULL COMMENT 'Chemin fichier uploade',
                `original_filename` VARCHAR(255) NULL,
                `file_size` INT(11) UNSIGNED NULL,
                `mime_type` VARCHAR(64) NULL,
                `signed_at` DATETIME NOT NULL,
                `pilot_attestation` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Attestation presence pilote (mode papier)',
                PRIMARY KEY (`id`),
                KEY `idx_record` (`record_id`),
                CONSTRAINT `fk_signatures_record` FOREIGN KEY (`record_id`)
                    REFERENCES `acceptance_records` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
            COMMENT='Signatures externes'",

            // Table: acceptance_tokens - Temporary links for external signatures
            "CREATE TABLE IF NOT EXISTS `acceptance_tokens` (
                `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `token` VARCHAR(128) NOT NULL COMMENT 'Token aleatoire',
                `item_id` INT(11) UNSIGNED NOT NULL,
                `record_id` BIGINT(20) UNSIGNED NULL COMMENT 'Enregistrement associe une fois cree',
                `mode` ENUM('direct','link','qrcode') NOT NULL,
                `created_by` VARCHAR(25) NOT NULL,
                `created_at` DATETIME NOT NULL,
                `expires_at` DATETIME NOT NULL,
                `used` TINYINT(1) NOT NULL DEFAULT 0,
                `used_at` DATETIME NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_token` (`token`),
                KEY `idx_item` (`item_id`),
                KEY `idx_expires` (`expires_at`),
                CONSTRAINT `fk_tokens_item` FOREIGN KEY (`item_id`)
                    REFERENCES `acceptance_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
            COMMENT='Liens temporaires pour signatures externes'"
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database up to " . $this->migration_number . ", errors=$errors");
        return !$errors;
    }

    public function down() {
        $errors = 0;

        // Drop tables in reverse order (foreign key dependencies)
        $sqls = array(
            "DROP TABLE IF EXISTS `acceptance_tokens`",
            "DROP TABLE IF EXISTS `acceptance_signatures`",
            "DROP TABLE IF EXISTS `acceptance_records`",
            "DROP TABLE IF EXISTS `acceptance_items`"
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");
        return !$errors;
    }
}

/* End of file 068_acceptance_system.php */
/* Location: ./application/migrations/068_acceptance_system.php */
