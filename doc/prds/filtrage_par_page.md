# PRD : Gestion des Filtres et Section Active par Contexte Fonctionnel

**Date:** 2025-12-02
**Version:** 1.1
**Statut:** Approuvé pour implémentation
**Priorité:** Moyenne
**Effort estimé:** Élevé (2-3 jours)

---

## 1. CONTEXTE

### 1.1 Situation Actuelle

GVV stocke les variables de filtrage et la section active dans la **session PHP globale**. Cette approche pose problème lorsque l'utilisateur ouvre plusieurs onglets ou fenêtres du navigateur.

**Architecture actuelle:**

```php
// application/libraries/Gvv_Controller.php

// Stockage des filtres en session (lignes 777-798)
function active_filter($filter_variables) {
    foreach ($filter_variables as $field) {
        $session[$field] = $this->input->post($field);
    }
    $this->session->set_userdata($session);  // ← SESSION GLOBALE
}

// Chargement des filtres depuis la session (lignes 806-811)
function load_filter($filter_variables) {
    foreach ($filter_variables as $field) {
        $this->data[$field] = $this->session->userdata($field);  // ← SESSION GLOBALE
    }
}
```

**Section active stockée en session:**

```php
// application/models/common_model.php (ligne 30)
$this->section_id = $this->session->userdata('section');  // ← SESSION GLOBALE
```

**Variables stockées en session:**
- **Filtres par contrôleur:** Variables définies dans `$filter_variables` (dates, comptes, pilotes, etc.)
- **Section active:** `section` - Identifiant de la section/club actif
- **Année active:** `year` - Année de consultation
- **Per page:** `per_page` - Nombre d'éléments par page
- **Autres:** `licence_type`, `balance_date`, etc.

### 1.2 Problème Identifié

**Vulnérabilité : Conflits multi-onglets**

Lorsque l'utilisateur ouvre plusieurs onglets/fenêtres et modifie les filtres ou la section dans l'un d'eux :

**Scénario 1 : Conflit de filtres**
1. **Onglet A** : Utilisateur consulte `/compta/page` avec filtre `compte1 = 512`
2. **Onglet B** : Utilisateur ouvre `/compta/page` et change le filtre à `compte1 = 411`
3. **Onglet A** : L'utilisateur rafraîchit ou navigue → **Le filtre est maintenant `compte1 = 411`** ⚠️
4. **Résultat** : L'utilisateur voit des données différentes sans avoir modifié le filtre dans cet onglet

**Scénario 2 : Conflit de section**
1. **Onglet A** : Utilisateur travaille sur Section "Avions" (section_id = 1)
2. **Onglet B** : Utilisateur bascule sur Section "Planeurs" (section_id = 2)
3. **Onglet A** : L'utilisateur crée un vol → **Le vol est créé dans Section "Planeurs"** ⚠️
4. **Résultat** : Données créées/modifiées dans la mauvaise section

**Scénario 3 : Workflow de comparaison**
1. **Onglet A** : Comptable consulte journal compte 512 (Banque) avec filtre année 2024
2. **Onglet B** : Comptable ouvre journal compte 411 (Clients) avec filtre année 2025
3. **Onglet A** : Rafraîchit pour voir les nouvelles écritures → **Affiche maintenant compte 411, année 2025** ⚠️
4. **Résultat** : Impossible de comparer deux vues simultanément

### 1.3 Impact

| Aspect | Niveau | Description |
|--------|--------|-------------|
| **Intégrité données** | Élevé | Création de données dans la mauvaise section |
| **Confusion utilisateur** | Élevé | Changements non intentionnels de contexte |
| **Productivité** | Moyen | Impossibilité de comparer plusieurs vues |
| **Fréquence** | Moyen | Affecte utilisateurs multi-onglets (comptables, administrateurs) |
| **Détection** | Difficile | Utilisateur peut ne pas réaliser le changement de contexte |

**Utilisateurs concernés:**
- **Comptables** : Comparaison de comptes, années, périodes
- **Gestionnaires** : Consultation simultanée de plusieurs sections
- **Administrateurs** : Travail sur plusieurs contextes en parallèle
- **Power users** : Utilisation intensive de l'application

### 1.4 Contrainte Technique : Architecture HTTP

**Question fondamentale :** Pourquoi ne pas stocker les filtres dans la session, indexés par URL ?

**Réponse :** Le serveur HTTP **ne peut pas distinguer les onglets du navigateur**.

#### Architecture HTTP et Sessions

Lorsqu'un serveur reçoit une requête HTTP :

```
GET /compta/page HTTP/1.1
Host: gvv.net
Cookie: PHPSESSID=abc123xyz789
```

**Ce que le serveur reçoit :**
- ✅ Cookie de session (PHPSESSID)
- ✅ URL demandée
- ✅ Headers HTTP standards

**Ce que le serveur NE reçoit PAS :**
- ❌ Identifiant de l'onglet/fenêtre
- ❌ Nombre d'onglets ouverts
- ❌ Historique de navigation de cet onglet spécifique

#### Pourquoi une solution basée session ne fonctionne pas

**Tentative : Session indexée par URL**
```php
$_SESSION['page_contexts'] = [
    'compta/page' => ['compte1' => '512'],  // Onglet A ou B ? Impossible de savoir !
];
```

