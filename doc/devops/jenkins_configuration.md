# Configuration Jenkins - Phase 1 : Tests PHPUnit

**Objectif** : Configurer deux jobs Jenkins pour exécuter automatiquement les tests PHPUnit et calculer la couverture.

**Date de création** : 2025-12-05
**Référence** : Plan DevOps Phase 1 (`doc/devops/ci_cd_plan.md`)

---

## Prérequis

Avant de commencer, vérifier que :

- [x] Jenkins est accessible et vous avez les droits administrateur
- [x] PHP 7.4 est installé sur l'agent Jenkins 
PHP Version 7.4.33
- [x] Le dépôt Git GVV est accessible depuis Jenkins
- [x] Les credentials Git sont configurés dans Jenkins (si dépôt privé)
- [x] Vous avez une adresse email pour les notifications

**Vérification PHP sur Jenkins** :
```bash
# SSH sur l'agent Jenkins ou via Console Jenkins
php --version  # Doit afficher PHP 7.4.x
which php      # Noter le chemin complet
```

---

## Architecture des jobs

```
┌─────────────────────────────────────────────────────────┐
│  GVV-PHPUnit-Tests (Job 1)                              │
│  ├─ Polling Git horaire (H * * * *)                     │
│  ├─ Exécute : ./run-all-tests.sh (SANS --coverage)      │
│  ├─ Rapide (~2-3 minutes)                               │
│  └─ Publie résultats JUnit                              │
└─────────────────────────────────────────────────────────┘
                          │
                          │ Si STABLE uniquement
                          ▼
┌─────────────────────────────────────────────────────────┐
│  GVV-PHPUnit-Coverage (Job 2)                           │
│  ├─ Déclenché par Job 1 (si succès)                     │
│  ├─ Exécute : ./run-all-tests.sh --coverage             │
│  ├─ Plus lent (~60 secondes)                            │
│  └─ Publie rapport de couverture                        │
└─────────────────────────────────────────────────────────┘
```

---

## Job 1 : GVV-PHPUnit-Tests (Tests seuls)

### Étape 1 : Créer le job

1. Dans Jenkins : **New Item**
2. Nom : `GVV-PHPUnit-Tests`
3. Type : **Freestyle project**
4. Cliquer sur **OK**

---

### Étape 2 : Configuration générale

**Section "General"** :
- [x] Description : `Tests PHPUnit automatiques (sans couverture) pour détection rapide des régressions`
- [x] ☑ Discard old builds
  - Strategy : Log Rotation
  - Max # of builds to keep : `30`

---

### Étape 3 : Source Code Management

**Section "Source Code Management"** :
- [x] Sélectionner **Git**
- [x] Repository URL : `https://github.com/votre-user/gvv.git` (ou chemin local)
- [x] Credentials : Sélectionner vos credentials Git (si nécessaire)
- [x] Branches to build : `*/main` (ou votre branche principale)
- [x] Repository browser : (laisser Auto)

---

### Étape 4 : Build Triggers

**Section "Build Triggers"** :
- [x] ☑ **Poll SCM**
- [x] Schedule : `H * * * *`
  - Cela vérifie Git toutes les heures
  - Le `H` distribue la charge (minute aléatoire mais stable)

**Explication du schedule** :
```
H * * * *
│ │ │ │ │
│ │ │ │ └─ Jour de la semaine (0-7)
│ │ │ └─── Mois (1-12)
│ │ └───── Jour du mois (1-31)
│ └─────── Heure (0-23)
└───────── Minute (H = hash, aléatoire mais stable)
```

---

### Étape 5 : Build Environment

**Section "Build Environment"** :
- [x] ☑ **Delete workspace before build starts** (optionnel, recommandé pour éviter les problèmes de cache)
- [x] ☑ **Abort the build if it's stuck** (optionnel)
  - Time-out strategy : **Absolute**
  - Timeout minutes : `10`

---

### Étape 6 : Build Steps

**Section "Build"** :
- [x] Ajouter **Execute shell**

**Commandes à exécuter** :
```bash
#!/bin/bash
set -e  # Arrête en cas d'erreur

# Afficher version PHP
echo "=== PHP Version ==="
php --version

# Sourcer l'environnement PHP 7.4
source setenv.sh

# Vérifier que PHP 7.4 est bien activé
echo "=== Active PHP Version ==="
php --version | head -1

# Exécuter les tests SANS couverture (rapide)
echo "=== Running PHPUnit Tests (no coverage) ==="
./run-all-tests.sh

echo "=== Tests completed ==="
```

**Note importante** : Si `setenv.sh` ne fonctionne pas, remplacer par :
```bash
export PATH=/usr/bin:$PATH
alias php=/usr/bin/php7.4
```

---

### Étape 7 : Post-build Actions

**Section "Post-build Actions"** :

#### 7.1 Publier les résultats JUnit

