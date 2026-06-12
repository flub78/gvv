<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Drop_mniveaux_macces extends CI_Migration {
    public function up() {
        $this->dbforge->drop_column('membres', 'mniveaux');
        $this->dbforge->drop_column('membres', 'macces');
    }

    public function down() {
        $this->dbforge->add_column('membres', array(
            'mniveaux' => array('type' => 'DOUBLE', 'null' => TRUE, 'default' => 0, 'after' => 'msexe'),
            'macces'   => array('type' => 'INT',    'null' => TRUE, 'default' => 0, 'after' => 'mniveaux'),
        ));
    }
}
