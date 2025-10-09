# GVV - Document d'Exigences Produit (PRD)
*Gestion Vol à voile (Système de Gestion de Club de Vol à Voile)*

---

## 1. Aperçu Général

### 1.1 Objectif du Produit
GVV (Gestion Vol à voile) est un système de gestion complet basé sur le web, conçu spécifiquement pour les clubs de vol à voile et les associations aéronautiques. L'objectif principal est de fournir une solution complète pour gérer tous les aspects des opérations d'un club de vol à voile, de la gestion des membres à l'enregistrement des vols, à la facturation et à la comptabilité de base.

### 1.2 Problème Résolu
Les clubs de vol à voile font face à des défis opérationnels complexes :
- **Complexité de la Gestion des Membres** : Suivi des pilotes avec différentes qualifications, licences et rôles
- **Gestion de la Flotte** : Gestion de types d'aéronefs divers (planeurs, remorqueurs, avions à moteur)
- **Opérations de Vol** : Enregistrement manuel et automatique des vols avec intégration de la facturation
- **Gestion Financière** : Règles de facturation complexes propres à chaque club, comptabilité et système de tickets
- **Conformité Réglementaire** : Gestion des licences, certificats médicaux et dossiers de formation
- **Communication** : Coordination des activités du club et communication avec les membres

### 1.3 Utilisateurs Cibles

#### Utilisateurs Principaux
- **Membres du Club** : Pilotes accédant aux informations personnelles, carnets de vol et calendriers
- **Instructeurs de Vol (Planchistes)** : Enregistrement des vols, gestion des dossiers de formation
- **Administrateurs du Club (CA)** : Gestion des opérations du club, communications avec les membres
- **Trésoriers** : Gestion financière, facturation, comptabilité
- **Administrateurs Système** : Gestion technique, sauvegardes, configuration

#### Utilisateurs Secondaires
- **Mécaniciens** : Suivi de la maintenance des aéronefs
- **Personnel au Sol** : Support des opérations quotidiennes
- **Organisations Externes** : Intégration FFVP, export GESASSO

---

## 2. Fonctionnalités Actuelles

### 2.1 Gestion des Membres
- **Registre des Membres** : Base de données complète des membres avec informations personnelles
- **Gestion des Licences** : Suivi des licences de pilote, certificats médicaux, qualifications
- **Accès Basé sur les Rôles** : Système de permissions hiérarchique (membre → planchiste → ca → trésorier → admin)
- **Support Multi-Sections** : Gestion de différentes sections du club (planeurs, avions à moteur, ULM)
- **Authentification Utilisateur** : Connexion sécurisée avec récupération de mot de passe
- **Gestion de Profil** : Mises à jour de profil en libre-service

### 2.2 Gestion de la Flotte d'Aéronefs
- **Base de Données des Aéronefs** : Registre complet de la flotte (planeurs, remorqueurs, avions à moteur)
- **Suivi de la Maintenance** : État des aéronefs et dossiers de maintenance
- **Suivi des Heures** : Accumulation du temps de vol par aéronef
- **Aéronefs Privés vs Club** : Catégorisation de la propriété
- **Configuration des Aéronefs** : Données de performance, règles de tarification

### 2.3 Opérations de Vol
- **Saisie Manuelle de Vol** : Interface traditionnelle du carnet de vol
- **Import Automatique de Vols** : Intégration avec les systèmes de journalisation de vol électroniques
- **Catégories de Vol** : Standard, formation (VI), vols d'essai (VE), compétition
- **Méthodes de Lancement** : Treuil, remorqueur, auto-lancement, externe
- **Support Double Commande** : Gestion des vols de formation
- **Validation de Vol** : Vérifications d'intégrité des données et règles de validation

### 2.4 Facturation et Gestion Financière
- **Moteur de Facturation Complexe** : Règles de facturation personnalisables par club
- **Catalogue de Produits** : Tarification configurable pour les vols, services, adhésions
- **Système de Tickets** : Tickets de vol prépayés avec déduction automatique
- **Gestion des Comptes** : Comptes individuels de pilote avec suivi crédit/débit
- **Support Multi-Tarifs** : Tarification différente selon la catégorie de pilote, le type d'aéronef
- **Modules de Facturation** : Implémentations de facturation spécifiques au club (DAC, ACES, Vichy, etc.)

