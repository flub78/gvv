<?php
require_once 'PhpunitGVVSelenium.php';

/**
 * Test de base de saisie de membres
 */
class PhpunitBasicMember extends PhpunitGVVSelenium {

	public function testMembre() {
        $this->login();
       
		// Gestion des membres
		$this->click("link=Ajout");
		$this->waitForPageToLoad("30000");
		$this->assertFalse($this->isTextPresent("PHP Error"));
		
		$this->type("name=mlogin", "first");
		$this->type("name=mprenom", "Jean");
		$this->type("name=mnom", "Pierre");
		$this->type("name=memail", "jean@free.fr");
		$this->type("name=madresse", "Sans domicile fixe");
		$this->type("name=cp", "80000");
		$this->type("name=ville", "Abbeville");
		$this->type("name=mtelf", "01 23 45 67 89");
		$this->type("name=mdaten", "01/01/1960");
		$this->click("name=button");
		$this->waitForPageToLoad("30000");
		$this->click("//img[@title='Changer']");
		$this->waitForPageToLoad("30000");
		try {
			$this->assertEquals("Jean", $this->getValue("name=mprenom"));
		} catch (PHPUnit_Framework_AssertionFailedError $e) {
			array_push($this->verificationErrors, $e->toString());
		}
		try {
			$this->assertEquals("Pierre", $this->getValue("name=mnom"));
		} catch (PHPUnit_Framework_AssertionFailedError $e) {
			array_push($this->verificationErrors, $e->toString());
		}
		try {
			$this->assertEquals("jean@free.fr", $this->getValue("name=memail"));
		} catch (PHPUnit_Framework_AssertionFailedError $e) {
			array_push($this->verificationErrors, $e->toString());
		}
		try {
			$this->assertEquals("Sans domicile fixe", $this->getValue("name=madresse"));
		} catch (PHPUnit_Framework_AssertionFailedError $e) {
			array_push($this->verificationErrors, $e->toString());
		}
		try {
			$this->assertEquals("80000", $this->getValue("name=cp"));
		} catch (PHPUnit_Framework_AssertionFailedError $e) {
			array_push($this->verificationErrors, $e->toString());
		}
		try {
			$this->assertEquals("Abbeville", $this->getValue("name=ville"));
		} catch (PHPUnit_Framework_AssertionFailedError $e) {
			array_push($this->verificationErrors, $e->toString());
		}
		try {
			$this->assertEquals("01 23 45 67 89", $this->getValue("name=mtelf"));
		} catch (PHPUnit_Framework_AssertionFailedError $e) {
			array_push($this->verificationErrors, $e->toString());
		}
		try {
			$this->assertEquals("01/01/1960", $this->getValue("name=mdaten"));
		} catch (PHPUnit_Framework_AssertionFailedError $e) {
			array_push($this->verificationErrors, $e->toString());
		}
		try {
			$this->assertEquals("on", $this->getValue("name=msexe"));
		} catch (PHPUnit_Framework_AssertionFailedError $e) {
			array_push($this->verificationErrors, $e->toString());
		}
		try {
			$this->assertEquals("on", $this->getValue("name=actif"));
		} catch (PHPUnit_Framework_AssertionFailedError $e) {
			array_push($this->verificationErrors, $e->toString());
		}
		try {
			$this->assertEquals("off", $this->getValue("name=ext"));
		} catch (PHPUnit_Framework_AssertionFailedError $e) {
			array_push($this->verificationErrors, $e->toString());
		}
		$this->type("name=mprenom", "Jean-Pierre");
		$this->type("name=mnom", "Dupont");
		$this->click("name=mniveau[]");
		$this->click("//input[@name='mniveau[]' and @value='8']");
		$this->click("//input[@name='mniveau[]' and @value='4']");
		$this->click("//input[@name='mniveau[]' and @value='1024']");
		$this->click("//input[@name='mniveau[]' and @value='64']");
		$this->click("//input[@name='mniveau[]' and @value='16384']");
		$this->click("//input[@name='mniveau[]' and @value='4096']");
		$this->click("//input[@name='mniveau[]' and @value='131072']");
		$this->click("//input[@name='mniveau[]' and @value='8192']");
		$this->click("//input[@name='mniveau[]' and @value='2048']");
		$this->click("//input[@name='mniveau[]' and @value='256']");
		$this->click("//input[@name='mniveau[]' and @value='32768']");
		$this->click("link=Liste");
		$this->waitForPageToLoad("30000");
		$this->click("//img[@title='Changer']");
		$this->waitForPageToLoad("30000");
		try {
			$this->assertEquals("Jean", $this->getValue("name=mprenom"));
		} catch (PHPUnit_Framework_AssertionFailedError $e) {
			array_push($this->verificationErrors, $e->toString());
		}
		try {
			$this->assertEquals("Pierre", $this->getValue("name=mnom"));
		} catch (PHPUnit_Framework_AssertionFailedError $e) {
			array_push($this->verificationErrors, $e->toString());
		}
		try {
			$this->assertEquals("01 23 45 67 89", $this->getValue("name=mtelf"));
		} catch (PHPUnit_Framework_AssertionFailedError $e) {
			array_push($this->verificationErrors, $e->toString());
		}
		try {
			$this->assertEquals("01/01/1960", $this->getValue("name=mdaten"));
		} catch (PHPUnit_Framework_AssertionFailedError $e) {
			array_push($this->verificationErrors, $e->toString());
		}
		$this->click("name=mniveau[]");
		$this->click("//input[@name='mniveau[]' and @value='8']");
		$this->click("//input[@name='mniveau[]' and @value='4']");
		$this->click("//input[@name='mniveau[]' and @value='32']");
		$this->click("//input[@name='mniveau[]' and @value='1024']");
		$this->click("//input[@name='mniveau[]' and @value='4096']");
		$this->click("//input[@name='mniveau[]' and @value='512']");
		$this->click("//input[@name='mniveau[]' and @value='262144']");
		$this->click("//input[@name='mniveau[]' and @value='2048']");
		$this->click("//input[@name='mniveau[]' and @value='256']");
		$this->type("name=mbrpdat", "01/01/2011");
		$this->click("//input[@name='mniveau[]' and @value='32768']");
		$this->click("name=button");
		$this->waitForPageToLoad("30000");
		$this->click("//img[@title='Changer']");
		$this->waitForPageToLoad("30000");

		$this->assertFalse($this->isTextPresent("Date incorrecte"));
		$this->assertFalse($this->isTextPresent("Fatal erro"));
		$this->assertFalse($this->isTextPresent("PHP error"));
	}

