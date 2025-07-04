<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 *	Accès base Terrains
 *
 *  C'est un CRUD de base, la seule chose que fait cette classe
 *  est de définir le nom de la table. Tous les méthodes sont 
 *  implémentés dans Common_Model
 */
class Associations_of_model extends Common_Model {
    public $table = 'associations_of';
    protected $primary_key = 'id';

    /**
     *	Retourne le tableau tableau utilisé pour l'affichage par page
     *	@return objet		  La liste
     */
    public function select_page($nb = 1000, $debut = 0) {
        $select = $this->select_columns('id, id_compte_of, nom_of, club, id_compte_gvv');
        $this->gvvmetadata->store_table("vue_associations_of", $select);
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
        if (array_key_exists('id', $vals) && array_key_exists('nom_of', $vals)) {
            return $vals['id_compte_of'] . " " . $vals['nom_of'] . " " . $vals['club'];
        } else {
            return "association inconnue $key";
        }
    }
}

/* End of file */