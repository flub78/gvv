# 19. Alarmes

## Vue d'ensemble

GVV peut vous prévenir quand un document arrive à expiration ou a expiré, ou quand un document obligatoire manque (votre certificat médical, l'assurance d'une machine, les visites médicales des élèves…), quand un pilote a volé sans cotisation ou sans licence, quand son expérience récente ne suffit plus (emport passager, 120 jours sans voler) et quand son compte est trop débiteur.

Une seule page, **Mes alarmes**, dans votre espace personnel (menu à votre nom et tableau de bord) : en haut la liste de vos alarmes, en dessous la configuration (bouton **Configuration des alarmes** pour y aller directement).

- **Vos propres alarmes** (vos documents qui expirent, vos documents obligatoires manquants, vos vols sans cotisation ou sans licence, votre expérience récente, votre compte débiteur) sont toujours suivies, sans rien configurer, et ne peuvent pas être désactivées.
- Si vous en avez le droit (CA, bureau, trésorier, instructeur, administrateur…), vous pouvez **suivre en plus les alarmes des autres**, alarme par alarme et section par section.
- Seuls les pilotes **actifs dans la section** sont concernés : un pilote extérieur, ou un pilote dans une section où il n'est pas actif, n'a pas d'alarme pour cette section.
- Vous choisissez comment les recevoir (email, message du jour dans GVV, rappel par SMS) et à quelle fréquence. **Sans canal choisi, vos alarmes restent seulement affichées sur la page.**


---

## 1. Configuration

Ouvrez **Mes alarmes**.

### 1.1 Les alarmes

