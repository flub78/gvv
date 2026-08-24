# Plan : Généralisation de la correction responsive des tableaux DataTables larges

**Date:** 2026-08-24
**Statut:** Implémenté et vérifié (tâches 1 à 6 complètes, tests Playwright + suite complète verts)
**Périmètre:** Correction ciblée (pas de PRD séparée, cf. justification en section 1.4)

---

## 1. Contexte et diagnostic

### 1.1 Bug de référence, déjà corrigé

Sur `application/views/compta/bs_journalCompteView.php` (extrait de compte), un tableau DataTables large (10-12 colonnes) posait trois problèmes sur écran étroit/tablette :

1. `<table>` sans wrapper, avec `"bAutoWidth": true`. Sur écran étroit, DataTables calcule et fige en pixels une largeur de `<table>` (style inline), ce qui écrase le CSS `width:100%` et fait déborder toute la page de façon incohérente : le menu et la bannière restaient à la largeur de l'écran, seul le tableau (et le contenu qui l'entoure) débordait. Sur écran large avec peu de données, la même mécanique produisait l'effet inverse : le tableau restait figé à une largeur trop étroite au lieu de remplir l'espace disponible.
2. Le menu (`application/views/bs_menu.php`, `<nav class="navbar sticky-top">`, contient le bouton "Quitter" et le sélecteur de section) et la bannière (`application/views/bs_banner.php`, `<header class="container-fluid">`, décorative) sont des **frères** du conteneur de contenu `#body` — pas des enfants — donc ne suivaient pas sa largeur quand celui-ci devait s'élargir pour accueillir le tableau.
3. Solution retenue et validée (tests Playwright + suite complète) :
   - `"bAutoWidth": false` dans l'init DataTables de la page — le CSS `width:100%` reprend la main, le tableau remplit toujours son conteneur.
   - Callback de rendu du tableau (`fnDrawCallback`) qui mesure la largeur réelle du tableau (`$(this).outerWidth()`) et l'applique en `min-width` sur `#body` (filtre, accordéons) et sur `header.container-fluid` (bannière), pour qu'ils restent alignés avec le tableau qu'il soit plus étroit ou plus large que l'écran.
   - Le menu, lui, **reste volontairement à la largeur de l'écran** (le bouton "Quitter" et le sélecteur de section doivent rester accessibles sans scroll horizontal) mais reçoit un calque décoratif (`position:absolute; z-index:-1; pointer-events:none`), même hauteur/couleur que le menu, étiré à la largeur du contenu, pour éviter un décrochement visuel de couleur au scroll.

Le code de cette solution vit aujourd'hui **uniquement** dans le `<script>` inline de `bs_journalCompteView.php`.

### 1.2 Cause racine partagée avec d'autres pages

La quasi-totalité des tableaux de l'application sont produits par les mêmes deux points d'entrée :

- `$this->gvvmetadata->table(...)` — `application/libraries/MetaData.php:459`
- `$this->datatable->display(...)` — `application/libraries/DataTable.php:96`

Les deux émettent un `<table>` nu, sans wrapper. L'initialisation DataTables se fait ensuite selon 3 patterns :

| Pattern | Où | Classes concernées |
|---|---|---|
| **A. Init globale partagée** | `application/views/bs_footer.php:73-219` | `.datatable`, `.datatable_nopaging`, `.datatable_500`, `.fixed_datatable`, `.searchable_nosort_datatable`, `.balance_searchable_datatable` |
| **B. Init dédiée inline dans la vue** | Script `<script>` propre à la page | `compta/bs_journalView.php` (`.datatable_mini`, `.datatable_mini_serverside`), `membre/bs_tableView.php` (`.table_membre`, `.table_membre_ro`) |
| **C. Init dédiée dans un fichier JS séparé** | `assets/javascript/table_vols_avion.js`, `assets/javascript/table_vols_planeur.js` | `.datedtable`/`.datedtable_ro` (vols_avion), `.datatable_server`/`.datatable_server_ro` (vols_planeur) |

Dans les 3 patterns, `"bAutoWidth": true` est réglé explicitement (ou est la valeur par défaut de DataTables) et rien ne synchronise la largeur du menu/bannière avec celle du contenu. **Toute page qui affiche un tableau suffisamment large pour déborder d'un écran étroit reproduit le bug de référence**, dans une mesure proportionnelle à son nombre de colonnes.

### 1.3 Audit des pages concernées

34 vues utilisant `gvvmetadata->table()`/`datatable->display()` ont été passées en revue (nombre de colonnes réel = champs par défaut + colonne actions le cas échéant, classe DataTables utilisée, présence d'un wrapper, fréquence d'usage réelle). Classement :

