# 17. Sauvegarde Hors-Site des Backups

## Vue d'ensemble

GVV dispose déjà de sauvegardes automatiques **locales** de la base de données et des fichiers média (voir la documentation technique `doc/features/Backup.md`), produites par les scripts `tools/autobackup.py` et `tools/autobackup_media.py`. Ces sauvegardes restent cependant stockées sur la même machine que l'application : en cas de perte totale du serveur, elles disparaissent avec lui.

Ce guide décrit la mise en place d'un envoi **quotidien** de ces sauvegardes déjà produites vers le Google Drive du club, à l'aide de l'outil **rclone**, avec suppression automatique des copies distantes de plus de trois jours et une alerte en cas de panne (§3).

> **Prérequis** : cette mise en place s'adresse à la personne qui administre le serveur GVV (accès SSH, droits pour éditer la crontab). Elle ne touche à aucune configuration de l'application GVV elle-même — voir la note de conception `doc/design_notes/sauvegarde_hors_site_design.md` pour le détail de ce choix architectural.

---

## 1. Installation et Configuration de rclone (Administrateur Système)

### 1.1 Installer rclone

**Méthode recommandée** — script d'installation officiel (toujours à jour) :

```bash
sudo -v ; curl https://rclone.org/install.sh | sudo bash
```

**Méthode alternative** — paquet de la distribution (peut être plus ancien) :

```bash
sudo apt update
sudo apt install rclone
```

