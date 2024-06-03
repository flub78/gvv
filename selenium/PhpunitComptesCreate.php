<?php
require_once 'PhpunitGVVSelenium.php';
/**
 * Test de création de comptes
 */
class PhpunitComptesCreate extends PhpunitGVVSelenium {

    /**
     * Création de plusieurs comptes
     */
     public function testComptesCreate() {
        $this->login();
        
        $this->click("link=Balance");        
        $this->waitForPageToLoad("50000");
        
        $this->click("css=img.icon");
        $this->waitForPageToLoad("50000");
        $this->type("name=nom", "Capital");
        $this->type("name=desc", "Capital");
        $this->click("name=button");
        $this->waitForPageToLoad("50000");
        
        $this->click("css=img.icon");
        $this->waitForPageToLoad("50000");
        $this->type("name=nom", "Compte courant");
        $this->select("name=codec", "label=512 Banque");
        $this->type("name=desc", "Compte courant");
        $this->click("name=button");
        $this->waitForPageToLoad("50000");
        
        $this->click("css=img.icon");
        $this->waitForPageToLoad("50000");
        $this->type("name=nom", "Fournisseur de ruban adésif");
        $this->select("name=codec", "label=401 Fournisseurs");
        $this->type("name=desc", "Fournisseur de ruban adésif");
        $this->click("name=button");
        $this->waitForPageToLoad("50000");
        
        $this->click("css=img.icon");
        $this->waitForPageToLoad("50000");        
        $this->type("name=nom", "Entretien");
        $this->select("name=codec", "label=615 Entretien et réparations");
        $this->type("name=desc", "Entretien");
        $this->click("name=button");
        $this->waitForPageToLoad("50000");
        
        $this->click("css=img.icon");
        $this->waitForPageToLoad("50000");        
        $this->type("name=nom", "Assurance");
        $this->type("name=desc", "Assurance");
        $this->select("name=codec", "label=616 Assurances");
        $this->click("name=button");
        $this->waitForPageToLoad("50000");

/*        
        $this->click("css=img.icon");
        $this->waitForPageToLoad("50000");        
        $this->type("name=nom", "Heures de vol et remorqués");
        $this->type("name=desc", "Heures de vol et remorqués");
        $this->select("name=codec", "label=756 Heures planeurs.");
        $this->click("name=button");
        $this->waitForPageToLoad("50000");
*/
        $this->click("css=img.icon");
        $this->waitForPageToLoad("50000");        
        $this->type("name=nom", "Subvention");
        $this->type("name=desc", "Subvention");
        $this->select("name=codec", "label=746 Autres subventions.");
        $this->click("name=button");
        $this->waitForPageToLoad("50000");

        $this->click("css=img.icon");
        $this->waitForPageToLoad("50000");
        $this->type("name=nom", "Essence et Huile");
        $this->select("name=codec", "label=601 Achats stockés - Matières premières et fournitures");
        $this->type("name=desc", "Essence et Huile");
        $this->click("name=button");
        $this->waitForPageToLoad("50000");
        
        
        $this->assertTrue($this->isTextPresent("Capital"));
        $this->assertTrue($this->isTextPresent("Fournisseur de ruban adésif"));
        $this->assertTrue($this->isTextPresent("Abraracourcix Abraracourcix"));
        $this->assertTrue($this->isTextPresent("Compte courant"));
        $this->assertTrue($this->isTextPresent("Essence et Huile"), "Essence et Huile");
        $this->assertTrue($this->isTextPresent("Entretien"));
        $this->assertTrue($this->isTextPresent("512"));
        $this->assertEquals("0.00", $this->getText("//div[@id='body']/table[2]/tbody/tr[16]/td[6]"));
        $this->assertEquals("0.00", $this->getText("//div[@id='body']/table[2]/tbody/tr/td[3]"));
        $this->assertEquals("0.00", $this->getText("//div[@id='body']/table[2]/tbody/tr/td[4]"));
        $this->assertEquals("0.00", $this->getText("//div[@id='body']/table[2]/tbody/tr/td[5]"));
        $this->assertEquals("0.00", $this->getText("//div[@id='body']/table[2]/tbody/tr/td[6]"));
    }
}
?>