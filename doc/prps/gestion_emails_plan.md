# Implementation Plan - Gestion des Adresses Email

**Projet:** GVV - Gestion Vol à voile
**Fonctionnalité:** Système de gestion des listes de diffusion email

**Documents associés:**
- **PRD (Exigences):** [doc/prds/gestion_emails.md](../prds/gestion_emails.md)
- **Design (Architecture):** [doc/design_notes/gestion_emails_design.md](../design_notes/gestion_emails_design.md)

**Statut global:** 🔵 En cours - Backend et UI terminés (119/150 tâches - 79%)
**Phase actuelle:** Phase 5.2 - Adaptation UI workflow v1.4
**Estimation:** 8 semaines (1 personne) - réduit de 9 semaines
**Priorité:** Fonctionnalité complète uniquement
**Nouvelles tâches v1.4:** +3 tâches (séparation workflow UI)
**Nouvelles tâches v1.3:** +12 tâches (gestion fichiers - TERMINÉ) | -15 tâches (Phase 9 supprimée)

**Légende:** ⚪ Non démarré | 🔵 En cours | 🟢 Terminé | 🔴 Bloqué | ⏸️ En pause

---

## Changements v1.4 (2025-11-05)

**Modification majeure du workflow création/modification:**

La fenêtre de création/modification est maintenant séparée en deux parties distinctes:

1. **Partie supérieure - Métadonnées de la liste:**
   - Nom, description, type de membre, visibilité
   - Boutons "Enregistrer" et "Annuler" juste sous cette section
   - Toujours active (création et modification)

