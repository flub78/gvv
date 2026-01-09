<?php
// Direct SQL test
$dbhost = 'localhost';
$dbuser = 'gvv_user';
$dbpass = 'lfoyfgbj';
$dbname = 'gvv2';

$conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$date = date('Y-m-d');
$start_datetime = $date . ' 00:00:00';
$end_datetime = $date . ' 23:59:59';

echo "Testing SQL query for date: $date\n";
echo "Start: $start_datetime\n";
echo "End: $end_datetime\n\n";

$sql = "SELECT r.id, r.aircraft_id, r.start_datetime, r.end_datetime, m.macmodele, ma.mprenom, ma.mnom
FROM reservations r
LEFT JOIN machinesa m ON r.aircraft_id = m.macimmat
LEFT JOIN membres ma ON r.pilot_member_id = ma.mlogin
WHERE r.status != 'cancelled'
AND r.start_datetime < '$end_datetime'
AND r.end_datetime > '$start_datetime'
ORDER BY r.aircraft_id, r.start_datetime";

echo "SQL Query:\n";
echo $sql . "\n\n";

$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}

echo "Results: " . $result->num_rows . " rows\n\n";

while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']}, Aircraft: {$row['aircraft_id']}, Start: {$row['start_datetime']}, End: {$row['end_datetime']}, Pilot: {$row['mprenom']} {$row['mnom']}\n";
}

$conn->close();
?>
