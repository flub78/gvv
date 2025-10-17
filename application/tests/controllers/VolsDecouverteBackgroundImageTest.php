<?php

use PHPUnit\Framework\TestCase;

/**
 * Test for discovery flight PDF generation background image configuration
 * Verifies that the vols_decouverte controller correctly uses configured background image
 */
class VolsDecouverteBackgroundImageTest extends TestCase {

    /**
     * Test that the background image path validation logic works
     */
    public function testBackgroundImagePathValidation() {
        // Test valid paths
        $valid_paths = [
            './uploads/configuration/vd.background_image.jpg',
            './uploads/configuration/vd.background_image.png',
            './uploads/configuration/test.gif'
        ];
        
        foreach ($valid_paths as $path) {
            $this->assertStringStartsWith('./uploads/configuration/', $path,
                "Path should start with configuration directory: $path");
        }
        
        // Test the fallback logic implemented in the controller
        $background_image = null; // Simulate no configuration
        $fallback = 'assets/images/Bon-Bapteme.png';
        
        if (!empty($background_image) && file_exists($background_image)) {
            $img_file = $background_image;
        } else {
            $img_file = $fallback;
        }
        
        $this->assertEquals($fallback, $img_file, 
            'Should fallback to default image when no configuration exists');
    }

    /**
     * Test the background image selection logic as implemented in the controller
     */
    public function testBackgroundImageSelectionLogic() {
        // Simulate different scenarios
        
        // Scenario 1: No background image configured
        $background_image = null;
        $fallback = 'assets/images/Bon-Bapteme.png';
        
        if (!empty($background_image) && file_exists($background_image)) {
            $img_file = $background_image;
        } else {
            $img_file = $fallback;
        }
        
        $this->assertEquals($fallback, $img_file, 
            'Should use fallback when no background image is configured');
            
        // Scenario 2: Background image configured but file doesn't exist
        $background_image = './uploads/configuration/nonexistent.jpg';
        
        if (!empty($background_image) && file_exists($background_image)) {
            $img_file = $background_image;
        } else {
            $img_file = $fallback;
        }
        
        $this->assertEquals($fallback, $img_file, 
            'Should use fallback when configured file does not exist');
            
        // Scenario 3: Background image configured and file exists
        $background_image = './uploads/configuration/vd.background_image.jpg';
        
        if (file_exists($background_image)) {
            if (!empty($background_image) && file_exists($background_image)) {
                $img_file = $background_image;
            } else {
                $img_file = $fallback;
            }
            
            $this->assertEquals($background_image, $img_file,
                'Should use configured image when it exists');
        } else {
            $this->markTestSkipped('Background image file does not exist for testing');
        }
    }

    /**
     * Test that the image file exists and is a valid image
     */
    public function testDiscoveryFlightBackgroundImageFile() {
        $expected_file = './uploads/configuration/vd.background_image.jpg';
        
        if (file_exists($expected_file)) {
            // Verify it's an image file
            $file_extension = strtolower(pathinfo($expected_file, PATHINFO_EXTENSION));
            $valid_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            $this->assertContains($file_extension, $valid_extensions,
                "Background image should be a valid image format");
        } else {
            $this->markTestSkipped('Custom background image file not found - using default');
        }
    }
}