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
- Pour les tests SMS (Phase 2) : le pilote A a un numéro de mobile renseigné (format `06XXXXXXXX`)

---

## Méthode d'accélération des tests

Pour éviter d'attendre 24 heures, les scénarios exploitent deux leviers :

1. **Réservation dans moins d'une heure** : crée une réservation démarrant dans 45 minutes. Avec `reminder_period_hours = 2`, la fenêtre d'envoi est déjà ouverte.
2. **Déclenchement manuel du scheduler** : l'URL publique `/reservation_scheduler/run/SECRET` ou la commande CLI déclenchent immédiatement l'envoi sans attendre le cron.

---

## TC-01 — Vérification préalable de la documentation GitHub

**Objectif :** S'assurer que la documentation en ligne est disponible et complète avant de démarrer toute installation.

**⚠️ Prérequis absolu : ce cas de test doit être exécuté en premier. En cas d'échec, contacter le responsable du projet avant de continuer.**

**Page utilisée :** Dépôt GitHub du projet GVV (navigateur)

**Mise en œuvre :**

1. Ouvrir un navigateur et naviguer vers le dépôt GitHub du projet GVV.
2. Localiser le fichier `doc/design_notes/rappels_email_reservations_design.md`.
3. Vérifier la présence des sections suivantes :
   - Architecture générale de la fonctionnalité
   - Paramètres de configuration (`program.php`)
   - Commande cron avec chemin complet vers PHP 7.4
   - Configuration Brevo SMS (clé API, expéditeur)
   - Description des pages d'administration (Sections, Logs rappels)
   - Description de la page utilisateur (Mes réservations)

**Résultat attendu :** Le document est lisible sur GitHub, le diagramme d'architecture est embarqué et visible, toutes les sections ci-dessus sont présentes.

- [ ] **PASS / FAIL** — Documentation accessible et complète sur GitHub.

---

## Phase 1 — Rappels par email

*Cette phase ne nécessite pas de clé API externe. Elle valide le canal email de bout en bout.*

---

### TC-02 — Activation des rappels au niveau section

**Objectif :** Vérifier qu'un administrateur peut activer les rappels pour une section via l'interface.

**Page utilisée : Administration > Sections** (`http://gvv.net/sections`)

#### TC-02.1 — Activation des rappels

**Vérification documentaire :** La documentation GitHub décrit-elle comment activer les rappels au niveau section ?

- [ ] **PASS / FAIL** — Instructions d'activation présentes dans la doc GitHub.

**Mise en œuvre :**

1. Se connecter en tant qu'administrateur (`testadmin`).
2. Naviguer vers **Administration > Sections**.
3. Sélectionner une section et cliquer sur **Modifier**.
4. Localiser le champ **Rappels de réservation activés** et le cocher.
5. Cliquer sur **Enregistrer**.

**Résultat attendu :** Message de confirmation de sauvegarde. Le champ reste coché au rechargement.

**En cas d'échec :** La suite des tests Phase 1 ne peut pas être exécutée. Vérifier les logs serveur.

- [ ] **PASS / FAIL** — Rappels activés pour la section, confirmation visible.

---

#### TC-02.2 — Désactivation des rappels pour une section

**Page utilisée : Administration > Sections** (`http://gvv.net/sections`)

**Mise en œuvre :**

1. Décocher **Rappels de réservation activés** sur la section.
2. Enregistrer.
3. Déclencher le scheduler (voir TC-04.2).
4. Consulter **Administration > Logs rappels de réservation**.

**Résultat attendu :** Aucune entrée `success` pour les réservations de cette section. Les entrées éventuelles ont le statut `skipped`.

- [ ] **PASS / FAIL** — Rappels bloqués pour la section désactivée.

5. **Réactiver les rappels** sur la section avant de continuer les tests.

---

### TC-03 — Configuration du cron

**Objectif :** Vérifier que la commande cron est documentée et opérationnelle.

#### TC-03.1 — Vérification de la commande cron dans la documentation

**Page utilisée :** Dépôt GitHub (navigateur) et terminal serveur

**Vérification documentaire :** La documentation GitHub fournit-elle la commande cron complète (chemin PHP 7.4, chemin `index.php`, paramètres) ?

- [ ] **PASS / FAIL** — Commande cron documentée avec chemin complet vers PHP 7.4.

**Mise en œuvre :**

