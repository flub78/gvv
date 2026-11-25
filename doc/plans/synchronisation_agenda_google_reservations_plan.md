# Plan d'implémentation : Synchronisation des réservations avec Google Agenda

**PRD :** `doc/prds/synchronisation_agenda_google_reservations_prd.md`
**Conception :** `doc/design_notes/synchronisation_agenda_google_reservations_design.md`
**Dernière mise à jour :** 2026-11-25
**Statut :** lots 1 à 3 réalisés, sauf la recette avec un vrai compte Google (L3-T9), à faire (branche `feature/synchronisation-agenda-google`)

Ce plan ne reprend ni les exigences ni l'architecture : il cite les exigences par leur identifiant (SYNC-…) et les décisions par leur numéro dans la note de conception (D1 à D7, §2).

---

## 1. Constat technique de départ

| Élément | État actuel | Rôle dans ce plan |
| :--- | :--- | :--- |
| `controllers/reservations.php` | Formulaire (`update_reservation`) et glisser-déposer (`on_event_drop`) font des `UPDATE` directs sur `reservations`. Le formulaire prévient `Reservation_reminder` ; le glisser-déposer non. | Toutes ces écritures passent par le modèle (lot 1). |
| `controllers/mes_reservations.php` | Suppression d'une réservation et préférences de rappel. Prévient `Reservation_reminder` à la suppression. | Même passage par le modèle (lot 1) ; accueille la gestion de la synchronisation (lot 2). |
| `models/reservations_model.php` | `create_reservation`, `update_reservation`, `delete_reservation`, sans notification. | Devient le seul point d'écriture et de notification (D2). |
| `models/membres_fusion_model.php`, `models/membres_model.php` (renommage) | Réécrivent `pilot_member_id` et `instructor_member_id` par une boucle générique sur les tables. | Signalent au modèle les réservations touchées (lot 1). |
| `controllers/reservation_scheduler.php` | Modèle d'entrée cron et URL à secret. | Repris pour l'entrée de synchronisation (lot 3). |
| `config/parametres_registry.php` | Registre de la page Configuration. | Activation de la fonction et identifiants de l'application Google (lot 2). |
| `third_party/Requests` | Client HTTP déjà présent. | Base de `Google_agenda_client` (D4). |
| Migrations | Version courante 185. | Migration 186 (lot 2). |

## 2. Stratégie de test sans Google

Google ne peut être appelé ni par PHPUnit ni par Playwright. Toute la logique passe donc par `Google_agenda_client` (D4), qui a une variante de test :

