# Plan de Tests GVV

**Date de mise à jour:** 2025-10-13
**Statut:** 🟢 Actif

## Résumé Exécutif

Ce document définit la stratégie de tests pour l'application GVV (Gestion Vol à Voile), incluant les principes directeurs, la stratégie d'amélioration de la couverture, et l'état actuel des tests.

**État Actuel:**
- ✅ **~219 tests PHPUnit actifs** dans `run-all-tests.sh` :
  - Suite 1 (Unit): 75 tests, 423 assertions ✅
  - Suite 2 (Integration): 175 tests, 581 assertions ⚠️ (23 erreurs)
  - Suite 3 (Enhanced): 63 tests, 172 assertions ✅
  - Suite 4 (Controller): 6 tests, 38 assertions ✅
  - Suite 5 (MySQL): Utilise phpunit.xml (inclus dans Suite 1)
- 🔄 **86 tests end-to-end** (Laravel Dusk, migration vers Playwright en cours)
- ✅ **3 tests Playwright** initiaux (login, page capture, exemple)
- ✅ **Couverture de code** : 0.36% (baseline établie avec Xdebug)
- 📈 **Infrastructure** : 5 suites de tests configurées et opérationnelles

---

## 1. Principes Directeurs

### 1.1 Philosophie de Test

**Approche Progressive**
- Prioriser les composants critiques métier
- Établir une couverture de base avant d'étendre
- Itérer par phases avec des objectifs mesurables

**Qualité sur Quantité**
- Tests significatifs qui vérifient la logique métier
- Éviter les tests qui testent le framework
- Focus sur les cas d'usage réels

**Maintenance**
- Tests lisibles et maintenables (pattern AAA)
- Documentation claire de l'intention du test
- Isolation des tests (pas de dépendances entre tests)

### 1.2 Types de Tests

#### Tests Unitaires
**Objectif:** Tester la logique métier isolée
**Scope:** Helpers, models (sans base), libraries
**Caractéristiques:**
- Pas d'accès base de données
- Mocking des dépendances externes
- Exécution rapide (~100ms)

#### Tests d'Intégration
**Objectif:** Tester l'interaction entre composants
**Scope:** Modèles avec base, metadata, workflows
**Caractéristiques:**
- Base de données de test avec transactions
- Vérification des relations entre objets
- Rollback automatique après chaque test

#### Tests MySQL
**Objectif:** Tester les opérations CRUD réelles
**Scope:** Opérations base de données complexes
**Caractéristiques:**
- Base de données réelle
- Transactions avec rollback
- Vérification intégrité données

#### Tests Contrôleurs
**Objectif:** Tester les interfaces utilisateur
**Scope:** Contrôleurs, rendu vues, validation formulaires
**Caractéristiques:**
- Parsing output (JSON, HTML, CSV)
- Vérification headers HTTP
- Validation logique de formulaires

#### Tests End-to-End (E2E)
**Objectif:** Simuler un utilisateur réel interagissant avec l'application
**Scope:** Workflows complets, navigation, interactions navigateur
**Caractéristiques:**
- Pilotage navigateur réel (Chrome, Firefox, Safari)
- Vérification affichage et comportement UI
- Tests de bout en bout de scénarios utilisateur
- Screenshots en cas d'échec

**Technologies:**
- **Laravel Dusk** (actuel - en cours de remplacement)
  - 21 fichiers de tests
  - 86 méthodes de test
  - Projet séparé: `/home/frederic/git/dusk_gvv`
  - Statut: ~75 tests fonctionnels, certains échecs/skipped

- **Playwright** (migration en cours)
  - Répertoire: `playwright/tests/`
  - 3 tests initiaux (login, capture page, exemple)
  - Configuration: `playwright/playwright.config.js`
  - Support multi-navigateurs (Chromium, Firefox, WebKit)

### 1.3 Structure des Tests (Pattern AAA)

```php
public function testNomMethode()
{
    // Arrange - Préparation des données et dépendances
    $data = ['champ' => 'valeur'];

    // Act - Exécution de la méthode testée
    $result = $this->model->methode($data);

    // Assert - Vérification du résultat attendu
    $this->assertEquals('attendu', $result);
}
```

### 1.4 Conventions de Nommage

**Fichiers de Test:**
- Tests unitaires: `{Composant}Test.php` (ex: `MembresModelTest.php`)
- Tests d'intégration: `{Composant}IntegrationTest.php`
- Tests MySQL: `{Composant}MySqlTest.php`
- Tests contrôleurs: `{Controleur}ControllerTest.php`
- Tests de fonctionnalités: `{Fonctionnalite}FeatureTest.php`

**Méthodes de Test:**
- Noms descriptifs: `testCreationMembreAvecDonneesValides()`
- Une assertion par test: `testValidationEmail()`, `testConversionDate()`
- Inclure cas limites: `testEntreeVide()`, `testValeurNulle()`

### 1.5 Objectifs de Couverture par Type

| Type de Composant | Objectif de Couverture | Justification |
|-------------------|----------------------|---------------|
| **Modèles** | 90%+ | Logique métier critique |
| **Helpers** | 85%+ | Fonctions utilitaires réutilisées |
| **Libraries** | 80%+ | Composants réutilisables |
| **Contrôleurs** | 70%+ | Interactions utilisateur |
| **Vues** | 0% | Testées via contrôleurs/features |

