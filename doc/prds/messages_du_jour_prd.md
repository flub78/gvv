# PRD — Messages du Jour

Date : 9 février 2026

## Contexte
Les clubs souhaitent communiquer rapidement des informations importantes aux membres (rappels, consignes, événements). Le message du jour permet une diffusion visible à l’ouverture de l’application.

## Objectifs
- Permettre aux administrateurs de créer et gérer des messages du jour.
- Offrir une visibilité claire aux utilisateurs via un une section repliable sur le dashboard d'accueil.
- Permettre aux utilisateurs de masquer un message ou l’ensemble des messages.
- Fournir une page dédiée listant tous les messages.

## Non-objectifs
- Remplacer les communications officielles par email.
- Fournir un fil d’actualité complet.

## Portée
- Gestion CRUD des messages par les administrateurs.
- Affichage des messages actifs dans une section repliable sur le dashboard d'accueil.
- Page de consultation listant tous les messages.

## Personae & rôles
- Administrateur : crée, modifie, supprime et planifie des messages.
- Utilisateur : consulte et masque des messages.

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
  - Contenu en texte simple ou Markdown.
  - Date de début d’affichage.
  - Date de fin d’affichage.
  - Niveau "Urgent, Important, Info" (optionnel).
  - Type de destinataires (optionnel, ex. tous les utilisateurs, basé sur la gestion des rôles).

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

## Exigences non fonctionnelles
- Performance : la section repliable doit s’afficher rapidement à l’ouverture.
- Utilisabilité : la suppression ou le masquage doit être explicite et visible.
- Compatibilité : rendu correct sur navigateur moderne.
- Responsive : la section repliable et la page dédiée doivent être utilisables sur mobile et tablette.

## Contraintes & dépendances
- Les messages doivent respecter les politiques de contenu (pas de HTML arbitraire).
- Le rendu Markdown doit être compatible avec l’existant (si déjà utilisé).

## Mesures de succès
- 80 % des utilisateurs voient au moins un message par semaine.
- Diminution des demandes répétitives liées aux consignes simples.

## Questions ouvertes
- Le titre est-il obligatoire ?
- Les messages expirés doivent-ils rester visibles sur la page dédiée ?
- Y a-t-il un ordre de priorité (ex. messages urgents en premier) ?
