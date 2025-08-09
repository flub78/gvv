<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 *	Accès base Associations Relevé
 *
 *  C'est un CRUD de base, la seule chose que fait cette classe
 *  est de définir le nom de la table. Tous les méthodes sont 
 *  implémentées dans Common_Model
 */
class Associations_releve_model extends Common_Model {
    public $table = 'associations_releve';
    protected $primary_key = 'id';

    /**
     *	Retourne le tableau tableau utilisé pour l'affichage par page
     *	@return objet		  La liste
     */
    public function select_page($nb = 1000, $debut = 0) {
        $this->load->model('comptes_model');

        $db_res = $this->db
            ->select('a.id, a.string_releve, a.type, a.id_compte_gvv, c.club')
            ->from("associations_releve as a")
            ->join("comptes as c", "a.id_compte_gvv = c.id", "left");

        $section = $this->gvv_model->section();
        if ($section) {
            $this->db->where('c.club', $section['id']);
        }

        $db_res = $this->db->get();
        $select = $this->get_to_array($db_res);

        foreach ($select as $key => $row) {
            $image = $this->comptes_model->image($row["id_compte_gvv"]);
            $select[$key]['nom_compte'] = $image;
        }
        // Get unassigned associations_releve records
        $db_orphans = $this->db->select('*')
                               ->from($this->table)
                               ->where('id_compte_gvv IS NULL')
                               ->get();
        $orphans = $this->get_to_array($db_orphans);

        // Add empty values for the missing fields in orphan records
        foreach ($orphans as &$row) {
            $row['club'] = '';
            $row['nom_compte'] = '';
        }

        // Merge both result sets
        $select = array_merge($select, $orphans);

        $this->gvvmetadata->store_table("vue_associations_releve", $select);
        return $select;
    }

    /**
     * Récupère l'identifiant du compte GVV associé à un relevé
     * 
     * @param int $releve_id Identifiant du compte dans le relevé
     * @param int $section_id Identifiant optionnel de la section
     * @return string Identifiant du compte GVV, ou chaîne vide si non trouvé
     */
    public function get_gvv_account($string, $section_id = 0) {

        $this->db->select('associations_releve.id_compte_gvv')
            ->from($this->table)
            ->where('string_releve', $string);

        if ($section_id) {
            $this->db->join('comptes', 'comptes.id = associations_releve.id_compte_gvv')
                ->where('comptes.club', $section_id);
        }

        $result = $this->db->get()->row();
        return ($result) ? $result->id_compte_gvv : '';
    }

    /**
     * Retrieves the id of an association where id_compte_gvv is NULL for a given string_releve
     * 
     * @param string $string_releve The string_releve value to search for
     * @return mixed Returns the association ID if found, empty string otherwise
     */
    public function associated_to_null($string_releve) {
        $this->db->select('id')
            ->from($this->table)
            ->where('string_releve', $string_releve)
            ->where('id_compte_gvv IS NULL');

        $result = $this->db->get()->row();
        return ($result) ? $result->id : '';
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
        if (array_key_exists('id', $vals) && array_key_exists('string_releve', $vals)) {
            return $vals['id'] . " - " . $vals['string_releve'] . " : " . $vals['type'];
        } else {
            return "association relevé inconnue $key";
        }
    }
}

/* End of file */