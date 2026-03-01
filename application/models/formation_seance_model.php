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
            p.type_aeronef,
            m.mnom as pilote_nom, m.mprenom as pilote_prenom,
            inst.mnom as instructeur_nom, inst.mprenom as instructeur_prenom,
            COALESCE(mp.mpmodele, ma.macmodele) as machine_modele,
            s.machine_id as machine_immat', FALSE)
            ->from($this->table . ' s')
            ->join('formation_programmes p', 's.programme_id = p.id', 'left')
            ->join('membres m', 's.pilote_id = m.mlogin', 'left')
            ->join('membres inst', 's.instructeur_id = inst.mlogin', 'left')
            ->join('machinesp mp', 's.machine_id = mp.mpimmat', 'left')
            ->join('machinesa ma', 's.machine_id = ma.macimmat', 'left')
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
            COALESCE(mp.mpmodele, ma.macmodele) as machine_modele', FALSE)
            ->from($this->table . ' s')
            ->join('membres inst', 's.instructeur_id = inst.mlogin', 'left')
            ->join('machinesp mp', 's.machine_id = mp.mpimmat', 'left')
            ->join('machinesa ma', 's.machine_id = ma.macimmat', 'left')
            ->where('s.inscription_id', $inscription_id)
            ->order_by('s.date_seance', 'desc');

        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Get theoretical sessions for a pilot linked to a given programme
     *
     * Finds sessions of nature 'theorique' where the pilot appears in
     * formation_seances_participants and the programme matches the inscription.
     *
     * @param string $pilote_id    Pilot login
     * @param int    $programme_id Programme ID from the inscription
     * @return array List of theoretical sessions
     */
    public function get_theoriques_by_pilote_programme($pilote_id, $programme_id) {
        $this->db->select('s.*, ts.nom as type_nom,
            inst.mnom as instructeur_nom, inst.mprenom as instructeur_prenom', FALSE)
            ->from($this->table . ' s')
            ->join('formation_types_seance ts', 's.type_seance_id = ts.id')
            ->join('formation_seances_participants fsp', 'fsp.seance_id = s.id')
            ->join('membres inst', 's.instructeur_id = inst.mlogin', 'left')
            ->where('ts.nature', 'theorique')
            ->where('fsp.pilote_id', $pilote_id)
            ->where('s.programme_id', $programme_id)
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
            COALESCE(mp.mpmodele, ma.macmodele) as machine_modele,
            CASE WHEN s.inscription_id IS NULL THEN 1 ELSE 0 END as is_libre', FALSE)
            ->from($this->table . ' s')
            ->join('formation_programmes p', 's.programme_id = p.id', 'left')
            ->join('membres inst', 's.instructeur_id = inst.mlogin', 'left')
            ->join('machinesp mp', 's.machine_id = mp.mpimmat', 'left')
            ->join('machinesa ma', 's.machine_id = ma.macimmat', 'left')
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
            COALESCE(mp.mpmodele, ma.macmodele) as machine_modele,
            CASE WHEN s.inscription_id IS NULL THEN 1 ELSE 0 END as is_libre', FALSE)
            ->from($this->table . ' s')
            ->join('formation_programmes p', 's.programme_id = p.id', 'left')
            ->join('membres m', 's.pilote_id = m.mlogin', 'left')
            ->join('machinesp mp', 's.machine_id = mp.mpimmat', 'left')
            ->join('machinesa ma', 's.machine_id = ma.macimmat', 'left')
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
     * @param array $filters Optional filters (pilote_id, instructeur_id, programme_id,
     *                       date_debut, date_fin, type, year, categorie_seance, nature)
     * @param int $limit Max results
     * @param int $offset Start offset
     * @return array Sessions with related info
     */
    public function select_page($filters = array(), $limit = 100, $offset = 0) {
        $this->db->select('s.*, p.code as programme_code, p.titre as programme_titre,
            m.mnom as pilote_nom, m.mprenom as pilote_prenom,
            inst.mnom as instructeur_nom, inst.mprenom as instructeur_prenom,
            COALESCE(mp.mpmodele, ma.macmodele) as machine_modele,
            CASE WHEN s.inscription_id IS NULL THEN "Libre" ELSE "Formation" END as type_seance,
            ts.nature as nature_seance,
            COUNT(DISTINCT part.id) as nb_participants', FALSE)
            ->from($this->table . ' s')
            ->join('formation_programmes p', 's.programme_id = p.id', 'left')
            ->join('membres m', 's.pilote_id = m.mlogin', 'left')
            ->join('membres inst', 's.instructeur_id = inst.mlogin', 'left')
            ->join('machinesp mp', 's.machine_id = mp.mpimmat', 'left')
            ->join('machinesa ma', 's.machine_id = ma.macimmat', 'left')
            ->join('formation_types_seance ts', 's.type_seance_id = ts.id', 'left')
            ->join('formation_seances_participants part', 'part.seance_id = s.id', 'left')
            ->group_by('s.id');

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
        if (!empty($filters['year'])) {
            $this->db->where('YEAR(s.date_seance)', $filters['year']);
        }
        if (!empty($filters['categorie_seance'])) {
            // Use FIND_IN_SET for comma-separated categories
            $escaped = $this->db->escape_str($filters['categorie_seance']);
            $this->db->where("FIND_IN_SET('" . $escaped . "', REPLACE(s.categorie_seance, ', ', ',')) > 0", NULL, FALSE);
        }
        if (!empty($filters['nature'])) {
            if ($filters['nature'] === 'vol') {
                $this->db->where('ts.nature', 'vol');
            } else if ($filters['nature'] === 'theorique') {
                $this->db->where('ts.nature', 'theorique');
            }
        }

        $this->db->order_by('s.date_seance', 'desc')
            ->limit($limit, $offset);

        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Get year selector for libre sessions (no inscription)
     *
     * @return array Year options for dropdown
     */
    public function getYearSelectorLibres() {
        $query = $this->db->select('YEAR(date_seance) as year')
            ->from($this->table)
            ->where('inscription_id IS NULL')
            ->order_by('year ASC')
            ->group_by('year')
            ->get();

        $year_selector = array();
        if ($query) {
            $results = $query->result_array();
            foreach ($results as $row) {
                $year_selector[$row['year']] = $row['year'];
            }
        }
        return $year_selector;
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
     * Get session statistics grouped by instructor for a given year
     *
     * @param int $year Year to filter
     * @return array Array grouped by instructor with formation and libre counts
     */
    public function get_stats_par_instructeur($year) {
        $this->db->select('s.instructeur_id, s.inscription_id, s.programme_id,
            inst.mnom as instructeur_nom, inst.mprenom as instructeur_prenom,
            p.code as programme_code, p.titre as programme_titre,
            m.mnom as pilote_nom, m.mprenom as pilote_prenom,
            COUNT(*) as nb_seances,
            CASE WHEN s.inscription_id IS NULL THEN 1 ELSE 0 END as is_libre', FALSE)
            ->from($this->table . ' s')
            ->join('membres inst', 's.instructeur_id = inst.mlogin', 'left')
            ->join('formation_programmes p', 's.programme_id = p.id', 'left')
            ->join('formation_inscriptions fi', 's.inscription_id = fi.id', 'left')
            ->join('membres m', 'fi.pilote_id = m.mlogin', 'left')
            ->where('YEAR(s.date_seance)', $year)
            ->group_by('s.instructeur_id, s.inscription_id, s.programme_id')
            ->order_by('inst.mnom', 'asc')
            ->order_by('inst.mprenom', 'asc');

        $rows = $this->db->get()->result_array();

        // Restructure by instructor
        $instructeurs = array();
        foreach ($rows as $row) {
            $iid = $row['instructeur_id'];
            if (!isset($instructeurs[$iid])) {
                $instructeurs[$iid] = array(
                    'id' => $iid,
                    'nom' => $row['instructeur_nom'],
                    'prenom' => $row['instructeur_prenom'],
                    'formations' => array(),
                    'nb_seances_libres' => 0
                );
            }

            if (empty($row['inscription_id'])) {
                $instructeurs[$iid]['nb_seances_libres'] += (int) $row['nb_seances'];
            } else {
                $instructeurs[$iid]['formations'][] = array(
                    'inscription_id' => $row['inscription_id'],
                    'programme_code' => $row['programme_code'],
                    'programme_titre' => $row['programme_titre'],
                    'pilote_nom' => $row['pilote_nom'],
                    'pilote_prenom' => $row['pilote_prenom'],
                    'nb_seances' => (int) $row['nb_seances']
                );
            }
        }

        return array_values($instructeurs);
    }

    /**
     * Get year selector for all seances (formation + libre)
     *
     * @param string $date_field Date field name (default: 'date_seance')
     * @return array Year options [year => year]
     */
    public function getYearSelector($date_field = 'date_seance') {
        $query = $this->db->select("YEAR($date_field) as year")
            ->from($this->table)
            ->order_by('year', 'ASC')
            ->group_by('year')
            ->get();

        $year_selector = array();
        if ($query) {
            foreach ($query->result_array() as $row) {
                if (!empty($row['year'])) {
                    $year_selector[$row['year']] = $row['year'];
                }
            }
        }
        return $year_selector;
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

    /**
     * Get session categories selector from configuration
     *
     * @return array Categories as [value => label] for dropdown
     */
    public function get_categories_selector() {
        $this->load->model('configuration_model');
        $config_value = $this->configuration_model->get_param('formation.categories_seance');

        $categories = array('' => '');
        if (!empty($config_value)) {
            $items = array_map('trim', explode(',', $config_value));
            foreach ($items as $item) {
                if (!empty($item)) {
                    $categories[$item] = $item;
                }
            }
        }
        return $categories;
    }

    /**
     * Count sessions by category for a given year
     * Handles sessions with multiple categories (comma-separated)
     *
     * @param int $year Year to filter
     * @return array Array of [categorie => count]
     */
    public function count_by_categorie($year) {
        // Fetch all sessions with categories for the year
        $this->db->select('categorie_seance')
            ->from($this->table)
            ->where('YEAR(date_seance)', $year)
            ->where('categorie_seance IS NOT NULL')
            ->where('categorie_seance !=', '');

        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());

        // Count each category separately (sessions can have multiple categories)
        $stats = array();
        foreach ($result as $row) {
            $categories = array_map('trim', explode(',', $row['categorie_seance']));
            foreach ($categories as $cat) {
                if (!empty($cat)) {
                    if (!isset($stats[$cat])) {
                        $stats[$cat] = 0;
                    }
                    $stats[$cat]++;
                }
            }
        }

        // Sort by count descending
        arsort($stats);
        
        return $stats;
    }

    // -----------------------------------------------------------------------
    // Méthodes pour les séances théoriques (Phase 2)
    // -----------------------------------------------------------------------

    /**
     * Indique si une séance est de nature théorique (via son type_seance_id).
     *
     * @param int $seance_id
     * @return bool
     */
    public function is_theorique($seance_id) {
        $row = $this->db
            ->select('ts.nature')
            ->from($this->table . ' s')
            ->join('formation_types_seance ts', 's.type_seance_id = ts.id', 'left')
            ->where('s.id', (int)$seance_id)
            ->limit(1)
            ->get()->row_array();
        return isset($row['nature']) && $row['nature'] === 'theorique';
    }

    /**
     * Crée une séance théorique et insère ses participants dans une transaction.
     *
     * @param array $seance_data  Données de formation_seances (sans pilote_id)
     * @param array $pilote_ids   Liste de mlogin des participants
     * @return int|false  ID de la séance créée, ou false en cas d'erreur
     */
    public function create_theorique($seance_data, array $pilote_ids) {
        $this->db->trans_start();

        $seance_id = $this->create($seance_data);

        if ($seance_id && !empty($pilote_ids)) {
            $this->load->model('formation_seance_participants_model');
            $this->formation_seance_participants_model->replace_participants($seance_id, $pilote_ids);
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            gvv_error("formation_seance_model::create_theorique failed");
            return false;
        }

        return $seance_id;
    }

    /**
     * Met à jour une séance théorique et remplace la liste des participants.
     *
     * @param int   $seance_id
     * @param array $seance_data Données de formation_seances
     * @param array $pilote_ids  Liste de mlogin des participants
     * @return bool
     */
    public function update_theorique($seance_id, $seance_data, array $pilote_ids) {
        $this->db->trans_start();

        $this->update('id', array_merge($seance_data, array('id' => $seance_id)));

        $this->load->model('formation_seance_participants_model');
        $this->formation_seance_participants_model->replace_participants($seance_id, $pilote_ids);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            gvv_error("formation_seance_model::update_theorique failed");
            return false;
        }

        return true;
    }

    /**
     * Retourne les participants d'une séance théorique.
     *
     * @param int $seance_id
     * @return array
     */
    public function get_participants($seance_id) {
        $this->load->model('formation_seance_participants_model');
        return $this->formation_seance_participants_model->get_by_seance($seance_id);
    }

    /**
     * Retourne les séances théoriques d'un instructeur pour une année.
     *
     * @param string   $instructeur_id  mlogin de l'instructeur
     * @param int|null $year            Année (null = toutes)
     * @return array
     */
    public function get_theoriques_by_instructeur($instructeur_id, $year = null) {
        $this->db
            ->select('s.*, ts.nom as type_nom, p.titre as programme_titre,
                COUNT(DISTINCT part.pilote_id) as nb_participants', FALSE)
            ->from($this->table . ' s')
            ->join('formation_types_seance ts', 's.type_seance_id = ts.id', 'left')
            ->join('formation_programmes p', 's.programme_id = p.id', 'left')
            ->join('formation_seances_participants part', 'part.seance_id = s.id', 'left')
            ->where('s.instructeur_id', $instructeur_id)
            ->where('ts.nature', 'theorique')
            ->group_by('s.id')
            ->order_by('s.date_seance', 'desc');

        if ($year) {
            $this->db->where('YEAR(s.date_seance)', (int)$year);
        }

        return $this->db->get()->result_array();
    }

    // -----------------------------------------------------------------------
    // Méthodes pour les rapports annuels (Phase 3)
    // -----------------------------------------------------------------------

    /**
     * Statistiques annuelles consolidées par instructeur (vol + théorique).
     *
     * Retourne un tableau indexé par instructeur_id :
     * [
     *   instructeur_id => [
     *     'id', 'nom', 'prenom',
     *     'nb_seances_vol', 'heures_vol',
     *     'nb_seances_sol', 'heures_sol',
     *     'nb_eleves_distincts'
     *   ]
     * ]
     *
     * @param int $year
     * @return array
     */
    public function get_stats_annuels_par_instructeur($year) {
        // Séances VOL
        $sql_vol = "
            SELECT s.instructeur_id,
                   inst.mnom AS instructeur_nom, inst.mprenom AS instructeur_prenom,
                   COUNT(*) AS nb_seances_vol,
                   ROUND(SUM(COALESCE(TIME_TO_SEC(s.duree), 0)) / 3600, 2) AS heures_vol,
                   COUNT(DISTINCT s.pilote_id) AS nb_eleves_vol
            FROM {$this->table} s
            LEFT JOIN membres inst ON s.instructeur_id = inst.mlogin
            LEFT JOIN formation_types_seance ts ON s.type_seance_id = ts.id
            WHERE YEAR(s.date_seance) = ?
              AND (ts.nature = 'vol' OR (ts.nature IS NULL AND s.pilote_id IS NOT NULL))
            GROUP BY s.instructeur_id, inst.mnom, inst.mprenom
        ";

        // Séances THÉORIQUES
        $sql_sol = "
            SELECT s.instructeur_id,
                   inst.mnom AS instructeur_nom, inst.mprenom AS instructeur_prenom,
                   COUNT(*) AS nb_seances_sol,
                   ROUND(SUM(COALESCE(TIME_TO_SEC(s.duree), 0)) / 3600, 2) AS heures_sol,
                   COUNT(DISTINCT p.pilote_id) AS nb_eleves_sol
            FROM {$this->table} s
            LEFT JOIN membres inst ON s.instructeur_id = inst.mlogin
            LEFT JOIN formation_types_seance ts ON s.type_seance_id = ts.id
            LEFT JOIN formation_seances_participants p ON p.seance_id = s.id
            WHERE YEAR(s.date_seance) = ?
              AND ts.nature = 'theorique'
            GROUP BY s.instructeur_id, inst.mnom, inst.mprenom
        ";

        $rows_vol = $this->db->query($sql_vol, array((int)$year))->result_array();
        $rows_sol = $this->db->query($sql_sol, array((int)$year))->result_array();

        $stats = array();

        foreach ($rows_vol as $r) {
            $id = $r['instructeur_id'];
            if (!isset($stats[$id])) {
                $stats[$id] = $this->_make_instructor_stats($id, $r['instructeur_nom'], $r['instructeur_prenom']);
            }
            $stats[$id]['nb_seances_vol'] = (int)$r['nb_seances_vol'];
            $stats[$id]['heures_vol']     = (float)$r['heures_vol'];
            $stats[$id]['nb_eleves_vol']  = (int)$r['nb_eleves_vol'];
        }

        foreach ($rows_sol as $r) {
            $id = $r['instructeur_id'];
            if (!isset($stats[$id])) {
                $stats[$id] = $this->_make_instructor_stats($id, $r['instructeur_nom'], $r['instructeur_prenom']);
            }
            $stats[$id]['nb_seances_sol'] = (int)$r['nb_seances_sol'];
            $stats[$id]['heures_sol']     = (float)$r['heures_sol'];
            $stats[$id]['nb_eleves_sol']  = (int)$r['nb_eleves_sol'];
        }

        // Sort by instructor name
        uasort($stats, function($a, $b) {
            return strcmp($a['nom'] . $a['prenom'], $b['nom'] . $b['prenom']);
        });

        return array_values($stats);
    }

    /**
     * Statistiques annuelles consolidées par programme.
     *
     * @param int $year
     * @return array Rows with: programme_id, programme_titre, nb_inscriptions,
     *               nb_seances_vol, heures_vol, nb_seances_sol, heures_sol
     */
    public function get_stats_annuels_par_programme($year) {
        $sql = "
            SELECT
                COALESCE(p.id, 0) AS programme_id,
                COALESCE(p.titre, '(sans programme)') AS programme_titre,
                SUM(CASE WHEN ts.nature = 'vol' OR (ts.nature IS NULL AND s.pilote_id IS NOT NULL) THEN 1 ELSE 0 END) AS nb_seances_vol,
                ROUND(SUM(CASE WHEN ts.nature = 'vol' OR (ts.nature IS NULL AND s.pilote_id IS NOT NULL)
                               THEN COALESCE(TIME_TO_SEC(s.duree), 0) ELSE 0 END) / 3600, 2) AS heures_vol,
                SUM(CASE WHEN ts.nature = 'theorique' THEN 1 ELSE 0 END) AS nb_seances_sol,
                ROUND(SUM(CASE WHEN ts.nature = 'theorique'
                               THEN COALESCE(TIME_TO_SEC(s.duree), 0) ELSE 0 END) / 3600, 2) AS heures_sol
            FROM {$this->table} s
            LEFT JOIN formation_programmes p ON s.programme_id = p.id
            LEFT JOIN formation_types_seance ts ON s.type_seance_id = ts.id
            WHERE YEAR(s.date_seance) = ?
            GROUP BY p.id, p.titre
            ORDER BY p.titre ASC
        ";

        return $this->db->query($sql, array((int)$year))->result_array();
    }

    private function _make_instructor_stats($id, $nom, $prenom) {
        return array(
            'id'            => $id,
            'nom'           => $nom ?? '',
            'prenom'        => $prenom ?? '',
            'nb_seances_vol' => 0,
            'heures_vol'    => 0.0,
            'nb_eleves_vol' => 0,
            'nb_seances_sol' => 0,
            'heures_sol'    => 0.0,
            'nb_eleves_sol' => 0,
        );
    }
}

/* End of file formation_seance_model.php */
/* Location: ./application/models/formation_seance_model.php */
