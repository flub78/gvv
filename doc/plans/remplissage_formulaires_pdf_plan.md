# Plan d'implémentation — Remplissage de Formulaires PDF

Date : 7 février 2026

## Références
- PRD : [doc/prds/remplissage_formulaires_pdf_prd.md](../prds/remplissage_formulaires_pdf_prd.md)
- Design : [doc/design_notes/remplissage_pdf.md](../design_notes/remplissage_pdf.md)
- Dépendance : archivage documentaire (PRD archivage_documentaire)

## Objectif
Permettre le remplissage automatique de formulaires PDF officiels (DGAC, FFPLUM, etc.) à partir des données GVV, avec upload de templates, mapping configurable, génération et archivage.

## Hypothèses
- Python 3 avec PyPDF2 disponible sur le serveur.
- Les formulaires PDF cibles sont au format AcroForm (ISO 32000).
- Le système d'archivage documentaire (migration 067) est en place.
- Migration suivante : n°068.
- Le formulaire prioritaire est le `134i-Formlic` (attestation début de formation ULM).
- Pas d'édition manuelle des champs avant génération (V1).
- Génération pour un seul jeu de contextes à la fois (pas de génération en lot).

## Tâches à réaliser

### Lot 1 — Script Python d'extraction et remplissage PDF

- [ ] Créer le script `bin/pdf_forms.py` avec deux commandes :
  - `extract <pdf_file>` : extraction des champs AcroForm au format JSON (nom, type, valeur par défaut)
  - `fill <pdf_file> <output> <json_data>` : remplissage d'un PDF avec des données JSON
- [ ] Gérer les types de champs : texte, checkbox, radio, liste déroulante
- [ ] Gérer l'encodage UTF-8 (caractères accentués)
- [ ] Ajouter la gestion d'erreurs (PDF sans champs, fichier invalide, champs inconnus)
- [ ] Tester manuellement avec le formulaire `134iFormlic.pdf`
- [ ] Écrire un test PHPUnit vérifiant l'appel au script Python et le parsing du JSON retourné

### Lot 2 — Migration base de données (068)

- [ ] Créer la migration `068_pdf_templates.php` avec les tables :
  - `pdf_templates` : id, code, nom, description, fichier, champs (JSON), contextes (JSON), created_at, updated_at
  - `pdf_template_mappings` : id, template_id, champ_pdf, source_type, source_value, format, contexte
- [ ] Ajouter un type de document dans `document_types` pour les formulaires PDF générés (code `formulaire_pdf`, scope `pilot`)
- [ ] Ajouter les index et clés étrangères
- [ ] Mettre à jour `application/config/migration.php` (version 68)
- [ ] Écrire un test PHPUnit de migration : up, vérification du schéma, down

### Lot 3 — Modèles

- [ ] Créer `application/models/pdf_templates_model.php` :
  - CRUD templates (insert, update, delete, get, get_all)
  - Stockage/récupération du cache des champs extraits
  - Récupération des contextes requis par template
