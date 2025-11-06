<?php
/**
 * Quick test to debug email lists model issues
 */

// Simple test setup
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/test';

// Load CodeIgniter
require_once('index.php');

// Load the model
$CI =& get_instance();
$CI->load->model('email_lists_model');

echo "Testing email lists model methods...\n";

// Test add_external_email
echo "\n1. Testing add_external_email with list_id=1:\n";
$result = $CI->email_lists_model->add_external_email(1, 'test@example.com', 'Test User');
echo "Result: " . var_export($result, true) . "\n";

// Test add_manual_member
echo "\n2. Testing add_manual_member with list_id=1:\n";
$result = $CI->email_lists_model->add_manual_member(1, 'testuser');
echo "Result: " . var_export($result, true) . "\n";

// Test add_role_to_list
echo "\n3. Testing add_role_to_list with list_id=1:\n";
$result = $CI->email_lists_model->add_role_to_list(1, 1, 1);
echo "Result: " . var_export($result, true) . "\n";

// Test get_list
echo "\n4. Testing get_list with id=1:\n";
$result = $CI->email_lists_model->get_list(1);
echo "Result: " . var_export($result, true) . "\n";

// Check if email_lists table exists and has data
echo "\n5. Checking if email_lists table exists:\n";
$query = $CI->db->query("SHOW TABLES LIKE 'email_lists'");
$table_exists = $query->num_rows() > 0;
echo "email_lists table exists: " . ($table_exists ? 'YES' : 'NO') . "\n";

if ($table_exists) {
    $query = $CI->db->get('email_lists');
    echo "Number of email lists: " . $query->num_rows() . "\n";
} else {
    echo "email_lists table does NOT exist!\n";
}

echo "\nTest completed.\n";
?>