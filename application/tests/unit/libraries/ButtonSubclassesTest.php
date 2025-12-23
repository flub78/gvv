<?php

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Button subclasses
 * 
 * Tests ButtonEdit, ButtonDelete, ButtonView, and ButtonNew classes
 * which extend Button with predefined defaults for common actions
 * 
 * @package tests
 */
class ButtonSubclassesTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        // Load the required libraries
        require_once APPPATH . 'libraries/Widget.php';
        require_once APPPATH . 'libraries/Button.php';
        require_once APPPATH . 'libraries/ButtonEdit.php';
        require_once APPPATH . 'libraries/ButtonDelete.php';
        require_once APPPATH . 'libraries/ButtonView.php';
    }

    /**
     * Test ButtonEdit instantiation with defaults
     */
    public function testButtonEditDefaults()
    {
        $button = new ButtonEdit();
        
        $this->assertInstanceOf(ButtonEdit::class, $button);
        $this->assertInstanceOf(Button::class, $button, "ButtonEdit should extend Button");
        $this->assertEquals('Changer', $button->get('label'), "ButtonEdit default label");
        $this->assertEquals('edit', $button->get('action'), "ButtonEdit default action");
        $this->assertStringContainsString('pencil.png', $button->get('image'), "ButtonEdit should use pencil icon");
    }

    /**
     * Test ButtonEdit with custom attributes
     */
    public function testButtonEditWithCustomAttributes()
    {
        $attrs = array(
            'controller' => 'users',
            'param' => '123'
        );
        
        $button = new ButtonEdit($attrs);
        
        $this->assertEquals('Changer', $button->get('label'), "Label should still be default");
        $this->assertEquals('edit', $button->get('action'), "Action should still be default");
        $this->assertEquals('users', $button->get('controller'));
        $this->assertEquals('123', $button->get('param'));
    }

    /**
     * Test ButtonDelete instantiation with defaults
     */
    public function testButtonDeleteDefaults()
    {
        $button = new ButtonDelete();
        
        $this->assertInstanceOf(ButtonDelete::class, $button);
        $this->assertInstanceOf(Button::class, $button, "ButtonDelete should extend Button");
        $this->assertEquals('Supprimer', $button->get('label'), "ButtonDelete default label");
        $this->assertEquals('delete', $button->get('action'), "ButtonDelete default action");
        $this->assertTrue($button->get('confirm'), "ButtonDelete should require confirmation");
        $this->assertEquals('Etes vous sur de vouloir supprimer ', $button->get('confirmMsg'));
        $this->assertStringContainsString('delete.png', $button->get('image'), "ButtonDelete should use delete icon");
    }

    /**
     * Test ButtonDelete with custom confirmation message
     */
    public function testButtonDeleteWithCustomConfirmMsg()
    {
        $attrs = array(
            'confirmMsg' => 'Voulez-vous vraiment supprimer cet élément'
        );
        
        $button = new ButtonDelete($attrs);
        
        $this->assertTrue($button->get('confirm'));
        $this->assertEquals('Voulez-vous vraiment supprimer cet élément', $button->get('confirmMsg'));
    }

    /**
     * Test ButtonDelete without custom confirmMsg uses default
     */
    public function testButtonDeleteDefaultConfirmMsg()
    {
        $button = new ButtonDelete(array('controller' => 'items'));
        
        $this->assertEquals('Etes vous sur de vouloir supprimer ', $button->get('confirmMsg'));
        $this->assertEquals('items', $button->get('controller'));
    }

    /**
     * Test ButtonView instantiation with defaults
     */
    public function testButtonViewDefaults()
    {
        $button = new ButtonView();
        
        $this->assertInstanceOf(ButtonView::class, $button);
        $this->assertInstanceOf(Button::class, $button, "ButtonView should extend Button");
        $this->assertEquals('Consulter', $button->get('label'), "ButtonView default label");
        $this->assertEquals('view', $button->get('action'), "ButtonView default action");
        $this->assertStringContainsString('eye.png', $button->get('image'), "ButtonView should use eye icon");
    }

    /**
     * Test ButtonView with custom attributes
     */
    public function testButtonViewWithCustomAttributes()
    {
        $attrs = array(
            'controller' => 'documents',
            'param' => 'report-2024'
        );
        
        $button = new ButtonView($attrs);
        
        $this->assertEquals('Consulter', $button->get('label'));
        $this->assertEquals('view', $button->get('action'));
        $this->assertEquals('documents', $button->get('controller'));
        $this->assertEquals('report-2024', $button->get('param'));
    }

    /**
     * Test all button subclasses inherit Widget functionality
     */
    public function testAllSubclassesInheritWidget()
    {
        $edit = new ButtonEdit();
        $delete = new ButtonDelete();
        $view = new ButtonView();
        
        $this->assertInstanceOf(Widget::class, $edit);
        $this->assertInstanceOf(Widget::class, $delete);
        $this->assertInstanceOf(Widget::class, $view);
    }

    /**
     * Test setting attributes after construction for subclasses
     */
    public function testSetAttributesOnSubclasses()
    {
        $button = new ButtonEdit();
        
        $button->set('controller', 'members');
        $button->set('param', '42');
        
        $this->assertEquals('members', $button->get('controller'));
        $this->assertEquals('42', $button->get('param'));
        $this->assertEquals('Changer', $button->get('label'), "Default label unchanged");
    }

    /**
     * Test ButtonDelete confirmation is always true
     */
    public function testButtonDeleteAlwaysRequiresConfirmation()
    {
        // Even with custom attributes, confirm should be TRUE
        $button = new ButtonDelete(array(
            'controller' => 'users',
            'param' => '999'
        ));
        
        $this->assertTrue($button->get('confirm'), "ButtonDelete should always confirm");
    }

    /**
     * Test different button types are independent
     */
    public function testDifferentButtonTypesAreIndependent()
    {
        $edit = new ButtonEdit(array('controller' => 'edit_controller'));
        $delete = new ButtonDelete(array('controller' => 'delete_controller'));
        $view = new ButtonView(array('controller' => 'view_controller'));
        
        $this->assertEquals('edit_controller', $edit->get('controller'));
        $this->assertEquals('delete_controller', $delete->get('controller'));
        $this->assertEquals('view_controller', $view->get('controller'));
        
        $this->assertEquals('edit', $edit->get('action'));
        $this->assertEquals('delete', $delete->get('action'));
        $this->assertEquals('view', $view->get('action'));
    }

    /**
     * Test all buttons have appropriate image icons
     */
    public function testAllButtonsHaveImages()
    {
        $edit = new ButtonEdit();
        $delete = new ButtonDelete();
        $view = new ButtonView();
        
        $this->assertNotEmpty($edit->get('image'));
        $this->assertNotEmpty($delete->get('image'));
        $this->assertNotEmpty($view->get('image'));
        
        $this->assertStringContainsString('.png', $edit->get('image'));
        $this->assertStringContainsString('.png', $delete->get('image'));
        $this->assertStringContainsString('.png', $view->get('image'));
    }

    /**
     * Test ButtonEdit action cannot be changed via constructor
     */
    public function testButtonEditActionIsFixed()
    {
        // Constructor sets action after attributes are passed
        $button = new ButtonEdit(array('action' => 'update'));
        
        // The constructor overwrites any action attribute
        $this->assertEquals('edit', $button->get('action'), "Action should be 'edit'");
    }

    /**
     * Test ButtonDelete action cannot be changed via constructor
     */
    public function testButtonDeleteActionIsFixed()
    {
        $button = new ButtonDelete(array('action' => 'remove'));
        
        $this->assertEquals('delete', $button->get('action'), "Action should be 'delete'");
    }

    /**
     * Test ButtonView action cannot be changed via constructor
     */
    public function testButtonViewActionIsFixed()
    {
        $button = new ButtonView(array('action' => 'show'));
        
        $this->assertEquals('view', $button->get('action'), "Action should be 'view'");
    }
}
