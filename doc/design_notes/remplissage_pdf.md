# Design Notes — Remplissage de Formulaires PDF

Date : 3 février 2026

## Contexte

L'objectif est de pouvoir remplir des formulaires PDF officiels à partir des données stockées en base de données GVV. Le mécanisme doit être générique pour supporter différents formulaires PDF sans développement spécifique.

## Workflow général

Le système fonctionne en trois étapes distinctes :

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              WORKFLOW                                        │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ÉTAPE 1: ANALYSE              ÉTAPE 2: MAPPING           ÉTAPE 3: GÉNÉRATION
│  ─────────────────             ─────────────────          ────────────────── │
│                                                                              │
│  ┌──────────────┐             ┌──────────────┐           ┌──────────────┐   │
│  │ Upload PDF   │             │ Interface de │           │ Sélection    │   │
│  │ formulaire   │             │ mapping      │           │ des données  │   │
│  └──────┬───────┘             │ manuel       │           │ (pilote...)  │   │
│         │                     └──────┬───────┘           └──────┬───────┘   │
│         ▼                            │                          │           │
│  ┌──────────────┐                    │                          ▼           │
│  │ Extraction   │                    │                   ┌──────────────┐   │
│  │ automatique  │                    │                   │ Remplissage  │   │
│  │ des champs   │                    │                   │ automatique  │   │
│  └──────┬───────┘                    │                   └──────┬───────┘   │
│         │                            │                          │           │
│         ▼                            ▼                          ▼           │
│  ┌──────────────┐             ┌──────────────┐           ┌──────────────┐   │
│  │ Liste des    │────────────▶│ Mapping      │──────────▶│ PDF rempli   │   │
│  │ champs       │             │ champ↔BDD    │           │ + archivage  │   │
│  └──────────────┘             └──────────────┘           └──────────────┘   │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Étape 1 : Upload et analyse du formulaire PDF

**Objectif** : Permettre à l'administrateur de télécharger un formulaire PDF et d'obtenir automatiquement la liste des champs éditables.

**Entrée** : Fichier PDF (formulaire AcroForm)

**Sortie** : Liste des champs avec :
- Nom du champ
- Type (texte, checkbox, radio, liste déroulante)
- Valeur par défaut (si présente)

**Outil** : Script Python utilisant PyPDF2 (déjà installé)

### Étape 2 : Mapping manuel

**Objectif** : Permettre à l'administrateur de définir la correspondance entre chaque champ PDF et une source de données.

**Interface** : Tableau éditable avec :
- Colonne 1 : Nom du champ PDF (lecture seule)
- Colonne 2 : Type de source (table, configuration, constante, expression)
- Colonne 3 : Valeur source (ex: `membres.mnom`, `configuration.club_name`, `"France"`)
- Colonne 4 : Format optionnel (ex: `date:d/m/Y`)
- Colonne 5 : Contexte (quel enregistrement : candidat, instructeur, etc.)

**Types de sources** :
- `table` : Colonne d'une table (ex: `membres.mnom`)
- `config` : Valeur de configuration (ex: `club_name`)
- `constant` : Valeur fixe (ex: `"France"`)
- `expression` : Expression PHP/SQL (ex: `CONCAT(membres.mnom, ' ', membres.mprenom)`)
- `date` : Date courante avec format

### Étape 3 : Génération et archivage

**Objectif** : Générer un PDF rempli à partir d'un template mappé et de données sélectionnées.

**Entrées** :
- Template PDF avec son mapping
- Sélection des enregistrements (ex: pilote candidat, instructeur)

**Sorties** :
- PDF rempli téléchargeable
- Archivage optionnel dans le système documentaire

## Architecture technique

### Modèle de données

```sql
-- Table des templates PDF
CREATE TABLE pdf_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    nom VARCHAR(255) NOT NULL,
    description TEXT,
    fichier VARCHAR(255) NOT NULL,        -- Chemin vers le PDF modèle
    champs JSON,                           -- Cache des champs extraits
    contextes JSON,                        -- Contextes requis (candidat, instructeur...)
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
);

-- Table des mappings champ PDF → source de données
CREATE TABLE pdf_template_mappings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,
    champ_pdf VARCHAR(255) NOT NULL,       -- Nom du champ dans le PDF
    source_type ENUM('table', 'config', 'constant', 'expression', 'date') NOT NULL,
    source_value VARCHAR(255) NOT NULL,    -- table.colonne, clé config, valeur, expression
    format VARCHAR(50),                    -- Format de sortie (date, nombre...)
    contexte VARCHAR(50),                  -- Quel enregistrement utiliser (candidat, instructeur...)
    FOREIGN KEY (template_id) REFERENCES pdf_templates(id) ON DELETE CASCADE,
    UNIQUE KEY (template_id, champ_pdf)
);

-- Les PDF générés sont archivés via la table `archived_documents`
-- avec le type de document `formulaire_pdf` (scope: pilot).
-- Le nom du template est inclus dans le champ `description`.
```

