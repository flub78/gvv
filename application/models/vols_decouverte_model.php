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
        $current_year = date('Y');

        // les VD sont numérotés de façon croissante à partir de l'année courante
        $count =  $this->gvv_model->count(array("YEAR(date_vente)" => $current_year));
        $data['id'] =  intval($current_year . "00" . $count) + 1;
        // pour prendre en compte les suppressions
        // qui ne devraient pas arriver
        while ($this->get_by_id('id', $data['id'])) {
            $data['id'] += 1;
        }
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
}
/* End of file */