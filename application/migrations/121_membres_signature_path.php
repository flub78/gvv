<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Migration_membres_signature_path extends CI_Migration {

    public function up() {
        if (!$this->db->field_exists('signature_path', 'membres')) {
            $this->dbforge->add_column('membres', array(
                'signature_path' => array(
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'photo',
                ),
            ));
        }
    }

    public function down() {
        if ($this->db->field_exists('signature_path', 'membres')) {
            $this->dbforge->drop_column('membres', 'signature_path');
        }
    }
}
