# PRD : Synchronisation des réservations avec un agenda Google

## 1. Contexte

Les membres réservent les aéronefs dans GVV (voir `doc/prds/aircraft_booking_prd.md`). Pour retrouver leurs créneaux de vol à côté de leurs autres rendez-vous, ils les recopient aujourd'hui à la main dans leur agenda personnel. Cette copie est fastidieuse. Elle devient fausse dès qu'une réservation est déplacée ou annulée, en particulier quand c'est un instructeur ou un administrateur qui la modifie.

Ce document définit les exigences d'une synchronisation, à sens unique, des réservations GVV d'un membre vers son agenda Google.

## 2. Objectifs

* Les réservations d'un membre apparaissent dans son agenda Google sans saisie de sa part.
* L'agenda reste juste : toute création, modification ou annulation dans GVV s'y reflète, quelle que soit la personne qui l'a faite.
* Le membre active et arrête la synchronisation lui-même, sans intervention d'un administrateur.
* GVV reste la seule référence : l'agenda Google n'est qu'une copie.

## 3. Périmètre

### 3.1 Inclus

* Activation de la synchronisation par le membre, pour son propre agenda Google.
* Création, mise à jour et suppression automatiques des événements dans cet agenda, suivant les réservations GVV du membre : celles où il est pilote et, pour un instructeur, celles où il est l'instructeur assigné.
* Arrêt de la synchronisation par le membre.
* Information du membre sur l'état de sa synchronisation et sur les éventuelles erreurs.

### 3.2 Hors périmètre

* Synchronisation dans l'autre sens : une modification ou une suppression faite dans l'agenda Google ne change rien dans GVV.
* Agendas autres que Google (Outlook, iCloud, abonnement iCal…).
* Synchronisation des blocs d'indisponibilité des aéronefs, du calendrier des présences ou d'autres événements GVV.
* Agenda partagé du club regroupant les réservations de tous les membres.
* Création par GVV d'un agenda Google dédié : les événements sont écrits dans l'agenda principal du membre.
* Activation de la fonction pour certaines sections seulement : elle vaut pour tout le club.
* Rappels : ils sont traités par `doc/prds/rappels_email_reservations_prd.md`. Les rappels propres à Google Agenda restent réglés par le membre dans Google.

## 4. Parties prenantes

* Membre ayant le droit de réserver (pilote, élève)
* Instructeur
* Administrateur ou gestionnaire du planning, qui modifie les réservations des autres
* Administrateur du club, qui active la fonctionnalité pour le club

## 5. User Stories

| En tant que... | Je veux... | Afin de... |
| :--- | :--- | :--- |
| Membre ayant le droit de réserver | fournir les informations nécessaires pour relier mon agenda Google à GVV | voir mes réservations dans mon agenda |
| Membre | que chaque réservation que j'effectue apparaisse dans mon agenda | ne plus la recopier à la main |
| Membre | que mon agenda soit mis à jour quand ma réservation est modifiée ou supprimée, par moi ou par un administrateur | ne jamais me fier à un créneau périmé |
| Instructeur | voir aussi dans mon agenda les réservations où je suis l'instructeur assigné | organiser mon planning d'instruction |
| Membre | arrêter la synchronisation | ne plus recevoir de nouvelles réservations dans mon agenda, sans rien perdre de ce qui y figure déjà |
| Membre | savoir si ma synchronisation fonctionne | réagir si mon agenda n'est plus à jour |

## 6. Exigences fonctionnelles

### 6.1 Activation

* **SYNC-ACT-01** : tout membre qui a le droit de réserver peut activer la synchronisation de ses réservations avec son agenda Google. Un membre sans ce droit ne se voit pas proposer la fonction.
* **SYNC-ACT-02** : le membre relie son agenda en se connectant à son compte Google et en autorisant GVV à écrire dans son agenda. Il ne dépend d'aucune intervention d'un administrateur.
* **SYNC-ACT-03** : les événements sont écrits dans l'agenda principal du compte Google autorisé.
* **SYNC-ACT-04** : la synchronisation se gère depuis la page « Mes réservations ».
* **SYNC-ACT-05** : à l'activation, les réservations à venir du membre sont ajoutées à son agenda : celles où il est pilote et celles où il est l'instructeur assigné. Les réservations passées ne sont pas ajoutées.
* **SYNC-ACT-06** : le résultat de l'activation est affiché clairement : synchronisation active, avec le nombre de réservations ajoutées, ou échec, avec sa cause et l'action à faire.
* **SYNC-ACT-07** : un membre ne peut relier que son propre agenda. Il ne peut ni voir ni modifier les informations de synchronisation d'un autre membre.
* **SYNC-ACT-08** : la fonction n'est proposée que si l'administrateur du club l'a activée dans la page Configuration. Elle vaut alors pour toutes les sections du club.

