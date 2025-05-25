<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 *	Accès base Clotures
 *
 *  C'est un CRUD de base, la seule chose que fait cette classe
 *  est de définir le nom de la table. Tous les méthodes sont 
 *  implémentés dans Common_Model
 */
class Clotures_model extends Common_Model {
    public $table = 'clotures';
    protected $primary_key = 'id';

    /**
     *	Retourne le tableau tableau utilisé pour l'affichage par page
     *	@return objet		  La liste
     */
    public function select_page($nb = 1000, $debut = 0) {
        $select = $this->select_columns('id, date, section, description');
        $this->gvvmetadata->store_table("vue_clotures", $select);
        return $select;
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
        if (array_key_exists('id', $vals) && array_key_exists('section', $vals)) {
            return $vals['date'] . " " . $vals['section'] . " " . $vals['description'];
        } else {
            return "terrain inconnu $key";
        }
    }



    /**
     * Retrieves the most recent freeze date for a given section
     * 
     * @param bool $localized Whether to return the date in localized format
     * @param string $section_id Optional section identifier to filter the freeze date
     * @return string The freeze date, either in database or localized format
     */
    public function freeze_date($localized = false, $section_id = "") {

        $this->db->select('*');
        $this->db->from('clotures');
        if ($section_id = "") {
            $this->db->where('section', $section_id);
        } else {
            $section = $this->gvv_model->section();
            if ($section) {
                $this->db->where('section', $section['id']);
            }
        }
        $this->db->order_by('date', 'DESC');
        $this->db->order_by('id', 'DESC'); // En cas d'égalité de date
        $this->db->limit(1);

        $query = $this->db->get();

        if ($query->num_rows() <= 0) {
            return "";
        }

        $elt = $query->row();

        if ($localized)
            return date_db2ht($elt->date);
        else
            return $elt->date;
    }

    /**
     * Check if the freeze date is after or before a given date
     * parameters:
     *      $date : a localized string date
     * return: a boolean value
     */
    public function before_freeze_date($date) {

        $freeze_date = $this->freeze_date();
        if ($freeze_date)
            return ($freeze_date >= $date);
        else
            return true;
    }

    /**
     * Creates a new freeze date record
     * 
     * @param string $date The date for the freeze record in database format
     * @param string $description Optional description for the freeze date
     */
    public function create_freeze_date($date, $description = "") {
        $data['date'] = $date;
        $data['description'] = $description;
        $section = $this->gvv_model->section();

        if ($section) {
            $data['section'] = $section['id'];
        }
        parent::create($data);
    }

    public function section_freeze_dates($localized = false) {
        $this->db->select('s.id, s.nom, s.description, MAX(c.date) as latest_cloture_date');
        $this->db->from('sections s');
        $this->db->join('clotures c', 's.id = c.section', 'left');
        $this->db->group_by('s.id, s.nom, s.description');
        $this->db->order_by('s.nom', 'ASC');

        $query = $this->db->get();

        $res = $query->result();
        if ($localized) {
            foreach ($res as $row) {
                if ($row->latest_cloture_date) {
                    $row->latest_cloture_date = date_db2ht($row->latest_cloture_date);
                }
            }
        }

        return $res;
    }
}

/* End of file */