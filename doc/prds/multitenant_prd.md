# PRD : Hébergement multi-clubs (multitenant de base)

*Statut : brouillon — 2026-11-27.*

Conception associée : à rédiger.

## 1. Contexte

GVV n'est pas multi-clubs : chaque club déploie sa propre instance sur son propre serveur. Cela impose à chaque club :

* de disposer d'un serveur et de quelqu'un capable de l'administrer ;
* d'installer chaque mise à jour de GVV lui-même, ce qui fait coexister des versions différentes d'un club à l'autre ;
* d'organiser ses propres sauvegardes.

L'objectif est de proposer, en plus de ce mode, un **hébergement commun** : une seule installation de GVV, par exemple sur `gvv.org`, sert plusieurs clubs, chacun accessible par son propre sous-domaine :

* `https://abbeville.gvv.org`
* `https://cpta.gvv.org`
* …

Chaque club garde ses propres données, qui restent séparées de celles des autres clubs.

## 2. Objectifs

* O1 : servir plusieurs clubs depuis une seule installation, mise à jour en une seule fois.
* O2 : pour les membres et les administrateurs d'un club, aucune différence perceptible avec une instance indépendante.
* O3 : ajouter un club rapidement, sans compétence de développement.
* O4 : continuer à permettre le déploiement indépendant actuel, sans changement pour les clubs qui l'utilisent.

## 3. Acteurs

| Acteur | Rôle |
|---|---|
| **Opérateur de la plateforme** | Administre le serveur commun : installe GVV, le met à jour, ajoute ou retire des clubs, supervise les sauvegardes. |
| **Administrateur de club** | Administre GVV pour son club, comme aujourd'hui sur une instance indépendante : configuration, utilisateurs, sauvegardes et restaurations. |
| **Membre** | Utilise GVV pour son club, comme aujourd'hui. |

## 4. Cas d'utilisation

* **CU1 — Accéder à son club.** Un membre ouvre l'adresse de son club (`abbeville.gvv.org`) et utilise GVV comme sur une instance indépendante.
* **CU2 — Ajouter un club.** L'opérateur ajoute un nouveau club : il obtient une instance vide, à jour, accessible à l'adresse du club, avec un premier compte administrateur.
* **CU3 — Configurer son club.** L'administrateur du club modifie la configuration (nom du club, logo, modules actifs, email, paiement en ligne, agenda…). Seul son club est concerné.
* **CU4 — Sauvegarder et restaurer son club.** L'administrateur du club sauvegarde et restaure la base et les fichiers de son club depuis GVV, comme aujourd'hui. Les autres clubs ne sont pas affectés.
* **CU5 — Mettre à jour la plateforme.** L'opérateur installe une nouvelle version de GVV. Tous les clubs passent à cette version, base de données comprise.
* **CU6 — Transférer un club.** Un club passe d'une instance indépendante à l'hébergement commun, ou l'inverse, à partir de ses sauvegardes de base et de fichiers.
* **CU7 — Retirer un club.** L'opérateur retire un club. Ses données ne sont plus accessibles, et l'opérateur peut en remettre une sauvegarde complète au club.

## 5. Exigences fonctionnelles

### 5.1 Comportement identique à une instance indépendante (E1)

