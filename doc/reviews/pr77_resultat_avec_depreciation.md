# Code Review — PR #77 : Résultat avant et après dépréciation

**Branch:** `feature/resultat_avant_depreciations` → `main`  
**Date:** 2026-04-04  

---

## Résumé

Ce PR ajoute la route `comptes/resultat_avec_depreciation` qui décompose le résultat d'exploitation en trois niveaux : comptes hors 68x/78x, section dépréciations, puis résultat final identique à `comptes/resultat`. Les exports CSV et PDF sont inclus.

**Verdict global :** Logique comptable correcte, tests Playwright valides. Plusieurs points méritent attention.

---

## Problèmes identifiés

### 🔴 Critique

**1. Double lecture de session dans `select_resultat_avec_depreciation()` — risque d'incohérence d'année**

`ecritures_model.php`, lignes 243–248 :
```php
function select_resultat_avec_depreciation($year = "") {
    $result = $this->select_resultat($year);    // lit session si $year == ""

    if ($year == "") {
        $year = $this->session->userdata('year'); // relit la session
    }
    $years = array($year - 1, $year);
```

`select_resultat()` lit `$this->session->userdata('year')` en interne. Si la session change entre les deux lectures (cas improbable mais possible sous charge), `$year` utilisé pour les requêtes `_select_*_codec_range` serait différent de celui utilisé dans `select_resultat()`. La donnée renvoyée serait alors incohérente.

**Correction :** Lire l'année une seule fois avant d'appeler `select_resultat()` :
```php
if ($year == "") {
    $year = $this->session->userdata('year');
}
$result = $this->select_resultat($year);
$years = array($year - 1, $year);
```

---

### 🟠 Majeur

**2. Nombre de requêtes SQL excessif (N+8 par page)**

Pour chaque année (N et N-1), `select_resultat_avec_depreciation()` émet 8 requêtes SQL via `_select_depenses_codec_range` / `_select_recettes_codec_range` (2 requêtes par appel × 4 appels par année). Soit **16 requêtes SQL** supplémentaires par rapport à `select_resultat()` qui en émet déjà 8.

Total pour la page : **~24 requêtes SQL**. Sur une base avec de nombreuses écritures, cela peut être lent.

Les données hors dépréciations (`montants_hd`) sont calculées indépendamment de `montants` (qui les inclut). On pourrait dériver `montants_hd` en soustrayant les montants 68x/78x de `montants` sans requêtes supplémentaires.

**Correction recommandée :** Calculer `montants_hd[y][total_depenses] = montants[y][total_depenses] - dep68_total` au lieu de refaire une requête complète.

---

**3. Codec comparaisons de chaînes — risque de tri lexicographique incorrect**

Dans `_select_depenses_codec_range` et `_select_recettes_codec_range`, les bornes sont passées comme chaînes entre guillemets :
```php
->where("compte1.codec >= $codec_min and compte1.codec < $codec_max")
// ex: compte1.codec >= "6" and compte1.codec < "68"
```

Si `codec` est de type `VARCHAR`, la comparaison est lexicographique : `"699" < "7"` est vrai en ordre lexicographique, mais `"698" < "699"` aussi. Cela fonctionne correctement pour les plages utilisées ici (un seul niveau de chiffres significatifs), mais c'est fragile si des codecs non standards existent (ex : `"680A"`). Ce n'est pas introduit par ce PR (le code parent `select_depenses` a le même pattern), mais vaut la peine d'être noté.

---

### 🟡 Mineur

**4. `$empty_row` utilisé comme séparateur mais avec des tabulations — rendu CSV incorrect**

`$empty_row = array_fill(0, 11, $tab)` où `$tab = nbs(6)` en HTML. Pour les exports CSV et PDF, `$tab` est `''` (chaîne vide), donc `$empty_row` devient 11 cellules vides — ce qui est correct pour CSV/PDF. Pas de bug, mais ce n'est pas évident à la lecture.

**5. Lignes de titre affichées avec le style "Profits/Pertes" — pas de différenciation visuelle**

Les lignes "Résultat avant dépréciations" et "Résultat après dépréciations" utilisent les mêmes clés `comptes_label_total_benefices` / `comptes_label_total_pertes` que les lignes de résultat ordinaires. Le titre intermédiaire (ex : "Résultat avant dépréciations") est placé dans la colonne des charges sans mise en forme particulière — il n'est pas mis en gras ni centré dans le tableau HTML. Dans le PDF, la table TCPDF applique les mêmes bordures et hauteur à toutes les lignes, rendant ces titres difficiles à distinguer visuellement.

