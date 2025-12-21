# Analyse des Erreurs Playwright - Suite de Tests GVV

**Date d'analyse:** 2025-12-21
**Tests exécutés:** 110
**Résultats:** 24 passés, 81 échecs, 5 skippés
**Taux de succès:** 21.8%
**Durée totale:** 6.6 minutes

---

## Résumé Exécutif

Les erreurs se regroupent en **6 catégories principales** avec des causes racines communes:

| Catégorie | Nombre | Impact | Priorité |
|-----------|--------|--------|----------|
| 1. Problèmes d'authentification/session | 32 tests | CRITIQUE | 🔴 HAUTE |
| 2. Problèmes de navigation/URLs | 15 tests | ÉLEVÉ | 🔴 HAUTE |
| 3. Timeouts sur éléments manquants | 24 tests | ÉLEVÉ | 🟡 MOYENNE |
| 4. Assertions sur structure de page | 7 tests | MOYEN | 🟡 MOYENNE |
| 5. Problèmes de données de test | 2 tests | FAIBLE | 🟢 BASSE |
| 6. Tests manuels (skippés volontairement) | 3 tests | INFO | ⚪ INFO |

---

## CATÉGORIE 1: Problèmes d'Authentification/Session (32 tests)

### 🔴 Impact: CRITIQUE - Bloque l'accès aux fonctionnalités

**Symptôme:** Tests redirigés vers `/auth/login` au lieu de la page attendue

### Sous-catégorie 1.A: Login échoue (9 tests)

**Pattern d'erreur:**
```
expect(page.url()).not.toBe("http://gvv.net/auth/login")
Received: "http://gvv.net/auth/login"
```

**Tests affectés:**
1. `auth-login.spec.js:26` - should successfully login with correct credentials and logout
2. `bugfix-payeur-selector.spec.js:42` - should verify payeur selector in glider flight form (×5 tests)
3. `email-lists-simple-creation.spec.js:19` - should create new email list

**Cause probable:**
- Identifiants de connexion incorrects ou modifiés
- CAPTCHA/reCAPTCHA bloquant l'authentification
- Session expirée trop rapidement
- Cookie de session non persisté

**Fichiers concernés:**
- `tests/auth-login.spec.js`
- `tests/bugfix-payeur-selector.spec.js` (tous les tests)

---

### Sous-catégorie 1.B: Session perdue après login (23 tests)

**Pattern d'erreur:**
```
Error: Redirected to login page when trying to access create form.
User may not be properly logged in.
```

**Tests affectés - Glider Flights (8 tests):**
1. `migrated/glider-flights.spec.js:48` - should create multiple glider flights
2. `migrated/glider-flights.spec.js:85` - show correct fields based on aircraft
3. `migrated/glider-flights.spec.js:121` - reject conflicting flights
4. `migrated/glider-flights.spec.js:194` - update flight information
5. `migrated/glider-flights.spec.js:238` - delete flight
6. `migrated/glider-flights.spec.js:283` - handle different launch methods
7. `migrated/glider-flights.spec.js:330` - handle flight sharing and billing
8. `migrated/glider-flights.spec.js:397` - validate required fields

**Tests affectés - Access Control (4 tests):**
9. `migrated/access-control.spec.js:86` - admin can access all administrative pages
10. `migrated/access-control.spec.js:125` - admin can access financial features
11. `migrated/access-control.spec.js:205` - bureau user has intermediate access
12. `migrated/access-control.spec.js:236` - CA user access to management
13. `migrated/access-control.spec.js:266` - planchiste user access to flight ops

**Tests affectés - Licences (4 tests):**
14-17. `licences-checkbox.spec.js:32,57,98,139` - timeout lors de beforeEach()

**Tests affectés - Login (3 tests):**
18. `migrated/login.spec.js:40` - complete login and logout workflow
19. `migrated/login.spec.js:73` - verify basic access for connected users
20. `migrated/login.spec.js:144` - handle different section selections

**Tests affectés - Smoke (3 tests):**
21. `migrated/smoke.spec.js:97` - handle login/logout cycle multiple times
22. `migrated/smoke.spec.js:115` - handle form interactions
23. `migrated/smoke.spec.js:154` - display proper navigation