### 2.5 Système Comptable
- **Comptabilité en Partie Double** : Comptabilité de base sans TVA ni paie
- **Plan Comptable** : Structure de comptes configurable
- **Rapports Financiers** : Comptes de résultat, bilans
- **Rapprochement Bancaire** : Intégration bancaire pour la gestion des comptes
- **Suivi des Achats** : Gestion et suivi des dépenses

### 2.6 Calendrier et Planification
- **Intégration Google Calendar** : Calendrier partagé du club pour les intentions de vol
- **Suivi des Présences** : Disponibilité et présence des membres
- **Gestion des Événements** : Planification des événements et activités du club
- **Intégration Météo** : Support de planification de vol

### 2.7 Système de Communication
- **Gestion des Emails** : Email groupé aux membres avec filtrage
- **Gestion des Adresses Email** : Maintenance de la liste de contacts
- **Notifications** : Alertes et rappels automatisés
- **Support Multilingue** : Traductions en français, anglais, néerlandais

### 2.8 Rapports et Statistiques
- **Statistiques de Vol** : Rapports mensuels, annuels et historiques
- **Progression du Pilote** : Suivi de la progression de la formation
- **Utilisation de la Flotte** : Analyses de l'utilisation des aéronefs
- **Rapports Financiers** : Analyse des revenus et des coûts
- **Démographie d'Âge** : Analyse de la distribution d'âge des pilotes

### 2.9 Outils d'Administration
- **Sauvegarde/Restauration de Base de Données** : Protection et récupération des données
- **Système de Migration** : Mises à jour du schéma de base de données
- **Gestion de la Configuration** : Paramètres spécifiques au club
- **Gestion des Rôles Utilisateur** : Attribution des permissions
- **Surveillance du Système** : Vérifications de santé et diagnostics

### 2.10 Capacités d'Intégration
- **Intégration FFVP** : Connectivité avec la fédération française
- **Export GESASSO** : Intégration du système comptable
- **Services Google** : Calendrier et authentification
- **Journaux de Vol Externes** : Import depuis diverses sources
- **Support API** : API de base pour les intégrations externes

---

## 3. Architecture Technique

### 3.1 Pile Technologique
- **Framework Backend** : CodeIgniter 2.x (framework PHP)
- **Langage de Programmation** : PHP 7.4
- **Base de Données** : MySQL 5.x avec pilote MySQLi
- **Frontend** : HTML5, CSS3, JavaScript, Bootstrap 5
- **Serveur Web** : Apache/Nginx avec mod_rewrite
- **Contrôle de Version** : Git (migré depuis SVN)

### 3.2 Dépendances Principales
- **Extensions PHP** : MySQLi, GD (graphiques), extensions standard
- **Bibliothèques JavaScript** :
  - FullCalendar (interface de calendrier)
  - Bootstrap 5 (interface responsive)
  - jQuery (manipulation DOM)
- **Bibliothèques Tierces** :
  - Google Calendar API
  - pChart (statistiques graphiques)
  - Diverses bibliothèques utilitaires

### 3.3 Intégrations Externes
- **Google Calendar** : Gestion et synchronisation d'événements
- **Systèmes FFVP** : Validation de licence et intégration fédération
- **GESASSO** : Export du logiciel comptable
- **Services FlightLog** : Import automatique de données de vol
- **Services Email** : Intégration SMTP pour les communications

### 3.4 Architecture de la Base de Données
- **Système de Migration** : Migrations CodeIgniter pour les mises à jour de schéma
- **Piloté par Métadonnées** : Génération de formulaires dynamiques basée sur les métadonnées de la base de données
- **Intégrité Référentielle** : Relations de clés étrangères maintenues
- **Piste d'Audit** : Journalisation des actions utilisateur et suivi des modifications de données

---

## 4. Spécifications Fonctionnelles

### 4.1 Authentification et Autorisation des Utilisateurs

