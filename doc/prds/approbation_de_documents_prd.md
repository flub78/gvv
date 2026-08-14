# PRD - Système d'Acceptations et Reconnaissances

## Objectif

Permettre de définir des éléments (documents, formations, briefings) devant être acceptés ou reconnus par les utilisateurs, avec traçabilité complète. Le système gère différentes catégories d'acceptation : prise en compte de documents, reconnaissance de délivrance de formation, etc. Les autorisations parentales pour les mineurs (passagers ou élèves) ainsi que l'acceptation des risques pour les briefing passager ULM sont prises en charge par la gestion des formulaires. On ne parle ici que des acceptations par des utilisateurs internes (membres du club). Le système doit garantir la conformité réglementaire et offrir une expérience utilisateur fluide.

## Stratégie de livraison

Le premier lot livré porte sur l'acceptation de documents déjà archivés (catégorie `document`, appuyée sur le module d'archivage documentaire existant — `archived_documents`/`document_types`) : un membre accepte en un clic un document qui lui est rattaché dans GVV. Les catégories à double validation (`formation`, `controle`) et la catégorie `briefing` restent définies dans le modèle mais ne sont pas prioritaires pour cette première livraison.

### Note d'orientation (évolution probable)

Plutôt que d'ajouter de nouvelles catégories ou de nouvelles sources d'éléments à faire accepter dans ce module, l'évolution privilégiée consiste à compléter le module Formulaires pour permettre de transformer une réponse de formulaire en document archivé (`archived_documents`) — ce document devient alors naturellement un élément à faire accepter par le mécanisme générique déjà en place, sans multiplier les sources spécialisées d'acceptation.

## Contexte

Dans le cadre de leurs activités réglementées, les clubs doivent archiver et tracer divers types d'acceptations par leurs membres :
- **Documents réglementaires** : déclaration initiale, renouvellements, manuel d'exploitation
- **Formations** : formation opérationnelle, facteurs humains, avec confirmation par l'instructeur ET l'élève
- **Contrôles de compétence** : vols de contrôle avec validation mutuelle

La réglementation exige une traçabilité des acceptations par signature des documents concernés.

## Catégories d'Acceptation

Le système supporte plusieurs catégories d'acceptation avec des comportements spécifiques :

| Catégorie | Description | Parties impliquées |
|-----------|-------------|-------------------|
| `document` | Acceptation simple d'un document | Un membre du club |
| `formation` | Reconnaissance de délivrance/réception de formation | Instructeur + Élève (double validation) |
| `controle` | Validation d'un contrôle de compétence | Contrôleur + Pilote contrôlé |
| `briefing` | Prise en compte d'un briefing | Un ou plusieurs membres |

L'administrateur peut définir de nouvelles catégories selon les besoins.

**Acceptation implicite du rédacteur** : pour les catégories à double validation (`formation`, `controle`), la personne qui saisit l'information est réputée l'avoir acceptée par le simple fait de la saisir — son acceptation n'est pas redemandée dans une étape séparée. Seule l'autre partie doit valider explicitement pour compléter la double validation. Exemple : l'instructeur qui valide la délivrance d'une formation n'a pas besoin de confirmer une seconde fois qu'il l'accepte, seul l'élève doit confirmer la réception.

## Périmètre Fonctionnel

### Utilisateurs concernés

Seuls les membres du club identifiés dans GVV, connectés lors de l'acceptation, sont concernés par ce système. Aucune acceptation par une personne non-inscrite dans GVV (passager, tuteur/parent, tiers externe) n'est traitée ici — ces cas sont couverts par le module Formulaires.

---

## Cas d'Utilisation

### Administrateur

