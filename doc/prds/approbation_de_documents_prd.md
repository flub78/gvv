# PRD - Système d'Acceptations et Reconnaissances

## Objectif

Permettre de définir des éléments (documents, formations, briefings) devant être acceptés ou reconnus par les utilisateurs, avec traçabilité complète. Le système gère différentes catégories d'acceptation : prise en compte de documents, reconnaissance de délivrance de formation, etc. Autorisations parentales pour les mineurs (passagers ou élèves) sont également prises en charge. Les acceptations peuvent être réalisées par des utilisateurs internes (membres du club) ou externes (passagers, parents). Le système doit garantir la conformité réglementaire et offrir une expérience utilisateur fluide.

## Contexte

Dans le cadre des vols de découverte ULM et d'autres activités réglementées, les clubs doivent archiver et tracer divers types d'acceptations :
- **Documents réglementaires** : déclaration initiale, renouvellements, manuel d'exploitation, briefing passager
- **Formations** : formation opérationnelle, facteurs humains, avec confirmation par l'instructeur ET l'élève
- **Contrôles de compétence** : vols de contrôle avec validation mutuelle
- **Conditions passager** : acceptation des conditions de vol par un passager de vol de découverte
- **Autorisations parentales** : autorisation signée par un parent ou tuteur légal pour un mineur (passager ou élève)

La réglementation exige une traçabilité des acceptations par signature des documents concernés.

## Catégories d'Acceptation

Le système supporte plusieurs catégories d'acceptation avec des comportements spécifiques :

| Catégorie | Description | Parties impliquées |
|-----------|-------------|-------------------|
| `document` | Acceptation simple d'un document | Une seule personne (interne ou externe) |
| `formation` | Reconnaissance de délivrance/réception de formation | Instructeur + Élève (double validation) |
| `controle` | Validation d'un contrôle de compétence | Contrôleur + Pilote contrôlé |
| `briefing` | Prise en compte d'un briefing | Une ou plusieurs personnes |
| `autorisation` | Autorisation donnée par un tiers pour un bénéficiaire (ex: autorisation parentale) | Signataire (parent/tuteur) pour le compte d'un bénéficiaire (mineur) |

L'administrateur peut définir de nouvelles catégories selon les besoins.

## Périmètre Fonctionnel

### Types d'Utilisateurs

**Utilisateurs internes** : Membres du club identifiés dans le système, connectés lors de l'acceptation.

**Utilisateurs externes** : Personnes non-membres (passagers, visiteurs) qui ne sont pas connectées lors de leur acceptation.

---

## Cas d'Utilisation

### Administrateur

**Gestion des éléments à accepter**
- Définir un nouvel élément à faire accepter (document, formation, contrôle, briefing)
- Choisir la catégorie d'acceptation
- Spécifier si l'élément est destiné aux utilisateurs internes ou externes
- Renseigner la date de création/version
- Indiquer si l'acceptation est obligatoire ou facultative
- Associer l'élément à une ou plusieurs catégories d'utilisateurs qui devront valider (pilotes, instructeurs, membres du bureau, etc.)
- Pour les catégories à double validation : définir les deux rôles impliqués

**Suivi des acceptations**
- Consulter la liste des acceptations par élément
- Identifier rapidement les utilisateurs ciblés qui n'ont pas encore accepté
- Pour les doubles validations : voir le statut de chaque partie (instructeur validé, élève en attente, etc.)
- Exporter les données d'acceptation

**Rattachement d'une acceptation externe à un pilote**
- Les acceptations signées par des utilisateurs externes ne sont initialement rattachées à aucun pilote dans le système
- Un administrateur ou responsable peut ultérieurement rattacher une acceptation externe au dossier d'un pilote inscrit
- Cas d'usage principal : un parent signe une autorisation parentale avant que l'élève mineur ne soit inscrit au club ; une fois l'inscription effectuée, l'autorisation est rattachée au dossier de l'élève
- Le rattachement est une action explicite (sélection du pilote dans la liste des membres) et ne modifie pas l'acceptation elle-même (signataire, date, signature inchangés)
- L'historique du rattachement est tracé (qui a rattaché, quand)

### Utilisateur Interne (Membre)

**Notification et acceptation simple**
- Être informé des éléments à prendre en compte (notification à la connexion ou dans un tableau de bord)
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
- Valider la délivrance de la formation en un clic
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

### Responsable Club / Pilote Vol de Découverte

**Initiation d'une signature externe**
- Déclencher une session de signature pour un document externe
- Quatre modes de présentation :
  - **Mode direct** : présenter la page de signature sur un smartphone/tablette au club
  - **Mode lien** : générer un lien temporaire à envoyer sur le smartphone de la personne
  - **Mode QR code** : générer un QR code à usage unique pointant vers la page de signature temporaire, affichable à l'écran ou imprimable
  - **Mode papier** : imprimer le formulaire, le faire signer manuscritement, puis télécharger la copie numérisée