#### User Stories
- **En tant que membre du club**, je peux me connecter en toute sécurité pour accéder à mes informations personnelles
- **En tant que membre**, je peux réinitialiser mon mot de passe s'il est oublié
- **En tant qu'administrateur**, je peux attribuer des rôles et des permissions aux utilisateurs
- **En tant qu'utilisateur**, je peux modifier mon mot de passe et mes informations personnelles

#### Critères d'Acceptation
- Connexion sécurisée avec nom d'utilisateur/mot de passe
- Récupération de mot de passe par email
- Contrôle d'accès basé sur les rôles avec permissions hiérarchiques
- Gestion de session avec délai d'expiration
- Validation de la force du mot de passe

#### Cas Limites
- Verrouillage de compte après plusieurs tentatives échouées
- Gestion des membres inactifs/suspendus
- Changements de rôle et propagation des permissions

### 4.2 Enregistrement des Vols

#### User Stories
- **En tant que planchiste**, je peux enregistrer les vols manuellement avec tous les détails requis
- **En tant que planchiste**, je peux importer les vols automatiquement depuis des systèmes externes
- **En tant que pilote**, je peux consulter mon historique de vol personnel et mes statistiques
- **En tant qu'administrateur**, je peux valider et corriger les données de vol

#### Critères d'Acceptation
- Capture complète des données de vol (date, heure, aéronef, pilote, durée, etc.)
- Règles de validation pour l'intégrité des données
- Support de différents types de vol et méthodes de lancement
- Génération automatique de facturation lors de la création du vol
- Capacités d'import en masse avec gestion des erreurs

#### Cas Limites
- Détection et prévention des vols en double
- Gestion des données de vol incomplètes ou invalides
- Modifications de vol après la génération de la facturation
- Vols passant minuit et gestion du fuseau horaire

### 4.3 Facturation et Gestion Financière

#### User Stories
- **En tant que trésorier**, je peux configurer les règles de facturation spécifiques à notre club
- **En tant que trésorier**, je peux générer des factures automatiquement en fonction de l'activité de vol
- **En tant que membre**, je peux consulter le solde de mon compte et l'historique des transactions
- **En tant que trésorier**, je peux gérer les tickets prépayés et les forfaits

#### Critères d'Acceptation
- Configuration flexible des règles de facturation
- Calcul automatique des frais basé sur les données de vol
- Support de différents schémas de tarification (horaire, forfait, tickets)
- Suivi du solde de compte avec gestion crédit/débit
- Intégration avec l'enregistrement des vols pour la facturation automatique

#### Cas Limites
- Conflits de règles de facturation et gestion des priorités
- Facturation partielle de vol et proratisation
- Remboursements et corrections de facturation
- Scénarios de facturation partagée complexes

### 4.4 Gestion des Membres

#### User Stories
- **En tant que membre du CA**, je peux maintenir les dossiers complets des membres
- **En tant que membre du CA**, je peux suivre les qualifications et certificats des pilotes
- **En tant que membre**, je peux mettre à jour mes informations de contact
- **En tant qu'administrateur**, je peux activer/désactiver les membres

#### Critères d'Acceptation
- Base de données complète des membres avec tous les champs requis
- Suivi de l'expiration des licences et certificats
- Rappels automatisés pour les renouvellements
- Gestion du statut des membres (actif, inactif, suspendu)
- Intégration avec le système d'authentification

#### Cas Limites
- Gestion des certificats médicaux expirés
- Confidentialité des données des membres et conformité RGPD
- Détection de membres en double
- Migration de données et fusion de membres

### 4.5 Gestion de la Flotte

#### User Stories
- **En tant que membre du CA**, je peux gérer la base de données de la flotte d'aéronefs
- **En tant que responsable maintenance**, je peux suivre les heures d'aéronef et la maintenance
- **En tant que planchiste**, je peux voir la disponibilité des aéronefs pour l'enregistrement des vols
- **En tant que trésorier**, je peux configurer la tarification pour différents aéronefs

#### Critères d'Acceptation
- Base de données complète des aéronefs avec spécifications techniques
- Accumulation et suivi des heures de vol
- Intégration du calendrier de maintenance
- Gestion du statut de disponibilité des aéronefs
- Configuration de tarification par type d'aéronef

