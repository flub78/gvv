# Plan de test manuel — Rappels de réservations

**Fonctionnalité :** Rappels email/SMS des réservations d'aéronefs  
**Date :** 22 juin 2026  
**Applicabilité :** Serveur de développement (`http://gvv.net/`) et serveur de production

---

## Prérequis

### Comptes de test

| Rôle | Login | Utilisation |
|------|-------|-------------|
| Administrateur club | `testadmin` | Configuration, logs |
| Pilote A | `fpeignot` | Pilote des réservations de test |
| Pilote B (instructeur) | *(second compte actif avec email et téléphone)* | Second membre d'équipage |

### Données nécessaires

- Au moins un aéronef actif avec immatriculation
- Les pilotes A et B ont une adresse email valide renseignée dans leur fiche membre
- Pour les tests SMS : le pilote B a un numéro de mobile renseigné (format `06XXXXXXXX`)

### Paramètres à vérifier avant les tests

```bash
# Vérifier la configuration SMS dans application/config/program.php
grep -E "brevo_sms|reservation_scheduler_secret" application/config/program.php
```

---

## Méthode d'accélération des tests

Pour éviter d'attendre 24 heures, les scénarios exploitent deux leviers :

1. **Réservation dans moins d'une heure** : crée une réservation démarrant dans 45 minutes. Avec `reminder_period_hours = 2`, la fenêtre d'envoi est déjà ouverte.
2. **Déclenchement manuel du scheduler** : l'URL publique `/reservation_scheduler/run/SECRET` ou la commande CLI déclenchent immédiatement l'envoi sans attendre le cron.

---

## TC-01 — Configuration du cron et exécution du scheduler

**Objectif :** Vérifier que le scheduler est configuré et peut être exécuté sans erreur.

### TC-01.1 — Vérification de la documentation cron

**Contexte :** La commande cron doit être documentée pour permettre l'installation.

**Mise en œuvre :**

1. Consulter le fichier `doc/design_notes/rappels_email_reservations_design.md` dans le dépôt.
2. Rechercher la commande cron documentée.
3. Sur le serveur, vérifier la crontab active :

```bash
crontab -l | grep reservation_scheduler
```

**Résultat attendu :** La commande cron est documentée avec le chemin complet vers PHP 7.4 et vers `index.php`.

- [ ] **PASS / FAIL** — La commande cron est documentée et syntaxiquement correcte.

---

### TC-01.2 — Exécution manuelle via CLI

**Contexte :** Le point d'entrée CLI `cron()` ne doit être accessible qu'en ligne de commande.

**Mise en œuvre :**

```bash
source setenv.sh
XDEBUG_MODE=off /usr/bin/php7.4 /home/frederic/git/gvv/index.php reservation_scheduler cron
```

**Résultat attendu :** La commande s'exécute et affiche `reservation_scheduler cron: sent=N` (N ≥ 0). Aucune erreur PHP fatale.

- [ ] **PASS / FAIL** — Exécution CLI sans erreur, sortie lisible.

---

### TC-01.3 — Déclenchement via URL publique (secret valide)

**Contexte :** L'URL `/reservation_scheduler/run/SECRET` doit retourner un JSON de résumé.

**Mise en œuvre :**

1. Récupérer le secret dans `application/config/program.php` :

```bash
grep reservation_scheduler_secret application/config/program.php
```

2. Déclencher le scheduler :

```bash
curl -s http://gvv.net/reservation_scheduler/run/VOTRE_SECRET
```

**Résultat attendu :** Réponse JSON `{"sent": N, "source": "public_url"}` avec HTTP 200.

- [ ] **PASS / FAIL** — Réponse JSON correcte, HTTP 200.

---

### TC-01.4 — Rejet d'un secret invalide

**Contexte :** Un secret erroné doit être rejeté avec HTTP 403.

**Mise en œuvre :**

```bash
curl -s -o /dev/null -w "%{http_code}" http://gvv.net/reservation_scheduler/run/MAUVAIS_SECRET
```

**Résultat attendu :** Code HTTP 403.

- [ ] **PASS / FAIL** — HTTP 403 retourné pour secret invalide.

---

### TC-01.5 — Traçabilité dans les logs application

