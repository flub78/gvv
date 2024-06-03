<?php
require_once 'PhpunitGVVSelenium.php';

class PhpunitFacturation extends PhpunitGVVSelenium {

    public function testFacturationPlaneur() {
        $this->login();

        print ("Facturation planeur" . "\n");
        $this->click("link=Maintenance site");
        $this->waitForPageToLoad("30000");
        $this->click("link=Configuration du club");
        $this->waitForPageToLoad("30000");
        $this->click("name=button");
        $this->waitForPageToLoad("30000");
        $this->type("name=club", "accabs");
        $this->click("name=button");
        $this->waitForPageToLoad("30000");
        $this->click("link=Balance");
        $this->waitForPageToLoad("30000");
        $this->assertEquals("394.67", $this->getText("//div[@id='body']/table[2]/tbody/tr[7]/td[6]"), "Solde Panoramix");
        $this->assertEquals("0.00", $this->getText("//div[@id='body']/table[2]/tbody/tr[5]/td[5]"), "Solde débiteur Astérix");
        $this->assertEquals("0.00", $this->getText("//div[@id='body']/table[2]/tbody/tr[5]/td[6]"), "Solde créditeur Astérix");
        $this->assertEquals("1085.33", $this->getText("//div[@id='body']/table[2]/tbody/tr[15]/td[6]"), "Solde HDV");
        $this->click("link=Solde remorqués");
        $this->waitForPageToLoad("30000");
        $this->assertTrue($this->isTextPresent("Panoramix Panoramix"), "Panoramix a des tickets");
        $this->assertEquals("10", $this->getText("//div[@id='body']/table[2]/tbody/tr/td[3]"), "Panoramix a 10 tickets");
        $this->click("link=Saisie des vols");
        $this->waitForPageToLoad("30000");
        $this->select("name=vppilid", "label=Panoramix Panoramix");
        $this->click("name=vpdc");
        $this->select("name=vpinst", "label=Pierre Jean");
        $this->type("name=vpcdeb", "17");
        $this->type("name=vpcfin", "18");
        $this->type("name=vpobs", "Test facturation");
        $this->type("name=vpaltrem", "600");
        $this->select("name=remorqueur", "label=F-BERK");
        $this->select("name=pilote_remorqueur", "label=Abraracourcix Abraracourcix");
        $this->click("name=button");
        $this->waitForPageToLoad("30000");

        print ("Vérification des soldes après création d'un vol" . "\n");
        $this->click("link=Soldes pilote");
        $this->waitForPageToLoad("30000");
        $this->assertEquals("369.67", $this->getText("//div[@id='body']/table[2]/tbody/tr[5]/td[6]"), "Solde panoramix après vol");
        $this->click("link=Solde remorqués");
        $this->waitForPageToLoad("30000");
        $this->assertEquals("9", $this->getText("//div[@id='body']/table[2]/tbody/tr/td[3]"), "Remorqué décompté");

        print ("Partage 50/50" . "\n");
        $this->click("link=Liste des vols");
        $this->waitForPageToLoad("30000");
        $this->click("//img[@title='Changer']");
        $this->waitForPageToLoad("30000");
        $this->select("name=payeur", "label=Legaulois Astérix");
        $this->click("id=50");
        $this->click("name=button");
        $this->waitForPageToLoad("30000");
        $this->click("link=Soldes pilote");
        $this->waitForPageToLoad("30000");
        $this->assertEquals("384.67", $this->getText("//div[@id='body']/table[2]/tbody/tr[5]/td[6]"), "Solde Panoramix 50/50");
        $this->assertEquals("43.50", $this->getText("//div[@id='body']/table[2]/tbody/tr[3]/td[5]"), "Solde Astérix 50/50");

        print ("Partage 100% payeur" . "\n");
        $this->click("link=Liste des vols");
        $this->waitForPageToLoad("30000");
        $this->click("//img[@title='Changer']");
        $this->waitForPageToLoad("30000");
        $this->click("id=100");
        $this->click("name=button");
        $this->waitForPageToLoad("30000");
        $this->click("link=Soldes pilote");
        $this->waitForPageToLoad("30000");
        $this->assertEquals("394.67", $this->getText("//div[@id='body']/table[2]/tbody/tr[5]/td[6]"), "Solde Panoramix = initial");
        $this->assertEquals("57.00", $this->getText("//div[@id='body']/table[2]/tbody/tr[3]/td[5]"), "Solde Astérix=payeur");
        $this->click("link=Solde remorqués");
        $this->waitForPageToLoad("30000");
        $this->assertEquals("10", $this->getText("//div[@id='body']/table[2]/tbody/tr/td[3]"), "Restitution remorqué");
        $this->click("link=Soldes pilote");
        $this->waitForPageToLoad("30000");
        $this->click("link=Legaulois Astérix");
        $this->waitForPageToLoad("30000");

        print ("Suppression du vol\n");
        $this->chooseOkOnNextConfirmation();
        $this->click("//img[@title='Supprimer']");
        $this->waitForPageToLoad("30000");
        $this->click("link=Liste des vols");
        $this->waitForPageToLoad("30000");
        $this->click("link=Soldes pilote");
        $this->waitForPageToLoad("30000");
        $this->assertEquals("0.00", $this->getText("//div[@id='body']/table[2]/tbody/tr[3]/td[5]"), "Solde Astérix après suppression");
    }

