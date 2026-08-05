<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Maintenance Programme Section Model
 *
 * Handles the intermediate level of a maintenance program (miroir de
 * Formation_lecon_model). Named maintenance_programme_sections, never
 * maintenance_sections, to avoid any confusion with the existing
 * sections table (clubs/activites planeur/avion/ULM).
 *
 * @see doc/prds/maintenance_aeronefs_prd.md (EF2)
 * @see doc/design_notes/maintenance_aeronefs_design.md
 */
class Maintenance_programme_section_model extends Common_Model {
    public $table = 'maintenance_programme_sections';
    protected $primary_key = 'id';

    function __construct() {
        parent::__construct();
    }

    /**
     * Get a programme section by its ID
     *
     * @param int $id Section ID
     * @return array Section data or empty array if not found
     */
    public function get($id) {
        return $this->get_by_id('id', $id);
    }

    /**
     * Get all sections for a program, ordered by ordre
     *
     * @param int $programme_id Program ID
     * @param bool $actif_only If true (default), only return active sections
     * @return array List of sections
     */
    public function get_by_programme($programme_id, $actif_only = true) {
        $this->db->select('*')
            ->from($this->table)
            ->where('programme_id', $programme_id);

        if ($actif_only) {
            $this->db->where('actif', 1);
        }

        $this->db->order_by('ordre', 'asc');

        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Deactivate a section (soft-delete) : used when a new program version
     * removes a section still referenced by a maintenance_realisation
     * (through one of its taches).
     *
     * @param int $id Section ID
     * @return bool Success
     */
    public function desactiver($id) {
        return (bool) $this->update('id', array('actif' => 0), $id);
    }

    /**
     * Get next order number for a program
     *
     * @param int $programme_id Program ID
     * @return int Next order number
     */
    public function get_next_ordre($programme_id) {
        $this->db->select_max('ordre')
            ->from($this->table)
            ->where('programme_id', $programme_id);
        $result = $this->db->get()->row_array();
        return ($result['ordre'] ?? 0) + 1;
    }

    /**
     * Get section image for display
     *
     * @param int $id Section ID
     * @return string Section title
     */
    public function image($id) {
        $section = $this->get($id);
        return $section ? $section['titre'] : '';
    }
}

/* End of file maintenance_programme_section_model.php */
/* Location: ./application/models/maintenance_programme_section_model.php */