**Contexte :** Chaque exécution du scheduler doit laisser une trace dans les logs GVV.

**Mise en œuvre :**

1. Exécuter le scheduler (CLI ou URL).
2. Consulter le log du jour :

```bash
grep "reservation_scheduler" application/logs/log-$(date +%Y-%m-%d).php | tail -5
```

**Résultat attendu :** Une ligne `reservation_scheduler::_execute source=... sent=...` est présente.

- [ ] **PASS / FAIL** — Trace présente dans les logs application.

---

## TC-02 — Configuration des paramètres SMS par un administrateur

**Objectif :** Vérifier qu'un administrateur peut configurer les paramètres Brevo SMS.

### TC-02.1 — Accès à la configuration

**Contexte :** Les paramètres SMS sont dans `application/config/program.php`. L'administrateur les modifie directement (pas d'interface graphique dédiée).

**Mise en œuvre :**

1. Se connecter en tant qu'administrateur système sur le serveur.
2. Éditer `application/config/program.php` et localiser la section SMS :

```php
$config['brevo_sms_api_key'] = '';     // Clé API Brevo
$config['brevo_sms_sender']  = 'GVV'; // Expéditeur (max 11 chars)
```

3. Renseigner une clé API Brevo de test ou de sandbox.
4. Sauvegarder le fichier.

**Résultat attendu :** Le fichier est modifiable, les paramètres sont clairement documentés dans le fichier.

- [ ] **PASS / FAIL** — Paramètres SMS localisables et éditables.

---

### TC-02.2 — Activation des rappels au niveau section

**Contexte :** Chaque section peut activer ou désactiver les rappels via l'interface d'administration.

**Mise en œuvre :**

1. Se connecter en tant qu'administrateur (`testadmin`).
2. Aller dans **Administration > Sections**.
3. Sélectionner une section et l'éditer.
4. Localiser le champ **Rappels de réservation activés** et le cocher.
5. Sauvegarder.

**Résultat attendu :** Le champ est présent dans le formulaire de section, la sauvegarde est confirmée par un message de succès.

- [ ] **PASS / FAIL** — Champ visible et sauvegarde confirmée.

---

### TC-02.3 — Désactivation des rappels pour une section

**Contexte :** Avec les rappels désactivés au niveau section, aucun envoi ne doit être tenté.

**Mise en œuvre :**

1. Décocher **Rappels de réservation activés** sur une section.
2. Sauvegarder.
3. Déclencher le scheduler via URL ou CLI.
4. Consulter les logs admin : **Administration > Logs rappels**.

**Résultat attendu :** Aucune entrée `success` n'apparaît pour les réservations de cette section. Les entrées éventuelles sont en statut `skipped`.

- [ ] **PASS / FAIL** — Rappels bloqués pour la section désactivée.

---

## TC-03 — Accès à la documentation sur GitHub

**Objectif :** Vérifier qu'un administrateur peut accéder à la documentation technique du projet.

### TC-03.1 — Documentation disponible sur GitHub

**Mise en œuvre :**

1. Ouvrir un navigateur.
2. Naviguer vers le dépôt GitHub du projet GVV.
3. Localiser le fichier `doc/design_notes/rappels_email_reservations_design.md`.
4. Vérifier la présence des sections : architecture, flux conceptuels, configuration cron, paramètres Brevo.

**Résultat attendu :** Le document est lisible sur GitHub avec le diagramme d'architecture embarqué.

- [ ] **PASS / FAIL** — Documentation accessible et complète sur GitHub.

---

### TC-03.2 — Accès aux logs de rappels via l'interface d'administration

**Contexte :** L'interface GVV offre une page de supervision des rappels aux administrateurs.

**Mise en œuvre :**

1. Se connecter en tant qu'administrateur (`testadmin`).
2. Naviguer vers **Administration > Logs rappels de réservation** (URL : `http://gvv.net/reservation_reminder_log`).
3. Vérifier que la page charge et affiche les colonnes : Date, Réservation, Pilote, Type, Source, Canal, Statut, Erreur.
4. Tester les filtres **Succès / Échecs / Ignorés**.

**Résultat attendu :** Page accessible uniquement aux administrateurs, filtres fonctionnels.

