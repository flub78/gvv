# Plan de Développement - Gestion des Procédures GVV

## Vue d'ensemble

Développement d'un système CRUD complet pour la gestion des procédures dans GVV, avec support markdown et gestion de fichiers attachés.

## Architecture

### Structure des données
- **Table**: `procedures`
- **Fichiers**: `uploads/procedures/{name}/`
  - `procedure_{name}.md` - Contenu principal en markdown
  - Autres fichiers attachés (images, PDFs, etc.)

### Composants principaux
1. **Migration base de données** - Table procedures
2. **Modèle** - Procedures_model
3. **Contrôleur** - Procedures
4. **Librairie** - File_manager (réutilisable pour attachments)
5. **Vues** - Interface CRUD complète
6. **Tests** - Suite de tests PHPUnit

---

## Étape 1: Analyse et Préparation

### 1.1 Analyse de l'existant
- [x] Étudier le système d'attachments actuel
- [x] Comprendre la structure des sections
- [x] Analyser les patterns GVV existants

### 1.2 Conception de la base de données
- [ ] Définir le schéma de la table `procedures`
- [ ] Planifier les relations avec `sections`
- [ ] Prévoir l'évolutivité

### 1.3 Conception de l'architecture fichiers
- [ ] Structure des dossiers `uploads/procedures/`
- [ ] Conventions de nommage
- [ ] Gestion des permissions

---

## Étape 2: Migration Base de Données

### 2.1 Création de la migration
- [ ] Créer `044_procedures.php`
- [ ] Définir la structure de table
- [ ] Ajouter contraintes et index
- [ ] Tester la migration up/down

### 2.2 Mise à jour de la configuration
- [ ] Mettre à jour `application/config/migration.php`
- [ ] Vérifier l'intégrité migratoire

---

## Étape 3: Librairie File_manager

### 3.1 Création de la librairie générique
- [ ] Créer `application/libraries/File_manager.php`
- [ ] Fonctions de base: upload, delete, list, validate
- [ ] Support des types de fichiers (markdown, images, PDF)
- [ ] Gestion des permissions et sécurité

### 3.2 Refactoring des attachments
- [ ] Adapter le contrôleur attachments pour utiliser File_manager
- [ ] Migrer les fonctions existantes
- [ ] Tester la compatibilité

---

## Étape 4: Modèle Procedures

### 4.1 Création du modèle
- [ ] Créer `application/models/procedures_model.php`
- [ ] Étendre Common_Model
- [ ] Implémenter les méthodes spécifiques

### 4.2 Métadonnées
- [ ] Ajouter définitions dans `Gvvmetadata.php`
- [ ] Définir types, sélecteurs, validations
- [ ] Configurer l'affichage

---

## Étape 5: Contrôleur Procedures

### 5.1 Création du contrôleur CRUD
- [ ] Créer `application/controllers/procedures.php`
- [ ] Étendre Gvv_Controller
- [ ] Implémenter les méthodes CRUD de base

### 5.2 Fonctionnalités spécifiques
- [ ] Upload de fichier markdown
- [ ] Rendu markdown pour visualisation
- [ ] Gestion des fichiers attachés
- [ ] Filtrage par section

---

## Étape 6: Vues Interface Utilisateur

### 6.1 Vues principales
- [ ] `procedures/bs_tableView.php` - Liste des procédures
- [ ] `procedures/bs_formView.php` - Formulaire création/édition
- [ ] `procedures/bs_view.php` - Visualisation procédure (rendu markdown)
- [ ] `procedures/bs_attachments.php` - Gestion fichiers attachés

### 6.2 Intégration navigation
- [ ] Ajouter menu dans `bs_menu.php`
- [ ] Configurer les autorisations
- [ ] Ajouter au dashboard si approprié

---

## Étape 7: Fonctionnalités Avancées

### 7.1 Upload et rendu markdown
- [ ] Interface upload fichier markdown
- [ ] Validation format et contenu
- [ ] Rendu avec support images relatives
- [ ] Prévisualisation en temps réel

### 7.2 Gestion des fichiers attachés
- [ ] Interface drag & drop pour fichiers
- [ ] Validation types autorisés
- [ ] Gestion des images dans markdown
- [ ] Download et suppression fichiers

---

## Étape 8: Tests et Validation

### 8.1 Tests unitaires
- [ ] `Procedures_modelTest.php` - Tests du modèle
- [ ] `File_managerTest.php` - Tests de la librairie
- [ ] `ProceduresControllerTest.php` - Tests du contrôleur