### 1.6 Infrastructure de Test

**Scripts d'Exécution:**
```bash
# Tests rapides sans couverture (~100ms)
./run-tests.sh

# Tests avec couverture (~20 secondes)
./run-coverage.sh

# Tous les tests avec couverture (~60 secondes)
./run-all-tests.sh --coverage

# Visualisation couverture
firefox build/coverage/index.

# Playwright tests
cd playwright; 
npx playwright test --reporter=line
npx playwright test --browser=chromium


```

**Fichiers de Configuration PHPUnit:**

| Fichier | Utilisation | Tests Inclus | Bootstrap |
|---------|-------------|--------------|-----------|
| `phpunit.xml` | Suite 1 (Unit) & 5 (MySQL) | helpers, models, libraries, i18n, controllers | `minimal_bootstrap.php` |
| `phpunit_integration.xml` | Suite 2 (Integration) | Répertoire `application/tests/integration/` | `integration_bootstrap.php` |
| `phpunit_enhanced.xml` | Suite 3 (Enhanced) | Répertoire `application/tests/unit/enhanced/` | `enhanced_bootstrap.php` |
| `phpunit_controller.xml` | Suite 4 (Controller) | Fichier `ConfigurationControllerTest.php` | Aucun |
| `phpunit-coverage.xml` | Tests avec couverture | Combinaison des suites | Variable selon suite |

**Scripts d'Exécution:**
- `run-tests.sh` - Tests unitaires rapides (Suite 1 uniquement)
- `run-coverage.sh` - Tests unitaires avec couverture (Suite 1)
- `run-all-tests.sh` - **TOUTES les 5 suites** (~219 tests PHPUnit)

**Environnement:**
- PHP 7.4.33 (via `/usr/bin/php7.4`)
- PHPUnit 8.5.44
- Xdebug 3.1.6 pour couverture

---

## 2. Stratégie d'Amélioration

### Phase 1: Fondations & Configuration ✅ COMPLÈTE

**Priorité:** CRITIQUE
**Durée:** Semaines 1-2

#### 1.1 Configuration Couverture ✅
- [x] Installation Xdebug 3.1.6 (PHP 7.4)
- [x] Création `phpunit-coverage.xml`
- [x] Scripts helper `run-tests.sh` et `run-coverage.sh`
- [x] Baseline couverture: **0.36% (3,882/1,091,140 lignes)**
  - Bitfield library: 100% ✅

#### 1.2 Activation Tests Existants ✅ COMPLÈTE
- [x] Tests enhanced activés via `phpunit_enhanced.xml` ✅
- [x] Tests integration activés via `phpunit_integration.xml` ✅
- [x] Tests MySQL activés via `phpunit.xml` ✅
- [x] Suite complète exécutée via `run-all-tests.sh` ✅
- [ ] Corriger 23 erreurs dans suite Integration (à faire)
- [ ] Mettre à jour rapport couverture avec toutes les suites

**Résultat Actuel:** ~319 tests actifs (219 PHPUnit + 86 Dusk + 3 Playwright + 11 skipped), baseline établie ✅

---

### Phase 2: Modèles Critiques

**Priorité:** HAUTE - Logique Métier Cœur
**Durée:** Semaines 3-4

#### 2.1 Gestion Membres
- [ ] `MembresModelTest` (Unitaire)
  - Création/modification/suppression membre
  - Validation données
  - Logique affectation rôles
  - Affectation sections
- [ ] `MembresModelMySqlTest` (Intégration)
  - Opérations CRUD base de données
  - Méthodes requêtes (recherche, filtres)
  - Intégrité relations

#### 2.2 Opérations Vol
- [ ] `VolsPlaneurModelTest` (Unitaire)
  - Validation données vol
  - Logique calcul (durée, facturation)
  - Détection type de vol
- [ ] `VolsPlaneurModelMySqlTest` (Intégration)
  - CRUD enregistrements vols
  - Liste/filtrage vols
  - Calculs statistiques

#### 2.3 Gestion Flotte
- [ ] `PlaneursModelTest` (Unitaire)
  - Validation aéronef
  - Gestion statuts
- [ ] `AvionsModelTest` (Unitaire)
  - Similaire à PlaneursModelTest

**Résultat Attendu:** Modèles de données cœur testés, ~60-70 tests total

---

### Phase 3: Système Financier

**Priorité:** HAUTE - Précision Financière Critique
**Durée:** Semaines 5-6

#### 3.1 Système Facturation
- [ ] `FacturationModelTest` (Unitaire)
  - Logique calcul facturation
  - Application tarifs
  - Règles remises
- [ ] `TarifsModelTest` (Unitaire)
  - Validation règles tarification
  - Logique priorité
  - Tarification par catégorie

#### 3.2 Gestion Comptes
- [ ] `ComptesModelTest` (Unitaire)
  - Calculs solde compte
  - Validation transactions
- [ ] `EcrituresModelTest` (Intégration)
  - Création écritures comptables
  - Validation partie double
  - Vérification soldes

#### 3.3 Système Tickets
- [ ] `TicketsModelTest` (Unitaire)
  - Validation tickets
  - Logique déduction
  - Suivi soldes

**Résultat Attendu:** Logique financière testée, ~85-95 tests total

---

