# 21. Configuration

## Vue d'ensemble

La page **Configuration** regroupe les paramètres du club et de l'application dans une page unique, organisée en onglets. Chacun n'y voit que les onglets qui le concernent :

| Profil | Accès |
| :--- | :--- |
| Membre du CA | Onglets ouverts au CA (certains en lecture seule) |
| Administrateur du club (club-admin) | Onglets du CA et onglets d'administration du club |
| Développeur (utilisateur listé dans `dev_users`) | Tous les onglets |

> La page est livrée progressivement. Pour l'instant, elle contient l'onglet **Pages publiques**, réservé à l'administrateur du club. Les autres réglages restent dans leurs écrans habituels (Admin > Club).

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

Tant que rien n'est saisi, GVV publie un **texte générique** en français, anglais ou néerlandais selon la langue du site. Ce texte reprend automatiquement l'identité du club, saisie dans **Admin > Club > Configuration club** :
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
