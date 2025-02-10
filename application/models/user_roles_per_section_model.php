<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');
$CI->load->language('user_roles_per_section');

/**
 *	Accès base Sections
 *
 *  C'est un CRUD de base. Beaucoup de méthodes sont 
 *  implémentés dans Common_Model
 */
class User_roles_per_section_model extends Common_Model {
    public $table = 'user_roles_per_section';
    protected $primary_key = 'id';

    /**
     *	Retourne le tableau tableau utilisé pour l'affichage par page
     *	@return objet		  La liste
         SELECT 
            u.username,
            t.nom AS role_type,
            s.nom AS section_name,
            urps.id AS user_role_id,
            u.email
            #    t.description AS role_description,
            #    s.description AS section_description
        FROM user_roles_per_section urps
        JOIN users u ON urps.user_id = u.id
        JOIN types_roles t ON urps.types_roles_id = t.id
        JOIN sections s ON urps.section_id = s.id;
     */
    public function select_page($nb = 1000, $debut = 0) {

        $this->db->select('users.username, types_roles.nom as role_type, sections.nom as section_name, 
                   user_roles_per_section.id as id, users.email, 
                   types_roles.description as role_description, 
                   sections.description as section_description');
        $this->db->from('user_roles_per_section');
        $this->db->join('users', 'user_roles_per_section.user_id = users.id');
        $this->db->join('types_roles', 'user_roles_per_section.types_roles_id = types_roles.id');
        $this->db->join('sections', 'user_roles_per_section.section_id = sections.id');

        $result = $this->db->get()->result_array();
        $this->gvvmetadata->store_table("vue_user_roles_per_section", $result);
    }

    /**
     * Retourne une chaîne de caractère qui identifie une ligne de façon unique.
     * Cette chaîne est utilisé dans les affichages.
     * Par défaut elle retourne la valeur de la clé, mais elle est conçue pour être
     * surchargée.
     */
    public function image($key) {
        if ($key == "")
            return "";
        $vals = $this->get_by_id('id', $key);
        if (array_key_exists('nom', $vals)) {
            return $vals['nom'];
        } else {
            return "section inconnu $key";
        }
    }

    /**
     * Retourne un hash qui peut-être utilisé dans un menu drow-down
     * avec une entrée "Tous .
     * ."
     *
     * @param $where selection
     */
    public function selector_with_all($where = array()) {
        $result = $this->selector($where);
        $result[] = $this->lang->line("all_sections");
        return $result;
    }

    /** 
     * For some reasons unit test library can only be invoked directly from the controller.
     * This test returns an array of test results.
     */
    public function test() {
        $res = [];

        $res[] = ["description" => "Model sections", "result" => true];

        // Count elements in sections table
        $initial_count = $this->db->count_all($this->table);
        $res[] = ["description" => "Initial count sections: " . $initial_count, "result" => true];

        return $res;

        // Insert a dummy element
        $data = array(
            'nom' => 'Autogire',
            'description' => 'Section Autogire'
        );
        $insert_result = $this->db->insert($this->table, $data);
        $last_id = $this->db->insert_id();

        $count = $this->db->count_all($this->table);

        $res[] = ["description" => "Insert returns true", "result" => $insert_result];
        $res[] = ["description" => "Section created", "result" => ($count == $initial_count + 1)];

        // Get last inserted id
        $res[] = ["description" => "Last inserted ID: " . $last_id, "result" => ($last_id > 0)];

        // Get last inserted element
        $last = $this->get_by_id('id', $last_id);

        $res[] = ["description" => "Last element id", "result" => ($last['id'] == $last_id)];

        // Delete last inserted element
        $delete_result = $this->db->delete($this->table, array('id' => $last_id));
        $res[] = ["description" => "Delete returns true", "result" => $delete_result];

        // Verify deletion
        $count_after_delete = $this->db->count_all($this->table);
        $res[] = ["description" => "Section deleted", "result" => ($count_after_delete == $initial_count)];

        return $res;
    }
}

/* End of file */