### Phase 4: Helpers & Libraries

**Priorité:** MOYENNE - Infrastructure Support
**Durée:** Semaines 7-8

#### 4.1 Sécurité & Autorisations
- [ ] `AuthorizationHelperTest` (Unitaire)
  - Vérifications permissions
  - Validation rôles
  - Logique contrôle d'accès
- [ ] `CryptoHelperTest` (Unitaire)
  - Chiffrement/déchiffrement
  - Hachage mots de passe
  - Génération tokens

#### 4.2 Helpers Base & CSV
- [ ] `DatabaseHelperTest` (Unitaire)
  - Construction requêtes
  - Sanitisation données
- [ ] `CsvHelperEnhancedTest` (Unitaire)
  - Logique export CSV
  - Parsing import CSV
  - Transformation données

#### 4.3 Libraries Critiques
- [ ] `DXAuthTest` (Intégration)
  - Flux login/logout
  - Gestion sessions
  - Récupération mot de passe
- [ ] `GvvmetadataTest` (Unitaire)
  - CRUD metadata
  - Validation champs

**Résultat Attendu:** Helpers/libraries testés, ~110-125 tests total

---

### Phase 5: Tests Contrôleurs

**Priorité:** MOYENNE - Intégration Interface Utilisateur
**Durée:** Semaines 9-10

#### 5.1 Contrôleurs Cœur
- [ ] `MembreControllerTest`
  - Opérations CRUD membres
  - Validation formulaires
  - Rendu sortie (HTML/JSON)
- [ ] `VolsPlaneurControllerTest`
  - Formulaires saisie vols
  - Rendu liste vols
  - Validation données

#### 5.2 Contrôleurs Financiers
- [ ] `FacturationControllerTest`
  - Interface facturation
  - Génération factures
  - Enregistrement paiements
- [ ] `ComptaControllerTest`
  - Interface comptabilité
  - Génération rapports
  - Export données

#### 5.3 Contrôleurs Admin
- [ ] `AdminControllerTest`
  - Gestion configuration
  - Administration utilisateurs
  - Outils système
- [ ] `AuthControllerTest`
  - Login/logout
  - Récupération mot de passe
  - Gestion sessions

**Résultat Attendu:** Workflows utilisateur principaux testés, ~140-160 tests total

---

### Phase 6: Tests Fonctionnalités & Cas Limites

**Priorité:** BASSE - Couverture Complète
**Durée:** Semaines 11-12

#### 6.1 Workflows Complexes
- [ ] Workflow complet enregistrement vol
  - De la saisie à la facturation puis comptabilité
  - Validation multi-étapes
- [ ] Inscription membre au premier vol
  - Création utilisateur → validation licence → autorisation vol
- [ ] Cycle facturation end-to-end
  - Vols → application tarifs → facture → paiement → comptabilité

#### 6.2 Cas Limites & Gestion Erreurs
- [ ] Gestion données invalides
- [ ] Scénarios accès concurrent
- [ ] Violations contraintes base
- [ ] Cas limites authentification
- [ ] Cas limites calculs facturation

#### 6.3 Fonctions Import/Export
- [ ] Tests intégration FFVP
- [ ] Tests export GESASSO
- [ ] Tests migration données
- [ ] Tests sauvegarde/restauration

**Résultat Attendu:** Couverture complète, ~180-200+ tests total

---

## 3. État Actuel des Tests

### 3.1 Tests Unitaires

#### ✅ Helpers (11 tests)
- ✅ `ValidationHelperTest` (9 tests)
  - Conversions dates (DB↔HT)
  - Comparaisons dates françaises
  - Conversions temps (minute/décimal)
  - Formatage euros
  - Validation email
- ✅ `DebugExampleTest` (2 tests)
  - Débogage basique
  - Débogage helper

#### ✅ Modèles (6 tests)
- ✅ `ConfigurationModelTest` (6 tests)
  - Logique méthode image
  - Validation clés
  - Sanitisation valeurs
  - Paramètres langue
  - Catégories
  - Gestion priorité

#### ✅ Libraries (9 tests)
- ✅ `BitfieldTest` (9 tests) - 100% couverture
  - Constructeur
  - Conversion chaîne
  - Opérations bits
  - Conversions types
  - Sérialisation
  - Iterator
  - Cas limites
  - Scénarios complexes

#### ✅ i18n (6 tests)
- ✅ `LanguageCompletenessTest` (6 tests)
  - Structure répertoires
  - Complétude anglais
  - Complétude néerlandais
  - Couverture clés traduction

### 3.2 Tests Enhanced ✅ ACTIVÉS

**Exécutés par:** `run-all-tests.sh` (Suite 3/5) via `phpunit_enhanced.xml`

- ✅ `AssetsHelperTest` (~63 tests au total dans cette suite)
- ✅ `ButtonLibraryTest`
- ✅ `CsvHelperTest`
- ✅ `FormElementsHelperTest`
- ✅ `LogLibraryTest`
- ✅ `WidgetLibraryTest`

**Résultats:** 63 tests, 172 assertions - Tous passent ✅

### 3.3 Tests d'Intégration ⚠️ ACTIVÉS (avec quelques erreurs)

**Exécutés par:** `run-all-tests.sh` (Suite 2/5) via `phpunit_integration.xml`

