<?php

/**
 * MySQL Integration Test Bootstrap
 *
 * This bootstrap provides a real MySQL database connection for integration testing.
 * It uses transactions to ensure the database is restored to its initial state after each test.
 *
 * Database credentials are configurable below.
 */

// Define constants that CodeIgniter needs
define('BASEPATH', dirname(__FILE__) . '/../../system/');
define('APPPATH', dirname(__FILE__) . '/../');
define('ENVIRONMENT', 'testing');

// Suppress notices for cleaner test output
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

// Mock some globals that CI expects
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/test';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['QUERY_STRING'] = '';
$_SERVER['PHP_SELF'] = '/index.php';

/**
 * Database Configuration
 *
 * IMPORTANT: Update these credentials to match your MySQL setup
 */
$db_config = [
    'hostname' => 'localhost',
    'username' => 'gvv_user',
    'password' => 'lfoyfgbj',
    'database' => 'gvv2',
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => FALSE, // Use non-persistent connections for tests
    'db_debug' => FALSE, // Don't show errors in tests
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8',
    'dbcollat' => 'utf8_general_ci',
    'swap_pre' => '',
    'autoinit' => TRUE,
    'stricton' => FALSE
];

/**
 * Real MySQL Database Connection with Transaction Support
 */
class RealMySQLDatabase {
    private $connection;
    private $transaction_started = false;

    public $conn_id;

    public function __construct($config) {
        $this->connection = new mysqli(
            $config['hostname'],
            $config['username'],
            $config['password'],
            $config['database']
        );

        if ($this->connection->connect_error) {
            throw new Exception("Database connection failed: " . $this->connection->connect_error);
        }

        $this->connection->set_charset($config['char_set']);
        $this->conn_id = $this->connection;
    }

    public function trans_start() {
        $this->connection->autocommit(FALSE);
        $this->connection->begin_transaction();
        $this->transaction_started = true;
        return true;
    }

    public function trans_rollback() {
        if ($this->transaction_started) {
            $this->connection->rollback();
            $this->connection->autocommit(TRUE);
            $this->transaction_started = false;
        }
        return true;
    }

    public function trans_complete() {
        if ($this->transaction_started) {
            $this->connection->commit();
            $this->connection->autocommit(TRUE);
            $this->transaction_started = false;
        }
        return true;
    }

    public function query($sql) {
        $result = $this->connection->query($sql);
        if ($result === false) {
            throw new Exception("Query failed: " . $this->connection->error . " SQL: " . $sql);
        }
        return new RealQueryResult($result);
    }

    public function escape_str($str) {
        return $this->connection->real_escape_string($str);
    }

    public function insert_id() {
        return $this->connection->insert_id;
    }

    public function affected_rows() {
        return $this->connection->affected_rows;
    }

    public function last_query() {
        return $this->last_executed_query ?? '';
    }

    public function _error_number() {
        return $this->connection->errno;
    }

    public function _error_message() {
        return $this->connection->error;
    }

    // Query builder methods
    private $where_conditions = [];
    private $select_fields = '*';
    private $limit_clause = '';
    private $order_clause = '';
    private $from_table = '';
    private $last_executed_query;

    public function select($fields) {
        $this->select_fields = $fields;
        return $this;
    }

    public function from($table) {
        $this->from_table = $table;
        return $this;
    }

    public function where($key, $value = null) {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                if ($v === null) {
                    $this->where_conditions[] = $k . " IS NULL";
                } else {
                    $this->where_conditions[] = $k . " = '" . $this->escape_str($v) . "'";
                }
            }
        } else {
            if ($value === null) {
                $this->where_conditions[] = $key . " IS NULL";
            } else {
                $this->where_conditions[] = $key . " = '" . $this->escape_str($value) . "'";
            }
        }
        return $this;
    }

    public function limit($limit, $offset = 0) {
        $this->limit_clause = " LIMIT " . intval($offset) . ", " . intval($limit);
        return $this;
    }

    public function order_by($field, $direction = 'ASC') {
        $this->order_clause = " ORDER BY " . $field . " " . $direction;
        return $this;
    }

    public function get($table = '', $limit = null) {
        if ($limit) {
            $this->limit_clause = " LIMIT " . intval($limit);
        }

        // Use from_table if set by from() method, otherwise use parameter
        $table_to_use = !empty($this->from_table) ? $this->from_table : $table;

        $sql = "SELECT " . $this->select_fields . " FROM " . $table_to_use;

        if (!empty($this->where_conditions)) {
            $sql .= " WHERE " . implode(' AND ', $this->where_conditions);
        }

        $sql .= $this->order_clause;
        $sql .= $this->limit_clause;

        $this->last_executed_query = $sql;

        // Reset query builder state
        $this->where_conditions = [];
        $this->select_fields = '*';
        $this->limit_clause = '';
        $this->order_clause = '';
        $this->from_table = '';

        return $this->query($sql);
    }

    public function insert($table, $data) {
        $fields = [];
        $values = [];

        foreach ($data as $field => $value) {
            $fields[] = $field;
            if ($value === null) {
                $values[] = 'NULL';
            } else {
                $values[] = "'" . $this->escape_str($value) . "'";
            }
        }

        $sql = "INSERT INTO " . $table . " (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";
        $this->last_executed_query = $sql;

        $result = $this->connection->query($sql);
        if ($result === false) {
            throw new Exception("Insert failed: " . $this->connection->error . " SQL: " . $sql);
        }

        return $this->insert_id();
    }

    public function update($table, $data, $where = null) {
        $set_clauses = [];
        foreach ($data as $key => $value) {
            if ($value === null) {
                $set_clauses[] = $key . " = NULL";
            } else {
                $set_clauses[] = $key . " = '" . $this->escape_str($value) . "'";
            }
        }

        $sql = "UPDATE " . $table . " SET " . implode(', ', $set_clauses);

        if ($where && !empty($this->where_conditions)) {
            $sql .= " WHERE " . implode(' AND ', $this->where_conditions);
        }

        $this->last_executed_query = $sql;
        $this->where_conditions = []; // Reset

        $result = $this->connection->query($sql);
        if ($result === false) {
            throw new Exception("Update failed: " . $this->connection->error . " SQL: " . $sql);
        }

        return $this->affected_rows();
    }

    public function delete($table, $where = null) {
        $sql = "DELETE FROM " . $table;

        if (!empty($this->where_conditions)) {
            $sql .= " WHERE " . implode(' AND ', $this->where_conditions);
        }

        $this->last_executed_query = $sql;
        $this->where_conditions = []; // Reset

        $result = $this->connection->query($sql);
        if ($result === false) {
            throw new Exception("Delete failed: " . $this->connection->error . " SQL: " . $sql);
        }

        return $this->affected_rows() > 0;
    }
}

