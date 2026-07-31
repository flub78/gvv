# Plan : Persistance par page des recherches DataTables

**Date:** 2026-07-31
**Statut:** Tâches 1, 2 et 4 implémentées et vérifiées (tests Playwright verts). Tâches 3 et 5 optionnelles non réalisées.
**Périmètre:** Correction ciblée (pas de PRD séparée, cf. justification en section 1)

---

## 1. Contexte et diagnostic

### 1.1 Symptôme signalé

Une chaîne tapée dans la searchbox DataTables (le champ "Rechercher :" intégré au widget, pas les formulaires de filtre) réapparaît sur d'autres pages de listing et masque des lignes que l'utilisateur ne s'attend pas à voir filtrées. Le comportement est surprenant car rien n'indique visuellement que le filtre vient d'une page précédente.

### 1.2 Cause racine (vérifiée dans le code)

Contrairement à la formulation initiale, **ce n'est pas la session PHP** qui est en cause ici (ce mécanisme existe pour les formulaires de filtre — cf. `doc/prds/filtrage_par_page.md`, sujet distinct et déjà traité par ailleurs). Le problème vient du stockage **côté navigateur** géré par le plugin JS `jquery.dataTables`.

**a) Tables sans `id` HTML**

`MetaData::table()` (`application/libraries/MetaData.php:535`) génère toujours `<table class="...">` sans jamais émettre d'attribut `id`, quel que soit le contenu de `$attrs`. C'est le cas de toutes les vues `bs_tableView.php`, mais seule une partie d'entre elles utilise la classe brute `datatable` (celle branchée sur le bloc défectueux de `bs_footer.php`, cf. point b) : achats, associations_ecriture, associations_of, associations_releve, attachments, avion, categorie, configuration, dates_gel, document_types, event, events_types, historique, meteo, motd, planeur, reports, sections, tarifs, terrains, tickets, types_ticket, user_roles_per_section, vols_decouverte — soit 24 contrôleurs.

D'autres vues utilisent des classes voisines mais **avec leur propre initialisation JS dédiée**, sans passer par le bloc défectueux : `membre` (`.table_membre`/`.table_membre_ro`, JS inline dans la vue), `vols_avion` (`.datedtable`/`.datedtable_ro`), `vols_planeur`/`plan_comptable` (`.datatable_server`/`.datatable_server_ro`), `comptes` (`.searchable_nosort_datatable`). Ces classes n'ont pas de surcharge `fnStateSave` et utilisent donc le mécanisme cookie natif décrit au point (c) — **déjà correctement isolé par chemin d'URL**. Elles ne sont pas affectées par le bug principal.

En l'absence d'`id`, DataTables 1.9.x en génère un automatiquement : `DataTables_Table_0`, `DataTables_Table_1`, ... Le compteur (`iNextUnique`) repart de 0 à **chaque chargement de page** (`assets/javascript/jquery.dataTables.js:6420`). Résultat : deux pages différentes qui n'affichent chacune qu'une seule table `.datatable` obtiennent **le même id auto-généré** `DataTables_Table_0`.

**b) Stockage localStorage non isolé par page**

Pour la classe `.datatable` (la plus utilisée), `bs_footer.php:60-95` surcharge la persistance native de DataTables avec un `fnStateSave`/`fnStateLoad` maison :

```js
"fnStateSave": function(oSettings, oState) {
    localStorage.setItem('DT_' + oSettings.sInstance, JSON.stringify(oState));
},
"fnStateLoad": function(oSettings) {
    var data = localStorage.getItem('DT_' + oSettings.sInstance);
    return data ? JSON.parse(data) : null;
}
```

La clé `'DT_' + oSettings.sInstance` vaut donc `DT_DataTables_Table_0` sur la quasi-totalité des pages de listing. `localStorage` n'étant ni isolé par onglet ni par chemin d'URL, **cette clé est strictement globale au navigateur** : la recherche (et la page, le tri, la longueur de page) saisie sur `/planeur/page` est relue telle quelle sur `/membre/page`, `/avion/page`, etc.

**c) Les autres variantes DataTables sont moins touchées**

