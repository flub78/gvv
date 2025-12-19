# Documentation DevOps - GVV

**Mise à jour** : 2025-12-19

---

## Vue d'ensemble

Cette documentation décrit l'automatisation des tests PHPUnit sur Jenkins avec une base de données anonymisée et chiffrée.

---

## Documents disponibles

### 📋 Guide rapide (commencez ici)
**[QUICKSTART.md](QUICKSTART.md)**
- Vue d'ensemble en 5 minutes
- Commandes essentielles
- Dépannage rapide
- **Recommandé pour démarrer**

---

### 📖 Plan détaillé
**[ci_cd_plan.md](ci_cd_plan.md)**
- Architecture complète
- Phase 1 : Génération base anonymisée (3-4h)
  - Scripts d'export avec chiffrement GPG
  - Alternative contrôleur PHP
- Phase 2 : Configuration Jenkins (2-3h)
  - Stockage passphrase
  - Configuration job
  - Notifications
- Phase 3 : Maintenance
  - Quand regénérer la base
  - Améliorer l'anonymisation
- Checklists et dépannage

**Utilisez ce document pour** : Implémentation complète pas à pas

---

### 📝 Changelog
**[CHANGELOG_PLAN_SIMPLIFIE.md](CHANGELOG_PLAN_SIMPLIFIE.md)**
- Modifications par rapport au plan initial
- Justification des choix
- Comparaison avant/après
- Phases optionnelles reportées

**Utilisez ce document pour** : Comprendre les décisions de simplification

---

### 🗄️ Archive - Installation MySQL (obsolète)
**[jenkins_database_setup.md](jenkins_database_setup.md)**
- Installation détaillée de MySQL/MariaDB
- Installation phpMyAdmin
- Configuration base de données

**Statut** : Non nécessaire (MySQL déjà installé sur Jenkins)  
**Conservation** : Pour référence future si serveur Jenkins réinstallé

---

## Workflow recommandé

### 1️⃣ Première lecture
1. Lire **QUICKSTART.md** (5 min)
2. Comprendre le workflow général
3. Identifier les étapes principales

### 2️⃣ Implémentation
1. Suivre **ci_cd_plan.md** - Phase 1
   - Créer script d'export
   - Générer base chiffrée
   - Commiter dans Git
2. Suivre **ci_cd_plan.md** - Phase 2
   - Configurer Jenkins
   - Tester le job
   - Valider les notifications

### 3️⃣ Maintenance
1. Consulter **ci_cd_plan.md** - Phase 3
   - Regénération base si schéma change
   - Amélioration anonymisation

---

## Structure des fichiers

```
doc/devops/
├── README.md                          # Ce fichier
├── QUICKSTART.md                       # ⭐ Démarrer ici
├── ci_cd_plan.md                       # Plan détaillé
├── CHANGELOG_PLAN_SIMPLIFIE.md         # Justification des choix
└── jenkins_database_setup.md           # Archive (obsolète)

bin/
├── export_anonymized_db.sh             # À créer (Phase 1)
├── restore_test_db.sh                  # À créer (Phase 2)
└── create_test_users.sql               # Existant (utilisateurs de test)

test_data/
└── gvv_test.sql.gpg                    # À générer (Phase 1)
```

---

## Objectifs du projet

### ✅ Objectif principal
**Tests PHPUnit automatisés** à chaque commit avec base de données réaliste

### 🎯 Avantages
1. **Détection rapide** : Régressions identifiées en 15-20 minutes
2. **Base sûre** : Données anonymisées + chiffrées dans Git
3. **Simplicité** : Maintenance minimale (regénération rare)
4. **Évolutivité** : Phases avancées disponibles si besoin

### 📊 Investissement
- **Installation** : 5-7h
- **Maintenance** : 15-30min par modification de schéma
- **ROI** : Économie de temps dès la première régression détectée

---

## Support et dépannage

### Problèmes courants
Consultez :
- **QUICKSTART.md** - Section "Dépannage rapide"
- **ci_cd_plan.md** - Section "Dépannage" (détaillée)

### Logs Jenkins
```
Jenkins → GVV-PHPUnit-Tests → Console Output
```

### Tests manuels
```bash
# Sur le serveur Jenkins
export GVV_TEST_DB_PASSPHRASE="votre_passphrase"
./bin/restore_test_db.sh
source setenv.sh
./run-all-tests.sh
```

---

## Évolutions futures (optionnelles)

### Court terme
- [ ] Couverture de code (job séparé, +1h)
- [ ] Amélioration anonymisation (patterns additionnels)

### Moyen terme
- [ ] Déploiement automatique serveur test (+3-4h)
- [ ] Dashboard de santé Jenkins (+2h)

### Long terme
- [ ] Tests Playwright automatisés (+4-5h)
- [ ] Protection branche main GitHub (+30min)
- [ ] GitHub Actions (alternative à Jenkins)

**Détails** : Voir `ci_cd_plan.md` - Section "Prochaines étapes optionnelles"

---

## Historique

| Date | Version | Description |
|------|---------|-------------|
| 2025-12-05 | 1.0 | Plan initial complet (10-13h) |
| 2025-12-19 | 2.0 | Plan simplifié (5-7h) + Quick Start |

---

## Références

### Documentation GVV
- `README.md` - Vue d'ensemble du projet
- `TESTING.md` - Documentation des tests
- `doc/AI_INSTRUCTIONS.md` - Instructions générales
- `doc/development/workflow.md` - Workflow de développement

### Outils
- [Jenkins Documentation](https://www.jenkins.io/doc/)
- [PHPUnit](https://phpunit.de/documentation.html)
- [GPG Manual](https://www.gnupg.org/documentation/manuals/gnupg/)

---

**Auteur** : Frédéric (dev solo)  
**Assistance** : Claude Code  
**Contact** : Voir README.md principal du projet
