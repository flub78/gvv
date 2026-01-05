<?php
/**
 * Simple fixture regenerator - directly using database
 */

// Read database config manually
$host = 'localhost';
$user = 'gvv_user';
$pass = 'lfoyfgbj';
$db = 'gvv2';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}

$result = [];

// Pilots with 411 account
$res = $conn->query("
    SELECT m.mlogin, CONCAT(m.mprenom, ' ', m.mnom) as full_name, m.mprenom, m.mnom, c.compte_id,
           CONCAT('(411) ', CONCAT(m.mprenom, ' ', m.mnom)) as account_label
    FROM membres m
    JOIN comptes c ON m.mlogin = c.mlogin AND c.compte_type = 411
    ORDER BY m.mlogin LIMIT 6
");
$pilots = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $pilots[] = [
            'login' => $row['mlogin'],
            'full_name' => $row['full_name'],
            'first_name' => $row['mprenom'],
            'last_name' => $row['mnom'],
            'account_id' => $row['compte_id'],
            'account_label' => $row['account_label']
        ];
    }
}
$result['pilots'] = $pilots;

// Glider instructors
$res = $conn->query("
    SELECT m.mlogin, CONCAT(m.mprenom, ' ', m.mnom) as full_name, m.mprenom, m.mnom
    FROM membres m
    WHERE m.qual_ITP > 0 OR m.qual_IVV > 0
    ORDER BY m.mlogin LIMIT 6
");
$gi = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $gi[] = [
            'login' => $row['mlogin'],
            'full_name' => $row['full_name'],
            'first_name' => $row['mprenom'],
            'last_name' => $row['mnom'],
            'qualification' => '',
            'account_id' => 0,
            'account_label' => "(411) {$row['full_name']}"
        ];
    }
}

// Airplane instructors
$res = $conn->query("
    SELECT m.mlogin, CONCAT(m.mprenom, ' ', m.mnom) as full_name, m.mprenom, m.mnom
    FROM membres m
    WHERE m.qual_FI_AVION > 0 OR m.qual_FE_AVION > 0
    ORDER BY m.mlogin LIMIT 9
");
$ai = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $ai[] = [
            'login' => $row['mlogin'],
            'full_name' => $row['full_name'],
            'first_name' => $row['mprenom'],
            'last_name' => $row['mnom'],
            'qualification' => '',
            'account_id' => 0,
            'account_label' => "(411) {$row['full_name']}"
        ];
    }
}

$result['instructors'] = ['glider' => $gi, 'airplane' => $ai];

// Two-seater gliders
$res = $conn->query("
    SELECT immatriculation, nom, type FROM machines
    WHERE num_places = 2
    ORDER BY immatriculation LIMIT 5
");
$gliders = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $gliders[] = [
            'registration' => trim($row['immatriculation']),
            'name' => $row['nom'],
            'type' => $row['type']
        ];
    }
}
$result['gliders'] = ['two_seater' => $gliders];

// Tow planes
$res = $conn->query("
    SELECT immatriculation, nom, type FROM machines
    WHERE (num_places = 1 OR num_places IS NULL) AND type NOT LIKE '%planeur%'
    ORDER BY immatriculation LIMIT 4
");
$tow = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $tow[] = [
            'registration' => trim($row['immatriculation']),
            'name' => $row['nom'],
            'type' => $row['type']
        ];
    }
}
$result['tow_planes'] = $tow;

$result['metadata'] = [
    'extracted_at' => date('Y-m-d H:i:s'),
    'database' => $db,
    'version' => '1.0'
];

$path = dirname(__DIR__) . '/playwright/test-data/fixtures.json';
@mkdir(dirname($path), 0755, true);
file_put_contents($path, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "✓ Fixtures regenerated!\n";
echo "  Pilots: " . count($pilots) . "\n";
echo "  Glider Instructors: " . count($gi) . "\n";
echo "  Airplane Instructors: " . count($ai) . "\n";
echo "  Gliders: " . count($gliders) . "\n";
echo "  Tow Planes: " . count($tow) . "\n";
?>
