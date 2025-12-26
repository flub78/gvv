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
     * @param $filter_section filter by section (not used in this model)
     */
    public function selector_with_all($where = array(), $filter_section = FALSE) {
        $result = $this->selector($where);
        $result[] = $this->lang->line("all_sections");
        return $result;
    }
}

/* End of file */