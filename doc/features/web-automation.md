# Automatisation des autorisations OpenFlyers avec PHP et Selenium

## Installation des dépendances

```bash
composer require php-webdriver/webdriver
```

## Configuration de ChromeDriver

1. Téléchargez ChromeDriver depuis https://sites.google.com/chromium.org/driver/
2. Décompressez-le et placez-le dans un répertoire accessible
3. Assurez-vous qu'il est exécutable : `chmod +x chromedriver`

## Classe d'automation OpenFlyers

```php
<?php
namespace App\Services;

use Facebook\WebDriver\Chrome\ChromeDriver;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class OpenFlyersAutomation {
    private $driver;
    private $baseUrl;
    private $adminUsername;
    private $adminPassword;

    public function __construct($baseUrl, $adminUsername, $adminPassword) {
        $this->baseUrl = $baseUrl;
        $this->adminUsername = $adminUsername;
        $this->adminPassword = $adminPassword;
    }

    public function init() {
        // Configuration de Chrome en mode headless
        $options = new ChromeOptions();
        $options->addArguments(['--headless', '--disable-gpu', '--no-sandbox']);
        
        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
        
        // Initialisation du driver
        $this->driver = ChromeDriver::start($capabilities);
    }

    public function login() {
        $this->driver->get($this->baseUrl . '/login');
        
        // Attendre que le formulaire de connexion soit chargé
        $this->driver->wait()->until(
            WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('username'))
        );
        
        // Remplir le formulaire de connexion
        $this->driver->findElement(WebDriverBy::id('username'))->sendKeys($this->adminUsername);
        $this->driver->findElement(WebDriverBy::id('password'))->sendKeys($this->adminPassword);
        $this->driver->findElement(WebDriverBy::cssSelector('button[type="submit"]'))->click();
        
        // Attendre que la page d'accueil soit chargée
        $this->driver->wait()->until(
            WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('.dashboard'))
        );
    }

    public function updatePilotAuthorization($pilotId, $canBook) {
        try {
            // Naviguer vers la page de gestion du pilote
            $this->driver->get($this->baseUrl . '/admin/pilots/' . $pilotId);
            
            // Attendre que la page soit chargée
            $this->driver->wait()->until(
                WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('booking-rights'))
            );
            
            // Modifier les droits de réservation
            $checkbox = $this->driver->findElement(WebDriverBy::id('booking-rights'));
            $isChecked = $checkbox->isSelected();
            
            if ($canBook && !$isChecked || !$canBook && $isChecked) {
                $checkbox->click();
                
                // Sauvegarder les modifications
                $this->driver->findElement(WebDriverBy::cssSelector('button[type="submit"]'))->click();
                
                // Attendre la confirmation
                $this->driver->wait()->until(
                    WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('.alert-success'))
                );
            }
            
            return true;
        } catch (\Exception $e) {
            // Log l'erreur
            error_log("Erreur lors de la mise à jour des droits du pilote {$pilotId}: " . $e->getMessage());
            return false;
        }
    }

    public function close() {
        $this->driver->quit();
    }
}
```

## Utilisation dans votre application CodeIgniter

```php
<?php
// Dans votre contrôleur ou service de gestion des comptes

public function updatePilotAccount($pilotId, $newBalance) {
    // Mise à jour du solde dans votre base de données
    $this->db->where('id', $pilotId);
    $this->db->update('pilots', ['balance' => $newBalance]);
    
    // Déterminer si le pilote peut réserver en fonction du solde
    $canBook = $newBalance >= 0;
    
    // Mettre à jour les droits sur OpenFlyers
    $automation = new OpenFlyersAutomation(
        'https://votre-instance.openflyers.fr',
        'admin@example.com',
        'motdepasse'
    );
    
    try {
        $automation->init();
        $automation->login();
        $success = $automation->updatePilotAuthorization($pilotId, $canBook);
        $automation->close();
        
        return $success;
    } catch (\Exception $e) {
        log_message('error', 'Erreur OpenFlyers: ' . $e->getMessage());
        return false;
    }
}
```

## Bonnes pratiques et considérations

1. **Sécurité**
   - Stockez les identifiants OpenFlyers dans votre configuration
   - Utilisez des variables d'environnement
   - Limitez l'accès à cette fonctionnalité aux administrateurs

2. **Performance**
   - La manipulation via Selenium prend du temps
   - Envisagez d'exécuter les mises à jour en arrière-plan via une file d'attente
   - Mettez en cache la session Selenium si possible

3. **Maintenance**
   - Surveillez les changements d'interface OpenFlyers
   - Ajoutez des logs détaillés
   - Mettez en place des notifications en cas d'échec

4. **Tests**
   - Créez des tests automatisés
   - Testez avec différents scénarios
   - Vérifiez régulièrement le bon fonctionnement
