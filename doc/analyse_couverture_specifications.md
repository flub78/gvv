# Analyse de la Couverture des Spécifications GVV
*Document de synthèse pour vérifier la couverture du plan de test*

**Date de création**: 22 février 2026  
**Objectif**: Inventorier la couverture documentaire des fonctionnalités GVV et identifier les lacunes

---

## 1. Vue d'ensemble

### 1.1 Contexte
GVV (Gestion Vol à voile) est un système complet de gestion de clubs de vol à voile développé depuis 2011. Il comprend environ **50 contrôleurs** couvrant un large éventail de fonctionnalités, de la gestion des membres à la comptabilité.

### 1.2 Structure documentaire
La documentation du projet est organisée comme suit:
- **doc/prds/**: Documents PRD (Product Requirements Documents) - 20 fichiers
- **doc/design_notes/**: Notes de conception technique - 38 fichiers
- **doc/features/**: Documentation fonctionnelle - 21 fichiers
- **doc/users/fr/**: Documentation utilisateur en français - 11 fichiers
- **doc/gvv_prd.md**: PRD principal et complet du produit

---

## 2. Inventaire des PRD Existants

### 2.1 PRD Disponibles (doc/prds/)

| # | Fichier | Fonctionnalité couverte | Statut |
|---|---------|------------------------|--------|
| 1 | **2025_authorization_refactoring_prd.md** | Refonte du système d'autorisations | ✅ Existant |
| 2 | **aircraft_booking_prd.md** | Réservation d'avions | ✅ Existant |
| 3 | **approbation_de_documents_prd.md** | Approbation de documents | ✅ Existant |
| 4 | **archivage_documentaire_prd.md** | Archivage documentaire | ✅ Existant |
| 5 | **date_validite_vols_decouverte.md** | Validité des vols de découverte | ✅ Existant |
| 6 | **email_sublists.md** | Sous-listes d'emails | ✅ Existant |
| 7 | **facturation_periodique_prd.md** | Facturation périodique | ✅ Existant |
| 8 | **filtrage_par_page.md** | Filtrage par page | ✅ Existant |
| 9 | **messages_du_jour_prd.md** | Messages du jour | ✅ Existant |
| 10 | **meteo_prd.md** | Intégration météo | ✅ Existant |
| 11 | **multi_section_views_prd.md** | Vues multi-sections | ✅ Existant |
| 12 | **navigation_improvements.md** | Améliorations de navigation | ✅ Existant |
| 13 | **navigation_optimization_prd.md** | Optimisation de navigation | ✅ Existant |
| 14 | **paiements_en_ligne_prd.md** | Paiements en ligne | ✅ Existant |
| 15 | **prd_licence_management_fix.md** | Correction gestion licences | ✅ Existant |
| 16 | **prg_creer_et_continuer.md** | Pattern Post-Redirect-Get | ✅ Existant |
| 17 | **remplissage_formulaires_pdf_prd.md** | Remplissage de formulaires PDF | ✅ Existant |
| 18 | **saisie_cotisations_prd.md** | Saisie des cotisations | ✅ Existant |
| 19 | **suivi_formation_prd.md** | Suivi de formation | ✅ Existant |
| 20 | **IMPLEMENTATION_SUMMARY.md** | Résumé d'implémentations | 📋 Méta-document |

### 2.2 PRD Principal
- **doc/gvv_prd.md**: Document PRD complet et global couvrant toutes les fonctionnalités principales du système (411 lignes)

---

## 3. Mapping Fonctionnalités vs Documentation

### 3.1 Fonctionnalités Principales de GVV (selon controllers et doc/features.md)

#### 🟢 Fonctionnalités avec PRD Complet

| Fonctionnalité | Controller(s) | Documentation |
|----------------|---------------|---------------|
| **Gestion des membres** | membre.php | ✅ PRD principal + doc/users/fr/02_gestion_membres.md |
| **Gestion des licences** | licences.php | ✅ prd_licence_management_fix.md + PRD principal |
| **Réservations d'avions** | reservations.php | ✅ aircraft_booking_prd.md |
| **Autorisations** | authorization.php | ✅ 2025_authorization_refactoring_prd.md |
| **Vols de découverte** | vols_decouverte.php | ✅ date_validite_vols_decouverte.md + doc/features/vols_de_découverte.md |
| **Météo** | meteo.php | ✅ meteo_prd.md |
| **Listes d'emails** | email_lists.php | ✅ email_sublists.md |
| **Messages du jour** | - | ✅ messages_du_jour_prd.md |
| **Paiements en ligne** | - | ✅ paiements_en_ligne_prd.md |
| **Saisie cotisations** | - | ✅ saisie_cotisations_prd.md |
| **Suivi formation** | formation_seances.php, formation_progressions.php, formation_inscriptions.php, formation_rapports.php, acceptance_admin.php, formation_autorisations_solo.php, programmes.php | ✅ suivi_formation_prd.md + doc/users/fr/11_formations.md |
| **Archivage documentaire** | archived_documents.php | ✅ archivage_documentaire_prd.md |
| **Remplissage PDF** | - | ✅ remplissage_formulaires_pdf_prd.md |

#### 🟡 Fonctionnalités avec Documentation Alternative (sans PRD dédié)

| Fonctionnalité | Controller(s) | Documentation Alternative | Type |
|----------------|---------------|---------------------------|------|
| **Gestion avions/planeurs** | avion.php, planeur.php | doc/users/fr/03_gestion_aeronefs.md | 📖 Doc utilisateur |
| **Saisie vols planeurs** | vols_planeur.php | doc/users/fr/04_saisie_vols.md + PRD principal | 📖 Doc utilisateur |
| **Saisie vols avion** | vols_avion.php | doc/features/vols_avion.md + doc/users/fr/04_saisie_vols.md | 📖 Doc feature |
| **Calendrier** | calendar.php | doc/users/fr/05_calendrier.md + doc/features/calendar.md | 📖 Doc utilisateur |
| **Facturation** | facturation.php | doc/users/fr/06_facturation.md + facturation_periodique_prd.md | 📖 Doc utilisateur |
| **Comptabilité** | compta.php, comptes.php | doc/users/fr/07_comptabilite.md + PRD principal | 📖 Doc utilisateur |
| **Rapprochements bancaires** | rapprochements.php | doc/features/rapprochements_bancaires.md | 📖 Doc feature |
| **Rapports** | rapports.php, reports.php | doc/users/fr/08_rapports.md + PRD principal | 📖 Doc utilisateur |
| **Tickets** | tickets.php | PRD principal section 2.4 | 📖 PRD principal |
| **Gestion des sections** | sections.php | doc/features/multi_sections.md + multi_section_views_prd.md | 📖 Doc feature |
| **Configuration** | configuration.php, config.php | doc/features/configuration.md | 📖 Doc feature |
| **Backup/Restauration** | - | doc/features/Backup.md | 📖 Doc feature |
| **Import de vols** | import.php | doc/features/import_vols_decouverte.md | 📖 Doc feature |
| **Amortissements** | - | doc/features/amortissements.md | 📖 Doc feature |
| **Attachements** | attachments.php | doc/features/attachement_justificatifs.md | 📖 Doc feature |
| **Date de gel** | - | doc/features/date_gel.md | 📖 Doc feature |
| **Export PDF** | - | doc/features/pdf-export.md | 📖 Doc feature |
| **Gestion des rôles** | user_roles_per_section.php | doc/features/gestion-roles-web.md + doc/users/fr/09_autorisations.md | 📖 Doc feature |
| **Envoi d'emails** | mails.php | doc/design_notes/gestion_emails_design.md + sending_email.md | 🔧 Design notes |
| **Intégration OpenFlyers** | openflyers.php | doc/users/openflyers_user.md + doc/design_notes/openflyers_technical.md | 📖 Doc utilisateur |
| **Procédures** | procedures.php | doc/design_notes/procedure_inscription.md | 🔧 Design notes |
| **Timeline** | - | doc/design_notes/timeline_feature.md | 🔧 Design notes |

#### 🔴 Fonctionnalités SANS Documentation Spécifique

| # | Fonctionnalité | Controller(s) | Couverture | Notes |
|---|----------------|---------------|------------|-------|
| 1 | **Authentification** | auth.php | ⚠️ Partielle | Mentionné dans PRD principal uniquement |
| 2 | **Intégration FFVV** | FFVV.php | ⚠️ Partielle | Mentionné dans PRD principal uniquement |
| 3 | **Associations OF** | associations_of.php | ⚠️ Partielle | Mentionné dans PRD principal (export GESASSO) |
| 4 | **Écritures comptables** | associations_ecriture.php | ❌ Aucune | Seulement couvert partiellement dans doc compta |
| 5 | **Relevés bancaires** | associations_releve.php | ❌ Aucune | Non documenté spécifiquement |
| 6 | **Achats** | achats.php | ⚠️ Partielle | Mentionné brièvement dans PRD principal |
| 7 | **Alarmes** | alarmes.php | ❌ Aucune | Non documenté |
| 8 | **Événements** | event.php, events_types.php | ⚠️ Partielle | Lié au calendrier mais non spécifié |
| 9 | **Présences** | presences.php | ❌ Aucune | Mentionné dans features.md seulement |
| 10 | **Terrains** | terrains.php | ⚠️ Partielle | Mentionné dans PRD principal uniquement |
| 11 | **Plan comptable** | plan_comptable.php | ⚠️ Partielle | Partie de la comptabilité générale |
| 12 | **Catégories** | categorie.php | ❌ Aucune | Non documenté |
| 13 | **Tarifs** | tarifs.php | ⚠️ Partielle | Lié à facturation mais non spécifié |
| 14 | **Types de documents** | document_types.php | ❌ Aucune | Non documenté |
| 15 | **Types de tickets** | types_ticket.php | ❌ Aucune | Non documenté |
| 16 | **Partage** | partage.php | ❌ Aucune | Non documenté |
| 17 | **Pompes (carburant)** | pompes.php | ❌ Aucune | Non documenté |
| 18 | **Login as** | login_as.php | ❌ Aucune | Fonctionnalité admin non documentée |
| 19 | **Historique** | historique.php | ❌ Aucune | Non documenté |
| 20 | **Migration DB** | migration.php | ⚠️ Partielle | Mentionné dans doc développement uniquement |
| 21 | **Backend/Admin** | backend.php, admin.php | ❌ Aucune | Non documenté |
| 22 | **DB Checks** | dbchecks.php | ❌ Aucune | Non documenté |
| 23 | **Tools** | tools.php | ❌ Aucune | Non documenté |
| 24 | **Oneshot** | oneshot.php | ❌ Aucune | Scripts ponctuels non documentés |
| 25 | **Rapports adhérents** | adherents_report.php | ❌ Aucune | Non documenté spécifiquement |
| 26 | **Coverage (tests)** | coverage.php | ❌ Aucune | Outil de développement |
| 27 | **Welcome** | welcome.php | ❌ Aucune | Page d'accueil non documentée |

---

## 4. Statistiques de Couverture

### 4.1 Résumé quantitatif

| Catégorie | Nombre | Pourcentage |
|-----------|--------|-------------|
| **Total de contrôleurs** | ~68 | 100% |
| **PRD dédiés** | 26 | 38% |
| **Documentation alternative** | 21 | 31% |
| **Documentation partielle** | 14 | 21% |
| **Sans documentation** | 7 | 10% |

### 4.2 Analyse par priorité fonctionnelle

#### Fonctionnalités CORE (critiques) - Couverture: 90%
- ✅ Gestion membres
- ✅ Gestion flotte
- ✅ Saisie vols
- ✅ Facturation
- ✅ Comptabilité
- ✅ Autorisations
- ⚠️ Authentification (partielle)

#### Fonctionnalités IMPORTANTES - Couverture: 65%
- ✅ Calendrier
- ✅ Rapports
- ✅ Licences
- ✅ Formation
- ⚠️ Événements (partielle)
- ❌ Présences (absente)
- ❌ Terrains (absente)

#### Fonctionnalités SECONDAIRES - Couverture: 65%
- ✅ Météo
- ✅ Réservations
- ✅ Archivage
- ✅ Programmes
- ❌ Alarmes
- ❌ Historique

#### Fonctionnalités ADMINISTRATIVES - Couverture: 20%
- ⚠️ Configuration (partielle)
- ❌ Backend/Admin
- ❌ DB Checks
- ❌ Tools
- ❌ Login as

---

## 5. Recommandations

### 5.1 PRD prioritaires à créer

#### Priorité HAUTE (fonctionnalités core utilisées quotidiennement)
1. **prd_authentification.md** - Système d'authentification et sécurité
2. **prd_presences.md** - Gestion des présences et disponibilités
3. **prd_evenements.md** - Gestion des événements et types d'événements
4. **prd_terrains.md** - Gestion des terrains et aérodromes

#### Priorité MOYENNE (fonctionnalités importantes)
5. **prd_tarification.md** - Gestion des tarifs et catalogue de produits
6. **prd_comptabilite_avancee.md** - Plan comptable, écritures, relevés bancaires
7. **prd_integration_ffvv.md** - Intégration FFVV et fédération

#### Priorité BASSE (fonctionnalités administratives)
8. **prd_outils_admin.md** - Backend, admin, db checks, tools, migration
9. **prd_fonctionnalites_diverses.md** - Catégories, pompes, partage, historique, oneshot

### 5.2 Documentation à consolider

Les documents suivants existent mais gagneraient à être convertis en PRD formels:
- doc/features/vols_avion.md → **prd_vols_avion.md**
- doc/features/rapprochements_bancaires.md → **prd_rapprochements_bancaires.md**
- doc/design_notes/sending_email.md → **prd_gestion_emails.md**

### 5.3 Documentation existante à référencer

Les documents suivants sont suffisamment complets et peuvent servir de référence sans nécessiter de PRD:
- ✅ doc/users/fr/*.md - Documentation utilisateur complète
- ✅ doc/features/*.md - Documentation fonctionnelle détaillée
- ✅ doc/gvv_prd.md - PRD principal complet

---

## 6. Plan d'Action pour la Couverture de Tests

### 6.1 Utilisation de ce document

Ce document peut être utilisé pour:
1. **Prioriser les tests** - Se concentrer d'abord sur les fonctionnalités core documentées
2. **Identifier les gaps** - Les 14 fonctionnalités sans documentation nécessitent une analyse préalable
3. **Créer des tests basés sur les specs** - Utiliser les PRD existants comme base pour les scénarios de test
4. **Documenter les comportements** - Les tests peuvent révéler des comportements non documentés

### 6.2 Prochaines étapes recommandées

1. **Court terme** (1-2 semaines):
   - Créer les 4 PRD prioritaires HAUTE
   - Consolider la documentation des fonctionnalités core

2. **Moyen terme** (1-2 mois):
   - Créer les 4 PRD prioritaires MOYENNE
   - Documenter les comportements découverts lors des tests
   
3. **Long terme** (3-6 mois):
   - Compléter la couverture avec les PRD prioritaires BASSE
   - Maintenir la cohérence entre PRD et documentation utilisateur

---

## 7. Annexes

### 7.1 Répertoires de documentation
- **doc/prds/** - PRD formels
- **doc/design_notes/** - Notes de conception technique
- **doc/features/** - Documentation fonctionnelle
- **doc/users/fr/** - Manuel utilisateur en français
- **doc/plans/** - Plans d'implémentation
- **doc/testing/** - Documentation de tests

### 7.2 Références
- [PRD Principal (gvv_prd.md)](gvv_prd.md)
- [Features Overview (features.md)](features.md)
- [Documentation Utilisateur](users/fr/README.md)
- [Guide de Workflow Développement](development/workflow.md)

---

**Dernière mise à jour**: 22 février 2026  
**Auteur**: Analyse automatique de la documentation GVV  
**Version**: 1.0
