<?php
/**
 * Direct test of Gaulois test users creation
 * Access via: http://gvv.net/test_gaulois_creation.php
 */

// Bootstrap CodeIgniter
define('BASEPATH', dirname(__FILE__).'/system/');
require_once('index.php');

// Get the CI instance
$CI = &get_instance();

// Load admin controller
$CI->load->controller('admin');
$admin = new Admin();

// Check authentication
if ($CI->dx_auth->get_username() !== 'fpeignot') {
    echo "Error: Only fpeignot can run this test\n";
    echo "Current user: " . $CI->dx_auth->get_username() . "\n";
    die;
}

echo "Testing Gaulois users creation...\n";
echo "================================\n\n";

// Call the private method using reflection
$reflection = new ReflectionClass('Admin');
$method = $reflection->getMethod('_create_test_gaulois_users');
$method->setAccessible(true);

$result = $method->invoke($admin);

echo "Result:\n";
echo "  Created: " . $result['created'] . "\n";
if (!empty($result['errors'])) {
    echo "  Errors:\n";
    foreach ($result['errors'] as $error) {
        echo "    - " . $error . "\n";
    }
}

// Verify data was created
$CI->db->where_in('username', array('asterix', 'obelix', 'abraracourcix', 'goudurix'));
$users = $CI->db->get('users')->num_rows();

$CI->db->where_in('mlogin', array('asterix', 'obelix', 'abraracourcix', 'goudurix'));
$membres = $CI->db->get('membres')->num_rows();

$CI->db->where_in('pilote', array('asterix', 'obelix', 'abraracourcix', 'goudurix'));
$comptes = $CI->db->get('comptes')->num_rows();

echo "\n\nVerification:\n";
echo "  Users created: $users\n";
echo "  Membres created: $membres\n";
echo "  Accounts created: $comptes\n";

if ($users > 0 && $membres > 0 && $comptes > 0) {
    echo "\n✓ SUCCESS: All test data created successfully!\n";
} else {
    echo "\n✗ FAILURE: Some test data was not created\n";
}
?>
