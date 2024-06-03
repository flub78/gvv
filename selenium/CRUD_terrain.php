<?php

require_once 'PhpunitGVVSelenium.php';

class CRUD_terrain extends PhpunitGVVSelenium {

	/**
	 * 
	 */
	public function testCreate() {

		$this->login();

		$this->open("/gvv2/index.php/terrains/create");
		$this->waitForPageToLoad("30000");
		try {
			$this->assertTrue($this->isTextPresent("Code OACI:"), "Formulaire de saisie affiché");
		} catch (PHPUnit_Framework_AssertionFailedError $e) {
			array_push($this->verificationErrors, $e->toString());
		}
		$this->type("id=oaci", "LFOI");
		$this->type("id=nom", "Abbeville");
		$this->type("name=freq1", "123.5");
		$this->type("name=comment", "Mon terrain");
		$this->click("name=button");
		$this->waitForPageToLoad("30000");
	}

	/**
	 * @depends testCreate
	 */
	public function testRead () {
		$this->login();
		$this->open("/gvv2/index.php/terrains/page");
		$this->waitForPageToLoad("30000");
		try {
			$this->assertTrue($this->isTextPresent("Abbeville"), "Présent dans la liste");
		} catch (PHPUnit_Framework_AssertionFailedError $e) {
			array_push($this->verificationErrors, $e->toString());
		}
		try {
			$this->assertTrue($this->isTextPresent("LFOI"), "Code OACI");
		} catch (PHPUnit_Framework_AssertionFailedError $e) {
			array_push($this->verificationErrors, $e->toString());
		}
		try {
			$this->assertTrue($this->isTextPresent("Terrains"), "Terrains sur la page");
		} catch (PHPUnit_Framework_AssertionFailedError $e) {
			array_push($this->verificationErrors, $e->toString());
		}
	}

	/**
	 * @depends testRead
	 */
	public function testUpdate() {
		$this->login();
		$this->open("/gvv2/index.php/terrains/edit/LFOI");
		$this->waitForPageToLoad("30000");
		$this->type("name=comment", "Abbeville - Buigny - Baie de Somme");
		$this->click("name=button");		
		$this->waitForPageToLoad("30000");
		
		$this->open("/gvv2/index.php/terrains/");
		$this->waitForPageToLoad("30000");
		try {
			$this->assertTrue($this->isTextPresent("Buigny"), "Nouvelle valeur dans la liste");
		} catch (PHPUnit_Framework_AssertionFailedError $e) {
			array_push($this->verificationErrors, $e->toString());
		}
		try {
			$this->assertTrue($this->isTextPresent("Terrains"), "Toujours terrains");
		} catch (PHPUnit_Framework_AssertionFailedError $e) {
			array_push($this->verificationErrors, $e->toString());
		}
	}

	/**
	 * @depends testUpdate
	 */
	public function testDelete () {
		$this->login();
		$this->open("/gvv2/index.php/terrains/delete/LFOI");
		$this->waitForPageToLoad("30000");
		try {
			$this->assertTrue($this->isTextPresent("Terrains"), "Titre après destruction");
		} catch (PHPUnit_Framework_AssertionFailedError $e) {
			array_push($this->verificationErrors, $e->toString());
		}
		try {
			$this->assertFalse($this->isTextPresent("Abbeville"), "Abbeville détruit");
		} catch (PHPUnit_Framework_AssertionFailedError $e) {
			array_push($this->verificationErrors, $e->toString());
		}
	}
	
}
?>