# PRD - Relances des comptes débiteurs

## 1. Objectif

Permettre aux trésoriers de repérer rapidement les membres dont la dette devient importante, puis de préparer, envoyer et suivre des relances email à partir d’une interface dédiée.

## 2. Contexte

Aujourd’hui, le suivi des mauvais payeurs repose principalement sur des consultations manuelles des comptes. Ce manque d’outillage rend plus difficile :
- l’identification rapide des membres en situation d’impayé,
- la priorisation des dossiers les plus critiques,
- la préparation homogène des relances,
- la conservation d’un historique clair des emails envoyés.

Ce PRD définit une page de relances avec un tableau de pilotage des dettes, une page de détail par membre et un système de template de relance configurable.

## 3. Périmètre

### 3.1 Inclus

- Accès à la fonctionnalité depuis le menu comptable.
- Accès à la fonctionnalité depuis une carte dédiée sur le dashboard Trésorerie.
- Consultation d’une liste des dettes classées par dette totale décroissante.
- Paramétrage de deux seuils d’alerte :
  - alarme en jaune au-delà de 300 € de dette,
  - critique en rouge au-delà de 500 € de dette.
- Possibilité de modifier ces seuils depuis la page.
- Mise en évidence visuelle des dettes selon le seuil atteint.
- Accès au détail des relances par utilisateur.
- Préparation d’un email de relance modifiable par l’opérateur.
- Envoi de la relance au membre concerné avec copie aux trésoriers du club.
- Historique des relances déjà effectuées pour un membre.
- Consultation du contenu réellement envoyé pour chaque relance.
- Configuration et sauvegarde du template email par défaut.

### 3.2 Hors périmètre

- Automatisation périodique des relances sans action humaine.
- Envoi de SMS ou de notifications push.
- Calcul comptable des dettes lui-même si les données de dette existent déjà ailleurs dans GVV.
- Règles avancées de segmentation par section au-delà des colonnes de dette affichées.

## 4. Parties prenantes

- Trésorier
- Club-admin
- Bureau
- Membres débiteurs concernés par la relance

## 5. User Stories

| En tant que... | Je veux... | Afin de... |
| :--- | :--- | :--- |
| Trésorier | voir les membres classés par dette décroissante | traiter d’abord les situations les plus urgentes |
| Trésorier | distinguer rapidement les dettes faibles, alarmantes et critiques | prioriser mes actions |
| Trésorier | modifier les seuils jaune et rouge | adapter la lecture des relances au contexte du club |
| Membre du bureau | accéder à la page de relances | suivre les impayés du club |
| Trésorier | ouvrir le détail d’un membre débiteur | préparer une relance adaptée |
| Trésorier | modifier le texte proposé avant envoi | personnaliser le message selon le cas |
| Trésorier | conserver un historique des relances envoyées | savoir ce qui a déjà été fait |
| Trésorier | sauvegarder un template par défaut | réutiliser un texte standard cohérent |

## 6. Exigences Fonctionnelles

### 6.1 Accès et navigation

- EF-001 : La page Relances doit être accessible aux utilisateurs disposant d’un rôle trésorier, club-admin ou bureau.
- EF-002 : Une entrée de menu doit être ajoutée dans le menu Compta.
- EF-003 : Une carte doit être ajoutée sur le dashboard Trésorerie pour accéder à la fonctionnalité.
- EF-004 : L’accès à la page doit respecter les règles d’autorisation existantes du système.

### 6.2 Liste des dettes

- EF-010 : La page principale doit afficher une liste des dettes des utilisateurs.
- EF-011 : La liste doit être triée par dette totale décroissante.
- EF-012 : Chaque ligne doit afficher au minimum les colonnes suivantes : Total, Avion, Planeur, ULM, Compte général, Dette 6 mois, Dette 1 an, Relances, Date de la dernière relance.
- EF-013 : La colonne Total doit représenter la dette totale au club.
- EF-014 : La colonne Avion doit représenter la dette de la section avion.
- EF-015 : La colonne Planeur doit représenter la dette de la section planeur.
- EF-016 : La colonne ULM doit représenter la dette de la section ULM.
- EF-017 : La colonne Compte général doit représenter la dette du compte général.
- EF-018 : La colonne Dette 6 mois doit afficher la dette totale constatée il y a 6 mois.
- EF-019 : La colonne Dette 1 an doit afficher la dette totale constatée il y a 1 an.
- EF-020 : La colonne Relances doit afficher un bouton d’accès au détail et le nombre de relances déjà effectuées.
- EF-021 : La colonne Date de la dernière relance doit afficher la dernière date d’envoi connue pour le membre.

