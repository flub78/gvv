# Test Manual - Amélioration des Messages d'Erreur d'Import Markdown

## Résumé des Améliorations

### Problème Initial
Lors de l'import d'un fichier Markdown invalide:
1. Aucun feedback immédiat - l'utilisateur ne voit rien après le téléchargement
2. L'erreur s'affiche plus tard sur la page de liste (via flashdata)
3. Les messages d'erreur sont génériques sans détails sur l'emplacement du problème

### Corrections Implémentées

#### 1. Parser - Messages d'Erreur Détaillés
**Fichier**: `application/libraries/Formation_markdown_parser.php`

**Améliorations**:
- Ajout du numéro de ligne dans tous les messages d'erreur
- Affichage de la ligne problématique dans le message
- Messages en français avec explications claires
- Exemples de syntaxe correcte dans les messages
- Détection des titres H1 dupliqués

**Exemples de messages**:
```
Erreur ligne 6 : Sujet trouvé avant toute leçon
Ligne : ### Sujet avant leçon
Un sujet (###) doit être précédé d'une leçon (##).
```

```
Erreur ligne 15 : Second titre H1 trouvé
Ligne : # Second titre
Un programme ne peut avoir qu'un seul titre principal (# Titre).
Le titre en double est : "Second titre"
Le premier titre était : "Premier titre"
```

#### 2. Controller - Affichage Immédiat des Erreurs
**Fichier**: `application/controllers/Programmes.php`

**Méthode**: `import_from_markdown()`

**Changements**:
- Suppression de l'utilisation de `flashdata` pour les erreurs
- Utilisation de `$data['import_error']` pour afficher l'erreur immédiatement
- Return vers la vue form au lieu de redirect en cas d'erreur
- Inclusion du nom du fichier dans le message d'erreur
- Gestion de tous les cas d'erreur:
  * Upload échoué
  * Fichier vide
  * Erreurs de parsing (avec catch d'exception)
  * Erreurs de validation
  * Erreurs d'insertion en base

#### 3. Vue - Présentation des Erreurs
**Fichier**: `application/views/programmes/form.php`

**Ajouts**:
- Nouvelle section d'affichage pour `import_error`
- Formatage en bloc `<pre>` pour préserver les retours à la ligne
- Style CSS pour améliorer la lisibilité (fond gris, police monospace)
- JavaScript pour basculer automatiquement vers l'onglet "Import" en cas d'erreur
- Icône d'avertissement et message "Erreur d'import Markdown"

## Guide de Test Manuel

### Pré-requis
- GVV installé et accessible (http://gvv.net/)
- Accès en tant qu'administrateur
- Les deux fichiers de test créés:
  * `test_import_invalid.md` - avec erreur de structure
  * `test_import_valid.md` - fichier valide

### Test 1: Erreur de Structure (H3 avant H2)

**Fichier**: `test_import_invalid.md`

**Steps**:
1. Aller sur http://gvv.net/programmes/create
2. Cliquer sur l'onglet "Importer depuis Markdown"
3. Sélectionner le fichier `test_import_invalid.md`
4. Cliquer sur "Importer"

**Résultat Attendu**:
- La page reste sur le formulaire (pas de redirect)
- L'onglet "Import" est actif automatiquement
- Une alerte rouge s'affiche avec:
  * Le titre "Erreur d'import Markdown"
  * Le nom du fichier
  * Le numéro de ligne de l'erreur (ligne 9)
  * La ligne problématique: `### Sujet avant leçon`
  * Une explication claire du problème
  * Un exemple de syntaxe correcte

**Message Attendu**:
```
Erreur d'analyse du fichier 'test_import_invalid.md' :

Erreur ligne 9 : Sujet trouvé avant toute leçon
Ligne : ### Sujet avant leçon
Un sujet (###) doit être précédé d'une leçon (##).

Structure attendue :
# Titre du programme
## Leçon 1
### Sujet 1.1
### Sujet 1.2
## Leçon 2
### Sujet 2.1
```

### Test 2: Import Réussi

**Fichier**: `test_import_valid.md`

**Steps**:
1. Rester sur http://gvv.net/programmes/create
2. Sélectionner le fichier `test_import_valid.md`
3. Cliquer sur "Importer"

**Résultat Attendu**:
- Redirect vers la page de visualisation du programme créé
- Message de succès en vert: "Programme importé avec succès"
- Le programme s'affiche avec:
  * Titre: "Programme PPL Théorique"
  * 2 leçons
  * Chaque leçon a 2 sujets
  * Tous les contenus sont préservés

### Test 3: Fichier Vide

**Steps**:
1. Créer un fichier vide `test_empty.md`
2. Tenter de l'importer

**Résultat Attendu**:
- Erreur affichée immédiatement
- Message: "Le fichier est vide"

### Test 4: Pas de Fichier Sélectionné

**Steps**:
1. Aller sur le formulaire de création
2. Cliquer sur "Importer" sans sélectionner de fichier

**Résultat Attendu**:
- Erreur affichée immédiatement
- Message d'erreur concernant l'upload

### Test 5: Titre H1 Manquant

**Créer**: `test_no_title.md`
```markdown
Description sans titre

## Leçon 1: Test

### 1.1 Sujet test
```

**Résultat Attendu**:
- Message d'erreur avec numéro de ligne
- Explication que le fichier doit commencer par un titre H1

### Test 6: Titres H1 Dupliqués

**Créer**: `test_duplicate_title.md`
```markdown
# Premier titre

Description

# Second titre

Autre description
```

**Résultat Attendu**:
- Message d'erreur indiquant:
  * Le numéro de ligne du second titre
  * Le contenu du titre dupliqué
  * Le contenu du premier titre
  * Explication qu'un seul titre H1 est autorisé

## Vérifications de Syntaxe

```bash
# Vérifier la syntaxe PHP du contrôleur
source setenv.sh && php -l application/controllers/Programmes.php

# Vérifier la syntaxe du parser
source setenv.sh && php -l application/libraries/Formation_markdown_parser.php
```

**Résultat**: Aucune erreur de syntaxe

## Fichiers Modifiés

1. **application/libraries/Formation_markdown_parser.php**
   - Méthode `parse()`: Ajout de tracking de ligne et messages détaillés
   - Méthode `validate()`: Return de string au lieu d'array pour messages

2. **application/controllers/Programmes.php**
   - Méthode `import_from_markdown()`: Réécriture complète pour affichage immédiat

3. **application/views/programmes/form.php**
   - Ajout de la section d'affichage `import_error`
   - JavaScript pour basculer vers l'onglet import

## Notes Techniques

- Tous les changements suivent les conventions GVV existantes
- Compatibilité PHP 7.4 maintenue
- Aucune modification de base de données requise
- Les transactions DB sont toujours utilisées pour garantir l'intégrité
- Format de message compatible avec la coloration syntaxique dans `<pre>`