### 6.2 Mise à jour de l'agenda

* **SYNC-MAJ-01** : les réservations d'un membre sont celles où il est le pilote et, s'il est instructeur, celles où il est l'instructeur assigné. Une même réservation peut ainsi figurer dans l'agenda du pilote et dans celui de l'instructeur, chacun l'ayant activée de son côté.
* **SYNC-MAJ-02** : quand une réservation du membre est créée, un événement correspondant est ajouté à son agenda Google.
* **SYNC-MAJ-03** : quand cette réservation est modifiée (date, horaires, aéronef, instructeur, commentaire), l'événement est mis à jour.
* **SYNC-MAJ-04** : quand cette réservation est supprimée ou annulée, l'événement est retiré de l'agenda.
* **SYNC-MAJ-05** : SYNC-MAJ-02 à 04 s'appliquent quelle que soit la personne qui fait l'action : le membre lui-même, un instructeur, un administrateur, ou un traitement automatique de GVV.
* **SYNC-MAJ-06** : une réservation qui change de pilote ou d'instructeur disparaît de l'agenda de celui qui n'y figure plus et apparaît dans celui du nouveau, si celui-ci a activé la synchronisation.
* **SYNC-MAJ-07** : chaque réservation n'apparaît qu'une seule fois dans l'agenda, quel que soit le nombre de modifications qu'elle a subies.
* **SYNC-MAJ-08** : la mise à jour est visible dans l'agenda en quelques minutes au plus.
* **SYNC-MAJ-09** : seuls les événements créés par GVV sont modifiés ou supprimés. Les autres événements de l'agenda du membre ne sont jamais touchés.

### 6.3 Contenu des événements

* **SYNC-CNT-01** : un événement indique l'aéronef (immatriculation et type), le début et la fin de la réservation, le pilote et l'instructeur s'il y en a un, et le commentaire de la réservation.
* **SYNC-CNT-02** : un événement contient un lien vers la réservation dans GVV.
* **SYNC-CNT-03** : un événement ne contient aucune information personnelle d'un autre membre en dehors des noms du pilote et de l'instructeur, ni aucune information financière (solde, tarif).
* **SYNC-CNT-04** : les horaires de l'événement sont ceux de la réservation, dans le fuseau horaire du club.

### 6.4 Arrêt de la synchronisation

* **SYNC-ARR-01** : le membre peut arrêter la synchronisation à tout moment, depuis la page « Mes réservations ».
* **SYNC-ARR-02** : à l'arrêt, tous les événements déjà créés restent dans l'agenda, ceux des réservations passées comme ceux des réservations à venir. Ils ne sont plus mis à jour.
* **SYNC-ARR-03** : l'arrêt ne supprime ni ne modifie aucune réservation dans GVV.
* **SYNC-ARR-04** : à l'arrêt, GVV ne conserve plus l'autorisation d'accéder à l'agenda du membre.
* **SYNC-ARR-05** : après l'arrêt, aucune réservation n'est plus envoyée vers l'agenda. Le membre peut réactiver la synchronisation plus tard (§6.1).
* **SYNC-ARR-06** : avant l'arrêt, le membre est prévenu que les événements déjà présents dans son agenda y resteront sans être mis à jour, et qu'il peut les supprimer lui-même dans Google.

### 6.5 Erreurs et suivi