Il y a **une alarme par type de document** (certificat médical, attestation d'assurance, manuel d'exploitation s'il a une date de validité…), plus une pour les **documents obligatoires manquants** et une pour les **visites médicales des élèves**. Les types de documents sans date d'expiration, et ceux pour lesquels un administrateur a décoché **Alarmes possibles sur ce type**, n'ont pas d'alarme.

S'y ajoutent deux alarmes sur les vols de l'**année en cours** (pilotes extérieurs non contrôlés) :

- **Vols sans cotisation** : le pilote a volé (planeur, avion ou ULM) sans cotisation enregistrée pour l'année. Le bouton **Ouvrir** mène le pilote au paiement de sa cotisation en ligne, et un responsable à la page « Vols sans cotisation ».
- **Vols sans licence** : le pilote a volé dans une section sans la licence exigée par cette section pour l'année. La licence exigée se règle dans la fiche de chaque section (**Licence exigée pour voler**), et sert aussi à la page « Licences par année ». Un remorquage, enregistré dans la section planeur, est couvert par la licence planeur. Une ligne par section. Pour un responsable, **Ouvrir** mène à la page des licences ; le pilote, qui obtient sa licence hors de GVV, n'a pas de bouton.

Ces alarmes sont toujours actives et disparaissent dès que la cotisation ou la licence est enregistrée dans GVV. Un vol planeur relève de la section qui gère les planeurs, un autre vol de la section où il est enregistré. Si les licences ne sont pas saisies dans GVV, tous les pilotes apparaissent dans « Vols sans licence » : désactivez alors cette alarme dans le catalogue (§4.2).

Enfin, quatre alarmes par section (en section planeur, seuls les vols planeur comptent, pas les remorquages) :

- **Emport passager** : pour les pilotes qui ont le rôle **Emport passager** dans la section, au moins 3 atterrissages comme commandant de bord (y compris comme instructeur) dans les 90 derniers jours. L'alarme passe **à venir** quand le 3ᵉ atterrissage le plus récent va sortir de la période de 90 jours (15 jours avant par défaut), puis **active**. Les vols en double commande comme élève ne comptent pas ; en avion et ULM, chaque atterrissage enregistré compte.
- **Remorquage** : pour les pilotes qui ont le rôle **Pilote remorqueur** dans la section planeur, au moins 10 remorqués dans les 12 derniers mois. Un remorqué compte quand le pilote est enregistré comme pilote remorqueur sur le vol planeur. L'alarme passe **à venir** quand le 10ᵉ remorqué le plus récent va sortir de la période de 12 mois (15 jours avant par défaut), puis **active**.
- **120 jours sans voler** : pour les membres actifs de la section (hors élèves) qui ont volé dans la section au cours des 24 derniers mois, au-delà de 120 jours sans vol le prochain vol doit se faire avec un instructeur. L'alarme passe à venir 15 jours avant, puis active ; un vol, même avec instructeur, la fait disparaître.
- **Compte débiteur** : la dette du pilote dans la section dépasse le seuil d'alarme réglé sur la page **Relances**. Le bouton **Ouvrir** mène le pilote à son compte, et un responsable à la page des relances.

Les seuils (3 atterrissages, 90 jours, 10 remorqués, 12 mois, 120 jours, 24 mois) sont fixes ; le délai « à venir » se règle dans le catalogue (§4.2).

Les alarmes sont rangées en quatre catégories, chacune avec sa couleur et son icône (barre à gauche de la ligne, badge dans la liste) : **Documents** (bleu), **Cotisation et licence** (vert), **Comptes** (jaune), **Expérience récente** (bleu clair). La matrice les présente groupées dans cet ordre.

Un simple membre n'a rien à cocher : la page lui rappelle que ses propres alarmes sont toujours suivies.

### 1.2 Suivre les alarmes des autres : la matrice

Un membre qui a des droits de supervision voit une **matrice** : une ligne par alarme, une colonne par section où il a ces droits, une case à cocher à chaque croisement.

| Alarme | Avion | Club |
|---|:---:|:---:|
| Certificat médical | ☑ | — |
| Attestation d'assurance | ☐ | ☐ |
| Documents obligatoires manquants | ☑ | — |

Cocher une case, c'est suivre cette alarme **pour cette section seulement**. Exemple : le vice-président avion coche « Certificat médical » dans la colonne Avion ; il ne verra que les pilotes **actifs dans la section avion** dont le certificat est périmé ou va l'être.

- Pour les documents de pilote, une section couvre les pilotes actifs dans cette section ; pour les machines et les documents de section, ceux rattachés à cette section.
- La colonne **Club** couvre les documents qui ne dépendent d'aucune section (documents du club, machine sans section). Elle n'existe que pour ces alarmes.
- Une case n'apparaît que si vous avez le droit correspondant : administrateurs club pour toutes les sections, membres du CA et du bureau pour leurs sections ; l'alarme des visites médicales des élèves est aussi ouverte aux instructeurs et aux responsables pédagogiques, celles des vols sans cotisation ou sans licence aux trésoriers de leurs sections, celles d'emport passager, de remorquage et des 120 jours sans voler aux instructeurs et responsables pédagogiques. L'alarme de compte débiteur, qui touche aux données financières personnelles, n'est ouverte qu'aux administrateurs club, au bureau et aux trésoriers, pas au CA.

Pour aller plus vite, la case placée **en tête de chaque ligne** (devant le nom de l'alarme) coche ou décoche toute la ligne, celle **sous le nom de chaque colonne** coche ou décoche toute la colonne, et celle **du coin**, à côté de « Alarme », coche ou décoche toute la matrice. Elle apparaît à moitié remplie quand une partie seulement des cases est cochée. Les cases obligatoires ne changent pas. Pensez à **Enregistrer**.

Une case peut porter une icône :

- **cadenas rouge** (obligatoire) : un administrateur l'a rendue obligatoire, vous ne pouvez pas la décocher ;
- **i bleu** : la case a été cochée par un administrateur, ou parce que vous avez un rôle (ex. instructeur de la section) ; survolez l'icône pour voir qui. Vous pouvez la décocher si elle n'est pas obligatoire.

### 1.3 Réception

- **Canaux** : email, message du jour dans GVV, ou les deux. Aucun n'est obligatoire : sans canal, rien n'est envoyé, mais vos alarmes restent affichées sur la page et sur la carte du tableau de bord.
- **Fréquence** :
  - *Immédiate* : un message à chaque nouvelle alarme ;
  - *Hebdomadaire* : une synthèse chaque lundi ;
  - *Mensuelle* : une synthèse le premier lundi du mois.

  La synthèse contient **toutes** vos alarmes encore actives ou qui vont s'activer prochainement (les vôtres et celles que vous suivez), pas seulement les nouvelles. Elle n'est pas envoyée s'il n'y a rien à signaler.
- **Rappel par SMS** : un SMS court avec le lien vers vos alarmes, le lundi (le premier lundi du mois en fréquence mensuelle), s'il y a des alarmes. Il ne contient aucun détail. Cocher cette case vaut consentement. Elle n'est disponible que si votre fiche membre contient un numéro de mobile.

Cliquez sur **Enregistrer** : un message confirme l'enregistrement, ou explique ce qui manque.

---

## 2. Liste des alarmes

La carte **Mes alarmes** du tableau de bord de votre espace personnel affiche dans un badge rouge le nombre de vos alarmes actives, s'il y en a.

En haut de la page, **Alarmes actives et à venir** liste vos alarmes actives (en rouge) et à venir (en orange) : vos propres alarmes (badge **Moi**) et celles des cases que vous avez cochées. Pour chacune : le document, la personne ou la machine concernée, la section, l'échéance, et un bouton **Ouvrir** vers la page où traiter l'alarme (déposer un nouveau document, par exemple). Vous pouvez filtrer par alarme et par section.

Une alarme disparaît d'elle-même quand le problème est réglé : nouveau document valide déposé, ou alerte désactivée sur le document par un administrateur.

---

## 3. Élèves et visite médicale

- Un membre reçoit automatiquement le rôle **Élève** à l'ouverture d'une formation, dans la section du programme (dans toutes les sections si le programme n'est rattaché à aucune). Le rôle lui est retiré à la clôture ou à l'abandon de la formation, sauf s'il a une autre formation en cours dans la section.
- Un administrateur peut aussi attribuer ou retirer le rôle à la main, depuis la gestion des rôles ; un retrait manuel est conservé jusqu'à la prochaine ouverture ou clôture de formation.
- Le **certificat médical** est obligatoire pour les élèves : un élève sans certificat valide apparaît dans les documents obligatoires manquants (c'est une de ses alarmes propres).

Un type de document peut ainsi être rendu obligatoire pour un rôle seulement : champ **Obligatoire seulement pour le rôle** de la fiche du type de document.

---

## 3 bis. Rôle Emport passager

Le rôle **Emport passager** s'attribue par section dans la gestion des rôles, comme Pilote remorqueur. À sa création, il a été attribué en section planeur aux membres qui avaient l'événement « Emport passager ». Seuls les pilotes qui ont ce rôle sont concernés par l'alarme d'emport passager.

---

## 4. Administration (administrateur club)

### 4.1 Configurer les alarmes d'un membre

Sur **Mes alarmes**, un administrateur club dispose d'un sélecteur **Membre**, positionné sur son propre profil. En choisissant un autre membre, il voit sa matrice (avec les cases que ce membre a le droit de cocher), ses réglages de réception et la liste de ses alarmes, et les modifie avec les mêmes règles. Le membre retrouve ensuite ces cases sur sa page, avec le nom de l'administrateur.

### 4.2 Page Administration des alarmes

Accessible depuis le tableau de bord **Administration club** (carte « Administration des alarmes ») :

- **Alarmes actives ou à venir que personne ne reçoit** : ni le membre concerné (faute de canal choisi), ni un abonné. Aucun abonnement n'est créé automatiquement, il faut donc abonner quelqu'un à chaque alarme utile.
- **Abonnements de catégories de membres** : abonner tous les membres ayant un rôle (ex. les instructeurs de la section planeur) à une alarme, éventuellement en la rendant obligatoire. Chez chacun, la case de la section du rôle est cochée (toutes les sections où il a le rôle, et la colonne Club, si aucune section n'est choisie). Un membre qui obtient le rôle est abonné ; un membre qui le perd ne l'est plus.
- **Catalogue des alarmes** : activer ou désactiver une alarme, régler son délai d'anticipation (nombre de jours avant l'échéance où l'alarme passe « à venir »). Le catalogue suit les types de documents : un nouveau type de document à expiration ajoute son alarme. Pour qu'un type n'ait jamais d'alarme, décochez **Alarmes possibles sur ce type** dans sa fiche, ou directement la case de la colonne **Alarmes** dans la liste des types de documents ; la case est cochée par défaut. Dans cette liste, les colonnes Obligatoire, Expiration, Alarmes et Actif sont des cases à cocher : un clic enregistre le changement, confirmé par un message en bas de l'écran.

---

## 5. Installation (administrateur système)

### 5.1 Paramètres dans `program.php`

```php
// Secret protégeant l'URL de déclenchement manuel du scheduler
$config['alarm_scheduler_secret'] = 'CHANGE_ME_IN_PRODUCTION';

// Rappel SMS des abonnés (FALSE = aucun SMS d'alarme, l'envoi est payant)
$config['alarm_sms_enabled'] = TRUE;

// Heure à partir de laquelle le cron horaire traite les alarmes
$config['alarm_scheduler_hour'] = 7;
```

Remplacez le secret par une chaîne aléatoire longue (ex. `openssl rand -hex 32`). Les SMS utilisent la clé Brevo des rappels de réservation (`brevo_sms_api_key`, voir [Rappels de réservations](14_rappels_reservations.md)).

### 5.2 Déclenchement : le cron des rappels de réservation

**Aucune entrée cron dédiée.** Les alarmes sont traitées par le cron horaire des rappels de réservation (`reservation_scheduler cron`, voir [Rappels de réservations](14_rappels_reservations.md) §1.2), à chaque passage à partir de `alarm_scheduler_hour` (7 h par défaut), quand la gestion documentaire est active.

- Les synthèses hebdomadaires et les rappels SMS ne partent **qu'une fois par lundi** (une fois le premier lundi du mois en fréquence mensuelle), au premier passage à partir de 7 h ; les passages suivants n'envoient rien de plus.
- En fréquence immédiate, une nouvelle alarme est envoyée au premier passage qui la détecte, une seule fois par état (à venir, puis active).

Résultat d'un passage du cron : `reservation_scheduler cron: sent=N` puis `alarms: email=N motd=N sms=N failed=N` (`alarms: skipped` avant l'heure ou sans gestion documentaire).

> La ligne de commande demande `$config['uri_protocol'] = 'AUTO';` dans `application/config/config.php` (valeur de `config.example.php`).

Pour un lancement immédiat, sans condition d'heure (tests, rattrapage) :

```bash
XDEBUG_MODE=off /usr/bin/php7.4 /chemin/vers/gvv/index.php alarm_scheduler cron
```

ou l'URL `https://votre-gvv/index.php/alarm_scheduler/run/<alarm_scheduler_secret>` (réponse JSON). Ce lancement respecte les mêmes règles : rien n'est envoyé deux fois pour la même période.

### 5.3 Tests

Si `test_email` et `test_phone` sont renseignés dans `program.php`, tous les emails et SMS d'alarme sont redirigés vers ces adresses.