**HIGH — 8+ colonnes, page pleine à fort trafic, aucun wrapper :**

| Page | Colonnes | Pattern |
|---|---|---|
| `vols_planeur/bs_tableView.php` | 17 | C — `table_vols_planeur.js` |
| `vols_avion/bs_tableView.php` | 17 | C — `table_vols_avion.js` |
| `compta/bs_journalView.php` | 12-13 | B — inline (Grand Journal, même page que la référence en version "toutes sections") |
| `membre/bs_tableView.php` | 11 | B — inline |
| `planeur/bs_tableView.php` | 12 | A — `.datatable` |
| `avion/bs_tableView.php` | 11 | A — `.datatable` |
| `document_types/bs_tableView.php` | 10 | A — `.datatable` |
| `achats/bs_tableView.php` | 10 | A — `.datatable` |
| `motd/bs_tableView.php` | 9 | A — `.datatable` |

**MEDIUM — 6-8 colonnes ou trafic incertain :**

| Page | Colonnes | Pattern |
|---|---|---|
| `configuration/bs_tableView.php` | 8 | A — `.datatable` |
| `produits/bs_tableView.php` | 8 | A — `.datatable` |
| `pompes/bs_tableView.php` | 8 | **Aucun** — `class="table"` simple, pas de DataTables du tout (sous-cas différent, cf. 4.3) |
| `event/bs_tableView.php` | 7 | A — `.datatable` |
| `event/bs_statsView.php` | Variable (1 colonne/année configurée) | A — `.datatable` |
| `tickets/bs_tableView.php` | 7 | A — `.datatable` |
| `attachments/bs_tableView.php` | 6 | A — `.datatable` |
| `comptes/bs_tableView.php` | 5-6 | A — `.searchable_nosort_datatable` |

**LOW — hors périmètre de ce plan** (tableau étroit, 2-5 colonnes, et/ou usage admin peu fréquent, et/ou déjà en `table-responsive`) : `achats/bs_TablePerYear.php`, `associations_ecriture`, `associations_of`, `associations_releve`, `categorie`, `dates_gel`, `historique`, `licences/bs_tableView.php` (pas initialisé en DataTable), `membre/bs_formView.php`, `membre/licences.php`, `meteo`, `reports`, `sections`, `tarifs`, `terrains`, `tickets/bs_soldes_pilote.php`, `types_ticket`, `user_roles_per_section`. Non traitées ici ; à reconsidérer seulement si un problème concret est signalé dessus.

**Point notable :** grâce au pattern A, corriger une seule fois `bs_footer.php` couvre d'un coup 12 des 17 pages HIGH+MEDIUM (`planeur`, `avion`, `document_types`, `achats`, `motd`, `configuration`, `produits`, `event`, `event/bs_statsView`, `tickets`, `attachments`, `comptes`). Seules 4 pages (patterns B et C) nécessitent un correctif individuel, plus le cas particulier `pompes`.

### 1.4 Pourquoi pas de PRD séparée

Il s'agit de la généralisation d'un correctif de rendu déjà validé sur une page (dette technique / bug transverse), pas d'une nouvelle fonctionnalité produit. Le diagnostic complet est capturé dans ce plan ; aucune exigence produit supplémentaire ne justifie un document séparé.

### 1.5 Documentation utilisateur

Correctif purement visuel/CSS-JS, sans changement de comportement fonctionnel ni de terminologie. Aucune mise à jour de `doc/users/` n'est nécessaire.

---

## 2. Objectif

Généraliser la correction validée sur `bs_journalCompteView.php` à toutes les pages **HIGH** et **MEDIUM** de la section 1.3, **sans dupliquer la logique JS par page** — en centralisant la synchronisation de largeur (tableau ↔ `#body`/bannière ↔ calque de menu) dans une fonction JS partagée, appelée depuis les 3 patterns d'initialisation.