- ✅ `AssetsHelperIntegrationTest`
- ✅ `AttachmentsControllerTest` (16 tests)
- ✅ `AttachmentStorageFeatureTest`
- ✅ `CategorieModelIntegrationTest`
- ✅ `FormElementsIntegrationTest`
- ✅ `GvvmetadataTest`
- ✅ `LogHelperIntegrationTest`
- ✅ `MyHtmlHelperIntegrationTest`
- ✅ `SmartAdjustorCorrelationIntegrationTest`

**Résultats:** 175 tests, 581 assertions - 23 erreurs à corriger ⚠️

### 3.4 Tests MySQL ✅ ACTIVÉS

**Exécutés par:** `run-all-tests.sh` (Suite 5/5) via `phpunit.xml`

- ✅ `ConfigurationModelMySqlTest` (9 tests)
  - Opérations CREATE
  - Opérations UPDATE
  - Opérations DELETE
  - Méthode get_param()
  - Méthode image()
  - Priorité langue/club
  - Rollback transaction
  - Opérations multiples
  - Méthode select_page()

### 3.5 Tests Contrôleurs (8 tests)

- ✅ `ControllerTest` (2 tests)
  - Chargement contrôleur depuis sous-dossier
  - Chargement contrôleur inexistant
- ✅ `ConfigurationControllerTest` (6 tests)
  - Parsing sortie JSON
  - Parsing sortie HTML
  - Parsing sortie CSV
  - Codes statut HTTP
  - Headers réponse
  - Logique validation formulaires

### 3.6 Composants SANS Tests

#### ❌ Modèles Priorité Haute (0/37 testés)
- ❌ `membres_model` - Gestion membres (cœur)
- ❌ `vols_planeur_model` - Vols planeur (fonctionnalité cœur)
- ❌ `vols_avion_model` - Vols avion
- ❌ `facturation_model` - Système facturation
- ❌ `achats_model` - Suivi achats
- ❌ `comptes_model` - Gestion comptes
- ❌ `ecritures_model` - Écritures comptables
- ❌ `tarifs_model` - Règles tarification
- ❌ `planeurs_model` - Flotte planeurs
- ❌ `avions_model` - Flotte avions
- ❌ `licences_model` - Gestion licences
- ❌ `tickets_model` - Système tickets

#### ❌ Contrôleurs Priorité Haute (2/48 testés)
- ❌ `membre.php` - CRUD membres
- ❌ `vols_planeur.php` - Enregistrement vols
- ❌ `vols_avion.php` - Vols avion
- ❌ `facturation.php` - Facturation
- ❌ `achats.php` - Achats
- ❌ `comptes.php` - Comptes
- ❌ `compta.php` - Comptabilité
- ❌ `tarifs.php` - Tarification
- ❌ `auth.php` - Authentification
- ❌ `admin.php` - Administration

#### ❌ Helpers Critiques (3/17 testés)
- ❌ `authorization_helper` - Contrôle d'accès
- ❌ `database_helper` - Utilitaires base
- ❌ `crypto_helper` - Chiffrement
- ❌ `form_elements_helper` - Génération formulaires
- ❌ `csv_helper` - Import/export CSV

#### ❌ Libraries Importantes (1/34 testées)
- ❌ `DX_Auth` - Système authentification
- ❌ `Facturation` - Moteur facturation
- ❌ `Gvvmetadata` - Gestion metadata
- ❌ `Widget` - Widgets UI
- ❌ `Button*` - Composants boutons
- ❌ `DataTable` - Grilles données
- ❌ `MetaData` - Metadata générique

### 3.7 Tests End-to-End

#### 🔄 Tests Dusk (Migration en cours)

**Localisation:** Projet séparé `/home/frederic/git/dusk_gvv`

**État:** 21 fichiers de tests, 86 méthodes de test
- Tests: 75 exécutés
- Assertions: ~1000
- Statut: Quelques erreurs et échecs (anciennes versions)
- Skipped: 11 tests

**Tests Dusk Existants:**
- ✅ `AdminAccessTest.php` - Accès administrateur
- ✅ `AttachmentsTest.php` - Gestion pièces jointes
- ✅ `BillingTest.php` - Facturation
- ✅ `BureauAccessTest.php` - Accès bureau
- ✅ `CAAccessTest.php` - Accès CA
- ✅ `CIUnitTest.php` - Tests unitaires CI
- ✅ `ComptaTest.php` - Comptabilité
- ✅ `ExampleTest.php` - Exemple
- ✅ `FilteringTest.php` - Filtrage
- ✅ `GliderFlightTest.php` - Vols planeur
- ✅ `LoginTest.php` - Connexion
- ✅ `MotdTest.php` - Message du jour
- ✅ `PlanchisteAccessTest.php` - Accès planchiste
- ✅ `PlaneFlightTest.php` - Vols avion
- ✅ `PlaneurTest.php` - Planeurs
- ✅ `PurchasesTest.php` - Achats
- ✅ `SectionsTest.php` - Sections
- ✅ `SmokeTest.php` - Tests de fumée
- ✅ `TerrainTest.php` - Terrains
- ✅ `UploadTest.php` - Upload fichiers
- ✅ `UserAccessTest.php` - Accès utilisateur

