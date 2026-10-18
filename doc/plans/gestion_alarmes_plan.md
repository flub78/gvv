# Plan — Gestion Générique des Alarmes

Date : 18 octobre 2026

## Références
- PRD : [doc/prds/gestion_alarmes_generiques_prd.md](../prds/gestion_alarmes_generiques_prd.md)
- Conception : [doc/design_notes/gestion_alarmes_design.md](../design_notes/gestion_alarmes_design.md)
- Notifications documentaires : PRD [archivage documentaire](../prds/archivage_documentaire_prd.md) §10, réalisées par ce plan (anciennement Lot 5 du [plan d'archivage](archivage_documentaire_plan.md))

## Objectif
Livrer le Lot 1 du MVP, limité aux alarmes sur les documents archivés : catalogue d'alarmes, abonnements individuels et par catégorie, pages de configuration et de consultation, notifications par email, message du jour et SMS, synthèses hebdomadaires et mensuelles, rôle élève et visite médicale obligatoire des élèves.

## Hypothèses de réalisation
- Seuls les membres actifs génèrent des alarmes ; `alarm_disabled = 1` et documents non approuvés sont exclus.
- Les emails et messages du jour ne contiennent que des métadonnées et un lien vers GVV, jamais un fichier.
- Redirection des emails et SMS en test via `test_intercept_email()`, comme les rappels de réservation.
- Déclenchement quotidien sur le modèle de `reservation_scheduler` : entrée CLI pour le cron et URL protégée par un secret.
- Aucun abonnement créé automatiquement, y compris à la migration.

## Lot 1 — Catalogue, abonnements et notifications

**1.1 — Détection** (1 j)
- [x] Requêtes expirés / expiration proche excluant `alarm_disabled = 1` (`get_expired_documents()`, `get_expiring_soon_documents()`)
- [ ] Filtrer les membres inactifs
- [ ] Collecte des documents obligatoires manquants pour tous les membres actifs, en tenant compte de `document_types.required_role_id`
- [ ] Résolution des sections d'un document selon sa portée (cf. conception)
- [ ] `AlarmItem`, `DocumentAlarmProvider`, `AlarmAggregator::collect()`

**1.2 — Modèle de données** (1,5 j)
- [ ] Migration `177_alarmes_lot1.php` :
  - tables `alarm_definitions`, `alarm_subscriptions`, `alarm_subscription_optouts`, `alarm_member_settings`, `notification_history` (champs d'audit inclus)
  - catalogue initial : alarmes personnelle et de supervision par type de document à expiration et pour les documents manquants
  - rôle `eleve` dans `types_roles`
  - colonne `document_types.required_role_id` ; visite médicale obligatoire pour le rôle `eleve`
  - `down()` symétrique, qui restaure `document_types` à l'identique
- [ ] `application/config/migration.php` → 177
- [ ] Modèles `alarm_definitions_model`, `alarm_subscriptions_model` (CRUD, abonnements effectifs, contrôle des droits), `alarm_member_settings_model`, `notification_history_model`
- [ ] Métadonnées `Gvvmetadata.php` (alarme, catégorie, section, canaux, fréquence, obligatoire)
- [ ] Création et désactivation des alarmes d'un type de document depuis le contrôleur des types de documents

**1.3 — Rôle élève** (1 j)
- [ ] Ouverture d'une formation : attribution du rôle `eleve` dans la section du programme, ou dans toutes les sections si le programme n'a pas de section
- [ ] Clôture ou abandon : retrait dans chaque section concernée sans autre formation ouverte ou suspendue (un programme sans section couvre toutes les sections)
- [ ] Rôle `eleve` proposé dans la gestion des rôles par section, attribuable et retirable à la main par un administrateur ; libellés FR/EN/NL

**1.4 — Bibliothèque `Notification_manager`** (2,5 j)
- [ ] Sélection par abonné : abonnements effectifs, filtre personnel / supervision, contrôle des droits, dédoublonnage
- [ ] Fréquence immédiate : occurrences pas encore notifiées dans leur état courant
- [ ] Synthèses hebdomadaire (lundi) et mensuelle (premier lundi du mois) : toutes les occurrences actives et à venir ; aucune synthèse vide
- [ ] Canal email : composition HTML listant les alarmes (groupées par alarme puis par section, avec liens)
- [ ] Canal message du jour : `motd_model::generate_system_message()` adressé à l'abonné, `source_type = 'alarm'`, visible jusqu'à la synthèse suivante
- [ ] Rappel SMS le jour de synthèse (lundi ; premier lundi du mois en mensuel) : `Brevo_sms_adapter`, au mobile de la fiche membre (`membres.mtelm`), message court + lien vers « Mes alarmes », seulement s'il y a une alarme active ou à venir, interrupteur global `alarm_sms_enabled`
- [ ] Journalisation dans `notification_history` et dans les logs (`grep "GVV: ALARM"`), poursuite du traitement si un envoi échoue
- [ ] Idempotence : une exécution répétée le même jour n'envoie rien de plus

**1.5 — Déclencheur** (0,5 j)
- [ ] Contrôleur `alarm_scheduler.php` : `cron()` (CLI uniquement) et `run($secret)` (URL publique, réponse JSON)
- [ ] Secret `alarm_scheduler_secret` et interrupteur `alarm_sms_enabled` dans `program.php` et `program.example.php`
- [ ] Ligne crontab d'exemple (quotidienne) dans l'en-tête du contrôleur et la doc utilisateur

**1.6 — Page de configuration des alarmes** (2 j)
- [ ] Contrôleur `alarmes_config.php` : liste des alarmes proposées au membre avec cases à cocher, canaux, rappel SMS, fréquence
- [ ] Alarmes de supervision limitées à celles que le membre peut consulter
- [ ] Abonnements créés par un administrateur affichés avec leur auteur ; abonnements obligatoires non décochables ; retrait d'un abonnement de catégorie (`alarm_subscription_optouts`)
- [ ] Au moins un canal exigé au premier abonnement ; option SMS désactivée et expliquée sans numéro de mobile
- [ ] Pour `club-admin` : sélecteur de membre sur la même page, positionné par défaut sur son propre profil ; configuration d'un autre membre avec les mêmes règles (y compris le rappel SMS), refus explicite hors droits du membre configuré
- [ ] Entrée « Configuration des alarmes » dans l'espace personnel ; message de confirmation après chaque action

**1.7 — Page « Mes alarmes »** (1 j)
- [ ] Contrôleur `mes_alarmes.php` : occurrences actives et à venir des abonnements effectifs, filtres par alarme et par section, liens de traitement
- [ ] Accès refusé sans abonnement effectif
- [ ] Entrée de menu et carte du tableau de bord affichées seulement si le membre a un abonnement effectif

**1.8 — Administration** (2 j)
- [ ] Contrôleur `alarmes_admin.php`, réservé à `club-admin` : catalogue (activation, délai d'anticipation)
- [ ] Abonnements de catégories : création (alarme, rôle, section, obligatoire), modification, suppression
- [ ] Liste des alarmes actives sans abonné
- [ ] Entrées de menu (Admin → Alarmes)

**1.9 — Langues** (0,5 j)
- [ ] Libellés FR/EN/NL : catalogue, emails, messages du jour, SMS, pages de configuration, de consultation et d'administration, scheduler

**1.10 — Tests** (3 j)
- [ ] PHPUnit MySQL `AlarmSubscriptionsModelTest.php` : abonnements individuels et par catégorie, retraits, obligatoires, abonnements effectifs après changement de rôle, contrôle des droits, aucun abonnement par défaut
- [ ] PHPUnit MySQL `DocumentAlarmProviderTest.php` : documents expirés, proches, manquants (dont obligation par rôle), alertes désactivées, sections d'un document selon sa portée
- [ ] PHPUnit MySQL `NotificationManagerTest.php` : personnel / supervision, dédoublonnage, fréquences et jours d'envoi, contenu des synthèses (actives et à venir), canaux, rappel SMS (jour de synthèse, un par période, rien sans alarme, interrupteur global, sans mobile, texte limité au lien), idempotence, membres sans configuration ; envois bouchonnés
- [ ] PHPUnit MySQL rôle élève : attribution à l'ouverture (section du programme, toutes les sections sans section), retrait à la clôture et à l'abandon, maintien si une autre formation est en cours, retrait manuel respecté
- [ ] PHPUnit migration 177 : up/down avec sauvegarde et restauration de `document_types` et `types_roles`
- [ ] Toutes les fixtures sont créées et supprimées par les tests (y compris en cas d'échec), sans dépendance aux données existantes
- [ ] Playwright `alarmes-smoke.spec.js` : configuration par le membre, page « Mes alarmes » masquée puis visible, sélecteur de membre `club-admin` positionné sur son propre profil puis configuration d'un autre membre, abonnement de catégorie, refus hors droits, déclenchement `run($secret)` (403 avec un mauvais secret, JSON avec le bon)
- [ ] Smoke tests complets phpunit + playwright avant clôture

**1.11 — Documentation** (0,5 j)
- [ ] Guide utilisateur `doc/users/fr/19_alarmes.md` : configuration, consultation, administration, configuration du cron, du secret et des SMS

**Estimation totale Lot 1 : ~15,5 jours.** Ressources : serveur de test gvv.net (redirection email `test_email` active), compte Brevo de test pour les SMS, accès crontab pour la validation en conditions réelles.

**Validation**
- Un membre sans abonnement ne reçoit rien et ne voit pas « Mes alarmes » ; il la voit dès qu'un administrateur l'abonne.
- Un instructeur abonné via la catégorie « instructeur » à la supervision des visites médicales des élèves reçoit, en synthèse hebdomadaire le lundi, les visites expirées ou proches de l'expiration des élèves, et seulement celles-ci.
- Une même occurrence couverte par deux abonnements n'apparaît qu'une fois ; une seconde exécution le même jour n'envoie rien.
- Un abonné qui a coché le rappel SMS reçoit le lundi un seul SMS court contenant le lien vers « Mes alarmes », et aucun SMS s'il n'a pas d'alarme active ou à venir.
- L'ouverture d'une formation donne le rôle élève, la clôture le retire si aucune autre formation n'est en cours.

## Lot 2 — Alarmes de conformité calculées
- [ ] Règle « vols sans licence » dans `licences_model`, sur le modèle de `pilotes_vols_sans_cotisation()`
- [ ] `ComputedAlarmProvider` (vols sans licence, vols sans cotisation) branché sur `AlarmAggregator`
- [ ] Migration : alarmes de supervision correspondantes dans le catalogue
- [ ] Tests PHPUnit des deux règles et de leur notification ; libellés FR/EN/NL

## Lots suivants
- Alarmes de maintenance, d'expérience récente et d'inactivité (extraction des règles d'`alarmes.php` vers `ComputedAlarmProvider`).
- Acquittement et suspension des alarmes.
- Vue consolidée avancée (sévérité, tri, historique).
