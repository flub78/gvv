# PRD - Gestion des Séances de Formation Théoriques

**Référence** : Évolution de [suivi_formation_prd.md](suivi_formation_prd.md)
**Date** : 2026-02-28
**Statut** : Proposition

---

## Objectif

Étendre le module de suivi de formation pour gérer les séances d'instruction théorique au sol, sans vol associé, pouvant regrouper plusieurs élèves ou stagiaires. Permettre aux responsables pédagogiques de structurer les types de séances de formation, et aux administrateurs de produire des rapports annuels consolidant séances pratiques et théoriques.

---

## Contexte

Le module de suivi de formation actuel (voir `suivi_formation_prd.md`) gère uniquement les séances liées à un vol (aéronef, durée, atterrissages, météo). La réglementation et les bonnes pratiques pédagogiques exigent également des séances d'instruction au sol : cours théoriques, réunions sécurité, briefings de groupe, débriefings collectifs. Ces séances doivent être tracées et intégrées dans les rapports d'activité.

### Limites actuelles

- La table `formation_seances` impose un aéronef, une durée de vol et des atterrissages (champs NOT NULL).
- Chaque séance est associée à un seul élève (`pilote_id NOT NULL`).
- Les rapports du contrôleur `formation_rapports` ne couvrent que l'activité en vol.
- Les catégories de séances sont limités à formation, remise en vol, ré-entraînement et contrôle de pilote VLD

---

## Rôles et Permissions

### Responsable Pédagogique (Administrateur)

- Définir et gérer les types de séances de formation (vol ou sol).
- Consulter les rapports d'activité globaux (pratiques et théoriques).

### Instructeur

- Enregistrer une séance théorique avec un ou plusieurs élèves.
- Associer des commentaires à la séance.

### Administrateur Club

- Générer les rapports annuels consolidés sur l'activité de formation.

---

## Fonctionnalités

### 1. Types de Séances de Formation

#### 1.1 Définition des Types

Le responsable pédagogique peut créer et gérer un référentiel de types de séances de formation.

| Attribut | Description | Obligatoire |
|----------|-------------|-------------|
| Nom | Libellé court du type (ex : « Vol biplace », « Cours sol météo ») | Oui |
| Nature | `vol` ou `theorique` | Oui |
| Description | Description détaillée du type de séance | Non |
| Périodicité maximale | Délai maximal (en jours) entre deux séances de ce type pour un même élève. Zéro ou vide = pas de contrainte. | Non |
| Actif | Indique si ce type est utilisable lors de la saisie | Oui |

La périodicité maximale permet de s'assurer que chaque élève reçoit une séance de ce type à une fréquence minimale, conformément aux engagements pédagogiques ou réglementaires de l'association (ex. : visite médicale annuelle, cours sol obligatoire tous les 6 mois).

**Exemples de types**:

| Nom | Nature | Périodicité max. |
|-----|--------|-----------------|
| Vol biplace d'instruction | vol | — |
| Vol solo supervisé | vol | — |
| Cours sol – Aérologie | theorique | 365 j |
| Cours sol – Navigation | theorique | 365 j |
| Briefing de groupe | theorique | — |
| Débriefing collectif | theorique | — |

#### 1.2 Règles de Gestion

- Un type de nature `vol` exige la saisie d'un aéronef, d'une durée et d'un nombre d'atterrissages.
- Un type de nature `theorique` n'exige pas ces données.
- Un type ne peut pas être supprimé s'il est référencé par des séances existantes ; il peut être désactivé.
- Un jeu de types par défaut est proposé à l'installation (vol biplace, cours sol).
- Si une périodicité maximale est définie, les rapports signalent les élèves dont le délai depuis la dernière séance de ce type dépasse le seuil.

---

### 2. Séances Théoriques

#### 2.1 Attributs d'une Séance Théorique

