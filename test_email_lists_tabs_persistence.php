<?php
/**
 * Test script to verify email lists tabs session persistence
 * 
 * This script tests:
 * 1. That the email lists form loads properly
 * 2. That the tabs are present in the HTML
 * 3. That the JavaScript for session persistence is included
 */

// Set the test environment
define('ENVIRONMENT', 'testing');

// Include the test bootstrap
require_once __DIR__ . '/index.php';

// Simple HTML output test function
function test_tabs_persistence() {
    $CI =& get_instance();
    
    // Load the language files
    $CI->lang->load('email_lists');
    
    // Mock data for testing
    $data = array(
        'title' => 'Test Email Lists Form',
        'controller' => 'email_lists',
        'action' => 'store',
        'list' => array(
            'name' => '',
            'description' => '',
            'active_member' => 'active',
            'visible' => 1
        ),
        'email_list_id' => null // Creation mode
    );
    
    // Capture the view output
    ob_start();
    $CI->load->view('email_lists/form', $data);
    $html_output = ob_get_clean();
    
    // Test 1: Check if tabs are present
    $has_criteria_tab = strpos($html_output, 'id="criteria-tab"') !== false;
    $has_manual_tab = strpos($html_output, 'id="manual-tab"') !== false;
    $has_import_tab = strpos($html_output, 'id="import-tab"') !== false;
    
    // Test 2: Check if session storage JavaScript is present
    $has_session_storage = strpos($html_output, 'sessionStorage.getItem(\'email_lists_active_tab\')') !== false;
    $has_session_save = strpos($html_output, 'sessionStorage.setItem(\'email_lists_active_tab\'') !== false;
    
    // Test 3: Check if tab event listeners are present
    $has_tab_listeners = strpos($html_output, '#listTabs .nav-link') !== false;
    
    // Output test results
    echo "=== Email Lists Tabs Session Persistence Test ===\n";
    echo "1. Criteria tab present: " . ($has_criteria_tab ? "PASS" : "FAIL") . "\n";
    echo "2. Manual tab present: " . ($has_manual_tab ? "PASS" : "FAIL") . "\n";
    echo "3. Import tab present: " . ($has_import_tab ? "PASS" : "FAIL") . "\n";
    echo "4. Session storage get: " . ($has_session_storage ? "PASS" : "FAIL") . "\n";
    echo "5. Session storage set: " . ($has_session_save ? "PASS" : "FAIL") . "\n";
    echo "6. Tab event listeners: " . ($has_tab_listeners ? "PASS" : "FAIL") . "\n";
    
    $all_passed = $has_criteria_tab && $has_manual_tab && $has_import_tab && 
                  $has_session_storage && $has_session_save && $has_tab_listeners;
    
    echo "\nOverall result: " . ($all_passed ? "PASS - Session persistence implemented correctly" : "FAIL - Some tests failed") . "\n";
    
    return $all_passed;
}

// Run the test if this script is executed directly
if (php_sapi_name() === 'cli') {
    try {
        test_tabs_persistence();
    } catch (Exception $e) {
        echo "Error running test: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>