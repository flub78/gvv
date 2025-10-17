# Liste des choses à faire


## Bugs

* [] **la page de retour après une modification des écritures est discutable**
* [] **la page après changement de la checkbox gel est incorrecte**
  
* [] **Déconnexion intempestive**

## Reste à faire

* [x] Images de configuration
* [ ] Utiliser la nouvelle configuration en base
* [] gestion des droits multi sections (plan draft)

* [] Support du markdown
  
* [] Import des informations planeur dans les vols de découverte
  
* [] Vérifier/completer la validation des vols avion, pilote en vol, machine en vol, etc
* [] vols planeur, les vols sont créés même en cas d'erreur sur la facturation (tarif manquant)
  - à vérifier aussi sur les vols avions
  
* [x] Vérifier les photos des membres sur le site déployé
* [] Gestion de l'inscription, y compris les autorisations parentales
  
* [] Reservation des avions                                   10j (plan OK)
* [] Paiements en ligne                                       10j
* [] Support de la gestion de la maintenance, visite périodique, équipements à potentiel, renouvellement d'assurance, etc.
* [] Gestion des fiches de progressions

* [] Désactiver la capacité d'envoyer des emails et remplacer la par la capacité de sélectionner les adresses emails.
  * [x] MVP
  il faut pouvoir sélectionner par section. Ca ne pourra être vraiment opérationnel que quand les rôles par section seront en place. Pour l'instant on a pas vraiment l'information si un membre est instructeur ULM ou qu'il est encore actif dans une section.

## Dette technique

* [] Supprimer les vues non bootstrap (emails)
* [] Utiliser les flexbox plutôt que les tableaux. [Vues non responsives](./reviews/non_responsive_views.md)
* [] Warnings en mode développement
* [] Supprimer les anciens modes de validation de formulaires
* [] IA revues et refactoring
* [] Traduire la vue dashboard (basse priorité)

## Idées et suggestions

* restreindre la suppression des membres aux admin. (sera géré correctement avec Laravel cascading)
* Accés à un carnet de vol ULM pour les membres
* Seconde adresse email
* Photos Gestionnaire de liste d'adresse email.

## Fait récemment

* [x] Vérifier les exports pdf/csv des comptes
  * Testé sur Echéance pret ULM, OK en local, OK Chez Ionos, CSV et PDF
  * Testé sur compte client Ionos, OK CSV et PDF
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
  * [x] OpenFlyers afficher les vols non existant dans les imports OF
* [x] Export CSV du tableau de bord
* [x] Gérer les suggestions de saisie par section
* [x] Pb de parseur des opérations en cas de flux à plus de deux comptes
* [x] La comparaison des soldes incite à des initialisations multiples incorrectes
* [x] Problèmes de CSS avec les boutons du calendrier et ceux des sélecteurs de date
* [x] Plus d'affichage des petites flèches quand le tri par colone est actif
* [x] Comparaison des soldes, recharger la date en cas d'erreur fichier non sélectionné
* [x] Vérifier le cadrage à droite pour tous les chiffres, 
  * grand journal, montants
  * Ventes, cadrer Produit à gauche
* [x] supprimer les textes orientés planeur dans les pages
* [x] Rapprochements bancaires
  
* [x] Compléter les exports CSV/PDF manquants

  - [x] application/views/plan_comptable/bs_tableView.php
  - [x] application/views/planeur/bs_tableView.php
  - [x] application/views/avion/bs_tableView.php
  - [x] application/views/sections/bs_tableView.php

  - application/views/attachments/bs_tableView.php
  - application/views/associations_ecriture/bs_tableView.php
  - application/views/associations_releve/bs_tableView.php
  - application/views/categorie/bs_tableView.php
  - application/views/historique/bs_tableView.php
  - application/views/licences/bs_tableView.php
  - application/views/pompes/bs_tableView.php
  - application/views/tarifs/bs_tableView.php
  - application/views/user_roles_per_section/bs_tableView.php
  - application/views/event/bs_tableView.php
  - application/views/achats/bs_tableView.php

* [x] gestion des vols de découverte
  * [x] Fonctionnalité minimum
  * [x] filtre
  * [x] présentation par année
  * [x] export CSV et Pdf

* [x] Les attachements devraient-être listé par section et par années
* [x] Gérer la configuration dans la base.
* [x] Sauvegarde/restauration des medias et attachements (optimisé mémoire)

* [x] Quand un trésorier saisie une écriture à partir du menu Ecritures, il a le choix entre Recette, Réglement par pilote, Dépenses, etc. Dans chaque cas, il y a présélection des comptes possibles. Mais quand la saisie est rejetée suite à une erreur de validation, il n'y a plus de présélection et l'utilisateur peut choisir n'importe quel compte en emploi et resource. Cela va à l'encontre de la volonté de guider l'utilisateur.

* [x] Attachements lors de la création d'écriture (plan OK)
  * [x] tester les téléchargements avec espace (upload->do_upload)
  * [x] compression des attachements
  * [x] tester upload photo