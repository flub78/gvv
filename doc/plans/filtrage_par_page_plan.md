# Plan d'Implémentation : Gestion des Filtres et Section par Contexte Fonctionnel

**Date:** 2025-12-02
**Version:** 1.1
**Statut:** Prêt pour implémentation
**PRD:** `doc/prds/filtrage_par_page.md`
**Approche:** Option A - Paramètres URL avec Contexte Fonctionnel

---

## Vue d'ensemble

**Objectif:** Isoler les variables de contexte (filtres, section active) par contexte fonctionnel pour permettre l'utilisation simultanée de plusieurs onglets sans conflit, tout en partageant les filtres entre pages fonctionnellement liées.

**Solution:** Stocker les filtres et la section dans l'URL sous forme de paramètres GET, avec un paramètre `ctx` pour regrouper les pages liées.

**Innovation:** Pages fonctionnellement liées (ex: `comptes/page` et `comptes/balance`) partagent le même contexte `ctx=comptes`, évitant de perdre les filtres lors de la navigation.

**Contrainte technique:** L'architecture HTTP ne permet pas au serveur de distinguer les onglets du navigateur (tous partagent la même session). L'utilisation de paramètres URL est donc obligatoire pour l'isolation multi-onglets.

**Estimation totale:** 4 jours (32 heures)

---

## Phase 1 : Infrastructure et Helpers (1 jour = 8h)

### ⏳ TÂCHE 1.1 : Créer helper context_url()
**Durée:** 3h
**Statut:** En attente
**Fichier:** `application/helpers/url_helper.php` (nouveau ou extension)

**Objectif:** Créer un helper pour générer des URLs avec préservation du contexte

**Implémentation:**

```php
<?php
/**
 * Generate URL with context parameters preserved
 *
 * Preserves filter parameters and section from current URL
 *
 * @param string $uri URI to generate (e.g., 'compta/page')
 * @param array $additional_params Additional parameters to add/override
 * @param bool $preserve_filters Whether to preserve filter parameters (default: true)
 * @return string Full URL with context parameters
 */
if (!function_exists('context_url')) {
    function context_url($uri, $additional_params = array(), $preserve_filters = true) {
        $CI =& get_instance();

        $context = array();

        // Always preserve filter context
        if ($ctx = $CI->input->get('ctx')) {
            $context['ctx'] = $ctx;
        } elseif (method_exists($CI, 'get_filter_context')) {
            $context['ctx'] = $CI->get_filter_context();
        }

        // Always preserve section
        if ($section = $CI->input->get('section')) {
            $context['section'] = $section;
        }

        // Preserve filters if requested and active
        if ($preserve_filters && $CI->input->get('filter_active')) {
            $context['filter_active'] = 1;

            // Get filter variables from controller if available
            if (isset($CI->filter_variables) && is_array($CI->filter_variables)) {
                foreach ($CI->filter_variables as $var) {
                    $value = $CI->input->get($var);
                    if ($value !== null && $value !== '') {
                        $context[$var] = $value;
                    }
                }
            }

            // Fallback: preserve all non-standard GET parameters
            // (in case filter_variables not defined)
            $all_get = $CI->input->get();
            $standard_params = array('ctx', 'section', 'filter_active');
            foreach ($all_get as $key => $value) {
                if (!in_array($key, $standard_params) && $value !== '') {
                    $context[$key] = $value;
                }
            }
        }

        // Merge with additional parameters (override context if conflict)
        $params = array_merge($context, $additional_params);

        // Build URL
        $url = site_url($uri);
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }
}

/**
 * Generate context parameters array from current request
 *
 * @return array Context parameters (section, filters, etc.)
 */
if (!function_exists('get_context_params')) {
    function get_context_params() {
        $CI =& get_instance();
        $params = array();

        // Filter context
        if ($ctx = $CI->input->get('ctx')) {
            $params['ctx'] = $ctx;
        } elseif (method_exists($CI, 'get_filter_context')) {
            $params['ctx'] = $CI->get_filter_context();
        }

        // Section
        if ($section = $CI->input->get('section')) {
            $params['section'] = $section;
        }

        // Filters
        if ($CI->input->get('filter_active')) {
            $params['filter_active'] = 1;

            if (isset($CI->filter_variables)) {
                foreach ($CI->filter_variables as $var) {
                    $value = $CI->input->get($var);
                    if ($value !== null && $value !== '') {
                        $params[$var] = $value;
                    }
                }
            }
        }

        return $params;
    }
}
```

**Actions:**
- [ ] Créer ou ouvrir `application/helpers/url_helper.php`
- [ ] Implémenter `context_url()`
- [ ] Implémenter `get_context_params()`
- [ ] Ajouter commentaires détaillés PHPDoc
- [ ] Valider syntaxe PHP

**Commandes:**
```bash
source setenv.sh
php -l application/helpers/url_helper.php
```

**Tests manuels:**
```php
// Dans un contrôleur de test
echo context_url('compta/page');
// Doit inclure paramètres actuels

echo context_url('membre/create', array('id' => 5));
// Doit inclure contexte + id=5
```

