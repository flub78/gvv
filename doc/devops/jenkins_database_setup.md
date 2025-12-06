# Installation Base de Données sur Serveur Jenkins

**Objectif** : Installer MySQL/MariaDB, phpMyAdmin et la base de données GVV sur le serveur Jenkins pour permettre l'exécution des tests d'intégration et MySQL.

**Date de création** : 2025-12-05
**Référence** : Plan DevOps Phase 1 (`doc/devops/ci_cd_plan.md`)

---

## Vue d'ensemble

Cette procédure installe :
- **MySQL 5.x / MariaDB 10.x** (compatible MySQL 5.x)
- **phpMyAdmin** pour administration web
- **Base de données GVV** (`gvv2`) avec schéma et données de test
- **Utilisateur de base de données** (`gvv_user`) avec privilèges appropriés

**Durée estimée** : 30-45 minutes

---

## Prérequis

- Accès SSH au serveur Jenkins avec droits sudo
- Serveur sous Debian/Ubuntu (adapté pour autres distributions)
- PHP 7.4 déjà installé (requis pour GVV)
- Au moins 500 Mo d'espace disque disponible

**Vérifications préalables** :
```bash
# Se connecter au serveur Jenkins
ssh jenkins@votre-serveur-jenkins

# Vérifier PHP
php --version  # Doit afficher PHP 7.4.x

# Vérifier l'espace disque
df -h /var/lib/mysql
```

---

## Étape 1 : Installation de MySQL/MariaDB

### 1.1 Installer le serveur de base de données

```bash
# Mettre à jour les paquets
sudo apt update

# Installer MariaDB (compatible MySQL 5.x)
sudo apt install -y mariadb-server mariadb-client

# Vérifier l'installation
sudo systemctl status mariadb

# Activer le démarrage automatique
sudo systemctl enable mariadb
```

**Résultat attendu** :
```
● mariadb.service - MariaDB 10.x database server
   Active: active (running)
```

### 1.2 Sécuriser l'installation MySQL

```bash
# Exécuter le script de sécurisation
sudo mysql_secure_installation
```

**Réponses recommandées** :
```
Enter current password for root: [Entrée - pas de mot de passe par défaut]
Switch to unix_socket authentication: N
Change the root password: Y
  New password: [votre_mot_de_passe_root_mysql]
  Re-enter new password: [votre_mot_de_passe_root_mysql]
Remove anonymous users: Y
Disallow root login remotely: Y
Remove test database: Y
Reload privilege tables: Y
```

**Important** : Notez le mot de passe root MySQL, vous en aurez besoin.

### 1.3 Vérifier la connexion MySQL

```bash
# Tester la connexion
sudo mysql -u root -p
# Entrer le mot de passe root défini ci-dessus
```

Si vous êtes dans le prompt `MariaDB [(none)]>`, c'est bon ! Tapez `exit` pour sortir.

---

## Étape 2 : Créer la base de données et l'utilisateur GVV

### 2.1 Créer la base de données et l'utilisateur

Connectez-vous à MySQL en tant que root :
```bash
sudo mysql -u root -p
```

Exécutez les commandes SQL suivantes :
```sql
-- Créer la base de données
CREATE DATABASE IF NOT EXISTS gvv2 CHARACTER SET utf8 COLLATE utf8_general_ci;

-- Créer l'utilisateur avec mot de passe
CREATE USER IF NOT EXISTS 'gvv_user'@'localhost' IDENTIFIED BY 'lfoyfgbj';

-- Accorder tous les privilèges sur la base gvv2
GRANT ALL PRIVILEGES ON gvv2.* TO 'gvv_user'@'localhost';

-- Appliquer les changements
FLUSH PRIVILEGES;

-- Vérifier la création
SELECT User, Host FROM mysql.user WHERE User = 'gvv_user';
SHOW DATABASES LIKE 'gvv2';

-- Quitter MySQL
exit
```

**Résultat attendu** :
```
+-----------+-----------+
| User      | Host      |
+-----------+-----------+
| gvv_user  | localhost |
+-----------+-----------+

+----------------+
| Database       |
+----------------+
| gvv2           |
+----------------+
```

### 2.2 Tester la connexion avec l'utilisateur GVV

```bash
# Tester la connexion en tant que gvv_user
mysql -u gvv_user -plfoyfgbj -h localhost gvv2

# Vous devriez voir le prompt :
# MariaDB [gvv2]>

# Tapez exit pour sortir
exit
```

