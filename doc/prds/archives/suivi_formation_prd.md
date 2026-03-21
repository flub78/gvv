# PRD - Suivi de Formation

## Objectif

Permettre aux aéroclubs de définir des programmes de formation structurés, de suivre les séances d'instruction, de visualiser la progression des élèves à travers des fiches de progression détaillées et d'accéder à des rapports synthétiques.

## Contexte

La formation au pilotage nécessite un suivi rigoureux et structuré. Les instructeurs doivent pouvoir :
- Suivre un programme de formation standardisé
- Enregistrer chaque séance avec le détail des compétences travaillées
- Évaluer la progression sur chaque sujet
- Planifier les prochaines étapes de formation

Actuellement, ce suivi est souvent réalisé sur papier. L'intégration dans GVV permettrait de centraliser ces informations avec les données de vol existantes.

---

## Activation de la Fonctionnalité

La gestion des formations est une fonctionnalité **optionnelle** qui doit être activée explicitement pour chaque club via un flag de configuration.

### Flag de Configuration

- **Nom du flag** : `gestion_formations`
- **Type** : Booléen (0 = désactivé, 1 = activé)
- **Défaut** : Désactivé (0)
- **Niveau** : Configuration globale du club

### Comportement

**Lorsque le flag est désactivé (par défaut) :**
- Les menus et liens liés aux formations n'apparaissent pas dans l'interface
- Les routes d'accès aux contrôleurs de formation retournent une erreur 403 (Accès refusé)
- Les tables de données relatives aux formations sont quand même créées lors de l'installation
- Aucune donnée de formation n'est stockée ou traitée

**Lorsque le flag est activé :**
- Les menus de gestion des formations apparaissent dans le menu principal (pour les utilisateurs autorisés)
- Les contrôleurs de formation sont accessibles selon les permissions de l'utilisateur
- Toutes les fonctionnalités décrites dans ce document sont disponibles

### Activation

L'activation de la fonctionnalité nécessite :
1. Une modification du fichier de configuration `program.php` pour définir le flag "gestion_formations" à `true`

### Désactivation

La désactivation du flag masque les fonctionnalités mais **conserve les données existantes** dans la base de données. Une réactivation ultérieure restaure l'accès aux données.

---

## Rôles et Permissions

### Administrateur (Responsable Pédagogique)

Le responsable pédagogique est responsable de la configuration et de la gestion globale du système de formation.