**6. `$comptes_69` / `$comptes_79` toujours vides en pratique**

```php
$comptes_69 = $this->comptes_model->list_of_account('codec >= "69" and codec < "7"', 'codec');
$result['comptes_depenses_hd'] = array_merge($result['comptes_depenses_hd'], $comptes_69);
```

Les comptes 690-699 n'existent pas dans le plan comptable français standard. Ces deux appels `list_of_account` sont donc systématiquement vides. Ils peuvent être supprimés pour simplifier le code sans perte fonctionnelle.

**7. Ordre non garanti après `array_merge` sur `comptes_depenses_hd`**

```php
$result['comptes_depenses_hd'] = array_merge(
    $this->comptes_model->list_of_account('codec >= "6" and codec < "68"', 'codec'),
    $comptes_69
);
```

Chaque appel est trié par `codec` individuellement, mais leur concaténation n'est pas re-triée. Si des comptes 690+ existaient, ils apparaîtraient après tous les comptes 680+ dans la liste, pas intercalés correctement.

**8. Séparateur de décimales dans le CSV — incohérence potentielle**

`csv_resultat_avec_depreciation()` passe `','` comme séparateur décimal (cohérent avec `csv_resultat()`), mais le CSV est généré avec des virgules décimales sans changement du séparateur de colonnes. Si le fichier CSV est ouvert dans Excel configuré en locale française, la virgule dans les montants sera interprétée comme séparateur de colonne. Ce comportement existait déjà dans `csv_resultat()` — pas introduit ici, mais toujours présent.

**9. Clé `comptes_label_total_benefices` réutilisée pour les lignes "avant" et "après"**

Les lignes de résultat intermédiaire (avant dépréciations) et finale (après dépréciations) utilisent toutes deux `comptes_label_total_benefices` ("Profits") et `comptes_label_total_pertes` ("Pertes"). Dans le PDF et le CSV, les deux lignes "Profits" sont identiques et non distinguables sans lire la ligne de titre qui précède.

---

### 🟢 Style / Cosmétique

**10. Variables temporaires `$tbl_0`..`$tbl_11` — style inhabituel dans la base de code**

L'approche de nommer des variables scalaires `$tbl_0`, `$tbl_7`, etc. pour construire les tableaux creux est fonctionnelle mais rend le code difficile à lire. Le pattern existant dans `resultat_table()` affecte directement `$tbl[$line][0]`. La contrainte du tableau creux était nécessaire (bug de décalage de colonnes), mais le nommage temporaire nuit à la lisibilité.

**11. Méthodes privées non protégées par `access control`**

`_select_depenses_codec_range()` et `_select_recettes_codec_range()` sont `private` — bien. Elles pourraient néanmoins être mutualisées en une seule méthode `_select_codec_range($year, $date_op, $min, $max, $group_by, $side)` puisque leur structure est identique à 90%.

---

## Todo (par criticité décroissante)

- [x] 🔴 **Corriger double lecture de session** — lire `$year` avant d'appeler `select_resultat()` (`ecritures_model.php`)
- [x] 🟠 **Réduire les requêtes SQL** — dériver `montants_hd` par soustraction plutôt que par nouvelles requêtes
- [ ] 🟠 **Documenter la dépendance au tri lexicographique** des codecs, ou passer les comparaisons en numérique (`CAST(codec AS UNSIGNED)`)
- [x] 🟡 **Supprimer les appels `list_of_account` pour 69x/79x** — dead code en pratique
- [x] 🟡 **Améliorer la distinction visuelle des lignes de titre** dans le HTML — titre en `<strong>` pour les lignes "Résultat avant/après dépréciations"
- [x] 🟡 **Utiliser des clés de langue dédiées** pour les résultats intermédiaires (`comptes_label_avant_dep_benefices`, `comptes_label_apres_dep_benefices`, etc.)
- [x] 🟢 **Refactoriser** `_select_depenses_codec_range` + `_select_recettes_codec_range` en méthode unique `_select_codec_range($year, $date_op, $min, $max, $group_by, $side)`

---

## Points positifs

- Le bug du tableau creux (décalage colonne 6, striping DataTables) a été correctement identifié et corrigé — commentaire explicatif dans le code.
- La structure `select_resultat() → select_resultat_avec_depreciation()` respecte le principe d'extension sans modification de l'existant.
- Les 3 exports (HTML, CSV, PDF) sont cohérents et utilisent la même source de données.
- Couverture Playwright sur 2 sections et 2 années (N et N-1) pour la propriété clé : résultat après dépréciations == `comptes/resultat`.
- Language files complets pour fr/en/nl.