**Problème :**
1. Onglet A ouvre `/compta/page`, définit `compte1=512`
2. Serveur écrit: `$_SESSION['compta/page']['compte1'] = 512`
3. Onglet B ouvre `/compta/page`, définit `compte1=411`
4. Serveur écrit: `$_SESSION['compta/page']['compte1'] = 411` ← **ÉCRASE la valeur de l'onglet A**
5. Onglet A rafraîchit → Lit `$_SESSION['compta/page']['compte1']` = **411** ❌

**Tous les onglets du même navigateur partagent la même session** car ils envoient le même cookie `PHPSESSID`.

#### Conclusion

**Sans identifiant unique dans la requête HTTP (URL ou POST), l'isolation multi-onglets est impossible.**

Ceci impose l'utilisation de **paramètres URL** pour stocker le contexte de filtrage.

### 1.5 Problématique des Pages Fonctionnellement Liées

**Observation :** Certaines pages sont fonctionnellement liées et devraient partager les mêmes filtres.

**Exemple - Contrôleur Comptes :**
- `comptes/page` → Journal des écritures comptables
- `comptes/balance` → Balance des comptes

**Attente utilisateur :** Si je filtre le journal par "filter_solde=1" (comptes avec solde uniquement), je m'attends à voir la même sélection dans la balance.

**Problème avec approche naïve (un contexte par URL) :**
```
/comptes/page?section=1&filter_solde=1
/comptes/balance?section=1                ← Filtre perdu !
```

**Solution :** Regrouper par **contexte fonctionnel** plutôt que par URL exacte.

---

## 2. OBJECTIFS

### 2.1 Objectif Principal

**Isoler les variables de contexte (filtres, section active) par contexte fonctionnel** pour permettre l'utilisation simultanée de plusieurs onglets sans conflit.

### 2.2 Objectifs Spécifiques

1. **Isolation multi-onglets** : Chaque onglet/fenêtre conserve son propre contexte de filtrage et de section
2. **Partage intra-fonctionnel** : Pages fonctionnellement liées partagent les mêmes filtres (ex: comptes/page et comptes/balance)
3. **Persistance** : Les filtres et la section restent actifs lors de la navigation dans le même onglet
4. **Cohérence** : Le comportement est prévisible et compréhensible pour l'utilisateur
5. **Rétrocompatibilité** : Le comportement mono-onglet reste identique
6. **Performance** : Pas de dégradation perceptible des temps de réponse

### 2.3 Non-Objectifs

- Synchronisation de l'état entre onglets (chaque onglet reste indépendant)
- Modification du comportement des autres variables de session (authentification, rôles, etc.)
- Migration vers une architecture stateless complète (hors scope)
- Support du multi-device (deux navigateurs différents)

---

## 3. SOLUTIONS POSSIBLES

### 3.1 Option A : Paramètres URL avec Contexte Fonctionnel ✅ RECOMMANDÉE

**Principe:** Stocker les filtres et la section dans l'URL sous forme de paramètres GET, avec regroupement par contexte fonctionnel.

**Innovation:** Utiliser un identifiant de **contexte fonctionnel** (`ctx`) pour partager les filtres entre pages liées.

**Architecture:**
```
Avant: /compta/page
Après:  /comptes/page?ctx=comptes&section=1&filter_solde=1
        /comptes/balance?ctx=comptes&section=1&filter_solde=1

Note: Les deux pages partagent ctx=comptes → mêmes filtres !
```

**Contextes fonctionnels identifiés:**

| Contexte | Contrôleur | Pages concernées | Filtres partagés |
|----------|------------|------------------|------------------|
| `comptes` | `comptes` | `page`, `balance` | `filter_solde`, `filter_masked` |
| `compta` | `compta` | `page`, journaux | `filter_code1`, `filter_code2`, `filter_date` |
| `avion` | `avion` | `page` | Filtres avion |
| `planeur` | `planeur` | `page` | Filtres planeur |
| `membre` | `membre` | `page` | Filtres membres |
| `event` | `event` | `page`, `formationView` | Filtres événements |

**Implémentation:**
```php
// Dans chaque contrôleur : définir le contexte fonctionnel
class Comptes extends Gvv_Controller {
    protected $controller = 'comptes';
    protected $filter_context = 'comptes';  // ← Pages liées partagent ce contexte
    protected $filter_variables = array('filter_solde', 'filter_masked');
}

// Stocker les filtres dans l'URL avec contexte
function active_filter($filter_variables) {
    $params = array();
    $params['ctx'] = $this->get_filter_context();  // Contexte fonctionnel

    foreach ($filter_variables as $field) {
        $params[$field] = $this->input->post($field);
    }
    $params['section'] = $this->session->userdata('section');

    // Redirect vers URL avec paramètres
    $url = $this->controller . '/page?' . http_build_query($params);
    redirect($url);
}

// Helper pour obtenir le contexte
protected function get_filter_context() {
    // Par défaut: nom du contrôleur
    return isset($this->filter_context) ? $this->filter_context : $this->controller;
}

// Charger les filtres depuis l'URL
function load_filter($filter_variables) {
    foreach ($filter_variables as $field) {
        // Priorité: URL > Session > Default
        $this->data[$field] = $this->input->get($field)
                            ?: $this->session->userdata($field)
                            ?: '';
    }
}
```

