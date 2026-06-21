# Plan d'implémentation — Rappels Email/SMS des Réservations

**Date :** 21 juin 2026  
**Statut :** En attente  
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
- [ ] Page accessible à l'utilisateur connecté (`http://gvv.net/mes_reservations`)
- [ ] Liste affiche les réservations de l'utilisateur courant uniquement
- [ ] Suppression retire la réservation et déclenche un event `cancel` tracé dans le log
- [ ] Bouton "Ajouter une réservation" présent et redirige vers le formulaire de réservation
- [ ] Formulaire de préférences sauvegarde correctement canal et période

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
- [ ] Affichage correct sur desktop et mobile (Bootstrap 5 responsive)
- [ ] Confirmation de suppression avant action
- [ ] Message de retour visible après chaque action (succès ou erreur)
- [ ] Préférences pré-remplies avec les valeurs actuelles

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
- [ ] Création d'une réservation → entrée dans `reservation_reminder_log` avec `trigger_source = event_create`
- [ ] Modification d'une réservation → entrée avec `trigger_source = event_update`
- [ ] Suppression/annulation → entrée avec `trigger_source = event_cancel`
- [ ] Échec email → page de réservation retourne quand même normalement, erreur dans `gvv_error`

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
- [ ] `run()` retourne HTTP 403 si secret invalide
- [ ] `run()` avec secret valide déclenche le scheduler et retourne un résumé JSON
- [ ] `cron()` accessible uniquement en CLI (`is_cli()`)
- [ ] Déclenchement via `curl http://gvv.net/reservation_scheduler/run/SECRET` → rappels envoyés si réservations éligibles

---

### Étape 6.2 — Configuration cron

**Objectif :** Documenter la commande cron pour déclenchement horaire.

Commande type :
```
0 * * * * /usr/bin/php7.4 /path/to/gvv/index.php reservation_scheduler cron >> /var/log/gvv_scheduler.log 2>&1
```

**Validation :**
- [ ] Commande cron documentée dans le design et dans `README.md` ou `doc/devops/`
- [ ] Exécution manuelle via CLI produit des logs dans `reservation_reminder_log`

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
- [ ] Email lisible sur desktop et mobile
- [ ] Contenu en français (langue par défaut de la section)
- [ ] Champs obligatoires présents pour les deux types de message
- [ ] Email reçu avec `From` conforme à la configuration SMTP de la section

---

### Étape 7.2 — Intégration envoi email dans la bibliothèque

**Objectif :** Utiliser `MY_Email` / CI Email pour l'envoi, avec gestion d'erreur.

Dans `Reservation_reminder._dispatch()` :
- Charger la bibliothèque email CI
- Préparer destinataire, sujet, corps
- En cas d'échec : `gvv_error(...)` + `log_attempt(status: 'failure', error_message: ...)`

**Validation :**
- [ ] Email envoyé et reçu pour un rappel temporel
- [ ] Email envoyé et reçu pour une notification événementielle
- [ ] Destinataire sans email valide → log `skipped`, pas d'erreur SMTP
- [ ] Échec SMTP simulé → trace dans `gvv_error` et dans `reservation_reminder_log`

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
- [ ] Fichier créé, syntaxe valide
- [ ] Numéro invalide → log `failure`, pas d'exception fatale
- [ ] SMS reçu sur un numéro de test Brevo en mode sandbox
- [ ] Absence de clé API → log `failure` explicite

---

### Étape 8.2 — Intégration SMS dans la bibliothèque

**Objectif :** Appeler l'adaptateur Brevo depuis `_dispatch()` selon le canal utilisateur.

Contenu SMS (EF-036) : date/heure, aéronef, rôle du destinataire (concis).

**Validation :**
- [ ] Canal `email` → email envoyé, pas de SMS
- [ ] Canal `sms` → SMS envoyé, pas d'email
- [ ] Canal `email+sms` → les deux envoyés, deux entrées dans le log (canaux tracés séparément, même clé logique)
- [ ] Numéro absent/invalide → log `skipped` pour le canal SMS, email envoyé si canal `email+sms`

---

## Phase 9 — Configuration administration

### Étape 9.1 — Paramètre section "Rappels email activés"

**Objectif :** Activer/désactiver les rappels au niveau section (EF-020).

Approche : ajouter le paramètre à la configuration section existante (`sections` ou table de config).

**Validation :**
- [ ] Paramètre visible dans le panneau d'administration section
- [ ] Rappels désactivés au niveau section → aucun envoi, log trace `skipped`
- [ ] Réactivation → rappels reprennent normalement

---

### Étape 9.2 — Vue d'administration des logs de rappels

**Objectif :** Permettre aux administrateurs de consulter `reservation_reminder_log` (CA-004).

Approche : table GVVMetadata dans le panneau d'administration, vue en lecture seule avec filtres (statut, date).

**Validation :**
- [ ] Page accessible aux administrateurs
- [ ] Affiche les colonnes clés : date, réservation, destinataire, canal, statut, erreur
- [ ] Filtres par statut (`success`, `failure`, `skipped`) fonctionnels

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
- [ ] Fichiers FR, EN, NL créés sans erreur de syntaxe
- [ ] Tous les `$this->lang->line(...)` utilisés dans contrôleurs et vues ont une clé définie
- [ ] Contenu email en français cohérent avec l'interface GVV

