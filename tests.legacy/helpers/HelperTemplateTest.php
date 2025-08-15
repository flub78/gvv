<?php

/**
 * Unit test for helper ...
 * @group Helper
 */

class HelperTemplateTest extends CIUnit_TestCase
{
	public function setUp()
	{
		$this->CI->load->helper('log');
	}
	
	/**
	 * Just a few assertions
	 */
	public function testAsserts()
	{
		$this->assertEquals(3, 3, "3 == 3");
		$this->assertTrue(true, "true == true");
	}	
}
