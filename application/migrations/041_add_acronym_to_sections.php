<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_acronym_to_sections extends CI_Migration {

    public function up()
    {
        $this->dbforge->add_column('sections', array(
            'acronyme' => array(
                'type' => 'VARCHAR',
                'constraint' => 10,
                'null' => TRUE,
            ),
            'couleur' => array(
                'type' => 'VARCHAR',
                'constraint' => 7,
                'null' => TRUE,
            ),
        ));
    }

    public function down()
    {
        $this->dbforge->drop_column('sections', 'acronyme');
        $this->dbforge->drop_column('sections', 'couleur');
    }
}
