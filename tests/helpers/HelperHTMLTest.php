<?php

/**
 * Unit test for helper HTML
 * @group Helper
 */

class HelperHTMLTest extends CIUnit_TestCase
{
	public function setUp()
	{
		$this->CI->load->helper('html');
	}
	
	/**
	 * Just a few assertions
	 */
	public function testHTML()
	{		
		$this->tst_function_args("p", array("Hello", "length = \"1\""));
		$this->tst_function_args("hr", array(2));
		$this->tst_function_args("Heading", array("Titre"));

		$tbl = array(
			array(1, 2, 3),
			array(4, 5, 6)
		);		
		$this->tst_function_args("table_from_array", array($tbl));
		
		$this->tst_function_args("curPageURL");
		$this->tst_function_args("html_link");
		$this->tst_function_args("html_script");
	}	
}