**Exemples d'URLs:**
```
# Comptabilité - Contexte partagé
/comptes/page?ctx=comptes&section=1&filter_solde=1&filter_masked=0
/comptes/balance?ctx=comptes&section=1&filter_solde=1&filter_masked=0
→ Mêmes filtres sur les deux pages !

# Multi-onglets - Contextes différents
Onglet A: /comptes/page?ctx=comptes&section=1&filter_solde=1
Onglet B: /comptes/page?ctx=comptes&section=1&filter_solde=0
→ Isolation parfaite entre onglets

# Contrôleurs différents - Contextes différents
/avion/page?ctx=avion&section=1
/planeur/page?ctx=planeur&section=2
→ Pas d'interférence entre types d'aéronefs
```

**✅ Avantages:**
- **Isolation multi-onglets parfaite** : Chaque onglet a son URL unique
- **Partage intra-fonctionnel** : Pages liées partagent les filtres (meilleure UX)
- **Bookmarkable** : L'utilisateur peut sauvegarder l'URL filtrée
- **Shareable** : Possibilité de partager un lien avec filtres
- **Back/Forward** : Historique navigateur fonctionne correctement
- **Simple** : Pas de gestion de tokens ou d'identifiants complexes
- **Standard web** : Pattern éprouvé et bien compris
- **URLs optimisées** : Le paramètre `ctx` permet de raccourcir les URLs futures

**❌ Inconvénients:**
- **URLs longues** : Peut devenir verbeux avec beaucoup de filtres (atténué par `ctx`)
- **Sécurité** : Données visibles dans l'URL (pas de données sensibles dans les filtres GVV)
- **Code changes** : Modifications importantes dans tous les contrôleurs utilisant filtres
- **Migration** : Nécessite adaptation des liens existants

**Effort:** Élevé (2-3 jours)

### 3.2 Option B : Identifiant de Contexte en Session avec Token URL

**Principe:** Générer un identifiant unique par onglet et stocker les variables sous cet identifiant.

**Architecture:**
```
URL: /compta/page?tab=abc123def456
Session:
    contexts[abc123def456] = {
        section: 1,
        compte1: 512,
        year: 2024
    }
```

**Implémentation:**
```php
function active_filter($filter_variables) {
    // Récupérer ou créer tab_id
    $tab_id = $this->input->get('tab') ?: uniqid('tab_', true);

    // Stocker sous le contexte de l'onglet
    $contexts = $this->session->userdata('tab_contexts') ?: array();
    foreach ($filter_variables as $field) {
        $contexts[$tab_id][$field] = $this->input->post($field);
    }
    $this->session->set_userdata('tab_contexts', $contexts);

    // Redirect avec tab_id
    redirect($this->controller . '/page?tab=' . $tab_id);
}
```

**✅ Avantages:**
- **URLs courtes** : Un seul paramètre dans l'URL
- **Données privées** : Filtres restent côté serveur
- **Rétrocompatibilité** : Fallback vers session globale si pas de tab_id

**❌ Inconvénients:**
- **Complexité** : Gestion du lifecycle des contextes
- **Nettoyage** : Nécessite garbage collection des contextes expirés
- **Memory leak** : Risque d'accumulation de contextes en session
- **Pas bookmarkable** : Le tab_id n'est valide que pour la session
- **Debuggage** : Plus difficile à tracer

**Effort:** Élevé (3 jours)

### 3.3 Option C : LocalStorage JavaScript + Synchronisation AJAX

**Principe:** Stocker les filtres côté client (localStorage) avec un identifiant d'onglet.

**❌ Rejetée** - Raisons:
- Nécessite refactoring JavaScript important
- Incompatible avec l'architecture server-side actuelle
- Problèmes de synchronisation client-serveur
- Pas de support pour utilisateurs avec JS désactivé
- Complexité disproportionnée pour le bénéfice

### 3.4 Option D : Ne Rien Faire (Statut Quo)

**Principe:** Documenter le comportement et former les utilisateurs.

**❌ Rejetée** - Raisons:
- Le problème d'intégrité (création dans mauvaise section) est trop critique
- Impact négatif sur productivité des power users
- Solution de contournement (n'utiliser qu'un seul onglet) non satisfaisante

---

## 4. SOLUTION RETENUE : OPTION A (Paramètres URL avec Contexte Fonctionnel)

### 4.1 Justification

**Option A retenue car:**
- ✅ Isolation multi-onglets parfaite et prévisible
- ✅ Partage intra-fonctionnel (meilleure UX que approche naïve)
- ✅ Pattern web standard et éprouvé
- ✅ Bookmarkable (fonctionnalité bonus)
- ✅ Facilite le debuggage (état visible dans URL)
- ✅ Pas de risque de memory leak
- ✅ Impossible à faire autrement (contrainte HTTP - voir section 1.4)
- ⚠️ Complexité acceptable pour le bénéfice obtenu

**Innovation : Contexte fonctionnel (`ctx`)**

Amélioration par rapport à une implémentation naïve :
- Pages liées (`comptes/page` et `comptes/balance`) partagent le même `ctx=comptes`
- Évite de perdre les filtres lors de la navigation entre pages fonctionnellement liées
- Meilleure expérience utilisateur tout en conservant l'isolation multi-onglets

**Compromis acceptés:**
- URLs plus longues (acceptable pour une application métier, atténué par `ctx`)
- Effort de migration important (investissement justifié)
- Modifications dans de nombreux contrôleurs (centralisable dans `Gvv_Controller`)

