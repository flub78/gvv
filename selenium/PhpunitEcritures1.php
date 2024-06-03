<?php
require_once 'PhpunitGVVSelenium.php';
/**
 * Test de base de passage d'écritures
 */
class PhpunitEcritures1 extends PhpunitGVVSelenium {

    public function testEcritures1() {
        $this->login();
        
        $this->click("link=Reglement par pilote");
        $this->waitForPageToLoad("30000");
        $this->type("name=montant", "500");
        $this->type("css=input[name=\"description\"]", "Avance sur vol");
        $this->select("name=categorie", "label=autre");
        $this->type("name=num_cheque", "CH123456");
        $this->click("name=button");
        $this->waitForPageToLoad("30000");
        
        $this->click("link=Dépenses");
        $this->waitForPageToLoad("30000");
        $this->type("name=montant", "100");
        $this->type("css=input[name=\"description\"]", "Achat d'essence");
        $this->type("name=num_cheque", "CHXYZT");
        $this->select("name=categorie", "label=autre");
        $this->click("name=button");
        $this->waitForPageToLoad("30000");
        $this->click("link=Balance");
        $this->waitForPageToLoad("30000");
        $compte_courant = 8;
        $this->assertEquals("500.00", $this->getText("//div[@id='body']/table[2]/tbody/tr[$compte_courant]/td[3]"));
        $this->assertEquals("100.00", $this->getText("//div[@id='body']/table[2]/tbody/tr[$compte_courant]/td[4]"));
        $this->assertEquals("400.00", $this->getText("//div[@id='body']/table[2]/tbody/tr[$compte_courant]/td[5]"));
        $this->click("link=Compte courant");
        $this->waitForPageToLoad("30000");
        try {
            $this->assertEquals("400.00", $this->getValue("xpath=(//input[@name='debit'])[2]"));
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            array_push($this->verificationErrors, $e->toString());
        }
        $this->click("css=input[type=\"submit\"]");
        $this->waitForPageToLoad("30000");
    }
}
?>