* **SYNC-ERR-01** : une réservation est toujours enregistrée dans GVV, même si l'agenda Google ne peut pas être mis à jour sur le moment. La synchronisation n'empêche ni ne retarde jamais une réservation.
* **SYNC-ERR-02** : une mise à jour qui échoue pour une raison passagère (Google injoignable) est retentée automatiquement, jusqu'à ce que l'agenda soit à jour.
* **SYNC-ERR-03** : si l'autorisation n'est plus valable (retirée par le membre dans Google, agenda supprimé…), la synchronisation est suspendue. Le membre en est informé sur la page « Mes réservations » et par e-mail, avec l'action à faire pour la rétablir.
* **SYNC-ERR-04** : la page « Mes réservations » indique l'état de la synchronisation : active, suspendue avec sa cause, ou inactive, et la date de la dernière mise à jour réussie.
* **SYNC-ERR-05** : un administrateur du club peut voir quels membres ont une synchronisation suspendue, sans accéder à leur agenda ni à leurs informations d'autorisation.

## 7. Exigences non fonctionnelles

* **Confidentialité** : GVV ne demande que l'accès nécessaire à l'écriture des événements de réservation. Les informations d'autorisation du membre sont conservées de façon confidentielle, ne sont jamais réaffichées, et sont supprimées à l'arrêt de la synchronisation. Les règles de confidentialité publiques de GVV (`legal/confidentialite`) décrivent ce traitement.
* **Performance** : l'enregistrement d'une réservation n'est pas ralenti par la synchronisation.
* **Fiabilité** : une panne ou une lenteur de Google ne bloque aucune fonction de GVV.
* **Multi-langue** : tous les textes de la fonction existent en français, anglais et néerlandais.
* **Traçabilité** : l'activation, l'arrêt, la suspension et les échecs répétés sont tracés.

## 8. Cas limites

* Réservation déplacée dans le passé par un administrateur : l'événement est mis à jour normalement.
* Réservation supprimée après son créneau : l'événement est retiré, comme pour une réservation à venir.
* Membre qui relie un autre compte Google : c'est un arrêt suivi d'une nouvelle activation. Les événements de l'ancien agenda y restent ; les réservations à venir sont ajoutées au nouveau.
* Réactivation après un arrêt : les réservations à venir déjà présentes dans l'agenda depuis la synchronisation précédente ne sont pas ajoutées une seconde fois.
* Membre qui supprime à la main un événement GVV dans son agenda : il n'est pas recréé, sauf si la réservation est ensuite modifiée.
* Membre qui modifie à la main un événement GVV dans son agenda : la modification est écrasée à la prochaine modification de la réservation dans GVV.
* Membre qui perd le droit de réserver : sa synchronisation est arrêtée, comme au §6.4. Un instructeur qui perd ce rôle ne reçoit plus les réservations où il serait assigné.
* Membre supprimé ou dont le login est renommé : la synchronisation suit le membre, ou est arrêtée avec sa fiche.
* Fonction désactivée pour le club par l'administrateur : plus aucune mise à jour n'est envoyée ; les événements existants restent dans les agendas.

## 9. Critères d'acceptation

* Un membre ayant le droit de réserver relie son agenda Google sans aide, et ses réservations à venir y apparaissent.
* Un instructeur qui a relié son agenda y voit les réservations où il est assigné, et elles en disparaissent s'il est remplacé par un autre instructeur.
* Une réservation créée, déplacée puis annulée par le membre est ajoutée, mise à jour puis retirée de son agenda.
* La même suite d'actions faite par un administrateur sur la réservation du membre donne le même résultat dans l'agenda du membre.
* Après l'arrêt de la synchronisation, tous les événements déjà créés restent dans l'agenda, plus aucun n'est créé ni mis à jour, et rien ne change dans les réservations GVV.
* Avec Google injoignable, une réservation est enregistrée normalement, et l'agenda est mis à jour quand Google redevient joignable.
* Une autorisation retirée côté Google suspend la synchronisation, et le membre en est informé avec l'action à faire.
* Aucun autre événement de l'agenda du membre n'est modifié.

## 10. Dépendances produit

* Système de réservation d'aéronefs : `doc/prds/aircraft_booking_prd.md`.
* Page « Mes réservations » et réglages par membre : `doc/prds/rappels_email_reservations_prd.md`.
* Page Configuration (activation de la fonction pour le club) : `doc/prds/configuration_unifiee_prd.md`.
* Règles de confidentialité publiques (pages publiques de la configuration unifiée).
* Une application Google enregistrée et publiée par le club ou par l'hébergeur de GVV, validée par Google pour l'accès aux agendas, nécessaire pour que les membres puissent autoriser GVV avec leur compte Google.
