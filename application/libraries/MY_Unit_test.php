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

// ------------------------------------------------------------------------
/**
 * MY_Unit_Test
 *
 * The CodeIgniter unit test framework is simple and convenient. Compared
 * to PHPUnit it has the main advantage to be fully integrated and to give
 * access to all CodeIgniter services.
 *
 * There is a project to integrate PHPUnit and CodeIgniter, it is named
 * CIUnit. All versions are not managed by the same team and the loader has
 * some difficulties to find classes when inheritance is used.
 *
 * So I prefer to use the simple CodeIgniter unit tests and to add the few features
 * existing in PHPUnit that I need.
 *
 * - Support for multi-sessions coverage.
 * - Generation of Junit style result files
 *
 * The coverage management is based on PHP/CodeCoverage based itself on xdebug.
 * For coverage, in order to merge the coverage results from different sessions
 * I just save and restore the coverage object between sessions.
 *
 * Junit file result
 *
 * Continuous integration tools like Jenkins are natively able to parse Junit
 * test results. It is usually easier to generate the test results at this format
 * than to develop a Jenkins extension to parse tests result to others formats.
 *
 * Format example
 * <?xml version="1.0" encoding="UTF-8" ?>
 * <testsuite errors="0" failures="0" hostname="iki-linux" name="test-sms" tests="1" time="23.4658439159393" timestamp="Tue Nov 20 09:58:53 CET 2012">
 * <property name="Script" value="/home/idefix/workspace/tool-cardlessSimu/src/test-sms.pl"></property>
 * <property name="Script path" value="/home/idefix/workspace/tool-cardlessSimu/src/test-sms.pl"></property>
 * <property name="Script basename" value="test-sms"></property>
 * <property name="Script directory" value="/home/idefix/workspace/tool-cardlessSimu/src/"></property>
 * <property name="Working directory" value="/media/realcrypt1/workspace/tool-cardlessSimu/tests"></property>
 * <property name="Configuration file name" value="/home/idefix/workspace/tool-cardlessSimu/src/test-sms.ini"></property>
 * <property name="Log file" value="/home/idefix/workspace/tool-cardlessSimu/results/test-sms.log"></property>
 * <property name="Log configuration file" value="log4perl.conf"></property>
 * <property name="Date" value="Tue Nov 20 09:58:53 CET 2012"></property>
 * <property name="User" value="idefix"></property>
 * <property name="OS" value="linux"></property>
 * <property name="Command line Arguments" value=""></property>
 * <property name="Synopsis" value=""></property>
 * <testcase classname="Simulation" name="test-sms" time="23.4658439159393"></testcase>
 * <system-out><![CDATA[2012/11/20 09:58:32 INFO -- Test.Checks : PASSED 1 SMS server connection
 * ]]></system-out>
 * <system-err><![CDATA[]]></system-err>
 * </testsuite>
 *
 * In case of error:
 * <testcase classname="Simulation" name="scn_suspension_1_con" time="">
 * <failure message="Error: unexpected response Message"></failure>
 * </testcase>
 */
class MY_Unit_Test extends CI_Unit_Test {
    var $titles = array();
    var $coverage;
    protected $CI;
    var $persistent_coverage_data = "coverage.data";

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
        parent::__construct();

        $str = "\n" . '<table style="width:100%; font-size:small; margin:10px 0; border-collapse:collapse; border:1px solid #CCC;">';
        $str .= '{rows}';
        $str .= "\n" . '</table>';

        $str_rows = "\n\t" . '<tr>';
        $str_rows .= "\n\t\t" . '<th style="text-align: left; border-bottom:1px solid #CCC;">{item}</th>';
        $str_rows .= "\n\t\t" . '<td style="border-bottom:1px solid #CCC;">{result}</td>';
        $str_rows .= "\n\t" . '</tr>';

        // xdebug_start_code_coverage();
        $this->set_template($str);

        // // Start coverage,
        // $filter = new PHP_CodeCoverage_Filter;
        // $filter->addDirectoryToBlacklist('system');
        // $filter->addDirectoryToBlacklist('application/logs');

        // $filter->addFileToBlacklist('application/libraries/DX_Auth.php');
        // $filter->addFileToBlacklist('application/libraries/DX_Auth_Event.php');
        // $this->coverage = new PHP_CodeCoverage(null, $filter);

        // // if a coverage context is found reload it in order to merge
        // // coverage sessions
        // if (file_exists ($this->persistent_coverage_data)) {
        // $s = file_get_contents($this->persistent_coverage_data);
        // $this->coverage = unserialize($s);
        // }

