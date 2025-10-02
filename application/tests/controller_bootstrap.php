<?php

/**
 * Controller Test Bootstrap
 *
 * Bootstrap file for testing CodeIgniter controllers with PHPUnit
 * Provides full CodeIgniter environment for controller testing
 */

// Define constants
define('BASEPATH', dirname(__FILE__) . '/../../system/');
define('APPPATH', dirname(__FILE__) . '/../');
define('ENVIRONMENT', 'testing');

// Suppress notices for cleaner test output
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

// Mock server variables for CodeIgniter
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/test';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['QUERY_STRING'] = '';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

// Initialize $_POST and $_GET if not set
if (!isset($_POST)) $_POST = [];
if (!isset($_GET)) $_GET = [];

/**
 * Database Configuration
 */
$db_config = [
    'hostname' => 'localhost',
    'username' => 'gvv_user',
    'password' => 'lfoyfgbj',
    'database' => 'gvv2',
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => FALSE,
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8',
    'dbcollat' => 'utf8_general_ci',
    'swap_pre' => '',
    'autoinit' => TRUE,
    'stricton' => FALSE
];

/**
 * Real MySQL Database Connection
 */
class ControllerTestDatabase {
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

    // Forward all other calls to load real CodeIgniter database
    public function __call($method, $args) {
        // This will be replaced by actual CI database when loaded
        return null;
    }
}

/**
 * Mock Output Class for Capturing Controller Output
 */
class MockOutput {
    private $output = '';
    private $headers = [];
    private $status_code = 200;

    public function set_output($output) {
        $this->output .= $output;
        return $this;
    }

    public function get_output() {
        return $this->output;
    }

    public function set_content_type($mime_type, $charset = NULL) {
        $this->headers['Content-Type'] = $mime_type . ($charset ? '; charset=' . $charset : '');
        return $this;
    }

    public function set_header($header, $replace = TRUE) {
        $this->headers[] = $header;
        return $this;
    }

    public function set_status_header($code = 200, $text = '') {
        $this->status_code = $code;
        return $this;
    }

    public function get_headers() {
        return $this->headers;
    }

    public function get_status_code() {
        return $this->status_code;
    }

    public function _display($output = '') {
        if ($output) {
            echo $output;
        } else {
            echo $this->output;
        }
    }

    // Enable output
    public function enable_profiler($val = TRUE) {
        return $this;
    }
}

/**
 * Mock Input Class
 */
class MockInput {
    public function post($key = NULL, $xss_clean = NULL) {
        if ($key === NULL) {
            return $_POST;
        }
        return isset($_POST[$key]) ? $_POST[$key] : NULL;
    }

    public function get($key = NULL, $xss_clean = NULL) {
        if ($key === NULL) {
            return $_GET;
        }
        return isset($_GET[$key]) ? $_GET[$key] : NULL;
    }

    public function post_get($key, $xss_clean = NULL) {
        return isset($_POST[$key]) ? $_POST[$key] : (isset($_GET[$key]) ? $_GET[$key] : NULL);
    }

    public function get_post($key, $xss_clean = NULL) {
        return isset($_GET[$key]) ? $_GET[$key] : (isset($_POST[$key]) ? $_POST[$key] : NULL);
    }

    public function server($index, $xss_clean = NULL) {
        return isset($_SERVER[$index]) ? $_SERVER[$index] : NULL;
    }

    public function ip_address() {
        return '127.0.0.1';
    }

    public function user_agent() {
        return 'PHPUnit Test';
    }
}

// Helper functions
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

if (!function_exists('gvv_error')) {
    function gvv_error($message) {
        error_log("GVV Error: " . $message);
    }
}

if (!function_exists('gvv_debug')) {
    function gvv_debug($message) {
        // Suppress debug output in tests
    }
}

if (!function_exists('show_error')) {
    function show_error($message, $status_code = 500, $heading = 'An Error Was Encountered') {
        throw new Exception($message, $status_code);
    }
}

if (!function_exists('show_404')) {
    function show_404($page = '', $log_error = TRUE) {
        throw new Exception($page, 404);
    }
}

// Bootstrap CodeIgniter - load the actual framework
// This gives us the real CI instance with all its functionality
require_once dirname(__FILE__) . '/../../index.php';

// Now we have a real $CI instance
// Override the database with our test database that supports transactions
$CI =& get_instance();

// Replace with test database if needed
// (The real CI database is already loaded, we can use it directly)

// Helper function for tests
if (!function_exists('get_instance')) {
    function &get_instance() {
        return CI_Controller::get_instance();
    }
}
