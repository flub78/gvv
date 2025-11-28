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
     * Retrieve paginated associations for display in management interface
     * 
     * Fetches associations between bank statement operations and GVV accounting entries
     * with additional metadata for display. Includes section information and handles
     * orphaned associations without linked accounting entries.
     * 
     * @param int $nb Maximum number of results to return (default: 1000)
     * @param int $debut Starting offset for pagination (default: 0)  
     * @return array Array of associations with metadata for table display
     */
    public function select_page($nb = 1000, $debut = 0) {
        $this->load->model('comptes_model');
        $this->load->model('ecritures_model');

        $section = $this->gvv_model->section();

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
     * Create association if it doesn't already exist
     * 
     * Validates input data and creates a new association between a bank statement
     * operation and a GVV accounting entry if no matching association exists.
     * Prevents duplicate associations for the same string_releve and ecriture_id.
     * 
     * @param array $data Association data containing 'string_releve' and 'id_ecriture_gvv'
     * @return bool|int True if existing association found, new ID if created, false on failure
     */
    function check_and_create($data) {

        // Input validation
        if (empty($data['string_releve']) || !is_string($data['string_releve'])) {
            gvv_error("Invalid string_releve in check_and_create");
            return false;
        }
        
        if (empty($data['id_ecriture_gvv']) || !is_numeric($data['id_ecriture_gvv'])) {
            gvv_error("Invalid id_ecriture_gvv in check_and_create");
            return false;
        }

        // first check that there is already some matching elements
        $this->db
            ->where('string_releve', $data['string_releve'])
            ->where('id_ecriture_gvv', $data['id_ecriture_gvv']);
        $db_res = $this->db->get($this->table);
        $res = $this->get_to_array($db_res);

        if (!empty($res)) {
            // If we found matching elements, we can return them
            return true;
        }

        return $this->create($data);
    }

    /**
     * Generate human-readable identifier for association record
     * 
     * Creates a display string combining the association ID and bank statement
     * operation identifier for use in user interfaces and logging.
     * 
     * @param string|int $key The association ID to generate image for
     * @return string Human-readable identifier or error message if not found
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
     * Retrieve associations by bank statement operation identifier
     * 
     * Fetches all reconciliation associations for a specific bank statement operation
     * identified by its string_releve. Includes the full accounting entry data for
     * each associated GVV transaction. Supports multiple reconciliations per operation.
     * 
     * @param string $string_releve Bank statement operation identifier
     * @return array Array of associations with embedded accounting entry data
     */
    public function get_by_string_releve($string_releve) {
        
        // Input validation: ensure string_releve is not empty and has reasonable length
        if (empty($string_releve) || !is_string($string_releve)) {
            return [];
        }
        
        // Additional security: limit string length to prevent potential issues
        if (strlen($string_releve) > 500) {
            gvv_error("String releve too long: " . strlen($string_releve) . " characters");
            return [];
        }
        
        // CodeIgniter's where() method properly escapes parameters
        $this->db->where('string_releve', $string_releve);
        $this->db->group_by(['string_releve', 'id_ecriture_gvv']);
        $db_res = $this->db->get($this->table);
        $result = $this->get_to_array($db_res);
        // Return all elements for multiple reconciliations
        $rapprochements = $result;

        // maybe that I will need to fetch additional information from the ecriture
        // like the amount or the date
        foreach ($rapprochements as &$rapprochement) {
            try {
                $ecriture = $this->ecritures_model->get_by_id('id', $rapprochement['id_ecriture_gvv']);

                // Check if the ecriture actually exists
                if (empty($ecriture)) {
                    // Mark as orphaned - ecriture no longer exists
                    $rapprochement['ecriture_exists'] = false;
                    $rapprochement['ecriture'] = null;
                    gvv_info("Orphaned association found: string_releve={$string_releve}, id_ecriture_gvv={$rapprochement['id_ecriture_gvv']}");
                } else {
                    $rapprochement['ecriture_exists'] = true;
                    $rapprochement['ecriture'] = $ecriture;
                }
            } catch (Exception $ex) {
                gvv_error('Exception in get_by_string_releve: ' . $ex->getMessage());
                $rapprochement['ecriture_exists'] = false;
                $rapprochement['ecriture'] = null;
            }
        }
        // gvv_dump($rapprochements);
        return $rapprochements;
    }

    /**
     * Delete association by bank statement operation identifier
     * 
     * Removes all reconciliation associations for a specific bank statement operation
     * identified by its string_releve. Used when reconciliations need to be cleared
     * or redone for a specific bank operation.
     * 
     * @param string $string_releve Bank statement operation identifier
     * @return bool True on successful deletion, false on failure
     */
    public function delete_by_string_releve($string_releve) {
        // Input validation: ensure string_releve is not empty and has reasonable length
        if (empty($string_releve) || !is_string($string_releve)) {
            gvv_error("Invalid string_releve parameter for delete operation");
            return false;
        }
        
        // Additional security: limit string length to prevent potential issues
        if (strlen($string_releve) > 500) {
            gvv_error("String releve too long for delete: " . strlen($string_releve) . " characters");
            return false;
        }
        
        // CodeIgniter's where() method properly escapes parameters
        $this->db->where('string_releve', $string_releve);
        return $this->db->delete($this->table);
    }

    /**
     * Retrieve all associations for a specific GVV accounting entry
     * 
     * Fetches all bank statement reconciliations associated with a specific
     * GVV accounting entry. Used to check reconciliation status and display
     * reconciled bank operations for an accounting entry.
     * 
     * @param int $id_ecriture_gvv GVV accounting entry ID
     * @return array Array of associations for the specified accounting entry
     */
    public function get_rapproches($id_ecriture_gvv) {
        // Input validation
        if (empty($id_ecriture_gvv) || !is_numeric($id_ecriture_gvv)) {
            gvv_error("Invalid id_ecriture_gvv in get_rapproches");
            return [];
        }
        
        $this->db->where('id_ecriture_gvv', $id_ecriture_gvv);
        $db_res = $this->db->get($this->table);
        return $this->get_to_array($db_res);
    }

    /**
     * Delete all associations for a specific GVV accounting entry
     * 
     * Removes all bank statement reconciliations associated with a specific
     * GVV accounting entry. Used when an accounting entry is deleted or when
     * all its reconciliations need to be cleared.
     * 
     * @param int $id_ecriture_gvv GVV accounting entry ID
     * @return bool True on successful deletion, false on failure
     */
    function delete_rapprochements($id_ecriture_gvv) {
        // Input validation
        if (empty($id_ecriture_gvv) || !is_numeric($id_ecriture_gvv)) {
            gvv_error("Invalid id_ecriture_gvv in delete_rapprochements");
            return false;
        }
        
        $this->db->where('id_ecriture_gvv', $id_ecriture_gvv);
        return $this->db->delete($this->table);
    }
}

/* End of file */