### 4.2 Architecture Cible

**Principe de fonctionnement:**

1. **Contexte fonctionnel:**
   - Chaque contrôleur définit `$filter_context` (défaut: nom du contrôleur)
   - Pages du même contexte partagent les filtres
   - Le paramètre `ctx` identifie le contexte dans l'URL

2. **Filtrage:**
   - Utilisateur soumet le formulaire de filtre
   - Contrôleur redirige vers URL avec paramètres GET incluant `ctx`
   - Tous les liens de navigation conservent les paramètres
   - Navigation entre pages du même contexte conserve les filtres

3. **Section active:**
   - Section incluse dans les paramètres URL
   - Changement de section = redirect vers nouvelle URL avec nouvelle section
   - Chaque onglet peut avoir sa propre section active

3. **Fallback:**
   - Si aucun paramètre dans URL → utiliser session (rétrocompatibilité)
   - Si paramètre dans URL → priorité sur session
   - Mettre à jour session avec valeurs URL (pour liens legacy)

**Exemples d'URLs:**

```
# Contexte comptes - Filtres partagés entre page et balance
/comptes/page?ctx=comptes&section=1&filter_solde=1&filter_masked=0
/comptes/balance?ctx=comptes&section=1&filter_solde=1&filter_masked=0
→ Les deux pages partagent les mêmes filtres via ctx=comptes

# Contexte compta - Filtres de journalisation
/compta/page?ctx=compta&section=1&filter_code1=600&code1_end=700&filter_active=1

# Multi-onglets - Isolation parfaite
Onglet A: /comptes/page?ctx=comptes&section=1&filter_solde=1
Onglet B: /comptes/page?ctx=comptes&section=1&filter_solde=0
→ Chaque onglet conserve son propre état

# Sections différentes dans chaque onglet
Onglet A: /membre/page?ctx=membre&section=1  (Avions)
Onglet B: /membre/page?ctx=membre&section=2  (Planeurs)
→ Isolation parfaite des sections
```

---

## 5. EXIGENCES FONCTIONNELLES

### 5.1 REQ-001 : Contexte Fonctionnel

**Priorité:** Critique

**Description:**
Chaque contrôleur **DOIT** définir un contexte fonctionnel (`filter_context`) pour permettre le partage de filtres entre pages liées.

**Comportement:**

1. **Définition du contexte:**
   ```php
   // Dans chaque contrôleur
   class Comptes extends Gvv_Controller {
       protected $filter_context = 'comptes';  // Pages liées partagent ce contexte
   }
   ```

2. **Inclusion dans l'URL:**
   - Le paramètre `ctx` **DOIT** être inclus dans toutes les URLs avec filtres
   - Format: `?ctx=<context>&section=<id>&...`

3. **Contextes par défaut:**
   - Si `filter_context` non défini → utiliser le nom du contrôleur
   - Exemple: contrôleur `avion` → `ctx=avion` par défaut

**Mapping contextes/contrôleurs:**

| Contrôleur | `filter_context` | Pages concernées | Justification |
|------------|------------------|------------------|---------------|
| `comptes` | `comptes` | `page`, `balance` | Même ensemble de comptes |
| `compta` | `compta` | `page`, journaux | Même journal comptable |
| `avion` | `avion` | `page` | Liste des avions |
| `planeur` | `planeur` | `page` | Liste des planeurs |
| `membre` | `membre` | `page` | Liste des membres |
| `event` | `event` | `page`, `formationView` | Gestion événements |

### 5.2 REQ-002 : Filtres dans URL

**Priorité:** Critique

**Description:**
Les variables de filtrage **DOIVENT** être stockées dans l'URL sous forme de paramètres GET au lieu de la session.

**Comportement:**

1. **Activation des filtres:**
   ```php
   POST /comptes/filterValidation
   Body: filter_solde=1&button=Filtrer

   → Redirect 302

   GET /comptes/page?ctx=comptes&section=1&filter_solde=1&filter_active=1
   ```

2. **Navigation avec filtres:**
   - Tous les liens de pagination conservent les paramètres
   - Tous les liens "Modifier", "Créer", "Retour" conservent les paramètres
   - Bouton "Annuler filtre" redirige vers URL sans paramètres de filtre

3. **Chargement des filtres:**
   ```php
   // Priorité: URL > Session > Default
   $compte1 = $this->input->get('compte1')           // Priorité 1: URL
           ?: $this->session->userdata('compte1')    // Priorité 2: Session (fallback)
           ?: '';                                    // Priorité 3: Vide
   ```

**Variables concernées:**
- Variables définies dans `$filter_variables` de chaque contrôleur
- Exemples: `filter_solde`, `filter_masked`, `pilote`, `avion`, `date_start`, `date_end`, `membre_id`, etc.
- **NE PAS inclure:** Variables de formulaire de création/modification

### 5.3 REQ-003 : Section Active dans URL

**Priorité:** Critique

**Description:**
La section active **DOIT** être incluse dans l'URL pour permettre à chaque onglet d'avoir sa propre section.

**Comportement:**

1. **Inclusion automatique:**
   - La section active est automatiquement ajoutée aux paramètres URL
   - Format: `?section=<id>&...`

