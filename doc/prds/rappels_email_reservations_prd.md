# PRD : Rappels Email/SMS des Réservations d'Aéronefs

## 1. Contexte

Le PRD de réservation d'aéronefs existant exclut explicitement les notifications automatiques. Ce document définit les exigences produit pour ajouter des rappels email/SMS autour des réservations, afin de réduire les absences, améliorer la coordination pilote/instructeur et fiabiliser la préparation des vols.

## 2. Objectifs

* Réduire les oublis de réservation côté pilote et instructeur.
* Améliorer la ponctualité et la préparation des vols planifiés.
* Rendre les rappels configurables selon les besoins de chaque section.
* Limiter les notifications aux cas où le risque d'oubli est réel.
* Permettre à l'utilisateur de gérer ses réservations et ses préférences de rappel depuis une page dédiée.

## 3. Périmètre

### 3.1 Inclus

* Envoi automatique de rappels pour les réservations à venir.
* Support des canaux email, SMS ou les deux selon le choix utilisateur.
* Notifications événementielles immédiates liées aux changements de réservation.
* Déclenchement par scheduler horaire avec fenêtre de 48 heures.
* Déclenchement possible par cron et par URL publique dédiée.
* Distinction explicite entre rappels temporels (avant départ) et notifications événementielles (création/modification/annulation).
* Couplage faible accepté : le mécanisme de réservation peut transmettre une description d'événement (création, modification, annulation) au mécanisme de rappel.
* Le mécanisme de rappel peut interroger le modèle de réservation pour extraire les réservations à traiter.
* Paramètres de base pour activer/désactiver les rappels.
* Page "Mes réservations" listant les réservations de l'utilisateur connecté.
* Suppression d'une réservation depuis la liste "Mes réservations".
* Bouton "Ajouter une réservation" depuis "Mes réservations".
* Configuration des notifications par utilisateur : email, SMS ou email+SMS.
* Configuration de la période de rappel par utilisateur.

### 3.2 Hors périmètre

* Push mobile ou messageries tierces.
* Rappels pour les vols non réservés via le module de réservation.
* Scénarios marketing/newsletter.

## 4. Parties Prenantes

* Pilote
* Instructeur
* Chef pilote / gestionnaire planning
* Administrateur section

## 5. User Stories

| En tant que... | Je veux... | Afin de... |
| :--- | :--- | :--- |
| Pilote | recevoir un rappel avant ma réservation | ne pas oublier mon créneau de vol |
| Instructeur | recevoir un rappel des vols où je suis assigné | organiser mon planning d'instruction |
| Chef pilote | être certain que les acteurs reçoivent un rappel clair | réduire les absences et conflits de planning |
| Administrateur | configurer le délai de rappel | adapter la communication au fonctionnement local |
| Utilisateur connecté | voir "Mes réservations" avec mes réservations actives | gérer mes actions sans passer par des écrans techniques |
| Utilisateur connecté | supprimer une réservation depuis "Mes réservations" | annuler un créneau devenu inutile |
| Utilisateur connecté | accéder au bouton "Ajouter une réservation" depuis "Mes réservations" | créer rapidement une nouvelle réservation |
| Utilisateur connecté | choisir email, SMS ou email+SMS et régler la période de rappel | adapter les rappels à mon organisation |

## 6. Exigences Fonctionnelles

### 6.1 Règles d'envoi

