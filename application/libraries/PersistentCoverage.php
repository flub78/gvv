<?php

if (! defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package CodeIgniter
 * @author ExpressionEngine Dev Team
 * @copyright Copyright (c) 2006, EllisLab, Inc.
 * @license http://codeigniter.com/user_guide/license.html
 * @link http://codeigniter.com
 * @since Version 1.0
 * @filesource
 *
 */

require 'PHP/CodeCoverage/Autoload.php';

// ------------------------------------------------------------------------
/**
 * Persistent Coverage
 *
 * This library integrates coverage measures into a CodeIgniter application.
 *
 * It is usually quite complex to integrate ḦÜnit, Selenium and the test coverage
 * data with CodeIgniter. By integrating the coverage directly into the application
 * the mechanism can be used whatever the invocation method (for example it also
 * works for tests launched remotely by Selenium RC).
 *
 * Activation is based on the CodeIgniter hooks (Post_controller_constructor and
 * Post_controller). CodeIgniter itself is not instancied before the the
 * Post_controller_constructor so it is not possible to measure coverage inside the
 * constructors.
 *
 * The coverage is based on a cookie.
 * Enable_coverage creates the cookie
 * Disable_coverage destroy the cookie
 * start reloads the coverage contex and starts coverage
 * stop save the coverage context
 */
class PersistentCoverage {
    var $coverage;
    var $persistent_coverage_data = "coverage.data";
    var $CI;
    var $clover_file = "clover.xml";
    var $html_dir = 'code-coverage-report';

    /**
     * Constructor
     *
     * @access public
     * @param
     *            array the array of loggable items
     * @param
     *            string the log file path
     * @param
     *            string the error threshold
     * @param
     *            string the date formatting codes
     */
    public function __construct() {
        // parent::__construct();
        $this->CI = & get_instance();

        $filter = new PHP_CodeCoverage_Filter();
        $filter->addDirectoryToBlacklist('system');
        $filter->addDirectoryToBlacklist('application/logs');
        $filter->addDirectoryToBlacklist('application/third_party');

        $filter->addFileToBlacklist('application/libraries/DX_Auth.php');
        $filter->addFileToBlacklist('application/libraries/DX_Auth_Event.php');
        $this->coverage = new PHP_CodeCoverage(null, $filter);

        if (file_exists($this->persistent_coverage_data)) {
            $s = file_get_contents($this->persistent_coverage_data);
            $this->coverage = unserialize($s);
        }
    }

    /**
     * Enable the coverage
     * Create a file containing a valid coverage object
     */
    public function enable() {
        $this->disable();
        $s = serialize($this->coverage);
        file_put_contents($this->persistent_coverage_data, $s);
    }

    /**
     * Disable coverage
     */
    public function disable() {
        if ($this->active()) {
            unlink($this->persistent_coverage_data);
        }
        if (file_exists($this->clover_file)) {
            unlink($this->clover_file);
        }
    }

    /**
     * true when coverage has been enabled
     */
    public function active() {
        return file_exists($this->persistent_coverage_data);
    }

    /**
     * Reset coverage
     */
    public function start() {
        if ($this->active()) {
            $this->coverage->start("CodeIgniter coverage");
        }
    }

    /**
     * Save the coverage context
     */
    public function stop() {
        if ($this->active()) {
            $this->coverage->stop();
            $s = serialize($this->coverage);
            file_put_contents($this->persistent_coverage_data, $s);
        }
    }

    /**
     * Generate coverage results
     */
    public function coverage_result($type = "clover") {
        // $this->coverage->stop();
        if ($this->active()) {
            if ($type == "clover") {
                $filename = $this->clover_file;
                // echo "generating $filename\n";
                $writer = new PHP_CodeCoverage_Report_Clover();
                $writer->process($this->coverage, $filename);
            } else {

                // echo "generating HTML\n";
                $writer = new PHP_CodeCoverage_Report_HTML();
                $writer->process($this->coverage, $this->html_dir);
            }
        }
    }
}