### 6.3 Seuils et mise en évidence

- EF-030 : La page doit présenter deux champs de saisie pour définir les seuils d’alerte.
- EF-031 : Le seuil critique par défaut doit être fixé à 500 €.
- EF-032 : Le seuil alarme par défaut doit être fixé à 300 €.
- EF-033 : Les utilisateurs autorisés doivent pouvoir modifier ces seuils.
- EF-034 : Une dette supérieure ou égale au seuil critique doit être affichée sur fond rouge.
- EF-035 : Une dette supérieure ou égale au seuil alarme et strictement inférieure au seuil critique doit être affichée sur fond jaune.
- EF-036 : Une dette inférieure au seuil alarme doit être affichée sur fond noir ou dans un style neutre équivalent.
- EF-037 : Les règles de couleur doivent être appliquées à l’ensemble des dettes affichées dans la liste.
- EF-038 : Les valeurs des seuils doivent être conservées comme paramètres configurables du club.

### 6.4 Détail d’une relance utilisateur

- EF-050 : Un clic sur la colonne Relances doit ouvrir la page de relance du membre.
- EF-051 : La page de détail doit afficher la liste des relances déjà effectuées pour ce membre.
- EF-052 : La liste historique doit être présentée dans un datatable.
- EF-053 : Chaque entrée d’historique doit afficher la date de la relance et l’adresse email du destinataire principal.
- EF-054 : Chaque entrée d’historique doit proposer un bouton de visualisation du contenu réellement envoyé.
- EF-055 : Le contenu affiché dans la page de détail doit permettre de préparer une nouvelle relance vers le membre.

### 6.5 Préparation et envoi d’un email

- EF-060 : La page de détail doit contenir des champs de saisie permettant de préparer l’email de relance.
- EF-061 : Le texte de l’email proposé doit être modifiable par l’opérateur avant envoi.
- EF-062 : Le template par défaut doit contenir un texte de base prérempli.
- EF-063 : Le template par défaut doit être sauvegardable et modifiable via la configuration.
- EF-064 : L’envoi doit être déclenché par un bouton dédié.
- EF-065 : L’email doit être envoyé au membre concerné.
- EF-066 : Une copie de l’email doit être envoyée aux trésoriers du club.
- EF-067 : Le contenu réellement envoyé doit être stocké pour consultation ultérieure.
- EF-068 : L’envoi doit être traçable dans l’historique du membre.

### 6.6 Contenu par défaut du message

- EF-070 : Le message par défaut doit commencer par une formule d’appel personnalisée contenant le nom et le prénom du membre.
- EF-071 : Le message doit mentionner la dette totale au club.
- EF-072 : Le message doit détailler la dette par rubrique : compte général, section planeur, section avion, section ULM.
- EF-073 : Le message doit mentionner le niveau de dette observé six mois auparavant.
- EF-074 : Le message doit conserver un ton poli, explicatif et incitatif au règlement.
- EF-075 : Le message doit contenir les moyens de paiement autorisés par le club.
- EF-076 : Le message doit rester éditable sans perdre la structure utile à la relance.

### 6.7 Exemple de message par défaut

Le template de base doit proposer un contenu proche de l’exemple suivant, corrigé orthographiquement et linguistiquement avant utilisation :

```text
Cher Nom Prénom,

Notre système de gestion montre une dette importante de ta part.
Sauf erreur de notre part, tu devrais au club un total de zzz € réparti comme suit :
Compte général xx €
Section planeur xx €
Section avion xx €
Section ULM xx €

Il y a six mois, le total de la dette était de xx €.

S'il s'agit d'une erreur de notre part, nous te prions de nous excuser et serions reconnaissants que tu contactes les trésoriers de section afin de faire corriger l'erreur.

S'il s'agit d'une dette réelle, nous te rappelons que la règle de fonctionnement du club demande à ce que tous les comptes soient gardés positifs en permanence. Le club ne fait pas crédit et demande à ce que les comptes soient suffisamment approvisionnés avant chaque vol.

Merci, dans ce cas, de régulariser ta dette au plus tôt :
* Par CB avec HelloAsso
* Par virement
  - IBAN CG =
  - IBAN Avion =
  - IBAN Planeur =
  - IBAN ULM =
* Par chèque bancaire

Merci de ta compréhension et de ta vigilance,

L'équipe bénévole de gestion de l'aéroclub.
```

## 7. Exigences d’Interface Utilisateur

### 7.1 Écran principal Relances

