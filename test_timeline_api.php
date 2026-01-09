<?php
// Test the timeline API
require_once('index.php');

// Simulate GET request
$_GET['date'] = date('Y-m-d');

$CI = &get_instance();
$CI->load->model('reservations_model');

echo "Testing timeline API for date: " . $_GET['date'] . "\n\n";

// Test get_aircraft_list
echo "=== Aircraft List ===\n";
$aircraft = $CI->reservations_model->get_aircraft_list();
echo "Number of aircraft: " . count($aircraft) . "\n";
foreach ($aircraft as $id => $name) {
    echo "  $id => $name\n";
}

echo "\n=== Day Reservations ===\n";
$reservations = $CI->reservations_model->get_day_reservations($_GET['date']);
echo "Number of reservations: " . count($reservations) . "\n";
foreach ($reservations as $res) {
    echo "  ID: {$res['id']}, Aircraft: {$res['aircraft_id']}, Start: {$res['start_datetime']}, End: {$res['end_datetime']}, Status: {$res['status']}\n";
}

echo "\n=== Timeline Events ===\n";
$events = $CI->reservations_model->get_timeline_events($_GET['date']);
echo "Number of events: " . count($events) . "\n";
foreach ($events as $event) {
    echo "  ID: {$event['id']}, ResourceID: {$event['resourceId']}, Title: {$event['title']}, Start: {$event['start']}, End: {$event['end']}\n";
}

echo "\nJSON Response:\n";
echo json_encode([
    'date' => $_GET['date'],
    'resources' => array_values(array_map(function($id, $title) { return ['id' => $id, 'title' => $title]; }, array_keys($aircraft), $aircraft)),
    'events' => $events
], JSON_PRETTY_PRINT) . "\n";
