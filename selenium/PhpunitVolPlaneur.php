<?php
require_once 'PhpunitGVVSelenium.php';
/**
 * Test d'ajout de vols planeur (sans facturation)
 */
class PhpunitVolPlaneur extends PhpunitGVVSelenium {

    /*
     * Test un vol planeur
     */
    public function testVolPlaneur() {
        $this->login();
        $this->click("link=Saisie des vols");
        $this->waitForPageToLoad("30000");
        $this->select("name=vpmacid", "label=Asw20 F-CERP");
        $this->select("name=vppilid", "label=Abraracourcix Bonemine");
        $this->type("name=vpcdeb", "12");
        $this->type("name=vpcfin", "13.30");
        $this->click("id=Treuil");
        $this->click("name=button");
        $this->waitForPageToLoad("30000");
        $this->assertTrue($this->isTextPresent("Vols planeurs"));
        $this->assertTrue($this->isTextPresent("F-CERP"));
        $this->click("//img[@title='Changer']");
        $this->waitForPageToLoad("30000");
        $this->click("id=Remorqué");
        $this->select("name=remorqueur", "label=F-BERK");
        $this->select("name=pilote_remorqueur", "label=Abraracourcix Abraracourcix");
        $this->click("name=button");
        $this->waitForPageToLoad("30000");
        $this->click("link=Liste des vols");
        $this->waitForPageToLoad("30000");
    }
}
?>