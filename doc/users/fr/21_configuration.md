# 21. Configuration

## Vue d'ensemble

La page **Configuration** regroupe les paramètres du club et de l'application dans une page unique, organisée en onglets. Chacun n'y voit que les onglets qui le concernent :

| Profil | Accès |
| :--- | :--- |
| Membre du CA | Onglets ouverts au CA (certains en lecture seule) |
| Administrateur du club (club-admin) | Onglets du CA et onglets d'administration du club |
| Développeur (utilisateur listé dans `dev_users`) | Tous les onglets |

| Onglet | Membre du CA | Administrateur du club |
| :--- | :--- | :--- |
| **Club** | lecture seule | modification |
| **Modules** | lecture seule | modification |
| **Messagerie** | — | modification |
| **Pages publiques** | — | modification |

> La page est livrée progressivement. Les réglages qui n'y figurent pas encore restent dans leurs écrans habituels (Admin > Club > Configuration club, Paiements en ligne > Configuration…).

## Accès

- Tableau de bord > **Administration du club** > carte **Configuration** ;
- ou menu **Admin > Club > Configuration**.

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

L'identité du club ne se modifie plus que dans cet onglet. Les autres réglages de l'ancien écran (calendrier Google, FFVV, message de maintenance…) restent dans **Admin > Club > Configuration club**, qui renvoie vers cet onglet.

## Onglet Modules

Active ou désactive les fonctionnalités du club : vols de découverte, réservations, formations, gestion documentaire, paiements HelloAsso, ULM, tickets, pompes, rapprochements bancaires, offres de formation, newsletter de veille, saisie des vols par les pilotes.

- Le menu est mis à jour dès la page suivante.
- La page Configuration, elle, ne dépend d'aucun module : elle reste accessible pour réactiver un module.
- Si un module actif a besoin d'un réglage qui manque, un avertissement jaune désigne l'onglet à compléter. Par exemple, les vols de découverte et la newsletter envoient des e-mails : en mode SMTP, il leur faut un serveur SMTP.

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
