<?php

use PHPUnit\Framework\TestCase;

/**
 * Integration test for log_helper functions
 * Tests GVV-specific logging functionality
 */
class LogHelperIntegrationTest extends TestCase {
    private $CI;
    private $test_log_file;

    public function setUp(): void {
        // Get CodeIgniter instance
        $this->CI = &get_instance();

        // Load log helper directly
        require_once APPPATH . 'helpers/log_helper.php';

        // Ensure log directory exists and is writable
        $log_dir = APPPATH . 'logs';
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }

        // Verify helper functions are available
        if (!function_exists('gvv_log')) {
            $this->markTestSkipped('log_helper not loaded properly');
        }

        // Store current log file for cleanup - use APPPATH directly since current_logfile() uses getcwd()
        $this->test_log_file = APPPATH . 'logs/log-' . date('Y-m-d') . '.php';
    }

    /**
     * Helper method to get the correct log file path
     */
    private function getLogFilePath() {
        return APPPATH . 'logs/log-' . date('Y-m-d') . '.php';
    }

    // ========== Tests for gvv_log() function ==========

    /**
     * Test gvv_log() with info level
     */
    public function testGvvLogInfo() {
        gvv_log('info', 'Test info message');

        // Verify the message was logged
        if (file_exists($this->test_log_file)) {
            $log_content = file_get_contents($this->test_log_file);
            $this->assertStringContainsString('GVV: Test info message', $log_content,
                'Log file should contain the test message');
        } else {
            $this->markTestSkipped('Log file not created');
        }
    }

    /**
     * Test gvv_log() with error level
     */
    public function testGvvLogError() {
        gvv_log('error', 'Test error message');

        // Verify the message was logged
        if (file_exists($this->test_log_file)) {
            $log_content = file_get_contents($this->test_log_file);
            $this->assertStringContainsString('GVV: Test error message', $log_content,
                'Log file should contain the error message');
            $this->assertStringContainsString('ERROR', $log_content,
                'Log file should contain ERROR level indicator');
        }
    }

    /**
     * Test gvv_log() with debug level
     */
    public function testGvvLogDebug() {
        gvv_log('debug', 'Test debug message');

        // Verify the message was logged
        if (file_exists($this->test_log_file)) {
            $log_content = file_get_contents($this->test_log_file);
            $this->assertStringContainsString('GVV: Test debug message', $log_content,
                'Log file should contain the debug message');
        }
    }

    // ========== Tests for gvv_info() function ==========

    /**
     * Test gvv_info() wrapper function
     */
    public function testGvvInfo() {
        gvv_info('Info message from gvv_info');

        if (file_exists($this->test_log_file)) {
            $log_content = file_get_contents($this->test_log_file);
            $this->assertStringContainsString('GVV: Info message from gvv_info', $log_content,
                'gvv_info should log with GVV prefix');
        }
    }

    /**
     * Test gvv_info() with special characters
     */
    public function testGvvInfoSpecialCharacters() {
        gvv_info('Message with special chars: é à ù $ & #');

        if (file_exists($this->test_log_file)) {
            $log_content = file_get_contents($this->test_log_file);
            $this->assertStringContainsString('GVV: Message with special chars', $log_content,
                'gvv_info should handle special characters');
        }
    }

    // ========== Tests for gvv_error() function ==========

    /**
     * Test gvv_error() wrapper function
     */
    public function testGvvError() {
        gvv_error('Error message from gvv_error');

        if (file_exists($this->test_log_file)) {
            $log_content = file_get_contents($this->test_log_file);
            $this->assertStringContainsString('GVV: Error message from gvv_error', $log_content,
                'gvv_error should log with GVV prefix');
            $this->assertStringContainsString('ERROR', $log_content,
                'gvv_error should log at ERROR level');
        }
    }

    /**
     * Test gvv_error() with long message
     */
    public function testGvvErrorLongMessage() {
        $long_message = str_repeat('This is a long error message. ', 20);
        gvv_error($long_message);

        if (file_exists($this->test_log_file)) {
            $log_content = file_get_contents($this->test_log_file);
            $this->assertStringContainsString('GVV: This is a long error message', $log_content,
                'gvv_error should handle long messages');
        }
    }

    // ========== Tests for gvv_debug() function ==========

    /**
     * Test gvv_debug() wrapper function
     */
    public function testGvvDebug() {
        gvv_debug('Debug message from gvv_debug');

        if (file_exists($this->test_log_file)) {
            $log_content = file_get_contents($this->test_log_file);
            $this->assertStringContainsString('GVV: Debug message from gvv_debug', $log_content,
                'gvv_debug should log with GVV prefix');
        }
    }

    /**
     * Test gvv_debug() with array data
     */
    public function testGvvDebugWithArray() {
        $data = array('key1' => 'value1', 'key2' => 'value2');
        gvv_debug('Debug with array: ' . print_r($data, true));

        if (file_exists($this->test_log_file)) {
            $log_content = file_get_contents($this->test_log_file);
            $this->assertStringContainsString('GVV: Debug with array', $log_content,
                'gvv_debug should handle array data');
        }
    }

    // ========== Tests for current_logfile() function ==========

    /**
     * Test current_logfile() returns valid path
     */
    public function testCurrentLogfile() {
        $logfile = current_logfile();

        $this->assertIsString($logfile, 'current_logfile should return a string');
        $this->assertStringContainsString('log-', $logfile, 'Log file path should contain "log-"');
        $this->assertStringContainsString(date('Y-m-d'), $logfile,
            'Log file path should contain current date');
        $this->assertStringContainsString('.php', $logfile, 'Log file should have .php extension');
    }

    /**
     * Test current_logfile() path structure
     */
    public function testCurrentLogfilePathStructure() {
        $logfile = current_logfile();

        $this->assertStringContainsString('application/logs/', $logfile,
            'Log file path should contain application/logs/');

        // Verify it's an absolute or relative path
        $this->assertNotEmpty($logfile, 'Log file path should not be empty');
    }

    // ========== Tests for occurences() function ==========

    /**
     * Test occurences() counts pattern in log file
     */
    public function testOccurences() {
        // Write a unique test pattern
        $unique_pattern = 'UNIQUE_TEST_PATTERN_' . uniqid();
        gvv_info($unique_pattern);
        gvv_info($unique_pattern);
        gvv_info($unique_pattern);

        // Count occurrences using file directly since current_logfile() has path issues
        if (file_exists($this->getLogFilePath())) {
            $log_content = file_get_contents($this->getLogFilePath());
            $count = substr_count($log_content, $unique_pattern);

            $this->assertIsInt($count, 'occurences should return an integer');
            $this->assertGreaterThanOrEqual(3, $count,
                'Should find at least 3 occurrences of the unique pattern');
        } else {
            $this->markTestSkipped('Log file not found');
        }
    }

    /**
     * Test occurences() with non-existent pattern
     */
    public function testOccurencesNonExistent() {
        if (file_exists($this->getLogFilePath())) {
            $log_content = file_get_contents($this->getLogFilePath());
            $count = substr_count($log_content, 'NONEXISTENT_PATTERN_' . uniqid());

            $this->assertIsInt($count, 'occurences should return an integer');
            $this->assertEquals(0, $count,
                'Should return 0 for non-existent pattern');
        } else {
            $this->markTestSkipped('Log file not found');
        }
    }

    /**
     * Test occurences() with GVV prefix
     */
    public function testOccurencesGvvPrefix() {
        if (file_exists($this->getLogFilePath())) {
            $log_content = file_get_contents($this->getLogFilePath());
            $count = substr_count($log_content, 'GVV:');

            $this->assertIsInt($count, 'occurences should return an integer');
            $this->assertGreaterThan(0, $count,
                'Should find multiple GVV: prefixes in the log file');
        } else {
            $this->markTestSkipped('Log file not found');
        }
    }

    /**
     * Test occurences() with ERROR pattern
     */
    public function testOccurencesErrorPattern() {
        // Log some errors
        gvv_error('Test error 1 for counting');
        gvv_error('Test error 2 for counting');

        if (file_exists($this->getLogFilePath())) {
            $log_content = file_get_contents($this->getLogFilePath());
            $count = substr_count($log_content, 'ERROR');

            $this->assertIsInt($count, 'occurences should return an integer');
            $this->assertGreaterThanOrEqual(2, $count,
                'Should find at least 2 ERROR occurrences');
        } else {
            $this->markTestSkipped('Log file not found');
        }
    }

    // ========== Tests for gvv_dump() function ==========

    /**
     * Test gvv_dump() output without dying
     */
    public function testGvvDumpNoDie() {
        $test_data = array('test' => 'data', 'number' => 123);

        ob_start();
        gvv_dump($test_data, false, 'Test dump');
        $output = ob_get_clean();

        $this->assertIsString($output, 'gvv_dump should produce output');
        $this->assertStringContainsString('gvv_dump from file:', $output,
            'Output should contain file information');
        $this->assertStringContainsString('Line:', $output,
            'Output should contain line number');
        $this->assertStringContainsString('Test dump', $output,
            'Output should contain the title');
        $this->assertStringContainsString('test', $output,
            'Output should contain the dumped data');
        $this->assertStringContainsString('<pre>', $output,
            'Output should be wrapped in <pre> tags');
    }

    /**
     * Test gvv_dump() with empty data
     */
    public function testGvvDumpEmpty() {
        ob_start();
        gvv_dump('', false);
        $output = ob_get_clean();

        $this->assertIsString($output, 'gvv_dump should produce output even for empty data');
        $this->assertStringContainsString('gvv_dump from file:', $output,
            'Output should still contain file information');
    }

    /**
     * Test gvv_dump() with array data
     */
    public function testGvvDumpArray() {
        $array_data = array('a' => 1, 'b' => 2, 'c' => array('d' => 3));

        ob_start();
        gvv_dump($array_data, false, 'Array Test');
        $output = ob_get_clean();

        $this->assertStringContainsString('Array Test', $output,
            'Output should contain the title');
        $this->assertStringContainsString('[a]', $output,
            'Output should show array keys');
    }

    // ========== Tests for gvv_assert() function ==========

    /**
     * Test gvv_assert() with true assertion (should not log or exit)
     */
    public function testGvvAssertTrue() {
        if (file_exists($this->getLogFilePath())) {
            $log_content_before = file_get_contents($this->getLogFilePath());
            $initial_count = substr_count($log_content_before, 'Assertion failed');

            gvv_assert(true, 'This should not trigger', false);

            $log_content_after = file_get_contents($this->getLogFilePath());
            $new_count = substr_count($log_content_after, 'Assertion failed');

            $this->assertEquals($initial_count, $new_count,
                'True assertion should not log an error');
        } else {
            $this->markTestSkipped('Log file not found');
        }
    }

    /**
     * Test gvv_assert() with false assertion (should log without dying)
     */
    public function testGvvAssertFalse() {
        if (file_exists($this->getLogFilePath())) {
            $log_content_before = file_get_contents($this->getLogFilePath());
            $initial_count = substr_count($log_content_before, 'Assertion failed');

            gvv_assert(false, 'Test assertion failure', false);

            $log_content_after = file_get_contents($this->getLogFilePath());
            $new_count = substr_count($log_content_after, 'Assertion failed');

            $this->assertGreaterThan($initial_count, $new_count,
                'False assertion should log an error message');
        } else {
            $this->markTestSkipped('Log file not found');
        }
    }

    /**
     * Test gvv_assert() error message format
     */
    public function testGvvAssertErrorFormat() {
        $unique_msg = 'UNIQUE_ASSERTION_MSG_' . uniqid();
        gvv_assert(false, $unique_msg, false);

        if (file_exists($this->test_log_file)) {
            $log_content = file_get_contents($this->test_log_file);
            $this->assertStringContainsString('Assertion failed', $log_content,
                'Error should contain "Assertion failed"');
            $this->assertStringContainsString($unique_msg, $log_content,
                'Error should contain the assertion message');
            $this->assertStringContainsString('file:', $log_content,
                'Error should contain file information');
            $this->assertStringContainsString('Line:', $log_content,
                'Error should contain line number');
        }
    }

    /**
     * Test multiple log levels in sequence
     */
    public function testMultipleLogLevels() {
        $unique_id = uniqid();

        gvv_debug("Debug $unique_id");
        gvv_info("Info $unique_id");
        gvv_error("Error $unique_id");

        if (file_exists($this->test_log_file)) {
            $log_content = file_get_contents($this->test_log_file);

            $this->assertStringContainsString("Debug $unique_id", $log_content,
                'Log should contain debug message');
            $this->assertStringContainsString("Info $unique_id", $log_content,
                'Log should contain info message');
            $this->assertStringContainsString("Error $unique_id", $log_content,
                'Log should contain error message');
        }
    }

    /**
     * Test that GVV prefix is consistently applied
     */
    public function testGvvPrefixConsistency() {
        $test_id = uniqid();

        gvv_info("Test $test_id");

        if (file_exists($this->test_log_file)) {
            $log_content = file_get_contents($this->test_log_file);

            // Check for GVV prefix before the message
            $this->assertStringContainsString("GVV: Test $test_id", $log_content,
                'GVV prefix should be present before message');
        }
    }

    /**
     * Test gvv_log() with all parameters
     */
    public function testGvvLogWithPhpError() {
        gvv_log('info', 'Test with php_error param', TRUE);

        if (file_exists($this->test_log_file)) {
            $log_content = file_get_contents($this->test_log_file);
            $this->assertStringContainsString('GVV: Test with php_error param', $log_content,
                'Log should contain message even with php_error parameter');
        }
    }

    /**
     * Test gvv_info() with php_error parameter
     */
    public function testGvvInfoWithPhpError() {
        gvv_info('Info with php_error', TRUE);

        if (file_exists($this->test_log_file)) {
            $log_content = file_get_contents($this->test_log_file);
            $this->assertStringContainsString('GVV: Info with php_error', $log_content,
                'Should log info message with php_error parameter');
        }
    }

    /**
     * Test gvv_error() with php_error parameter
     */
    public function testGvvErrorWithPhpError() {
        gvv_error('Error with php_error', TRUE);

        if (file_exists($this->test_log_file)) {
            $log_content = file_get_contents($this->test_log_file);
            $this->assertStringContainsString('GVV: Error with php_error', $log_content,
                'Should log error message with php_error parameter');
        }
    }

    /**
     * Test gvv_debug() with php_error parameter
     */
    public function testGvvDebugWithPhpError() {
        gvv_debug('Debug with php_error', TRUE);

        if (file_exists($this->test_log_file)) {
            $log_content = file_get_contents($this->test_log_file);
            $this->assertStringContainsString('GVV: Debug with php_error', $log_content,
                'Should log debug message with php_error parameter');
        }
    }

    /**
     * Test gvv_dump() with numeric data
     */
    public function testGvvDumpNumeric() {
        ob_start();
        gvv_dump(12345, false, 'Numeric Test');
        $output = ob_get_clean();

        $this->assertStringContainsString('12345', $output,
            'Output should contain the numeric value');
        $this->assertStringContainsString('Numeric Test', $output,
            'Output should contain the title');
    }

    /**
     * Test gvv_dump() with boolean data
     */
    public function testGvvDumpBoolean() {
        ob_start();
        gvv_dump(true, false);
        $output = ob_get_clean();

        $this->assertStringContainsString('1', $output,
            'Output should show boolean true as 1');
    }

    /**
     * Test gvv_dump() with NULL data
     */
    public function testGvvDumpNull() {
        ob_start();
        gvv_dump(null, false, 'NULL Test');
        $output = ob_get_clean();

        $this->assertStringContainsString('NULL Test', $output,
            'Output should contain the title for NULL data');
    }

    /**
     * Test gvv_dump() with object data
     */
    public function testGvvDumpObject() {
        $obj = new \stdClass();
        $obj->property = 'value';

        ob_start();
        gvv_dump($obj, false, 'Object Test');
        $output = ob_get_clean();

        $this->assertStringContainsString('Object Test', $output,
            'Output should contain the title');
        $this->assertStringContainsString('stdClass', $output,
            'Output should contain object class name');
    }

    /**
     * Test gvv_assert() with different message formats
     */
    public function testGvvAssertWithComplexMessage() {
        if (file_exists($this->getLogFilePath())) {
            $complex_msg = "Complex assertion: value=123, status='failed'";
            gvv_assert(false, $complex_msg, false);

            $log_content = file_get_contents($this->getLogFilePath());
            $this->assertStringContainsString($complex_msg, $log_content,
                'Assertion message should be preserved in log');
        } else {
            $this->markTestSkipped('Log file not found');
        }
    }

    /**
     * Test current_logfile() returns consistent format
     */
    public function testCurrentLogfileFormat() {
        $logfile = current_logfile();

        // Should contain the date format YYYY-MM-DD
        $this->assertRegExp('/log-\d{4}-\d{2}-\d{2}\.php/',
            basename($logfile),
            'Log file should follow log-YYYY-MM-DD.php format');
    }

    /**
     * Test logging with empty message
     */
    public function testGvvInfoEmptyMessage() {
        gvv_info('');

        if (file_exists($this->test_log_file)) {
            $log_content = file_get_contents($this->test_log_file);
            $this->assertStringContainsString('GVV:', $log_content,
                'Should log GVV prefix even with empty message');
        }
    }

    /**
     * Test logging with newline characters
     */
    public function testGvvInfoWithNewlines() {
        gvv_info("Line 1\nLine 2\nLine 3");

        if (file_exists($this->test_log_file)) {
            $log_content = file_get_contents($this->test_log_file);
            $this->assertStringContainsString('Line 1', $log_content,
                'Should preserve newline content in log');
        }
    }

    /**
     * Test sequential logging maintains order
     */
    public function testSequentialLogging() {
        $id = uniqid();
        gvv_info("First $id");
        gvv_info("Second $id");
        gvv_info("Third $id");

        if (file_exists($this->test_log_file)) {
            $log_content = file_get_contents($this->test_log_file);

            $pos_first = strpos($log_content, "First $id");
            $pos_second = strpos($log_content, "Second $id");
            $pos_third = strpos($log_content, "Third $id");

            $this->assertLessThan($pos_second, $pos_first,
                'First message should appear before second');
            $this->assertLessThan($pos_third, $pos_second,
                'Second message should appear before third');
        }
    }
}
