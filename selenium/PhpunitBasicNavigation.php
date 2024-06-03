<?php
require_once 'PhpunitGVVSelenium.php';
/*
 * Test de navigation de base
 */
class PhpunitBasicNavigation extends PhpunitGVVSelenium {

    public function testMyTestCase() {

        $this->login();

        // Basic navigation
        // $this->open("/gvv2/index.php/");
        $this->click("link=Membre");
        $this->waitForPageToLoad("30000");
        $this->verifyTextPresent("Bienvenue");
        $this->assertFalse($this->isTextPresent("PHP Error"));

        $this->click("link=Administrateur club");
        $this->waitForPageToLoad("30000");
        $this->verifyTextPresent("Administration");
        $this->assertFalse($this->isTextPresent("PHP Error"));

        $this->click("link=Certificats par pilote");
        $this->waitForPageToLoad("30000");
        $this->assertTrue($this->isTextPresent("Certificats"));
        $this->assertFalse($this->isTextPresent("PHP Error"));

        $this->click("link=Administrateur club");
        $this->waitForPageToLoad("30000");
        $this->click("link=Certificats par pilote");
        $this->waitForPageToLoad("30000");
        $this->assertTrue($this->isTextPresent("Certificats"));
        $this->assertFalse($this->isTextPresent("PHP Error"));

        $this->click("link=Administrateur club");
        $this->waitForPageToLoad("30000");
        $this->assertFalse($this->isTextPresent("PHP Error"));

        $this->click("link=Certificats par pilote");
        $this->waitForPageToLoad("30000");
        $this->assertFalse($this->isTextPresent("PHP Error"));

        $this->click("link=Administrateur club");
        $this->waitForPageToLoad("30000");
        $this->assertFalse($this->isTextPresent("PHP Error"));

        $this->click("//ul[3]/li[2]/a");
        $this->waitForPageToLoad("30000");
        $this->assertFalse($this->isTextPresent("PHP Error"));

        $this->click("link=Administrateur club");
        $this->waitForPageToLoad("30000");
        $this->assertFalse($this->isTextPresent("PHP Error"));

        $this->click("//ul[3]/li[3]/a");
        $this->waitForPageToLoad("30000");
        $this->assertFalse($this->isTextPresent("PHP Error"));

        $this->click("link=Administrateur club");
        $this->waitForPageToLoad("30000");
        $this->assertFalse($this->isTextPresent("PHP Error"));

        $this->click("//ul[3]/li[4]/a");
        $this->waitForPageToLoad("30000");
        $this->assertFalse($this->isTextPresent("PHP Error"));

        $this->click("link=Administrateur club");
        $this->waitForPageToLoad("30000");
        $this->assertFalse($this->isTextPresent("PHP Error"));

        $this->click("link=Trésorier");
        $this->waitForPageToLoad("30000");
        $this->assertFalse($this->isTextPresent("PHP Error"));
        $this->verifyTextPresent("reservée aux trésoriers");

        $this->click("link=Définition du plan comptable");
        $this->waitForPageToLoad("30000");
        $this->verifyTextPresent("Plan comptable");
        $this->assertFalse($this->isTextPresent("PHP Error"));

        $this->click("link=Trésorier");
        $this->waitForPageToLoad("30000");
        $this->assertFalse($this->isTextPresent("PHP Error"));

        $this->click("link=Trésorier");
        $this->waitForPageToLoad("30000");
        $this->assertFalse($this->isTextPresent("PHP Error"));

        $this->click("link=Définition des catégories de dépenses et recettes");
        $this->waitForPageToLoad("30000");
        $this->verifyTextPresent("Catégories");
        $this->assertFalse($this->isTextPresent("PHP Error"));

        $this->click("link=Trésorier");
        $this->waitForPageToLoad("30000");
        $this->assertFalse($this->isTextPresent("PHP Error"));

        $this->click("link=Définition des produits et tarifs");
        $this->waitForPageToLoad("30000");
        $this->verifyTextPresent("Tarifs des produits");
        $this->assertFalse($this->isTextPresent("PHP Error"));

        $this->click("link=Trésorier");
        $this->waitForPageToLoad("30000");
        $this->assertFalse($this->isTextPresent("PHP Error"));

        $this->click("link=Ecriture générale");
        $this->waitForPageToLoad("30000");
        $this->verifyTextPresent("Ecriture comptable");
        $this->assertFalse($this->isTextPresent("PHP Error"));

        $this->click("link=Maintenance site");
        $this->waitForPageToLoad("30000");
        $this->verifyTextPresent("Administration du site et du programme");
        $this->assertFalse($this->isTextPresent("PHP Error"));

        $this->click("link=Configuration du club");
        $this->waitForPageToLoad("30000");
        $this->verifyTextPresent("Configuration");
        $this->assertFalse($this->isTextPresent("PHP Error"));

        $this->click("link=Maintenance site");
        $this->waitForPageToLoad("30000");
        $this->assertFalse($this->isTextPresent("PHP Error"));

        $this->click("link=phpinfo()");
        $this->waitForPageToLoad("30000");
        $this->verifyTextPresent("PHP Version");
        $this->verifyTextPresent("PHP Credits");

        $this->open("/gvv2/index.php/");
        $this->click("link=Maintenance site");
        $this->waitForPageToLoad("30000");
        $this->assertFalse($this->isTextPresent("PHP Error"));

        $this->click("link=Utilisateurs");
        $this->waitForPageToLoad("30000");
        $this->verifyTextPresent("Utilisateur");
        $this->verifyTextPresent("Email");
        $this->assertFalse($this->isTextPresent("PHP Error"));

        $this->click("link=Maintenance site");
        $this->waitForPageToLoad("30000");
        $this->click("link=Roles");
        $this->waitForPageToLoad("30000");
        $this->open("/gvv2/index.php/");
        $this->click("link=Aide");
        $this->waitForPageToLoad("30000");
        $this->verifyTextPresent("Documentation");
        $this->verifyTextPresent("GV");
        $this->open("/gvv2/index.php/");

    }

    /*
     * Vérifie l'affichage des statistiques quand il n'y a pas de vols
     */
    public function testStatsSansVols() {
        $this->login();

        $this->click("link=Statistiques");
        $this->waitForPageToLoad("30000");
        $this->assertTrue($this->isTextPresent("Statistiques planeur"));
        $this->assertFalse($this->isTextPresent("PHP Error"));

        $this->click("xpath=(//a[contains(text(),'Statistiques')])[2]");
        $this->waitForPageToLoad("30000");
        $this->assertTrue($this->isTextPresent("Statistiques avion"));
        $this->assertFalse($this->isTextPresent("PHP Error"));
    }

}
?>