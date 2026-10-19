# Plan — Gestion Générique des Alarmes

Date : 18 octobre 2026 — mis à jour le 19 octobre 2026 (page unique « Mes alarmes », abonnements par alarme et par section, alarmes propres)

## Références
- PRD : [doc/prds/gestion_alarmes_generiques_prd.md](../prds/gestion_alarmes_generiques_prd.md)
- Conception : [doc/design_notes/gestion_alarmes_design.md](../design_notes/gestion_alarmes_design.md)
- Notifications documentaires : PRD [archivage documentaire](../prds/archivage_documentaire_prd.md) §10, réalisées par ce plan (anciennement Lot 5 du [plan d'archivage](archivage_documentaire_plan.md))

## Objectif
Livrer le Lot 1 du MVP, limité aux alarmes sur les documents archivés : catalogue d'alarmes, alarmes propres de chaque membre, abonnements individuels et par catégorie par alarme et par section, page « Mes alarmes » (configuration puis liste), notifications par email, message du jour et SMS, synthèses hebdomadaires et mensuelles, rôle élève et visite médicale obligatoire des élèves.

## Hypothèses de réalisation
- Seuls les membres actifs génèrent des alarmes ; `alarm_disabled = 1` et documents non approuvés sont exclus.
- Les emails et messages du jour ne contiennent que des métadonnées et un lien vers GVV, jamais un fichier.
- Redirection des emails et SMS en test via `test_intercept_email()`, comme les rappels de réservation.
- Déclenchement quotidien sur le modèle de `reservation_scheduler` : entrée CLI pour le cron et URL protégée par un secret.
- Aucun abonnement créé automatiquement, y compris à la migration.

## Lot 1 — Catalogue, abonnements et notifications

**1.1 — Détection** (1 j)
- [x] Requêtes expirés / expiration proche excluant `alarm_disabled = 1` (`get_expired_documents()`, `get_expiring_soon_documents()`)
- [x] Filtrer les membres inactifs
- [x] Collecte des documents obligatoires manquants pour tous les membres actifs, en tenant compte de `document_types.required_role_id`
- [x] Résolution des sections d'un document selon sa portée (cf. conception)
- [x] `AlarmItem`, `DocumentAlarmProvider`, `AlarmAggregator::collect()`

**1.2 — Modèle de données** (1,5 j)
- [x] Migration `177_alarmes_lot1.php` :
  - tables `alarm_definitions`, `alarm_subscriptions` et `alarm_subscription_optouts` (avec `section_id` : colonne de la case, NULL = Club), `alarm_member_settings`, `notification_history` (champs d'audit inclus)
  - catalogue initial : une alarme par type de document à expiration (pas une par document), une pour les documents manquants, une pour les visites médicales des élèves (`alarm_definitions.target_role_id`)
  - rôle `eleve` dans `types_roles`
  - colonne `document_types.required_role_id` ; visite médicale obligatoire pour le rôle `eleve`
  - colonne `document_types.alarm_enabled` (alarmes possibles), à 1 pour tous les types existants
  - `down()` symétrique, qui restaure `document_types` à l'identique
- [x] `application/config/migration.php` → 177
- [x] Modèles `alarm_definitions_model`, `alarm_subscriptions_model` (CRUD, cases autorisées et effectives, contrôle des droits), `alarm_member_settings_model`, `notification_history_model`
- [x] Métadonnées `Gvvmetadata.php` : `document_types.required_role_id` et `alarm_enabled` (formulaire et liste des types) ; booléens de la liste des types modifiables en un clic (sous-type `toggle`, `document_types/ajax_toggle`) ; les pages d'alarmes affichent des données calculées (occurrences, abonnements effectifs), en tableaux Bootstrap
- [x] Création et désactivation des alarmes d'un type de document depuis le contrôleur des types de documents

**1.3 — Rôle élève** (1 j)
- [x] Ouverture d'une formation : attribution du rôle `eleve` dans la section du programme, ou dans toutes les sections si le programme n'a pas de section
- [x] Clôture ou abandon : retrait dans chaque section concernée sans autre formation ouverte ou suspendue (un programme sans section couvre toutes les sections)
- [x] Rôle `eleve` proposé dans la gestion des rôles par section, attribuable et retirable à la main par un administrateur ; libellés FR/EN/NL

**1.4 — Bibliothèque `Notification_manager`** (2,5 j)
- [x] Sélection par membre : ses alarmes propres (toujours) et les occurrences de ses cases effectives (section ou Club) qu'il peut superviser, dédoublonnage ; membres traités : membres actifs ayant choisi un canal ou le SMS
- [x] Fréquence immédiate : occurrences pas encore notifiées dans leur état courant
- [x] Synthèses hebdomadaire (lundi) et mensuelle (premier lundi du mois) : toutes les occurrences actives et à venir ; aucune synthèse vide
- [x] Canal email : composition HTML listant les alarmes (groupées par alarme puis par section, avec liens)
- [x] Canal message du jour : `motd_model::generate_system_message()` adressé à l'abonné, `source_type = 'alarm'`, visible jusqu'à la synthèse suivante
- [x] Rappel SMS le jour de synthèse (lundi ; premier lundi du mois en mensuel) : `Brevo_sms_adapter`, au mobile de la fiche membre (`membres.mtelm`), message court + lien vers « Mes alarmes », seulement s'il y a une alarme active ou à venir, interrupteur global `alarm_sms_enabled`
- [x] Journalisation dans `notification_history` et dans les logs (`grep "GVV: ALARM"`), poursuite du traitement si un envoi échoue
- [x] Idempotence : une exécution répétée le même jour n'envoie rien de plus

**1.5 — Déclencheur** (0,5 j)
- [x] Contrôleur `alarm_scheduler.php` : `cron()` (CLI uniquement) et `run($secret)` (URL publique, réponse JSON)
- [x] Secret `alarm_scheduler_secret` et interrupteur `alarm_sms_enabled` dans `program.php` et `program.example.php`
- [x] Ligne crontab d'exemple (quotidienne) dans l'en-tête du contrôleur ; doc utilisateur en 1.10

**1.6 — Page « Mes alarmes »** (3 j)
- [x] Contrôleur `mes_alarmes.php`, une seule page : configuration en haut, liste des alarmes en dessous
- [x] Matrice alarme × colonne (sections supervisées, plus Club pour les documents sans section) ; une case seulement là où le membre a le droit ; pas de matrice sans droit de supervision, avec un message indiquant que les alarmes propres sont toujours suivies
- [x] Cases cochées par un administrateur affichées avec leur auteur ; cases obligatoires non décochables ; retrait d'une case d'un abonnement de catégorie (`alarm_subscription_optouts`)
- [x] Canaux (aucun exigé), rappel SMS, fréquence ; option SMS désactivée et expliquée sans numéro de mobile
- [x] Liste : alarmes propres et occurrences des cases effectives, actives et à venir, filtres par alarme et par section, liens de traitement
- [x] Pour `club-admin` : sélecteur de membre positionné par défaut sur son propre profil ; configuration et alarmes d'un autre membre avec les mêmes règles, refus explicite hors droits du membre configuré
- [x] Une seule entrée « Mes alarmes » dans l'espace personnel (menu et tableau de bord), pour tout membre ; la carte du tableau de bord affiche le nombre d'alarmes actives ; message de confirmation après chaque action

**1.7 — Administration** (2 j)
- [x] Contrôleur `alarmes_admin.php`, réservé à `club-admin` : catalogue (activation, délai d'anticipation)
- [x] Abonnements de catégories : création (alarme, rôle, section, obligatoire) et suppression ; chez chaque titulaire, l'abonnement coche la case de la section du rôle
- [x] Liste des alarmes actives ou à venir que personne ne reçoit (ni le membre concerné avec un canal, ni un abonné)
- [x] Carte « Administration des alarmes » du tableau de bord Administration club (`club-admin`)

**1.8 — Langues** (0,5 j)
- [x] Libellés FR/EN/NL : catalogue, emails, messages du jour, SMS, page Mes alarmes et page d'administration, scheduler

**1.9 — Tests** (3 j)
- [x] PHPUnit MySQL `AlarmesLot1ModelsTest.php` : catalogue, cases autorisées (sections supervisées, colonne Club), abonnements individuels et par catégorie par section, retraits de case, obligatoires, cases effectives après changement de rôle, contrôle des droits, aucun abonnement par défaut ; documents expirés, proches, manquants (dont obligation par rôle), alertes désactivées, pilotes inactifs, sections
- [x] PHPUnit MySQL `NotificationManagerTest.php` : alarmes propres sans abonnement, occurrences limitées aux pilotes actifs des sections cochées, colonne Club, dédoublonnage, fréquences et jours d'envoi, contenu des synthèses (actives et à venir), canaux, rappel SMS (jour de synthèse, un par période, rien sans alarme, interrupteur global, sans mobile, texte limité au lien), idempotence, membres sans canal ; envois bouchonnés
- [x] PHPUnit MySQL rôle élève : attribution à l'ouverture (section du programme, toutes les sections sans section), retrait à la clôture et à l'abandon, maintien si une autre formation est en cours, retrait manuel respecté
- [x] PHPUnit migration 177 : up/down avec sauvegarde et restauration de `document_types` et `types_roles`
- [x] Toutes les fixtures sont créées et supprimées par les tests (y compris en cas d'échec), sans dépendance aux données existantes
- [x] Playwright `document-types-toggle-smoke.spec.js` : bascule en un clic des booléens de la liste des types, effet sur le catalogue
- [x] Playwright `alarmes-smoke.spec.js` : page « Mes alarmes » d'un simple membre (alarmes propres, pas de matrice), matrice par section d'un superviseur et alarmes filtrées par section, sélecteur de membre `club-admin` positionné sur son propre profil puis configuration d'un autre membre, abonnement de catégorie, refus hors droits, déclenchement `run($secret)` (403 avec un mauvais secret, JSON avec le bon)
- [x] Smoke tests complets phpunit + playwright avant clôture

**1.10 — Documentation** (0,5 j)
- [x] Guide utilisateur `doc/users/fr/19_alarmes.md` : page Mes alarmes (alarmes propres, matrice par section, réception, liste), administration, configuration du cron, du secret et des SMS

**Estimation totale Lot 1 : ~15,5 jours.** Ressources : serveur de test gvv.net (redirection email `test_email` active), compte Brevo de test pour les SMS, accès crontab pour la validation en conditions réelles.

**Validation**
- Un membre sans abonnement voit ses propres alarmes sur « Mes alarmes », sans pouvoir les désactiver ; il ne les reçoit que s'il a choisi un canal.
- Un vice-président abonné à l'alarme du certificat médical dans la seule colonne avion ne voit que les pilotes actifs en section avion.
- Un instructeur abonné via la catégorie « instructeur » de sa section à la supervision des visites médicales des élèves reçoit, en synthèse hebdomadaire le lundi, les visites expirées ou proches de l'expiration des élèves, et seulement celles-ci.
- Une même occurrence couverte par deux abonnements n'apparaît qu'une fois ; une seconde exécution le même jour n'envoie rien.
- Un abonné qui a coché le rappel SMS reçoit le lundi un seul SMS court contenant le lien vers « Mes alarmes », et aucun SMS s'il n'a pas d'alarme active ou à venir.
- L'ouverture d'une formation donne le rôle élève, la clôture le retire si aucune autre formation n'est en cours.

## Lot 2 — Alarmes de conformité calculées
- [ ] Règle « vols sans licence » dans `licences_model`, sur le modèle de `pilotes_vols_sans_cotisation()`
- [ ] `ComputedAlarmProvider` (vols sans licence, vols sans cotisation) branché sur `AlarmAggregator`
- [ ] Migration : alarmes correspondantes dans le catalogue
- [ ] Tests PHPUnit des deux règles et de leur notification ; libellés FR/EN/NL

## Lots suivants
- Alarmes de maintenance, d'expérience récente et d'inactivité (extraction des règles d'`alarmes.php` vers `ComputedAlarmProvider`).
- Acquittement et suspension des alarmes.
- Vue consolidée avancée (sévérité, tri, historique).
