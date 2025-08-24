<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Accès base Associations Ecriture
 * CRUD de base, la seule chose que fait cette classe est de définir le nom de la table.
 * Tous les méthodes sont implémentées dans Common_Model.
 */
class Associations_ecriture_model extends Common_Model {
    public $table = 'associations_ecriture';
    protected $primary_key = 'id';

    /**
     * Retourne le tableau pour l'affichage par page
     * @return objet La liste
     */
    public function select_page($nb = 1000, $debut = 0) {
        $this->load->model('comptes_model');

        $section = $this->gvv_model->section();

        // $db_res = $this->db
        //     ->select('a.id, a.string_releve, a.id_ecriture_gvv, c.club')
        //     ->from("associations_ecriture as a")
        //     ->join("comptes as c", "a.id_ecriture_gvv = c.id", "left");

        $db_res = $this->db
            ->select('a.id, a.string_releve, a.id_ecriture_gvv, e.club, s.nom as nom_section')
            ->from("associations_ecriture as a")
            ->join("ecritures as e", "a.id_ecriture_gvv = e.id", "left")
            ->join("sections as s", "e.club = s.id", "left");

        if ($section) {
            $this->db->where('s.id', $section['id']);
        }

        $db_res = $this->db->get();
        $select = $this->get_to_array($db_res);

        foreach ($select as $key => $row) {
            $image = $this->ecritures_model->image($row["id_ecriture_gvv"]);
            $select[$key]['image'] = $image;
        }

        // Get unassigned ecriture
        $db_orphans = $this->db->select('*')
            ->from($this->table)
            ->where('id_ecriture_gvv IS NULL')
            ->get();
        $orphans = $this->get_to_array($db_orphans);

        foreach ($orphans as &$row) {
            $row['nom_section'] = '';
            $row['id_ecriture_gvv'] = '';
            $row['image'] = 'Null';
        }

        $select = array_merge($select, $orphans);
        // gvv_dump($select);

        $this->gvvmetadata->store_table("vue_associations_ecriture", $select);
        return $select;
    }

    /**
     * Retourne une chaîne qui identifie une ligne de façon unique.
     */
    public function image($key) {
        if ($key == "")
            return "";
        $vals = $this->get_by_id('id', $key);
        if (array_key_exists('id', $vals) && array_key_exists('string_releve', $vals)) {
            return $vals['id'] . " - " . $vals['string_releve'];
        } else {
            return "association inconnue $key";
        }
    }

    /**
     * Retourne les associations d'écriture par la chaîne de relevé.
     * @param string $string_releve
     * @return array
     */
    public function get_by_string_releve($string_releve) {
        $this->db->where('string_releve', $string_releve);
        $db_res = $this->db->get($this->table);
        return $this->get_to_array($db_res);
    }

    /**
     * Supprime une association d'écriture par la chaîne de relevé.
     * @param string $string_releve
     * @return bool
     */
    public function delete_by_string_releve($string_releve) {
        $this->db->where('string_releve', $string_releve);
        return $this->db->delete($this->table);
    }

    public function get_rapproches($id_ecriture_gvv) {
        $this->db->where('id_ecriture_gvv', $id_ecriture_gvv);
        $db_res = $this->db->get($this->table);
        return $this->get_to_array($db_res);
    }

    function delete_rapprochements($id_ecriture_gvv) {
        $this->db->where('id_ecriture_gvv', $id_ecriture_gvv);
        return $this->db->delete($this->table);
    }
}

/* End of file */