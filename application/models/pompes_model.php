<?php
if (!defined('BASEPATH'))
    exit ('No direct script access allowed');

/**
 *	Pompes model
 *
 *  C'est un CRUD de base.
 */

$CI = & get_instance();
$CI->load->model('common_model');

class Pompes_model extends Common_Model {
    public $table = 'pompes';
    protected $primary_key = 'pid';
    protected $CI;

    /**
     *
     * Constructor
     */
    function __construct() {
        parent :: __construct();
        $this->CI = & get_instance();
        //$this->load->model("ecritures_model");
        //$this->load->model("membres_model");
    }

    /**
     *	Retourne le tableau tableau utilisé pour l'affichage par page
     *	@return objet		  La liste
     */
    public function select_page($selection = array ()) {
    	$select='pid, pnum, pdatemvt, ppilid, pmacid, ptype, pqte, ppu, pprix, pdesc, mlogin';
    	$select .= ', concat(mprenom," ", mnom) as utilisateur' ;
        $result = $this->db->select($select, FALSE)->from('pompes, membres')->where($selection)->where('ppilid = mlogin')
        //  ->limit($nb, $debut)
    ->order_by('pdatemvt, utilisateur')->get()->result_array();

  
        // print_r($result);
        $this->gvvmetadata->store_table("vue_pompes", $result, $this->db->last_query());
        return $result;
    }

 

    /**
     *	Retourne les totaux pour l'affichage de la liste
     *	@return objet
     */
    public function select_totaux($selection = array ()) //, $group_by = 'codec')
    {
        $result = array ();
        $this->db->trans_start();

        $result['totaux'] = $this->db->select('SUM(pqte) as total_qte,SUM(pprix) as total_prix ')->from($this->table)->where($selection)->get()->result_array();
        $this->db->trans_complete();
        return $result;
    }

 
 
    /**
     * Ajoute un mouvement à la pompe
     */
    public function create($data) {
    	$this->load->model('tarifs_model');
        if ($data['ptype']=='D' && $data['pqte']>0) $data['pqte']*=-1;		// les quantités débités sont négatives
        $data['pmacid']=strtoupper($data['pmacid']);
        
        // récupération du prix à la date du mouvement.
         $product_info = $this->tarifs_model->get_tarif($data['ppu'], $data['pdatemvt']);
	        $data['pprix'] = $product_info['prix']*$data['pqte']*-1;
        
        if ($this->db->insert($this->table, $data)) {
            $id = $this->db->insert_id();
            $data['pid'] = $id;
            if ($data['ptype']=='D') $this->facture($data);	// on ne facture pas les remplissages ou les ajustements 
            return $id;
        } else {
            return FALSE;
        }
    }
    
        /*
     * Facture le mouvement pompe
     *
     *  @param $vol
     */
    public function facture($mvtpompe) {
        // Active la facturation
        // ### à faire facture générique
        
        $this->load->library("Facturation", '', 'facturation_generique');
        $club = $this->config->item('club');
        if ($club) {
            $facturation_module = "Facturation_" . $club;
            $this->load->library($facturation_module, '', "facturation_club");
            $data['logs'] = $this->facturation_club->facture_pompes($mvtpompe);
        } /*
        else {
            $data['logs'] = $this->facturation_generique->facture_pompe($mvtpompe);
        }
        */
    }
    
    
    /**
     *	Mise à jour mvt pompe
     *
     *	@param integer $id	$id de l'élément
     *	@param string  $data donnée à remplacer
     *	@return bool		Le résultat de la requête
     */
    public function update($keyid, $data) {
    	$this->load->model('tarifs_model');
        // detruit les lignes d'achat correspondante
        $this->delete_facture($data[$keyid]);
        
        if ($data['ptype']=='D' && $data['pqte']>0) $data['pqte']*=-1;		// les quantités débités sont négatives

        // récupération du prix à la date du mouvement.
        $product_info = $this->tarifs_model->get_tarif($data['ppu'], $data['pdatemvt']);
        $data['pprix'] = $product_info['prix']*$data['pqte']*-1;
        

        // MAJ du vol
        $keyvalue = $data[$keyid];
        $this->db->where($keyid, $keyvalue);
        $this->db->update($this->table, $data);

        // Nouvelle facturation
        if ($data['ptype']=='D') $this->facture($data);	// on ne facture pas les remplissages ou les ajustements 

    }    
     
    function delete($where = array ()) {

        // detruit les lignes d'achat correspondante

        $selection = $this->select_all($where);
        foreach ($selection as $row) {
            $this->delete_facture($row['pid']);
        }
        // Detruit le mvt dans la table pompe
        $this->db->delete($this->table, $where);
    }     
     
     
     
   /*
     * Supprime les elements de mvt pompe
     * 
     * @param $id identifiant du vol
     */
    public function delete_facture($id) {
        $this->load->model('achats_model');
        $achats = $this->achats_model->delete(array (
            'mvt_pompe' => $id
        ));
    }
   

}

/* End of file planeurs_model.php */
/* Location: ./application/models/planeurs_model.php */