# Liste des choses à faire


## Bugs

* [] Rapprochement, lors des rapprochements, au lieu de considérer rapprochés les entrées qui ont une entrée de rapprochement, il faudrait aussi vérifier qu'elle pointe sur une écriture existante. Et sinon supprimer l'écriture de rapprochement.

* [] **la page de retour après une modification des écritures est discutable**
* [] les big_select pour les gros select (800 comptes) ne fonctionnent pas

* [~] Les membres du CA ne peuvent pas modifier les droits. C'est du à un mixte entre les anciens et les nouveaux mécanismes de gestion des droits. Pas d'action, ce sera résolu après basculement.


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

* [ ] tests migration
  * [ ] phpunit
    * [x] checks if there are still some CIUnit tests to migrate
    * [x] migrate them
    * [ ] Implement missing phpunit tests according to the test plan (low priority)
    * [ ] Investigate the coverage reporting which looks underestimated
  * [ ] playwright
    * [x] mechanism to restore a well defined test database before testing
    * [x] checks if there are still some Dusk tests to migrate
    * [x] migrate them
    * [ ] Implement missing playwright tests according to the test plan
     
* [] gestion des droits multi sections (wip)
  * [] quelques colonnes inutiles à supprimer
  * [] donner les droits dans les controllers
  
* [] Interdire les réservations pour les gens qui ne sont pas à jour de leurs cotisations et qui n'ont pas le crédit suffisant sur leur compte.

* [] Message d'erreur de validation, les mettre dans un container qu'on peut fermer comme c'est fait dans la gestion des listes d'email. Unifier l'interface utilisateur des messages d'erreur.

* [ ] Utiliser la nouvelle configuration en base. Cela devrait permettre de désactiver le mécanisme précédant? Il faut peut-être ajouter un type de paramètre de configuration et les présenter de façon hiérarchique
* [x] supprimer la configuration des emails dans la base de données.

* [] Ajout des types vol de découverte et vol propriétaire dans les réservations.

* [] Interdire les réservations sans cotisation et sans argent sur le compte.

* [] Gestion des visites médicales
  
* [] Vérifier/completer la validation des vols avion, pilote en vol, machine en vol, etc
* [] vols planeur, les vols sont créés même en cas d'erreur sur la facturation (tarif manquant)
  - à vérifier aussi sur les vols avions
  
* [] Gestion de l'inscription, y compris les autorisations parentales (wip)
  
* [x] Reservation des avions                                  10j (plan OK)

* [] Paiements en ligne                                       10j (PRD OK)

* [] Support de la gestion de la maintenance,                 (PRD à rédiger)
  visite périodique, équipements à potentiel, 
  renouvellement d'assurance, etc.

                    


## Dette technique

* [x] Supprimer les vues non bootstrap
* [] Utiliser les flexbox plutôt que les tableaux. [Vues non responsives](./reviews/non_responsive_views.md)
* [] Supprimer les warnings en mode développement
* [] Supprimer les anciens modes de validation de formulaires
* [] IA revues de code et refactoring
* [] Check translations completeness
* [x] Corriger les tests qui ne passent pas
* [x] Redéployer un serveur CI/CD fonctionnel
* [] Définir une charte graphique et l'appliquer partout
* [] Améliorer les vues saisies des vols (avion, planeur)
* [x] basculer à tfpdf pour les exports PDF
* [x] Affichage des listing des comptes. Quand il y a plus de 400 écritures, on bascule sur le mode de pagination de codeigniter. Il faudrait utiliser le mode datatable partout. Et supprimer le code relatif à la pagination CodeIgniter.

## Idées et suggestions

* restreindre la suppression des membres aux admin. (sera géré correctement avec Laravel cascading)
* Accès à un carnet de vol ULM pour les membres
* Seconde adresse email

* [] Gérer le contexte des filtres par page.
* [] Afficher une marque sur les écritures rapprochés.

## Fait récemment

* [x] Vérifier les exports pdf/csv des comptes
  * Testé sur Échéance pret ULM, OK en local, OK Chez Ionos, CSV et PDF
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

* [x] Quand un trésorier saisie une écriture à partir du menu Écritures, il a le choix entre Recette, Règlement par pilote, Dépenses, etc. Dans chaque cas, il y a présélection des comptes possibles. Mais quand la saisie est rejetée suite à une erreur de validation, il n'y a plus de présélection et l'utilisateur peut choisir n'importe quel compte en emploi et resource. Cela va à l'encontre de la volonté de guider l'utilisateur.

* [x] Attachements lors de la création d'écriture (plan OK)
  * [x] tester les téléchargements avec espace (upload->do_upload)
  * [x] compression des attachements
  * [x] tester upload photo

* [x] Supporter des comptes masqués
  [x] créer une migration pour ajouter un champ boolean dans comptes "masked" 
  [x]  modifier le formulaire et le modèle pour pouvoir le modifier. Il n'est possible de masquer un compte que quand son solde est à 0.
  [x] Modifier le filtre dans comptes/detail pour afficher tous les comptes, les comptes non masqués et les comptes masqués. Le défaut est d'afficher les comptes non masqués.
  [x] Modifier les sélecteurs de comptes pour que les comptes masqués ne soient plus visible

  * [x] **la page après changement de la checkbox gel est incorrecte**

* [x] Support du markdown
* [x] Images de configuration
* [x] Vérifier les photos des membres sur le site déployé
* [x] Amélioration de la gestion des licences
  * [x] Vérifier la création et la mise à jour des licences
  * [x] Ajouter des tests pour la gestion des licences
  * [x] Améliorer l'interface utilisateur pour la gestion des licences
* [x] Le rechargement d'un vol avion ne recharge pas les heures de début et de fin (faux positif du à une corruption des heurs de début et de fin qui c'étaient retrouvées hors norme)

* [x] Gestionnaire de liste d'adresse email.
  * [x] import de fichiers
  * [x] tester export TXT/MD
  * [x] ajouter toutes les adresses email d'un membre dans la liste d'email
  * [x] La gestion des listes devrait passer de membre à gestion
  * [x] Prévisualisation, il y a un ascenseur, pas de raison de limiter la liste à 20 éléments
  * [x] Tester le découpage

* [x] quand on essaye de visualiser un compte d'une autre section on arrive sur la vieille page de la balance.
  * choisir un compte dans une section http://gvv.net/compta/journal_compte/37
  * changer de section
  * rappeler l'URL
* [x] Balance, mise à jour des sous-totaux en cas de filtrage.
* [x] Cotisations permettre de voir les cotisations de l'année prochaine (2026)
* [x] Le mécanisme de pilotes qui volent sur le compte d'autres pilote ne fonctionne pas avec les sections. La recherche du compte cible ne prend pas les sections en compte.
* [x] retour à la balance hiérarchique après la suppression d'un compte
* [x] suppression de compte depuis la balance,il manque le message de confirmation
* [x] Comptes > 400 lignes, passer aux datatable serveur side.
* [x] Ajout d'une date de validité aux vols de découvertes. 
* [x] **Déconnexion intempestive**  (plus de plaintes depuis le changement des paramètres de timeout dans le php.ini du serveur)
* [x] problème d'alignement sur la page résultats, les chiffres devraient être alignés à droite.
* [x] Création d'une vue planche ULM ()
* [x] Mes vols Avion/ULM
* [x] Gestion des fiches de progressions   