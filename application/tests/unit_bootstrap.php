<?php
/**
 * Bootstrap for unit tests
 * 
 * Loads CodeIgniter framework with minimal dependencies for unit testing
 */

// Define paths
define('BASEPATH', dirname(__FILE__) . '/../../system/');
define('APPPATH', dirname(__FILE__) . '/../');
define('ENVIRONMENT', 'testing');

// Define CodeIgniter file operation constants
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

// Define file permission constants
if (!defined('FILE_READ_MODE')) {
    define('FILE_READ_MODE', 0644);
    define('FILE_WRITE_MODE', 0666);
    define('DIR_READ_MODE', 0755);
    define('DIR_WRITE_MODE', 0755);
}

// Define CodeIgniter constants
define('CREATION', 'creation');
define('MODIFICATION', 'modification');

// Mock log_message function
if (!function_exists('log_message')) {
    function log_message($level, $message, $php_error = FALSE) {
        return TRUE;
    }
}

// Load helper functions
require_once APPPATH . 'helpers/log_helper.php';

// Load CodeIgniter framework
require_once BASEPATH . 'core/Common.php';
require_once BASEPATH . 'core/CodeIgniter.php';

// Get the CodeIgniter instance
function &get_instance()
{
    return CI_Controller::get_instance();
}
