<?php

/**
 * @group Controller
 */

class ControllerComptesTest extends CIUnit_TestCase
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
		$this->CI->load->helper('form_elements');
		$this->CI->load->library('DX_Auth');
	
		// echo "ControllerComptesTest setup, setting controller\n";		
		$this->CI = set_controller('comptes');
	
		$this->login("testadmin", "testadmin");
	
	}
	
	public function setUp()
	{		
	}
	
	public function testIndex()
	{		
		// Call the controllers method
		$this->no_errors_on_page("index");
		$this->no_errors_on_page("view");
		$this->no_errors_on_page("resultat");
		$this->no_errors_on_page("tresorerie");
		$this->no_errors_on_page("bilan");
		$this->no_errors_on_page("check");
		$this->no_errors_on_page("cloture");
	}
	
	function __destruct() {
		$this->logout();
	}
	
}
