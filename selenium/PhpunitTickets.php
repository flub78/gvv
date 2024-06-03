<?php
require_once 'PhpunitGVVSelenium.php';
/**
 * Test de base de la gestion des tickets
 */
class PhpunitTickets extends PhpunitGVVSelenium {

    /*
     * Tickets
     */
    public function testTickets() {
        $this->login();

        // activation du mode de facturation Abbeville
        $this->click("link=Maintenance site");
        $this->waitForPageToLoad("30000");

        $this->click("link=Configuration du club");
        $this->waitForPageToLoad("30000");

        $this->type("name=club", "accabs");
        $this->click("name=button");
        $this->waitForPageToLoad("30000");

        $this->click("link=Maintenance site");
        $this->waitForPageToLoad("30000");

        $this->click("link=Configuration du club");
        $this->waitForPageToLoad("30000");
        try {
            $this->assertEquals("accabs", $this->getValue("name=club"));
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            array_push($this->verificationErrors, $e->toString());
        }
        
        // Définition des tarifs
        $this->click("link=Trésorier");
        $this->waitForPageToLoad("30000");

        $this->click("link=Définition des produits et tarifs");
        $this->waitForPageToLoad("30000");

        $this->click("css=img.icon");
        $this->waitForPageToLoad("30000");

        $this->type("name=reference", "Pack remorqués");
        $this->type("css=input[name=\"description\"]", "Pack 11 remorqué");
        $this->type("name=prix", "270");
        $this->select("name=compte", "label=(756) Heures de vol et remorqués");
        $this->type("name=nb_tickets", "11");
        $this->click("name=button");
        $this->waitForPageToLoad("30000");

        // Avence de 1000 € Par Panoramix
        $this->click("link=Reglement par pilote");
        $this->waitForPageToLoad("30000");

        $this->select("name=compte2", "label=(411) Panoramix Panoramix");
        $this->type("name=montant", "1000");
        $this->type("css=input[name=\"description\"]", "Avance sur vol");
        $this->type("name=num_cheque", "X1234");
        $this->click("name=button");
        $this->waitForPageToLoad("30000");

        $this->click("link=Soldes pilote");
        $this->waitForPageToLoad("30000");

        $this->click("xpath=(//img[@title='Changer'])[4]");
        $this->waitForPageToLoad("30000");

        // Vente d'un forfait et d'un pack de remorqué à Panoramix
        $this->click("link=Soldes pilote");
        $this->waitForPageToLoad("30000");

        $this->click("link=Panoramix Panoramix");
        $this->waitForPageToLoad("30000");

        $this->click("//div[@id='body']/form/fieldset/legend");
        $this->select("id=product_selector", "label=Forfait heures : 300.00");
        $this->click("xpath=(//input[@name='button'])[3]");
        $this->waitForPageToLoad("30000");

        $this->click("//div[@id='body']/form/fieldset/legend");
        $this->select("id=product_selector", "label=Pack remorqués : 270.00");
        $this->click("xpath=(//input[@name='button'])[3]");
        $this->waitForPageToLoad("30000");

        // Vérification que 11 remorqués on été créditiés
        $this->click("link=Solde remorqués");
        $this->waitForPageToLoad("30000");

        $this->assertEquals("11", $this->getText("//div[@id='body']/table[2]/tbody/tr/td[3]"), "11 remorqués crédités");
        $this->assertTrue($this->isTextPresent("Panoramix Panoramix"));
        
        // Saisie du 1er vol
        $this->click("link=Saisie des vols");
        $this->waitForPageToLoad("30000");

        $this->select("name=vppilid", "label=Panoramix Panoramix");
        $this->type("name=vpcdeb", "13");
        $this->type("name=vpcfin", "14.5");
        $this->select("name=remorqueur", "label=F-BERK");
        $this->select("name=pilote_remorqueur", "label=Abraracourcix Abraracourcix");
        $this->click("name=button");
        $this->waitForPageToLoad("30000");

        $this->click("link=Saisie des vols");
        $this->waitForPageToLoad("30000");

        $this->select("name=vppilid", "label=Panoramix Panoramix");
        $this->type("name=vpcdeb", "15");
        $this->type("name=vpcfin", "16");
        $this->select("name=remorqueur", "label=F-BERK");
        $this->select("name=pilote_remorqueur", "label=Abraracourcix Abraracourcix");
        $this->click("name=button");
        $this->waitForPageToLoad("30000");

        // Vérification que deux remorqués ont été débités
        $this->click("link=Mes remorqués");
        $this->waitForPageToLoad("30000");

        $this->click("link=Solde remorqués");
        $this->waitForPageToLoad("30000");

        $this->assertTrue($this->isTextPresent("9"), "reste 9 après 2 vols");
        
        // Modification d'un des vols pour décoller au treuil
        $this->click("link=Liste des vols");
        $this->waitForPageToLoad("30000");
        $this->click("xpath=(//img[@title='Changer'])[2]");

        $this->waitForPageToLoad("30000");
        $this->click("id=Treuil");
        $this->click("name=button");

//         $this->waitForPageToLoad("30000");
//         $this->click("link=Solde remorqués");
//         $this->waitForPageToLoad("30000");

//         $this->assertTrue($this->isTextPresent("10"), "rest 10 après chgt treuil");
    }
}
?>