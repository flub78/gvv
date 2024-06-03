<?php
require_once 'PhpunitGVVSelenium.php';

class PhpunitAvions extends PhpunitGVVSelenium {

    public function testAvions() {
        $this->login();
                
        $this->click("xpath=(//a[contains(text(),'Machines')])[2]");
        $this->waitForPageToLoad("30000");
        $this->click("css=img.icon");
        $this->waitForPageToLoad("30000");
        $this->type("name=macconstruc", "Socata");
        $this->type("name=macmodele", "MS893-L");
        $this->type("name=macimmat", "F-BLIT");
        $this->click("name=macrem");
        $this->select("name=maprix", "label=Heure de vol avion : 120.00");
        $this->click("name=button");
        $this->waitForPageToLoad("30000");
        $this->click("css=img.icon");
        $this->waitForPageToLoad("30000");
        $this->type("name=macconstruc", "Robin");
        $this->type("name=macmodele", "DR400");
        $this->type("name=macimmat", "F-BERK");
        $this->type("name=macplaces", "4");
        $this->click("name=macrem");
        $this->select("name=maprix", "label=Heure de vol avion : 120.00");
        $this->click("name=button");
        $this->waitForPageToLoad("30000");
        $this->assertTrue($this->isTextPresent("MS893-L"));
        $this->assertTrue($this->isTextPresent("DR400"));
        $this->assertEquals("F-BLIT", $this->getText("//div[@id='body']/table[2]/tbody/tr[2]/td[3]"));
        $this->assertTrue($this->isTextPresent("Robin"));
        $this->click("css=input[type=\"submit\"]");
        $this->waitForPageToLoad("30000");
    }
}
?>