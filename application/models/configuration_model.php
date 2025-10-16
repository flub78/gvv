<?php
/**
 * Configuration model - manages application settings with multi-language and multi-club support.
 */
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

class Configuration_model extends Common_Model {
    public $table = 'configuration';
    protected $primary_key = 'id';

    /**
     * Returns configuration records for table display
     *
     * @param int $nb Maximum records (default 1000)
     * @param int $debut Starting offset (default 0)
     * @return object Database result
     */
    public function select_page($nb = 1000, $debut = 0) {
        $select = $this->select_columns('id, cle, valeur, file, lang, categorie, club, description');
        $this->gvvmetadata->store_table("vue_configuration", $select);
        return $select;
    }

    /**
     * Returns human-readable identifier for configuration record
     *
     * @param string|int $key Configuration ID
     * @return string Format: "key description"
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
     * Retrieves configuration value with priority: club+lang > lang > global
     *
     * Performs up to 3 queries to find most specific match.
     *
     * @param string $key Configuration key
     * @param string|null $lang Language code (null = current language)
     * @return mixed Configuration value or null
     */
    public function get_param($key, $lang = null) {

        if ($lang === null) {
            $lang = $this->config->item('language');
        }

        // Try global match first
        $this->db->where('cle', $key);
        $query = $this->db->get($this->table);

        // Narrow by language if multiple results
        if ($query->num_rows() > 1) {
            $this->db->where('cle', $key);
            $this->db->where('lang', $lang);
            $query = $this->db->get($this->table);
        }

        // Narrow by section/club if still multiple
        if ($query->num_rows() > 1) {
            $this->db->where('cle', $key);
            $section = $this->gvv_model->section();
            $this->db->where('club', $section['id']);
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
