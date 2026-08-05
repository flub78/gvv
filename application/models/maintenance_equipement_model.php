<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Maintenance Equipement Model
 *
 * Handles maintainable equipments attached to an aircraft (moteur, helice,
 * parachute, radio, etc.). An equipment belongs to a single aircraft at a
 * time and can be transferred without losing its history (dossiers,
 * operations reference it by id, not by aeronef_id).
 *
 * @see doc/prds/maintenance_aeronefs_prd.md (EF1)
 * @see doc/design_notes/maintenance_aeronefs_design.md
 */
class Maintenance_equipement_model extends Common_Model {
    public $table = 'maintenance_equipements';
    protected $primary_key = 'id';

    function __construct() {
        parent::__construct();
    }

    /**
     * Get an equipment by its ID
     *
     * @param int $id Equipment ID
     * @return array Equipment data or empty array if not found
     */
    public function get($id) {
        return $this->get_by_id('id', $id);
    }

    /**
     * Get all equipments attached to an aircraft
     *
     * @param string $aeronef_id Aircraft registration (machinesa.macimmat)
     * @param bool $actif_only If true, only return active equipments
     * @return array List of equipments
     */
    public function get_by_aeronef($aeronef_id, $actif_only = false) {
        $this->db->select('*')
            ->from($this->table)
            ->where('aeronef_id', $aeronef_id)
            ->order_by('nom', 'asc');

        if ($actif_only) {
            $this->db->where('actif', 1);
        }

        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Get equipment selector for a given aircraft
     *
     * @param string $aeronef_id Aircraft registration
     * @return array [id => nom]
     */
    public function get_selector($aeronef_id) {
        $equipements = $this->get_by_aeronef($aeronef_id, true);
        $result = array('' => '');
        foreach ($equipements as $equipement) {
            $result[$equipement['id']] = $equipement['nom'];
        }
        return $result;
    }

    /**
     * Get all equipments, most recently created first, with aircraft model
     * joined in for display.
     *
     * @param bool $actif_only If true (default), only return active equipments
     * @return array List of equipments (fields + aeronef_modele)
     */
    public function get_all($actif_only = true) {
        $this->db->select('e.*, m.macmodele as aeronef_modele')
            ->from($this->table . ' e')
            ->join('machinesa m', 'e.aeronef_id = m.macimmat', 'left');

        if ($actif_only) {
            $this->db->where('e.actif', 1);
        }

        $this->db->order_by('m.macmodele', 'asc')->order_by('e.nom', 'asc');

        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Get all active aircraft, optionally filtered by section (club),
     * for the fleet synthesis view (Etape 5.6).
     *
     * @param int|null|string $section_id Section ID, null/empty = all sections
     * @return array List of aircraft (macimmat, macmodele, club)
     */
    public function get_aeronefs_by_section($section_id = null) {
        $this->db->select('macimmat, macmodele, club')
            ->from('machinesa')
            ->where('actif', 1);

        if ($section_id !== null && $section_id !== '') {
            $this->db->where('club', $section_id);
        }

        $this->db->order_by('macmodele', 'asc');

        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Get a dropdown selector of all active aircraft, for the "target
     * aircraft" field of the create/transfer forms.
     *
     * @return array [macimmat => "macmodele - macimmat"]
     */
    public function get_aeronef_selector() {
        $this->db->select('macimmat, macmodele')
            ->from('machinesa')
            ->where('actif', 1)
            ->order_by('macmodele', 'asc');

        $results = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());

        $selector = array('' => '');
        foreach ($results as $row) {
            $selector[$row['macimmat']] = $row['macmodele'] . ' - ' . $row['macimmat'];
        }
        return $selector;
    }

    /**
     * Get a dropdown selector of all active equipments, across every
     * aircraft (used when opening a dossier for an equipment, since the
     * entity is chosen before knowing which aircraft it currently sits on).
     *
     * @return array [id => "nom (aeronef_id)"]
     */
    public function get_all_selector() {
        $equipements = $this->get_all(true);
        $result = array('' => '');
        foreach ($equipements as $equipement) {
            $result[$equipement['id']] = $equipement['nom'] . ' (' . $equipement['aeronef_id'] . ')';
        }
        return $result;
    }

    /**
     * Deactivate an equipment (soft-delete, PRD EF1.3) : history (dossiers,
     * operations) is preserved, the row remains consultable.
     *
     * @param int $id Equipment ID
     * @return bool Success
     */
    public function desactiver($id) {
        return (bool) $this->update('id', array('actif' => 0), $id);
    }

    /**
     * Reactivate a previously deactivated equipment.
     *
     * @param int $id Equipment ID
     * @return bool Success
     */
    public function reactiver($id) {
        return (bool) $this->update('id', array('actif' => 1), $id);
    }

    /**
     * Transfer an equipment to another aircraft (PRD Parcours 5)
     *
     * Only aeronef_id is updated. Dossiers and operations reference the
     * equipment by its own id (entite_id), independent of the aircraft it
     * is currently attached to, so their history is preserved untouched.
     *
     * @param int $equipement_id Equipment ID
     * @param string $nouvel_aeronef_id New aircraft registration
     * @return bool Success
     */
    public function transferer($equipement_id, $nouvel_aeronef_id) {
        return $this->update('id', array('aeronef_id' => $nouvel_aeronef_id), $equipement_id);
    }

    /**
     * Get equipment image for display
     *
     * @param int $id Equipment ID
     * @return string "nom (aeronef_id)"
     */
    public function image($id) {
        $equipement = $this->get($id);
        if ($equipement) {
            return $equipement['nom'] . ' (' . $equipement['aeronef_id'] . ')';
        }
        return '';
    }
}

/* End of file maintenance_equipement_model.php */
/* Location: ./application/models/maintenance_equipement_model.php */