- Le lien temporaire (modes lien et QR code) a une durée de validité limitée (ex: 24h) et est à usage unique
- Visualiser les sessions de signature en cours et leur statut

**Mode papier (formulaire imprimé)**

Ce mode est adapté aux situations où la signature numérique n'est pas pratique (pas de tablette disponible, passager peu à l'aise avec le numérique, conditions terrain).

Processus :
1. Le pilote imprime le formulaire vierge depuis le système (PDF pré-formaté)
2. Le passager remplit et signe le formulaire papier
3. Le pilote prend en photo ou scanne le document signé
4. Le pilote télécharge l'image dans le système en renseignant :
   - Nom et prénom du signataire (tels qu'inscrits sur le formulaire)
   - Date de signature
   - Fichier image (JPEG, PNG) ou PDF du document signé
5. Le système archive le document avec les métadonnées

**Formule d'attestation pilote** (enregistrée automatiquement) :
> "Je soussigné(e) [Prénom Nom Pilote], certifie que le document ci-joint a été signé en ma présence par [Prénom Nom Passager] le [date de signature]."

### Passager de Vol de Découverte

Le passager est un utilisateur externe. Le responsable club ou pilote initie une session de signature (mode direct, lien ou papier) pour le document "Conditions de vol de découverte".

**Acceptation des conditions de vol**
- Lire les conditions de vol présentées via le viewer PDF intégré (défilement obligatoire)
- Saisir son nom et prénom
- Signer (signature tactile, upload de document signé, ou mode papier)
- Le système enregistre l'acceptation avec horodatage

**Formule d'acceptation passager** (enregistrée automatiquement) :
> "Je soussigné(e) [Prénom Nom Passager], reconnais avoir pris connaissance et accepter les conditions de vol de découverte en date du [date]."

### Parent / Tuteur Légal (Autorisation Parentale)

Le parent ou tuteur est un utilisateur externe qui signe pour le compte d'un bénéficiaire mineur. Le responsable club ou pilote initie la session de signature.

**Signature d'une autorisation parentale**
- Lire le document d'autorisation via le viewer PDF intégré (défilement obligatoire)
- Saisir ses informations :
  - Nom et prénom du signataire (parent/tuteur)
  - Qualité du signataire : père, mère, tuteur légal
  - Nom et prénom du bénéficiaire (mineur)
- Signer (signature tactile, upload de document signé, ou mode papier)
- Le système enregistre l'autorisation avec horodatage
- L'autorisation n'est pas nécessairement rattachée à un pilote au moment de la signature (le mineur peut ne pas encore être inscrit)
- Le rattachement au dossier du pilote mineur peut être effectué ultérieurement par un administrateur ou responsable

**Formule d'autorisation parentale** (enregistrée automatiquement) :
> "Je soussigné(e) [Prénom Nom Signataire], en qualité de [qualité], autorise [Prénom Nom Mineur] à [objet de l'autorisation] en date du [date]."

### Utilisateur Externe

**Consultation et signature** (via session initiée par un responsable)
- Accéder au document via le lien temporaire fourni
- Lire le document en ligne
- Télécharger le document au format PDF
- Saisir son nom et prénom
- Choisir l'une des méthodes de signature :
  - Télécharger le document signé manuellement (scan ou photo)
  - Signer électroniquement sur téléphone ou tablette graphique

**Note** : Aucune page publique permanente n'est exposée. L'accès est uniquement possible via un lien temporaire généré par un responsable club.

---

## Contraintes

- Les documents sont au format PDF et archivés sur le serveur
- L'horodatage des acceptations doit être fiable
- Pour les utilisateurs internes, l'identité est garantie par l'authentification
- Pour les utilisateurs externes, la signature manuscrite ou électronique fait foi
- Les données personnelles des utilisateurs externes doivent être protégées (RGPD)
- Les liens de signature externe (mode lien et mode QR code) doivent être temporaires, à usage unique, et non devinables (token aléatoire)
- Le QR code encode le lien temporaire à usage unique ; une fois utilisé ou expiré, le QR code devient invalide
- Pour le mode papier : les fichiers uploadés (JPEG, PNG, PDF) sont limités à 10 Mo
- Le pilote qui uploade un document papier engage sa responsabilité via l'attestation de présence

### Processus de lecture obligatoire

Pour garantir que l'utilisateur a bien pris connaissance du document :
- Le document PDF doit être affiché dans un viewer intégré
- L'utilisateur doit faire défiler l'intégralité du document
- Le bouton "Accepter" n'apparaît qu'à la fin du document (après défilement complet)
- Un message informatif est affiché au début de la lecture :
  > "Veuillez lire l'intégralité du document. Le bouton d'acceptation apparaîtra à la fin."

### Date limite d'acceptation

- L'administrateur peut définir une date limite d'acceptation pour chaque élément
- L'utilisateur peut reporter l'acceptation (bouton "Plus tard") tant que la date limite n'est pas atteinte
- L'interface affiche clairement la date limite : "À accepter avant le [date]"
- À l'approche de la date limite, le rappel devient plus visible (ex: couleur d'alerte)
- Après la date limite, l'acceptation reste possible mais l'élément est signalé comme "en retard" dans les rapports