**Tests affectés - Saisie Cotisation (1 test):**
24. `saisie-cotisation.spec.js:142` - should be accessible from menu

**Cause probable:**
- Cookie de session non partagé entre les pages
- Timeout de session trop court
- Problème de gestion de contexte Playwright (storageState)
- Protection CSRF bloquant les requêtes

**Code problématique identifié:**
```javascript
// helpers/GliderFlightPage.js:52
const isOnLoginPage = await this.page.locator('input[name="username"]').isVisible()
if (isOnLoginPage) {
  throw new Error('Redirected to login page...')
}
```

---

## CATÉGORIE 2: Problèmes de Navigation/URLs (15 tests)

### 🟡 Impact: ÉLEVÉ - Tests ne peuvent pas accéder aux pages

**Pattern d'erreur:**
```
TimeoutError: locator.click: Timeout 15000ms exceeded
waiting for locator('table a[href*="journal_compte"]').first()
```

### Sous-catégorie 2.A: Éléments de navigation manquants (9 tests)

**Tests affectés - Compta Journal Server-side:**
1-6. `compta_journal_serverside.spec.js` (6 tests)
   - Line 31: DataTables loads correctly
   - Line 53: Search functionality works
   - Line 85: Pagination works correctly
   - Line 120: Column sorting works
   - Line 151: Page length selector works
   - Line 188: No JavaScript errors
7-8. `compta_journal_serverside.spec.js` (2 regression tests)
   - Line 223: Edit and delete buttons still work
   - Line 256: Filters still work

**Tests affectés - Frozen Entry Buttons:**
9-11. `compta_frozen_entry_buttons.spec.js:31` (3 tests)
   - frozen entry shows eye icon
   - unfreezing entry restores edit button
   - view button opens form in view mode

**Cause probable:**
- Page de comptabilité nécessite des permissions spécifiques
- Lien `href*="journal_compte"` n'existe pas sur la page actuelle
- Table vide ou pas de comptes dans la base de test
- JavaScript non chargé correctement

---

### Sous-catégorie 2.B: Problèmes URL wrong host (3 tests)

**Pattern d'erreur:**
```
Expected pattern: /email_lists\/create$/
Received: "http://localhost/email_lists/store"
```

**Tests affectés:**
1. `email-lists-validation.spec.js:43` - name too long validation
2. `email-lists-validation.spec.js:72` - description too long validation
3. `email-lists-validation.spec.js:102` - invalid active_member value

**Cause probable:**
- Test utilise `localhost` au lieu de `gvv.net`
- Redirection après validation vers URL incorrecte
- Config de base URL incohérente

---

### Sous-catégorie 2.C: Problèmes de login alternatif (3 tests)

**Tests affectés - Email Lists Workflow v1.4:**
1-3. `email-lists-workflow-v14.spec.js` (lignes 8)
```javascript
await page.goto('http://localhost/gvv/index.php/dx_auth/login');
await page.fill('input[name="login"]', 'admin');  // ← Timeout ici
```

**Cause probable:**
- URL incorrecte: `localhost` au lieu de `gvv.net`
- Champ de formulaire s'appelle `username` pas `login`

---

## CATÉGORIE 3: Timeouts sur Éléments Manquants (24 tests)

### 🟡 Impact: ÉLEVÉ - Sélecteurs CSS incorrects ou éléments absents

### Sous-catégorie 3.A: DataTables non initialisés (3 tests)

**Pattern d'erreur:**
```
TimeoutError: page.waitForSelector: Timeout 15000ms exceeded
waiting for locator('#journal-table') to be visible
```

**Tests affectés:**
1-3. `datatable-persistence.spec.js:29,50,71`
   - persist page length across reloads
   - persist search term across reloads
   - persist current page across reloads

**Cause probable:**
- ID `#journal-table` n'existe pas dans le DOM
- DataTables JavaScript non chargé
- Page nécessite authentification ou données spécifiques

---

### Sous-catégorie 3.B: Problèmes de bouton submit (3 tests)

