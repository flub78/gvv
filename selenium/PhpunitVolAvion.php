<?php
require_once 'PhpunitGVVSelenium.php';

class PhpunitVolAvion extends PhpunitGVVSelenium {

    public function testVolAvion() {
        $this->login(); 
        
        $this->click("xpath=(//a[contains(text(),'Saisie des vols')])[2]");
        $this->waitForPageToLoad("30000");
        $this->select("name=vapilid", "label=Goudurix Goudurix");
        $this->select("name=vainst", "label=Pierre Jean");
        $this->type("name=vacfin", "1.5");
        $this->click("name=button");
        $this->waitForPageToLoad("30000");
        $this->assertTrue($this->isTextPresent("Goudurix Goudurix"));
        $this->click("link=Soldes pilote");
        $this->waitForPageToLoad("30000");
        $this->click("link=Soldes pilote");
        $this->waitForPageToLoad("30000");
        $this->click("link=Abraracourcix Abraracourcix");
        $this->waitForPageToLoad("30000");
        $this->assertTrue($this->isTextPresent("Heure de vol avion"));
    }
}
?>