#!/usr/bin/env php
<?php
/**
 * HelloAsso Payment Configuration Test (Simple)
 * 
 * Tests files and basic configuration without CI
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║         HelloAsso Payment Integration - File Test              ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$base_path = dirname(__FILE__);

// Test 1: Configuration File
echo "✓ Test 1: Configuration File\n";
$config_file = $base_path . '/application/config/helloasso.php';
if (file_exists($config_file)) {
    echo "  Path: ✓ EXISTS\n";
    echo "  Size: " . filesize($config_file) . " bytes\n";
    $content = file_get_contents($config_file);
    echo "  Debug Mode: " . (strpos($content, "'helloasso_debug' => TRUE") !== false ? 'ENABLED ✓' : 'DISABLED') . "\n";
    echo "  Sandbox URLs: " . (strpos($content, 'sandbox-api.helloasso.com') !== false ? 'CONFIGURED ✓' : 'NOT FOUND') . "\n";
} else {
    echo "  Path: ✗ NOT FOUND\n";
}
echo "\n";

// Test 2: Controller
echo "✓ Test 2: Payments Controller\n";
$controller = $base_path . '/application/controllers/payments.php';
if (file_exists($controller)) {
    echo "  Path: ✓ EXISTS\n";
    echo "  Size: " . filesize($controller) . " bytes\n";
    $content = file_get_contents($controller);
    echo "  Class: " . (strpos($content, 'class Payments') !== false ? 'DEFINED ✓' : 'NOT FOUND') . "\n";
    echo "  test_helloasso method: " . (strpos($content, 'public function test_helloasso') !== false ? 'FOUND ✓' : 'NOT FOUND') . "\n";
    echo "  API integration: " . (strpos($content, '_call_helloasso_api') !== false ? 'FOUND ✓' : 'NOT FOUND') . "\n";
} else {
    echo "  Path: ✗ NOT FOUND\n";
}
echo "\n";

// Test 3: Views
echo "✓ Test 3: Payment Views\n";
$views = array(
    'Payment Form' => $base_path . '/application/views/payments/test_helloasso.php',
    'Callback' => $base_path . '/application/views/payments/helloasso_callback.php',
);

foreach ($views as $name => $path) {
    if (file_exists($path)) {
        echo "  $name: ✓ " . basename($path) . " (" . filesize($path) . " bytes)\n";
    } else {
        echo "  $name: ✗ NOT FOUND\n";
    }
}
echo "\n";

// Test 4: Logs Directory
echo "✓ Test 4: Logs Directory\n";
$log_dir = $base_path . '/application/logs';
if (is_dir($log_dir)) {
    echo "  Directory: ✓ EXISTS\n";
    echo "  Writable: " . (is_writable($log_dir) ? '✓ YES' : '✗ NO (need chmod +wx)') . "\n";
    
    // Check for existing log file
    $log_file = $log_dir . '/helloasso_payments.log';
    if (file_exists($log_file)) {
        echo "  Log file: ✓ " . basename($log_file) . " (" . filesize($log_file) . " bytes)\n";
    } else {
        echo "  Log file: (will be created on first API call)\n";
    }
} else {
    echo "  Directory: ✗ NOT FOUND\n";
}
echo "\n";

// Test 5: Configuration Content Validation
echo "✓ Test 5: Configuration Details\n";
$config_content = file_get_contents($config_file);

// Extract some key config values using regex
if (preg_match("/\['helloasso_auth_method'\]\s*=\s*['\"](\w+)['\"]/", $config_content, $m)) {
    echo "  Auth Method: " . $m[1] . "\n";
}
if (preg_match("/\['helloasso_environment'\]\s*=\s*getenv\(['\"](\w+)['\"]\)\s*\?:\s*['\"](\w+)['\"]/", $config_content, $m)) {
    echo "  Default Environment: " . $m[2] . "\n";
}
if (preg_match("/\['helloasso_min_amount'\]\s*=\s*([\d.]+)/", $config_content, $m)) {
    echo "  Min Amount: €" . $m[1] . "\n";
}
if (preg_match("/\['helloasso_max_amount'\]\s*=\s*([\d.]+)/", $config_content, $m)) {
    echo "  Max Amount: €" . $m[1] . "\n";
}
if (preg_match("/\['helloasso_timeout'\]\s*=\s*(\d+)/", $config_content, $m)) {
    echo "  API Timeout: " . $m[1] . " seconds\n";
}

echo "\n";

// Test 6: Environment Variables
echo "✓ Test 6: Environment Variables Status\n";
$env_vars = array(
    'HELLOASSO_ENV' => getenv('HELLOASSO_ENV'),
    'HELLOASSO_CLIENT_ID' => getenv('HELLOASSO_CLIENT_ID'),
    'HELLOASSO_CLIENT_SECRET' => getenv('HELLOASSO_CLIENT_SECRET'),
    'HELLOASSO_MERCHANT_ID' => getenv('HELLOASSO_MERCHANT_ID'),
    'HELLOASSO_ACCOUNT_SLUG' => getenv('HELLOASSO_ACCOUNT_SLUG'),
    'APP_URL' => getenv('APP_URL'),
);

foreach ($env_vars as $var => $value) {
    $status = $value !== false ? '✓ SET' : '✗ NOT SET';
    $display = $value !== false ? (strlen($value) > 30 ? substr($value, 0, 30) . '...' : $value) : '';
    echo "  $var: $status $display\n";
}

echo "\n";

// Summary
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                     Summary                                    ║\n";
echo "╠════════════════════════════════════════════════════════════════╣\n";

$all_files_exist = file_exists($config_file) && 
                   file_exists($controller) && 
                   file_exists($views['Payment Form']) && 
                   file_exists($views['Callback']);

if ($all_files_exist) {
    echo "║  ✓ All implementation files created successfully!             ║\n";
    echo "║                                                              ║\n";
    echo "║  Next Steps:                                                 ║\n";
    echo "║  1. Verify HelloAsso credentials are in env vars             ║\n";
    echo "║  2. Login to GVV as a dev admin user                         ║\n";
    echo "║  3. Access: http://gvv.net/payments/test_helloasso           ║\n";
    echo "║  4. Submit test payment form                                 ║\n";
    echo "║  5. Check log: application/logs/helloasso_payments.log       ║\n";
} else {
    echo "║  ✗ Some files are missing:                                   ║\n";
    if (!file_exists($config_file)) echo "║     - Config file not found                                      ║\n";
    if (!file_exists($controller)) echo "║     - Controller not found                                       ║\n";
    if (!file_exists($views['Payment Form'])) echo "║     - Payment form view not found                                ║\n";
    if (!file_exists($views['Callback'])) echo "║     - Callback view not found                                    ║\n";
}

echo "║                                                              ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";
