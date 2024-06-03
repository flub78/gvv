<?php
require_once 'PhpunitGVVSelenium.php';

class PhpunitAsterix extends PhpunitGVVSelenium
{

  public function testMyTestCase()
  {
            $this->login();
      	
    $this->open("/gvv2/index.php");
    
    $this->click("link=Ajout");
    $this->waitForPageToLoad("30000");
    $this->type("name=mlogin", "bon emine");
    $this->type("name=mprenom", "Bonemine");
    $this->type("name=mnom", "Abraracourcix");
    $this->type("name=memail", "bonemine@free.fr");
    $this->type("name=madresse", "Hutte d'abraracourcix");
    $this->type("name=ville", "Village d'Astérix");
    $this->click("xpath=(//input[@name='msexe'])[2]");
    $this->click("name=button");
    $this->waitForPageToLoad("30000");
    $this->click("css=img.icon");
    $this->waitForPageToLoad("30000");
    $this->type("name=mlogin", "abraracourcix");
    $this->type("name=mprenom", "Abraracourcix");
    $this->type("name=mnom", "Abraracourcix");
    $this->type("name=memail", "abraracourcix@free.fr");
    $this->type("name=madresse", "Hutte d'");
    $this->type("name=madresse", "Hutte d'Abraracourcix");
    $this->type("name=ville", "Village d'Astérix");
    $this->click("xpath=(//input[@name='mniveau[]'])[11]");
    $this->click("xpath=(//input[@name='mniveau[]'])[13]");
    $this->click("xpath=(//input[@name='button'])[1]");
    $this->waitForPageToLoad("30000");
    $this->click("link=Ajout");
    $this->waitForPageToLoad("30000");
    $this->type("name=mlogin", "panoramix");
    $this->type("name=mprenom", "Panoramix");
    $this->type("name=mnom", "Panoramix");
    $this->type("name=memail", "panoramix@free.fr");
    $this->type("name=madresse", "Hutte de Panoramix");
    $this->type("name=ville", "Village d'Astérix");
    $this->click("xpath=(//input[@name='mniveau[]'])[3]");
    $this->click("name=button");
    $this->waitForPageToLoad("30000");
    $this->click("xpath=(//img[@title='Changer'])[2]");
    $this->waitForPageToLoad("30000");
    $this->select("name=compte", "label=(411) Abraracourcix Abraracourcix");
    $this->click("name=button");
    $this->waitForPageToLoad("30000");
    $this->click("link=Liste");
    $this->waitForPageToLoad("30000");
    $this->click("//img[@title='Changer']");
    $this->waitForPageToLoad("30000");
    $this->click("link=Ajout");
    $this->waitForPageToLoad("30000");
    $this->type("name=mlogin", "goudurix");
    $this->type("name=mprenom", "Goudurix");
    $this->type("name=mnom", "Goudurix");
    $this->type("name=memail", "goudurix@free.fr");
    $this->type("name=madresse", "Hutte d'Abraracourcix");
    $this->type("name=ville", "Village d'Astérix");
    $this->click("name=m25ans");
    $this->select("name=compte", "label=(411) Abraracourcix Abraracourcix");
    $this->click("name=button");
    $this->waitForPageToLoad("30000");
    $this->assertTrue($this->isTextPresent("Abraracourcix"));
    $this->assertTrue($this->isTextPresent("Goudurix"));
    $this->assertTrue($this->isTextPresent("Legaulois"));
    $this->click("link=Balance");
    $this->waitForPageToLoad("30000");
    $this->assertTrue($this->isTextPresent("Abraracourcix Abraracourcix"));
    $this->assertTrue($this->isTextPresent("0.00"));
    $this->assertTrue($this->isTextPresent("0.00"));
    $this->assertTrue($this->isTextPresent("Abraracourcix Bonemine"));
    $this->assertTrue($this->isTextPresent("0.00"));
    $this->click("link=Maintenance site");
    $this->waitForPageToLoad("30000");
    $this->click("link=Utilisateurs");
    $this->waitForPageToLoad("30000");
    $this->assertTrue($this->isTextPresent("asterix"));
    $this->assertTrue($this->isTextPresent("bon emine"));
    $this->assertTrue($this->isTextPresent("goudurix"));
  }
}
?>