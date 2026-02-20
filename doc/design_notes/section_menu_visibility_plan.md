# Plan d'implémentation — Visibilité des menus de vol par section

## Objectif

Remplacer la condition hardcodée `$section['id'] == '1'` dans `bs_menu.php` par des
propriétés déclaratives sur la table `sections` :

- `gestion_planeurs` et `gestion_avions` : booléens contrôlant la visibilité des
  sous-menus de vol
- `libelle_menu_avions` : libellé personnalisable du sous-menu avion (ULM, Autogire,
  Avion…) — si vide, repli sur la traduction par défaut

Chaque section déclare ainsi explicitement quels sous-menus elle active et sous quel nom.

**Comportement cible :**

| Section        | Planeurs | Avions/ULM           | Libellé menu avions        |
|----------------|:--------:|:--------------------:|----------------------------|
| (aucune)       | ❌       | ❌                   | —                          |
| Planeur (id=1) | ✅       | ✅                   | *(défaut)* Avions/ULM      |
| ULM (id=2)     | ❌       | ✅                   | ULM                        |
| Avion (id=3)   | ❌       | ✅                   | *(défaut)* Avions/ULM      |
| Général (id=4) | ❌       | ❌                   | —                          |

---

## Tâche 1 — Migration base de données

**Fichier à créer :** `application/migrations/072_section_menu_flags.php`

Ajouter trois colonnes à la table `sections`. Aucune initialisation de données :
après migration, tous les menus de vol sont masqués par défaut ; l'administrateur
configure chaque section via l'interface.

```sql
-- up()
ALTER TABLE sections
  ADD COLUMN gestion_planeurs    TINYINT(1)   NOT NULL DEFAULT 0    AFTER ordre_affichage,
  ADD COLUMN gestion_avions      TINYINT(1)   NOT NULL DEFAULT 0    AFTER gestion_planeurs,
  ADD COLUMN libelle_menu_avions VARCHAR(64)  NULL     DEFAULT NULL  AFTER gestion_avions;

-- down()
ALTER TABLE sections
  DROP COLUMN libelle_menu_avions,
  DROP COLUMN gestion_avions,
  DROP COLUMN gestion_planeurs;
```

> **Note de déploiement :** après migration, aucun sous-menu de vol n'est visible.
> L'administrateur doit ouvrir chaque section dans Admin → Sections et cocher
> `gestion_planeurs` / `gestion_avions` selon l'activité de la section,
> et renseigner `libelle_menu_avions` si le libellé par défaut "Avions/ULM" ne convient pas.

**Fichier à modifier :** `application/config/migration.php`

```php
$config['migration_version'] = 72;
```

- [x] Créer `application/migrations/072_section_menu_flags.php`
- [x] Mettre à jour `application/config/migration.php` → version 72

---

## Tâche 2 — Métadonnées

**Fichier à modifier :** `application/libraries/Gvvmetadata.php`

Dans le bloc de la table `sections` (après `ordre_affichage`), ajouter pour les
deux tables `sections` et `vue_sections` :

```php
// table sections
$this->field['sections']['gestion_planeurs']['Name']    = $CI->lang->line('gvv_sections_field_gestion_planeurs');
$this->field['sections']['gestion_planeurs']['Type']    = 'int';
$this->field['sections']['gestion_planeurs']['Subtype'] = 'boolean';

$this->field['sections']['gestion_avions']['Name']    = $CI->lang->line('gvv_sections_field_gestion_avions');
$this->field['sections']['gestion_avions']['Type']    = 'int';
$this->field['sections']['gestion_avions']['Subtype'] = 'boolean';

$this->field['sections']['libelle_menu_avions']['Name']  = $CI->lang->line('gvv_sections_field_libelle_menu_avions');
// type varchar par défaut, pas de Subtype particulier

// vue sections
$this->field['vue_sections']['gestion_planeurs']['Name']    = $CI->lang->line('gvv_sections_field_gestion_planeurs');
$this->field['vue_sections']['gestion_planeurs']['Subtype'] = 'boolean';

$this->field['vue_sections']['gestion_avions']['Name']    = $CI->lang->line('gvv_sections_field_gestion_avions');
$this->field['vue_sections']['gestion_avions']['Subtype'] = 'boolean';

$this->field['vue_sections']['libelle_menu_avions']['Name'] = $CI->lang->line('gvv_sections_field_libelle_menu_avions');
```

