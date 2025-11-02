<?php
/**
 * Smoke test for email_lists feature
 *
 * This test verifies that:
 * - Controller can be instantiated
 * - Model can be loaded
 * - Required methods exist
 * - Basic structure is correct
 *
 * Run with: php test_email_lists_smoke.php
 */

// Bootstrap CodeIgniter
define('BASEPATH', 'system/');
define('ENVIRONMENT', 'development');

// Minimal bootstrap
$_SERVER['REQUEST_URI'] = '/email_lists';
$_SERVER['SCRIPT_NAME'] = '/index.php';

require_once('index.php');

echo "\n=== Email Lists Smoke Test ===\n\n";

try {
    // Test 1: Load controller
    echo "Test 1: Loading controller...\n";
    $CI =& get_instance();
    $CI->load->model('email_lists_model');
    echo "  ✓ Model loaded\n";

    // Test 2: Check model methods
    echo "\nTest 2: Checking model methods...\n";
    $required_methods = ['primary_key', 'table', 'create_list', 'get_list', 'update_list',
                         'delete_list', 'get_user_lists', 'textual_list'];
    foreach ($required_methods as $method) {
        if (method_exists($CI->email_lists_model, $method)) {
            echo "  ✓ $method() exists\n";
        } else {
            throw new Exception("Missing method: $method");
        }
    }

    // Test 3: Check model properties
    echo "\nTest 3: Checking model properties...\n";
    $pk = $CI->email_lists_model->primary_key();
    $table = $CI->email_lists_model->table();
    echo "  ✓ Primary key: $pk\n";
    echo "  ✓ Table: $table\n";

    if ($pk !== 'id') {
        throw new Exception("Primary key should be 'id', got '$pk'");
    }
    if ($table !== 'email_lists') {
        throw new Exception("Table should be 'email_lists', got '$table'");
    }

    // Test 4: Check helper is loaded
    echo "\nTest 4: Checking email helper...\n";
    if (function_exists('validate_email')) {
        echo "  ✓ validate_email() available\n";
    } else {
        throw new Exception("email helper not loaded");
    }

    if (function_exists('normalize_email')) {
        echo "  ✓ normalize_email() available\n";
    } else {
        throw new Exception("normalize_email() not found in helper");
    }

    // Test 5: Check view files exist
    echo "\nTest 5: Checking view files...\n";
    $view_files = [
        'application/views/email_lists/index.php',
        'application/views/email_lists/form.php',
        'application/views/email_lists/view.php',
        'application/views/email_lists/_criteria_tab.php',
        'application/views/email_lists/_manual_tab.php',
        'application/views/email_lists/_import_tab.php',
        'application/views/email_lists/_export_section.php'
    ];

    foreach ($view_files as $file) {
        if (file_exists($file)) {
            echo "  ✓ $file\n";
        } else {
            throw new Exception("View file missing: $file");
        }
    }

    // Test 6: Check language files exist
    echo "\nTest 6: Checking language files...\n";
    $lang_files = [
        'application/language/french/email_lists_lang.php',
        'application/language/english/email_lists_lang.php',
        'application/language/dutch/email_lists_lang.php'
    ];

    foreach ($lang_files as $file) {
        if (file_exists($file)) {
            echo "  ✓ $file\n";
        } else {
            throw new Exception("Language file missing: $file");
        }
    }

    // Test 7: Check JavaScript file exists
    echo "\nTest 7: Checking JavaScript file...\n";
    if (file_exists('assets/javascript/email_lists.js')) {
        echo "  ✓ assets/javascript/email_lists.js\n";
    } else {
        throw new Exception("JavaScript file missing");
    }

    echo "\n=== ✓ ALL SMOKE TESTS PASSED ===\n\n";
    exit(0);

} catch (Exception $e) {
    echo "\n=== ✗ SMOKE TEST FAILED ===\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
