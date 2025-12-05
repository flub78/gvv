# Plan DevOps CI/CD pour GVV

**Objectif** : Automatiser les tests et le déploiement pour détecter les régressions rapidement et maintenir le serveur de test à jour.

**Date de création** : 2025-12-05
**Statut** : Planification

---

## Contexte

### Situation actuelle
- ✅ Serveur de test Oracle Free Tier (mise à jour manuelle)
- ✅ Jenkins existant (analyse statique uniquement)
- ✅ Suite PHPUnit avec couverture moyenne (non automatisée)
- ✅ Suite Playwright (utilise base anonymisée, non automatisée)
- ✅ Utilisateurs de test créés manuellement

### Problèmes identifiés
- ❌ Régressions indétectées (tests non systématiques)
- ❌ Serveur de test pas toujours à jour
- ❌ Processus manuel chronophage
- ❌ Pas de notification automatique en cas d'échec

### Objectifs
1. Exécution automatique des tests PHPUnit sur Jenkins
2. Génération automatique de la base de données anonymisée
3. Déploiement automatique sur serveur de test après commit
4. Exécution automatique des tests Playwright sur Jenkins
5. Notifications en cas d'échec ou régression

---

## Phase 1 : Tests PHPUnit automatisés ⚡ PRIORITÉ HAUTE

**Bénéfice immédiat** : Détection automatique des régressions PHP à chaque commit

**Stratégie** : Deux jobs Jenkins séparés pour optimiser le feedback
- **Job 1 (Tests)** : Rapide, sans couverture → feedback immédiat en cas d'échec
- **Job 2 (Coverage)** : Plus lent, avec couverture → s'exécute uniquement si tests OK
- **Avantage** : Ne pas perdre de temps sur la couverture si les tests échouent

### Étape 1.1a : Job Jenkins PHPUnit (tests seuls)
**Durée estimée** : 1-2h
**Prérequis** : Accès Jenkins, dépôt Git accessible depuis Jenkins

**Actions** :
- [ ] Créer job Jenkins "GVV-PHPUnit-Tests"
- [ ] Configurer Source Code Management (Git) avec l'URL du dépôt
- [ ] Configurer Build Triggers → Poll SCM
  - Schedule : `H * * * *` (vérifie toutes les heures)
  - Note : Délai de détection jusqu'à 1h après un commit
- [ ] Ajouter les commandes de build (tests SANS couverture - rapide) :
  ```bash
  source setenv.sh
  ./run-all-tests.sh
  ```
- [ ] Installer/configurer plugin JUnit pour publier résultats
- [ ] Archiver les artefacts (rapports de tests)

**Validation** :
```bash
# Test manuel du job
# Vérifier que les résultats apparaissent dans Jenkins
# Vérifier que le job est rapide (quelques minutes max)
```

**Livrables** :
- Job Jenkins fonctionnel
- Rapports de tests visibles dans l'interface
- Feedback rapide sur les échecs de tests

---

### Étape 1.1b : Job Jenkins Couverture (si tests OK)
**Durée estimée** : 1h
**Prérequis** : Étape 1.1a terminée

**Actions** :
- [ ] Créer job Jenkins "GVV-PHPUnit-Coverage"
- [ ] Configurer Source Code Management (Git) - même config que 1.1a
- [ ] Configurer Build Triggers → Build after other projects are built
  - Projet : "GVV-PHPUnit-Tests"
  - Trigger : "Trigger only if build is stable" (uniquement si tests OK)
- [ ] Ajouter les commandes de build (tests AVEC couverture - plus lent) :
  ```bash
  source setenv.sh
  ./run-all-tests.sh --coverage
  ```
- [ ] Installer/configurer plugin Cobertura ou HTML Publisher pour couverture
- [ ] Archiver les artefacts (rapports de couverture)

**Validation** :
```bash
# Faire un commit qui passe les tests
git push
# Vérifier que job Tests s'exécute
# Vérifier que job Coverage se déclenche automatiquement après
# Vérifier le rapport de couverture accessible

# Faire un commit qui casse un test
git push
# Vérifier que job Tests échoue
# Vérifier que job Coverage ne se déclenche PAS
```

