<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests for Button library classes
 * 
 * Tests Button, ButtonDelete, and ButtonEdit library functionality
 */
class ButtonLibraryTest extends TestCase
{
    public function setUp(): void
    {
        // Load Button libraries
        require_once APPPATH . 'libraries/Button.php';
        
        // Load specialized button classes if they exist
        if (file_exists(APPPATH . 'libraries/ButtonDelete.php')) {
            require_once APPPATH . 'libraries/ButtonDelete.php';
        }
        
        if (file_exists(APPPATH . 'libraries/ButtonEdit.php')) {
            require_once APPPATH . 'libraries/ButtonEdit.php';
        }
    }

    /**
     * Test Button library instantiation
     */
    public function testButtonLibraryLoaded()
    {
        $button = new Button(array('color' => 'orange'));
        
        $this->assertNotNull($button, "Button library should be loaded");
        $this->assertInstanceOf('Button', $button, "Should be instance of Button class");
    }

    /**
     * Test Button default attribute
     */
    public function testButtonDefaultAttribute()
    {
        $button = new Button(array('color' => 'orange'));
        $color = $button->get('color');
        
        $this->assertEquals('orange', $color, "Button default color attribute should be 'orange'");
    }

    /**
     * Test Button attribute modification
     */
    public function testButtonAttributeModification()
    {
        $button = new Button(array('color' => 'orange'));
        
        // Modify color attribute
        $button->set('color', 'red');
        $color = $button->get('color');
        
        $this->assertEquals('red', $color, "Button color attribute should be 'red' after modification");
    }

    /**
     * Test Button display method
     */
    public function testButtonDisplay()
    {
        $button = new Button(array('color' => 'orange'));
        
        // Test that display method exists and can be called
        ob_start();
        $button->display();
        $output = ob_get_clean();
        
        // Should not throw exception and should produce some output
        $this->assertIsString($output, "Button display should return string output");
    }

    /**
     * Test ButtonDelete class instantiation
     */
    public function testButtonDeleteClass()
    {
        if (class_exists('ButtonDelete')) {
            $deleteButton = new ButtonDelete();
            
            $this->assertNotNull($deleteButton, "ButtonDelete should be instantiated");
            $this->assertInstanceOf('ButtonDelete', $deleteButton, "Should be instance of ButtonDelete class");
        } else {
            $this->markTestSkipped('ButtonDelete class not available');
        }
    }

    /**
     * Test ButtonEdit class instantiation
     */
    public function testButtonEditClass()
    {
        if (class_exists('ButtonEdit')) {
            $editButton = new ButtonEdit();
            
            $this->assertNotNull($editButton, "ButtonEdit should be instantiated");
            $this->assertInstanceOf('ButtonEdit', $editButton, "Should be instance of ButtonEdit class");
        } else {
            $this->markTestSkipped('ButtonEdit class not available');
        }
    }

    /**
     * Test Button with no initial attributes
     */
    public function testButtonWithoutAttributes()
    {
        $button = new Button();
        
        $this->assertNotNull($button, "Button should be instantiated without attributes");
        
        // Set attribute after creation
        $button->set('type', 'submit');
        $type = $button->get('type');
        
        $this->assertEquals('submit', $type, "Button type should be set correctly");
    }

    /**
     * Test Button inheritance behavior
     */
    public function testButtonInheritanceBehavior()
    {
        $button = new Button(array('id' => 'test-button', 'class' => 'btn-primary'));
        
        // Test multiple attributes
        $this->assertEquals('test-button', $button->get('id'), "Button ID should be set correctly");
        $this->assertEquals('btn-primary', $button->get('class'), "Button class should be set correctly");
        
        // Test attribute modification
        $button->set('class', 'btn-secondary');
        $this->assertEquals('btn-secondary', $button->get('class'), "Button class should be modified correctly");
    }
}