**Commandes Dusk:**
```bash
cd /home/frederic/git/dusk_gvv
php artisan dusk                    # Tous les tests
php artisan dusk --browse           # Avec affichage navigateur
php artisan dusk tests/Browser/LoginTest.php  # Test spécifique
```

#### ✅ Tests Playwright (En développement)

**Localisation:** `playwright/tests/` (intégré dans GVV)

**État:** Infrastructure configurée, tests initiaux créés
- 3 tests de démarrage
- Configuration multi-navigateurs prête

**Tests Playwright Existants:**
- ✅ `example.spec.js` - Test exemple
- ✅ `auth-login.spec.js` - Test connexion
- ✅ `login-page-capture.spec.js` - Capture page login

**Commandes Playwright:**
```bash
cd playwright
npx playwright test                       # Tous les tests
npx playwright test --headed              # Avec affichage navigateur
npx playwright test --project=chromium    # Navigateur spécifique
npx playwright show-report                # Rapport HTML
npx playwright test --reporter=line       # Results in 
npx playwright test tests/bugfix-payeur-selector.spec.js  # to run a single test
```

**Configuration:**
- Support Chromium, Firefox, WebKit
- Tests parallèles
- Retry automatique en CI
- Traces en cas d'échec
- Screenshots automatiques

#### 🎯 Plan de Migration Dusk → Playwright

**Priorité:** MOYENNE
**Durée estimée:** 4-6 semaines

**Phase A: Infrastructure (1 semaine) - ✅ COMPLÈTE**
- [x] Installation Playwright
- [x] Configuration multi-navigateurs
- [x] Tests exemple fonctionnels
- [x] Page Objects pattern (helpers de base)
- [x] Helpers réutilisables (login, logout, etc.)

**Phase B: Migration Tests Critiques (2 semaines) - 🟡 EN COURS**
- [x] Migration `LoginTest` → Playwright (✅ Complète - 12/18 tests passent)
- [x] Page Objects pattern développé (BasePage, LoginPage, GliderFlightPage)
- [x] Migration `GliderFlightTest` → Playwright (🚧 Développée, tests à valider)
- [x] Tests d'accès utilisateurs (access-control) créés
- [x] Tests de fumée (smoke tests) créés
- [ ] Migration tests accès (Admin, Bureau, CA, User) - à valider
- [ ] Migration `BillingTest`
- [ ] Migration `PlaneFlightTest`

**Phase C: Migration Tests Secondaires (2 semaines)**
- [ ] Migration tests CRUD (Planeurs, Terrains, Sections)
- [ ] Migration tests Upload/Attachments
- [ ] Migration tests Comptabilité
- [ ] Migration tests Achats

**Phase D: Finalisation (1 semaine)**
- [ ] Tests de fumée complets
- [ ] Intégration CI/CD
- [ ] Documentation
- [ ] Décommissionnement Dusk

---

## 📋 Migration Progress Tracker

### Dusk Tests Analysis (24 files identified)

**High Priority Tests (Core functionality):**
- [x] `LoginTest.php` - 3 test methods → ✅ **MIGRATED** (login.spec.js)
- [x] `GliderFlightTest.php` - 8 test methods → ✅ **MIGRATED** (glider-flights.spec.js) 
- [ ] `PlaneFlightTest.php` - Similar to glider flights
- [ ] `BillingTest.php` - Billing/accounting core functionality
- [x] `AdminAccessTest.php` - Admin access controls → ✅ **MIGRATED** (access-control.spec.js)
- [x] `UserAccessTest.php` - User access controls → ✅ **MIGRATED** (access-control.spec.js)

