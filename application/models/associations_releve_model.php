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
     * Retrieve paginated bank statement associations for display
     * 
     * Fetches associations between bank statement accounts and GVV chart of accounts
     * with additional metadata for management interface display. Includes section
     * information and handles orphaned associations without linked GVV accounts.
     * 
     * @param int $nb Maximum number of results to return (default: 1000)
     * @param int $debut Starting offset for pagination (default: 0)
     * @return array Array of associations with metadata for table display
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
     * Retrieve GVV account ID associated with bank statement operation
     * 
     * Looks up the GVV chart of accounts ID that corresponds to a bank statement
     * operation identifier. Optionally filters by section for multi-club installations.
     * Used for automatic account assignment during reconciliation.
     * 
     * @param string $string Bank statement operation identifier
     * @param int $section_id Optional section ID for filtering (default: 0)
     * @return string GVV account ID, or empty string if no association found
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
     * Find association ID for bank statement operations without GVV account mapping
     * 
     * Retrieves the association ID for bank statement operations that have been
     * identified but not yet mapped to a specific GVV chart of accounts entry.
     * Used to identify unmapped operations that need manual account assignment.
     * 
     * @param string $string_releve Bank statement operation identifier
     * @return mixed Association ID if found, empty string otherwise
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
     * Generate human-readable identifier for bank statement association record
     * 
     * Creates a display string combining the association ID, bank statement operation
     * identifier, and operation type for use in user interfaces and logging.
     * 
     * @param string|int $key The association ID to generate image for
     * @return string Human-readable identifier or error message if not found
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