**Pattern d'erreur:**
```
TimeoutError: page.click: Timeout 15000ms exceeded
waiting for locator('button[type="submit"]')
```

**Tests affectés:**
1-3. `email_lists_sublists_smoke.spec.js:10` (3 tests identiques)

**Cause probable:**
- Formulaire de login différent (utilise `input[type="submit"]` ?)
- Page de login différente
- JavaScript non chargé

---

### Sous-catégorie 3.C: Problèmes de sélecteurs login (2 tests)

**Pattern d'erreur:**
```
TimeoutError: page.fill: Timeout 15000ms exceeded
waiting for locator('input[name="login"]')
```

**Tests affectés:**
1. `balance-search-debug.spec.js:11` - typing "PEI"
2. `balance-search-debug.spec.js:76` - clear search results
3. `email-lists-create-debug.spec.js:13` - create list debug

**Cause probable:**
- Champ s'appelle `username` pas `login`
- Incohérence de nommage entre tests

---

### Sous-catégorie 3.D: Journaux de compte - soldes pagination (4 tests)

**Pattern d'erreur:**
```
TimeoutError: locator.click: Timeout 15000ms exceeded
waiting for locator('table a[href*="journal_compte"]').first()
```

**Tests affectés:**
1-4. `journal-compte-soldes-pagination.spec.ts` (4 tests TypeScript)

**Cause probable:**
- Même problème que Catégorie 2.A
- Pas de lien vers journal_compte sur la page

---

### Sous-catégorie 3.E: Rapprochements - tabs manquants (6 tests)

**Pattern d'erreur:**
```
TimeoutError: locator.click: Timeout 15000ms exceeded
waiting for locator('#gvv-tab')
```

**Tests affectés:**
1-3. `rapprochements-export.spec.js:46,100,128` (3 tests export buttons)
4-6. `rapprochements-tab-persistence.spec.js:78,159` (2 tests tab switching)

**Cause probable:**
- Tab ID `#gvv-tab` n'existe pas
- Structure HTML différente de ce que les tests attendent
- Tabs Bootstrap non initialisés

---

### Sous-catégorie 3.F: Problèmes de form fields (6 tests)

**Tests affectés - Saisie Cotisation:**
1. `saisie-cotisation.spec.js:24` - membership fee entry form (h3 manquant)
2. `saisie-cotisation.spec.js:51` - select[name="pilote"] manquant
3. `saisie-cotisation.spec.js:104` - button#btnValidate timeout
4. `saisie-cotisation.spec.js:137` - form[name="saisie_cotisation"] manquant

**Tests affectés - Sections:**
5. `sections_ordre_affichage.spec.js:37` - edit button manquant
6. `sections_ordre_affichage.spec.js:61` - create button manquant

**Cause probable:**
- Mauvaise URL de navigation
- Page nécessite permissions spécifiques
- Sélecteurs CSS incorrects

---

## CATÉGORIE 4: Assertions sur Structure de Page (7 tests)

### 🟢 Impact: MOYEN - Tests atteignent la page mais structure différente

**Pattern d'erreur:**
```
expect(locator).toContainText('Expected Text')
Received: "Different Text" or element not found
```

### Tests affectés:

1. **email-lists-simple-creation.spec.js:40**
   ```
   Expected: "Modification"
   Received: "Modifier la liste"
   ```
   → Texte différent, test trop strict

2. **email-lists-smoke.spec.js:65**
   ```
   Expected h3 to contain: "Listes de diffusion"
   Element not found
   ```
   → Mauvais sélecteur ou page incorrecte

3. **email-lists-smoke.spec.js:103**
   ```
   Expected input[name="name"] to be visible
   Element not found
   ```
   → Formulaire non chargé

4. **migrated/access-control.spec.js:334**
   ```
   expect(hasAdminNav).toBeTruthy()
   Received: false
   ```
   → Navigation admin non visible

5. **rapprochements-tab-persistence.spec.js:45**
   ```
   expect(savedTab).toBeTruthy()
   Received: null
   ```
   → sessionStorage vide

6. **sections_ordre_affichage.spec.js:28**
   ```
   expect(headers).toContain('Ordre')
   Received: []
   ```
   → Table headers vides (page probablement incorrecte)

