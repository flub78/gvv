<?php

// Real database integration test bootstrap - uses actual MySQL database

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

// Real database configuration from application/config/database.php
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

// Create a real database connection
class RealDatabase {
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
    
    // Simple query builder methods for testing
    private $where_conditions = [];
    private $select_fields = '*';
    private $limit_clause = '';
    private $order_by_clause = '';
    private $join_clauses = [];
    private $from_table = '';
    private $from_alias = '';
    private $last_executed_query;

    public function select($fields) {
        $this->select_fields = $fields;
        return $this;
    }

    public function from($table) {
        // Handle table aliases like "user_roles_per_section urps"
        if (strpos($table, ' ') !== false) {
            $parts = explode(' ', trim($table), 2);
            $this->from_table = $parts[0];
            $this->from_alias = $parts[1];
        } else {
            $this->from_table = $table;
            $this->from_alias = '';
        }
        return $this;
    }

    public function join($table, $cond, $type = 'INNER') {
        $this->join_clauses[] = strtoupper($type) . " JOIN " . $table . " ON " . $cond;
        return $this;
    }

    public function where($key, $value = null) {
        // Add AND before this condition if we already have conditions
        // But NOT if the last element is '(' (we're starting a group) or 'OR'
        $last_condition = end($this->where_conditions);
        if (!empty($this->where_conditions) &&
            $last_condition !== '(' &&
            $last_condition !== 'OR') {
            $this->where_conditions[] = 'AND';
        }

        if (is_array($key)) {
            $is_first = true;
            foreach ($key as $k => $v) {
                // Add AND between array elements (but not for first element after group start)
                if (!$is_first) {
                    $this->where_conditions[] = 'AND';
                }

                if ($v === NULL) {
                    $this->where_conditions[] = $k . " IS NULL";
                } else {
                    $this->where_conditions[] = $k . " = '" . $this->escape_str($v) . "'";
                }
                $is_first = false;
            }
        } else {
            // Check if key already contains IS NULL, IS NOT NULL, or other operators
            if (stripos($key, ' IS NULL') !== FALSE || stripos($key, ' IS NOT NULL') !== FALSE ||
                stripos($key, '>=') !== FALSE || stripos($key, '<=') !== FALSE ||
                stripos($key, '!=') !== FALSE || stripos($key, '>') !== FALSE ||
                stripos($key, '<') !== FALSE) {
                // Key already has the full condition
                $this->where_conditions[] = $key;
            } elseif ($value === NULL) {
                $this->where_conditions[] = $key . " IS NULL";
            } else {
                $this->where_conditions[] = $key . " = '" . $this->escape_str($value) . "'";
            }
        }
        return $this;
    }

    public function where_in($key, $values = array()) {
        // Add AND before this condition if we already have conditions
        $last_condition = end($this->where_conditions);
        if (!empty($this->where_conditions) &&
            $last_condition !== '(' &&
            $last_condition !== 'OR') {
            $this->where_conditions[] = 'AND';
        }

        if (!empty($values)) {
            $escaped_values = array_map(function($v) {
                return "'" . $this->escape_str($v) . "'";
            }, $values);
            $this->where_conditions[] = $key . " IN (" . implode(', ', $escaped_values) . ")";
        }
        return $this;
    }

    public function group_start() {
        // Add AND before group start if we already have conditions
        if (!empty($this->where_conditions)) {
            $this->where_conditions[] = 'AND';
        }
        $this->where_conditions[] = '(';
        return $this;
    }

    public function group_end() {
        $this->where_conditions[] = ')';
        return $this;
    }

    public function or_where($key, $value = null) {
        if (!empty($this->where_conditions)) {
            $this->where_conditions[] = 'OR';
        }
        if ($value === NULL) {
            $this->where_conditions[] = $key . " IS NULL";
        } else {
            $this->where_conditions[] = $key . " = '" . $this->escape_str($value) . "'";
        }
        return $this;
    }

    public function order_by($field, $direction = 'ASC') {
        $this->order_by_clause = " ORDER BY " . $field . " " . strtoupper($direction);
        return $this;
    }

