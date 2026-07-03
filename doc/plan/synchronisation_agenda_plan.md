# Plan de développement — Synchronisation agenda

## Contexte

Les membres GVV souhaitent que leurs réservations d'appareils apparaissent automatiquement dans leur agenda personnel (Google Calendar, Apple Calendar, Outlook…). Ce plan couvre deux options complémentaires exposées à l'utilisateur dans la page "Mes réservations".

**Référence** : brainstorming du 2026-07-03, analyse de l'approche OpenFLyers.

---

## Options et exclusivité

Les deux options sont **cumulables, non exclusives** :

| | Option 1 — Flux iCal | Option 3 — Google Calendar OAuth2 |
|--|--|--|
| Agendas compatibles | Tous (Google, Apple, Outlook…) | Google Calendar uniquement |
| Mise à jour | Polling ~8-24h | Instantanée |
| Complexité | Faible | Élevée |
| Token stocké | Aléatoire en base | Refresh token OAuth2 en base |
| Infrastructure Google | Aucune | `client_id`/`client_secret` existants |

Un utilisateur peut activer les deux simultanément (ex. : iCal pour Outlook professionnel + Google Calendar pour usage personnel). L'interface proposera les deux sections de manière indépendante dans "Mes réservations".

**Ordre d'implémentation** : Option 1 en premier (base, 3 jours), Option 3 en second (enrichissement, 7-10 jours).

---

## Option 1 — Flux iCal

### Principe

Un endpoint public génère un fichier `.ics` dynamique, protégé par un token personnel opaque. L'utilisateur abonne ce lien dans n'importe quel agenda. Google Calendar interroge l'URL périodiquement.

### 1.1 Migration base de données (migration 138)

Ajouter à la table `membres` :

```sql
ALTER TABLE membres ADD COLUMN ical_token VARCHAR(64) NULL DEFAULT NULL;
```

Fichier : `application/migrations/138_ical_token.php`  
Mise à jour : `application/config/migration.php` → version 138

### 1.2 Contrôleur `Ical.php`

Nouveau fichier `application/controllers/Ical.php` :

