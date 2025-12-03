<?php

// Minimal bootstrap for testing

// Define some constants that might be needed
define('BASEPATH', dirname(__FILE__) . '/../../system/');
define('APPPATH', dirname(__FILE__) . '/../');

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

// Define CodeIgniter constants
define('CREATION', 'creation');
define('MODIFICATION', 'modification');

// Load the helper functions
require_once APPPATH . 'helpers/validation_helper.php';
require_once APPPATH . 'helpers/bitfields_helper.php';
require_once APPPATH . 'helpers/assets_helper.php';
require_once APPPATH . 'helpers/crypto_helper.php';
require_once APPPATH . 'helpers/csv_helper.php';
require_once APPPATH . 'helpers/markdown_helper.php';
require_once APPPATH . 'helpers/email_helper.php';
require_once BASEPATH . 'helpers/url_helper.php';

// Load library files for testing
function load_library($library_name) {
    $library_file = APPPATH . 'libraries/' . $library_name . '.php';
    if (file_exists($library_file)) {
        require_once $library_file;
    }
}

// Auto-load the Bitfield library for testing
load_library('Bitfield');

// Auto-load libraries needed for markdown
load_library('MY_Parsedown');

// Simple email validation function for testing
if (!function_exists('valid_email')) {
    function valid_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

// Mock debug function
if (!function_exists('gvv_debug')) {
    function gvv_debug($message) {
        // Mock debug function - does nothing in tests
    }
}

// Mock CI_Controller base class
if (!class_exists('CI_Controller')) {
    class CI_Controller {
        public function __construct() {
            // Mock constructor
        }
    }
}

// Mock CI_Model base class for models
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

// Enhanced mock loader class for CI
class MinimalMockLoader {
    private $CI;

    public function __construct(&$CI) {
        $this->CI =& $CI;
    }

    public function helper($helper) {
        // Mock helper loading - helpers are already loaded in bootstrap
        return TRUE;
    }

    public function database() {
        // Mock database loading
        return TRUE;
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

    public function language($language_file) {
        // Mock language file loading
        return TRUE;
    }

    public function model($model, $name = '') {
        // Handle model names with underscores
        $model_file = APPPATH . 'models/' . $model . '.php';
        echo "MinimalMockLoader: Trying to load model: $model_file\n"; // Debug statement
        if (!file_exists($model_file)) {
            $ucfirst_model_file = APPPATH . 'models/' . ucfirst($model) . '.php';
            echo "MinimalMockLoader: Model file not found, trying with ucfirst: $ucfirst_model_file\n"; // Debug statement
            $model_file = $ucfirst_model_file;
        }

        if (file_exists($model_file)) {
            echo "MinimalMockLoader: Found model file: $model_file. Requiring once.\n"; // Debug statement
            require_once $model_file;

            // Class name should be ucfirst (e.g., Authorization_model)
            $class_name = ucfirst($model);
            // Property name is either the alias or the original model name
            $property_name = ($name === '') ? $model : $name;

            // Check if class exists before instantiating
            if (!class_exists($class_name)) {
                echo "MinimalMockLoader: Error: Class '$class_name' not found in '$model_file' after require_once.\n"; // Debug statement
                // Fallback to try a different class naming convention if needed
                $class_name = $model; // Try exact name
                if (!class_exists($class_name)) {
                    echo "MinimalMockLoader: Error: Class '$class_name' not found after trying exact model name.\n"; // Debug statement
                    return FALSE; // Cannot load model
                }
            }
            echo "MinimalMockLoader: Instantiating class '$class_name' as '$property_name'.\n"; // Debug statement
            $this->CI->$property_name = new $class_name();
        } else {
            echo "MinimalMockLoader: Error: Model file '$model_file' not found.\n"; // Debug statement
            return FALSE; // Cannot load model
        }
        return TRUE;
    }
}

// Mock config class for CI
class MinimalMockConfig {
    private $config = array();

    public function item($item) {
        return isset($this->config[$item]) ? $this->config[$item] : NULL;
    }

    public function set_item($item, $value) {
        $this->config[$item] = $value;
    }

    public function load($file, $use_sections = FALSE, $fail_gracefully = FALSE) {
        // For authorization tests, set the gvv_config
        if ($file === 'gvv_config') {
            $this->config['gvv_config'] = array(
                'use_new_authorization' => TRUE,
                'authorization_debug' => FALSE,
                'authorization_progressive_migration' => FALSE
            );
        }
        return TRUE;
    }

    public function site_url($uri = '', $protocol = NULL) {
        // Build URL from base_url and index_page config
        $base_url = $this->item('base_url');
        $index_page = $this->item('index_page');

        if (empty($base_url)) {
            $base_url = 'http://localhost/';
        }

        // Ensure base_url has trailing slash
        if (substr($base_url, -1) !== '/') {
            $base_url .= '/';
        }

        // Add index_page if set
        if (!empty($index_page)) {
            $base_url .= $index_page . '/';
        }

        // Add URI, removing leading slash
        return $base_url . ltrim($uri, '/');
    }

    public function slash_item($item) {
        $value = $this->item($item);
        if (empty($value)) {
            return '';
        }
        return rtrim($value, '/') . '/';
    }

    public function base_url($uri = '', $protocol = NULL) {
        // Same as site_url but without index_page
        $base_url = $this->item('base_url');

        if (empty($base_url)) {
            $base_url = 'http://localhost/';
        }

        // Ensure base_url has trailing slash
        if (substr($base_url, -1) !== '/') {
            $base_url .= '/';
        }

        // Add URI, removing leading slash
        return $base_url . ltrim($uri, '/');
    }
}

// Mock input class for CI
class MinimalMockInput {
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

// Mock DB Result class for CI
class MinimalMockDBResult {
    public function result_array() { return array(); }
    public function row_array() { return NULL; }
    public function num_rows() { return 0; }
    public function row() { return NULL; }
    public function result() { return array(); }
}

// Mock database class for CI
class MinimalMockDatabase {
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
        return new MinimalMockDBResult();
    }

    public function query($sql) {
        // Mock query method
        return new MinimalMockDBResult();
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
}

// Mock Lang class for CI

/*
 * This will autoload controllers inside subfolders
 * Skip controllers with known compatibility issues when running coverage
 */
spl_autoload_register(function ($class) {
	// Skip problematic controllers that have method signature incompatibilities
	// These don't affect the tests but cause issues during coverage analysis
	$skip_for_coverage = ['achats', 'vols_planeur', 'vols_avion'];

	$class_lower = strtolower($class);
	if (in_array($class_lower, $skip_for_coverage) && getenv('XDEBUG_MODE') === 'coverage') {
		return;
	}

	foreach (glob(APPPATH.'controllers/**/'.strtolower($class).'.php') as $controller) {
		require_once $controller;
	}
});