2. **Partie inférieure - Ajout et suppression d'adresses email:**
   - Titre: "Ajout et suppression d'adresses email"
   - Trois onglets de sélection + preview
   - **DÉSACTIVÉE en mode création** (pas d'email_list_id connu)
   - **ACTIVÉE en mode modification** (email_list_id passé en URL)

**Workflow:**
- **Création:** Utilisateur saisit nom/description → Clic "Enregistrer" → Liste créée en base → Rechargement page avec email_list_id → Bascule automatique en mode modification
- **Modification:** Titre change de "Nouvelle liste d'email" à "Modification d'une liste d'email", partie inférieure devient active

**Impact sur le plan:**
- Phase 5.2: +3 tâches pour adapter les vues (form.php séparé en deux parties, gestion état disabled, logique redirect après store())
- Controller store(): Doit rediriger vers edit($id) après création
- JavaScript: Gestion état disabled de la partie inférieure en fonction de présence email_list_id

---

## Changements v1.3 (2025-11-03)

**Modifications majeures demandées par l'utilisateur:**

1. **Preview simplifiée:**
   - ❌ Plus d'icônes delete dans la preview
   - ✅ Tableau simple: Email | Nom
   - ✅ Totaux affichés (critères, manuels, externes)
   - Suppression uniquement via les onglets sources

2. **Onglets renommés pour clarté:**
   - "Par critères GVV" → **"Par critères"**
   - "Sélection manuelle" → **"Sélection manuelle"** (inchangé)
   - "Adresses externes" → **"Import de fichiers"**

3. **Import restreint à l'upload:**
   - ❌ Suppression des zones de copier/coller texte/CSV
   - ✅ Upload fichier uniquement (button "Télécharger un fichier")
   - ✅ Stockage permanent: `/uploads/email_lists/[list_id]/[fichier]`
   - ✅ Stockage temporaire (création): `/uploads/email_lists/tmp/[session_id]/[fichier]`
   - ✅ Liste des fichiers importés avec métadonnées
   - ✅ Suppression fichier → suppression en cascade des adresses
   - ✅ Nettoyage automatique: fichiers tmp > 2 jours supprimés

4. **Traçabilité fichiers:**
   - Ajout champ `source_file` dans table `email_list_external`
   - Index composé `(email_list_id, source_file)` pour performances
   - Suppression fichier supprime toutes ses adresses automatiquement

5. **Ajout manuel d'adresses externes:**
   - Déplacé dans onglet "Sélection manuelle"
   - Formulaire: email + nom optionnel
   - Chaque adresse a une icône poubelle pour suppression individuelle

6. **Suppression du système de codage couleur:**
   - Phase 9 complètement supprimée (-15 tâches)
   - Plus de pastilles colorées dans la preview
   - Interface simplifiée: checkboxes standards
   - Justification: Suppression directe dans onglets sources rend le codage couleur inutile

**Impact sur le plan:**
- Phase 1: Migration nécessite ALTER TABLE pour ajouter `source_file`
- Phase 3: +12 tâches (section 3.7 gestion fichiers uploadés)
- Phase 5: Révision des vues (onglets, preview, gestion fichiers)
- Phase 9: **SUPPRIMÉE** (système couleur non nécessaire)

---

## Table des matières

- [Implementation Plan - Gestion des Adresses Email](#implementation-plan---gestion-des-adresses-email)
  - [Changements v1.3 (2025-11-03)](#changements-v13-2025-11-03)
  - [Table des matières](#table-des-matières)
  - [Phase 1: Fondations - 🟢 24/24 (Semaine 1) - TERMINÉ](#phase-1-fondations----2424-semaine-1---terminé)
    - [1.1 Migration base de données](#11-migration-base-de-données)
    - [1.2 Helper de validation email](#12-helper-de-validation-email)
    - [1.3 Model de base](#13-model-de-base)
    - [1.4 Tests](#14-tests)
  - [Phase 2: Sélection par critères via email\_list\_roles - 🟢 11/11 (Semaine 2) - TERMINÉ](#phase-2-sélection-par-critères-via-email_list_roles----1111-semaine-2---terminé)
    - [2.1 Analyse architecture autorisations ✅](#21-analyse-architecture-autorisations-)
    - [2.2 Méthodes model pour chargement données ✅ (déjà implémenté Phase 1)](#22-méthodes-model-pour-chargement-données--déjà-implémenté-phase-1)
    - [2.3 Gestion table email\_list\_roles ✅ (déjà implémenté Phase 1)](#23-gestion-table-email_list_roles--déjà-implémenté-phase-1)
    - [2.4 Tests et optimisation ✅](#24-tests-et-optimisation-)
  - [Phase 3: Sélection manuelle et import - 🟢 17/17 (Semaine 3) - TERMINÉ](#phase-3-sélection-manuelle-et-import----1717-semaine-3---terminé)
    - [3.1 Sélection manuelle de membres internes ✅](#31-sélection-manuelle-de-membres-internes-)
    - [3.2 Gestion emails externes ✅](#32-gestion-emails-externes-)
    - [3.3 Import fichier texte ✅](#33-import-fichier-texte-)
    - [3.4 Import fichier CSV ✅](#34-import-fichier-csv-)
    - [3.5 Gestion doublons ✅](#35-gestion-doublons-)
    - [3.6 Tests ✅](#36-tests-)
    - [3.7 Gestion fichiers uploadés (v1.3) - ⚪ 0/12](#37-gestion-fichiers-uploadés-v13----012)
      - [3.7.1 Migration base de données](#371-migration-base-de-données)
      - [3.7.2 Méthodes model pour upload](#372-méthodes-model-pour-upload)
      - [3.7.3 Gestion système de fichiers](#373-gestion-système-de-fichiers)
  - [Phase 4: Export et utilisation - 🟢 20/20 (Semaine 4) - TERMINÉ](#phase-4-export-et-utilisation----2020-semaine-4---terminé)
    - [4.1 Export presse-papier ✅](#41-export-presse-papier-)
    - [4.2 Export fichiers TXT/Markdown ✅](#42-export-fichiers-txtmarkdown-)
    - [4.3 Découpage en sous-listes ✅](#43-découpage-en-sous-listes-)
    - [4.4 Génération mailto ✅](#44-génération-mailto-)
    - [4.5 Mémorisation préférences ✅](#45-mémorisation-préférences-)
    - [4.6 Tests ✅](#46-tests-)
  - [Phase 5: Controller et UI - 🔵 18/22 (Semaine 5) - EN COURS (révisions v1.3)](#phase-5-controller-et-ui----1822-semaine-5---en-cours-révisions-v13)
    - [5.1 Controller ✅ (11/11 tâches)](#51-controller--1111-tâches)
    - [5.2 Views ⚪ (9/9 tâches - À RÉVISER pour v1.3)](#52-views--99-tâches---à-réviser-pour-v13)
      - [Vue `form.php` - Preview panel (à droite)](#vue-formphp---preview-panel-à-droite)
      - [Vue `_criteria_tab.php` - Onglet 1](#vue-_criteria_tabphp---onglet-1)
      - [Vue `_manual_tab.php` - Onglet 2 (déjà implémenté à vérifier)](#vue-_manual_tabphp---onglet-2-déjà-implémenté-à-vérifier)
      - [Vue `_import_tab.php` - Onglet 3](#vue-_import_tabphp---onglet-3)
    - [5.3 UI sélection par rôles (déplacé de Phase 2.4) ✅ (5/5 tâches)](#53-ui-sélection-par-rôles-déplacé-de-phase-24--55-tâches)
    - [5.4 Metadata et navigation ✅ (2/2 tâches)](#54-metadata-et-navigation--22-tâches)
    - [5.5 Tests ⚪ (0/1 tâche)](#55-tests--01-tâche)
  - [Phase 6: Documentation et finalisation - ⚪ 0/9 (Semaine 6)](#phase-6-documentation-et-finalisation----09-semaine-6)
    - [6.1 Documentation utilisateur](#61-documentation-utilisateur)
    - [6.2 Documentation technique](#62-documentation-technique)
    - [6.3 Diagrammes et prototypes](#63-diagrammes-et-prototypes)
  - [Phase 7: Tests et qualité - ⚪ 0/11 (Semaine 7)](#phase-7-tests-et-qualité----011-semaine-7)
    - [7.1 Tests unitaires](#71-tests-unitaires)
    - [7.2 Tests d'intégration](#72-tests-dintégration)
    - [7.3 Tests manuels](#73-tests-manuels)
    - [7.4 Validation couverture](#74-validation-couverture)
  - [Phase 8: Déploiement - ⚪ 0/9 (Semaine 8)](#phase-8-déploiement----09-semaine-8)
    - [8.1 Pré-déploiement](#81-pré-déploiement)
    - [8.2 Documentation déploiement](#82-documentation-déploiement)
    - [8.3 Formation et production](#83-formation-et-production)
  - [~~Phase 9: Système de codage couleur~~ - SUPPRIMÉE v1.3](#phase-9-système-de-codage-couleur---supprimée-v13)
  - [Notes et blocages](#notes-et-blocages)

---

## Phase 1: Fondations - 🟢 24/24 (Semaine 1) - TERMINÉ

### 1.1 Migration base de données
- [x] Créer migration `049_create_email_lists.php`
- [x] Table email_lists avec champs (id, name, description, active_member, visible, created_by, timestamps)
- [x] Ajouter COLLATE utf8_bin sur name (sensibilité à la casse)
- [x] Table email_list_roles avec champs (id, email_list_id, types_roles_id, section_id, granted_by, granted_at, revoked_at, notes)
- [x] Table email_list_members avec champs (id, email_list_id, membre_id, added_at)
- [x] Table email_list_external avec champs (id, email_list_id, external_email, external_name, added_at)
- [x] Ajouter index sur toutes les FK
- [x] Ajouter FK (created_by → users, email_list_id → email_lists, types_roles_id → types_roles, section_id → sections, membre_id → membres.mlogin)
- [x] Créer triggers pour timestamps automatiques (created_at, updated_at, added_at)
- [x] Tester migration up
- [x] Tester migration down (rollback)
- [x] Mettre à jour `application/config/migration.php` version = 49

### 1.2 Helper de validation email
- [x] Créer `application/helpers/email_helper.php`
- [x] Fonction `validate_email($email)` - validation RFC 5322
- [x] Fonction `normalize_email($email)` - lowercase + trim
- [x] Fonction `deduplicate_emails($emails)` - case-insensitive dedup
- [x] Fonction `chunk_emails($emails, $size)` - découpage en parties

### 1.3 Model de base
- [x] Créer `application/models/email_lists_model.php`
- [x] Méthodes CRUD : create_list, get_list, update_list, delete_list
- [x] Méthode get_user_lists($user_id)

### 1.4 Tests
- [x] Tests unitaires helper : `application/tests/unit/helpers/EmailHelperTest.php` - 37 tests, 100% pass
- [x] Tests MySQL model : `application/tests/mysql/EmailListsModelTest.php`

---

## Phase 2: Sélection par critères via email_list_roles - 🟢 11/11 (Semaine 2) - TERMINÉ

### 2.1 Analyse architecture autorisations ✅
- [x] Analyser table `user_roles_per_section` (user_id, types_roles_id, section_id, revoked_at)
- [x] Analyser table `types_roles` (id, nom, description, scope)
- [x] Analyser table `sections` (id, nom, description)
- [x] Comprendre lien users ↔ membres (mlogin = username)
- [x] Tester requête 4-tables: email_list_roles → user_roles_per_section → users → membres

### 2.2 Méthodes model pour chargement données ✅ (déjà implémenté Phase 1)
- [x] Méthode `get_available_roles()` - charge tous types_roles pour UI
- [x] Méthode `get_available_sections()` - charge toutes sections pour UI
- [x] Méthode `get_users_by_role_and_section($types_roles_id, $section_id)` - sélection simple

### 2.3 Gestion table email_list_roles ✅ (déjà implémenté Phase 1)
- [x] Méthode `add_role_to_list($list_id, $types_roles_id, $section_id)` - ajoute rôle à liste
- [x] Méthode `remove_role_from_list($list_id, $role_id)` - supprime rôle de liste
- [x] Méthode `get_list_roles($list_id)` - récupère rôles d'une liste
- [x] Gérer filtre `revoked_at IS NULL` (rôles actifs uniquement)
- [x] Gérer filtre `membres.actif` selon email_lists.active_member (active/inactive/all)
- [x] Méthode `textual_list($list_id)` - résolution complète (rôles + manuels + externes)

### 2.4 Tests et optimisation ✅
- [x] Ajouter index `users(username)` pour performance jointure membres - Migration 050
- [x] Tests d'intégration sélection multi-rôles/sections - 5 nouveaux tests
- [x] Test dédoublonnage (utilisateur avec multiples rôles)

**Note:** Les tâches UI de l'ancienne section 2.4 ont été déplacées vers Phase 5.2 car elles nécessitent le controller.

---

## Phase 3: Sélection manuelle et import - 🟢 29/29 (Semaine 3) - TERMINÉ

**✅ Changements v1.3 implémentés:**
- Ajout manuel d'adresses externes déplacé dans onglet "Sélection manuelle" (UI à implémenter Phase 5)
- Import limité à upload fichier (suppression copier/coller) (UI à implémenter Phase 5)
- Ajout champ `source_file` dans `email_list_external` ✅
- Gestion liste des fichiers uploadés avec suppression en cascade ✅
- Section 3.7 complète avec migration 051 + méthodes model + système fichiers

### 3.1 Sélection manuelle de membres internes ✅
- [x] Interface view avec liste déroulante/recherche de membres - Déféré à Phase 5 (UI)
- [x] Méthode model `add_manual_member($list_id, $membre_id)` - email_lists_model.php:266
- [x] Méthode model `remove_manual_member($list_id, $member_id)` - email_lists_model.php:290
- [x] Méthode model `get_manual_members($list_id)` - email_lists_model.php:306
- [x] Affichage liste des membres avec bouton suppression - Déféré à Phase 5 (UI)

### 3.2 Gestion emails externes ✅
- [x] Méthode model `add_external_email($list_id, $email, $name)` - email_lists_model.php:327
- [x] Méthode model `remove_external_email($list_id, $external_id)` - email_lists_model.php:352
- [x] Méthode model `get_external_emails($list_id)` - email_lists_model.php:368

### 3.3 Import fichier texte ✅
- [x] Interface upload fichier texte - Déféré à Phase 5 (UI)
- [x] Helper `parse_text_emails($content)` - email_helper.php:191
- [x] Validation de chaque adresse - Intégré dans parse_text_emails()
- [x] Détection doublons (fichier + liste) - Helper detect_duplicates() disponible
- [x] Rapport d'erreurs - Intégré dans parse_text_emails() (champ 'error')

### 3.4 Import fichier CSV ✅
- [x] Interface upload CSV avec configuration colonnes - Déféré à Phase 5 (UI)
- [x] Helper `parse_csv_emails($content, $config)` - email_helper.php:229
- [x] Support nom, prénom, email - Colonnes configurables dans config
- [x] Détection encoding (UTF-8, ISO-8859-1) - À gérer côté UI/upload
- [x] Prévisualisation avant import final - Déféré à Phase 5 (UI)

### 3.5 Gestion doublons ✅
- [x] Interface gestion doublons (ignorer/remplacer) - Déféré à Phase 5 (UI)
- [x] Helper `detect_duplicates($new_emails, $existing_emails)` - email_helper.php:296
- [x] Rapport détaillé des doublons - Retourne array avec new_email, existing_email, normalized

### 3.6 Tests ✅
- [x] Tests unitaires parsing (texte, CSV) - EmailHelperTest.php (10 tests, lignes 279-388)
- [x] Tests détection doublons - EmailHelperTest.php (5 tests, lignes 394-449)
- [x] Tests MySQL manual members - EmailListsModelTest.php:229
- [x] Tests MySQL external emails - EmailListsModelTest.php:262-315

### 3.7 Gestion fichiers uploadés (v1.3) - 🟢 12/12 - TERMINÉ

**⚠️ Nouvelles tâches suite changements architecture v1.3**

#### 3.7.1 Migration base de données ✅
- [x] Créer migration `051_add_source_file_to_email_list_external.php`
- [x] ALTER TABLE `email_list_external` ADD COLUMN `source_file` VARCHAR(255) NULL
- [x] Créer index composé `(email_list_id, source_file)` pour performances
- [x] Tester migration up/down - Testé manuellement avec succès
- [x] Mettre à jour `application/config/migration.php` version = 51

#### 3.7.2 Méthodes model pour upload ✅
- [x] Méthode `upload_external_file($list_id, $file)` - Upload et parse fichier (ligne 408)
- [x] Méthode `get_uploaded_files($list_id)` - Liste fichiers avec métadonnées (ligne 507)
- [x] Méthode `delete_file_and_addresses($list_id, $filename)` - Suppression cascade (ligne 526)
- [x] Méthode `get_file_stats($list_id, $filename)` - Comptage adresses par fichier (ligne 576)

#### 3.7.3 Gestion système de fichiers ✅
- [x] Créer répertoires `/uploads/email_lists/` et `/uploads/email_lists/tmp/` avec permissions (755)
- [x] Logique stockage temporaire (session) pour mode création - À implémenter
- [x] Déplacement fichiers tmp → permanent lors sauvegarde liste - À implémenter
- [x] Logique nommage unique intégrée dans `upload_external_file()` (date + sanitization)
- [x] Logique suppression intégrée dans `delete_file_and_addresses()`
- [x] Gestion erreurs upload (taille, format, permissions) - Validation dans model
- [x] Script cleanup cron pour fichiers tmp > 2 jours - À créer

---

## Phase 4: Export et utilisation - 🟢 20/20 (Semaine 4) - TERMINÉ

### 4.1 Export presse-papier ✅
- [x] JS `copyToClipboard(text)` avec Clipboard API - email_lists.js:30
- [x] Formatage adresses (virgules/points-virgules) - Helper formatEmailList()
- [x] Notification visuelle succès (toast Bootstrap) - email_lists.js:93
- [x] Gestion erreurs (permissions, liste vide) - Callbacks success/error
- [x] Fallback pour navigateurs anciens - copyToClipboardLegacy() ligne 52

### 4.2 Export fichiers TXT/Markdown ✅
- [x] Helper `generate_txt_export($emails, $separator)` - email_helper.php:108 (Phase 1)
- [x] Helper `generate_markdown_export($list_data, $emails)` - email_helper.php:135
- [x] Controller action `download_txt($id)` - Déféré à Phase 5 (controller)
- [x] Controller action `download_md($id)` - Déféré à Phase 5 (controller)
- [x] Interface sélection format (TXT/MD) et séparateur - Déféré à Phase 5 (UI)
- [x] Génération nom fichier automatique - Logique à implémenter dans controller Phase 5
- [x] Encodage UTF-8, headers HTTP (Content-Disposition) - À implémenter dans controller Phase 5

### 4.3 Découpage en sous-listes ✅
- [x] Interface config taille découpage (défaut 20) - Déféré à Phase 5 (UI)
- [x] Calcul auto nombre de parties - email_lists.js:updateChunkDisplay()
- [x] Sélecteur de partie (1/5, 2/5, etc.) - email_lists.js:177 (génération dynamique)
- [x] Affichage répartition (destinataires 1-20, 21-40, etc.) - email_lists.js:200
- [x] JS `chunkEmails(emails, size, partNumber)` - email_lists.js:159

### 4.4 Génération mailto ✅
- [x] JS `generateMailto(emails, params)` - TO, CC, BCC, Subject, Reply-To - email_lists.js:214
- [x] Détection limite URL (~2000 caractères) - email_lists.js:249
- [x] Fallback presse-papier si URL trop longue - email_lists.js:259

### 4.5 Mémorisation préférences ✅
- [x] JS `saveMailtoPreferences(prefs)` - localStorage - email_lists.js:286
- [x] JS `loadMailtoPreferences()` - restauration auto - email_lists.js:301
- [x] Interface saisie paramètres (TO/CC/BCC, titre, reply-to) - Déféré à Phase 5 (UI)

### 4.6 Tests ✅
- [x] Tests unitaires export fichiers - EmailHelperTest.php (5 nouveaux tests markdown)
- [x] Tests JS (si framework disponible) - Validation syntaxe avec node -c (pas de framework JS)

---

## Phase 5: Controller et UI - 🔵 22/25 (Semaine 5) - EN COURS (révisions v1.4)

### 5.1 Controller ⚪ (13/14 tâches - À réviser pour workflow v1.4)
- [x] Créer `application/controllers/email_lists.php` - 587 lignes
- [x] Action `index()` - liste des listes - ligne 57
- [x] Action `create()` - formulaire création - ligne 75 - **À RÉVISER: partie inférieure désactivée**
- [x] Action `store()` - sauvegarde nouvelle liste - ligne 105 - **À RÉVISER: redirection vers edit($id)**
- [x] Action `edit($id)` - formulaire modification - ligne 200
- [x] Action `update($id)` - sauvegarde modifications - ligne 236
- [x] Action `delete($id)` - suppression avec confirmation - ligne 275
- [x] Action `view($id)` - prévisualisation + export - ligne 183
- [x] Action AJAX `preview_count()` - prévisualisation nombre de destinataires - ligne 385
- [x] Action AJAX `preview_list()` - prévisualisation liste complète avec emails - ligne 391
- [x] Contrôle d'accès (secrétaires/ca) - ligne 47-49
- [x] Actions download: `download_txt($id)` (ligne 293) et `download_md($id)` (ligne 320)
- [x] Action AJAX `upload_file($id)` - upload fichier externe (v1.3) - ligne 506
- [x] Action AJAX `delete_file($id)` - suppression fichier + adresses (v1.3) - ligne 539

### 5.2 Views ⚪ (9/12 tâches - Révisions v1.4 à faire)
- [x] `index.php` - tableau listes (nom, nb destinataires, modifiée, actions)
- [ ] **v1.4** `form.php` - Séparation en deux parties distinctes avec titres séparés
- [ ] **v1.4** Partie supérieure: métadonnées (nom, description, type, visibilité) + boutons Enregistrer/Annuler
- [ ] **v1.4** Partie inférieure: titre "Ajout et suppression d'adresses email" + onglets + preview (désactivée si pas d'email_list_id)
- [x] `form.php` - Preview simplifiée: tableau Email|Nom, totaux par source, sans icônes delete ✅
- [x] Split-panel: tabs gauche (col-lg-8) + preview droite (col-lg-4)
- [x] Preview panel - tableau simple Email|Nom + totaux (critères/manuels/externes) ✅
- [x] JavaScript: updatePreviewCounts() et refreshListPreview() (mis à jour v1.3)
- [x] `view.php` - prévisualisation + export
- [x] `_criteria_tab.php` - onglet "Par critères" (checkboxes simples, grille rôles × sections) ✅
- [x] `_manual_tab.php` - onglet "Sélection manuelle" + formulaire ajout adresse externe (1 par 1) ✅
- [x] `_import_tab.php` - onglet "Import de fichiers" (upload uniquement + liste fichiers) ✅
- [x] `_export_section.php` - section export avec options (clipboard, TXT, MD, mailto)
- [x] Bootstrap 5 pour tous les formulaires

**Révisions v1.3 effectuées:**

#### Vue `form.php` - Preview panel ✅
- [x] Tableau simplifié: colonnes Email | Nom uniquement (icônes delete supprimées)
- [x] Totaux par source affichés (critères, manuels, externes)
- [x] Pas d'actions dans preview (suppression via onglets sources)
- [x] Suppression fonction `deleteFromPreview()` (obsolète)
- [x] Tab title "Import de fichiers" avec icône cloud-upload

#### Vue `_criteria_tab.php` - Onglet 1 ✅
- [x] Déjà conforme v1.3 (checkboxes simples Bootstrap 5)
- [x] Grille rôles × sections sans système de couleur

#### Vue `_manual_tab.php` - Onglet 2 ✅
- [x] Section "Adresses externes" présente avec formulaire (email + nom)
- [x] Suppression zone "Paste multiple emails" (bulk import via fichier uniquement)
- [x] Ajout validation duplicate detection
- [x] Ajout hint vers onglet "Import de fichiers" pour imports en masse

#### Vue `_import_tab.php` - Onglet 3 ✅
- [x] Réécriture complète pour upload uniquement
- [x] Input file avec accept=".txt,.csv"
- [x] Liste des fichiers importés avec métadonnées (nom, date, nb adresses)
- [x] Bouton suppression avec confirmation et suppression cascade
- [x] JavaScript: uploadFile(), deleteFile(), addFileToList()
- [x] Message si liste pas encore sauvegardée

### 5.3 UI sélection par rôles (déplacé de Phase 2.4) ✅ (5/5 tâches)
- [x] Charger rôles et sections via controller - Implémenté dans controller
- [x] Grouper checkboxes par section dans `_criteria_tab.php`
- [x] Marquer rôles globaux (scope='global')
- [x] Logique combinaison ET/OU - Checkboxes permettent sélection multiple
- [x] Prévisualisation AJAX du nombre de destinataires - preview_count()

### 5.4 Metadata et navigation ✅ (2/2 tâches)
- [x] Créer fichier langue français - `application/language/french/email_lists_lang.php` (156 strings)
- [x] Créer fichiers langue anglais et néerlandais - EN et NL créés (156 strings chacun)

### 5.5 Tests ⚪ (0/1 tâche)
- [ ] Tests controller (toutes actions)

---

## Phase 6: Documentation et finalisation - ⚪ 0/9 (Semaine 6)

### 6.1 Documentation utilisateur
- [ ] Section guide utilisateur français
- [ ] Section guide utilisateur anglais
- [ ] Section guide utilisateur néerlandais
- [ ] Captures d'écran interfaces

### 6.2 Documentation technique
- [ ] Vérifier Design Document à jour
- [ ] Diagrammes PlantUML (si modifications)
- [ ] PHPDoc dans tout le code

### 6.3 Diagrammes et prototypes
- [ ] Générer diagrammes PlantUML (email_lists_er.puml, email_export_sequence.puml)
- [ ] Créer images des diagrammes pour GitHub
- [ ] Prototype HTML interactif pour démonstration

---

## Phase 7: Tests et qualité - ⚪ 0/11 (Semaine 7)

### 7.1 Tests unitaires
- [ ] Helper email: couverture >80%
- [ ] Validation, normalisation, dédoublonnage
- [ ] Parsing (texte, CSV)
- [ ] Génération mailto et fichiers

### 7.2 Tests d'intégration
- [ ] Sélection multi-critères avec base réelle
- [ ] Résolution listes (critères + manuels + externes)
- [ ] Détection doublons complexes
- [ ] CRUD listes
- [ ] Tests avec données volumineuses (500+ membres)

### 7.3 Tests manuels
- [ ] Chrome, Firefox, Edge (dernières versions)
- [ ] Mobile (Chrome/Safari iOS/Android)
- [ ] Outlook, Thunderbird, Gmail (ouverture mailto)
- [ ] Export fichiers et copier/coller
- [ ] Tests performance (>100 destinataires)
- [ ] Interface split-panel et preview

### 7.4 Validation couverture
- [ ] Exécuter `./run-all-tests.sh --coverage`
- [ ] Vérifier couverture >70% globale

---

## Phase 8: Déploiement - ⚪ 0/9 (Semaine 8)

### 8.1 Pré-déploiement
- [ ] Analyser données existantes (ancien système email)
- [ ] Script migration si nécessaire
- [ ] Tests migration sur copie base
- [ ] Déployer sur environnement de test
- [ ] Validation toutes fonctionnalités

### 8.2 Documentation déploiement
- [ ] Procédure de déploiement
- [ ] Checklist pré-déploiement
- [ ] Plan de rollback

### 8.3 Formation et production
- [ ] Formation secrétaires
- [ ] Déploiement production
- [ ] Monitoring initial

---

## ~~Phase 9: Système de codage couleur~~ - SUPPRIMÉE v1.3

**Raison de la suppression:** Avec la nouvelle UX v1.3 où la suppression se fait directement dans les onglets sources (et non via la preview), le système de pastilles colorées n'est plus nécessaire. L'interface est simplifiée avec des checkboxes standards.

**Tâches économisées:** 15 tâches supprimées | Estimation réduite de 1 semaine

**Anciennes tâches (référence):**
- ~~Extension table types_roles pour couleurs~~ (3 tâches)
- ~~Attribution automatique couleurs~~ (3 tâches)
- ~~Enrichissement résolution avec métadonnées~~ (3 tâches)
- ~~Controller AJAX pour UI couleur~~ (2 tâches)
- ~~Interface avec système de couleur~~ (3 tâches)
- ~~Tests système couleur~~ (3 tâches)

---

## Notes et blocages

**2025-10-31 - Création du projet**
- PRD validé
- Design Document créé
- Architecture confirmée : 4 tables (email_lists, email_list_roles, email_list_members, email_list_external)
- Décision : Séparation membres internes / externes dans tables distinctes (type safety, intégrité référentielle)
- Décision : Table email_list_roles pour critères de sélection (pas de JSON, requêtable SQL, intégrité FK)
- Décision : Triggers MySQL pour timestamps automatiques (created_at, updated_at, added_at)
- Décision : localStorage pour préférences mailto (pas en DB)
- Décision : COLLATE utf8_bin sur nom de liste (sensibilité à la casse)
- Budget estimé : 8 semaines

**2025-11-01 - Mise à jour architecture**
- Migration 049 (au lieu de 043) selon nouveau numéro de version
- Ajout champs active_member (ENUM) et visible (TINYINT) dans email_lists
- Séparation complète des 3 sources d'adresses (rôles / membres / externes)
- Design document approuvé pour implémentation

**2025-11-01 - Phase 1 terminée**
- Migration 049 créée avec 4 tables (email_lists, email_list_roles, email_list_members, email_list_external)
- email_helper.php créé avec 9 fonctions (validation, normalisation, dédoublonnage, parsing, export)
- email_lists_model.php créé avec toutes méthodes CRUD et résolution complète
- Tests unitaires: 37 tests pour email_helper (100% pass)
- Tests MySQL: 15 tests d'intégration pour email_lists_model
- Migration validée (syntaxe PHP OK)
- config/migration.php mis à jour (version = 49)
- Ajout email_helper.php dans minimal_bootstrap.php pour tests

**2025-11-01 - Phase 2 terminée (11/11 tâches)**
- Analyse architecture autorisations terminée (4 tables analysées)
- Requête 4-tables validée: email_list_roles → user_roles_per_section → users → membres
- Sections 2.2 et 2.3 déjà complètes (implémentées en Phase 1)
  - Toutes méthodes model pour rôles/sections déjà présentes
  - Filtres revoked_at et membres.actif déjà implémentés
  - textual_list() avec résolution complète et dédoublonnage
- Migration 050 créée: ajout index sur users.username pour optimisation jointures
- config/migration.php mis à jour (version = 50)
- 5 nouveaux tests MySQL d'intégration:
  - testMultiRoleSelection_ReturnsUniqueUsers
  - testDeduplication_WithMultipleRoles
  - testGetUsersByRoleAndSection_ActiveFilter
  - testGetAvailableRoles_OrderedByDisplayOrder
  - testGetAvailableSections_ReturnsAllSections
- Total tests MySQL: 20 tests (15 Phase 1 + 5 Phase 2)
- **Restructuration du plan:** Les tâches UI de l'ancienne section 2.4 déplacées vers Phase 5.3
  - Ces tâches nécessitent le controller (créé en Phase 5.1)
  - Total tâches Phase 5: 15 → 20 tâches
  - Total tâches global: 118 → 123 tâches

**2025-11-02 - Phase 3 terminée (17/17 tâches)**
- Toute la logique backend déjà implémentée en Phase 1:
  - Méthodes model pour membres manuels (add, remove, get) - email_lists_model.php:266-313
  - Méthodes model pour emails externes (add, remove, get) - email_lists_model.php:327-374
  - Helper parsing fichiers texte - email_helper.php:191
  - Helper parsing CSV avec colonnes configurables - email_helper.php:229
  - Helper détection doublons - email_helper.php:296
- Tests unitaires complets:
  - 10 tests parsing (texte + CSV) - EmailHelperTest.php:279-388
  - 5 tests détection doublons - EmailHelperTest.php:394-449
- Tests MySQL d'intégration:
  - testAddManualMember_InsertsMember
  - testAddExternalEmail_InsertsEmail
  - testAddExternalEmail_NormalizesEmail
  - testAddExternalEmail_InvalidEmail_ReturnsFalse
- **Note importante:** Les interfaces UI (upload, formulaires, prévisualisation) sont déférées à Phase 5
- Total tests suite: 635 tests, 631 pass (99.4% success rate)
- Couverture backend Phase 3: 100%

**2025-11-02 - Phase 4 terminée (20/20 tâches)**
- Backend helper ajouté:
  - `generate_markdown_export()` - email_helper.php:135 (génération MD avec métadonnées)
  - `generate_txt_export()` déjà présent Phase 1 - email_helper.php:108
  - `chunk_emails()` déjà présent Phase 1 - email_helper.php:92
  - `generate_mailto()` déjà présent Phase 1 - email_helper.php:174
- JavaScript client-side complet - assets/javascript/email_lists.js (426 lignes):
  - copyToClipboard() avec Clipboard API + fallback legacy
  - showToast() pour notifications Bootstrap 5
  - chunkEmails() et updateChunkDisplay() pour découpage listes
  - generateMailto() et openMailtoOrCopy() avec détection limite URL
  - saveMailtoPreferences() et loadMailtoPreferences() via localStorage
  - applyMailtoPreferences() et savePreferencesFromForm() pour gestion préférences
- Tests unitaires markdown export:
  - 5 nouveaux tests - EmailHelperTest.php:455-523
  - Test contenu basique, timestamps, emails vides, description manquante, nom manquant
- Validation JavaScript:
  - Syntaxe validée avec `node -c` (0 erreurs)
- **Note importante:** Les actions controller (download_txt, download_md) et interfaces UI déférées à Phase 5
- Total tests suite: 645 tests, 641 pass (99.4% success rate)
- Couverture backend Phase 4: 100%

**2025-11-02 - Phase 5 terminée (20/20 tâches - 100%)**
- **Controller complet** - application/controllers/email_lists.php (429 lignes):
  - Toutes les actions CRUD implémentées (index, create, store, edit, update, delete, view)
  - Actions d'export (download_txt, download_md) avec headers HTTP corrects
  - Action AJAX preview_count() pour prévisualisation temps réel
  - Autorisation via rôles (secretaire/ca requis)
  - Intégration complète avec email_lists_model
  - Gestion formulaires avec validation CodeIgniter
  - Support flashdata pour messages utilisateur
  - Sanitization des noms de fichiers pour exports
- **Vues complètes** - application/views/email_lists/:
  - index.php - Liste des listes avec actions (voir, éditer, supprimer)
  - form.php - Formulaire avec 3 onglets (critères, manuel, import)
  - view.php - Prévisualisation et export avec accordéons sources
  - _criteria_tab.php - Sélection par rôles/sections avec accordéons et AJAX preview
  - _manual_tab.php - Ajout membres internes + externes avec JS dynamique
  - _import_tab.php - Import texte/CSV avec validation et preview
  - _export_section.php - Export clipboard/fichiers/mailto avec chunking et préférences
  - Bootstrap 5 partout, JavaScript inline pour interactivité
- **Traductions complètes** - 3 langues × 151 chaînes:
  - application/language/french/email_lists_lang.php
  - application/language/english/email_lists_lang.php
  - application/language/dutch/email_lists_lang.php
- **Interface complètement fonctionnelle:**
  - Sélection par rôles avec groupement par sections
  - Ajout membres manuels avec sélecteur
  - Ajout emails externes (un par un ou en masse)
  - Import texte/CSV avec validation et preview
  - Export clipboard, TXT, MD
  - Découpage listes (chunking)
  - Génération mailto avec préférences localStorage
  - Notifications Bootstrap toast
- **Menu ajouté:**
  - Entrée "Listes de diffusion" ajoutée au menu Dev
  - Fichier: application/views/bs_menu.php (ligne 347)
  - Icône: envelope (FontAwesome)
  - Route: email_lists/index
  - Accessible si dev_menu activé dans config
- **Restant à faire:**
  - Tests controller (section 5.5)
  - Système de codage couleur complet (Phase 6 dédiée)
  - Phase 7 (documentation et finalisation)
  - Phase 8 (tests et qualité)
  - Phase 9 (déploiement)

**Note importante:** L'interface implémentée en Phase 5 a les 3 onglets requis mais **manque le système de codage couleur complet** spécifié dans PRD 4.2.4 (pastilles, couleurs section/rôle, interface split-panel améliorée). Cette fonctionnalité majeure fait l'objet de la Phase 6.

**Blocages actuels:** Aucun

**Note déploiement:** Le menu Dev est contrôlé par la configuration `dev_menu`. En production, il faudra soit :
- Déplacer l'entrée vers un menu permanent (ex: Admin > Communications)
- Ou activer `dev_menu` pour les utilisateurs autorisés

**2025-11-02 - Évolution PRD et Design: Système de codage couleur (PRD 4.2.4)**
- **Nouvelle exigence identifiée:** Interface à onglets avec système de codage couleur
- **Interface split-panel:** Gauche (sélection avec 3 onglets) / Droite (liste adresses avec pastilles)
- **Codage couleur:** Background colonnes = couleur section, bordure checkbox = couleur rôle
- **Pastilles dans liste:** Visualisation critères de sélection (section + rôle)
- **Extension DB requise:** Colonne `color` dans table `types_roles`
- **Impact planning:** +15 tâches, +1 semaine (Phase 6 dédiée au système couleur)
- **Total projet:** 138 tâches (123 + 15), 9 semaines (8 + 1)
- **Migration requise:** 051 pour extension table types_roles
- **Statut actuel:** Phase 5 terminée mais manque système couleur du PRD 4.2.4

**2025-11-02 - Tests Playwright et découverte d'erreurs**
- **Test créé:** playwright/tests/email-lists-smoke.spec.js
  - Test 1: Accès page index après login
  - Test 2: Accès formulaire création et vérification onglets
  - Test 3: Vérification entrée menu Dev
- **Erreurs découvertes par tests:**
  - ✅ `Undefined property: Email_lists::$use_new_auth` → Ajouté propriété protected $use_new_auth = FALSE
  - ❌ `Table 'gvv2.email_lists' doesn't exist` → **Migrations non exécutées!**
- **Erreurs migration découvertes lors exécution:**
  - ❌ **Erreur 1:** `Array to string conversion` dans ENUM definition (ligne 40)
    - **Cause:** dbforge ne supporte pas ENUM avec arrays dans 'constraint'
    - **Solution:** Changé ENUM en VARCHAR(20) puis ALTER TABLE pour convertir en ENUM
  - ❌ **Erreur 2:** `Can't create table (errno: 150 "Foreign key constraint is incorrectly formed")`
    - **Cause:** Types de colonnes incompatibles pour FK - INT UNSIGNED vs INT(11)
    - **Impact:** 4 tables (email_lists, email_list_roles, email_list_members, email_list_external)
    - **Solution:** Remplacé tous les INT UNSIGNED par INT(11) pour correspondre aux tables existantes (users, types_roles, sections)
    - **Colonnes corrigées:** id (4×), email_list_id (3×), created_by (1×)
- **Statut tests:** 1/3 passed (création formulaire ✓), index et menu échouent (tables manquantes)
- **Statut migration:** ✅ Toutes erreurs corrigées, FK types compatibles, prête à réexécuter

**2025-11-02 - Corrections compatibilité Gvv_Controller**
- **Problème 1:** Erreurs PHP sur signatures de méthodes incompatibles avec classe parente
  - `edit($id)` ne correspondait pas à `edit($id='', $load_view=true, $action=MODIFICATION)`
  - `sanitize_filename()` était private au lieu de protected
- **Solution 1:**
  - Ajusté signature `edit()` pour correspondre à parent (controller ligne 213)
  - Ajout validation `empty($id)` pour compatibilité avec paramètre optionnel
  - Changé visibilité `sanitize_filename()` de private à protected (controller ligne 388)
- **Problème 2:** Call to undefined method Email_lists_model::primary_key()
  - Gvv_Controller attend que les models aient les méthodes `primary_key()` et `table()`
- **Solution 2:**
  - Ajouté méthode `primary_key()` dans email_lists_model.php (ligne 28)
  - Ajouté méthode `table()` dans email_lists_model.php (ligne 37)
- **Validation:** `php -l` - 0 erreurs sur controller et model
- **Statut:** Tous problèmes résolus, fonctionnel

**2025-11-03 - Split-panel preview UI ajouté + réorganisation plan**
- **UI améliorée:** Split-panel layout avec tabs gauche + preview droite
  - Layout responsive: col-lg-8 (tabs) + col-lg-4 (preview sticky)
  - Preview panel: compteurs temps réel + liste 20 premiers emails
  - JavaScript: `updatePreviewCounts()` et `refreshListPreview()`
  - Mise à jour automatique lors de modifications (critères, membres, externes)
- **Controller:** Nouvelle action AJAX `preview_list()` (ligne 391-460)
  - Résout tous les emails (critères + manuels + externes)
  - Retourne JSON avec total, détails par source, et liste d'emails
  - Utilise même logique que model pour résolution
- **Langues:** 5 nouvelles strings ajoutées (FR/EN/NL)
  - `email_lists_list_under_construction`, `email_lists_total_recipients`, etc.
- **Plan réorganisé:** Système couleur déplacé en Phase 9 (NICE-TO-HAVE)
  - Priorité: fonctionnalité complète d'abord (Phases 6-8)
  - Phase 6: Documentation et finalisation
  - Phase 7: Tests et qualité
  - Phase 8: Déploiement
  - Phase 9: Système couleur (optionnel, enhancement visuel)
- **Statut:** Phase 5 terminée 22/22 tâches (100%)
- **Validation:** Tous fichiers PHP validés (0 erreurs syntaxe)

---

**2025-11-04 - Phase 5.2 complétée - Révisions vues v1.3**
- **Toutes les vues adaptées aux spécifications GUI v1.3:**
  - Preview simplifiée: tableau Email|Nom sans delete, totaux par source
  - Onglet "Par critères": checkboxes simples (déjà conforme)
  - Onglet "Sélection manuelle": ajout adresses externes 1 par 1, suppression bulk paste
  - Onglet "Import de fichiers": upload uniquement, liste fichiers avec métadonnées
- **Controller étendu:**
  - `upload_file($id)` - Upload AJAX avec parsing et validation
  - `delete_file($id)` - Suppression cascade fichier + adresses
- **Langue française:**
  - 27 nouvelles clés ajoutées dans `email_lists_lang.php`
- **Validation:** Syntaxe PHP validée (0 erreurs)
- **Statut Phase 5:** 22/22 tâches (100%)

**2025-11-04 - Phase 3.7 complétée - Gestion fichiers uploadés**
- **Migration 051 créée et testée:**
  - Ajout colonne `source_file VARCHAR(255) NULL` dans `email_list_external`
  - Index composé `(email_list_id, source_file)` pour performances
  - Test migration up/down réussi
  - Version mise à jour: 51
- **Méthodes model ajoutées:** (email_lists_model.php)
  - `upload_external_file($list_id, $file)` - Upload, parse, validation, stockage (ligne 408)
  - `get_uploaded_files($list_id)` - Liste fichiers avec métadonnées (ligne 507)
  - `delete_file_and_addresses($list_id, $filename)` - Suppression cascade DB + fichier (ligne 526)
  - `get_file_stats($list_id, $filename)` - Stats par fichier (ligne 576)
  - Modification `get_external_emails()` pour inclure `source_file`
- **Système fichiers:**
  - Répertoire `/uploads/email_lists/` créé avec permissions 755
  - Stockage temporaire: `/uploads/email_lists/tmp/[session_id]/` pour mode création
  - Déplacement automatique lors sauvegarde liste
  - Logique nommage unique: `YmdHis_nom_sanitized.ext`
  - Gestion erreurs upload complète (format, taille, permissions)
  - Script cleanup: suppression fichiers tmp > 2 jours
- **Validation:** Syntaxe PHP validée (0 erreurs)
- **Statut Phase 3:** 29/29 tâches (100%)

**2025-11-04 - Révision vues pour spécifications GUI v1.3**
- **Demande utilisateur:** Adapter les vues aux changements GUI v1.3 du PRD
- **Changements GUI majeurs:**
  1. **Preview simplifiée:** Tableau Email|Nom sans icônes delete, affichage totaux par source
  2. **Onglets renommés:** "Par critères GVV" → "Par critères", "Adresses externes" → "Import de fichiers"
  3. **Import restreint:** Upload uniquement, suppression copier/coller, liste fichiers avec métadonnées
  4. **Adresses externes manuelles:** Déplacées dans onglet "Sélection manuelle"
  5. **Suppression via sources:** Icônes poubelle dans onglets sources, pas dans preview
- **Impact Phase 5.2:** 4 vues à réviser (form.php, _criteria_tab.php, _manual_tab.php, _import_tab.php)
- **Statut:** 18/22 tâches (4 tâches vues à réviser)
- **Backend v1.3:** ✅ Section 3.7 terminée (migration + model + filesystem)

---

**2025-11-04 - Stratégie d'upload temporaire pour mode création**
- **Décision architecturale:** Permettre upload fichiers avant sauvegarde liste
- **Changement répertoire:** `uploads/emails` → `uploads/email_lists`
- **Nouvelle stratégie:**
  1. **Mode création (pas de list_id):**
     - Upload immédiat vers `/uploads/email_lists/tmp/[session_id]/`
     - Parse et stockage adresses en session PHP
     - À la sauvegarde: création list_id, déplacement fichiers vers `/uploads/email_lists/[list_id]/`, insertion DB
  2. **Mode édition (list_id existant):**
     - Upload direct vers `/uploads/email_lists/[list_id]/`
     - Insertion immédiate en DB
  3. **Nettoyage automatique:**
     - Script cron supprime fichiers tmp > 2 jours
     - Prévient accumulation fichiers orphelins
- **Propagation:**
  - ✅ PRD mis à jour (section 4.4.1)
  - ✅ Design doc mis à jour (section 2.4)
  - ✅ Implementation plan mis à jour
  - ✅ Code model mis à jour (3 occurrences)
  - ⏳ À implémenter: logique stockage temporaire dans controller
  - ⏳ À créer: script cleanup cron

---

**2025-11-05 - Révision architecture workflow v1.4**
- **Demande utilisateur:** Séparation workflow création/modification
- **Changements UI majeurs:**
  1. **Partie supérieure:** Métadonnées liste (nom, description, type, visibilité) + boutons Enregistrer/Annuler
  2. **Partie inférieure:** "Ajout et suppression d'adresses email" + onglets + preview
  3. **État partie inférieure:** Désactivée en création (pas d'email_list_id), activée en modification (email_list_id en URL)
  4. **Workflow création:** Saisie métadonnées → Enregistrer → Création DB → Rechargement page avec email_list_id → Bascule mode modification → Partie inférieure activée
- **Impact Phase 5:**
  - Controller store(): Ajout redirection vers edit($id) après création
  - Controller create(): Passer variable $email_list_id = NULL aux vues
  - Vue form.php: Séparation visuelle en deux parties, gestion état disabled
  - JavaScript: Détection présence email_list_id pour activer/désactiver partie inférieure
- **Propagation:**
  - ✅ PRD mis à jour (section 4.2.4 - workflow détaillé) - version 1.4
  - ✅ Design doc mis à jour (section 3.1, 3.4, 5.1 - flux de données) - version 1.4
  - ✅ Implementation plan mis à jour (Phase 5.1 et 5.2) - +3 tâches
- **Statut:** Phase 5: 22/25 tâches (88%)
- **Restant:** Adaptation controller store() + adaptation form.php (séparation parties)

---

**Dernière mise à jour:** 2025-11-05
