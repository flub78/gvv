#!/usr/bin/env php
<?php
/**
 * HelloAsso Payment Test Script
 * 
 * Tests the HelloAsso payment integration
 */

// Set up environment
define('BASEPATH', dirname(__FILE__) . '/system/');
define('APPPATH', dirname(__FILE__) . '/application/');

// Load CodeIgniter (set error reporting first)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'system/core/CodeIgniter.php';

// Initialize CI
$CI = &get_instance();
$CI->load->config('helloasso');

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║         HelloAsso Payment Integration Test                     ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Test 1: Configuration Loaded
echo "✓ Test 1: Configuration Loaded\n";
echo "  Environment: " . $CI->config->item('helloasso_environment') . "\n";
echo "  Auth Method: " . $CI->config->item('helloasso_auth_method') . "\n";
echo "  Debug Mode: " . ($CI->config->item('helloasso_debug') ? 'ENABLED' : 'DISABLED') . "\n";
echo "  Log Level: " . $CI->config->item('helloasso_log_level') . "\n";
echo "  API URL: " . $CI->config->item('helloasso_api_urls')[$CI->config->item('helloasso_environment')] . "\n";
echo "\n";

// Test 2: Configuration Validation
echo "✓ Test 2: Configuration Validation\n";
$oauth_config = $CI->config->item('helloasso_oauth');
$has_client_id = !empty($oauth_config['client_id']);
$has_client_secret = !empty($oauth_config['client_secret']);
$has_merchant_id = !empty($CI->config->item('helloasso_merchant_id'));

echo "  Client ID Set: " . ($has_client_id ? 'YES' : 'NO') . "\n";
echo "  Client Secret Set: " . ($has_client_secret ? 'YES' : 'NO') . "\n";
echo "  Merchant ID Set: " . ($has_merchant_id ? 'YES' : 'NO') . "\n";

if (!$has_client_id || !$has_client_secret || !$has_merchant_id) {
    echo "\n  ⚠️  WARNING: Some required credentials are not set!\n";
    echo "  Make sure env vars are set:\n";
    echo "    - HELLOASSO_CLIENT_ID\n";
    echo "    - HELLOASSO_CLIENT_SECRET\n";
    echo "    - HELLOASSO_MERCHANT_ID\n";
}
echo "\n";

// Test 3: Controller Path
echo "✓ Test 3: Controller File Exists\n";
$controller_path = APPPATH . 'controllers/payments.php';
if (file_exists($controller_path)) {
    echo "  Path: ✓ $controller_path\n";
    echo "  Size: " . filesize($controller_path) . " bytes\n";
} else {
    echo "  Path: ✗ NOT FOUND\n";
}
echo "\n";

// Test 4: Views Exist
echo "✓ Test 4: View Files Exist\n";
$views = array(
    'test_helloasso' => APPPATH . 'views/payments/test_helloasso.php',
    'callback' => APPPATH . 'views/payments/helloasso_callback.php',
);
foreach ($views as $name => $path) {
    $exists = file_exists($path) ? '✓' : '✗';
    $size = file_exists($path) ? filesize($path) : 0;
    echo "  $exists $name: $path ($size bytes)\n";
}
echo "\n";

// Test 5: Logs Directory
echo "✓ Test 5: Logs Directory\n";
$log_dir = APPPATH . 'logs';
if (is_dir($log_dir)) {
    echo "  Directory: ✓ $log_dir\n";
    echo "  Writable: " . (is_writable($log_dir) ? '✓ YES' : '✗ NO') . "\n";
} else {
    echo "  Directory: ✗ NOT FOUND\n";
}
echo "\n";

// Test 6: Configuration Constraints
echo "✓ Test 6: Payment Constraints\n";
echo "  Min Amount: €" . $CI->config->item('helloasso_min_amount') . "\n";
echo "  Max Amount: €" . $CI->config->item('helloasso_max_amount') . "\n";
echo "  Currency: " . $CI->config->item('helloasso_currency') . "\n";
echo "  Timeout: " . $CI->config->item('helloasso_timeout') . " seconds\n";
echo "  SSL Verify: " . ($CI->config->item('helloasso_verify_ssl') ? 'YES' : 'NO') . "\n";
echo "\n";

// Test 7: Return URLs
echo "✓ Test 7: Return URLs Configured\n";
echo "  Success URL: " . $CI->config->item('helloasso_return_url_success') . "\n";
echo "  Failure URL: " . $CI->config->item('helloasso_return_url_failure') . "\n";
echo "\n";

// Summary
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                     Test Summary                               ║\n";
echo "╠════════════════════════════════════════════════════════════════╣\n";

if ($has_client_id && $has_client_secret && $has_merchant_id && file_exists($controller_path)) {
    echo "║  ✓ Configuration and files look good!                         ║\n";
    echo "║                                                              ║\n";
    echo "║  Next: Try accessing the payment form at:                   ║\n";
    echo "║  http://gvv.net/payments/test_helloasso                     ║\n";
} else {
    echo "║  ⚠️  Some configuration is missing. Please check:            ║\n";
    if (!$has_client_id) echo "║     - HELLOASSO_CLIENT_ID environment variable                   ║\n";
    if (!$has_client_secret) echo "║     - HELLOASSO_CLIENT_SECRET environment variable               ║\n";
    if (!$has_merchant_id) echo "║     - HELLOASSO_MERCHANT_ID environment variable                 ║\n";
    if (!file_exists($controller_path)) echo "║     - Payments controller file                                   ║\n";
}

echo "║                                                              ║\n";
echo "║  Logs: application/logs/helloasso_payments.log               ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";