---

## Phase 11 — Tests PHPUnit

### Étape 11.1 — Tests unitaires de la bibliothèque

**Objectif :** Couvrir la logique métier centrale : éligibilité, idempotence, routage destinataires.

**Fichier :** `application/tests/unit/Reservation_reminderTest.php`

Cas de test :
- [ ] `test_recipients_creator_is_crew` → second membre uniquement
- [ ] `test_recipients_creator_is_third_party` → pilote + instructeur
- [ ] `test_idempotency_key_is_deterministic` → même entrée = même clé
- [ ] `test_no_send_if_already_sent` → `already_sent()` bloque le second envoi
- [ ] `test_no_send_if_reservation_cancelled` → réservation annulée → pas d'envoi
- [ ] `test_no_send_if_no_valid_email` → destinataire sans email → skipped

---

### Étape 11.2 — Tests d'intégration du modèle

**Objectif :** Vérifier les opérations CRUD sur `reservation_reminder_log`.

**Fichier :** `application/tests/integration/ReservationReminderModelTest.php`

Cas de test :
- [ ] `test_log_attempt_inserts_record`
- [ ] `test_already_sent_returns_true_after_insert`
- [ ] `test_unique_constraint_on_idempotency_key`
- [ ] `test_get_pending_reservations_returns_only_active`

---

### Étape 11.3 — Tests du scheduler

**Objectif :** Vérifier que le scheduler sélectionne les bonnes réservations.

**Fichier :** `application/tests/integration/ReservationSchedulerTest.php`

Cas de test :
- [ ] `test_scheduler_sends_reminder_for_reservation_in_window`
- [ ] `test_scheduler_skips_reservation_outside_window`
- [ ] `test_scheduler_skips_cancelled_reservation`
- [ ] `test_scheduler_respects_user_reminder_period`
- [ ] `test_no_duplicate_on_double_scheduler_run`

---

### Étape 11.4 — Exécution de la suite de tests

**Validation :**
- [ ] `source setenv.sh && ./run-all-tests.sh` passe sans régression
- [ ] Couverture des nouveaux fichiers ≥ 70 %

---

## Phase 12 — Tests Playwright (smoke tests)

### Étape 12.1 — Smoke test "Mes réservations"

**Objectif :** Vérifier l'accès à la page et les actions de base.

**Fichier :** `playwright/tests/mes_reservations.spec.js`

Scénarios :
- [ ] Connexion → accès à "Mes réservations" → page chargée sans erreur
- [ ] Liste affiche les réservations de l'utilisateur connecté
- [ ] Sauvegarde des préférences de rappel → confirmation visible
- [ ] Suppression d'une réservation → n'apparaît plus dans la liste

---

### Étape 12.2 — Smoke test scheduler

**Objectif :** Vérifier le déclenchement via URL publique.

**Fichier :** `playwright/tests/reservation_scheduler.spec.js`

Scénarios :
- [ ] URL sans secret → HTTP 403
- [ ] URL avec secret valide → HTTP 200 + résumé JSON

---

## Phase 13 — Documentation

### Étape 13.1 — Compléter le design

**Objectif :** Ajouter les détails d'implémentation manquants au design.

- [ ] Ajouter le schéma de la table `reservation_reminder_log` au design
- [ ] Décrire la commande cron recommandée
- [ ] Documenter la configuration du secret URL

---

### Étape 13.2 — Mise à jour `doc/release_notes.md`

- [ ] Mentionner la fonctionnalité rappels email/SMS dans les release notes

---

## Suivi global

| Phase | Description | Statut |
|---|---|---|
| 1 | Infrastructure DB (migrations) | ✅ Terminé |
| 2 | Modèle reservation_reminder_model | ✅ Terminé |
| 3 | Bibliothèque Reservation_reminder | ✅ Terminé |
| 4 | Page "Mes réservations" | ✅ Terminé |
| 5 | Adaptateur événementiel (reservations.php) | ✅ Terminé |
| 6 | Scheduler + contrôleur déclencheur | ✅ Terminé |
| 7 | Envoi email | ✅ Terminé |
| 8 | Envoi SMS (Brevo) | ✅ Terminé |
| 9 | Configuration administration | ✅ Terminé |
| 10 | Fichiers de langue FR/EN/NL | ✅ Terminé |
| 11 | Tests PHPUnit | ✅ Terminé |
| 12 | Tests Playwright | ✅ Terminé |
| 13 | Documentation | ✅ Terminé |

---

## Risques et points d'attention

| Risque | Mitigation |
|---|---|
| Doublon d'envoi si scheduler lancé plusieurs fois | Contrainte UNIQUE sur `idempotency_key` + `already_sent()` |
| Couplage fort avec le module réservations | Injection de la librairie via `$this->load->library`, appel unique |
| Clé API Brevo exposée | Stockage dans `config/program.php`, hors dépôt git |
| Secret URL devinable | Générer un UUID aléatoire fort à l'installation |
| Changement de modèle de réservations | L'adaptateur événementiel est le seul point de couplage |
