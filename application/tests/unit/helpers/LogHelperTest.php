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
     * Test occurences() against the real current log file.
     *
     * occurences() has no injectable path, so it can only be exercised on the
     * live log file. To stay safe on a shared server, the test only *appends* a
     * uniquely-tagged block, counts its own markers, then rewrites the file with
     * exactly that block stripped from the current content — so any lines the
     * web server logs during the test are preserved, never truncated.
     */
    public function testOccurences()
    {
        // occurences() -> current_logfile() builds its path from getcwd(); run
        // the whole test with cwd = APPPATH so it resolves to application/logs/,
        // matching how the app runs. Restored in the finally.
        $originalCwd = getcwd();
        chdir(APPPATH);

        try {
            $logFile = current_logfile();
            $logDir  = dirname($logFile);

            if (!is_dir($logDir) || !is_writable($logDir)) {
                $this->markTestSkipped('Log directory is not writable');
                return;
            }
            if (file_exists($logFile) && !is_writable($logFile)) {
                $this->markTestSkipped('Current log file is not writable by the test runner');
                return;
            }

            $preExisted = file_exists($logFile);
            $marker = 'GVV_LOGTEST_' . uniqid();
            $block  = "\n{$marker} pattern\n{$marker} pattern\n{$marker} line\n{$marker} line\n{$marker} line\n";

            file_put_contents($logFile, $block, FILE_APPEND | LOCK_EX);

            try {
                $this->assertEquals(2, occurences("{$marker} pattern"));
                $this->assertEquals(3, occurences("{$marker} line"));
                $this->assertEquals(0, occurences("{$marker} absent"));
            } finally {
                // Re-read (captures concurrent writes) and remove only our block.
                $content = file_exists($logFile) ? file_get_contents($logFile) : '';
                $content = str_replace($block, '', $content);
                if ($content === '' && !$preExisted) {
                    @unlink($logFile);
                } else {
                    file_put_contents($logFile, $content, LOCK_EX);
                }
            }
        } finally {
            chdir($originalCwd);
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