2. **Changement de section:**
   ```php
   // Utilisateur change de section via menu
   POST /welcome/change_section
   Body: section=2

   → Redirect 302

   GET /welcome?section=2
   ```

3. **Chargement de la section:**
   ```php
   // Déterminer la section active
   $section_id = $this->input->get('section')           // URL
              ?: $this->session->userdata('section')    // Session (fallback)
              ?: 1;                                     // Default

   // Mettre à jour la session (pour liens legacy)
   $this->session->set_userdata('section', $section_id);
   ```

4. **Propagation:**
   - Tous les liens internes conservent le paramètre `section`
   - Création/modification d'éléments utilise la section de l'URL

**Impact:**
- Chaque onglet peut travailler sur une section différente
- Pas de risque de créer des données dans la mauvaise section

### 5.4 REQ-004 : Conservation des Paramètres dans les Liens

**Priorité:** Élevée

**Description:**
Tous les liens générés par l'application **DOIVENT** conserver les paramètres de contexte (filtres, section).

**Implémentation:**

1. **Helper de génération de liens:**
   ```php
   // application/helpers/url_helper.php
   function context_url($uri, $additional_params = array()) {
       $CI =& get_instance();

       // Récupérer paramètres de contexte actuels
       $context = array();
       if ($section = $CI->input->get('section')) {
           $context['section'] = $section;
       }
       if ($filter_active = $CI->input->get('filter_active')) {
           $context['filter_active'] = $filter_active;
           // Ajouter tous les paramètres de filtre actifs
           foreach ($CI->filter_variables as $var) {
               if ($val = $CI->input->get($var)) {
                   $context[$var] = $val;
               }
           }
       }

       // Merger avec paramètres additionnels
       $params = array_merge($context, $additional_params);

       return site_url($uri) . '?' . http_build_query($params);
   }
   ```

2. **Utilisation:**
   ```php
   // Dans les vues
   <a href="<?= context_url('compta/edit/' . $id) ?>">Modifier</a>
   <a href="<?= context_url('compta/create') ?>">Créer</a>

   // Dans les contrôleurs
   redirect(context_url('compta/page'));
   ```

3. **Pagination:**
   - Les liens de pagination générés par CodeIgniter doivent inclure les paramètres
   - Utiliser `$config['suffix']` ou `$config['reuse_query_string'] = TRUE`

**Exceptions:**
- Liens vers pages d'authentification (login/logout)
- Liens vers pages d'administration globale
- Liens de navigation principale (mais inclure section)

### 5.5 REQ-005 : Bouton "Annuler Filtre"

**Priorité:** Moyenne

**Description:**
Le bouton "Annuler" ou "Réinitialiser" les filtres **DOIT** rediriger vers l'URL sans paramètres de filtre.

**Comportement:**

```php
// Dans filterValidation()
if ($button == "Annuler" || $button == $this->lang->line("gvv_button_reset_filter")) {
    // Garder seulement la section
    $params = array();
    if ($section = $this->input->get('section')) {
        $params['section'] = $section;
    }

    $url = $this->controller . '/page';
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    redirect($url);
}
```

**Résultat:**
- Filtres supprimés de l'URL
- Section préservée
- Variables de filtre retirées de la session également

### 5.6 REQ-006 : Rétrocompatibilité Session

**Priorité:** Élevée

**Description:**
L'application **DOIT** rester compatible avec les liens existants sans paramètres URL.

**Fallback:**

```php
// Fonction générique de chargement de contexte
function load_context_variable($name, $default = null) {
    // Priorité 1: URL
    $value = $this->input->get($name);

    if ($value === null || $value === '') {
        // Priorité 2: Session
        $value = $this->session->userdata($name);
    }

    if ($value === null || $value === '') {
        // Priorité 3: Default
        $value = $default;
    }

    // Mettre à jour session pour cohérence
    if ($value !== null && $value !== '') {
        $this->session->set_userdata($name, $value);
    }

    return $value;
}
```

**Usage:**
```php
// Au lieu de:
$compte1 = $this->session->userdata('compte1');

// Utiliser:
$compte1 = $this->load_context_variable('compte1');
```

### 5.7 REQ-007 : Variables Globales Restent en Session

**Priorité:** Critique

**Description:**
Les variables **NON liées au contexte de page** **DOIVENT** rester en session.

**Variables en session (pas dans URL):**
- `user_id` - Utilisateur connecté
- `username` - Nom d'utilisateur
- `role` - Rôle de l'utilisateur
- `permissions` - Permissions
- `language` - Langue de l'interface
- `per_page` - Nombre d'éléments par page (préférence globale)
- `requested_url` - URL demandée pour redirections
- `return_url_stack` - Pile des URLs de retour
- Flash messages

**Variables dans URL:**
- `section` - Section/club actif
- `filter_active` - Filtres actifs ou non
- Toutes les variables de `$filter_variables`
- `year` - Année de consultation (contexte de filtrage)

---

## 6. EXIGENCES NON FONCTIONNELLES

### 6.1 NFR-001 : Performance

**Priorité:** Élevée

- Pas de dégradation perceptible des temps de réponse (< 50ms overhead)
- Pas d'augmentation du nombre de requêtes SQL
- Les URLs avec paramètres doivent être cachées de la même manière

### 6.2 NFR-002 : Sécurité

**Priorité:** Critique

