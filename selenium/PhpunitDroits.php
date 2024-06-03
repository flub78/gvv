<?php
require_once 'PhpunitGVVSelenium.php';
/**
 * Test des droits d'accès
 */
class PhpunitDroits extends PhpunitGVVSelenium {

    protected $rights = array (
        'membre' =>"Membre",
        'admin' =>"Administrateur club",
        'tresorier' =>"Trésorier",
        'maintenance' =>"Maintenance site",
        'aide' =>"Aide",
        'liste_pilotes' =>"Liste",
        'licences' =>"Licences",
        'ajout_pilote' =>"Ajout",
        'fiches' =>"Fiches",
        'password' =>"Mot de passe",
        'liste_vols' =>"Liste des vols",
        'saisie_vol' =>"Saisie des vols",
        'machines' =>"Machines",
        'stats' =>"Statistiques",
        'formation' =>"Formation",
        'facture' =>"Ma facture",
        'remorques' =>"Mes remorqués",
        'journaux' =>"Journaux",
        'balance' =>"Balance",
        'soldes' =>"Soldes pilote",
        'resultats' =>"Résultats",
        'bilan' =>"Bilan",
        'recettes' =>"Recettes",
        'reglement' =>"Reglement par pilote",
        'depenses' =>"Dépenses",
        'virement' =>"Virement bancaire"
    );
    
    protected $admin = array ('membre', 'admin', 'tresorier', 'maintenance', 'aide', 'liste_pilotes', 'licences', 'ajout_pilote', 'fiches', 'password', 
        'liste_vols', 'saisie_vol', 'machines', 'stats', 'formation', 'facture', 'remorques',
        'journaux', 'balance', 'soldes', 'resultats', 'bilan', 
        'recettes', 'reglement', 'depenses', 'virement');
    protected $user = array ('membre', 'aide', 'liste_pilotes', 'licences', 'fiches', 'password', 'liste_vols', 'machines', 'stats', 'formation', 'facture', 'remorques');
    
    protected $planchiste = array ('saisie_vol');
    protected $ca = array ('admin', 'ajout_pilote', 'resultats', 'bilan');
    protected $bureau = array ('journaux', 'balance', 'soldes');
    protected $tresorier = array ('recettes', 'reglement', 'depenses', 'virement');
    
    /*
     * Test l'affichage de la page journal'
     */
    public function checkDroits($login, $password, $list) {
        $this->open("/gvv2/index.php/auth/login");
        $this->type("id=username", $login);
        $this->type("id=password", $password);
        $this->click("name=login");
        $this->waitForPageToLoad("30000");
        
        $txt = "";
        foreach ($this->rights as $id => $value) {
            if (in_array($id, $list)) {
                $this->assertTrue($this->isTextPresent($value), "check $login has $id");
            } else {
                $this->assertFalse($this->isTextPresent($value), "check $login has not $id");
            }
        }
        echo $txt;
    }

    /*
     * Test les droits users
     */
    public function testUser() {
        $this->checkDroits("testuser", "testuser", $this->user);
    }

    /*
     * Test les droits planchiste
     */
    public function testPlanchiste() {
        $this->checkDroits("testplanchiste", "testplanchiste", array_merge( $this->user, $this->planchiste));
    }

    /*
     * Test les droits CA
     */
    public function testCA() {
        $this->checkDroits("testca", "testca", array_merge( $this->user, $this->planchiste, $this->ca));
    }
  
    /*
     * Test les droits Bureau
     */
    public function testBureau() {
        $this->checkDroits("testbureau", "testbureau", array_merge( $this->user, $this->planchiste, $this->ca, $this->bureau));
    }

    /*
     * Test les droits Trésorier
    public function testTresorier() {
        $this->checkDroits("testtresorier", "testtresorier", array_merge( $this->user, $this->planchiste, $this->ca, $this->bureau, $this->tresorier));
    }
     */

    /*
     * Test les droits admin
     */
    public function testDroitsAdmin() {
        $this->checkDroits("testadmin", "testadmin", $this->admin);
    }

     
}
?>