### ⏳ TÂCHE 1.2 : Ajouter méthode load_context_variable() dans Gvv_Controller
**Durée:** 2h
**Statut:** En attente
**Dépendances:** Aucune
**Fichier:** `application/libraries/Gvv_Controller.php`

**Objectif:** Créer une méthode centralisée pour charger les variables de contexte

**Implémentation:**

```php
/**
 * Load a context variable with priority: URL > Session > Default
 *
 * Context variables are those that define the current view context:
 * filters, section, year, etc. They can come from URL parameters
 * (for multi-tab isolation) or session (for backward compatibility).
 *
 * When loaded from URL, the value is also stored in session for
 * consistency with legacy code.
 *
 * @param string $name Variable name
 * @param mixed $default Default value if not found
 * @param bool $update_session Whether to update session with URL value (default: true)
 * @return mixed Variable value
 */
protected function load_context_variable($name, $default = null, $update_session = true) {
    // Priority 1: URL parameter
    $value = $this->input->get($name);

    if ($value === null || $value === '') {
        // Priority 2: Session (fallback for backward compatibility)
        $value = $this->session->userdata($name);
    }

    if ($value === null || $value === '') {
        // Priority 3: Default value
        $value = $default;
    }

    // Update session for consistency (unless explicitly disabled)
    if ($update_session && $value !== null && $value !== '') {
        $this->session->set_userdata($name, $value);
    }

    return $value;
}

/**
 * Load all context variables (filters + section)
 *
 * @return array Associative array of context variables
 */
protected function load_all_context() {
    $context = array();

    // Section
    $context['section'] = $this->load_context_variable('section', 1);

    // Filter active flag
    $context['filter_active'] = $this->load_context_variable('filter_active', 0);

    // Filter variables (if defined in controller)
    if (isset($this->filter_variables)) {
        foreach ($this->filter_variables as $var) {
            $context[$var] = $this->load_context_variable($var, '');
        }
    }

    return $context;
}

/**
 * Validate context parameters from URL
 *
 * Ensures URL parameters are safe and valid
 *
 * @return bool True if valid, redirects to error page if invalid
 */
protected function validate_context_params() {
    // Validate section (must be numeric if present)
    $section = $this->input->get('section');
    if ($section !== null && $section !== '' && !is_numeric($section)) {
        show_error('Invalid section parameter', 400);
        return false;
    }

    // Validate filter_active (must be 0 or 1)
    $filter_active = $this->input->get('filter_active');
    if ($filter_active !== null && $filter_active !== '' && !in_array($filter_active, array('0', '1'))) {
        show_error('Invalid filter_active parameter', 400);
        return false;
    }

    // Additional validation can be added here

    return true;
}

/**
 * Get filter context for this controller
 *
 * Used to group functionally related pages that should share filters.
 * For example, comptes/page and comptes/balance share ctx=comptes.
 *
 * @return string Filter context identifier
 */
protected function get_filter_context() {
    // Use filter_context if defined, otherwise controller name
    return isset($this->filter_context) ? $this->filter_context : $this->controller;
}
```

**Actions:**
- [ ] Localiser le bon endroit dans `Gvv_Controller.php` (après `load_filter()`)
- [ ] Implémenter `load_context_variable()`
- [ ] Implémenter `load_all_context()`
- [ ] Implémenter `validate_context_params()`
- [ ] Implémenter `get_filter_context()`
- [ ] Ajouter commentaires PHPDoc
- [ ] Valider syntaxe PHP

**Commandes:**
```bash
source setenv.sh
php -l application/libraries/Gvv_Controller.php
```

### ⏳ TÂCHE 1.3 : Créer tests unitaires pour les helpers
**Durée:** 2h
**Statut:** En attente
**Dépendances:** TÂCHE 1.1, TÂCHE 1.2
**Fichier:** `application/tests/unit/helpers/UrlHelperTest.php` (nouveau)

**Objectif:** Valider le fonctionnement des helpers

**Implémentation:**

```php
<?php
use PHPUnit\Framework\TestCase;

class UrlHelperTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        // Load CI instance and helpers
        $this->CI =& get_instance();
        $this->CI->load->helper('url_helper');
    }

    public function test_context_url_preserves_section() {
        // Mock GET parameter
        $_GET['section'] = '2';

        $url = context_url('compta/page');

        $this->assertStringContainsString('section=2', $url);
    }

    public function test_context_url_preserves_filters() {
        $_GET['section'] = '1';
        $_GET['filter_active'] = '1';
        $_GET['compte1'] = '512';

        $this->CI->filter_variables = array('compte1');

        $url = context_url('compta/page');

        $this->assertStringContainsString('section=1', $url);
        $this->assertStringContainsString('filter_active=1', $url);
        $this->assertStringContainsString('compte1=512', $url);
    }

    public function test_context_url_adds_additional_params() {
        $_GET['section'] = '1';

        $url = context_url('membre/edit/5', array('mode' => 'view'));

        $this->assertStringContainsString('section=1', $url);
        $this->assertStringContainsString('mode=view', $url);
    }

    public function test_context_url_without_preserve_filters() {
        $_GET['section'] = '1';
        $_GET['filter_active'] = '1';
        $_GET['compte1'] = '512';

        $url = context_url('welcome', array(), false);

        $this->assertStringContainsString('section=1', $url);
        $this->assertStringNotContainsString('compte1', $url);
    }

    public function test_get_context_params() {
        $_GET['section'] = '2';
        $_GET['filter_active'] = '1';
        $_GET['compte1'] = '512';

        $this->CI->filter_variables = array('compte1');

        $params = get_context_params();

        $this->assertEquals('2', $params['section']);
        $this->assertEquals('1', $params['filter_active']);
        $this->assertEquals('512', $params['compte1']);
    }

    protected function tearDown(): void {
        // Clean up
        $_GET = array();
        parent::tearDown();
    }
}
```

