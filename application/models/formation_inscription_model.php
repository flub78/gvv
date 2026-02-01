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
     * Get all enrollments for a programme
     *
     * @param int $programme_id Programme ID
     * @return array List of enrollments
     */
    public function get_by_programme($programme_id) {
        return $this->db->select('*')
            ->from($this->table)
            ->where('programme_id', $programme_id)
            ->get()->result_array();
    }


    /**
     * Count enrollments for a programme
     *
     * @param int $programme_id Programme ID
     * @return int Number of enrollments
     */
    public function count_by_programme($programme_id) {
        return $this->db->where('programme_id', $programme_id)
            ->count_all_results($this->table);
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
     * Get all enrollments with optional filters
     *
     * @param array $filters Optional filters (pilote_id, programme_id, statut, instructeur_referent_id)
     * @return array List of enrollments with details
     */
    public function get_all($filters = array()) {
        return $this->select_page($filters, 1000, 0);
    }
    
    /**
     * Get enrollment with full details (program, pilot, instructor info)
     *
     * @param int $id Enrollment ID
     * @return array Enrollment with related data
     */
    public function get_with_details($id) {
        return $this->get_full($id);
    }
    
    /**
     * Get enrollment by pilot and program with optional status filter
     *
     * @param string $pilote_id Pilot member login
     * @param int $programme_id Program ID
     * @param string|null $statut Optional status filter
     * @return array Enrollment data or empty array
     */
    public function get_by_pilote_programme($pilote_id, $programme_id, $statut = null) {
        $this->db->where('pilote_id', $pilote_id)
            ->where('programme_id', $programme_id);
        
        if ($statut) {
            $this->db->where('statut', $statut);
        }
        
        $result = $this->db->get($this->table)->row_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result ?: array();
    }
    
    /**
     * Calculate progression percentage for an enrollment
     *
     * @param int $inscription_id Enrollment ID
     * @return array Array with 'total_sujets', 'sujets_acquis', 'pourcentage'
     */
    public function calculate_progression($inscription_id) {
        // Get inscription
        $inscription = $this->get($inscription_id);
        if (!$inscription) {
            return array('total_sujets' => 0, 'sujets_acquis' => 0, 'pourcentage' => 0);
        }
        
        // Count total subjects in programme
        $this->db->select('COUNT(DISTINCT fs.id) as total')
            ->from('formation_lecons fl')
            ->join('formation_sujets fs', 'fl.id = fs.lecon_id', 'left')
            ->where('fl.programme_id', $inscription['programme_id']);
        
        $total_result = $this->db->get()->row_array();
        $total_sujets = $total_result['total'] ?? 0;
        
        if ($total_sujets == 0) {
            return array('total_sujets' => 0, 'sujets_acquis' => 0, 'pourcentage' => 0);
        }
        
        // Count acquired subjects (niveau = 'Q')
        $this->db->select('COUNT(DISTINCT fe.sujet_id) as acquis')
            ->from('formation_seances fse')
            ->join('formation_evaluations fe', 'fse.id = fe.seance_id', 'left')
            ->where('fse.inscription_id', $inscription_id)
            ->where('fe.niveau', 'Q');
        
        $acquis_result = $this->db->get()->row_array();
        $sujets_acquis = $acquis_result['acquis'] ?? 0;
        
        $pourcentage = $total_sujets > 0 ? round(($sujets_acquis / $total_sujets) * 100, 1) : 0;
        
        return array(
            'total_sujets' => $total_sujets,
            'sujets_acquis' => $sujets_acquis,
            'pourcentage' => $pourcentage
        );
    }

    /**
     * Get enrollment selector for a pilot (open enrollments only)
     *
     * @param string $pilote_id Pilot member login
     * @return array [id => "Programme (depuis Date ouverture)"]
     */
    public function get_selector_for_pilote($pilote_id) {
        $inscriptions = $this->get_ouvertes($pilote_id);
        $result = array('' => '-- Aucune (seance libre) --');
        foreach ($inscriptions as $inscription) {
            $result[$inscription['id']] = $inscription['programme_titre'] .
                ' (depuis ' . $inscription['date_ouverture'] . ')';
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
        $this->update('id', $data, $id);
        // update() throws error on failure, so if we're here it succeeded
        return true;
    }

    /**
     * Reactivate a suspended enrollment
     *
     * @param int $id Enrollment ID
     * @return bool Success
     */
    public function reactiver($id) {
        // Direct SQL update to handle NULL values correctly
        // Cast to int for security since it's the primary key
        $id = (int) $id;
        $sql = "UPDATE {$this->table} SET 
                statut = 'ouverte',
                date_suspension = NULL,
                motif_suspension = NULL
                WHERE id = {$id}";
        
        $this->db->query($sql);
        
        // Check for errors
        $error_msg = $this->db->_error_message();
        if (!empty($error_msg)) {
            gvv_error("MySQL Error: $error_msg");
        }
        
        return true;
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
        $this->update('id', $data, $id);
        // update() throws error on failure, so if we're here it succeeded
        return true;
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
        // Check if section exists BEFORE starting query construction
        $section_filter_needed = false;
        if ($this->section_id !== null && $this->section_id !== '') {
            $query = $this->db->where('id', $this->section_id)->get('sections');
            $section_filter_needed = ($query->num_rows() > 0);
        }

        // Now build the main query
        $this->db->select('i.*, p.code as programme_code, p.titre as programme_titre,
            m.mnom as pilote_nom, m.mprenom as pilote_prenom,
            inst.mnom as instructeur_nom, inst.mprenom as instructeur_prenom')
            ->from($this->table . ' i')
            ->join('formation_programmes p', 'i.programme_id = p.id', 'left')
            ->join('membres m', 'i.pilote_id = m.mlogin', 'left')
            ->join('membres inst', 'i.instructeur_referent_id = inst.mlogin', 'left');

        // Filter by section: show only inscriptions for programs visible to current section
        if ($section_filter_needed) {
            // Section valide : afficher inscriptions des programmes globaux (p.section_id IS NULL) + ceux de cette section
            $this->db->where("(p.section_id IS NULL OR p.section_id = " . (int) $this->section_id . ")", null, false);
        }

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
        // Check if section exists BEFORE starting query construction
        $section_filter_needed = false;
        if ($this->section_id !== null && $this->section_id !== '') {
            $query = $this->db->where('id', $this->section_id)->get('sections');
            $section_filter_needed = ($query->num_rows() > 0);
        }

        // Now build the main query
        $this->db->from($this->table . ' i')
            ->join('formation_programmes p', 'i.programme_id = p.id', 'left');

        // Filter by section: show only inscriptions for programs visible to current section
        if ($section_filter_needed) {
            // Section valide : afficher inscriptions des programmes globaux + ceux de cette section
            $this->db->where("(p.section_id IS NULL OR p.section_id = " . (int) $this->section_id . ")", null, false);
        }

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
        // Check if section exists BEFORE starting query construction
        $section_filter_needed = false;
        if ($this->section_id !== null && $this->section_id !== '') {
            $query = $this->db->where('id', $this->section_id)->get('sections');
            $section_filter_needed = ($query->num_rows() > 0);
        }

        // Now build the main query
        $this->db->select('i.*, p.code as programme_code, p.titre as programme_titre,
            m.mnom as pilote_nom, m.mprenom as pilote_prenom')
            ->from($this->table . ' i')
            ->join('formation_programmes p', 'i.programme_id = p.id', 'left')
            ->join('membres m', 'i.pilote_id = m.mlogin', 'left')
            ->where('i.instructeur_referent_id', $instructeur_id);

        // Filter by section: show only inscriptions for programs visible to current section
        if ($section_filter_needed) {
            // Section valide : afficher inscriptions des programmes globaux + ceux de cette section
            $this->db->where("(p.section_id IS NULL OR p.section_id = " . (int) $this->section_id . ")", null, false);
        }

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
     * Get formations grouped by status for a given year
     *
     * @param int $year Year to filter
     * @return array Associative array with keys: cloturees, abandonnees, suspendues, ouvertes, en_cours
     */
    public function get_by_year($year) {
        $result = array(
            'cloturees' => array(),
            'abandonnees' => array(),
            'suspendues' => array(),
            'ouvertes' => array(),
            'en_cours' => array()
        );

        $select = 'i.*, p.code as programme_code, p.titre as programme_titre,
            m.mnom as pilote_nom, m.mprenom as pilote_prenom,
            inst.mnom as instructeur_nom, inst.mprenom as instructeur_prenom';

        // Clôturées avec succès dans l'année
        $this->db->select($select)
            ->from($this->table . ' i')
            ->join('formation_programmes p', 'i.programme_id = p.id', 'left')
            ->join('membres m', 'i.pilote_id = m.mlogin', 'left')
            ->join('membres inst', 'i.instructeur_referent_id = inst.mlogin', 'left')
            ->where('i.statut', 'cloturee')
            ->where('YEAR(i.date_cloture)', $year)
            ->order_by('i.date_cloture', 'desc');
        $result['cloturees'] = $this->db->get()->result_array();

        // Abandonnées dans l'année
        $this->db->select($select)
            ->from($this->table . ' i')
            ->join('formation_programmes p', 'i.programme_id = p.id', 'left')
            ->join('membres m', 'i.pilote_id = m.mlogin', 'left')
            ->join('membres inst', 'i.instructeur_referent_id = inst.mlogin', 'left')
            ->where('i.statut', 'abandonnee')
            ->where('YEAR(i.date_cloture)', $year)
            ->order_by('i.date_cloture', 'desc');
        $result['abandonnees'] = $this->db->get()->result_array();

        // Suspendues dans l'année
        $this->db->select($select)
            ->from($this->table . ' i')
            ->join('formation_programmes p', 'i.programme_id = p.id', 'left')
            ->join('membres m', 'i.pilote_id = m.mlogin', 'left')
            ->join('membres inst', 'i.instructeur_referent_id = inst.mlogin', 'left')
            ->where('i.statut', 'suspendue')
            ->where('YEAR(i.date_suspension)', $year)
            ->order_by('i.date_suspension', 'desc');
        $result['suspendues'] = $this->db->get()->result_array();

        // Ouvertes dans l'année (toutes formations ouvertes cette année, quel que soit le statut actuel)
        $this->db->select($select)
            ->from($this->table . ' i')
            ->join('formation_programmes p', 'i.programme_id = p.id', 'left')
            ->join('membres m', 'i.pilote_id = m.mlogin', 'left')
            ->join('membres inst', 'i.instructeur_referent_id = inst.mlogin', 'left')
            ->where('YEAR(i.date_ouverture)', $year)
            ->order_by('i.date_ouverture', 'desc');
        $result['ouvertes'] = $this->db->get()->result_array();

        // En cours (ouvertes avant ou pendant l'année)
        $this->db->select($select)
            ->from($this->table . ' i')
            ->join('formation_programmes p', 'i.programme_id = p.id', 'left')
            ->join('membres m', 'i.pilote_id = m.mlogin', 'left')
            ->join('membres inst', 'i.instructeur_referent_id = inst.mlogin', 'left')
            ->where('i.statut', 'ouverte')
            ->where('YEAR(i.date_ouverture) <=', $year)
            ->order_by('i.date_ouverture', 'desc');
        $result['en_cours'] = $this->db->get()->result_array();

        return $result;
    }

    /**
     * Get year selector from inscription dates
     *
     * @param string $date_field Date field name (default: 'date_ouverture')
     * @return array Year options [year => year]
     */
    public function getYearSelector($date_field = 'date_ouverture') {
        // Get years from specified date field
        $query = $this->db->select("YEAR($date_field) as year")
            ->from($this->table)
            ->where("$date_field IS NOT NULL")
            ->order_by('year', 'ASC')
            ->group_by('year')
            ->get();

        $years = array();
        if ($query) {
            foreach ($query->result_array() as $row) {
                if (!empty($row['year'])) {
                    $years[$row['year']] = $row['year'];
                }
            }
        }

        // Also get years from date_cloture
        $query2 = $this->db->select('YEAR(date_cloture) as year')
            ->from($this->table)
            ->where('date_cloture IS NOT NULL')
            ->order_by('year', 'ASC')
            ->group_by('year')
            ->get();

        if ($query2) {
            foreach ($query2->result_array() as $row) {
                if (!empty($row['year'])) {
                    $years[$row['year']] = $row['year'];
                }
            }
        }

        ksort($years);
        return $years;
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
