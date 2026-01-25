<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Suivi Sujet Model
 *
 * Handles topics within training lessons (sujets de formation).
 *
 * @author GVV Development Team
 * @see doc/prds/suivi_formation_prd.md
 */
class Formation_sujet_model extends Common_Model {
    public $table = 'formation_sujets';
    protected $primary_key = 'id';

    function __construct() {
        parent::__construct();
    }

    /**
     * Get a topic by its ID
     *
     * @param int $id Topic ID
     * @return array Topic data or empty array if not found
     */
    public function get($id) {
        return $this->get_by_id('id', $id);
    }

    /**
     * Get all topics for a lesson
     *
     * @param int $lecon_id Lesson ID
     * @return array List of topics ordered by ordre
     */
    public function get_by_lecon($lecon_id) {
        $this->db->select('*')
            ->from($this->table)
            ->where('lecon_id', $lecon_id)
            ->order_by('ordre', 'asc');

        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Get all topics for a program (through lessons)
     *
     * @param int $programme_id Program ID
     * @return array List of topics with lesson info
     */
    public function get_by_programme($programme_id) {
        $this->db->select('s.*, l.numero as lecon_numero, l.titre as lecon_titre')
            ->from($this->table . ' s')
            ->join('formation_lecons l', 's.lecon_id = l.id')
            ->where('l.programme_id', $programme_id)
            ->order_by('l.ordre', 'asc')
            ->order_by('s.ordre', 'asc');

        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Get topics grouped by lesson for a program
     *
     * @param int $programme_id Program ID
     * @return array Array indexed by lecon_id containing arrays of topics
     */
    public function get_grouped_by_lecon($programme_id) {
        $topics = $this->get_by_programme($programme_id);
        $grouped = array();
        foreach ($topics as $topic) {
            $lecon_id = $topic['lecon_id'];
            if (!isset($grouped[$lecon_id])) {
                $grouped[$lecon_id] = array();
            }
            $grouped[$lecon_id][] = $topic;
        }
        return $grouped;
    }

    /**
     * Create multiple topics in batch
     *
     * @param array $sujets Array of topic data
     * @return bool Success
     */
    public function create_batch($sujets) {
        if (empty($sujets)) {
            return true;
        }
        return $this->db->insert_batch($this->table, $sujets);
    }

    /**
     * Count topics for a program
     *
     * @param int $programme_id Program ID
     * @return int Number of topics
     */
    public function count_by_programme($programme_id) {
        $this->db->select('COUNT(s.id) as total')
            ->from($this->table . ' s')
            ->join('formation_lecons l', 's.lecon_id = l.id')
            ->where('l.programme_id', $programme_id);
        $result = $this->db->get()->row_array();
        return (int)$result['total'];
    }

    /**
     * Delete all topics for a lesson
     *
     * @param int $lecon_id Lesson ID
     * @return bool Success
     */
    public function delete_by_lecon($lecon_id) {
        $this->db->where('lecon_id', $lecon_id);
        return $this->db->delete($this->table);
    }

    /**
     * Get next order number for a lesson
     *
     * @param int $lecon_id Lesson ID
     * @return int Next order number
     */
    public function get_next_ordre($lecon_id) {
        $this->db->select_max('ordre')
            ->from($this->table)
            ->where('lecon_id', $lecon_id);
        $result = $this->db->get()->row_array();
        return ($result['ordre'] ?? 0) + 1;
    }

    /**
     * Get topic selector for dropdown (within a lesson)
     *
     * @param int $lecon_id Lesson ID
     * @return array [id => "numero - titre"]
     */
    public function get_selector($lecon_id) {
        $topics = $this->get_by_lecon($lecon_id);
        $result = array('' => '');
        foreach ($topics as $topic) {
            $result[$topic['id']] = $topic['numero'] . ' - ' . $topic['titre'];
        }
        return $result;
    }

    /**
     * Get topic image for display
     *
     * @param int $id Topic ID
     * @return string "numero - titre"
     */
    public function image($id) {
        $topic = $this->get($id);
        if ($topic) {
            return $topic['numero'] . ' - ' . $topic['titre'];
        }
        return '';
    }
}

/* End of file formation_sujet_model.php */
/* Location: ./application/models/formation_sujet_model.php */
