<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Migration_ecritures_description_varchar255 extends CI_Migration {

    public function up() {
        $this->db->query('ALTER TABLE ecritures MODIFY description VARCHAR(255)');
    }

    public function down() {
        $this->db->query('ALTER TABLE ecritures MODIFY description VARCHAR(80)');
    }
}
