<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Suivi Inscription Model
 *
 * Handles student enrollments in training programs (inscriptions aux formations).
 *
 * @author GVV Development Team
 * @see doc/prds/suivi_formation_prd.md
 */
class Formation_inscription_model extends Common_Model {
    public $table = 'formation_inscriptions';
    protected $primary_key = 'id';

    function __construct() {
        parent::__construct();
    }

    /**
     * Get an enrollment by its ID
     *
     * @param int $id Enrollment ID
     * @return array Enrollment data or empty array if not found
     */
    public function get($id) {
        return $this->get_by_id('id', $id);
    }

    /**
     * Get enrollment with full details (program, pilot, instructor info)
     *
     * @param int $id Enrollment ID
     * @return array Enrollment with related data
     */
    public function get_full($id) {
        $this->db->select('i.*, p.code as programme_code, p.titre as programme_titre,
            m.mnom as pilote_nom, m.mprenom as pilote_prenom,
            inst.mnom as instructeur_nom, inst.mprenom as instructeur_prenom')
            ->from($this->table . ' i')
            ->join('formation_programmes p', 'i.programme_id = p.id', 'left')
            ->join('membres m', 'i.pilote_id = m.mlogin', 'left')
            ->join('membres inst', 'i.instructeur_referent_id = inst.mlogin', 'left')
            ->where('i.id', $id);

        $result = $this->db->get()->row_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result ?: array();
    }

    /**
     * Get all enrollments for a pilot
     *
     * @param string $pilote_id Pilot member login
     * @param string|null $statut Optional status filter
     * @return array List of enrollments
     */
    public function get_by_pilote($pilote_id, $statut = null) {
        $this->db->select('i.*, p.code as programme_code, p.titre as programme_titre')
            ->from($this->table . ' i')
            ->join('formation_programmes p', 'i.programme_id = p.id', 'left')
            ->where('i.pilote_id', $pilote_id);

        if ($statut) {
            $this->db->where('i.statut', $statut);
        }

        $this->db->order_by('i.date_ouverture', 'desc');
        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Get open enrollments for a pilot
     *
     * @param string $pilote_id Pilot member login
     * @return array List of open enrollments
     */
    public function get_ouvertes($pilote_id) {
        return $this->get_by_pilote($pilote_id, 'ouverte');
    }

    /**
     * Get enrollment selector for a pilot (open enrollments only)
     *
     * @param string $pilote_id Pilot member login
     * @return array [id => "Programme - Date ouverture"]
     */
    public function get_selector_for_pilote($pilote_id) {
        $inscriptions = $this->get_ouvertes($pilote_id);
        $result = array('' => '-- Aucune (seance libre) --');
        foreach ($inscriptions as $inscription) {
            $result[$inscription['id']] = $inscription['programme_code'] . ' - ' .
                $inscription['programme_titre'] . ' (depuis ' . $inscription['date_ouverture'] . ')';
        }
        return $result;
    }

    /**
     * Open a new enrollment
     *
     * @param array $data Enrollment data (pilote_id, programme_id required)
     * @return int|false Inserted ID or false on failure
     */
    public function ouvrir($data) {
        // Set defaults
        if (!isset($data['date_ouverture'])) {
            $data['date_ouverture'] = date('Y-m-d');
        }
        if (!isset($data['statut'])) {
            $data['statut'] = 'ouverte';
        }

        // Get current program version
        if (!isset($data['version_programme'])) {
            $this->load->model('formation_programme_model');
            $program = $this->formation_programme_model->get($data['programme_id']);
            $data['version_programme'] = $program['version'] ?? 1;
        }

        return $this->create($data);
    }

    /**
     * Suspend an enrollment
     *
     * @param int $id Enrollment ID
     * @param string $motif Suspension reason
     * @return bool Success
     */
    public function suspendre($id, $motif = null) {
        $data = array(
            'statut' => 'suspendue',
            'date_suspension' => date('Y-m-d'),
            'motif_suspension' => $motif
        );
        return $this->update('id', $data, $id);
    }

    /**
     * Reactivate a suspended enrollment
     *
     * @param int $id Enrollment ID
     * @return bool Success
     */
    public function reactiver($id) {
        $data = array(
            'statut' => 'ouverte',
            'date_suspension' => null,
            'motif_suspension' => null
        );
        return $this->update('id', $data, $id);
    }

    /**
     * Close an enrollment (success or abandon)
     *
     * @param int $id Enrollment ID
     * @param string $type 'cloturee' or 'abandonnee'
     * @param string|null $motif Closure reason
     * @return bool Success
     */
    public function cloturer($id, $type = 'cloturee', $motif = null) {
        $data = array(
            'statut' => $type,
            'date_cloture' => date('Y-m-d'),
            'motif_cloture' => $motif
        );
        return $this->update('id', $data, $id);
    }

    /**
     * Get enrollments for list view with pagination
     *
     * @param array $filters Optional filters
     * @param int $limit Max results
     * @param int $offset Start offset
     * @return array Enrollments with related info
     */
    public function select_page($filters = array(), $limit = 100, $offset = 0) {
        $this->db->select('i.*, p.code as programme_code, p.titre as programme_titre,
            m.mnom as pilote_nom, m.mprenom as pilote_prenom,
            inst.mnom as instructeur_nom, inst.mprenom as instructeur_prenom')
            ->from($this->table . ' i')
            ->join('formation_programmes p', 'i.programme_id = p.id', 'left')
            ->join('membres m', 'i.pilote_id = m.mlogin', 'left')
            ->join('membres inst', 'i.instructeur_referent_id = inst.mlogin', 'left');

        // Apply filters
        if (!empty($filters['statut'])) {
            $this->db->where('i.statut', $filters['statut']);
        }
        if (!empty($filters['pilote_id'])) {
            $this->db->where('i.pilote_id', $filters['pilote_id']);
        }
        if (!empty($filters['programme_id'])) {
            $this->db->where('i.programme_id', $filters['programme_id']);
        }
        if (!empty($filters['instructeur_id'])) {
            $this->db->where('i.instructeur_referent_id', $filters['instructeur_id']);
        }

        $this->db->order_by('i.date_ouverture', 'desc')
            ->limit($limit, $offset);

        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Count enrollments matching filters
     *
     * @param array $filters Optional filters
     * @return int Count
     */
    public function count_filtered($filters = array()) {
        $this->db->from($this->table . ' i');

        if (!empty($filters['statut'])) {
            $this->db->where('i.statut', $filters['statut']);
        }
        if (!empty($filters['pilote_id'])) {
            $this->db->where('i.pilote_id', $filters['pilote_id']);
        }
        if (!empty($filters['programme_id'])) {
            $this->db->where('i.programme_id', $filters['programme_id']);
        }

        return $this->db->count_all_results();
    }

    /**
     * Get enrollments for an instructor (their students)
     *
     * @param string $instructeur_id Instructor member login
     * @param string|null $statut Optional status filter
     * @return array List of enrollments
     */
    public function get_by_instructeur($instructeur_id, $statut = null) {
        $this->db->select('i.*, p.code as programme_code, p.titre as programme_titre,
            m.mnom as pilote_nom, m.mprenom as pilote_prenom')
            ->from($this->table . ' i')
            ->join('formation_programmes p', 'i.programme_id = p.id', 'left')
            ->join('membres m', 'i.pilote_id = m.mlogin', 'left')
            ->where('i.instructeur_referent_id', $instructeur_id);

        if ($statut) {
            $this->db->where('i.statut', $statut);
        }

        $this->db->order_by('m.mnom', 'asc');
        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Check if pilot already has an open enrollment for a program
     *
     * @param string $pilote_id Pilot member login
     * @param int $programme_id Program ID
     * @return bool True if enrollment exists
     */
    public function has_open_enrollment($pilote_id, $programme_id) {
        $this->db->where('pilote_id', $pilote_id)
            ->where('programme_id', $programme_id)
            ->where('statut', 'ouverte');
        return ($this->db->count_all_results($this->table) > 0);
    }

    /**
     * Get enrollment image for display
     *
     * @param int $id Enrollment ID
     * @return string "Pilote - Programme"
     */
    public function image($id) {
        $inscription = $this->get_full($id);
        if ($inscription) {
            return $inscription['pilote_prenom'] . ' ' . $inscription['pilote_nom'] .
                ' - ' . $inscription['programme_code'];
        }
        return '';
    }
}

/* End of file formation_inscription_model.php */
/* Location: ./application/models/formation_inscription_model.php */
