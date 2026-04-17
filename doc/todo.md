# Liste des choses à faire


## Bugs



* [] **la page de retour après une modification des écritures est discutable**
* [] les big_select pour les gros select (800 comptes) ne fonctionnent pas
* [] Simplifier le workflow des briefings passagers
* [] Des utilisateurs arrivent à générer deux vols de découvertes, pourquoi ?
* [] Toujours des plaintes sur les pages de retour.
* [] Quand une opération pointe sur un compte masqué, il faut montrer le compte.
* [] QUand on supprime une opération depuis un listing de compte on se retrouve sur un formulaire de création d'écriture
* [] Fusion de comptes. Nettoyage 451 467.
  
## Priorités

### Priorité 1
La priorité opérationnelle est de pouvoir remplacer complètement OpenFlyers qui gèrent pour l'instant les réservations et la facturation ULM et avion. 

La synchronisation périodique des opérations OpenFLyers est fastidieuse et source d'erreurs.

Ont peut envisager un déploiement avant que toutes les fonctionnalités d'OpenFLyers ne soient complètement terminées (acceptation de documents, validités) par contre il faut que la facturation et les réservations soient opérationnelles.

Cela implique de:

* terminer le changement du mécanisme d'autorisation
* Vérifier le support de la facturation ULM et avion
* Mettre en place une stratégie de migration

## Reste à faire

* [] Sur créer et continuer, ne pas garder les montants, libellés et numéro de pièce. Discutable, c'est pratique quand on fait des ajustements comptables.
* [] Timeout, il faudrait mieux déconnecter. La section active a été perdue. Merci de sélectionner à nouveau une section.
* [] Vérifier qu'il existe une écriture guidée pour tous les types d'écriture déjà passé dans GVV.
* [] Le gel/dégel des écritures ne retourne pas exactement sur l'endroit ou était l'utilisateur, il faudrait faire mieux.
   
 
* [] Accepter les paiements en centimes. ou pas ?
* [] On perd l'occasion sur les vols de découverte

* [] Facture automatique de hangar
* [] Briefing passager, simplifier le workflow avec le QRCode ???
     
* [] gestion des droits multi sections (wip)
  * [] quelques colonnes inutiles à supprimer
  * [x] donner les droits dans les controllers WIP
  
* [] Interdire les réservations pour les gens qui ne sont pas à jour de leurs cotisations et qui n'ont pas le crédit suffisant sur leur compte.

* [] Message d'erreur de validation, les mettre dans un container qu'on peut fermer comme c'est fait dans la gestion des listes d'email. Unifier l'interface utilisateur des messages d'erreur.

* [ ] Utiliser la nouvelle configuration en base. Cela devrait permettre de désactiver le mécanisme précédant? Il faut peut-être ajouter un type de paramètre de configuration et les présenter de façon hiérarchique

* [] Ajout des types vol de découverte et vol propriétaire dans les réservations. et types VLD.
* [] Forcer la saisie d'un numéro de vol de découverte pour les vols de découverte.
* [] Les briefings passagers doivent mettre à jour la date des vols de découverte.Vérifier.
* [] Ajout des contrôle de compétence des pilotes VLD et REP dans les types de séance
* [] Ajout d'un calendrier des échéances

* [] Alarmes par email sur les échéances à venir (visite médicale, licence, etc)
* [] Informer le trésorier des renouvellement de cotisation par email

* [] Support des messages du jour, qui pourront inclure les alarmes.

* [] Interdire les réservations sans cotisation et sans argent sur le compte.

* [] Vérifier/completer la validation des vols avion, pilote en vol, machine en vol, etc
* [] vols planeur, les vols sont créés même en cas d'erreur sur la facturation (tarif manquant)
  - à vérifier aussi sur les vols avions
  
* [] Gestion de l'inscription, y compris les autorisations parentales (wip)
  
* [] Support de la gestion de la maintenance,                 (PRD à rédiger)
  visite périodique, équipements à potentiel, 
  renouvellement d'assurance, etc.


## Dette technique

* [] Utiliser les flexbox plutôt que les tableaux. [Vues non responsives](./reviews/non_responsive_views.md)
* [] Supprimer les anciens modes de validation de formulaires
* [] IA revues de code et refactoring
* [] Check translations completeness
* [] Définir une charte graphique et l'appliquer partout
  * [] Unifier le style des filtres
  * [] Unifier les erreurs de validation
* [] Supprimer les warnings en mode développement
    passer en mode development, essayer toutes les vues, vérifier les erreurs php à l'écran
    corriger les erreurs.
* [] Verifier qu'il n'y a plus d'erreurs dans les logs pendant les tests phpunit et playwright.

## Idées et suggestions

* restreindre la suppression des membres aux admin. (sera géré correctement avec Laravel cascading)
* Accès à un carnet de vol ULM pour les membres
* Seconde adresse email

* [] Gérer le contexte des filtres par page.
* [] Afficher une marque sur les écritures rapprochés.