1. Dans la documentation GitHub, relever la commande cron documentée.
2. Sur le serveur, vérifier la crontab active :

```bash
crontab -l | grep reservation_scheduler
```

**Résultat attendu :** La crontab contient une ligne correspondant à la commande documentée, avec le bon chemin vers PHP 7.4.

**En cas d'absence de crontab :** Utiliser le déclenchement manuel par URL (TC-03.3) pour la suite des tests.

- [ ] **PASS / FAIL** — Cron configuré conformément à la documentation.

---

#### TC-03.2 — Exécution manuelle via CLI

**Page utilisée :** Terminal serveur

**Mise en œuvre :**

```bash
source setenv.sh
XDEBUG_MODE=off /usr/bin/php7.4 /home/frederic/git/gvv/index.php reservation_scheduler cron
```

**Résultat attendu :** Sortie `reservation_scheduler cron: sent=N` (N ≥ 0). Aucune erreur PHP fatale.

- [ ] **PASS / FAIL** — Exécution CLI sans erreur, sortie lisible.

---

#### TC-03.3 — Déclenchement via URL publique (secret valide)

**Page utilisée :** Terminal (curl) ou navigateur

**Vérification documentaire :** La documentation GitHub décrit-elle l'URL de déclenchement et l'emplacement du secret dans `program.php` ?

- [ ] **PASS / FAIL** — URL et emplacement du secret documentés.

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

#### TC-03.4 — Rejet d'un secret invalide

**Mise en œuvre :**

```bash
curl -s -o /dev/null -w "%{http_code}" http://gvv.net/reservation_scheduler/run/MAUVAIS_SECRET
```

**Résultat attendu :** Code HTTP 403.

- [ ] **PASS / FAIL** — HTTP 403 retourné pour secret invalide.

---

#### TC-03.5 — Traçabilité dans les logs application

**Page utilisée : Administration > Logs rappels de réservation** (`http://gvv.net/reservation_reminder_log`) et terminal

**Mise en œuvre :**

1. Exécuter le scheduler (CLI ou URL).
2. Vérifier dans les logs serveur :

```bash
grep "reservation_scheduler" application/logs/log-$(date +%Y-%m-%d).php | tail -5
```

3. Naviguer vers **Administration > Logs rappels de réservation** et vérifier que la page charge.

**Résultat attendu :** Une ligne `reservation_scheduler::_execute source=... sent=...` dans les logs serveur. La page d'administration est accessible aux administrateurs uniquement (redirection login pour un utilisateur non connecté).

- [ ] **PASS / FAIL** — Trace présente dans les logs application.
- [ ] **PASS / FAIL** — Page de logs accessible aux administrateurs, inaccessible sans connexion.

---

### TC-04 — Configuration des préférences email par l'utilisateur

**Objectif :** Vérifier qu'un utilisateur peut configurer le canal email et son délai de rappel.

**Page utilisée : Mes réservations** (`http://gvv.net/mes_reservations`)

#### TC-04.1 — Accès à la page

**Vérification documentaire :** La documentation GitHub décrit-elle l'accès à la page "Mes réservations" pour la configuration des préférences ?

- [ ] **PASS / FAIL** — Page "Mes réservations" mentionnée dans la doc GitHub avec son URL.

**Mise en œuvre :**

1. Se connecter avec le compte pilote (`fpeignot`).
2. Naviguer vers **Mes réservations** (`http://gvv.net/mes_reservations`).

**Résultat attendu :** La page charge, affiche les réservations futures et un formulaire de préférences de rappel.

**En cas d'échec :** La configuration des préférences utilisateur est impossible. Vérifier les droits d'accès et les logs.

- [ ] **PASS / FAIL** — Page accessible, formulaire de préférences visible.

---

#### TC-04.2 — Configuration du canal email

**Page utilisée : Mes réservations** (`http://gvv.net/mes_reservations`)

**Mise en œuvre :**

1. Dans le formulaire de préférences, sélectionner le canal **Email uniquement**.
2. Définir le délai à **2 heures** avant la réservation.
3. Cliquer sur **Enregistrer mes préférences**.
4. Recharger la page.

**Résultat attendu :** Message de confirmation. Les valeurs sélectionnées sont pré-remplies au rechargement.

- [ ] **PASS / FAIL** — Préférences email sauvegardées et persistées.

---

#### TC-04.3 — Accès refusé sans connexion

**Mise en œuvre :**

