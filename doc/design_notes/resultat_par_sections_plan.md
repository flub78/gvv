# Plan d'implémentation: Résultat d'exploitation par sections

## Vue d'ensemble

Création d'une nouvelle page `/comptes/resultat_par_sections` qui affiche les résultats d'exploitation (charges et produits) par sections pour deux années consécutives (année en cours et année précédente).

## Architecture

La fonctionnalité réutilise les composants existants du dashboard et du bilan:
- Méthodes du modèle: `select_par_section()` et `select_charges_et_produits()`
- Modèle sections: `section_list()` pour récupérer les acronymes
- Patterns d'export PDF/CSV similaires à `dashboard()` et `bilan()`

## Structure de données

### Tableau principal

```
                    2025              2024
                    Sections         Sections
Code    Charges          AVI  PLA  GEN  ULM  Total  AVI  PLA  GEN  ULM  Total
-------------------------------------------------------------------------
606     Achats          xx   xx   xx   xx   XXX    xx   xx   xx   xx   XXX
615     Services ext.   xx   xx   xx   xx   XXX    xx   xx   xx   xx   XXX
...

Produits
-------------------------------------------------------------------------
701     Ventes          xx   xx   xx   xx   XXX    xx   xx   xx   xx   XXX
765     Subventions     xx   xx   xx   xx   XXX    xx   xx   xx   xx   XXX
...

        RÉSULTAT        xx   xx   xx   xx   XXX    xx   xx   xx   xx   XXX
```

### Tableau de détail (par compte)

Même structure mais avec le détail des comptes pour un codec spécifique:

```
                    2025              2024
                    Sections         Sections
Code    Libellé         AVI  PLA  GEN  ULM  Total  AVI  PLA  GEN  ULM  Total
-------------------------------------------------------------------------
6151    Compte 1        xx   xx   xx   xx   XXX    xx   xx   xx   xx   XXX
6152    Compte 2        xx   xx   xx   xx   XXX    xx   xx   xx   xx   XXX
...
```

## Implémentation détaillée

### 1. Modèle (application/models/comptes_model.php)

**Méthode: `select_par_section_deux_annees($selection, $balance_date, $factor = 1, $with_sections = true, $html = false)`**

- Récupère les données pour l'année courante en appelant `select_par_section()`
- Calcule la date de l'année précédente (même jour/mois, année - 1)
- Récupère les données pour l'année précédente
- Fusionne les deux tableaux en une seule structure avec colonnes dupliquées
- Structure de retour:
  ```php
  [
      'year' => 2025,
      'year_prev' => 2024,
      'headers' => ['Code', 'Libellé', 'AVI_2025', 'PLA_2025', ...],
      'data' => [...]
  ]
  ```

**Méthode: `select_resultat_par_sections_deux_annees($balance_date, $html = false)`**

- Appelle `select_par_section_deux_annees()` pour les charges (codec >= "6" and codec < "7")
- Appelle `select_par_section_deux_annees()` pour les produits (codec >= "7" and codec < "8")
- Calcule la ligne de résultat (produits - charges) pour chaque section et année
- Retourne un tableau avec:
  ```php
  [
      'charges' => [...],
      'produits' => [...],
      'resultat' => [...]
  ]
  ```

**Méthode: `select_detail_codec_deux_annees($codec, $balance_date, $factor = 1, $html = false)`**

- Similaire à `select_par_section_deux_annees()` mais pour le détail d'un codec
- Sélectionne tous les comptes d'un codec spécifique au lieu des codecs
- Retourne le même format de données avec le détail par compte

### 2. Controller (application/controllers/comptes.php)

**Méthode: `resultat_par_sections($mode = "html")`**

