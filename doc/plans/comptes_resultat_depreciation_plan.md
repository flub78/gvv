# Plan : Résultat avant et après dépréciations

## Objectif

Ajouter une route `comptes/resultat_avec_depreciation` qui affiche le résultat d'exploitation en distinguant :
- le résultat **avant dépréciations** (hors comptes 68x et 78x)
- le détail des charges et produits de dépréciation (68x et 78x)
- le résultat **après dépréciations** (identique à `comptes/resultat` actuel)

Un test Playwright vérifie que le résultat après dépréciations est cohérent avec `comptes/resultat` sur plusieurs sections et années.

---

## Contexte technique

### Flux actuel (`comptes/resultat`)

```
Controller::resultat()
  → ecritures_model->select_resultat()        // agrège 6xx et 7xx
  → ecritures_model->resultat_table()         // formate le tableau
  → bs_resultatView.php                       // affiche
```

### Méthodes clés dans `ecritures_model.php`

| Méthode | Rôle |
|---------|------|
| `select_resultat($year)` | Retourne comptes 6xx/7xx + montants N et N-1 |
| `select_depenses($year, $group_by, $date)` | Total débits 6xx (y compris 68x) |
| `select_recettes($year, $group_by, $date)` | Total crédits 7xx (y compris 78x) |
| `resultat_table($resultat, ...)` | Formate le tableau HTML/CSV/PDF |
| `montants($rows)` | Indexe les montants par id compte |

### Structure de `select_resultat()` retournée

```php
[
  'years'            => [N-1, N],
  'comptes_depenses' => [...],  // liste comptes 6xx
  'comptes_recettes' => [...],  // liste comptes 7xx
  'montants' => [
    N   => ['total_depenses', 'total_recettes', 'depenses' => [...], 'recettes' => [...]],
    N-1 => [...],
  ],
  'balance_date' => '...',
]
```

### Filtrage 68x/78x

Les comptes 68x/78x sont dans la plage 6xx/7xx — ils sont actuellement agrégés dans les totaux sans distinction. Le filtre codec `>= "68" and < "69"` (resp. `>= "78" and < "79"`) les isole.

---

## Étapes

### Étape 1 — Modèle : `select_resultat_avec_depreciation()` ✅

**Fichier :** `application/models/ecritures_model.php`

Nouvelle méthode basée sur `select_resultat()`, qui construit deux ensembles de données :

**a) Résultat hors dépréciations** (comptes 6xx hors 68x, 7xx hors 78x)
- Filtrer `comptes_depenses` et `comptes_recettes` pour exclure les comptes 68x/78x
- Recalculer les totaux et montants par compte sur ce sous-ensemble uniquement
- Utiliser `select_depenses` / `select_recettes` avec un filtre codec supplémentaire excluant la plage 68x/78x

**b) Dépréciations** (comptes 68x seulement, comptes 78x seulement)
- Appeler `select_depenses` / `select_recettes` avec filtre `codec >= "68" and codec < "69"` / `codec >= "78" and codec < "79"`
- Retourner les comptes et montants de ces deux sous-ensembles

**Structure retournée :**
```php
[
  // standard (complet, pour "résultat après dépréciations")
  'years', 'comptes_depenses', 'comptes_recettes', 'montants', 'balance_date',
  // hors dépréciations
  'comptes_depenses_hd', 'comptes_recettes_hd',
  'montants_hd',   // même structure que montants, mais sans 68x/78x
  // dépréciations seules
  'comptes_68', 'comptes_78',
  'montants_dep',  // montants 68x/78x par année
]
```

---

### Étape 2 — Modèle : `resultat_avec_depreciation_table()` ✅

**Fichier :** `application/models/ecritures_model.php`

Nouvelle méthode de formatage, produit un tableau de lignes (même format que `resultat_table`) avec la structure suivante :

```
[en-tête : Code | Charges | Section | N | N-1 | ... | Code | Produits | Section | N | N-1]
[lignes comptes hors 68x/78x ...]
[ligne vide]
[ligne "Total charges hors dép." | ... | "Total produits hors dép." | ...]
[ligne titre : "Résultat avant dépréciations" | ... | ]
[ligne résultat avant dép. : Perte | ... | Profit | ...]
[ligne vide]
[lignes comptes 68x | ... | comptes 78x | ...]
[ligne "Total dépréciations" | ... | "Total reprises" | ...]
[ligne vide]
[ligne titre : "Résultat après dépréciations"]
[ligne résultat final : Perte | ... | Profit | ...]
```

Les lignes de titre et de résultat intermédiaire utilisent une cellule `colspan` ou un marqueur spécial pour le rendu. Pour simplifier et rester compatible avec le `DataTable` existant, les cellules vides sont remplies avec `nbs(6)` (espace insécable) comme dans `resultat_table`.

---

### Étape 3 — Contrôleur : `resultat_avec_depreciation()` ✅

**Fichier :** `application/controllers/comptes.php`

