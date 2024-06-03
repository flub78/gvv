<?php
require_once 'PhpunitGVVSelenium.php';
/**
 * Test de base de la configuration
 */
class PhpunitConfig extends PhpunitGVVSelenium {

    /*
     * Test de mise à jour de configuration
     */
    public function testConfig() {
        $this->login();

        $this->click("link=Maintenance site");
        $this->waitForPageToLoad("30000");
        $this->click("link=Configuration du club");
        $this->waitForPageToLoad("30000");
        $this->assertTrue($this->isTextPresent("Configuration"));
        $this->type("name=sigle_club", "Mon club");
        $this->type("name=nom_club", "Les merveilleux fous volants");
        $this->type("name=cp_club", "99999");
        $this->type("name=tel_club", "0123456789");
        $this->type("name=mois_bilan", "12");
        $this->type("name=jour_bilan", "31");
        $this->type("name=club", "");

// Desactivation temporaire.
//      1) le chargement des images ne marche pas bien sous Linux
//      2) Il faudrait aller chercher l'image dans le workspace
//        
//         $this->type("name=userfile", "/home/flubber/workspace/gvv2/tests/logo.jpeg");

        $this->click("name=button");
        $this->waitForPageToLoad("30000");
        $this->assertFalse($this->isTextPresent("error"));

        try {
            $this->assertEquals("Les merveilleux fous volants", $this->getValue("name=nom_club"));
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            array_push($this->verificationErrors, $e->toString());
        }
        try {
            $this->assertEquals("0123456789", $this->getValue("name=tel_club"));
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            array_push($this->verificationErrors, $e->toString());
        }
        try {
            $this->assertEquals("12", $this->getValue("name=mois_bilan"));
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            array_push($this->verificationErrors, $e->toString());
        }
        $this->click("css=input[type=\"submit\"]");
        $this->waitForPageToLoad("30000");
    }
}
?>