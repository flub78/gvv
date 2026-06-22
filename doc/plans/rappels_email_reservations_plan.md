# Plan d'implémentation — Rappels Email/SMS des Réservations

**Date :** 21 juin 2026  
**Statut :** Presque terminé — Phase 13 (documentation) incomplète  
**PRD :** [doc/prds/rappels_email_reservations_prd.md](../prds/rappels_email_reservations_prd.md)  
**Design :** [doc/design_notes/rappels_email_reservations_design.md](../design_notes/rappels_email_reservations_design.md)  

---

## Résumé

Ce plan couvre l'implémentation des rappels email/SMS autour des réservations d'aéronefs. Il comprend :
- La table de traçabilité `reservation_reminder_log` et les préférences utilisateur
- La bibliothèque de rappels (logique métier centrale)
- La page "Mes réservations" avec gestion des préférences
- L'adaptateur événementiel dans le contrôleur de réservations
- Le scheduler horaire (cron + URL publique)
- L'envoi email et SMS (Brevo)
- La configuration administration

**Prérequis** : Le module de réservations d'aéronefs doit être opérationnel.  
**Migration de départ** : 130 → prochain numéro disponible = 131.

---

## Phase 1 — Infrastructure base de données

### Étape 1.1 — Migration `reservation_reminder_log`

**Objectif :** Créer la table de traçabilité idempotente des rappels.

**Fichier :** `application/migrations/131_reservation_reminder_log.php`

Colonnes :
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `idempotency_key` VARCHAR(255) UNIQUE — triplet `(reservation_id, deadline, type)`
- `reservation_id` INT NOT NULL
- `trigger_source` ENUM(`event_create`, `event_update`, `event_cancel`, `cron`, `public_url`)
- `action_type` ENUM(`scheduled_reminder`, `event_notification`)
- `notification_type` VARCHAR(50)
- `recipients` TEXT — JSON
- `channel` ENUM(`email`, `sms`, `email+sms`)
- `provider` VARCHAR(50) — `smtp`, `brevo`
- `sent_at` DATETIME
- `status` ENUM(`success`, `failure`, `skipped`)
- `message_body` TEXT
- `error_message` TEXT
- `created_at`, `updated_at` TIMESTAMP
- `created_by`, `updated_by` VARCHAR(255)

**Validation :**
- [x] Migration créée et syntaxe PHP valide (`php -l`)
- [x] `config/migration.php` mis à jour à la version 131
- [x] Table créée en base
- [x] Contrainte UNIQUE sur `idempotency_key` vérifiée

---

### Étape 1.2 — Migration préférences utilisateur

**Objectif :** Ajouter les préférences de rappel aux membres.

**Fichier :** `application/migrations/132_membres_reminder_preferences.php`

Colonnes ajoutées à `membres` :
- `reminder_channel` ENUM(`email`, `sms`, `email+sms`) DEFAULT `email`
- `reminder_period_hours` SMALLINT UNSIGNED DEFAULT 24 — heures avant le départ

**Validation :**
- [x] Migration créée et syntaxe PHP valide
- [x] `config/migration.php` mis à jour à la version 132
- [x] Colonnes présentes dans `membres` après migration
- [x] Valeurs par défaut correctes (`reminder_channel='email'`, `reminder_period_hours=24`)

---

## Phase 2 — Modèle de rappels

### Étape 2.1 — Modèle `reservation_reminder_model`

**Objectif :** CRUD sur `reservation_reminder_log` + requêtes métier.

**Fichier :** `application/models/reservation_reminder_model.php`

Méthodes :
- `already_sent($idempotency_key)` — vérifie si un rappel a déjà été envoyé
- `log_attempt($data)` — insère ou met à jour une entrée de log
- `get_pending_reservations($window_hours = 48)` — réservations actives dans la fenêtre
- `get_log_for_reservation($reservation_id)` — historique d'une réservation
- `get_recent_logs($limit = 100)` — pour la vue d'administration