    public function limit($limit, $offset = 0) {
        $this->limit_clause = " LIMIT " . intval($offset) . ", " . intval($limit);
        return $this;
    }

    public function get($table = '', $limit = null) {
        if ($limit) {
            $this->limit_clause = " LIMIT " . intval($limit);
        }

        // Handle table parameter with possible alias
        if ($table) {
            if (strpos($table, ' ') !== false) {
                $parts = explode(' ', trim($table), 2);
                $table_name = $parts[0];
                $table_alias = ' ' . $parts[1];
            } else {
                $table_name = $table;
                $table_alias = '';
            }
        } else {
            $table_name = $this->from_table;
            $table_alias = $this->from_alias ? ' ' . $this->from_alias : '';
        }

        $sql = "SELECT " . $this->select_fields . " FROM " . $table_name . $table_alias;

        foreach ($this->join_clauses as $join) {
            $sql .= " " . $join;
        }

        if (!empty($this->where_conditions)) {
            $sql .= " WHERE " . implode(' ', $this->where_conditions);
        }

        $sql .= $this->order_by_clause;
        $sql .= $this->limit_clause;

        $this->last_executed_query = $sql;

        // Reset query builder state
        $this->where_conditions = [];
        $this->select_fields = '*';
        $this->limit_clause = '';
        $this->order_by_clause = '';
        $this->join_clauses = [];
        $this->from_table = '';
        $this->from_alias = '';

        return $this->query($sql);
    }
    
    public function insert($table, $data) {
        $fields = array_keys($data);
        $values = array_values($data);

        $escaped_values = array_map(function($value) {
            if ($value === NULL || $value === '') {
                return "NULL";
            }
            return "'" . $this->escape_str($value) . "'";
        }, $values);

        $sql = "INSERT INTO " . $table . " (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $escaped_values) . ")";
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
            $set_clauses[] = $key . " = '" . $this->escape_str($value) . "'";
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

// Query result wrapper for real database
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
    
    public function num_rows() {
        if ($this->result === true || $this->result === false) {
            return 0;
        }
        
        return $this->result->num_rows;
    }
}

// Add essential CodeIgniter functions that helpers need
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
            default:
                return '';
        }
    }
}

// Create a minimal CI loader
class MockLoader {
    private $loaded_helpers = array();

    public function database($db = '', $return = FALSE, $active_record = NULL) {
        // Mock database loading - return true
        return true;
    }

    public function model($model, $name = '', $db_conn = FALSE) {
        $CI =& get_instance();

        // Convert model name to lowercase for property assignment (CodeIgniter convention)
        $model_name = $name ? $name : strtolower($model);

        // Determine class name (CodeIgniter uses Ucfirst)
        $model_class = ucfirst($model);

        // Load the actual model file only if class doesn't exist
        $model_file = APPPATH . 'models/' . strtolower($model) . '.php';
        if (file_exists($model_file) && !class_exists($model_class, false)) {
            require_once $model_file;
        }

        // Create instance if class exists
        if (class_exists($model_class, false)) {
            $CI->$model_name = new $model_class();
        }

        return true;
    }

    public function library($library, $params = NULL, $object_name = NULL) {
        $CI =& get_instance();

        // Convert library name to lowercase for property assignment
        $library_name = $object_name ? $object_name : strtolower($library);

        // Determine class name
        $library_class = ucfirst($library);

        // Load the actual library file only if class doesn't exist
        $library_file = APPPATH . 'libraries/' . ucfirst($library) . '.php';
        if (file_exists($library_file) && !class_exists($library_class, false)) {
            require_once $library_file;
        }

        // Create instance if class exists
        if (class_exists($library_class, false)) {
            $CI->$library_name = new $library_class($params);
        }

        return true;
    }

    public function config($config, $use_sections = FALSE, $fail_gracefully = FALSE) {
        // Mock config file loading
        return true;
    }

    public function language($file, $idiom = '', $return = FALSE, $add_suffix = TRUE, $alt_path = '') {
        // Mock language file loading
        return true;
    }

