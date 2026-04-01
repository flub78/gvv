<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Modèle — Produits de cotisation (UC3)
 *
 * Gère le catalogue des produits de cotisation par section.
 * Utilisé par le pilote pour choisir et payer sa cotisation en ligne.
 *
 * Table : cotisation_produits
 */
class Cotisation_produits_model extends common_model {

    public $table = 'cotisation_produits';
    public $primary_key = 'id';

    function __construct() {
        parent::__construct();
    }

    /**
     * Retourne les produits actifs pour une section (affichage pilote).
     */
    public function get_active_for_section($section_id) {
        return $this->db
            ->where('section_id', (int) $section_id)
            ->where('actif', 1)
            ->order_by('annee', 'DESC')
            ->order_by('libelle', 'ASC')
            ->get($this->table)->result_array();
    }

    /**
     * Retourne tous les produits pour une section (admin).
     */
    public function get_all_for_section($section_id) {
        return $this->db
            ->where('section_id', (int) $section_id)
            ->order_by('annee', 'DESC')
            ->order_by('libelle', 'ASC')
            ->get($this->table)->result_array();
    }

    /**
     * Crée un nouveau produit de cotisation.
     *
     * @param array $data Clés : section_id, libelle, montant, annee, compte_cotisation_id, created_by
     * @return int|false  ID du produit créé ou false
     */
    public function create($data) {
        $now = date('Y-m-d H:i:s');
        $row = array(
            'section_id'           => (int) $data['section_id'],
            'libelle'              => (string) $data['libelle'],
            'montant'              => (float) $data['montant'],
            'annee'                => (int) $data['annee'],
            'compte_cotisation_id' => (int) $data['compte_cotisation_id'],
            'actif'                => 1,
            'created_at'           => $now,
            'updated_at'           => $now,
            'created_by'           => isset($data['created_by']) ? $data['created_by'] : null,
            'updated_by'           => isset($data['created_by']) ? $data['created_by'] : null,
        );
        $this->db->insert($this->table, $row);
        $id = $this->db->insert_id();
        return $id ? (int) $id : false;
    }

    /**
     * Bascule l'état actif/inactif d'un produit.
     */
    public function toggle_actif($id, $username) {
        $current = $this->db->where('id', (int) $id)->get($this->table)->row_array();
        if (!$current) {
            return false;
        }
        $new_actif = $current['actif'] ? 0 : 1;
        $this->db->where('id', (int) $id)->update($this->table, array(
            'actif'      => $new_actif,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $username,
        ));
        return true;
    }
}
