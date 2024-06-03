<?php
require_once 'PhpunitGVVSelenium.php';
/**
 * Vérification de correction de bug
 */
class PhpunitFrenchDatePicker extends PhpunitGVVSelenium {

    /**
     * Check that the DatePicker format is in French
     */
    public function testFrench() {
        $this->login();
        $this->open($this->base_url . "compta/recettes");
        $this->waitForPageToLoad("30000");
        $this->click("id=date_op");
        $this->click("css=span.ui-icon.ui-icon-circle-triangle-e");
        $this->click("link=25");
        try {
        	$date = $this->getValue("id=date_op");
        	$date_regexp = '%(0[1-9]|[12][0-9]|3[01])[- \/\.](0[1-9]|1[012])[- \/\.]((19|20)[0-9]{2})%';
        	if (preg_match($date_regexp, $date, $matches)) {
        		$day = $matches[1];
        		$month = $matches[2];
        		$year = $matches[3];
        		$this->assertTrue($day >= 1, "day >= 1");
        		$this->assertTrue($month >= 1, "month >= 1");
        		$this->assertTrue($year >= 2012, "year >= 2012");
        		$this->assertTrue($day <= 31, "day <= 31");
        		$this->assertTrue($month <= 12, "month <= 12");
        		
        	} else {
        		$this->assertTrue(false, "date matches dd/mm/yyyy");
        	}
        	 
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
        	array_push($this->verificationErrors, $e->toString());
        }
        
    }
}
?>