Les classes `.datedtable`, `.datedtable_ro` (vols_avion), `.datatable_server`, `.datatable_server_ro` (vols_planeur), `.datatable_nopaging`, `.datatable_500`, `.searchable_nosort_datatable`, `.balance_searchable_datatable` n'ont pas de surcharge `fnStateSave` : elles utilisent le mécanisme natif à base de cookie (`_fnCreateCookie`, `jquery.dataTables.js:4482`). Ce mécanisme découpe déjà le nom du cookie et surtout son attribut `path` à partir de `window.location.pathname` (tout sauf le dernier segment) — un cookie posé sur `/planeur/page` a pour `path` `/planeur/`, donc n'est jamais envoyé sur `/membre/page`. **Ces tables sont donc déjà correctement isolées par contrôleur**, avec une réserve mineure : deux actions du même contrôleur (ex. `vols_avion/page` et `vols_avion/statistic`) partagent le même préfixe de path et donc le même état — comportement discutable mais nettement moins visible/grave que le cas (b), et hors périmètre de ce plan.

### 1.3 Pourquoi pas de PRD séparée

Le problème est un défaut d'implémentation localisé (une clé de cache mal construite dans `bs_footer.php`), pas une nouvelle fonctionnalité. `doc/prds/filtrage_par_page.md` couvre déjà, pour un mécanisme différent (filtres serveur en session PHP), la même préoccupation de fond ("état partagé globalement au lieu d'être isolé par contexte") — ce plan s'y référencera mais ne le duplique pas.

### 1.4 Documentation utilisateur

Recherche effectuée dans `doc/users/fr/*.md` (02_gestion_membres.md, 03_gestion_aeronefs.md, 04_saisie_vols.md, README.md) : les sections "Recherche et filtres" décrivent l'usage de la searchbox mais **ne documentent nulle part la persistance ou son caractère global**. Aucune mise à jour de documentation utilisateur n'est donc requise au sens strict de la consigne ("si le mécanisme est décrit"). Une clarification optionnelle est proposée en tâche 3.2, à valider avec l'utilisateur.

---

## 2. Objectif

Isoler l'état DataTables sauvegardé (recherche, page courante, tri, longueur de page) **par page** (au sens URL/pathname, comme le fait déjà le mécanisme cookie natif), pour la classe `.datatable`, sans changer la sémantique pour les autres classes qui sont déjà correctement scopées.

Non-objectifs : isolation multi-onglets (nécessiterait `sessionStorage`, changerait la sémantique de persistance — hors demande), refonte des filtres serveur (couvert par `filtrage_par_page_plan.md`).

---

## 3. Solution retenue

Corriger la clé utilisée par `fnStateSave`/`fnStateLoad` dans `bs_footer.php` pour y inclure `window.location.pathname`, à l'identique de ce que fait déjà le mécanisme cookie natif des autres tables :

```js
"fnStateSave": function(oSettings, oState) {
    try {
        var key = 'DT_' + oSettings.sInstance + '_' + window.location.pathname;
        localStorage.setItem(key, JSON.stringify(oState));
    } catch(e) {}
},
"fnStateLoad": function(oSettings) {
    try {
        var key = 'DT_' + oSettings.sInstance + '_' + window.location.pathname;
        var data = localStorage.getItem(key);
        return data ? JSON.parse(data) : null;
    } catch(e) {
        return null;
    }
},
```

- La query string n'est volontairement pas incluse (comportement aligné sur celui des cookies DataTables natifs, qui ne sont pas non plus scopés par query string).
- Changement dans un seul fichier, aucune modification PHP, aucune migration de vue nécessaire.
- Les anciennes entrées `localStorage['DT_DataTables_Table_0']` deviennent orphelines mais inoffensives (jamais relues après le changement de format de clé). Nettoyage automatique non nécessaire (pas de limite de taille problématique en pratique) — voir tâche optionnelle 3.3 si un nettoyage est souhaité.

### Option écartée : attribution d'un `id` explicite à chaque `<table>`

Ajouter un `id` stable (ex. `datatable-planeur`) dans les 29 vues `bs_tableView.php` réglerait aussi la collision d'`id` auto-généré, et améliorerait au passage la testabilité (sélecteurs Playwright stables) et l'accessibilité. C'est une amélioration plus large, à effort et surface de code bien supérieurs (29 fichiers + `MetaData::table()`), pour un bénéfice marginal une fois le correctif de clé appliqué. Proposée en tâche optionnelle 3.4, à ne faire que si demandé explicitement.

