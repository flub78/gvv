<?php

// Minimal bootstrap for testing

// Define some constants that might be needed
define('BASEPATH', dirname(__FILE__) . '/../../system/');
define('APPPATH', dirname(__FILE__) . '/../');

// Load the validation helper functions
require_once APPPATH . 'helpers/validation_helper.php';

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
 */ 
spl_autoload_register(function ($class) {
	foreach (glob(APPPATH.'controllers/**/'.strtolower($class).'.php') as $controller) {
		require_once $controller;
	}
});