### Composants

#### Script Python : `bin/pdf_forms.py`

```python
#!/usr/bin/env python3
"""
Utilitaire pour l'extraction des champs et le remplissage de formulaires PDF.
Usage:
  pdf_forms.py extract --pdf file.pdf [--json_fields fields.json]
  pdf_forms.py fill --pdf file.pdf --json_data data.json [--json_fields fields.json] [--output output.pdf]
"""
```

Points importants (état courant) :
- Un seul script de référence : `bin/pdf_forms.py`.
- Le remplissage des champs AcroForm (texte, checkbox, radio) est géré par `fill`.
- Les signatures visuelles peuvent être ajoutées via le bloc `images` dans `json_data`.
- Les checkboxes sont forcées avec l'état d'apparence réel du PDF (`/On` ou `/Yes` selon le formulaire).

Exemple de `json_data` avec signature:

```json
{
  "fields": {
    "Nom de famille 1": "ELEVE TEST",
    "Prénoms": "Andre"
  },
  "images": [
    {
      "pdf": "signature_overlay.pdf",
      "page": 0,
      "x": 200,
      "y": 100,
      "width": 150,
      "height": 50
    }
  ]
}
```

#### Bibliothèque PHP : `application/libraries/Pdf_form_filler.php`

Responsabilités :
- `extract_fields($pdf_path)` : Appelle le script Python pour extraire les champs
- `get_mapping($template_id)` : Charge le mapping depuis la base
- `collect_data($mapping, $contextes)` : Collecte les données selon le mapping
- `fill($template_id, $contextes, $output_path)` : Génère le PDF rempli
- `archive($pdf_path, $template, $pilote_login)` : Archive via `archived_documents_model->create_document()` (type `formulaire_pdf`)

#### Contrôleur PHP : `application/controllers/Pdf_forms.php`

Vues :
- `index` : Liste des templates disponibles
- `upload` : Upload et analyse d'un nouveau template
- `mapping/$id` : Interface de mapping pour un template
- `generate/$id` : Formulaire de sélection des données, génération et archivage via `archived_documents`

### Outils disponibles sur le système

- **PyPDF2** : Installé (`python3-pypdf2`), lecture/écriture de formulaires AcroForm
- **qpdf** : Installé, manipulation bas niveau des PDF
- **TCPDF** : Disponible dans le projet (génération, pas remplissage)
- **Pillow (optionnel)** : requis uniquement si `images[].file` pointe vers PNG/JPEG

Contrainte serveur de production (sans droits root) :
- Le chemin recommandé pour la signature est `images[].pdf` (overlay PDF), qui ne dépend que de PyPDF2.
- Le chemin `images[].file` (PNG/JPEG) reste possible si Pillow est présent dans l'environnement Python (venv).
- Aucune dépendance ImageMagick/`convert` n'est requise.

---

## Exemple de cas d'utilisation : Formulaire 134i-Formlic

Le formulaire DGAC `134i-Formlic` est une attestation de début de formation au brevet et licence ULM.

### Champs extraits du PDF

Le formulaire `134iFormlic.pdf` est un formulaire AcroForm avec les champs suivants :

| Champ PDF | Type | Description |
|-----------|------|-------------|
| `Nom de famille 1` | Texte | Nom du candidat |
| `Nom dusage Si différent de 1` | Texte | Nom d'usage |
| `Prénoms` | Texte | Prénom(s) du candidat |
| `Date de naissance` | Texte | Date de naissance |
| `Lieu de naissance` | Texte | Lieu de naissance |
| `Adresse` | Texte | Adresse postale |
| `Commune` | Texte | Ville |
| `Code postal` | Texte | Code postal |
| `Téléphones` | Texte | Numéro(s) de téléphone |
| `Courriel` | Texte | Email |
| `N licence Si applicable` | Texte | Numéro de licence existante |
| `Pays de résidence` | Texte | Pays |
| `Nom de famille` | Texte | Nom de l'instructeur |
| `Prénom` | Texte | Prénom de l'instructeur |
| `N Licence` | Texte | N° licence instructeur |
| `Date de fin de validité` | Texte | Validité licence instructeur |
| `N Qualification dinstructeur` | Texte | N° qualification |
| `Date de fin de validité_2` | Texte | Validité qualification |
| `Aéroclub  Association` | Texte | Nom du club |
| `Fait à` | Texte | Lieu de signature |
| `Le` | Texte | Date de signature |
| `CopieScan rectoverso de la pièce didentité` | Checkbox | Case à cocher |

