<?php
if (!defined('BASEPATH'))
    exit ('No direct script access allowed');

/**
 *	Modèle des tickets
 *
 * @package models
 * @title Tickets model
 *
 */

$CI = & get_instance();
$CI->load->model('common_model');
$CI->load->model('vols_planeur_model');
$CI->load->model('achats_model');

class tickets_model extends Common_Model {
    public $table = 'tickets';
    protected $primary_key = 'id';

    /**
     *	Retourne le tableau tableau utilisé pour l'affichage par page
     *	@return array La liste
     */
    public function select_page($nb = 1000, $debut = 0, $selection = array ()) {
        global $CI;

        /*
         * Select `tickets`.`id` as id, `tickets`.`date` as date, `tickets`.`quantite`, `nom`, `tickets`.`description` as description, `tickets`.`pilote`, `achat`, `type`, `mnom`, `mprenom`
         * from `tickets`
         * INNER JOIN `membres` ON `tickets`.`pilote` = membres.mlogin
         * INNER JOIN `type_ticket` ON tickets.type = type_ticket.id
         */

        $select = 'tickets.id as id, tickets.date as date, tickets.quantite, nom, ' 
        		. 'tickets.description as description,  tickets.pilote, achat, type, mnom, mprenom';

         $result = $this->db->select($select)
         		->from("tickets")
        		->join("membres", 'tickets.pilote = membres.mlogin', 'inner')
         		->join('type_ticket', 'tickets.type = type_ticket.id', 'left')
         		->where($selection)
         		->limit($nb, $debut)
         		->order_by('tickets.date')->get()->result_array();
                  
        // enregistre la requête 
        $query = $this->db->last_query();

        foreach ($result as $key => $row) {
            $row['vol'] = '';

            if ($row['vol'] != '') {
                $image = $CI->vols_planeur_model->image($row['vol']);
                $result[$key]['vol_image'] = substr($image, 11);
            } else {
                $result[$key]['vol_image'] = '';
            }
            if ($row['pilote'] != '') {
                $image = $CI->membres_model->image($row['pilote']);
                $result[$key]['pilote_image'] = $image;
            } else {
                $result[$key]['pilote_image'] = '';
            }
            $result[$key]['image'] = $row['date'] . ' ' . $row['quantite'] . ' ' . $row['nom'];
        }

        $this->gvvmetadata->store_table("vue_tickets", $result, $query);
        return $result;
    }

    /**
     *	Retourne les totaux pour le solde
     *	@return objet
     */
    public function select_totaux($selection = array (), $group_by = 'codec') {
        $where = "tickets.pilote = membres.mlogin and tickets.type = type_ticket.id";

        $result = $this->db->select("pilote, type, nom, sum(quantite) as solde, mnom, mprenom, CONCAT(mnom, ' ', mprenom) as nom_prenom", FALSE)
        ->from('tickets, membres, type_ticket')->where($where)->where($selection)->group_by($group_by)->order_by('mnom, mprenom')->get()->result_array();

        $this->gvvmetadata->store_table("vue_solde_tickets", $result);
        return $result;
    }

    /**
     *	Retourne le solde de ticket par pilote
     *	@return objet
     */
    public function solde($pilote, $type = 0) {
        $selection = array (
            'pilote' => $pilote,
            'type' => $type
        );

        $result = $this->db->select('pilote, type, nom, sum(quantite) as solde')
        ->from('tickets, type_ticket')
        ->where($selection)
        ->where("tickets.type = type_ticket.id")
        ->get()->result_array();

        return isset ($result[0]['solde']) ? $result[0]['solde'] : 0;
    }

    /**
     * Detruit toute les operations sur les tickets correspondant à la selection
     * @param unknown_type $where
     */
    public function delete_all($where = array ()) {
        $this->db->delete($this->table, $where);
    }

    /**
     * Utilisé lors des migration de données
     * @deprecated
     */
    public function select_raw() {
    	$where = array ();
    	return $this->db->select("*")->from("tickets")->get()->result_array();
    }
    
}

/* End of file */