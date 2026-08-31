<?php

/**
 * Bootstrap for the export test suite (phpunit_exports.xml).
 *
 * The export tests under application/tests/integration/exports/ are fully
 * simulated — they never touch the database or the CodeIgniter framework — so
 * they do not need the heavy integration_bootstrap.php. The only shared
 * dependency is the TestLogger helper used to keep their output terse.
 */

define('BASEPATH', dirname(__FILE__) . '/../../system/');
define('APPPATH', dirname(__FILE__) . '/../');

require_once APPPATH . 'tests/integration/TestLogger.php';
TestLogger::init('export_tests');
TestLogger::cleanup(10); // keep only the last 10 log files