    public function helper($helpers = array()) {
        // Actually load real helper files for integration tests
        if (!is_array($helpers)) {
            $helpers = array($helpers);
        }
        
        foreach ($helpers as $helper) {
            $helper_file = APPPATH . 'helpers/' . $helper . '_helper.php';
            if (file_exists($helper_file) && !function_exists($helper)) {
                require_once $helper_file;
                $this->loaded_helpers[] = $helper;
            }
            
            // Also try CodeIgniter system helpers
            $system_helper_file = BASEPATH . 'helpers/' . $helper . '_helper.php';
            if (file_exists($system_helper_file)) {
                require_once $system_helper_file;
                $this->loaded_helpers[] = $helper;
            }
        }
        return true;
    }
    
    public function is_loaded($type) {
        // Return FALSE for form_validation since we don't need it for basic tests
        if ($type === 'form_validation') {
            return FALSE;
        }
        // Simple implementation for helper loading status
        if ($type === 'helper') {
            return $this->loaded_helpers;
        }
        return FALSE;
    }
}

// Store database config globally for CI_Controller
$GLOBALS['test_db_config'] = $db_config;

// Create a simplified CI controller base for testing
class CI_Controller {
    public $load;
    public $db;
    
    public function __construct() {
        $this->load = new MockLoader();
        $this->db = new RealDatabase($GLOBALS['test_db_config']);
    }
}

// Create a minimal CI session mock
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

// Create minimal gvvmetadata mock
class MockGvvMetadata {
    public function store_table($name, $data, $query = '') {
        // Mock metadata storage
        return true;
    }
}

// Mock config for CodeIgniter
class MockConfig {
    private $config = array();

    public function load($file, $use_sections = FALSE, $fail_gracefully = FALSE) {
        // Load the actual config file
        $config_file = APPPATH . 'config/' . $file . '.php';
        if (file_exists($config_file)) {
            include($config_file);
            if (isset($config) && is_array($config)) {
                if ($use_sections) {
                    $this->config[$file] = $config;
                } else {
                    $this->config = array_merge($this->config, $config);
                }
            }
            return TRUE;
        }
        return $fail_gracefully ? FALSE : TRUE;
    }

    public function item($item, $index = '') {
        if ($index == '') {
            if (isset($this->config[$item])) {
                return $this->config[$item];
            }
        } else {
            if (isset($this->config[$index][$item])) {
                return $this->config[$index][$item];
            }
        }

        // Fallback defaults
        switch($item) {
            case 'theme':
                return 'binary-news';
            case 'base_url':
                return 'http://localhost/gvv2/';
            case 'index_page':
                return 'index.php';
            case 'compression':
                // Return compression config for File_compressor
                return [
                    'enabled' => true,
                    'min_size' => 102400, // 100KB
                    'min_ratio' => 0.10,
                    'image_max_width' => 1600,
                    'image_max_height' => 1200,
                    'image_quality' => 85,
                    'pdf_quality' => 'ebook',
                    'ghostscript_path' => 'gs'
                ];
            default:
                return '';
        }
    }
}

// Mock Lang class
class MockLang {
    public function load($file) {
        // Mock language file loading
        return true;
    }

    public function line($key) {
        // Return mock translation
        return $key;
    }
}

// Mock Input class
class MockInput {
    public function ip_address() {
        return '127.0.0.1';
    }

    public function post($key = NULL, $xss_clean = FALSE) {
        return isset($_POST[$key]) ? $_POST[$key] : NULL;
    }

    public function get($key = NULL, $xss_clean = FALSE) {
        return isset($_GET[$key]) ? $_GET[$key] : NULL;
    }

    public function is_ajax_request() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
}

// Create a mock CI instance
class MockCI {
    public $load;
    public $db;
    public $session;
    public $config;
    public $lang;
    public $input;

    public function __construct() {
        $this->load = new MockLoader();
        $this->db = new RealDatabase($GLOBALS['test_db_config']);
        $this->session = new MockSession();
        $this->config = new MockConfig();
        $this->lang = new MockLang();
        $this->input = new MockInput();
    }
}

