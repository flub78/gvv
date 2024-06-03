<?php
require_once 'PhpunitGVVSelenium.php';
/**
 * Test de base des résultats comptables
 */
class PhpunitBasicCompta extends PhpunitGVVSelenium {

    /*
     * Test l'affichage de la page journal'
     */
    public function testJournaux() {
        $this->login();
        $this->click("link=Journaux");
        $this->waitForPageToLoad("30000");
        $this->assertTrue($this->isTextPresent("Compte courant"));
        $this->assertTrue($this->isTextPresent("Avance sur vol"));
        $this->assertTrue($this->isTextPresent("Achat d'essence"));
        $this->assertTrue($this->isTextPresent("Heure de vol avion"));
        $this->assertTrue($this->isTextPresent("Goudurix"));
    }
    
    /*
     * Test l'affichage de la balance des comtpes
     */
    public function testBalance() {        
        $this->login();
        $this->click("link=Balance");
        $this->waitForPageToLoad("30000");
        $this->assertTrue($this->isTextPresent("Balance des Comptes"));
        $this->assertTrue($this->isTextPresent("Capital"));
        $this->assertTrue($this->isTextPresent("Fournisseur de ruban adésif"));
        $this->assertTrue($this->isTextPresent("Abraracourcix"));
        $this->assertTrue($this->isTextPresent("512"));
        $this->assertTrue($this->isTextPresent("401"));
        $total_debiteur = $this->getText("//div[@id='body']/table[2]/tbody/tr[15]/td[5]");
        $total_crediteur = $this->getText("//div[@id='body']/table[2]/tbody/tr[15]/td[6]");
        $this->assertEquals("500", $total_debiteur);
        $this->assertEquals("500", $total_crediteur);
        $this->assertEquals("0.00", $this->getText("//div[@id='body']/table[2]/tbody/tr[17]/td[6]"), "Balance équilibrée");
    }
    
    /*
     * Test l'affichage des soldes pilote
     */
    public function testSoldesPilote() {        
        $this->login();
        $this->click("link=Soldes pilote");
        $this->waitForPageToLoad("30000");
        $this->assertEquals("480.00", $this->getText("//div[@id='body']/table[2]/tbody/tr/td[3]"));
        $this->assertEquals("500.00", $this->getText("//div[@id='body']/table[2]/tbody/tr/td[4]"));
        $this->assertEquals("20.00", $this->getText("//div[@id='body']/table[2]/tbody/tr/td[6]"));
        $this->assertEquals("20.00", $this->getText("//div[@id='body']/table[2]/tbody/tr[8]/td[6]"));
    }
    
    /*
     * Test l'affichage du résultat annuel
     */
    public function testResultat() {        
        $this->login();
        $this->click("link=Résultats");
        $this->waitForPageToLoad("30000");
        $this->assertTrue($this->isTextPresent("Dépenses"));
        $this->assertTrue($this->isTextPresent("Recettes"));
        $this->assertTrue($this->isTextPresent("Essence et Huile"));
        $this->assertTrue($this->isTextPresent("756"));
        $this->assertTrue($this->isTextPresent("Total charges"));
        $this->assertTrue($this->isTextPresent("Total produits"));
        $this->assertTrue($this->isTextPresent("Benefices"));
        $this->assertTrue($this->isTextPresent("Pertes"));
        $this->assertEquals("480.00", $this->getText("//div[@id='body']/table/tbody/tr[4]/td[7]"));
    }
    
    /*
     * Test l'affichage du bilan
     */
    public function testBilan() {        
        $this->login();
        $this->click("link=Bilan");
        $this->waitForPageToLoad("30000");
        $this->assertTrue($this->isTextPresent("Actif"));
        $this->assertTrue($this->isTextPresent("Passif"));
        $this->assertTrue($this->isTextPresent("Total actif"));
        $this->assertTrue($this->isTextPresent("Total passif"));
        $this->assertEquals("400", $this->getText("//div[@id='body']/table/tbody/tr[6]/td[3]"));
        $this->assertEquals("400", $this->getText("//div[@id='body']/table/tbody/tr[6]/td[7]"));

    }
    
    /*
     * Test l'affichage des ventes de l'année
     */
    public function testVentes() {        
        $this->login();

        $this->click("link=Ventes");
        $this->waitForPageToLoad("30000");
        $this->assertTrue($this->isTextPresent("Produit"));
        $this->assertTrue($this->isTextPresent("Heure de vol avion"));
        $this->assertTrue($this->isTextPresent("Forfait heures"));
        $this->assertEquals("300.00", $this->getText("//div[@id='body']/table[2]/tbody/tr/td[4]"));
        $this->assertEquals("180.00", $this->getText("//div[@id='body']/table[2]/tbody/tr[2]/td[4]"));
    }
}
?>