<?php

use PHPUnit\Framework\TestCase;

/**
 * Test suite for Backend anonymization functionality
 */
class BackendAnonymizationTest extends TestCase
{
    protected $CI;

    public function setUp(): void
    {
        parent::setUp();
        $this->CI = &get_instance();
        $this->CI->load->database();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Test that anonymize_all method exists in Backend controller
     */
    public function testAnonymizeAllMethodExists()
    {
        // Test by checking the file content
        $backend_file = file_get_contents(APPPATH . 'controllers/backend.php');

        $this->assertStringContainsString(
            'function anonymize_all()',
            $backend_file,
            'Backend controller should have anonymize_all method'
        );
    }

    /**
     * Test that anonymize_all_data method exists in Admin controller
     */
    public function testAnonymizeAllDataMethodExists()
    {
        // Test by checking the file content
        $admin_file = file_get_contents(APPPATH . 'controllers/admin.php');

        $this->assertStringContainsString(
            'function anonymize_all_data()',
            $admin_file,
            'Admin controller should have anonymize_all_data method'
        );
    }

    /**
     * Test that ENVIRONMENT constant is checked
     */
    public function testEnvironmentCheck()
    {
        // This test verifies that the methods check for development environment
        // In production, they should show an error

        // Note: We can't easily test the actual error display without running the full method
        // but we can verify the constant is used in the code

        $backend_file = file_get_contents(APPPATH . 'controllers/backend.php');
        $this->assertStringContainsString(
            "ENVIRONMENT !== 'development'",
            $backend_file,
            'Backend::anonymize_all should check ENVIRONMENT'
        );

        $admin_file = file_get_contents(APPPATH . 'controllers/admin.php');
        $this->assertStringContainsString(
            "ENVIRONMENT !== 'development'",
            $admin_file,
            'Admin::anonymize_all_data should check ENVIRONMENT'
        );
    }
}