    public function testFacturationAvion() {
        $this->login();

        $this->click("link=Soldes pilote");
        $this->waitForPageToLoad("30000");
        $this->assertEquals("369.67", $this->getText("//div[@id='body']/table[2]/tbody/tr[5]/td[6]"));
        $this->assertEquals("0.00", $this->getText("//div[@id='body']/table[2]/tbody/tr[3]/td[5]"));
        $this->assertEquals("0.00", $this->getText("//div[@id='body']/table[2]/tbody/tr[3]/td[6]"));
        $this->click("link=Balance");
        $this->waitForPageToLoad("30000");
        $this->assertEquals("1085.33", $this->getText("//div[@id='body']/table[2]/tbody/tr[15]/td[6]"));
        $this->click("xpath=(//a[contains(text(),'Saisie des vols')])[2]");
        $this->waitForPageToLoad("30000");
        $this->select("name=vapilid", "label=Panoramix Panoramix");
        $this->type("name=vacfin", "2");
        $this->click("name=button");
        $this->waitForPageToLoad("30000");
        $this->click("link=Soldes pilote");
        $this->waitForPageToLoad("30000");
        $this->click("link=Panoramix Panoramix");
        $this->waitForPageToLoad("30000");
        $this->assertTrue($this->isTextPresent("Heure de vol avion"));
        try {
            $this->assertEquals("334.67", $this->getValue("xpath=(//input[@name='credit'])[2]"));
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            array_push($this->verificationErrors, $e->toString());
        }
        $this->click("xpath=(//img[@title='Changer'])[8]");
        $this->waitForPageToLoad("30000");
        $this->select("name=payeur", "label=Goudurix Goudurix");
        $this->click("id=50");
        $this->click("name=button");
        $this->waitForPageToLoad("30000");
        $this->click("link=Soldes pilote");
        $this->waitForPageToLoad("30000");
        $this->assertEquals("364.67", $this->getText("//div[@id='body']/table[2]/tbody/tr[5]/td[6]"));
        $this->assertEquals("10.00", $this->getText("//div[@id='body']/table[2]/tbody/tr/td[5]"));
        $this->click("link=Abraracourcix Abraracourcix");
        $this->waitForPageToLoad("30000");
        $this->click("xpath=(//img[@title='Changer'])[4]");
        $this->waitForPageToLoad("30000");
        $this->click("id=100");
        $this->click("name=button");
        $this->waitForPageToLoad("30000");
        $this->click("link=Balance");
        $this->waitForPageToLoad("30000");
        $this->assertEquals("1145.33", $this->getText("//div[@id='body']/table[2]/tbody/tr[15]/td[6]"));
        $this->assertEquals("40.00", $this->getText("//div[@id='body']/table[2]/tbody/tr[3]/td[5]"));
        $this->assertEquals("394.67", $this->getText("//div[@id='body']/table[2]/tbody/tr[7]/td[6]"));
        $this->click("link=Panoramix Panoramix");
        $this->waitForPageToLoad("30000");
        $this->click("link=Soldes pilote");
        $this->waitForPageToLoad("30000");
        $this->click("link=Abraracourcix Abraracourcix");
        $this->waitForPageToLoad("30000");
        $this->chooseOkOnNextConfirmation();
        $this->click("xpath=(//img[@title='Supprimer'])[4]");
        $this->assertTrue((bool) preg_match('/^Etes vous sure de vouloir supprimer /', $this->getConfirmation()));
        $this->click("link=Balance");
        $this->waitForPageToLoad("30000");
        $this->assertEquals("1145.33", $this->getText("//div[@id='body']/table[2]/tbody/tr[15]/td[6]"));
        $this->assertEquals("394.67", $this->getText("//div[@id='body']/table[2]/tbody/tr[7]/td[6]"));
        $this->assertEquals("40.00", $this->getText("//div[@id='body']/table[2]/tbody/tr[3]/td[5]"));
    }

}
?>