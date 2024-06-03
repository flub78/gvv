<?php
if (!defined('BASEPATH'))
    exit ('No direct script access allowed');

$CI = & get_instance();
$CI->load->model('common_model');

/**
 *	Accès base Avions
 *
 *  C'est un CRUD de base, la seule chose que fait cette classe
 *  est de définir le nom de la table. Tous les méthodes sont 
 *  implémentés dans Common_Model
 */
class Types_ticket_model extends Common_Model {
    public $table = 'type_ticket';
    protected $primary_key = 'id';

    /**
     *	Retourne le tableau tableau utilisé pour l'affichage par page
     *	@return objet		  La liste
     */
    public function select_page($nb = 1000, $debut = 0) {
        $columns = 'id, nom';

        $select = $this->db->select($columns)->from("type_ticket")->limit($nb, $debut)->get()->result_array();

        $this->gvvmetadata->store_table("vue_type_ticket", $select);

        return $select;
    }
    
    /**
     * Retourne une chaine de caractère qui identifie une ligne de façon unique.
     * Cette chaine est utilisé dans les affichages.
     * Par défaut elle retourne la valeur de la clé, mais elle est conçue pour être
     * surchargée.
     */
    public function image($key) {
    	$vals = $this->get_by_id('id', $key);
    	$str = $vals['nom'];
    	return $str;
    }
    
}

/* End of file */