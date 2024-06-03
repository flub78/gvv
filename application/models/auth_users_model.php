<?php
if (!defined('BASEPATH'))
    exit ('No direct script access allowed');

/**
 *	Avions model
 *
 *  C'est un CRUD de base, la seule chose que fait cette classe
 *  est de définir le nom de la table. Tous les méthodes sont 
 *  implémentés dans Common_Model
 */

$CI = & get_instance();
$CI->load->model('common_model');

class Avions_model extends Common_Model {
    public $table = 'machinesa';
    protected $primary_key = 'macimmat';

    /**
     *	Retourne le tableau tableau utilisé pour l'affichage par page
     *	@return objet		  La liste
     */
    public function select_page($nb = 1000, $debut = 0) {
        return $this->select_columns('macmodele, macimmat, macconstruc, macplaces, macrem, maprive', $nb, $debut);
    }
}

/* End of file */