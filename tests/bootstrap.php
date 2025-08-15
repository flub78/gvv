<?php
define('BASEPATH', TRUE);
define('ENVIRONMENT', 'testing');

$system_path = '../system';
$application_folder = '../application';

require_once $system_path.'/core/Common.php';
require_once APPPATH.'config/database.php';

// Charger CI
$CI =& get_instance();