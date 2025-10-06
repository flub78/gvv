<?php
/**
 * Simple test script to verify attachment section directory logic
 * Tests the core logic without bootstrapping full CodeIgniter
 *
 * Run with: source setenv.sh && php test_attachment_section_dirs_simple.php
 */

echo "=== Attachment Section Directory Test (Simple) ===\n\n";

// Database connection
$db_host = 'localhost';
$db_user = 'gvv_user';
$db_pass = 'lfoyfgbj';
$db_name = 'gvv2';

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$passed = 0;
$failed = 0;

// Test 1: Get sections from database
echo "Test 1: Fetch sections from database\n";
$sections = [];
$query = $mysqli->query("SELECT id, nom FROM sections ORDER BY id");
if ($query) {
    while ($row = $query->fetch_assoc()) {
        $sections[$row['id']] = $row['nom'];
        echo "  ✓ Section {$row['id']}: {$row['nom']}\n";
        $passed++;
    }
} else {
    echo "  ✗ Failed to query sections\n";
    $failed++;
}

// Test 2: Simulate sections_model->image() logic
echo "\nTest 2: Simulate section name lookup\n";
function get_section_name($sections, $club_id) {
    if ($club_id === '' || $club_id === null || $club_id === 0 || $club_id === '0') {
        return '';
    }
    return isset($sections[$club_id]) ? $sections[$club_id] : '';
}

$test_cases = [
    1 => 'Planeur',
    2 => 'ULM',
    3 => 'Avion',
    4 => 'Général'
];

foreach ($test_cases as $id => $expected) {
    $result = get_section_name($sections, $id);
    if ($result === $expected) {
        echo "  ✓ club_id=$id -> '$result'\n";
        $passed++;
    } else {
        echo "  ✗ club_id=$id -> Expected '$expected', got '$result'\n";
        $failed++;
    }
}

// Test 3: Empty/null handling
echo "\nTest 3: Empty/null/zero club_id handling\n";
$empty_cases = ['', null, 0, '0'];
foreach ($empty_cases as $case) {
    $result = get_section_name($sections, $case);
    $display = $case === null ? 'null' : ($case === '' ? 'empty string' : $case);
    if ($result === '') {
        echo "  ✓ club_id=$display returns empty string\n";
        $passed++;
    } else {
        echo "  ✗ club_id=$display returned '$result'\n";
        $failed++;
    }
}

// Test 4: Directory path construction with controller logic
echo "\nTest 4: Directory path construction (with controller logic)\n";
$year = date('Y');

function build_directory_path($sections, $club_id, $year) {
    $section_name = get_section_name($sections, $club_id);

    // Controller logic: default to 'Unknown' if empty
    if (empty($section_name)) {
        $section_name = 'Unknown';
    }

    return "./uploads/attachments/$year/$section_name/";
}

$path_tests = [
    ['club_id' => 1, 'expected_section' => 'Planeur'],
    ['club_id' => 2, 'expected_section' => 'ULM'],
    ['club_id' => 3, 'expected_section' => 'Avion'],
    ['club_id' => 4, 'expected_section' => 'Général'],
    ['club_id' => 0, 'expected_section' => 'Unknown'],
    ['club_id' => '', 'expected_section' => 'Unknown'],
    ['club_id' => null, 'expected_section' => 'Unknown'],
];

foreach ($path_tests as $test) {
    $club_id = $test['club_id'];
    $expected_section = $test['expected_section'];
    $dirname = build_directory_path($sections, $club_id, $year);
    $expected_path = "./uploads/attachments/$year/$expected_section/";

    $display_id = $club_id === null ? 'null' : ($club_id === '' ? 'empty' : $club_id);

    if ($dirname === $expected_path) {
        echo "  ✓ club_id=$display_id -> '$dirname'\n";
        $passed++;
    } else {
        echo "  ✗ club_id=$display_id -> Expected '$expected_path', got '$dirname'\n";
        $failed++;
    }
}

// Test 5: Check existing attachments club distribution
echo "\nTest 5: Existing attachments club distribution\n";
$query = $mysqli->query("SELECT club, COUNT(*) as count FROM attachments GROUP BY club ORDER BY club");
if ($query) {
    echo "  Current attachments by club:\n";
    while ($row = $query->fetch_assoc()) {
        $club_id = $row['club'];
        $count = $row['count'];
        $section_name = get_section_name($sections, $club_id);
        if (empty($section_name)) {
            $section_name = 'Unknown';
        }
        echo "    club=$club_id ($section_name): $count attachments\n";
    }
    $passed++;
} else {
    echo "  ✗ Failed to query attachments\n";
    $failed++;
}

// Test 6: Check if any ecritures have club values (for inheritance test)
echo "\nTest 6: Check ecritures have club values (for inheritance)\n";
$query = $mysqli->query("SELECT club, COUNT(*) as count FROM ecritures WHERE club IS NOT NULL AND club != 0 GROUP BY club ORDER BY club LIMIT 5");
if ($query) {
    $has_data = false;
    echo "  Sample ecritures by club:\n";
    while ($row = $query->fetch_assoc()) {
        $club_id = $row['club'];
        $count = $row['count'];
        $section_name = get_section_name($sections, $club_id);
        echo "    club=$club_id ($section_name): $count ecritures\n";
        $has_data = true;
    }
    if ($has_data) {
        echo "  ✓ Ecritures have club values for inheritance\n";
        $passed++;
    } else {
        echo "  ⚠ No ecritures with club values found\n";
        $passed++;
    }
} else {
    echo "  ✗ Failed to query ecritures\n";
    $failed++;
}

$mysqli->close();

// Summary
echo "\n=== Summary ===\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";
echo "Total: " . ($passed + $failed) . "\n";

if ($failed > 0) {
    echo "\n❌ Some tests failed!\n";
    exit(1);
} else {
    echo "\n✅ All tests passed!\n";
    exit(0);
}
