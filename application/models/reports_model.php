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
class Reports_model extends Common_Model {
    public $table = 'reports';
    protected $primary_key = 'nom';

    /**
     *	Retourne le tableau tableau utilisé pour l'affichage par page
     *	@return objet		  La liste
     */
    public function select_page($nb = 1000, $debut = 0) {
        $columns = 'nom, titre';

        $select = $this->db
        ->select($columns)
        ->from($this->table)
        ->limit($nb, $debut)->get()->result_array();

        $this->gvvmetadata->store_table("vue_reports", $select);

        return $select;
    }
}

/* End of file */