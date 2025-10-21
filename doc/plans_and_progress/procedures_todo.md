# TODO Liste - Gestion des Procédures GVV

## Phase 1: Fondations ✅ COMPLÉTÉE
- [x] **P1.1** Analyser attachments_model.php et comprendre les patterns
- [x] **P1.2** Créer le schéma de base de données procedures  
- [x] **P1.3** Créer la migration 044_procedures.php
- [x] **P1.4** Tester et corriger la migration (résolu problème FK)
- [x] **P1.5** Mettre à jour application/config/migration.php (version 44)

## Phase 2: Librairie File_manager ✅ COMPLÉTÉE  
- [x] **P2.1** Créer File_manager.php avec fonctions de base
- [x] **P2.2** Implémenter upload sécurisé avec validation
- [x] **P2.3** Implémenter gestion dossiers et fichiers
- [ ] **P2.4** Créer tests unitaires File_manager
- [ ] **P2.5** Documenter l'API de la librairie

## Phase 3: Modèle et Métadonnées ✅ COMPLÉTÉE
- [x] **P3.1** Créer procedures_model.php
- [x] **P3.2** Ajouter métadonnées dans Gvvmetadata.php (corrigé type int)
- [x] **P3.3** Configurer sélecteurs et validations
- [x] **P3.4** Créer structure dossiers et fichiers exemple

## Phase 4: Contrôleur CRUD ✅ COMPLÉTÉE
- [x] **P4.1** Créer procedures.php contrôleur
- [x] **P4.2** Implémenter méthodes CRUD standard (index, view, create, edit, delete)
- [x] **P4.3** Ajouter gestion upload markdown
- [x] **P4.4** Ajouter rendu markdown pour visualisation
- [x] **P4.5** Intégrer File_manager pour attachments
- [x] **P4.6** Créer vues principales (tableView, view, formView, attachments)
- [x] **P4.7** Créer fichier de langue français
- [x] **P4.8** Valider syntaxe de tous les fichiers

## Phase 5: Interface Utilisateur ⏳ PROCHAINE ÉTAPE
- [x] **P5.1** Créer bs_tableView.php avec liste procédures
- [x] **P5.2** Créer bs_formView.php avec upload markdown
- [x] **P5.3** Créer bs_view.php avec rendu markdown
- [x] **P5.4** Créer bs_attachments.php pour gestion fichiers
- [ ] **P5.5** Ajouter navigation et menus dans bs_menu.php

## Phase 6: Tests et Validation ⏸️ EN ATTENTE
- [ ] **P6.1** Créer suite tests unitaires
- [ ] **P6.2** Créer tests intégration CRUD
- [ ] **P6.3** Tester upload/validation fichiers
- [ ] **P6.4** Tester rendu markdown et images
- [ ] **P6.5** Validation sécurité et permissions

## Phase 7: Finalisation ⏸️ EN ATTENTE
- [ ] **P7.1** Documentation technique complète
- [ ] **P7.2** Revue code et optimisations
- [ ] **P7.3** Tests finaux et validation
- [ ] **P7.4** Nettoyage et livraison

---

## ✅ Problèmes Résolus

### Foreign Key Constraint Error
**Problème**: Erreur 1005 "Foreign key constraint is incorrectly formed"
**Cause**: Type `tinyint(4)` dans procedures.section_id vs `int(11)` dans sections.id
**Solution**: Changé `tinyint(4)` → `int(11)` dans migration et métadonnées

### Migration Réussie
- ✅ Table `procedures` créée avec succès
- ✅ Contraintes et index en place
- ✅ Données d'exemple insérées
- ✅ Structure dossiers créée manuellement
- ✅ Fichiers markdown exemple créés

---

## 🎯 Actions Immédiates (prochaine session)

1. **Créer contrôleur procedures.php** - Pattern Gvv_Controller
2. **Tester modèle via interface web** - Valider CRUD complet
3. **Créer vues minimales** - Liste et visualisation de base
4. **Ajouter navigation** - Menu procédures dans bs_menu.php

### 📋 Structure Créée et Validée

```
uploads/procedures/
├── example_procedure/
│   └── procedure_example_procedure.md (700 chars)
├── maintenance_planeur/
│   └── procedure_maintenance_planeur.md (838 chars)
└── .htaccess (protection)

application/
├── migrations/044_procedures.php ✅
├── models/procedures_model.php ✅
├── libraries/File_manager.php ✅
└── libraries/Gvvmetadata.php ✅ (modifié)
```

### 💾 Base de Données
- Table `procedures` opérationnelle
- 2 procédures d'exemple insérées
- Relations avec `sections` fonctionnelles
- Migrations à jour (version 44)

---

## État d'Avancement: 80% ✅✅✅✅✅⏳⏸️

**Dernière mise à jour:** 20 octobre 2025, 00:15

**Étape courante:** Interface complète, navigation à ajouter