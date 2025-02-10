<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');
$CI->load->language('sections');

/**
 *	Accès base Sections
 *
 *  C'est un CRUD de base. Beaucoup de méthodes sont 
 *  implémentés dans Common_Model
 */
class Types_roles_model extends Common_Model {
    public $table = 'types_roles';
    protected $primary_key = 'id';

    /**
     *	Retourne le tableau tableau utilisé pour l'affichage par page
     *	@return objet		  La liste
     */
    public function select_page($nb = 1000, $debut = 0) {
        $select = $this->select_columns('id, nom, description');
        $this->gvvmetadata->store_table("types_roles", $select);
        return $select;
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
            return "role inconnu $key";
        }
    }

    /**
     * Retourne un hash qui peut-être utilisé dans un menu drop-down
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