**Actions:**
- [ ] Créer `application/tests/unit/helpers/UrlHelperTest.php`
- [ ] Implémenter les tests
- [ ] Exécuter les tests
- [ ] Ajuster le code si tests échouent

**Commandes:**
```bash
source setenv.sh
vendor/bin/phpunit application/tests/unit/helpers/UrlHelperTest.php
```

### ⏳ TÂCHE 1.4 : Documentation des helpers
**Durée:** 1h
**Statut:** En attente
**Dépendances:** TÂCHE 1.1, TÂCHE 1.2

**Actions:**
- [ ] Ajouter section dans `doc/AI_INSTRUCTIONS.md` sur les helpers de contexte
- [ ] Documenter l'usage dans les commentaires de code
- [ ] Créer exemples d'utilisation

---

## Phase 2 : Modification Gvv_Controller (0.5 jour = 4h)

### ⏳ TÂCHE 2.1 : Modifier active_filter() pour rediriger avec URL
**Durée:** 2h
**Statut:** En attente
**Dépendances:** TÂCHE 1.1, TÂCHE 1.2
**Fichier:** `application/libraries/Gvv_Controller.php`
**Lignes:** 777-798

**Objectif:** Modifier la fonction pour stocker les filtres dans l'URL au lieu de seulement dans la session

**Modifications:**

**AVANT (lignes 777-798):**
```php
function active_filter($filter_variables) {
    $button = $this->input->post('button');

    if (($button == "Filtrer") || ($button == $this->lang->line("gvv_str_select"))) {
        // Enable filtering
        foreach ($filter_variables as $field) {
            $session[$field] = $this->input->post($field);
        }

        $session['filter_active'] = 1;
        $this->session->set_userdata($session);
    } else {
        // Disable filtering
        foreach ($filter_variables as $field) {
            $this->session->unset_userdata($field);
        }
    }
}
```

**APRÈS:**
```php
function active_filter($filter_variables) {
    $button = $this->input->post('button');

    // Store filter_variables for context_url helper
    $this->filter_variables = $filter_variables;

    if (($button == "Filtrer") || ($button == $this->lang->line("gvv_str_select"))) {
        // Enable filtering - Redirect to URL with parameters

        // Build filter parameters
        $params = array();

        // Always include filter context
        $params['ctx'] = $this->get_filter_context();

        foreach ($filter_variables as $field) {
            $value = $this->input->post($field);
            if ($value !== null && $value !== '') {
                $params[$field] = $value;
                // Also update session for backward compatibility
                $this->session->set_userdata($field, $value);
            }
        }

        $params['filter_active'] = 1;
        $this->session->set_userdata('filter_active', 1);

        // Always include section in URL
        $section = $this->load_context_variable('section', 1);
        $params['section'] = $section;

        // Redirect to page with filter parameters
        $url = $this->controller . '/page?' . http_build_query($params);
        redirect($url);

    } elseif ($button == $this->lang->line("gvv_button_reset_filter") || $button == "Annuler") {
        // Disable filtering - Redirect to URL without filter parameters

        // Clear filter variables from session
        foreach ($filter_variables as $field) {
            $this->session->unset_userdata($field);
        }
        $this->session->unset_userdata('filter_active');

        // Redirect to page without filters (keep ctx and section)
        $params = array();

        // Keep filter context
        $params['ctx'] = $this->get_filter_context();

        // Keep section
        $section = $this->load_context_variable('section', 1);
        if ($section) {
            $params['section'] = $section;
        }

        $url = $this->controller . '/page';
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        redirect($url);
    }
}
```

**Actions:**
- [ ] Localiser la fonction `active_filter()` dans `Gvv_Controller.php`
- [ ] Remplacer l'implémentation
- [ ] Ajouter gestion du bouton "Annuler" / "Reset"
- [ ] Inclure la section dans les paramètres
- [ ] Valider syntaxe PHP
- [ ] Tester manuellement

**Commandes:**
```bash
source setenv.sh
php -l application/libraries/Gvv_Controller.php
```

### ⏳ TÂCHE 2.2 : Modifier load_filter() pour utiliser URL
**Durée:** 1h
**Statut:** En attente
**Dépendances:** TÂCHE 1.2, TÂCHE 2.1
**Fichier:** `application/libraries/Gvv_Controller.php`
**Lignes:** 806-811

**Objectif:** Charger les filtres depuis l'URL en priorité

**Modifications:**

**AVANT (lignes 806-811):**
```php
function load_filter($filter_variables) {
    foreach ($filter_variables as $field) {
        $this->data[$field] = $this->session->userdata($field);
    }
}
```

