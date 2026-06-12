<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Drop_mails_table extends CI_Migration {
    public function up() {
        $this->dbforge->drop_table('mails', TRUE);
    }

    public function down() {
        $this->dbforge->add_field(array(
            'id'                  => array('type' => 'INT', 'unsigned' => TRUE, 'auto_increment' => TRUE),
            'titre'               => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE),
            'destinataires'       => array('type' => 'TEXT', 'null' => TRUE),
            'copie_a'             => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE),
            'selection'           => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE),
            'individuel'          => array('type' => 'TINYINT', 'constraint' => 1, 'null' => TRUE, 'default' => 0),
            'date_envoie'         => array('type' => 'DATE', 'null' => TRUE),
            'texte'               => array('type' => 'TEXT', 'null' => TRUE),
            'debut_facturation'   => array('type' => 'DATE', 'null' => TRUE),
            'fin_facturation'     => array('type' => 'DATE', 'null' => TRUE),
            'created_at'          => array('type' => 'DATETIME', 'null' => TRUE),
            'updated_at'          => array('type' => 'DATETIME', 'null' => TRUE),
            'created_by'          => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE),
            'updated_by'          => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('mails');
    }
}