**Gestion des éléments à accepter**
- Définir un nouvel élément à faire accepter (document, formation, contrôle, briefing)
- Choisir la catégorie d'acceptation
- Renseigner la date de création/version
- Indiquer le niveau d'obligation de l'acceptation : facultatif, obligatoire non bloquant, ou obligatoire bloquant (voir Canal de notification et niveaux d'obligation)
- Associer l'élément à un utilisateur ou à une ou plusieurs catégories d'utilisateurs qui devront valider (pilotes, instructeurs, membres du bureau, etc.)
- Pour les catégories à double validation : définir les deux rôles impliqués (le rôle du rédacteur, dont l'acceptation est implicite, et le rôle de l'autre partie, qui doit valider explicitement)

**Suivi des acceptations**
- Consulter la liste des acceptations par élément
- Identifier rapidement les utilisateurs ciblés qui n'ont pas encore accepté
- Pour les doubles validations : voir le statut de chaque partie (instructeur validé, élève en attente, etc.)
- Exporter les données d'acceptation

### Membre

**Notification et acceptation simple**
- Être informé des éléments à prendre en compte via un message du jour (canal de notification par défaut) donnant accès à la page de validation
- Lire le contenu du document/élément
- **Accepter en un clic** via un bouton d'acceptation simple
- Possibilité de refuser explicitement si nécessaire
- Consulter l'historique de ses acceptations et refus
- Relire un élément précédemment traité
- Accepter un élément précédemment refusé

**Formule d'acceptation**
L'acceptation enregistre automatiquement :
> "Je soussigné(e) [Prénom Nom], membre du club identifié par le système, reconnais avoir pris connaissance et accepter [titre de l'élément] en date du [date]."

### Instructeur

**Délivrance de formation**
- Sélectionner l'élève concerné
- Sélectionner le type de formation dispensée
- Valider la délivrance de la formation en un clic — cette action vaut acceptation de l'instructeur, sans étape de confirmation distincte
- L'élève reçoit une notification pour confirmer réception
- Consulter l'historique des formations dispensées et leur statut de validation

**Formule de délivrance**
> "Je soussigné(e) [Prénom Nom Instructeur], certifie avoir dispensé la formation [titre] à [Prénom Nom Élève] le [date]."

### Élève

**Réception de formation**
- Être notifié qu'une formation lui a été attribuée par un instructeur
- Consulter le contenu de la formation
- Confirmer la réception en un clic
- Consulter l'historique des formations reçues

**Formule de réception**
> "Je soussigné(e) [Prénom Nom Élève], reconnais avoir reçu la formation [titre] dispensée par [Prénom Nom Instructeur] le [date]."

---

## Contraintes

- Les documents sont au format PDF et archivés sur le serveur
- L'horodatage des acceptations doit être fiable
- L'identité de l'utilisateur est garantie par l'authentification GVV

### Canal de notification et niveaux d'obligation

- Par défaut, la notification d'une validation nécessaire se fait par message du jour : le message informe l'utilisateur qu'une validation est nécessaire et donne le lien vers la page de validation correspondante.
- Pour la catégorie `document`, la page de validation permet simplement de visualiser le document archivé à valider (viewer intégré, voir Processus de lecture obligatoire).
- Un élément à accepter porte l'un des trois niveaux d'obligation suivants :
  - **Facultatif** : l'utilisateur peut accepter, refuser, ignorer ou reporter (bouton "Plus tard") librement.
  - **Obligatoire non bloquant** : le message du jour associé ne peut pas être masqué tant que la validation n'a pas été faite (pas de bouton "Plus tard" ni de masquage), mais l'utilisateur peut continuer à utiliser normalement le reste de GVV.
  - **Obligatoire bloquant** : comme le niveau non bloquant, mais en plus l'utilisateur ne peut effectuer aucune autre action dans GVV tant qu'il n'a pas validé l'élément — le blocage s'étend à l'ensemble de l'application, pas seulement au message du jour.
- Le blocage (niveau obligatoire bloquant) exempte toujours la déconnexion et la page de validation elle-même. Les club-admins ne sont jamais bloqués par une acceptation, quel que soit le niveau d'obligation, pour ne pas risquer de perdre l'accès à l'administration du club.

### Processus de lecture obligatoire

Pour garantir que l'utilisateur a bien pris connaissance du document :
- Le document PDF doit être affiché dans un viewer intégré
- L'utilisateur doit faire défiler l'intégralité du document
- Le bouton "Accepter" n'apparaît qu'à la fin du document (après défilement complet)
- Un message informatif est affiché au début de la lecture :
  > "Veuillez lire l'intégralité du document. Le bouton d'acceptation apparaîtra à la fin."

### Date limite d'acceptation

- L'administrateur peut définir une date limite d'acceptation pour chaque élément
- Pour un élément facultatif, l'utilisateur peut reporter l'acceptation (bouton "Plus tard") tant que la date limite n'est pas atteinte — un élément obligatoire (bloquant ou non) ne peut pas être reporté, voir Canal de notification et niveaux d'obligation
- L'interface affiche clairement la date limite : "À accepter avant le [date]"
- À l'approche de la date limite, le rappel devient plus visible (ex: couleur d'alerte)
- Après la date limite, l'acceptation reste possible mais l'élément est signalé comme "en retard" dans les rapports