---

## 4. Tâches

### ✅ Tâche 1 : Corriger la clé de persistance dans `bs_footer.php`
**Fichier:** `application/views/bs_footer.php` (lignes 65-77)
**Effort:** 15 min
Correctif appliqué tel que décrit en section 3.

### ✅ Tâche 2 : Tests Playwright de non-régression
**Fichier:** `playwright/tests/datatable-persistence.spec.js`
**Effort:** 1h

Test `should not leak a search term from one datatable page to another` ajouté. Vérifié en pratique : échoue sans le correctif de la tâche 1 (terme "zzz_leak_test" saisi sur `/planeur/page` retrouvé sur `/avion/page`), passe avec le correctif. Suite complète (5 tests) verte.

Reproduit précisément le symptôme signalé par l'utilisateur :

1. Aller sur une page de listing utilisant `.datatable` (ex. `/planeur/page`), saisir un terme dans la searchbox, attendre la sauvegarde d'état.
2. Naviguer vers une **autre page de listing** utilisant aussi `.datatable` (ex. `/avion/page` ou `/tickets/page` — voir liste en section 1.2a ; ne pas utiliser `/membre/page`, qui n'est pas affectée par ce bug).
3. Vérifier que la searchbox de cette seconde page est **vide** (pas de fuite du terme saisi sur la première page).
4. Revenir sur la première page et vérifier que le terme saisi est **toujours présent** (la persistance intra-page continue de fonctionner).

Conserver ce test dans la suite (régression) conformément aux consignes du projet.

### ⏳ Tâche 3 (optionnelle) : Clarification documentation utilisateur
**Fichier:** `doc/users/fr/02_gestion_membres.md` (section "Recherche et filtres") ou équivalent
**Effort:** 15 min
**À valider avec l'utilisateur avant exécution** — le mécanisme actuel n'étant pas documenté, cette tâche n'est pas requise par la consigne mais peut être ajoutée pour informer les utilisateurs que la recherche reste mémorisée en revenant sur une même page. Exemple de phrase : *« Le terme recherché est mémorisé tant que vous restez sur cet écran, y compris après rechargement de la page. »*

### ✅ Tâche 4 (optionnelle) : Nettoyage des entrées localStorage orphelines
**Fichier:** `application/views/bs_footer.php` (avant l'init `.datatable`)
**Effort:** 30 min
Boucle exécutée à chaque chargement de page, avant l'initialisation `.datatable` : supprime toute clé `localStorage` commençant par `DT_` et ne contenant pas de `/` (donc sans suffixe de chemin — format d'avant le correctif). Les clés au nouveau format (qui embarquent toujours un chemin commençant par `/`) sont préservées. Vérifié manuellement (test Playwright jetable, supprimé après vérification) : une clé orpheline `DT_DataTables_Table_0` est bien supprimée au rechargement, tandis qu'une clé `DT_DataTables_Table_0_/index.php/planeur/page` est conservée.

### ⏳ Tâche 5 (optionnelle, hors périmètre par défaut) : Identifiants HTML explicites
**Effort:** ~1 jour (29 vues + `MetaData::table()`)
Cf. section 3, "Option écartée". À ne lancer que sur demande explicite, en tant que plan séparé si retenu.

---

## 5. Tests — récapitulatif

| Type | Nécessaire ? | Justification |
|------|--------------|----------------|
| PHPUnit | Non | Aucun code PHP modifié (tâche 1 est un changement JS pur) |
| Playwright | Oui (tâche 2) | Seul moyen de vérifier un comportement de persistance navigateur (localStorage) |

---

## 6. Risques

- **Effet de bord minimal** : le changement de clé ne touche que la classe `.datatable`. Les autres classes (déjà isolées par cookie natif) ne sont pas modifiées.
- **Compatibilité navigateurs anciens IE** mentionnée dans les commentaires historiques de DataTables : sans objet ici, `window.location.pathname` est disponible partout.
- **Test flaky potentiel** : les tests Playwright de persistance existants utilisent déjà des `waitForTimeout` généreux pour laisser le temps à `localStorage`/aux cookies de se mettre à jour ; suivre le même pattern pour le nouveau test.