* EF-001 : Le système doit pouvoir envoyer un rappel avant l'heure de début de réservation.
* EF-002 : Un scheduler doit être exécuté toutes les heures pour évaluer les rappels à envoyer.
* EF-003 : Le mécanisme doit pouvoir être déclenché par une tâche cron.
* EF-004 : Le mécanisme doit pouvoir être déclenché par une URL publique dédiée.
* EF-005 : L'accès à l'URL publique doit être protégé par un secret technique pour empêcher les déclenchements non autorisés.
* EF-006 : Le mécanisme ne doit pas reposer sur une planification préalable des envois ; la décision d'envoi est prise quand le scheduler est déclenché.
* EF-007 : À chaque exécution, le scheduler doit analyser les réservations existantes dont le début est dans les 48 heures à venir.
* EF-008 : Le rappel n'est envoyé que si la réservation existe toujours au moment du déclenchement.
* EF-009 : Le système doit distinguer explicitement les rappels temporels et les notifications événementielles.
* EF-010 : Un rappel temporel est envoyé un certain temps avant le départ selon la période de rappel configurée.
* EF-011 : Une notification événementielle est envoyée lors d'une création, modification ou annulation de réservation.
* EF-012 : Si la réservation est créée ou modifiée par un membre d'équipage, la notification événementielle est envoyée uniquement au second membre d'équipage.
* EF-013 : Aucune notification email ne doit être envoyée aux utilisateurs sans adresse email valide.
* EF-033 : Aucune notification SMS ne doit être envoyée aux utilisateurs sans numéro de téléphone valide.
* EF-034 : Le système doit permettre le choix du canal de notification : `email`, `sms` ou `email+sms`.
* EF-035 : Le service SMS initial utilisé par le système doit être Brevo, via un adaptateur de fournisseur.
* EF-045 : Si la réservation est créée ou modifiée par une tierce personne (ni pilote ni instructeur), la notification événementielle doit être envoyée aux deux membres d'équipage.
* EF-038 : L'utilisateur connecté doit disposer d'une page "Mes réservations" listant ses réservations actives.
* EF-039 : Depuis "Mes réservations", l'utilisateur doit pouvoir supprimer une réservation de la liste.
* EF-040 : Depuis "Mes réservations", l'utilisateur doit voir un bouton "Ajouter une réservation".
* EF-041 : Depuis "Mes réservations", l'utilisateur doit pouvoir configurer son canal de notification (`email`, `sms`, `email+sms`).
* EF-042 : Depuis "Mes réservations", l'utilisateur doit pouvoir définir sa période de rappel (en jours ou heures avant le départ).

### 6.2 Gestion des changements

* EF-015 : Le risque d'oubli est considéré réel dès lors que le rappel vise le second membre d'équipage, y compris pour le jour même.
* EF-016 : Le module réservation peut appeler le mécanisme de rappel avec une description d'événement (`create`, `update`, `cancel`).
* EF-017 : Les événements `create`, `update` et `cancel` déclenchent l'évaluation des notifications événementielles.
* EF-018 : Les rappels temporels sont évalués indépendamment par le scheduler selon la période configurée.

### 6.3 Préférences et configuration

* EF-020 : Un paramètre section doit permettre d'activer/désactiver globalement les rappels email.
* EF-021 : Le mode standard repose sur un scheduler horaire et une fenêtre fixe de 48 heures.
* EF-022 : Le système doit rester simple d'exploitation sans paramétrage avancé obligatoire.
* EF-043 : Les préférences de rappel utilisateur (canal et période) doivent être appliquées au moment de la décision d'envoi.

### 6.4 Contenu des messages

* EF-023 : Le rappel doit inclure au minimum : date/heure, aéronef, pilote, instructeur (si présent), statut de la réservation.
* EF-024 : Le rappel doit utiliser des libellés compréhensibles et cohérents avec l'interface GVV.
* EF-025 : Le message doit indiquer clairement s'il s'agit d'un rappel ou d'une notification, ainsi que sa source de déclenchement.
* EF-036 : Le contenu SMS doit être concis et inclure au minimum la date/heure, l'aéronef et le rôle du destinataire.

### 6.5 Traçabilité et supervision

* EF-026 : Le système doit enregistrer les activations et les tentatives d'envoi dans une table de log dédiée `reservation_reminder_log`.
* EF-027 : Les administrateurs doivent pouvoir identifier les erreurs d'envoi via cette table sans accès technique avancé.
* EF-028 : Chaque entrée doit inclure au minimum : clé unique d'idempotence, date/heure d'envoi, message, source d'exécution (`event_create`, `event_update`, `event_cancel`, `cron` ou `public_url`), statut d'envoi, identifiant de réservation, destinataire.
* EF-029 : La clé unique d'idempotence doit empêcher les envois multiples pour un même rappel logique.
* EF-030 : Les événements reçus depuis le module réservation (`create`, `update`, `cancel`) doivent être tracés, avec indication explicite de l'envoi effectif ou non.
* EF-031 : En cas d'échec d'envoi email, l'erreur doit être tracée dans `gvv_error`.
* EF-032 : Aucun mécanisme de relance/retry automatique ne doit être mis en place après un échec SMTP.
* EF-037 : Chaque trace d'envoi doit inclure le canal utilisé (`email`, `sms`, `email+sms`) et le fournisseur effectif (`brevo` pour SMS initialement).
* EF-044 : Les suppressions réalisées depuis "Mes réservations" doivent être tracées comme événements `cancel` dans le mécanisme de rappel.

## 7. Exigences Non Fonctionnelles