- [x] Mettre à jour `application/libraries/Gvvmetadata.php`

---

## Tâche 3 — Fichiers de langue

Ajouter 6 clés dans chacun des trois fichiers de langue (4 pour les booléens,
2 pour le libellé).

**`application/language/french/sections_lang.php`**

```php
$lang['gvv_sections_field_gestion_planeurs']               = 'Gestion planeurs';
$lang['gvv_sections_field_gestion_avions']                 = 'Gestion avions/ULM';
$lang['gvv_sections_field_libelle_menu_avions']            = 'Libellé menu avions';
$lang['gvv_vue_sections_short_field_gestion_planeurs']     = 'Planeurs';
$lang['gvv_vue_sections_short_field_gestion_avions']       = 'Avions/ULM';
$lang['gvv_vue_sections_short_field_libelle_menu_avions']  = 'Menu avions';
```

**`application/language/english/sections_lang.php`**

```php
$lang['gvv_sections_field_gestion_planeurs']               = 'Glider management';
$lang['gvv_sections_field_gestion_avions']                 = 'Aircraft/ULM management';
$lang['gvv_sections_field_libelle_menu_avions']            = 'Aircraft menu label';
$lang['gvv_vue_sections_short_field_gestion_planeurs']     = 'Gliders';
$lang['gvv_vue_sections_short_field_gestion_avions']       = 'Aircraft/ULM';
$lang['gvv_vue_sections_short_field_libelle_menu_avions']  = 'Aircraft menu';
```

**`application/language/dutch/sections_lang.php`**

```php
$lang['gvv_sections_field_gestion_planeurs']               = 'Zweefvliegtuigbeheer';
$lang['gvv_sections_field_gestion_avions']                 = 'Vliegtuig/ULM-beheer';
$lang['gvv_sections_field_libelle_menu_avions']            = 'Menulabel vliegtuig';
$lang['gvv_vue_sections_short_field_gestion_planeurs']     = 'Zweefvliegtuigen';
$lang['gvv_vue_sections_short_field_gestion_avions']       = 'Vliegtuigen/ULM';
$lang['gvv_vue_sections_short_field_libelle_menu_avions']  = 'Menu vliegtuig';
```

- [x] Mettre à jour `application/language/french/sections_lang.php`
- [x] Mettre à jour `application/language/english/sections_lang.php`
- [x] Mettre à jour `application/language/dutch/sections_lang.php`

---

## Tâche 4 — Vue du formulaire sections

**Fichier à modifier :** `application/views/sections/bs_formView.php`

Ajouter les deux nouveaux champs à l'appel `gvvmetadata->form()` (actuellement
lignes 50-56) :

```php
<?= ($this->gvvmetadata->form('sections', array(
    'nom'                  => $nom,
    'description'          => $description,
    'acronyme'             => $acronyme,
    'couleur'              => $couleur,
    'ordre_affichage'      => $ordre_affichage,
    'gestion_planeurs'     => $gestion_planeurs,
    'gestion_avions'       => $gestion_avions,
    'libelle_menu_avions'  => $libelle_menu_avions,
))); ?>
```

Le contrôleur `sections.php` hérite de `Gvv_Controller` qui gère les données
du formulaire automatiquement — aucune modification du contrôleur n'est nécessaire.

- [x] Mettre à jour `application/views/sections/bs_formView.php`

---

## Tâche 5 — Menu

**Fichier à modifier :** `application/views/bs_menu.php`

### Sous-menu Planeurs (ligne ~194)

**Avant :**
```php
<?php if (empty($section) || ($section && ($section['id'] == '1'))) : ?>
```

**Après :**
```php
<?php if ($section && !empty($section['gestion_planeurs'])) : ?>
```

### Sous-menu Avions/ULM (ligne ~231)

Le libellé du sous-menu est déterminé par `libelle_menu_avions` : si renseigné,
il remplace la traduction par défaut `gvv_menu_airplane`.

**Avant** — aucune condition, libellé figé :
```php
<li class="nav-item dropdown">
  <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
    <?= translation("gvv_menu_airplane") ?>
  </a>
  <ul class="dropdown-menu">
    ...
  </ul>
</li>
```

**Après** — condition + libellé dynamique :
```php
<?php if ($section && !empty($section['gestion_avions'])) : ?>
<?php $menu_avions_label = !empty($section['libelle_menu_avions'])
    ? htmlspecialchars($section['libelle_menu_avions'])
    : translation("gvv_menu_airplane"); ?>
<li class="nav-item dropdown">
  <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
    <?= $menu_avions_label ?>
  </a>
  <ul class="dropdown-menu">
    ...
  </ul>
</li>
<?php endif; ?>
```

