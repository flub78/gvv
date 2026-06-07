<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Form_fields_identifier extends CI_Migration {

    public function up() {
        $this->db->query(
            "ALTER TABLE form_fields
             ADD COLUMN is_identifier TINYINT(1) NOT NULL DEFAULT 0 AFTER is_required"
        );
    }

    public function down() {
        $this->db->query("ALTER TABLE form_fields DROP COLUMN is_identifier");
    }
}
