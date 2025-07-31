<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 *	Configuration model
 *
 *  C'est un CRUD de base, la plupart des méthodes sont 
 *  implémentés dans Common_Model.
 * 
 *  reviewed by: copilot on 2025-07-31
 */
class Configuration_model extends Common_Model {
    public $table = 'configuration';
    protected $primary_key = 'id';

    /**
     *	Retourne le tableau tableau utilisé pour l'affichage par page
     *	@return objet		  La liste
     */
    public function select_page($nb = 1000, $debut = 0) {
        $select = $this->select_columns('id, cle, valeur, lang, categorie, club, description');
        $this->gvvmetadata->store_table("vue_configuration", $select);
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
        if (array_key_exists('cle', $vals) && array_key_exists('valeur', $vals)) {
            $description = array_key_exists('description', $vals) ? $vals['description'] : '';
            return $vals['cle'] . " " . $description;
        } else {
            return "configuration inconnue $key";
        }
    }

    /**
     * Retrieves a configuration parameter by its key.
     * 
     * @param string $key The key identifier of the configuration parameter
     * @param string|null $lang Optional language code for localized parameters
     * @return mixed The value of the configuration parameter
     */
    public function get_param($key, $lang = null) {
        if ($lang === null) {
            $lang = $this->config->item('language');
        }

        $section = $this->gvv_model->section();

        // First try with specific section
        $this->db->where('cle', $key);
        $this->db->where('lang', $lang);
        if ($section) {
            $this->db->where('club', $section['id']);
        }
        $query = $this->db->get($this->table);
        
        // If not found, try with club = null (global configuration)
        if ($query->num_rows() == 0) {
            $this->db->where('cle', $key);
            $this->db->where('lang', $lang);
            $this->db->where('club', null);
            $query = $this->db->get($this->table);
        }

        if ($query->num_rows() > 0) {
            return $query->row()->valeur;
        } else {
            return null;
        }
    }   
}

/* End of file */