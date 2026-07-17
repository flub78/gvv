# Installation

La machine utilisée lors de la rédaction de cette documentation est une machine virtuelle Oracle free tier sur laquelle est installé Ubuntu 22.04 et Hestia Control Panel. 

https://www.oracle.com/cloud/free/

https://hestiacp.com/docs/introduction/getting-started.html.

https://www.youtube.com/watch?v=Hz58Zkke4VE&list=PLSk3zfDlC1f_Up6GBgckMIqLdS_HRjdEy&index=1&t=873s

C'est un environment entièrement gratuit, à vie, sans publicité et sans limite d'utilisation. Il est donc possible de l'utiliser pour tester et déployer GVV.

## Pré-requis

* une machine avec PHP 7.4 ou 8.4 et MySql 5.x (linux, windows ou MacOS, linux recommandé) — GVV a été testé avec ces deux versions de PHP
* un serveur web (Apache ou Nginx)
* un nom de domaine

## Étapes d'installation

La plupart doivent être réalisées avec une connection ssh et le compte gestionnaire sur Hestia.

Certaines étapes se font avec l'interface graphique d'Hestia.

### Vérifiez la version php

GVV a été testé avec PHP 7.4 et PHP 8.4.

    frederic@hcp:~$ php --version
        PHP 8.4.x (cli) (built: ...) ( NTS )
        Copyright (c) The PHP Group
        Zend Engine v4.4.x, Copyright (c) Zend Technologies

Hestia Control Panel allows you to change the PHP version used by the domain.

**By default, the latest version of PHP will be used. To change the PHP version, go to the WEB section - click the Edit domain icon - click the Additional options button - select the desired version (PHP 7.4 or 8.4) in the Backend PHP-FPM template field - click the Save button.**

### Configurer le serveur WEB, Apache ou Nginx, 

C'est déjà fait si vous utilisez Hestia.
Pour référence https://www.digitalocean.com/community/tutorials/how-to-install-linux-apache-mysql-php-lamp-stack-on-ubuntu-18-04. 

Installez une page WEB de test pour vérifier que le serveur web est bien configuré et accessible sur votre domaine.

Installez les certificats SSL.

Installez MySql et créez une base de données.

Notez que pour les utilisateurs de Hestia Control Panel, il est possible de réaliser ces étapes directement depuis l'interface web.

![Création de la base sous Hestia Control Panel](./images/hestia_database.png)

Une fois la base crée elle pourra être accédée avec les identifiants que vous avez choisi.

```https://hcp.mondomaine/phpmyadmin/```

### Configurer l'accès ssh

Vous en aurez besoin pour télécharger GVV et configurer les fichiers de configuration, et surtout pour faire les mise à jour de GVV.

    * Créer les fichier nécessaires dans ~/.ssh
    * Ajouter votre clé publique dans ~/.ssh/authorized_keys
    * Configurer les droits d'accès sur les fichiers et répertoires ~/.ssh
    * Ajouter un shell dans /etc/passwd pour votre utilisateur, par exemple /bin/bash
   
Editer /etc/ssh/sshd_config

```
                # override default of no subsystems
                Subsystem sftp internal-sftp

                # Example of overriding settings on a per-user basis
                #Match User anoncvs
                #       X11Forwarding no
                #       AllowTcpForwarding no
                #       PermitTTY no
                #       ForceCommand cvs server


                # Hestia SFTP Chroot
                Match User sftp_dummy99,admin,planeur
                    ChrootDirectory /srv/jail/%u
                    X11Forwarding no
                    AllowTCPForwarding no
                    ForceCommand internal-sftp -d /home/%u

                Match User frederic,aeroclub
                    ForceCommand none
                    PermitTTY yes
                    AllowTcpForwarding yes
```

```
systemctl reload sshd
```


### Téléchargez GVV

Connectez vous à votre serveur avec SSH et allez dans le répertoire web. Dans mon cas ~/web/gvvg.flub78.net.

Donnez les droits d'écriture sur le répertoire.

git clone https://github.com/flub78/gvv.git public_html

### Créez la base de données

![Créez une base de données](./images/new_database.png)

### Lancer le programme d'installation

```
https://gvvg.flub78.net/install/
```

#### Étape 1 — Prérequis

![Etape 1](./images/install_1.png)

#### Étape 2 — Configuration de la base de données

