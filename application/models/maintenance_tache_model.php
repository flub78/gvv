<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Maintenance Tache Model
 *
 * Handles elementary control points within a maintenance program section
 * (miroir de Formation_sujet_model).
 *
 * @see doc/prds/maintenance_aeronefs_prd.md (EF2)
 * @see doc/design_notes/maintenance_aeronefs_design.md
 */
class Maintenance_tache_model extends Common_Model {
    public $table = 'maintenance_taches';
    protected $primary_key = 'id';

    function __construct() {
        parent::__construct();
    }

    /**
     * Get a tache by its ID
     *
     * @param int $id Tache ID
     * @return array Tache data or empty array if not found
     */
    public function get($id) {
        return $this->get_by_id('id', $id);
    }

    /**
     * Get all taches for a programme section, ordered by ordre
     *
     * @param int $programme_section_id Section ID
     * @param bool $actif_only If true (default), only return active taches
     * @return array List of taches
     */
    public function get_by_programme_section($programme_section_id, $actif_only = true) {
        $this->db->select('*')
            ->from($this->table)
            ->where('programme_section_id', $programme_section_id);

        if ($actif_only) {
            $this->db->where('actif', 1);
        }

        $this->db->order_by('ordre', 'asc');

        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Get all taches for a program (through its sections)
     *
     * @param int $programme_id Program ID
     * @param bool $actif_only If true (default), only return active taches (of active sections)
     * @return array List of taches with section info
     */
    public function get_by_programme($programme_id, $actif_only = true) {
        $this->db->select('t.*, ps.ordre as section_ordre, ps.titre as section_titre')
            ->from($this->table . ' t')
            ->join('maintenance_programme_sections ps', 't.programme_section_id = ps.id')
            ->where('ps.programme_id', $programme_id);

        if ($actif_only) {
            $this->db->where('t.actif', 1);
            $this->db->where('ps.actif', 1);
        }

        $this->db->order_by('ps.ordre', 'asc');
        $this->db->order_by('t.ordre', 'asc');

        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Deactivate a tache (soft-delete) : used when a new program version
     * removes a tache still referenced by a maintenance_realisation.
     *
     * @param int $id Tache ID
     * @return bool Success
     */
    public function desactiver($id) {
        return (bool) $this->update('id', array('actif' => 0), $id);
    }

    /**
     * Count realisations referencing a tache (used to decide whether an
     * obsolete tache can be hard-deleted or must be soft-deactivated).
     *
     * @param int $id Tache ID
     * @return int Number of realisations
     */
    public function count_realisations($id) {
        return $this->db->where('tache_id', $id)->count_all_results('maintenance_realisations');
    }

    /**
     * Get next order number for a section
     *
     * @param int $programme_section_id Section ID
     * @return int Next order number
     */
    public function get_next_ordre($programme_section_id) {
        $this->db->select_max('ordre')
            ->from($this->table)
            ->where('programme_section_id', $programme_section_id);
        $result = $this->db->get()->row_array();
        return ($result['ordre'] ?? 0) + 1;
    }

    /**
     * Get tache image for display
     *
     * @param int $id Tache ID
     * @return string Tache title
     */
    public function image($id) {
        $tache = $this->get($id);
        return $tache ? $tache['titre'] : '';
    }
}

/* End of file maintenance_tache_model.php */
/* Location: ./application/models/maintenance_tache_model.php */