**Note sur le polling** :
Le polling horaire (`H * * * *`) a été choisi pour sa simplicité :
- ✅ Pas besoin d'exposer Jenkins sur Internet
- ✅ Configuration minimale (pas de webhook GitHub)
- ✅ Suffisant pour un dev solo (délai max 1h acceptable)
- Alternative : Webhook GitHub si délai instantané nécessaire (nécessite Jenkins accessible publiquement)

**Livrables** :
- Job Jenkins couverture fonctionnel
- Rapport de couverture de code précis
- Pipeline Tests → Coverage automatisé

---

### Étape 1.2 : Notifications d'échec
**Durée estimée** : 30min
**Prérequis** : Étape 1.1a terminée (notifications sur job Tests)

**Actions** :
- [ ] Configurer notifications email dans Jenkins pour "GVV-PHPUnit-Tests"
  - Destinataire : adresse du développeur
  - Déclencher sur : échec, régression, récupération
- [ ] Optionnel : Configurer notifications pour "GVV-PHPUnit-Coverage" (dégradation couverture)
- [ ] OU configurer webhook Slack/Discord (alternatif)
  - Créer webhook entrant
  - Ajouter notification post-build Jenkins

**Validation** :
```bash
# Introduire un test qui échoue
git commit -m "test: force failure"
git push
# Vérifier réception de la notification (job Tests)
git revert HEAD && git push
```

**Livrables** :
- Notification fonctionnelle en cas d'échec de tests
- Documentation de la configuration

---

### ✅ État après Phase 1
**Amélioration** :
- Vous êtes averti automatiquement si un commit casse les tests PHP (feedback rapide)
- La couverture est calculée automatiquement uniquement quand les tests passent (gain de temps)
- Rapports de tests et couverture accessibles dans Jenkins

---

## Phase 2 : Génération automatique de la base anonymisée ⚡ PRIORITÉ HAUTE

**Bénéfice** : Base de test fraîche et réaliste sans risque pour la base de développement

### Étape 2.1 : Script d'anonymisation en export
**Durée estimée** : 3-4h
**Prérequis** : Accès à la base de production locale, logique d'anonymisation existante

**Actions** :
- [ ] Créer `bin/export_anonymized_db.sh`
  ```bash
  #!/bin/bash
  # 1. Dump base de prod locale
  # 2. Anonymisation sur le dump (sed/awk/PHP)
  # 3. Génération gvv_test_anonymized.sql
  # 4. Ajout des utilisateurs de test (bin/create_test_users.sql)
  # 5. Compression (optionnel)
  ```
- [ ] Extraire/adapter la logique d'anonymisation existante
- [ ] Tester le script localement
- [ ] Documenter le processus dans `doc/devops/database_anonymization.md`

**Validation** :
```bash
# Exécuter le script
./bin/export_anonymized_db.sh

# Vérifier le dump généré
ls -lh gvv_test_anonymized.sql

# Tester l'import sur une base temporaire
mysql -u test -p test_db < gvv_test_anonymized.sql

# Vérifier l'anonymisation (pas de données sensibles)
mysql -u test -p test_db -e "SELECT email, nom, prenom FROM membres LIMIT 10"
```

**Livrables** :
- Script `bin/export_anonymized_db.sh` fonctionnel
- Dump SQL anonymisé
- Documentation du processus

---

### Étape 2.2 : Stockage du dump pour serveur de test
**Durée estimée** : 1h
**Prérequis** : Étape 2.1 terminée, accès SSH au serveur Oracle

**Actions** :
- [ ] Créer répertoire sur serveur Oracle : `/opt/gvv_test/db_dumps/`
- [ ] Configurer clé SSH pour transfert automatique
- [ ] Ajouter commande upload au script :
  ```bash
  scp gvv_test_anonymized.sql.gz oracle_server:/opt/gvv_test/db_dumps/latest.sql.gz
  ```
- [ ] Alternative : utiliser stockage cloud (S3, Oracle Object Storage)

**Validation** :
```bash
# Test de transfert
./bin/export_anonymized_db.sh
ssh oracle_server "ls -lh /opt/gvv_test/db_dumps/latest.sql.gz"
```