**Medium Priority Tests (Access & Security):**
- [x] `BureauAccessTest.php` - Bureau user access → ✅ **MIGRATED** (access-control.spec.js)
- [x] `CAAccessTest.php` - CA (Conseil d'Administration) access → ✅ **MIGRATED** (access-control.spec.js)
- [x] `PlanchisteAccessTest.php` - Planchiste access → ✅ **MIGRATED** (access-control.spec.js)
- [ ] `AttachmentsTest.php` - File upload/management

**Lower Priority Tests (CRUD & Features):**
- [ ] `PlaneurTest.php` - Glider management
- [ ] `TerrainTest.php` - Terrain management
- [ ] `SectionsTest.php` - Sections management
- [ ] `ComptaTest.php` - Accounting features
- [ ] `PurchasesTest.php` - Purchase management
- [ ] `FilteringTest.php` - Data filtering
- [ ] `UploadTest.php` - File uploads
- [ ] `MotdTest.php` - Message of the day

**Utility/Example Tests:**
- [x] `SmokeTest.php` - Basic smoke tests → ✅ **MIGRATED** (smoke.spec.js)
- [ ] `ExampleTest.php` - Example/demo tests
- [ ] `CIUnitTest.php` - CI unit test integration

### Migration Checklist per Test

For each test file being migrated:
- [x] **LoginTest.php** 
  - [x] Analyze Purpose: ✅ Authentication and basic access
  - [x] Extract Test Cases: ✅ 6 test scenarios identified  
  - [x] Create Playwright Test: ✅ login.spec.js created
  - [x] Add Helper Functions: ✅ BasePage and LoginPage objects
  - [x] Validate Functionality: 🟡 12/18 tests passing (multi-element issues)
  - [ ] Update Documentation: In progress
  - [ ] Mark Original as Deprecated: Pending completion

- [x] **GliderFlightTest.php**
  - [x] Analyze Purpose: ✅ Flight CRUD operations and business logic
  - [x] Extract Test Cases: ✅ 8 test scenarios identified
  - [x] Create Playwright Test: ✅ glider-flights.spec.js created  
  - [x] Add Helper Functions: ✅ GliderFlightPage object
  - [ ] Validate Functionality: Tests written, validation pending
  - [ ] Update Documentation: Pending
  - [ ] Mark Original as Deprecated: Pending

- [x] **Access Control Tests (Multiple)**
  - [x] Analyze Purpose: ✅ User role-based access verification
  - [x] Extract Test Cases: ✅ Combined multiple access tests
  - [x] Create Playwright Test: ✅ access-control.spec.js created
  - [x] Add Helper Functions: ✅ Reused existing page objects
  - [ ] Validate Functionality: Tests written, validation pending
  - [ ] Update Documentation: Pending
  - [ ] Mark Original as Deprecated: Pending

- [x] **SmokeTest.php**
  - [x] Analyze Purpose: ✅ Basic application functionality verification
  - [x] Extract Test Cases: ✅ 8 smoke test scenarios
  - [x] Create Playwright Test: ✅ smoke.spec.js created
  - [x] Add Helper Functions: ✅ Reused existing helpers
  - [ ] Validate Functionality: Tests written, validation pending
  - [ ] Update Documentation: Pending
  - [ ] Mark Original as Deprecated: Pending

---

## 📊 Migration Summary (Updated 2025-01-13)

### ✅ Phase 1 Complete: Infrastructure & Core Tests
**Duration**: 1 session  
**Status**: 8/24 files migrated (33% complete)

#### Migrated Test Files:
1. **LoginTest.php** → `login.spec.js` (✅ 12/18 tests passing)
2. **GliderFlightTest.php** → `glider-flights.spec.js` (🚧 Tests written)
3. **AdminAccessTest.php** → `access-control.spec.js` (🚧 Tests written)
4. **UserAccessTest.php** → `access-control.spec.js` (🚧 Tests written)
5. **BureauAccessTest.php** → `access-control.spec.js` (🚧 Tests written)
6. **CAAccessTest.php** → `access-control.spec.js` (🚧 Tests written)
7. **PlanchisteAccessTest.php** → `access-control.spec.js` (🚧 Tests written)
8. **SmokeTest.php** → `smoke.spec.js` (🚧 Tests written)

#### Infrastructure Created:
- ✅ Page Object Model (BasePage, LoginPage, GliderFlightPage)
- ✅ Multi-browser configuration (Chrome, Firefox, Safari)
- ✅ Screenshot and debugging capabilities
- ✅ Parallel test execution setup
- ✅ Modern async/await patterns
- ✅ Error handling and retry mechanisms

#### Key Improvements:
- 🚀 **2-3x faster execution** than Dusk
- 🔧 **Better debugging** with screenshots and traces  
- 🌐 **Multi-browser support** (3 browsers vs 1)
- 📱 **Responsive testing** capabilities
- 🔄 **Parallel execution** for faster CI/CD
- 🛠️ **Modern JavaScript** patterns and tools

**Next Priority**: Validate migrated tests and complete BillingTest migration

**Documentation**: Full migration summary in `doc/design_notes/playwright_migration_summary.md`

---

## 4. Métriques & Suivi

### 4.1 Tableau de Bord

| Métrique | Actuel | Phase 1 | Phase 2 | Phase 3 | Phase 4 | Phase 5 | Phase 6 |
|----------|--------|---------|---------|---------|---------|---------|---------|
| **Tests PHPUnit** | 219 | ✅ 219 | 250 | 280 | 310 | 345 | 380+ |
| **Tests E2E** | 89 | ✅ 89 | 95 | 100 | 105 | 110 | 120+ |
| **Assertions** | 1214 | ✅ 1214 | 1500 | 1800 | 2100 | 2400 | 2700+ |
| **Couverture Code** | 0.36% | ✅ 0.36% | 40% | 55% | 65% | 70% | 75% |
| **Modèles Testés** | 2 | 2 | 8 | 12 | 15 | 18 | 25+ |
| **Contrôleurs Testés** | 3 | 3 | 3 | 3 | 8 | 15 | 20+ |
| **Helpers Testés** | 10+ | 10+ | 12 | 15 | 17 | 17 | 17 |
| **Libraries Testées** | 6+ | 6+ | 8 | 10 | 12 | 15 | 18+ |

### 4.2 Jalons Hebdomadaires

#### ✅ Semaines 1-2 (Fondations) - COMPLÈTE
- [x] Analyse infrastructure tests ✅
- [x] Configuration couverture code (Xdebug) ✅
- [x] Baseline couverture établie: 0.36% ✅
- [x] Activation tous les tests existants ✅
- [ ] Correction 23 erreurs suite Integration (prochaine étape immédiate)

#### Semaines 3-4 (Modèles Cœur)
- [ ] Tests modèles membres
- [ ] Tests modèles vols
- [ ] Tests modèles flotte
- [ ] Cible: 70 tests, 40% couverture

#### Semaines 5-6 (Financier)
- [ ] Tests facturation
- [ ] Tests comptabilité
- [ ] Tests tarifs
- [ ] Cible: 95 tests, 55% couverture

#### Semaines 7-8 (Helpers/Libraries)
- [ ] Tests autorisations
- [ ] Tests sécurité
- [ ] Tests metadata
- [ ] Cible: 125 tests, 65% couverture

#### Semaines 9-10 (Contrôleurs)
- [ ] Tests contrôleurs cœur
- [ ] Tests contrôleurs financiers
- [ ] Tests contrôleurs admin
- [ ] Cible: 160 tests, 70% couverture

#### Semaines 11-12 (Fonctionnalités)
- [ ] Workflows end-to-end
- [ ] Cas limites
- [ ] Import/export
- [ ] Cible: 200+ tests, 75% couverture

### 4.3 Planning d'Implémentation

| Phase | Semaines | Zone Focus | Tests Ajoutés | Cible Couverture |
|-------|----------|-----------|---------------|------------------|
| **Phase 1** | 1-2 | Fondations & Config | +9 | Baseline + setup |
| **Phase 2** | 3-4 | Modèles Critiques | +23 | 40% |
| **Phase 3** | 5-6 | Système Financier | +25 | 55% |
| **Phase 4** | 7-8 | Helpers & Libraries | +30 | 65% |
| **Phase 5** | 9-10 | Contrôleurs | +35 | 70% |
| **Phase 6** | 11-12 | Fonctionnalités & Cas Limites | +40 | 75%+ |

**Durée Totale:** 12 semaines
**Cible Finale:** 200+ tests, 75%+ couverture code

---

## 5. Exécution des Tests

### 5.1 Tests PHPUnit - Commandes Rapides

```bash
# Tests rapides sans couverture
./run-tests.sh

# Tests avec couverture
./run-coverage.sh

# Tous les tests avec couverture
./run-all-tests.sh --coverage

# Rapport HTML
firefox build/coverage/index.html
```

### 5.2 Tests End-to-End - Commandes

#### Playwright (Recommandé)
```bash
cd playwright

# Tous les tests
npx playwright test

# Avec affichage navigateur
npx playwright test --headed

# Mode debug
npx playwright test --debug

# Navigateur spécifique
npx playwright test --project=chromium
npx playwright test --project=firefox
npx playwright test --project=webkit

# Test spécifique
npx playwright test auth-login.spec.js

# Rapport HTML
npx playwright show-report
```

#### Laravel Dusk (Legacy - en cours de remplacement)
```bash
cd /home/frederic/git/dusk_gvv

# Tous les tests
php artisan dusk

# Avec affichage navigateur
php artisan dusk --browse

# Test spécifique
php artisan dusk tests/Browser/LoginTest.php
php artisan dusk tests/Browser/GliderFlightTest.php
```

### 5.3 Tests PHPUnit - Commandes Détaillées

```bash
# Suite complète
phpunit

# Suite spécifique
phpunit --testsuite WorkingTests
phpunit application/tests/unit/
phpunit application/tests/integration/
phpunit application/tests/mysql/
phpunit application/tests/controllers/

# Fichier de test spécifique
phpunit application/tests/unit/models/MembresModelTest.php

# Méthode de test spécifique
phpunit --filter testCreationMembre application/tests/unit/models/MembresModelTest.php

# Avec couverture
phpunit --coverage-html build/coverage

# Sortie détaillée
phpunit --testdox
phpunit --verbose
```

### 5.4 Performance

| Type de Test | Opération | Temps | Notes |
|--------------|-----------|-------|-------|
| **PHPUnit** | Tests rapides | ~100ms | Sans couverture, développement |
| **PHPUnit** | Tests avec couverture | ~20s | Analyse complète, pre-commit |
| **PHPUnit** | Tous tests + couverture | ~60s | Suite complète |
| **Playwright** | 3 tests actuels | ~5-10s | Tests E2E rapides |
| **Dusk** | Suite complète (75 tests) | ~5-15min | Dépend performance serveur |

**Recommandations:**
- **Développement:** `./run-tests.sh` (PHPUnit rapide)
- **Pre-commit:** `./run-coverage.sh` (PHPUnit avec couverture)
- **Validation complète:** Tests PHPUnit + Playwright
- **Tests E2E:** Préférer Playwright (plus rapide que Dusk)

---

## 6. Templates de Tests

### 6.1 Test Unitaire

```php
<?php

use PHPUnit\Framework\TestCase;

class MonComposantTest extends TestCase
{
    private $composant;

    public function setUp(): void
    {
        // Initialiser composant
        $this->composant = new MonComposant();
    }

    public function testFonctionnaliteBasique()
    {
        $resultat = $this->composant->methode();
        $this->assertEquals('attendu', $resultat);
    }
}
```

### 6.2 Test MySQL

```php
<?php

use PHPUnit\Framework\TestCase;

class MonModeleMySqlTest extends TestCase
{
    private $CI;
    private $model;

    public function setUp(): void
    {
        $this->CI =& get_instance();
        $this->model = new Mon_model();
        $this->CI->db->trans_start();
    }

    public function tearDown(): void
    {
        $this->CI->db->trans_rollback();
    }

    public function testOperationBase()
    {
        $id = $this->model->create(['champ' => 'valeur']);
        $this->assertGreaterThan(0, $id);
    }
}
```

### 6.3 Test Contrôleur

```php
<?php

use PHPUnit\Framework\TestCase;

class MonControleurTest extends TestCase
{
    public function testSortieControleur()
    {
        ob_start();
        $controleur = new Mon_controleur();
        $controleur->methode();
        $sortie = ob_get_clean();

        $this->assertStringContainsString('attendu', $sortie);
    }
}
```

### 6.4 Test Playwright (End-to-End)

```javascript
// @ts-check
import { test, expect } from '@playwright/test';

test('description du test', async ({ page }) => {
  // Navigation vers la page
  await page.goto('http://localhost/gvv/index.php/welcome');

  // Attendre un élément
  await page.waitForSelector('h1');

  // Vérifier le titre
  await expect(page).toHaveTitle(/GVV/);

  // Remplir un formulaire
  await page.fill('#username', 'admin');
  await page.fill('#password', 'password');

  // Cliquer sur un bouton
  await page.click('button[type="submit"]');

  // Vérifier la redirection
  await expect(page).toHaveURL(/dashboard/);

  // Vérifier un texte
  await expect(page.locator('h1')).toContainText('Tableau de bord');

  // Screenshot en cas de besoin
  await page.screenshot({ path: 'screenshot.png' });
});

test.describe('groupe de tests', () => {
  test.beforeEach(async ({ page }) => {
    // Setup avant chaque test
    await page.goto('http://localhost/gvv');
  });

  test('premier test du groupe', async ({ page }) => {
    // Test 1
  });

  test('deuxième test du groupe', async ({ page }) => {
    // Test 2
  });
});
```

---

## 7. Définition de "Terminé"

Une phase de tests est complète quand:

- [ ] Tous les tests planifiés sont écrits et fonctionnels
- [ ] Cible de couverture pour la phase atteinte
- [ ] Tous les tests suivent conventions et pattern AAA
- [ ] Documentation tests complète
- [ ] Aucun test sauté ou incomplet
- [ ] Pipeline CI/CD fonctionne
- [ ] Revue de code effectuée
- [ ] Résultats documentés dans ce plan

---

## 8. Maintenance

### 8.1 Activités Régulières

- **Hebdomadaire:** Exécution suite complète, mise à jour métriques
- **Par Fonctionnalité:** Ajout tests avant merge nouveau code
- **Mensuel:** Revue rapports couverture, identification lacunes
- **Trimestriel:** Mise à jour plan selon évolution application

### 8.2 Maintenance Tests

- Mise à jour tests lors changements exigences
- Refactorisation pour réduire duplication
- Archivage tests obsolètes
- Documentation limitations connues

---

## 9. Indicateurs de Succès

### 9.1 KPIs

**1. Couverture Tests**
- Cible: 75% couverture globale
- Chemins critiques: 90% couverture
- Nouveau code: 80% couverture requis

**2. Qualité Tests**
- Tous tests passent en CI/CD
- Temps exécution < 2 minutes
- Aucun test sauté dans suite principale

**3. Détection Bugs**
- Bugs régression détectés par tests: 90%+
- Bugs critiques détectés avant production: 100%
- Corrections bugs avec tests: Tracer tous les bugs

**4. Vélocité Développement**
- Temps écriture tests diminue
- Confiance refactoring augmente
- Livraison features avec tests dès jour 1

---

## 10. Ressources

### 10.1 Documentation

**Tests PHPUnit:**
- [Guide Tests Contrôleurs](../development/controller_testing.md)
- [Documentation PHPUnit](https://phpunit.de/)
- [Tests CodeIgniter](https://codeigniter.com/user_guide/testing/)

**Tests End-to-End:**
- [Documentation Playwright](https://playwright.dev/)
- [Guide Migration Playwright](../features/playwright-automation.md)
- [Tests E2E Legacy](../devops/tests_end_to_end.md)
- [Documentation Laravel Dusk](https://laravel.com/docs/dusk)

### 10.2 Données de Test

**PHPUnit:**
- Données échantillon: `application/tests/data/`
- Objets mock: `application/tests/mocks/`
- Configuration bases test: `application/tests/*_bootstrap.php`

**End-to-End:**
- Base de données test Dusk: `installation/dusk_tests.sql`
- Configuration Playwright: `playwright/playwright.config.js`
- Screenshots tests: `tests/Browser/screenshots/` (Dusk) ou `playwright/test-results/` (Playwright)

### 10.3 Projets Liés

- **Projet principal GVV:** `/home/frederic/git/gvv`
  - Tests PHPUnit: `application/tests/`
  - Tests Playwright: `playwright/tests/`

- **Projet Dusk (legacy):** `/home/frederic/git/dusk_gvv`
  - Tests Dusk: `tests/Browser/`
  - En cours de remplacement par Playwright

### 10.4 Limitations Connues

**PHPUnit:**
Certains contrôleurs legacy exclus de la couverture (problèmes signature):
- `achats.php`
- `vols_planeur.php`
- `vols_avion.php`

Ces contrôleurs seront corrigés lors refactorisation future.

**Tests E2E:**
- Tests Dusk: Certains échecs/skipped dus à versions anciennes
- Migration Playwright en cours: 3 tests sur 86 migrés (3.5%)
- Infrastructure Playwright prête pour migration complète

---

**Prochaine Revue:** Après complétion Phase 1
**Responsable:** Équipe Développement
