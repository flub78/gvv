<?php

// Enhanced CI bootstrap for testing helpers and libraries that need CodeIgniter integration
// "Enhanced" means this provides an enhanced mock CodeIgniter environment with full CI object,
// config, loader, and framework functions - as opposed to minimal_bootstrap.php which only
// loads helpers without the CI framework

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

// Load CodeIgniter Common functions first
require_once BASEPATH . 'core/Common.php';

// Mock base_url function for assets helper
if (!function_exists('base_url')) {
    function base_url($uri = '') {
        return 'http://localhost/gvv2/' . $uri;
    }
}

// Mock site_url function
if (!function_exists('site_url')) {
    function site_url($uri = '') {
        return base_url('index.php/' . $uri);
    }
}

// Mock anchor function for URL helper
if (!function_exists('anchor')) {
    function anchor($uri = '', $title = '', $attributes = '') {
        $title = (string) $title;
        
        if ( ! is_array($uri)) {
            $site_url = site_url($uri);
        } else {
            $site_url = site_url($uri);
        }

        if ($title == '') {
            $title = $site_url;
        }

        if ($attributes != '') {
            $attributes = _parse_attributes($attributes);
        }

        return '<a href="'.$site_url.'"'.$attributes.'>'.$title.'</a>';
    }
}

// Helper function for parsing attributes (needed by anchor)
if (!function_exists('_parse_attributes')) {
    function _parse_attributes($attributes) {
        if (is_array($attributes)) {
            $att = '';
            foreach ($attributes as $key => $val) {
                $att .= ' ' . $key . '="' . $val . '"';
            }
            return $att;
        }

        if (is_string($attributes) AND strlen($attributes) > 0) {
            if ($attributes[0] != ' ') {
                $attributes = ' '.$attributes;
            }
        }

        return $attributes;
    }
}

// Load CodeIgniter form helper functions
if (!function_exists('form_checkbox')) {
    require_once BASEPATH . 'helpers/form_helper.php';
}

// Load CodeIgniter HTML helper functions (needed for nbs())
if (!function_exists('nbs')) {
    require_once BASEPATH . 'helpers/html_helper.php';
}

// Load validation helper for euro() function
if (!function_exists('euro')) {
    require_once APPPATH . 'helpers/validation_helper.php';
}

// Enhanced mock config class that properly implements item() method
class EnhancedMockConfig {
    public function item($item) {
        switch($item) {
            case 'theme':
                return 'binary-news';
            case 'base_url':
                return 'http://localhost/gvv2/';
            case 'index_page':
                return '';
            case 'palette':
                return 'base';
            default:
                return '';
        }
    }
}

// Mock DB Result class for CI
class EnhancedMockDBResult {
    public function result_array() { return array(); }
    public function row_array() { return NULL; }
    public function num_rows() { return 0; }
    public function row() { return NULL; }
    public function result() { return array(); }
}

// Mock database class for CI
class EnhancedMockDatabase {
    public $conn_id = null;

    public function select($select) {
        return $this;
    }

    public function from($table) {
        return $this;
    }

    public function join($table, $cond, $type = '') {
        return $this;
    }

    public function where($key, $value = NULL) {
        return $this;
    }

    public function or_where($key, $value = NULL) {
        return $this;
    }

    public function where_in($key, $values = array()) {
        return $this;
    }

    public function get($table = '', $limit = NULL, $offset = NULL) {
        return new EnhancedMockDBResult();
    }

    public function query($sql) {
        // Mock query method
        return new EnhancedMockDBResult();
    }

    public function insert($table, $data = NULL) {
        return TRUE;
    }

    public function update($table, $data = NULL, $where = NULL) {
        return TRUE;
    }

    public function _error_number() {
        return 0; // Mock no error
    }

    public function _error_message() {
        return ''; // Mock empty error message
    }

    public function last_query() {
        return 'SELECT * FROM mock_table';
    }
}