### Mapping proposé avec les données GVV

**Contextes requis** : `candidat` (membre), `instructeur` (membre)

#### Section Candidat (contexte: candidat)

| Champ PDF | Source | Notes |
|-----------|--------|-------|
| `Nom de famille 1` | `table:membres.mnom` | |
| `Nom dusage Si différent de 1` | - | Non stocké actuellement |
| `Prénoms` | `table:membres.mprenom` | |
| `Date de naissance` | `table:membres.mdaten` | Format: `d/m/Y` |
| `Lieu de naissance` | `table:membres.place_of_birth` | |
| `Adresse` | `table:membres.madresse` | |
| `Commune` | `table:membres.ville` | |
| `Code postal` | `table:membres.cp` | |
| `Téléphones` | `table:membres.mtelm` | |
| `Courriel` | `table:membres.memail` | |
| `N licence Si applicable` | `table:membres.licfed` | |
| `Pays de résidence` | `table:membres.pays` | Défaut: "France" |

#### Section Instructeur (contexte: instructeur)

| Champ PDF | Source | Notes |
|-----------|--------|-------|
| `Nom de famille` | `table:membres.mnom` | |
| `Prénom` | `table:membres.mprenom` | |
| `N Licence` | À définir | Nouveau champ requis |
| `Date de fin de validité` | À définir | Nouveau champ requis |
| `N Qualification dinstructeur` | À définir | Nouveau champ requis |
| `Date de fin de validité_2` | À définir | Nouveau champ requis |
| `Aéroclub  Association` | `config:club_name` | |

#### Section Validation (sans contexte)

| Champ PDF | Source | Notes |
|-----------|--------|-------|
| `Fait à` | `config:club_city` | |
| `Le` | `date:d/m/Y` | Date courante |

### Données manquantes pour ce formulaire

Pour compléter le mapping, il faudrait ajouter des champs pour les qualifications instructeur :

- `licence_numero` : Numéro de licence
- `licence_validite` : Date de validité de la licence
- `qualification_numero` : Numéro de qualification instructeur
- `qualification_validite` : Date de validité de la qualification

Ces champs pourraient être ajoutés à la table `membres` ou dans une table dédiée `qualifications_instructeur`.

---

## Considérations techniques

### Format des dates

Les dates doivent être formatées selon le format attendu par le formulaire (généralement `d/m/Y` pour les formulaires français).

### Encodage

Les caractères accentués doivent être correctement gérés (UTF-8 vers PDF).

### Sécurité

- Validation des fichiers PDF uploadés (type MIME, taille)
- Contrôle d'accès : seuls les administrateurs peuvent créer/modifier les templates
- Contrôle d'accès aux données des pilotes lors de la génération
- Nettoyage des fichiers temporaires

### Intégration avec le module archived_documents

Les PDF générés sont archivés directement via `archived_documents_model->create_document()` :
- Type de document : `formulaire_pdf` (créé dans `document_types`, scope `pilot`)
- Association au pilote concerné via `pilot_login`
- Description incluant le nom du template utilisé
- Stockage dans `uploads/documents/pilots/{login}/formulaire_pdf/`
- L'historique et le re-téléchargement passent par le module `archived_documents`

---

## Nommage des champs de formulaire dans LibreOffice

Pour remplir un PDF avec précision, il faut cibler les champs par leur nom technique, pas par position XY.

### Procédure

1. Activer la barre des contrôles de formulaire :
  - Affichage > Barres d'outils > Contrôles de formulaire
2. Activer le mode Ébauche (Design Mode)
3. Créer ou sélectionner un champ (texte, case à cocher, liste, etc.)
4. Ouvrir les propriétés du contrôle :
  - Clic droit > Contrôle...
5. Renseigner la propriété `Nom` (onglet Général)
6. Exporter le document en cochant "Créer un formulaire PDF"

### Convention de nommage recommandée

- Utiliser `snake_case`
- Éviter les espaces et les accents
- Utiliser des noms stables dans le temps
- Renommer un champ uniquement avec mise à jour simultanée du script de remplissage

Exemples de noms :
- `nom_pilote`
- `date_vol`
- `signature_png`

