# Automatisation OpenFlyers avec cURL en PHP 7.4

Cette solution utilise uniquement cURL, qui est généralement disponible par défaut dans PHP, pour simuler les actions d'un navigateur.

## Classe d'automation OpenFlyers

```php
<?php
class OpenFlyersAutomation {
    private $baseUrl;
    private $username;
    private $password;
    private $cookieJar;
    private $curlHandle;
    
    public function __construct($baseUrl, $username, $password) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->username = $username;
        $this->password = $password;
        
        // Créer un fichier temporaire pour les cookies
        $this->cookieJar = tempnam(sys_get_temp_dir(), 'openflyers_cookies_');
    }
    
    private function initCurl() {
        $this->curlHandle = curl_init();
        curl_setopt_array($this->curlHandle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR => $this->cookieJar,
            CURLOPT_COOKIEFILE => $this->cookieJar,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HEADER => true
        ]);
    }
    
    private function request($url, $method = 'GET', $data = null, $headers = []) {
        if (!$this->curlHandle) {
            $this->initCurl();
        }
        
        curl_setopt($this->curlHandle, CURLOPT_URL, $url);
        curl_setopt($this->curlHandle, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($data) {
            curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, $data);
        }
        
        // Ajouter les en-têtes par défaut
        $defaultHeaders = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control: no-cache',
            'Pragma: no-cache'
        ];
        
        curl_setopt($this->curlHandle, CURLOPT_HTTPHEADER, array_merge($defaultHeaders, $headers));
        
        $response = curl_exec($this->curlHandle);
        $httpCode = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($this->curlHandle, CURLINFO_HEADER_SIZE);
        
        return [
            'headers' => substr($response, 0, $headerSize),
            'body' => substr($response, $headerSize),
            'httpCode' => $httpCode
        ];
    }
    
    public function login() {
        // Première requête pour obtenir le token CSRF
        $loginPage = $this->request($this->baseUrl . '/login');
        if ($loginPage['httpCode'] !== 200) {
            throw new Exception("Impossible d'accéder à la page de login");
        }
        
        // Extraire le token CSRF (adapter selon le HTML d'OpenFlyers)
        preg_match('/<input[^>]*name="csrf_token"[^>]*value="([^"]*)"/', $loginPage['body'], $matches);
        $csrfToken = isset($matches[1]) ? $matches[1] : '';
        
        if (empty($csrfToken)) {
            throw new Exception("Token CSRF non trouvé");
        }
        
        // Préparer les données de login
        $loginData = http_build_query([
            'csrf_token' => $csrfToken,
            'username' => $this->username,
            'password' => $this->password
        ]);
        
        // Effectuer le login
        $loginResponse = $this->request(
            $this->baseUrl . '/login',
            'POST',
            $loginData,
            ['Content-Type: application/x-www-form-urlencoded']
        );
        
        if ($loginResponse['httpCode'] !== 302 && $loginResponse['httpCode'] !== 200) {
            throw new Exception("Échec de la connexion");
        }
        
        // Vérifier si on est bien connecté
        $dashboardCheck = $this->request($this->baseUrl . '/dashboard');
        if (strpos($dashboardCheck['body'], 'logout') === false) {
            throw new Exception("Vérification de connexion échouée");
        }
        
        return true;
    }
    
    public function updatePilotAuthorization($pilotId, $canBook) {
        try {
            // Accéder à la page du pilote
            $pilotPage = $this->request($this->baseUrl . '/admin/pilots/' . $pilotId);
            if ($pilotPage['httpCode'] !== 200) {
                throw new Exception("Impossible d'accéder à la page du pilote");
            }
            
            // Extraire le token CSRF de la page
            preg_match('/<input[^>]*name="csrf_token"[^>]*value="([^"]*)"/', $pilotPage['body'], $matches);
            $csrfToken = isset($matches[1]) ? $matches[1] : '';
            
            // Extraire l'état actuel des droits
            preg_match('/<input[^>]*id="booking_rights"[^>]*checked/', $pilotPage['body'], $currentRights);
            $currentlyCanBook = !empty($currentRights);
            
            // Si l'état actuel est différent de l'état désiré
            if ($currentlyCanBook !== $canBook) {
                // Préparer les données pour la mise à jour
                $updateData = http_build_query([
                    'csrf_token' => $csrfToken,
                    'booking_rights' => $canBook ? '1' : '0',
                    'action' => 'update_rights'
                ]);
                
                // Envoyer la mise à jour
                $updateResponse = $this->request(
                    $this->baseUrl . '/admin/pilots/' . $pilotId,
                    'POST',
                    $updateData,
                    ['Content-Type: application/x-www-form-urlencoded']
                );
                
                if ($updateResponse['httpCode'] !== 302 && $updateResponse['httpCode'] !== 200) {
                    throw new Exception("Échec de la mise à jour des droits");
                }
                
                // Vérifier que la mise à jour a bien été effectuée
                $checkPage = $this->request($this->baseUrl . '/admin/pilots/' . $pilotId);
                preg_match('/<input[^>]*id="booking_rights"[^>]*checked/', $checkPage['body'], $newRights);
                $newCanBook = !empty($newRights);
                
                if ($newCanBook !== $canBook) {
                    throw new Exception("La mise à jour n'a pas été appliquée correctement");
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Erreur lors de la mise à jour des droits du pilote {$pilotId}: " . $e->getMessage());
            return false;
        }
    }
    
    public function __destruct() {
        if ($this->curlHandle) {
            curl_close($this->curlHandle);
        }
        if (file_exists($this->cookieJar)) {
            unlink($this->cookieJar);
        }
    }
}
```

