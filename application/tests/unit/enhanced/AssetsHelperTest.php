<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests for assets helper functions
 * 
 * Tests CSS, JavaScript, image, and theme URL generation functions
 */
class AssetsHelperTest extends TestCase
{
    public function setUp(): void
    {
        // Load assets helper
        if (!function_exists('theme')) {
            require_once APPPATH . 'helpers/assets_helper.php';
        }
    }

    /**
     * Test theme() function returns correct default theme URL
     */
    public function testThemeFunction()
    {
        $theme = theme();
        $expected = base_url() . "themes/binary-news";
        
        $this->assertEquals($expected, $theme, "Default theme URL should match expected value");
    }

    /**
     * Test css_url() function generates correct CSS file path
     */
    public function testCssUrlFunction()
    {
        $css = css_url("menu");
        $theme = theme();
        $expected = $theme . "/css/menu.css";
        
        $this->assertEquals($expected, $css, "CSS URL should be generated correctly within theme");
    }

    /**
     * Test js_url() function generates correct JavaScript file path
     */
    public function testJsUrlFunction()
    {
        $javascript_url = js_url("menu");
        $expected = base_url() . "assets/javascript/menu.js";
        
        $this->assertEquals($expected, $javascript_url, "JavaScript URL should point to assets directory");
    }

    /**
     * Test img_url() function works (basic functionality test)
     */
    public function testImgUrlFunction()
    {
        // Test that function exists and returns something
        if (function_exists('img_url')) {
            $img_url = img_url("menu");
            $this->assertIsString($img_url, "img_url should return a string");
        } else {
            $this->markTestSkipped('img_url function not available');
        }
    }

    /**
     * Test asset_url() function works (basic functionality test)
     */
    public function testAssetUrlFunction()
    {
        // Test that function exists and returns something
        if (function_exists('asset_url')) {
            $asset_url = asset_url("menu");
            $this->assertIsString($asset_url, "asset_url should return a string");
        } else {
            $this->markTestSkipped('asset_url function not available');
        }
    }

    /**
     * Test controller_url() function works (basic functionality test)
     */
    public function testControllerUrlFunction()
    {
        // Test that function exists and returns something
        if (function_exists('controller_url')) {
            $controller_url = controller_url("menu");
            $this->assertIsString($controller_url, "controller_url should return a string");
        } else {
            $this->markTestSkipped('controller_url function not available');
        }
    }

    /**
     * Test image_dir() function works (basic functionality test)
     */
    public function testImageDirFunction()
    {
        // Test that function exists and returns something
        if (function_exists('image_dir')) {
            $image = image_dir();
            $this->assertIsString($image, "image_dir should return a string");
        } else {
            $this->markTestSkipped('image_dir function not available');
        }
    }
}
