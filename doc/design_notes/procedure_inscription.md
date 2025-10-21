# Gestion des procédures (notamment la procédure d'inscription)

## Table des matières

1. [Introduction](#1-introduction)
2. [Cas d'utilisation](#2-cas-dutilisation)
   - 2.1 [Rôle Administrateur](#21-rôle-administrateur)
   - 2.2 [Rôle Utilisateur](#22-rôle-utilisateur)
3. [Architecture et implémentation](#3-architecture-et-implémentation)
   - 3.1 [Composants d'une procédure](#31-composants-dune-procédure)
   - 3.2 [Utilisation du Markdown](#32-utilisation-du-markdown)
   - 3.3 [Métabalises disponibles](#33-métabalises-disponibles)
4. [Modèle de données](#4-modèle-de-données)
   - 4.1 [Table procedures](#41-table-procedures)
   - 4.2 [Table d'exécution de procédure](#42-table-dexécution-de-procédure)
   - 4.3 [Structure des fichiers](#43-structure-des-fichiers)
5. [Questions ouvertes](#5-questions-ouvertes)

---

## 1. Introduction

Ce document décrit la conception fonctionnelle et technique du système de gestion des procédures dans GVV. Les procédures permettent de guider les utilisateurs à travers des processus structurés (inscription, validation de documents, etc.) tout en collectant les informations nécessaires.

**Objectif principal :** Permettre aux administrateurs de définir des procédures sans compétences en programmation, en utilisant des fichiers Markdown enrichis de méta-balises.

---

## 2. Cas d'utilisation

### 2.1 Rôle Administrateur

#### 2.1.1 Définir une procédure
- Définir le texte et l'enchaînement des pages
- Charger les fichiers (images, PDF) à valider
- Définir les informations à collecter

#### 2.1.2 Consulter le suivi des procédures
- Connaître les procédures en cours
- Consulter les procédures validées/terminées
- Supprimer des suivis de procédure

#### 2.1.3 Valider des documents soumis
- Visualiser les documents téléchargés par les utilisateurs
- Valider les documents conformes
- Rejeter les documents non conformes avec explication

### 2.2 Rôle Utilisateur

#### 2.2.1 Commencer une procédure
- Recevoir un identifiant aléatoire pour continuer la procédure ultérieurement
- Saisir les informations demandées
- Accepter et valider des documents
- Télécharger des documents

#### 2.2.2 Suivi de la procédure
- Accéder à l'état d'avancement de la procédure
- Naviguer en arrière pour modifier des informations
- Une fois la procédure soumise, consulter l'état de validation des documents fournis

---

## 3. Architecture et implémentation

### 3.1 Composants d'une procédure

Une procédure est constituée des éléments suivants :

- **Pages d'informations** qui s'enchaînent
- **Fichiers PDF** à visualiser et accepter
- **Fichiers PDF générés** pendant la procédure
- **Sous-procédures** (procédures imbriquées)
- **Mécanisme de navigation** (avancer/reculer)

### 3.2 Utilisation du Markdown

Les administrateurs peuvent définir des procédures en Markdown enrichi avec des métabalises pour contrôler la logique de la procédure.

**Principe de base :** Une procédure sans intervention de l'utilisateur est simplement un fichier Markdown visualisé en HTML.


### 3.4 Sémantique des métabalises

#### 3.4.1 Balise `{page}`

**Syntaxe :** `{page}`

**Sémantique :**
- **Effet :** Marque une rupture de page dans la procédure
- **Navigation :** L'utilisateur peut naviguer entre les pages avec des boutons "Précédent" / "Suivant"
- **Persistance :** La page courante est sauvegardée dans le fichier JSON de suivi
- **Validation :** Avant de passer à la page suivante, tous les champs obligatoires de la page courante doivent être remplis

**Exemple d'utilisation :**
```markdown
# Page 1 : Informations personnelles
Veuillez renseigner vos informations personnelles.

{input:text:nom:"Nom*" required}
{input:text:prenom:"Prénom*" required}

{page}

# Page 2 : Contact
Vos coordonnées de contact.

{input:email:email:"Adresse email*" required}
{input:tel:telephone:"Téléphone"}
```

#### 3.4.2 Balise `{pdf:filename}`

**Syntaxe :** `{pdf:filename}`

**Paramètres :**
- `filename` : Nom du fichier PDF (relatif au répertoire de la procédure)

**Sémantique :**
- **Affichage :** Le PDF est affiché dans un iframe ou un visualiseur intégré
- **Interaction :** L'utilisateur peut faire défiler, zoomer dans le document
- **Progression :** Le système peut détecter si l'utilisateur a fait défiler jusqu'à la fin
- **Fichiers supportés :** Uniquement les fichiers PDF présents dans le répertoire de la procédure

**Exemple :**
```markdown
Veuillez prendre connaissance du règlement intérieur :

{pdf:reglement_interieur.pdf}

{acceptation:"J'ai lu et j'accepte le règlement intérieur"}
```

#### 3.4.3 Balise `{acceptation:text}`

**Syntaxe :** `{acceptation:text}`

**Paramètres :**
- `text` : Le texte à afficher à côté de la case à cocher

**Sémantique :**
- **Interface :** Case à cocher + texte
- **Validation :** L'acceptation peut être obligatoire pour continuer
- **Persistance :** L'état (coché/non coché) est sauvegardé dans `validations` du JSON
- **Utilisation :** Acceptation de conditions, règlements, clauses légales

**Exemple :**
```markdown
{acceptation:"J'accepte les conditions générales d'utilisation"}
{acceptation:"J'autorise le traitement de mes données personnelles"}
```

#### 3.4.4 Balises `{input:...}` - Sémantique complète

**Syntaxe générale :** `{input:type:name:"label" attributs}`

**Paramètres obligatoires :**
- `type` : Type de champ (voir types supportés ci-dessous)
- `name` : Nom unique du champ (utilisé comme clé dans le JSON)
- `label` : Texte affiché à l'utilisateur (entre guillemets)

**Attributs optionnels :**
- `required` : Champ obligatoire
- `maxlength="N"` : Longueur maximale pour les champs texte
- `minlength="N"` : Longueur minimale
- `placeholder="texte"` : Texte d'aide dans le champ
- `pattern="regex"` : Expression régulière de validation
- `min="valeur"` : Valeur minimale (nombres, dates)
- `max="valeur"` : Valeur maximale (nombres, dates)
- `step="N"` : Incrément pour les champs numériques
- `multiple` : Sélection multiple (pour select)

##### Types de champs supportés :

**1. Champs texte :**
```markdown
{input:text:nom:"Nom*" required maxlength="50"}
{input:email:email:"Adresse email" required}
{input:tel:telephone:"Téléphone" placeholder="06 12 34 56 78"}
{input:url:site_web:"Site web" placeholder="https://exemple.com"}
{input:password:mot_de_passe:"Mot de passe" required minlength="8"}
```

**2. Zones de texte :**
```markdown
{input:textarea:commentaires:"Commentaires" maxlength="500" placeholder="Remarques optionnelles"}
```

**3. Champs numériques :**
```markdown
{input:number:age:"Âge" min="16" max="99" required}
{input:range:niveau:"Niveau (1-10)" min="1" max="10" step="1"}
```

**4. Champs de date/heure :**
```markdown
{input:date:date_naissance:"Date de naissance*" required max="2007-12-31"}
{input:datetime-local:rdv:"Date et heure du rendez-vous"}
{input:time:heure_prefere:"Heure préférée"}
{input:month:mois_debut:"Mois de début de saison"}
{input:week:semaine:"Semaine souhaitée"}
```

**5. Cases et boutons radio :**
```markdown
{input:checkbox:newsletter:"Je souhaite recevoir la newsletter"}
{input:radio:civilite:"Civilité" options="M.|Madame,Mme.|Monsieur" required}
```

**6. Listes de sélection :**
```markdown
{input:select:section:"Section*" options="Planeur|Planeur,Avion|Avion,ULM|ULM" required}
{input:select:langues:"Langues parlées" options="Français|fr,Anglais|en,Allemand|de" multiple}
```

**7. Champs fichier :**
```markdown
{input:file:photo:"Photo d'identité" accept="image/*" required}
{input:file:documents:"Documents" accept=".pdf,.doc,.docx" multiple}
```

##### Sémantique de validation :

**Validation côté client :**
- Les attributs HTML5 (`required`, `pattern`, `min`, `max`, etc.) sont utilisés pour la validation immédiate
- Messages d'erreur personnalisés selon le type de champ
- Validation en temps réel pendant la saisie

**Validation côté serveur :**
- Toutes les validations client sont répétées côté serveur
- Intégration avec le système de métadonnées GVV (`Gvvmetadata.php`)
- Validation des types de données selon la configuration

**Gestion des options :**
- Format : `"Texte affiché|valeur,Autre texte|autre_valeur"`
- Si pas de `|`, le texte sert de valeur
- Pour les `radio` et `select`, le format est identique

**Exemples complets d'utilisation :**

```markdown
# Formulaire d'inscription
{input:text:nom:"Nom*" required maxlength="50"}
{input:text:prenom:"Prénom*" required maxlength="50"}
{input:email:email:"Email*" required}
{input:tel:telephone:"Téléphone" pattern="[0-9 .+-]{10,}"}
{input:date:date_naissance:"Date de naissance*" required max="2007-12-31"}
{input:select:section:"Section souhaitée*" options="Planeur|planeur,Avion|avion,ULM|ulm" required}
{input:radio:niveau:"Niveau actuel" options="Débutant|debutant,Confirmé|confirme,Expert|expert" required}
{input:textarea:motivation:"Motivation" maxlength="500" placeholder="Pourquoi souhaitez-vous rejoindre notre club ?"}
{input:checkbox:newsletter:"Je souhaite recevoir la newsletter du club"}
{input:file:photo:"Photo d'identité*" accept="image/jpeg,image/png" required}

{acceptation:"J'accepte le règlement intérieur du club"}
{acceptation:"J'autorise le traitement de mes données personnelles"}
```

#### 3.4.5 Balises `{upload:...}`

**Syntaxe :**
- `{upload:nom_fichier:"Description du fichier"}`
- `{upload_validate:nom_fichier:"Description du fichier"}`

**Différences :**
- `upload` : Fichier simplement stocké, pas de validation admin nécessaire
- `upload_validate` : Fichier nécessitant une validation par un administrateur

**Sémantique :**
- **Interface :** Zone de glisser-déposer ou bouton de sélection
- **Validation :** Types de fichiers, taille maximale configurable
- **Stockage :** Fichiers sauvegardés dans le dossier de suivi de la procédure
- **Nommage :** `{nom_fichier}.{extension}` (le nom original peut être préservé en métadonnée)

**Exemples :**
```markdown
{upload:photo:"Photo d'identité (format JPG ou PNG)"}
{upload_validate:certificat_medical:"Certificat médical (obligatoire - sera validé par un administrateur)"}
{upload:pieces_jointes:"Documents complémentaires (optionnel)"}
```

---

## 4. Modèle de données

### 4.1 Table `procedures` (existante)

✅ **Table déjà créée** - La table `procedures` existe déjà dans la base de données avec la structure suivante :

| Champ | Type | Null | Clé | Défaut | Description |
|-------|------|------|-----|--------|-------------|
| `id` | bigint(20) unsigned | NO | PRI | AUTO_INCREMENT | Identifiant unique |
| `name` | varchar(128) | NO | UNI | NULL | Nom unique de la procédure (slug/identifiant) |
| `title` | varchar(255) | NO | | NULL | Titre affiché de la procédure |
| `description` | text | YES | | NULL | Description courte de la procédure |
| `markdown_file` | varchar(255) | YES | | NULL | Chemin vers le fichier markdown |
| `section_id` | int(11) | YES | MUL | NULL | Section associée (NULL = globale) |
| `status` | enum('draft','published','archived') | YES | MUL | 'draft' | Statut de la procédure |
| `version` | varchar(20) | YES | | '1.0' | Version de la procédure |
| `created_by` | varchar(25) | YES | MUL | NULL | Utilisateur créateur |
| `created_at` | timestamp | YES | | current_timestamp() | Date de création |
| `updated_by` | varchar(25) | YES | | NULL | Dernier utilisateur modificateur |
| `updated_at` | timestamp | YES | | current_timestamp() | Date de dernière modification |

#### Contraintes existantes
- **Clé primaire :** `id`
- **Index unique :** `name` (unique_name)
- **Index :** `section_id`, `status`, `created_by`
- **Clé étrangère :** `section_id` → `sections(id)` (ON DELETE SET NULL, ON UPDATE CASCADE)

#### Notes importantes
- Le champ `name` sert d'identifiant unique pour la procédure (ex: "inscription", "inscription_avion")
- Le champ `markdown_file` contient le chemin relatif vers le fichier markdown (ex: "inscription/procedure_inscription.md")
- Le statut permet de gérer le cycle de vie : **draft** (brouillon), **published** (publiée), **archived** (archivée)
- La référence à `section_id` permet d'associer une procédure à une section spécifique du club

### 4.2 Suivi de l'exécution des procédures (stockage fichier)

💡 **Approche simplifiée** - Pas de table de suivi en base de données. L'exécution d'une procédure générera un dossier de suivi qui comprendra tous les éléments relatifs à l'exécution de la procédure.

Lorsqu'un utilisateur commencera une procédure:
- il pourra commencer une procédure depuis le début
  - on lui demandera une adresse email
  - et on lui affichera un nombre aléatoire de quatre chiffre

- il pourra reprendre une procédure en cours non complétée
  - il fournira son adresse email et le nombre aléatoire fourni précédemment

#### Structure du dossier de suivi
Chaque exécution de procédure créera un dossier unique contenant :

Le dossier unique sera généré à partir de l'adresse email et du numéro aléatoire
Ex: 5732_jean_dupont_at_gmail_com

si le nombre aléatoire est 5732 et l'adresse email jean.dupont@gmail.com

* **Fichier JSON de données** (`data.json`) avec :
  * Tous les champs saisis par l'utilisateur
  * L'état des validations d'acceptation
  * L'état de validation des documents par les administrateurs
  * Un log des actions effectuées par l'utilisateur
  * L'état de navigation courant dans la procédure
  * Métadonnées (timestamps, IP, user-agent, etc.)

* **Fichiers uploadés** par l'utilisateur :
  * Photos d'identité
  * Certificats médicaux
  * Autorisations parentales
  * Autres documents requis

#### Avantages de cette approche
- ✅ **Simplicité** : Pas de schéma complexe en base
- ✅ **Flexibilité** : Structure de données adaptable selon les procédures
- ✅ **Performance** : Pas de requêtes SQL pour le stockage temporaire
- ✅ **Backup** : Sauvegarde simple par copie de répertoires
- ✅ **Debug** : Fichiers lisibles directement
- ✅ **Scalabilité** : Possibilité de déplacer vers un stockage distribué

#### Format du fichier JSON de données
```json
{
  "procedure_info": {
    "procedure_name": "inscription",
    "version": "1.0",
    "started_at": "2025-10-21T10:30:00Z",
    "updated_at": "2025-10-21T11:15:00Z",
    "status": "in_progress",
    "current_page": 3,
    "total_pages": 5
  },
  "user_data": {
    "nom": "Dupont",
    "prenom": "Jean",
    "email": "jean.dupont@gmail.com",
    "date_naissance": "1990-05-15"
  },
  "validations": {
    "acceptation_reglement": true,
    "acceptation_cgv": true
  },
  "uploads": {
    "photo": {
      "filename": "photo.jpg",
      "uploaded_at": "2025-10-21T10:45:00Z",
      "validation_status": "pending"
    },
    "certificat_medical": {
      "filename": "certificat_medical.pdf",
      "uploaded_at": "2025-10-21T11:00:00Z",
      "validation_status": "pending"
    }
  },
  "admin_validations": {
    "photo": {
      "status": "approved",
      "validated_by": "admin",
      "validated_at": "2025-10-21T14:30:00Z",
      "comment": ""
    }
  },
  "activity_log": [
    {
      "timestamp": "2025-10-21T10:30:00Z",
      "action": "procedure_started",
      "details": "Procédure inscription démarrée"
    },
    {
      "timestamp": "2025-10-21T10:45:00Z",
      "action": "file_uploaded",
      "details": "Upload photo.jpg"
    }
  ],
  "metadata": {
    "ip_address": "192.168.1.100",
    "user_agent": "Mozilla/5.0...",
    "token": "a1b2c3d4e5f6...",
    "session_duration": 2700
  }
}
```

### 4.3 Structure des fichiers

#### Fichiers de définition des procédures
```
procedures/
├── example_procedure/
│   └── procedure_example_procedure.md
├── inscription/
│   └── procedure_inscription.md
├── inscription_avion/
│   └── procedure_inscription_avion.md
└── maintenance_planeur/
    └── procedure_maintenance_planeur.md
```

#### Fichiers de suivi des procédures
```
suivi_procedure/
└── inscription_avion/
    └── jean_dupont_at_gmail_com_5434/
        ├── certificat_medical.png
        ├── jean_dupont_data.json
        └── photo.png
```

---

## 5. Questions ouvertes et décisions de conception

### 5.1 Intégration avec le système de membres

**Question :** Comment créer automatiquement une fiche de membre à partir des informations saisies dans la procédure d'inscription ?

**Proposition :** 
- Ajouter un champ `member_mapping` (JSON) dans la table `procedures` pour définir la correspondance
- Format du mapping : 
  ```json
  {
    "target_table": "membres",
    "field_mapping": {
      "nom": "nom",
      "prenom": "prenom",
      "email": "email",
      "date_naissance": "date_naissance"
    },
    "auto_create": true,
    "status_field": "statut",
    "default_status": "candidat"
  }
  ```

### 5.2 Gestion des identifiants anonymes

**Question :** Comment générer et gérer l'identifiant unique permettant à un utilisateur de reprendre sa procédure ?

**Proposition :**
- Utiliser un token aléatoire sécurisé (ex: `bin2hex(random_bytes(32))`)
- Le token sert d'URL : `/procedures/continue/{token}`
- Stocké dans `procedure_tracking.unique_token` (index unique)
- Option d'envoi par email pour ne pas perdre le lien

### 5.3 Politique de conservation des données

**Question :** Combien de temps conserver les suivis de procédures ?

**Proposition :**
- **Procédures validées** : conserver 1 an (archivage possible)
- **Procédures abandonnées** : supprimer après 90 jours d'inactivité
- **Procédures rejetées** : conserver 6 mois (possibilité de re-soumission)
- Ajouter un job cron pour le nettoyage automatique

---

## 6. Plan de développement et nouvelles étapes

### 6.1 Phase 1 : Fondations (Sprint 1-2) ✅ Simplifiée

#### 6.1.1 Structure des répertoires et permissions
- [ ] Créer la structure des répertoires `procedures/` et `suivi_procedure/`
- [ ] Configurer les permissions d'écriture appropriées
- [ ] Créer des exemples de procédures de test

#### 6.1.2 Modèle et contrôleur de base
- [ ] Créer le modèle `Procedure_model.php` (utilise la table existante)
- [ ] Créer le contrôleur `Procedures.php` 
- [ ] Ajouter les métadonnées dans `Gvvmetadata.php`
- [ ] Créer les vues de base (liste, création, édition)

#### 6.1.3 Tests unitaires
- [ ] Tests du modèle `Procedure_model`
- [ ] Tests des métadonnées
- [ ] Tests de validation des données

### 6.2 Phase 2 : Parser Markdown et métabalises (Sprint 3-4)

#### 6.2.1 Parser Markdown
- [ ] Créer la librairie `Procedure_parser.php`
- [ ] Implémenter la détection des métabalises
- [ ] Gérer la pagination avec `{page}`
- [ ] Gérer l'affichage PDF avec `{pdf:filename}`

#### 6.2.2 Gestion des champs de saisie
- [ ] Implémenter `{input:type:name:"label"}`
- [ ] Intégration avec les métadonnées GVV existantes
- [ ] Validation des types de champs

#### 6.2.3 Tests du parser
- [ ] Tests unitaires du parser Markdown
- [ ] Tests d'intégration avec différents types de métabalises
- [ ] Créer une procédure d'exemple pour les tests

### 6.3 Phase 3 : Moteur d'exécution (Sprint 5-6)

#### 6.3.1 Gestion des sessions de procédure
- [ ] Créer le système de tokens uniques
- [ ] Gérer la persistance des données en JSON
- [ ] Implémenter la navigation avant/arrière

#### 6.3.2 Interface utilisateur d'exécution
- [ ] Vue d'exécution de procédure
- [ ] Gestion de l'upload de fichiers
- [ ] Interface de progression et navigation

#### 6.3.3 Tests d'exécution
- [ ] Tests d'exécution complète d'une procédure
- [ ] Tests de reprise de procédure via token
- [ ] Tests d'upload et sauvegarde de fichiers

### 6.4 Phase 4 : Interface d'administration (Sprint 7-8)

#### 6.4.1 Gestion des procédures
- [ ] Interface de création/édition de procédures
- [ ] Prévisualisation des procédures
- [ ] Gestion des statuts (draft/published/archived)

#### 6.4.2 Suivi et validation
- [ ] Dashboard de suivi des procédures en cours
- [ ] Interface de validation des documents
- [ ] Historique et logs d'actions

#### 6.4.3 Tests administrateur
- [ ] Tests d'interface d'administration
- [ ] Tests de validation de documents
- [ ] Tests de gestion des statuts

### 6.5 Phase 5 : Intégration et fonctionnalités avancées (Sprint 9-10)

#### 6.5.1 Intégration avec le système de membres
- [ ] Implémenter le mapping automatique vers la table `membres`
- [ ] Créer le système de correspondance de champs
- [ ] Tests d'intégration avec création de membres

#### 6.5.2 Fonctionnalités avancées
- [ ] Notifications par email
- [ ] Système de nettoyage automatique
- [ ] Export des données de procédures

#### 6.5.3 Documentation et formation
- [ ] Documentation utilisateur
- [ ] Guide d'administration
- [ ] Formation des utilisateurs finaux

### 6.6 Prochaines actions immédiates (mises à jour)

**Action 1 :** Créer la structure des répertoires
```bash
# Créer les répertoires de base
mkdir -p procedures/
mkdir -p suivi_procedure/
chmod 755 procedures/
chmod 777 suivi_procedure/  # Écriture nécessaire pour les dossiers de suivi
```

**Action 2 :** Vérifier si un modèle Procedure existe déjà
```bash
# Chercher un modèle existant
find application/models/ -name "*rocedure*" -o -name "*Procedure*"
```

**Action 3 :** Créer une procédure d'exemple simple
- Procédure "test" avec quelques pages et champs de base
- Fichier `procedures/test/procedure_test.md`
- Servira de référence pour le développement du parser

**Action 4 :** Choisir la stratégie de parsing Markdown
- Utiliser une librairie existante (Parsedown) ou développer sur mesure
- Intégrer avec les métabalises personnalisées

**Action 5 :** Vérifier les métadonnées existantes
```bash
# Chercher si des métadonnées procedures existent
grep -n "procedure" application/libraries/Gvvmetadata.php
```

---

## 7. Questions techniques à résoudre

### 7.1 Sécurité
- **Validation des uploads :** Types de fichiers autorisés, taille maximale
- **Sanitisation :** Nettoyage du contenu Markdown pour éviter les injections
- **Accès aux fichiers :** Protection des fichiers uploadés contre l'accès direct

### 7.2 Performance
- **Cache :** Mettre en cache le parsing des fichiers Markdown
- **Stockage :** Optimiser le stockage des gros fichiers uploadés
- **Nettoyage :** Automatiser la suppression des procédures abandonnées

### 7.3 Compatibilité
- **Responsive :** Interface mobile pour l'exécution des procédures
- **Accessibilité :** Respect des standards WCAG
- **Navigateurs :** Support des navigateurs modernes


