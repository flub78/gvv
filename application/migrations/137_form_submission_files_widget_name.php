<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Migration_Form_submission_files_widget_name extends CI_Migration {

    public function up() {
        $this->db->query('ALTER TABLE form_submission_files MODIFY COLUMN field_id INT(11) NULL');
        $this->db->query('ALTER TABLE form_submission_files ADD COLUMN widget_name VARCHAR(100) NULL AFTER field_id');
    }

    public function down() {
        $this->db->query('ALTER TABLE form_submission_files DROP COLUMN widget_name');
        // Note: reverting field_id to NOT NULL would fail if NULL rows exist
    }
}
