#!/usr/bin/env php
<?php
/**
 * Extract Test Data for Playwright Tests
 *
 * This script extracts real pilot, aircraft, and account data from the GVV database
 * to be used in Playwright end-to-end tests. This approach solves the problem of
 * hardcoded test data that doesn't exist after database anonymization.
 *
 * Usage:
 *   php extract_test_data.php [output_file]
 *
 * Default output: playwright/test-data/fixtures.json
 *
 * The script extracts:
 * - Regular pilots with accounts
 * - Glider instructors
 * - Airplane instructors (tow pilots)
 * - Two-seater gliders
 * - Single-seater gliders
 * - Tow planes (remorqueurs)
 * - Member accounts (for billing)
 *
 * @package GVV
 * @author  GVV Team
 */

// Database configuration
$db_config = array(
    'host' => getenv('MYSQL_HOST') ?: 'localhost',
    'user' => getenv('MYSQL_USER') ?: 'gvv_user',
    'password' => getenv('MYSQL_PASSWORD') ?: 'lfoyfgbj',
    'database' => getenv('MYSQL_DATABASE') ?: 'gvv2'
);

// Output file
$output_file = $argv[1] ?? __DIR__ . '/../test-data/fixtures.json';

// Connect to database
try {
    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_config['user'], $db_config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to database: {$db_config['database']}\n";
} catch (PDOException $e) {
    die("✗ Database connection failed: " . $e->getMessage() . "\n");
}

// Initialize test data structure
$test_data = array(
    'metadata' => array(
        'extracted_at' => date('Y-m-d H:i:s'),
        'database' => $db_config['database'],
        'version' => '1.0'
    ),
    'pilots' => array(),
    'instructors' => array(
        'glider' => array(),
        'airplane' => array()
    ),
    'gliders' => array(
        'two_seater' => array(),
        'single_seater' => array()
    ),
    'tow_planes' => array(),
    'accounts' => array()
);

echo "\nExtracting test data...\n";
echo str_repeat('=', 60) . "\n";

// Extract regular pilots with accounts
echo "\n1. Extracting regular pilots with accounts...\n";
$stmt = $pdo->query("
    SELECT
        m.mlogin,
        CONCAT(m.mprenom, ' ', m.mnom) as full_name,
        m.mprenom as first_name,
        m.mnom as last_name,
        m.actif,
        c.id as account_id,
        CONCAT('(411) ', m.mprenom, ' ', m.mnom) as account_label
    FROM membres m
    LEFT JOIN comptes c ON c.pilote = m.mlogin AND c.codec LIKE '411%'
    WHERE m.actif = 1
        AND m.ext = 0
        AND c.id IS NOT NULL
    ORDER BY m.mnom, m.mprenom
    LIMIT 10
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $test_data['pilots'][] = array(
        'login' => $row['mlogin'],
        'full_name' => $row['full_name'],
        'first_name' => $row['first_name'],
        'last_name' => $row['last_name'],
        'account_id' => (int)$row['account_id'],
        'account_label' => $row['account_label']
    );
}
echo "  ✓ Found " . count($test_data['pilots']) . " active pilots with accounts\n";

// Extract glider instructors
echo "\n2. Extracting glider instructors...\n";
$stmt = $pdo->query("
    SELECT
        m.mlogin,
        CONCAT(m.mprenom, ' ', m.mnom) as full_name,
        m.mprenom as first_name,
        m.mnom as last_name,
        m.inst_glider,
        c.id as account_id,
        CONCAT('(411) ', m.mprenom, ' ', m.mnom) as account_label
    FROM membres m
    LEFT JOIN comptes c ON c.pilote = m.mlogin AND c.codec LIKE '411%'
    WHERE m.actif = 1
        AND m.ext = 0
        AND m.inst_glider IS NOT NULL
        AND m.inst_glider != ''
        AND c.id IS NOT NULL
    ORDER BY m.mnom, m.mprenom
    LIMIT 5
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $test_data['instructors']['glider'][] = array(
        'login' => $row['mlogin'],
        'full_name' => $row['full_name'],
        'first_name' => $row['first_name'],
        'last_name' => $row['last_name'],
        'qualification' => $row['inst_glider'],
        'account_id' => (int)$row['account_id'],
        'account_label' => $row['account_label']
    );
}
echo "  ✓ Found " . count($test_data['instructors']['glider']) . " glider instructors\n";

// Extract airplane instructors (tow pilots)
echo "\n3. Extracting airplane instructors (tow pilots)...\n";
$stmt = $pdo->query("
    SELECT
        m.mlogin,
        CONCAT(m.mprenom, ' ', m.mnom) as full_name,
        m.mprenom as first_name,
        m.mnom as last_name,
        m.inst_airplane
    FROM membres m
    WHERE m.actif = 1
        AND m.ext = 0
        AND m.inst_airplane IS NOT NULL
        AND m.inst_airplane != ''
    ORDER BY m.mnom, m.mprenom
    LIMIT 5
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $test_data['instructors']['airplane'][] = array(
        'login' => $row['mlogin'],
        'full_name' => $row['full_name'],
        'first_name' => $row['first_name'],
        'last_name' => $row['last_name'],
        'qualification' => $row['inst_airplane']
    );
}
echo "  ✓ Found " . count($test_data['instructors']['airplane']) . " airplane instructors\n";

