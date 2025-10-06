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
    public function select_page($per_page = 0, $premier = 0, $selection = []) {
        // Build a join with sections to also retrieve the section name
        $this->db->select($this->table . '.id, ' . $this->table . '.referenced_table, ' . $this->table . '.referenced_id, ' . $this->table . '.description, ' . $this->table . '.file, ' . $this->table . '.club, sections.nom as section');
        $this->db->from($this->table);
        $this->db->join('sections', $this->table . '.club = sections.id', 'left');
        if (!empty($selection)) {
            $this->db->where($selection);
        }
        // select per section (like in other models)
        if ($this->section) {
            $this->db->where('sections.id', $this->section_id);
        }
        // filter by year selected in session based on file path ./uploads/attachments/<year>/...
        $year = $this->session->userdata('year');
        if ($year) {
            $this->db->like($this->table . '.file', '/attachments/' . $year . '/', 'both');
        }
 
        $query = $this->db->get();
        $select = $this->get_to_array($query);

        foreach ($select as $key => $elt) {
            $referenced_table = $elt['referenced_table'];
            $referenced_id = $elt['referenced_id'];

            if ($referenced_table == 'ecritures') {
                $referenced_table = 'compta';
            }

            // Create the link
            $select[$key]['referenced_id'] = '<a href="'
                . base_url()
                . 'index.php/'
                . $referenced_table . '/edit/' . $referenced_id . '">' .
                $referenced_id . '</a>';
        }

        $this->gvvmetadata->store_table("vue_attachments", $select);
        return $select;
    }

    /**
     * Build a year selector from the attachments file path (./uploads/attachments/<year>/...)
     */
    public function get_available_years() {
        $years = [];
        // Query distinct years from file path using MySQL string functions
        $this->db->select("DISTINCT(LEFT(SUBSTRING_INDEX(file, 'attachments/', -1), 4)) as year", false);
        $this->db->from($this->table);
        $this->db->like('file', 'attachments/');
        $this->db->order_by('year', 'DESC');
        $query = $this->db->get();
        if ($query) {
            foreach ($query->result_array() as $row) {
                $y = $row['year'];
                if ($y && ctype_digit($y)) {
                    $years[$y] = $y;
                }
            }
        }
        // Ensure current year exists
        $current_year = date('Y');
        if (!isset($years[$current_year])) {
            $years[$current_year] = $current_year;
        }
        return $years;
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