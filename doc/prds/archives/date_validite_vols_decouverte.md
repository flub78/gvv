# PRD : Date de validité indépendante pour les vols de découverte

## Contexte

Actuellement, la date de validité des vols de découverte est calculée automatiquement en ajoutant un an à la date de vente. Cette approche pose un problème : si on modifie la date de vente (par exemple pour corriger une erreur de saisie), la date de validité change également, ce qui peut déplacer des vols d'une année à l'autre dans les pages de suivi.

**Problème rencontré** : Un vol vendu en 2024 dont on corrige la date de vente pour la déplacer en 2025 se retrouve affiché sur la page 2025 au lieu de rester sur la page 2024 où il a été initialement vendu.

## Objectif

Permettre de gérer indépendamment la date de vente et la date de validité d'un vol de découverte, tout en conservant un comportement par défaut cohérent (validité = date de vente + 1 an).

## Exigences fonctionnelles

### 1. Ajout du champ date_validite

- Ajouter une colonne `date_validite` (type DATE, nullable) à la table `vols_decouverte`
- Le champ doit être visible et éditable dans le formulaire de création/modification
- Le champ peut rester vide (NULL)

### 2. Comportement pour les vols existants

- Tous les vols de découverte existants doivent conserver `date_validite = NULL`
- Le système doit continuer à calculer la validité à partir de `date_vente + 1 an` quand `date_validite` est NULL
- Aucune migration de données n'est nécessaire

### 3. Comportement lors de la création

- La `date_vente` doit être initialisée avec la date courante par défaut
- La `date_validite` doit rester NULL par défaut
- L'utilisateur peut saisir les deux dates indépendamment dès la création

### 4. Comportement lors de la modification

- **Si `date_validite` est NULL** :
  - Lors de la modification de `date_vente`, proposer automatiquement `date_validite = date_vente + 1 an` dans le formulaire
  - L'utilisateur peut accepter cette valeur ou la modifier
  - Si l'utilisateur laisse `date_validite` vide, elle reste NULL

- **Si `date_validite` est déjà définie** :
  - Les deux dates sont modifiables indépendamment
  - Aucun recalcul automatique n'est effectué

### 5. Affichage et filtrage

- Le champ `date_validite` doit apparaître dans :
  - Le formulaire de création/modification
  - La vue tableau (liste des vols)
  - Les exports CSV/PDF

- Les filtres "À faire" et "Expirés" doivent utiliser :
  - `date_validite` si elle est définie
  - Sinon `date_vente + 1 an` (comportement actuel)

## Règles métier

1. **Rétrocompatibilité** : Les vols existants avec `date_validite = NULL` continuent de fonctionner comme avant
2. **Indépendance** : Une fois `date_validite` définie, elle ne change plus automatiquement
3. **Correction d'erreurs** : On peut corriger `date_vente` sans affecter `date_validite`
4. **Flexibilité** : On peut définir une date de validité différente de `date_vente + 1 an` si nécessaire

## Impacts techniques

### Base de données
- Migration pour ajouter la colonne `date_validite` (DATE, NULL)

### Modèle (`vols_decouverte_model.php`)
- Modifier `select_page()` pour utiliser `date_validite` si elle existe, sinon calculer à partir de `date_vente`
- Ajouter `date_validite` dans la liste des champs sélectionnés

### Contrôleur (`vols_decouverte.php`)
- Modifier `form_static_element()` pour initialiser `date_vente` avec la date courante à la création
- Ajouter la logique de suggestion de `date_validite` lors de l'édition

### Métadonnées (`Gvvmetadata.php`)
- Le champ `validite` existe déjà dans les métadonnées (ligne 794), il est déjà configuré comme type 'date'

### Vues
- Le formulaire (`bs_formView.php`) affichera automatiquement le champ via les métadonnées
- La vue tableau (`bs_tableView.php`) affichera automatiquement le champ via `$this->gvvmetadata->table()`

### Langues
- Les traductions existent déjà pour le champ `validite` dans les trois langues (français, anglais, néerlandais)

## Non-objectifs

- Pas de migration de données (les `date_validite` restent NULL pour les vols existants)
- Pas de modification de l'interface utilisateur au-delà de l'ajout du champ
- Pas de modification des règles de numérotation des vols de découverte

## Critères d'acceptation

1. La colonne `date_validite` existe dans la base et accepte NULL
2. Les vols existants ont `date_validite = NULL` et continuent de fonctionner
3. À la création, `date_vente` est initialisée avec la date courante
4. Lors de la modification d'un vol avec `date_validite = NULL`, le système suggère `date_vente + 1 an`
5. Les deux dates peuvent être modifiées indépendamment une fois définies
6. Le champ apparaît dans le formulaire, la liste et les exports
7. Les filtres utilisent `date_validite` si elle existe, sinon `date_vente + 1 an`
8. Les tests passent avec succès