**Important** : Si cette connexion échoue, le problème est ici. Vérifiez :
- Le mot de passe est correct
- L'utilisateur a été créé avec `@'localhost'`
- Les privilèges ont été accordés

---

## Étape 3 : Importer le schéma de base GVV

### 3.1 Récupérer les fichiers SQL depuis le dépôt

```bash
# Se placer dans le workspace Jenkins (adapter le chemin)
cd /var/lib/jenkins/workspace/GVV-PHPUnit-Tests

# Ou cloner le dépôt si ce n'est pas déjà fait
# git clone https://github.com/votre-user/gvv.git /tmp/gvv
# cd /tmp/gvv
```

### 3.2 Importer le schéma initial

```bash
# Importer le schéma de base
mysql -u gvv_user -plfoyfgbj -h localhost gvv2 < install/gvv_init.sql

# Vérifier l'import
mysql -u gvv_user -plfoyfgbj -h localhost gvv2 -e "SHOW TABLES;"
```

**Résultat attendu** : Liste des tables créées (achats, alarmes, avions, etc.)

### 3.3 Exécuter les migrations

Les migrations sont gérées par CodeIgniter. Deux options :

**Option A : Via script PHP (recommandé)**

Créez un script temporaire pour exécuter les migrations :
```bash
# Créer le script
cat > /tmp/run_migrations.php << 'EOF'
<?php
// Script temporaire pour exécuter les migrations
define('BASEPATH', true);
require_once('/var/lib/jenkins/workspace/GVV-PHPUnit-Tests/index.php');

// Charger le contrôleur de migration
$CI =& get_instance();
$CI->load->library('migration');

// Exécuter les migrations
if ($CI->migration->current() === FALSE) {
    echo "Migration failed: " . $CI->migration->error_string() . "\n";
    exit(1);
} else {
    echo "Migrations executed successfully!\n";
    echo "Current version: " . $CI->migration->current() . "\n";
    exit(0);
}
EOF

# Exécuter le script
cd /var/lib/jenkins/workspace/GVV-PHPUnit-Tests
source setenv.sh
php /tmp/run_migrations.php
```

**Option B : Via l'interface web (si accessible)**

Si vous avez configuré un accès web temporaire :
```
http://votre-jenkins:8080/migrate
```

### 3.4 Vérifier la version de migration

```bash
mysql -u gvv_user -plfoyfgbj -h localhost gvv2 -e \
  "SELECT * FROM migrations ORDER BY version DESC LIMIT 1;"
```

**Résultat attendu** : Version 55 (ou la version actuelle dans `application/config/migration.php`)

---

## Étape 4 : Créer les utilisateurs de test

### 4.1 Exécuter le script de création des utilisateurs

```bash
# Se placer dans le dépôt
cd /var/lib/jenkins/workspace/GVV-PHPUnit-Tests

# Rendre le script exécutable
chmod +x bin/create_test_users.sh

# Exécuter le script
./bin/create_test_users.sh
```

**Résultat attendu** :
```
=========================================
Creating test users for GVV system
=========================================

Using section ID: 1
Creating user: testuser
✓ User testuser created successfully
Creating user: testadmin
✓ User testadmin created successfully
...

All test users have password: password
```

### 4.2 Vérifier la création des utilisateurs

```bash
mysql -u gvv_user -plfoyfgbj -h localhost gvv2 -e \
  "SELECT id, username, email FROM users WHERE username LIKE 'test%';"
```

**Résultat attendu** : 6 utilisateurs (testuser, testadmin, testplanchiste, testca, testbureau, testtresorier)

---

## Étape 5 : Installation de phpMyAdmin

### 5.1 Installer phpMyAdmin

```bash
# Installer phpMyAdmin
sudo apt install -y phpmyadmin

# Durant l'installation :
# - Serveur web à reconfigurer : apache2 (ou nginx selon votre config)
# - Configurer la base de données pour phpmyadmin avec dbconfig-common : Oui
# - Mot de passe de l'application MySQL pour phpmyadmin : [choisir un mot de passe]
```

### 5.2 Configurer phpMyAdmin pour Apache

