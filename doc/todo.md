# Liste des choses à faire

## Bugs

* [] les big_select pour les gros select (800 comptes) ne fonctionnent pas

* [] déplacer les répertoire de travail ailleurs que dans l'arborescence servie par apache.

## Reste à faire

* AJouter une liste des machines sur laquelle les pilotes sont lachés.
  
* [~] Support de la gestion de la maintenance,  
  visite périodique, équipements à potentiel, 
  renouvellement d'assurance, etc.

  A tester en fonction du guide dans doc/users/fr/16_maintenance_aeronefs.md
  
* [] Blocage des réservations si la licence est expirée, ou si le certificat médical est expiré. Si le pilote n'a pas volé depuis 120 jours, il doit indiquer un instructeur (qui recevra un rappel de réservation). Prévoir de pouvoir dispenser certain membres qui volent ailleurs de ce contrôle
   
* [] Écrans de gestion des listes qui n'en ont pas encore (reliquat de « Unifier la configuration ») : types de vol (tableaux de `program.php`), types d'écriture comptable, types d'alerte.
  
* [] Insérer la liste des documents et qualifications d'un pilote dans sa page membre.
  
* [] Informer le trésorier des renouvellement de cotisation par email

* [] Configuration de la facturation

* [] Automatiser le déploiement pour héberger des clubs multiple dans des sous-domaines.

## Prochain lot sécurité

* [] Cookie de session `Secure` et `HttpOnly` (et `Secure` pour le cookie du jeton CSRF, qui doit rester lisible par le script) : à ajouter avec `SameSite=Lax`, au même endroit.

* [] Clé `encryption_key` : remplacer la valeur d'exemple publique de `config.example.php` par une valeur vide et un commentaire demandant d'en générer une ; vérifier que les serveurs déployés n'utilisent pas la valeur d'exemple.

## Dette technique

* [] IA revues de code et refactoring
* [] Unifier le style des filtres

## Idées et suggestions

Les idées non décidées sont dans [suggestions.md](suggestions.md).
  