1. Se déconnecter.
2. Accéder directement à `http://gvv.net/mes_reservations`.

**Résultat attendu :** Redirection vers la page de connexion.

- [ ] **PASS / FAIL** — Accès non authentifié redirigé vers login.

---

### SC-A — Scénario email end-to-end

**Objectif :** Démontrer qu'un rappel email est envoyé et tracé de bout en bout.

**Prérequis :**
- TC-02.1 PASS (section avec rappels activés)
- TC-04.2 PASS (pilote A avec canal `email`, délai `2`h)
- Pilote A (`fpeignot`) avec adresse email valide

**Étapes :**

**Étape A.1 — Créer une réservation dans 45 minutes**

1. Se connecter en tant que pilote A (`fpeignot`).
2. Naviguer vers **Réservations d'aéronefs**.
3. Créer une réservation :
   - Pilote : Pilote A
   - Aéronef : aéronef de test
   - Heure de début : maintenant + 45 min
   - Heure de fin : maintenant + 2h
4. Sauvegarder.

- [ ] Réservation créée, ID noté : `___`

**Étape A.2 — Déclencher le scheduler**

```bash
curl -s http://gvv.net/reservation_scheduler/run/VOTRE_SECRET
```

- [ ] Réponse JSON avec `"sent": 1` (ou plus).

**Étape A.3 — Vérifier les logs admin**

1. Se connecter en tant qu'administrateur.
2. Naviguer vers **Administration > Logs rappels de réservation** (`http://gvv.net/reservation_reminder_log`).
3. Filtrer par **Succès**.
4. Localiser l'entrée avec la réservation ID notée, type `scheduled`, canal `email`.

- [ ] Entrée `scheduled` avec statut `success` visible dans les logs.

**Étape A.4 — Vérifier la réception email**

1. Consulter la boîte mail de Pilote A.
2. Rechercher un email avec sujet `[GVV] Rappel réservation IMMAT le JJ/MM/AAAA`.
3. Vérifier le contenu : date/heure, aéronef, rôle.

- [ ] Email reçu avec contenu correct.

**Étape A.5 — Vérifier l'idempotence (pas de doublon)**

1. Déclencher à nouveau le scheduler.
2. Vérifier dans les logs que la même réservation n'a pas reçu un second envoi.

- [ ] Réponse JSON `"sent": 0` (ou les entrées déjà envoyées ne sont pas répétées).

---

### SC-B — Scénario : notification au second membre d'équipage

**Objectif :** Démontrer que les deux membres d'équipage reçoivent les notifications appropriées selon le créateur.

**Prérequis :** Pilote A et Pilote B avec email valide, canal `email`, section avec rappels activés.

#### Cas B.1 — Pilote crée sa propre réservation avec instructeur

**Page utilisée : Réservations d'aéronefs**

1. Se connecter en tant que Pilote A.
2. Créer une réservation avec Pilote B en instructeur, début dans 45 min.
3. Consulter **Administration > Logs rappels**, filtrer par cette réservation.

**Résultat attendu :** Entrée `event_create` avec destinataire = Pilote B uniquement.

- [ ] Log `event_create` — destinataire Pilote B uniquement.
- [ ] Pilote B reçoit un email de notification de création.
- [ ] Pilote A ne reçoit PAS d'email de notification de création.

---

#### Cas B.2 — Administrateur (tiers) crée une réservation pour un équipage

**Page utilisée : Réservations d'aéronefs**

1. Se connecter en tant qu'administrateur (`testadmin`).
2. Créer une réservation : Pilote A + Pilote B en instructeur, début dans 45 min.
3. Consulter **Administration > Logs rappels**.

**Résultat attendu :** Entrée `event_create` avec deux destinataires (Pilote A et Pilote B).

- [ ] Log `event_create` — deux destinataires.
- [ ] Pilote A reçoit un email de notification.
- [ ] Pilote B reçoit un email de notification.

---

#### Cas B.3 — Rappel temporel pour l'équipage complet

1. Déclencher le scheduler.
2. Vérifier les logs.

**Résultat attendu :** Deux entrées `scheduled`, une par membre d'équipage.

- [ ] Deux entrées `scheduled` dans les logs.
- [ ] Pilote A reçoit un email rappel avec rôle `Pilote`.
- [ ] Pilote B reçoit un email rappel avec rôle `Instructeur`.

---

#### Cas B.4 — Notification d'annulation

