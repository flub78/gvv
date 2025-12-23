<?php

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Button library
 * 
 * Tests the Button class which extends Widget and generates
 * submit buttons with controller/action/parameter functionality
 * 
 * @package tests
 */
class ButtonTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        // Load the required libraries
        require_once APPPATH . 'libraries/Widget.php';
        require_once APPPATH . 'libraries/Button.php';
    }

    /**
     * Test button instantiation with defaults
     */
    public function testInstantiationWithDefaults()
    {
        $button = new Button();
        
        $this->assertInstanceOf(Button::class, $button);
        $this->assertEquals('Button', $button->get('label'), "Default label should be 'Button'");
        $this->assertEquals('', $button->get('controller'), "Default controller should be empty");
        $this->assertEquals('', $button->get('action'), "Default action should be empty");
        $this->assertEquals('', $button->get('param'), "Default param should be empty");
        $this->assertEquals('', $button->get('image'), "Default image should be empty");
        $this->assertFalse($button->get('confirm'), "Default confirm should be false");
        $this->assertEquals('', $button->get('confirmMsg'), "Default confirmMsg should be empty");
    }

    /**
     * Test button instantiation with custom attributes
     */
    public function testInstantiationWithAttributes()
    {
        $attrs = array(
            'label' => 'Save',
            'controller' => 'users',
            'action' => 'save',
            'param' => '123'
        );
        
        $button = new Button($attrs);
        
        $this->assertEquals('Save', $button->get('label'));
        $this->assertEquals('users', $button->get('controller'));
        $this->assertEquals('save', $button->get('action'));
        $this->assertEquals('123', $button->get('param'));
    }

    /**
     * Test button with confirmation dialog
     */
    public function testButtonWithConfirmation()
    {
        $attrs = array(
            'label' => 'Delete',
            'confirm' => true,
            'confirmMsg' => 'Are you sure you want to delete this item'
        );
        
        $button = new Button($attrs);
        
        $this->assertTrue($button->get('confirm'));
        $this->assertEquals('Are you sure you want to delete this item', $button->get('confirmMsg'));
    }

    /**
     * Test button with image icon
     */
    public function testButtonWithImage()
    {
        $attrs = array(
            'label' => 'Edit',
            'image' => 'assets/images/edit.png'
        );
        
        $button = new Button($attrs);
        
        $this->assertEquals('assets/images/edit.png', $button->get('image'));
    }

    /**
     * Test setting attributes after construction
     */
    public function testSetAttributesAfterConstruction()
    {
        $button = new Button();
        
        $button->set('label', 'Submit');
        $button->set('controller', 'forms');
        $button->set('action', 'process');
        
        $this->assertEquals('Submit', $button->get('label'));
        $this->assertEquals('forms', $button->get('controller'));
        $this->assertEquals('process', $button->get('action'));
    }

    /**
     * Test button inherits from Widget
     */
    public function testButtonInheritsFromWidget()
    {
        $button = new Button();
        
        $this->assertInstanceOf(Widget::class, $button, "Button should be instance of Widget");
    }

    /**
     * Test button with all attributes set
     */
    public function testButtonWithAllAttributes()
    {
        $attrs = array(
            'label' => 'Process',
            'controller' => 'data',
            'action' => 'process',
            'param' => 'batch-123',
            'image' => 'icons/process.png',
            'confirm' => true,
            'confirmMsg' => 'Start processing'
        );
        
        $button = new Button($attrs);
        
        $this->assertEquals('Process', $button->get('label'));
        $this->assertEquals('data', $button->get('controller'));
        $this->assertEquals('process', $button->get('action'));
        $this->assertEquals('batch-123', $button->get('param'));
        $this->assertEquals('icons/process.png', $button->get('image'));
        $this->assertTrue($button->get('confirm'));
        $this->assertEquals('Start processing', $button->get('confirmMsg'));
    }

    /**
     * Test overriding default attributes
     */
    public function testOverrideDefaults()
    {
        $button = new Button();
        
        // Defaults
        $this->assertEquals('Button', $button->get('label'));
        $this->assertFalse($button->get('confirm'));
        
        // Override
        $button->set('label', 'Custom Label');
        $button->set('confirm', true);
        
        $this->assertEquals('Custom Label', $button->get('label'));
        $this->assertTrue($button->get('confirm'));
    }

    /**
     * Test button with numeric parameter
     */
    public function testButtonWithNumericParameter()
    {
        $attrs = array(
            'label' => 'View',
            'controller' => 'items',
            'action' => 'view',
            'param' => 42
        );
        
        $button = new Button($attrs);
        
        $this->assertEquals(42, $button->get('param'));
    }

    /**
     * Test button with complex parameter (array)
     */
    public function testButtonWithComplexParameter()
    {
        $attrs = array(
            'label' => 'Execute',
            'param' => array('id' => 1, 'type' => 'batch')
        );
        
        $button = new Button($attrs);
        
        $param = $button->get('param');
        $this->assertIsArray($param);
        $this->assertEquals(1, $param['id']);
        $this->assertEquals('batch', $param['type']);
    }

    /**
     * Test multiple button instances are independent
     */
    public function testMultipleButtonInstances()
    {
        $button1 = new Button(array('label' => 'Save'));
        $button2 = new Button(array('label' => 'Cancel'));
        
        $this->assertEquals('Save', $button1->get('label'));
        $this->assertEquals('Cancel', $button2->get('label'));
        
        $button1->set('label', 'Update');
        
        $this->assertEquals('Update', $button1->get('label'));
        $this->assertEquals('Cancel', $button2->get('label'), "Button2 should not be affected");
    }

    /**
     * Test button with empty controller and action
     */
    public function testButtonWithEmptyControllerAndAction()
    {
        $button = new Button(array('label' => 'Test'));
        
        $this->assertEquals('', $button->get('controller'));
        $this->assertEquals('', $button->get('action'));
        $this->assertIsString($button->get('controller'));
        $this->assertIsString($button->get('action'));
    }
}
