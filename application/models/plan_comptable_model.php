<?php
if (!defined('BASEPATH'))
    exit ('No direct script access allowed');

/**
 *	Plan comptable model
 *
 *  C'est un CRUD de base, la seule chose que fait cette classe
 *  est de définir le nom de la table. Tous les méthodes sont 
 *  implémentés dans Common_Model
 */

$CI = & get_instance();
$CI->load->model('common_model');

class Plan_comptable_model extends Common_Model {
    public $table = 'planc';
    protected $primary_key = 'pcode';

    /**
     *	Retourne le tableau tableau utilisé pour l'affichage par page
     *	@return objet		  La liste
     */
    public function select_page() {
        // $res = $this->select_columns('pcode, pdesc');
        $res = array(array('pcode' => 101, 'pdesc' => "0123456789012345678901234567890123456789"));
        foreach ($res as $key => $row) {
            $res[$key]['image'] = '(' . $row['pcode'] . ') ' . $row['pdesc'];
        }
        $this->gvvmetadata->store_table("planc", $res, $this->db->last_query());
        return $res;
    }

    /**
     * Retourne une chaine de caractère qui identifie une ligne de façon unique.
     * Cette chaine est utilisé dans les affichages.
     * Par défaut elle retourne la valeur de la clé, mais elle est conçue pour être
     * surchargée.
     */
    public function image($key) {
        $vals = $this->get_by_id($this->primary_key, $key);
        return $vals['pcode'] . " " . $vals['pdesc'];
    }

}

/* End of file */