```php
function resultat_avec_depreciation() {
    $this->data['controller'] = "comptes";
    $this->data['year_selector'] = $this->ecritures_model->getYearSelector("date_op");
    $this->data['year'] = $this->session->userdata('year');
    $this->data['resultat_table'] = $this->ecritures_model->resultat_avec_depreciation_table(
        $this->ecritures_model->select_resultat_avec_depreciation(), true, nbs(6), '.'
    );
    $this->data['section'] = $this->gvv_model->section();
    $this->push_return_url("resultat_avec_depreciation");
    load_last_view('comptes/resultatAvecDepreciationView', $this->data);
}
```

Pas de route personnalisée nécessaire — CodeIgniter route automatiquement `comptes/resultat_avec_depreciation`.

---

### Étape 4 — Vue : `bs_resultatAvecDepreciationView.php` ✅

**Fichier :** `application/views/comptes/bs_resultatAvecDepreciationView.php`

Copie de `bs_resultatView.php` avec :
- Titre : `$lang['gvv_comptes_title_resultat_avec_depreciation']`
- Même DataTable, même alignements, mêmes boutons Export (sans bouton Clôture)

---

### Étape 5 — Language files ✅

**Fichiers :** `application/language/{french,english,dutch}/comptes_lang.php`

| Clé | Français | Anglais | Néerlandais |
|-----|----------|---------|-------------|
| `gvv_comptes_title_resultat_avec_depreciation` | Résultat avant et après dépréciations | Result before and after depreciation | Resultaat voor en na afschrijvingen |
| `comptes_label_resultat_avant_dep` | Résultat avant dépréciations | Result before depreciation | Resultaat voor afschrijvingen |
| `comptes_label_resultat_apres_dep` | Résultat après dépréciations | Result after depreciation | Resultaat na afschrijvingen |
| `comptes_label_total_dep_charges` | Total dotations aux dépréciations | Total depreciation charges | Totaal afschrijvingslasten |
| `comptes_label_total_dep_produits` | Total reprises sur dépréciations | Total depreciation income | Totaal terugnames afschrijvingen |
| `comptes_label_total_charges_hd` | Total charges hors dépréciations | Total expenses excl. depreciation | Totaal lasten excl. afschrijvingen |
| `comptes_label_total_produits_hd` | Total produits hors dépréciations | Total income excl. depreciation | Totaal opbrengsten excl. afschrijvingen |

---

### Étape 6 — Lien dans le menu ✅

**Fichier :** Identifier et ajouter un lien vers `comptes/resultat_avec_depreciation` dans le menu comptabilité (même emplacement que `comptes/resultat`).

Chercher le menu dans `application/views/bs_menu.php` ou dans le contrôleur qui génère les items de navigation comptabilité.

---

### Étape 7 — Test Playwright ✅

**Fichier :** `playwright/tests/resultat-avec-depreciation.spec.js`

**Stratégie :**
1. Récupérer le résultat final ("après dépréciations") depuis `comptes/resultat_avec_depreciation` pour plusieurs combinaisons (section planeur, section générale, années N et N-1).
2. Récupérer le résultat depuis `comptes/resultat` pour les mêmes combinaisons.
3. Comparer : les deux doivent être identiques.

**Cas de test :**

| # | Section | Année | Attendu |
|---|---------|-------|---------|
| 1 | Planeur (Astérix) | Année courante | résultat après dép. == resultat simple |
| 2 | Planeur (Astérix) | Année précédente | idem |
| 3 | Général (Obélix) | Année courante | idem |
| 4 | Général (Obélix) | Année précédente | idem |

**Fonctions helpers dans le test :**
- `getResultatFromPage(page, url, section, year)` → navigue, sélectionne section/year, lit le montant "Résultat après dépréciations" ou "Profits/Pertes"
- `getResultatSimple(page, section, year)` → lit depuis `comptes/resultat`
- `getResultatAvecDep(page, section, year)` → lit depuis `comptes/resultat_avec_depreciation`

**Extraction des valeurs :** Les lignes de résultat sont identifiables par leur contenu textuel ("Résultat après dépréciations" ou "Profits"). Le montant est dans la 4e ou 5e cellule de la même ligne (colonne N ou N-1).

---

## Fichiers impactés

| Fichier | Modification |
|---------|-------------|
| `application/models/ecritures_model.php` | +`select_resultat_avec_depreciation()`, +`resultat_avec_depreciation_table()` |
| `application/controllers/comptes.php` | +`resultat_avec_depreciation()` |
| `application/views/comptes/bs_resultatAvecDepreciationView.php` | Nouveau |
| `application/language/french/comptes_lang.php` | +7 clés |
| `application/language/english/comptes_lang.php` | +7 clés |
| `application/language/dutch/comptes_lang.php` | +7 clés |
| `application/views/bs_menu.php` (ou équivalent) | +lien menu |
| `playwright/tests/resultat-avec-depreciation.spec.js` | Nouveau |

---

## Statut

| Étape | Description | Statut |
|-------|-------------|--------|
| 1 | `select_resultat_avec_depreciation()` | ✅ |
| 2 | `resultat_avec_depreciation_table()` | ✅ |
| 3 | Contrôleur `resultat_avec_depreciation()` | ✅ |
| 4 | Vue `bs_resultatAvecDepreciationView.php` | ✅ |
| 5 | Language files (fr/en/nl) | ✅ |
| 6 | Lien menu | ✅ |
| 7 | Test Playwright | ✅ |