7. **resultat_par_sections_detail_links.spec.js:84**
   ```
   expect(excelButton).toBeVisible()
   Element not found
   ```
   → Boutons export manquants

---

## CATÉGORIE 5: Problèmes de Données de Test (2 tests)

### ⚪ Impact: FAIBLE - Tests skippés car données manquantes

**Tests skippés (intentionnellement):**

1-2. **journal-compte-soldes-simple.spec.ts** (2 tests skippés)
   ```
   Message: "Pas de DataTable trouvé - le compte 37 pourrait ne pas exister"
   Message: "Aucun compte trouvé"
   ```

**Cause:** Base de données de test ne contient pas les données requises

---

## CATÉGORIE 6: Tests Manuels (3 tests)

### ⚪ Impact: INFO - Tests marqués [MANUAL TEST]

**Tests skippés volontairement:**

1-3. **compta_journal_search.spec.js** (3 tests marqués manuels)
   - should display search box for account with > 400 entries
   - should filter table rows based on search term
   - should show all rows when search is cleared

**Raison:** Tests nécessitent intervention manuelle ou setup spécifique

---

## Problèmes Transversaux Identifiés

### 🔴 Problème #1: Incohérence des sélecteurs de login

**Impact:** Au moins 15 tests

**Observations:**
- Certains tests utilisent `input[name="login"]`
- D'autres utilisent `input[name="username"]`
- Page de login réelle utilise probablement `username`

**Fichiers à vérifier:**
```javascript
// Incorrect (15+ fichiers)
await page.fill('input[name="login"]', 'admin');

// Correct (quelques fichiers)
await page.fill('input[name="username"]', 'testadmin');
```

---

### 🔴 Problème #2: Incohérence des URLs de base

**Impact:** Au moins 10 tests

**Observations:**
- Tests mélangent `http://gvv.net` et `http://localhost`
- Tests mélangent `http://localhost/gvv/` et `http://localhost/`

**Exemples:**
```javascript
// Différentes URLs utilisées
'http://gvv.net/auth/login'
'http://localhost/gvv/index.php/dx_auth/login'
'http://localhost/email_lists/create'
```

---

### 🟡 Problème #3: Gestion de session Playwright

**Impact:** 32 tests (toute la catégorie 1)

**Observation:**
Session perdue entre les pages, même après login réussi

**Solutions potentielles:**
1. Utiliser `storageState` pour persister les cookies
2. Augmenter le timeout de session côté serveur
3. Vérifier la configuration CSRF
4. Ajouter `await context.addCookies()` après login

---

### 🟡 Problème #4: Structure de page différente de Dusk

**Impact:** Tests migrés de Dusk (~30 tests)

**Observation:**
Tests migrés depuis Dusk cherchent des éléments qui n'existent plus ou ont changé

**Exemples:**
- `text=Membres` non trouvé dans menu
- `#gvv-tab` n'existe pas
- Table headers vides

---

## Recommandations par Priorité

### 🔴 PRIORITÉ 1 - BLOQUANTS (à faire en premier)

1. **Fixer l'authentification de base**
   - Standardiser sur `username` au lieu de `login`
   - Vérifier que les credentials test sont corrects
   - Documenter les users de test valides

2. **Standardiser les URLs**
   - Créer une constante `BASE_URL = 'http://gvv.net'`
   - Remplacer tous les hardcoded URLs
   - Vérifier la configuration `playwright.config.js`

3. **Implémenter la persistance de session**
   - Utiliser `storageState` pour sauvegarder les cookies après login
   - Créer un helper `loginAndSaveState()` réutilisable

### 🟡 PRIORITÉ 2 - IMPORTANT

4. **Mettre à jour les sélecteurs CSS**
   - Audit des sélecteurs dans tests migrés
   - Vérifier contre le HTML réel de l'application
   - Créer des Page Objects robustes

5. **Fixer les problèmes de navigation**
   - Vérifier que les liens comptabilité existent
   - Ajouter logging pour debug
   - Utiliser des waits plus intelligents

