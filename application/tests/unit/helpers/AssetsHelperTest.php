<?php

use PHPUnit\Framework\TestCase;

/**
 * Test class for assets helper functions
 *
 * Tests URL generation functions for CSS, JS, images and other assets
 */
class AssetsHelperTest extends TestCase
{
    private $originalGetInstance;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock base_url function
        if (!function_exists('base_url')) {
            function base_url() {
                return 'http://localhost/gvv/';
            }
        }

        // Mock site_url function
        if (!function_exists('site_url')) {
            function site_url($uri = '') {
                return 'http://localhost/gvv/index.php/' . $uri;
            }
        }

        // Create a mock CI instance
        $mockCI = new stdClass();
        $mockCI->config = new stdClass();
        $mockCI->config->item = function($key) {
            if ($key === 'theme') {
                return 'default';
            }
            return null;
        };

        // Mock get_instance to return our mock CI
        $GLOBALS['mockCIInstance'] = $mockCI;

        // Load the helper
        require_once APPPATH . 'helpers/assets_helper.php';
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['mockCIInstance']);
        parent::tearDown();
    }

    /**
     * Test theme function returns correct URL
     */
    public function testTheme()
    {
        // We need to test with a real or mocked CI instance
        // For now, just verify the function exists
        $this->assertTrue(function_exists('theme'));
    }

    /**
     * Test css_url generates correct CSS URL
     */
    public function testCssUrl()
    {
        $url = css_url('style');

        $this->assertIsString($url);
        $this->assertStringContainsString('css', $url);
        $this->assertStringContainsString('style.css', $url);
        $this->assertStringEndsWith('.css', $url);
    }

    /**
     * Test js_url generates correct JavaScript URL
     */
    public function testJsUrl()
    {
        $url = js_url('script');

        $this->assertIsString($url);
        $this->assertStringContainsString('javascript', $url);
        $this->assertStringContainsString('script.js', $url);
        $this->assertStringEndsWith('.js', $url);
    }

    /**
     * Test image_dir returns correct directory path
     */
    public function testImageDir()
    {
        $dir = image_dir();

        $this->assertIsString($dir);
        $this->assertEquals('assets/images/', $dir);
    }

    /**
     * Test img_url generates correct image URL
     */
    public function testImgUrl()
    {
        $url = img_url('logo.png');

        $this->assertIsString($url);
        $this->assertStringContainsString('images', $url);
        $this->assertStringContainsString('logo.png', $url);
    }

    /**
     * Test asset_url generates correct asset URL
     */
    public function testAssetUrl()
    {
        $url = asset_url('file.txt');

        $this->assertIsString($url);
        $this->assertStringContainsString('assets', $url);
        $this->assertStringContainsString('file.txt', $url);
    }

    /**
     * Test controller_url with relative path
     */
    public function testControllerUrlRelative()
    {
        $url = controller_url('welcome/index');

        $this->assertIsString($url);
        $this->assertStringContainsString('welcome/index', $url);
    }

    /**
     * Test controller_url with absolute HTTP URL
     */
    public function testControllerUrlAbsoluteHttp()
    {
        $url = controller_url('http://example.com/page');

        $this->assertEquals('http://example.com/page', $url);
    }

    /**
     * Test controller_url with absolute HTTPS URL
     */
    public function testControllerUrlAbsoluteHttps()
    {
        $url = controller_url('https://example.com/page');

        $this->assertEquals('https://example.com/page', $url);
    }

    /**
     * Test css_url with different filenames
     */
    public function testCssUrlVariousNames()
    {
        $urls = [
            css_url('bootstrap'),
            css_url('custom'),
            css_url('theme-dark')
        ];

        foreach ($urls as $url) {
            $this->assertIsString($url);
            $this->assertStringEndsWith('.css', $url);
        }

        $this->assertStringContainsString('bootstrap.css', $urls[0]);
        $this->assertStringContainsString('custom.css', $urls[1]);
        $this->assertStringContainsString('theme-dark.css', $urls[2]);
    }

    /**
     * Test js_url with different filenames
     */
    public function testJsUrlVariousNames()
    {
        $urls = [
            js_url('jquery'),
            js_url('app'),
            js_url('utils-helper')
        ];

        foreach ($urls as $url) {
            $this->assertIsString($url);
            $this->assertStringEndsWith('.js', $url);
        }

        $this->assertStringContainsString('jquery.js', $urls[0]);
        $this->assertStringContainsString('app.js', $urls[1]);
        $this->assertStringContainsString('utils-helper.js', $urls[2]);
    }

    /**
     * Test img_url with different image types
     */
    public function testImgUrlDifferentTypes()
    {
        $images = [
            'photo.jpg',
            'logo.png',
            'icon.svg',
            'banner.gif'
        ];

        foreach ($images as $image) {
            $url = img_url($image);
            $this->assertStringContainsString($image, $url);
        }
    }

    /**
     * Test controller_url with empty string
     */
    public function testControllerUrlEmpty()
    {
        $url = controller_url('');

        $this->assertIsString($url);
    }

    /**
     * Test all functions exist
     */
    public function testAllFunctionsExist()
    {
        $this->assertTrue(function_exists('theme'));
        $this->assertTrue(function_exists('css_url'));
        $this->assertTrue(function_exists('js_url'));
        $this->assertTrue(function_exists('image_dir'));
        $this->assertTrue(function_exists('img_url'));
        $this->assertTrue(function_exists('asset_url'));
        $this->assertTrue(function_exists('controller_url'));
    }
}
