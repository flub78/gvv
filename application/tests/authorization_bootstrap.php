<?php
/**
 * Bootstrap for Authorization System Tests
 *
 * Loads necessary CodeIgniter components and mocks for testing
 * the Gvv_Authorization library and Authorization_model
 */

// Define constants
define('BASEPATH', dirname(__FILE__) . '/../../system/');
define('APPPATH', dirname(__FILE__) . '/../');
define('ENVIRONMENT', 'testing');

// Define CodeIgniter file operation constants (from config/constants.php)
if (!defined('FOPEN_READ')) {
    define('FOPEN_READ', 'rb');
    define('FOPEN_READ_WRITE', 'r+b');
    define('FOPEN_WRITE_CREATE_DESTRUCTIVE', 'wb');
    define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE', 'w+b');
    define('FOPEN_WRITE_CREATE', 'ab');
    define('FOPEN_READ_WRITE_CREATE', 'a+b');
    define('FOPEN_WRITE_CREATE_STRICT', 'xb');
    define('FOPEN_READ_WRITE_CREATE_STRICT', 'x+b');
}

// Define file permission constants (from config/constants.php)
if (!defined('FILE_READ_MODE')) {
    define('FILE_READ_MODE', 0644);
    define('FILE_WRITE_MODE', 0666);
    define('DIR_READ_MODE', 0755);
    define('DIR_WRITE_MODE', 0755);
}

// Suppress notices for cleaner test output
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

// Mock some globals that CI expects
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/test';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['QUERY_STRING'] = '';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

// Load CodeIgniter Common functions
require_once BASEPATH . 'core/Common.php';

// Mock log_message function
if (!function_exists('log_message')) {
    function log_message($level, $message) {
        // Mock log function for tests
        return TRUE;
    }
}

// Mock base_url function
if (!function_exists('base_url')) {
    function base_url($uri = '') {
        return 'http://localhost/gvv/' . $uri;
    }
}

// Mock config_item function
if (!function_exists('config_item')) {
    function config_item($item) {
        return '';
    }
}

// Mock CI_Controller base class
if (!class_exists('CI_Controller')) {
    class CI_Controller {
        public function __construct() {}
    }
}

// Mock CI_Model base class
if (!class_exists('CI_Model')) {
    class CI_Model {
        public function __construct() {}

        public function __get($key) {
            $CI = get_instance();
            return $CI->$key;
        }
    }
}

// Enhanced mock config class
class AuthMockConfig {
    private $config = array();

    public function __construct() {
        // Default test configuration
        $this->config['gvv_config'] = array(
            'use_new_authorization' => TRUE,
            'authorization_debug' => FALSE,
            'authorization_progressive_migration' => FALSE
        );
    }

    public function item($item) {
        return isset($this->config[$item]) ? $this->config[$item] : NULL;
    }

    public function set_item($item, $value) {
        $this->config[$item] = $value;
    }

    public function load($file, $use_sections = FALSE, $fail_gracefully = FALSE) {
        return TRUE;
    }
}

// Enhanced mock loader class
class AuthMockLoader {
    private $CI;

    public function __construct(&$CI) {
        $this->CI =& $CI;
    }

    public function library($library) {
        // Handle library names with underscores (e.g., Gvv_Authorization)
        $library_file = APPPATH . 'libraries/' . $library . '.php';
        if (!file_exists($library_file)) {
            $library_file = APPPATH . 'libraries/' . ucfirst($library) . '.php';
        }

        if (file_exists($library_file)) {
            require_once $library_file;

            // Determine class name (preserve case and underscores)
            $library_name = $library;
            if (strpos($library, '_') === false) {
                $library_name = ucfirst($library);
            }

            // Property name is lowercase
            $library_var = strtolower($library);

            $this->CI->$library_var = new $library_name();
        }
        return TRUE;
    }

    public function model($model) {
        // Handle model names with underscores
        $model_file = APPPATH . 'models/' . $model . '.php';
        if (!file_exists($model_file)) {
            $model_file = APPPATH . 'models/' . ucfirst($model) . '.php';
        }

        if (file_exists($model_file)) {
            require_once $model_file;

            // Use the model name as-is for the class
            $model_name = $model;
            if (strpos($model, '_') === false) {
                $model_name = ucfirst($model);
            }

            $this->CI->$model_name = new $model_name();
        }
        return TRUE;
    }

    public function helper($helper) {
        $helper_file = APPPATH . 'helpers/' . $helper . '_helper.php';
        if (file_exists($helper_file)) {
            require_once $helper_file;
        }
        return TRUE;
    }

    public function database() {
        // Mock database loading - the database will be mocked in tests
        return TRUE;
    }
}

// Mock database class
class AuthMockDatabase {
    public function select($select) {
        return $this;
    }

    public function from($table) {
        return $this;
    }

    public function join($table, $cond, $type) {
        return $this;
    }

    public function where($key, $value = NULL) {
        return $this;
    }

    public function or_where($key, $value = NULL) {
        return $this;
    }

    public function group_start() {
        return $this;
    }

    public function group_end() {
        return $this;
    }

    public function order_by($orderby, $direction = '') {
        return $this;
    }

    public function limit($value, $offset = 0) {
        return $this;
    }

    public function get($table = '', $limit = NULL, $offset = NULL) {
        $result = new stdClass();
        $result->result_array = function() { return array(); };
        $result->row_array = function() { return NULL; };
        $result->num_rows = function() { return 0; };
        return $result;
    }

    public function insert($table, $data) {
        return TRUE;
    }

    public function update($table, $data, $where = NULL) {
        return TRUE;
    }

    public function delete($table, $where = '') {
        return TRUE;
    }

    public function truncate($table) {
        return TRUE;
    }
}

// Mock input class
class AuthMockInput {
    public function ip_address() {
        return '127.0.0.1';
    }

    public function get($index = NULL, $xss_clean = FALSE) {
        return NULL;
    }

    public function post($index = NULL, $xss_clean = FALSE) {
        return NULL;
    }
}

// Create enhanced CI mock
class AuthMockCI {
    public $config;
    public $load;
    public $db;
    public $input;

    public function __construct() {
        $this->config = new AuthMockConfig();
        $this->load = new AuthMockLoader($this);
        $this->db = new AuthMockDatabase();
        $this->input = new AuthMockInput();
    }
}

// Set up the global CI instance
global $CI;
$CI = new AuthMockCI();

// Ensure get_instance() function works
if (!function_exists('get_instance')) {
    function &get_instance() {
        global $CI;
        return $CI;
    }
}

// Load the Authorization_model for tests
require_once APPPATH . 'models/Authorization_model.php';

// Load the Gvv_Authorization library for tests
require_once APPPATH . 'libraries/Gvv_Authorization.php';