* **PHPUnit** : `Agenda_google_sync` reçoit un faux client qui enregistre les appels, renvoie des identifiants d'événements et simule les erreurs (passagère, autorisation invalide, événement disparu). Les tests vérifient la réconciliation sans réseau.
* **Playwright** : sur une plateforme de test uniquement, le paramètre `agenda_google_bouchon` de `program.php` remplace Google par un contrôleur bouchon local (`agenda_google_bouchon`). Ce paramètre n'est pas proposé dans la page Configuration : seul l'hébergeur peut le poser (gvv.net tourne en environnement `production`, l'environnement ne peut donc pas servir de garde). Ce bouchon joue l'écran d'autorisation (il renvoie un code au retour) et accepte les appels d'événements en les enregistrant en base, dans une table du bouchon. Sans le paramètre, il répond 404 à toute requête. Les tests Playwright posent le paramètre le temps de leur exécution et restaurent `program.php`.
* **Recette réelle** : un essai manuel avec un compte Google de test, déclaré dans l'application Google en mode test, avant chaque livraison du lot 3.

## 3. Lots et tâches

Les trois lots sont réalisés sur une seule branche, `feature/synchronisation-agenda-google`, livrée par une PR unique (`/create-pr`) : l'ensemble comporte une migration et touche plus de 5 fichiers. Les lots restent des étapes de validation successives.

### Lot 1 — Un seul point d'écriture des réservations (≈ 3 j) — réalisé

Ce lot ne change rien pour l'utilisateur, sauf que les rappels partent désormais aussi après un glisser-déposer. Il prépare D2.

| # | Tâche | Exigences | Estimation |
| :--- | :--- | :--- | :--- |
| L1-T1 | `Reservations_model` : `create`, `update` et `delete` notifient après écriture (contrat `create / update / cancel` des rappels, avec l'auteur de l'action). Le point de notification est unique et extensible : la synchronisation s'y ajoutera au lot 3. | SYNC-MAJ-05 | 1 j |
| L1-T2 | `reservations` (formulaire, glisser-déposer, suppression) et `mes_reservations` (suppression) passent par le modèle ; leurs appels directs à `Reservation_reminder` sont retirés. | SYNC-MAJ-05 | 0,75 j |
| L1-T3 | Fusion et renommage de membres : la réaffectation des réservations passe par le modèle, qui signale ensuite les réservations touchées (notification `update`). Les rappels aux membres ne sont pas envoyés dans ce cas : seul leur identifiant change. La synchronisation s'y branchera au lot 3. | SYNC-MAJ-06 | 0,5 j |
| L1-T4 | Mise à jour de `doc/design_notes/rappels_email_reservations_design.md` : les événements viennent du modèle, et non plus des contrôleurs. | — | 0,25 j |
| L1-T5 | Tests : non-régression des rappels pour la création, la modification au formulaire et au glisser-déposer, et la suppression ; test qui signale toute écriture directe dans la table `reservations` en dehors du modèle (analyse des sources). | — | 0,5 j |

### Lot 2 — Liaison du compte Google (≈ 5 j) — réalisé

À la fin de ce lot, un membre relie et délie son agenda, et voit l'état de sa liaison. Aucun événement n'est encore envoyé.

| # | Tâche | Exigences | Estimation |
| :--- | :--- | :--- | :--- |
| L2-T1 | Migration 186 : tables `agenda_google_liaisons`, `agenda_google_evenements`, `agenda_google_file` (conception §4, avec les champs d'audit), et table `agenda_google_bouchon` (état du bouchon, vide en production). Mise à jour de `config/migration.php`. | §7 | 0,5 j |
| L2-T2 | `Secret_box` : chiffrement sodium ; création de `config/agenda_google.php` (clé, droits restreints, hors dépôt) à la première utilisation ; ajout du fichier à `.gitignore`. | §7 Confidentialité, D5 | 0,5 j |
| L2-T3 | `Google_agenda_client`, partie autorisation : adresse d'autorisation (périmètre `calendar.events`, accès hors ligne, paramètre d'état), échange du code, renouvellement du jeton d'accès, révocation, adresse du compte. Classement des erreurs. Variante bouchon (§2). | SYNC-ACT-02, SYNC-ARR-04 | 1,25 j |
| L2-T4 | Registre de la page Configuration : drapeau du module dans l'onglet Modules (`requires` : identifiant et secret de l'application) ; identifiant et secret de l'application Google dans l'onglet Système (secret masqué). | SYNC-ACT-08 | 0,25 j |
| L2-T5 | `mes_reservations` : bloc « Agenda Google » visible si le module est actif et si le membre peut réserver. Il permet l'activation (redirection vers Google, retour, enregistrement du jeton chiffré), l'arrêt (avertissement SYNC-ARR-06, puis révocation) et affiche l'état (SYNC-ERR-04). Chaque action se conclut par une alerte Bootstrap qu'on peut fermer. | SYNC-ACT-01 à 07, SYNC-ARR-01 à 06, SYNC-ERR-04 | 1,5 j |
| L2-T6 | Contrôleur bouchon `agenda_google_bouchon` (plateforme de test seulement) : écran d'autorisation simulé et révocation. | — | 0,5 j |
| L2-T7 | Traductions fr/en/nl ; mise à jour de la page `legal/confidentialite` générique (traitement des jetons Google). | §7 Multi-langue, Confidentialité | 0,5 j |

### Lot 3 — Synchronisation des événements (≈ 6 j) — réalisé, sauf L3-T9

