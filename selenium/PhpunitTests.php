<?php
require_once 'PhpunitGVVSelenium.php';
/**
 * Activation des tests unitaires CodeIgniter
 */
class PhpunitTests extends PhpunitGVVSelenium {

	var $base_url = 'http://localhost/gvv2/index.php/';

	private function check_url($url, $with_tests = true) {
		$this->login();
		$url = $this->base_url . $url;
		$this->open($url);
		$this->waitForPageToLoad("30000");

		if ($with_tests) {
			try {
				$this->assertTrue($this->isTextPresent("Passed"), "Passed $url");
			} catch (PHPUnit_Framework_AssertionFailedError $e) {
				array_push($this->verificationErrors, $e->toString());
			}

			try {
				$this->assertFalse($this->isTextPresent("Failed"), "No failed $url");
			} catch (PHPUnit_Framework_AssertionFailedError $e) {
				array_push($this->verificationErrors, $e->toString());
			}
		}
		try {
			$this->assertFalse($this->isTextPresent("PHP Error"), "No PHP error $url");
		} catch (PHPUnit_Framework_AssertionFailedError $e) {
			array_push($this->verificationErrors, $e->toString());
		}

	}

	public function testCoverage() {
		$this->check_url('coverage/reset_coverage', false);
	}
	public function testHelpers() {
		$this->check_url('tests/test_helpers');
	}
	public function testLibrairies() {
		$this->check_url('tests/test_libraries');
	}
	public function testCtrlAchats() {
		$this->check_url('achats/test');
	}
	public function testCtrlAdmin() {
		$this->check_url('admin/test');
	}
	public function testCtrlAvion() {
		$this->check_url('avion/test');
	}
	public function testCtrlCategorie() {
		$this->check_url('categorie/test');
	}
	public function testCtrlCompta() {
		$this->check_url('compta/test');
	}
	public function testCtrlcomptes() {
		$this->check_url('comptes/test');
	}
	public function testCtrlevent() {
		$this->check_url('event/test');
	}
	public function testCtrllicences() {
		$this->check_url('licences/test');
	}
	public function testCtrlmembre() {
		$this->check_url('membre/test');
	}
	public function testCtrlplan_comptable() {
		$this->check_url('plan_comptable/test');
	}
	public function testCtrlplaneur() {
		$this->check_url('planeur/test');
	}
	public function testCtrlpompes() {
		$this->check_url('pompes/test');
	}
	public function testCtrlpresences() {
		$this->check_url('presences/test');
	}
	public function testCtrlrapports() {
		$this->check_url('rapports/test');
	}
	public function testCtrltarifs() {
		$this->check_url('tarifs/test');
	}
	public function testCtrlterrains() {
		$this->check_url('terrains/test');
	}
	public function testCtrltickets() {
		$this->check_url('tickets/test');
	}
	public function testCtrltype_ticket() {
		$this->check_url('types_ticket/test');
	}
	public function testCtrlvols_avion() {
		$this->check_url('vols_avion/test');
	}
	public function testCtrlvols_planeur() {
		$this->check_url('vols_planeur/test');
	}
	public function testCtrlfacturation() {
		$this->check_url('facturation/test');
	}

}
?>