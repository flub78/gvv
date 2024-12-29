# Automatisation OpenFlyers avec Playwright PHP

## Avantages de Playwright par rapport à Selenium/ChromeDriver
- Gestion automatique des versions des navigateurs
- Installation simple avec tout inclus
- Meilleure performance et stabilité
- Support moderne des événements web
- Fonctionne avec Chrome, Firefox et WebKit

## Installation

```bash
composer require microsoft/playwright-php
# Installation des navigateurs et des dépendances
./vendor/bin/playwright install
```

## Classe d'automation OpenFlyers

```php
<?php
namespace App\Services;

use Playwright\Playwright;

class OpenFlyersAutomation {
    private $browser;
    private $context;
    private $page;
    private $baseUrl;
    private $adminUsername;
    private $adminPassword;

    public function __construct($baseUrl, $adminUsername, $adminPassword) {
        $this->baseUrl = $baseUrl;
        $this->adminUsername = $adminUsername;
        $this->adminPassword = $adminPassword;
    }

    public async function init() {
        try {
            // Initialisation de Playwright
            $playwright = await Playwright::create();
            
            // Lancement du navigateur en mode headless
            $this->browser = await $playwright->chromium()->launch([
                'headless' => true,
                'args' => ['--no-sandbox']
            ]);
            
            // Création d'un nouveau contexte
            $this->context = await $this->browser->newContext([
                'userAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'viewport' => [
                    'width' => 1920,
                    'height' => 1080
                ]
            ]);
            
            // Activation des logs de console
            $this->context->on('console', function ($msg) {
                error_log("Console: " . $msg->text());
            });
            
            $this->page = await $this->context->newPage();
            
            // Configuration des timeouts
            $this->page->setDefaultTimeout(30000); // 30 secondes
            $this->page->setDefaultNavigationTimeout(30000);
            
            return true;
        } catch (\Exception $e) {
            error_log("Erreur d'initialisation Playwright: " . $e->getMessage());
            throw $e;
        }
    }

    public async function login() {
        try {
            await $this->page->goto($this->baseUrl . '/login');
            
            // Attente et remplissage du formulaire
            await $this->page->fill('#username', $this->adminUsername);
            await $this->page->fill('#password', $this->adminPassword);
            
            // Click avec retry automatique
            await $this->page->click('button[type="submit"]');
            
            // Attente de la redirection
            await $this->page->waitForSelector('.dashboard');
            
            return true;
        } catch (\Exception $e) {
            error_log("Erreur de login: " . $e->getMessage());
            throw $e;
        }
    }

    public async function updatePilotAuthorization($pilotId, $canBook) {
        try {
            // Navigation avec retry automatique
            await $this->page->goto($this->baseUrl . '/admin/pilots/' . $pilotId);
            
            // Attente de l'élément avec retry automatique
            const checkbox = await $this->page->waitForSelector('#booking-rights');
            
            // Vérification de l'état actuel
            $isChecked = await $checkbox->isChecked();
            
            if ($canBook !== $isChecked) {
                await $checkbox->click();
                
                // Click sur le bouton de sauvegarde
                await $this->page->click('button[type="submit"]');
                
                // Attente de la confirmation
                await $this->page->waitForSelector('.alert-success');
            }
            
            return true;
        } catch (\Exception $e) {
            error_log("Erreur mise à jour pilote {$pilotId}: " . $e->getMessage());
            throw $e;
        }
    }

    public async function close() {
        if ($this->browser) {
            await $this->browser->close();
        }
    }
}
```

## Utilisation dans CodeIgniter avec gestion asynchrone

```php
<?php
// Créez un service dédié pour la gestion asynchrone
class OpenFlyersService {
    private $automation;
    
    public function __construct() {
        $this->automation = new OpenFlyersAutomation(
            getenv('OPENFLYERS_URL'),
            getenv('OPENFLYERS_ADMIN'),
            getenv('OPENFLYERS_PASSWORD')
        );
    }
    
    public async function updatePilotRights($pilotId, $canBook) {
        try {
            await $this->automation->init();
            await $this->automation->login();
            $result = await $this->automation->updatePilotAuthorization($pilotId, $canBook);
            await $this->automation->close();
            return $result;
        } catch (\Exception $e) {
            log_message('error', 'Erreur OpenFlyers: ' . $e->getMessage());
            return false;
        }
    }
}

// Dans votre contrôleur
public function updatePilotAccount($pilotId, $newBalance) {
    // Mise à jour du solde
    $this->db->where('id', $pilotId);
    $this->db->update('pilots', ['balance' => $newBalance]);
    
    $canBook = $newBalance >= 0;
    
    // Création d'une tâche asynchrone
    $job = new UpdateOpenFlyersRightsJob([
        'pilotId' => $pilotId,
        'canBook' => $canBook
    ]);
    
    // Ajout à la file d'attente
    $this->queue->push($job);
    
    return true;
}
```

## Configuration du worker pour les tâches asynchrones

```php
<?php
// worker.php
require 'vendor/autoload.php';

class UpdateOpenFlyersRightsJob {
    public function handle($data) {
        $service = new OpenFlyersService();
        return $service->updatePilotRights($data['pilotId'], $data['canBook']);
    }
}

// Démarrage du worker
$worker = new Worker();
$worker->start();
```

## Système de surveillance

```php
<?php
class OpenFlyersMonitor {
    public function checkAutomationHealth() {
        try {
            $automation = new OpenFlyersAutomation(
                getenv('OPENFLYERS_URL'),
                getenv('OPENFLYERS_ADMIN'),
                getenv('OPENFLYERS_PASSWORD')
            );
            
            await $automation->init();
            await $automation->login();
            await $automation->close();
            
            return true;
        } catch (\Exception $e) {
            // Envoi d'une alerte aux administrateurs
            $this->alertAdmins('Erreur automation OpenFlyers: ' . $e->getMessage());
            return false;
        }
    }
}
```

## Bonnes pratiques supplémentaires

1. **Gestion des sessions**
   - Utilisez le state storage de Playwright pour sauvegarder/restaurer les sessions
   - Réutilisez les sessions quand possible pour réduire le nombre de connexions

2. **Gestion des erreurs**
   - Implémentez un système de retry avec backoff exponentiel
   - Enregistrez les screenshots en cas d'erreur
   - Mettez en place des alertes par email/Slack

3. **Maintenance**
   - Exécutez des tests de santé réguliers
   - Surveillez les temps d'exécution
   - Gardez un historique des actions effectuées

4. **Sécurité**
   - Utilisez un coffre-fort pour les credentials
   - Limitez les IP autorisées
   - Rotez régulièrement les mots de passe
