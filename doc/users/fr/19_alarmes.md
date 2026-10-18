# 19. Alarmes

## Vue d'ensemble

GVV peut vous prévenir quand un document arrive à expiration ou a expiré, ou quand un document obligatoire manque : votre certificat médical, l'assurance d'une machine, les visites médicales des élèves, etc.

Vous choisissez vous-même les alarmes que vous voulez recevoir, comment (email, message du jour dans GVV, rappel par SMS) et à quelle fréquence. **Tant que vous n'avez rien choisi, et qu'aucun administrateur ne l'a fait pour vous, vous ne recevez aucune alarme.**

Deux pages, dans votre espace personnel (menu à votre nom et tableau de bord) :

- **Configuration des alarmes** : choisir ses alarmes, ses canaux et sa fréquence ;
- **Mes alarmes** : la liste des alarmes actives ou à venir auxquelles vous êtes abonné. Cette page n'apparaît qu'une fois que vous avez au moins un abonnement.

> Le Lot 1 couvre les documents archivés (voir [Archivage documentaire](README.md)). Les alarmes sur les vols sans licence ou sans cotisation viendront ensuite.

---

## 1. Choisir ses alarmes

Ouvrez **Configuration des alarmes**.

### 1.1 Les alarmes

Il y a **une alarme par type de document** (certificat médical, attestation d'assurance, manuel d'exploitation s'il a une date de validité…), plus une pour les **documents obligatoires manquants** et une pour les **visites médicales des élèves**. Les types de documents sans date d'expiration, et ceux pour lesquels un administrateur a décoché **Alarmes possibles sur ce type**, n'ont pas d'alarme.

Une alarme couvre à la fois :

- **vos propres documents** (ex. votre certificat médical) ;
- **les documents des autres** membres, des machines, des sections et du club, **si vous avez le droit de les consulter** : administrateurs club pour toutes les sections, membres du CA et du bureau pour leurs sections. L'alarme des visites médicales des élèves est aussi ouverte aux instructeurs et aux responsables pédagogiques.

Un simple membre ne voit donc dans la liste que les alarmes qui peuvent concerner ses propres documents.

Cochez les alarmes que vous voulez recevoir. Certaines peuvent porter un badge :

- **Obligatoire** : un administrateur l'a rendue obligatoire, vous ne pouvez pas la décocher ;
- **Abonné par catégorie** : vous êtes abonné parce que vous avez un rôle (ex. instructeur) ; vous pouvez la décocher si elle n'est pas obligatoire ;
- **Créé par …** : un administrateur l'a cochée pour vous.

### 1.2 Réception

- **Canaux** : email, message du jour dans GVV, ou les deux. Au moins un canal est demandé dès que vous avez une alarme.
- **Fréquence** :
  - *Immédiate* : un message à chaque nouvelle alarme ;
  - *Hebdomadaire* : une synthèse chaque lundi ;
  - *Mensuelle* : une synthèse le premier lundi du mois.

  La synthèse contient **toutes** vos alarmes encore actives ou qui vont s'activer prochainement, pas seulement les nouvelles. Elle n'est pas envoyée s'il n'y a rien à signaler.
- **Rappel par SMS** : un SMS court avec le lien vers vos alarmes, le lundi (le premier lundi du mois en fréquence mensuelle), s'il y a des alarmes. Il ne contient aucun détail. Cocher cette case vaut consentement. Elle n'est disponible que si votre fiche membre contient un numéro de mobile.

Cliquez sur **Enregistrer** : un message confirme l'enregistrement, ou explique ce qui manque.

---

## 2. Consulter ses alarmes

**Mes alarmes** liste les alarmes actives (en rouge) et à venir (en orange) de vos abonnements : le document, la personne ou la machine concernée, l'échéance, et un bouton **Ouvrir** vers la page où traiter l'alarme (déposer un nouveau document, par exemple). Vous pouvez filtrer par alarme et par section.

Une alarme disparaît d'elle-même quand le problème est réglé : nouveau document valide déposé, ou alerte désactivée sur le document par un administrateur.

---

## 3. Élèves et visite médicale

- Un membre reçoit automatiquement le rôle **Élève** à l'ouverture d'une formation, dans la section du programme (dans toutes les sections si le programme n'est rattaché à aucune). Le rôle lui est retiré à la clôture ou à l'abandon de la formation, sauf s'il a une autre formation en cours dans la section.
- Un administrateur peut aussi attribuer ou retirer le rôle à la main, depuis la gestion des rôles ; un retrait manuel est conservé jusqu'à la prochaine ouverture ou clôture de formation.
- Le **certificat médical** est obligatoire pour les élèves : un élève sans certificat valide apparaît dans les documents obligatoires manquants.

Un type de document peut ainsi être rendu obligatoire pour un rôle seulement : champ **Obligatoire seulement pour le rôle** de la fiche du type de document.

---

## 4. Administration (administrateur club)

### 4.1 Configurer les alarmes d'un membre

Sur **Configuration des alarmes**, un administrateur club dispose d'un sélecteur **Membre**, positionné sur son propre profil. En choisissant un autre membre, il voit et modifie sa configuration avec les mêmes règles. Le membre retrouve ensuite ces abonnements sur sa page, avec le nom de l'administrateur.

### 4.2 Page Administration des alarmes

Accessible depuis le tableau de bord **Administration club** (carte « Administration des alarmes ») :

- **Alarmes actives ou à venir sans abonné** : ce que personne ne reçoit. Aucun abonnement n'est créé automatiquement, il faut donc abonner quelqu'un à chaque alarme utile.
- **Abonnements de catégories de membres** : abonner tous les membres ayant un rôle (ex. les instructeurs de la section planeur) à une alarme, éventuellement en la rendant obligatoire. Un membre qui obtient le rôle est abonné ; un membre qui le perd ne l'est plus.
- **Catalogue des alarmes** : activer ou désactiver une alarme, régler son délai d'anticipation (nombre de jours avant l'échéance où l'alarme passe « à venir »). Le catalogue suit les types de documents : un nouveau type de document à expiration ajoute son alarme. Pour qu'un type n'ait jamais d'alarme, décochez **Alarmes possibles sur ce type** dans sa fiche, ou directement la case de la colonne **Alarmes** dans la liste des types de documents ; la case est cochée par défaut. Dans cette liste, les colonnes Obligatoire, Expiration, Alarmes et Actif sont des cases à cocher : un clic enregistre le changement, confirmé par un message en bas de l'écran.

---

## 5. Installation (administrateur système)

### 5.1 Paramètres dans `program.php`

```php
// Secret protégeant l'URL de déclenchement manuel du scheduler
$config['alarm_scheduler_secret'] = 'CHANGE_ME_IN_PRODUCTION';

// Rappel SMS des abonnés (FALSE = aucun SMS d'alarme, l'envoi est payant)
$config['alarm_sms_enabled'] = TRUE;
```

Remplacez le secret par une chaîne aléatoire longue (ex. `openssl rand -hex 32`). Les SMS utilisent la clé Brevo des rappels de réservation (`brevo_sms_api_key`, voir [Rappels de réservations](14_rappels_reservations.md)).

### 5.2 Tâche quotidienne

Les notifications sont calculées une fois par jour. Ajoutez à la crontab du serveur :

```bash
0 7 * * * XDEBUG_MODE=off /usr/bin/php7.4 /chemin/vers/gvv/index.php alarm_scheduler cron >> /var/log/gvv_alarm_scheduler.log 2>&1
```

Résultat attendu d'un lancement manuel : `alarm_scheduler cron: email=N motd=N sms=N failed=N`.

> La ligne de commande demande `$config['uri_protocol'] = 'AUTO';` dans `application/config/config.php` (valeur de `config.example.php`).

Sans accès à la crontab, l'URL suivante déclenche le même traitement et répond en JSON :

```
https://votre-gvv/index.php/alarm_scheduler/run/<alarm_scheduler_secret>
```

Une deuxième exécution le même jour n'envoie rien de plus.

### 5.3 Tests

Si `test_email` et `test_phone` sont renseignés dans `program.php`, tous les emails et SMS d'alarme sont redirigés vers ces adresses.