![Etape 2](./images/install_2.png)

#### Étape 3 — URL de l'application

![Etape 3](./images/install_3.png)

#### Étape 6 — Fonctionnalités

![Etape 6](./images/install_6.png)

#### Étape 7 — Google (optionnel)

Synchronisation du calendrier Google. Peut être passée si non utilisé.

#### Étape 8 — Email Brevo (optionnel)

Configuration SMTP pour l'envoi d'emails via [Brevo](https://www.brevo.com/).

> **Sur un hébergement mutualisé** (OVH, Infomaniak, o2switch…), cette étape peut être **passée** : le serveur mutualisé configure lui-même la fonction PHP `mail()` et les emails partent sans configuration SMTP supplémentaire.
>
> Utilisez Brevo uniquement sur un VPS ou serveur dédié où le port 587 est ouvert.

Si vous configurez Brevo :
1. Créez un compte sur [brevo.com](https://www.brevo.com/)
2. Dans le tableau de bord Brevo : **SMTP & API → Identifiants SMTP**
3. Notez l'**identifiant SMTP** (format `xxxxxxxx@smtp-brevo.com`) et générez une **clé SMTP** (commence par `xsmtpsib-`)
4. Saisissez ces deux valeurs dans le formulaire — le reste (hôte, port, chiffrement) est pré-configuré

#### Étape 9 — Initialisation de la base de données

![Etape 8](./images/install_8.png)

#### Étape 10 — Répertoires & droits

![Etape 9](./images/install_9.png)

#### Étape 11 — Installation terminée

![Etape 10](./images/install_10.png)

### Étapes additionnelles

* Vérifiez la quantité de mémoire disponible pour l'application. La librairie zip utilisée pour les sauvegardes et restauration à besoin de beaucoup de mémoire.

> J'ai résolu mon problème de sauvegarde de la base de donnée qui me retournait systématiquement une erreur 500.
> Dans le fichier /etc/php/<version>/apache2/php.ini (7.4 ou 8.4 selon la version installée), > j'ai passé memory_limit de 128M à 256M
> Je pense que le module zip n'avait pas assez de mémoire disponible à la vue des données à compresser.
> Ça risque d'arriver à tout le monde au fur et à mesure du temps...

#### Configuration des emails

La configuration email est prise en charge par l'étape 8 de l'assistant d'installation.
Si vous avez passé cette étape ou souhaitez la modifier manuellement, créez ou éditez `application/config/email.php` à partir du modèle `email.example.php` :


#### Configuration de la tache cron pour les sauvegardes automatiques

L'objectif est d'automatiser une sauvegarde quotidienne de la base (et optionnellement des médias) sans passer par l'interface web.

1. Créez un script shell dédié sur le serveur, par exemple `/usr/local/bin/gvv_backup.sh` :

```bash
#!/usr/bin/env bash
set -euo pipefail

# === Paramètres à adapter ===
DB_HOST="127.0.0.1"
DB_NAME="gvv"
DB_USER="gvv_user"
DB_PASS="change_me"
BACKUP_DIR="/home/www/gvv/backups"
WEBROOT="/home/www/gvv"

DATE="$(date +%Y%m%d_%H%M%S)"
FILE_PREFIX="${DB_NAME}_backup_${DATE}"
SQL_FILE="${BACKUP_DIR}/${FILE_PREFIX}.sql"
ZIP_FILE="${BACKUP_DIR}/${FILE_PREFIX}.zip"
MEDIA_FILE="${BACKUP_DIR}/uploads_${DATE}.tar.gz"

mkdir -p "${BACKUP_DIR}"

# Sauvegarde SQL
/usr/bin/mysqldump \
    --host="${DB_HOST}" \
    --user="${DB_USER}" \
    --password="${DB_PASS}" \
    --single-transaction \
    --routines \
    --triggers \
    "${DB_NAME}" > "${SQL_FILE}"

# Compression SQL
/usr/bin/zip -j "${ZIP_FILE}" "${SQL_FILE}"
rm -f "${SQL_FILE}"

# Sauvegarde des médias (optionnel mais recommandé)
/usr/bin/tar \
    --exclude='restore' \
    --exclude='attachments_backup' \
    -czf "${MEDIA_FILE}" \
    -C "${WEBROOT}" uploads

# Rétention: conserve 30 jours
/usr/bin/find "${BACKUP_DIR}" -type f -name '*.zip' -mtime +30 -delete
/usr/bin/find "${BACKUP_DIR}" -type f -name '*.tar.gz' -mtime +30 -delete
```

2. Donnez les droits d'exécution :

```bash
sudo chmod 750 /usr/local/bin/gvv_backup.sh
```

3. Testez le script manuellement avant de planifier :

```bash
sudo /usr/local/bin/gvv_backup.sh
ls -lh /home/www/gvv/backups
```

4. Éditez la crontab de l'utilisateur qui exécute le serveur web (ou d'un utilisateur technique dédié) :

```bash
crontab -e
```

5. Ajoutez une exécution quotidienne (exemple 02:15) avec journal dédié :

```cron
15 2 * * * /usr/local/bin/gvv_backup.sh >> /var/log/gvv-backup.log 2>&1
```

6. Vérifiez que le service cron est actif :

```bash
sudo systemctl status cron
```

> Recommandation sécurité : évitez de laisser le mot de passe MySQL en clair dans un script. En production, préférez un fichier de credentials MySQL (`~/.my.cnf`) lisible uniquement par l'utilisateur d'exécution.


#### Vérification du bon fonctionnement

Après configuration, validez les points suivants :

1. La tâche est bien enregistrée :

```bash
crontab -l
```

2. Le log se met à jour à l'heure prévue :

```bash
tail -n 50 /var/log/gvv-backup.log
```

3. De nouveaux fichiers apparaissent dans le répertoire de sauvegarde :

```bash
ls -ltr /home/www/gvv/backups | tail -n 10
```

4. L'archive SQL est lisible :

```bash
unzip -l /home/www/gvv/backups/<fichier>.zip
```

5. La sauvegarde média est lisible :

```bash
tar -tzf /home/www/gvv/backups/<fichier>.tar.gz | head -n 20
```

6. (Recommandé) Faites un test de restauration sur une base de test au moins une fois :

```bash
unzip -p /home/www/gvv/backups/<fichier>.zip | mysql -u <user> -p <base_test>
```


#### Troubleshooting (cron et sauvegardes)

Symptôme: aucun nouveau fichier dans `backups`.
Cause probable: la crontab n'est pas installée pour le bon utilisateur, ou cron n'est pas démarré.
Vérifications:

```bash
crontab -l
sudo systemctl status cron
```

Symptôme: `mysqldump: command not found`.
Cause probable: chemin binaire différent selon la distribution.
Correctif:

```bash
which mysqldump
```

Puis remplacez `/usr/bin/mysqldump` dans le script par le chemin trouvé.

Symptôme: `Access denied for user` lors du dump.
Cause probable: identifiants MySQL incorrects ou droits insuffisants.
Correctif: testez la connexion avec les mêmes identifiants et accordez les droits `SELECT`, `LOCK TABLES`, `SHOW VIEW`, `TRIGGER` selon votre configuration.

Symptôme: le job cron tourne manuellement mais échoue en automatique.
Cause probable: variables d'environnement minimales dans cron (PATH, droits, répertoire courant).
Correctif: utilisez des chemins absolus (comme dans l'exemple), et journalisez `stdout/stderr` dans `/var/log/gvv-backup.log`.

Symptôme: erreurs `No space left on device` ou backups incomplètes.
Cause probable: disque plein ou rétention absente.
Correctif:

```bash
df -h
du -sh /home/www/gvv/backups
```

Activez la rotation/rétention (exemple fourni: suppression au-delà de 30 jours).

Symptôme: erreur 500 lors d'une sauvegarde via l'interface web.
Cause probable: mémoire PHP insuffisante pour la compression.
Correctif: augmentez `memory_limit` dans `/etc/php/<version>/apache2/php.ini` (7.4 ou 8.4 selon la version installée ; exemple courant: `256M`) puis redémarrez Apache.



#### Configuration de HelloAsso

### Premiers pas

![Login](./images/login.png)

Si vous voyez la page suivante, c'est que GVV est correctement installé.

![Home](./images/home.png)

Un fois installé, je vous recommande de tester, tester et tester.

Créez, modifiez et supprimez:
* des comptes
* des machines
* les produits de facturation
* des vols
* etc.

Ensuite, si vous êtes déjà un club utilisateur, sauvegarder votre base de données et restaurez la sur votre nouvelle machine.