<?php
/**
 *    GVV Gestion vol a voile
 *    Copyright (C) 2011  Philippe Boissel & Frederic Peignot
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Formation Autorisation Solo Model
 *
 * Handles solo flight authorizations for students.
 *
 * @author GVV Development Team
 * @see doc/design_notes/autorisations_vol_solo_plan.md
 */
class Formation_autorisation_solo_model extends Common_Model {
    public $table = 'formation_autorisations_solo';
    protected $primary_key = 'id';

    function __construct() {
        parent::__construct();
    }

    /**
     * Get an authorization by its ID
     *
     * @param int $id Authorization ID
     * @return array Authorization data or empty array if not found
     */
    public function get($id) {
        return $this->get_by_id('id', $id);
    }

    /**
     * Get authorization with full details (inscription, student, instructor info)
     *
     * @param int $id Authorization ID
     * @return array Authorization with related data
     */
    public function get_full($id) {
        $this->db->select('a.*,
            i.programme_id, p.code as programme_code, p.titre as programme_titre,
            eleve.mnom as eleve_nom, eleve.mprenom as eleve_prenom,
            inst.mnom as instructeur_nom, inst.mprenom as instructeur_prenom,
            s.nom as section_nom')
            ->from($this->table . ' a')
            ->join('formation_inscriptions i', 'a.inscription_id = i.id', 'left')
            ->join('formation_programmes p', 'i.programme_id = p.id', 'left')
            ->join('membres eleve', 'a.eleve_id = eleve.mlogin', 'left')
            ->join('membres inst', 'a.instructeur_id = inst.mlogin', 'left')
            ->join('sections s', 'a.section_id = s.id', 'left')
            ->where('a.id', $id);

        $result = $this->db->get()->row_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result ?: array();
    }

    /**
     * Get all authorizations for an inscription (formation)
     *
     * @param int $inscription_id Inscription ID
     * @return array List of authorizations
     */
    public function get_by_inscription($inscription_id) {
        $this->db->select('a.*,
            inst.mnom as instructeur_nom, inst.mprenom as instructeur_prenom')
            ->from($this->table . ' a')
            ->join('membres inst', 'a.instructeur_id = inst.mlogin', 'left')
            ->where('a.inscription_id', $inscription_id)
            ->order_by('a.date_autorisation', 'desc');

        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Get all authorizations for a student
     *
     * @param string $eleve_id Student member login
     * @return array List of authorizations
     */
    public function get_by_eleve($eleve_id) {
        $this->db->select('a.*,
            p.code as programme_code, p.titre as programme_titre,
            inst.mnom as instructeur_nom, inst.mprenom as instructeur_prenom')
            ->from($this->table . ' a')
            ->join('formation_inscriptions i', 'a.inscription_id = i.id', 'left')
            ->join('formation_programmes p', 'i.programme_id = p.id', 'left')
            ->join('membres inst', 'a.instructeur_id = inst.mlogin', 'left')
            ->where('a.eleve_id', $eleve_id)
            ->order_by('a.date_autorisation', 'desc');

        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Get all authorizations created by an instructor
     *
     * @param string $instructeur_id Instructor member login
     * @return array List of authorizations
     */
    public function get_by_instructeur($instructeur_id) {
        $this->db->select('a.*,
            p.code as programme_code, p.titre as programme_titre,
            eleve.mnom as eleve_nom, eleve.mprenom as eleve_prenom')
            ->from($this->table . ' a')
            ->join('formation_inscriptions i', 'a.inscription_id = i.id', 'left')
            ->join('formation_programmes p', 'i.programme_id = p.id', 'left')
            ->join('membres eleve', 'a.eleve_id = eleve.mlogin', 'left')
            ->where('a.instructeur_id', $instructeur_id)
            ->order_by('a.date_autorisation', 'desc');

        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Get authorizations for list view with pagination
     *
     * @param array $filters Optional filters
     * @param int $limit Max results
     * @param int $offset Start offset
     * @return array Authorizations with related info
     */
    public function select_page($filters = array(), $limit = 100, $offset = 0) {
        $this->db->select('a.id, a.date_autorisation, a.machine_id, a.consignes, a.date_creation,
            p.code as programme_code, p.titre as programme_titre,
            eleve.mnom as eleve_nom, eleve.mprenom as eleve_prenom,
            inst.mnom as instructeur_nom, inst.mprenom as instructeur_prenom,
            s.nom as section_nom')
            ->from($this->table . ' a')
            ->join('formation_inscriptions i', 'a.inscription_id = i.id', 'left')
            ->join('formation_programmes p', 'i.programme_id = p.id', 'left')
            ->join('membres eleve', 'a.eleve_id = eleve.mlogin', 'left')
            ->join('membres inst', 'a.instructeur_id = inst.mlogin', 'left')
            ->join('sections s', 'a.section_id = s.id', 'left');

        // Filter by section if set
        if ($this->section_id !== null && $this->section_id !== '') {
            $this->db->where('a.section_id', $this->section_id);
        }

        // Apply filters
        if (!empty($filters['eleve_id'])) {
            $this->db->where('a.eleve_id', $filters['eleve_id']);
        }
        if (!empty($filters['instructeur_id'])) {
            $this->db->where('a.instructeur_id', $filters['instructeur_id']);
        }
        if (!empty($filters['inscription_id'])) {
            $this->db->where('a.inscription_id', $filters['inscription_id']);
        }
        if (!empty($filters['date_from'])) {
            $this->db->where('a.date_autorisation >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $this->db->where('a.date_autorisation <=', $filters['date_to']);
        }

        $this->db->order_by('a.date_autorisation', 'desc')
            ->limit($limit, $offset);

        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Count authorizations matching filters
     *
     * @param array $filters Optional filters
     * @return int Count
     */
    public function count_filtered($filters = array()) {
        $this->db->from($this->table . ' a');

        // Filter by section if set
        if ($this->section_id !== null && $this->section_id !== '') {
            $this->db->where('a.section_id', $this->section_id);
        }

        // Apply filters
        if (!empty($filters['eleve_id'])) {
            $this->db->where('a.eleve_id', $filters['eleve_id']);
        }
        if (!empty($filters['instructeur_id'])) {
            $this->db->where('a.instructeur_id', $filters['instructeur_id']);
        }
        if (!empty($filters['inscription_id'])) {
            $this->db->where('a.inscription_id', $filters['inscription_id']);
        }

        return $this->db->count_all_results();
    }

    /**
     * Create a new authorization
     *
     * @param array $data Authorization data
     * @return int|false Inserted ID or false on failure
     */
    public function create_autorisation($data) {
        // Set timestamps
        $data['date_creation'] = date('Y-m-d H:i:s');
        $data['date_modification'] = null;

        return $this->create($data);
    }

    /**
     * Update an authorization
     *
     * @param int $id Authorization ID
     * @param array $data Data to update
     * @return bool Success
     */
    public function update_autorisation($id, $data) {
        // Update modification timestamp
        $data['date_modification'] = date('Y-m-d H:i:s');

        $this->update('id', $data, $id);
        return true;
    }

    /**
     * Get authorization image for display
     *
     * @param int $id Authorization ID
     * @return string "Eleve - Date - Machine"
     */
    public function image($id) {
        $auth = $this->get_full($id);
        if ($auth) {
            return $auth['eleve_prenom'] . ' ' . $auth['eleve_nom'] .
                ' - ' . date('d/m/Y', strtotime($auth['date_autorisation'])) .
                ' - ' . $auth['machine_id'];
        }
        return '';
    }

    /**
     * Get selector for inscriptions (open formations for current section)
     *
     * @return array [id => "Eleve - Programme"]
     */
    public function get_inscription_selector() {
        $this->load->model('formation_inscription_model');

        $inscriptions = $this->formation_inscription_model->select_page(
            array('statut' => 'ouverte'),
            1000,
            0
        );

        $result = array('' => '-- Sélectionner --');
        foreach ($inscriptions as $inscription) {
            $result[$inscription['id']] = $inscription['pilote_prenom'] . ' ' .
                $inscription['pilote_nom'] . ' - ' . $inscription['programme_code'];
        }
        return $result;
    }

    /**
     * Get inscriptions with type_aeronef for aircraft filtering
     *
     * @return array Array of inscriptions with id, label, and type_aeronef
     */
    public function get_inscriptions_with_type() {
        $CI = &get_instance();
        $section_id = $CI->session->userdata('section');

        $CI->db->select('i.id, i.pilote_id, m.mprenom as pilote_prenom, m.mnom as pilote_nom,
            p.code as programme_code, p.type_aeronef')
            ->from('formation_inscriptions i')
            ->join('formation_programmes p', 'i.programme_id = p.id', 'left')
            ->join('membres m', 'i.pilote_id = m.mlogin', 'left')
            ->where('i.statut', 'ouverte');

        // Filter by section if set
        if ($section_id && $section_id != '' && $section_id != 'Toutes') {
            $CI->db->where("(p.section_id IS NULL OR p.section_id = " . (int)$section_id . ")", null, false);
        }

        $CI->db->order_by('m.mnom, m.mprenom');

        $inscriptions = $CI->db->get()->result_array();

        $result = array();
        foreach ($inscriptions as $inscription) {
            $result[] = array(
                'id' => $inscription['id'],
                'label' => $inscription['pilote_prenom'] . ' ' . $inscription['pilote_nom'] . ' - ' . $inscription['programme_code'],
                'pilote_id' => $inscription['pilote_id'],
                'type_aeronef' => $inscription['type_aeronef']
            );
        }
        return $result;
    }
}

/* End of file formation_autorisation_solo_model.php */
/* Location: ./application/models/formation_autorisation_solo_model.php */