**Non-objectifs :**
- Les pages classées LOW (section 1.3) — hors périmètre, à traiter séparément si besoin avéré.
- Toute refonte plus profonde (masquage de colonnes secondaires sur mobile, vue "carte" alternative, etc.) — une option déjà écartée lors de la correction de référence, cf. échanges antérieurs sur `bs_journalCompteView.php`.
- `pompes/bs_tableView.php` au sens strict de la correction DataTables (elle n'utilise pas DataTables) — traitée à part en tâche 4.3.

---

## 3. Solution retenue

### 3.1 Fonction JS partagée

Extraire la logique de synchronisation (aujourd'hui inline dans `bs_journalCompteView.php`) en une fonction unique, chargée globalement :

- **Fichier nouveau :** `assets/javascript/gvv_wide_table_layout.js`
- **Fonction exposée :** `gvvSyncWideTableLayout(tableEl)` — reçoit l'élément `<table>` DataTables qui vient d'être dessiné, et :
  1. Mesure `$(tableEl).outerWidth()`.
  2. Applique cette largeur en `min-width` sur `#body` et sur `header.container-fluid` (silencieux si absents — certaines pages n'ont pas de bannière).
  3. Crée (une seule fois, idempotent) et redimensionne le calque décoratif derrière `nav.navbar`.
- Chargé une fois via `application/views/bs_header.php` (à côté des autres scripts partagés), disponible sur toutes les pages sans avoir à le déclarer individuellement.

### 3.2 Intégration dans les 3 patterns

- **Pattern A (`bs_footer.php`)** : ajouter `"bAutoWidth": false` et un appel à `gvvSyncWideTableLayout(this)` dans le `fnDrawCallback` de chacun des 6 blocs (`.datatable`, `.datatable_nopaging`, `.datatable_500`, `.searchable_nosort_datatable`, `.balance_searchable_datatable` ont déjà un `fnDrawCallback` partagé `highlightSearchCallback` — on y ajoute l'appel ; `.fixed_datatable` n'en a pas, il faut lui en créer un).
- **Pattern B/C (inline ou fichier JS dédié)** : dans chacun des 4 fichiers concernés (`compta/bs_journalView.php`, `membre/bs_tableView.php`, `table_vols_avion.js`, `table_vols_planeur.js`), régler `"bAutoWidth": false` sur les blocs concernés et ajouter l'appel à `gvvSyncWideTableLayout(this)` dans leur `fnDrawCallback` respectif (déjà présent partout sauf à vérifier au cas par cas).
- **`bs_journalCompteView.php` (page de référence)** : refactorisée pour appeler la fonction partagée au lieu de sa version inline actuelle — garantit que la page déjà validée teste le même code que toutes les autres, et évite la divergence entre deux implémentations du même correctif.

### 3.3 Option écartée : ajouter un wrapper dans `MetaData::table()`/`DataTable::display()`

La correction de référence n'a **pas eu besoin** d'un `<div class="table-responsive">` autour du `<table>` : la synchronisation de largeur de `#body` fonctionne directement sur la largeur mesurée du tableau, wrapper ou pas. Modifier les deux générateurs partagés n'apporte donc rien ici et élargirait inutilement la surface de risque (ils sont utilisés par des dizaines de vues, y compris hors périmètre). Écarté.

---

## 4. Tâches

### ✅ Tâche 1 : Extraire la fonction JS partagée
**Fichiers :** `assets/javascript/gvv_wide_table_layout.js` (nouveau), `application/views/bs_header.php` (ajout du `<script src=...>`), `application/views/compta/bs_journalCompteView.php` (remplacement du code inline par l'appel à la fonction partagée)
Reprend la logique déjà validée (mesure de largeur, sync `#body`/bannière, calque du menu). Page de référence revalidée (écran étroit/large, aucune erreur console) après refactorisation — comportement identique.

**Ajustement découvert en cours d'implémentation :** sur certaines tables (rendu client, sans `bServerSide`), la largeur mesurée au moment du `fnDrawCallback` n'était pas toujours la largeur finale — un second passage de mesure/synchronisation était nécessaire une fois le layout du navigateur stabilisé (écart constaté : ~24-26px, invisible à l'œil mais mesurable). `gvvSyncWideTableLayout()` refait donc une mesure et un réajustement 50ms après le premier passage si la largeur a changé entre-temps. Cf. code commenté dans le fichier.

### ✅ Tâche 2 : Brancher le pattern A (`bs_footer.php`)
**Fichier :** `application/views/bs_footer.php` (les 6 blocs `.dataTable(...)`, lignes ~73-219)
`"bAutoWidth": false` appliqué aux 6 blocs. 5 des 6 (`.datatable`, `.datatable_nopaging`, `.datatable_500`, `.searchable_nosort_datatable`, `.balance_searchable_datatable`) partagent déjà `highlightSearchCallback` comme `fnDrawCallback` (`bs_header.php`) — l'appel à `gvvSyncWideTableLayout(this)` y a été ajouté une seule fois. Le 6ᵉ (`.fixed_datatable`) n'avait aucun `fnDrawCallback` — un a été ajouté. Vérifié sur `planeur/page` et `avion/page` : tableau/filtre/menu cohérents, aucune erreur console.

### ✅ Tâche 3 : Brancher les patterns B et C (4 pages à init dédiée)
**Fichiers :** `application/views/compta/bs_journalView.php`, `application/views/membre/bs_tableView.php`, `assets/javascript/table_vols_avion.js`, `assets/javascript/table_vols_planeur.js`
`membre/bs_tableView.php` et les deux fichiers `table_vols_*.js` utilisaient déjà `highlightSearchCallback` (branché en tâche 2) — juste `bAutoWidth` à passer à `false`. `compta/bs_journalView.php` (init propre, sans `highlightSearchCallback`) a reçu l'appel direct à `gvvSyncWideTableLayout(this)`. Un bloc sans aucun `fnDrawCallback` a été trouvé dans `table_vols_planeur.js` (`.datatable_server_ro`) et complété. Vérifié sur `compta/page`, `membre/page`, `vols_avion/page`, `vols_planeur/page` : largeurs cohérentes, aucune erreur console.

### ✅ Tâche 4 : Cas particulier `pompes/bs_tableView.php`
**Fichier :** `application/views/pompes/bs_tableView.php`
`table-responsive` ajouté autour de l'appel à `gvvmetadata->table()`. Vérifié : plus de débordement de page sur écran étroit.

### ✅ Tâche 5 : Tests Playwright
**Fichier :** `playwright/tests/wide-datatable-responsive.spec.js` (nouveau)
Test paramétré sur 5 pages (au moins un représentant de chaque pattern A/B/C) × 2 breakpoints (375px, 1600px) = 10 cas, vérifiant largeur `#body` vs tableau, accessibilité du bouton "Quitter" sans scroll, et absence d'erreur console (une exception documentée : l'échec de chargement du kit Font Awesome, dépendance CDN externe indisponible dans l'environnement de test, sans rapport avec ce correctif). 10/10 verts. Conservés dans la suite de régression.

**Leçon retenue en écrivant le test :** comparer `#body.offsetWidth` au tableau avec une tolérance stricte (±5px) est correct dans le cas où la page déborde (le `min-width` JS pilote directement `#body`), mais pas dans le cas où le tableau est plus étroit que l'écran : `#body` (container-fluid) a alors son padding horizontal normal (~24px) qui le rend légitimement un peu plus large que le tableau qu'il contient — ce n'est pas un bug. L'assertion retenue vérifie l'invariant réel (`#body` jamais nettement plus étroit que le tableau, avec 30px de tolérance pour ce padding), pas une égalité stricte.

### ✅ Tâche 6 : Vérification manuelle des pages LOW non modifiées
Vérifié sur `sections/page`, `categorie/page`, `dates_gel/page` (écran étroit) : aucune erreur console, aucune régression visuelle. Ces pages gardent leurs limites préexistantes (tableaux un peu plus larges que l'écran, hors périmètre de ce plan) mais bénéficient au passage du même mécanisme de cohérence de largeur, sans effet négatif.

**Effort total réel : conforme à l'estimation (~7h)**

---

## 5. Tests — récapitulatif

| Type | Nécessaire ? | Justification |
|------|--------------|----------------|
| PHPUnit | Non | Aucun code métier/PHP modifié — uniquement JS et une classe CSS (`pompes`) |
| Playwright | Oui (tâche 5) | Seul moyen de vérifier un comportement de layout/JS across breakpoints |
| `./run-all-tests.sh` | Oui, après chaque tâche | Non-régression générale (déjà utilisé pendant la correction de référence) |

---

## 6. Risques

- **Surface large de la tâche 2** : `bs_footer.php` est chargé par la quasi-totalité des pages de l'application (y compris les 17 pages LOW et d'autres non auditées). Un changement mal calibré dans les 6 blocs d'init pourrait régresser des pages hors périmètre. Mitigation : tâche 6 (vérification manuelle ciblée) + suite de tests complète après la tâche 2.
- **Fichiers JS externes (pattern C)** : `table_vols_avion.js`/`table_vols_planeur.js` pourraient avoir d'autres dépendances sur une largeur de tableau fixe (ex. calculs de synthèse, colonnes figées) — à vérifier au cas par cas avant de désactiver `bAutoWidth`.
- **`event/bs_statsView.php`** a un nombre de colonnes dynamique (une par année configurée) — tester avec une plage d'années large pour confirmer que la synchronisation reste correcte même avec un tableau très large.
- **Idempotence du calque de menu** : la fonction partagée doit vérifier l'existence du calque avant d'en recréer un, pour éviter une accumulation de `<div>` sur les pages où `fnDrawCallback` est appelé plusieurs fois (recherche, pagination, changement de page). Déjà géré dans la version de référence — à préserver lors de l'extraction (tâche 1).
