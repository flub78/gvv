<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Migration 164: add 'signature' and 'subform' to form_fields.field_type
 *
 * These widget types (data-gvv-type="signature" / "subform") have been
 * supported by the forms rendering engine (Forms_renderer, Forms_public,
 * Forms_admin::extract_html_fields) since their introduction, but the
 * field_type ENUM created in migration 116_forms_core.php was never
 * updated to match. As a result, sync_fields_from_html() silently fails
 * to insert a form_fields row for every signature/subform widget (see
 * Forms_admin::_split_existing_files() docblock).
 *
 * @see application/migrations/116_forms_core.php
 */
class Migration_Form_fields_signature_subform_type extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 164;
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
            "ALTER TABLE `form_fields`
                MODIFY COLUMN `field_type`
                ENUM('text','email','date','number','textarea','select','radio','checkbox','file','signature','subform')
                NOT NULL",
        );

        $errors += $this->run_queries($sqls);

        if ($errors > 0) {
            gvv_error("Migration " . $this->migration_number . ": $errors error(s) while extending form_fields.field_type");
            return false;
        }

        gvv_info("Migration database up to " . $this->migration_number);
        return true;
    }

    public function down() {
        $errors = 0;

        $sqls = array(
            "ALTER TABLE `form_fields`
                MODIFY COLUMN `field_type`
                ENUM('text','email','date','number','textarea','select','radio','checkbox','file')
                NOT NULL",
        );

        $errors += $this->run_queries($sqls);
        gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");
        return !$errors;
    }
}

/* End of file 164_form_fields_signature_subform_type.php */
/* Location: ./application/migrations/164_form_fields_signature_subform_type.php */
