# Quick Start - CI/CD Jenkins pour GVV

**Objectif** : Tests PHPUnit automatisés avec base de données anonymisée et chiffrée.

---

## Vue d'ensemble

```
Développeur → Git → Jenkins → Tests PHPUnit
                ↓
        base_test.sql.gpg
        (anonymisée + chiffrée)
```

---

## Étapes principales

### 1. Générer la base anonymisée (Local - 1 fois)

```bash
# Créer le script d'export
# Voir doc/devops/ci_cd_plan.md - Phase 1, Étape 1.1

# Définir la passphrase
export GVV_TEST_DB_PASSPHRASE="votre_passphrase_secrete"

# Exécuter l'export
./bin/export_anonymized_db.sh

# Vérifier le fichier chiffré
ls -lh test_data/gvv_test.sql.gpg

# Commiter dans Git
git add test_data/gvv_test.sql.gpg .gitignore
git commit -m "feat: add encrypted test database"
git push
```

**Résultat** : Fichier `test_data/gvv_test.sql.gpg` disponible dans Git

---

### 2. Configurer Jenkins (Jenkins - 1 fois)

#### a) Stocker la passphrase

Dans Jenkins → Manage Jenkins → Credentials :
- Type : Secret text
- ID : `gvv-test-db-passphrase`
- Secret : votre passphrase

#### b) Créer le job Jenkins

1. **Nouveau Item** → "GVV-PHPUnit-Tests" → Freestyle project

2. **Source Code Management** :
   - Git : URL de votre dépôt
   - Branch : `*/main`

3. **Build Triggers** :
   - Poll SCM : `H/15 * * * *` (vérifie toutes les 15 minutes)

4. **Build Environment** :
   - Use secret text : Variable `GVV_TEST_DB_PASSPHRASE` → Credentials `gvv-test-db-passphrase`

5. **Build** → Execute shell :
   ```bash
   #!/bin/bash
   set -e
   
   # Restaurer la base
   ./bin/restore_test_db.sh
   
   # Configuration PHP 7.4
   source setenv.sh
   
   # Tests
   ./run-all-tests.sh
   ```

6. **Post-build Actions** :
   - Publish JUnit : `build/logs/*.xml`
   - Email notification : votre email

**Résultat** : Job Jenkins opérationnel

---

### 3. Workflow quotidien

```bash
# Développement normal
git add .
git commit -m "feat: nouvelle fonctionnalité"
git push

# Jenkins détecte le commit (max 15 min)
# → Déchiffre la base
# → Restaure dans MySQL
# → Exécute les tests
# → Email si échec
```

**C'est tout !** Les tests s'exécutent automatiquement.

---

## Maintenance

### Quand regénérer la base anonymisée ?

**À chaque modification du schéma** (nouvelle migration) :

```bash
# 1. Appliquer la migration localement
php run_migrations.php

# 2. Régénérer la base
export GVV_TEST_DB_PASSPHRASE="votre_passphrase"
./bin/export_anonymized_db.sh

# 3. Commiter
git add test_data/gvv_test.sql.gpg
git commit -m "chore: update test database (migration XX)"
git push

# Jenkins utilisera automatiquement la nouvelle version
```

**Fréquence** : Rare (uniquement si schéma change)

---

## Dépannage rapide

### Tests échouent ?

```bash
# Vérifier les logs Jenkins
# Jenkins → GVV-PHPUnit-Tests → Console Output
```

### Base non restaurée ?

```bash
# Sur le serveur Jenkins, tester manuellement
export GVV_TEST_DB_PASSPHRASE="votre_passphrase"
./bin/restore_test_db.sh
```

### Passphrase incorrecte ?

```bash
# Vérifier dans Jenkins Credentials
# Manage Jenkins → Credentials → gvv-test-db-passphrase
```

---

## Pour aller plus loin

Voir `doc/devops/ci_cd_plan.md` pour :
- Détails des scripts
- Alternative contrôleur PHP
- Job Jenkins avec couverture
- Améliorations optionnelles

---

**Temps d'installation** : 3-5h
**Maintenance** : 15-30min par modification de schéma
**ROI** : Détection immédiate des régressions