- [ ] Créer `application/models/pdf_template_mappings_model.php` :
  - CRUD mappings par template
  - Sauvegarde en lot (remplacement de tous les mappings d'un template)
  - Récupération des mappings par template_id
- [ ] Écrire les tests PHPUnit des modèles (CRUD, validation, contraintes)

### Lot 4 — Bibliothèque Pdf_form_filler

- [ ] Créer `application/libraries/Pdf_form_filler.php` avec les méthodes :
  - `extract_fields($pdf_path)` : appel au script Python, retour tableau PHP
  - `collect_data($mapping, $contextes)` : collecte des données selon le mapping
    - Résolution `table` : requête SELECT sur table.colonne avec filtre contexte
    - Résolution `config` : lecture `$this->config->item()`
    - Résolution `constant` : valeur directe
    - Résolution `expression` : évaluation d'expression SQL
    - Résolution `date` : date courante formatée
  - `fill($template_id, $contextes, $output_path)` : orchestration complète (chargement mapping → collecte → appel Python → PDF généré)
  - `archive($pdf_path, $template, $pilote_login)` : archivage via `archived_documents_model->create_document()` avec le type de document `formulaire_pdf`
- [ ] Valider que les colonnes référencées dans un mapping existent (introspection BDD)
- [ ] Écrire les tests PHPUnit de la bibliothèque (mock du script Python, résolution des sources)

### Lot 5 — Métadonnées

- [ ] Ajouter les définitions de champs dans `Gvvmetadata.php` pour les tables :
  - `pdf_templates` : code, nom, description, fichier, champs, contextes, dates
  - `pdf_template_mappings` : champ_pdf, source_type (enum), source_value, format, contexte
- [ ] Définir les enums pour `source_type` : table, config, constant, expression, date
- [ ] Définir les selectors nécessaires (template_id → nom du template)

### Lot 6 — Contrôleur et routes

- [ ] Créer `application/controllers/pdf_forms.php` avec les actions :
  - `index()` : liste des templates disponibles
  - `upload()` : formulaire d'upload + traitement POST (validation, extraction champs, création template)
  - `mapping($template_id)` : affichage et sauvegarde du mapping pour un template
  - `delete_template($template_id)` : suppression d'un template
  - `generate($template_id)` : formulaire de sélection des contextes + génération du PDF + archivage via `archived_documents`
- [ ] Implémenter le contrôle d'accès :
  - Administrateur : toutes les actions
  - Instructeur : generate, download, history (ses générations uniquement)
  - Pilote : consultation des documents archivés (via archived_documents)
- [ ] Ajouter les routes dans `application/config/routes.php` si nécessaire

### Lot 7 — Vues

- [ ] Créer `application/views/pdf_forms/` avec les vues :
  - `bs_index.php` : liste des templates (tableau avec nom, description, nb champs, actions)
  - `bs_upload.php` : formulaire d'upload avec drag & drop, validation côté client (type PDF, taille max 10 Mo)
  - `bs_mapping.php` : tableau éditable du mapping (colonnes : champ PDF, type source, valeur source, format, contexte) avec aide contextuelle
  - `bs_generate.php` : sélection des contextes (selectors pilote/instructeur), aperçu des données, bouton générer (l'historique des documents générés est consultable via le module archived_documents)
- [ ] Utiliser Bootstrap 5 pour tous les composants UI
- [ ] Ajouter les feedbacks utilisateur : messages de succès, erreurs, spinners pendant la génération
- [ ] Intégrer la prévisualisation des données avant génération (tableau récapitulatif)

### Lot 8 — Intégration menu et navigation

- [ ] Ajouter les entrées de menu dans `bs_menu.php` :
  - Administration → Formulaires PDF (templates, mapping)
  - Outils → Générer un formulaire (accès instructeur/admin)
- [ ] Vérifier la visibilité des menus selon les rôles

### Lot 9 — Internationalisation

- [ ] Créer les fichiers de langue :
  - `application/language/french/pdf_forms_lang.php`
  - `application/language/english/pdf_forms_lang.php`
  - `application/language/dutch/pdf_forms_lang.php`
- [ ] Définir les libellés pour : titres de pages, boutons, messages d'erreur, labels de formulaire, colonnes de tableau, messages de confirmation
- [ ] Vérifier que toutes les chaînes UI utilisent `$this->lang->line()`

### Lot 10 — Stockage des fichiers

- [ ] Créer le répertoire `uploads/pdf_templates/` pour les templates PDF
- [ ] Configurer les permissions d'accès (chmod +wx)
- [ ] Les PDF générés sont stockés via le module `archived_documents` (répertoire `uploads/documents/`)

### Lot 11 — Tests unitaires et d'intégration

- [ ] Tests du script Python (`bin/pdf_forms.py`) :
  - Extraction de champs d'un PDF AcroForm de test
  - Remplissage d'un PDF et vérification du résultat
  - Gestion des erreurs (PDF invalide, sans champs)
- [ ] Tests des modèles PHP :
  - CRUD pdf_templates
  - CRUD pdf_template_mappings (sauvegarde en lot, contraintes d'unicité)
  - Intégration avec `archived_documents_model` (archivage, historique)
- [ ] Tests de la bibliothèque Pdf_form_filler :
  - Résolution des sources de données (table, config, constant, expression, date)
  - Validation des colonnes référencées
  - Orchestration complète avec mock Python
- [ ] Tests du contrôleur :
  - Upload avec validation (type MIME, taille, présence de champs AcroForm)
  - Contrôle d'accès par rôle
  - Génération et téléchargement
- [ ] Tests de migration (up/down, vérification schéma)

### Lot 12 — Tests Playwright (end-to-end)

- [ ] Smoke test : accès à la page d'index des formulaires PDF
- [ ] Test upload : upload d'un PDF de test, vérification extraction des champs
- [ ] Test mapping : configuration du mapping pour un template, sauvegarde et relecture
- [ ] Test génération : sélection d'un template et de contextes, génération d'un PDF, téléchargement
- [ ] Test contrôle d'accès : vérifier que les instructeurs ne peuvent pas accéder aux fonctions admin
- [ ] Test archivage : générer un PDF, vérifier sa présence dans archived_documents du pilote

### Lot 13 — Formulaire pilote : 134i-Formlic

- [ ] Uploader le formulaire `134iFormlic.pdf` comme template de démonstration
- [ ] Configurer le mapping complet selon la documentation du design (sections candidat, instructeur, validation)
- [ ] Identifier les champs manquants dans la table `membres` pour les qualifications instructeur :
  - Évaluer : ajout de champs à `membres` vs création d'une table `qualifications_instructeur`
  - Créer la migration correspondante si nécessaire
- [ ] Tester la génération complète avec des données réelles de test
- [ ] Valider l'encodage des caractères accentués dans le PDF généré

### Lot 14 — Documentation utilisateur

- [ ] Rédiger le guide utilisateur dans `doc/user_guide/` ou section dédiée :
  - Comment uploader un template PDF
  - Comment configurer le mapping des champs
  - Comment générer un formulaire rempli
  - Comment archiver un document généré
- [ ] Ajouter des captures d'écran ou descriptions des écrans principaux
- [ ] Documenter les types de sources disponibles et leur syntaxe (table, config, constant, expression, date)
- [ ] Documenter les limitations connues (AcroForm uniquement, pas d'édition manuelle)

### Lot 15 — Validation finale et nettoyage

- [ ] Exécuter `./run-all-tests.sh` — tous les tests passent
- [ ] Exécuter les tests Playwright — tous les tests passent
- [ ] Vérifier les performances : génération d'un PDF en moins de 5 secondes
- [ ] Vérifier la sécurité : validation des uploads, contrôle d'accès, nettoyage fichiers temporaires
- [ ] Vérifier l'encodage UTF-8 sur les PDF générés (accents, caractères spéciaux)
- [ ] Nettoyer les fichiers temporaires de test
- [ ] Revue de code finale

## Ordre de réalisation recommandé

1. **Lot 1** (script Python) — fondation technique, indépendant
2. **Lot 2** (migration) — schéma BDD nécessaire pour tout le reste
3. **Lot 3** (modèles) — dépend du lot 2
4. **Lot 4** (bibliothèque) — dépend des lots 1 et 3
5. **Lot 5** (métadonnées) — dépend du lot 2
6. **Lot 10** (stockage) — peut être fait en parallèle des lots 3-5
7. **Lot 9** (i18n) — peut être fait en parallèle des lots 3-5
8. **Lot 6** (contrôleur) — dépend des lots 3, 4, 5
9. **Lot 7** (vues) — dépend des lots 5, 6
10. **Lot 8** (menu) — dépend du lot 6
11. **Lot 11** (tests unitaires) — au fur et à mesure des lots 1-6
12. **Lot 12** (tests Playwright) — après les lots 6-8
13. **Lot 13** (formulaire pilote) — après le lot 8
14. **Lot 14** (documentation) — après le lot 13
15. **Lot 15** (validation finale) — dernier

## Critères de fin
- Upload, extraction, mapping, génération et téléchargement fonctionnels de bout en bout.
- Formulaire 134i-Formlic opérationnel avec données de test.
- Archivage des PDF générés dans le système documentaire.
- Contrôle d'accès par rôle (admin, instructeur, pilote) vérifié.
- Tests PHPUnit et Playwright green.
- Documentation utilisateur rédigée.
- Encodage UTF-8 validé sur les PDF générés.
