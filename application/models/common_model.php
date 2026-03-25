<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 *
 * @package models
 *
 *          Modèle parent
 */

/**
 * Le modèle commun implémente un simple CRUD (Create, Read, Update, Delete).
 *
 * Il fournit les fonction de création, lecture, mise à jour et suppression sur une table simple.
 * De cette façon tout le code courant est géré par ce module d'interface avec la base de données.
 * Dans les cas simple les enfants n'ont besoin que de déclarer le nom de la table
 * sur laquelle ils travaillent.
 */
class Common_Model extends CI_Model {
    public $table;
    protected $primary_key;
    protected static $table_columns_cache = array();

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();
        $this->load->library('gvvmetadata');

        $this->section_id = $this->session->userdata('section');
        $this->db->where('id', $this->section_id);

        $section_query = $this->db->get('sections');
        if ($this->db->_error_number()) {
            gvv_error("sql error: " . $this->db->_error_message());
            $this->section = array();
        } else {
            $this->section = $section_query->row_array();
        }
    }

    /**
     * Safe query execution with error handling
     * @param string|null $table Optional table name to override default
     * @return array|false Returns false on error, array of results on success
     */
    protected function safe_get() {
        // Execute the query
        $query = $this->db->get();

        // Check if query failed
        if ($query === FALSE) {
            // Log the error
            gvv_error("sql error: " . $this->db->_error_message());
            gvv_error("sql last query: " . $this->db->last_query());

            return FALSE;
        }

        return $query->result_array();
    }

    public function safe_count_all($table) {
        try {
            // First check if the table exists to prevent SQL errors
            if (!$this->db->table_exists($table)) {
                return -1;
            }

            // Perform the count query directly to avoid num_rows() issues
            $query = $this->db->query("SELECT COUNT(*) as count FROM " . $this->db->protect_identifiers($table));

            if ($query === FALSE) {
                gvv_error('sql: Count query failed: ' . $this->db->_error_message());
                return -1;
            }

            $row = $query->row();
            return (int)$row->count;
        } catch (Exception $e) {
            gvv_error('error', 'Exception in safe_count_all: ' . $e->getMessage());
            return -1;
        }
    }

    public function section_id() {
        return $this->section_id;
    }

    /**
     * 
     * [id => "2", nom => "ULM", description => "Section ULM de l'aéroclub d'Abbeville"]
     */
    public function section() {
        return $this->section;
    }

    /**
     * Accès en lecture au nom de la table
     *
     * @return string
     */
    public function table() {
        return $this->table;
    }

    /**
     * Retourne le nom de la clé primaire sur la table
     *
     * @return string
     */
    public function primary_key() {
        return $this->primary_key;
    }

    /**
     * Ajoute un élément
     *
     * @param $data hash
     *            des valeurs
     */
    public function create($data) {
        $this->inject_audit_fields($data, TRUE);

        if ($this->db->insert($this->table, $data)) {
            $last_id = $this->db->insert_id();
            gvv_debug("create successful, table=" . $this->table . ", \$last_id=$last_id, data=" . var_export($data, true));

            // Temporary debug: if insert_id() is empty even though insert() succeeded,
            // log the last query and DB error info to help diagnose AUTO_INCREMENT / driver issues.
            if (! $last_id) {
                gvv_debug("insert_id returned empty for table=" . $this->table . "; last_query=" . $this->db->last_query());
                $db_err_msg = $this->db->_error_message();
                $db_err_num = $this->db->_error_number();
                if (!empty($db_err_msg) || !empty($db_err_num)) {
                    gvv_debug("DB error while insert: (" . $db_err_num . ") " . $db_err_msg);
                }
                // Fallback to primary key value in provided data if present
                $last_id = isset($data[$this->primary_key]) ? $data[$this->primary_key] : null;
                gvv_debug("\$last_id fallback value=" . var_export($last_id, true));
            }
            return $last_id;
        } else {
            gvv_error("create error: " . $this->table . ' - ' . $this->db->_error_message());
            return FALSE;
        }
    }

    /**
     * delete
     *
     * @param unknown_type $where
     *            selection des éléments à détruire
     */
    function delete($where = array()) {
        $username = $this->current_username();
        gvv_info("delete requested, table=" . $this->table . ", by=" . $username . ", where=" . json_encode($where));
        $this->db->delete($this->table, $where);
    }

    /**
     * Retourne une ligne de base
     *
     * @return hash des valeurs
     */
    public function get_by_id($keyid, $keyvalue) {
        $this->db->where($keyid, $keyvalue);
        $res = $this->db->get($this->table)->row_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $res;
    }

    /**
     * Retourne le premier élément
     *
     * @param $where selection
     *            des éléments
     * @return hash des valeurs
     */
    public function get_first($where = array()) {
        return $this->db->select('*')->from($this->table)->where($where)->limit(1)->get()->row_array(0);
    }

    /**
     * Edite un element existant
     *
     * @param integer $id
     *            $id de l'élément
     * @param hash $data
     *            donnée à remplacer
     * @return bool Le résultat de la requête
     */
    public function update($keyid, $data, $keyvalue = '') {
        if ($keyvalue == '')
            $keyvalue = $data[$keyid];

        $this->inject_audit_fields($data, FALSE);

        // on positionne la base sur le bon élément
        $this->db->where($keyid, $keyvalue);
        unset($data[$keyid]);

        if (!$this->db->update($this->table, $data)) {
            // Get MySQL error message
            $errno = $this->db->_error_number();
            $error = $this->db->_error_message();
            gvv_error("MySQL Error #$errno: $error");
        }
    }

    /**
     * Fill audit fields automatically when target table contains matching columns.
     *
     * @param array $data Data to insert or update
     * @param bool $is_create TRUE for create operation, FALSE for update
     */
    protected function inject_audit_fields(&$data, $is_create = TRUE) {
        if (!is_array($data) || empty($this->table)) {
            return;
        }

        $columns = $this->table_columns();
        if (empty($columns)) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $username = $this->current_username();

        if ($is_create) {
            if (in_array('created_at', $columns, TRUE) && !isset($data['created_at'])) {
                $data['created_at'] = $now;
            }
            if (in_array('created_by', $columns, TRUE) && !isset($data['created_by']) && $username !== NULL) {
                $data['created_by'] = $username;
            }
        }

        if (in_array('updated_at', $columns, TRUE) && !isset($data['updated_at'])) {
            $data['updated_at'] = $now;
        }
        if (in_array('updated_by', $columns, TRUE) && !isset($data['updated_by']) && $username !== NULL) {
            $data['updated_by'] = $username;
        }
    }

    /**
     * Get and cache table columns to avoid repeated schema introspection.
     *
     * @return array<string>
     */
    protected function table_columns() {
        if (empty($this->table)) {
            return array();
        }

        if (isset(self::$table_columns_cache[$this->table])) {
            return self::$table_columns_cache[$this->table];
        }

        if (!$this->db->table_exists($this->table)) {
            self::$table_columns_cache[$this->table] = array();
            return self::$table_columns_cache[$this->table];
        }

        if (method_exists($this->db, 'list_fields')) {
            $columns = $this->db->list_fields($this->table);
        } else {
            $columns = array();
            $query = $this->db->query(
                "SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ?",
                array($this->table)
            );
            foreach ($query->result_array() as $row) {
                if (isset($row['column_name'])) {
                    $columns[] = $row['column_name'];
                }
            }
        }
        self::$table_columns_cache[$this->table] = is_array($columns) ? $columns : array();

        return self::$table_columns_cache[$this->table];
    }

    /**
     * Return current authenticated username when available.
     *
     * @return string|null
     */
    protected function current_username() {
        $CI = get_instance();
        if (isset($CI->dx_auth) && method_exists($CI->dx_auth, 'get_username')) {
            return $CI->dx_auth->get_username();
        }
        return NULL;
    }

    /**
     * Retourne le nombre de membres
     *
     * Il y a deux paramètres $where pour pouvoir passer des chaines de caractère et des tableaux ...
     * 
     * @param array $where
     *            Tableau associatif permettant de définir des conditions
     * @return integer Le nombre de news satisfaisant la condition
     */
    public function count($where = array(), $where2 = array()) {
        if (! $this->db->table_exists($this->table)) {
            gvv_error("count error: table does not exist: " . $this->table);
            return 0;
        }
        $this->db->where($where);
        if (isset($where2))
            $this->db->where($where2);
        $res = $this->db->count_all_results($this->table);
        gvv_debug("sql: count: " . $this->db->last_query());

        if ($res === false) {
            gvv_error("count error on table=" . $this->table . ": " . $this->db->_error_message());
            return 0;
        }

        return $res;
    }

    /**
     * Retourne une liste d'objets
     *
     * <pre>
     * foreach ($list as $line) {
     * $this->table->add_row($line->mlogin,
     * $line->mprenom,
     * $line->mnom,
     * </pre>
     *
     * @param integer $nb
     *            taille de la page
     * @param integer $debut
     *            nombre à sauter
     * @return objet La liste
     */
    public function list_of($where = array(), $nb = 100, $debut = 0) {
        return $this->db->select('*')->from($this->table)->where($where)->limit($nb, $debut)->get()->result();
    }

    /**
     * Retourne un tableau
     *
     * <pre>
     * foreach ($list as $line) {
     * $line['mlogin'], $line['mnom']
     * </pre>
     *
     * @param
     *            $columns
     * @param $nb taille
     *            de la page
     * @param $where selection
     * @return objet La liste
     */
    public function select_columns($columns, $nb = 0, $debut = 0, $where = array()) {
        if ($nb) {
            $db_res = $this->db->select($columns)->from($this->table)->where($where)->limit($nb, $debut)->get();
        } else {
            $db_res = $this->db->select($columns)->from($this->table)->where($where)->get();
        }
        return $this->get_to_array($db_res);
    }

    /**
     * Retourne un tableau
     *
     * <pre>
     * foreach ($list as $line) {
     * $line['mlogin'], $line['mnom']
     * </pre>
     *
     * @param $where selection
     * @return objet La liste
     */
    public function select_all($where = array(), $order_by = "") {
        if ($order_by) {
            $db_res = $this->db->from($this->table)->where($where)->order_by($order_by)->get();
        } else {
            $db_res = $this->db->from($this->table)->where($where)->get();
        }
        return $this->get_to_array($db_res);
    }

    /**
     * Retourne une chaine de caractère qui identifie une ligne de façon unique.
     * Cette chaine est utilisé dans les affichages.
     * Par défaut elle retourne la valeur de la clé, mais elle est conçue pour être
     * surchargée.
     *
     * @param $key identifiant
     *            de la ligne à représenter
     */
    public function image($key) {
        return $key;
    }

    /**
     * Retourne un hash qui peut-être utilisé dans un menu drop-down
     *
     * @param $where selection
     * @param $order ordre
     *            de tri
     */
    public function selector($where = array(), $order = "asc", $filter_section = FALSE) {
        $key = $this->primary_key;

        $this->db->select($key)->from($this->table)->where($where);

        if ($filter_section && $this->section) {
            $this->db->where('club', $this->section_id);
        }
        $db_res = $this->db->get();
        $allkeys = $this->get_to_array($db_res);

        $result = array();
        foreach ($allkeys as $row) {
            $value = $row[$key];
            $result[$value] = $this->image($value);
        }
        if ($order == "asc") {
            natcasesort($result);
        } else {
            arsort($result);
        }
        return $result;
    }

    /**
     * Retourne un hash qui peut-être utilisé dans un menu drop-down
     * avec une entrée "Tous .
     * ."
     *
     * @param $where selection
     */
    public function selector_with_all($where = array(), $filter_section = FALSE) {
        $result = $this->selector($where, "asc", $filter_section);
        $result['all'] = $this->lang->line("gvv_tous") . ' ...';
        return $result;
    }

    /**
     * Retourne un hash qui peut-être utilisé dans un menu drop-down
     * avec une entrée vide
     *
     * @param $where selection
     */
    public function selector_with_null($where = array(), $filter_section = FALSE) {
        $allkeys = $this->selector($where, "asc", $filter_section);
        $result = array();
        $result[''] = '';
        foreach ($allkeys as $key => $value) {
            $result[$key] = $value;
        }
        return $result;
    }

    /**
     * Génère un sélecteur d'année contenant toutes les années possibles pour une table
     *
     * @param $date_field champ
     *            contenant la date dont extraire l'année
     */
    public function getYearSelector($date_field) {
        $query = $this->db->select("YEAR($date_field) as year")->from($this->table)->order_by("year ASC")->group_by('year')->get();
        if ($query) {
            $results = $query->result_array();
        } else {
            $results = array();
        }
        gvv_debug("sql: year selector: " . $this->db->last_query());

        $year_selector = array();

        foreach ($results as $key => $row) {
            $year_selector[$row['year']] = $row['year'];
        }
        return $year_selector;
    }

    /**
     * Transform a database query into an array of result
     * 
     * Previous version of php were rather lenient and did not complain about calling a method on null
     * when the query was returning a sql error. More recent versions raises an exception.
     * 
     * @param unknown $res result of a database select
     */
    public function get_to_array($res) {
        gvv_debug("sql: " . $this->db->last_query());

        if ($res) {
            return $res->result_array();
        } else {
            if ($this->db->_error_number()) {
                gvv_debug("sql: error: " .  $this->db->_error_number() . " - " . $this->db->_error_message());
            }
            return array();
        }
    }
}

/* End of file common_model.php */
/* Location: ./application/models/common_model.php */
