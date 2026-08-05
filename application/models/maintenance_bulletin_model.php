<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Maintenance Bulletin Model
 *
 * Handles the applicative status of a service bulletin (a_traiter /
 * traite / non_applicable). A bulletin itself is an archived_documents
 * row (scope 'machine') ; this model only owns the companion status
 * table maintenance_bulletin_statuts, kept separate so the generic
 * archived_documents schema is never polluted with maintenance-specific
 * columns.
 *
 * @see doc/prds/maintenance_aeronefs_prd.md (EF6)
 * @see doc/design_notes/maintenance_aeronefs_design.md
 */
class Maintenance_bulletin_model extends Common_Model {
    public $table = 'maintenance_bulletin_statuts';
    protected $primary_key = 'id';

    const STATUT_A_TRAITER = 'a_traiter';
    const STATUT_TRAITE = 'traite';
    const STATUT_NON_APPLICABLE = 'non_applicable';

    function __construct() {
        parent::__construct();
    }

    /**
     * Get a bulletin status row by its ID
     *
     * @param int $id Row ID
     * @return array Row data or empty array if not found
     */
    public function get($id) {
        return $this->get_by_id('id', $id);
    }

    /**
     * Get the status row for a given document
     *
     * @param int $archived_document_id archived_documents.id
     * @return array Row data or empty array if no status has been set yet
     */
    public function get_by_document($archived_document_id) {
        return $this->get_by_id('archived_document_id', $archived_document_id);
    }

    /**
     * Get the current status of a bulletin
     *
     * @param int $archived_document_id archived_documents.id
     * @return string Status, defaults to 'a_traiter' when no row exists yet
     */
    public function get_statut($archived_document_id) {
        $row = $this->get_by_document($archived_document_id);
        return $row ? $row['statut'] : self::STATUT_A_TRAITER;
    }

    /**
     * Set the status of a bulletin (create the row on first change)
     *
     * @param int $archived_document_id archived_documents.id
     * @param string $statut a_traiter | traite | non_applicable
     * @return bool Success
     */
    public function set_statut($archived_document_id, $statut) {
        $existing = $this->get_by_document($archived_document_id);
        if ($existing) {
            return (bool) $this->update('id', array('statut' => $statut), $existing['id']);
        }

        return (bool) $this->create(array(
            'archived_document_id' => $archived_document_id,
            'statut' => $statut,
        ));
    }

    /**
     * List service bulletins attached to a machine, with their status
     *
     * @param string $machine_immat Aircraft or club-wide document machine_immat
     * @param int|null $document_type_id Optional document_types.id filter
     * @return array List of bulletins (archived_documents fields + statut)
     */
    public function get_by_machine($machine_immat, $document_type_id = null) {
        // $escape=FALSE : requis pour toute expression select() contenant une virgule
        // (ex. COALESCE(a, b)) -- CodeIgniter 2 decoupe naivement la chaine select() sur
        // les virgules avant de reechapper chaque fragment, ce qui casse un appel de
        // fonction a plusieurs arguments. escape=FALSE desactive ce reechappement par
        // fragment, rendant le decoupage/rassemblage neutre (meme convention que
        // formation_seance_model.php et form_submissions_model.php).
        $this->db->select("ad.*, COALESCE(bs.statut, '" . self::STATUT_A_TRAITER . "') as statut", FALSE)
            ->from('archived_documents ad')
            ->join($this->table . ' bs', 'ad.id = bs.archived_document_id', 'left')
            ->where('ad.machine_immat', $machine_immat)
            ->where('ad.is_current_version', 1);

        if ($document_type_id) {
            $this->db->where('ad.document_type_id', $document_type_id);
        }

        $this->db->order_by('ad.uploaded_at', 'desc');
        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Get available bulletin statuses
     *
     * @return array [statut => label]
     */
    public static function get_statuts() {
        return array(
            self::STATUT_A_TRAITER => 'A traiter',
            self::STATUT_TRAITE => 'Traite',
            self::STATUT_NON_APPLICABLE => 'Non applicable',
        );
    }

    /**
     * Get bulletin status image for display
     *
     * @param int $id Row ID
     * @return string Status label
     */
    public function image($id) {
        $row = $this->get($id);
        if ($row) {
            $statuts = self::get_statuts();
            return $statuts[$row['statut']] ?? $row['statut'];
        }
        return '';
    }
}

/* End of file maintenance_bulletin_model.php */
/* Location: ./application/models/maintenance_bulletin_model.php */
