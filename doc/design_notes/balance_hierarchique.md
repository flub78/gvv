# Balance Hiérarchique Développable - Design Document

## Vue d'ensemble

La balance hiérarchique est une nouvelle vue fusionnée qui combine les balances générale et détaillée en une seule page avec des sections développables/réductibles. Cela améliore l'expérience utilisateur en évitant la navigation entre deux pages distinctes.

## URL d'accès

```
http://gvv.net/comptes/balance
http://gvv.net/comptes/balance/{codec}
http://gvv.net/comptes/balance/{codec}/{codec2}
```

## Fonctionnalités

### 1. Affichage fusionné
- **Lignes générales (sections)** : Affichage des comptes généraux regroupés par codec
- **Lignes détaillées** : Sous chaque section générale, affichage de tous les comptes individuels correspondants
- **État initial** : Toutes les sections sont réduites par défaut

### 2. Interaction utilisateur
- **Clic sur section générale** : Développe/réduit les comptes détaillés de cette section
- **Bouton "Tout développer"** : Développe toutes les sections en une fois
- **Bouton "Tout réduire"** : Réduit toutes les sections en une fois
- **Indicateur visuel** : Icône flèche (▶) qui pivote lors du développement

### 3. Style visuel
- **Sections générales** :
  - Fond bleu clair (#e7f1ff)
  - Bordure gauche bleue de 4px
  - Police en gras (font-weight: 600)
  - Curseur pointer pour indiquer cliquabilité
  - Effet hover avec fond plus foncé (#cfe2ff)

- **Lignes détaillées** :
  - Fond blanc
  - Indentation visuelle (padding-left: 2rem)
  - Cachées par défaut (display: none)
  - Affichées avec classe 'show' (display: table-row)

### 4. Filtres
Les filtres existants restent disponibles :
- **Filtre de solde** : Tous / Débiteur / Créditeur / Solde nul
- **Filtre comptes masqués** : Tous / Non masqués / Masqués uniquement
- **Note** : Les radioboxes "Détaillée/Générale" sont retirées car cette vue fusionne les deux

### 5. Actions disponibles
Pour les utilisateurs avec droits de modification (trésorier) :
- **Créer un compte** : Bouton en haut du tableau
- **Éditer un compte** : Icône sur les lignes détaillées uniquement
- **Supprimer un compte** : Icône sur les lignes détaillées uniquement

### 6. Exports
Deux nouvelles fonctions d'export qui respectent la structure hiérarchique :

#### CSV : `balance_hierarchical_csv()`
- Exporte toutes les données affichées (générales + détaillées)
- Indentation visuelle avec espaces pour les comptes détaillés
- Colonnes : codec, nom, section_name, solde_debit, solde_credit

#### PDF : `balance_hierarchical_pdf()`
- Même structure que le CSV
- Utilise la bibliothèque TCPDF existante
- Format A4 portrait avec colonnes adaptées

## Architecture technique

### Contrôleur : `comptes::balance()`

```php
function balance($codec = "", $codec2 = "")
```

**Logique** :
1. Récupération de la balance générale via `select_page_general()`
2. Récupération de la balance détaillée via `select_page()`
3. Organisation des comptes détaillés par codec dans un tableau associatif
4. Fusion des deux résultats en marquant chaque ligne :
   - `is_general` = true pour les lignes générales
   - `is_detail` = true pour les lignes détaillées
   - `parent_codec` = codec parent pour les lignes détaillées

### Vue : `bs_balanceView.php`

**Composants** :
1. **En-tête** : Titre, date de balance, filtre accordéon
2. **Boutons d'action** : Créer, Tout développer, Tout réduire
3. **Tableau HTML manuel** : 
   - Thead avec colonnes : codec, nom, section, solde débit, solde crédit, actions
   - Tbody avec lignes générales et détaillées
   - Tfoot avec totaux

4. **JavaScript** :
   - `toggleCodec(codec)` : Développe/réduit une section spécifique
   - Event listeners pour boutons "Tout développer" / "Tout réduire"
   - Intégration DataTables pour recherche et tri

### Exports

#### `balance_hierarchical_csv($codec, $codec2)`
- Réutilise la logique de fusion
- Indente les noms avec 2 espaces pour les détails
- Appelle `gvvmetadata->csv_table()`

#### `balance_hierarchical_pdf($codec, $codec2)`
- Réutilise la logique de fusion
- Indente les noms avec 2 espaces pour les détails
- Appelle `gvvmetadata->pdf_table()`

## Multi-langue

Nouvelles clés ajoutées dans les 3 langues (français, anglais, néerlandais) :

```php
$lang['gvv_comptes_title_hierarchical_balance'] = "Balance hiérarchique"
$lang['gvv_comptes_expand_all'] = "Tout développer"
$lang['gvv_comptes_collapse_all'] = "Tout réduire"
```

## Compatibilité

- **Pages existantes conservées** : `/comptes/general` et `/comptes/detail` restent fonctionnelles
- **Exports existants conservés** : `balance_csv()` et `balance_pdf()` inchangés
- **Nouveaux exports distincts** : `balance_hierarchical_csv()` et `balance_hierarchical_pdf()`

## Avantages UX

1. **Navigation simplifiée** : Plus besoin de cliquer sur les liens pour voir les détails
2. **Vue d'ensemble** : Vision globale et détaillée en une seule page
3. **Performance** : Pas de rechargement de page lors de l'expansion
4. **Filtrage intelligent** : Recherche DataTables fonctionne sur toutes les lignes
5. **Accessibilité** : Indicateurs visuels clairs et interactions intuitives

## Limitations connues

1. **DataTables** : Configuré avec `paging: false` pour éviter les problèmes avec les lignes cachées
2. **Recherche** : La recherche DataTables peut afficher des lignes détaillées même si leur parent est réduit (comportement acceptable)
3. **Tri** : Le tri par colonnes peut mélanger sections générales et détails (désactivable si nécessaire)

## Tests recommandés

1. Vérifier l'affichage avec différents filtres de solde
2. Tester le développement/réduction de chaque section
3. Vérifier les boutons "Tout développer"/"Tout réduire"
4. Tester les exports CSV et PDF
5. Vérifier les droits d'édition (trésorier vs lecteur)
6. Tester avec des comptes masqués
7. Vérifier les 3 langues (FR, EN, NL)
