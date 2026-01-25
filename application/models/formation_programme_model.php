<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Suivi Programme Model
 *
 * Handles training programs (programmes de formation) for the glider pilot training system.
 *
 * @author GVV Development Team
 * @see doc/prds/suivi_formation_prd.md
 */
class Formation_programme_model extends Common_Model {
    public $table = 'formation_programmes';
    protected $primary_key = 'id';

    function __construct() {
        parent::__construct();
    }

    /**
     * Get a program by its ID
     *
     * @param int $id Program ID
     * @return array Program data or empty array if not found
     */
    public function get($id) {
        return $this->get_by_id('id', $id);
    }

    /**
     * Get all programs
     *
     * @param bool $active_only If true, only return active programs
     * @return array List of all programs
     */
    public function get_all($active_only = false) {
        $this->db->select('*')
            ->from($this->table)
            ->order_by('titre', 'asc');

        if ($active_only) {
            $this->db->where('actif', 1);
        }

        return $this->db->get()->result_array();
    }

    /**
     * Get a program by its code
     *
     * @param string $code Program code (e.g., SPL, BPP)
     * @return array Program data or empty array if not found
     */
    public function get_by_code($code) {
        $this->db->where('code', $code);
        $result = $this->db->get($this->table)->row_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result ?: array();
    }

    /**
     * Get all programs for a section (including "all sections" programs)
     *
     * @param int|null $section_id Section ID (null = only global programs)
     * @return array List of programs
     */
    public function get_by_section($section_id = null) {
        $this->db->select('*')
            ->from($this->table)
            ->group_start()
                ->where('section_id IS NULL')
                ->or_where('section_id', $section_id)
            ->group_end()
            ->where('statut', 'actif')
            ->order_by('titre', 'asc');

        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Get all active programs visible to the current user
     *
     * Programs with section_id = NULL are visible to all sections
     * Programs with specific section_id are visible only to that section
     *
     * @return array List of visible programs
     */
    public function get_visibles() {
        return $this->get_by_section($this->section_id);
    }

    /**
     * Get all active programs for selector dropdown
     *
     * @return array [id => titre] for use in dropdown selectors
     */
    public function get_selector() {
        $programs = $this->get_visibles();
        $result = array('' => '');
        foreach ($programs as $program) {
            $result[$program['id']] = $program['code'] . ' - ' . $program['titre'];
        }
        return $result;
    }

    /**
     * Create a new program
     *
     * @param array $data Program data
     * @return int|false Inserted ID or false on failure
     */
    public function create_programme($data) {
        // Set defaults
        if (!isset($data['version'])) {
            $data['version'] = 1;
        }
        if (!isset($data['statut'])) {
            $data['statut'] = 'actif';
        }
        if (!isset($data['date_creation'])) {
            $data['date_creation'] = date('Y-m-d H:i:s');
        }

        return $this->create($data);
    }

    /**
     * Update a program
     *
     * @param int $id Program ID
     * @param array $data Updated data
     * @return bool Success
     */
    public function update_programme($id, $data) {
        $data['date_modification'] = date('Y-m-d H:i:s');
        log_message('debug', 'FORMATION_PROGRAMME_MODEL: Calling update with id=' . $id);
        log_message('debug', 'FORMATION_PROGRAMME_MODEL: Data: ' . print_r($data, TRUE));
        $result = $this->update('id', $data, $id);
        log_message('debug', 'FORMATION_PROGRAMME_MODEL: update() returned: ' . var_export($result, TRUE));
        log_message('debug', 'FORMATION_PROGRAMME_MODEL: affected_rows: ' . $this->db->affected_rows());
        // Return true if at least one row was affected
        return $this->db->affected_rows() > 0;
    }

    /**
     * Increment program version (when structure changes)
     *
     * @param int $id Program ID
     * @return bool Success
     */
    public function increment_version($id) {
        $date_modification = date('Y-m-d H:i:s');
        $id = intval($id);
        $sql = "UPDATE {$this->table} SET version = version + 1, date_modification = '{$date_modification}' WHERE id = {$id}";
        return $this->db->query($sql);
    }

    /**
     * Archive a program (set status to 'archive')
     *
     * @param int $id Program ID
     * @return bool Success
     */
    public function archive($id) {
        return $this->update_programme($id, array('statut' => 'archive'));
    }

    /**
     * Reactivate an archived program
     *
     * @param int $id Program ID
     * @return bool Success
     */
    public function reactivate($id) {
        return $this->update_programme($id, array('statut' => 'actif'));
    }

    /**
     * Get programs list for admin view with pagination
     *
     * @param array $filters Optional filters (statut, section_id)
     * @param int $limit Max results
     * @param int $offset Start offset
     * @return array Programs with related info
     */
    public function select_page($filters = array(), $limit = 100, $offset = 0) {
        $this->db->select('p.*, s.nom as section_nom')
            ->from($this->table . ' p')
            ->join('sections s', 'p.section_id = s.id', 'left');

        // Apply filters
        if (!empty($filters['statut'])) {
            $this->db->where('p.statut', $filters['statut']);
        }
        if (isset($filters['section_id'])) {
            if ($filters['section_id'] === '') {
                $this->db->where('p.section_id IS NULL');
            } else {
                $this->db->where('p.section_id', $filters['section_id']);
            }
        }

        $this->db->order_by('p.code', 'asc')
            ->limit($limit, $offset);

        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Count programs matching filters
     *
     * @param array $filters Optional filters
     * @return int Count
     */
    public function count_filtered($filters = array()) {
        $this->db->from($this->table);

        if (!empty($filters['statut'])) {
            $this->db->where('statut', $filters['statut']);
        }
        if (isset($filters['section_id'])) {
            if ($filters['section_id'] === '') {
                $this->db->where('section_id IS NULL');
            } else {
                $this->db->where('section_id', $filters['section_id']);
            }
        }

        return $this->db->count_all_results();
    }

    /**
     * Check if a program code is unique
     *
     * @param string $code Code to check
     * @param int|null $exclude_id ID to exclude from check (for updates)
     * @return bool True if unique
     */
    public function is_code_unique($code, $exclude_id = null) {
        $this->db->where('code', $code);
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        return ($this->db->count_all_results($this->table) == 0);
    }

    /**
     * Get program title for display
     *
     * @param int $id Program ID
     * @return string "[CODE] - Titre" or empty string
     */
    public function image($id) {
        $program = $this->get($id);
        if ($program) {
            return $program['code'] . ' - ' . $program['titre'];
        }
        return '';
    }
}

/* End of file formation_programme_model.php */
/* Location: ./application/models/formation_programme_model.php */