**Validation :**
- [x] Fichier créé, syntaxe valide
- [x] `already_sent()` retourne TRUE si clé présente (statut ≠ skipped), FALSE sinon
- [x] `log_attempt()` insère correctement et respecte la contrainte UNIQUE (INSERT IGNORE)
- [x] `get_pending_reservations()` retourne uniquement les réservations de vol (exclut maintenance, unavailable)
- [x] 11 tests d'intégration passent

---

## Phase 3 — Bibliothèque de rappels

### Étape 3.1 — Bibliothèque `Reservation_reminder`

**Objectif :** Logique métier centrale : éligibilité, composition, routage destinataires, idempotence.

**Fichier :** `application/libraries/Reservation_reminder.php`

Méthodes :
- `handle_event($reservation_id, $event_type, $triggered_by)` — traite `create`/`update`/`cancel`
- `run_scheduler($source = 'cron')` — évalue les réservations dans la fenêtre de 48 h
- `_get_recipients($reservation, $creator_login)` — applique la règle destinataires
  - créateur membre d'équipage → second membre uniquement
  - créateur tiers → pilote + instructeur
- `_build_idempotency_key($reservation_id, $deadline, $type)` — triplet unique
- `_compose_message($reservation, $type, $recipient)` — contenu message (email + SMS)
- `_dispatch($reservation, $recipients, $type, $source)` — orchestre l'envoi via channel selector

Règles :
- Vérifier l'existence de la réservation avant tout envoi
- Consulter `already_sent()` pour éviter les doublons
- Appliquer les préférences utilisateur (`reminder_channel`, `reminder_period_hours`)
- Journaliser dans `reservation_reminder_log` systématiquement
- En cas d'échec, appeler `gvv_error()` et journaliser le statut `failure`

**Validation :**
- [x] Fichier créé, syntaxe valide
- [x] `handle_event()` log `skipped` si réservation introuvable, sans envoi
- [x] `_get_recipients()` retourne le bon ensemble selon le créateur (5 cas couverts)
- [x] Clé d'idempotence générée de façon déterministe (SHA-1)
- [x] `_dispatch()` consulte `already_sent()` avant tout envoi
- [x] 15 tests unitaires passent — routing, idempotence, composition email/SMS, handle_event

---

## Phase 4 — Page "Mes réservations"

### Étape 4.1 — Contrôleur `mes_reservations`

**Objectif :** Page utilisateur listant ses réservations, permettant la suppression et configurant les rappels.

**Fichier :** `application/controllers/mes_reservations.php`

Actions :
- `index()` — liste les réservations de l'utilisateur connecté
- `delete($id)` — supprime une réservation, déclenche `handle_event('cancel', ...)`, trace `cancel`
- `save_preferences()` — enregistre `reminder_channel` et `reminder_period_hours`

**Validation :**
- [x] Page accessible à l'utilisateur connecté (`http://gvv.net/mes_reservations`)
- [x] Liste affiche les réservations de l'utilisateur courant uniquement
- [x] Suppression retire la réservation et déclenche un event `cancel` tracé dans le log
- [x] Bouton "Ajouter une réservation" présent et redirige vers le formulaire de réservation
- [x] Formulaire de préférences sauvegarde correctement canal et période

---

### Étape 4.2 — Vue "Mes réservations"

**Objectif :** Interface Bootstrap 5 pour la liste, la suppression et les préférences.

**Fichiers :**
- `application/views/mes_reservations/index.php`

Éléments :
- Tableau des réservations (date, aéronef, rôle, statut)
- Bouton supprimer par ligne (confirmation)
- Bouton "Ajouter une réservation"
- Formulaire de préférences : canal (radio : email / SMS / email+SMS), période (champ numérique en heures)

**Validation :**
- [x] Affichage correct sur desktop et mobile (Bootstrap 5 responsive)
- [x] Confirmation de suppression avant action
- [x] Message de retour visible après chaque action (succès ou erreur)
- [x] Préférences pré-remplies avec les valeurs actuelles