**APRÈS:**
```php
function load_filter($filter_variables) {
    // Store filter_variables for context_url helper
    $this->filter_variables = $filter_variables;

    foreach ($filter_variables as $field) {
        // Use load_context_variable: URL > Session > Default
        $this->data[$field] = $this->load_context_variable($field, '');
    }

    // Also load filter_active flag
    $this->data['filter_active'] = $this->load_context_variable('filter_active', 0);
}
```

**Actions:**
- [ ] Localiser la fonction `load_filter()`
- [ ] Remplacer l'implémentation
- [ ] Utiliser `load_context_variable()`
- [ ] Stocker `filter_variables` pour les helpers
- [ ] Valider syntaxe PHP

### ⏳ TÂCHE 2.3 : Adapter le constructeur pour la section
**Durée:** 1h
**Statut:** En attente
**Dépendances:** TÂCHE 1.2
**Fichier:** `application/libraries/Gvv_Controller.php`
**Lignes:** ~40-95

**Objectif:** Charger la section depuis l'URL en priorité

**Modifications:**

Dans le constructeur de `Gvv_Controller`, ajouter après l'initialisation de la session:

```php
// Load context variables (section, year, etc.) from URL or session
// This must be done early so they're available for all subsequent operations

// Validate context parameters from URL
$this->validate_context_params();

// Section (critical for data isolation)
// Priority: URL > Session > Default (1)
$section_from_url = $this->input->get('section');
if ($section_from_url !== null && $section_from_url !== '') {
    $this->session->set_userdata('section', $section_from_url);
} elseif (!$this->session->userdata('section')) {
    $this->session->set_userdata('section', 1); // Default section
}

// Year (filter context)
if (!$this->session->userdata('year')) {
    $this->session->set_userdata('year', date('Y'));
}

// Per page (global preference, NOT in URL)
if (!$this->session->userdata('per_page')) {
    $this->session->set_userdata('per_page', PER_PAGE);
}

// Licence type
if (!$this->session->userdata('licence_type')) {
    $this->session->set_userdata('licence_type', 0);
}
```

**Actions:**
- [ ] Localiser le constructeur `__construct()`
- [ ] Ajouter validation des paramètres
- [ ] Ajouter chargement de la section depuis URL
- [ ] Maintenir compatibilité avec code existant
- [ ] Valider syntaxe PHP

---

## Phase 3 : Adaptation Common_Model (0.5 jour = 4h)

### ⏳ TÂCHE 3.1 : Modifier common_model pour utiliser URL
**Durée:** 2h
**Statut:** En attente
**Dépendances:** TÂCHE 2.3
**Fichier:** `application/models/common_model.php`
**Lignes:** ~30

**Objectif:** Charger `section_id` depuis URL > Session

**Modifications:**

**AVANT (ligne 30):**
```php
$this->section_id = $this->session->userdata('section');
```

**APRÈS:**
```php
// Load section from URL (if present) or session
// The controller should have already loaded this into session if URL parameter exists
$this->section_id = $this->session->userdata('section');

// Fallback to 1 if not set (should not happen if controller setup is correct)
if (!$this->section_id) {
    $this->section_id = 1;
    gvv_debug("Warning: section_id not found in session, defaulting to 1");
}
```

**Note:** La logique principale de chargement depuis l'URL est dans `Gvv_Controller::__construct()`, donc ici on lit juste depuis la session qui a déjà été mise à jour.

**Actions:**
- [ ] Ouvrir `application/models/common_model.php`
- [ ] Modifier la ligne 30
- [ ] Ajouter fallback de sécurité
- [ ] Ajouter debug log
- [ ] Valider syntaxe PHP

### ⏳ TÂCHE 3.2 : Vérifier autres modèles
**Durée:** 2h
**Statut:** En attente
**Dépendances:** TÂCHE 3.1

**Objectif:** S'assurer qu'aucun autre modèle n'a de dépendance directe sur `section` en session

**Actions:**
- [ ] Rechercher tous les usages de `userdata('section')` dans models
- [ ] Vérifier qu'ils héritent de `common_model` ou utilisent le bon pattern
- [ ] Documenter les cas particuliers

**Commandes:**
```bash
grep -r "userdata.*section" application/models/ --include="*.php"
```

**Validation:**
- Tous les modèles utilisent soit `common_model::section_id`, soit chargent correctement depuis session

---

## Phase 4 : Adaptation des Vues et Liens (1 jour = 8h)

### ⏳ TÂCHE 4.1 : Créer une vue de test pour les liens
**Durée:** 1h
**Statut:** En attente
**Dépendances:** TÂCHE 1.1

**Objectif:** Créer un fichier de test pour valider les modifications

**Actions:**
- [ ] Créer `application/views/test_context_links.php`
- [ ] Ajouter exemples de tous types de liens
- [ ] Tester que les liens incluent le contexte

### ⏳ TÂCHE 4.2 : Identifier toutes les vues avec liens
**Durée:** 1h
**Statut:** En attente

**Objectif:** Créer un inventaire des vues à modifier

**Actions:**
- [ ] Lister toutes les vues `*View.php`
- [ ] Identifier celles avec des liens internes (pagination, édition, création)
- [ ] Prioriser par fréquence d'utilisation

