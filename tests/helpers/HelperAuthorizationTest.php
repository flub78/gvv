<?php

/**
 * @group Helper
 */

class HelperAuthorizationTest extends CIUnit_TestCase
{
	public function setUp()
	{
		$this->CI->load->library('DX_Auth');
		$this->CI->load->helper('Authorization');
	}
		
	public function testAuthorization() {
		$this->login("testadmin", "password");
		
		# $this->assertSame("testadmin", logged_username(), "Looged in username");
		# $this->assertSame("testadmin", $this->CI->dx_auth->get_username(), "Looged in username");
		$this->logout();
	}
	

}
