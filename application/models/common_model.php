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
 * De cette façon tout le code courrant est géré par ce module d'interface avec la base de données.
 * Dans les cas simple les enfants n'on besoin que de déclarer le nom de la table
 * sur laquelle ils travaillent.
 */
class Common_Model extends CI_Model {
    public $table;
    protected $primary_key;

    /**
     * Constructor
     *
     * Affiche header et menu
     */
    function __construct() {
        parent::__construct();
        $this->load->library('gvvmetadata');
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
        if ($this->db->insert($this->table, $data)) {
            $last_id = $this->db->insert_id();
            gvv_debug("create succesful, table=" . $this->table . ", \$last_id=$last_id, data=" . var_export($data, true));
            if (! $last_id) {
                $last_id = $data[$this->primary_key];
                gvv_debug("\$last_id=$last_id (\$data[primary_key])");
            }
            return $last_id;
        } else {
            gvv_error("create error: " .  $this->db->_error_message());
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
        $this->db->delete($this->table, $where);
    }

    /**
     * Retourne une ligne de base
     *
     * @param
     *            mlogin
     * @return hash des valeurs
     */
    public function get_by_id($keyid, $keyvalue) {
        $table = $this->table;

        $this->db->where($keyid, $keyvalue);
        $res = $this->db->get($this->table)->row_array();
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
        $this->db->where($keyid, $keyvalue);
        unset($data[$keyid]);
        $this->db->update($this->table, $data);
    }

    /**
     * Retourne le nombre de membres
     *
     * @param array $where
     *            Tableau associatif permettant de définir des conditions
     * @return integer Le nombre de news satisfaisant la condition
     */
    public function count($where = array(), $where2 = array()) {
        $this->db->where($where);
        if (isset($where2))
            $this->db->where($where2);
        return $this->db->count_all_results($this->table);
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
     * Retourne un hash qui peut-être utilisé dans un menu drow-down
     *
     * @param $where selection
     * @param $order ordre
     *            de tri
     */
    public function selector($where = array(), $order = "asc") {
        $key = $this->primary_key;

        $db_res = $this->db->select($key)->from($this->table)->where($where)->get();
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
     * Retourne un hash qui peut-être utilisé dans un menu drow-down
     * avec une entrée "Tous .
     * ."
     *
     * @param $where selection
     */
    public function selector_with_all($where = array()) {
        $result = $this->selector($where);
        $result[''] = $this->lang->line("gvv_tous") . ' ...';
        return $result;
    }

    /**
     * Retourne un hash qui peut-être utilisé dans un menu drow-down
     * avec une entrée vide
     *
     * @param $where selection
     */
    public function selector_with_null($where = array()) {
        $allkeys = $this->selector($where);
        $result = array();
        $result[''] = '';
        foreach ($allkeys as $key => $value) {
            $result[$key] = $value;
        }
        return $result;
    }

    /**
     * Génère un selecteur d'année contenant toutes les années possibles pour une table
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