**Commandes:**
```bash
find application/views -name "*View.php" -type f | wc -l
grep -r "site_url\|base_url\|anchor" application/views/ --include="*View.php" | wc -l
```

**Livrable:**
- Liste des vues à modifier (~50-100 vues estimées)

### ⏳ TÂCHE 4.3 : Modifier les vues de table (tableView)
**Durée:** 3h
**Statut:** En attente
**Dépendances:** TÂCHE 1.1, TÂCHE 4.2

**Objectif:** Adapter toutes les vues de type `tableView` pour utiliser `context_url()`

**Pattern de modification:**

**AVANT:**
```php
<a href="<?= site_url($controller . '/edit/' . $row[$kid]) ?>">Modifier</a>
```

**APRÈS:**
```php
<a href="<?= context_url($controller . '/edit/' . $row[$kid]) ?>">Modifier</a>
```

**Actions par vue:**
1. Ouvrir la vue `application/views/*/bs_tableView.php`
2. Remplacer `site_url()` par `context_url()` pour:
   - Liens "Modifier" / "Edit"
   - Liens "Voir" / "View"
   - Liens "Créer nouveau" / "Create"
   - Pagination (voir TÂCHE 4.5)
3. Valider syntaxe PHP
4. Tester manuellement la vue

**Vues prioritaires (haute fréquence):**
- `compta/bs_tableView.php`
- `membre/bs_tableView.php`
- `vols_avion/bs_tableView.php`
- `vols_planeur/bs_tableView.php`
- `avion/bs_tableView.php`
- `planeur/bs_tableView.php`

**Script de remplacement automatique (à valider manuellement ensuite):**
```bash
# Backup
cp -r application/views application/views.backup

# Remplacement (à ajuster selon patterns trouvés)
find application/views -name "*tableView.php" -exec sed -i 's/site_url(\$controller/context_url(\$controller/g' {} \;
```

### ⏳ TÂCHE 4.4 : Modifier les vues de formulaire (formView)
**Durée:** 2h
**Statut:** En attente
**Dépendances:** TÂCHE 1.1, TÂCHE 4.2

**Objectif:** Adapter les vues de formulaire pour les liens "Annuler", "Retour liste"

**Pattern:**

**AVANT:**
```php
<a href="<?= site_url($controller . '/page') ?>" class="btn btn-secondary">Retour</a>
```

**APRÈS:**
```php
<a href="<?= context_url($controller . '/page') ?>" class="btn btn-secondary">Retour</a>
```

**Actions:**
- [ ] Identifier les vues `*formView.php`
- [ ] Remplacer `site_url()` par `context_url()` pour liens de navigation
- [ ] Tester que les formulaires POST ne sont pas affectés
- [ ] Valider que "Annuler" revient à la page filtrée

### ⏳ TÂCHE 4.5 : Configurer pagination CodeIgniter
**Durée:** 1h
**Statut:** En attente
**Dépendances:** TÂCHE 2.1, TÂCHE 2.2

**Objectif:** S'assurer que la pagination préserve les paramètres GET

**Modifications dans contrôleurs utilisant pagination:**

```php
// Dans la fonction page() ou similaire
$config['base_url'] = site_url($this->controller . '/page');
$config['reuse_query_string'] = TRUE;  // ← AJOUTER CETTE LIGNE
$config['total_rows'] = $this->gvv_model->count();
$config['per_page'] = PER_PAGE;

$this->pagination->initialize($config);
```

**Actions:**
- [ ] Identifier tous les usages de `$this->pagination`
- [ ] Ajouter `$config['reuse_query_string'] = TRUE`
- [ ] Tester que les liens de pagination incluent les filtres

**Contrôleurs à modifier:**
- Tous les contrôleurs avec pagination (chercher `->pagination->initialize`)

**Commandes:**
```bash
grep -r "pagination->initialize" application/controllers/ --include="*.php"
```

---

## Phase 5 : Tests et Validation (0.5 jour = 4h)

### ⏳ TÂCHE 5.1 : Tests multi-onglets manuels
**Durée:** 2h
**Statut:** En attente
**Dépendances:** Toutes les phases précédentes

**Objectif:** Valider les cas d'usage du PRD

**Test 1: Isolation des filtres (CU-001)**

1. **Onglet A:**
   - [ ] Ouvrir `http://gvv.net/compta/page`
   - [ ] Appliquer filtre: `compte1 = 512`, `year = 2024`
   - [ ] Vérifier URL: `/compta/page?section=1&compte1=512&year=2024&filter_active=1`
   - [ ] Noter les données affichées

2. **Onglet B:**
   - [ ] Ouvrir nouvel onglet (Ctrl+T)
   - [ ] Aller sur `http://gvv.net/compta/page`
   - [ ] Appliquer filtre: `compte1 = 411`, `year = 2025`
   - [ ] Vérifier URL: `/compta/page?section=1&compte1=411&year=2025&filter_active=1`
   - [ ] Noter les données affichées

3. **Onglet A:**
   - [ ] Revenir sur Onglet A
   - [ ] Rafraîchir (F5)
   - [ ] **Vérifier:** Affiche toujours `compte1 = 512`, `year = 2024` ✅
   - [ ] **Vérifier:** URL n'a pas changé ✅