> **Version trop ancienne** : si, lors de l'autorisation (§1.3), rclone affiche une adresse `https://accounts.google.com/...` contenant `redirect_uri=urn:ietf:wg:oauth:2.0:oob` au lieu d'une commande `rclone authorize`, sa version est trop ancienne : Google refuse cette méthode (« Accès bloqué : la demande n'est pas valide »). Mettez rclone à jour avec la méthode recommandée ci-dessus ; la configuration existante est conservée.

**Vérifier l'installation :**

```bash
rclone version
```

Résultat attendu (numéro de version selon la méthode choisie) :

```
rclone v1.6x.x
- os/version: ubuntu 22.04
- os/kernel: ...
- go/version: ...
```

### 1.2 Créer le remote Google Drive

Documentation officielle de référence pour cette étape :

* [Configuration rclone pour Google Drive](https://rclone.org/drive/)
* [Créer son propre client ID Google](https://rclone.org/drive/#making-your-own-client-id)

> **À lancer sous le compte qui exécutera les tâches cron** (§2) — celui qui fait déjà tourner `tools/autobackup.py` et possède les fichiers de `backups/`, **pas `root`**. Depuis `root` : `sudo -u <compte> -i rclone config`.
>
> `rclone config` peut être relancé sans risque après une interruption ou une erreur : si le remote n'a pas été confirmé (`Keep this "gdrive" remote? y`), rien n'a été enregistré ; s'il a été enregistré incomplet, supprimez-le (`d) Delete remote`) puis recréez-le. Si seule l'autorisation Google a échoué : `rclone config reconnect gdrive:`.

#### Préalable — créer un client OAuth Google dédié

Ne laissez **pas** `client_id` / `client_secret` vides lors de `rclone config`. Google a annoncé qu'il commencerait à facturer les appels API passant par le client OAuth partagé par défaut de rclone (Google Drive et Google Photos), « plus tard en 2026, avec un préavis de 90 jours » ([annonce officielle sur le forum rclone](https://forum.rclone.org/t/google-drive-and-google-photos-users-action-required/54005)). Créez donc votre propre client OAuth dès la mise en place initiale.

Procédure (testée sur `console.cloud.google.com` en août 2026), **depuis un poste avec navigateur, pas depuis le serveur** :

1. Connectez-vous sur [console.cloud.google.com](https://console.cloud.google.com) avec le compte Google cible (`info@planeur-abbeville.fr`).
   Google Cloud Console exige la validation en deux étapes (MFA) sur le compte depuis mai 2025. Activez-la au préalable si ce n'est pas déjà fait (**Sécurité et connexion > Validation en deux étapes**) et conservez des **codes de secours** — c'est un compte partagé du club.
2. Créez un **nouveau projet** dédié (ex. « GVV remote backups »), sans organisation.
3. **API et services > Bibliothèque** > rechercher « Google Drive API » > **Activer**.
4. **Google Auth Platform** (anciennement « Écran de consentement OAuth ») **> Présentation > Premiers pas**. Renseignez :
   - Nom de l'application : ex. `rclone`
   - Adresse d'assistance utilisateur : celle du club
   - Étape **Cible** : **Externe** (sauf si le compte est un Google Workspace payant avec un accès « Interne » pertinent)
   - Étape **Coordonnées** : une adresse e-mail de contact technique (peut être personnelle, sert uniquement aux notifications Google sur ce projet ; pas besoin d'une adresse Gmail)
   - **Créer**
5. **Accès aux données > Ajouter ou supprimer des niveaux d'accès** > chercher `drive` > cocher précisément `https://www.googleapis.com/auth/drive.file` (**pas** `drive` complet) > **Update** > **Save**.
6. **Branding** : Google exige, pour publier l'application, une **page d'accueil** et un lien vers des **règles de confidentialité** publiques. Renseignez :
   - Page d'accueil de l'application : la page de connexion de GVV (`https://<votre-gvv>/auth/login`), qui contient le lien vers les règles de confidentialité ;
   - Lien vers les règles de confidentialité : `https://<votre-gvv>/legal/confidentialite` ;
   - Lien vers les conditions d'utilisation (facultatif) : `https://<votre-gvv>/legal/conditions` ;
   - Domaines autorisés : le domaine de votre GVV ;
   - **pas de logo** : un logo impose une validation de l'application par Google.

   Le contenu de ces pages se règle dans GVV, voir [21. Configuration](21_configuration.md). Mentionnez-y le dépôt des sauvegardes sur Google Drive (texte propre au club).
7. **Audience > État de publication > Publier l'application** > confirmer, pour passer l'application en **Production**. **Ne la laissez pas en mode « Test »** : dans ce mode, Google invalide le jeton d'accès au bout de **7 jours** et l'envoi hors-site s'arrête sans prévenir (erreur `invalid_grant`, voir §5). Avec le seul scope `drive.file`, la publication ne demande aucune validation par Google ; lors de l'autorisation (§1.3), l'écran de consentement affichera simplement un avertissement « Google n'a pas validé cette application » : cliquez sur **Paramètres avancés > Accéder à rclone (non sécurisé)** pour continuer.
8. **Clients > Créer un client** > Type d'application : **Application de bureau** (Desktop app) > Nom : `rclone` > **Créer**.
9. Cliquez sur le client créé pour récupérer le **client ID** et le **client secret** ; vous les saisirez dans `rclone config` ci-dessous.

> **Sécurité** : ce `client_secret` n'est pas plus sensible que celui de n'importe quelle application OAuth « installée » standard (ce type d'identifiant est volontairement visible dans de nombreux projets open source). Par précaution générale, évitez néanmoins de le committer dans le dépôt Git.

#### Lancer l'assistant interactif

```bash
rclone config
```

Répondez aux questions dans l'ordre suivant :

| Invite | Réponse | Remarque |
| :--- | :--- | :--- |
| `n) New remote` | `n` | Créer un nouveau remote |
| `name>` | `gdrive` | Nom court, réutilisé dans toutes les commandes suivantes (`gdrive:...`) |
| `Storage>` | `drive` (ou le numéro correspondant dans la liste) | Sélectionne le backend Google Drive |
| `client_id>` | *client ID créé au préalable* | Ne pas laisser vide (voir « Préalable » ci-dessus) |
| `client_secret>` | *client secret créé au préalable* | Idem |
| `scope>` | option **`drive.file`** | Choisissez l'option correspondant au libellé `drive.file` dans la liste affichée par **votre** version de rclone — vérifiez le libellé, pas le numéro : l'ordre a changé entre versions (sur rclone v1.6x d'août 2026 : `1) drive`, `2) drive.readonly`, `3) drive.file`, `4) drive.appfolder`, `5) drive.metadata.readonly`). Ce scope limite l'accès de rclone aux seuls fichiers qu'il crée lui-même, pas à l'ensemble du Drive |
| `root_folder_id>` | *(laisser vide)* | Pas de restriction de dossier racine |
| `service_account_file>` | *(laisser vide)* | Authentification interactive, pas de compte de service |
| `Edit advanced config?` | `n` | Les réglages par défaut conviennent |
| `Use web browser to automatically authenticate?` | `y` | Voir §1.3 selon que le serveur a un navigateur accessible ou non |
| `Configure this as a Shared Drive (Team Drive)?` | `n` | Sauf si le club utilise un Drive Partagé plutôt qu'un compte Google individuel |
| `Keep this "gdrive" remote?` | `y` | Confirme et enregistre |

### 1.3 Autorisation Google Drive (`gvv.abbeville@... `du club)

Deux cas de figure selon l'accès réseau du serveur :

**Le serveur a un navigateur graphique accessible** (poste de travail, ou connexion SSH avec redirection X) :
Le prompt `y/n> y` ci-dessus ouvre automatiquement une page Google. Connectez-vous avec le compte Google cible (`info@planeur-abbeville.fr`, voir décision produit dans `doc/prds/sauvegarde_hors_site_prd.md` §11 QO-002), puis cliquez sur **Autoriser** / **Allow** sur l'écran de consentement qui liste les permissions demandées (accès aux fichiers créés par l'application, conforme au scope `drive.file` choisi). rclone récupère automatiquement le jeton et referme la page.

**Le serveur est distant sans navigateur (cas le plus probable en production)** :
1. Répondez `n` à `Use web browser to automatically authenticate?`.
2. rclone affiche une commande du type :
   ```
   rclone authorize "drive" "eyJjbGllbnRfaWQiOi..."
   ```
3. Copiez cette commande **telle quelle** (elle contient l'identifiant de votre client OAuth) et exécutez-la sur **votre poste local** (où rclone doit aussi être installé, et où un navigateur est disponible).
4. Si le navigateur s'ouvre sur un autre compte Google que le compte cible, fermez cet onglet : copiez l'adresse `http://127.0.0.1:53682/auth?state=…` affichée dans le terminal local et ouvrez-la dans une **fenêtre de navigation privée**, puis connectez-vous avec le compte cible. À l'avertissement « Google n'a pas validé cette application », cliquez sur **Paramètres avancés > Accéder à … (non sécurisé)**, puis **Autoriser**.
5. Le terminal local affiche un jeton entre `Paste the following into your remote machine --->` et `<---End paste` : copiez-le et collez-le dans le prompt `config_token>` resté ouvert sur le serveur distant.

### 1.4 Vérifier la configuration

```bash
rclone listremotes
```
```
gdrive:
```

```bash
rclone lsd gdrive:
```
Liste les dossiers déjà présents à la racine du Drive cible (peut être vide sur un compte neuf).

> **Note (scope `drive.file`)** : sur un Drive déjà utilisé, cette commande retourne une **liste vide** — ce n'est pas une erreur. Avec le scope `drive.file`, rclone ne voit que les fichiers et dossiers qu'il a lui-même créés ; les fichiers préexistants restent invisibles. Un dossier créé ensuite par un `rclone copy` (voir §1.5) apparaîtra bien, lui.

```bash
rclone about gdrive:
```
Affiche le quota utilisé/disponible du compte — à comparer avec le volume estimé des sauvegardes (voir `doc/prds/sauvegarde_hors_site_prd.md` §11 QO-003 : jugé largement suffisant).

### 1.5 Test du transfert

```bash
echo "Test rclone GVV $(date)" > /tmp/rclone_test.txt
rclone copy /tmp/rclone_test.txt gdrive:GVV_backups_test/
rclone ls gdrive:GVV_backups_test/
```

Résultat attendu :
```
       32 rclone_test.txt
```

Vérifiez également dans l'interface web de Google Drive (drive.google.com, connecté avec le compte cible) que le dossier `GVV_backups_test` et le fichier apparaissent bien. Une fois le test validé, supprimez le dossier de test :

```bash
rclone purge gdrive:GVV_backups_test/
rm /tmp/rclone_test.txt
```

---

## 2. Mise en place de l'envoi quotidien (cron)

### 2.1 Organisation du dossier distant

Ce guide utilise la structure suivante sur le Drive du club (à adapter, mais à rester cohérent avec les commandes ci-dessous) :

```
GVV_backups/
├── database/   ← sauvegardes de la base de données (*.zip)
└── media/      ← sauvegardes des fichiers média (*.tar.gz)
```

### 2.2 Localiser le chemin exact de rclone et des sauvegardes

```bash
which rclone
```
Notez le chemin complet retourné (ex. `/usr/bin/rclone`) : cron ne dispose pas toujours du même `PATH` qu'un shell interactif, mieux vaut utiliser le chemin absolu dans les entrées crontab.

Vérifiez le répertoire de sauvegarde locale existant (déjà utilisé par `tools/autobackup.py`/`tools/autobackup_media.py`) :

```bash
ls -la /home/frederic/git/gvv/backups/
```

### 2.3 Script d'envoi

L'envoi est fait par le script versionné `tools/rclone_offsite_upload.sh`. Il ne contient **aucun secret ni chemin en dur** (tout est passé en paramètre) :

```bash
tools/rclone_offsite_upload.sh "<motif des sauvegardes locales>" <remote:chemin/> <fichier_log> [url_ping]
```

Pour chaque type de sauvegarde, il :
1. sélectionne la sauvegarde locale la plus récente correspondant au motif (à mettre **entre guillemets**). Le chemin du dossier peut contenir des espaces ; le motif ne porte que sur le nom des fichiers ;
2. vérifie qu'elle date de moins de 36 heures. Sinon, c'est que la sauvegarde locale ne tourne plus : sans ce contrôle, rclone renverrait sans erreur l'ancien fichier et la panne passerait inaperçue ;
3. l'envoie avec `rclone copy` ;
4. écrit le résultat dans le log (`envoi OK` ou `ALERTE : envoi hors-site ECHOUE …` avec la cause) ;
5. si une URL de supervision est fournie (§3), la prévient du succès ou de l'échec. En cas d'échec, la fin du log est jointe à l'alerte.

Il rend le code `0` en cas de succès, `1` sinon. Le seuil de 36 heures se change avec la variable `MAX_AGE_HOURS`, en heures entières ; une valeur invalide déclenche une alerte au lieu de désactiver le contrôle.

Les deux scripts du §2.3 et du §2.4 chargent des fonctions communes depuis `tools/rclone_common.sh` : les trois fichiers doivent rester ensemble dans le dossier `tools/`.

Testez-le manuellement avant la mise en cron :

```bash
/home/frederic/git/gvv/tools/rclone_offsite_upload.sh "/home/frederic/git/gvv/backups/*_backup_*_migration_*.zip" gdrive:GVV_backups/database/ /home/frederic/git/gvv/backups/logfile.txt
echo "code retour: $?"
tail -n 3 /home/frederic/git/gvv/backups/logfile.txt
```

### 2.4 Rétention distante (suppression après trois jours)

**Ne lancez pas `rclone --min-age 3d delete` directement en cron.** Si les envois s'arrêtent (sauvegarde locale en panne, jeton expiré…), la suppression `--min-age 3d` continuerait de tourner aveuglément : au bout de trois jours, elle effacerait la **dernière copie distante valide**.

Le script versionné `tools/rclone_safe_retention.sh` évite ce piège : il ne purge que s'il reste au moins un fichier distant de moins de trois jours dans le dossier concerné. Sinon, il annule la purge et journalise une alerte, qui distingue deux causes :
- `erreur rclone` : le dossier distant n'a pas pu être lu (jeton expiré, réseau…) ;
- `aucune sauvegarde distante de moins de 3 jours` : les envois ne se font plus.

```bash
tools/rclone_safe_retention.sh <remote:chemin/> <fichier_log>
```

Testez-le manuellement avant la mise en cron :

```bash
/home/frederic/git/gvv/tools/rclone_safe_retention.sh gdrive:GVV_backups/database/ /home/frederic/git/gvv/backups/logfile.txt
tail -n 2 /home/frederic/git/gvv/backups/logfile.txt
```

> **⚠️ Fenêtre de rétention courte** : avec un envoi quotidien et une rétention de trois jours, seules 3 à 4 sauvegardes distantes coexistent à tout moment. Si un problème (corruption de données, suppression accidentelle) n'est détecté qu'après ce délai, il n'y a plus de copie distante saine disponible. Gardez aussi à l'esprit que dans les durées rclone `M` (majuscule) signifie *mois* et `m` (minuscule) *minutes* — une confusion de casse peut vider le dossier distant en une seule exécution.

### 2.5 Entrées crontab complètes

```bash
crontab -e
```

Ajoutez (exemple : tous les jours à 3h du matin, après les sauvegardes locales). Remplacez `<uuid-db>` et `<uuid-media>` par les identifiants de vos checks de supervision (§3), ou supprimez le dernier paramètre si vous n'en utilisez pas :

```
# Sauvegarde hors-site quotidienne (base de données)
0 3 * * * /home/frederic/git/gvv/tools/rclone_offsite_upload.sh "/home/frederic/git/gvv/backups/*_backup_*_migration_*.zip" gdrive:GVV_backups/database/ /home/frederic/git/gvv/backups/logfile.txt https://hc-ping.com/<uuid-db>

# Sauvegarde hors-site quotidienne (médias)
5 3 * * * /home/frederic/git/gvv/tools/rclone_offsite_upload.sh "/home/frederic/git/gvv/backups/media_backup_*.tar.gz" gdrive:GVV_backups/media/ /home/frederic/git/gvv/backups/logfile.txt https://hc-ping.com/<uuid-media>

# Rétention distante (suppression > 3 jours, avec garde-fou), après les deux envois
15 3 * * * /home/frederic/git/gvv/tools/rclone_safe_retention.sh gdrive:GVV_backups/database/ /home/frederic/git/gvv/backups/logfile.txt
16 3 * * * /home/frederic/git/gvv/tools/rclone_safe_retention.sh gdrive:GVV_backups/media/ /home/frederic/git/gvv/backups/logfile.txt
```

Adaptez `/home/frederic/git/gvv/` au chemin réel d'installation sur votre serveur. Le cinquième champ de l'heure doit rester `*` (tous les jours de la semaine) : `0` limiterait l'envoi au dimanche.

**Vérifier que les entrées sont bien enregistrées :**

```bash
crontab -l | grep -E 'rclone|GVV_backups'
```

**Tester manuellement une des lignes** (copiez-collez la partie après l'heure), puis :

```bash
echo "code retour: $?"
tail -n 3 /home/frederic/git/gvv/backups/logfile.txt
```

Un code retour `0` et une ligne `envoi OK` confirment le succès. En cas d'échec, la ligne `ALERTE` indique la cause et rclone affiche généralement une erreur explicite juste au-dessus (jeton expiré, quota dépassé…) — voir §5 Dépannage.

---

## 3. Supervision et alertes

### 3.1 Pourquoi une alerte est nécessaire

Les scripts du §2 écrivent leurs alertes dans le log, mais personne ne lit ce log au quotidien. En septembre 2026, l'envoi s'est arrêté (jeton Google expiré) et la panne n'a été découverte que deux mois plus tard, malgré une ligne `ALERTE` chaque nuit. Une alerte active, envoyée par un service **extérieur au serveur**, est donc indispensable : elle doit aussi se déclencher si le serveur est éteint ou si le cron ne tourne plus, cas où le serveur ne peut rien signaler lui-même.

### 3.2 Option A (recommandée) — Healthchecks.io

Un service de supervision de tâches planifiées : le script d'envoi le prévient de chaque succès et de chaque échec ; si rien n'arrive à l'heure attendue, le service alerte de lui-même. Offre gratuite : 20 checks.

**Mise en place :**

1. Créez un compte sur [healthchecks.io](https://healthchecks.io/) (email + mot de passe, ou connexion via un compte existant — à faire vous-même, aucun identifiant ne doit être saisi par un tiers).
2. Créez deux **checks** (bouton **Add Check**) : un nommé « GVV backup hors-site — base de données », un autre « GVV backup hors-site — médias ».
3. Pour chacun, réglez la **période** (**Period**) sur `1 day` et la **marge de tolérance** (**Grace Time**) sur `4 hours`, pour laisser le temps à une exécution un peu tardive sans déclencher une fausse alerte.
4. Chaque check affiche une **URL de ping** unique du type `https://hc-ping.com/<identifiant-unique>`. Notez les deux URL et placez-les en dernier paramètre des lignes d'envoi de la crontab (§2.5).
5. Dans **Integrations**, ajoutez le ou les canaux d'alerte et associez-les aux deux checks :
   * **Email** : l'adresse (ou la liste de diffusion) du club qui doit recevoir les alertes ;
   * **SMS** ou **appel** : selon les crédits inclus dans votre offre Healthchecks.io ;
   * notifications sur téléphone sans frais : **Telegram**, **Signal** ou **ntfy** ;
   * **SMS gratuit pour un abonné Free Mobile** : activez « Notifications par SMS » dans l'espace abonné Free Mobile, puis ajoutez une intégration **Webhook** dont l'URL est `https://smsapi.free-mobile.fr/sendmsg?user=<identifiant>&pass=<clé>&msg=GVV%20sauvegarde%20hors-site%20$NAME%20$STATUS` (identifiant et clé fournis par Free Mobile).

Le check passe en alerte dans trois cas :
* le script signale un échec (envoi rclone refusé, sauvegarde locale absente ou trop ancienne) : alerte immédiate, avec la fin du log jointe ;
* aucun signal n'arrive dans la période plus la tolérance (serveur éteint, cron supprimé, script introuvable) ;
* le serveur n'a plus accès à internet.

**Tester** : lancez manuellement une ligne d'envoi de la crontab, puis rafraîchissez la page du check : son statut doit passer à « Up ». Pour tester l'alerte, lancez le script avec un dossier distant inexistant (par exemple `gdrive_inexistant:x/`) : le check doit passer à « Down » et l'alerte arriver. Relancez ensuite la vraie ligne pour revenir à « Up ».

### 3.3 Option B — Google Apps Script (lié directement au compte Drive cible)

Un script hébergé et exécuté par Google (pas sur le serveur GVV), qui vérifie chaque jour la date du fichier le plus récent dans le dossier de sauvegarde et envoie un email si aucun fichier récent n'est trouvé. Cette option reste entièrement dans l'écosystème Google déjà utilisé (compte `info@planeur-abbeville.fr`), sans dépendance externe supplémentaire.

**Mise en place :**

1. Connectez-vous sur [script.google.com](https://script.google.com/) avec le compte Google cible (celui qui possède `GVV_backups/`).
2. **Nouveau projet**, renommez-le par exemple « Surveillance backup GVV ».
3. Remplacez le contenu de `Code.gs` par :

```javascript
function checkRecentBackup(folderName, label, alertEmail) {
  var folders = DriveApp.getFoldersByName(folderName);
  if (!folders.hasNext()) {
    MailApp.sendEmail(alertEmail,
      '[GVV] Alerte : dossier de sauvegarde hors-site introuvable',
      'Le dossier "' + folderName + '" est introuvable sur ce Google Drive.');
    return;
  }
  var folder = folders.next();
  var files = folder.getFiles();
  var newest = null;
  while (files.hasNext()) {
    var file = files.next();
    if (newest === null || file.getLastUpdated() > newest) {
      newest = file.getLastUpdated();
    }
  }

  var ageInDays = newest ? (new Date() - newest) / (1000 * 60 * 60 * 24) : Infinity;
  var toleranceDays = 2; // quotidien + 1 jour de marge

  if (ageInDays > toleranceDays) {
    MailApp.sendEmail(alertEmail,
      '[GVV] Alerte : sauvegarde hors-site "' + label + '" manquante',
      'Aucun fichier récent trouvé dans "' + folderName + '".\n' +
      'Dernier fichier : ' + (newest ? newest : 'aucun') + '\n' +
      'Vérifiez la crontab rclone sur le serveur GVV.');
  }
}

function checkAllBackups() {
  var alertEmail = 'club@example.fr'; // à remplacer par l'adresse ou la liste du club
  checkRecentBackup('database', 'base de données', alertEmail);
  checkRecentBackup('media', 'médias', alertEmail);
}
```

4. Remplacez `club@example.fr` par l'adresse (ou liste de diffusion) réelle du club, et ajustez `'database'`/`'media'` si vos noms de sous-dossiers diffèrent de la structure du §2.1.
5. Menu **Déclencheurs** (icône horloge dans la barre latérale) → **Ajouter un déclencheur** :
   - Fonction à exécuter : `checkAllBackups`
   - Source de l'événement : **Basée sur le temps**
   - Type de déclencheur temporel : **Minuteur journalier**, plage horaire de votre choix (idéalement après l'heure du cron rclone)
6. Au premier lancement (test manuel via **Exécuter**), Google demande d'autoriser le script à accéder à Drive et à envoyer des emails en votre nom — passez en revue les permissions demandées et cliquez sur **Autoriser**.

**Tester** : exécutez `checkAllBackups` manuellement (bouton **Exécuter**) après avoir temporairement renommé ou vidé un des dossiers, pour vérifier que l'email d'alerte arrive bien ; remettez le dossier en état ensuite.

---

## 4. Sécurité

* Le jeton d'authentification Google créé par `rclone config` est stocké dans `~/.config/rclone/rclone.conf` sur le serveur — **jamais dans le dépôt Git**. Vérifiez qu'aucune copie de ce fichier n'est committée par erreur.
* Le scope `drive.file` recommandé (§1.2) limite l'accès de rclone aux seuls fichiers qu'il a lui-même créés, même si le jeton était compromis.
* Les URL de ping Healthchecks.io (§3.2) ne donnent accès à rien d'autre qu'à signaler un statut « up/down » — elles ne nécessitent pas de protection particulière, mais évitez de les publier publiquement pour ne pas recevoir de faux positifs générés par un tiers. En cas d'échec, le script joint au ping les 30 dernières lignes du log : elles contiennent des noms de fichiers et des messages d'erreur rclone, jamais le jeton Google.
* Le SMS Free Mobile (§3.2) place votre identifiant et votre clé Free Mobile dans l'URL du webhook, donc chez Healthchecks.io. Cette clé ne permet que d'envoyer des SMS à votre propre numéro, mais elle est confiée à un tiers : en cas de doute, régénérez-la dans l'espace abonné Free Mobile (l'ancienne cesse alors de fonctionner) et mettez à jour l'intégration.
* Le compte Google Apps Script (§3.3) hérite des permissions du compte Google qui l'exécute — utilisez bien le compte cible (`info@planeur-abbeville.fr`), pas un compte personnel.

---

## 5. Dépannage

| Symptôme | Cause probable | Solution |
| :--- | :--- | :--- |
| `rclone: command not found` dans le log cron | `PATH` restreint sous cron | Utiliser le chemin absolu retourné par `which rclone` (§2.2) |
| `Failed to create file system... didn't find section in config file` | `rclone config` a été lancé sous un autre compte (souvent `root`) que celui du cron | Relancer `rclone config` sous le compte propriétaire de la crontab (§1.2) |
| `Failed to copy: googleapi: Error 401` | Jeton d'authentification expiré ou révoqué | Relancer `rclone config reconnect gdrive:` |
| `couldn't fetch token ... oauth2: cannot fetch token: 400 Bad Request` / `"error": "invalid_grant"`, environ une semaine après la mise en place | L'application OAuth est restée en mode « Test » : Google invalide ses jetons au bout de 7 jours | Passer l'application en **Production** (§1.2, étape 7), **puis** relancer `rclone config reconnect gdrive:` sous le compte du cron (§1.3). Sans le passage en Production, la panne revient 7 jours plus tard |
| `Failed to copy: googleapi: Error 403: The user's Drive storage quota has been exceeded` | Quota Google Drive dépassé | Vérifier `rclone about gdrive:`, libérer de l'espace ou vérifier la politique de rétention (§2.4) |
| `ALERTE : envoi hors-site ECHOUE … aucune sauvegarde locale ne correspond` | Le motif ne correspond à aucun fichier de `backups/` | Vérifier que le motif est **entre guillemets** dans la crontab et qu'il correspond aux fichiers réellement présents (`ls backups/`) |
| `ALERTE : envoi hors-site ECHOUE … la sauvegarde locale ne tourne plus ?` | `tools/autobackup.py`/`tools/autobackup_media.py` ne produisent plus de sauvegarde | Vérifier leur cron et leurs logs (`gvv-backup.log` / `gvv-media-backup.log`) |
| Lors de l'autorisation, Google affiche « Accès bloqué : la demande n'est pas valide » et l'adresse contient `urn:ietf:wg:oauth:2.0:oob` | Version de rclone trop ancienne sur le serveur | Mettre rclone à jour (§1.1), puis relancer `rclone config reconnect gdrive:` |
| Le check Healthchecks.io reste « Late »/« Down » alors que le cron semble tourner | Le `curl` de ping échoue silencieusement (pas de sortie internet, coupe-feu) | Tester `curl -v https://hc-ping.com/<uuid>` manuellement depuis le serveur |
| L'email d'alerte Apps Script n'arrive jamais | Le déclencheur temporel n'est pas actif, ou le script n'a pas été autorisé | Vérifier l'onglet **Déclencheurs** et les **Exécutions** dans script.google.com |
| Le dossier distant `GVV_backups/database/` ou `media/` est vide sans raison apparente | Une purge `rclone --min-age 3d delete` lancée sans garde-fou a supprimé la dernière sauvegarde | Vérifier que la crontab utilise bien `tools/rclone_safe_retention.sh` (§2.4) et non `rclone delete` directement |
| `ALERTE : purge ANNULEE … erreur rclone` | Le dossier distant n'a pas pu être lu (jeton expiré, réseau) | Voir les lignes précédentes du log ; jeton expiré : `rclone config reconnect gdrive:` |
| `rclone lsd gdrive:` renvoie une liste vide alors que le Drive contient des fichiers | Comportement normal du scope `drive.file` (voir §1.4) | Aucune action requise, ce n'est pas un dysfonctionnement |

---

## 6. Références

* `doc/prds/sauvegarde_hors_site_prd.md` — exigences produit détaillées
* `doc/design_notes/sauvegarde_hors_site_design.md` — analyse architecturale et choix (GVV vs outils externes)
* `doc/features/Backup.md` — sauvegardes locales existantes (`tools/autobackup.py`, `tools/autobackup_media.py`)
* [Documentation officielle rclone](https://rclone.org/docs/)
* [Documentation rclone pour Google Drive](https://rclone.org/drive/)
* [Créer son propre client ID Google (rclone)](https://rclone.org/drive/#making-your-own-client-id)
* [Google Drive and Google Photos users – ACTION REQUIRED (forum rclone)](https://forum.rclone.org/t/google-drive-and-google-photos-users-action-required/54005) — facturation à venir du client OAuth partagé par défaut
* [Documentation Healthchecks.io](https://healthchecks.io/docs/)
* [Documentation Google Apps Script — Déclencheurs](https://developers.google.com/apps-script/guides/triggers)
