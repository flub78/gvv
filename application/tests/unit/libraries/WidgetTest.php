<?php

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Widget library
 * 
 * Tests the base Widget class functionality including
 * attribute management and constructor initialization
 * 
 * @package tests
 */
class WidgetTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        // Load the Widget library
        require_once APPPATH . 'libraries/Widget.php';
    }

    /**
     * Test widget instantiation with no attributes
     */
    public function testInstantiationEmpty()
    {
        $widget = new Widget();
        
        $this->assertInstanceOf(Widget::class, $widget, "Should create Widget instance");
    }

    /**
     * Test widget instantiation with attributes
     */
    public function testInstantiationWithAttributes()
    {
        $attrs = array(
            'id' => 'test-widget',
            'class' => 'widget-class',
            'name' => 'Test Widget'
        );
        
        $widget = new Widget($attrs);
        
        $this->assertInstanceOf(Widget::class, $widget, "Should create Widget instance");
        $this->assertEquals('test-widget', $widget->get('id'), "Should set id attribute");
        $this->assertEquals('widget-class', $widget->get('class'), "Should set class attribute");
        $this->assertEquals('Test Widget', $widget->get('name'), "Should set name attribute");
    }

    /**
     * Test set() method
     */
    public function testSetMethod()
    {
        $widget = new Widget();
        
        $widget->set('color', 'blue');
        $widget->set('size', 'large');
        $widget->set('count', 42);
        
        $this->assertEquals('blue', $widget->get('color'), "Should set color attribute");
        $this->assertEquals('large', $widget->get('size'), "Should set size attribute");
        $this->assertEquals(42, $widget->get('count'), "Should set numeric attribute");
    }

    /**
     * Test get() method with existing attribute
     */
    public function testGetMethodExisting()
    {
        $widget = new Widget(array('test' => 'value'));
        
        $result = $widget->get('test');
        
        $this->assertEquals('value', $result, "Should return attribute value");
    }

    /**
     * Test get() method with non-existent attribute returns null
     */
    public function testGetMethodNonExistent()
    {
        $widget = new Widget();
        
        $result = $widget->get('nonexistent');
        
        $this->assertNull($result, "Should return null for non-existent attribute");
    }

    /**
     * Test overwriting existing attribute
     */
    public function testOverwriteAttribute()
    {
        $widget = new Widget(array('name' => 'Original'));
        
        $this->assertEquals('Original', $widget->get('name'), "Should have original value");
        
        $widget->set('name', 'Updated');
        
        $this->assertEquals('Updated', $widget->get('name'), "Should have updated value");
    }

    /**
     * Test setting multiple attributes
     */
    public function testMultipleAttributes()
    {
        $widget = new Widget();
        
        $widget->set('attr1', 'value1');
        $widget->set('attr2', 'value2');
        $widget->set('attr3', 'value3');
        
        $this->assertEquals('value1', $widget->get('attr1'));
        $this->assertEquals('value2', $widget->get('attr2'));
        $this->assertEquals('value3', $widget->get('attr3'));
    }

    /**
     * Test setting attributes with various data types
     */
    public function testVariousDataTypes()
    {
        $widget = new Widget();
        
        $widget->set('string', 'text');
        $widget->set('integer', 123);
        $widget->set('float', 45.67);
        $widget->set('boolean', true);
        $widget->set('array', array(1, 2, 3));
        $widget->set('null', null);
        
        $this->assertIsString($widget->get('string'));
        $this->assertIsInt($widget->get('integer'));
        $this->assertIsFloat($widget->get('float'));
        $this->assertIsBool($widget->get('boolean'));
        $this->assertIsArray($widget->get('array'));
        $this->assertNull($widget->get('null'));
    }

    /**
     * Test image() method returns empty string by default
     */
    public function testImageMethodDefault()
    {
        $widget = new Widget();
        
        $result = $widget->image();
        
        $this->assertEquals('', $result, "Default image() should return empty string");
        $this->assertIsString($result, "image() should return a string");
    }

    /**
     * Test constructor with empty array
     */
    public function testConstructorWithEmptyArray()
    {
        $widget = new Widget(array());
        
        $this->assertInstanceOf(Widget::class, $widget);
        $this->assertNull($widget->get('anything'), "Should have no attributes set");
    }

    /**
     * Test constructor with nested array values
     */
    public function testConstructorWithNestedArrays()
    {
        $attrs = array(
            'config' => array(
                'enabled' => true,
                'settings' => array('key' => 'value')
            )
        );
        
        $widget = new Widget($attrs);
        
        $config = $widget->get('config');
        $this->assertIsArray($config);
        $this->assertTrue($config['enabled']);
        $this->assertArrayHasKey('settings', $config);
    }

    /**
     * Test attribute isolation between instances
     */
    public function testAttributeIsolation()
    {
        $widget1 = new Widget(array('name' => 'Widget1'));
        $widget2 = new Widget(array('name' => 'Widget2'));
        
        $this->assertEquals('Widget1', $widget1->get('name'));
        $this->assertEquals('Widget2', $widget2->get('name'));
        
        $widget1->set('name', 'Modified1');
        
        $this->assertEquals('Modified1', $widget1->get('name'));
        $this->assertEquals('Widget2', $widget2->get('name'), 
            "Modifying widget1 should not affect widget2");
    }

    /**
     * Test setting attribute with special characters
     */
    public function testAttributesWithSpecialCharacters()
    {
        $widget = new Widget();
        
        $widget->set('special', 'Value with "quotes" and \'apostrophes\'');
        $widget->set('unicode', 'Français €');
        $widget->set('html', '<div>HTML content</div>');
        
        $this->assertStringContainsString('quotes', $widget->get('special'));
        $this->assertStringContainsString('Français', $widget->get('unicode'));
        $this->assertStringContainsString('<div>', $widget->get('html'));
    }
}