### Cas particulier des boutons radio

- Les options d'un même groupe partagent un même nom de groupe
- Chaque option doit avoir une valeur distincte
- Le script choisit l'option en envoyant la valeur attendue

### Signature PNG

- Deux modes d'insertion sont supportés :
  - `images[].pdf` : overlay PDF prêt à l'emploi (mode recommandé en production)
  - `images[].file` : image PNG/JPEG (nécessite Pillow)
- Comportement de l'overlay :
  - La position et la taille finales ne sont pas figées dans l'overlay PDF.
  - Le placement final est appliqué au moment du `fill` via `x`, `y`, `width`, `height`.
  - Un même overlay peut donc être réutilisé pour plusieurs emplacements/signatures, en changeant uniquement les coordonnées et dimensions dans le JSON.
- Coordonnées :
  - Unité : point PDF (`1 pt = 1/72 inch`)
  - Origine : coin bas-gauche de la page
  - Paramètres : `x`, `y`, `width`, `height`
- Bonnes pratiques de taille :
  - Zone de signature typique : `width=120..180`, `height=35..60` (en points)
  - Si source raster : viser au moins 2x la taille d'affichage (ex: 300x100 px pour ~150x50 pt)
- Cette signature est visuelle uniquement.
- Si une signature légale forte est requise, ajouter ensuite une signature numérique du PDF final.

### Test manuel de l'insertion d'image

#### Préparation du fichier JSON

1. Copier `doc/prds/reference/pdf_data_134i.sample.json` vers un fichier de test local (ex: `/tmp/pdf_data_img_test.json`).
2. Ajouter un bloc `images` dans le JSON.

Exemple (mode overlay PDF) :

```json
{
  "fields": {
    "Nom de famille 1": "ELEVE TEST",
    "Prénoms": "Andre"
  },
  "images": [
    {
      "pdf": "/chemin/vers/signature_overlay.pdf",
      "page": 0,
      "x": 200,
      "y": 100,
      "width": 150,
      "height": 50
    }
  ]
}
```

Exemple (mode PNG/JPEG) :

```json
{
  "fields": {
    "Nom de famille 1": "ELEVE TEST",
    "Prénoms": "Andre"
  },
  "images": [
    {
      "file": "/chemin/vers/signature.png",
      "page": 0,
      "x": 200,
      "y": 100,
      "width": 150,
      "height": 50
    }
  ]
}
```

#### Commande de génération

```bash
python3 bin/pdf_forms.py fill \
  --pdf doc/design_notes/documents/134iFormlic.pdf \
  --json_fields doc/prds/reference/pdf_fields_134i.json \
  --json_data /tmp/pdf_data_img_test.json \
  --output /tmp/134i_with_signature.pdf
```

#### Ajustement rapide des coordonnées

- Premier essai recommandé : `x=50`, `y=50`, `width=180`, `height=60`.
- Si la signature est trop à droite, diminuer `x`.
- Si la signature est trop haute, diminuer `y`.
- Si la signature est trop grande, diminuer `width` et `height`.
- Travailler par pas de 20 points pour converger rapidement.

#### Dépannage (troubleshooting)

- Erreur `fichier introuvable` :
  - Vérifier le chemin de `--pdf`, `--json_data` et `images[].pdf`/`images[].file`.
  - Utiliser des chemins absolus pour le premier test.

- Erreur `images[i].page hors limites` :
  - La numérotation commence à 0.
  - Vérifier le nombre de pages du PDF source.

- Erreur liée à Pillow (`Pillow est requis...`) :
  - Cette erreur ne concerne que le mode `images[].file` (PNG/JPEG).
  - En production minimale, basculer vers `images[].pdf`.

- Signature invisible ou hors zone :
  - Commencer avec `x=50`, `y=50`, `width=180`, `height=60`.
  - Ajuster ensuite par pas de 20 points.

- Signature déformée :
  - Conserver un ratio `width/height` proche de l'image d'origine.
  - Préparer une image source avec peu de marges blanches.

- Rectangle blanc autour de la signature :
  - Préférer un PNG avec transparence.
  - Si conversion en PDF, générer un overlay recadré au plus près du tracé.

- Erreur `champs inconnus` :
  - Vérifier les noms exacts avec `extract`.
  - Contrôler la cohérence avec `--json_fields` quand cette option est utilisée.

### Vérification

Après export, vérifier les noms de champs avec un outil d'inspection (`pdftk`, `qpdf`, `pypdf`) pour s'assurer que les noms du PDF correspondent exactement aux clés utilisées par le script.