// Extract two-seater gliders
echo "\n4. Extracting two-seater gliders...\n";
$stmt = $pdo->query("
    SELECT
        mpimmat as registration,
        mpmodele as model,
        mpconstruc as manufacturer,
        mpbiplace as seats,
        mpautonome as autonomous
    FROM machinesp
    WHERE actif = 1
        AND mpbiplace = '1'
    ORDER BY mpimmat
    LIMIT 5
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $test_data['gliders']['two_seater'][] = array(
        'registration' => $row['registration'],
        'model' => $row['model'],
        'manufacturer' => $row['manufacturer'],
        'seats' => 2,
        'autonomous' => (bool)$row['autonomous']
    );
}
echo "  ✓ Found " . count($test_data['gliders']['two_seater']) . " two-seater gliders\n";

// Extract single-seater gliders
echo "\n5. Extracting single-seater gliders...\n";
$stmt = $pdo->query("
    SELECT
        mpimmat as registration,
        mpmodele as model,
        mpconstruc as manufacturer,
        mpbiplace as seats,
        mpautonome as autonomous
    FROM machinesp
    WHERE actif = 1
        AND mpbiplace = '0'
    ORDER BY mpimmat
    LIMIT 5
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $test_data['gliders']['single_seater'][] = array(
        'registration' => $row['registration'],
        'model' => $row['model'],
        'manufacturer' => $row['manufacturer'],
        'seats' => 1,
        'autonomous' => (bool)$row['autonomous']
    );
}
echo "  ✓ Found " . count($test_data['gliders']['single_seater']) . " single-seater gliders\n";

// Extract tow planes
echo "\n6. Extracting tow planes...\n";
$stmt = $pdo->query("
    SELECT
        macimmat as registration,
        macmodele as model,
        macconstruc as manufacturer,
        macrem as is_tow_plane
    FROM machinesa
    WHERE actif = 1
        AND macrem = 1
    ORDER BY macimmat
    LIMIT 5
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $test_data['tow_planes'][] = array(
        'registration' => $row['registration'],
        'model' => $row['model'],
        'manufacturer' => $row['manufacturer']
    );
}
echo "  ✓ Found " . count($test_data['tow_planes']) . " tow planes\n";

// Extract member accounts (for billing)
echo "\n7. Extracting member accounts...\n";
$stmt = $pdo->query("
    SELECT
        c.id,
        c.nom as account_name,
        c.pilote as pilot_login,
        c.codec as account_code,
        CONCAT('(', c.codec, ') ', c.nom) as label
    FROM comptes c
    WHERE c.actif = 1
        AND c.codec LIKE '411%'
        AND c.pilote IS NOT NULL
        AND c.pilote != ''
    ORDER BY c.nom
    LIMIT 20
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $test_data['accounts'][] = array(
        'id' => (int)$row['id'],
        'name' => $row['account_name'],
        'pilot_login' => $row['pilot_login'],
        'code' => $row['account_code'],
        'label' => $row['label']
    );
}
echo "  ✓ Found " . count($test_data['accounts']) . " member accounts\n";

// Create output directory if it doesn't exist
$output_dir = dirname($output_file);
if (!is_dir($output_dir)) {
    mkdir($output_dir, 0755, true);
    echo "\n✓ Created output directory: $output_dir\n";
}

// Write JSON file
$json = json_encode($test_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
if (file_put_contents($output_file, $json) === false) {
    die("\n✗ Failed to write output file: $output_file\n");
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "✓ Test data extracted successfully!\n";
echo "✓ Output file: $output_file\n";
echo "✓ File size: " . number_format(filesize($output_file)) . " bytes\n";

// Display summary
echo "\n" . str_repeat('=', 60) . "\n";
echo "SUMMARY:\n";
echo str_repeat('=', 60) . "\n";
echo sprintf("  Pilots:                  %3d\n", count($test_data['pilots']));
echo sprintf("  Glider instructors:      %3d\n", count($test_data['instructors']['glider']));
echo sprintf("  Airplane instructors:    %3d\n", count($test_data['instructors']['airplane']));
echo sprintf("  Two-seater gliders:      %3d\n", count($test_data['gliders']['two_seater']));
echo sprintf("  Single-seater gliders:   %3d\n", count($test_data['gliders']['single_seater']));
echo sprintf("  Tow planes:              %3d\n", count($test_data['tow_planes']));
echo sprintf("  Member accounts:         %3d\n", count($test_data['accounts']));
echo str_repeat('=', 60) . "\n";

// Sample data preview
if (count($test_data['pilots']) > 0) {
    echo "\nSample pilot: " . $test_data['pilots'][0]['full_name'] . "\n";
}
if (count($test_data['instructors']['glider']) > 0) {
    echo "Sample instructor: " . $test_data['instructors']['glider'][0]['full_name'] . "\n";
}
if (count($test_data['gliders']['two_seater']) > 0) {
    echo "Sample glider: " . $test_data['gliders']['two_seater'][0]['registration'] . "\n";
}

echo "\nDone!\n";
exit(0);
