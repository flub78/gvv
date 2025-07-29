# Liste des choses à faire

Il existe un projet de ré-écriture complet de GVV, néanmoins c'est un travail lourd, et le GVV actuel doit être maintenu pour s'adapter aux nouveaux besoins des utilisateurs.


## Bugs

* [] **la page de retour après une modification des écritures est discutable**
* [] **la page après changement de la checkbox gel est incorrecte**
* [] Plus d'affichage des petites flèches quand le tri par colone est actif
* [] Vérifier le cadrage à droite pour tous les chiffres, 
  * grand journal, montants
  * Ventes, cadrer Produit à gauche
* [] Problèmes de CSS avec les boutons du calendrier et ceux des sélecteurs de date

  
## Reste à faire

* [] OpenFlyers afficher les vols non existant dans les imports OF

* [] Gérer la configuration dans la base.
* [] Support du markdown
  
* [] Compléter les exports CSV manquants
* [] gestion des vols de découverte
  * [x] Fonctionnalité minimum
  * [ ] filtre
  * [ ] présentation par année
  * [ ] export CSV et Pdf
  * [ ] Import des informations planeur
  
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
* [] supprimer les textes orientés planeur sur les pages
  

* [] Reservation des avions                                   10j
* [] Payments en ligne                                        10j
* [] Sauvegarde/restauration des medias et attachements
* [] Attachements sur les achats


## Dette technique

* [] Supprimer les vues non bootstrap (emails)
* [] Utiliser les flexbox plutôt que les tableaux
* [] Warnings en mode développement
* [] Supprimer les anciens modes de validation de formulaires
* [] IA revues et refactoring
* [] Traduire la vue dashboard


## Fait récemment

* [x] Correction du téléchargement des photos                                  
* [x] Documentation de la sauvegarde automatique            
* [x] Vue des photos dans la fiche membre            
* [x] Analyse et documentation du mécanisme de sauvegarde automatique
* [x] Support des attachments                                  5j
* [x] Les écritures devraient accepter les dates en 1/1/2025
* [x] Les écritures devraient accepter les montants avec virgule comme séparateur
* [x] Retour vers le compte emploi après passage des écritures
* [x] Les heures de début et de fin ne sont pas rechargés sur les vols avion
* [x] avec créer et continuer, afficher le message dans la page plutôt que comme popup, Les popups doivent être réservés pour les demandes de confirmation ou action utilisateur 
* [x] correction du nombre d'éléments du bandeau bas des journaux lors de la selection d'une section
* [x] "A propos" ne fonctionne plus en production HTTP ERROR 500
* [x] Le sélecteur de compte n'est pas filtré par section..
* [x] Ne pas créer deux comptes 411 depuis la section générale
* [x] Verifier la synchro HEVA
* [x] Vérifier qu'il n'y a pas d'écriture entre deux sections dans la validation
* [x] bilan et clôture par section
  * [x] bilan par section
  * [x] clôture par section
* [x] Vue compte, autoriser les vues sur plusieurs années
* [x] Tableau de bord comptable
* [x] En mode édition d'écritures les comptes ne sont pas filtrés par section
* [x] Supprimer les choix par défaut pour les écritures
* [x] Écriture spéciale de remise de liquide en banque
* [x] Prise en compte des comptes de caisse dans le bilan
* [x] Fixer la date de gel par section
* [x] Gestion des prêts dans le bilan
* [x] Réactiver les tris par colonnes dans les datatable
* [x] utiliser les accordéons bootstrap sur la vue compte pilote
* [x] Supprimer watir
* [x] emails des vols de découverte

* [x] synchro avec OpenFlyer
  * [] Vérifier les pages de retour des associations
  * [x] Filtrer les associations par sections
  * [x] Mécanisme d'association des comptes de soldes 
  * [x] Import des soldes
  * [x] Vérification des soldes
  * [x] import des écritures
  * [x] Annulation des imports
  * [x] prendre la date en compte pour l'import des soldes
  * [x] Openflyers fusionner import et vérification des soldes
* [x] Export CSV du tableau de bord
* [x] Gérer les suggestions de saisie par section

