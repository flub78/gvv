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
class User_roles_per_section_model extends Common_Model {
    public $table = 'user_roles_per_section';
    protected $primary_key = 'id';

    /**
     *	Retourne le tableau tableau utilisé pour l'affichage par page
     *	@return objet		  La liste
     */
    public function select_page($nb = 1000, $debut = 0) {
        $select = $this->select_columns('id, nom, description');
        $this->gvvmetadata->store_table("vue_user_roles_per_section", $select);
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
}

/* End of file */