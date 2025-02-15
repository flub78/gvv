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
     * Retourne un hash qui peut-être utilisé dans un menu drop-down
     *
     * @param $where selection
     * @param $order ordre
     *            de tri
     */
    public function selector($where = array(), $order = "asc", $filter_section = FALSE) {
        return parent::selector($where, $order, TRUE);
    }
}

/* End of file */