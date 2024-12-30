<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 *	Accès base Attachments
 *
 *  C'est un CRUD de base, la seule chose que fait cette classe
 *  est de définir le nom de la table. Tous les méthodes sont 
 *  implémentés dans Common_Model
 */
class Attachments_model extends Common_Model {
    public $table = 'attachments';
    protected $primary_key = 'id';

    /**
     *	Retourne le tableau tableau utilisé pour l'affichage par page
     *	@return objet		  La liste
     */
    public function select_page($nb = 1000, $debut = 0) {
        $select = $this->select_columns('referenced_table, referenced_id, user_id, filename, description, file');

        $this->gvvmetadata->store_table("vue_attachments", $select);
        return $select;
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
        if (array_key_exists('id', $vals) && array_key_exists('nom', $vals)) {
            return $vals['id'] . " " . $vals['nom'];
        } else {
            return "attachment inconnu $key";
        }
    }

    /** 
     * For some reasons unit test library can only be invoked directly from the controller.
     * This test returns an array of test results.
     */
    public function test() {
        $res = [];

        $res[] = ["description" => "Model attachments", "result" => true];

        // Count elements in attachments table
        $initial_count = $this->db->count_all($this->table);
        $res[] = ["description" => "Initial count attachments: " . $initial_count, "result" => true];

        // Insert a dummy element
        $data = array(
            'referenced_table' => 'ecritures',
            'referenced_id' => '10',
            'user_id' => 'fpeignot',
            'filename' => 'asterix.jpeg',
            'description' => 'Facture Asterix',
            'file' => 'asterix.jpeg'
        );
        $insert_result = $this->db->insert($this->table, $data);
        $last_id = $this->db->insert_id();

        $count = $this->db->count_all($this->table);

        $res[] = ["description" => "Insert returns true", "result" => $insert_result];
        $res[] = ["description" => "Attachment created", "result" => ($count == $initial_count + 1)];

        // Get last inserted id
        $res[] = ["description" => "Last inserted ID: " . $last_id, "result" => ($last_id > 0)];

        // Get last inserted element
        $last = $this->get_by_id('id', $last_id);

        $res[] = ["description" => "Last element id", "result" => ($last['id'] == $last_id)];
        $res[] = ["description" => "Last element referenced_table", "result" => ($last['referenced_table'] == 'ecritures')];
        $res[] = ["description" => "Last element referenced_id", "result" => ($last['referenced_id'] == '10')];

        // Delete last inserted element
        $delete_result = $this->db->delete($this->table, array('id' => $last_id));
        $res[] = ["description" => "Delete returns true", "result" => $delete_result];

        // Verify deletion
        $count_after_delete = $this->db->count_all($this->table);
        $res[] = ["description" => "Attachment deleted", "result" => ($count_after_delete == $initial_count)];

        return $res;
    }
}

/* End of file */