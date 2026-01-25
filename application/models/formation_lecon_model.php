<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Suivi Lecon Model
 *
 * Handles lessons within training programs (lecons de formation).
 *
 * @author GVV Development Team
 * @see doc/prds/suivi_formation_prd.md
 */
class Formation_lecon_model extends Common_Model {
    public $table = 'formation_lecons';
    protected $primary_key = 'id';

    function __construct() {
        parent::__construct();
    }

    /**
     * Get a lesson by its ID
     *
     * @param int $id Lesson ID
     * @return array Lesson data or empty array if not found
     */
    public function get($id) {
        return $this->get_by_id('id', $id);
    }

    /**
     * Get all lessons for a program
     *
     * @param int $programme_id Program ID
     * @return array List of lessons ordered by ordre
     */
    public function get_by_programme($programme_id) {
        $this->db->select('*')
            ->from($this->table)
            ->where('programme_id', $programme_id)
            ->order_by('ordre', 'asc');

        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Create multiple lessons in batch
     *
     * @param array $lecons Array of lesson data
     * @return bool Success
     */
    public function create_batch($lecons) {
        if (empty($lecons)) {
            return true;
        }
        return $this->db->insert_batch($this->table, $lecons);
    }

    /**
     * Delete all lessons for a program
     *
     * @param int $programme_id Program ID
     * @return bool Success
     */
    public function delete_by_programme($programme_id) {
        $this->db->where('programme_id', $programme_id);
        return $this->db->delete($this->table);
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
     * Get lessons with subjects count for display
     *
     * @param int $programme_id Program ID
     * @return array Lessons with subjects_count field
     */
    public function get_with_subjects_count($programme_id) {
        $this->db->select('l.*, COUNT(s.id) as subjects_count')
            ->from($this->table . ' l')
            ->join('formation_sujets s', 's.lecon_id = l.id', 'left')
            ->where('l.programme_id', $programme_id)
            ->group_by('l.id')
            ->order_by('l.ordre', 'asc');

        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Reorder lessons after deletion
     *
     * @param int $programme_id Program ID
     * @return bool Success
     */
    public function reorder($programme_id) {
        $lessons = $this->get_by_programme($programme_id);
        $ordre = 1;
        foreach ($lessons as $lesson) {
            $this->db->where('id', $lesson['id']);
            $this->db->update($this->table, array('ordre' => $ordre));
            $ordre++;
        }
        return true;
    }

    /**
     * Get lesson selector for dropdown
     *
     * @param int $programme_id Program ID
     * @return array [id => "Lecon X - Titre"]
     */
    public function get_selector($programme_id) {
        $lessons = $this->get_by_programme($programme_id);
        $result = array('' => '');
        foreach ($lessons as $lesson) {
            $result[$lesson['id']] = 'Lecon ' . $lesson['numero'] . ' - ' . $lesson['titre'];
        }
        return $result;
    }

    /**
     * Get lesson image for display
     *
     * @param int $id Lesson ID
     * @return string "Lecon X - Titre"
     */
    public function image($id) {
        $lesson = $this->get($id);
        if ($lesson) {
            return 'Lecon ' . $lesson['numero'] . ' - ' . $lesson['titre'];
        }
        return '';
    }
}

/* End of file formation_lecon_model.php */
/* Location: ./application/models/formation_lecon_model.php */
