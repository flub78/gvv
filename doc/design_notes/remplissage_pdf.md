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

-- Table des PDF générés (archivage)
CREATE TABLE pdf_generated (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,
    generated_by VARCHAR(25) NOT NULL,     -- Utilisateur qui a généré
    generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    contexte_data JSON,                    -- Données utilisées (pilote_id, instructeur_id...)
    fichier VARCHAR(255) NOT NULL,         -- Chemin vers le PDF généré
    archived TINYINT(1) DEFAULT 0,         -- Archivé dans le système documentaire
    attachment_id INT,                     -- Lien vers attachments si archivé
    FOREIGN KEY (template_id) REFERENCES pdf_templates(id)
);
```

### Composants

#### Script Python : `bin/pdf_forms.py`

```python
#!/usr/bin/env python3
"""
Utilitaire pour l'extraction des champs et le remplissage de formulaires PDF.
Usage:
  pdf_forms.py extract <pdf_file>          # Liste les champs au format JSON
  pdf_forms.py fill <pdf_file> <output> <json_data>  # Remplit le PDF
"""
```

#### Bibliothèque PHP : `application/libraries/Pdf_form_filler.php`

Responsabilités :
- `extract_fields($pdf_path)` : Appelle le script Python pour extraire les champs
- `get_mapping($template_id)` : Charge le mapping depuis la base
- `collect_data($mapping, $contextes)` : Collecte les données selon le mapping
- `fill($template_id, $contextes, $output_path)` : Génère le PDF rempli
- `archive($generated_id, $pilote_id)` : Archive dans le système documentaire

#### Contrôleur PHP : `application/controllers/Pdf_forms.php`

Vues :
- `index` : Liste des templates disponibles
- `upload` : Upload et analyse d'un nouveau template
- `mapping/$id` : Interface de mapping pour un template
- `generate/$id` : Formulaire de sélection des données et génération
- `history` : Historique des PDF générés

### Outils disponibles sur le système

- **PyPDF2** : Installé (`python3-pypdf2`), lecture/écriture de formulaires AcroForm
- **qpdf** : Installé, manipulation bas niveau des PDF
- **TCPDF** : Disponible dans le projet (génération, pas remplissage)

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

### Intégration avec l'archivage documentaire

Les PDF générés peuvent être archivés dans le système documentaire (voir PRD archivage_documentaire) :
- Association au pilote concerné
- Type de document : "Attestation de formation"
- Date de validité : optionnelle selon le type de formulaire
