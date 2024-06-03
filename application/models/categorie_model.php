<?php
if (!defined('BASEPATH'))
    exit ('No direct script access allowed');

/**
 * C'est un CRUD de base, la seule chose que fait cette classe
 * est de définir le nom de la table. Tous les méthodes sont
 * implémentés dans Common_Model
 *
 * @package models
 * @title Catégorie model
 *
 */
$CI = & get_instance();
$CI->load->model('common_model');

/**
 *
 * Catégorie des écritures
 * @author Frédéric
 *
 */
class Categorie_model extends Common_Model {
    public $table = 'categorie';
    protected $primary_key = 'id';

    /**
     *	Retourne le tableau tableau utilisé pour l'affichage par page
     *	@return objet		  La liste
     */
    public function select_page($nb = 1000, $debut = 0) {
        $res = $this->db->select('categorie.id as id, categorie.nom as nom, categorie.description as description, ' . 'categorie.parent, categorie.type, categorie_parent.nom as nom_parent')
        ->from('categorie, categorie as categorie_parent')
        ->where("categorie.parent = categorie_parent.id")
        //->limit($nb, $debut)
        ->get()->result_array();

        foreach ($res as $key => $row) {
            $res[$key]['image'] = $row['nom'];
        }
        $this->gvvmetadata->store_table("vue_categories", $res, $this->db->last_query());
        return $res;
    }

    /**
     * Retourne une chaine de caractère qui identifie une ligne de façon unique.
     * Cette chaine est utilisé dans les affichages.
     * Par défaut elle retourne la valeur de la clé, mais elle est conçue pour être
     * surchargée.
     */
    public function image($key) {
        if ($key == "")
            return "";
        $vals = $this->get_by_id('id', $key);
        if (array_key_exists('nom', $vals)) {
            return $vals['nom'];
        } else {
            return "catégorie inconnu $key";
        }
    }

}

/* End of file */