**Livrables** :
- Dump accessible sur serveur de test
- Documentation du stockage

---

### ✅ État après Phase 2
**Amélioration** : Vous pouvez générer facilement une base anonymisée fraîche pour les tests, sans risque pour votre base de développement.

---

## Phase 3 : Déploiement automatique sur serveur de test 🔄 PRIORITÉ MOYENNE

**Bénéfice** : Serveur de test toujours synchronisé avec la dernière version du code

### Étape 3.1 : Script de déploiement
**Durée estimée** : 2-3h
**Prérequis** : Accès SSH serveur Oracle, clés configurées

**Actions** :
- [ ] Créer `bin/deploy_test_server.sh`
  ```bash
  #!/bin/bash
  # 1. SSH vers serveur Oracle
  # 2. git pull sur le dépôt
  # 3. source setenv.sh
  # 4. Vérifier version migration actuelle
  # 5. Recharger base anonymisée si nouvelle version disponible
  # 6. Redémarrer services si nécessaire
  # 7. Vérifier que l'application répond (curl health check)
  ```
- [ ] Gérer les migrations :
  - Comparer version locale vs serveur
  - Appliquer migrations si nécessaire
  - Rollback en cas d'échec
- [ ] Tester manuellement plusieurs fois

**Validation** :
```bash
# Test de déploiement
./bin/deploy_test_server.sh

# Vérifier version déployée
ssh oracle_server "cd /path/to/gvv && git log -1 --oneline"

# Vérifier que l'app répond
curl http://test.gvv.example.com/
```

**Livrables** :
- Script `bin/deploy_test_server.sh` fonctionnel
- Documentation du processus de déploiement
- Checklist de rollback en cas de problème

---

### Étape 3.2 : Job Jenkins de déploiement
**Durée estimée** : 1h
**Prérequis** : Étape 3.1 terminée, script testé

**Actions** :
- [ ] Créer job Jenkins "GVV-Deploy-Test"
- [ ] Configuration :
  - Déclenchement : manuel (au début) ou automatique après succès PHPUnit
  - Build step : exécuter `bin/deploy_test_server.sh`
  - Post-build : notification si échec
- [ ] Ajouter credentials SSH dans Jenkins
- [ ] Tester le job manuellement

**Recommandation** : Commencer avec déclenchement manuel, passer en automatique après confiance établie (2-3 semaines).

**Validation** :
```bash
# Déclencher le job Jenkins manuellement
# Vérifier les logs Jenkins
# Vérifier le serveur de test mis à jour
```

**Livrables** :
- Job Jenkins de déploiement
- Documentation du processus

---

### Étape 3.3 : Pipeline PHPUnit → Déploiement (optionnel)
**Durée estimée** : 1h
**Prérequis** : Étapes 1.1a et 3.2 terminées

**Actions** :
- [ ] Créer pipeline Jenkins ou configurer downstream job
- [ ] Enchaînement :
  1. Job PHPUnit s'exécute
  2. Si succès → déclencher job Déploiement
  3. Si échec → arrêter, notification
- [ ] Ajouter paramètre pour skip déploiement si besoin

**Validation** :
```bash
# Faire un commit
git push
# Vérifier que PHPUnit s'exécute
# Vérifier que déploiement se déclenche si tests OK
# Vérifier serveur de test mis à jour automatiquement
```

**Livrables** :
- Pipeline automatisé
- Documentation du workflow

---

### ✅ État après Phase 3
**Amélioration** : Le serveur de test est automatiquement mis à jour après chaque commit réussissant les tests, sans intervention manuelle.

---

## Phase 4 : Tests Playwright automatisés 🎭 PRIORITÉ MOYENNE

**Bénéfice** : Tests end-to-end automatiques, détection des régressions UI et fonctionnelles

### Étape 4.1 : Job Jenkins Playwright
**Durée estimée** : 2h
**Prérequis** : Serveur de test déployé et accessible, Playwright configuré

**Actions** :
- [ ] Créer job Jenkins "GVV-Playwright-E2E"
- [ ] Installer dépendances Playwright sur Jenkins agent :
  ```bash
  npm install
  npx playwright install --with-deps
  ```