## Utilisation dans votre application

```php
<?php
// Dans votre contrôleur de gestion des comptes
function updatePilotAccount($pilotId, $newBalance) {
    // Mise à jour du solde dans la base de données
    $query = "UPDATE pilots SET balance = ? WHERE id = ?";
    $success = $this->db->query($query, array($newBalance, $pilotId));
    
    if ($success) {
        // Déterminer si le pilote peut réserver
        $canBook = $newBalance >= 0;
        
        // Configuration de l'automation
        $automation = new OpenFlyersAutomation(
            'https://votre-instance.openflyers.fr',
            'admin@example.com',
            'motdepasse'
        );
        
        try {
            // Connexion et mise à jour des droits
            if ($automation->login()) {
                return $automation->updatePilotAuthorization($pilotId, $canBook);
            }
        } catch (Exception $e) {
            error_log("Erreur OpenFlyers: " . $e->getMessage());
            // Envoi d'une notification aux administrateurs
            mail(
                'admin@votreclub.fr',
                'Erreur automation OpenFlyers',
                "Erreur lors de la mise à jour des droits du pilote {$pilotId}: " . $e->getMessage()
            );
        }
    }
    
    return false;
}
```

## Système de logging personnalisé

```php
<?php
class OpenFlyersLogger {
    private $logFile;
    
    public function __construct($logFile = null) {
        $this->logFile = $logFile ?: dirname(__FILE__) . '/openflyers.log';
    }
    
    public function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = sprintf("[%s] [%s] %s\n", $timestamp, $level, $message);
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }
}
```

## Bonnes pratiques et considérations

1. **Gestion des erreurs**
   - Implémentez un système de retry simple
   - Gardez une trace des erreurs dans un fichier de log dédié
   - Envoyez des notifications en cas d'échec

2. **Sécurité**
   - Stockez les identifiants dans un fichier de configuration
   - Utilisez HTTPS pour toutes les requêtes
   - Nettoyez le fichier de cookies après utilisation

3. **Maintenance**
   - Documentez les sélecteurs HTML utilisés
   - Mettez en place des vérifications régulières
   - Gardez une trace des modifications effectuées

4. **Performance**
   - La solution est synchrone, donc prévoyez un timeout approprié
   - Considérez l'ajout d'un système de queue simple si nécessaire
