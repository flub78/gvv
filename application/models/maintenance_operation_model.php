<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Maintenance Operation Model
 *
 * Handles maintenance operations, a dated event attached to a dossier
 * (miroir de Formation_seance_model). Two entry modes on the same
 * screen : 'directe' (taches checked in GVV) or 'compte_rendu' (scanned
 * report attached via the document archive system).
 *
 * @see doc/prds/maintenance_aeronefs_prd.md (EF4)
 * @see doc/design_notes/maintenance_aeronefs_design.md
 */
class Maintenance_operation_model extends Common_Model {
    public $table = 'maintenance_operations';
    protected $primary_key = 'id';

    function __construct() {
        parent::__construct();
    }

    /**
     * Get an operation by its ID
     *
     * @param int $id Operation ID
     * @return array Operation data or empty array if not found
     */
    public function get($id) {
        return $this->get_by_id('id', $id);
    }

    /**
     * Get operation with full details (dossier, mecano and document info)
     *
     * @param int $id Operation ID
     * @return array Operation with related data
     */
    public function get_full($id) {
        $this->db->select('o.*, d.entite_type, d.entite_id, d.programme_id,
            m.mnom as mecano_nom, m.mprenom as mecano_prenom,
            ad.original_filename as document_filename')
            ->from($this->table . ' o')
            ->join('maintenance_dossiers d', 'o.dossier_id = d.id', 'left')
            ->join('membres m', 'o.mecano_id = m.mlogin', 'left')
            ->join('archived_documents ad', 'o.document_id = ad.id', 'left')
            ->where('o.id', $id);

        $result = $this->db->get()->row_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result ?: array();
    }

    /**
     * Get all operations for a dossier, most recent first
     *
     * @param int $dossier_id Dossier ID
     * @return array List of operations
     */
    public function get_by_dossier($dossier_id) {
        $this->db->select('o.*, m.mnom as mecano_nom, m.mprenom as mecano_prenom')
            ->from($this->table . ' o')
            ->join('membres m', 'o.mecano_id = m.mlogin', 'left')
            ->where('o.dossier_id', $dossier_id)
            ->order_by('o.date_operation', 'desc');

        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Get all operations, most recent first, for the dashboard/admin list
     * view. Scoped to a section through dossier -> programme, like
     * Maintenance_dossier_model::get_all().
     *
     * @param int|null|string $section_id Section ID
     * @param int $limit Max results
     * @return array List of operations
     */
    public function get_all($section_id = null, $limit = 100) {
        $section_exists = false;
        if ($section_id !== null && $section_id !== '') {
            $query = $this->db->where('id', $section_id)->get('sections');
            $section_exists = $query->num_rows() > 0;
        }

        $this->db->select('o.*, m.mnom as mecano_nom, m.mprenom as mecano_prenom,
            d.entite_type, d.entite_id, p.code as programme_code, p.titre as programme_titre')
            ->from($this->table . ' o')
            ->join('membres m', 'o.mecano_id = m.mlogin', 'left')
            ->join('maintenance_dossiers d', 'o.dossier_id = d.id', 'left')
            ->join('maintenance_programmes p', 'd.programme_id = p.id', 'left');

        if ($section_exists) {
            $this->db->where("(p.section_id IS NULL OR p.section_id = " . (int) $section_id . ")", null, false);
        }

        $this->db->order_by('o.date_operation', 'desc')->limit($limit);

        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Get operation image for display
     *
     * @param int $id Operation ID
     * @return string "date_operation - mode_saisie"
     */
    public function image($id) {
        $operation = $this->get($id);
        if ($operation) {
            return $operation['date_operation'] . ' - ' . $operation['mode_saisie'];
        }
        return '';
    }
}

/* End of file maintenance_operation_model.php */
/* Location: ./application/models/maintenance_operation_model.php */
