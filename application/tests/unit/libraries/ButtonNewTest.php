<?php

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ButtonNew class
 *
 * Tests ButtonNew which extends Button with predefined defaults for creating new items
 *
 * @package tests
 */
class ButtonNewTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        // Load the required libraries
        require_once APPPATH . 'libraries/Widget.php';
        require_once APPPATH . 'libraries/Button.php';
        require_once APPPATH . 'libraries/ButtonNew.php';
    }

    /**
     * Test ButtonNew can be instantiated
     */
    public function testButtonNewInstantiation()
    {
        $button = new ButtonNew();

        $this->assertInstanceOf(ButtonNew::class, $button);
        $this->assertInstanceOf(Button::class, $button, "ButtonNew should extend Button");
        $this->assertInstanceOf(Widget::class, $button, "ButtonNew should inherit from Widget");
    }

    /**
     * Test ButtonNew has correct default label
     */
    public function testButtonNewDefaultLabel()
    {
        $button = new ButtonNew();

        // ButtonNew sets label using lang->line("gvv_button_new")
        // In test environment, we can verify it's set
        $label = $button->get('label');
        $this->assertIsString($label, "Label should be a string");
        $this->assertNotEmpty($label, "Label should not be empty");
    }

    /**
     * Test ButtonNew has correct default action
     */
    public function testButtonNewDefaultAction()
    {
        $button = new ButtonNew();

        $this->assertEquals('create', $button->get('action'),
            "ButtonNew default action should be 'create'");
    }

    /**
     * Test ButtonNew has default image
     */
    public function testButtonNewDefaultImage()
    {
        $button = new ButtonNew();

        $image = $button->get('image');
        $this->assertIsString($image, "Image should be a string");
        $this->assertStringContainsString('add.png', $image,
            "ButtonNew should use add.png icon");
    }

    /**
     * Test ButtonNew with custom attributes
     */
    public function testButtonNewWithCustomAttributes()
    {
        $attrs = array(
            'controller' => 'users',
            'param' => 'new_user'
        );

        $button = new ButtonNew($attrs);

        $this->assertEquals('create', $button->get('action'),
            "Action should still be default 'create'");
        $this->assertEquals('users', $button->get('controller'));
        $this->assertEquals('new_user', $button->get('param'));
    }

    /**
     * Test ButtonNew action is fixed to 'create'
     */
    public function testButtonNewActionIsFixed()
    {
        // Try to override action in constructor
        $button = new ButtonNew(array('action' => 'add'));

        // Constructor should set action to 'create' after parent constructor
        $this->assertEquals('create', $button->get('action'),
            "Action should be 'create' even if overridden in constructor");
    }

    /**
     * Test ButtonNew can have controller and param set
     */
    public function testButtonNewWithControllerAndParam()
    {
        $attrs = array(
            'controller' => 'products',
            'param' => ''
        );

        $button = new ButtonNew($attrs);

        $this->assertEquals('products', $button->get('controller'));
        $this->assertEquals('', $button->get('param'));
        $this->assertEquals('create', $button->get('action'));
    }

    /**
     * Test ButtonNew inherits set() method from Widget
     */
    public function testButtonNewInheritsSetMethod()
    {
        $button = new ButtonNew();

        $button->set('controller', 'items');
        $button->set('param', '123');

        $this->assertEquals('items', $button->get('controller'));
        $this->assertEquals('123', $button->get('param'));
    }

    /**
     * Test ButtonNew does not require confirmation by default
     */
    public function testButtonNewNoConfirmationByDefault()
    {
        $button = new ButtonNew();

        $confirm = $button->get('confirm');
        $this->assertFalse($confirm,
            "ButtonNew should not require confirmation by default");
    }

    /**
     * Test ButtonNew with empty attributes array
     */
    public function testButtonNewWithEmptyAttributes()
    {
        $button = new ButtonNew(array());

        $this->assertEquals('create', $button->get('action'));
        $this->assertIsString($button->get('label'));
        $this->assertStringContainsString('add.png', $button->get('image'));
    }

    /**
     * Test multiple ButtonNew instances are independent
     */
    public function testMultipleButtonNewInstancesAreIndependent()
    {
        $button1 = new ButtonNew(array('controller' => 'users'));
        $button2 = new ButtonNew(array('controller' => 'products'));

        $this->assertEquals('users', $button1->get('controller'));
        $this->assertEquals('products', $button2->get('controller'));

        $button1->set('param', '1');
        $button2->set('param', '2');

        $this->assertEquals('1', $button1->get('param'));
        $this->assertEquals('2', $button2->get('param'));
    }

    /**
     * Test ButtonNew defaults are consistent
     */
    public function testButtonNewDefaultsAreConsistent()
    {
        $button1 = new ButtonNew();
        $button2 = new ButtonNew();

        $this->assertEquals($button1->get('action'), $button2->get('action'));
        $this->assertEquals($button1->get('label'), $button2->get('label'));
        $this->assertEquals($button1->get('image'), $button2->get('image'));
    }

    /**
     * Test ButtonNew image path uses theme()
     */
    public function testButtonNewImageUsesTheme()
    {
        $button = new ButtonNew();

        $image = $button->get('image');
        // The image path should use theme() function which returns theme path
        $this->assertIsString($image);
        $this->assertStringContainsString('/images/add.png', $image,
            "Image path should contain /images/add.png");
    }
}