**Page utilisée : Mes réservations** (`http://gvv.net/mes_reservations`)

1. Se connecter en tant que Pilote A.
2. Sur **Mes réservations**, supprimer la réservation du Cas B.1.
3. Consulter **Administration > Logs rappels**, chercher l'entrée `event_cancel`.

**Résultat attendu :** Pilote B reçoit un email d'annulation.

- [ ] Log `event_cancel` présent avec statut `success`.
- [ ] Email d'annulation reçu par Pilote B.

---

## Phase 2 — Rappels SMS (nécessite une clé API Brevo)

*Cette phase est optionnelle si aucune clé API Brevo n'est disponible. Elle s'exécute après la validation complète de la Phase 1.*

---

### TC-05 — Configuration des paramètres SMS Brevo

**Objectif :** Vérifier que les paramètres SMS sont correctement configurés sur le serveur.

**Page utilisée :** Terminal serveur (fichier `application/config/program.php`)

#### TC-05.1 — Vérification de la documentation SMS

**Vérification documentaire :** La documentation GitHub décrit-elle où saisir la clé API Brevo, le nom de l'expéditeur et la signification de chaque paramètre ?

- [ ] **PASS / FAIL** — Instructions de configuration SMS présentes et claires dans la doc GitHub.

**En cas d'échec :** Ne pas continuer la Phase 2. Signaler le manque de documentation.

---

#### TC-05.2 — Configuration de la clé API Brevo

**Mise en œuvre :**

1. Se connecter en tant qu'administrateur système sur le serveur.
2. Éditer `application/config/program.php` et localiser la section SMS :

```php
$config['brevo_sms_api_key'] = '';     // Clé API Brevo
$config['brevo_sms_sender']  = 'GVV'; // Expéditeur (max 11 chars)
```

3. Renseigner une clé API Brevo valide.
4. Sauvegarder le fichier.
5. Vérifier la configuration :

```bash
grep -E "brevo_sms_api_key|brevo_sms_sender|reservation_scheduler_secret" application/config/program.php
```

**Résultat attendu :** Les paramètres sont lisibles, la clé API est renseignée.

**En cas d'absence de clé API :** Passer directement à TC-06.1 (comportement sans clé) et arrêter la Phase 2.

- [ ] **PASS / FAIL** — Paramètres SMS configurés.

---

### TC-06 — Configuration des préférences SMS par l'utilisateur

**Page utilisée : Mes réservations** (`http://gvv.net/mes_reservations`)

**Prérequis :** TC-05.2 PASS. Pilote A a un numéro de mobile renseigné dans sa fiche membre.

#### TC-06.1 — Canal SMS uniquement

1. Se connecter en tant que Pilote A.
2. Sur **Mes réservations**, sélectionner le canal **SMS**, délai **2 heures**.
3. Enregistrer et recharger la page.

**Résultat attendu :** Canal SMS persisté.

- [ ] **PASS / FAIL** — Canal SMS sauvegardé correctement.

---

#### TC-06.2 — Canal Email + SMS

1. Sur **Mes réservations**, sélectionner le canal **Email + SMS**, délai **2 heures**.
2. Enregistrer et recharger la page.

**Résultat attendu :** Canal `email+sms` persisté.

- [ ] **PASS / FAIL** — Canal Email + SMS sauvegardé.

---

### SC-C — Scénario SMS end-to-end

**Prérequis :**
- TC-05.2 PASS (clé API Brevo configurée)
- TC-06.2 PASS (pilote A avec canal `email+sms`, délai `2`h)
- Pilote A avec numéro de mobile valide (`06XXXXXXXX`)

**Étapes :**

**Étape C.1 — Créer une réservation dans 45 minutes**

1. Se connecter en tant que Pilote A.
2. Créer une réservation sur **Réservations d'aéronefs**, début dans 45 min.
3. Sauvegarder. ID noté : `___`

**Étape C.2 — Déclencher le scheduler**

```bash
curl -s http://gvv.net/reservation_scheduler/run/VOTRE_SECRET
```

- [ ] Réponse JSON avec `"sent": 1` (ou plus).

**Étape C.3 — Vérifier les logs admin**

**Page utilisée : Administration > Logs rappels de réservation** (`http://gvv.net/reservation_reminder_log`)

- [ ] Entrée `scheduled` avec statut `success`, canal `email+sms`.

**Étape C.4 — Vérifier la réception email**