- [ ] **PASS / FAIL** — Page de logs accessible et filtres opérationnels.

---

## TC-04 — Configuration des préférences de rappel par un utilisateur

**Objectif :** Vérifier qu'un utilisateur connecté peut configurer son canal et son délai de rappel.

### TC-04.1 — Accès à la page "Mes réservations"

**Mise en œuvre :**

1. Se connecter avec le compte pilote (`fpeignot`).
2. Naviguer vers **Mes réservations** (URL : `http://gvv.net/mes_reservations`).

**Résultat attendu :** La page charge et affiche les réservations futures de l'utilisateur ainsi que le formulaire de préférences.

- [ ] **PASS / FAIL** — Page accessible et formulaire de préférences visible.

---

### TC-04.2 — Modification des préférences : canal email uniquement

**Mise en œuvre :**

1. Sur la page **Mes réservations**, localiser le formulaire de préférences.
2. Sélectionner le canal **Email uniquement**.
3. Définir le délai à **2 heures** avant la réservation.
4. Cliquer sur **Enregistrer mes préférences**.

**Résultat attendu :** Un message de confirmation apparaît. Les valeurs sélectionnées sont pré-remplies au rechargement de la page.

- [ ] **PASS / FAIL** — Préférences sauvegardées, confirmation visible, valeurs persistées.

---

### TC-04.3 — Modification des préférences : canal SMS

**Prérequis :** Le pilote a un numéro de mobile renseigné dans sa fiche membre.

**Mise en œuvre :**

1. Sur la page **Mes réservations**, sélectionner le canal **SMS**.
2. Définir le délai à **2 heures**.
3. Sauvegarder.

**Résultat attendu :** Confirmation de sauvegarde, canal SMS sélectionné au rechargement.

- [ ] **PASS / FAIL** — Canal SMS sauvegardé correctement.

---

### TC-04.4 — Modification des préférences : canal Email + SMS

**Mise en œuvre :**

1. Sélectionner le canal **Email + SMS**.
2. Définir le délai à **2 heures**.
3. Sauvegarder et recharger la page.

**Résultat attendu :** Canal `email+sms` persisté.

- [ ] **PASS / FAIL** — Canal Email + SMS sauvegardé.

---

### TC-04.5 — Accès refusé sans connexion

**Mise en œuvre :**

1. Se déconnecter.
2. Accéder directement à `http://gvv.net/mes_reservations`.

**Résultat attendu :** Redirection vers la page de connexion.

- [ ] **PASS / FAIL** — Accès non authentifié redirigé vers login.

---

## SC-A — Scénario : rappels email et SMS fonctionnels

**Objectif :** Démontrer end-to-end qu'un rappel email et un rappel SMS sont effectivement envoyés et tracés.

**Prérequis :**
- Section avec rappels activés
- Aéronef disponible
- Pilote A (`fpeignot`) avec email valide, canal `email+sms`, délai `2`h
- Pilote A avec numéro de mobile valide (`06XXXXXXXX`)
- Clé API Brevo configurée dans `program.php`

### Étapes

**Étape A.1 — Configurer les préférences du pilote**

1. Se connecter en tant que pilote A.
2. Aller sur **Mes réservations**.
3. Sélectionner canal **Email + SMS**, délai **2 heures**.
4. Sauvegarder.

- [ ] Préférences sauvegardées confirmées.

**Étape A.2 — Créer une réservation dans 45 minutes**

1. Aller dans **Réservations d'aéronefs**.
2. Créer une réservation :
   - Pilote : Pilote A
   - Aéronef : aéronef de test
   - Heure de début : maintenant + 45 min
   - Heure de fin : maintenant + 2h
3. Sauvegarder la réservation.

**Note :** Avec un délai de rappel de 2h, la réservation démarrant dans 45 min est déjà dans la fenêtre d'envoi.

- [ ] Réservation créée, numéro d'ID noté : `___`

**Étape A.3 — Vérifier la notification de création**

1. Se connecter en tant qu'administrateur.
2. Aller sur **Administration > Logs rappels**.
3. Chercher l'entrée correspondant à la réservation créée, type `event_create`.

- [ ] Entrée `event_create` présente dans les logs avec statut `success` ou `skipped` (si solo sans second membre).