| # | Tâche | Exigences | Estimation |
| :--- | :--- | :--- | :--- |
| L3-T1 | `Google_agenda_client`, partie événements : créer, modifier, supprimer un événement de l'agenda principal ; fuseau horaire du club. Variante bouchon. | SYNC-ACT-03, SYNC-CNT-04 | 0,75 j |
| L3-T2 | `Agenda_google_sync` : contenu de l'événement et empreinte (D6) ; réconciliation d'une réservation (agendas attendus du pilote et de l'instructeur, création, mise à jour, suppression, liens d'une liaison arrêtée laissés en l'état, événement disparu recréé seulement si la réservation a changé). | SYNC-MAJ-01 à 09, SYNC-CNT-01 à 03, SYNC-ARR-02 | 1,75 j |
| L3-T3 | Branchement sur le point de notification du lot 1 : toute écriture inscrit la réservation dans `agenda_google_file`. Un échec d'inscription est journalisé, et la réservation reste enregistrée. | SYNC-ERR-01 | 0,25 j |
| L3-T4 | Activation : inscription des réservations à venir du membre, comme pilote et comme instructeur, et compte rendu SYNC-ACT-06. Réactivation : réutilisation des liens existants pour le même compte (D7). | SYNC-ACT-05, SYNC-ACT-06 | 0,5 j |
| L3-T5 | Contrôleur `agenda_google_traitement` (le nom `agenda_google_sync` est pris par la bibliothèque) : entrée en ligne de commande et URL à secret (secret dans le registre, onglet Système) ; traitement de la file par lots ; essais repoussés après une erreur passagère ; suspension après une autorisation invalide, avec e-mail au membre. | SYNC-ERR-02, SYNC-ERR-03, SYNC-MAJ-08 | 1 j |
| L3-T6 | Liste des liaisons suspendues pour l'administrateur du club (membre, état, cause, date), avec ses métadonnées dans `Gvvmetadata.php`, accessible depuis l'onglet Modules ou le tableau de bord Administration du club. | SYNC-ERR-05 | 0,5 j |
| L3-T7 | Cas limites du PRD (§8) : perte du droit de réserver, membre supprimé, fonction désactivée pour le club, changement de compte Google. | §8 | 0,5 j |
| L3-T8 | Journalisation (activation, arrêt, suspension, échec persistant ; jamais le jeton) et passage de la tâche de `doc/todo.md` à `doc/done.md`. | §7 Traçabilité | 0,25 j |
| L3-T9 | Recette manuelle avec un compte Google de test (§2). | §9 | 0,5 j |

**Total estimé : ≈ 14 jours**, tests et documentation compris (§4, §5). Il ne comprend pas la démarche de validation de l'application auprès de Google (§6).

## 4. Tests

Toutes les descriptions de tests suivent la règle Gherkin en français de CLAUDE.md. Fonctionnalité de rattachement : « Réservations » (mot-clé `reservation`). Les fichiers de test de la synchronisation portent `@feature Réservations`, ou bien une ligne « Synchronisation agenda » (mot-clé `agenda`) est ajoutée au tableau de CLAUDE.md, au-dessus de « Réservations ».

### PHPUnit

| Suite | Contenu | Lot |
| :--- | :--- | :--- |
| integration | Point d'écriture unique : chaque écriture de réservation déclenche une notification avec le bon type et le bon auteur ; fusion et renommage signalent les réservations touchées | 1 |
| unit | Aucune écriture directe dans la table `reservations` en dehors du modèle (analyse des sources) | 1 |
| unit | `Secret_box` : chiffrement réversible, clé créée une seule fois, déchiffrement refusé avec une autre clé. Travaille sur un fichier de clé temporaire | 2 |
| unit | `Google_agenda_client` : construction de l'adresse d'autorisation (périmètre, état), classement des réponses de Google, sans réseau (réponses simulées) | 2-3 |
| integration | Liaison : activation, arrêt (révocation appelée, jeton effacé, liens conservés), un membre ne gère que sa liaison, fonction invisible quand le module est inactif ou que le membre ne peut pas réserver | 2 |
| integration | Réconciliation avec le faux client : création, mise à jour seulement si l'empreinte change, suppression ; pilote et instructeur ; changement de pilote ou d'instructeur ; liaison arrêtée laissée en l'état ; événement disparu ; blocs d'indisponibilité ignorés | 3 |
| integration | File : erreur passagère (la réservation reste, l'essai est repoussé), autorisation invalide (suspension, e-mail), réservation enregistrée même si l'inscription échoue | 3 |

Règles d'état (CLAUDE.md) : les tests créent leurs propres membres, réservations et liaisons, puis les suppriment, y compris en cas d'échec. Le fichier de clé de chiffrement d'un test est temporaire, et le vrai `config/agenda_google.php` n'est jamais modifié.

### Playwright

| Scénario | Lot |
| :--- | :--- |
| Un pilote déplace sa réservation au glisser-déposer : le rappel du second membre d'équipage est déclenché | 1 |
| Un membre qui peut réserver relie son agenda (bouchon), voit la synchronisation active, puis l'arrête après l'avertissement | 2 |
| Un membre qui ne peut pas réserver, ou un club où la fonction est inactive, ne voit pas le bloc Agenda Google | 2 |
| Une réservation créée puis supprimée par le membre est ajoutée puis retirée de son agenda (bouchon, après passage du traitement) | 3 |
| La même réservation, déplacée par un administrateur, est mise à jour dans l'agenda du membre | 3 |
| Une autorisation retirée (bouchon) suspend la synchronisation ; « Mes réservations » l'indique avec l'action à faire | 3 |

## 5. Validation et documentation

**À la fin de chaque lot :**
1. `php -l` sur les fichiers modifiés, `./run-all-tests.sh`, puis la suite Playwright complète.
2. Test fonctionnel sur gvv.net avec les utilisateurs de test (smoke test PHPUnit et Playwright, CLAUDE.md §22).

**À la fin du lot 3 :** revue de la PR unique (`/pr-review`), fusion après accord (`/merge-pr`).

**Documentation :**
* Utilisateur : nouvelle section « Agenda Google » dans le chapitre des réservations de `doc/users/fr/` (lot 2, complétée au lot 3).
* Installation : `doc/installation.md`, création de l'application Google (client OAuth web, adresse de retour, périmètre `calendar.events`, mode test puis validation), réglage dans la page Configuration et cron de `agenda_google_traitement` (lot 3).

## 6. Risques

| Risque | Parade |
| :--- | :--- |
| Validation de l'application par Google (périmètre sensible) longue ou refusée | Démarche lancée dès la fin du lot 2. Jusqu'à la validation, la fonction reste ouverte aux seuls comptes de test déclarés. |
| Régression des rappels au lot 1 (déplacement des notifications) | Tests de non-régression écrits avant la modification (L1-T5) ; lot livré seul, avant tout le reste. |
| Le bouchon s'active par erreur en production | Le paramètre n'existe que dans `program.php`, hors de la page Configuration ; sans lui, le contrôleur répond 404, ce qu'un test vérifie. |
| Charge de l'entrée cron toutes les minutes | Sortie immédiate si la file est vide ; traitement par lots limité. |
| Écrasement du `.gitignore` ou fuite de la clé de chiffrement | Clé créée hors dépôt avec des droits restreints, et vérifiée par le test de L2-T2. |