- `mes_reservations($token)` : valide le token, récupère les réservations futures du membre, retourne une réponse `Content-Type: text/calendar` au format RFC 5545
- Aucune session requise (le token est le seul mécanisme d'authentification)
- Format de sortie :

```
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//GVV//Mes Reservations//FR
X-WR-CALNAME:Mes réservations GVV
X-WR-TIMEZONE:Europe/Paris
BEGIN:VEVENT
UID:reservation-{id}@gvv.net
DTSTAMP:{now}
DTSTART:{start}
DTEND:{end}
SUMMARY:Réservation {immat} — {rôle}
DESCRIPTION:{notes}
END:VEVENT
...
END:VCALENDAR
```

- Inclure les réservations des 30 prochains jours (pilote et instructeur)
- Inclure les réservations passées des 7 derniers jours (pour éviter les disparitions immédiates d'agenda)
- Retourner HTTP 404 si le token est inconnu

### 1.3 Gestion du token dans `Mes_reservations`

Ajouter dans `Mes_reservations.php` :

- `generate_ical_token()` : génère un token aléatoire (`bin2hex(random_bytes(32))`), le sauvegarde en base, redirige vers `mes_reservations`
- `revoke_ical_token()` : met le token à NULL en base, redirige vers `mes_reservations`

Modifier `index()` pour passer `$ical_token` à la vue.

### 1.4 Vue `mes_reservations/index.php`

Nouvelle section "Abonnement agenda (iCal)" sous la liste des réservations :

**Si aucun token :**
```
[ Générer mon lien d'abonnement ]
```

**Si token présent :**
```
Votre lien d'abonnement :
[ https://gvv.net/ical/mes_reservations/xxxx ]  [Copier]  [Révoquer]

Comment abonner dans Google Calendar :
  1. Copiez le lien ci-dessus
  2. Ouvrez Google Calendar → Autres agendas (colonne gauche) → [+] → "À partir de l'URL"
  3. Collez le lien et cliquez "Ajouter un agenda"
  4. Un agenda "Mes réservations GVV" apparaît dans quelques secondes
  ℹ️  Google Calendar met à jour cet agenda toutes les 8 à 24 heures.

Comment abonner dans Apple Calendar (iPhone/Mac) :
  1. Copiez le lien ci-dessus
  2. Sur iPhone : Réglages → Apps → Calendrier → Comptes → Ajouter un compte → Autre → "Ajouter un cal. avec abonnement"
  3. Collez le lien et validez

Comment abonner dans Outlook :
  1. Copiez le lien ci-dessus
  2. Outlook → Calendrier → Ajouter → À partir d'Internet → Collez le lien
```

Bouton [Copier] via JavaScript (`navigator.clipboard.writeText`).

### 1.5 Chaînes de traduction

Ajouter dans `application/language/{french,english,dutch}/mes_reservations_lang.php` :
- `mes_reservations_ical_title`
- `mes_reservations_ical_generate`
- `mes_reservations_ical_revoke`
- `mes_reservations_ical_url_label`
- `mes_reservations_ical_copy`
- `mes_reservations_ical_instructions_*`

### 1.6 Tests Option 1

**PHPUnit** (`application/tests/integration/IcalTest.php`) :
- Génération du token (unicité, longueur 64 chars)
- Endpoint iCal retourne HTTP 200 avec `Content-Type: text/calendar`
- Token invalide → HTTP 404
- Format VCALENDAR valide (présence `BEGIN:VCALENDAR`, `BEGIN:VEVENT`, `END:VCALENDAR`)
- Révocation → endpoint retourne 404

**Playwright** (`playwright/tests/ical_sync.spec.js`) :
- Connexion en tant qu'utilisateur test avec réservation future
- Section "Abonnement agenda" absente si pas de token
- Clic "Générer" → URL apparaît
- Clic "Révoquer" → URL disparaît
- Accès direct à l'URL iCal → contenu valide
- Smoke test : copier l'URL (vérifier que le bouton est présent)

**Durée estimée Option 1** : 3 jours

---

## Option 3 — Google Calendar OAuth2 par utilisateur

### Principe

Chaque utilisateur connecte son compte Google via OAuth2. GVV crée/modifie/supprime les événements directement dans son Google Calendar à chaque changement de réservation. Utilise la librairie `google-api-php-client` déjà présente et les credentials `client_id`/`client_secret` de `application/config/google.php`.

### 3.1 Prérequis Google Cloud Console

- Ajouter `https://gvv.net/mes_agenda_google/callback` aux URI de redirection autorisées dans la Google Cloud Console (à faire manuellement, hors code)
- Scope requis : `https://www.googleapis.com/auth/calendar.events`
- Activer `access_type=offline` et `prompt=consent` pour obtenir un refresh token

> ⚠️ L'application Google doit être vérifiée par Google si elle compte plus de 100 utilisateurs extérieurs. En usage interne associatif (<100 membres), la vérification n'est pas obligatoire.

### 3.2 Migration base de données (migration 139)

Ajouter à la table `membres` :

```sql
ALTER TABLE membres
  ADD COLUMN google_calendar_token TEXT NULL DEFAULT NULL,
  ADD COLUMN google_calendar_id VARCHAR(255) NULL DEFAULT NULL;
```

- `google_calendar_token` : JSON du refresh token retourné par Google
- `google_calendar_id` : ID de l'agenda choisi par l'utilisateur (ex. `primary` ou un ID opaque)

Fichier : `application/migrations/139_google_calendar_user_token.php`  
Mise à jour : `application/config/migration.php` → version 139

### 3.3 Librairie `GoogleCalUser.php`

Nouveau fichier `application/libraries/GoogleCalUser.php` (distinct de `GoogleCal.php` qui gère le calendrier club) :

Méthodes :
- `get_auth_url($login)` : retourne l'URL d'autorisation Google pour l'utilisateur
- `handle_callback($code, $login)` : échange le code contre un refresh token, le sauvegarde en base
- `get_calendar_list($login)` : retourne la liste des agendas de l'utilisateur (nom + ID)
- `save_selected_calendar($login, $calendar_id)` : sauvegarde l'agenda choisi
- `push_reservation($login, $reservation)` : crée ou met à jour un événement Google Calendar
- `delete_reservation($login, $reservation_id)` : supprime un événement Google Calendar
- `disconnect($login)` : efface le token et l'agenda sélectionné en base
- `_get_client($login)` : initialise `Google_Client` avec le refresh token de l'utilisateur

La librairie gère silencieusement les erreurs de token expiré (révocation Google) en effaçant le token et loggant l'erreur — l'utilisateur voit un message d'avertissement au prochain accès à `mes_reservations`.

### 3.4 Contrôleur `Mes_agenda_google.php`

Nouveau fichier `application/controllers/Mes_agenda_google.php` :

- `connect()` : redirige vers l'URL OAuth2 Google
- `callback()` : reçoit le code OAuth, appelle `GoogleCalUser::handle_callback()`, redirige vers `mes_reservations` avec message de succès, puis affiche le sélecteur d'agendas
- `select_calendar()` : POST — sauvegarde l'agenda choisi, redirige vers `mes_reservations`
- `disconnect()` : efface token + calendrier, redirige vers `mes_reservations`

### 3.5 Intégration dans les réservations

Modifier `application/controllers/reservations.php` pour appeler `GoogleCalUser` après chaque opération :

- Après création → `push_reservation($pilot_login, $reservation)` et `push_reservation($instructor_login, $reservation)` si instructeur
- Après modification → `push_reservation(...)` (l'API Google Calendar met à jour via `UID`)
- Après annulation/suppression → `delete_reservation(...)`

L'appel est non bloquant : une erreur Google ne doit jamais empêcher la réservation GVV. Logger l'erreur, continuer.

Idem dans `application/controllers/Mes_reservations.php` pour la suppression depuis la vue personnelle.

### 3.6 Vue `mes_reservations/index.php` — section Google Calendar

**État 1 : non connecté**
```
[ Se connecter avec Google ]
Synchronisez vos réservations GVV avec votre Google Calendar.
Dès qu'une réservation est créée ou modifiée, votre agenda Google
est mis à jour automatiquement.
```

**État 2 : connecté, pas d'agenda choisi**
```
✓ Compte Google connecté

Choisissez l'agenda où enregistrer vos réservations :
[ Liste déroulante des agendas Google ] [ Enregistrer ]

💡 Conseil : créez un agenda dédié "Réservations GVV" dans Google Calendar
   pour garder vos réservations séparées de vos autres événements.

[ Se déconnecter ]
```

**État 3 : connecté + agenda choisi**
```
✓ Synchronisation active → Agenda : "Réservations GVV"
Vos réservations se mettent à jour instantanément.

[ Changer d'agenda ] [ Se déconnecter ]

⚠️ Si vous révoquez l'accès depuis Google (myaccount.google.com),
   cliquez "Se déconnecter" ici pour éviter les erreurs.
```

**État 4 : token invalide détecté (révocation côté Google)**
```
⚠️ La connexion Google a été interrompue. Veuillez vous reconnecter
   pour reprendre la synchronisation.
[ Reconnecter ]
```

### 3.7 Chaînes de traduction

Ajouter dans les trois fichiers de langue :
- `mes_reservations_gcal_title`
- `mes_reservations_gcal_connect`
- `mes_reservations_gcal_connected`
- `mes_reservations_gcal_select_calendar`
- `mes_reservations_gcal_active`
- `mes_reservations_gcal_disconnect`
- `mes_reservations_gcal_token_invalid`
- `mes_reservations_gcal_error_push`

### 3.8 Tests Option 3

**PHPUnit** (`application/tests/integration/GoogleCalUserTest.php`) :
- `get_auth_url()` retourne une URL Google OAuth valide (contient `accounts.google.com`)
- `handle_callback()` avec code invalide → exception capturée, pas de token sauvegardé
- `push_reservation()` sans token → no-op silencieux (pas d'exception)
- `delete_reservation()` sans token → no-op silencieux
- `disconnect()` → token et calendar_id mis à NULL en base

> Les tests d'intégration réelle avec Google ne sont pas automatisés (dépendance externe). Les tests couvrent la logique sans appel réseau réel via des stubs.

**Playwright** (`playwright/tests/google_cal_sync.spec.js`) :
- Connexion utilisateur → section Google Calendar présente avec bouton "Se connecter"
- Smoke test : le bouton redirige vers une URL Google (vérifier que le redirect commence par `https://accounts.google.com`)
- État "token invalide" simulé en base → message d'avertissement affiché

> Le flux OAuth complet ne peut pas être testé en Playwright sans compte Google de test dédié. Le smoke test se limite à vérifier la présence des éléments UI et la URL de redirection.

**Durée estimée Option 3** : 7-10 jours

---

## Documentation utilisateur

### Emplacement

`doc/users/fr/synchronisation_agenda.md`

### Structure du document

```markdown
# Synchroniser vos réservations avec votre agenda

GVV peut envoyer vos réservations d'appareils directement dans votre 
agenda personnel. Deux méthodes sont disponibles, cumulables.

## Méthode 1 — Abonnement iCal (tous les agendas)
[Instructions détaillées Google Calendar, Apple Calendar, Outlook]

## Méthode 2 — Connexion Google Calendar (instantanée)
[Instructions détaillées avec captures d'écran]

## Questions fréquentes
- Mes réservations n'apparaissent pas dans Google Calendar
- J'ai révoqué l'accès depuis Google, que faire ?
- Puis-je utiliser les deux méthodes en même temps ?
- Comment supprimer la synchronisation ?
```

---

## Séquence d'implémentation

```
Semaine 1 — Option 1 (iCal)
  J1 : Migration 138 + contrôleur Ical.php + tests PHPUnit
  J2 : Section vue mes_reservations + traductions
  J3 : Tests Playwright + documentation utilisateur Option 1

Semaine 2-3 — Option 3 (Google Calendar)
  J4-5 : Migration 139 + librairie GoogleCalUser.php + tests PHPUnit
  J6   : Contrôleur Mes_agenda_google.php
  J7   : Intégration dans reservations.php et mes_reservations.php
  J8-9 : Section vue mes_reservations (3 états) + traductions
  J10  : Tests Playwright + documentation utilisateur Option 3
         + mise à jour Google Cloud Console
```

---

## Critères de succès

- [ ] Option 1 : un utilisateur peut copier son lien iCal et le voir apparaître dans Google Calendar en moins de 24h
- [ ] Option 1 : la révocation du token supprime l'accès immédiatement
- [ ] Option 3 : une réservation créée dans GVV apparaît dans Google Calendar en moins de 30 secondes
- [ ] Option 3 : une réservation supprimée disparaît de Google Calendar
- [ ] Option 3 : une erreur Google n'empêche jamais une opération GVV
- [ ] Les deux options sont indépendantes et cumulables
- [ ] La documentation utilisateur permet à un non-technicien de configurer la synchronisation sans assistance
- [ ] Smoke tests PHPUnit et Playwright passent sur les deux options
