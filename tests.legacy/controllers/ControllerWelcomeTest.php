<?php

/**
 * @group Controller
 */

class ControllerWelcomeTest extends CIUnit_TestCase
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
	
		// echo "ControllerComptesTest setup, setting controller\n";
		$this->CI = set_controller('welcome');
	
		$this->login("testadmin", "testadmin");
	
	}
	
	public function setUp()
	{
	}
	
	public function testWelcomeController()
	{
		$this->no_errors_on_page("index");		
		$this->no_errors_on_page("compta");		
		$this->no_errors_on_page("new_year", 2013);		
		$this->no_errors_on_page("ca");		
		$this->no_errors_on_page("nyi");		
	}
	
	function __destruct() {
		$this->logout();
	}
	
}