Si vous utilisez Apache :
```bash
# Activer la configuration phpMyAdmin
sudo ln -s /etc/phpmyadmin/apache.conf /etc/apache2/conf-available/phpmyadmin.conf
sudo a2enconf phpmyadmin

# Redémarrer Apache
sudo systemctl restart apache2
```

Si vous utilisez Nginx :
```bash
# Créer un lien symbolique vers la racine web
sudo ln -s /usr/share/phpmyadmin /var/www/html/phpmyadmin

# Ajuster les permissions
sudo chown -R www-data:www-data /usr/share/phpmyadmin
```

### 5.3 Sécuriser phpMyAdmin (recommandé)

**Créer une protection par mot de passe Apache** :
```bash
# Créer un fichier de mot de passe
sudo htpasswd -c /etc/phpmyadmin/.htpasswd admin
# Entrer le mot de passe souhaité

# Éditer la configuration Apache de phpMyAdmin
sudo nano /etc/apache2/conf-available/phpmyadmin.conf
```

Ajoutez ces lignes dans la section `<Directory>` :
```apache
<Directory /usr/share/phpmyadmin>
    AuthType Basic
    AuthName "Restricted Access"
    AuthUserFile /etc/phpmyadmin/.htpasswd
    Require valid-user
</Directory>
```

```bash
# Redémarrer Apache
sudo systemctl restart apache2
```

### 5.4 Accéder à phpMyAdmin

Ouvrez votre navigateur :
```
http://votre-serveur-jenkins/phpmyadmin
```

**Connexion** :
- Utilisateur : `gvv_user`
- Mot de passe : `lfoyfgbj`
- Serveur : `localhost`

Vous devriez voir la base de données `gvv2` dans la liste.

---

## Étape 6 : Configurer la base de données pour les tests

### 6.1 Vérifier la configuration de base de données

```bash
cd /var/lib/jenkins/workspace/GVV-PHPUnit-Tests

# Vérifier que le fichier database.php existe
ls -l application/config/database.php

# Si le fichier n'existe pas, le créer depuis l'exemple
if [ ! -f application/config/database.php ]; then
    cp application/config/database.example.php application/config/database.php
fi

# Vérifier le contenu
cat application/config/database.php | grep -A 5 "hostname\|username\|password\|database"
```

**Contenu attendu** :
```php
$db['default']['hostname'] = 'localhost';
$db['default']['username'] = 'gvv_user';
$db['default']['password'] = 'lfoyfgbj';
$db['default']['database'] = 'gvv2';
```

### 6.2 Tester la connexion depuis PHP

```bash
# Créer un script de test
cat > /tmp/test_db_connection.php << 'EOF'
<?php
$hostname = 'localhost';
$username = 'gvv_user';
$password = 'lfoyfgbj';
$database = 'gvv2';

echo "Testing database connection...\n";
echo "Host: $hostname\n";
echo "User: $username\n";
echo "Database: $database\n\n";

$conn = new mysqli($hostname, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
} else {
    echo "✓ Connection successful!\n";

    // Test query
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "✓ Query successful! Found " . $row['count'] . " users.\n";
    }

    $conn->close();
}
EOF

# Exécuter le test
source setenv.sh
php /tmp/test_db_connection.php
```

**Résultat attendu** :
```
Testing database connection...
Host: localhost
User: gvv_user
Database: gvv2

✓ Connection successful!
✓ Query successful! Found 6 users.
```

---

## Étape 7 : Tester les suites de tests

### 7.1 Tester les tests d'intégration

```bash
cd /var/lib/jenkins/workspace/GVV-PHPUnit-Tests
source setenv.sh

# Exécuter les tests d'intégration
/usr/bin/php7.4 vendor/bin/phpunit --configuration phpunit_integration.xml
```