**Étape A.4 — Déclencher le scheduler manuellement**

```bash
curl -s http://gvv.net/reservation_scheduler/run/VOTRE_SECRET
```

- [ ] Réponse JSON avec `"sent": 1` (ou plus).

**Étape A.5 — Vérifier les logs admin**

1. Aller sur **Administration > Logs rappels**.
2. Filtrer par **Succès**.
3. Localiser l'entrée avec la réservation ID noté, type `scheduled`, canal `email+sms`.

- [ ] Entrée `scheduled` avec statut `success` visible dans les logs.

**Étape A.6 — Vérifier la réception email**

1. Consulter la boîte mail de Pilote A.
2. Rechercher un email avec sujet `[GVV] Rappel réservation IMMAT le JJ/MM/AAAA`.
3. Vérifier le contenu : date/heure, aéronef, statut, rôle.

- [ ] Email reçu avec contenu correct.

**Étape A.7 — Vérifier la réception SMS**

1. Consulter le téléphone mobile de Pilote A.
2. Un SMS doit être reçu au format : `Rappel vol IMMAT le JJ/MM HH:MM – rôle: pilot – GVV`.

- [ ] SMS reçu sur le mobile.

**Étape A.8 — Vérifier l'idempotence (pas de doublon)**

1. Déclencher à nouveau le scheduler :

```bash
curl -s http://gvv.net/reservation_scheduler/run/VOTRE_SECRET
```

2. Vérifier dans les logs que la même réservation n'a pas reçu un second envoi.

- [ ] Réponse JSON `"sent": 0` (ou les entrées déjà envoyées ne sont pas répétées).

---

## SC-B — Scénario : notification au second membre d'équipage

**Objectif :** Démontrer que lors d'une réservation créée par un tiers, les deux membres d'équipage sont notifiés. Et que lorsque le pilote crée la réservation, seul l'instructeur est notifié.

**Prérequis :**
- Pilote A (`fpeignot`) avec email valide, canal `email`
- Pilote B (instructeur) avec email valide, canal `email`
- Section avec rappels activés

### Cas B.1 — Pilote crée sa propre réservation avec instructeur

**Étapes :**

1. Se connecter en tant que Pilote A.
2. Créer une réservation :
   - Pilote : Pilote A
   - Instructeur : Pilote B
   - Heure de début : maintenant + 45 min
3. Sauvegarder.

- [ ] Réservation créée, ID noté : `___`

4. Se connecter en tant qu'administrateur.
5. Consulter **Logs rappels**, filtrer par cette réservation.

