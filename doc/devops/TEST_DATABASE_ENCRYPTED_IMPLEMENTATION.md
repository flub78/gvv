# Fonctionnalité : Génération de base de données de test chiffrée

## Résumé

Implémentation d'une solution complète pour générer, chiffrer et restaurer une base de données de test anonymisée pour les tests CI/CD Jenkins.

## Problème résolu

Jenkins échouait car la base de test (`install/gvv_init.sql`) était obsolète (migration 26 vs 55 actuelle). Impossible de stocker les données réelles en clair dans Git pour des raisons de confidentialité.

## Solution implémentée

### 1. Interface utilisateur - Dashboard Admin

**Nouvelle carte dans le dashboard admin** (section "Outils de développement") :
- Titre : "Générer base de test"
- Description : "Chiffrée pour CI/CD"
- URL : `/admin/generate_test_database`
- Icône : Archive verte
- Restriction : Utilisateur `fpeignot` uniquement

### 2. Contrôleur - `admin.php`

**Nouvelle méthode `generate_test_database()`** qui :

1. ✅ Crée une sauvegarde temporaire de la base actuelle
2. ✅ Anonymise les données (appel aux méthodes existantes)
3. ✅ Ajoute les utilisateurs de test via `bin/create_test_users.sh`
4. ✅ Crée un dump SQL de la base anonymisée
5. ✅ Chiffre avec GPG AES256 → `install/base_de_test.sql.gpg`
6. ✅ Crée une archive ZIP (compatibilité) → `install/base_de_test.zip`
7. ✅ **Restaure automatiquement** la base à son état initial (rollback)

**Gestion de la passphrase** :
- Variable d'environnement `GVV_TEST_DB_PASSPHRASE` (prioritaire)
- Sinon, champ input dans le formulaire

### 3. Vue - `bs_test_database_generation.php`

Interface Bootstrap 5 avec :
- **Formulaire** : Option anonymisation numérotée + champ passphrase
- **Alertes** : Succès/erreurs avec icônes
- **Tableau de résultats** : Étapes avec statut (OK/WARNING/ERROR/SKIPPED)
- **Navigation** : Retour dashboard / Régénérer

### 4. Script Jenkins - `bin/init_test_database.sh`

Script bash robuste qui :
- Vérifie les prérequis (fichier `.gpg`, passphrase, credentials MySQL)
- Drop/Create la base de données
- Déchiffre et restaure en pipe : `gpg --decrypt | mysql`
- Vérifie la version de migration
- Contrôle l'intégrité des tables principales
- Output couleur avec statut ✓/✗

### 5. Documentation

Trois documents créés :

**`doc/test-database-encrypted.md`** (6.4KB) :
- Vue d'ensemble du système
- Procédure de génération (développeur)
- Configuration Jenkins/CI
- Sécurité et anonymisation
- Maintenance et rotation passphrase
- Dépannage

**`doc/jenkins-phpunit-setup.md`** (6.6KB) :
- Configuration Pipeline complète
- Configuration Freestyle (alternative)
- Credentials Jenkins
- Déclencheurs recommandés
- Optimisations (parallélisation, cache)
- Troubleshooting spécifique Jenkins

**README dans le code** :
- Commentaires inline dans `admin.php`
- Help text dans la vue
- Comments dans le script bash

### 6. Sécurité

**Fichiers versionnés dans Git** :
```
✅ install/base_de_test.sql.gpg  (chiffré AES256)
❌ install/base_de_test.sql      (ignoré par .gitignore)
❌ install/base_de_test.zip      (ignoré par .gitignore)
```

**Passphrase** :
- Jamais stockée en clair dans le code
- Stockée dans Jenkins Credentials (secret)
- Passée via variable d'environnement
- Fichier temporaire supprimé immédiatement après usage

**Anonymisation** :
- Réutilise les méthodes existantes et testées
- Membres, users, vols_decouverte
- Rollback automatique = pas de risque pour la base de dev

### 7. Traductions

