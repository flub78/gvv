# Liste des choses à faire

## Bugs

* [] les big_select pour les gros select (800 comptes) ne fonctionnent pas

* [] déplacer les répertoire de travail ailleurs que dans l'arborescence servie par apache.

## Reste à faire
  
* [] Ajout de vrais export excel en sus des csv.

* [~] Support de la gestion de la maintenance,  
  visite périodique, équipements à potentiel, 
  renouvellement d'assurance, etc.

  A tester en fonction du guide dans doc/users/fr/16_maintenance_aeronefs.md

* [] Blocage des réservations si la licence est expirée, ou si le certificat médical est expiré. Si le pilote n'a pas volé depuis 120 jours, il doit indiquer un instructeur (qui recevra un rappel de réservation). Prévoir de pouvoir dispenser certain membres qui volent ailleurs de ce contrôle
   
* [] Message d'erreur de validation, les mettre dans un container qu'on peut fermer comme c'est fait dans la gestion des listes d'email. Unifier l'interface utilisateur des messages d'erreur.

* [] Unifier la configuration globale de l'application avec des onglets ou des pages, un peu comme la procédure d'installation.
Suivant les cas, la procédure éditera des fichiers de configuration ou des enregistrements dans la base de données. 
  * [] Gestion des emails (smtp, etc)
  * [] Gestion des documents (chemin, etc)
  * [] Gestion des vols de découverte
  * [] Gestion des tarifs et produits
  * [] Gestion des types de vol
  * [] Gestion des types d'écriture comptable
  * [] Gestion des types de qualification
  * [] Gestion des types de documents
  * [] Gestion des types d'alerte
  * [] Gestion des types de maintenance.
  
* [] Insérer la liste des documents et qualifications d'un pilote dans sa page membre.
  
* [] Alarmes par email sur les échéances à venir (visite médicale, licence, etc)
  
* [] Informer le trésorier des renouvellement de cotisation par email

* [] Configuration de la facturation

* [] Automatiser le déploiement pour héberger des clubs multiple dans des sous-domaines.

## Dette technique

* [] IA revues de code et refactoring

* [] Définir une charte graphique et l'appliquer partout
  * [] Unifier le style des filtres
  * [] Unifier les erreurs de validation

## Idées et suggestions

* Accès à un carnet de vol ULM pour les membres
* Seconde adresse email