        // $this->coverage->start("Unit tests suite");
    }

    /**
     * Reset coverage
     */
    public function reset_coverage() {
        // $this->coverage = new PHP_CodeCoverage;
        // if (file_exists ($this->persistent_coverage_data)) {
        // unlink($this->persistent_coverage_data);
        // }
        // $this->coverage->start("Unit tests suite");
    }

    /**
     * Save the coverage context
     */
    public function save_coverage() {
        // $this->coverage->stop();
        // $s = serialize($this->coverage);
        // file_put_contents($this->persistent_coverage_data, $s);
    }

    /**
     * Display a title when testing is active
     */
    public function header($str, $level = "4") {
        $balise = "h" . $level;
        $count = count($this->results);
        $titles[$count] = array(
            'header' => $str,
            'level' => $level
        );
        return ($this->active) ? "<$balise>$str</$balise>" . br() : "";
    }

    /**
     * Run the tests
     *
     * Runs the supplied tests
     *
     * @access public
     * @param
     *            mixed
     * @param
     *            mixed
     * @param
     *            string
     * @return string TODO escape the parameters so the generated XML is still correct
     */
    function run($test, $expected = TRUE, $test_name = 'undefined', $notes = '') {
        if (! $this->active)
            return "";

        $res = parent::run($test, $expected, $test_name, $notes);
        $count = count($this->results);
        $result = $this->results[$count - 1][0]['result'];
        if ($result == "failed")
            $this->results[$count - 1][0]['notes'] .= " result=" . $test . ", expected=" . $expected;

        // echo "res = " . $res . " result = " . $result . " expected = " . $expected . " test = " . $test . " test_name = " . $test_name . " notes = " . $notes;
        return $res;
    }

    /**
     * Generate a backtrace
     *
     * This lets us show file names and line numbers
     *
     * @access private
     * @return array
     */
    function _backtrace($level = 2) {
        if (function_exists('debug_backtrace')) {
            $back = debug_backtrace();

            $file = (! isset($back[$level]['file'])) ? '' : $back[$level]['file'];
            $line = (! isset($back[$level]['line'])) ? '' : $back[$level]['line'];

            return array(
                'file' => $file,
                'line' => $line
            );
        }
        return array(
            'file' => 'Unknown',
            'line' => 'Unknown'
        );
    }

    /**
     * cli_result
     *
     * Display the result in ASCII
     */
    public function cli_result() {
        $results = $this->result();
        $passed = $failed = 0;
        foreach ($results as $row) {
            $result = $row['Result'];
            $name = $row['Test Name'];
            $file = $row['File Name'];
            $line = $row['Line Number'];
            $notes = isset($row['Notes']) ? $row['Notes'] : "";
            $result = $row['Result'];
            if (preg_match('/.*\/application\/(.*)/', $file, $matches)) {
                $file = $matches[1];
            }

            $str = sprintf("%-40s %-40s %6s %s", $file . ":" . $line, $name, $result, $notes);
            echo $str . PHP_EOL;
            if ($result == "Passed") {
                $passed++;
            } else {
                $failed++;
            }
        }
        $count = $passed + $failed;
        echo "Tests total=$count, passed=$passed, failed=$failed" . PHP_EOL;
    }

    /**
     * Generate coverage results
     */
    public function coverage_result($type = "clover", $filename = "clover.xml") {
        // $this->coverage->stop();

        // # echo "generating $filename\n";
        // $writer = new PHP_CodeCoverage_Report_Clover;
        // $writer->process($this->coverage, $filename);

        // # echo "generating HTML\n";
        // $writer = new PHP_CodeCoverage_Report_HTML;
        // $writer->process($this->coverage, 'code-coverage-report');
    }

    /**
     *
     * @param unknown $filename
     * @param string $suitename
     */
    public function XML_result($filename, $suitename = "Test suite") {

        $results = $this->result();
        $passed = $failed = 0;
        foreach ($results as $row) {
            $result = $row['Result'];
            $name = $row['Test Name'];
            $file = $row['File Name'];
            $line = $row['Line Number'];
            $notes = isset($row['Notes']) ? $row['Notes'] : "";
            $result = $row['Result'];
            if (preg_match('/.*\/application\/(.*)/', $file, $matches)) {
                $file = $matches[1];
            }

            $str = sprintf("%-40s %-40s %6s %s", $file . ":" . $line, $name, $result, $notes);
            // echo $str .PHP_EOL;
            if ($result == "Passed") {
                $passed++;
            } else {
                $failed++;
            }
        }
        $count = $passed + $failed;
        // echo "Tests total=$count, passed=$passed, failed=$failed" . PHP_EOL;

        $xml = '<?xml version="1.0" encoding="UTF-8" ?>' . PHP_EOL;

        $xml .= '<testsuite';
        $xml .= " errors=\"0\"";
        $xml .= " failures=\"$failed\"";
        $xml .= " name=\"$suitename\"";
        $xml .= " tests=\"$count\"";
        $xml .= '>' . PHP_EOL;

        foreach ($results as $row) {
            $result = $row['Result'];
            $name = $row['Test Name'];
            $notes = isset($row['Notes']) ? $row['Notes'] : "";
            $result = $row['Result'];
            if (preg_match('/.*\/application\/(.*)/', $file, $matches)) {
                $file = $matches[1];
            }

            $str = sprintf("%-40s %-40s %6s %s", $file . ":" . $line, $name, $result, $notes);

            $xml .= '<testcase';
            $xml .= " name=\"$name\"";
            $xml .= " file=\"$file\"";
            $xml .= " line=\"$line\"";
            $xml .= '>';
            // . PHP_EOL;
            // $xml .= $str . PHP_EOL;

            if ($result != "Passed") {
                $xml .= "<failure>$str</failure>";
            }

            $xml .= '</testcase>' . PHP_EOL;
        }

        $xml .= '</testsuite>' . PHP_EOL;

        file_put_contents($filename, $xml);
    }
}
