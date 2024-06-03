<?php
if (!defined('BASEPATH'))
    exit ('No direct script access allowed');

/**
 *	events_types_model CRUD pattern.
 *
 * Everything is done in Common_Model except the table
 * name declaration
 */

$CI = & get_instance();
$CI->load->model('common_model');

/**
 * Modèle pour la gestion des types d'événements
 */
 class Events_types_model extends Common_Model {
    public $table = 'events_types';
    protected $primary_key = 'id';

    /**
     * Retourne une chaine de caractère qui identifie une ligne de façon unique.
     * Cette chaine est utilisée dans les affichages.
     * Par défaut elle retourne la valeur de la clé, mais elle est conçue pour être
     * surchargée.
     */
    public function image($key) {
        if ($key == "")
            return "";
        $vals = $this->get_by_id('id', $key);
        if (array_key_exists('name', $vals)) {
            return $vals['name'];
        } else {
            return "événement inconnu $key";
        }
    }

    public function select_page($nb = 1000, $debut = 0) {
         
        $columns = 'id, name, activite, en_vol, multiple, expirable, ordre, annual';
        
        $select = $this->db
        ->select($columns)
        ->from($this->table)
        ->order_by('activite, ordre')
        // ->limit($nb, $debut)
        ->get()->result_array();
        
        $this->eventstypesmetadata->store_table("vue_events_types", $select);

        gvv_debug("sql: " . $this->db->last_query());
        
        return $select;

    }

}
?>