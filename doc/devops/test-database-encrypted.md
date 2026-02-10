# Base de données de test chiffrée

## Vue d'ensemble

Pour éviter de stocker des données sensibles en clair dans Git, GVV utilise une base de données de test **anonymisée et chiffrée avec GPG**.

Cette base contient :
- Des données réelles anonymisées (noms, emails, adresses remplacés)
- Les utilisateurs de test standard (testuser, testadmin, testplanchiste, etc.)
- Le schéma complet à la dernière migration (actuellement migration 55)

## Génération de la base de test (développeur)

### Prérequis
- Accès utilisateur `fpeignot` sur GVV
- Base de données de développement avec données de production restaurées

### Processus

1. **Accéder au dashboard admin**
   ```
   http://gvv.net/admin
   ```

2. **Cliquer sur "Générer base de test"** dans la section "Outils de développement"

3. **Configurer les options** :
   - ☑️ Anonymisation numérotée (recommandé, plus rapide)
   - 🔑 Passphrase de chiffrement (ou utiliser `GVV_TEST_DB_PASSPHRASE`)

4. **Lancer la génération**

### Étapes automatiques

Le processus effectue automatiquement :

1. ✅ **Sauvegarde initiale** - Dump de la base actuelle
2. ✅ **Anonymisation** - Appel à `anonymize_all_data()`
3. ✅ **Utilisateurs de test** - Exécution de `bin/create_test_users.sh`
4. ✅ **Dump anonymisé** - Export de la base anonymisée
5. ✅ **Chiffrement GPG** - Chiffrement AES256 avec passphrase
6. ✅ **Archive ZIP** - Backup non chiffré pour compatibilité
7. ✅ **Restauration** - **Rollback automatique** de la base

### Fichiers générés

```
install/
├── base_de_test.sql.gpg  ← Fichier chiffré (à commiter dans Git)
└── base_de_test.zip      ← Archive non chiffrée (NE PAS commiter)
```

### Commit dans Git

```bash
git add install/base_de_test.sql.gpg
git commit -m "Update test database to migration 55"
git push
```

**⚠️ Important** : Seul le fichier `.gpg` doit être commité. L'archive `.zip` est en clair et ne doit **jamais** être versionnée.

## Utilisation dans Jenkins (CI/CD)

### Configuration Jenkins

1. **Créer un credential** de type "Secret text"
   - ID: `gvv-test-db-passphrase`
   - Secret: La passphrase de chiffrement

2. **Configurer le job Jenkins**

```groovy
pipeline {
    agent any
    
    environment {
        GVV_TEST_DB_PASSPHRASE = credentials('gvv-test-db-passphrase')
        MYSQL_DATABASE = 'gvv2'
        MYSQL_USER = 'gvv_user'
        MYSQL_PASSWORD = credentials('gvv-mysql-password')
    }
    
    stages {
        stage('Setup Database') {
            steps {
                sh './bin/init_test_database.sh'
            }
        }
        
        stage('Run Tests') {
            steps {
                sh 'source setenv.sh && ./run-all-tests.sh'
            }
        }
    }
}
```

### Script d'initialisation

Le script `bin/init_test_database.sh` :
- Déchiffre `install/base_de_test.sql.gpg`
- Restaure la base dans `gvv2`
- Vérifie la version de migration
- Contrôle l'intégrité des tables principales

### Exécution manuelle

```bash
export GVV_TEST_DB_PASSPHRASE="votre_passphrase"
export MYSQL_PASSWORD="mot_de_passe_mysql"
./bin/init_test_database.sh
```

## Sécurité

### Chiffrement
- **Algorithme** : GPG AES256 (symétrique)
- **Passphrase** : Stockée uniquement dans Jenkins Credentials
- **Fichier** : `base_de_test.sql.gpg` versionné dans Git

### Anonymisation
Les données suivantes sont anonymisées :
- **Membres** : Noms, prénoms, adresses, téléphones, emails
- **Utilisateurs** : Emails synchronisés avec membres
- **Vols découverte** : Informations personnelles des pilotes

### Utilisateurs de test

| Username        | Mot de passe | Rôle          |
|-----------------|--------------|---------------|
| testuser        | password     | membre        |
| testadmin       | password     | admin         |
| testplanchiste  | password     | planchiste    |
| testca          | password     | ca            |
| testbureau      | password     | bureau        |
| testtresorier   | password     | tresorier     |

## Maintenance

### Mise à jour de la base de test

**Quand mettre à jour ?**
- Après une nouvelle migration importante
- Après modification du schéma impactant les tests
- Si les tests échouent à cause de données obsolètes

**Procédure** :
1. Restaurer la base de production sur l'environnement de développement
2. Générer la nouvelle base de test via le dashboard admin
3. Tester localement avec `bin/init_test_database.sh`
4. Commiter le nouveau fichier `.gpg`

### Rotation de la passphrase

Si nécessaire, pour rechiffrer avec une nouvelle passphrase :

```bash
# Déchiffrer avec l'ancienne passphrase
gpg --decrypt install/base_de_test.sql.gpg > /tmp/test.sql

# Rechiffrer avec la nouvelle passphrase
gpg --symmetric --cipher-algo AES256 \
    --output install/base_de_test.sql.gpg \
    /tmp/test.sql

# Nettoyer
rm /tmp/test.sql

# Mettre à jour Jenkins Credentials
```

## Dépannage

### "Passphrase non fournie"
```bash
export GVV_TEST_DB_PASSPHRASE="votre_passphrase"
```

### "Fichier chiffré introuvable"
Vérifier que `install/base_de_test.sql.gpg` existe. Sinon, le générer via le dashboard admin.

### "Échec du déchiffrement"
- Vérifier que la passphrase est correcte
- Vérifier que GPG est installé : `which gpg`
- Tester manuellement : `gpg --decrypt install/base_de_test.sql.gpg`

### "Base restaurée mais tables vides"
Le fichier `.gpg` est peut-être corrompu. Régénérer via le dashboard admin.

### Tests échouent après restauration
Vérifier la version de migration :
```bash
mysql -u gvv_user -p gvv2 -e \
  "SELECT version FROM migrations ORDER BY version DESC LIMIT 1"
```

Doit correspondre à la version dans `application/config/migration.php`.

## Fichiers ignorés par Git

Ajouté dans `.gitignore` :
```
install/base_de_test.sql      # Dump SQL en clair
install/base_de_test.zip      # Archive non chiffrée
```

**Seul** `install/base_de_test.sql.gpg` est versionné.

## Avantages de cette approche

✅ **Données réalistes** - Tests couvrent tous les cas d'usage réels  
✅ **Sécurisé** - Chiffrement AES256, pas de fuite de données  
✅ **Automatisé** - Génération et restauration scriptées  
✅ **CI-friendly** - Intégration Jenkins simple  
✅ **Rollback automatique** - Pas de risque pour la base de dev  
✅ **Maintenable** - Mise à jour via interface web  

## Support

En cas de problème, contacter l'administrateur système ou consulter :
- Logs Jenkins
- Logs GVV : `application/logs/`
- Script : `bin/init_test_database.sh`
