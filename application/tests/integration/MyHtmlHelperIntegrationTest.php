<?php

use PHPUnit\Framework\TestCase;

/**
 * Integration test for assets helper functions with real CodeIgniter integration
 * 
 * This demonstrates how to load and test helpers in integration tests
 */
class MyHtmlHelperIntegrationTest extends TestCase {
    private $CI;

    public function setUp(): void {
        // Get CodeIgniter instance
        $this->CI = &get_instance();

        // In setUp(), instead of $this->CI->load->helper('html'):
require_once APPPATH . '../system/helpers/html_helper.php';  // Core CI html helper
require_once APPPATH . 'helpers/MY_html_helper.php';  // Your extensions

        // Load assets helper using CodeIgniter's loader
        $this->CI->load->helper('assets');
        // $this->CI->load->helper('html');

        // Verify helper functions are available
        if (!function_exists('theme')) {
            $this->markTestSkipped('Assets helper not loaded properly');
        }
    }

    /**
     * Test p() function with integration environment
     */
    public function testP() {
        $this->assertEquals("<p >coucou</p>", p("coucou"), "p() should wrap text in <p> tags");
    }


}
