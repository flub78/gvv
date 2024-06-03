<?php
require_once 'PhpunitGVVSelenium.php';
/**
 * Test de remise à 0
 */
class PhpunitReset extends PhpunitGVVSelenium {

    // protected $coverageScriptUrl = 'http://localhost/phpunit_coverage.php';

    /**
     * Efface toutes les tables
     */
    public function testReset() {
        
        $hostname = getenv("GVV_HOSTNAME");
        $username = getenv("GVV_USERNAME");
        $password = getenv("GVV_PASSWORD");
        $database = getenv("GVV_DATABASE");
        
        $reset_url = "/gvv2/install/reset.php";
        $install_url = $this->site_to_test . "gvv2/install/index.php";
        
        if ($hostname && $database) {
            $get = http_build_query(array(
                'HOSTNAME' => $hostname,
                'USERNAME' => $username,
                'PASSWORD' => $password,
                'DATABASE' => $database
            ));
            $reset_url .= "?$get";
            $install_url .= "?$get";
            echo "url=$reset_url\n";
        }
        $this->open($reset_url);
        $this->waitForPageToLoad("30000");
        // $this->click("link=exact:" . $this->site_to_test . "gvv2/install");
        $this->open($install_url);
        $this->waitForPageToLoad("30000");
        $this->click("link=exact:". $this->site_to_test . "gvv2");
        $this->waitForPageToLoad("30000");

        // sleep(120);
        // PHPUNIT_SELENIUM_TEST_ID
    }
}
?>
