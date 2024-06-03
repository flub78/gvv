<?php
require_once 'PhpunitGVVSelenium.php';
/*
 * Test de l'affichage statistique
 */
class PhpunitStatistiques extends PhpunitGVVSelenium {

    /*
     * Vérifie l'affichage des statistiques avec des vols
     * quand il faut regénérer les images
     */
    public function testStatsAvecVols() {
        $this->login();

        $base_dir = "/var/www/html/gvv2/assets/images/";
        $list = array_merge(glob($base_dir . "avion_*.png"), glob($base_dir . "planeur_*.png"));
        foreach ($list as $filename) {
       		unlink($filename);
        }
        $this->click("link=Statistiques");
        $this->waitForPageToLoad("30000");
        $this->assertTrue($this->isTextPresent("Statistiques planeur"));
        $this->assertFalse($this->isTextPresent("PHP Error"));

        $this->click("xpath=(//a[contains(text(),'Statistiques')])[2]");
        $this->waitForPageToLoad("30000");
        $this->assertTrue($this->isTextPresent("Statistiques avion"));
        $this->assertFalse($this->isTextPresent("PHP Error"));
    }

    /*
     * Cette fois les images ont été générées
     */
    public function testSansRecalcul() {
       $this->testStatsAvecVols(); 
    }
}
?>