// Mock Lang class for language support
class EnhancedMockLang {
    public function line($key) {
        // Return simple translations for common keys
        $translations = array(
            'gvv_button_new' => 'Nouveau',
            'all_sections' => 'Toutes les sections',
            'gvv_events_types_enum_activite' => array('Vol', 'Administratif', 'Autre')
        );

        return isset($translations[$key]) ? $translations[$key] : $key;
    }

    public function load($file, $idiom = '') {
        return TRUE;
    }
}

// Create enhanced CI mock with config and other required properties
class EnhancedMockCI {
    public $config;
    public $load;
    public $db;
    public $session;
    public $log;
    public $lang;

    public function __construct() {
        // Mock config with theme and base_url settings
        $this->config = new EnhancedMockConfig();

        // Mock loader - pass reference to this CI instance
        $this->load = new EnhancedMockLoader($this);

        // Mock lang
        $this->lang = new EnhancedMockLang();

        // Mock database with required methods
        $this->db = new EnhancedMockDatabase();

        // Mock session
        $this->session = new stdClass();

        // Mock log with required methods
        $this->log = new MockLog();
    }

    public function helper($helper) {
        // Mock helper loading
        return true;
    }
}

class EnhancedMockLoader {
    private $CI;

    public function __construct(&$CI) {
        $this->CI =& $CI;
    }

    public function helper($helper) {
        // Load actual helpers if they exist
        $helper_file = APPPATH . 'helpers/' . $helper . '_helper.php';
        if (file_exists($helper_file)) {
            require_once $helper_file;
        }
        return true;
    }

    public function database() {
        // Mock database loading
        return true;
    }

    public function library($library) {
        // Load actual library files if they exist
        $library_file = APPPATH . 'libraries/' . $library . '.php';
        if (file_exists($library_file)) {
            require_once $library_file;

            // Instantiate the library and attach it to CI instance
            // Property name is lowercase
            $library_var = strtolower($library);

            // Only instantiate if not already loaded
            if (!isset($this->CI->$library_var)) {
                $this->CI->$library_var = new $library();
            }
        }
        return true;
    }

    public function is_loaded($type) {
        // Return FALSE for form_validation since we don't need it for basic tests
        if ($type === 'form_validation') {
            return FALSE;
        }
        return FALSE;
    }
}

class MockLog {
    public $log_size = 1000; // Mock log size - made public for test access
    public $error_count = 5; // Track error count separately
    
    public function log_file_size() {
        return $this->log_size;
    }
    
    public function log_file() {
        return APPPATH . 'logs/log-' . date('Y-m-d') . '.php';
    }
    
    public function count_lines($function, $pattern) {
        // Mock line counting - return some realistic numbers
        if ($pattern === 'ERROR') {
            return $this->error_count; // Return actual error count
        }
        return 0;
    }
}

// Mock gvv logging functions
if (!function_exists('gvv_info')) {
    function gvv_info($level, $message) {
        global $CI;
        $CI->log->log_size += 50; // Simulate log growth
        return "";
    }
}

if (!function_exists('gvv_debug')) {
    function gvv_debug($level, $message) {
        global $CI;
        $CI->log->log_size += 30; // Simulate log growth
        return "";
    }
}

if (!function_exists('gvv_error')) {
    function gvv_error($level, $message) {
        global $CI;
        $CI->log->log_size += 40; // Simulate log growth
        $CI->log->error_count += 1; // Increment error count
        return "";
    }
}

// Set up the global CI instance
global $CI;
$CI = new EnhancedMockCI();

// Ensure get_instance() function works
if (!function_exists('get_instance')) {
    function &get_instance() {
        global $CI;
        return $CI;
    }
}

// Pre-load required helpers and libraries (now that get_instance() is available)
$CI->load->helper('assets');
$CI->load->library('Widget');
$CI->load->library('Button');
$CI->load->library('ButtonDelete');
$CI->load->library('ButtonEdit');

// Mock config_item function for assets helper
if (!function_exists('config_item')) {
    function config_item($item) {
        global $CI;
        return $CI->config->item($item);
    }
}