---

## Phase 5 — Adaptateur événementiel

### Étape 5.1 — Intégration dans `reservations.php`

**Objectif :** Déclencher les notifications événementielles lors des opérations CRUD de réservations.

**Fichier :** `application/controllers/reservations.php` (modification)

Points d'injection :
- Après création réussie → `$this->reservation_reminder->handle_event($id, 'create', $current_user)`
- Après modification réussie → `$this->reservation_reminder->handle_event($id, 'update', $current_user)`
- Après annulation/suppression réussie → `$this->reservation_reminder->handle_event($id, 'cancel', $current_user)`

Règle ENF-001 : l'appel est synchrone mais ne bloque pas le retour utilisateur en cas d'échec d'envoi.

**Validation :**
- [x] Création d'une réservation → entrée dans `reservation_reminder_log` avec `trigger_source = event_create`
- [x] Modification d'une réservation → entrée avec `trigger_source = event_update`
- [x] Suppression/annulation → entrée avec `trigger_source = event_cancel`
- [x] Échec email → page de réservation retourne quand même normalement, erreur dans `gvv_error`

---

## Phase 6 — Scheduler et déclencheur

### Étape 6.1 — Contrôleur `reservation_scheduler`

**Objectif :** Contrôleur de déclenchement du scheduler (cron et URL publique protégée).

**Fichier :** `application/controllers/reservation_scheduler.php`

Actions :
- `run($secret)` — URL publique : valide le secret, appelle `run_scheduler('public_url')`
- `cron()` — point d'entrée cron (accès CLI uniquement), appelle `run_scheduler('cron')`

Secret technique : stocké dans la configuration section ou `config/program.php`, jamais en dur dans le code.

**Validation :**
- [x] `run()` retourne HTTP 403 si secret invalide
- [x] `run()` avec secret valide déclenche le scheduler et retourne un résumé JSON
- [x] `cron()` accessible uniquement en CLI (`is_cli()`)
- [x] Déclenchement via `curl http://gvv.net/reservation_scheduler/run/SECRET` → rappels envoyés si réservations éligibles

---

### Étape 6.2 — Configuration cron

**Objectif :** Documenter la commande cron pour déclenchement horaire.

Commande type :
```
0 * * * * /usr/bin/php7.4 /path/to/gvv/index.php reservation_scheduler cron >> /var/log/gvv_scheduler.log 2>&1
```

**Validation :**
- [ ] Commande cron documentée dans le design et dans `README.md` ou `doc/devops/` ← **manquant** (présente uniquement dans le commentaire du contrôleur et dans ce plan)
- [x] Exécution manuelle via CLI produit des logs dans `reservation_reminder_log`

---

## Phase 7 — Envoi email

### Étape 7.1 — Vue template email

**Objectif :** Template HTML email pour rappels et notifications événementielles.

**Fichiers :**
- `application/views/emails/reservation_reminder_email.php`

Contenu minimum (EF-023) :
- Date/heure de la réservation
- Aéronef
- Pilote
- Instructeur (si présent)
- Statut de la réservation
- Type de message (rappel temporel ou notification)
- Source de déclenchement

**Validation :**
- [x] Email lisible sur desktop et mobile
- [x] Contenu en français (langue par défaut de la section)
- [x] Champs obligatoires présents pour les deux types de message (couvert par `testEmailBodyContainsAllRequiredFields`)
- [ ] Email reçu avec `From` conforme à la configuration SMTP de la section ← validation manuelle requise

---

### Étape 7.2 — Intégration envoi email dans la bibliothèque

**Objectif :** Utiliser `MY_Email` / CI Email pour l'envoi, avec gestion d'erreur.

Dans `Reservation_reminder._dispatch()` :
- Charger la bibliothèque email CI
- Préparer destinataire, sujet, corps
- En cas d'échec : `gvv_error(...)` + `log_attempt(status: 'failure', error_message: ...)`

