<?php

use PHPUnit\Framework\TestCase;

/**
 * Integration test for assets helper functions with real CodeIgniter integration
 * 
 * This demonstrates how to load and test helpers in integration tests
 */
class AssetsHelperIntegrationTest extends TestCase
{
    private $CI;
    
    public function setUp(): void
    {
        // Get CodeIgniter instance
        $this->CI =& get_instance();
        
        // Load assets helper using CodeIgniter's loader
        $this->CI->load->helper('assets');
        
        // Verify helper functions are available
        if (!function_exists('theme')) {
            $this->markTestSkipped('Assets helper not loaded properly');
        }
    }

    /**
     * Test theme() function with integration environment
     */
    public function testThemeFunctionIntegration()
    {
        $theme = theme();
        $expected = base_url() . "themes/binary-news";
        
        $this->assertEquals($expected, $theme, "Theme function should return correct URL");
        $this->assertStringContainsString('binary-news', $theme, "Theme should contain theme name");
        $this->assertStringStartsWith('http://', $theme, "Theme URL should be absolute");
    }

    /**
     * Test css_url() function with integration environment
     */
    public function testCssUrlFunctionIntegration()
    {
        $css = css_url("menu");
        $theme = theme();
        $expected = $theme . "/css/menu.css";
        
        $this->assertEquals($expected, $css, "CSS URL should be correctly generated");
        $this->assertStringEndsWith('.css', $css, "CSS URL should end with .css");
        $this->assertStringContainsString('/css/', $css, "CSS URL should contain css directory");
    }

    /**
     * Test js_url() function with integration environment
     */
    public function testJsUrlFunctionIntegration()
    {
        $javascript_url = js_url("menu");
        $expected = base_url() . "assets/javascript/menu.js";
        
        $this->assertEquals($expected, $javascript_url, "JavaScript URL should be correctly generated");
        $this->assertStringEndsWith('.js', $javascript_url, "JS URL should end with .js");
        $this->assertStringContainsString('assets/javascript/', $javascript_url, "JS URL should contain assets/javascript path");
    }

    /**
     * Test multiple asset functions together
     */
    public function testMultipleAssetFunctions()
    {
        $theme = theme();
        $css = css_url("style");
        $js = js_url("script");
        
        // Test that all functions return different but related URLs
        $this->assertNotEquals($theme, $css, "Theme and CSS URLs should be different");
        $this->assertNotEquals($css, $js, "CSS and JS URLs should be different");
        
        // Test that CSS URL starts with theme URL
        $this->assertStringStartsWith($theme, $css, "CSS URL should start with theme URL");
        
        // Test that JS URL starts with base URL but not theme URL
        $base = base_url();
        $this->assertStringStartsWith($base, $js, "JS URL should start with base URL");
        $this->assertStringNotContainsString('themes/', $js, "JS URL should not contain themes directory");
    }

    /**
     * Test helper loading mechanism itself
     */
    public function testHelperLoadingMechanism()
    {
        // Test that we can load another helper
        $this->CI->load->helper('url');
        
        // URL helper should now be available (it might already be loaded)
        $this->assertTrue(function_exists('base_url'), "base_url function should be available");
        $this->assertTrue(function_exists('site_url'), "site_url function should be available");
        
        // Test base_url function works
        $base = base_url('test/path');
        $this->assertStringContainsString('test/path', $base, "base_url should append path correctly");
    }

    /**
     * Test config integration with assets helper
     */
    public function testConfigIntegration()
    {
        // Test that config_item function works
        $theme_config = config_item('theme');
        $this->assertEquals('binary-news', $theme_config, "Theme config should be accessible");
        
        $base_url_config = config_item('base_url');
        $this->assertEquals('http://localhost/gvv2/', $base_url_config, "Base URL config should be accessible");
        
        // Test that theme() function uses config
        $theme = theme();
        $this->assertStringContainsString($theme_config, $theme, "Theme function should use config value");
    }

    /**
     * Test that assets helper works with different file names
     */
    public function testDifferentAssetNames()
    {
        $test_cases = [
            'main' => ['main.css', 'main.js'],
            'admin' => ['admin.css', 'admin.js'],
            'style-responsive' => ['style-responsive.css', 'style-responsive.js']
        ];

        foreach ($test_cases as $name => $expected) {
            $css = css_url($name);
            $js = js_url($name);

            $this->assertStringContainsString($expected[0], $css, "CSS URL should contain correct filename for $name");
            $this->assertStringContainsString($expected[1], $js, "JS URL should contain correct filename for $name");
        }
    }

    /**
     * Test image_dir() function
     */
    public function testImageDir()
    {
        $image_dir = image_dir();

        $this->assertEquals('assets/images/', $image_dir, "Image directory should be assets/images/");
        $this->assertStringEndsWith('/', $image_dir, "Image directory should end with slash");
        $this->assertStringStartsWith('assets/', $image_dir, "Image directory should start with assets/");
    }

    /**
     * Test img_url() function
     */
    public function testImgUrl()
    {
        $img = img_url('logo.png');
        $theme = theme();
        $expected = $theme . '/images/logo.png';

        $this->assertEquals($expected, $img, "Image URL should be correctly generated");
        $this->assertStringContainsString('/images/', $img, "Image URL should contain /images/ path");
        $this->assertStringStartsWith($theme, $img, "Image URL should start with theme URL");
        $this->assertStringEndsWith('logo.png', $img, "Image URL should end with filename");
    }

    /**
     * Test img_url() with different image types
     */
    public function testImgUrlDifferentTypes()
    {
        $test_cases = ['icon.svg', 'banner.jpg', 'avatar.gif', 'diagram.png'];

        foreach ($test_cases as $filename) {
            $img = img_url($filename);
            $this->assertStringEndsWith($filename, $img, "Image URL should end with $filename");
            $this->assertStringContainsString('/images/', $img, "Image URL should contain /images/ for $filename");
        }
    }

    /**
     * Test asset_url() function
     */
    public function testAssetUrl()
    {
        $asset = asset_url('custom.css');
        $theme = theme();
        $expected = $theme . '/assets/custom.css';

        $this->assertEquals($expected, $asset, "Asset URL should be correctly generated");
        $this->assertStringContainsString('/assets/', $asset, "Asset URL should contain /assets/ path");
        $this->assertStringStartsWith($theme, $asset, "Asset URL should start with theme URL");
        $this->assertStringEndsWith('custom.css', $asset, "Asset URL should end with filename");
    }

    /**
     * Test controller_url() function
     */
    public function testControllerUrl()
    {
        $controller = controller_url('members/list');
        $site = site_url();

        $this->assertStringStartsWith($site, $controller, "Controller URL should start with site URL");
        $this->assertStringContainsString('members/list', $controller, "Controller URL should contain controller path");
    }

    /**
     * Test controller_url() with different paths
     */
    public function testControllerUrlDifferentPaths()
    {
        $test_cases = ['vols', 'machines/edit/1', 'users/profile', 'reports/monthly'];

        foreach ($test_cases as $path) {
            $url = controller_url($path);
            $this->assertStringContainsString($path, $url, "Controller URL should contain path: $path");
            $this->assertStringStartsWith('http', $url, "Controller URL should be absolute for $path");
        }
    }
}
