# Analyse des Échecs des Tests Playwright

**Date**: 2025-12-07
**Tests exécutés**: 99
**Tests échoués**: 76
**Taux d'échec**: 76.8%

## Analyse Préliminaire des Erreurs

### Catégories d'Échecs Identifiées

Basé sur l'analyse des fichiers de résultats et messages d'erreur:

#### 1. **Éléments Cachés dans les Menus Dropdown** (Fréquence: Élevée - ~22 occurrences)

**Symptôme**:
```
TimeoutError: locator.waitFor: Timeout 15000ms exceeded.
Call log:
  - waiting for locator('text=Planche').first() to be visible
  34 × locator resolved to hidden <a class="dropdown-item" href="...">Planche automatique</a>
```

**Cause Probable**:
- Les éléments de menu dropdown Bootstrap ne sont pas visibles par défaut
- Les tests tentent d'accéder directement aux liens du dropdown sans ouvrir le menu parent

**Impact**: Tests d'accès aux pages (access-control.spec.js, glider-flights.spec.js)

#### 2. **Timeout de Test Global** (Fréquence: Moyenne - 3+ occurrences)

**Symptôme**:
```
Test timeout of 60000ms exceeded.
```

**Cause Probable**:
- Tests qui prennent trop de temps à s'exécuter
- Possibilité d'attentes actives ou de deadlocks
- Chargement de page très lent ou qui ne se termine pas

**Impact**: Tests migrated (glider-flights.spec.js principalement)

#### 3. **Erreur de Référence `expect`** (Fréquence: Faible - 1 occurrence)

**Symptôme**:
```
ReferenceError: expect is not defined
```

**Cause Probable**:
- Import manquant de `expect` depuis '@playwright/test'
- Test mal migré depuis Dusk

**Impact**: Tests migrated spécifiques

#### 4. **Champs de Formulaire Non Visibles** (Fréquence: Moyenne)

**Symptôme**:
```
TimeoutError: locator.waitFor: Timeout 15000ms exceeded.
Call log:
  - waiting for locator('select[name="vptreuillard"]') to be visible
  35 × locator resolved to hidden <select id="vptreuillard" name="vptreuillard">…</select>
```

**Cause Probable**:
- Champs conditionnels qui dépendent d'autres sélections (mode de décollage, type d'appareil)
- Tests qui tentent de remplir des champs avant que les conditions ne soient remplies
- Logique JavaScript de visibilité des champs qui ne s'active pas correctement

**Impact**: Tests de création de vol (glider-flights.spec.js)

#### 5. **Échecs d'Assertions** (Fréquence: Faible - quelques occurrences)

**Symptôme**:
```
Error: expect(received).toBe(expected) // Object.is equality
Expected: 25
Received: 26
```

**Cause Probable**:
- Données de test qui ne correspondent pas aux attentes
- Compteurs ou pagination qui diffèrent des valeurs attendues
- État de la base de données qui a changé entre les exécutions

**Impact**: Divers tests de validation

## Plan de Correction Proposé

### Phase 1: Corrections Critiques (Blockers)

#### 1.1. Corriger les Accès aux Menus Dropdown
**Priorité**: HAUTE
**Fichiers concernés**:
- `tests/helpers/LoginPage.js`
- `tests/migrated/access-control.spec.js`
- Tous les tests qui naviguent via les menus

**Actions**:
1. Créer une méthode helper `openDropdownMenu(menuText)` dans `BasePage.js`
2. Identifier les menus dropdown (Planche, Comptabilité, etc.)
3. Modifier les tests pour:
   - Hover sur le menu parent
   - Attendre que le dropdown soit visible
   - Cliquer sur l'élément du sous-menu
4. Alternative: Navigation directe par URL plutôt que par menu

**Exemple de correction**:
```javascript
// Avant (échoue)
await page.locator('text=Planche').first().click();

// Après (réussi)
await openDropdownMenu('Vols'); // Ouvre le menu parent
await page.locator('text=Planche').first().click();

// Ou navigation directe
await goto('/vols_planeur/plancheauto_select');
```

#### 1.2. Corriger les Imports Manquants
**Priorité**: HAUTE
**Fichiers concernés**: Tests avec `ReferenceError: expect is not defined`

**Actions**:
1. Vérifier tous les fichiers de test dans `tests/migrated/`
2. Ajouter `const { test, expect } = require('@playwright/test');` si manquant
3. Exécuter `grep -r "expect(" tests/ | grep -v "require.*expect"` pour trouver les fichiers problématiques

