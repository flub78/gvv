<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Maintenance Realisation Model
 *
 * Handles the realisation of a program tache during an operation (fait /
 * non fait / non applicable), miroir exact de Formation_evaluation_model.
 * An operation in mode 'compte_rendu' with no realisation at all remains
 * valid (PRD EF4) : save_batch() with an empty array is a no-op.
 *
 * @see doc/prds/maintenance_aeronefs_prd.md (EF4)
 * @see doc/design_notes/maintenance_aeronefs_design.md
 */
class Maintenance_realisation_model extends Common_Model {
    public $table = 'maintenance_realisations';
    protected $primary_key = 'id';

    // Realisation statuses
    const STATUT_FAIT = 'fait';
    const STATUT_NON_FAIT = 'non_fait';
    const STATUT_NON_APPLICABLE = 'non_applicable';

    function __construct() {
        parent::__construct();
    }

    /**
     * Get a realisation by its ID
     *
     * @param int $id Realisation ID
     * @return array Realisation data or empty array if not found
     */
    public function get($id) {
        return $this->get_by_id('id', $id);
    }

    /**
     * Get all realisations for an operation, with tache and section info
     *
     * @param int $operation_id Operation ID
     * @return array List of realisations
     */
    public function get_by_operation($operation_id) {
        $this->db->select('r.*, t.titre as tache_titre, t.ordre as tache_ordre,
            ps.titre as section_titre, ps.ordre as section_ordre')
            ->from($this->table . ' r')
            ->join('maintenance_taches t', 'r.tache_id = t.id', 'left')
            ->join('maintenance_programme_sections ps', 't.programme_section_id = ps.id', 'left')
            ->where('r.operation_id', $operation_id)
            ->order_by('ps.ordre', 'asc')
            ->order_by('t.ordre', 'asc');

        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Save multiple realisations for an operation (batch)
     *
     * @param int $operation_id Operation ID
     * @param array $realisations [tache_id => ['statut' => 'fait', 'commentaire' => '...']]
     * @return bool Success
     */
    public function save_batch($operation_id, $realisations) {
        if (empty($realisations)) {
            return true;
        }

        $batch_data = array();
        foreach ($realisations as $tache_id => $realisation) {
            $batch_data[] = array(
                'operation_id' => $operation_id,
                'tache_id' => $tache_id,
                'statut' => $realisation['statut'] ?? self::STATUT_NON_FAIT,
                'commentaire' => $realisation['commentaire'] ?? null,
            );
        }

        return $this->db->insert_batch($this->table, $batch_data);
    }

    /**
     * Delete all realisations for an operation
     *
     * @param int $operation_id Operation ID
     * @return bool Success
     */
    public function delete_by_operation($operation_id) {
        $this->db->where('operation_id', $operation_id);
        return $this->db->delete($this->table);
    }

    /**
     * Get available realisation statuses
     *
     * @return array [statut => label]
     */
    public static function get_statuts() {
        return array(
            self::STATUT_FAIT => 'Fait',
            self::STATUT_NON_FAIT => 'Non fait',
            self::STATUT_NON_APPLICABLE => 'Non applicable',
        );
    }

    /**
     * Get realisation image for display
     *
     * @param int $id Realisation ID
     * @return string "tache_titre - statut"
     */
    public function image($id) {
        $realisation = $this->get($id);
        if ($realisation) {
            $this->load->model('maintenance_tache_model');
            $tache = $this->maintenance_tache_model->get($realisation['tache_id']);
            $statuts = self::get_statuts();
            $titre = $tache ? $tache['titre'] : $realisation['tache_id'];
            return $titre . ' - ' . ($statuts[$realisation['statut']] ?? $realisation['statut']);
        }
        return '';
    }
}

/* End of file maintenance_realisation_model.php */
/* Location: ./application/models/maintenance_realisation_model.php */