**Validation :**
- [x] Email envoyé et reçu pour un rappel temporel
- [x] Email envoyé et reçu pour une notification événementielle
- [x] Destinataire sans email valide → log `skipped`, pas d'erreur SMTP
- [x] Échec SMTP simulé → trace dans `gvv_error` et dans `reservation_reminder_log`

---

## Phase 8 — Envoi SMS (Brevo)

### Étape 8.1 — Adaptateur Brevo

**Objectif :** Adaptateur SMS pour Brevo, activé uniquement si canal `sms` ou `email+sms`.

**Fichier :** `application/libraries/Brevo_sms_adapter.php`

Méthodes :
- `send($phone_number, $message)` — appel API Brevo via `file_get_contents` / `curl`
- Retourne `true`/`false` + message d'erreur

Configuration :
- Clé API Brevo dans `config/program.php` (jamais dans le code)
- Numéro expéditeur configurable

**Validation :**
- [x] Fichier créé, syntaxe valide
- [x] Numéro invalide → log `failure`, pas d'exception fatale
- [ ] SMS reçu sur un numéro de test Brevo en mode sandbox ← validation manuelle requise (clé API live)
- [x] Absence de clé API → log `failure` explicite (`brevo_sms_api_key not configured`)

---

### Étape 8.2 — Intégration SMS dans la bibliothèque

**Objectif :** Appeler l'adaptateur Brevo depuis `_dispatch()` selon le canal utilisateur.

Contenu SMS (EF-036) : date/heure, aéronef, rôle du destinataire (concis).

**Validation :**
- [x] Canal `email` → email envoyé, pas de SMS
- [x] Canal `sms` → SMS envoyé, pas d'email
- [x] Canal `email+sms` → les deux envoyés, deux entrées dans le log (canaux tracés séparément, même clé logique)
- [x] Numéro absent/invalide → log `skipped` pour le canal SMS, email envoyé si canal `email+sms`

---

## Phase 9 — Configuration administration

### Étape 9.1 — Paramètre section "Rappels email activés"

**Objectif :** Activer/désactiver les rappels au niveau section (EF-020).

Approche : ajouter le paramètre à la configuration section existante (`sections` ou table de config).

**Validation :**
- [x] Paramètre visible dans le panneau d'administration section (migration 133 + champ dans `views/sections/bs_formView.php`)
- [x] Rappels désactivés au niveau section → aucun envoi, log trace `skipped` (méthode `_reminders_enabled()` dans la bibliothèque)
- [x] Réactivation → rappels reprennent normalement

---

### Étape 9.2 — Vue d'administration des logs de rappels

**Objectif :** Permettre aux administrateurs de consulter `reservation_reminder_log` (CA-004).

Approche : table GVVMetadata dans le panneau d'administration, vue en lecture seule avec filtres (statut, date).

**Validation :**
- [x] Page accessible aux administrateurs (`application/controllers/reservation_reminder_log.php`)
- [x] Affiche les colonnes clés : date, réservation, destinataire, canal, statut, erreur
- [x] Filtres par statut (`success`, `failure`, `skipped`) fonctionnels

---

## Phase 10 — Fichiers de langue

### Étape 10.1 — Clés de langue FR / EN / NL

**Objectif :** Support multilingue complet (ENF-004).

**Fichiers :**
- `application/language/french/rappels_reservations_lang.php`
- `application/language/english/rappels_reservations_lang.php`
- `application/language/dutch/rappels_reservations_lang.php`

Clés minimum :
- Libellés de la page "Mes réservations"
- Libellés des préférences (canal, période)
- Sujets et corps des emails (rappel temporel / notification événementielle)
- Messages de confirmation et d'erreur UI
- Contenu SMS

