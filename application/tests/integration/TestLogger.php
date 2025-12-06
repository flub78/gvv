<?php
/**
 * Test Logger for Integration Tests
 *
 * Provides a simple logging facility for integration tests to reduce
 * stdout verbosity while preserving debug information.
 */
class TestLogger {
    private static $logFile = null;
    private static $testName = null;

    /**
     * Initialize the logger for a test run
     * Creates a timestamped log file and outputs its location to stdout
     */
    public static function init($testName = 'integration_tests') {
        self::$testName = $testName;
        $timestamp = date('Y-m-d_His');
        $logDir = APPPATH . 'tests/logs';

        // Create logs directory if it doesn't exist
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        self::$logFile = $logDir . '/' . $testName . '_' . $timestamp . '.log';

        // Output log file location to stdout (shown once at test start)
        echo "\n=== Integration Test Log ===\n";
        echo "Log file: " . self::$logFile . "\n";
        echo "============================\n\n";

        // Write header to log file
        self::write("=== Integration Test Log ===");
        self::write("Test: " . $testName);
        self::write("Started: " . date('Y-m-d H:i:s'));
        self::write("=============================\n");
    }

    /**
     * Write a message to the log file
     *
     * @param string $message Message to log
     * @param string $level Log level (INFO, DEBUG, WARNING, ERROR)
     */
    public static function log($message, $level = 'INFO') {
        if (self::$logFile === null) {
            self::init();
        }

        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = "[{$timestamp}] [{$level}] {$message}";

        self::write($formattedMessage);
    }

    /**
     * Log informational message
     */
    public static function info($message) {
        self::log($message, 'INFO');
    }

    /**
     * Log debug message
     */
    public static function debug($message) {
        self::log($message, 'DEBUG');
    }

    /**
     * Log warning message
     */
    public static function warning($message) {
        self::log($message, 'WARNING');
    }

    /**
     * Log error message
     */
    public static function error($message) {
        self::log($message, 'ERROR');
    }

    /**
     * Log a section header (for test organization)
     */
    public static function section($title) {
        self::write("\n" . str_repeat('=', 60));
        self::write($title);
        self::write(str_repeat('=', 60));
    }

    /**
     * Log test results in a structured format
     */
    public static function testResult($testName, $passed, $details = '') {
        $status = $passed ? 'PASSED' : 'FAILED';
        self::log("Test: {$testName} - {$status}");
        if ($details) {
            self::log("  Details: {$details}");
        }
    }

    /**
     * Write raw content to log file (without timestamp/level)
     */
    private static function write($content) {
        if (self::$logFile) {
            file_put_contents(self::$logFile, $content . "\n", FILE_APPEND);
        }
    }

    /**
     * Get the current log file path
     */
    public static function getLogFile() {
        return self::$logFile;
    }

    /**
     * Cleanup old log files (keep only last N files)
     */
    public static function cleanup($keepCount = 10) {
        $logDir = APPPATH . 'tests/logs';
        if (!is_dir($logDir)) {
            return;
        }

        $files = glob($logDir . '/*.log');
        if (count($files) <= $keepCount) {
            return;
        }

        // Sort by modification time
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        // Delete old files
        for ($i = $keepCount; $i < count($files); $i++) {
            @unlink($files[$i]);
        }
    }
}
