<?php
require_once 'PhpunitGVVSelenium.php';

class Unit_test extends PhpunitGVVSelenium {

  protected function setUp()
  {
    $this->setBrowser("*chrome");
    $this->setBrowserUrl("http://localhost/");
  }

  private function check_url($url) {
  	$this->login();
  	
  	$this->open($url);
  	$this->verifyTextPresent("Passed");
  	$this->verifyTextNotPresent("Failed");
  	$this->verifyTextNotPresent("PHP Error");
  }
  
  public function testAvion()
  {
  	$this->check_url("/gvv2/index.php/avion/test");
  }
  
  public function testPlaneur()
  {
  	$this->check_url("/gvv2/index.php/planeur/test");
  }
  
}
?>