- [ ] Ajouter **Publish JUnit test result report**
- [ ] Test report XMLs : `build/logs/*.xml`
  - Cocher ☑ **Retain long standard output/error**
  - Cocher ☑ **Do not fail the build on empty test results**

#### 7.2 Archiver les artefacts

- [ ] Ajouter **Archive the artifacts**
- [ ] Files to archive : `build/logs/*.xml, build/logs/*.txt`
- [ ] Cocher ☑ **Archive artifacts only if build is successful**

#### 7.3 Notifications email (Phase 1.2)

- [ ] Ajouter **E-mail Notification**
- [ ] Recipients : `votre-email@example.com`
- [ ] Cocher ☑ **Send e-mail for every unstable build**
- [ ] Cocher ☑ **Send separate e-mails to individuals who broke the build**

---

### Étape 8 : Sauvegarder et tester

- [ ] Cliquer sur **Save**
- [ ] Cliquer sur **Build Now** pour tester manuellement
- [ ] Vérifier les logs du build (Console Output)
- [ ] Vérifier que les résultats JUnit apparaissent dans l'interface

**Vérifications** :
```bash
# Dans "Console Output", vous devez voir :
# - Version PHP 7.4.x
# - Exécution des 6 suites de tests
# - Résumé final avec nombre de tests passés/échoués
```

---

## Job 2 : GVV-PHPUnit-Coverage (Couverture conditionnelle)

### Étape 1 : Créer le job

1. Dans Jenkins : **New Item**
2. Nom : `GVV-PHPUnit-Coverage`
3. Type : **Freestyle project**
4. Cliquer sur **OK**

---

### Étape 2 : Configuration générale

**Section "General"** :
- [ ] Description : `Calcul de la couverture PHPUnit (déclenché uniquement si tests passent)`
- [ ] ☑ Discard old builds
  - Strategy : Log Rotation
  - Max # of builds to keep : `20` (moins que Job 1 car moins fréquent)

---

### Étape 3 : Source Code Management

**Même configuration que Job 1** :
- [ ] Sélectionner **Git**
- [ ] Repository URL : (même URL que Job 1)
- [ ] Credentials : (même que Job 1)
- [ ] Branches to build : `*/main`

---

### Étape 4 : Build Triggers

**Section "Build Triggers"** :
- [ ] ☑ **Build after other projects are built**
- [ ] Projects to watch : `GVV-PHPUnit-Tests`
- [ ] ☑ **Trigger only if build is stable** ← **IMPORTANT**

**Explication** : Ce job ne se déclenche QUE si `GVV-PHPUnit-Tests` réussit (stable).

---

### Étape 5 : Build Environment

**Section "Build Environment"** :
- [ ] ☑ **Delete workspace before build starts** (optionnel)
- [ ] ☑ **Abort the build if it's stuck**
  - Time-out strategy : **Absolute**
  - Timeout minutes : `15` (plus long car calcul de couverture)

---

### Étape 6 : Build Steps

**Section "Build"** :
- [ ] Ajouter **Execute shell**

**Commandes à exécuter** :
```bash
#!/bin/bash
set -e  # Arrête en cas d'erreur

# Afficher version PHP
echo "=== PHP Version ==="
php --version

# Sourcer l'environnement PHP 7.4
source setenv.sh

# Vérifier que PHP 7.4 est bien activé
echo "=== Active PHP Version ==="
php --version | head -1

# Vérifier que Xdebug est disponible
echo "=== Xdebug Status ==="
php -m | grep -i xdebug || echo "WARNING: Xdebug not found!"

# Exécuter les tests AVEC couverture (plus lent)
echo "=== Running PHPUnit Tests WITH coverage ==="
./run-all-tests.sh --coverage

echo "=== Coverage analysis completed ==="
```

---

### Étape 7 : Post-build Actions

**Section "Post-build Actions"** :

#### 7.1 Publier les résultats JUnit

- [ ] Ajouter **Publish JUnit test result report**
- [ ] Test report XMLs : `build/logs/*.xml`

#### 7.2 Publier le rapport de couverture

**Option A : HTML Publisher (recommandé)** :
- [ ] Installer le plugin **HTML Publisher** si pas déjà installé
- [ ] Ajouter **Publish HTML reports**
- [ ] HTML directory to archive : `build/coverage`
- [ ] Index page[s] : `index.html`
- [ ] Report title : `PHPUnit Coverage Report`

**Option B : Cobertura** :
- [ ] Installer le plugin **Cobertura** si pas déjà installé
- [ ] Ajouter **Publish Cobertura Coverage Report**
- [ ] Cobertura xml report pattern : `build/logs/cobertura.xml`

#### 7.3 Archiver les artefacts

- [ ] Ajouter **Archive the artifacts**
- [ ] Files to archive : `build/logs/*.xml, build/coverage/**/*`

#### 7.4 Notifications (optionnel)

- [ ] Ajouter **E-mail Notification** pour alerter en cas de dégradation de couverture (optionnel)