**Résultat attendu :** Une entrée `event_create` est présente. Le destinataire est **Pilote B uniquement** (l'instructeur), car le créateur (Pilote A) est déjà membre d'équipage.

- [ ] Log `event_create` avec destinataire = Pilote B uniquement.
- [ ] Pilote B reçoit un email de notification de création.
- [ ] Pilote A ne reçoit PAS d'email de notification de création.

### Cas B.2 — Administrateur (tiers) crée une réservation pour un équipage

**Étapes :**

1. Se connecter en tant qu'administrateur (`testadmin`).
2. Créer une réservation :
   - Pilote : Pilote A
   - Instructeur : Pilote B
   - Heure de début : maintenant + 45 min
3. Sauvegarder.

- [ ] Réservation créée, ID noté : `___`

4. Consulter **Logs rappels**, filtrer par cette réservation.

**Résultat attendu :** Une entrée `event_create` est présente. Les destinataires sont **Pilote A ET Pilote B** (le créateur est tiers).

- [ ] Log `event_create` avec deux destinataires (Pilote A et Pilote B).
- [ ] Pilote A reçoit un email de notification.
- [ ] Pilote B reçoit un email de notification.

### Cas B.3 — Rappel temporel pour l'équipage complet

**Étapes :**

1. S'assurer que Pilote A a `reminder_period_hours = 2` et Pilote B a `reminder_period_hours = 2`.
2. Déclencher le scheduler :

```bash
curl -s http://gvv.net/reservation_scheduler/run/VOTRE_SECRET
```

3. Vérifier les logs.

**Résultat attendu :** Deux entrées `scheduled` sont créées, une pour Pilote A (rôle pilot) et une pour Pilote B (rôle instructor).

- [ ] Deux entrées `scheduled` dans les logs, une par membre d'équipage.
- [ ] Pilote A reçoit un email rappel avec rôle `Pilote`.
- [ ] Pilote B reçoit un email rappel avec rôle `Instructeur`.

### Cas B.4 — Notification d'annulation

**Étapes :**

1. Se connecter en tant que Pilote A.
2. Sur **Mes réservations**, supprimer la réservation créée au Cas B.1.
3. Vérifier dans les logs admin l'entrée `event_cancel`.

**Résultat attendu :** Pilote B reçoit un email d'annulation (`[GVV] Réservation annulée – IMMAT le JJ/MM/AAAA`).

- [ ] Log `event_cancel` présent avec statut `success`.
- [ ] Email d'annulation reçu par Pilote B.

---

## TC-05 — Comportement en cas d'erreur de configuration

### TC-05.1 — Clé API Brevo absente → log skipped/failure, pas de crash

**Mise en œuvre :**

1. Vider `brevo_sms_api_key` dans `program.php` (mettre `''`).
2. Configurer un pilote avec canal `sms`.
3. Déclencher le scheduler.
4. Vérifier les logs.

**Résultat attendu :** Entrée en statut `failure` avec message d'erreur `brevo_sms_api_key not configured`. L'application ne plante pas, l'exécution continue.

- [ ] **PASS / FAIL** — Erreur tracée sans crash.

---

### TC-05.2 — Destinataire sans email → log skipped, pas d'erreur SMTP

**Mise en œuvre :**

1. Créer (ou choisir) un pilote sans email renseigné.
2. Créer une réservation le concernant.
3. Déclencher le scheduler.
4. Vérifier les logs.

**Résultat attendu :** Entrée en statut `skipped`, pas d'erreur dans les logs application.

- [ ] **PASS / FAIL** — Destinataire sans email géré proprement.

---

## Récapitulatif des cas de test

| ID | Description | PASS | FAIL | N/A |
|----|-------------|------|------|-----|
| TC-01.1 | Documentation cron disponible | ☐ | ☐ | ☐ |
| TC-01.2 | Exécution CLI sans erreur | ☐ | ☐ | ☐ |
| TC-01.3 | URL publique avec secret valide | ☐ | ☐ | ☐ |
| TC-01.4 | URL publique avec secret invalide → 403 | ☐ | ☐ | ☐ |
| TC-01.5 | Traçabilité dans les logs application | ☐ | ☐ | ☐ |
| TC-02.1 | Paramètres SMS localisables dans config | ☐ | ☐ | ☐ |
| TC-02.2 | Activation rappels au niveau section | ☐ | ☐ | ☐ |
| TC-02.3 | Désactivation rappels pour une section | ☐ | ☐ | ☐ |
| TC-03.1 | Documentation accessible sur GitHub | ☐ | ☐ | ☐ |
| TC-03.2 | Page logs accessible aux admins | ☐ | ☐ | ☐ |
| TC-04.1 | Accès à "Mes réservations" | ☐ | ☐ | ☐ |
| TC-04.2 | Préférences canal email | ☐ | ☐ | ☐ |
| TC-04.3 | Préférences canal SMS | ☐ | ☐ | ☐ |
| TC-04.4 | Préférences canal email+SMS | ☐ | ☐ | ☐ |
| TC-04.5 | Accès refusé sans connexion | ☐ | ☐ | ☐ |
| SC-A.1 à A.8 | Scénario email + SMS end-to-end | ☐ | ☐ | ☐ |
| SC-B.1 | Notification instructeur par pilote | ☐ | ☐ | ☐ |
| SC-B.2 | Notification deux membres par tiers | ☐ | ☐ | ☐ |
| SC-B.3 | Rappel temporel pour équipage complet | ☐ | ☐ | ☐ |
| SC-B.4 | Notification d'annulation | ☐ | ☐ | ☐ |
| TC-05.1 | Clé Brevo absente → failure gracieux | ☐ | ☐ | ☐ |
| TC-05.2 | Destinataire sans email → skipped | ☐ | ☐ | ☐ |