## Interfaces Utilisateur

### Administration

**Liste des éléments**
- Tableau des éléments définis avec catégorie et statut (actif/inactif)
- Nombre d'acceptations par élément
- Pour double validation : nombre de validations complètes vs partielles
- Actions : éditer, activer/désactiver, voir acceptations

**Formulaire de création/édition**
- Titre de l'élément
- Catégorie d'acceptation (document, formation, contrôle, briefing)
- Fichier PDF à téléverser (stocké sur le serveur)
- Type : interne ou externe
- Date de version
- Obligatoire : oui/non
- **Date limite d'acceptation** (optionnelle)
- Catégories d'utilisateurs ciblées (pour éléments internes)
- Pour double validation : rôles impliqués (ex: instructeur/élève)

**Suivi des acceptations**
- Liste des acceptations avec statut (complète, partielle, refusée, en attente)
- Indicateur de respect de la date limite (dans les temps / en retard)
- Date et heure de chaque action
- Pour double validation : statut de chaque partie (ex: "Instructeur: validé, Élève: en attente")
- Pour les externes : nom, prénom, fichier de signature, responsable ayant initié la session, mode utilisé (direct/lien/QR code/papier)
- Filtre pour afficher uniquement les acceptations en retard ou proches de l'échéance
- Pour les acceptations externes non rattachées : bouton "Rattacher à un pilote" ouvrant un sélecteur de membre
- Indicateur visuel distinguant les acceptations rattachées et non rattachées à un pilote

### Utilisateur Interne (Membre)

**Tableau de bord**
- Badge ou notification indiquant le nombre d'éléments en attente
- Liste des éléments à traiter avec :
  - Titre et date limite ("À accepter avant le [date]")
  - Indicateur visuel si proche de la date limite ou en retard
  - Bouton "Lire et accepter"
  - Bouton "Plus tard" (si date limite non atteinte)

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

### Responsable Club / Pilote Vol de Découverte

**Initiation de signature externe**
- Sélection du document à faire signer
- Choix du mode :
  - Bouton "Présenter sur cet écran" → affiche directement la page de signature
  - Bouton "Envoyer un lien" → génère un lien temporaire avec option de copie ou envoi par email/SMS
  - Bouton "Générer un QR code" → affiche un QR code à usage unique que la personne scanne avec son smartphone pour accéder à la page de signature ; possibilité de télécharger ou imprimer le QR code
  - Bouton "Mode papier" → accède au formulaire d'upload de document signé
- Liste des sessions en cours avec statut (en attente, signé, expiré) et mode utilisé

**Mode papier**
- Bouton "Imprimer le formulaire vierge" → génère et télécharge le PDF à imprimer
- Formulaire d'upload après signature :
  - Champ : Nom du signataire
  - Champ : Prénom du signataire
  - Pour la catégorie `autorisation` : champs supplémentaires — qualité (père, mère, tuteur légal), nom et prénom du bénéficiaire (mineur)
  - Champ : Date de signature (par défaut : aujourd'hui)
  - Zone d'upload : glisser-déposer ou sélection de fichier (formats acceptés : JPEG, PNG, PDF)
  - Case à cocher : "J'atteste que ce document a été signé en ma présence"
  - Bouton "Valider et archiver"
- Message de confirmation après validation

### Utilisateur Externe

**Page de signature** (accessible uniquement via lien temporaire)
- Message informatif en haut : "Veuillez lire l'intégralité du document. Le formulaire de signature apparaîtra à la fin."
- Viewer PDF intégré avec défilement obligatoire
- Bouton de téléchargement du PDF
- Après défilement complet, affichage du formulaire :
  - Champs : nom, prénom du signataire
  - Pour la catégorie `autorisation` : champs supplémentaires — qualité (père, mère, tuteur légal), nom et prénom du bénéficiaire (mineur)
  - Zone de signature tactile ou upload de fichier signé
  - Bouton de validation
- Message d'erreur explicite si le lien est expiré ou invalide

---

## Hors Périmètre

- Signature électronique certifiée (eIDAS) - signature simple uniquement
- Workflow d'approbation multi-niveaux
- Versioning automatique des documents avec migration des acceptations
- Intégration avec des systèmes de GED externes

## Bénéfices Attendus

- Conformité réglementaire pour les vols de découverte ULM
- Traçabilité complète des acceptations (documents, formations, contrôles, autorisations parentales)
- Réduction de la gestion papier
- Simplification du processus pour les passagers (signature sur tablette ou papier)
- Gestion des autorisations parentales pour les mineurs (passagers ou élèves)
- Acceptation en un clic pour les membres connectés
- Double validation instructeur/élève pour les formations
- Visibilité immédiate des éléments non acceptés ou en attente de confirmation