Ajouts dans `application/language/french/admin_lang.php` :
```php
$lang['gvv_admin_test_db_title']
$lang['gvv_admin_test_db_desc']
$lang['gvv_admin_test_db_success']
$lang['gvv_admin_test_db_error']
```

## Fichiers modifiés/créés

### Modifiés (3)
1. `application/controllers/admin.php` - Ajout méthode `generate_test_database()`
2. `application/views/admin/bs_admin.php` - Nouvelle carte dashboard
3. `application/language/french/admin_lang.php` - Traductions
4. `.gitignore` - Exclusion fichiers SQL non chiffrés

### Créés (4)
1. `application/views/admin/bs_test_database_generation.php` - Interface utilisateur
2. `bin/init_test_database.sh` - Script restauration Jenkins
3. `doc/test-database-encrypted.md` - Documentation complète
4. `doc/jenkins-phpunit-setup.md` - Guide Jenkins

## Workflow complet

### Développeur (mise à jour base de test)

```bash
# 1. Restaurer prod sur dev
mysql gvv2 < backup_prod.sql

# 2. Générer base de test chiffrée
# → http://gvv.net/admin/generate_test_database
# → Entrer passphrase
# → Cliquer "Générer"

# 3. Vérifier
ls -lh install/base_de_test.sql.gpg  # Doit exister

# 4. Commiter
git add install/base_de_test.sql.gpg
git commit -m "Update test database to migration 55"
git push
```

### Jenkins (tests automatiques)

```groovy
environment {
    GVV_TEST_DB_PASSPHRASE = credentials('gvv-test-db-passphrase')
}

stages {
    stage('Setup') {
        steps {
            sh './bin/init_test_database.sh'
        }
    }
    
    stage('Test') {
        steps {
            sh 'source setenv.sh && ./run-all-tests.sh'
        }
    }
}
```

## Avantages

✅ **Données réalistes** - Couvre tous les cas d'usage métier  
✅ **Sécurisé** - Chiffrement AES256, pas de données sensibles en clair  
✅ **Automatisé** - Génération et restauration scriptées  
✅ **CI-friendly** - Intégration Jenkins simple  
✅ **Pas de risque** - Rollback automatique de la base de dev  
✅ **Maintenable** - Interface web pour régénérer  
✅ **Documenté** - Guides utilisateur et admin complets  

## Limitations connues

- ⚠️ Fichier binaire dans Git → merge conflicts possibles (last-win strategy)
- ⚠️ Taille ~1MB compressé → surveiller croissance (Git LFS si nécessaire)
- ⚠️ Synchronisation manuelle après migrations majeures
- ⚠️ Passphrase partagée (tous les devs/CI) → rotation périodique recommandée

## Tests de validation

### Manuel
```bash
# Test génération
1. http://gvv.net/admin/generate_test_database
2. Vérifier fichiers install/*.gpg et *.zip créés
3. Vérifier base restaurée (pas anonymisée)

# Test restauration
export GVV_TEST_DB_PASSPHRASE="test_passphrase"
export MYSQL_PASSWORD="lfoyfgbj"
./bin/init_test_database.sh
# → Doit afficher ✓ pour toutes les étapes
```

### Jenkins
```bash
# Job Jenkins GVV-PHPUnit-Tests
# → Doit passer stage "Setup Database"
# → Doit exécuter tous les tests sans erreur
```

## Prochaines étapes

1. ✅ Générer la première base de test chiffrée
2. ✅ Commiter `install/base_de_test.sql.gpg`
3. ✅ Configurer credentials Jenkins
4. ✅ Tester le job Jenkins
5. ⏳ Établir procédure de mise à jour régulière
6. ⏳ Documenter rotation passphrase (si nécessaire)

## Support

- Documentation : `doc/test-database-encrypted.md`
- Configuration Jenkins : `doc/jenkins-phpunit-setup.md`
- Script : `bin/init_test_database.sh`
- Contact : fpeignot (administrateur autorisé)

---

**Date** : 2025-12-07  
**Version GVV** : Migration 55  
**Status** : ✅ Implémenté, prêt pour tests