- **Validation des paramètres URL:** Tous les paramètres GET doivent être validés
- **Pas de données sensibles:** Ne jamais mettre de mots de passe ou tokens dans URL
- **Protection CSRF:** Maintenir la protection CSRF sur les POST
- **Injection SQL:** Les paramètres URL doivent être échappés comme les données POST

```php
// Validation
$section = $this->input->get('section');
if ($section && !is_numeric($section)) {
    show_error('Invalid section parameter', 400);
}
```

### 6.3 NFR-003 : Compatibilité Navigateurs

**Priorité:** Moyenne

- Support de tous les navigateurs modernes (Chrome, Firefox, Safari, Edge)
- Limite de longueur d'URL: max 2000 caractères (standard IE11)
- Encodage correct des paramètres (urlencode)

### 6.4 NFR-004 : Maintenabilité

**Priorité:** Élevée

- Centraliser la logique de contexte dans `Gvv_Controller`
- Créer des helpers réutilisables (`context_url`, `load_context_variable`)
- Documenter le nouveau comportement
- Migration progressive possible (coexistence ancien/nouveau)

---

## 7. CRITÈRES D'ACCEPTATION

### 7.1 Critères Fonctionnels

| ID | Critère | Vérification |
|----|---------|--------------|
| **AC-001** | Filtres stockés dans URL | Vérifier paramètres GET après filtrage |
| **AC-002** | Section stockée dans URL | Vérifier paramètre `section` dans URL |
| **AC-003** | Isolation multi-onglets | Filtres différents dans 2 onglets restent indépendants |
| **AC-004** | Section isolée par onglet | Sections différentes dans 2 onglets restent indépendantes |
| **AC-005** | Liens conservent contexte | Tous les liens incluent paramètres de contexte |
| **AC-006** | Pagination conserve contexte | Les pages suivantes gardent les filtres |
| **AC-007** | Annuler filtre fonctionne | Bouton annuler retire les filtres de l'URL |
| **AC-008** | Fallback session fonctionne | URL sans paramètres utilise session |
| **AC-009** | Bookmarkable | URL copiée/collée conserve les filtres |
| **AC-010** | Back/Forward navigateur | Historique navigateur fonctionne correctement |

### 7.2 Critères de Régression

| ID | Critère | Vérification |
|----|---------|--------------|
| **AC-REG-001** | Mono-onglet fonctionne | Comportement identique avec un seul onglet |
| **AC-REG-002** | Authentification préservée | Login/logout fonctionnent |
| **AC-REG-003** | Tests PHPUnit passent | 100% des tests passent |
| **AC-REG-004** | Création/modification OK | CRUD fonctionne dans contexte correct |

### 7.3 Critères de Performance

| ID | Critère | Vérification |
|----|---------|--------------|
| **AC-PERF-001** | Temps de réponse ≤ +50ms | Chronométrer avant/après |
| **AC-PERF-002** | Pas de requêtes supplémentaires | Vérifier logs SQL |

### 7.4 Critères de Sécurité

| ID | Critère | Vérification |
|----|---------|--------------|
| **AC-SEC-001** | Validation paramètres | Injection tentatives bloquées |
| **AC-SEC-002** | Pas de données sensibles | Audit des URLs |
| **AC-SEC-003** | CSRF protection maintenue | Tests de sécurité |

---

## 8. CAS D'USAGE

### 8.1 CU-001 : Comparaison Comptable Multi-Onglets

**Acteur:** Comptable / Trésorier

**Préconditions:**
- Utilisateur connecté avec rôle "tresorier"
- Multiple comptes comptables dans la base

**Flux nominal:**

1. **Onglet A:** Comptable ouvre `/compta/page`
2. **Onglet A:** Applique filtre `compte1 = 512` (Banque), `year = 2024`
3. **Onglet A:** URL devient `/compta/page?section=1&compte1=512&year=2024&filter_active=1`
4. **Onglet B:** Comptable ouvre nouvel onglet via Ctrl+T
5. **Onglet B:** Navigate vers `/compta/page`
6. **Onglet B:** Applique filtre `compte1 = 411` (Clients), `year = 2025`
7. **Onglet B:** URL devient `/compta/page?section=1&compte1=411&year=2025&filter_active=1`
8. **Onglet A:** Comptable revient sur onglet A et rafraîchit (F5)
9. **Vérification:** Onglet A affiche toujours `compte1 = 512`, `year = 2024` ✅
10. **Onglet B:** Comptable rafraîchit onglet B
11. **Vérification:** Onglet B affiche toujours `compte1 = 411`, `year = 2025` ✅

**Résultat:**
- Comptable peut comparer les deux comptes simultanément
- Pas de confusion, pas de changement de contexte involontaire
- Productivité améliorée

### 8.2 CU-002 : Travail Multi-Section

**Acteur:** Gestionnaire / Administrateur

**Préconditions:**
- Utilisateur avec accès à plusieurs sections
- Sections "Avions" (id=1) et "Planeurs" (id=2) configurées

**Flux nominal:**

1. **Onglet A:** Utilisateur travaille sur section "Avions"
2. **Onglet A:** URL = `/vols_avion/page?section=1`
3. **Onglet B:** Utilisateur ouvre nouvel onglet
4. **Onglet B:** Change section vers "Planeurs" via menu
5. **Onglet B:** URL = `/welcome?section=2` puis navigation vers `/vols_planeur/page?section=2`
6. **Onglet A:** Utilisateur crée un nouveau vol
7. **Vérification:** Vol créé dans section "Avions" (section=1) ✅
8. **Onglet B:** Utilisateur crée un nouveau vol
9. **Vérification:** Vol créé dans section "Planeurs" (section=2) ✅

