<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');
$CI->load->model('sections_model');

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
     * Constructor
     */
    function __construct() {
        parent::__construct();
    }

    /**
     *	Retourne le tableau tableau utilisé pour l'affichage par page
     *	@return objet		  La liste
     */
    public function select_page($nb = 1000, $debut = 0, $selection = array()) {

        $columns = 'macmodele, macimmat, macconstruc, macplaces, macrem, maprive, actif, fabrication, club, sections.nom as section_name';

        $this->db
            ->select($columns)
            ->from("machinesa")
            ->where($selection)
            ->order_by('macimmat asc');
        $this->db->join('sections', 'machinesa.club = sections.id');

        // select per section
        if ($this->section) {
            $this->db->where('sections.id', $this->section_id);
        }
        // ->limit($nb, $debut)
        $select = $this->db->get()->result_array();

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
    public function machine_list($where = array(), $list_only = true) {

        $columns = 'macimmat, horametre_en_minutes, club';

        $this->db
            ->select($columns)
            ->from("machinesa")
            ->where($where);

        // select per section
        if ($this->section) {
            $this->db->where('club', $this->section_id);
        }

        $select = $this->db->order_by('macimmat asc')
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

    /**
     * Ajoute un élément
     *
     * @param $data hash
     *            des valeurs
     */
    public function create($data) {
        if (isset($data['fabrication']) && !$data['fabrication']) {
            unset($data['fabrication']);
        }
        parent::create($data);
    }

    /**
     * Edite un element existant
     *
     * @param integer $id
     *            $id de l'élément
     * @param hash $data
     *            donnée à remplacer
     * @return bool Le résultat de la requête
     */
    public function update($keyid, $data, $keyvalue = '') {
        if ($keyvalue == '')
            $keyvalue = $data[$keyid];
        $this->db->where($keyid, $keyvalue);
        unset($data[$keyid]);

        if (isset($data['fabrication'])) {
            if ($data['fabrication'] == '') {
                unset($data['fabrication']);
            }
        }

        if (!$this->db->update($this->table, $data)) {
            // Get MySQL error message
            $error = $this->db->_error_message();
            gvv_error("MySQL Error #$errno: $error");
        }
    }

    /**
     * Retourne un hash qui peut-être utilisé dans un menu drop-down
     *
     * @param $where selection
     * @param $order ordre
     *            de tri
     */
    public function selector($where = array(), $order = "asc", $filter_section = FALSE) {
        return parent::selector($where, $order, TRUE);
    }

    /**
     * Get active multi-seat airplanes for dropdown selector
     *
     * @return array [macimmat => "Modèle - Immat"]
     */
    public function get_selector_multiplace() {
        $this->db->select('macimmat, macmodele')
            ->from($this->table)
            ->where('actif', 1)
            ->where('macplaces >', 1)
            ->order_by('macmodele', 'asc');

        if ($this->section) {
            $this->db->where('club', $this->section_id);
        }

        $results = $this->db->get()->result_array();
        $selector = array('' => '');
        foreach ($results as $row) {
            $selector[$row['macimmat']] = $row['macmodele'] . ' - ' . $row['macimmat'];
        }
        return $selector;
    }

    /**
     * Delete an airplane with validation
     * Checks if the airplane is referenced in flight records before deletion
     * 
     * @param array $where - selection criteria
     * @return boolean - TRUE if deleted, FALSE if blocked
     */
    function delete($where = array()) {
        // Get macimmat from where clause
        if (!isset($where['macimmat'])) {
            // If no macimmat specified, can't validate - abort
            return FALSE;
        }
        
        $macimmat = $where['macimmat'];
        
        // Get CodeIgniter instance for language support
        $CI =& get_instance();
        $CI->lang->load('avions');
        
        // Check if airplane is referenced in flight records
        $references = array();
        
        // Check volsa.vamacid (airplane flights)
        $this->db->where('vamacid', $macimmat);
        $count = $this->db->count_all_results('volsa');
        if ($count > 0) {
            $references[] = $CI->lang->line('avion_delete_ref_volsa') . " ($count)";
        }
        
        // If there are references, block deletion with error message
        if (!empty($references)) {
            $CI->load->library('session');
            
            // Create detailed error message
            $error_msg = $CI->lang->line('avion_delete_blocked') . "\n\n";
            $error_msg .= $CI->lang->line('avion_delete_dependencies') . "\n";
            $error_msg .= "• " . implode("\n• ", $references);
            
            $CI->session->set_flashdata('error', $error_msg);
            $CI->session->set_flashdata('delete_failed_avion', $macimmat);
            return FALSE;
        }
        
        // If no references, proceed with deletion using parent method
        parent::delete($where);
        return TRUE;
    }
}

/* End of file */