| Attribut | Description | Obligatoire |
|----------|-------------|-------------|
| Date | Date de la séance | Oui |
| Type de séance | Référence à un type de nature `theorique` | Oui |
| Instructeur | Instructeur dispensant la séance | Oui |
| Programme | Programme de formation de référence | Non |
| Lieu | Salle ou lieu de la séance (champ libre) | Non |
| Durée | Durée de la séance (HH:MM) | Non |
| Commentaires | Observations générales sur la séance | Non |
| Liste des participants | Un ou plusieurs élèves ou stagiaires | Oui (min. 1) |

Une séance théorique n'a pas de champ aéronef, atterrissages, ni météo en vol.

#### 2.2 Gestion des Participants

Chaque participant peut recevoir des commentaires généraux de séance. Il peut consulter les séances auxquelles il a assisté et retrouver les sujets et conclusions en description.

**Règles de gestion** :
- Un participant peut être un membre inscrit à une formation ou tout autre membre du club (stagiaire, visiteur).
- Pas d'évaluation individuelles pour les séances collectives
- Un programme peut-être associé à la séance.

---

### 3. Architecture de Données : Analyse des Approches

La gestion de plusieurs élèves par séance implique un choix structurant. Deux approches sont comparées.

#### Option A – Une ligne par (séance × élève) avec groupement

Un champ `groupe_id` est ajouté à la table `formation_seances` existante. Pour une séance théorique de groupe, le système crée une ligne par participant, toutes avec le même `groupe_id`. Les données communes (date, instructeur, commentaires généraux) sont dupliquées sur chaque ligne.

**Avantages** :
- Compatible avec le modèle existant (évaluations par élève, fiches de progression).
- Requêtes et vues existantes inchangées.
- Implémentation minimale.

**Inconvénients** :
- Redondance des données communes (date, instructeur, contenu) : une modification doit être propagée sur toutes les lignes du groupe.
- Risque d'incohérence si les lignes d'un même groupe divergent.
- Complexifie les rapports (dédoublonnage nécessaire pour compter les séances).

#### Option B – Table séance + table participants

Une table `formation_seances_participants` est créée. La table `formation_seances` est étendue :
- Les champs `machine_id`, `duree`, `nb_atterrissages` deviennent nullable (absents pour les séances théoriques).
- `pilote_id` est déplacé dans la table des participants pour les séances théoriques, ou conservé pour les séances en vol (rétrocompatible).
- Les évaluations (`formation_evaluations`) associent un `pilote_id` pour les séances multi-participants.

**Avantages** :
- Pas de redondance : une seule ligne par séance pour les données communes.
- Gestion des participants flexible (ajout/suppression).
- Rapports simplifiés (une séance = une ligne).

**Inconvénients** :
- Rupture partielle du modèle actuel : les évaluations doivent être étendues avec `pilote_id`.
- Requêtes par élève légèrement plus complexes (jointure sur `formation_seances_participants`).
- Champs nullable sur `formation_seances` introduisent une asymétrie selon le type.

#### Recommandation

L'**Option B** est préconisée pour sa cohérence à long terme : une séance est un fait unique, indépendant du nombre de participants. Les duplications de l'Option A créent un risque d'incohérence difficile à prévenir. La conception détaillée précisera le schéma exact et la migration.

Décision, l'option B sera implémenté, en gardant le pilote_id pour les séances en vol afin de maintenir la rétrocompatibilité.

---

### 4. Rapports et Statistiques Annuels

#### 4.1 Rapport Annuel d'Activité de Formation

L'administrateur peut générer un rapport annuel regroupant les séances pratiques (vol) et théoriques.

**Critères de filtrage** :
- Année (obligatoire)
- Programme de formation (optionnel)
- Instructeur (optionnel)
- Nature des séances : vol, théorique, ou toutes

**Données produites par instructeur** :

| Colonne | Description |
|---------|-------------|
| Instructeur | Nom et prénom |
| Séances en vol | Nombre de séances de vol dispensées |
| Heures vol | Total des heures d'instruction en vol |
| Séances théoriques | Nombre de séances théoriques dispensées |
| Heures sol | Total des heures de cours au sol |
| Nombre d'élèves distincts | Nombre d'élèves différents instruits |

