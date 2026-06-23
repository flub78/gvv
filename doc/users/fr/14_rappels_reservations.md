# 14. Rappels de Réservations

## Vue d'ensemble

GVV peut envoyer automatiquement des **rappels par email ou SMS** avant chaque réservation d'aéronef, ainsi que des **notifications immédiates** lors de la création, modification ou annulation d'une réservation.

Chaque membre configure son propre canal (email, SMS ou les deux) et son délai de rappel depuis la page **Mes réservations**.

> **Prérequis** : Cette fonctionnalité requiert une configuration initiale par un administrateur système (voir section 1).

---

## 1. Installation et Configuration (Administrateur Système)

Cette section s'adresse à la personne qui gère le serveur.

### 1.1 Paramètres dans `program.php`

Éditez le fichier `application/config/program.php` et renseignez les trois paramètres suivants :

```php
// Secret protégeant l'URL de déclenchement manuel du scheduler
$config['reservation_scheduler_secret'] = 'CHANGE_ME_IN_PRODUCTION';

// Clé API Brevo pour l'envoi de SMS (laisser vide pour désactiver les SMS)
$config['brevo_sms_api_key'] = '';

// Nom de l'expéditeur SMS affiché sur le téléphone (11 caractères max)
$config['brevo_sms_sender']  = 'GVV';
```

**Étapes :**