**Résultat:**
- Pas de risque de créer des données dans la mauvaise section
- Chaque onglet a son propre contexte de section
- Intégrité des données préservée

### 8.3 CU-003 : Bookmark et Partage de Vue Filtrée

**Acteur:** Tout utilisateur

**Préconditions:**
- Vue filtrée configurée

**Flux nominal:**

1. Utilisateur applique filtres complexes: `pilote=15&date_start=01/01/2024&date_end=31/12/2024`
2. URL = `/vols_avion/page?section=1&pilote=15&date_start=01/01/2024&date_end=31/12/2024&filter_active=1`
3. Utilisateur ajoute la page aux favoris du navigateur
4. **Le lendemain:** Utilisateur clique sur le favori
5. **Vérification:** La page s'ouvre avec les mêmes filtres ✅

**Bonus - Partage:**
6. Utilisateur copie l'URL et l'envoie à un collègue
7. Collègue ouvre l'URL
8. **Vérification:** Collègue voit la même vue filtrée ✅

**Résultat:**
- Fonctionnalité bonus très utile pour la collaboration
- Pas d'effort supplémentaire, conséquence naturelle de l'architecture URL

---

## 9. PLAN DE MIGRATION

### 9.1 Phase 1 : Infrastructure (1 jour)

**Objectif:** Créer les helpers et fonctions de base

**Tâches:**
1. Créer helper `context_url()` dans `application/helpers/url_helper.php`
2. Créer méthode `load_context_variable()` dans `Gvv_Controller`
3. Créer méthode `get_context_params()` pour récupérer tous les paramètres de contexte
4. Créer tests unitaires pour les helpers

**Livrables:**
- Helpers fonctionnels et testés
- Documentation des fonctions

### 9.2 Phase 2 : Gvv_Controller (0.5 jour)

**Objectif:** Modifier les fonctions de gestion des filtres dans le contrôleur parent

**Tâches:**
1. Modifier `active_filter()` pour rediriger avec paramètres URL
2. Modifier `load_filter()` pour utiliser `load_context_variable()`
3. Ajouter gestion de la section dans l'URL
4. Assurer le fallback vers session

**Fichiers:**
- `application/libraries/Gvv_Controller.php`

### 9.3 Phase 3 : Common_Model (0.5 jour)

**Objectif:** Adapter la récupération de `section_id` depuis URL

**Tâches:**
1. Modifier `common_model.php` pour utiliser URL > Session
2. Propager le changement aux modèles héritant de `common_model`

**Fichiers:**
- `application/models/common_model.php`
- Autres modèles si nécessaire

### 9.4 Phase 4 : Vues et Liens (1 jour)

**Objectif:** Adapter les vues pour utiliser `context_url()`

**Tâches:**
1. Identifier toutes les vues avec des liens internes
2. Remplacer `site_url()` par `context_url()` où approprié
3. Configurer la pagination pour inclure les paramètres (`$config['reuse_query_string'] = TRUE`)
4. Tester chaque type de lien (pagination, édition, création, retour)

**Fichiers:**
- `application/views/**/*View.php`

### 9.5 Phase 5 : Tests et Validation (0.5 jour)

**Objectif:** Valider le comportement multi-onglets