4. **Onglet B:**
   - [ ] Rafraîchir
   - [ ] **Vérifier:** Affiche toujours `compte1 = 411`, `year = 2025` ✅

**Test 2: Isolation de section (CU-002)**

1. **Onglet A:**
   - [ ] Travailler sur Section "Avions" (section=1)
   - [ ] URL: `/vols_avion/page?section=1`
   - [ ] Créer un nouveau vol
   - [ ] **Vérifier:** Vol créé dans section 1 ✅

2. **Onglet B:**
   - [ ] Changer de section vers "Planeurs" (section=2)
   - [ ] URL: `/vols_planeur/page?section=2`
   - [ ] Créer un nouveau vol
   - [ ] **Vérifier:** Vol créé dans section 2 ✅

3. **Onglet A:**
   - [ ] Revenir sur Onglet A
   - [ ] **Vérifier:** Toujours sur section 1 ✅
   - [ ] Créer un autre vol
   - [ ] **Vérifier:** Vol créé dans section 1 ✅

**Test 3: Bookmark et partage (CU-003)**

1. [ ] Configurer des filtres complexes
2. [ ] Copier l'URL
3. [ ] Fermer l'onglet
4. [ ] Ouvrir nouvelle fenêtre et coller l'URL
5. [ ] **Vérifier:** Filtres sont restaurés ✅
6. [ ] Partager l'URL avec un collègue (simulation)
7. [ ] **Vérifier:** Collègue voit la même vue ✅

**Livrable:**
- Rapport de tests avec captures d'écran
- Liste des bugs trouvés (si applicable)

### ⏳ TÂCHE 5.2 : Tests de navigation et liens
**Durée:** 1h
**Statut:** En attente
**Dépendances:** TÂCHE 5.1

**Objectif:** Valider que tous les types de liens fonctionnent

**Actions:**

1. **Avec filtres actifs:**
   - [ ] Cliquer "Modifier" → Vérifier URL conserve filtres
   - [ ] Modifier et sauvegarder → Vérifier retour à page filtrée
   - [ ] Cliquer "Créer nouveau" → Vérifier URL conserve filtres
   - [ ] Créer et sauvegarder → Vérifier retour à page filtrée
   - [ ] Utiliser pagination → Vérifier filtres préservés
   - [ ] Cliquer "Annuler filtre" → Vérifier filtres supprimés de l'URL

2. **Sans filtres:**
   - [ ] Navigation normale fonctionne
   - [ ] Pas de paramètres superflus dans URL

3. **Back/Forward navigateur:**
   - [ ] Tester bouton Précédent
   - [ ] Tester bouton Suivant
   - [ ] **Vérifier:** Historique fonctionne correctement ✅

### ⏳ TÂCHE 5.3 : Tests de régression
**Durée:** 30min
**Statut:** En attente
**Dépendances:** TÂCHE 5.1, TÂCHE 5.2

**Objectif:** S'assurer qu'aucune régression n'a été introduite

**Actions:**
- [ ] Exécuter suite PHPUnit complète
- [ ] Tester création/modification dans plusieurs contrôleurs
- [ ] Tester suppression
- [ ] Vérifier authentification fonctionne
- [ ] Vérifier changement de langue fonctionne

**Commandes:**
```bash
source setenv.sh
./run-all-tests.sh
```

**Critère de succès:**
- [ ] 100% des tests PHPUnit passent
- [ ] Aucun warning PHP dans les logs
- [ ] Fonctionnalités de base fonctionnent

### ⏳ TÂCHE 5.4 : Tests de sécurité
**Durée:** 30min
**Statut:** En attente
**Dépendances:** TÂCHE 5.1

**Objectif:** Valider que les paramètres URL sont sécurisés

**Tests d'injection:**

1. **SQL Injection:**
   ```
   /compta/page?compte1=512' OR '1'='1
   ```
   - [ ] **Vérifier:** Requête bloquée ou échappée ✅

2. **XSS:**
   ```
   /compta/page?compte1=<script>alert('XSS')</script>
   ```
   - [ ] **Vérifier:** Script non exécuté ✅

3. **Section invalide:**
   ```
   /compta/page?section=abc
   /compta/page?section=-1
   ```
   - [ ] **Vérifier:** Erreur 400 ou fallback section 1 ✅

4. **Paramètres excessifs:**
   - [ ] Créer URL avec 50+ paramètres
   - [ ] **Vérifier:** Pas de crash, pas de comportement anormal ✅

**Livrable:**
- Rapport de tests de sécurité
- Confirmation que tous les tests passent

---

## Phase 6 : Documentation (0.5 jour = 4h)

### ⏳ TÂCHE 6.1 : Mettre à jour AI_INSTRUCTIONS.md
**Durée:** 1h
**Statut:** En attente
**Dépendances:** Toutes les phases précédentes

**Objectif:** Documenter le nouveau comportement pour les développeurs

**Actions:**
- [ ] Ouvrir `doc/AI_INSTRUCTIONS.md`
- [ ] Ajouter section "Context Management via URL"
- [ ] Documenter l'utilisation de `context_url()`
- [ ] Documenter `load_context_variable()`
- [ ] Ajouter exemples

