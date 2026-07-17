# PRD — Messages du Jour

Date : 9 février 2026

## Contexte
Les clubs souhaitent communiquer rapidement des informations importantes aux membres (rappels, consignes, événements). Le message du jour permet une diffusion visible à l’ouverture de l’application.

## Objectifs
- Permettre aux administrateurs de créer et gérer des messages destinés aux utilisateurs.
- Offrir une visibilité claire aux utilisateurs via un une section repliable sur le dashboard d'accueil.
- Permettre aux utilisateurs de masquer un message ou l’ensemble des messages. (marquer lu et n'afficher que les non lus
- Les messages seront affichés dans une fenêtre de la page d’accueil. Il serra possible de replier cette fenêtre. L'état replié/déplié sera persistent. Si de nouveau messages sont à destination de l'utilisateur, la fenêtre sera dépliée.
- Les messages pourront être à destination d'une liste de diffusion. On ré-utilisera les liste d'email, même ces messages ne sont pas des emails. Les messages pourront être filtrés par liste de diffusion. Les messages pourront également être à destination d'un utilisateur unique.
- Les destinataires des messages pourront répondre aux messages. Dans ce cas les réponses seront visibles après chaque message. Les administrateurs pourront voir les réponses et y répondre. Les réponses seront visibles par tous les destinataires du message initial ainsi que par l'éditeur du message initial.

## Non-objectifs
- Remplacer les communications officielles par email.
- Fournir un fil d’actualité complet.

## Portée
- Gestion CRUD des messages par les administrateurs.
- Affichage des messages actifs dans une section repliable sur le dashboard d'accueil.
- Page de consultation listant tous les messages.
- Rendu Markdown contrôle des messages avec support d'images référencées.

## Personae & rôles
- Administrateur : crée, modifie, supprime et planifie des messages.
- Utilisateur : consulte et masque des messages.
- GVV : génère des messages d'alarmes à destination d'un ou plusieurs utilisateurs. Ces messages sont créés automatiquement par le système et peuvent être modifiés par les administrateurs.

## Parcours clés
1. Un administrateur crée un message avec une période de diffusion.
2. Un utilisateur voit le message dans la section repliable du dashboard d'accueil.
3. L’utilisateur masque un message individuel.
4. L’utilisateur masque tous les messages.
5. L’utilisateur ferme la section repliable et consulte la page dédiée listant les messages.

## Exigences fonctionnelles
### EF1 — CRUD administrateur
- Les administrateurs peuvent créer, modifier et supprimer des messages.
- Un message contient :
  - Titre (optionnel si souhaité).
  - Contenu en Markdown (source de reference), rendu en HTML securise a l'affichage.
  - Date de début d’affichage.
  - Date de fin d’affichage.
  - Niveau "Urgent, Important, Info, Alerte" (optionnel).
  - Type de destinataires (optionnel, ex. tous les utilisateurs, basé sur la gestion des rôles).

### EF1bis — Rendu Markdown
- Le contenu des messages est saisi en Markdown et stocke tel quel.
- Le rendu HTML est produit a l'affichage via un pipeline de sanitation (pas de HTML arbitraire execute).
- Les elements Markdown supportes incluent au minimum: titres, listes, liens, gras/italique, citations, tableaux simples, images.
- Tout HTML dangereux ou non autorise est supprime avant rendu.

### EF2 — Période d’affichage
- Un message est affiché uniquement si la date actuelle est comprise entre la date de début et la date de fin.
- Les messages hors période ne sont pas affichés dans la section repliable du dashboard d'accueil.

### EF3 — Section repliable du dashboard d'accueil
- À l’ouverture de l’application, les messages actifs sont affichés dans une section repliable du dashboard d'accueil.
- La section est repliée si l'utilsateur n'a pas de message urgents ou importants qu'il n'a pas encore consulté. Si il y a des messages urgents ou importants non consultés, la section est dépliée par défaut.
- L’utilisateur peut :
  - Masquer un message individuellement.
  - Masquer tous les messages.
  - Indiquer qu'il a pris connaissance du message (optionnel).
- Les choix de masquage sont persistants.
- La section de message est une liste scrollable. Elle peut-être trié par date de début d’affichage ou par niveau d’importance. Chaque message peut être développé ou réduit (accordéon) pour consulter le contenu complet.

### EF4 — Page “Tous mes messages”
- Une page dédiée liste tous les messages (actifs et passés selon la politique définie).
- Chaque message peut être développé ou réduit (accordéon).
- Depuis cette page, l’utilisateur peut consulter les détails complets des messages qui lui sont adressés.

### EF5 — Accès
- Seuls les administrateurs peuvent gérer les messages.
- Tous les utilisateurs autorisés peuvent consulter les messages.

### EF6 — Images referencees dans les messages
- Les administrateurs peuvent televerser des images depuis l'interface d'edition du message.
- Le televersement retourne une URL interne stable pouvant etre referencee dans le Markdown via `![alt](url)`.
- Les images ne sont accessibles qu'aux utilisateurs authentifies autorises a voir les messages.
- Les formats acceptes sont limites (PNG, JPG, WEBP) et la taille maximale est configurable.
- Les metadonnees de fichier (nom d'origine, type MIME, taille, auteur, dates) sont journalisees.
- Lorsqu'une image est supprimee ou remplacee, le systeme garantit un comportement explicite (erreur lisible ou image de remplacement).

#### Conception proposee pour le chargement d'images
- Stockage fichier: repertoire dedie `uploads/motd/` avec nommage serveur non predictible.
- Service d'acces: endpoint applicatif `motd/media/{id}` qui verifie les droits puis sert le binaire.
- Reference dans le contenu: insertion automatique du lien Markdown apres upload (copie en presse-papiers ou insertion curseur).
- Integrite: hash SHA-256 du fichier stocke pour dedoublonnage optionnel et verification.
- Traçabilite: liaison `message_id` <-> media pour faciliter nettoyage et audit.

## Exigences non fonctionnelles
- Performance : la section repliable doit s’afficher rapidement à l’ouverture.
- Utilisabilité : la suppression ou le masquage doit être explicite et visible.
- Compatibilité : rendu correct sur navigateur moderne.
- Responsive : la section repliable et la page dédiée doivent être utilisables sur mobile et tablette.
- Le rendu Markdown + images doit rester performant sur dashboard (miniatures optimisees, chargement paresseux si necessaire).

## Contraintes & dépendances
- Les messages doivent respecter les politiques de contenu (pas de HTML arbitraire).
- Le rendu Markdown doit être compatible avec l’existant (si déjà utilisé).
- Les images televersees doivent etre validees cote serveur (MIME reel, extension, taille) et protegees contre l'execution de contenu actif.
- Les URL d'images doivent rester internes a l'application (pas d'injection d'URL non maitrisee par defaut).

## Mesures de succès
- 80 % des utilisateurs voient au moins un message par semaine.
- Diminution des demandes répétitives liées aux consignes simples.

## Questions ouvertes
- Le titre est-il obligatoire ?
- Les messages expirés doivent-ils rester visibles sur la page dédiée ?
- Y a-t-il un ordre de priorité (ex. messages urgents en premier) ?
- Quelle limite de taille/frequence de televersement image retenir par club et par message ?
