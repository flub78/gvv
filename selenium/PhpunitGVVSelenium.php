<?php

/**
 * Test de base des résultats comptables
 */
class PhpunitGVVSelenium extends PHPUnit_Extensions_SeleniumTestCase {
    
    protected $site_to_test = "http://localhost/";
    protected $captureScreenshotOnFailure = TRUE;
    protected $screenshotPath = '/var/www/html/screenshots';
    protected $screenshotUrl = 'http://localhost/screenshots';
        
    protected $base_url;
    
    protected function setUp() {
        $site = getenv("GVV_SITE");
        if ($site) {
            $this->site_to_test = $site;
        }
        $this->base_url = $this->site_to_test . "gvv2/index.php/";
       
        $this->setBrowser("*chrome");
        $this->setBrowserUrl($this->site_to_test);
        $slow_mode = getenv("SLOW_MODE");
        if ($slow_mode) {
        	$this->setSpeed((integer) $slow_mode);
        } else {
        	$this->setSpeed(0);
        }
    }
    
    static public function setUpBeforeClass() {
        // echo "setUpBeforeClass\n";
        if( ! ini_get('date.timezone') ) {
        	date_default_timezone_set('Europe/Paris');
        }
    }
    
    public function login() {
        // Login as testadmin
        $this->open("/gvv2/index.php/auth/login");
        $this->type("id=username", "testadmin");
        $this->type("id=password", "testadmin");
        $this->click("name=login");
        $this->waitForPageToLoad("30000");        
        $this->verifyTextPresent("Bienvenue");
    }
    
//     public function testExample() {
    
//     	$this->login();
//     	$this->assertTrue(0 == 1, "assert true");
    
//     }
}
?>