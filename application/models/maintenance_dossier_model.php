<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Maintenance Dossier Model
 *
 * Handles the association of a maintenance program to a maintainable
 * entity (aircraft or equipment), with a lifecycle (ouvert / suspendu /
 * cloture / abandonne) -- miroir exact de Formation_inscription_model.
 *
 * entite_type/entite_id is a polymorphic key (no native FK possible) :
 * entite_exists() validates the referenced entity at the application
 * level.
 *
 * @see doc/prds/maintenance_aeronefs_prd.md (EF3)
 * @see doc/design_notes/maintenance_aeronefs_design.md
 */
class Maintenance_dossier_model extends Common_Model {
    public $table = 'maintenance_dossiers';
    protected $primary_key = 'id';

    function __construct() {
        parent::__construct();
    }

    /**
     * Get a dossier by its ID
     *
     * @param int $id Dossier ID
     * @return array Dossier data or empty array if not found
     */
    public function get($id) {
        return $this->get_by_id('id', $id);
    }

    /**
     * Get dossier with full details (program and mecano referent info)
     *
     * @param int $id Dossier ID
     * @return array Dossier with related data
     */
    public function get_full($id) {
        $this->db->select('d.*, p.code as programme_code, p.titre as programme_titre,
            m.mnom as mecano_nom, m.mprenom as mecano_prenom')
            ->from($this->table . ' d')
            ->join('maintenance_programmes p', 'd.programme_id = p.id', 'left')
            ->join('membres m', 'd.mecano_referent_id = m.mlogin', 'left')
            ->where('d.id', $id);

        $result = $this->db->get()->row_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result ?: array();
    }

    /**
     * Get all dossiers for a maintainable entity
     *
     * @param string $entite_type 'aeronef' or 'equipement'
     * @param string $entite_id Entity ID (macimmat or maintenance_equipements.id)
     * @param string|null $statut Optional status filter
     * @return array List of dossiers
     */
    public function get_by_entite($entite_type, $entite_id, $statut = null) {
        $this->db->select('d.*, p.code as programme_code, p.titre as programme_titre')
            ->from($this->table . ' d')
            ->join('maintenance_programmes p', 'd.programme_id = p.id', 'left')
            ->where('d.entite_type', $entite_type)
            ->where('d.entite_id', $entite_id);

        if ($statut) {
            $this->db->where('d.statut', $statut);
        }

        $this->db->order_by('d.date_ouverture', 'desc');
        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Get open dossiers for a maintainable entity
     *
     * @param string $entite_type 'aeronef' or 'equipement'
     * @param string $entite_id Entity ID
     * @return array List of open dossiers
     */
    public function get_ouverts($entite_type, $entite_id) {
        return $this->get_by_entite($entite_type, $entite_id, 'ouvert');
    }

    /**
     * Get all dossiers for a program
     *
     * @param int $programme_id Program ID
     * @return array List of dossiers
     */
    public function get_by_programme($programme_id) {
        $query = $this->db->select('*')
            ->from($this->table)
            ->where('programme_id', $programme_id)
            ->get();
        return $query ? $query->result_array() : array();
    }

    /**
     * Get all dossiers (any statut), most recent first, for the admin/mecano
     * list view. Scoped to a section through the linked program, like
     * Maintenance_programme_model::get_by_section_admin().
     *
     * @param int|null|string $section_id Section ID
     * @return array List of dossiers
     */
    public function get_all($section_id = null) {
        $section_exists = false;
        if ($section_id !== null && $section_id !== '') {
            $query = $this->db->where('id', $section_id)->get('sections');
            $section_exists = $query->num_rows() > 0;
        }

        $this->db->select('d.*, p.code as programme_code, p.titre as programme_titre')
            ->from($this->table . ' d')
            ->join('maintenance_programmes p', 'd.programme_id = p.id', 'left');

        if ($section_exists) {
            $this->db->where("(p.section_id IS NULL OR p.section_id = " . (int) $section_id . ")", null, false);
        }

        $this->db->order_by('d.date_ouverture', 'desc');
        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Human-readable label for a maintainable entity, independent of the
     * SQL join (entite_type/entite_id is polymorphic, no native FK).
     *
     * @param string $entite_type 'aeronef' or 'equipement'
     * @param string $entite_id Entity ID
     * @return string
     */
    public function entite_label($entite_type, $entite_id) {
        if ($entite_type === 'aeronef') {
            $this->load->model('avions_model');
            $aeronef = $this->avions_model->get_by_id('macimmat', $entite_id);
            return $aeronef ? $aeronef['macmodele'] . ' - ' . $aeronef['macimmat'] : $entite_id;
        }
        if ($entite_type === 'equipement') {
            $this->load->model('maintenance_equipement_model');
            return $this->maintenance_equipement_model->image($entite_id);
        }
        return (string) $entite_id;
    }

    /**
     * Check that a maintainable entity actually exists.
     *
     * entite_type/entite_id is a polymorphic reference with no native FK ;
     * this is the application-level check that replaces it.
     *
     * @param string $entite_type 'aeronef' or 'equipement'
     * @param string $entite_id Entity ID
     * @return bool True if the entity exists
     */
    public function entite_exists($entite_type, $entite_id) {
        if ($entite_type === 'aeronef') {
            $this->load->model('avions_model');
            $aeronef = $this->avions_model->get_by_id('macimmat', $entite_id);
            return !empty($aeronef);
        }
        if ($entite_type === 'equipement') {
            $this->load->model('maintenance_equipement_model');
            $equipement = $this->maintenance_equipement_model->get($entite_id);
            return !empty($equipement);
        }
        return false;
    }

    /**
     * Open a new dossier
     *
     * @param array $data Dossier data (entite_type, entite_id, programme_id required)
     * @return int|false Inserted ID or false on failure
     */
    public function ouvrir($data) {
        if (!isset($data['date_ouverture'])) {
            $data['date_ouverture'] = date('Y-m-d');
        }
        if (!isset($data['statut'])) {
            $data['statut'] = 'ouvert';
        }

        return $this->create($data);
    }

    /**
     * Suspend a dossier
     *
     * @param int $id Dossier ID
     * @return bool Success
     */
    public function suspendre($id) {
        $data = array(
            'statut' => 'suspendu',
            'date_suspension' => date('Y-m-d'),
        );
        $this->update('id', $data, $id);
        return true;
    }

    /**
     * Reactivate a suspended dossier
     *
     * @param int $id Dossier ID
     * @return bool Success
     */
    public function reactiver($id) {
        $id = (int) $id;
        $sql = "UPDATE {$this->table} SET statut = 'ouvert', date_suspension = NULL WHERE id = {$id}";
        $this->db->query($sql);

        $error_msg = $this->db->_error_message();
        if (!empty($error_msg)) {
            gvv_error("MySQL Error: $error_msg");
        }

        return true;
    }

    /**
     * Close a dossier (cloture or abandon)
     *
     * @param int $id Dossier ID
     * @param string $type 'cloture' or 'abandonne'
     * @return bool Success
     */
    public function cloturer($id, $type = 'cloture') {
        $data = array(
            'statut' => $type,
            'date_cloture' => date('Y-m-d'),
        );
        $this->update('id', $data, $id);
        return true;
    }

    /**
     * Get dossier image for display
     *
     * @param int $id Dossier ID
     * @return string "entite_id - programme_code"
     */
    public function image($id) {
        $dossier = $this->get_full($id);
        if ($dossier) {
            return $dossier['entite_id'] . ' - ' . $dossier['programme_code'];
        }
        return '';
    }
}

/* End of file maintenance_dossier_model.php */
/* Location: ./application/models/maintenance_dossier_model.php */
