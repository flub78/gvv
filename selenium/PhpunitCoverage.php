<?php
require_once 'PhpunitGVVSelenium.php';
/**
 * Activation des tests unitaires CodeIgniter
 */
class PhpunitCoverage extends PhpunitGVVSelenium {

	/**
	 * Bug 1509 duplication des utilisateurs non détecté
	 */
	public function testUnits() {
		$this->login();

		$urls = array (
				'http://localhost/gvv2/index.php/coverage/coverage_result/clover'
		);

		foreach ($urls as $url) {
			$this->open($url);
			$this->waitForPageToLoad("60000");

			try {
				$this->assertFalse($this->isTextPresent("PHP Error"), "No PHP error $url");
			} catch (PHPUnit_Framework_AssertionFailedError $e) {
				array_push($this->verificationErrors, $e->toString());
			}

		}

	}

}
?>