### 🟢 PRIORITÉ 3 - AMÉLIORATION

6. **Améliorer les données de test**
   - Créer un script de seed pour la base de test
   - Documenter les pré-requis de données
   - Utiliser des fixtures Playwright

7. **Refactoriser les tests**
   - Éliminer la duplication de code login
   - Centraliser les configurations
   - Améliorer les messages d'erreur

---

## Statistiques Détaillées

### Erreurs par Type

```
TimeoutError:           47 tests (58%)
Authentication:         32 tests (39%)
Assertion failures:      7 tests (9%)
URL mismatches:          3 tests (4%)
Data issues:             2 tests (2%)
Manual tests (skipped):  3 tests (3%)
```

### Tests par Statut

```
✓ Passés:     24 tests (21.8%)
✗ Échecs:     81 tests (73.6%)
⊘ Skippés:     5 tests (4.5%)
━━━━━━━━━━━━━━━━━━━━━━━━━━━
TOTAL:       110 tests (100%)
```

### Distribution des Échecs par Fichier (Top 10)

```
1. migrated/glider-flights.spec.js        8 échecs
2. bugfix-payeur-selector.spec.js         5 échecs
3. saisie-cotisation.spec.js              5 échecs
4. migrated/access-control.spec.js        5 échecs
5. compta_journal_serverside.spec.js      7 échecs
6. rapprochements-export.spec.js          3 échecs
7. rapprochements-tab-persistence.spec.js 4 échecs
8. licences-checkbox.spec.js              4 échecs
9. migrated/login.spec.js                 3 échecs
10. migrated/smoke.spec.js                3 échecs
```

---

## Annexe: Tests Passant (24 tests)

Ces tests fonctionnent correctement et peuvent servir de référence:

### Login & Auth (3 tests)
- ✓ auth-login.spec.js - deny login with incorrect password
- ✓ auth-login.spec.js - show login form elements
- ✓ migrated/login.spec.js - deny access with wrong password
- ✓ migrated/login.spec.js - show all required login form elements
- ✓ migrated/login.spec.js - access home page and see basic elements

### Email Lists (5 tests)
- ✓ email-lists-simple-creation.spec.js - show validation errors for empty name
- ✓ email-lists-simple-creation.spec.js - cancel creation and return to list
- ✓ email-lists-validation-simple.spec.js - display validation error for name too long
- ✓ email-lists-validation.spec.js - preserve form values after validation error
- ✓ email-lists-smoke.spec.js - check menu entry exists in Dev menu

### Examples (2 tests)
- ✓ example.spec.js - has title
- ✓ example.spec.js - get started link

### Smoke Tests (6 tests)
- ✓ migrated/smoke.spec.js - load application without errors
- ✓ migrated/smoke.spec.js - navigate to core pages without errors
- ✓ migrated/smoke.spec.js - handle AJAX requests without errors
- ✓ migrated/smoke.spec.js - handle different screen sizes
- ✓ migrated/smoke.spec.js - load all critical resources

### Access Control (1 test)
- ✓ migrated/access-control.spec.js - all user types can access basic flight pages

### Rapprochements Search (3 tests)
- ✓ rapprochements-search.spec.js - filter operations when typing
- ✓ rapprochements-search.spec.js - clear search when clear button clicked
- ✓ rapprochements-search.spec.js - be case-insensitive

### Misc (4 tests)
- ✓ login-page-capture.spec.js - capture screenshot and HTML
- ✓ resultat_par_sections_detail_links.spec.js - display simplified table structure

---

## Conclusion

**État actuel:** 21.8% des tests passent (24/110)

**Problème racine principal:** Gestion d'authentification/session défaillante

**Impact:** La majorité des échecs (40%) sont liés à l'authentification

**Prochaines étapes recommandées:**
1. Fixer le login de base (sélecteurs + credentials)
2. Implémenter la persistence de session
3. Standardiser les URLs
4. Mettre à jour les sélecteurs migrés de Dusk

**Estimation:**
- Correction de l'auth: pourrait faire passer ~40 tests supplémentaires
- Taux de succès potentiel après fix auth: ~58% (64/110 tests)
