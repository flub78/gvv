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
class Avions_model extends Common_Model {
    public $table = 'machinesa';
    protected $primary_key = 'macimmat';

    /**
     *	Retourne le tableau tableau utilisé pour l'affichage par page
     *	@return objet		  La liste
     */
    public function select_page($nb = 1000, $debut = 0, $selection = array()) {
    	/*
        $columns = 'macmodele, macimmat, macconstruc, macplaces, macrem, maprive, actif, tarifs.prix as prix, tarifs.date';
        $where = "machinesa.maprix = tarifs.reference";
        
        $select = $this->db
        ->select($columns)
        ->from("machinesa, tarifs")->where($where)
        ->order_by('macimmat asc, tarifs.date asc')
        // ->limit($nb, $debut)
        ->get()->result_array();
		*/
        
    	$columns = 'macmodele, macimmat, macconstruc, macplaces, macrem, maprive, actif, fabrication';
        
        $select = $this->db
        ->select($columns)
        ->from("machinesa")
        ->where($selection)
        ->order_by('macimmat asc')
        // ->limit($nb, $debut)
        ->get()->result_array();
    	
        foreach ($select as $key => $row) {
            $machine = $row['macimmat'];
            $select[$key]['vols'] = anchor(controller_url("vols_avion/vols_de_la_machine/$machine"), "vols");
        }
        $this->gvvmetadata->store_table("vue_avions", $select);

        gvv_debug("sql: " . $this->db->last_query());
        
        return $select;
    }
    
    /*
     * retourne la liste des immatriculations 
     */
    public function machine_list ($where = array(), $list_only = true) {

    	$columns = 'macimmat, horametre_en_minutes';
    	
    	$select = $this->db
    	->select($columns)
    	->from("machinesa")
    	->where($where)
    	->order_by('macimmat asc')
    	->get()->result_array();

    	$result = array();
    	foreach ($select as $key => $row) {
    		$machine = $row['macimmat'];
    		if ($list_only) {
    			$result[] = $machine;
    		} else {
    			$result[$machine] = $row['horametre_en_minutes'];
    		}
    	}
    	
    	gvv_debug("sql: " . $this->db->last_query());
    	
    	return $result;
    	 
    }
}

/* End of file */