### Phase 2: Corrections de Stabilité

#### 2.1. Gérer les Champs Conditionnels du Formulaire de Vol
**Priorité**: MOYENNE
**Fichiers concernés**:
- `tests/helpers/GliderFlightPage.js`
- `tests/migrated/glider-flights.spec.js`

**Actions**:
1. Améliorer la logique de `verifyFieldVisibility()` pour attendre les transitions
2. Ajouter des attentes conditionnelles basées sur le mode de décollage:
   - Remorquage → champs pilote/avion remorqueur visibles
   - Treuil → champ treuillard visible
   - Autonome → aucun champ supplémentaire
3. Utiliser `page.waitForFunction()` pour attendre que la visibilité change
4. Augmenter les timeouts pour les formulaires dynamiques

**Exemple**:
```javascript
// Attendre que le champ devienne visible après sélection du mode
await page.waitForFunction(
  (selector) => {
    const el = document.querySelector(selector);
    return el && el.offsetParent !== null; // visible
  },
  'select[name="pilote_remorqueur"]',
  { timeout: 5000 }
);
```

#### 2.2. Réduire les Timeouts Globaux
**Priorité**: MOYENNE
**Fichiers concernés**: `playwright.config.js`, tests lents

**Actions**:
1. Identifier les tests qui dépassent 60s (via logs)
2. Diviser les tests trop longs en tests plus petits
3. Optimiser les attentes (`waitForLoadState`, `waitForTimeout`)
4. Remplacer les `waitForTimeout(2000)` fixes par des attentes conditionnelles

### Phase 3: Corrections de Données

#### 3.1. Stabiliser les Données de Test
**Priorité**: BASSE
**Fichiers concernés**: Tous les tests qui échouent sur assertions

**Actions**:
1. Vérifier que les fixtures `test-data/fixtures.json` sont à jour
2. S'assurer que la base de données de test est dans un état connu
3. Ajouter un script de reset de données avant les tests
4. Utiliser des données relatives plutôt qu'absolues (ex: "au moins X" au lieu de "exactement X")

### Phase 4: Amélioration de la Robustesse

#### 4.1. Améliorer les Helpers de Page
**Priorité**: BASSE
**Fichiers concernés**: `tests/helpers/*.js`

**Actions**:
1. Ajouter retry logic pour les opérations qui peuvent échouer transitoirement
2. Améliorer les messages d'erreur avec contexte (screenshots, HTML dump)
3. Ajouter des vérifications de préconditions avant les actions
4. Implémenter un système de "wait until stable" pour les éléments dynamiques

#### 4.2. Configurer des Rapports Plus Détaillés
**Priorité**: BASSE
**Fichiers concernés**: `playwright.config.js`

**Actions**:
1. Activer le tracing systématique (pas seulement on retry)
2. Configurer HTML reporter avec screenshots pour tous les échecs
3. Ajouter des logs de débogage dans les helpers

## Approche de Correction Recommandée

### Étape 1: Quick Wins (1-2 heures)
1. Corriger les imports manquants (Phase 1.2)
2. Remplacer navigation par menu par navigation URL directe (Phase 1.1 - solution rapide)

### Étape 2: Stabilisation des Formulaires (3-4 heures)
1. Implémenter les helpers de dropdown (Phase 1.1 - solution propre)
2. Améliorer la gestion des champs conditionnels (Phase 2.1)

### Étape 3: Optimisation (2-3 heures)
1. Réduire les timeouts et améliorer les attentes (Phase 2.2)
2. Nettoyer les données de test (Phase 3.1)

### Étape 4: Polish (optionnel, 2-3 heures)
1. Améliorer les helpers (Phase 4.1)
2. Améliorer le reporting (Phase 4.2)

## Métriques de Succès Attendues

- **Court terme** (après Phase 1): 50% de tests qui passent (49/99)
- **Moyen terme** (après Phase 2): 75% de tests qui passent (74/99)
- **Long terme** (après Phase 3): 90%+ de tests qui passent (89+/99)

## Notes Complémentaires

- Les tests migrés depuis Dusk peuvent avoir des patterns incompatibles avec Playwright
- La version Bootstrap utilisée (5.x) utilise des dropdowns qui nécessitent interaction JavaScript
- Les formulaires GVV ont une logique de visibilité complexe qui nécessite une approche prudente

---

**Note**: Cette analyse est basée sur l'examen préliminaire des résultats de tests. Une analyse complète nécessitera l'examen des résultats complets une fois les tests terminés.