	public function testAddAsterix() {
        $this->login();

		$this->click("link=Ajout");
		$this->waitForPageToLoad("30000");
		$this->type("name=mlogin", "asterix");
		$this->type("name=mprenom", "Astérix");
		$this->type("name=mnom", "Legaulois");
//		$this->type("name=userfile", "/home/flubber/workspace/gvv2/tests/asterix.jpg");
// 		$this->click("name=button_photo");
// 		$this->waitForPageToLoad("30000");
		$this->type("name=memail", "asterix@free.fr");
		$this->type("name=cp", "");
		$this->type("name=cp", "56340");
		$this->type("name=ville", "Village Gaulois");
		$this->click("name=button");
		$this->waitForPageToLoad("30000");
		$this->type("name=madresse", "hutte d'astérix");
		$this->click("name=button");
		$this->waitForPageToLoad("30000");
		$this->click("//img[@title='Changer']");
		$this->waitForPageToLoad("30000");
// 		$this->type("name=userfile", "/home/flubber/workspace/gvv2/tests/asterix.jpg");
// 		$this->click("name=button_photo");
// 		$this->waitForPageToLoad("30000");
		$this->click("link=Liste");
		$this->waitForPageToLoad("30000");
		$this->click("//img[@title='Changer']");
		$this->waitForPageToLoad("30000");
	}

}
?>