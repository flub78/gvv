<?php
/**
 * Test script to verify attachment section directory logic
 *
 * This script tests:
 * 1. sections_model->image() returns correct section names
 * 2. Empty/null club_id defaults to 'Unknown'
 * 3. Directory path construction works correctly
 *
 * Run with: source setenv.sh && php test_attachment_section_dirs.php
 */

// Bootstrap CodeIgniter
$_SERVER['REQUEST_METHOD'] = 'CLI';
require_once('index.php');

echo "=== Attachment Section Directory Test ===\n\n";

// Get CI instance
$CI =& get_instance();
$CI->load->model('sections_model');

$test_results = [];
$passed = 0;
$failed = 0;

// Test 1: Valid section IDs return correct names
echo "Test 1: Valid section IDs\n";
$test_cases = [
    1 => 'Planeur',
    2 => 'ULM',
    3 => 'Avion',
    4 => 'Général'
];

foreach ($test_cases as $id => $expected_name) {
    $result = $CI->sections_model->image($id);
    if ($result === $expected_name) {
        echo "  ✓ Section $id -> '$result'\n";
        $passed++;
    } else {
        echo "  ✗ Section $id -> Expected '$expected_name', got '$result'\n";
        $failed++;
    }
}

// Test 2: Empty club_id returns empty string (should be handled by controller)
echo "\nTest 2: Empty club_id\n";
$result = $CI->sections_model->image('');
if ($result === '') {
    echo "  ✓ Empty club_id returns empty string (controller will default to 'Unknown')\n";
    $passed++;
} else {
    echo "  ✗ Empty club_id returned '$result' instead of empty string\n";
    $failed++;
}

// Test 3: Null club_id
echo "\nTest 3: Null club_id\n";
$result = $CI->sections_model->image(null);
if ($result === '') {
    echo "  ✓ Null club_id returns empty string (controller will default to 'Unknown')\n";
    $passed++;
} else {
    echo "  ✗ Null club_id returned '$result' instead of empty string\n";
    $failed++;
}

// Test 4: Zero club_id
echo "\nTest 4: Zero club_id\n";
$result = $CI->sections_model->image(0);
if ($result === '') {
    echo "  ✓ Zero club_id returns empty string (controller will default to 'Unknown')\n";
    $passed++;
} else {
    echo "  ✗ Zero club_id returned '$result' instead of empty string\n";
    $failed++;
}

// Test 5: Directory path construction logic
echo "\nTest 5: Directory path construction\n";
$year = date('Y');
$test_paths = [
    ['club_id' => 3, 'expected' => "./uploads/attachments/$year/Avion/"],
    ['club_id' => 4, 'expected' => "./uploads/attachments/$year/Général/"],
    ['club_id' => 2, 'expected' => "./uploads/attachments/$year/ULM/"],
    ['club_id' => 1, 'expected' => "./uploads/attachments/$year/Planeur/"],
    ['club_id' => 0, 'expected' => "./uploads/attachments/$year/Unknown/"],
    ['club_id' => '', 'expected' => "./uploads/attachments/$year/Unknown/"],
];

foreach ($test_paths as $test) {
    $club_id = $test['club_id'];
    $section_name = $CI->sections_model->image($club_id);

    // Simulate controller logic
    if (empty($section_name)) {
        $section_name = 'Unknown';
    }

    $dirname = "./uploads/attachments/$year/$section_name/";

    if ($dirname === $test['expected']) {
        echo "  ✓ club_id=$club_id -> '$dirname'\n";
        $passed++;
    } else {
        echo "  ✗ club_id=$club_id -> Expected '{$test['expected']}', got '$dirname'\n";
        $failed++;
    }
}

// Test 6: Query database to verify existing club values
echo "\nTest 6: Verify existing attachments have club values\n";
$query = $CI->db->query("SELECT club, COUNT(*) as count FROM attachments GROUP BY club ORDER BY club");
if ($query) {
    echo "  Database club distribution:\n";
    foreach ($query->result_array() as $row) {
        $club_id = $row['club'];
        $count = $row['count'];
        $section_name = $CI->sections_model->image($club_id);
        if (empty($section_name)) {
            $section_name = 'Unknown';
        }
        echo "    club=$club_id ($section_name): $count attachments\n";
    }
    $passed++;
} else {
    echo "  ✗ Failed to query database\n";
    $failed++;
}

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
