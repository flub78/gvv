<?php
if (!defined('BASEPATH'))
    exit ('No direct script access allowed');

$CI = & get_instance();
$CI->load->model('common_model');

/**
 *	Accès base Terrainss
 *
 *  C'est un CRUD de base, la seule chose que fait cette classe
 *  est de définir le nom de la table. Tous les méthodes sont 
 *  implémentés dans Common_Model
 */
class Terrains_model extends Common_Model {
    public $table = 'terrains';
    protected $primary_key = 'oaci';

    /**
     *	Retourne le tableau tableau utilisé pour l'affichage par page
     *	@return objet		  La liste
     */
    public function select_page($nb = 1000, $debut = 0) {
        $select = $this->select_columns('oaci, nom, freq1, freq2, comment');
        $this->gvvmetadata->store_table("vue_terrains", $select);
        return $select;
    }
    
    
    /**
     * Retourne une chaine de caractère qui identifie une ligne de façon unique.
     * Cette chaine est utilisé dans les affichages.
     * Par défaut elle retourne la valeur de la clé, mais elle est conçue pour être
     * surchargée.
     */
    public function image($key) {
        if ($key == "")
            return "";
        $vals = $this->get_by_id('oaci', $key);
        if (array_key_exists('oaci', $vals) && array_key_exists('nom', $vals)) {
            return $vals['oaci'] . " " . $vals['nom'];
        } else {
            return "terrain inconnu $key";
        }
    }
    
}

/* End of file */