## Interfaces Utilisateur

### Administration

**Liste des éléments**
- Tableau des éléments définis avec catégorie, statut (actif/inactif) et niveau d'obligation
- Nombre d'acceptations par élément
- Pour double validation : nombre de validations complètes vs partielles
- Actions : éditer, activer/désactiver, voir acceptations

**Formulaire de création/édition**
- Titre de l'élément
- Catégorie d'acceptation (document, formation, contrôle, briefing)
- Fichier PDF à téléverser (stocké sur le serveur)
- Date de version
- Niveau d'obligation : facultatif / obligatoire non bloquant / obligatoire bloquant
- **Date limite d'acceptation** (optionnelle)
- Ciblage : un utilisateur individuel (ex: un pilote précis) ou une ou plusieurs catégories d'utilisateurs
- Pour double validation : rôles impliqués (ex: instructeur/élève)

**Suivi des acceptations**
- Liste des acceptations avec statut (complète, partielle, refusée, en attente)
- Indicateur de respect de la date limite (dans les temps / en retard)
- Date et heure de chaque action
- Pour double validation : statut de chaque partie (ex: "Instructeur: validé, Élève: en attente")
- Filtre pour afficher uniquement les acceptations en retard ou proches de l'échéance

### Membre

**Tableau de bord**
- Message du jour (canal par défaut) pour chaque élément en attente, avec lien vers sa page de validation — non masquable pour un élément obligatoire (bloquant ou non), jusqu'à validation
- Badge ou notification indiquant le nombre d'éléments en attente, en complément du message du jour
- Liste des éléments à traiter avec :
  - Titre et date limite ("À accepter avant le [date]")
  - Indicateur visuel si proche de la date limite ou en retard
  - Bouton "Lire et accepter"
  - Bouton "Plus tard" (élément facultatif uniquement, si date limite non atteinte)

**Écran de lecture et acceptation**
- Message informatif en haut : "Veuillez lire l'intégralité du document. Le bouton d'acceptation apparaîtra à la fin."
- Viewer PDF intégré avec le document complet
- Détection du défilement complet
- En bas du document (après défilement) :
  - **Bouton "Accepter"** (action principale)
  - Bouton "Refuser" (optionnel)

**Historique personnel**
- Liste des éléments traités avec statut, date d'acceptation et éventuel retard
- Possibilité de relire et modifier sa réponse

### Instructeur

**Écran de délivrance de formation**
- Sélecteur d'élève
- Sélecteur de type de formation
- Date de la formation (par défaut : aujourd'hui)
- Bouton "Valider la délivrance"

**Suivi des formations dispensées**
- Liste des formations avec statut : en attente de confirmation élève / confirmée
- Filtre par élève, par type de formation, par période

### Élève

**Notifications de formation**
- Liste des formations à confirmer
- Pour chaque formation : contenu, instructeur, date
- **Bouton "Confirmer réception"** (action en un clic)

**Historique des formations reçues**
- Liste des formations confirmées avec dates et instructeurs

---

## Hors Périmètre

- Acceptations par des personnes non-inscrites dans GVV (passagers, tuteurs/parents, tiers externes) — couvertes par le module Formulaires, y compris les autorisations parentales et l'acceptation des risques pour le briefing passager ULM
- Signature électronique certifiée (eIDAS) - signature simple uniquement
- Workflow d'approbation multi-niveaux
- Versioning automatique des documents avec migration des acceptations
- Intégration avec des systèmes de GED externes

## Bénéfices Attendus

- Conformité réglementaire pour la prise en compte des documents internes (déclaration initiale, manuel d'exploitation, etc.)
- Traçabilité complète des acceptations (documents, formations, contrôles)
- Réduction de la gestion papier
- Acceptation en un clic pour les membres connectés
- Double validation instructeur/élève pour les formations
- Visibilité immédiate des éléments non acceptés ou en attente de confirmation