**Contenu à ajouter:**

```markdown
### Context Management via URL

GVV utilise les paramètres URL pour isoler le contexte de filtrage entre onglets.

**Helpers disponibles:**

- `context_url($uri, $additional_params)` - Génère URL avec contexte préservé
- `get_context_params()` - Récupère les paramètres de contexte actuels

**Variables de contexte:**

- `section` - Section/club actif (CRITIQUE pour isolation)
- `filter_active` - Filtres actifs (0 ou 1)
- Variables de filtrage spécifiques au contrôleur

**Utilisation dans les vues:**

```php
// Au lieu de:
<a href="<?= site_url('compta/edit/' . $id) ?>">Modifier</a>

// Utiliser:
<a href="<?= context_url('compta/edit/' . $id) ?>">Modifier</a>
```

**Utilisation dans les contrôleurs:**

```php
// Charger une variable de contexte
$compte1 = $this->load_context_variable('compte1', '');

// Rediriger avec contexte
redirect(context_url('compta/page'));
```

**Avantages:**

- Isolation multi-onglets (chaque onglet a son contexte)
- URLs bookmarkables
- Partage de vues filtrées possible
- Historique navigateur fonctionne correctement
```

### ⏳ TÂCHE 6.2 : Ajouter commentaires inline
**Durée:** 1h
**Statut:** En attente
**Dépendances:** Phases 1-4

**Objectif:** Documenter le code modifié

**Actions:**
- [ ] Ajouter commentaires dans `Gvv_Controller.php`
- [ ] Ajouter commentaires dans `common_model.php`
- [ ] Expliquer le pattern URL > Session > Default
- [ ] Documenter les raisons de la migration

**Exemple de commentaire:**

```php
/**
 * Filter management with URL-based context (Multi-tab isolation)
 *
 * Since GVV 2025-12, filter variables are stored in URL parameters
 * instead of session to allow multiple tabs/windows to work independently.
 *
 * The session is still updated for backward compatibility with legacy code.
 *
 * Priority: URL > Session > Default
 *
 * @see doc/prds/filtrage_par_page.md
 */
```

### ⏳ TÂCHE 6.3 : Créer guide de migration (optionnel)
**Durée:** 2h
**Statut:** En attente (optionnel)

**Objectif:** Documenter la migration pour développeurs externes

**Actions:**
- [ ] Créer `doc/guides/migration_url_context.md`
- [ ] Expliquer les changements
- [ ] Donner exemples before/after
- [ ] Lister les points d'attention

---

## Phase 7 : Optimisations et Améliorations (Optionnel - 0.5 jour)

### ⏳ TÂCHE 7.1 : Compression URL (optionnel)
**Durée:** 2h
**Statut:** Optionnel - Après validation

**Objectif:** Réduire la longueur des URLs avec beaucoup de filtres

**Approche:**

```php
// Encoder les filtres en base64
$filters = array('compte1' => '512', 'year' => '2024');
$encoded = base64_encode(json_encode($filters));

// URL courte
/compta/page?section=1&f=eyJjb21wdGUxIjoiNTEyIiwieWVhciI6IjIwMjQifQ==
```

**Actions:**
- [ ] Créer fonction `encode_filters()`
- [ ] Créer fonction `decode_filters()`
- [ ] Adapter `context_url()` pour utiliser encoding si >X paramètres
- [ ] Tester

**Note:** À implémenter seulement si URLs trop longues deviennent un problème.

### ⏳ TÂCHE 7.2 : Preset de filtres (optionnel)
**Durée:** 2h
**Statut:** Optionnel - Future feature

**Objectif:** Permettre de sauvegarder des configurations de filtres

**Hors scope de cette implémentation** - Peut être un PRD séparé plus tard.

---

## Métriques de Succès

### Critères d'Acceptation (du PRD)

| ID | Critère | Status |
|----|---------|--------|
| AC-001 | Filtres stockés dans URL | ⏳ |
| AC-002 | Section stockée dans URL | ⏳ |
| AC-003 | Isolation multi-onglets filtres | ⏳ |
| AC-004 | Isolation multi-onglets section | ⏳ |
| AC-005 | Liens conservent contexte | ⏳ |
| AC-006 | Pagination conserve contexte | ⏳ |
| AC-007 | Annuler filtre fonctionne | ⏳ |
| AC-008 | Fallback session fonctionne | ⏳ |
| AC-009 | URLs bookmarkables | ⏳ |
| AC-010 | Back/Forward navigateur | ⏳ |
| AC-REG-001 | Mono-onglet fonctionne | ⏳ |
| AC-REG-002 | Authentification OK | ⏳ |
| AC-REG-003 | Tests PHPUnit 100% | ⏳ |
| AC-REG-004 | CRUD fonctionne | ⏳ |
| AC-PERF-001 | Performance ≤ +50ms | ⏳ |
| AC-SEC-001 | Validation paramètres | ⏳ |

### Jalons

