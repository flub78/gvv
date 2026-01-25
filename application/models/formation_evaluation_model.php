<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Suivi Evaluation Model
 *
 * Handles topic evaluations within training sessions.
 * Evaluation levels:
 * - '-': Not covered (non aborde)
 * - 'A': Introduced (aborde)
 * - 'R': Review needed (a revoir)
 * - 'Q': Acquired (acquis)
 *
 * @author GVV Development Team
 * @see doc/prds/suivi_formation_prd.md
 */
class Formation_evaluation_model extends Common_Model {
    public $table = 'formation_evaluations';
    protected $primary_key = 'id';

    // Evaluation levels
    const NIVEAU_NON_ABORDE = '-';
    const NIVEAU_ABORDE = 'A';
    const NIVEAU_A_REVOIR = 'R';
    const NIVEAU_ACQUIS = 'Q';

    function __construct() {
        parent::__construct();
    }

    /**
     * Get an evaluation by its ID
     *
     * @param int $id Evaluation ID
     * @return array Evaluation data or empty array if not found
     */
    public function get($id) {
        return $this->get_by_id('id', $id);
    }

    /**
     * Get all evaluations for a session
     *
     * @param int $seance_id Session ID
     * @return array List of evaluations with topic info
     */
    public function get_by_seance($seance_id) {
        $this->db->select('e.*, s.numero as sujet_numero, s.titre as sujet_titre,
            l.numero as lecon_numero, l.titre as lecon_titre')
            ->from($this->table . ' e')
            ->join('formation_sujets s', 'e.sujet_id = s.id', 'left')
            ->join('formation_lecons l', 's.lecon_id = l.id', 'left')
            ->where('e.seance_id', $seance_id)
            ->order_by('l.ordre', 'asc')
            ->order_by('s.ordre', 'asc');

        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Get evaluation history for a topic
     *
     * @param int $sujet_id Topic ID
     * @param int|null $inscription_id Optional: filter by enrollment
     * @return array List of evaluations with session info, ordered by date desc
     */
    public function get_by_sujet($sujet_id, $inscription_id = null) {
        $this->db->select('e.*, se.date_seance, se.inscription_id,
            inst.mnom as instructeur_nom, inst.mprenom as instructeur_prenom')
            ->from($this->table . ' e')
            ->join('formation_seances se', 'e.seance_id = se.id')
            ->join('membres inst', 'se.instructeur_id = inst.mlogin', 'left')
            ->where('e.sujet_id', $sujet_id);

        if ($inscription_id) {
            $this->db->where('se.inscription_id', $inscription_id);
        }

        $this->db->order_by('se.date_seance', 'desc');
        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Get the last evaluation level for each topic in an enrollment
     *
     * Used for calculating progression percentage.
     *
     * @param int $inscription_id Enrollment ID
     * @return array [sujet_id => ['niveau' => 'Q', 'date' => '2025-01-20']]
     */
    public function get_dernier_niveau_par_sujet($inscription_id) {
        // Subquery to get the most recent seance date for each sujet
        $subquery = "SELECT e.sujet_id, MAX(se.date_seance) as max_date
            FROM {$this->table} e
            JOIN formation_seances se ON e.seance_id = se.id
            WHERE se.inscription_id = ?
            GROUP BY e.sujet_id";

        $this->db->select('e.sujet_id, e.niveau, se.date_seance')
            ->from($this->table . ' e')
            ->join('formation_seances se', 'e.seance_id = se.id')
            ->join("($subquery) latest", 'e.sujet_id = latest.sujet_id AND se.date_seance = latest.max_date', 'inner', FALSE)
            ->where('se.inscription_id', $inscription_id);

        $results = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());

        // Convert to indexed array
        $indexed = array();
        foreach ($results as $row) {
            $indexed[$row['sujet_id']] = array(
                'niveau' => $row['niveau'],
                'date' => $row['date_seance']
            );
        }
        return $indexed;
    }

    /**
     * Save multiple evaluations for a session (batch)
     *
     * @param int $seance_id Session ID
     * @param array $evaluations Array of [sujet_id => ['niveau' => 'A', 'commentaire' => '...']]
     * @return bool Success
     */
    public function save_batch($seance_id, $evaluations) {
        if (empty($evaluations)) {
            return true;
        }

        $batch_data = array();
        foreach ($evaluations as $sujet_id => $eval) {
            if (!empty($eval['niveau']) && $eval['niveau'] != '-') {
                $batch_data[] = array(
                    'seance_id' => $seance_id,
                    'sujet_id' => $sujet_id,
                    'niveau' => $eval['niveau'],
                    'commentaire' => $eval['commentaire'] ?? null
                );
            }
        }

        if (empty($batch_data)) {
            return true;
        }

        return $this->db->insert_batch($this->table, $batch_data);
    }

    /**
     * Delete all evaluations for a session
     *
     * @param int $seance_id Session ID
     * @return bool Success
     */
    public function delete_by_seance($seance_id) {
        $this->db->where('seance_id', $seance_id);
        return $this->db->delete($this->table);
    }

    /**
     * Count acquired topics for an enrollment
     *
     * @param int $inscription_id Enrollment ID
     * @return int Number of acquired topics
     */
    public function count_acquis($inscription_id) {
        $derniers = $this->get_dernier_niveau_par_sujet($inscription_id);
        $count = 0;
        foreach ($derniers as $niveau_data) {
            if ($niveau_data['niveau'] == self::NIVEAU_ACQUIS) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Get progress summary for an enrollment
     *
     * @param int $inscription_id Enrollment ID
     * @param int $programme_id Program ID
     * @return array Progress stats
     */
    public function get_progression_summary($inscription_id, $programme_id) {
        $this->load->model('formation_sujet_model');

        $total_sujets = $this->formation_sujet_model->count_by_programme($programme_id);
        $derniers = $this->get_dernier_niveau_par_sujet($inscription_id);

        $stats = array(
            'total' => $total_sujets,
            'acquis' => 0,
            'a_revoir' => 0,
            'aborde' => 0,
            'non_aborde' => 0,
            'pourcentage_acquis' => 0
        );

        foreach ($derniers as $niveau_data) {
            switch ($niveau_data['niveau']) {
                case self::NIVEAU_ACQUIS:
                    $stats['acquis']++;
                    break;
                case self::NIVEAU_A_REVOIR:
                    $stats['a_revoir']++;
                    break;
                case self::NIVEAU_ABORDE:
                    $stats['aborde']++;
                    break;
            }
        }

        $stats['non_aborde'] = $total_sujets - count($derniers);

        if ($total_sujets > 0) {
            $stats['pourcentage_acquis'] = round(($stats['acquis'] / $total_sujets) * 100, 1);
        }

        return $stats;
    }

    /**
     * Get available evaluation levels
     *
     * @return array [level => label]
     */
    public static function get_niveaux() {
        return array(
            self::NIVEAU_NON_ABORDE => 'Non aborde',
            self::NIVEAU_ABORDE => 'Aborde',
            self::NIVEAU_A_REVOIR => 'A revoir',
            self::NIVEAU_ACQUIS => 'Acquis'
        );
    }

    /**
     * Get evaluation image for display
     *
     * @param int $id Evaluation ID
     * @return string "Sujet numero - Niveau"
     */
    public function image($id) {
        $eval = $this->get($id);
        if ($eval) {
            $this->load->model('formation_sujet_model');
            $sujet = $this->formation_sujet_model->get($eval['sujet_id']);
            $niveaux = self::get_niveaux();
            return $sujet['numero'] . ' - ' . ($niveaux[$eval['niveau']] ?? $eval['niveau']);
        }
        return '';
    }
}

/* End of file formation_evaluation_model.php */
/* Location: ./application/models/formation_evaluation_model.php */