---

### Étape 8 : Sauvegarder et tester

- [ ] Cliquer sur **Save**
- [ ] Déclencher manuellement le Job 1 (`GVV-PHPUnit-Tests`)
- [ ] Vérifier que le Job 2 se déclenche automatiquement après
- [ ] Vérifier le rapport de couverture dans Jenkins

---

## Tests de validation

### Test 1 : Pipeline complet avec succès

```bash
# Sur votre machine de développement
git commit --allow-empty -m "test: trigger Jenkins pipeline"
git push

# Attendre max 1 heure (polling)
# Ou déclencher manuellement dans Jenkins

# Vérifier :
# 1. Job GVV-PHPUnit-Tests s'exécute
# 2. Job GVV-PHPUnit-Coverage se déclenche après
# 3. Rapport de couverture accessible
```

### Test 2 : Pipeline avec échec de tests

Pour tester que Job 2 ne se déclenche PAS en cas d'échec :

```bash
# Introduire temporairement un test qui échoue
# Par exemple, dans un fichier de test :
# $this->assertTrue(false, "Test failure simulation");

git commit -m "test: simulate test failure"
git push

# Vérifier :
# 1. Job GVV-PHPUnit-Tests échoue (rouge)
# 2. Job GVV-PHPUnit-Coverage NE SE DÉCLENCHE PAS
# 3. Notification email reçue

# Annuler le test qui échoue
git revert HEAD
git push
```

---

## Plugins Jenkins requis

Installer ces plugins via **Manage Jenkins** → **Manage Plugins** :

- [x] **Git Plugin** (normalement déjà installé)
- [x] **JUnit Plugin** (normalement déjà installé)
- [x] **HTML Publisher Plugin** (pour rapport de couverture HTML)
- [ ] **Cobertura Plugin** (alternatif pour couverture)
- [ ] **Email Extension Plugin** (pour notifications avancées)
- [ ] **Build Pipeline Plugin** (optionnel, pour visualiser le pipeline)

---

## Dépannage

### Problème : PHP 7.4 non trouvé

**Symptôme** : `php: command not found` ou version incorrecte

**Solution** :
```bash
# Dans Build Steps, spécifier le chemin complet
/usr/bin/php7.4 --version

# Ou mettre à jour PATH
export PATH=/usr/bin:$PATH
```

### Problème : Tests échouent mais Job 2 se déclenche quand même

**Symptôme** : Job Coverage s'exécute même si Job Tests échoue

**Solution** :
- Vérifier dans Job 2, Build Triggers : `Trigger only if build is stable` est bien coché
- Ne PAS cocher `Trigger even if the build is unstable`

### Problème : Rapport de couverture non généré

**Symptôme** : `build/coverage/` vide ou inexistant

**Solution** :
```bash
# Vérifier que Xdebug est installé sur l'agent Jenkins
php -m | grep xdebug

# Si absent, installer Xdebug :
sudo apt-get install php7.4-xdebug  # Debian/Ubuntu
```

### Problème : Polling Git ne fonctionne pas

**Symptôme** : Job ne se déclenche jamais automatiquement

**Solution** :
- Vérifier les logs du polling : Cliquer sur "Git Polling Log" dans le job
- Vérifier que Jenkins peut accéder au dépôt Git
- Tester manuellement : `Build Now` doit fonctionner

### Problème : Timeout du build

**Symptôme** : `Build timed out (after 10 minutes)`

**Solution** :
- Augmenter le timeout dans Build Environment
- Vérifier que les tests ne sont pas bloqués (attente infinie)

---

## Métriques attendues

Après configuration complète, vous devriez observer :

| Métrique | Valeur cible | Comment vérifier |
|----------|--------------|------------------|
| Fréquence d'exécution | Toutes les heures (si commit) | Git Polling Log |
| Durée Job Tests | 2-5 minutes | Build History |
| Durée Job Coverage | 30-90 secondes | Build History |
| Taux de déclenchement Coverage | ~90% (seulement si tests OK) | Build History |
| Délai de notification | < 5 minutes après échec | Email reçu |

---

## Prochaines étapes

Une fois la Phase 1 terminée et validée :

- [ ] Phase 2 : Script d'anonymisation de la base de données
- [ ] Phase 3 : Déploiement automatique sur serveur de test
- [ ] Phase 4 : Tests Playwright automatisés

Voir `doc/devops/ci_cd_plan.md` pour le plan complet.

---

## Références

- Plan DevOps complet : `doc/devops/ci_cd_plan.md`
- Scripts de test : `run-all-tests.sh`, `run-coverage.sh`
- Configuration PHPUnit : `phpunit.xml`, `phpunit_integration.xml`, etc.
- Documentation Jenkins : https://www.jenkins.io/doc/

---

**Document maintenu par** : Frédéric (dev solo)
**Dernière mise à jour** : 2025-12-05
**Version** : 1.0