- [x] Mettre à jour `application/views/bs_menu.php`

---

## Tâche 6 — Tests

### 6a. Test PHPUnit de migration

**Fichier à créer :** `application/tests/mysql/SectionMenuFlagsTest.php`

- Vérifier que les colonnes `gestion_planeurs`, `gestion_avions` et
  `libelle_menu_avions` existent dans `sections`
- Vérifier que toutes les sections existantes ont `gestion_planeurs=0`,
  `gestion_avions=0` et `libelle_menu_avions=NULL` après migration (valeurs par défaut)
- Tester `down()` puis `up()` pour valider la réversibilité

### 6b. Test Playwright smoke

**Fichier à créer :** `playwright/tests/sections_menu_flags.spec.js`

- Login en tant que `testadmin`
- Sélectionner section "Planeur" → vérifier présence menus Planeurs ET Avions/ULM,
  libellé = valeur par défaut
- Sélectionner section "ULM" → vérifier absence Planeurs, présence menu avec libellé "ULM"
- Sélectionner section "Général" → vérifier absence des deux menus
- Vérifier que les champs `gestion_planeurs`, `gestion_avions` et `libelle_menu_avions`
  sont présents dans le formulaire de modification d'une section

- [x] Créer `application/tests/mysql/SectionMenuFlagsTest.php`
- [x] Créer `playwright/tests/sections_menu_flags.spec.js`

---

## Ordre d'exécution

Les tâches 2, 3, 4 sont indépendantes entre elles et peuvent être réalisées
en parallèle après la tâche 1.

```
1. Migration 072 + migration.php   (prérequis pour tout)
   ├── 2. Gvvmetadata.php
   ├── 3. sections_lang.php ×3
   └── 4. bs_formView.php
5. bs_menu.php                     (après 1)
6. Tests PHPUnit + Playwright      (après tout)
```

---

## Risques

| Risque | Mitigation |
|--------|-----------|
| Menus de vol invisibles après migration | Comportement voulu et documenté — l'administrateur configure chaque section via l'interface ; `DEFAULT 0` évite toute dépendance aux données existantes |
| Nouvelle section sans flags configurés | Aucun menu visible — comportement sûr et cohérent avec le défaut de la migration |
| `$section['gestion_planeurs']` absent si migration non jouée | `!empty()` sur une clé inexistante retourne `false` sans erreur PHP — comportement dégradé sûr (menus cachés) |
| `libelle_menu_avions` contenant du HTML | `htmlspecialchars()` dans le menu empêche toute injection XSS |
| `libelle_menu_avions` vide mais non NULL | La condition `!empty()` traite `""` et `NULL` de façon identique — repli sur la traduction par défaut dans les deux cas |

---

## Corrections découvertes lors des tests

### `MetaData.php` — checkbox non cochée rejette la validation

**Problème :** `MetaData::rules()` génère `required|integer` pour les colonnes `NOT NULL INT`.
Un checkbox non coché n'est pas inclus dans les données POST, ce qui fait échouer la règle
`required` et empêche la sauvegarde.

**Correction (`application/libraries/MetaData.php`) :** ajout d'un champ caché avant chaque
checkbox dans `input_field()` :
```php
return form_hidden($field, 0) . form_checkbox($checkbox_attrs);
```
PHP conserve la dernière valeur pour les clés dupliquées : `0` quand non coché, `1` quand coché.

### Tests Playwright — interférence entre tests parallèles

**Problème :** `fullyParallel: true` fait tourner tous les tests en parallèle. Les tests du groupe
"Navigation menu visibility" modifient tous la section 1 dans la base partagée, créant des
conflits de données.

**Correction (`playwright/tests/sections_menu_flags.spec.js`) :** ajout de
`test.describe.configure({ mode: 'serial' })` dans le groupe pour forcer l'exécution séquentielle.

### Tests Playwright — `selectSection` — race condition AJAX

**Problème :** Le déclenchement de l'événement `change` sur le sélecteur de section lançait
une requête AJAX + `window.location.href`, avec un risque de navigation avortée.

**Correction :** `selectSection` passe directement par `fetch()` vers l'endpoint `set_section`,
puis navigue explicitement vers `/index.php/welcome` avec `page.goto()`.
