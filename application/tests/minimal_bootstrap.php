<?php

// Minimal bootstrap for testing

// Define some constants that might be needed
define('BASEPATH', dirname(__FILE__) . '/../../system/');
define('APPPATH', dirname(__FILE__) . '/../');

// Load the validation helper functions
require_once APPPATH . 'helpers/validation_helper.php';

// Load the bitfields helper functions
require_once APPPATH . 'helpers/bitfields_helper.php';

// Load library files for testing
function load_library($library_name) {
    $library_file = APPPATH . 'libraries/' . $library_name . '.php';
    if (file_exists($library_file)) {
        require_once $library_file;
    }
}

// Auto-load the Bitfield library for testing
load_library('Bitfield');

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

// Mock get_instance function
if (!function_exists('get_instance')) {
    function &get_instance() {
        static $CI;
        if (!$CI) {
            $CI = new stdClass();
            $CI->load = new stdClass();
            $CI->load->helper = function($helper) {
                // Mock helper loading
            };
            $CI->load->database = function() {
                // Mock database loading
            };
        }
        return $CI;
    }
}

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