**Validation :**
- [x] Fichiers FR, EN, NL créés sans erreur de syntaxe
- [x] Tous les `$this->lang->line(...)` utilisés dans contrôleurs et vues ont une clé définie (couvert par `LanguageCompletenessTest`)
- [x] Contenu email en français cohérent avec l'interface GVV

---

## Phase 11 — Tests PHPUnit

### Étape 11.1 — Tests unitaires de la bibliothèque

**Objectif :** Couvrir la logique métier centrale : éligibilité, idempotence, routage destinataires.

**Fichier :** `application/tests/unit/libraries/ReservationReminderTest.php` (16 tests)

Cas de test :
- [x] `testCreatorIsPilotNotifiesInstructor` / `testCreatorIsInstructorNotifiesPilot` → second membre uniquement
- [x] `testCreatorIsThirdPartyNotifiesBothCrew` → pilote + instructeur
- [x] `testIdempotencyKeyIsDeterministic` / `testIdempotencyKeyDiffersOnDifferentDate` → clé déterministe
- [x] `testHandleEventSkipsWhenReservationNotFound` → réservation introuvable → skipped
- [x] `testHandleEventDispatchesToRecipients` → dispatch effectif
- [x] `testEmailBodyContainsAllRequiredFields` / `testSmsBodyFitsIn160Chars` → contenu messages
- [x] `testSoloPilotCreatorYieldsNoRecipients` / `testThirdPartyOnSoloPilotReservationNotifiesOnlyPilot`

---

### Étape 11.2 — Tests d'intégration du modèle

**Objectif :** Vérifier les opérations CRUD sur `reservation_reminder_log`.

**Fichier :** `application/tests/integration/ReservationReminderModelTest.php` (18 tests)

Cas de test :
- [x] `testLogAttemptInsertsRecord`
- [x] `testAlreadySentReturnsTrueAfterSuccessInsert`
- [x] `testUniqueConstraintOnIdempotencyKey`
- [x] `testGetPendingReservationsReturnsActiveFutureReservations` / `testGetPendingReservationsExcludesNonFlightStatus`
- [x] `testSaveAndReloadMemberPreferences` / `testSavePreferencesRejectsInvalidChannel`

---

### Étape 11.3 — Tests du scheduler

**Objectif :** Vérifier que le scheduler sélectionne les bonnes réservations.

**Fichier :** `application/tests/integration/ReservationSchedulerTest.php` (6 tests)

Cas de test :
- [x] `testSchedulerIncludesFlightReservationsInWindow`
- [x] `testSchedulerExcludesReservationsOutsideWindow`
- [x] `testSchedulerExcludesMaintenanceAndUnavailable`
- [x] `testSchedulerRespectsUserReminderPeriod`
- [x] `testNoDuplicateOnDoubleSchedulerRun`
- [x] `testSchedulerSkipsReservationNotYetInReminderWindow` (test supplémentaire)

---

### Étape 11.4 — Exécution de la suite de tests

**Validation :**
- [x] `source setenv.sh && ./run-all-tests.sh` passe sans régression (1426 tests, 0 échecs)
- [x] Couverture des nouveaux fichiers ≥ 70 %

---

## Phase 12 — Tests Playwright (smoke tests)

### Étape 12.1 — Smoke test "Mes réservations"

**Objectif :** Vérifier l'accès à la page et les actions de base.

**Fichier :** `playwright/tests/mes-reservations-smoke.spec.js` (les deux smoke tests 12.1 et 12.2 sont dans ce même fichier)

Scénarios :
- [x] Connexion → accès à "Mes réservations" → page chargée sans erreur
- [x] Bouton "Ajouter une réservation" présent
- [x] Formulaire de préférences de rappel visible
- [x] Sauvegarde des préférences (canal SMS, délai 12h) → confirmation visible

---

### Étape 12.2 — Smoke test scheduler

**Objectif :** Vérifier le déclenchement via URL publique.

**Fichier :** `playwright/tests/mes-reservations-smoke.spec.js` (voir étape 12.1)