- [ ] Configurer variables d'environnement (URL serveur de test)
- [ ] Ajouter commandes de build :
  ```bash
  cd playwright
  PLAYWRIGHT_BASE_URL=http://test.gvv.example.com npx playwright test --reporter=line
  ```
- [ ] Publier résultats (plugin HTML Publisher pour rapport Playwright)
- [ ] Archiver screenshots/vidéos en cas d'échec

**Validation** :
```bash
# Exécuter le job manuellement
# Vérifier que tous les tests passent
# Vérifier le rapport HTML accessible
# Simuler un échec et vérifier screenshots capturés
```

**Livrables** :
- Job Jenkins Playwright fonctionnel
- Rapports de tests E2E visibles
- Screenshots/vidéos d'échecs archivés

---

### Étape 4.2 : Pipeline complet orchestré
**Durée estimée** : 2-3h
**Prérequis** : Toutes les étapes précédentes terminées

**Actions** :
- [ ] Créer Jenkins Pipeline (Jenkinsfile ou UI)
  ```groovy
  pipeline {
    stages {
      stage('PHPUnit Tests') {
        // Déclenche job GVV-PHPUnit-Tests
      }
      stage('PHPUnit Coverage') {
        when {
          expression { currentBuild.result == null || currentBuild.result == 'SUCCESS' }
        }
        // Déclenche job GVV-PHPUnit-Coverage
      }
      stage('Deploy to Test') {
        when {
          expression { currentBuild.result == null || currentBuild.result == 'SUCCESS' }
        }
        // Déclenche job GVV-Deploy-Test
      }
      stage('Playwright E2E') {
        when {
          expression { currentBuild.result == null || currentBuild.result == 'SUCCESS' }
        }
        // Déclenche job GVV-Playwright-E2E
      }
    }
    post {
      failure { ... notify ... }
      success { ... notify ... }
    }
  }
  ```