* **EF1** : Un club hébergé se comporte exactement comme s'il était déployé sur un serveur indépendant. Toutes les fonctions sont disponibles, y compris la sauvegarde et la restauration de la base et des fichiers.
* **EF2** : Les adresses produites par GVV (liens dans les emails, QR codes, retours de paiement en ligne, liens d'agenda, pages publiques) désignent le sous-domaine du club concerné.
* **EF3** : Les tâches automatiques (alarmes, relances, rappels de réservation, synchronisation d'agenda, sauvegardes planifiées) s'exécutent pour chaque club, avec ses propres données et sa propre configuration.
* **EF4** : Chaque club choisit son module de facturation, comme aujourd'hui.

### 5.2 Isolation entre clubs

* **EF5** : Un club n'accède jamais aux données d'un autre club : base de données, documents et fichiers déposés, photos, sauvegardes, journaux.
* **EF6** : Une session ouverte sur un club ne donne aucun accès à un autre club.
* **EF7** : Une modification de configuration faite par un club ne concerne que ce club.
* **EF8** : La restauration d'une sauvegarde par un club ne peut modifier que les données de ce club, même si la sauvegarde restaurée provient d'un autre serveur ou contient des instructions visant d'autres données.
* **EF9** : Les identifiants et secrets propres à un club (paiement en ligne, agenda, envoi d'emails et de SMS) ne sont ni visibles ni utilisables par un autre club.
* **EF10** : Les fonctions qui agissent sur le serveur lui-même (génération de bases de test, commandes système, informations sur le serveur) sont réservées à l'opérateur de la plateforme et ne sont pas accessibles aux administrateurs de club.

### 5.3 Gestion des clubs par l'opérateur

* **EF11** : L'opérateur ajoute un club en indiquant son sous-domaine et son premier administrateur. L'ajout ne demande ni modification du code ni intervention de développement.
* **EF12** : Une adresse qui ne correspond à aucun club affiche un message clair et ne donne accès à aucune donnée.
* **EF13** : Une mise à jour de GVV met à jour la base de chaque club. L'opérateur sait si la mise à jour a échoué pour un club, et lequel.
* **EF14** : Un club restaurant une sauvegarde faite avec une version antérieure de GVV retrouve une base à jour, comme sur une instance indépendante.
* **EF15** : L'opérateur peut sauvegarder hors site tous les clubs (base et fichiers), et restaurer un club donné sans toucher aux autres.
* **EF16** : L'opérateur peut retirer un club et en obtenir une sauvegarde complète (base et fichiers) restaurable sur une instance indépendante.

### 5.4 Compatibilité

* **EF17** : Une instance indépendante continue de fonctionner comme aujourd'hui, sans configuration supplémentaire.
* **EF18** : Une sauvegarde faite sur une instance indépendante se restaure sur un club hébergé, et inversement.

## 6. Exigences non fonctionnelles

* **ENF1 (E2)** : Le choix du club à partir de l'adresse n'introduit pas de délai notable dans les temps de réponse par rapport à une instance indépendante.
* **ENF2** : L'ajout de clubs ne dégrade pas les temps de réponse des clubs existants, tant que les ressources du serveur sont suffisantes. Le nombre de clubs qu'un serveur peut accueillir est documenté.
* **ENF3** : Ajouter un club prend moins d'une heure à l'opérateur, DNS compris.
* **ENF4** : Les tests automatisés vérifient l'isolation entre deux clubs (EF5 à EF9).

## 7. Contraintes

* Tous les clubs hébergés utilisent la même version de GVV.
* Les sous-domaines sont sous un même domaine, servi en HTTPS.
* Les modules de facturation propres à un club font partie du code commun : un nouveau module ou sa modification passe par l'opérateur, comme toute mise à jour de GVV.

## 8. Hors périmètre

* Un compte utilisateur commun à plusieurs clubs : un membre de deux clubs a un compte dans chacun.
* Un domaine propre à un club (`gvv.monclub.fr`) en plus de son sous-domaine.
* La facturation de l'hébergement aux clubs et les quotas d'espace disque.
* Une interface web de gestion des clubs pour l'opérateur : l'ajout et le retrait peuvent se faire en ligne de commande.
* Le partage de données entre clubs (statistiques communes, annuaire).

## 9. Questions ouvertes

1. **Domaines personnalisés** : un club peut-il vouloir garder son adresse actuelle (`gvv.monclub.fr`) en passant sur l'hébergement commun ? Si oui, faut-il l'intégrer dès la première version ?
2. **Sauvegardes par l'opérateur** : l'opérateur doit-il garantir des sauvegardes hors site pour tous les clubs, ou chaque club reste-t-il responsable des siennes ?
3. **Modules de facturation** : la configuration de la facturation sans programmation ([configuration_facturation_prd.md](configuration_facturation_prd.md)) est-elle un préalable à l'ouverture de l'hébergement commun à des clubs qui ont besoin d'un module propre ?
4. **Données personnelles** : l'hébergement des données de plusieurs clubs par un même opérateur demande-t-il un accord de sous-traitance (RGPD) avec chaque club ?