- [ ] Email reçu avec contenu correct.

**Étape C.5 — Vérifier la réception SMS**

1. Consulter le téléphone mobile de Pilote A.
2. SMS attendu au format : `Rappel vol IMMAT le JJ/MM HH:MM – rôle: pilot – GVV`.

- [ ] SMS reçu sur le mobile.

---

## TC-07 — Comportement en cas d'erreur de configuration

### TC-07.1 — Clé API Brevo absente → log failure, pas de crash

**Mise en œuvre :**

1. Vider `brevo_sms_api_key` dans `program.php` (mettre `''`).
2. Configurer Pilote A avec canal `sms`.
3. Déclencher le scheduler.
4. Consulter **Administration > Logs rappels de réservation**.

**Résultat attendu :** Entrée en statut `failure` avec message `brevo_sms_api_key not configured`. L'application ne plante pas, l'exécution continue.

- [ ] **PASS / FAIL** — Erreur tracée sans crash.

---

### TC-07.2 — Destinataire sans email → log skipped, pas d'erreur SMTP

**Mise en œuvre :**

1. Choisir un pilote sans email renseigné dans sa fiche membre.
2. Créer une réservation le concernant.
3. Déclencher le scheduler.
4. Consulter **Administration > Logs rappels de réservation**.

**Résultat attendu :** Entrée en statut `skipped`, pas d'erreur dans les logs application.

- [ ] **PASS / FAIL** — Destinataire sans email géré proprement.

---

## Récapitulatif des cas de test

| ID | Description | Page utilisée | PASS | FAIL | N/A |
|----|-------------|---------------|------|------|-----|
| TC-01 | Documentation GitHub complète et accessible | GitHub (navigateur) | ☐ | ☐ | ☐ |
| TC-02.1 | Activation rappels au niveau section | Administration > Sections | ☐ | ☐ | ☐ |
| TC-02.2 | Désactivation rappels pour une section | Administration > Sections | ☐ | ☐ | ☐ |
| TC-03.1 | Commande cron documentée et configurée | GitHub + terminal | ☐ | ☐ | ☐ |
| TC-03.2 | Exécution CLI sans erreur | Terminal | ☐ | ☐ | ☐ |
| TC-03.3 | URL publique avec secret valide | curl / navigateur | ☐ | ☐ | ☐ |
| TC-03.4 | URL publique avec secret invalide → 403 | curl | ☐ | ☐ | ☐ |
| TC-03.5 | Traçabilité logs application et admin | Terminal + Admin > Logs | ☐ | ☐ | ☐ |
| TC-04.1 | Accès à "Mes réservations" | Mes réservations | ☐ | ☐ | ☐ |
| TC-04.2 | Préférences canal email | Mes réservations | ☐ | ☐ | ☐ |
| TC-04.3 | Accès refusé sans connexion | Mes réservations | ☐ | ☐ | ☐ |
| SC-A | Scénario email end-to-end | Réservations + Admin > Logs | ☐ | ☐ | ☐ |
| SC-B.1 | Notification instructeur par pilote | Réservations + Admin > Logs | ☐ | ☐ | ☐ |
| SC-B.2 | Notification deux membres par tiers | Réservations + Admin > Logs | ☐ | ☐ | ☐ |
| SC-B.3 | Rappel temporel pour équipage complet | Admin > Logs | ☐ | ☐ | ☐ |
| SC-B.4 | Notification d'annulation | Mes réservations + Admin > Logs | ☐ | ☐ | ☐ |
| **Phase 2 SMS** | | | | | |
| TC-05.1 | Documentation SMS dans GitHub | GitHub (navigateur) | ☐ | ☐ | ☐ |
| TC-05.2 | Configuration clé API Brevo | Terminal (program.php) | ☐ | ☐ | ☐ |
| TC-06.1 | Préférences canal SMS | Mes réservations | ☐ | ☐ | ☐ |
| TC-06.2 | Préférences canal Email + SMS | Mes réservations | ☐ | ☐ | ☐ |
| SC-C | Scénario SMS end-to-end | Réservations + Admin > Logs | ☐ | ☐ | ☐ |
| TC-07.1 | Clé Brevo absente → failure gracieux | Admin > Logs | ☐ | ☐ | ☐ |
| TC-07.2 | Destinataire sans email → skipped | Admin > Logs | ☐ | ☐ | ☐ |