#### Cas Limites
- Retrait d'aéronef et préservation des données historiques
- Gestion des changements de propriété d'aéronef
- Dérogations de maintenance et opérations d'urgence
- Partage d'aéronefs entre clubs

---

## 5. Contraintes et Dépendances

### 5.1 Limitations Techniques
- **Framework Legacy** : CodeIgniter 2.x limite les fonctionnalités PHP modernes, pas de composer
- **Verrouillage de Version PHP** : Nécessite PHP 7.4 spécifiquement (non compatible avec les versions plus récentes)
- **Architecture Mono-Tenant** : Chaque club nécessite une installation séparée
- **Optimisation Mobile Limitée** : Design responsive mais pas d'application mobile native
- **Couplage à la Base de Données** : Couplage étroit à MySQL limite la portabilité de la base de données

### 5.2 Prérequis Système
- **Exigences Serveur** :
  - Serveur Linux/Windows avec Apache/Nginx
  - PHP 7.4 avec extensions MySQLi, GD
  - MySQL 5.x ou base de données compatible
  - Minimum 256MB RAM (recommandé 512MB pour les sauvegardes)
- **Exigences Client** :
  - Navigateur web moderne avec JavaScript activé
  - Connexion Internet pour les intégrations cloud
  - Lecteur PDF pour les rapports

### 5.3 Exigences de Compatibilité
- **Support Navigateur** : Chrome, Firefox, Safari, Edge (versions récentes)
- **Compatibilité Mobile** : Design responsive pour tablettes et téléphones
- **Standards d'Intégration** : APIs REST pour l'intégration de systèmes externes
- **Support Format de Données** : Import/export CSV, génération PDF
- **Standards Email** : Conformité SMTP pour les fonctionnalités de communication

### 5.4 Dépendances Réglementaires
- **Intégration FFVP** : Dépend de la disponibilité de l'API de la Fédération Française
- **Conformité RGPD** : Doit gérer les données personnelles selon les réglementations européennes
- **Réglementations Aéronautiques** : Doit supporter les exigences de l'autorité aéronautique locale
- **Normes Comptables** : Conformité comptable de base pour les organisations à but non lucratif

---

## 6. Améliorations Potentielles

### 6.1 Points de Friction Identifiés

#### Dette Technique
- **Modernisation du Framework** : Mise à niveau de CodeIgniter 2.x vers un framework moderne
- **Compatibilité Version PHP** : Support pour PHP 8.x et versions futures
- **Couverture de Tests** : Tests automatisés insuffisants (problème connu en cours)
- **Standardisation API** : Manque d'API REST complète

#### Problèmes d'Expérience Utilisateur
- **Expérience Mobile** : Optimisation mobile limitée pour les opérations terrain
- **Complexité de Facturation** : Nécessite de la programmation PHP pour les règles de facturation personnalisées
- **Intégration Calendrier** : Processus de configuration Google Calendar complexe
- **Efficacité de Saisie de Données** : La saisie manuelle de vol pourrait être plus rationalisée

#### Limitations Opérationnelles
- **Support Multi-Tenant** : Chaque club nécessite une installation séparée
- **Collaboration Temps Réel** : Support limité pour les utilisateurs simultanés
- **Capacités Hors Ligne** : Pas de mode hors ligne pour les opérations terrain
- **Complexité d'Intégration** : Intégrations limitées avec des systèmes tiers

### 6.2 Suggestions d'Optimisation

#### Améliorations Court Terme (6-12 mois)
1. **Interface Mobile Améliorée** : Améliorer le design responsive pour une meilleure expérience mobile
2. **Configuration Facturation Simplifiée** : Créer une configuration des règles de facturation basée sur une interface graphique
3. **Tests Automatisés** : Augmenter la couverture de tests pour permettre un développement confiant
4. **Documentation API** : Documentation API complète pour les intégrations
5. **Optimisation Performance** : Optimisation des requêtes de base de données et mise en cache

#### Améliorations Moyen Terme (1-2 ans)
1. **Migration Framework** : Migration graduelle vers un framework PHP moderne (Laravel, Symfony)
2. **Fonctionnalités Temps Réel** : Intégration WebSocket pour les mises à jour en direct
3. **Rapports Avancés** : Tableaux de bord et analyses de business intelligence
4. **Application Mobile** : Application mobile native pour les opérations terrain
5. **Services Cloud** : Option SaaS pour les petits clubs