/**
 * Query Result Wrapper
 */
class RealQueryResult {
    private $result;

    public function __construct($result) {
        $this->result = $result;
    }

    public function result_array() {
        if ($this->result === true || $this->result === false) {
            return [];
        }

        $rows = [];
        while ($row = $this->result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function row_array() {
        if ($this->result === true || $this->result === false) {
            return [];
        }

        return $this->result->fetch_assoc() ?: [];
    }

    public function row() {
        if ($this->result === true || $this->result === false) {
            return null;
        }

        $row = $this->result->fetch_assoc();
        return $row ? (object)$row : null;
    }

    public function num_rows() {
        if ($this->result === true || $this->result === false) {
            return 0;
        }

        return $this->result->num_rows;
    }
}

/**
 * Essential CodeIgniter Functions
 */
if (!function_exists('base_url')) {
    function base_url($uri = '') {
        return 'http://localhost/gvv2/' . $uri;
    }
}

if (!function_exists('site_url')) {
    function site_url($uri = '') {
        return base_url('index.php/' . $uri);
    }
}

if (!function_exists('config_item')) {
    function config_item($item) {
        switch($item) {
            case 'theme':
                return 'binary-news';
            case 'base_url':
                return 'http://localhost/gvv2/';
            case 'index_page':
                return 'index.php';
            case 'language':
                return 'fr';
            default:
                return '';
        }
    }
}

/**
 * Minimal CI Loader
 */
class MockLoader {
    public function database($db = '', $return = FALSE, $active_record = NULL) {
        return true;
    }

    public function model($model, $name = '', $db_conn = FALSE) {
        return true;
    }

    public function library($library, $params = NULL, $object_name = NULL) {
        return true;
    }

    public function helper($helpers = array()) {
        return true;
    }
}

/**
 * Mock Session
 */
class MockSession {
    private $userdata = ['section' => 1];

    public function userdata($key) {
        return isset($this->userdata[$key]) ? $this->userdata[$key] : null;
    }

    public function set_userdata($key, $value = null) {
        if (is_array($key)) {
            $this->userdata = array_merge($this->userdata, $key);
        } else {
            $this->userdata[$key] = $value;
        }
    }
}

/**
 * Mock GVV Metadata
 */
class MockGvvMetadata {
    public function store_table($name, $data, $query = '') {
        return true;
    }
}

/**
 * Mock Config
 */
class MockConfig {
    public function item($item) {
        switch($item) {
            case 'theme':
                return 'binary-news';
            case 'base_url':
                return 'http://localhost/gvv2/';
            case 'index_page':
                return 'index.php';
            case 'language':
                return 'fr';
            default:
                return '';
        }
    }
}

/**
 * Mock GVV Model
 */
class MockGvvModel {
    public function section() {
        return ['id' => 1, 'nom' => 'Test Section'];
    }
}

/**
 * CodeIgniter Base Model
 */
if (!class_exists('CI_Model')) {
    class CI_Model {
        public function __construct() {
            // Mock constructor
        }

        public function __get($key) {
            $CI = get_instance();
            return $CI->$key;
        }
    }
}

/**
 * Mock CI Instance
 */
class MockCI {
    public $load;
    public $db;
    public $session;
    public $config;
    public $gvvmetadata;
    public $gvv_model;

    public function __construct($db_config) {
        $this->load = new MockLoader();
        $this->db = new RealMySQLDatabase($db_config);
        $this->session = new MockSession();
        $this->config = new MockConfig();
        $this->gvvmetadata = new MockGvvMetadata();
        $this->gvv_model = new MockGvvModel();
    }
}

// Set up the global CI instance
global $CI;
$CI = new MockCI($db_config);

// Ensure get_instance() function works
if (!function_exists('get_instance')) {
    function &get_instance() {
        global $CI;
        return $CI;
    }
}

// Add missing GVV helper functions
if (!function_exists('gvv_error')) {
    function gvv_error($message) {
        // Mock error function
        error_log("GVV Error: " . $message);
    }
}

if (!function_exists('gvv_debug')) {
    function gvv_debug($message) {
        // Mock debug function - suppress output in tests
        // Uncomment the line below to see debug messages during testing
        // error_log("GVV Debug: " . $message);
    }
}

// Load the Common_model
require_once APPPATH . 'models/common_model.php';

// Load the Configuration_model
require_once APPPATH . 'models/configuration_model.php';