**Tâches:**
1. Tests manuels multi-onglets (tous les cas d'usage)
2. Tests de régression (mono-onglet)
3. Tests de performance
4. Tests de sécurité (injection paramètres)
5. Exécuter suite PHPUnit

### 9.6 Phase 6 : Documentation (0.5 jour)

**Objectif:** Documenter le nouveau comportement

**Tâches:**
1. Mettre à jour `doc/AI_INSTRUCTIONS.md`
2. Ajouter commentaires dans le code
3. Créer guide utilisateur si nécessaire (optionnel)

---

## 10. CONTRAINTES

### 10.1 Contraintes Techniques

1. **Framework:** CodeIgniter 2.x (legacy) - Limitations sur routing et helpers
2. **PHP:** Version 7.4
3. **Longueur URL:** Maximum 2000 caractères (standard)
4. **Sessions:** Mécanisme de session CodeIgniter existant
5. **Navigateurs:** Pas de dépendance JavaScript (solution server-side)

### 10.2 Contraintes de Déploiement

1. **Migration progressive:** Possible de déployer par phase
2. **Rollback:** Doit être possible si problèmes majeurs
3. **Zero downtime:** Pas d'interruption de service requise
4. **Database:** Aucune migration de base de données nécessaire

### 10.3 Contraintes de Compatibilité

1. **Liens externes:** Liens bookmarkés existants doivent fonctionner (fallback session)
2. **Mobile:** URLs longues doivent être supportées sur mobile
3. **Tests:** Ne doit pas casser les tests PHPUnit existants

---

## 11. RISQUES

### 11.1 Risques Techniques

| Risque | Probabilité | Impact | Mitigation |
|--------|-------------|--------|------------|
| URLs dépassant 2000 caractères | Faible | Moyen | Limiter nombre de filtres, avertir utilisateur |
| Performance dégradée avec URLs longues | Faible | Faible | Tester avec URLs maximales |
| Conflit avec .htaccess / routing | Moyen | Élevé | Tester routing existant, ajuster si nécessaire |
| Problèmes d'encodage (caractères spéciaux) | Moyen | Moyen | Utiliser urlencode/urldecode systématiquement |
| Casse des tests existants | Moyen | Élevé | Tests complets avant merge |

### 11.2 Risques Utilisateur

| Risque | Probabilité | Impact | Mitigation |
|--------|-------------|--------|------------|
| Surprise du changement d'URL | Faible | Faible | URLs restent fonctionnelles |
| Partage involontaire d'URLs sensibles | Très faible | Faible | Pas de données sensibles dans filtres |
| Confusion avec plusieurs onglets | Faible | Faible | Formation si nécessaire |

### 11.3 Risques de Migration

| Risque | Probabilité | Impact | Mitigation |
|--------|-------------|--------|------------|
| Liens cassés dans documentation | Moyen | Faible | Audit de la documentation |
| Scripts externes appelant l'API | Faible | Moyen | Maintenir compatibilité session |
| Régression fonctionnelle | Moyen | Élevé | Tests exhaustifs |

---

## 12. MÉTRIQUES DE SUCCÈS

### 12.1 Métriques Quantitatives

| Métrique | Cible | Mesure |
|----------|-------|--------|
| **Tests PHPUnit réussis** | 100% | `./run-all-tests.sh` |
| **Cas d'usage validés** | 3/3 | Tests manuels multi-onglets |
| **Temps de réponse** | ≤ +50ms | Chrome DevTools Network |
| **Couverture de code** | ≥ 70% | PHPUnit coverage |
| **URLs longues max** | < 2000 chars | Tests avec filtres max |

### 12.2 Métriques Qualitatives

- ✅ Les utilisateurs multi-onglets ne rencontrent plus de conflits
- ✅ Intégrité des données préservée (pas de création dans mauvaise section)
- ✅ Workflow de comparaison fonctionnel
- ✅ Code conforme aux conventions GVV
- ✅ Documentation à jour

---

## 13. PLANNING

### 13.1 Estimation d'Effort

| Phase | Durée | Description |
|-------|-------|-------------|
| **Phase 1** | 1 jour | Infrastructure (helpers, fonctions de base) |
| **Phase 2** | 0.5 jour | Gvv_Controller modifications |
| **Phase 3** | 0.5 jour | Common_Model adaptations |
| **Phase 4** | 1 jour | Vues et liens |
| **Phase 5** | 0.5 jour | Tests et validation |
| **Phase 6** | 0.5 jour | Documentation |
| **TOTAL** | **4 jours** | Estimation totale |

**Note:** Estimation conservatrice. Peut être réduit avec développeur expérimenté GVV.

### 13.2 Jalons

| Jalon | Livrable | Critère de succès |
|-------|----------|-------------------|
| **M1** | Helpers créés | Helpers fonctionnels et testés |
| **M2** | Gvv_Controller modifié | Code compile, filtres en URL |
| **M3** | Section dans URL | Section isolée par onglet |
| **M4** | Vues adaptées | Tous les liens incluent contexte |
| **M5** | Tests passent | 100% tests, cas d'usage validés |
| **M6** | Documentation | Documentation complète |

---

## 14. ALTERNATIVES FUTURES

### 14.1 Court Terme (Après Implémentation)

- **Compression URL:** Encoder les filtres en base64 pour URLs plus courtes
- **Preset de filtres:** Sauvegarder des configurations de filtres nommées
- **UI amélioration:** Indicateur visuel de la section active par onglet

### 14.2 Moyen Terme

- **Session Redis:** Migrer vers Redis pour meilleures performances
- **API REST:** Exposer endpoints RESTful avec filtres en query params
- **Single Page App:** Migration progressive vers architecture SPA

### 14.3 Long Terme

- **Stateless Architecture:** Migration complète vers architecture sans session
- **WebSockets:** Updates en temps réel entre onglets (optionnel)

---

## 15. RÉFÉRENCES

### 15.1 Code Source

- **Gvv_Controller:** `application/libraries/Gvv_Controller.php` (lignes 777-811)
- **Common_Model:** `application/models/common_model.php` (ligne 30)
- **Exemples de contrôleurs:** `compta.php`, `vols_avion.php`, `membre.php`

### 15.2 Documentation

- **CodeIgniter 2 Routing:** https://codeigniter.com/userguide2/general/routing.html
- **URL Helper:** https://codeigniter.com/userguide2/helpers/url_helper.html
- **Input Class:** https://codeigniter.com/userguide2/libraries/input.html

### 15.3 Glossaire

| Terme | Définition |
|-------|------------|
| **Contexte de page** | Ensemble des variables (filtres, section) spécifiques à une vue |
| **Isolation multi-onglets** | Capacité de maintenir des contextes différents dans plusieurs onglets |
| **Query string** | Paramètres GET dans l'URL (ex: `?key=value&key2=value2`) |
| **Fallback session** | Mécanisme de secours utilisant la session si paramètres URL absents |
| **Bookmarkable** | Capacité de sauvegarder une URL et retrouver le même état |

---

**Document créé le:** 2025-12-02
**Auteur:** Claude Code
**Statut:** Proposition - En attente d'approbation
**Prochaine étape:** Revue et approbation par mainteneur
