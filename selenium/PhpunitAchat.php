<?php
require_once 'PhpunitGVVSelenium.php';

class PhpunitAchat extends PhpunitGVVSelenium {

    public function testAchat() {

        $this->login();
        
        $this->click("link=Balance");
        $this->waitForPageToLoad("30000");
        $this->click("css=img.icon");
        $this->waitForPageToLoad("30000");
        $this->type("name=nom", "Vente librairie");
        $this->select("name=codec", "label=75 Autres produits de gestion courante");
        $this->type("name=desc", "Vente librairie");
        $this->click("name=button");
        $this->waitForPageToLoad("30000");
        $this->click("link=Trésorier");
        $this->waitForPageToLoad("30000");
        $this->click("link=Définition des produits et tarifs");
        $this->waitForPageToLoad("30000");
        $this->click("css=img.icon");
        $this->waitForPageToLoad("30000");
        $this->type("name=reference", "Carnet de vol");
        $this->type("css=input[name=\"description\"]", "Carnet de vol");
        $this->type("name=prix", "15");
        $this->select("name=compte", "label=(75) Vente librairie");
        $this->click("name=button");
        $this->waitForPageToLoad("30000");
        $this->click("link=Soldes pilote");
        $this->waitForPageToLoad("30000");
        $this->click("link=Abraracourcix Abraracourcix");
        $this->waitForPageToLoad("30000");
        $this->select("id=product_selector", "label=Forfait heures : 300.00");
        $this->click("xpath=(//input[@name='button'])[3]");
        $this->waitForPageToLoad("30000");
        try {
            $this->assertEquals("200.00", $this->getValue("xpath=(//input[@name='credit'])[2]"));
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            array_push($this->verificationErrors, $e->toString());
        }
        $this->assertTrue($this->isTextPresent("Heures de vol et remorqués"));
        $this->click("link=Ventes");
        $this->waitForPageToLoad("30000");
        $this->assertTrue($this->isTextPresent("Forfait heures"));
        $this->assertEquals("1.00", $this->getText("//div[@id='body']/table[2]/tbody/tr/td[3]"));
        $this->assertEquals("300.00", $this->getText("//div[@id='body']/table[2]/tbody/tr/td[4]"));
    }
}
?>