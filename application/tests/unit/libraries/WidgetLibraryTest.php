<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests for Widget library
 * 
 * Tests Widget library functionality including attribute management and display
 */
class WidgetLibraryTest extends TestCase
{
    private $widget;

    public function setUp(): void
    {
        // Load Widget library
        require_once APPPATH . 'libraries/Widget.php';
        
        // Create widget instance for testing
        $this->widget = new Widget(array('color' => 'orange'));
    }

    /**
     * Test Widget library instantiation
     */
    public function testWidgetLibraryLoaded()
    {
        $this->assertNotNull($this->widget, "Widget library should be loaded");
        $this->assertInstanceOf('Widget', $this->widget, "Should be instance of Widget class");
    }

    /**
     * Test Widget default attribute setting
     */
    public function testWidgetDefaultAttribute()
    {
        $color = $this->widget->get('color');
        $this->assertEquals('orange', $color, "Widget default color attribute should be 'orange'");
    }

    /**
     * Test Widget attribute modification
     */
    public function testWidgetAttributeModification()
    {
        // Modify color attribute
        $this->widget->set('color', 'red');
        $color = $this->widget->get('color');
        
        $this->assertEquals('red', $color, "Widget color attribute should be 'red' after modification");
    }

    /**
     * Test Widget display method (should not throw error)
     */
    public function testWidgetDisplay()
    {
        // Test that display method exists and can be called
        ob_start();
        $this->widget->display();
        $output = ob_get_clean();
        
        // Should not throw exception and should produce some output (string)
        $this->assertIsString($output, "Widget display should return string output");
    }

    /**
     * Test Widget image method returns empty string by default
     */
    public function testWidgetImageMethod()
    {
        $image = $this->widget->image();
        $this->assertEquals("", $image, "Widget default image should be empty string");
    }

    /**
     * Test Widget with multiple attributes
     */
    public function testWidgetMultipleAttributes()
    {
        $widget = new Widget(array(
            'color' => 'blue',
            'size' => 'large',
            'type' => 'button'
        ));
        
        $this->assertEquals('blue', $widget->get('color'), "Color should be set correctly");
        $this->assertEquals('large', $widget->get('size'), "Size should be set correctly");
        $this->assertEquals('button', $widget->get('type'), "Type should be set correctly");
    }

    /**
     * Test Widget attribute getter for non-existent attribute
     */
    public function testWidgetNonExistentAttribute()
    {
        $value = $this->widget->get('nonexistent');
        
        // Should return null or empty for non-existent attributes
        $this->assertTrue($value === null || $value === '', "Non-existent attribute should return null or empty");
    }

    /**
     * Test Widget attribute setter and getter workflow
     */
    public function testWidgetSetGetWorkflow()
    {
        // Set new attribute
        $this->widget->set('width', '100px');
        $width = $this->widget->get('width');
        
        $this->assertEquals('100px', $width, "Newly set width attribute should be retrievable");
        
        // Modify existing attribute
        $this->widget->set('width', '200px');
        $new_width = $this->widget->get('width');
        
        $this->assertEquals('200px', $new_width, "Modified width attribute should be updated");
    }
}
