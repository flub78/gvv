# Boîte de recherche pour le journal de compte (mode pagination > 400 entrées)

## Contexte

Lorsqu'un compte a plus de 400 entrées dans le journal, le système bascule du mode DataTables (avec recherche intégrée) vers le mode pagination CodeIgniter standard. Dans ce mode, la boîte de recherche DataTables disparaissait, rendant la recherche dans les entrées impossible.

## Solution implémentée

Ajout d'une boîte de recherche émulant l'apparence et le comportement de DataTables lorsque le mode pagination CodeIgniter est activé (count > 400).

### Modifications apportées

**Fichier modifié:** `application/views/compta/bs_journalCompteView.php`

#### 1. HTML/JavaScript - Boîte de recherche (lignes 510-526)

```javascript
// Create and inject search box into pagination bar
var searchBoxHtml = '<div class="pagination-search-box">' +
    '<label>Recherche: <input type="search" id="tableSearch" class="form-control form-control-sm" placeholder="" aria-controls="dataTable"></label>' +
    '</div>';

// Find the FIRST pagination bar (top one) and insert search box just before "Dernier" button
var $paginationTable = $('#body > div > table:not(.sql_table)').first();
var $paginationCell = $paginationTable.find('td');
if ($paginationCell.length > 0) {
    // Find the last pagination link in the first bar (because float:right reverses visual order)
    // Insert after the last link so it appears visually just before "Dernier"
    var $lastLink = $paginationCell.find('a, strong').last();
    if ($lastLink.length > 0) {
        $(searchBoxHtml).insertAfter($lastLink);
    } else {
        // Fallback if no links found
        $(searchBoxHtml).appendTo($paginationCell);
    }
}
```

**Position:** Intégrée dans la **première** barre de pagination grise (en haut de la table), cadrée à droite, juste avant le bouton "Dernier".

**Note technique:**
- Il y a deux barres de pagination (haut et bas), le code cible spécifiquement la première avec `.first()`
- Les boutons de pagination ont `float: right`, ce qui inverse leur ordre visuel par rapport au DOM
- La boîte de recherche est insérée **après** le dernier élément dans le DOM pour apparaître visuellement **avant** le bouton "Dernier"

#### 2. JavaScript - Filtrage (lignes 492-510)

```javascript
// Add table search functionality for CodeIgniter pagination mode (> 400 entries)
<?php if ($count > 400): ?>
$('#tableSearch').on('keyup', function() {
    var searchTerm = $(this).val().toLowerCase();

    // Find the main data table
    $('.sql_table tbody tr').each(function() {
        var $row = $(this);
        var rowText = $row.text().toLowerCase();

        // Show/hide row based on search match
        if (rowText.indexOf(searchTerm) === -1) {
            $row.hide();
        } else {
            $row.show();
        }
    });
});
<?php endif; ?>
```

**Comportement:**
- Recherche en temps réel lors de la saisie (event `keyup`)
- Recherche insensible à la casse
- Filtre toutes les colonnes de la ligne
- Masque les lignes qui ne correspondent pas au motif de recherche
- Affiche les lignes correspondantes

#### 3. CSS - Style (lignes 342-367)

```css
/* Style search box in pagination bar - floats right, before navigation */
.pagination-search-box {
    float: right;
    margin-left: 15px;
    margin-right: 5px;
}

.pagination-search-box label {
    font-weight: normal;
    white-space: nowrap;
    color: #333;
    font-size: 13px;
    margin: 0;
    line-height: 24px;
}

.pagination-search-box input {
    margin-left: 0.5em;
    display: inline-block;
    width: 180px;
    border: 1px solid #aaa;
    border-radius: 3px;
    padding: 3px 5px;
    background-color: white;
    font-size: 12px;
}
```

**Apparence:** Intégrée dans la barre de pagination grise avec un style cohérent.

## Comportement

### Mode < 400 entrées
- Utilise DataTables avec recherche native
- Pas de boîte de recherche additionnelle

### Mode ≥ 400 entrées
- Utilise pagination CodeIgniter
- Affiche la boîte de recherche personnalisée
- Filtrage côté client via JavaScript
- Compatible avec la pagination (filtre uniquement la page courante)

## Tests

### Test unitaire HTML
Créé et exécuté avec succès : génération correcte du HTML conditionnel.

### Test manuel
1. Accéder à un compte avec > 400 entrées (ex: compte #23 avec 1937 entrées)
2. Vérifier l'apparence de la boîte de recherche
3. Taper un terme de recherche (ex: "2024")
4. Vérifier que les lignes sont filtrées correctement
5. Effacer la recherche et vérifier que toutes les lignes réapparaissent

## Comptes concernés

Comptes avec plus de 400 entrées dans la base de données actuelle:
- Compte #23: 1937 entrées
- Compte #47: 1210 entrées
- Compte #209: 639 entrées
- Compte #39: 609 entrées
- Compte #77: 556 entrées

## Remarques

- Le filtrage s'applique uniquement aux lignes affichées sur la page courante
- Pour rechercher dans tout le journal, il faut utiliser les filtres du panneau de filtrage
- La boîte de recherche ne modifie pas les données, seulement l'affichage

## Date de modification

16 novembre 2025