**Responsabilités :**
- Créer, modifier et archiver les programmes de formation
- Définir la structure des formations (leçons, sujets)
- Gérer les versions des programmes de formation
- Superviser l'ensemble des formations en cours dans le club (inscriptions)
- Lister et filtrer toutes les formations (ouvertes, suspendues, clôturées, abandonnées)
- Générer des rapports de synthèse sur l'activité de formation
- Configurer les paramètres généraux (échelle d'évaluation, météo types, etc.)

**Accès :**
- Lecture/écriture sur les programmes de formation visibles ("Toutes" + section courante)
- Lecture sur les inscriptions, séances et progressions de sa section
- Modification des paramètres système liés à la formation
- Un administrateur club (sans section) a accès à l'ensemble des données

### Instructeur

L'instructeur est responsable de la délivrance des formations et du suivi de ses élèves.

**Responsabilités :**
- Ouvrir une formation pour un pilote (créer une inscription)
- Suspendre, réactiver ou clôturer une formation
- Enregistrer les séances de formation qu'il dispense
- Évaluer la progression des élèves sur chaque sujet
- Rédiger les commentaires et observations
- Définir les prochaines leçons à travailler
- Consulter l'historique de progression de ses élèves
- Consulter les programmes de formation disponibles

**Accès :**
- Lecture sur tous les programmes de formation
- Lecture/écriture sur les inscriptions (ouverture, suspension, clôture)
- Lecture/écriture sur les séances qu'il dispense
- Lecture sur les séances de ses élèves (dispensées par d'autres instructeurs)
- Lecture sur les fiches de progression de ses élèves

### Pilote (Élève)

Le pilote en formation peut consulter sa propre progression.

**Responsabilités :**
- Consulter son programme de formation actif
- Visualiser sa fiche de progression
- Consulter l'historique de ses séances
- Prendre connaissance des commentaires de ses instructeurs
- Voir les prochaines leçons recommandées

**Accès :**
- Lecture sur les programmes de formation auxquels il est inscrit
- Lecture sur ses propres séances de formation
- Lecture sur sa propre fiche de progression
- Aucun accès aux données des autres élèves

---

## Fonctionnalités

### 1. Définition des Programmes de Formation

#### 1.1 Format Markdown des Programmes

Les programmes de formation sont définis au format Markdown, permettant une rédaction simple et structurée. Le fichier peut être **importé** depuis un fichier `.md` ou **édité en ligne** dans l'interface.

**Syntaxe :**

| Élément | Syntaxe Markdown | Description |
|---------|------------------|-------------|
| Programme | `# Titre` | Titre de niveau 1 (un seul par fichier) |
| Leçon | `## Leçon N : Titre` | Titre de niveau 2, numérotation libre |
| Sujet | `### Sujet N.M : Titre` | Titre de niveau 3, sous une leçon |
| Description | Texte libre | Paragraphes sous chaque titre |
| Objectifs | Liste à puces `- item` | Liste des objectifs ou points clés |

**Règles de parsing :**
- Le titre `#` définit le nom du programme (obligatoire, unique)
- Chaque `##` crée une nouvelle leçon
- Chaque `###` crée un sujet rattaché à la leçon précédente
- Le texte entre un titre et le titre suivant constitue la description
- Le contenu (description, objectifs) des sujets est optionnel
- Les listes à puces et le formatage (gras, italique) sont préservés dans les descriptions

#### 1.2 Exemple Complet de Programme

```markdown
# Formation Initiale Planeur

## Leçon 1 : Découverte du planeur

### Sujet 1.1 : Présentation de l'aéronef
Familiarisation avec le planeur utilisé pour la formation.

- Cellule et gouvernes
- Cockpit et instruments de base
- Commandes de vol
- Procédures d'installation à bord

### Sujet 1.2 : Sensations de base
Découverte des sensations de vol et des effets des commandes.

- Effet du manche sur la trajectoire
- Effet du palonnier
- Compensation et équilibre

## Leçon 2 : Le vol rectiligne

### Sujet 2.1 : Assiette et inclinaison
Maintien d'une attitude de vol stable en ligne droite.

- Référence visuelle extérieure
- Instruments de contrôle (horizon, bille)
- Corrections d'assiette

### Sujet 2.2 : Utilisation des aérofreins
Contrôle de la trajectoire avec les aérofreins.

- Effets des aérofreins sur la trajectoire
- Dosage et anticipation
- Utilisation en approche

## Leçon 3 : Les virages

### Sujet 3.1 : Virage de faible inclinaison
Virages à 15-20° d'inclinaison, coordination pied-main.

- Mise en virage
- Tenue du virage (bille centrée)
- Sortie de virage sur cap

### Sujet 3.2 : Virage à moyenne inclinaison
Virages à 30-45° d'inclinaison.

- Gestion de l'assiette en virage
- Compensation de la perte d'altitude
- Enchaînement de virages

## Leçon 4 : Le décollage

### Sujet 4.1 : Décollage remorqué
Procédure complète de décollage au treuil ou remorqué avion.

- Préparation et check-list
- Phase de roulement
- Rotation et montée initiale
- Tenue de position derrière le remorqueur

### Sujet 4.2 : Largage et séparation
Procédure de largage et transition vers le vol libre.

- Décision de largage
- Technique de largage
- Manœuvre de séparation

## Leçon 5 : L'atterrissage

### Sujet 5.1 : Circuit et intégration
Intégration dans le circuit de piste.

- Points caractéristiques du circuit
- Gestion de l'altitude et de la vitesse
- Espacement et intégration trafic

### Sujet 5.2 : Approche finale
Gestion de la finale jusqu'au seuil de piste.

- Plan d'approche (PAPI/repères visuels)
- Utilisation des aérofreins
- Corrections de trajectoire

### Sujet 5.3 : Arrondi et toucher
Phase finale d'atterrissage.

- Transition vers l'arrondi
- Gestion de l'assiette au toucher
- Roulement et immobilisation
```

#### 1.3 Import et Édition

**Import de fichier :**
- Format accepté : `.md` (encodage UTF-8)
- Le système valide la structure et affiche un aperçu avant import
- Erreurs signalées : titre manquant, sujet sans leçon parente

**Édition en ligne :**
- Éditeur avec coloration syntaxique Markdown
- Prévisualisation en temps réel de la structure extraite
- Validation automatique à la saisie

#### 1.4 Attributs d'un Programme

| Attribut | Description |
|----------|-------------|
| Titre | Nom du programme de formation |
| Code | Identifiant court unique (ex: PPL-INIT) |
| Version | Numéro de version du programme |
| Description | Description générale du programme |
| Contenu Markdown | Structure complète avec leçons et sujets |
| Section | Section propriétaire ou "Toutes" (tout le club) |
| Statut | Actif / Archivé |
| Date de création | Date de création du programme |
| Date de modification | Dernière modification |

#### 1.6 Appartenance aux Sections

Un programme de formation peut être :

| Type | Description | Visibilité |
|------|-------------|------------|
| **Toutes** | Programme commun à tout le club | Visible par toutes les sections |
| **Section** | Programme spécifique à une section | Visible uniquement par la section propriétaire |

**Règles de visibilité :**
- Un administrateur connecté à une section voit :
  - Tous les programmes marqués **"Toutes"**
  - Tous les programmes appartenant à **sa section**
- Un administrateur club (sans section) voit tous les programmes
- Lors de la création, l'administrateur choisit : "Toutes" ou sa section
- Un programme de section peut être promu en "Toutes" (mais pas l'inverse si des inscriptions existent)

#### 1.5 Parsing et Utilisation

Le système analyse automatiquement le contenu Markdown pour extraire :
- Le titre du programme (niveau 1 : `#`)
- Les leçons (titres de niveau 2 : `##`)
- Les sujets (titres de niveau 3 : `###`)
- Les descriptions et listes associées

Cette structure est utilisée pour :
- Générer les formulaires de saisie des séances (liste des sujets à évaluer)
- Construire les fiches de progression (arborescence leçons/sujets)
- Afficher le détail des objectifs pour l'instructeur et l'élève

---

### 2. Gestion des Inscriptions aux Formations

#### 2.1 Cycle de Vie d'une Inscription

Une inscription représente l'engagement d'un pilote dans un programme de formation. Elle suit un cycle de vie défini :

```
[Ouverte] → [Suspendue] ↔ [Ouverte] → [Clôturée]
                                    → [Abandonnée]
```

| Statut | Description |
|--------|-------------|
| **Ouverte** | Formation en cours, l'élève peut recevoir des séances |
| **Suspendue** | Formation temporairement interrompue (raison médicale, indisponibilité, etc.) |
| **Clôturée** | Formation terminée avec succès |
| **Abandonnée** | Formation arrêtée définitivement sans complétion |

#### 2.2 Attributs d'une Inscription

| Attribut | Description | Obligatoire |
|----------|-------------|-------------|
| Pilote | Élève inscrit à la formation | Oui |
| Programme | Programme de formation suivi | Oui |
| Version programme | Version du programme à l'ouverture | Oui (auto) |
| Date d'ouverture | Date de début de la formation | Oui |
| Instructeur référent | Instructeur principal (optionnel) | Non |
| Statut | Ouverte / Suspendue / Clôturée / Abandonnée | Oui |
| Date de suspension | Date de mise en suspension | Si suspendue |
| Motif de suspension | Raison de la suspension | Si suspendue |
| Date de clôture | Date de fin de formation | Si clôturée/abandonnée |
| Motif de clôture | Raison de la clôture ou abandon | Non |
| Commentaires | Notes libres sur l'inscription | Non |

#### 2.3 Règles de Gestion

- Un pilote peut avoir **plusieurs inscriptions ouvertes ou suspendues** simultanément (ex: formation initiale + qualification voltige)
- Une inscription suspendue peut être réouverte ou abandonnée
- Une inscription clôturée ou abandonnée ne peut plus être modifiée
- Les séances ne peuvent être enregistrées que sur une inscription **ouverte**

---

### 3. Gestion des Séances de Formation

#### 3.1 Types de Séances

Le système permet deux types de séances :

**Séance liée à une inscription (Formation structurée)**
- Rattachée à une inscription active d'un pilote
- Utilise le programme de formation de l'inscription
- Suit la progression structurée avec évaluations des sujets
- Contribue à la fiche de progression officielle

**Séance libre (Sans inscription)**
- Pour des pilotes non inscrits à une formation formelle
- Permet de sélectionner un programme de formation comme référence
- Permet d'indiquer et d'archiver les sujets abordés
- Sert d'historique et de préparation avant une inscription formelle
- Ne génère pas de fiche de progression officielle
- Utile pour : vols de perfectionnement, remise à niveau, découverte

#### 3.2 Attributs d'une Séance

| Attribut | Description | Obligatoire |
|----------|-------------|-------------|
| Date | Date de la séance | Oui |
| Élève | Pilote en formation | Oui |
| Instructeur | Instructeur dispensant la formation | Oui (auto-rempli) |
| Inscription | Inscription à une formation (optionnel) | Non |
| Programme | Programme de formation suivi | Oui |
| Machine | Aéronef utilisé | Oui |
| Durée | Durée totale de vol (HH:MM) | Oui |
| Nombre d'atterrissages | Nombre d'atterrissages effectués | Oui |
| Météo | Conditions météorologiques | Oui |
| Commentaires généraux | Observations libres sur la séance | Non |
| Prochaines leçons | Leçons recommandées pour la suite | Non |

**Règles de gestion** :
- Si `inscription_id` est NULL → séance libre (pilote non inscrit)
- Si `inscription_id` est renseigné → séance liée à une formation structurée
- Le programme peut être sélectionné librement pour une séance libre
- Les évaluations sont enregistrées de la même manière dans les deux cas

#### 3.3 Évaluation par Sujet

Pour chaque sujet du programme, l'instructeur peut indiquer :

| Niveau | Description | Signification |
|--------|-------------|---------------|
| - | Non abordé | Le sujet n'a pas été travaillé lors de cette séance |
| A | Abordé | Le sujet a été introduit ou travaillé |
| R | À revoir | Le sujet nécessite d'être retravaillé |
| Q | Acquis | Le sujet est maîtrisé par l'élève |

**Pour les séances libres (sans inscription)** :
- Les évaluations sont enregistrées de la même manière
- Elles servent d'historique et de référence
- Elles ne contribuent pas à une fiche de progression officielle
- Elles peuvent être consultées ultérieurement pour évaluer le niveau du pilote

#### 3.4 Conditions Météorologiques

Sélection multiple parmi des conditions prédéfinies :

- Vent faible (< 15 kt)
- Vent modéré (15-25 kt)
- Vent fort (> 25 kt)
- Vent de travers
- Thermiques faibles
- Thermiques modérés
- Thermiques forts
- Ciel clair
- Cumulus
- Couvert
- Turbulences

#### 3.5 Analyse des Commentaires

Les commentaires de l'instructeur sont enregistrés librement. Le système peut les associer aux compétences travaillées pour faciliter la restitution dans les fiches de progression.

**Pour les séances libres** : Les commentaires sont archivés et peuvent être consultés par l'instructeur pour référence future lors de l'ouverture d'une formation formelle.

---

### 4. Fiches de Progression

#### 4.1 Structure de la Fiche

La fiche de progression présente une vue synthétique de l'avancement de l'élève, organisée selon la structure du programme de formation :

```
Programme : [Nom du programme]
Élève : [Nom de l'élève]
Date de début : [Date première séance]
Dernière séance : [Date dernière séance]
Progression : 45% des sujets acquis (9/20)

Leçon 1 : [Titre]
├── Sujet 1.1 : [Titre]
│   ├── Séances : 3
│   ├── Dernier niveau : Acquis (Q)
│   └── Dernière date : 15/01/2025
├── Sujet 1.2 : [Titre]
│   ├── Séances : 2
│   ├── Dernier niveau : À revoir (R)
│   └── Dernière date : 10/01/2025

Leçon 2 : [Titre]
├── Sujet 2.1 : [Titre]
│   ├── Séances : 0
│   ├── Dernier niveau : Non abordé (-)
│   └── Dernière date : -
```

#### 4.2 Indicateur de Progression Globale

En tête de la fiche de progression, un **indicateur de progression** affiche le pourcentage de sujets acquis :

**Formule de calcul :**
```
Pourcentage = (Nombre de sujets avec dernier niveau "Q") / (Nombre total de sujets du programme) × 100
```

**Affichage :**
- Format : "X% des sujets acquis (N/Total)"
- Exemple : "45% des sujets acquis (9/20)"
- Barre de progression visuelle (jauge colorée)
- Couleur de la jauge :
  - **Rouge** : 0-25%
  - **Orange** : 26-50%
  - **Jaune** : 51-75%
  - **Vert** : 76-100%

**Règles :**
- Seuls les sujets avec statut "Q" (Acquis) sont comptabilisés comme acquis
- Les sujets non abordés (-), abordés (A) ou à revoir (R) ne sont pas comptabilisés comme acquis
- Le total inclut tous les sujets du programme, qu'ils aient été travaillés ou non

#### 4.3 Informations Affichées par Sujet

| Information | Description |
|-------------|-------------|
| Nombre de séances | Nombre total de séances où le sujet a été abordé (A, R ou Q) |
| Dernier niveau | Dernière évaluation enregistrée pour ce sujet |
| Date dernière évaluation | Date de la dernière séance ayant évalué ce sujet |
| Historique | Liste des évaluations chronologiques (optionnel, en détail) |

#### 4.4 Indicateurs Visuels par Sujet

- **Vert** : Sujet acquis (Q)
- **Orange** : Sujet à revoir (R)
- **Bleu** : Sujet abordé (A)
- **Gris** : Sujet non abordé (-)

#### 4.5 Vue Détaillée d'un Sujet

En cliquant sur un sujet, l'utilisateur peut accéder à :
- L'historique complet des évaluations
- Les commentaires associés
- Les séances concernées avec liens vers le détail

---

## Cas d'Utilisation

### Administrateur

**Créer un programme de formation**
1. Accéder à la gestion des programmes de formation
2. Cliquer sur "Nouveau programme"
3. Saisir le code et la description
4. Choisir l'appartenance : "Toutes" (tout le club) ou section courante
5. Choisir le mode d'entrée :
   - **Import** : sélectionner un fichier `.md` existant
   - **Édition en ligne** : rédiger directement dans l'éditeur Markdown
6. Vérifier la structure extraite dans le panneau de prévisualisation
7. Corriger les erreurs éventuelles (titre manquant, sujet orphelin)
8. Valider et enregistrer

**Modifier un programme existant**
1. Sélectionner le programme à modifier
2. Éditer le contenu Markdown en ligne ou importer une nouvelle version
3. Le système détecte les changements de structure (leçons/sujets ajoutés, supprimés, renommés)
4. Avertissement si des sujets sont supprimés (impact sur les progressions existantes)
5. Valider avec incrémentation automatique de version
6. Option : exporter la version actuelle avant modification

**Consulter la synthèse club**
1. Accéder au tableau de bord formations
2. Visualiser le nombre d'élèves en formation par programme
3. Consulter les statistiques de progression globales

**Lister les formations en cours**
1. Accéder à la liste des inscriptions
2. Filtrer par statut (ouvertes, suspendues, clôturées, abandonnées)
3. Filtrer par programme, par instructeur référent
4. Visualiser les informations clés : élève, programme, statut, dernière activité

### Instructeur

**Ouvrir une formation pour un pilote**
1. Accéder à "Nouvelle inscription" 
2. Sélectionner le pilote
3. Sélectionner le programme de formation
4. Optionnel : désigner un instructeur référent
5. Ajouter des commentaires si nécessaire
6. Valider l'ouverture
7. Le système enregistre la date d'ouverture et la version du programme

**Suspendre une formation**
1. Accéder à la fiche de l'inscription ou à la liste des inscriptions
2. Sélectionner l'inscription à suspendre (statut "Ouverte")
3. Cliquer sur "Suspendre"
4. Saisir le motif de suspension (ex: raison médicale, indisponibilité prolongée)
5. Valider
6. Le système enregistre la date et le motif de suspension

**Réactiver une formation suspendue**
1. Accéder à la fiche de l'inscription suspendue
2. Cliquer sur "Réactiver"
3. Confirmer la réactivation
4. Le statut repasse à "Ouverte"

**Clôturer une formation (succès)**
1. Accéder à la fiche de l'inscription
2. Cliquer sur "Clôturer"
3. Sélectionner "Formation terminée avec succès"
4. Ajouter un commentaire de clôture (optionnel)
5. Valider
6. Le système enregistre la date de clôture

**Abandonner une formation**
1. Accéder à la fiche de l'inscription
2. Cliquer sur "Clôturer"
3. Sélectionner "Abandon"
4. Saisir le motif d'abandon
5. Valider

**Consulter les formations en cours**
1. Accéder à "Formations en cours"
2. Visualiser les inscriptions ouvertes et suspendues
3. Filtrer par programme si nécessaire

**Enregistrer une séance de formation (avec inscription)**
1. Accéder à "Nouvelle séance de formation"
2. Sélectionner l'élève
3. Le système propose les inscriptions ouvertes de l'élève
4. Sélectionner l'inscription (le programme est automatiquement associé)
5. Saisir les informations de vol (date, machine, durée, atterrissages, météo)
6. Pour chaque sujet de la leçon travaillée, indiquer le niveau (-, A, R, Q)
7. Rédiger les commentaires généraux
8. Indiquer les prochaines leçons recommandées
9. Valider

**Enregistrer une séance libre (sans inscription)**
1. Accéder à "Nouvelle séance de formation"
2. Sélectionner l'élève (pilote non inscrit ou inscription non sélectionnée)
3. Cocher "Séance libre (sans inscription formelle)"
4. Sélectionner un programme de formation comme référence
5. Saisir les informations de vol (date, machine, durée, atterrissages, météo)
6. Sélectionner les leçons/sujets abordés et indiquer le niveau (-, A, R, Q)
7. Rédiger les commentaires (ex: "Vol de perfectionnement", "Remise à niveau")
8. Valider
9. La séance est archivée et consultable ultérieurement

**Consulter l'historique des séances d'un pilote**
1. Accéder à la fiche du pilote
2. Onglet "Séances de formation"
3. Visualiser toutes les séances : avec inscription (formations structurées) et libres
4. Filtrer par type, par programme, par période
5. Consulter les sujets abordés et les évaluations

**Consulter la progression d'un élève**
1. Accéder à la fiche de l'élève ou à la liste des progressions
2. Sélectionner l'élève
3. Visualiser la fiche de progression
4. Consulter le détail par sujet si nécessaire

**Modifier une séance passée**
1. Accéder à l'historique des séances
2. Sélectionner la séance à modifier
3. Corriger les informations (dans un délai limité ou avec justification)
4. Valider

### Pilote (Élève)

**Consulter ma progression**
1. Accéder à "Ma formation"
2. Visualiser la fiche de progression personnelle
3. Consulter les commentaires de l'instructeur
4. Voir les prochaines leçons recommandées

**Consulter l'historique de mes séances**
1. Accéder à l'historique des séances
2. Filtrer par période si nécessaire
3. Consulter le détail de chaque séance

---

## Interfaces Utilisateur

### Administration - Liste des Programmes

- Tableau listant les programmes avec : code, titre, section (ou "Toutes"), version, statut, nombre d'élèves inscrits
- Indicateur visuel pour distinguer programmes "Toutes" et programmes de section
- Filtres : par section, par statut
- Actions : éditer, dupliquer, archiver, voir structure
- Bouton "Nouveau programme"

**Visibilité selon le contexte :**
- Administrateur connecté à une section : voit les programmes de sa section + ceux marqués "Toutes"
- Administrateur club : voit tous les programmes de toutes les sections

### Administration - Éditeur de Programme

- Formulaire avec :
  - Code (identifiant unique)
  - Description
  - **Appartenance** : sélecteur "Toutes" ou section courante
  - (Le titre est extrait automatiquement du Markdown)
- **Deux modes d'entrée** :
  - Bouton "Importer un fichier .md" : sélection de fichier avec validation
  - Éditeur en ligne avec coloration syntaxique Markdown
- Panneau de prévisualisation en temps réel :
  - Structure extraite (arborescence leçons/sujets)
  - Compteurs : nombre de leçons, nombre de sujets
  - Erreurs de parsing signalées en rouge
- Validation de la syntaxe avant enregistrement
- Bouton "Exporter en .md" pour télécharger le programme

### Liste des Inscriptions (Formations en Cours)

- Tableau listant les inscriptions avec : élève, programme, statut, instructeur référent, date ouverture, dernière séance
- Indicateur visuel du statut :
  - **Vert** : Ouverte
  - **Orange** : Suspendue
  - **Gris** : Clôturée
  - **Rouge** : Abandonnée
- Filtres : par statut, par programme, par instructeur, par période
- Actions selon statut :
  - Ouverte : voir progression, nouvelle séance, suspendre, clôturer
  - Suspendue : voir progression, réactiver, abandonner
  - Clôturée/Abandonnée : voir progression (lecture seule)
- Bouton "Nouvelle inscription"

### Formulaire d'Inscription

- Sélecteur de pilote (avec recherche)
- Sélecteur de programme (parmi les programmes actifs)
- Affichage de la version du programme qui sera utilisée
- Sélecteur d'instructeur référent (optionnel)
- Zone de commentaires
- Bouton "Ouvrir la formation"

### Dialogue de Suspension/Clôture

- **Suspension** :
  - Champ obligatoire : motif de suspension
  - Boutons : Annuler / Confirmer la suspension

- **Clôture** :
  - Choix : "Formation terminée" ou "Abandon"
  - Champ optionnel : commentaire de clôture
  - Si abandon : champ obligatoire motif d'abandon
  - Boutons : Annuler / Confirmer

### Instructeur - Saisie de Séance

- **Sélection du type de séance** :
  - Case à cocher "Séance libre (sans inscription formelle)"
  - Par défaut : séance liée à une inscription
  
- **Si séance avec inscription** :
  - Sélecteur d'élève
  - Sélecteur d'inscription parmi les inscriptions ouvertes de l'élève
  - Le programme est automatiquement associé
  
- **Si séance libre** :
  - Sélecteur de pilote (avec recherche)
  - Sélecteur de programme de formation comme référence
  - Message : "Cette séance ne sera pas liée à une inscription formelle"

- Formulaire en deux parties :
  1. **Informations générales** : date, machine, durée, atterrissages, météo
  2. **Évaluation par leçon** : affichage des sujets de la leçon avec sélecteur de niveau
- Zone de commentaires libres
- Sélecteur des prochaines leçons (parmi les leçons du programme)
- Bouton de validation

### Instructeur - Historique des Séances

- Tableau listant toutes les séances avec : date, pilote, type (inscription/libre), programme, durée
- Badge visuel pour distinguer :
  - **Badge bleu "Formation"** : séance liée à une inscription
  - **Badge gris "Libre"** : séance libre
- Filtres : par pilote, par type, par programme, par période
- Actions : voir détail, modifier (si autorisé), dupliquer

### Instructeur - Liste des Élèves

- Tableau des élèves en formation avec : nom, programme, statut inscription, progression globale (% sujets acquis), dernière séance
- Indicateur visuel du statut (vert=ouverte, orange=suspendue)
- Colonne progression : affichage du pourcentage de sujets acquis avec mini-jauge colorée
- Actions : voir fiche de progression, nouvelle séance, gérer inscription (suspendre/clôturer)
- Filtres : par programme, par statut, par avancement
- Bouton "Nouvelle inscription" pour ouvrir une formation

### Fiche de Progression

- En-tête : élève, programme, dates de début/dernière séance
- **Indicateur de progression** : 
  - Barre de progression colorée avec pourcentage
  - Format : "X% des sujets acquis (N/Total)"
  - Couleur variable selon le pourcentage (rouge → orange → jaune → vert)
- Statistiques générales : nombre de séances, heures totales, atterrissages totaux
- Arborescence des leçons et sujets avec indicateurs visuels
- Possibilité d'expansion pour voir le détail
- Export PDF

### Pilote - Tableau de Bord Formation

- Carte "Ma formation" avec :
  - Programme actuel
  - **Progression globale** : barre de progression colorée + pourcentage de sujets acquis
  - Format : "X% des sujets acquis (N/Total)"
  - Prochaines leçons recommandées
  - Lien vers fiche détaillée

---

## Contraintes

- Les programmes de formation doivent pouvoir être versionnés pour gérer les évolutions
- Les données de progression sont liées à une version spécifique du programme (uniquement pour les inscriptions)
- La suppression d'un sujet dans un programme ne doit pas supprimer l'historique
- Un élève peut avoir plusieurs inscriptions ouvertes simultanément (formations différentes)
- **Les séances peuvent être enregistrées avec ou sans inscription** :
  - Séances avec inscription : contribuent à la fiche de progression officielle
  - Séances libres : archivées pour référence, ne génèrent pas de fiche de progression
- Les séances libres permettent de garder une trace des sujets abordés avant une inscription formelle
- Les programmes de section ne sont visibles que par les utilisateurs de cette section
- Les programmes marqués "Toutes" sont visibles par toutes les sections
- Les séances doivent pouvoir être liées aux vols existants dans GVV (optionnel)
- Le Markdown doit être validé syntaxiquement avant enregistrement
- Export PDF des fiches de progression (uniquement pour les inscriptions)

---

## Hors Périmètre

- Génération automatique de carnets de vol officiels
- Intégration avec des systèmes de certification externe
- Application mobile dédiée (interface web responsive uniquement)
- Vidéos ou supports multimédias intégrés aux programmes
- Système de messagerie intégré instructeur-élève
- Planification automatique des séances
- Migration automatique des séances libres vers une inscription lors de l'ouverture d'une formation

---

## Bénéfices Attendus

- Standardisation des formations au sein du club
- Suivi précis et traçable de la progression des élèves
- Meilleure continuité entre instructeurs (historique partagé)
- Réduction de la paperasse administrative
- Visibilité pour l'élève sur son avancement
- Statistiques de formation pour le club
- Identification rapide des points à retravailler
- **Archivage des séances de perfectionnement et remises à niveau** sans formalisme d'inscription
- **Historique consultable** pour évaluer le niveau d'un pilote avant ouverture de formation
