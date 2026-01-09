#!/usr/bin/env php
<?php
/**
 * Test timeline data retrieval
 */

define('ENVIRONMENT', 'development');
$system_path = 'system';
$application_folder = 'application';

chdir(dirname(__FILE__));

if (realpath($system_path) !== FALSE) {
    $system_path = realpath($system_path) . '/';
}
$system_path = rtrim($system_path, '/') . '/';

define('BASEPATH', str_replace('\\', '/', $system_path));
define('APPPATH', $application_folder . '/');
define('FCPATH', dirname(__FILE__) . '/');
define('SYSDIR', trim(strrchr(trim(BASEPATH, '/'), '/'), '/'));
define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));
define('EXT', '.php');
define('TEST', 1);

require_once BASEPATH . 'core/CodeIgniter.php';

$CI =& get_instance();
$CI->load->model('reservations_model');

$date = date('Y-m-d');
echo "Testing get_day_reservations for $date\n";
echo "========================================\n\n";

$reservations = $CI->reservations_model->get_day_reservations($date);
echo "Reservations found: " . count($reservations) . "\n\n";

foreach ($reservations as $res) {
    echo "ID: {$res['id']}\n";
    echo "Aircraft: {$res['aircraft_model']} ({$res['aircraft_id']})\n";
    echo "Pilot: {$res['pilot_name']}\n";
    echo "Time: {$res['start_datetime']} - {$res['end_datetime']}\n";
    echo "Status: {$res['status']}\n";
    echo "---\n";
}

echo "\n\nTimeline Events JSON:\n";
$events = $CI->reservations_model->get_timeline_events($date);
echo json_encode($events, JSON_PRETTY_PRINT) . "\n";