**Données produites par programme** :

| Colonne | Description |
|---------|-------------|
| Programme | Nom du programme |
| Inscriptions actives | Élèves en formation sur l'année |
| Séances vol | Nombre total de séances en vol |
| Séances théoriques | Nombre total de séances au sol |
| Total heures formation | Vol + sol |

#### 4.2 Accès aux Dates de Séances

L'administrateur peut consulter une liste chronologique de toutes les séances de formation (vol et théoriques) avec :
- Date, instructeur, type de séance, nombre d'élèves, durée
- Filtres : période, instructeur, type, programme

Cette liste est exportable (CSV ou PDF).

#### 4.3 Rapport par Élève

L'administrateur peut consulter pour chaque élève le récapitulatif de ses séances sur une année :
- Séances en vol : nombre, heures, atterrissages
- Séances théoriques : nombre, heures
- Progression dans son programme de formation

#### 4.4 Rapport de Conformité aux Périodicités

Pour les types de séances avec une périodicité maximale définie, l'administrateur peut obtenir un rapport de conformité listant :
- Par type de séance concerné : la liste des élèves actifs dont la dernière séance de ce type est absente ou dépasse le seuil défini.
- Colonnes : élève, dernière séance (date), délai écoulé (jours), statut (conforme / dépassé).
- Indicateur visuel : ligne en rouge si le délai est dépassé, en orange si le seuil est atteint à moins de 30 jours.

Ce rapport permet à l'association de démontrer sa conformité à ses engagements pédagogiques ou réglementaires.

---

## Interfaces Utilisateur

### Responsable Pédagogique – Gestion des Types de Séances

- Tableau listant les types (nom, nature, description, statut actif)
- Actions : créer, modifier, désactiver
- Indicateur visuel : badge « Vol » (bleu) / « Sol » (vert)

### Instructeur – Saisie de Séance Théorique

Formulaire dédié accessible depuis le menu « Nouvelle séance » :

1. **Type de séance** : sélecteur filtré sur les types de nature `theorique`
2. **Date**
3. **Programme** (optionnel) : sélecteur de programme de formation
4. **Lieu** et **Durée** (optionnels)
5. **Participants** : sélecteur multi-entrées de membres (avec recherche par nom)
6. **Commentaires généraux**

### Instructeur – Historique des Séances

La liste existante des séances est étendue pour inclure les séances théoriques :
- Colonne **Nature** : badge « Vol » ou « Sol »
- Colonne **Participants** : nombre d'élèves (« 1 élève » ou « 3 élèves »)
- Filtre par nature

### Administration – Rapports Annuels

- Sélection de l'année et des filtres
- Tableau de synthèse par instructeur et par programme
- Bouton d'export CSV et PDF
- Lien vers la liste détaillée des séances

---

## Contraintes

- Rétrocompatibilité : les séances en vol existantes ne doivent pas être impactées.
- Les séances théoriques doivent apparaître dans les fiches de progression si les sujets évalués sont associés à un programme de formation.
- L'activation de la fonctionnalité reste soumise au flag `gestion_formations`.
- Les types de séances et les séances théoriques font partie du périmètre activé par ce flag.
- Les rapports annuels couvrent les deux natures de séances dès l'activation.

---

## Hors Périmètre

- Planification automatique des séances théoriques (convocations, calendrier).
- Gestion des présences et des absences (feuille d'émargement numérique).
- Contenu pédagogique des cours théoriques (documents, présentations).
- Attestation ou certification de participation à une séance théorique.
- Notification automatique des participants.

---

## Bénéfices Attendus

- Traçabilité complète de l'activité pédagogique (vol et sol).
- Conformité avec les exigences de déclaration d'activité des clubs affiliés.
- Meilleure planification pédagogique : identification des lacunes théoriques dans la progression.
- Rapports annuels produits sans saisie manuelle hors GVV.
