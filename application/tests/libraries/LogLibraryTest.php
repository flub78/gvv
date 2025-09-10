<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests for Log library additional services
 * 
 * Tests logging functionality and system information retrieval
 */
class LogLibraryTest extends TestCase
{
    private $CI;

    public function setUp(): void
    {
        // Get CodeIgniter instance (log library is always loaded)
        $this->CI =& get_instance();
        
        // Ensure log directory exists and is writable
        $log_dir = "application/logs";
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
    }

    /**
     * Test system user information functions
     */
    public function testSystemUserInformation()
    {
        $user = get_current_user();
        $this->assertNotEmpty($user, "Current user should not be empty (user=$user)");
        
        $uid = getmyuid();
        $this->assertGreaterThanOrEqual(0, $uid, "User ID should be >= 0 (uid=$uid)");
        
        $gid = getmygid();
        $this->assertGreaterThanOrEqual(0, $gid, "Group ID should be >= 0 (gid=$gid)");
        
        $pid = getmypid();
        $this->assertNotEquals(0, $pid, "Process ID should not be 0 (pid=$pid)");
        
        $fileowner = fileowner(".");
        $this->assertGreaterThanOrEqual(0, $fileowner, "File owner should be >= 0 (fileowner=$fileowner)");
    }

    /**
     * Test log directory permissions
     */
    public function testLogDirectoryPermissions()
    {
        $log_dir = "application/logs";
        
        $this->assertTrue(is_dir($log_dir), "$log_dir should be a directory");
        $this->assertTrue(is_writable($log_dir), "$log_dir should be writable");
    }

    /**
     * Test gvv_info logging function
     */
    public function testGvvInfoLogging()
    {
        if (function_exists('gvv_info')) {
            // Get initial log size
            $initial_size = $this->CI->log->log_file_size();
            
            // Log an info message
            $result = gvv_info('info', "PHPUnit test info message");
            $this->assertEquals("", $result, "gvv_info should return empty string");
            
            // Check that log size increased
            $size_after_info = $this->CI->log->log_file_size();
            $this->assertGreaterThan($initial_size, $size_after_info, 
                "Log file size should increase after gvv_info (initial: $initial_size, after: $size_after_info)");
        } else {
            $this->markTestSkipped('gvv_info function not available');
        }
    }

    /**
     * Test gvv_debug logging function
     */
    public function testGvvDebugLogging()
    {
        if (function_exists('gvv_debug') && function_exists('gvv_info')) {
            // Log an info message first
            gvv_info('info', "PHPUnit test info before debug");
            $size_after_info = $this->CI->log->log_file_size();
            
            // Log a debug message
            $result = gvv_debug('debug', "PHPUnit test debug message");
            $this->assertEquals("", $result, "gvv_debug should return empty string");
            
            // Check that log size increased
            $size_after_debug = $this->CI->log->log_file_size();
            $this->assertGreaterThan($size_after_info, $size_after_debug,
                "Log file size should increase after gvv_debug (info: $size_after_info, debug: $size_after_debug)");
        } else {
            $this->markTestSkipped('gvv_debug or gvv_info functions not available');
        }
    }

    /**
     * Test gvv_error logging function with line counting
     */
    public function testGvvErrorLogging()
    {
        if (function_exists('gvv_error')) {
            // Count existing error lines
            $initial_count = $this->CI->log->count_lines("gvv_error", "ERROR");
            
            // Log an error message
            $result = gvv_error('error', "PHPUnit test error message");
            $this->assertEquals("", $result, "gvv_error should return empty string");
            
            // Check that error count increased
            $new_count = $this->CI->log->count_lines("gvv_error", "ERROR");
            $this->assertEquals($initial_count + 1, $new_count,
                "Error count should increase by 1 (initial: $initial_count, new: $new_count)");
        } else {
            $this->markTestSkipped('gvv_error function not available');
        }
    }

    /**
     * Test log_file method returns valid path
     */
    public function testLogFileMethod()
    {
        $logfile = $this->CI->log->log_file();
        
        $this->assertNotEmpty($logfile, "Log file path should not be empty");
        $this->assertIsString($logfile, "Log file path should be a string (logfile=$logfile)");
        
        // Check if file exists (it should after previous logging operations)
        if (file_exists($logfile)) {
            $this->assertTrue(is_readable($logfile), "Log file should be readable");
        }
    }

    /**
     * Test log_file_size method returns valid size
     */
    public function testLogFileSizeMethod()
    {
        $size = $this->CI->log->log_file_size();
        
        $this->assertIsNumeric($size, "Log file size should be numeric");
        $this->assertGreaterThanOrEqual(0, $size, "Log file size should be >= 0");
    }

    /**
     * Test count_lines method functionality
     */
    public function testCountLinesMethod()
    {
        // Test counting lines with different patterns
        $error_count = $this->CI->log->count_lines("gvv_error", "ERROR");
        $this->assertIsNumeric($error_count, "Error line count should be numeric");
        $this->assertGreaterThanOrEqual(0, $error_count, "Error line count should be >= 0");
        
        // Test with a pattern that should not exist
        $nonexistent_count = $this->CI->log->count_lines("nonexistent", "NONEXISTENT");
        $this->assertEquals(0, $nonexistent_count, "Non-existent pattern count should be 0");
    }
}
