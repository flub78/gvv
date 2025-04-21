# Liste des choses à faire

Cela peut paraître paradoxal de maintenir une liste de chose à faire pour un logiciel qui doit être remplacé.

Néanmoins, la date de sortie probable du remplacement est loin et j'ai deja incorporé dans cette version certaines des améliorations prévues pour la prochaine version. C'est assez désagréable d'avoir à maintenir quelque chose dont les défauts semblent maintenant plus évidents lorsqu'on compare à la nouvelle version. Lorsque j'ai commencé le travail sur la nouvelle je m'étais juré que je ne ferais pas de concessions même au détriment du temps de développement. Et quelle plus grande concession pour un développeur que de travailler à la maintenance d'un projet legacy aux dépends de la grande et glorieuse nouvelle version ? :-).

Cependant le fait que ce projet soit utile et utilisé est une part de ma motivation et pour mes utilisateurs, certaines fonctionnalités sont plus utiles livrées dans quelques semaines que dans quelques années. Ceci d'autant plus que les limitations de GVV sont plus dues à l’obsolescence de l’environnement et à certains choix d'architecture douteux plutôt qu'aux fonctionnalités proprement dites. En d'autre termes c'est plutôt moi qui pense qu'on peut faire mieux que les utilisateurs qui se plaignent. 

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


## Reste à faire

* [] Problèmes de CSS avec les boutons du calendrier et ceux des sélecteurs de date
* [] Attachements lors de la création d'écriture
* [] tester les téléchargements avec espace (upload->do_upload)
* [] Les attachements devrait-être listé par section et par années
* [] compression des attachements
* [] Vérifier/completer la validation des vols avion, pilote en vol, machine en vol, etc
* [] vols planeur, les vols sont créés même en cas d'erreur sur la facturation (tarif manquant)
  - à vérifier aussi sur les vols avions
* [] bilan et clôture par section
* [] gestion des droits multi sections
* [] Vérifier les photos des membres sur le site déployé
* [] gestion des vols de découverte
* [] synchro avec OpenFlyers
* [] Gestion de l'inscription, y compris les autorisations parentales
* [] supprimer les trucs orienté planeur dans les formulaires
  
* [] Online Payments                                         10j
* [] Reservation des avions                                  10j
* [] Fixer la date de gel lors de la cloture (par section quand la dernière section a clôturé)
* [] Support du markdown


## Dette technique

* [] Supprimer les vues non bootstrap
* [] Utiliser les flexbox plutôt que les tableaux
* [] Warnings en mode développement
* [x] Supprimer watir
* [] Supprimer les anciens modes de validation de formulaires
* [] IA revues et refactoring
* [x] utiliser les accordéons bootstrap sur la vue compte pilote
