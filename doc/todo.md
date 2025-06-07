# Liste des choses à faire

Il existe un projet de ré-écriture complet de GVV, néanmoins c'est un travail lour, et le GVV actuel doit être maintenu pour s'adapter aux nouveaux besoins des utilisateurs.


## Fait récemment

* [x] Fix the photo upload                                  
* [x] Documentation de la sauvegarde automatique            
* [x] Adapt the membre view to show the picture             
* [x] Analyse and document auto backup mechanism             2j
* [x] Implement attachments                                  5j
* [x] les écritures devraient accepter les dates en 1/1/2025
* [x] les écritures devraient accepter les montants avec virgule comme séparateur
* [x] retour vers le compte emploi après passage des écritures
* [x] les heures de début et de fin ne sont pas rechargés sur les vols avion
* [x] avec créer et continuer, afficher le message dans la page plutôt que comme popup, Les popups doivent être réserver pour les demandes de confirmation ou action utilisateur 
* [x] correction du nombre d'éléments du bandeau bas des journaux lors de la selection d'une section
* [x] A propos ne fonctionne plus en production HTTP ERROR 500
* [x] Le sélecteur de compte n'est pas filtré par section..
* [x] ne pas créer deux comptes 411 depuis la section générale
* [x] verifier la synchro HEVA
* [x] vérifier qu'il n'y a pas d'écriture entre deux sections dans la validation
* [x] bilan et clôture par section
  * [x] bilan par section
  * [x] clôture par section
* [x] Vue compte, autoriser les vues sur plusieurs années
* [*] Compte d'exploitation (tableau de bord comptable)

## Reste à faire

* [] synchro avec OpenFlyers
* [] gestion des vols de découverte
  * [x] Fonctionnalité minimum
  * [ ] filtre
  * [ ] présentation par année
  * [ ] export CSV et Pdf
  * [ ] Import des informations planeur
* [] Gérer la configuration dans la base.
* [] Vérifier le cadrage à droite pour tous les chiffres

* [] la page de retour après une modification des écritures est discutable
* [] Problèmes de CSS avec les boutons du calendrier et ceux des sélecteurs de date
* [] Attachements lors de la création d'écriture
  * [] tester les téléchargements avec espace (upload->do_upload)
  * [] Les attachements devraient-être listé par section et par années
  * [] compression des attachements
* [] Vérifier/completer la validation des vols avion, pilote en vol, machine en vol, etc
* [] vols planeur, les vols sont créés même en cas d'erreur sur la facturation (tarif manquant)
  - à vérifier aussi sur les vols avions
* [] gestion des droits multi sections
* [] Vérifier les photos des membres sur le site déployé
* [] Gestion de l'inscription, y compris les autorisations parentales
* [] supprimer les textes orienté planeur sur les pages
  
* [] Reservation des avions                                  10j
* [] Online Payments                                         10j
* [] Fixer la date de gel lors de la cloture (par section quand la dernière section a clôturé)
* [] Support du markdown
* [] Sauvegarde/restauration des medias et attachements
* [] Attachements sur les achats


## Dette technique

* [] Supprimer les vues non bootstrap
* [] Utiliser les flexbox plutôt que les tableaux
* [] Warnings en mode développement
* [x] Supprimer watir
* [] Supprimer les anciens modes de validation de formulaires
* [] IA revues et refactoring
* [x] utiliser les accordéons bootstrap sur la vue compte pilote
