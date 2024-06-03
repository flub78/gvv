<?php

/**
 * @group Controller
 */

class ControllerAvionTest extends CIUnit_TestCase
{
	
	
	/**
	 * Constructor
	 * 
	 * Load the libraries
	 * 
	 */
	function __construct() {
		
		CIUnit_TestCase::__construct();
				
		$this->CI->load->helper('file');
		$this->CI->load->library('DX_Auth');

		// echo "ControllerAvionTest setup, setting controller\n";
		$this->CI = set_controller('avion');

		$this->login("testadmin", "testadmin");
		
	}
	
	public function setUp()
	{
	}
	
	public function testAvionController()
	{
		$this->no_errors_on_page("index");
		// @todo a reactiver
		// $this->no_errors_on_page("create");
	}
	
	function __destruct() {
		$this->logout();
	}
	
}
