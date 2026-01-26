<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Suivi Seance Model
 *
 * Handles training sessions (seances de formation).
 * Supports both sessions linked to an enrollment (inscription_id NOT NULL)
 * and free sessions (inscription_id IS NULL).
 *
 * @author GVV Development Team
 * @see doc/prds/suivi_formation_prd.md
 */
class Formation_seance_model extends Common_Model {
    public $table = 'formation_seances';
    protected $primary_key = 'id';

    function __construct() {
        parent::__construct();
    }

    /**
     * Get a session by its ID
     *
     * @param int $id Session ID
     * @return array Session data or empty array if not found
     */
    public function get($id) {
        return $this->get_by_id('id', $id);
    }

    /**
     * Get session with full details
     *
     * @param int $id Session ID
     * @return array Session with related data
     */
    public function get_full($id) {
        $this->db->select('s.*, p.code as programme_code, p.titre as programme_titre,
            m.mnom as pilote_nom, m.mprenom as pilote_prenom,
            inst.mnom as instructeur_nom, inst.mprenom as instructeur_prenom,
            mp.mpmodele as machine_modele, mp.mpimmat as machine_immat')
            ->from($this->table . ' s')
            ->join('formation_programmes p', 's.programme_id = p.id', 'left')
            ->join('membres m', 's.pilote_id = m.mlogin', 'left')
            ->join('membres inst', 's.instructeur_id = inst.mlogin', 'left')
            ->join('machinesp mp', 's.machine_id = mp.mpimmat', 'left')
            ->where('s.id', $id);

        $result = $this->db->get()->row_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result ?: array();
    }

    /**
     * Get all sessions for an enrollment
     *
     * @param int $inscription_id Enrollment ID
     * @return array List of sessions
     */
    public function get_by_inscription($inscription_id) {
        $this->db->select('s.*, inst.mnom as instructeur_nom, inst.mprenom as instructeur_prenom,
            mp.mpmodele as machine_modele')
            ->from($this->table . ' s')
            ->join('membres inst', 's.instructeur_id = inst.mlogin', 'left')
            ->join('machinesp mp', 's.machine_id = mp.mpimmat', 'left')
            ->where('s.inscription_id', $inscription_id)
            ->order_by('s.date_seance', 'desc');

        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Get all sessions for a pilot (both with and without enrollment)
     *
     * @param string $pilote_id Pilot member login
     * @param array $filters Optional filters (date_debut, date_fin, programme_id, inscription_only)
     * @return array List of sessions
     */
    public function get_by_pilote($pilote_id, $filters = array()) {
        $this->db->select('s.*, p.code as programme_code, p.titre as programme_titre,
            inst.mnom as instructeur_nom, inst.mprenom as instructeur_prenom,
            mp.mpmodele as machine_modele,
            CASE WHEN s.inscription_id IS NULL THEN 1 ELSE 0 END as is_libre', FALSE)
            ->from($this->table . ' s')
            ->join('formation_programmes p', 's.programme_id = p.id', 'left')
            ->join('membres inst', 's.instructeur_id = inst.mlogin', 'left')
            ->join('machinesp mp', 's.machine_id = mp.mpimmat', 'left')
            ->where('s.pilote_id', $pilote_id);

        // Apply filters
        if (!empty($filters['date_debut'])) {
            $this->db->where('s.date_seance >=', $filters['date_debut']);
        }
        if (!empty($filters['date_fin'])) {
            $this->db->where('s.date_seance <=', $filters['date_fin']);
        }
        if (!empty($filters['programme_id'])) {
            $this->db->where('s.programme_id', $filters['programme_id']);
        }
        if (!empty($filters['inscription_only'])) {
            $this->db->where('s.inscription_id IS NOT NULL');
        }
        if (!empty($filters['libre_only'])) {
            $this->db->where('s.inscription_id IS NULL');
        }

        $this->db->order_by('s.date_seance', 'desc');
        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Get free sessions for a pilot (without enrollment)
     *
     * @param string $pilote_id Pilot member login
     * @return array List of free sessions
     */
    public function get_libres_by_pilote($pilote_id) {
        return $this->get_by_pilote($pilote_id, array('libre_only' => true));
    }

    /**
     * Get sessions by instructor
     *
     * @param string $instructeur_id Instructor member login
     * @param array $filters Optional filters
     * @return array List of sessions
     */
    public function get_by_instructeur($instructeur_id, $filters = array()) {
        $this->db->select('s.*, p.code as programme_code, p.titre as programme_titre,
            m.mnom as pilote_nom, m.mprenom as pilote_prenom,
            mp.mpmodele as machine_modele,
            CASE WHEN s.inscription_id IS NULL THEN 1 ELSE 0 END as is_libre', FALSE)
            ->from($this->table . ' s')
            ->join('formation_programmes p', 's.programme_id = p.id', 'left')
            ->join('membres m', 's.pilote_id = m.mlogin', 'left')
            ->join('machinesp mp', 's.machine_id = mp.mpimmat', 'left')
            ->where('s.instructeur_id', $instructeur_id);

        // Apply filters
        if (!empty($filters['date_debut'])) {
            $this->db->where('s.date_seance >=', $filters['date_debut']);
        }
        if (!empty($filters['date_fin'])) {
            $this->db->where('s.date_seance <=', $filters['date_fin']);
        }

        $this->db->order_by('s.date_seance', 'desc');
        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Create a session with its evaluations
     *
     * @param array $seance_data Session data
     * @param array $evaluations Array of evaluation data [{sujet_id, niveau, commentaire}, ...]
     * @return int|false Inserted session ID or false on failure
     */
    public function create_with_evaluations($seance_data, $evaluations = array()) {
        $this->db->trans_start();

        $seance_id = $this->create($seance_data);

        if ($seance_id && !empty($evaluations)) {
            $this->load->model('formation_evaluation_model');
            foreach ($evaluations as $eval) {
                $eval['seance_id'] = $seance_id;
                $this->formation_evaluation_model->create($eval);
            }
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            gvv_error("formation_seance_model::create_with_evaluations failed");
            return false;
        }

        return $seance_id;
    }

    /**
     * Update a session with its evaluations
     *
     * @param int $id Session ID
     * @param array $seance_data Session data
     * @param array $evaluations Array of evaluation data
     * @return bool Success
     */
    public function update_with_evaluations($id, $seance_data, $evaluations = array()) {
        $this->db->trans_start();

        $this->update('id', $seance_data, $id);

        // Delete existing evaluations and recreate
        $this->load->model('formation_evaluation_model');
        $this->formation_evaluation_model->delete_by_seance($id);

        if (!empty($evaluations)) {
            foreach ($evaluations as $eval) {
                $eval['seance_id'] = $id;
                $this->formation_evaluation_model->create($eval);
            }
        }

        $this->db->trans_complete();

        return ($this->db->trans_status() !== FALSE);
    }

    /**
     * Check if a session is a free session (no enrollment)
     *
     * @param int $seance_id Session ID
     * @return bool True if free session
     */
    public function is_seance_libre($seance_id) {
        $seance = $this->get($seance_id);
        return empty($seance['inscription_id']);
    }

    /**
     * Get sessions for list view with pagination
     *
     * @param array $filters Optional filters
     * @param int $limit Max results
     * @param int $offset Start offset
     * @return array Sessions with related info
     */
    public function select_page($filters = array(), $limit = 100, $offset = 0) {
        $this->db->select('s.*, p.code as programme_code, p.titre as programme_titre,
            m.mnom as pilote_nom, m.mprenom as pilote_prenom,
            inst.mnom as instructeur_nom, inst.mprenom as instructeur_prenom,
            mp.mpmodele as machine_modele,
            CASE WHEN s.inscription_id IS NULL THEN "Libre" ELSE "Formation" END as type_seance', FALSE)
            ->from($this->table . ' s')
            ->join('formation_programmes p', 's.programme_id = p.id', 'left')
            ->join('membres m', 's.pilote_id = m.mlogin', 'left')
            ->join('membres inst', 's.instructeur_id = inst.mlogin', 'left')
            ->join('machinesp mp', 's.machine_id = mp.mpimmat', 'left');

        // Apply filters
        if (!empty($filters['pilote_id'])) {
            $this->db->where('s.pilote_id', $filters['pilote_id']);
        }
        if (!empty($filters['instructeur_id'])) {
            $this->db->where('s.instructeur_id', $filters['instructeur_id']);
        }
        if (!empty($filters['programme_id'])) {
            $this->db->where('s.programme_id', $filters['programme_id']);
        }
        if (!empty($filters['date_debut'])) {
            $this->db->where('s.date_seance >=', $filters['date_debut']);
        }
        if (!empty($filters['date_fin'])) {
            $this->db->where('s.date_seance <=', $filters['date_fin']);
        }
        if (!empty($filters['type'])) {
            if ($filters['type'] == 'libre') {
                $this->db->where('s.inscription_id IS NULL');
            } else if ($filters['type'] == 'formation') {
                $this->db->where('s.inscription_id IS NOT NULL');
            }
        }

        $this->db->order_by('s.date_seance', 'desc')
            ->limit($limit, $offset);

        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Get statistics for an enrollment
     *
     * @param int $inscription_id Enrollment ID
     * @return array Stats: nb_seances, heures_totales, atterrissages_totaux
     */
    public function get_stats_inscription($inscription_id) {
        $this->db->select('COUNT(*) as nb_seances,
            SEC_TO_TIME(SUM(TIME_TO_SEC(duree))) as heures_totales,
            SUM(nb_atterrissages) as atterrissages_totaux')
            ->from($this->table)
            ->where('inscription_id', $inscription_id);

        $result = $this->db->get()->row_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result ?: array('nb_seances' => 0, 'heures_totales' => '00:00:00', 'atterrissages_totaux' => 0);
    }

    /**
     * Get session image for display
     *
     * @param int $id Session ID
     * @return string "Date - Pilote - Programme"
     */
    public function image($id) {
        $seance = $this->get_full($id);
        if ($seance) {
            return $seance['date_seance'] . ' - ' . $seance['pilote_prenom'] . ' ' .
                $seance['pilote_nom'] . ' - ' . $seance['programme_code'];
        }
        return '';
    }
}

/* End of file formation_seance_model.php */
/* Location: ./application/models/formation_seance_model.php */