1. Remplacez `CHANGE_ME_IN_PRODUCTION` par une chaîne aléatoire longue (ex. `openssl rand -hex 32`). Ce secret protège l'URL de déclenchement contre les appels non autorisés.
2. Pour activer les SMS, inscrivez votre clé API [Brevo](https://www.brevo.com) dans `brevo_sms_api_key`.  
   Si ce champ reste vide, les rappels SMS sont ignorés (aucune erreur).
3. Ajustez `brevo_sms_sender` au nom de votre club (ex. `MonClub`, 11 caractères alphanumériques max).

### 1.2 Configuration du cron (déclenchement horaire)

Le scheduler doit être lancé toutes les heures par le système. Ajoutez cette ligne à la crontab du serveur :

```bash
crontab -e
```

```
0 * * * * XDEBUG_MODE=off /usr/bin/php7.4 /chemin/vers/gvv/index.php reservation_scheduler cron >> /var/log/gvv_scheduler.log 2>&1
```

Remplacez `/chemin/vers/gvv/` par le chemin réel d'installation. Sur le serveur de développement :

```
0 * * * * XDEBUG_MODE=off /usr/bin/php7.4 /home/frederic/git/gvv/index.php reservation_scheduler cron >> /var/log/gvv_scheduler.log 2>&1
```

**Vérifier que le cron est actif :**

```bash
crontab -l | grep reservation_scheduler
```

**Tester manuellement :**

```bash
source setenv.sh
XDEBUG_MODE=off /usr/bin/php7.4 /chemin/vers/gvv/index.php reservation_scheduler cron
```

Résultat attendu : `reservation_scheduler cron: sent=N` (N ≥ 0, aucune erreur PHP).

### 1.3 Déclenchement via URL (sans cron)

Si vous ne pouvez pas configurer de cron, utilisez l'URL publique pour déclencher le scheduler depuis n'importe quel outil d'automatisation :

```
https://votre-domaine/reservation_scheduler/run/VOTRE_SECRET
```

Réponse JSON attendue :
```json
{"sent": 2, "source": "public_url"}
```

Un secret invalide retourne HTTP 403.

### 1.4 Vérification de l'activité dans les logs applicatifs

Toutes les actions du mécanisme de rappel sont tracées dans les logs CI de l'application (`application/logs/log-YYYY-MM-DD.php`) avec le préfixe `REMINDER`. Ce préfixe permet de filtrer exclusivement les entrées liées à la fonctionnalité.

**Afficher toutes les entrées rappel du jour :**

```bash
grep "REMINDER" application/logs/log-$(date +%Y-%m-%d).php
```

**Vérifier que le cron s'est bien exécuté :**

```bash
grep "REMINDER scheduler source=cron" application/logs/log-*.php
```

Résultat attendu (une ligne par exécution horaire) :

```
INFO  - 2026-06-22 21:00:01 --> GVV: REMINDER scheduler source=cron evaluated=3 sent=1
```

L'absence totale de ligne `source=cron` indique que le cron n'a jamais été déclenché sur cette machine.

**Interpréter les entrées clés :**

| Motif | Signification |
|---|---|
| `REMINDER scheduler source=cron evaluated=N sent=M` | Exécution horaire : N réservations évaluées, M rappels envoyés |
| `REMINDER scheduler source=public_url ...` | Déclenchement manuel via URL |
| `REMINDER run_scheduler source=... evaluated=N sent=M` | Détail interne du scheduler (même exécution) |
| `REMINDER handle_event reminders disabled for section X` | Section X n'a pas les rappels activés |
| `REMINDER no valid email for <login>` | Destinataire sans email valide |
| `REMINDER email failed: SMTP send failed to <email>` | Échec d'envoi email |
| `REMINDER SMS failed for <login>: ...` | Échec d'envoi SMS |

> **Bon état** : des lignes `source=cron` apparaissent régulièrement (toutes les heures quand la machine est active), avec `sent=0` ou plus. L'absence de `failure` confirme qu'aucun envoi n'a échoué.

---

## 2. Activation par Section (Administrateur Club)

Les rappels doivent être activés section par section depuis l'interface d'administration.

**Menu** : `Administration > Sections`  
**URL** : `/sections`

![Liste des sections](../../screenshots/rappels_reservations/admin_sections_liste.png)

Cliquez sur **Changer** sur la ligne de la section concernée.

![Formulaire d'édition de section](../../screenshots/rappels_reservations/admin_section_edit.png)

Cochez la case **Rappels réservations activés** et cliquez sur **Enregistrer**.

> **Note :** Si la case est décochée, aucun rappel n'est envoyé pour les réservations de cette section. Les tentatives sont enregistrées avec le statut `skipped` dans les logs.

---

## 3. Configuration des Préférences (Pilote)

Chaque pilote configure ses propres préférences depuis la page **Mes réservations**.

**Accès** : Menu principal → **Mes réservations**  
**URL** : `/mes_reservations`

![Page Mes réservations](../../screenshots/rappels_reservations/mes_reservations_page.png)

La page affiche :
- **Vos futures réservations** avec la date, l'aéronef, votre rôle et le type de réservation
- **Un bouton "Ajouter une réservation"** pour créer une nouvelle réservation
- **Le formulaire de préférences de rappel** en bas de page

### 3.1 Canal de notification

Choisissez comment vous souhaitez recevoir vos rappels :

| Option | Description |
|--------|-------------|
| **Email seulement** | Un email est envoyé à l'adresse renseignée dans votre fiche membre |
| **SMS seulement** | Un SMS est envoyé au numéro de mobile de votre fiche membre |
| **Email + SMS** | Les deux canaux sont utilisés simultanément |

> **Prérequis SMS** : Votre numéro de mobile doit être renseigné dans votre fiche membre (format `06XXXXXXXX`). Si le numéro est absent, le canal SMS est ignoré sans erreur.

### 3.2 Délai de rappel

Indiquez combien d'heures **avant le départ** vous souhaitez recevoir le rappel.

Exemples :
- `2` → rappel 2 heures avant la réservation
- `24` → rappel la veille (valeur par défaut)
- `48` → rappel deux jours avant

### 3.3 Enregistrer les préférences

Cliquez sur **Enregistrer mes préférences**. Un message de confirmation apparaît. Vos préférences sont appliquées à toutes vos réservations futures.

---

## 4. Types de Messages

### 4.1 Rappel temporel (email)

Envoyé automatiquement par le scheduler lorsque votre réservation entre dans la fenêtre de rappel définie dans vos préférences.

**Sujet :** `[GVV] Rappel réservation F-CGAA le 22/06/2026`

**Contenu :**

```
Bonjour Jean Dupont,

Vous avez une réservation prévue prochainement.

  Aéronef    : F-CGAA
  Date       : 22/06/2026 à 14:00
  Fin        : 22/06/2026 à 17:00
  Votre rôle : Pilote
  Type       : vol

Bonne préparation de vol !
```

### 4.2 Rappel temporel (SMS)

Format concis pour SMS (160 caractères max) :

```
Rappel vol F-CGAA le 22/06 14:00 – rôle: Pilote – GVV
```

### 4.3 Notification de création

Envoyée immédiatement lorsqu'un tiers crée une réservation vous concernant.

**Sujet :** `[GVV] Nouvelle réservation – F-CGAA le 22/06/2026`

> **Règle :** Si vous créez vous-même la réservation, vous ne recevez pas de notification de création — seul votre éventuel instructeur en est informé.

### 4.4 Notification d'annulation

Envoyée immédiatement lorsqu'une réservation est supprimée.

**Sujet :** `[GVV] Réservation annulée – F-CGAA le 22/06/2026`

---

## 5. Gestion des Réservations

### 5.1 Ajouter une réservation

Depuis **Mes réservations**, cliquez sur **Ajouter une réservation** pour accéder au formulaire de réservation d'aéronef.

![Liste des réservations](../../screenshots/rappels_reservations/reservations_liste.png)

### 5.2 Supprimer une réservation

Dans le tableau de vos réservations, cliquez sur **Supprimer** sur la ligne concernée. Une confirmation est demandée. La suppression déclenche automatiquement une notification d'annulation vers les autres membres de l'équipage.

---

## 6. Supervision des Rappels (Administrateur)

Les administrateurs peuvent consulter l'historique complet des rappels envoyés.

**Menu** : `Administration > Logs rappels de réservation`  
**URL** : `/reservation_reminder_log`

![Page de logs des rappels](../../screenshots/rappels_reservations/admin_logs_rappels.png)

### Colonnes affichées

| Colonne | Description |
|---------|-------------|
| **Date** | Horodatage de l'envoi |
| **Réservation** | Identifiant de la réservation concernée |
| **Pilote** | Destinataire du rappel |
| **Type** | `scheduled_reminder` (rappel temporel) ou `event_notification` (création/modif/annulation) |
| **Source** | `cron`, `public_url`, `event_create`, `event_update`, `event_cancel` |
| **Canal** | `email`, `sms` ou `email+sms` |
| **Statut** | `success` ✅, `failure` ❌, `skipped` ⏭ |
| **Erreur** | Message d'erreur en cas d'échec |

### Filtres disponibles

Utilisez les filtres **Succès / Échecs / Ignorés** pour affiner l'affichage.

### Statuts

| Statut | Signification |
|--------|---------------|
| `success` | Message envoyé avec succès |
| `failure` | Échec d'envoi (erreur SMTP ou Brevo) — voir colonne Erreur |
| `skipped` | Envoi ignoré : rappels désactivés pour la section, destinataire sans email/téléphone, ou rappel déjà envoyé |

> **Idempotence :** Le scheduler ne renvoie jamais deux fois le même rappel. Si vous le déclenchez plusieurs fois dans la même heure, les rappels déjà envoyés apparaissent en `skipped`.

---

## 7. Dépannage

### Aucun rappel reçu

1. Vérifier que les rappels sont activés pour la section (**Administration > Sections** → case **Rappels réservations activés**).
2. Vérifier que votre adresse email est renseignée dans votre fiche membre.
3. Vérifier que le cron est bien configuré : `crontab -l | grep reservation_scheduler`.
4. Consulter les logs (**Administration > Logs rappels**) et filtrer par votre login.

### SMS non reçu

1. Vérifier que la clé API Brevo est renseignée dans `program.php`.
2. Vérifier que votre numéro de mobile est au format `06XXXXXXXX` dans votre fiche membre.
3. Consulter les logs : une entrée `failure` avec message `brevo_sms_api_key not configured` indique une clé manquante.

### Doublon de rappels

Impossible par conception : la contrainte d'idempotence garantit qu'un rappel ne peut être envoyé qu'une seule fois par réservation et par fenêtre horaire.

### Déclencher manuellement le scheduler (test ou urgence)

```bash
curl -s https://votre-domaine/reservation_scheduler/run/VOTRE_SECRET
```

Réponse attendue : `{"sent": N, "source": "public_url"}`.
