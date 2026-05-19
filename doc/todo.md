# Liste des choses à faire

## Bugs

* [] les big_select pour les gros select (800 comptes) ne fonctionnent pas

* [] Quand une opération pointe sur un compte masqué, il faut montrer le compte.
  
## Reste à faire

* [x] Test approfondi gestion des vols avion et ULM.

* [] Les briefings passagers doivent mettre à jour la date des vols de découverte.Vérifier.

* [] Ajout des contrôle de compétence des pilotes VLD et REP dans les types de séance
   
* [] Génération des certificats de formation et de pilotes VD.
  
* [] Ajout de vrais export excel en sus des csv.

* [] Vérifier qu'il existe une écriture guidée pour tous les types d'écriture déjà passé dans GVV.
   
* [] Configuration des vols de découverte comme les cartes de membres. Editeur de bon de vol de découverte, avec des champs configurables, et génération d'un pdf à partir d'un template. Support des vols de découverte pour tous les clubs.     

* [] Message d'erreur de validation, les mettre dans un container qu'on peut fermer comme c'est fait dans la gestion des listes d'email. Unifier l'interface utilisateur des messages d'erreur.

* [ ] Utiliser la nouvelle configuration en base. Cela devrait permettre de désactiver le mécanisme précédant? Il faut peut-être ajouter un type de paramètre de configuration et les présenter de façon hiérarchique

* [] Ajout d'un calendrier des échéances

* [] Alarmes par email sur les échéances à venir (visite médicale, licence, etc)

* [] Facture automatique de hangar

* [] Informer le trésorier des renouvellement de cotisation par email

* [] Support des messages du jour, qui pourront inclure les alarmes.

* [] Vérifier/completer la validation des vols avion, pilote en vol, machine en vol, etc

* [] vols planeur, les vols sont créés même en cas d'erreur sur la facturation (tarif manquant)
  - à vérifier aussi sur les vols avions
  
* [] Gestion de l'inscription, y compris les autorisations parentales (wip)
  
* [] Support de la gestion de la maintenance,                 (PRD à rédiger)
  visite périodique, équipements à potentiel, 
  renouvellement d'assurance, etc.

* [] Passer à php8

* [] Configuration de la facturation

* [] Automatiser le déploiement pour héberger des clubs multiple dans des sous-domaines.

* [] il faudrait afficher un asterix rouge à côté des champs obligatoires dans les formulaires.

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

* Accès à un carnet de vol ULM pour les membres

* Seconde adresse email

* [] Gérer le contexte des filtres par page.






