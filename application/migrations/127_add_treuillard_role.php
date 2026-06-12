<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_treuillard_role extends CI_Migration {
    public function up() {
        $this->db->insert('types_roles', array(
            'nom'             => 'treuillard',
            'description'     => 'Opérateur de treuil',
            'scope'           => 'section',
            'is_system_role'  => 1,
            'display_order'   => 98,
            'translation_key' => 'role_treuillard',
        ));
    }

    public function down() {
        $this->db->where('nom', 'treuillard')->delete('types_roles');
    }
}