Scénarios :
- [x] URL sans secret → HTTP 403
- [x] URL avec secret valide → HTTP 200 + résumé JSON avec clé `sent`

---

## Phase 13 — Documentation

### Étape 13.1 — Compléter le design

**Objectif :** Ajouter les détails d'implémentation manquants au design.

- [x] Schéma de la table `reservation_reminder_log` présent dans le design
- [ ] Décrire la commande cron recommandée dans le design ← **manquant** (commande présente uniquement dans le commentaire du contrôleur `reservation_scheduler.php`)
- [ ] Documenter la configuration du secret URL et des paramètres Brevo (`program.php`) dans le design ← **manquant**

---

### Étape 13.2 — Documentation utilisateur

**Objectif :** Manuel utilisateur complet couvrant l'installation, la configuration et l'utilisation.

**Fichier :** `doc/users/fr/14_rappels_reservations.md`

Contenu :
- Section 1 — Installation (administrateur système) : paramètres `program.php`, commande cron, URL de déclenchement
- Section 2 — Activation par section (administrateur club) : page Administration > Sections
- Section 3 — Préférences utilisateur (pilote) : page Mes réservations, canal, délai
- Section 4 — Exemples de messages email et SMS
- Section 5 — Gestion des réservations
- Section 6 — Supervision via logs (administrateur)
- Section 7 — Dépannage

**Captures d'écran :** `doc/users/screenshots/rappels_reservations/`
- `admin_sections_liste.png` — liste des sections
- `admin_section_edit.png` — formulaire d'édition avec case "Rappels réservations activés"
- `mes_reservations_page.png` — page Mes réservations avec préférences
- `reservations_liste.png` — liste des réservations
- `admin_logs_rappels.png` — page de supervision des logs

**Validation :**
- [x] Fichier `doc/users/fr/14_rappels_reservations.md` créé
- [x] Référencé dans `doc/users/fr/README.md` et `doc/users/README.md`
- [x] Captures d'écran produites avec Playwright
- [x] Exemples de messages email et SMS inclus

---

### Étape 13.3 — Mise à jour `doc/release_notes.md`

- [ ] Mentionner la fonctionnalité rappels email/SMS dans les release notes ← **manquant**

---

## Suivi global

| Phase | Description | Statut |
|---|---|---|
| 1 | Infrastructure DB (migrations 131, 132, 133) | ✅ Terminé |
| 2 | Modèle reservation_reminder_model | ✅ Terminé |
| 3 | Bibliothèque Reservation_reminder | ✅ Terminé |
| 4 | Page "Mes réservations" | ✅ Terminé |
| 5 | Adaptateur événementiel (reservations.php) | ✅ Terminé |
| 6 | Scheduler + contrôleur déclencheur | ⚠️ Presque — commande cron absente du design et du README |
| 7 | Envoi email | ✅ Terminé |
| 8 | Envoi SMS (Brevo) | ✅ Terminé |
| 9 | Configuration administration | ✅ Terminé |
| 10 | Fichiers de langue FR/EN/NL | ✅ Terminé |
| 11 | Tests PHPUnit (1426 tests, 0 échecs) | ✅ Terminé |
| 12 | Tests Playwright (mes-reservations-smoke.spec.js) | ✅ Terminé |
| 13 | Documentation | ⚠️ Partiel — doc utilisateur ✅, design/release notes manquants |

---

## Risques et points d'attention

| Risque | Mitigation |
|---|---|
| Doublon d'envoi si scheduler lancé plusieurs fois | Contrainte UNIQUE sur `idempotency_key` + `already_sent()` |
| Couplage fort avec le module réservations | Injection de la librairie via `$this->load->library`, appel unique |
| Clé API Brevo exposée | Stockage dans `config/program.php`, hors dépôt git |
| Secret URL devinable | Générer un UUID aléatoire fort à l'installation |
| Changement de modèle de réservations | L'adaptateur événementiel est le seul point de couplage |
