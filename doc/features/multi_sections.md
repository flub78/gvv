---
markdown-preview-enhanced:
  style: "/home/frederic/css/style.css"
---
<!-- Multi Sections --> 
# Multi sections


- [Multi sections](#multi-sections)
  - [Description](#description)
  - [Cas d'utilisations](#cas-dutilisations)
    - [Cas complexes](#cas-complexes)
      - [Virement entre deux sections](#virement-entre-deux-sections)
      - [Multiple comptes 401 pour un utilisateur](#multiple-comptes-401-pour-un-utilisateur)
      - [Lien avec le système de réservation](#lien-avec-le-système-de-réservation)
      - [Approvisionnement automatique des comptes.](#approvisionnement-automatique-des-comptes)
  - [Notes de conception](#notes-de-conception)
  - [Gestion des droits.](#gestion-des-droits)
  - [Commentaires, Idées,  etc.](#commentaires-idées--etc)

## Description

Le concept de multi sections, consiste à gérer plusieurs entités indépendantes de façon comptable. Cette fonction peut-être utilisée pour gérer plusieurs activités au sein de la même association.

Pour la plupart des utilisateurs cela implique de choisir la section à laquelle ils veulent accéder. 

Cela va également impliquer la création d'un rôle de super-trésorier, qui aura la possibilité de gérer les comptes de toutes les sections et d'effectuer les consolidations. Ne pas confondre ce rôle avec le rôle de trésorier qui est dédié à une section, par example la section du compte générale. Pour le programme une section est équivalente à une comptabilité indépendante.

Si possible les membres seront partagés par toutes les sections, ils appartiennent à l'association.

## Cas d'utilisations

* En tant que membre d'une section, je me connecte et j'ai les mêmes accès au sein de cette section que ceux que j'avais avec le GVV de ma section.
 
* En tant qu'admin je peux inscrire un membre à une section.
* En tant qu'admin je peux désactiver l'accès d'un membre à une section. Attention pour garder la cohérence des données, les écritures seront conservées.

* En tant que trésorier d'une section, je peux voir et éditer les écritures de la section.
* En tant que trésorier d'une section, je peux réaliser les opérations de cloture de la section.

* En tant que super trésorier je peux voir et éditer les écritures de toutes les sections (ou pas)
* En tant que super trésorier je peux réaliser les opérations de consolidation. Les clotures doivent être réalisées par les trésoriers de chaque section. La consolidation n'est probablement qu'une vue.

### Cas complexes
#### Virement entre deux sections

 Comment réaliser un virement entre deux comptes de sections différentes. C'est un cas d'utilisation qui existe par example pour alimenter une section à partir d'une autre. Est-ce que cela veut dire que pour chaque section, les autres sections  sont des comptes tiers au même titre qu'un fournisseur ou un client? 

Auquel cas, il faudrait que le trésorier de la section de départ et celui de la section d'arrivée créent chacun une écriture.

On pourrait envisager que les super trésoriers puissent réaliser cela en une fois, entre autre pour partager les références ('N° de chèque, de virement, etc). Ca ressemblerait à une écriture entre quatre comptes qui partagerait la description et le montant. 

Le problème de ce genre de mouvement est que si un virement est fait sur le compte courant d'une section pour alimenter une section il y a une logique. Un virement de compte pilote ULM vers la section planeur n'est pas compensé par un paiement, en fait il créerait une dette de la section ULM vers la section planeur. Il faudrait probablement que chaque section ait les autres sections en compte 401.

#### Multiple comptes 401 pour un utilisateur

Des utilisateurs pourraient avoir envie d'avoir un compte client unique et d'y voir les écritures qui correspondent à leurs activités au sein de différentes section. J'ai un compte, et sur ma facture je vois mes vols ent ULM, planeur et avion.

Bien sûr cela leur éviterait d'avoir à alimenter plusieurs comptes bancaires et de se retrouver dans des situations ou ils ne peuvent plus voler alors qu'un de leur compte est abondamment garni.

C'est non compatible avec le concept de comptabilité parfaitement indépendante entre les sections. Si c'était une situation acceptée avec des systèmes de gestion différents, les utilisateurs risque de moins bien en comprendre la raison avec un système de gestion unifié.

Notons que le super-trésorier pourrait effectuer des virements entre compte bancaire de différentes sections, avec pour justification de prélever sur un compte pilote dans une section pour alimenter son compte pilote dans une autre section. Ca reporterait le travail que les membres sont réticents à faire sur l'association. Et le but de la mise en place de ce système est de simplifier la vie des trésoriers, pas de leur ajouter de la charge de travail.

#### Lien avec le système de réservation

Ca aussi c'est un problème, si les comptes clients sont gérés avec GVV, il devient plus compliquer de bloquer les réservations en cas de comptes non suffisamment approvisionnés.

Pas forcément impossible qu'on puisse connecter GVV et OpenFlyers pour interdire la réservation d'un vol si le compte n'est pas approvisionné.

#### Approvisionnement automatique des comptes.

Ce point aussi a généré pas mal de frustrations surtout depuis le blocage des réservations en cas de comptes non approvisionnés. Comme la mise à jour des comptes implique une intervention humaine pour vérifier que des virements ont été effectués et une seconde intervention humaine pour créditer le compte pilote. Il suffis d'une non disponibilité des responsables pour que le système reste bloqué plusieurs jours.

C'est d'autant plus frustrant que ce sont des opérations qui pourraient être automatisées.

## Notes de conception

* Depuis l'origine de GVV il existait un champ `club` dans la table `ecritures` et dans la table `comptes`. (un entier sur un octet).

* Il est donc relativement facile de créer une table `sections` ou `clubs` et de restreindre les vues des comptes et écritures à la section active. Ce mécanisme garantie la compatibilité avec le fonctionnement précédant. Une fois connecté à une section, rien de change.

* Cette approche a un léger désavantage, la base de données est partagée entre toutes les sections. Il n'est donc pas possible pour un trésorier de restaurer une sauvegarde sans s'être synchronisé avec les autres trésoriers. Cela veux peut-être dire que les sauvegardes et les restorations doivent être faites par le super trésorier.

## Gestion des droits.

Avant le système des droits était hiérarchique.
  * membre < planchiste < ca < trésorier < admin

Maintenant un membre qui appartient à plusieurs section peut être trésorier d'une section et planchiste d'une autre.
Il faut donc que les droits soient définis par section.
 
On voit bien qu'on a des resources spécifiques à chaque section: les comptes, les écritures, les machines, les tarifs, les tickets, les vols. D'autres sont partagées entre les sections. Les membres, les terrains, le plan comptable.

Notons que les membres du ca devraient garder les droits de ca sur toutes les sections. 

Notons également que les droits hiérarchiques étaient une idée bizarre, c'est une question de responsabilité et dans l'absolu on pourrait envisager qu'un trésorier ne soit pas automatiquement planchiste. De plus en ULM et avion les gens sont souvent auto-planchistes ils peuvent rentrer et modifier leurs vols mais seulement leurs vols.

On pourrait remplacer les droits hiérarchique pas des droits par section.

Connection, auto-planchiste, planchiste, trésorier, 

et des droits pour toutes les sections
ca, super-trésorier, admin.

## Commentaires, Idées,  etc.