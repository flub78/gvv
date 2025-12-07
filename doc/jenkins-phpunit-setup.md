# Exemple de configuration Jenkins pour GVV PHPUnit Tests

## Job Jenkins - Configuration Pipeline

```groovy
pipeline {
    agent any
    
    environment {
        // Credentials stockés dans Jenkins
        GVV_TEST_DB_PASSPHRASE = credentials('gvv-test-db-passphrase')
        MYSQL_PASSWORD = credentials('gvv-mysql-password')
        
        // Configuration base de données
        MYSQL_DATABASE = 'gvv2'
        MYSQL_USER = 'gvv_user'
        MYSQL_HOST = 'localhost'
    }
    
    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }
        
        stage('Setup Environment') {
            steps {
                sh '''
                    # Source PHP 7.4 environment
                    source setenv.sh
                    php --version
                '''
            }
        }
        
        stage('Initialize Test Database') {
            steps {
                echo 'Restoring encrypted test database...'
                sh './bin/init_test_database.sh'
            }
        }
        
        stage('Run PHPUnit Tests') {
            steps {
                echo 'Running all PHPUnit test suites...'
                sh '''
                    source setenv.sh
                    ./run-all-tests.sh
                '''
            }
        }
        
        stage('Publish Results') {
            steps {
                // Publier les résultats JUnit
                junit 'build/logs/*.xml'
                
                // Publier le rapport de couverture HTML
                publishHTML([
                    allowMissing: false,
                    alwaysLinkToLastBuild: true,
                    keepAll: true,
                    reportDir: 'build/coverage',
                    reportFiles: 'index.html',
                    reportName: 'PHPUnit Coverage Report'
                ])
            }
        }
    }
    
    post {
        always {
            // Nettoyer les fichiers temporaires
            sh 'rm -f /tmp/gvv_*.sql'
        }
        
        failure {
            // Notification en cas d'échec
            echo 'Tests failed! Check logs for details.'
        }
        
        success {
            echo 'All tests passed successfully!'
        }
    }
}
```

## Configuration des Credentials Jenkins

### 1. Passphrase de la base de test

**Type** : Secret text  
**ID** : `gvv-test-db-passphrase`  
**Description** : Passphrase GPG pour déchiffrer base_de_test.sql.gpg  

### 2. Mot de passe MySQL

**Type** : Secret text  
**ID** : `gvv-mysql-password`  
**Description** : Mot de passe pour l'utilisateur MySQL gvv_user  

## Configuration Freestyle (Alternative)

Si vous utilisez un job Freestyle au lieu de Pipeline :

### Build Environment
- ☑️ Use secret text(s) or file(s)
  - Variable: `GVV_TEST_DB_PASSPHRASE`
  - Credentials: `gvv-test-db-passphrase`
  - Variable: `MYSQL_PASSWORD`
  - Credentials: `gvv-mysql-password`

### Build Steps

**1. Execute shell - Setup Database**
```bash
export MYSQL_DATABASE=gvv2
export MYSQL_USER=gvv_user
export MYSQL_HOST=localhost

./bin/init_test_database.sh
```

**2. Execute shell - Run Tests**
```bash
source setenv.sh
./run-all-tests.sh
```

### Post-build Actions
- ☑️ Publish JUnit test result report
  - Test report XMLs: `build/logs/*.xml`
  
- ☑️ Publish HTML reports
  - HTML directory: `build/coverage`
  - Index page: `index.html`
  - Report title: `PHPUnit Coverage Report`

## Déclencheurs recommandés

### Pour développement actif
- ☑️ Poll SCM : `H/15 * * * *` (toutes les 15 minutes)
- ☑️ GitHub hook trigger for GITScm polling

### Pour production
- ☑️ Build périodiquement : `H 2 * * *` (tous les jours à 2h du matin)
- ☑️ Déclenché à distance (par webhook GitHub)

## Notifications

### Email
```groovy
post {
    failure {
        emailext (
            subject: "GVV Tests Failed - Build #${env.BUILD_NUMBER}",
            body: "Check console output at ${env.BUILD_URL}console",
            to: "dev-team@example.com"
        )
    }
}
```

### Slack (optionnel)
```groovy
post {
    failure {
        slackSend (
            color: 'danger',
            message: "GVV Tests Failed - <${env.BUILD_URL}|Build #${env.BUILD_NUMBER}>"
        )
    }
}
```

## Optimisations

### Cache Composer/Vendor
Si le projet utilisait Composer (actuellement non):
```groovy
stages {
    stage('Cache Dependencies') {
        steps {
            cache(maxCacheSize: 250, caches: [
                arbitraryFileCache(
                    path: 'vendor',
                    cacheValidityDecidingFile: 'composer.lock'
                )
            ]) {
                sh 'composer install --no-dev --optimize-autoloader'
            }
        }
    }
}
```

### Parallélisation des tests
Pour accélérer l'exécution (si tests indépendants):
```groovy
stage('Run Tests') {
    parallel {
        stage('Unit Tests') {
            steps {
                sh 'phpunit --configuration phpunit.xml'
            }
        }
        stage('Integration Tests') {
            steps {
                sh 'phpunit --configuration phpunit_integration.xml'
            }
        }
        stage('Controller Tests') {
            steps {
                sh 'phpunit --configuration phpunit_controller.xml'
            }
        }
    }
}
```

## Monitoring et métriques

### Tendances de tests
Jenkins génère automatiquement :
- Graphiques de tendance des tests
- Historique des taux de réussite
- Durée d'exécution par build

### Couverture de code
Le rapport HTML montre :
- Couverture globale du projet
- Couverture par fichier/classe
- Lignes non couvertes
- Complexité cyclomatique

## Troubleshooting Jenkins

### "Passphrase non définie"
Vérifier que le credential existe et est bien mappé dans `environment`.

### "Database already exists"
Normal - le script `init_test_database.sh` drop/create automatiquement.

### "GPG decryption failed"
- Vérifier que GPG est installé sur l'agent Jenkins
- Tester manuellement : `gpg --version`
- Vérifier que la passphrase est correcte

### Tests échouent après restauration
- Vérifier la version de migration dans la base
- Comparer avec `application/config/migration.php`
- Regénérer la base de test si obsolète

### Build très lent
- Activer le cache des dépendances si applicable
- Utiliser la parallélisation des tests
- Vérifier les ressources de l'agent Jenkins

## Ressources

- Documentation complète : `doc/test-database-encrypted.md`
- Script d'initialisation : `bin/init_test_database.sh`
- Tests PHPUnit : `./run-all-tests.sh`
- Génération base : `http://gvv.net/admin/generate_test_database`
