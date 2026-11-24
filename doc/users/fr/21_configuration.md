# 21. Configuration

## Vue d'ensemble

La page **Configuration** regroupe les paramètres du club et de l'application dans une page unique, organisée en onglets. Chacun n'y voit que les onglets qui le concernent :

| Profil | Accès |
| :--- | :--- |
| Membre du CA | Onglets ouverts au CA (certains en lecture seule) |
| Administrateur du club (club-admin) | Onglets du CA et onglets d'administration du club |
| Développeur (utilisateur listé dans `dev_users`) | Tous les onglets |

| Onglet | Membre du CA | Administrateur du club | Développeur |
| :--- | :--- | :--- | :--- |
| **Club** | lecture seule | modification | modification |
| **Apparence** | — | modification | modification |
| **Modules** | lecture seule | modification | modification |
| **Vols de découverte** | modification | modification | modification |
| **Finances** | lecture seule | modification | modification |
| **Paiements en ligne** | — | accès à l'écran HelloAsso | idem |
| **Documents et cartes** | — | accès aux écrans | idem |
| **Pages publiques** | — | modification | modification |
| **Référentiels** | — | modification, accès aux listes | idem |
| **Messagerie** | — | modification | modification |
| **Newsletter** | — | modification, tests | modification, tests |
| **Système** | — | — | modification |

Certains onglets ne contiennent pas de réglages mais des **liens** vers des écrans spécialisés (réglages HelloAsso par section, mise en page des cartes de membre, listes de référence…) : le bouton **Ouvrir** y mène. Ces écrans n'ont pas changé.

Les anciens écrans « Configuration club », « Configuration » (liste clé/valeur) et « Newsletter — Configuration » ont été remplacés : leurs adresses mènent à l'onglet correspondant.

## Accès

- Tableau de bord > **Administration du club** > carte **Configuration** ;
- ou menu **Admin > Club > Configuration** ;
- pour un développeur, menu **Dev > Configuration — Système**.

Si vous n'avez accès à aucun onglet, la carte et l'entrée de menu n'apparaissent pas. Une adresse d'onglet ouverte sans les droits nécessaires affiche un message de refus.

## Utilisation

- Chaque onglet a un **état** :
  - pastille verte : complet ;
  - pastille rouge : un paramètre obligatoire manque ;
  - pastille grise : optionnel, rien n'est configuré.
- Sous chaque paramètre, une mention indique s'il garde la **valeur par défaut de GVV**, ou bien la date et l'auteur de sa dernière modification.
- **Enregistrer** n'enregistre que l'onglet affiché. Un message liste les paramètres enregistrés.
- Une valeur refusée est signalée à côté du champ, avec le motif. Les autres saisies sont conservées et rien n'est perdu.
- Si vous changez d'onglet alors que des modifications ne sont pas enregistrées, une confirmation vous est demandée.
- L'adresse de la page désigne l'onglet affiché : on peut la copier pour envoyer un lien direct.
- Un onglet en **lecture seule** affiche les valeurs, sans champ ni bouton d'enregistrement, avec la mention « Cet onglet est en lecture seule pour vous ».
- Un champ marqué d'une étoile rouge est obligatoire.

### Mots de passe et clés

Un mot de passe enregistré n'est jamais réaffiché : le champ est vide, et une pastille indique **Valeur définie** ou **Aucune valeur**.
- Laisser le champ vide et enregistrer **conserve** le mot de passe actuel.
- Pour le remplacer, saisissez le nouveau puis enregistrez.
- Pour l'effacer, cochez **Effacer la valeur enregistrée** puis enregistrez.

### Historique et retour arrière

Le bouton **Historique**, en haut de chaque onglet que vous pouvez modifier, liste les modifications de l'onglet, les plus récentes d'abord : date, auteur, paramètre, ancienne et nouvelle valeur.

Le bouton **Revenir** d'une ligne remet la valeur d'avant cette modification. Ce retour :
- est vérifié comme une saisie : une valeur devenue invalide est refusée, avec le motif ;
- apparaît à son tour dans l'historique ;
- n'est pas possible pour un mot de passe, dont la valeur n'est jamais conservée.

## Onglet Club

Identité du club : sigle, nom, code d'identification, adresse, téléphone, e-mail, site web, logo et **contact RGPD**. Ces informations sont reprises dans les documents, les e-mails et les pages publiques.

- Le membre du CA les consulte ; seul l'administrateur du club les modifie.
- **Logo** : choisissez une image PNG, JPEG ou GIF de 500 Ko au plus, puis enregistrez. L'ancien logo reste sur le serveur : on peut y revenir depuis l'historique.

