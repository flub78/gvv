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

    /**
     * Get active gliders for dropdown selector
     *
     * @return array [mpimmat => "Modèle - Immat"]
     */
    public function get_selector() {
        $this->db->select('mpimmat, mpmodele')
            ->from($this->table)
            ->where('actif', 1)
            ->order_by('mpmodele', 'asc');

        $results = $this->db->get()->result_array();
        $selector = array('' => '');
        foreach ($results as $row) {
            $selector[$row['mpimmat']] = $row['mpmodele'] . ' - ' . $row['mpimmat'];
        }
        return $selector;
    }

    /**
     * Delete a glider with validation
     * Checks if the glider is referenced in flight records before deletion
     * 
     * @param array $where - selection criteria
     * @return boolean - TRUE if deleted, FALSE if blocked
     */
    function delete($where = array()) {
        // Get mpimmat from where clause
        if (!isset($where['mpimmat'])) {
            // If no mpimmat specified, can't validate - abort
            return FALSE;
        }
        
        $mpimmat = $where['mpimmat'];
        
        // Get CodeIgniter instance for language support
        $CI =& get_instance();
        $CI->lang->load('planeurs');
        
        // Check if glider is referenced in flight records
        $references = array();
        
        // Check volsp.vpmacid (glider flights)
        $this->db->where('vpmacid', $mpimmat);
        $count = $this->db->count_all_results('volsp');
        if ($count > 0) {
            $references[] = $CI->lang->line('planeur_delete_ref_volsp') . " ($count)";
        }
        
        // Check volsp.remorqueur (tow plane for glider flights)
        $this->db->where('remorqueur', $mpimmat);
        $count = $this->db->count_all_results('volsp');
        if ($count > 0) {
            $references[] = $CI->lang->line('planeur_delete_ref_remorqueur') . " ($count)";
        }
        
        // If there are references, block deletion with error message
        if (!empty($references)) {
            $CI->load->library('session');
            
            // Create detailed error message
            $error_msg = $CI->lang->line('planeur_delete_blocked') . "\n\n";
            $error_msg .= $CI->lang->line('planeur_delete_dependencies') . "\n";
            $error_msg .= "• " . implode("\n• ", $references);
            
            $CI->session->set_flashdata('error', $error_msg);
            $CI->session->set_flashdata('delete_failed_planeur', $mpimmat);
            return FALSE;
        }
        
        // If no references, proceed with deletion using parent method
        parent::delete($where);
        return TRUE;
    }

}

/* End of file */