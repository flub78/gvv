<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Formation Type Seance Model
 *
 * Manages the reference table of training session types.
 * Each type specifies whether the session is in-flight ('vol') or
 * theoretical ground instruction ('theorique').
 *
 * @package models
 * @see doc/prds/gestion_des_seances_theoriques.md
 * @see doc/plans/seances_theoriques_plan.md Phase 1
 */
class Formation_type_seance_model extends Common_Model {
    public $table = 'formation_types_seance';
    protected $primary_key = 'id';

    function __construct() {
        parent::__construct();
    }

    /**
     * Returns all session types ordered by nature then name
     * @return array
     */
    public function get_all() {
        return $this->db
            ->order_by('nature', 'asc')
            ->order_by('nom', 'asc')
            ->get($this->table)
            ->result_array();
    }

    /**
     * Returns active session types
     * @param string|null $nature Filter by 'vol' or 'theorique'
     * @return array
     */
    public function get_active($nature = null) {
        $this->db->where('actif', 1);
        if ($nature !== null) {
            $this->db->where('nature', $nature);
        }
        return $this->db
            ->order_by('nature', 'asc')
            ->order_by('nom', 'asc')
            ->get($this->table)
            ->result_array();
    }

    /**
     * Returns a selector array (id => nom) of all active types, optionally filtered by nature
     * @param string|null $nature 'vol' or 'theorique' or null for all
     * @return array
     */
    public function get_selector($nature = null) {
        $result = array('' => '');
        foreach ($this->get_active($nature) as $row) {
            $result[$row['id']] = $row['nom'];
        }
        return $result;
    }

    /**
     * Returns paginated list for the admin view
     * @return array
     */
    public function select_page($per_page = 0, $premier = 0, $selection = array()) {
        $rows = $this->get_all();
        $this->gvvmetadata->store_table('formation_types_seance', $rows);
        return $rows;
    }

    /**
     * Returns a human-readable label for a type id
     * @param mixed $key
     * @return string
     */
    public function image($key) {
        if ($key == '')
            return '';
        $row = $this->get_by_id('id', $key);
        if ($row && isset($row['nom'])) {
            return $row['nom'];
        }
        return '';
    }

    /**
     * Returns types that have a periodicite_max_jours constraint defined
     * @return array
     */
    public function get_with_periodicite() {
        return $this->db
            ->where('actif', 1)
            ->where('periodicite_max_jours IS NOT NULL', null, false)
            ->order_by('nature', 'asc')
            ->order_by('nom', 'asc')
            ->get($this->table)
            ->result_array();
    }

    /**
     * Returns active pilots (open inscription) who exceed the periodicity threshold
     * for the given session type.
     *
     * A pilot is non-compliant if:
     * - They have no session of this type recorded (seance or participant), or
     * - Their last session of this type is older than periodicite_max_jours days.
     *
     * @param int $type_id
     * @return array Each row: mlogin, mnom, mprenom, derniere_seance (DATE|null), jours_ecoules (int|null), periodicite_max_jours
     */
    public function get_eleves_non_conformes($type_id) {
        $type = $this->get_by_id('id', $type_id);
        if (!$type || empty($type['periodicite_max_jours'])) {
            return array();
        }

        // Pilots with an open inscription
        $sql = "
            SELECT
                m.mlogin,
                m.mnom,
                m.mprenom,
                MAX(last_seance.date_seance) AS derniere_seance,
                DATEDIFF(CURDATE(), MAX(last_seance.date_seance)) AS jours_ecoules,
                ? AS periodicite_max_jours
            FROM membres m
            INNER JOIN formation_inscriptions fi
                ON fi.pilote_id = m.mlogin AND fi.statut = 'ouverte'
            LEFT JOIN (
                -- Sessions where pilot is the direct pilot_id
                SELECT pilote_id AS membre_id, date_seance
                FROM formation_seances
                WHERE type_seance_id = ? AND pilote_id IS NOT NULL
                UNION ALL
                -- Sessions where pilot is a participant (theoretical group sessions)
                SELECT sp.pilote_id AS membre_id, s.date_seance
                FROM formation_seances_participants sp
                INNER JOIN formation_seances s ON s.id = sp.seance_id
                WHERE s.type_seance_id = ?
            ) AS last_seance ON last_seance.membre_id = m.mlogin
            GROUP BY m.mlogin, m.mnom, m.mprenom
            HAVING derniere_seance IS NULL
                OR jours_ecoules > ?
            ORDER BY jours_ecoules DESC, m.mnom ASC, m.mprenom ASC
        ";

        $query = $this->db->query($sql, array(
            (int)$type['periodicite_max_jours'],
            (int)$type_id,
            (int)$type_id,
            (int)$type['periodicite_max_jours'],
        ));

        return $query->result_array();
    }

    /**
     * Deactivates a type (soft delete)
     * @param int $id
     * @return bool
     */
    public function deactivate($id) {
        $this->db->where('id', $id);
        return $this->db->update($this->table, array('actif' => 0));
    }

    /**
     * Returns true if the type is referenced by at least one seance
     * @param int $id
     * @return bool
     */
    public function is_in_use($id) {
        $count = $this->db
            ->where('type_seance_id', $id)
            ->count_all_results('formation_seances');
        return $count > 0;
    }
}

/* End of file formation_type_seance_model.php */
/* Location: ./application/models/formation_type_seance_model.php */
