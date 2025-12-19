# Correction de l'affichage du détail par sections

## Problème identifié

Dans la vue `/comptes/resultat_par_sections_detail/607`, le tableau affichait :
- **Structure prévue** : Code, Libellé, Section avec colonnes doubles par section (N et N-1)
- **Problème** : Seules les colonnes de l'année N étaient affichées, l'année N-1 n'apparaissait pas
- **Exports** : Décalage de colonnes entre les titres et les valeurs dans les exports CSV et PDF

## Solution implémentée

### 1. Modification de la vue HTML (`bs_resultat_par_sections_detailView.php`)

**a) Correction de `render_simple_detail_table()` - En-têtes**
- Avant : L'en-tête traitait `$idx >= 3` comme colonnes numériques avec une logique ternaire
- Après : Traitement explicite de chaque colonne :
  - `$idx == 3` → Année N (class: `year-current`)
  - `$idx == 4` → Année N-1 (class: `year-previous`)

**b) Correction de `render_simple_detail_table()` - Corps du tableau**
- Avant : `else if ($col_idx >= 4)` avec logique ternaire pour déterminer la classe
- Après : Traitement explicite de chaque colonne :
  - `$col_idx == 4` → Année N avec class `year-current`
  - `$col_idx == 5` → Année N-1 avec class `year-previous`
- **Résultat** : La colonne 5 (Année N-1) est maintenant correctement affichée

### 2. Modification de l'export CSV (`comptes.php::csv_resultat_par_sections_detail()`)

**Avant :**
```php
$csv_data[] = array(
    $this->lang->line("comptes_label_date"),
    $data['balance_date'],
    '',
    '',
    '',
    ''  // 6 colonnes
);
```

**Après :**
```php
$csv_data[] = array(
    $this->lang->line("comptes_label_date"),
    $data['balance_date'],
    '',
    '',
    ''  // 5 colonnes (Code, Libellé, Section, N, N-1)
);
```

### 3. Modification du traitement des exports (`comptes.php::resultat_par_sections_detail()`)

**Avant :**
```php
foreach ($detail as $row) {
    $export_row = array();
    foreach ($row as $idx => $value) {
        if ($idx == 2) continue; // Skip compte_id
        $export_row[] = $value;
    }
    $detail_export[] = $export_row;
}
```

**Problème** : La ligne d'en-tête (index 0) ne devrait pas avoir de colonne `compte_id`, mais le code essayait quand même de la supprimer.

**Après :**
```php
foreach ($detail as $row_idx => $row) {
    $export_row = array();
    foreach ($row as $col_idx => $value) {
        if ($row_idx == 0) {
            // En-tête: Code, Libellé, Section, N, N-1 (pas de compte_id)
            $export_row[] = $value;
        } else {
            // Données: sauter compte_id à l'index 2
            if ($col_idx == 2) continue;
            $export_row[] = $value;
        }
    }
    $detail_export[] = $export_row;
}
```

### 4. Export PDF

L'export PDF (`pdf_resultat_par_sections_detail()`) utilisait déjà la structure correcte avec `leadingCols = 3` :
- Code (20mm)
- Libellé (70mm)
- Section (60mm)
- Colonnes numériques (largeur dynamique pour N et N-1)

Aucune modification n'était nécessaire pour le PDF.

## Structure finale des données

### Données brutes (retournées par `select_detail_codec_deux_annees()`)
```
Index  | Colonne       | Type     | Usage
-------|---------------|----------|---------------------------
0      | Code          | string   | Code du compte
1      | Libellé       | string   | Nom du compte
2      | compte_id     | int      | ID pour lien vers journal (caché)
3      | Section       | string   | Nom ou acronyme de la section
4      | Année N       | float    | Montant année courante
5      | Année N-1     | float    | Montant année précédente
```

### Données pour export (CSV/PDF) - après suppression de compte_id
```
Index  | Colonne       | Type     | Usage
-------|---------------|----------|---------------------------
0      | Code          | string   | Code du compte
1      | Libellé       | string   | Nom du compte
2      | Section       | string   | Nom ou acronyme de la section
3      | Année N       | float    | Montant année courante
4      | Année N-1     | float    | Montant année précédente
```

## Tests effectués

✓ Syntaxe PHP validée pour :
  - `application/controllers/comptes.php`
  - `application/views/comptes/bs_resultat_par_sections_detailView.php`

## Tests manuels recommandés

1. **Vue HTML** :
   - Accéder à `/comptes/resultat_par_sections_detail/607`
   - Vérifier l'affichage des 5 colonnes : Code, Libellé, Section, N, N-1
   - Vérifier que les valeurs de N-1 sont visibles et correctement formatées

2. **Export CSV** :
   - Cliquer sur le bouton "Excel"
   - Vérifier que le CSV contient 5 colonnes
   - Vérifier l'alignement des données

3. **Export PDF** :
   - Cliquer sur le bouton "PDF"
   - Vérifier que le PDF contient 5 colonnes alignées
   - Vérifier que les valeurs numériques sont alignées à droite

## Fichiers modifiés

1. `/home/frederic/git/gvv/application/views/comptes/bs_resultat_par_sections_detailView.php`
   - Fonction `render_simple_detail_table()` : correction de l'affichage des colonnes

2. `/home/frederic/git/gvv/application/controllers/comptes.php`
   - Fonction `resultat_par_sections_detail()` : correction du traitement des exports
   - Fonction `csv_resultat_par_sections_detail()` : correction du nombre de colonnes dans l'en-tête CSV
