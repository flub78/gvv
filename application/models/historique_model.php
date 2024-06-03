<?php
if (!defined('BASEPATH'))
    exit ('No direct script access allowed');

$CI = & get_instance();
$CI->load->model('common_model');

/**
 *	Accès base Historique
 *
 *  C'est un CRUD de base, la seule chose que fait cette classe
 *  est de définir le nom de la table. Tous les méthodes sont 
 *  implémentés dans Common_Model
 */
class Historique_model extends Common_Model {
    public $table = 'historique';
    protected $primary_key = 'id';

    /**
     *	Retourne le tableau tableau utilisé pour l'affichage par page
     *	@return objet		  La liste
     */
    public function select_page($nb = 1000, $debut = 0, $selection = array()) {
        
    	$columns = 'id, machine, annee, heures';
        
        $select = $this->db
        ->select($columns)
        ->from($this->table)
        ->where($selection)
        ->order_by('machine, annee')
        // ->limit($nb, $debut)
        ->get()->result_array();
    	
        $this->gvvmetadata->store_table("vue_historique", $select);

        gvv_debug("sql: " . $this->db->last_query());
        
        return $select;
    }
}

/* End of file */