#### Vision Long Terme (2-5 ans)
1. **Architecture Multi-Tenant** : Installation unique supportant plusieurs clubs
2. **Architecture Microservices** : Conception système modulaire et évolutive
3. **Intégration IA** : Analyses prédictives pour la maintenance, la météo, les opérations
4. **Intégration IoT** : Télémétrie directe des aéronefs et journalisation automatique
5. **Plateforme Fédération** : Partage de données inter-clubs et compétitions

### 6.3 Feuille de Route Possible

#### Phase 1 : Stabilisation et Modernisation
- Planification de la mise à niveau du framework
- Implémentation de tests complets
- Audit de sécurité et améliorations
- Amélioration de la documentation

#### Phase 2 : Amélioration de l'Expérience Utilisateur
- Refonte responsive mobile-first
- Interfaces de configuration simplifiées
- Optimisation des performances
- Accessibilité améliorée

#### Phase 3 : Expansion des Fonctionnalités
- Analyses et rapports avancés
- Intégrations améliorées
- Automatisation des workflows
- Fonctionnalités de collaboration temps réel

#### Phase 4 : Évolution de la Plateforme
- Architecture multi-tenant
- Options de déploiement cloud
- Développement d'écosystème API
- Fonctionnalités de fédération avancées

---

## 7. Métriques de Succès

### 7.1 Métriques d'Adoption
- Nombre d'installations de clubs actives
- Taux d'engagement et de rétention des utilisateurs
- Utilisation des fonctionnalités selon les différents rôles utilisateur
- Temps de formation pour les nouveaux administrateurs de club

### 7.2 Métriques de Performance
- Temps de réponse du système et disponibilité
- Performance de la base de données et optimisation des requêtes
- Évaluations de l'expérience mobile
- Taux de succès des intégrations

### 7.3 Métriques de Valeur Métier
- Améliorations de l'efficacité opérationnelle du club
- Gains de temps dans les tâches administratives
- Précision de la facturation et taux d'automatisation
- Scores de satisfaction des membres

---

## 8. Évaluation des Risques

### 8.1 Risques Techniques
- **Framework Legacy** : Difficulté croissante à maintenir CodeIgniter 2.x
- **Compatibilité PHP** : Incompatibilités futures avec les versions PHP
- **Vulnérabilités de Sécurité** : Préoccupations de sécurité du framework vieillissant
- **Limites de Scalabilité** : Problèmes de mise à l'échelle de l'architecture mono-tenant

### 8.2 Risques Opérationnels
- **Dépendance aux Développeurs** : Pool limité de développeurs familiers avec la pile legacy
- **Fragilité d'Intégration** : Dépendances aux services externes (Google, FFVP)
- **Migration de Données** : Chemins de mise à niveau complexes pour les installations existantes
- **Complexité de Personnalisation** : Développement de modules de facturation spécifiques au club

### 8.3 Stratégies d'Atténuation
- **Modernisation Graduelle** : Approche de migration incrémentale du framework
- **Construction de Communauté** : Élargir la communauté de développeurs et la documentation
- **Standardisation** : Réduire les implémentations personnalisées via la configuration
- **Systèmes de Sauvegarde** : Procédures robustes de sauvegarde et de reprise après sinistre

---

## Conclusion

GVV représente une solution mature et riche en fonctionnalités pour la gestion de clubs de vol à voile qui a évolué pendant 14 ans pour répondre aux besoins spécifiques des clubs aéronautiques. Bien que l'implémentation actuelle serve efficacement sa base d'utilisateurs, la dette technique identifiée et les opportunités de modernisation présentent un chemin clair pour le développement futur. La force du système réside dans son ensemble complet de fonctionnalités et sa compréhension approfondie des opérations des clubs de vol à voile, tandis que ses principaux défis découlent de la fondation technique vieillissante et des exigences de personnalisation complexes.

La feuille de route proposée équilibre les besoins de stabilité immédiats avec l'évolution à long terme de la plateforme, assurant un service continu aux utilisateurs existants tout en se positionnant pour la croissance et la modernisation futures.