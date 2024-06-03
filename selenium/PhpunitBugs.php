<?php
require_once 'PhpunitGVVSelenium.php';
/**
 * Vérification de correction de bug
 */
class PhpunitBugs extends PhpunitGVVSelenium {

    /**
     * Bug 1509 duplication des utilisateurs non détecté
     */
    public function testBug1509() {
        $this->login();

        $this->click("link=Maintenance site");
        $this->waitForPageToLoad("30000");
        $this->click("link=Utilisateurs");
        $this->waitForPageToLoad("30000");
        $this->assertEquals("testuser", $this->getText("//div[@id='body']/form/table/tbody/tr/td[2]"));
        $this->click("link=Ajout");
        $this->waitForPageToLoad("30000");
        $this->type("name=mlogin", "testuser");
        $this->type("name=mprenom", "Frédéric");
        $this->type("name=mnom", "Moi");
        $this->type("name=memail", "mon.email@moi.fr");
        $this->type("name=madresse", "Chez moi");
        $this->type("name=ville", "Cityville");
        $this->click("name=button");
        $this->waitForPageToLoad("30000");
        $this->click("link=Maintenance site");
        $this->waitForPageToLoad("30000");
        $this->click("link=Utilisateurs");
        $this->waitForPageToLoad("30000");
        
        try {
            $this->assertNotEquals("testuser", $this->getText("//div[@id='body']/form/table/tbody/tr[13]/td[2]"),
                "Vérifie que testuser n'a pas été ajouté");
            $this->assertTrue(FALSE, "Pas d'erreur détectée");
        } catch (Exception $e) {
            $this->assertTrue(TRUE, "Erreur détectée");
        }        
            
        $this->click("link=Liste");
        $this->waitForPageToLoad("30000");
        $this->click("xpath=(//img[@title='Supprimer'])[5]");
        $this->assertTrue((bool) preg_match('/^Etes vous sure de vouloir supprimer le pilote Frédéric Moi[\s\S]$/', $this->getConfirmation()));
    }
    
    public function testUsers() {
        $this->login();
        $this->click("link=Maintenance site");
        $this->waitForPageToLoad("30000");
        $this->click("link=Utilisateurs");
        $this->waitForPageToLoad("30000");
        $this->click("css=img.icon");
        $this->waitForPageToLoad("30000");
        $this->type("name=username", "testuser");
        $this->type("name=password", "testuser");
        $this->type("name=passconf", "testuser");
        $this->type("name=email", "testuser2@free.fr");
        $this->click("name=button");
        $this->waitForPageToLoad("30000");
        $this->assertTrue($this->isTextPresent("testuser existe déjà"));
        $this->type("name=username", "titi");
        $this->type("name=password", "titi");
        $this->type("name=passconf", "titi");
        $this->type("name=email", "titi@free.fr");
        $this->select("name=role_id", "label=planchiste");
        $this->click("name=button");
        $this->waitForPageToLoad("30000");
        $this->assertEquals("titi", $this->getText("//div[@id='body']/form/table/tbody/tr[13]/td[2]"));        
    }    
}
?>