**Résultat attendu** : Tests exécutés avec succès (pas d'erreur de connexion)

### 7.2 Tester les tests MySQL

```bash
# Exécuter les tests MySQL
/usr/bin/php7.4 vendor/bin/phpunit --configuration phpunit_mysql.xml
```

**Résultat attendu** : Tests exécutés avec succès

### 7.3 Exécuter tous les tests

```bash
# Exécuter la suite complète
./run-all-tests.sh
```

**Résultat attendu** :
```
═══════════════════════════════════════════════════
  Final Summary
═══════════════════════════════════════════════════

Suite Name                     |  Tests | Passed | Failed |  Skipped
───────────────────────────────────────────────────────────────────────────
Unit Tests                     |    184 |    184 |      0 |        0
URL Helper Tests               |      8 |      8 |      0 |        0
Integration Tests              |    XXX |    XXX |      0 |        0
Enhanced CI Tests              |     63 |     63 |      0 |        0
Controller Tests               |      8 |      8 |      0 |        0
MySQL Tests                    |    XXX |    XXX |      0 |        0
───────────────────────────────────────────────────────────────────────────
TOTAL                          |    XXX |    XXX |      0 |        0

✓ All test suites passed
```

---

## Dépannage

### Problème : "Access denied for user 'gvv_user'@'localhost'"

**Diagnostic** :
```bash
# Vérifier l'utilisateur MySQL
sudo mysql -u root -p -e "SELECT User, Host FROM mysql.user WHERE User='gvv_user';"

# Vérifier les privilèges
sudo mysql -u root -p -e "SHOW GRANTS FOR 'gvv_user'@'localhost';"
```

**Solution 1** : Recréer l'utilisateur
```bash
sudo mysql -u root -p
```
```sql
DROP USER IF EXISTS 'gvv_user'@'localhost';
CREATE USER 'gvv_user'@'localhost' IDENTIFIED BY 'lfoyfgbj';
GRANT ALL PRIVILEGES ON gvv2.* TO 'gvv_user'@'localhost';
FLUSH PRIVILEGES;
exit
```

**Solution 2** : Vérifier la méthode d'authentification
```bash
sudo mysql -u root -p -e \
  "SELECT User, Host, plugin FROM mysql.user WHERE User='gvv_user';"
```

Si le plugin est `unix_socket`, changez-le :
```sql
ALTER USER 'gvv_user'@'localhost' IDENTIFIED WITH mysql_native_password BY 'lfoyfgbj';
FLUSH PRIVILEGES;
```

### Problème : "Can't connect to MySQL server on 'localhost'"

**Solution** :
```bash
# Vérifier que MySQL est démarré
sudo systemctl status mariadb

# Si arrêté, le démarrer
sudo systemctl start mariadb

# Vérifier les logs
sudo tail -f /var/log/mysql/error.log
```

### Problème : phpMyAdmin "mysqli_real_connect(): (HY000/2002): No such file or directory"

**Solution** :
```bash
# Éditer la configuration phpMyAdmin
sudo nano /etc/phpmyadmin/config.inc.php

# Modifier la ligne du socket :
$cfg['Servers'][$i]['socket'] = '/var/run/mysqld/mysqld.sock';

# Vérifier le chemin du socket
ls -l /var/run/mysqld/mysqld.sock
```

### Problème : Tests échouent avec "Unknown database 'gvv2'"

**Solution** :
```bash
# Recréer la base
mysql -u gvv_user -plfoyfgbj -e "CREATE DATABASE IF NOT EXISTS gvv2;"

# Réimporter le schéma
mysql -u gvv_user -plfoyfgbj gvv2 < install/gvv_init.sql
```

### Problème : Migrations échouent

**Solution** :
```bash
# Vérifier la table migrations
mysql -u gvv_user -plfoyfgbj gvv2 -e "SELECT * FROM migrations;"

# Si la table n'existe pas, la créer
mysql -u gvv_user -plfoyfgbj gvv2 -e \
  "CREATE TABLE migrations (version BIGINT NOT NULL);"

# Insérer la version initiale
mysql -u gvv_user -plfoyfgbj gvv2 -e \
  "INSERT INTO migrations (version) VALUES (0);"
```

---

## Liste de vérification finale

Avant de marquer l'installation comme terminée, vérifiez :

- [ ] MySQL/MariaDB démarre automatiquement : `sudo systemctl status mariadb`
- [ ] Connexion root MySQL fonctionne : `sudo mysql -u root -p`
- [ ] Base de données `gvv2` existe : `mysql -u gvv_user -plfoyfgbj -e "SHOW DATABASES;"`
- [ ] Utilisateur `gvv_user` peut se connecter : `mysql -u gvv_user -plfoyfgbj gvv2`
- [ ] Tables GVV existent : `mysql -u gvv_user -plfoyfgbj gvv2 -e "SHOW TABLES;"`
- [ ] Migrations à jour : `mysql -u gvv_user -plfoyfgbj gvv2 -e "SELECT * FROM migrations;"`
- [ ] Utilisateurs de test créés : `mysql -u gvv_user -plfoyfgbj gvv2 -e "SELECT username FROM users WHERE username LIKE 'test%';"`
- [ ] phpMyAdmin accessible : http://votre-serveur/phpmyadmin
- [ ] Tests d'intégration passent : `./run-all-tests.sh`
- [ ] Job Jenkins `GVV-PHPUnit-Tests` passe : Build Now dans Jenkins

---

## Commandes de maintenance

### Sauvegarder la base de données

```bash
# Sauvegarde complète
mysqldump -u gvv_user -plfoyfgbj gvv2 > /tmp/gvv2_backup_$(date +%Y%m%d_%H%M%S).sql

# Sauvegarde du schéma seulement
mysqldump -u gvv_user -plfoyfgbj --no-data gvv2 > /tmp/gvv2_schema.sql
```

### Restaurer la base de données

```bash
# Restaurer depuis une sauvegarde
mysql -u gvv_user -plfoyfgbj gvv2 < /tmp/gvv2_backup_20251205_120000.sql
```

### Réinitialiser la base de données

```bash
# ATTENTION : Supprime toutes les données !
mysql -u gvv_user -plfoyfgbj -e "DROP DATABASE IF EXISTS gvv2;"
mysql -u gvv_user -plfoyfgbj -e "CREATE DATABASE gvv2;"
mysql -u gvv_user -plfoyfgbj gvv2 < install/gvv_init.sql
./bin/create_test_users.sh
```

### Vérifier la santé de MySQL

```bash
# Statut du serveur
sudo systemctl status mariadb

# Utilisation de l'espace disque
sudo du -sh /var/lib/mysql/gvv2

# Nombre de connexions actives
mysql -u gvv_user -plfoyfgbj -e "SHOW PROCESSLIST;"

# Variables importantes
mysql -u gvv_user -plfoyfgbj -e "SHOW VARIABLES LIKE 'max_connections';"
```

---

## Sécurité

### Recommandations de production

Si ce serveur Jenkins est accessible depuis Internet :

1. **Changer les mots de passe** :
   ```bash
   # Mot de passe root MySQL
   sudo mysql -u root -p
   ALTER USER 'root'@'localhost' IDENTIFIED BY 'nouveau_mot_de_passe_fort';

   # Mot de passe gvv_user
   ALTER USER 'gvv_user'@'localhost' IDENTIFIED BY 'nouveau_mot_de_passe_fort';
   FLUSH PRIVILEGES;
   ```

2. **Limiter l'accès phpMyAdmin** :
   - Ajouter authentification HTTP (déjà fait dans Étape 5.3)
   - Restreindre par IP si possible
   - Utiliser HTTPS

3. **Configurer le pare-feu** :
   ```bash
   # N'autoriser MySQL que localement (pas d'accès externe)
   sudo ufw status
   sudo ufw allow 22/tcp    # SSH
   sudo ufw allow 80/tcp    # HTTP (si nécessaire)
   sudo ufw allow 443/tcp   # HTTPS (si nécessaire)
   sudo ufw enable
   ```

4. **Désactiver l'accès root MySQL à distance** (déjà fait dans mysql_secure_installation)

---

## Prochaines étapes

Après avoir installé la base de données avec succès :

1. [ ] Mettre à jour `doc/devops/jenkins_configuration.md` avec l'état coché des prérequis base de données
2. [ ] Re-exécuter le Job Jenkins `GVV-PHPUnit-Tests` pour vérifier que tous les tests passent
3. [ ] Continuer avec Phase 1.2 : Configuration des notifications email
4. [ ] Continuer avec Phase 2 : Script d'anonymisation de la base de données

Voir `doc/devops/ci_cd_plan.md` pour le plan complet.

---

## Références

- Documentation MySQL : https://dev.mysql.com/doc/
- Documentation MariaDB : https://mariadb.com/kb/en/documentation/
- Documentation phpMyAdmin : https://docs.phpmyadmin.net/
- CodeIgniter Database : https://codeigniter.com/userguide2/database/
- Plan DevOps : `doc/devops/ci_cd_plan.md`
- Configuration Jenkins : `doc/devops/jenkins_configuration.md`

---

**Document créé par** : Frédéric (dev solo) avec assistance Claude Code
**Dernière mise à jour** : 2025-12-05
**Version** : 1.0