L'identité du club ne se modifie plus que dans cet onglet. L'onglet contient aussi le **calendrier Google** des présences (utile seulement si le calendrier des présences est synchronisé avec Google ; sinon il est enregistré dans la base de GVV) et l'adresse de la **planche automatique**.

## Onglet Apparence

Titre de l'application (affiché dans l'onglet du navigateur), et couleur de la bannière (couleur CSS, par exemple `#1f6f8b` ou `darkgreen` ; vide : vert). Le changement se voit dès la page suivante.

## Onglet Modules

Active ou désactive les fonctionnalités du club : vols de découverte, réservations, formations, gestion documentaire, paiements HelloAsso, ULM, tickets, pompes, rapprochements bancaires, offres de formation, newsletter de veille, saisie des vols par les pilotes.

- Le menu est mis à jour dès la page suivante.
- La page Configuration, elle, ne dépend d'aucun module : elle reste accessible pour réactiver un module.
- Si un module actif a besoin d'un réglage qui manque, un avertissement jaune désigne l'onglet à compléter. Par exemple, les vols de découverte et la newsletter envoient des e-mails : en mode SMTP, il leur faut un serveur SMTP.

## Onglet Vols de découverte

Ouvert au **conseil d'administration**, qui peut le modifier :
- adresse, nom et signature de l'expéditeur des e-mails des bons ;
- contacts (nom et téléphone) par type d'appareil : avion, planeur, ULM, indiqués sur les bons ;
- nouveau moteur PDF des bons (réservé à l'administrateur du club), dont les modèles se règlent dans l'écran **Apparence des bons**, accessible depuis l'onglet.

## Onglet Finances

Le conseil d'administration le consulte ; l'administrateur du club le modifie.
- **Cotisations et licences** : compte de recette proposé à la saisie d'une cotisation, produit des licences fédérales, identifiant et mot de passe fédéraux, export Gesasso.
- **Relances** : seuils d'alarme et critique, en euros.
- **Facturation** : contrôle du solde à la réservation, module de facturation du club (à ne changer qu'en connaissance de cause).
- **Règles de facturation du serveur** : payeur autre que le pilote, partage des frais, remorquage au 100e. Elles sont affichées en lecture seule : elles se règlent dans `application/config/facturation.php`, sur le serveur.

Si la valeur enregistrée d'une liste n'en fait pas partie (par exemple un produit d'une autre section), elle reste affichée avec la mention « valeur actuelle, absente de la liste ».

## Onglets Paiements en ligne et Documents et cartes

Ils présentent les écrans existants :
- **Réglages HelloAsso par section** (identifiants, comptes, adresse de notification, test de connexion) ;
- **Cartes de membre** (fonds et mise en page par année), **Types de documents** (si la gestion documentaire est active), **Formulaires** et **Réglages globaux des formulaires** (valeurs communes, tampon de l'association).

## Onglet Référentiels

- **Aérodrome par défaut** : code OACI en majuscules (par exemple `LFOI`), proposé à la saisie des vols avion, ULM et découverte.
- Liens vers les listes de référence : terrains, types de qualifications et d'événements, plan comptable, tarifs et produits, types de tickets (si actifs), types de séances de formation (si actives), programmes de maintenance.

## Onglet Messagerie

Réglages d'envoi des e-mails :

| Paramètre | Usage |
| :--- | :--- |
| Mode d'envoi | « mail (PHP) », valeur par défaut, sur un hébergement mutualisé ; « SMTP » pour passer par un serveur d'envoi (Brevo…) |
| Serveur, port, chiffrement | En mode SMTP : par exemple `smtp-relay.brevo.com`, `587`, TLS |
| Identifiant et mot de passe SMTP | Pour Brevo : tableau de bord Brevo → SMTP & API |
| Redirection des e-mails / des SMS (essais) | Si renseignée, tout ce que GVV envoie part vers cette adresse ou ce numéro. **À laisser vide en production.** |

**Envoi d'un e-mail de test** : en bas de l'onglet, saisissez un destinataire (votre adresse est proposée) et cliquez sur **Envoyer**. Le résultat s'affiche en haut de l'onglet ; en cas d'échec, « Détails techniques » donne le dialogue avec le serveur.
- Le test utilise les réglages **enregistrés** : enregistrez vos modifications avant de tester.
- La redirection des e-mails d'essai ne s'applique pas à ce test.

## Onglet Pages publiques

Ces pages sont consultables **sans connexion** :

| Page | Adresse | Lien |
| :--- | :--- | :--- |
| Règles de confidentialité | `…/legal/confidentialite` | en bas de la page de connexion |
| Conditions d'utilisation | `…/legal/conditions` | en bas de la page de connexion |
| Newsletters (si la newsletter de veille est activée) | `…/newsletter` | adresse à communiquer |

L'onglet affiche l'adresse de chaque page, avec deux boutons :
- **Copier** copie l'adresse ;
- **Aperçu** ouvre la page publiée.

### Texte générique ou texte du club

Tant que rien n'est saisi, GVV publie un **texte générique** en français, anglais ou néerlandais selon la langue du site. Ce texte reprend automatiquement l'identité du club, saisie dans l'onglet **Club** :
- nom, adresse, e-mail, téléphone et site ;
- **Contact RGPD** : nom ou fonction de la personne à qui adresser les demandes. S'il est vide, la page renvoie vers « le président de l'association ».

Le texte générique mentionne HelloAsso seulement si le paiement en ligne est activé.

Pour publier un **texte propre au club**, saisissez-le au format Markdown dans la langue voulue (onglets Français / English / Nederlands), puis **Enregistrez**. Vous pouvez y insérer les informations du club, qui restent ainsi à jour quand l'identité du club change :

`{nom_club}`, `{adresse_club}`, `{cp_club}`, `{ville_club}`, `{email_club}`, `{tel_club}`, `{url_club}`, `{contact_rgpd}`

Points à connaître :
- Une langue non saisie affiche le texte français du club. S'il n'y en a pas, elle affiche le texte générique.
- **Videz le champ** et enregistrez pour revenir au texte générique.
- Le texte générique est consultable sous chaque champ (« Texte générique de GVV ») et peut servir de point de départ.
- Le texte propre au club est le bon endroit pour les mentions que GVV ne peut pas connaître :
  - prestataires techniques, par exemple le stockage des sauvegardes sur Google Drive ;
  - organismes destinataires des données ;
  - durées de conservation propres au club.

### Utilisation pour Google (sauvegarde hors-site)

Google exige une **page d'accueil** et des **règles de confidentialité** publiques pour publier l'application OAuth utilisée par rclone (voir [17. Sauvegarde hors-site](17_sauvegarde_hors_site.md), §1.2). Utilisez :
- comme page d'accueil : la page de connexion de GVV (`…/auth/login`), qui contient le lien vers les règles ;
- comme règles de confidentialité : `…/legal/confidentialite` ;
- comme conditions d'utilisation (facultatif) : `…/legal/conditions`.

## Onglet Newsletter

Ouvert à l'administrateur du club, pour la newsletter de veille (voir [20. Newsletter de veille](20_newsletter_veille.md)) :
- **Boîte de collecte (IMAP)** où arrivent les réponses des relecteurs ;
- **Envoi (SMTP)** : serveur, compte et expéditeur ; serveur vide = réglages de l'onglet Messagerie ;
- **Relecture** : relecteurs, délai (2 jours par défaut), sujet et texte de la demande de relecture ;
- **Stockage et alertes**, **Intelligence artificielle** (clé d'API, modèle).

Les mots de passe et la clé d'API ne sont jamais réaffichés. En haut de l'onglet, les boutons **Ouvrir** mènent aux tests d'envoi, de la boîte IMAP et de l'IA, qui utilisent les réglages enregistrés.

## Onglet Système

Réservé aux développeurs (utilisateurs listés dans `dev_users`) :
- **Accès et maintenance** : accès réservé aux administrateurs, message affiché sur la page de connexion pendant la maintenance, liste des développeurs. On ne peut pas se retirer soi-même de cette liste.
- **SMS**, **ordonnanceurs** (secrets des adresses de déclenchement, heure de traitement des alarmes), **intégration Google**.
- Lien vers le **test d'envoi de SMS**, qui utilise les réglages enregistrés.
- **Installation (lecture seule)** : adresse de base, base de données, version, migration (actuelle / attendue). Ces réglages ne se modifient que sur le serveur.
- **Autres clés de la table de configuration** : réglages enregistrés par des écrans spécialisés (mise en page des cartes…). Le crayon ouvre l'ancien éditeur clé/valeur.

> Une modification faite directement dans un fichier de configuration sur le serveur peut mettre quelques secondes à être prise en compte (cache de PHP). Celles faites dans cette page le sont aussitôt.