- Récupère l'année courante depuis la session
- Calcule la date de balance (31/12 de l'année courante ou date de balance en session)
- Appelle `$this->gvv_model->select_resultat_par_sections_deux_annees()`
- Prépare les données pour la vue
- Gère les exports selon le mode ('html', 'csv', 'pdf')

**Méthode: `resultat_par_sections_detail($codec, $mode = "html")`**

- Récupère le codec en paramètre
- Récupère l'année et la date de balance
- Appelle `$this->gvv_model->select_detail_codec_deux_annees($codec, ...)`
- Identifie si c'est une charge ou un produit (codec < 7 = charge, codec >= 7 = produit)
- Prépare les données pour la vue
- Gère les exports selon le mode

**Méthode: `csv_resultat_par_sections($data)`**

- Génère l'en-tête CSV avec le nom du club et les dates
- Ajoute les tableaux de charges et produits
- Ajoute la ligne de résultat
- Utilise `csv_file()` pour générer le fichier

**Méthode: `pdf_resultat_par_sections($data)`**

- Utilise la bibliothèque PDF
- Configure la page en paysage (Landscape) pour plus de colonnes
- Calcule les largeurs de colonnes dynamiquement (similaire à `pdf_dashboard()`)
- Affiche les sections Charges, Produits et Résultat
- Utilise `$pdf->table()` pour le rendu

**Méthode: `csv_resultat_par_sections_detail($data, $codec)`**

- Similaire à `csv_resultat_par_sections()` mais pour le détail
- Titre incluant le codec et son libellé

**Méthode: `pdf_resultat_par_sections_detail($data, $codec)`**

- Similaire à `pdf_resultat_par_sections()` mais pour le détail

### 3. Vues (application/views/comptes/)

**Fichier: `bs_resultat_par_sectionsView.php`**

Structure:
```php
<div id="body" class="body container-fluid">
    <h2>Résultat d'exploitation par sections</h2>

    <!-- Sélecteur d'année -->
    <?= $year_selector ?>

    <!-- Sélecteur de date de balance -->
    <input type="text" name="balance_date" id="balance_date"
           value="<?= $balance_date ?>" class="datepicker" />

    <h3>Charges</h3>
    <?php
    $table = new DataTable([
        'title' => "",
        'values' => $charges,
        'controller' => $controller,
        'class' => "sql_table table",
        'align' => [...]  // 'left' pour code/nom, 'right' pour montants
    ]);
    $table->display();
    ?>

    <h3>Produits</h3>
    <?php
    $table = new DataTable([...]);
    $table->display();
    ?>

    <h3>Résultat</h3>
    <?php
    $table = new DataTable([...]);
    $table->display();
    ?>

    <!-- Boutons d'export -->
    <?php
    $bar = [
        ['label' => "Excel", 'url' => "comptes/resultat_par_sections/csv"],
        ['label' => "Pdf", 'url' => "comptes/resultat_par_sections/pdf"]
    ];
    echo button_bar4($bar);
    ?>
</div>
```

**Fichier: `bs_resultat_par_sections_detailView.php`**

Similaire à la vue principale mais:
- Titre incluant le codec et son libellé
- Affiche soit les charges soit les produits selon le codec
- Pas de section résultat (détail d'un seul type)
- Boutons d'export avec le codec en paramètre

### 4. Routes (application/config/routes.php)

Aucune modification nécessaire. Les routes par défaut de CodeIgniter gèrent:
- `/comptes/resultat_par_sections` → `Comptes::resultat_par_sections()`
- `/comptes/resultat_par_sections/csv` → `Comptes::resultat_par_sections('csv')`
- `/comptes/resultat_par_sections/pdf` → `Comptes::resultat_par_sections('pdf')`
- `/comptes/resultat_par_sections_detail/606` → `Comptes::resultat_par_sections_detail('606')`
- `/comptes/resultat_par_sections_detail/606/csv` → `Comptes::resultat_par_sections_detail('606', 'csv')`
- `/comptes/resultat_par_sections_detail/606/pdf` → `Comptes::resultat_par_sections_detail('606', 'pdf')`

### 5. Fichiers de langue

**application/language/french/comptes_lang.php**
```php
$lang['gvv_comptes_title_resultat_par_sections'] = "Résultat d'exploitation par sections";
$lang['gvv_comptes_title_resultat_par_sections_detail'] = "Détail du résultat par sections - %s";
$lang['comptes_label_charges'] = "Charges";
$lang['comptes_label_produits'] = "Produits";
```

**application/language/english/comptes_lang.php**
```php
$lang['gvv_comptes_title_resultat_par_sections'] = "Operating result by sections";
$lang['gvv_comptes_title_resultat_par_sections_detail'] = "Result details by sections - %s";
$lang['comptes_label_charges'] = "Expenses";
$lang['comptes_label_produits'] = "Income";
```

**application/language/dutch/comptes_lang.php**
```php
$lang['gvv_comptes_title_resultat_par_sections'] = "Bedrijfsresultaat per secties";
$lang['gvv_comptes_title_resultat_par_sections_detail'] = "Resultaatdetails per secties - %s";
$lang['comptes_label_charges'] = "Kosten";
$lang['comptes_label_produits'] = "Inkomsten";
```

### 6. Tests

**Fichier: `application/tests/controllers/ComptesResultatParSectionsTest.php`**

Tests unitaires pour:
- `test_resultat_par_sections_html()`: Vérifie l'affichage HTML
- `test_resultat_par_sections_csv()`: Vérifie la génération CSV
- `test_resultat_par_sections_pdf()`: Vérifie la génération PDF
- `test_resultat_par_sections_detail_html()`: Vérifie le détail HTML
- `test_resultat_par_sections_detail_csv()`: Vérifie le détail CSV
- `test_resultat_par_sections_detail_pdf()`: Vérifie le détail PDF

**Fichier: `application/tests/unit/ComptesModelResultatTest.php`**

Tests pour les méthodes du modèle:
- `test_select_par_section_deux_annees()`: Vérifie la structure des données
- `test_select_resultat_par_sections_deux_annees()`: Vérifie les calculs
- `test_select_detail_codec_deux_annees()`: Vérifie le détail

**Fichier: `playwright/tests/resultat-par-sections.spec.ts`**

Tests end-to-end:
- Navigation vers la page
- Vérification de l'affichage des tableaux
- Clic sur un lien de codec pour voir le détail
- Export CSV et PDF
- Changement d'année et de date de balance

## Ordre d'implémentation

1. **Modèle** (`comptes_model.php`):
   - Méthode `select_par_section_deux_annees()`
   - Méthode `select_resultat_par_sections_deux_annees()`
   - Méthode `select_detail_codec_deux_annees()`

2. **Fichiers de langue**:
   - Ajout des clés dans `french/comptes_lang.php`
   - Ajout des clés dans `english/comptes_lang.php`
   - Ajout des clés dans `dutch/comptes_lang.php`

3. **Controller** (`comptes.php`):
   - Méthode `resultat_par_sections()`
   - Méthode `resultat_par_sections_detail()`
   - Méthode `csv_resultat_par_sections()`
   - Méthode `pdf_resultat_par_sections()`
   - Méthode `csv_resultat_par_sections_detail()`
   - Méthode `pdf_resultat_par_sections_detail()`

4. **Vues**:
   - `bs_resultat_par_sectionsView.php`
   - `bs_resultat_par_sections_detailView.php`

5. **Tests**:
   - Tests unitaires du modèle
   - Tests du controller
   - Tests end-to-end Playwright

6. **Validation**:
   - Test manuel sur http://gvv.net/comptes/resultat_par_sections
   - Vérification des exports CSV et PDF
   - Test du détail par codec
   - Vérification du changement d'année

## Points d'attention

1. **Gestion des années**: S'assurer que la date de l'année précédente est calculée correctement (même jour/mois, année - 1)

2. **Performance**: Les requêtes sont doublées (2 années). Optimiser si nécessaire en regroupant les requêtes.

3. **Sections dynamiques**: Le nombre de sections est variable. Les largeurs de colonnes doivent s'adapter dynamiquement.

4. **Liens dans les exports**: Les liens vers le détail ne fonctionnent que dans la version HTML. Dans les exports CSV/PDF, afficher seulement le code sans lien.

5. **Formatage des montants**: Utiliser `euro()` pour HTML et `number_format()` pour CSV avec virgule comme séparateur décimal.

6. **Alignement des colonnes**:
   - Code et libellé: alignement à gauche
   - Montants: alignement à droite
   - Total: mise en gras si possible

7. **Réutilisation du code existant**:
   - Pattern similaire à `dashboard()` pour la structure
   - Pattern similaire à `bilan()` pour les deux années
   - Utilisation de `select_par_section()` existant

## Estimation

- **Modèle**: 2-3 heures (3 méthodes + tests)
- **Controller**: 2-3 heures (6 méthodes)
- **Vues**: 1-2 heures (2 vues)
- **Fichiers de langue**: 0.5 heure
- **Tests**: 2-3 heures (unitaires + end-to-end)
- **Validation**: 1 heure

**Total estimé**: 8-12 heures de développement

## Dépendances

- Aucune modification de base de données nécessaire
- Aucune nouvelle bibliothèque externe
- Réutilisation complète du code existant

## Migration

Aucune migration de base de données nécessaire.