* ENF-001 : Les envois ne doivent pas dégrader l'expérience utilisateur lors de la création/modification d'une réservation. Néanmoins, on peut tolérer un délai de quelques secondes pour le traitement des notifications événementielles, surtout si cela évite un mécanisme complexe de mise en attente des notifications à envoyer.
* ENF-002 : Les envois doivent respecter les autorisations et ne pas exposer des informations à des destinataires non concernés.
* ENF-003 : Le format des emails doit rester lisible sur desktop et mobile.
* ENF-004 : Les textes doivent rester compatibles avec le support multilingue de GVV (français, anglais, néerlandais).

## 8. Cas Limites

* CL-001 : Réservation créée puis annulée avant la prochaine échéance de rappel.
* CL-002 : Réservation sans instructeur.
* CL-003 : Changement d'instructeur après premier rappel.
* CL-004 : Adresse email invalide ou absente pour un ou plusieurs destinataires.
* CL-005 : Plusieurs réservations consécutives pour le même pilote le même jour.
* CL-006 : Réservation annulée peu avant le passage du scheduler horaire.
* CL-007 : Réservation du jour même pour le second membre d'équipage (peut déclencher un rappel).
* CL-008 : Réservation créée ou modifiée par un membre d'équipage : seul le second membre reçoit la notification événementielle.
* CL-009 : Réservation créée par une tierce personne : les deux membres d'équipage reçoivent la notification événementielle.

## 9. Critères d'Acceptation

* CA-001 : Le scheduler horaire envoie les rappels pour les réservations existantes dont le début est à moins de 48 heures.
* CA-003 : Si la réservation est créée ou modifiée par un membre d'équipage, seule l'autre personne de l'équipage reçoit la notification événementielle.
* CA-004 : Les erreurs d'envoi sont visibles dans la table `reservation_reminder_log` via un mécanisme de suivi consultable par l'administration.
* CA-005 : La fonctionnalité peut être activée/désactivée au niveau section.
* CA-006 : Les traces du mécanisme de rappel sont extractibles de façon fiable via requête sur la table `reservation_reminder_log`.
* CA-007 : Une réservation annulée, supprimée ou inexistante n'entraîne pas d'envoi de rappel par le scheduler.
* CA-008 : Le rappel temporel est envoyé selon la période configurée avant départ et reste distinct des notifications événementielles.
* CA-009 : Le déclenchement fonctionne via cron et via URL publique dédiée protégée par secret technique.
* CA-010 : Les événements `create`, `update` et `cancel` sont traités comme notifications événementielles avec traçabilité dédiée.
* CA-011 : Si le créateur de la réservation est une tierce personne, la notification événementielle est envoyée au pilote et à l'instructeur.
* CA-012 : En cas d'échec SMTP, une trace d'erreur est présente dans `gvv_error` et dans `reservation_reminder_log`.
* CA-013 : Après un échec SMTP, aucun retry automatique n'est exécuté.
* CA-014 : Si le canal `sms` est choisi, le rappel est envoyé via Brevo lorsque le numéro est valide.
* CA-015 : Si le canal `email+sms` est choisi, le système envoie les deux notifications sans duplicata (même clé logique, canaux tracés séparément).
* CA-016 : La page "Mes réservations" affiche les réservations de l'utilisateur connecté.
* CA-017 : Une suppression effectuée depuis "Mes réservations" retire la réservation de la liste et empêche tout rappel ultérieur.
* CA-018 : Le bouton "Ajouter une réservation" est visible et permet d'accéder au flux de création.
* CA-019 : Les préférences de canal et de période configurées dans "Mes réservations" sont prises en compte lors des rappels temporels.

## 10. Dépendances Produit

* Ce PRD complète le PRD de réservation d'aéronefs existant.
* Les exigences d'autorisation et de sécurité existantes restent applicables.

## 11. Questions Ouvertes

* QO-001 : Faut-il permettre à un utilisateur de se désabonner des rappels de réservation ?
  Oui via la page "Mes réservations", avec configuration du canal (`email`, `sms`, `email+sms`) et de la période de rappel.
* QO-003 : Faut-il regrouper plusieurs rappels d'une même journée en un email digest ?
  Oui on peut traiter toutes les réservations d'une même journée dans un seul email, mais il faut que le rappel contienne toutes les informations nécessaires pour chaque réservation.
* QO-004 : Souhaite-t-on une alerte opérateur (email interne/tableau de bord) en complément de `gvv_error` en cas d'échec SMTP ? non