| Jalon | Statut | Date |
|-------|--------|------|
| M1: Infrastructure créée | ⏳ | - |
| M2: Gvv_Controller modifié | ⏳ | - |
| M3: Models adaptés | ⏳ | - |
| M4: Vues migrées | ⏳ | - |
| M5: Tests validés | ⏳ | - |
| M6: Documentation complète | ⏳ | - |

---

## Risques et Mitigations

### Risques Identifiés

| Risque | Probabilité | Impact | Mitigation | Status |
|--------|-------------|--------|------------|--------|
| URLs > 2000 caractères | Faible | Moyen | Avertir si >1500, limiter filtres | ⏳ |
| Casse de tests PHPUnit | Moyen | Élevé | Tests après chaque phase | ⏳ |
| Régression fonctionnelle | Moyen | Élevé | Tests manuels exhaustifs | ⏳ |
| Performance dégradée | Faible | Moyen | Benchmarks avant/après | ⏳ |
| Problèmes d'encodage | Moyen | Moyen | Utiliser urlencode/decode | ⏳ |

---

## Commandes Utiles

```bash
# Environnement
source setenv.sh

# Validation syntaxe
php -l application/helpers/url_helper.php
php -l application/libraries/Gvv_Controller.php

# Tests
./run-all-tests.sh
vendor/bin/phpunit application/tests/unit/helpers/UrlHelperTest.php

# Recherche
grep -r "site_url" application/views/ --include="*View.php" | wc -l
grep -r "pagination->initialize" application/controllers/ --include="*.php"
grep -r "userdata.*section" application/models/ --include="*.php"

# Backup avant modifications
cp -r application/views application/views.backup
cp application/libraries/Gvv_Controller.php application/libraries/Gvv_Controller.php.backup

# Test URL longue
echo "http://gvv.net/compta/page?section=1&filter_active=1&compte1=512&compte2=411&year=2024&date_start=01/01/2024&date_end=31/12/2024" | wc -c
```

---

## Notes d'Implémentation

### Points d'Attention

1. **Encodage des caractères:**
   - Utiliser `urlencode()` pour les valeurs
   - Utiliser `urldecode()` lors de la récupération (Input class le fait automatiquement)

2. **Longueur d'URL:**
   - Monitorer la longueur des URLs générées
   - Ajouter warning si >1500 caractères
   - Limite IE11: 2000 caractères

3. **Compatibilité:**
   - Maintenir fallback vers session
   - Ne pas casser les liens bookmarkés existants
   - Tests sur tous les navigateurs

4. **Performance:**
   - `http_build_query()` est très rapide
   - Pas d'impact SQL (même requêtes)
   - Possibilité de cache URLs

5. **Sécurité:**
   - Valider TOUS les paramètres GET
   - Ne jamais mettre de données sensibles dans URL
   - Maintenir protection CSRF sur POST

### Ordre d'Implémentation Recommandé

1. **Phase 1 d'abord** - Créer l'infrastructure
2. **Tester les helpers** isolément
3. **Phase 2** - Modifier Gvv_Controller
4. **Tester avec un contrôleur** (ex: compta)
5. **Phase 3** - Models
6. **Tester création/modification**
7. **Phase 4** - Vues (par batch de 10-15)
8. **Phase 5** - Tests complets
9. **Phase 6** - Documentation

### Rollback Plan

En cas de problème majeur:

1. Restaurer backups:
```bash
cp application/libraries/Gvv_Controller.php.backup application/libraries/Gvv_Controller.php
cp -r application/views.backup application/views
```

2. Commit de rollback:
```bash
git revert <commit-hash>
git push
```

3. Redéployer version précédente

---

**Plan créé le:** 2025-12-02
**Dernière mise à jour:** 2025-12-03
**Version:** 1.1 - Ajout du concept de contexte fonctionnel
**Prêt pour implémentation:** ✅ Oui
**Estimation:** 4 jours (32 heures)

---

## Note sur le Contexte Fonctionnel (v1.1)

**Innovation principale:** Ajout du paramètre `ctx` pour regrouper les pages fonctionnellement liées.

**Bénéfices:**
- ✅ **Isolation multi-onglets** maintenue (objectif principal)
- ✅ **Partage intra-fonctionnel** : `comptes/page` et `comptes/balance` partagent `ctx=comptes`
- ✅ **Meilleure UX** : Les filtres ne sont pas perdus lors de la navigation entre pages liées
- ✅ **URLs optimisées** : Le paramètre `ctx` facilite de futures optimisations

**Implémentation:**
1. Chaque contrôleur définit `protected $filter_context = 'nom_contexte'`
2. La méthode `get_filter_context()` retourne le contexte (défaut: nom du contrôleur)
3. Le helper `context_url()` inclut automatiquement le paramètre `ctx` dans les URLs
4. Les filtres sont partagés entre toutes les pages ayant le même `ctx`

**Mapping par défaut:**
- `comptes` → `ctx=comptes` (partagé entre page/balance)
- `compta` → `ctx=compta` (partagé entre vues journal)
- `avion`, `planeur`, `membre`, `event` → `ctx=<nom_contrôleur>`

**Contrainte technique rappelée:**
L'architecture HTTP ne permet pas au serveur de distinguer les onglets (tous partagent la session via le même cookie PHPSESSID). L'utilisation de paramètres URL est donc obligatoire.