### 8.2 Tests d'intégration
- [ ] Test complet CRUD
- [ ] Test upload/download fichiers
- [ ] Test rendu markdown
- [ ] Test gestion permissions

### 8.3 Tests fonctionnels
- [ ] Navigation et interface
- [ ] Performance avec gros fichiers
- [ ] Sécurité uploads
- [ ] Compatibilité navigateurs

---

## Étape 9: Documentation et Finalisation

### 9.1 Documentation technique
- [ ] Documentation API File_manager
- [ ] Guide développeur procédures
- [ ] Documentation base de données

### 9.2 Documentation utilisateur
- [ ] Guide création procédures
- [ ] Guide upload fichiers
- [ ] Guide markdown GVV

### 9.3 Finalisation
- [ ] Revue code qualité
- [ ] Optimisation performances
- [ ] Nettoyage code temporaire

---

## TODO Liste Détaillée

### Phase 1: Fondations (1-2 jours)
- [ ] **P1.1** Analyser attachments_model.php et comprendre les patterns
- [ ] **P1.2** Créer le schéma de base de données procedures
- [ ] **P1.3** Créer la migration 044_procedures.php
- [ ] **P1.4** Tester la migration up/down
- [ ] **P1.5** Créer la structure uploads/procedures/

### Phase 2: Librairie File_manager (1 jour)
- [ ] **P2.1** Créer File_manager.php avec fonctions de base
- [ ] **P2.2** Implémenter upload sécurisé avec validation
- [ ] **P2.3** Implémenter gestion dossiers et fichiers
- [ ] **P2.4** Créer tests unitaires File_manager
- [ ] **P2.5** Documenter l'API de la librairie

### Phase 3: Modèle et Métadonnées (0.5 jour)
- [ ] **P3.1** Créer procedures_model.php
- [ ] **P3.2** Ajouter métadonnées dans Gvvmetadata.php
- [ ] **P3.3** Configurer sélecteurs et validations
- [ ] **P3.4** Tester le modèle avec données sample

### Phase 4: Contrôleur CRUD (1 jour)
- [ ] **P4.1** Créer procedures.php contrôleur
- [ ] **P4.2** Implémenter méthodes CRUD standard
- [ ] **P4.3** Ajouter gestion upload markdown
- [ ] **P4.4** Ajouter rendu markdown pour visualisation
- [ ] **P4.5** Intégrer File_manager pour attachments

### Phase 5: Interface Utilisateur (1-2 jours)
- [ ] **P5.1** Créer bs_tableView.php avec liste procédures
- [ ] **P5.2** Créer bs_formView.php avec upload markdown
- [ ] **P5.3** Créer bs_view.php avec rendu markdown
- [ ] **P5.4** Intégrer gestion fichiers attachés
- [ ] **P5.5** Ajouter navigation et menus

### Phase 6: Tests et Validation (1 jour)
- [ ] **P6.1** Créer suite tests unitaires
- [ ] **P6.2** Créer tests intégration CRUD
- [ ] **P6.3** Tester upload/validation fichiers
- [ ] **P6.4** Tester rendu markdown et images
- [ ] **P6.5** Validation sécurité et permissions

### Phase 7: Finalisation (0.5 jour)
- [ ] **P7.1** Documentation technique complète
- [ ] **P7.2** Revue code et optimisations
- [ ] **P7.3** Tests finaux et validation
- [ ] **P7.4** Nettoyage et livraison

---

## Estimation Totale: 5-7 jours de développement

## Livrables Attendus

1. **Migration**: `044_procedures.php`
2. **Librairie**: `File_manager.php` (réutilisable)
3. **Modèle**: `procedures_model.php`
4. **Contrôleur**: `procedures.php`
5. **Vues**: Interface CRUD complète
6. **Tests**: Suite PHPUnit complète
7. **Documentation**: Technique et utilisateur

## Critères de Succès

- [ ] CRUD complet fonctionnel
- [ ] Upload et rendu markdown parfait
- [ ] Gestion fichiers attachés opérationnelle
- [ ] Tests passent à 100%
- [ ] Code respecte les standards GVV
- [ ] Performance acceptable avec gros fichiers
- [ ] Sécurité validée (uploads, permissions)
- [ ] Documentation complète

---

*Ce plan sera mis à jour au fur et à mesure de l'avancement du développement.*