<?php

/**
 * @group Lib
 */

class LibWidgetTest extends CIUnit_TestCase
{
	public function setUp()
	{
		// Set up fixtures to be run before each test
		
		// Load the tested library so it will be available in all tests
		$this->CI->load->library('Widget');
	}
	
	public function testMethod()
	{
		// Check if everything is ok
		$widget = new Widget(array('color' => 'white'));
		$this->assertNotNull($widget, "widget created");
		
		$this->assertEquals($widget->get('color'), 'white', 'attribut par défaut');

		$widget->set('color', 'red');
		$this->assertEquals($widget->get('color'), 'red', 'attribut après affectation');

		$this->assertEquals($widget->image(), '', 'image par defaut');
		$this->assertNull($widget->display(), "widget->display()");
	}
}
