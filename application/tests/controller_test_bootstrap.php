<?php

/**
 * Controller Test Bootstrap
 *
 * Loads core CodeIgniter classes for controller testing without full routing
 */

// Define paths
$system_path = dirname(__FILE__) . '/../../system';
$application_folder = dirname(__FILE__) . '/..';

define('BASEPATH', str_replace("\\", "/", realpath($system_path)).'/');
define('APPPATH', str_replace("\\", "/", realpath($application_folder)).'/');
define('ENVIRONMENT', 'testing');

// Suppress errors for cleaner test output
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

// Set up server variables
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/test';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['QUERY_STRING'] = '';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

// Initialize superglobals
$_POST = [];
$_GET = [];
$_COOKIE = [];

// Load CodeIgniter constants
if (file_exists(APPPATH.'config/'.ENVIRONMENT.'/constants.php')) {
    require(APPPATH.'config/'.ENVIRONMENT.'/constants.php');
} else {
    require(APPPATH.'config/constants.php');
}

// Load CodeIgniter Common functions
require_once BASEPATH.'core/Common.php';

// Load base controller
require_once BASEPATH.'core/Controller.php';

// Define get_instance() before creating any controllers
if (!function_exists('get_instance')) {
    function &get_instance() {
        return CI_Controller::get_instance();
    }
}

// We need to create a minimal CI instance
// Load configuration
if (file_exists(APPPATH.'config/'.ENVIRONMENT.'/database.php')) {
    require(APPPATH.'config/'.ENVIRONMENT.'/database.php');
} else {
    require(APPPATH.'config/database.php');
}

if (file_exists(APPPATH.'config/'.ENVIRONMENT.'/config.php')) {
    require(APPPATH.'config/'.ENVIRONMENT.'/config.php');
} else {
    require(APPPATH.'config/config.php');
}

// Create a test controller instance to initialize CI
class PHPUnit_Test_Controller extends CI_Controller {
    public function __construct() {
        $this->_ci_initialize();
    }

    private function _ci_initialize() {
        // Load core classes
        foreach (is_loaded() as $var => $class) {
            $this->$var =& load_class($class);
        }

        $this->load =& load_class('Loader', 'core');
        $this->load->initialize();
    }
}

// Create the test instance
$CI = new PHPUnit_Test_Controller();

// Helper functions
if (!function_exists('reset_request_data')) {
    function reset_request_data() {
        $_POST = [];
        $_GET = [];
        $_REQUEST = [];
    }
}

if (!function_exists('simulate_post')) {
    function simulate_post($data) {
        $_POST = $data;
        $_SERVER['REQUEST_METHOD'] = 'POST';
    }
}

if (!function_exists('simulate_get')) {
    function simulate_get($data) {
        $_GET = $data;
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }
}

if (!function_exists('gvv_error')) {
    function gvv_error($message) {
        error_log("GVV Error: " . $message);
    }
}

if (!function_exists('gvv_debug')) {
    function gvv_debug($message) {
        // Suppress in tests
    }
}
