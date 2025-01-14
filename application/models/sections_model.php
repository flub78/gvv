<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 *	Accès base Sections
 *
 *  C'est un CRUD de base. Beaucoup de méthodes sont 
 *  implémentés dans Common_Model
 */
class Sections_model extends Common_Model {
    public $table = 'sections';
    protected $primary_key = 'id';

    /**
     *	Retourne le tableau tableau utilisé pour l'affichage par page
     *	@return objet		  La liste
     */
    public function select_page($nb = 1000, $debut = 0) {
        $select = $this->select_columns('id, nom, description');
        $this->gvvmetadata->store_table("vue_sections", $select);
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
}

/* End of file */