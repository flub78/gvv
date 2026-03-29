# Code Review : application/views/bs_menu.php

**Date** : 2026-03-18
**Fichier** : `application/views/bs_menu.php`
**Contexte** : Revue suite à la correction du faux positif « section active perdue » lors de la sélection de « Toutes ».

---

## Problèmes identifiés

### [MEDIUM] Code mort : `form_hidden` orphelins (lignes 432-436)

```php
if (strlen($gvv_user) > 1) {
    echo form_hidden('gvv_user', $gvv_user);
    echo form_hidden('gvv_role', $gvv_role);
}
```

Ces inputs cachés sont émis **en dehors de tout `<form>`**. Le `form_open` correspondant est commenté à la ligne 419 :
```php
// echo form_open(controller_url("auth/logout")) . "\n";
```
Les valeurs `gvv_user` et `gvv_role` ne sont donc jamais soumises avec aucun formulaire. C'est du code mort, potentiellement exposant inutilement des données utilisateur dans le DOM.

**Mitigation** : supprimer les lignes 432-436 et le bloc `if` encapsulant.

---

### [LOW] `console.log` de débogage en production (lignes 466, 472)

```javascript
console.log("Updating section to:", value);
console.log("Current page:", currentPage);
// ...
console.log("Section changed:", response);
```

Ces traces polluent la console navigateur en production et exposent des informations internes (URL courante, valeur de section).

**Mitigation** : supprimer les trois appels `console.log`.

---

### [LOW] Double affectation de `$CI` (ligne 417)

```php
$CI = &get_instance();  // ligne 35 — première affectation
// ...
$CI = &get_instance();  // ligne 417 — redondant
```

La variable est déjà définie à la ligne 35. La seconde affectation est inutile.

**Mitigation** : supprimer la ligne 417.

---

### [LOW] Conflit de positionnement CSS (ligne 60)

```html
<nav class="navbar ... fixed-top" style="position: sticky;">
```

La classe Bootstrap `fixed-top` applique `position: fixed`, tandis que l'attribut inline `position: sticky` est contradictoire. Le comportement réel dépend de la priorité de résolution CSS (l'inline l'emporte), rendant `fixed-top` partiellement inefficace.

**Mitigation** : choisir une seule stratégie de positionnement et supprimer l'autre.

---

### [LOW] Nom de variable avec préfixe `$_` (ligne 41)

```php
$_uses_new_auth = $CI->session->userdata('use_new_auth') || ...;
```

Le préfixe `$_` est conventionnellement réservé aux superglobales PHP (`$_GET`, `$_POST`, etc.). Son usage pour une variable locale prête à confusion.

**Mitigation** : renommer en `$uses_new_auth`.

---

### [INFO] Incohérence du comptage de sections entre les deux systèmes d'auth (lignes 43-49)

```php
if ($_uses_new_auth && $CI->dx_auth->is_logged_in()) {
    $section_selector = $CI->sections_model->selector_for_user($user_id);
    $section_count = count($section_selector);          // compte les items du sélecteur
} else {
    $section_selector = $CI->sections_model->selector_with_all();
    $section_count = $CI->sections_model->safe_count_all();  // compte les lignes en DB
}
```

Pour le nouveau système d'auth, `$section_count` inclut l'entrée « Toutes » dans le décompte (si présente). Pour l'ancien, il ne compte que les sections réelles. Cela peut décaler d'une unité le seuil `$section_count > 1` selon le contexte.

**Mitigation** : aligner les deux branches pour qu'elles comptent la même chose (sections réelles), en séparant le décompte réel du nombre d'items dans le sélecteur.

---

### [INFO] Non-échappement de `$gvv_display_name` (ligne 443)

```php
<?= $gvv_display_name ?>
```

La valeur est construite depuis `mprenom` et `mnom` (DB), sans `htmlspecialchars()`. Risque XSS faible car ces champs sont saisis par des utilisateurs authentifiés à droits élevés.

**Mitigation** : appliquer `htmlspecialchars($gvv_display_name, ENT_QUOTES, 'UTF-8')` par rigueur défensive.

---

### [INFO] URL externe codée en dur dans le menu Dev (ligne 405)

```php
<li><a class="dropdown-item" href="https://legacy.datatables.net/api.html">
```

URL externe figée dans un menu visible uniquement des utilisateurs `dev_users`. Impact limité.

---

## Correction récente validée

**Lignes 52-55** — Détection de session perdue vs sélection de « Toutes » :

```php
// Avant
if (is_logged_in() && $section_count > 1 && empty($section)) {

// Après (correct)
$raw_section = $CI->session->userdata('section');
if (is_logged_in() && $section_count > 1 && empty($raw_section)) {
```

La correction distingue correctement :
- **Session perdue** : `userdata('section')` retourne `false` → `empty()` = TRUE → warning affiché ✓
- **« Toutes » sélectionné** : `userdata('section')` retourne un entier > 0 → `empty()` = FALSE → pas de warning ✓

---

## Todo — par criticité décroissante

- [x] **[MEDIUM]** Supprimer les `form_hidden` orphelins (lignes 432-436) et le `if` encapsulant
- [x] **[LOW]** Supprimer les `console.log` dans `updateSection()` (lignes 466, 472, 473)
- [x] **[LOW]** Supprimer la double affectation `$CI = &get_instance()` (ligne 417)
- [x] **[LOW]** Résoudre le conflit `fixed-top` / `position: sticky` (ligne 60) → remplacé par `sticky-top`
- [x] **[LOW]** Renommer `$_uses_new_auth` en `$uses_new_auth` (ligne 41)
- [ ] **[INFO]** Aligner le calcul de `$section_count` entre les deux systèmes d'auth
- [ ] **[INFO]** Échapper `$gvv_display_name` avec `htmlspecialchars()`
