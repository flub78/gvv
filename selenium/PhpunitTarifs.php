<?php
require_once 'PhpunitGVVSelenium.php';
/**
 * Test de création de tarifs
 */
class PhpunitTarifs extends PhpunitGVVSelenium {

    public function testTarifs() {
        $this->login();

        $this->click("link=Trésorier");
        $this->waitForPageToLoad("30000");
        $this->click("link=Définition des produits et tarifs");
        $this->waitForPageToLoad("30000");
        $this->click("css=img.icon");
        $this->waitForPageToLoad("30000");
        
        $this->type("name=reference", "Heure de vol monoplace");
        $this->type("css=input[name=\"description\"]", "Heure de vol monoplace");
        $this->type("name=prix", "10");
        $this->select("name=compte", "label=(756) Heures de vol et remorqués");
        $this->click("name=button");
        $this->waitForPageToLoad("30000");
        $this->click("css=img.icon");
        $this->waitForPageToLoad("30000");
                
        $this->type("name=reference", "Heure de vol biplace");
        $this->type("css=input[name=\"description\"]", "Heure de vol biplace");
        $this->type("name=prix", "27");
        $this->click("name=button");
        $this->waitForPageToLoad("30000");
        $this->click("css=img.icon");
        $this->waitForPageToLoad("30000");
        
        $this->type("name=reference", "Heure de vol au forfait");
        $this->type("css=input[name=\"description\"]", "Heure de vol au forfait");
        $this->type("name=prix", "10");
        $this->select("name=compte", "label=(756) Heures de vol et remorqués");
        $this->click("name=button");
        $this->waitForPageToLoad("30000");
        $this->click("css=img.icon");
        $this->waitForPageToLoad("30000");
        
        $this->type("name=reference", "Heure de vol avion");
        $this->type("css=input[name=\"description\"]", "Heure de vol avion");
        $this->type("name=prix", "120");
        $this->select("name=compte", "label=(756) Heures de vol et remorqués");
        $this->click("name=button");
        $this->waitForPageToLoad("30000");
        $this->click("css=img.icon");
        $this->waitForPageToLoad("30000");
        
        $this->type("name=reference", "Forfait heures");
        $this->type("css=input[name=\"description\"]", "Forfait heures");
        $this->type("name=prix", "300");
        $this->click("name=button");
        $this->waitForPageToLoad("30000");
        $this->click("css=img.icon");
        $this->waitForPageToLoad("30000");
        
        $this->type("name=reference", "Remorqué");
        $this->type("css=input[name=\"description\"]", "Remorqué");
        $this->type("name=prix", "30");
        $this->select("name=compte", "label=(756) Heures de vol et remorqués");
        $this->click("name=button");
        $this->waitForPageToLoad("30000");
        $this->click("css=img.icon");
        $this->waitForPageToLoad("30000");
        
        $this->type("name=reference", "Treuillé");
        $this->type("css=input[name=\"description\"]", "Treuillé");
        $this->type("name=prix", "7");
        $this->select("name=compte", "label=(756) Heures de vol et remorqués");
        $this->click("name=button");
        $this->waitForPageToLoad("30000");
        $this->click("//img[@title='Changer']");
        $this->waitForPageToLoad("30000");
        
        $this->select("name=compte", "label=(756) Heures de vol et remorqués");
        $this->click("name=button");
        $this->waitForPageToLoad("30000");
        $this->click("link=Trésorier");
        $this->waitForPageToLoad("30000");
        $this->click("link=Définition des produits et tarifs");
        $this->waitForPageToLoad("30000");
        $this->click("xpath=(//img[@title='Changer'])[4]");
        $this->waitForPageToLoad("30000");
        $this->select("name=compte", "label=(756) Heures de vol et remorqués");
        $this->click("name=button");
        $this->waitForPageToLoad("30000");

        $this->assertTrue($this->isTextPresent("Forfait heures"), "forfait existe");
        $this->assertEquals("Heure de vol au forfait", $this->getText("//div[@id='body']/table[2]/tbody/tr[2]/td[2]"));
        $this->assertEquals("120.00", $this->getText("//div[@id='body']/table[2]/tbody/tr[3]/td[4]"), "HDV avion");
        $this->assertTrue($this->isTextPresent("Heures de vol et remorqués"), "HDV");
        $this->assertTrue($this->isTextPresent("Heure de vol monoplace"), "mono");
        $this->assertTrue($this->isTextPresent("Heure de vol gratuite"), "free");
        $this->assertTrue($this->isTextPresent("Remorqué"), "rem");
        $this->assertEquals("Treuillé", $this->getText("//div[@id='body']/table[2]/tbody/tr[8]/td"), "treuillé");
    }
}
?>