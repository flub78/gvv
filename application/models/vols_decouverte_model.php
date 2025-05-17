<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');
$CI->load->model('avions_model');
$CI->load->model('planeurs_model');
$CI->load->model('tarifs_model');

/**
 *	Accès base vols_decouverte
 *
 *  C'est un CRUD de base, la seule chose que fait cette classe
 *  est de définir le nom de la table. Tous les méthodes sont 
 *  implémentés dans Common_Model
 */
class Vols_decouverte_model extends Common_Model {
    public $table = 'vols_decouverte';
    protected $primary_key = 'id';

    /**
     *	Retourne le tableau tableau utilisé pour l'affichage par page
     *	@return objet		  La liste
     */
    public function select_page($nb = 1000, $debut = 0) {
        // select per section
        if ($this->section) {
            // select elements from vols_decouverte where product has club equal to $this->section_id
            $db_res = $this->db
                ->select('id, date_vente, club, product, beneficiaire, de_la_part, beneficiaire_email, date_vol, urgence, cancelled, paiement, participation')
                ->from('vols_decouverte')
                ->where('club', $this->section_id)
                ->get();
            $select = $this->get_to_array($db_res);
        } else {
            $select = $this->select_columns('id, date_vente, club, product, beneficiaire, de_la_part, beneficiaire_email, date_vol, urgence, cancelled, paiement, participation');
        }
        $i = 0;
        foreach ($select as $elt) {
            $product = $elt['product'];
            $tarif = $this->tarifs_model->get_by_id('reference', $product);
            if ($tarif) {
                $select[$i]['product'] = $tarif['description'];
            }

            // Compute validity date (1 year from date_vente)
            $date_vente = new DateTime($elt['date_vente']);
            $select[$i]['validite'] = $date_vente->modify('+1 year')->format('Y-m-d');

            $i += 1;
        }
        $this->gvvmetadata->store_table("vue_vols_decouverte", $select);
        return $select;
    }

    /**
     * Ajoute un élément
     *
     * @param $data hash
     *            des valeurs
     */
    public function create($data) {
        $data['saisie_par'] = $this->dx_auth->get_username();
        $year = date('Y', strtotime($data['date_vente']));

        // les VD sont numérotés de façon croissante chaque année
        $highest_id = $this->highest_id_by_year($year);
        $data['id'] = $highest_id   + 1;

        parent::create($data);
    }

    /**
     * Retourne une chaîne de caractère qui identifie une ligne de façon unique.
     * Cette chaîne est utilisé dans les affichages.
     * Par défaut elle retourne la valeur de la clé, mais elle est conçue pour être
     * surchargée.
     */
    public function image($key) {
        if ($key == "")
            return "";
        $vals = $this->get_by_id('id', $key);
        if (array_key_exists('date_vente', $vals) && array_key_exists('beneficiaire', $vals)) {
            return $vals['date_vente'] . " " . $vals['beneficiaire'];
        } else {
            return "vols_decouverte inconnu $key";
        }
    }

    /**
     * Suivant que la section planeur est active ou pas
     * le selecteur retourne
     *      - toutes les machines actives
     *      - les planeurs biplace actifs
     */
    public function machine_selector() {
        if ($this->section) {
            // var_dump($this->section['nom']); exit;
            if ($this->section['nom'] == "Planeur") {
                return $this->planeurs_model->selector(array('actif' => 1, 'mpbiplace' => 2));
            } else {
                return $this->avions_model->selector(array('actif' => 1));
            }
        }
    }

    /**
     * Retrieves the highest ID for a given year from the vols_decouverte table
     *
     * @param int $year The year to search for the highest ID
     * @return int The highest ID found for the specified year
     */
    function highest_id_by_year($year) {

        $min_id = $year * 1000;
        $max_id = ($year + 1) * 1000 - 1;

        $this->db->select_max('id', 'highest_id');
        $this->db->from('vols_decouverte');
        $this->db->where('id >=', $min_id);
        $this->db->where('id <=', $max_id);
        $query = $this->db->get();

        // Check if any results were found
        if ($query->num_rows() > 0 && $query->row()->highest_id !== null) {
            return $query->row()->highest_id;
        } else {
            return ($year - 2000) * 1000;
        }
    }
}/* End of file */