- Affichage de deux champs de seuils au-dessus du tableau.
- Tableau des dettes avec tri décroissant.
- Mise en couleur de chaque ligne ou cellule selon le niveau de dette.
- Bouton d’accès aux relances dans la colonne dédiée.
- Présentation claire du nombre de relances déjà envoyées.

### 7.2 Écran détail d’un membre

- Datatable des relances déjà envoyées.
- Bloc de préparation du message avec le texte par défaut prérempli.
- Bouton d’envoi visible et explicite.
- Accès au contenu envoyé pour chaque relance.
- Indication du destinataire principal et des copies envoyées.

### 7.3 Configuration du template

- Le template email par défaut doit pouvoir être chargé depuis la configuration.
- Le template doit être éditable depuis une interface d’administration existante ou dédiée.
- Les changements doivent être persistés pour les prochaines relances.

## 8. Comportement Système

1. L’opérateur ouvre la page Relances depuis le menu Compta ou le dashboard Trésorerie.
2. Le système affiche les seuils configurés et la liste des membres triée par dette totale décroissante.
3. Les dettes sont colorées selon le seuil atteint.
4. L’opérateur peut ajuster les seuils puis rafraîchir l’affichage.
5. L’opérateur ouvre le détail d’un membre débiteur.
6. Le système affiche l’historique des relances et préremplit le modèle de message.
7. L’opérateur modifie le texte si nécessaire puis lance l’envoi.
8. Le système envoie l’email au membre et met les trésoriers du club en copie.
9. Le système enregistre le contenu envoyé et alimente l’historique du membre.

## 9. Cas Limites

- CL-001 : Un membre a une dette nulle ou négative ; il ne doit pas être mis en avant comme débiteur critique.
- CL-002 : Un membre n’a pas d’email valide ; l’envoi doit être bloqué ou signalé clairement.
- CL-003 : Un membre possède plusieurs types de dettes ; le détail doit rester lisible et cohérent avec le total.
- CL-004 : Le nombre de relances est élevé ; la page doit rester exploitable dans le datatable.
- CL-005 : Le template configuré est vide ou invalide ; le système doit proposer un texte minimal exploitable.
- CL-006 : Une relance a déjà été envoyée le même jour ; l’historique doit rester explicite sur le contenu réellement expédié.

## 10. Critères d’Acceptation

- CA-001 : Un utilisateur trésorier, club-admin ou bureau peut accéder à la page Relances.
- CA-002 : Une entrée de menu apparaît dans Compta et une carte apparaît sur le dashboard Trésorerie.
- CA-003 : La liste principale affiche les colonnes attendues et reste triée par dette totale décroissante.
- CA-004 : Les dettes supérieures ou égales à 500 € apparaissent en rouge.
- CA-005 : Les dettes supérieures ou égales à 300 € et inférieures à 500 € apparaissent en jaune.
- CA-006 : Les dettes inférieures à 300 € apparaissent dans un style neutre.
- CA-007 : Les seuils jaune et rouge peuvent être modifiés par un utilisateur autorisé.
- CA-008 : Un clic sur le bouton de relance ouvre la page de détail du membre.
- CA-009 : La page de détail affiche l’historique des relances déjà envoyées.
- CA-010 : Le texte de relance est modifiable avant envoi.
- CA-011 : L’envoi crée une trace consultable avec la date, l’adresse email du destinataire principal et le texte réellement envoyé.
- CA-012 : Le membre reçoit l’email de relance et les trésoriers du club sont en copie.
- CA-013 : Le template email par défaut peut être sauvegardé et rechargé depuis la configuration.

## 11. Dépendances Produit

- Le module doit s’appuyer sur les rôles et permissions existants de GVV.
- Le module doit s’intégrer au menu comptable existant.
- Le dashboard Trésorerie doit permettre l’ajout d’une carte de raccourci.
- Le système doit disposer des données de dettes nécessaires pour alimenter les colonnes affichées.
- Le système doit disposer d’une configuration d’envoi email fonctionnelle.

## 12. Questions Ouvertes

- QO-001 : Le style noir pour les dettes sous le seuil d’alarme doit-il être un vrai fond noir ou un simple style neutre par défaut ?
  style neutre.
- QO-002 : La configuration du template doit-elle être globale au club ou spécifique à une section ? globale pour commencer, puis possibilité d’affiner par section si le besoin se fait sentir dans le futur.
- QO-003 : Faut-il proposer un aperçu avant envoi dans la page de détail ? 
  oui, un aperçu est souhaitable pour vérifier la mise en forme et le contenu avant l’envoi.
- QO-004 : Faut-il archiver aussi la liste des destinataires en copie à chaque relance ? oui
- 
