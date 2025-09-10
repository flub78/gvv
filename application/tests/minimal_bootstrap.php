<?php

// Minimal bootstrap for testing

// Define some constants that might be needed
define('BASEPATH', dirname(__FILE__) . '/../../system/');
define('APPPATH', dirname(__FILE__) . '/../');

// Simple email validation function for testing
if (!function_exists('valid_email')) {
    function valid_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
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
        }
        return $CI;
    }
}
