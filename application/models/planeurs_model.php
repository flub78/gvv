<?php
if (!defined('BASEPATH'))
    exit ('No direct script access allowed');

$CI = & get_instance();
$CI->load->model('common_model');

/**
 *	Planeurs model
 *
 *  C'est un CRUD de base, la seule chose que fait cette classe
 *  est de définir le nom de la table. Tous les méthodes sont
 *  implémentés dans Common_Model
 */
class Planeurs_model extends Common_Model {
    public $table = 'machinesp';
    protected $primary_key = 'mpimmat';

    /**
     *	Retourne le tableau tableau utilisé pour l'affichage par page
     *	@return objet		  La liste
     */
    public function select_page($nb = 1000, $debut = 0, $selection = array()) {
    	/*
        $columns = 'mpimmat, mpmodele, mpnumc, mpconstruc, mpbiplace, mpautonome, mptreuil, mpprive, mmax_facturation, actif';
        $columns .= ", tarifs1.prix as prix, tarifs2.prix as prix_forfait, tarifs3.prix as prix_moteur";
        $where = "machinesp.mprix = tarifs1.reference and machinesp.mprix_forfait = tarifs2.reference and machinesp.mprix_moteur = tarifs3.reference";
        $select = $this->db->select($columns)->from("machinesp, tarifs as tarifs1, tarifs as tarifs2, tarifs as tarifs3")
        	->where($where)
            // ->limit($nb, $debut)
            ->get()->result_array();
		*/
        $columns = 'mpimmat, mpmodele, mpnumc, mpconstruc, mpbiplace, mpautonome, mptreuil, mpprive, actif, fabrication';
        $select = $this->db->select($columns)->from("machinesp")
        	->where($selection)
            // ->limit($nb, $debut)
            ->get()->result_array();
    	
        foreach ($select as $key => $row) {
            $machine = $row['mpimmat'];
            $select[$key]['vols'] = anchor(controller_url("vols_planeur/vols_de_la_machine/$machine"), "vols");
        }

        $this->gvvmetadata->store_table("vue_planeurs", $select);
        
        gvv_debug("sql: " . $this->db->last_query());
        
        return $select;
    }

    /**
     * Retourne une chaine de caractère qui identifie une ligne de façon unique.
     * Cette chaine est utilisé dans les affichages.
     * Par défaut elle retourne la valeur de la clé, mais elle est conçue pour être
     * surchargée.
     */
    public function image($key) {
        $vals = $this->get_by_id('mpimmat', $key);
        $str = $vals['mpmodele'] . " - " . $vals['mpimmat'];
        if ($vals['mpnumc']) { 
            $str .= " - (" . $vals['mpnumc'] .")";
        }
        return $str;
    }

    /**
     *	Retourne une liste d'objets
     *
     *  foreach ($list as $line) {
     *     $this->table->add_row($line->mlogin,
     *     $line->mprenom,
     *     $line->mnom,
     *
     *	@param integer $nb	  Le nombre de membres
     *	@param integer $debut Nombre de news à sauter
     *	@return objet		  La liste
     */
    public function list_of($where = array (), $nb = 100, $debut = 0) {
        return $this->db->select('*')
        ->from($this->table)
        ->where($where)
        ->limit($nb, $debut)
        ->order_by('mpmodele')->get()->result();
    }

}

/* End of file */