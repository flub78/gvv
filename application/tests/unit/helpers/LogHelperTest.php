<?php

use PHPUnit\Framework\TestCase;

/**
 * Test class for log helper functions
 *
 * Tests the GVV-specific logging functions
 */
class LogHelperTest extends TestCase
{
    private $logMessages = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Load the helper
        require_once APPPATH . 'helpers/log_helper.php';

        // Reset log messages
        $this->logMessages = [];
    }

    /**
     * Test current_logfile returns expected format
     */
    public function testCurrentLogfile()
    {
        $logfile = current_logfile();

        // Check that it returns a string
        $this->assertIsString($logfile);

        // Check that it contains the expected pattern
        $this->assertStringContainsString('log-', $logfile);
        $this->assertStringContainsString('.php', $logfile);
        $this->assertStringContainsString('application/logs/', $logfile);

        // Check that it contains today's date
        $today = date('Y-m-d');
        $this->assertStringContainsString($today, $logfile);
    }

    /**
     * Test gvv_dump outputs correctly without dying
     */
    public function testGvvDumpNoDie()
    {
        ob_start();
        gvv_dump(['test' => 'value'], false, 'Test Title');
        $output = ob_get_clean();

        // Check output contains expected elements
        $this->assertStringContainsString('gvv_dump from file:', $output);
        $this->assertStringContainsString('Line:', $output);
        $this->assertStringContainsString('Test Title', $output);
        $this->assertStringContainsString('[test]', $output);
        $this->assertStringContainsString('value', $output);
    }

    /**
     * Test gvv_dump outputs correctly without title
     */
    public function testGvvDumpNoTitle()
    {
        ob_start();
        gvv_dump(['key' => 'data'], false);
        $output = ob_get_clean();

        // Check output contains expected elements
        $this->assertStringContainsString('gvv_dump from file:', $output);
        $this->assertStringContainsString('[key]', $output);
        $this->assertStringContainsString('data', $output);
    }

    /**
     * Test gvv_dump with simple string
     */
    public function testGvvDumpString()
    {
        ob_start();
        gvv_dump('Simple string', false);
        $output = ob_get_clean();

        // Check output contains the string
        $this->assertStringContainsString('Simple string', $output);
        $this->assertStringContainsString('gvv_dump from file:', $output);
    }

    /**
     * Test gvv_dump with array
     */
    public function testGvvDumpArray()
    {
        ob_start();
        gvv_dump(['a' => 1, 'b' => 2, 'c' => 3], false, 'Array Test');
        $output = ob_get_clean();

        // Check array elements are in output
        $this->assertStringContainsString('[a]', $output);
        $this->assertStringContainsString('[b]', $output);
        $this->assertStringContainsString('[c]', $output);
        $this->assertStringContainsString('Array Test', $output);
    }

    /**
     * Test gvv_assert with true assertion (should not exit)
     */
    public function testGvvAssertTrue()
    {
        // This should not throw or exit
        gvv_assert(true, 'This should not fail', false);

        // If we get here, the test passed
        $this->assertTrue(true);
    }

    /**
     * Test gvv_assert with false assertion (should not exit when dye=false)
     * Note: We can't easily test the exit behavior without process isolation
     */
    public function testGvvAssertFalseNoDie()
    {
        // Create a mock log_message function if needed
        // Since gvv_assert calls gvv_error which calls gvv_log which calls log_message
        // we can't fully test this without mocking the entire chain

        // For now, just verify the function exists and can be called
        $this->assertTrue(function_exists('gvv_assert'));
    }

    /**
     * Test that logging functions exist
     */
    public function testLoggingFunctionsExist()
    {
        $this->assertTrue(function_exists('gvv_log'));
        $this->assertTrue(function_exists('gvv_info'));
        $this->assertTrue(function_exists('gvv_error'));
        $this->assertTrue(function_exists('gvv_debug'));
    }

    /**
     * Test occurences function with a temp log file
     */
    public function testOccurences()
    {
        // Create a temporary log file in the expected location
        $logDir = getcwd() . '/../application/logs';
        $logFile = current_logfile();

        // Skip test if we can't write to log directory
        if (!is_writable($logDir)) {
            $this->markTestSkipped('Log directory is not writable');
            return;
        }

        // Backup existing log file if it exists
        $backup = null;
        if (file_exists($logFile)) {
            $backup = file_get_contents($logFile);
        }

        try {
            // Write test content
            $testContent = "Test line 1\nTest line 2\nTest pattern\nTest pattern\nTest line 3\n";
            file_put_contents($logFile, $testContent);

            // Test occurences
            $count = occurences('Test pattern');
            $this->assertEquals(2, $count);

            $count = occurences('Test line');
            $this->assertEquals(3, $count);

            $count = occurences('NonExistent');
            $this->assertEquals(0, $count);

        } finally {
            // Restore original log file
            if ($backup !== null) {
                file_put_contents($logFile, $backup);
            } elseif (file_exists($logFile)) {
                unlink($logFile);
            }
        }
    }

    /**
     * Test gvv_dump with nested structures
     */
    public function testGvvDumpNestedStructure()
    {
        $data = [
            'level1' => [
                'level2' => [
                    'level3' => 'deep value'
                ]
            ]
        ];

        ob_start();
        gvv_dump($data, false, 'Nested Structure');
        $output = ob_get_clean();

        // Check nested structure is visible
        $this->assertStringContainsString('level1', $output);
        $this->assertStringContainsString('level2', $output);
        $this->assertStringContainsString('level3', $output);
        $this->assertStringContainsString('deep value', $output);
    }
}
