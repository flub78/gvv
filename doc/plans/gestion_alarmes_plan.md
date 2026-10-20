# Plan — Gestion Générique des Alarmes

Date : 18 octobre 2026 — mis à jour le 19 octobre 2026 (page unique « Mes alarmes », abonnements par alarme et par section, alarmes propres)

## Références
- PRD : [doc/prds/gestion_alarmes_generiques_prd.md](../prds/gestion_alarmes_generiques_prd.md)
- Conception : [doc/design_notes/gestion_alarmes_design.md](../design_notes/gestion_alarmes_design.md)
- Notifications documentaires : PRD [archivage documentaire](../prds/archivage_documentaire_prd.md) §10, réalisées par ce plan (anciennement Lot 5 du [plan d'archivage](archivage_documentaire_plan.md))

## Objectif
Livrer le Lot 1 du MVP, limité aux alarmes sur les documents archivés : catalogue d'alarmes, alarmes propres de chaque membre, abonnements individuels et par catégorie par alarme et par section, page « Mes alarmes » (liste puis configuration), notifications par email, message du jour et SMS, synthèses hebdomadaires et mensuelles, rôle élève et visite médicale obligatoire des élèves.

## Hypothèses de réalisation
- Seuls les membres actifs génèrent des alarmes ; `alarm_disabled = 1` et documents non approuvés sont exclus.
- Les emails et messages du jour ne contiennent que des métadonnées et un lien vers GVV, jamais un fichier.
- Redirection des emails et SMS en test via `test_intercept_email()`, comme les rappels de réservation.
- Déclenchement par le cron horaire existant de `reservation_scheduler` (une seule entrée cron), à partir d'une heure configurable ; lancement manuel en CLI ou par URL protégée par un secret.
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
- [x] Traitement des alarmes par le cron horaire de `reservation_scheduler` (une seule entrée cron), à partir de `alarm_scheduler_hour` (`Notification_manager::run_scheduled()`)
- [x] Contrôleur `alarm_scheduler.php` pour un lancement manuel : `cron()` (CLI uniquement) et `run($secret)` (URL publique, réponse JSON)
- [x] Secret `alarm_scheduler_secret`, interrupteur `alarm_sms_enabled` et heure `alarm_scheduler_hour` dans `program.php` et `program.example.php`
- [x] Pas d'entrée crontab dédiée ; doc utilisateur en 1.10

**1.6 — Page « Mes alarmes »** (3 j)
- [x] Contrôleur `mes_alarmes.php`, une seule page : configuration en haut, liste des alarmes en dessous
- [x] Matrice alarme × colonne (sections supervisées, plus Club pour les documents sans section) ; une case seulement là où le membre a le droit ; cocher ou décocher une ligne, une colonne ou toute la matrice en un clic (cases obligatoires exclues) ; pas de matrice sans droit de supervision, avec un message indiquant que les alarmes propres sont toujours suivies
- [x] Cases cochées par un administrateur affichées avec leur auteur ; cases obligatoires non décochables ; retrait d'une case d'un abonnement de catégorie (`alarm_subscription_optouts`)
- [x] Canaux (aucun exigé), rappel SMS, fréquence ; option SMS désactivée et expliquée sans numéro de mobile
- [x] Liste : alarmes propres et occurrences des cases effectives, actives et à venir, filtres par alarme et par section, liens de traitement
- [x] Pour `club-admin` : sélecteur de membre positionné par défaut sur son propre profil ; configuration et alarmes d'un autre membre avec les mêmes règles, refus explicite hors droits du membre configuré
- [x] Une seule entrée « Mes alarmes » dans l'espace personnel (menu et tableau de bord), pour tout membre ; la carte du tableau de bord affiche le nombre d'alarmes actives ; message de confirmation après chaque action

**1.7 — Administration** (2 j)
- [x] Contrôleur `alarmes_admin.php`, réservé à `club-admin` : catalogue (activation, délai d'anticipation)
- [x] Abonnements de catégories : création (alarme, rôle, section, obligatoire) et suppression ; chez chaque titulaire, l'abonnement coche la case de la section du rôle
- [x] Liste des alarmes actives ou à venir que personne ne reçoit (ni le membre concerné avec un canal, ni un abonné)
- [x] Carte « Administration des alarmes » du tableau de bord Administration système (`club-admin`)

**1.8 — Langues** (0,5 j)
- [x] Libellés FR/EN/NL : catalogue, emails, messages du jour, SMS, page Mes alarmes et page d'administration, scheduler

**1.9 — Tests** (3 j)
- [x] PHPUnit MySQL `AlarmesLot1ModelsTest.php` : catalogue, cases autorisées (sections supervisées, colonne Club), abonnements individuels et par catégorie par section, retraits de case, obligatoires, cases effectives après changement de rôle, contrôle des droits, aucun abonnement par défaut ; documents expirés, proches, manquants (dont obligation par rôle), alertes désactivées, pilotes inactifs, sections
- [x] PHPUnit MySQL `NotificationManagerTest.php` : alarmes propres sans abonnement, occurrences limitées aux pilotes actifs des sections cochées, colonne Club, dédoublonnage, fréquences et jours d'envoi, contenu des synthèses (actives et à venir), canaux, rappel SMS (jour de synthèse, un par période, rien sans alarme, interrupteur global, sans mobile, texte limité au lien), idempotence, cron horaire (rien avant l'heure, une seule synthèse et un seul SMS par lundi), membres sans canal ; envois bouchonnés
- [x] PHPUnit MySQL rôle élève : attribution à l'ouverture (section du programme, toutes les sections sans section), retrait à la clôture et à l'abandon, maintien si une autre formation est en cours, retrait manuel respecté
- [x] PHPUnit migration 177 : up/down avec sauvegarde et restauration de `document_types` et `types_roles`
- [x] Toutes les fixtures sont créées et supprimées par les tests (y compris en cas d'échec), sans dépendance aux données existantes
- [x] Playwright `document-types-toggle-smoke.spec.js` : bascule en un clic des booléens de la liste des types, effet sur le catalogue
- [x] Playwright `alarmes-smoke.spec.js` : page « Mes alarmes » d'un simple membre (alarmes propres, pas de matrice), matrice par section d'un superviseur et alarmes filtrées par section, ligne, colonne et matrice entières cochées ou décochées en un clic, sélecteur de membre `club-admin` positionné sur son propre profil puis configuration d'un autre membre, abonnement de catégorie, refus hors droits, déclenchement `run($secret)` (403 avec un mauvais secret, JSON avec le bon)
- [x] Smoke tests complets phpunit + playwright avant clôture

**1.10 — Documentation** (0,5 j)
- [x] Guide utilisateur `doc/users/fr/19_alarmes.md` : page Mes alarmes (alarmes propres, matrice par section, réception, liste), administration, déclenchement par le cron des rappels de réservation, secret et SMS

**Estimation totale Lot 1 : ~15,5 jours.** Ressources : serveur de test gvv.net (redirection email `test_email` active), compte Brevo de test pour les SMS, accès crontab pour la validation en conditions réelles.

**Validation**
- Un membre sans abonnement voit ses propres alarmes sur « Mes alarmes », sans pouvoir les désactiver ; il ne les reçoit que s'il a choisi un canal.
- Un vice-président abonné à l'alarme du certificat médical dans la seule colonne avion ne voit que les pilotes actifs en section avion.
- Un instructeur abonné via la catégorie « instructeur » de sa section à la supervision des visites médicales des élèves reçoit, en synthèse hebdomadaire le lundi, les visites expirées ou proches de l'expiration des élèves, et seulement celles-ci.
- Une même occurrence couverte par deux abonnements n'apparaît qu'une fois ; une seconde exécution le même jour n'envoie rien.
- Un abonné qui a coché le rappel SMS reçoit le lundi un seul SMS court contenant le lien vers « Mes alarmes », et aucun SMS s'il n'a pas d'alarme active ou à venir.
- L'ouverture d'une formation donne le rôle élève, la clôture le retire si aucune autre formation n'est en cours.

## Lot 2 — Alarmes de conformité calculées

Vols de l'année civile en cours, pilotes extérieurs exclus ; licence de l'activité du vol (planeur 1, avion 2, ULM 3) ; supervision ouverte en plus au trésorier de la section.

**2.1 — Règles** (1 j)
- [x] `licences_model::pilotes_vols_sans_cotisation()` : sections des vols en plus (planeur : section PLA ; avion, ULM : `volsa.club`), sans changer la page « Vols sans cotisation »
- [x] `licences_model::pilotes_vols_sans_licence($year)` : une ligne par pilote et par activité sans licence de l'activité

**2.2 — Fournisseur et catalogue** (1 j)
- [x] `Computed_alarm_provider` (règles `vols_sans_cotisation`, `vols_sans_licence`) branché sur `Alarm_aggregator`
- [x] Migration `178_alarmes_lot2.php` : alarmes `computed_vols_sans_cotisation` et `computed_vols_sans_licence` ; `config/migration.php` → 178
- [x] Libellés traduits des alarmes calculées ; occurrences sans lien pour le pilote (licence) : pas de bouton « Ouvrir »
- [x] Droit de supervision du rôle `tresorier` sur les alarmes calculées

**2.3 — Tests et documentation** (1 j)
- [x] PHPUnit MySQL : les deux règles (vols planeur, avion, ULM ; licence d'une autre activité ; année précédente ; pilote extérieur ; régularisation), occurrences et sections, droits du trésorier, alarme propre du pilote, migration 178
- [x] Playwright : alarme calculée visible sur « Mes alarmes » d'un superviseur abonné
- [x] Guide utilisateur `19_alarmes.md`, libellés FR/EN/NL

**Validation**
- Un pilote qui vole sans cotisation de l'année voit l'alarme sur sa page ; elle disparaît quand la cotisation est enregistrée.
- Le trésorier de la section avion abonné à « vols sans licence » en colonne Avion voit les pilotes ayant volé en avion sans licence avion, et pas les vols planeur.

## Lot 3 — Expérience récente et compte débiteur

Par section ; seuils fixes (3 atterrissages CDB en 90 jours, 10 remorqués en 12 mois, 120 jours, 24 mois), délai d'anticipation réglable dans le catalogue.

**3.1 — Rôle et catalogue** (0,5 j)
- [x] Migration `179_alarmes_lot3.php` : rôle `emport_passager` (par section, attribué en section planeur aux membres ayant l'événement « Emport passager ») ; alarmes `computed_emport_passager`, `computed_remorquages`, `computed_inactivite` (délai 15 j) et `computed_compte_debiteur` ; `config/migration.php` → 179
- [x] Libellés du rôle (FR/EN/NL), proposé dans la gestion des rôles par section

**3.2 — Règles** (1,5 j)
- [x] `Computed_alarm_provider` : `emport_passager` (atterrissages CDB, échéance du 3ᵉ plus récent + 90 j), `remorquages` (rôle `pilote_rem`, remorqués enregistrés sur les vols planeur, échéance du 10ᵉ plus récent + 12 mois), `inactivite` (dernier vol + 120 j, actifs non élèves ayant volé depuis moins de 24 mois), `compte_debiteur` (`Relances_model::soldes_debiteurs_par_section()` : calcul des relances réduit au solde du jour, seuil `relances.seuil_alarme`)
- [x] Pilotes extérieurs et sections où le pilote n'est pas actif exclus de toutes les alarmes (filtre commun dans `Alarm_aggregator`) ; « Mes alarmes » présente la liste avant la configuration
- [x] Tests : réglages du catalogue (activation) préservés par les tests, y compris les allers-retours de migration ; date de référence du fournisseur restaurée après chaque test
- [x] Revue de code PR #102 (`doc/reviews/pr102_alarmes_lot3.md`) : rôles globaux (super-trésorier) valables pour toutes les sections ; compte débiteur signalé même pour un pilote parti (pilotes extérieurs toujours exclus) ; règle unique pour la section des vols planeur ; code commun emport passager / remorquage ; seuil par défaut des relances partagé
- [x] Licence exigée par section : colonne `sections.licence_type` (fiche section, migration 179), utilisée par « vols sans licence » et par la page « Licences par année » ; vols planeur rattachés à la première section `gestion_planeurs` (`Licences_model::glider_section_id()`, règle commune aux Lots 2 et 3) ; remorquages couverts par la licence planeur ; plus aucune correspondance section → licence dans le code (l'acronyme ULM reste utilisé pour les colonnes planeur / avion / ULM de la page existante « Vols sans cotisation »)
- [x] Catégories d'alarmes (documents, cotisation et licence, comptes, expérience récente) : regroupement de la matrice, couleur et icône dans la matrice, la liste et l'administration
- [x] Droits : instructeur et RP en plus pour l'emport passager, le remorquage et l'inactivité ; bureau, trésoriers et admin club seuls pour le compte débiteur
- [x] Délai d'anticipation réglable dans le catalogue pour les alarmes calculées à échéance

**3.3 — Tests et documentation** (1 j)
- [x] PHPUnit MySQL : les quatre règles (fenêtres, états à venir et actif, double commande et instructeur, remorqués, élève exclu, 24 mois, seuil), sections, droits, migration 179
- [x] Playwright : nouvelles alarmes dans la matrice d'un administrateur ; alarme de compte débiteur absente pour un membre du CA
- [x] Guide utilisateur `19_alarmes.md`

**Validation**
- Un pilote de la section planeur ayant le rôle emport passager et 2 vols CDB dans les 90 derniers jours voit l'alarme active ; un 3ᵉ vol la fait disparaître.
- Un membre qui n'a pas volé en avion depuis 125 jours voit l'alarme « 120 jours sans voler » de la section avion ; un vol avec instructeur la fait disparaître.
- Le trésorier abonné voit les pilotes dont la dette dans sa section dépasse le seuil des relances ; un membre du CA ne peut pas s'y abonner.

## Affichage par section active

La page « Mes alarmes » et la carte du tableau de bord suivent la section active ; les notifications ne changent pas.

- [x] `Notification_manager::item_in_section()` (occurrence sans section ou de la section) et `active_counts_for()` (actives de la section et total)
- [x] `mes_alarmes` : filtre de section par défaut = section active (« Toutes » = aucun filtre), paramètre `section` vide pour toutes les sections ; bandeau du nombre d'alarmes masquées avec lien « toutes les sections »
- [x] Carte « Mes alarmes » : nombre de la section active, total dans l'infobulle et en badge gris quand la section n'en a aucune
- [x] Libellés FR/EN/NL ; guide utilisateur `19_alarmes.md`
- [x] PHPUnit `NotificationManagerTest.php` : `item_in_section()`, `active_counts_for()` ; Playwright `alarmes-smoke.spec.js` : liste filtrée par la section active, bandeau et lien vers toutes les sections

**Validation**
- Section ULM active : un trésorier abonné au compte débiteur en planeur et en ULM ne voit que les débiteurs ULM, un bandeau indique le nombre d'alarmes planeur masquées ; « Toutes » les affiche toutes.

## Lot 4 — Désactivation des occurrences

Désactivation d'une occurrence pour soi (superviseur) ou pour tout le monde (`club-admin`), définitive ou jusqu'à une date ; réactivation automatique et par `club-admin`.

**4.1 — Données** (0,5 j)
- [ ] Migration : table `alarm_dismissals` (clé d'occurrence, alarme, membre ou tous, date de fin, motif, instantanés d'état et d'échéance, réactivation, champs d'audit) ; `config/migration.php`
- [ ] `alarm_dismissals_model` : désactiver, réactiver, désactivations en cours d'un lecteur, droits (pour soi : occurrence non propre de ses cases ; pour tout le monde et toute réactivation : `club-admin`), réactivation automatique (date, changement d'état ou d'échéance, occurrence disparue)

**4.2 — Filtrage** (0,5 j)
- [ ] `Notification_manager::occurrences_for()` : écarte les désactivations pour tout le monde (propres comprises) et pour le lecteur, les rend à part ; page, carte, synthèses et SMS en bénéficient
- [ ] Alarmes que personne ne reçoit sans les désactivations pour tout le monde ; réactivation des désactivations d'occurrences disparues au passage du scheduler

**4.3 — Pages** (1,5 j)
- [ ] `mes_alarmes` : bouton « Désactiver » par ligne (pour moi ; pour tout le monde réservé à `club-admin`), boîte de dialogue définitive ou date de fin, motif (obligatoire pour tout le monde), confirmation
- [ ] `mes_alarmes` : nombre d'alarmes désactivées, affichage à la demande avec auteur, motif et date de fin, bouton « Réactiver » selon droits
- [ ] `alarmes_admin` : désactivations en cours (pour tout le monde et par membre) avec réactivation
- [ ] Libellés FR/EN/NL ; guide utilisateur `19_alarmes.md`

**4.4 — Tests** (1 j)
- [ ] PHPUnit MySQL : droits (propre refusée, hors cases refusée, pour tout le monde réservée à `club-admin`), filtrage pour soi et pour tout le monde (pilote compris), réactivation à la date, au changement d'état ou d'échéance, à la disparition de l'occurrence, réactivation manuelle, synthèses sans les désactivées ; migration up/down avec sauvegarde
- [ ] Playwright : désactivation pour soi puis réactivation, désactivation pour tout le monde par `club-admin` (invisible pour le pilote et un autre abonné), réactivation depuis l'administration

**Estimation totale Lot 4 : ~3,5 jours.**

**Validation**
- Un trésorier désactive pour lui le compte débiteur d'un pilote jusqu'au 31/12 : il ne le voit ni ne le reçoit plus, un autre trésorier abonné le voit toujours ; il réapparaît le 1/1.
- Un administrateur club désactive pour tout le monde « vols sans licence » d'un pilote licencié ailleurs, avec un motif : plus personne ne la voit, pilote compris ; il la réactive depuis l'administration.
- Une alarme « à venir » désactivée réapparaît quand elle devient active.

## Lots suivants
- Alarmes de maintenance, autres alarmes d'expérience récente (extraction des règles d'`alarmes.php` vers `ComputedAlarmProvider`).
- Vue consolidée avancée (sévérité, tri, historique).
