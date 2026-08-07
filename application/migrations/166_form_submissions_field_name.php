<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Migration 166: form_submission_values/form_submission_files switch from
 * field_id (FK to form_fields) to field_name (plain string), then drop
 * form_fields entirely.
 *
 * Context: form_fields was a DB cache of the fields declared in a form's
 * HTML content, auto-synced on every save (sync_fields_from_html()). Since
 * migration 165, form content is file-backed (uploads/formulaires/) and no
 * longer needs a DB mirror of its structure — field metadata (type,
 * required, validation, identifier) is now parsed on demand from the HTML
 * file. Keeping form_fields around only to anchor form_submission_values/
 * form_submission_files via a numeric FK is no longer justified once those
 * two tables reference fields by name instead.
 *
 * form_submission_files already had a nullable `widget_name` fallback
 * column (used for HTML-only signature widgets that never had a
 * form_fields row) — this migration backfills it for every row still
 * keyed by field_id, then makes it the sole key.
 *
 * Irreversible: down() restores the schema shape (empty form_fields table,
 * field_id columns back) but does NOT restore the field_id values dropped
 * by up() — there is no way to reconstruct which numeric id a field_name
 * used to have.
 *
 * @see doc/prds/remplissage_formulaires_prd.md (EF2-bis)
 */
class Migration_Form_submissions_field_name extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 166;
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

        $errors += $this->run_queries(array(
            "ALTER TABLE `form_submission_values`
                ADD COLUMN `field_name` VARCHAR(100) NULL AFTER `field_id`",
            "UPDATE `form_submission_values` fsv
                JOIN `form_fields` ff ON ff.id = fsv.field_id
                SET fsv.field_name = ff.name",
            "ALTER TABLE `form_submission_values`
                DROP FOREIGN KEY `fk_form_submission_values_field`",
            // The new unique key must be added before dropping the old one:
            // uq_form_submission_value(submission_id, field_id) is also the only
            // index covering submission_id, so it is pinned by
            // fk_form_submission_values_submission until a replacement exists.
            "ALTER TABLE `form_submission_values`
                ADD UNIQUE KEY `uq_form_submission_value_new` (`submission_id`, `field_name`)",
            "ALTER TABLE `form_submission_values`
                DROP INDEX `uq_form_submission_value`",
            "ALTER TABLE `form_submission_values`
                DROP INDEX `idx_form_submission_values_field`",
            "ALTER TABLE `form_submission_values`
                DROP COLUMN `field_id`",
            "ALTER TABLE `form_submission_values`
                MODIFY COLUMN `field_name` VARCHAR(100) NOT NULL",
            "ALTER TABLE `form_submission_values`
                RENAME INDEX `uq_form_submission_value_new` TO `uq_form_submission_value`",
        ));

        $errors += $this->run_queries(array(
            "UPDATE `form_submission_files` fsf
                JOIN `form_fields` ff ON ff.id = fsf.field_id
                SET fsf.widget_name = ff.name
                WHERE fsf.widget_name IS NULL",
            "ALTER TABLE `form_submission_files`
                DROP FOREIGN KEY `fk_form_submission_files_field`",
            "ALTER TABLE `form_submission_files`
                DROP INDEX `idx_form_submission_files_field`",
            "ALTER TABLE `form_submission_files`
                DROP COLUMN `field_id`",
        ));

        $errors += $this->run_queries(array(
            "DROP TABLE IF EXISTS `form_fields`",
        ));

        if ($errors > 0) {
            gvv_error("Migration " . $this->migration_number . ": $errors error(s) while switching submissions to field_name");
            return false;
        }

        gvv_info("Migration database up to " . $this->migration_number);
        return true;
    }

    public function down() {
        $errors = 0;

        $errors += $this->run_queries(array(
            "CREATE TABLE IF NOT EXISTS `form_fields` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `form_id` int(11) NOT NULL,
                `page_id` int(11) NOT NULL,
                `name` varchar(100) NOT NULL,
                `label` varchar(255) NOT NULL,
                `field_type` enum('text','email','date','number','textarea','select','radio','checkbox','file','signature','subform') NOT NULL,
                `is_required` tinyint(1) NOT NULL DEFAULT 0,
                `is_identifier` tinyint(1) NOT NULL DEFAULT 0,
                `sort_order` int(11) NOT NULL DEFAULT 0,
                `options_json` text DEFAULT NULL,
                `validation_rules` text DEFAULT NULL,
                `gvv_role` varchar(50) DEFAULT NULL,
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                `created_by` varchar(50) DEFAULT NULL,
                `updated_by` varchar(50) DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_form_fields_name` (`form_id`,`name`),
                KEY `idx_form_fields_page` (`page_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci",
            "ALTER TABLE `form_submission_files`
                ADD COLUMN `field_id` int(11) DEFAULT NULL AFTER `submission_id`",
            "ALTER TABLE `form_submission_values`
                ADD COLUMN `field_id` int(11) DEFAULT NULL AFTER `submission_id`",
        ));
        // field_name and its unique key are intentionally left in place: dropping
        // uq_form_submission_value(submission_id, field_name) here would fail
        // (no other index covers submission_id for fk_form_submission_values_submission
        // once field_id is re-added empty/unindexed). down() restores the pre-166
        // *shape* only — field_name staying populated alongside the restored,
        // empty field_id column is harmless.

        gvv_info("Migration database down to " . ($this->migration_number - 1) . " (schema restauré, données field_id/form_fields NON restaurées, errors=$errors)");
        return !$errors;
    }
}

/* End of file 166_form_submissions_field_name.php */
/* Location: ./application/migrations/166_form_submissions_field_name.php */