// Set up the global CI instance
global $CI;
$CI = new MockCI();

// Ensure get_instance() function works
if (!function_exists('get_instance')) {
    function &get_instance() {
        global $CI;
        return $CI;
    }
}

// Create a mock CI_Model base class BEFORE loading models
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

// Load log_helper for gvv_debug, gvv_info, gvv_error functions
require_once APPPATH . 'helpers/log_helper.php';

// Add log_message function for log_helper
if (!function_exists('log_message')) {
    function log_message($level, $message, $php_error = FALSE) {
        // Write to actual log file like CodeIgniter does
        $log_path = APPPATH . 'logs/';

        // Create logs directory if it doesn't exist
        if (!is_dir($log_path)) {
            mkdir($log_path, 0755, true);
        }

        $filepath = $log_path . 'log-' . date('Y-m-d') . '.php';

        // Create log file header if file doesn't exist
        if (!file_exists($filepath)) {
            $newfile = TRUE;
            $message_header = "<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>\n\n";
        } else {
            $newfile = FALSE;
            $message_header = '';
        }

        // Format log message
        $level = strtoupper($level);
        $log_line = $level . ' - ' . date('Y-m-d H:i:s') . ' --> ' . $message . "\n";

        // Write to log file
        if ($fp = @fopen($filepath, 'a')) {
            flock($fp, LOCK_EX);
            fwrite($fp, $message_header . $log_line);
            flock($fp, LOCK_UN);
            fclose($fp);

            @chmod($filepath, 0644);
            return TRUE;
        }

        return FALSE;
    }
}

// Load the Common_model
require_once APPPATH . 'models/common_model.php';

// Create a mock Common_Model that works with our mock database
class TestCommonModel {
    public $table;
    protected $primary_key = 'id';
    protected $db;
    protected $gvvmetadata;
    private static $mock_data = [];
    private static $next_id = 1;
    
    public function __construct() {
        $this->db = get_instance()->db;
        $this->gvvmetadata = new MockGvvMetadata();
    }
    
    public function save($data) {
        if (isset($data['id']) && $data['id'] > 0) {
            // Update existing record
            $id = $data['id'];
            self::$mock_data[$id] = $data;
            return $id;
        } else {
            // Insert new record
            $id = self::$next_id++;
            $data['id'] = $id;
            self::$mock_data[$id] = $data;
            return $id;
        }
    }
    
    public function get_by_id($field, $value) {
        foreach (self::$mock_data as $record) {
            if ($record[$field] == $value) {
                return $record;
            }
        }
        return [];
    }
    
    public function delete($id) {
        if (isset(self::$mock_data[$id])) {
            unset(self::$mock_data[$id]);
            return true;
        }
        return false;
    }
    
    public function select_page($nb = 1000, $debut = 0) {
        $result = array_values(self::$mock_data);
        foreach ($result as &$row) {
            $row['image'] = $row['nom'];
            $row['nom_parent'] = 'Mock Parent';
        }
        return $result;
    }
    
    public static function clear_mock_data() {
        self::$mock_data = [];
        self::$next_id = 1;
    }
}

// Load and mock the Categorie_model
require_once APPPATH . 'models/categorie_model.php';

class TestCategorieModel extends TestCommonModel {
    public $table = 'categorie';
    
    public function image($key) {
        if ($key == "") return "";
        
        $vals = $this->get_by_id('id', $key);
        if (array_key_exists('nom', $vals)) {
            return $vals['nom'];
        } else {
            return "catégorie inconnu $key";
        }
    }
    
    public function select_page($nb = 1000, $debut = 0) {
        $result = parent::select_page($nb, $debut);
        
        // Add mock parent data for joined results
        foreach ($result as &$row) {
            $row['nom_parent'] = 'Root Category';
        }
        
        return $result;
    }
}

// Clean up temporary database config if we created it
if (isset($temp_db_config_created) && $temp_db_config_created) {
    register_shutdown_function(function() use ($db_config_file) {
        if (file_exists($db_config_file)) {
            unlink($db_config_file);
        }
    });
}