- [ ] Paralléliser si possible (analyse statique peut s'exécuter en parallèle des tests)
- [ ] Configurer timeout raisonnable (20-25min max avec couverture)
- [ ] Ajouter possibilité de rejouer uniquement Playwright si échec

**Validation** :
```bash
# Faire un commit qui passe les tests
git push
# Vérifier pipeline complet s'exécute
# Vérifier ordre : PHPUnit Tests → Coverage → Deploy → Playwright
# Vérifier notifications à chaque étape

# Faire un commit qui casse les tests
git push
# Vérifier que le pipeline s'arrête après PHPUnit Tests
# Vérifier que Coverage, Deploy et Playwright ne s'exécutent PAS
```

**Livrables** :
- Pipeline CI/CD complet
- Documentation du workflow
- Dashboard Jenkins avec vue d'ensemble

---

### ✅ État après Phase 4
**Amélioration** :
- Pipeline CI/CD complet : Tests → Coverage → Déploiement → E2E
- Optimisation : couverture et déploiement uniquement si tests passent
- Feedback rapide en cas d'échec (arrêt du pipeline)
- Serveur de test toujours à jour avec code validé par tous les tests

---

## Phase 5 : Améliorations optionnelles 🚀 PRIORITÉ BASSE

### Option 5.1 : Tests de migration automatisés
**Durée estimée** : 3-4h

**Actions** :
- [ ] Créer `bin/test_migrations.sh`
  - Dump base actuelle
  - Appliquer migrations sur copie
  - Rollback automatique si échec
  - Vérifier intégrité schéma
- [ ] Ajouter au pipeline (étape facultative)

**Bénéfice** : Sécurité supplémentaire sur les migrations complexes.

---

### Option 5.2 : Dashboard de santé
**Durée estimée** : 2h

**Actions** :
- [ ] Page HTML générée par Jenkins
  - État des tests (PHPUnit, Playwright)
  - Couverture de code (tendance)
  - Version déployée sur serveur de test
  - Derniers commits
- [ ] Publier via GitHub Pages ou serveur interne

**Bénéfice** : Visibilité rapide de l'état du projet.

---

### Option 5.3 : Refresh automatique des données de test
**Durée estimée** : 1h

**Actions** :
- [ ] Job Jenkins programmé (hebdomadaire)
- [ ] Régénère base anonymisée depuis prod
- [ ] Redéploie sur serveur de test

**Bénéfice** : Données de test toujours réalistes et à jour.

---

### Option 5.4 : Protection de la branche main
**Durée estimée** : 30min

**Actions** :
- [ ] Configurer GitHub Branch Protection
  - Require status checks (PHPUnit) avant merge
  - Require pull request reviews
- [ ] Workflow de PR avec tests automatiques

**Bénéfice** : Empêche le merge de code cassé sur main.

---

## Alternative : GitHub Actions au lieu de Jenkins

Si Jenkins pose des problèmes de maintenance, GitHub Actions offre une alternative moderne :

### Avantages GitHub Actions
- ✅ Configuration as code (`.github/workflows/ci.yml`)
- ✅ Pas de serveur Jenkins à maintenir
- ✅ Intégration native GitHub (status checks, PR comments)
- ✅ Gratuit pour projets publics, limites généreuses pour privés
- ✅ Marketplace d'actions réutilisables

### Inconvénients
- ❌ Minutes de build limitées (2000min/mois gratuit)
- ❌ Nécessite adaptation si infrastructure Jenkins spécifique
- ❌ Playwright peut nécessiter self-hosted runner pour serveur de test

### Workflow minimal GitHub Actions
```yaml
# .github/workflows/ci.yml
name: CI/CD

# Alternative 1 : Déclenchement sur push (équivalent webhook)
on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

# Alternative 2 : Déclenchement planifié (équivalent polling horaire)
# on:
#   schedule:
#     - cron: '0 * * * *'  # Toutes les heures
#   workflow_dispatch:  # Permet déclenchement manuel

jobs:
  phpunit:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP 7.4
        uses: shivammathur/setup-php@v2
        with:
          php-version: 7.4

      - name: Run PHPUnit
        run: |
          source setenv.sh
          ./run-all-tests.sh --coverage

      - name: Publish coverage
        uses: codecov/codecov-action@v3

  deploy:
    needs: phpunit
    if: github.ref == 'refs/heads/main'
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Deploy to test server
        run: ./bin/deploy_test_server.sh
        env:
          SSH_KEY: ${{ secrets.SSH_PRIVATE_KEY }}

  playwright:
    needs: deploy
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Run Playwright
        run: |
          cd playwright
          npm ci
          npx playwright install --with-deps
          npx playwright test

      - name: Upload test results
        if: always()
        uses: actions/upload-artifact@v3
        with:
          name: playwright-report
          path: playwright/playwright-report/
```

**Recommandation** : Si Jenkins fonctionne bien pour l'analyse statique, rester sur Jenkins. Si problèmes de maintenance, migrer vers GitHub Actions progressivement.

---

## Priorisation recommandée

### Sprint 1 : Valeur immédiate (3-4h)
1. ✅ Phase 1.1a : Job Jenkins PHPUnit tests seuls (1-2h)
2. ✅ Phase 1.1b : Job Jenkins Couverture conditionnelle (1h)
3. ✅ Phase 1.2 : Notifications (30min)

**Livrable** : Tests automatiques à chaque commit + couverture calculée si tests OK

---

### Sprint 2 : Données de test (4-5h)
3. ✅ Phase 2.1 : Script anonymisation (3-4h)
4. ✅ Phase 2.2 : Stockage dump (1h)

**Livrable** : Base de test anonymisée facilement générée

---

### Sprint 3 : Déploiement auto (3-4h)
5. ✅ Phase 3.1 : Script déploiement (2-3h)
6. ✅ Phase 3.2 : Job Jenkins déploiement (1h)

**Livrable** : Serveur de test mis à jour automatiquement

---

### Sprint 4 : Tests E2E (4-5h)
7. ✅ Phase 4.1 : Jenkins Playwright (2h)
8. ✅ Phase 4.2 : Pipeline orchestré (2-3h)

**Livrable** : Pipeline CI/CD complet

---

### Sprint 5+ : Améliorations (optionnel)
9. 🔧 Phase 5.x : Au besoin selon priorités

---

## Investissement total

**Minimum viable (Sprints 1-2)** : 7-9h
**Complet (Sprints 1-4)** : 15-20h
**Avec améliorations (Sprint 5)** : +5-10h

**ROI estimé** : Après 2-3 sprints, économie de temps significative (tests manuels évités) + réduction drastique des régressions.

---

## Points d'attention et risques

### Sécurité
- ⚠️ Clés SSH pour déploiement (Jenkins Credentials ou GitHub Secrets)
- ⚠️ Ne JAMAIS committer le dump anonymisé dans Git (ajouter à `.gitignore`)
- ⚠️ Vérifier l'anonymisation complète (regex pour emails, tél, noms)
- ⚠️ Accès limité au serveur de test (pas de données de production réelles)

### Infrastructure
- ⚠️ Serveur Oracle Free Tier : limites CPU/RAM pour Jenkins
- ⚠️ Playwright nécessite ressources (Chromium headless)
- ⚠️ Taille du dump anonymisé (compression recommandée)
- ⚠️ Bande passante pour transfert dump (planifier transferts nocturnes si gros volume)

### Maintenance
- ⚠️ Garder backup de la base de test avant déploiement
- ⚠️ Stratégie de rollback en cas d'échec de migration
- ⚠️ Monitoring de l'espace disque (dumps, logs Jenkins, artefacts)
- ⚠️ Nettoyage régulier des anciens builds Jenkins

### Tests
- ⚠️ Flaky tests Playwright (réseau, timing) : retry automatique
- ⚠️ Tests longs : parallélisation Playwright si possible
- ⚠️ Base de test : refresh régulier pour données réalistes
- ⚠️ Utilisateurs de test : script de création idempotent

---

## Métriques de succès

### Quantitatives
- ✅ Temps de détection de régression : < 15min (vs plusieurs jours actuellement)
- ✅ Taux d'exécution des tests : 100% des commits (vs manuel occasionnel)
- ✅ Couverture de code : maintenir > 70%
- ✅ Temps de déploiement serveur de test : < 5min (vs manuel 30min+)

### Qualitatives
- ✅ Confiance accrue pour refactoring
- ✅ Détection précoce des régressions
- ✅ Serveur de test toujours fonctionnel et à jour
- ✅ Temps développeur libéré (moins de tests manuels)

---

## Documentation à créer

- [ ] `doc/devops/database_anonymization.md` - Processus d'anonymisation
- [ ] `doc/devops/deployment_process.md` - Procédure de déploiement
- [ ] `doc/devops/jenkins_jobs.md` - Configuration des jobs Jenkins
- [ ] `doc/devops/rollback_procedure.md` - Procédure de rollback
- [ ] `doc/devops/troubleshooting.md` - Problèmes courants et solutions

---

## Checklist de démarrage

Avant de commencer la Phase 1 :

- [ ] Accès Jenkins configuré
- [ ] Dépôt Git accessible depuis Jenkins (credentials si repo privé)
- [ ] PHP 7.4 disponible sur agent Jenkins
- [ ] Tests PHPUnit passent en local
- [ ] Email de notification configuré

Avant de commencer la Phase 3 :

- [ ] Accès SSH au serveur Oracle
- [ ] Clés SSH configurées (sans passphrase pour automatisation)
- [ ] Git configuré sur serveur de test
- [ ] Chemin d'installation GVV sur serveur connu
- [ ] Permissions d'écriture sur répertoires nécessaires

---

## Support et évolution

### En cas de problème
1. Consulter `doc/devops/troubleshooting.md`
2. Vérifier logs Jenkins
3. Tester manuellement les scripts
4. Rollback si nécessaire

### Évolutions futures possibles
- Integration continue sur branches de feature
- Environnements de test multiples (staging, preprod)
- Tests de charge automatisés
- Déploiement production automatisé (avec validation manuelle)
- Monitoring applicatif (Sentry, New Relic)

---

**Document maintenu par** : Frédéric (dev solo)
**Dernière mise à jour** : 2025-12-05
**Version** : 1.0
