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
 * Migration 063: Create Formation Training Tables
 *
 * Creates tables for the training tracking system:
 * - formation_programmes: Training programs (e.g., SPL, BPP)
 * - formation_lecons: Lessons within programs
 * - formation_sujets: Topics within lessons
 * - formation_inscriptions: Student enrollments in programs
 * - formation_seances: Training sessions (with or without enrollment)
 * - formation_evaluations: Topic evaluations per session
 *
 * @see doc/prds/suivi_formation_prd.md
 */
class Migration_Add_formation_tables extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 63;
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
            // Table: formation_programmes - Training programs
            "CREATE TABLE IF NOT EXISTS `formation_programmes` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `code` VARCHAR(50) NOT NULL COMMENT 'Program code (e.g., SPL, BPP)',
                `titre` VARCHAR(255) NOT NULL COMMENT 'Program title',
                `description` TEXT NULL COMMENT 'Program description',
                `contenu_markdown` LONGTEXT NOT NULL COMMENT 'Full program content in Markdown',
                `section_id` INT(11) NULL COMMENT 'Section ID (NULL = all sections)',
                `version` INT(11) NOT NULL DEFAULT 1 COMMENT 'Program version number',
                `type_aeronef` ENUM('planeur', 'avion') NOT NULL DEFAULT 'planeur' COMMENT 'Aircraft type: planeur or avion',
                `statut` ENUM('actif', 'archive') NOT NULL DEFAULT 'actif' COMMENT 'Program status',
                `date_creation` DATETIME NOT NULL COMMENT 'Creation date',
                `date_modification` DATETIME NULL COMMENT 'Last modification date',
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_code` (`code`),
                KEY `idx_section` (`section_id`),
                KEY `idx_statut` (`statut`),
                CONSTRAINT `fk_form_prog_section` FOREIGN KEY (`section_id`)
                    REFERENCES `sections` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
            COMMENT='Training programs for glider pilots'",

            // Table: formation_lecons - Lessons within programs
            "CREATE TABLE IF NOT EXISTS `formation_lecons` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `programme_id` INT(11) NOT NULL COMMENT 'Parent program ID',
                `numero` INT(11) NOT NULL COMMENT 'Lesson number',
                `titre` VARCHAR(255) NOT NULL COMMENT 'Lesson title',
                `description` TEXT NULL COMMENT 'Lesson description',
                `ordre` INT(11) NOT NULL COMMENT 'Display order',
                PRIMARY KEY (`id`),
                KEY `idx_programme` (`programme_id`),
                KEY `idx_ordre` (`programme_id`, `ordre`),
                CONSTRAINT `fk_form_lecon_prog` FOREIGN KEY (`programme_id`)
                    REFERENCES `formation_programmes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
            COMMENT='Lessons within training programs'",

            // Table: formation_sujets - Topics within lessons
            "CREATE TABLE IF NOT EXISTS `formation_sujets` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `lecon_id` INT(11) NOT NULL COMMENT 'Parent lesson ID',
                `numero` VARCHAR(20) NOT NULL COMMENT 'Topic number (e.g., 1.1, 1.2)',
                `titre` VARCHAR(255) NOT NULL COMMENT 'Topic title',
                `description` TEXT NULL COMMENT 'Topic description',
                `objectifs` TEXT NULL COMMENT 'Learning objectives',
                `ordre` INT(11) NOT NULL COMMENT 'Display order',
                PRIMARY KEY (`id`),
                KEY `idx_lecon` (`lecon_id`),
                KEY `idx_ordre` (`lecon_id`, `ordre`),
                CONSTRAINT `fk_form_sujet_lecon` FOREIGN KEY (`lecon_id`)
                    REFERENCES `formation_lecons` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
            COMMENT='Topics within training lessons'",

            // Table: formation_inscriptions - Student enrollments
            "CREATE TABLE IF NOT EXISTS `formation_inscriptions` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `pilote_id` VARCHAR(25) NOT NULL COMMENT 'Student member login',
                `programme_id` INT(11) NOT NULL COMMENT 'Training program ID',
                `version_programme` INT(11) NOT NULL COMMENT 'Program version at enrollment',
                `instructeur_referent_id` VARCHAR(25) NULL COMMENT 'Reference instructor login',
                `statut` ENUM('ouverte', 'suspendue', 'cloturee', 'abandonnee') NOT NULL DEFAULT 'ouverte'
                    COMMENT 'Enrollment status',
                `date_ouverture` DATE NOT NULL COMMENT 'Enrollment opening date',
                `date_suspension` DATE NULL COMMENT 'Suspension date',
                `motif_suspension` TEXT NULL COMMENT 'Suspension reason',
                `date_cloture` DATE NULL COMMENT 'Closure date',
                `motif_cloture` TEXT NULL COMMENT 'Closure reason',
                `commentaires` TEXT NULL COMMENT 'General comments',
                PRIMARY KEY (`id`),
                KEY `idx_pilote` (`pilote_id`),
                KEY `idx_programme` (`programme_id`),
                KEY `idx_statut` (`statut`),
                KEY `idx_instructeur` (`instructeur_referent_id`),
                CONSTRAINT `fk_form_inscr_pilote` FOREIGN KEY (`pilote_id`)
                    REFERENCES `membres` (`mlogin`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_form_inscr_prog` FOREIGN KEY (`programme_id`)
                    REFERENCES `formation_programmes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT `fk_form_inscr_inst` FOREIGN KEY (`instructeur_referent_id`)
                    REFERENCES `membres` (`mlogin`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
            COMMENT='Student enrollments in training programs'",

            // Table: formation_seances - Training sessions
            "CREATE TABLE IF NOT EXISTS `formation_seances` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `inscription_id` INT(11) NULL COMMENT 'Enrollment ID (NULL = free session)',
                `pilote_id` VARCHAR(25) NOT NULL COMMENT 'Student receiving training',
                `programme_id` INT(11) NOT NULL COMMENT 'Reference training program',
                `date_seance` DATE NOT NULL COMMENT 'Session date',
                `instructeur_id` VARCHAR(25) NOT NULL COMMENT 'Instructor login',
                `machine_id` VARCHAR(10) NOT NULL COMMENT 'Aircraft registration',
                `duree` TIME NOT NULL COMMENT 'Session duration (HH:MM:SS)',
                `nb_atterrissages` INT(11) NOT NULL DEFAULT 1 COMMENT 'Number of landings',
                `meteo` TEXT NULL COMMENT 'Weather conditions (JSON array)',
                `commentaires` TEXT NULL COMMENT 'Session comments',
                `prochaines_lecons` VARCHAR(255) NULL COMMENT 'Recommended next lessons',
                PRIMARY KEY (`id`),
                KEY `idx_inscription` (`inscription_id`),
                KEY `idx_pilote` (`pilote_id`),
                KEY `idx_programme` (`programme_id`),
                KEY `idx_date` (`date_seance`),
                KEY `idx_instructeur` (`instructeur_id`),
                KEY `idx_machine` (`machine_id`),
                CONSTRAINT `fk_form_seance_inscr` FOREIGN KEY (`inscription_id`)
                    REFERENCES `formation_inscriptions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_form_seance_pilote` FOREIGN KEY (`pilote_id`)
                    REFERENCES `membres` (`mlogin`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_form_seance_prog` FOREIGN KEY (`programme_id`)
                    REFERENCES `formation_programmes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT `fk_form_seance_inst` FOREIGN KEY (`instructeur_id`)
                    REFERENCES `membres` (`mlogin`) ON DELETE RESTRICT ON UPDATE CASCADE
                -- No FK on machine_id: references machinesp or machinesa depending on programme type_aeronef
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
            COMMENT='Training sessions (with or without enrollment)'",

            // Table: formation_evaluations - Topic evaluations per session
            "CREATE TABLE IF NOT EXISTS `formation_evaluations` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `seance_id` INT(11) NOT NULL COMMENT 'Parent session ID',
                `sujet_id` INT(11) NOT NULL COMMENT 'Evaluated topic ID',
                `niveau` ENUM('-', 'A', 'R', 'Q') NOT NULL DEFAULT '-'
                    COMMENT 'Evaluation level: - (not covered), A (introduced), R (review needed), Q (acquired)',
                `commentaire` TEXT NULL COMMENT 'Evaluation comment',
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_seance_sujet` (`seance_id`, `sujet_id`),
                KEY `idx_seance` (`seance_id`),
                KEY `idx_sujet` (`sujet_id`),
                KEY `idx_niveau` (`niveau`),
                CONSTRAINT `fk_form_eval_seance` FOREIGN KEY (`seance_id`)
                    REFERENCES `formation_seances` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_form_eval_sujet` FOREIGN KEY (`sujet_id`)
                    REFERENCES `formation_sujets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
            COMMENT='Topic evaluations within training sessions'"
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database up to " . $this->migration_number . ", errors=$errors");
        return !$errors;
    }

    public function down() {
        $errors = 0;

        // Drop tables in reverse order due to foreign key constraints
        $sqls = array(
            "DROP TABLE IF EXISTS `formation_evaluations`",
            "DROP TABLE IF EXISTS `formation_seances`",
            "DROP TABLE IF EXISTS `formation_inscriptions`",
            "DROP TABLE IF EXISTS `formation_sujets`",
            "DROP TABLE IF EXISTS `formation_lecons`",
            "DROP TABLE IF EXISTS `formation_programmes`"
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");
        return !$errors;
    }
}

/* End of file 063_add_formation_tables.php */
/* Location: ./application/migrations/063_add_formation_tables.php */
