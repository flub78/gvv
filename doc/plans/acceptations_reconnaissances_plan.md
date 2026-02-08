# Plan d'implémentation — Système d'Acceptations et Reconnaissances

Date : 8 février 2026

## Références

- PRD : [doc/prds/approbation_de_documents_prd.md](../prds/approbation_de_documents_prd.md)
- Infrastructure existante : Module archivage documentaire (migration 067, contrôleur `archived_documents`, modèles)

## Objectif

Livrer un système complet d'acceptation et reconnaissance de documents, formations, contrôles et autorisations, avec :
- Acceptation simple (un clic) pour utilisateurs internes
- Double validation (instructeur/élève) pour les formations et contrôles
- Signature externe (directe, lien, QR code, papier) pour utilisateurs non-membres
- Autorisation parentale pour mineurs
- Rattachement différé d'une acceptation externe au dossier d'un pilote
- Traçabilité complète avec horodatage

## Analyse de l'existant

### Infrastructure réutilisable
- **Module archivage documentaire** : tables `document_types` et `archived_documents` (migration 067), contrôleur, modèles et vues déjà en place
- **Bibliothèque QR code** : `application/third_party/phpqrcode/` prête à l'emploi
- **TCPDF** : génération PDF pour les formulaires imprimables
- **File_compressor** : compression images/PDF pour les uploads
- **Système email** : helpers et contrôleur existants
- **Gvvmetadata** : système de métadonnées pour formulaires et tables

### Gaps identifiés
- Pas de signature tactile (signature pad JavaScript)
- Pas de viewer PDF intégré (PDF.js) — le navigateur affiche nativement
- Pas de système de liens temporaires / tokens
- Pas de page publique (sans authentification)
- Pas de notifications automatiques à la connexion

## Architecture

### Nouvelles tables

| Table | Rôle |
|-------|------|
| `acceptance_items` | Éléments à faire accepter (documents, formations, briefings) |
| `acceptance_records` | Enregistrements d'acceptation/refus par personne |
| `acceptance_signatures` | Signatures externes (tactiles, uploads papier) |
| `acceptance_tokens` | Liens temporaires pour signatures externes |

### Relations avec l'existant
- `acceptance_items.category` utilise les catégories du PRD (document, formation, controle, briefing, autorisation)
- Les fichiers PDF des éléments sont stockés dans `uploads/acceptances/items/`
- Les signatures/uploads sont stockés dans `uploads/acceptances/signatures/`
- Les liens temporaires pointent vers un contrôleur public dédié

### Contrôleurs

| Contrôleur | Rôle |
|------------|------|
| `acceptance_admin` | CRUD éléments, suivi acceptations (admin) |
| `acceptance` | Acceptation interne, historique personnel (membre) |
| `acceptance_training` | Délivrance/réception formations (instructeur/élève) |
| `acceptance_external` | Initiation signature externe (pilote/responsable) |
| `acceptance_sign` | Page publique de signature (sans auth, via token) |

---

## Lots d'implémentation

### Lot 1 — Modèle de données & migration

- [x] 1.1 Concevoir le schéma `acceptance_items` (titre, catégorie, fichier PDF, type interne/externe, obligatoire, date limite, double validation, rôles, cibles utilisateurs)
- [x] 1.2 Concevoir le schéma `acceptance_records` (item_id, user_login, statut, horodatage, formule, rôle dans double validation, rattachement pilote)
- [x] 1.3 Concevoir le schéma `acceptance_signatures` (record_id, nom, prénom, qualité signataire, bénéficiaire mineur, type signature, fichier, données signature tactile)
- [x] 1.4 Concevoir le schéma `acceptance_tokens` (token, item_id, créé par, expiration, usage unique, mode)
- [x] 1.5 Créer la migration `068_acceptance_system.php`
- [x] 1.6 Mettre à jour `application/config/migration.php` (version 68)
- [x] 1.7 Écrire les tests de migration `AcceptanceSystemMigrationTest.php` (29 tests, 104 assertions)
- [x] 1.8 Valider : migration up (67→68), rollback down (68→67), re-up (67→68) — tous OK

### Lot 2 — Modèles de données

- [ ] 2.1 Créer `acceptance_items_model.php` (CRUD éléments, filtres par catégorie/statut/cible)
- [ ] 2.2 Créer `acceptance_records_model.php` (création acceptation/refus, requêtes par utilisateur, par élément, détection en attente, rattachement à un pilote)
- [ ] 2.3 Créer `acceptance_signatures_model.php` (stockage signatures tactiles et uploads)
- [ ] 2.4 Créer `acceptance_tokens_model.php` (génération token, validation, expiration, usage unique)
- [ ] 2.5 Ajouter les métadonnées dans `Gvvmetadata.php` pour les nouvelles tables
- [ ] 2.6 Écrire les tests unitaires des modèles

### Lot 3 — Administration des éléments

- [ ] 3.1 Créer le contrôleur `acceptance_admin.php` (liste, création, édition, activation/désactivation)
- [ ] 3.2 Créer la vue liste des éléments (`bs_acceptance_items_list.php`)
- [ ] 3.3 Créer le formulaire création/édition d'élément (`bs_acceptance_item_form.php`)
- [ ] 3.4 Implémenter l'upload PDF lors de la création d'un élément (réutiliser `File_compressor`)
- [ ] 3.5 Créer la vue suivi des acceptations par élément (`bs_acceptance_tracking.php`)
- [ ] 3.6 Implémenter les filtres : en attente, en retard, proches échéance, non rattachées
- [ ] 3.7 Implémenter le rattachement d'une acceptation externe à un pilote :
  - [ ] 3.7.1 Action "Rattacher à un pilote" dans le suivi des acceptations (sélecteur de membre)
  - [ ] 3.7.2 Enregistrer le rattachement (linked_pilot_login, linked_by, linked_at) sans modifier l'acceptation d'origine
  - [ ] 3.7.3 Indicateur visuel distinguant les acceptations rattachées et non rattachées
  - [ ] 3.7.4 L'acceptation rattachée apparaît dans le dossier du pilote concerné
- [ ] 3.8 Ajouter les entrées menu admin dans `bs_menu.php`
- [ ] 3.9 Fichiers de langue FR/EN/NL pour l'administration
- [ ] 3.10 Valider : test Playwright accès page admin, création d'un élément, rattachement à un pilote

### Lot 4 — Acceptation interne (utilisateurs membres)

- [ ] 4.1 Créer le contrôleur `acceptance.php` (tableau de bord, lecture, acceptation, refus, historique)
- [ ] 4.2 Créer la vue tableau de bord des éléments en attente (`bs_acceptance_dashboard.php`)
- [ ] 4.3 Implémenter le badge/notification du nombre d'éléments en attente (intégrer dans `bs_menu.php` ou layout)
- [ ] 4.4 Créer la vue lecture et acceptation (`bs_acceptance_read.php`) avec :
  - [ ] 4.4.1 Viewer PDF intégré (iframe ou PDF.js)
  - [ ] 4.4.2 Détection défilement complet (JavaScript)
  - [ ] 4.4.3 Bouton "Accepter" masqué jusqu'au défilement complet
  - [ ] 4.4.4 Bouton "Refuser" optionnel
  - [ ] 4.4.5 Bouton "Plus tard" (si date limite non atteinte)
  - [ ] 4.4.6 Message informatif en haut de page
- [ ] 4.5 Enregistrer la formule d'acceptation automatique avec horodatage
- [ ] 4.6 Créer la vue historique personnel (`bs_acceptance_history.php`)
- [ ] 4.7 Permettre de relire et modifier une réponse précédente
- [ ] 4.8 Fichiers de langue FR/EN/NL pour les membres
- [ ] 4.9 Valider : test PHPUnit workflow acceptation, test Playwright lecture et clic accepter

### Lot 5 — Double validation (formations et contrôles)

- [ ] 5.1 Créer le contrôleur `acceptance_training.php` (délivrance, confirmation, suivi)
- [ ] 5.2 Créer la vue délivrance de formation (`bs_training_deliver.php`) :
  - [ ] 5.2.1 Sélecteur d'élève
  - [ ] 5.2.2 Sélecteur de type de formation
  - [ ] 5.2.3 Date de la formation
  - [ ] 5.2.4 Bouton "Valider la délivrance"
- [ ] 5.3 Créer le mécanisme de notification à l'élève (flag en base + affichage au tableau de bord)
- [ ] 5.4 Créer la vue confirmation élève (`bs_training_confirm.php`)
- [ ] 5.5 Créer la vue suivi formations instructeur (`bs_training_instructor_list.php`)
- [ ] 5.6 Créer la vue historique formations élève (`bs_training_student_list.php`)
- [ ] 5.7 Gérer les formules automatiques (délivrance instructeur + réception élève)
- [ ] 5.8 Fichiers de langue FR/EN/NL
- [ ] 5.9 Valider : test PHPUnit double validation, test Playwright workflow complet instructeur→élève

### Lot 6 — Signature externe

- [ ] 6.1 Créer le contrôleur `acceptance_external.php` (initiation session, choix mode, suivi sessions)
- [ ] 6.2 Implémenter la génération de tokens temporaires (aléatoires, à usage unique, durée limitée 24h)
- [ ] 6.3 Créer la vue choix du mode de signature (`bs_external_initiate.php`) :
  - [ ] 6.3.1 Bouton "Présenter sur cet écran" (mode direct)
  - [ ] 6.3.2 Bouton "Envoyer un lien" (mode lien) avec copie/envoi email
  - [ ] 6.3.3 Bouton "Générer un QR code" (utiliser `phpqrcode`) avec affichage/impression
  - [ ] 6.3.4 Bouton "Mode papier" (formulaire upload)
- [ ] 6.4 Créer la vue suivi des sessions en cours (`bs_external_sessions.php`)
- [ ] 6.5 Fichiers de langue FR/EN/NL
- [ ] 6.6 Valider : test PHPUnit génération token, test Playwright initiation session

### Lot 7 — Page publique de signature (sans authentification)

- [ ] 7.1 Créer le contrôleur `acceptance_sign.php` (accès par token uniquement, sans auth CI)
- [ ] 7.2 Implémenter la validation du token (existence, expiration, usage unique)
- [ ] 7.3 Créer la vue page de signature (`bs_sign_page.php`) :
  - [ ] 7.3.1 Message informatif + viewer PDF avec défilement obligatoire
  - [ ] 7.3.2 Bouton téléchargement PDF
  - [ ] 7.3.3 Formulaire : nom, prénom du signataire
  - [ ] 7.3.4 Pour catégorie `autorisation` : champs qualité, nom/prénom bénéficiaire mineur
  - [ ] 7.3.5 Zone signature tactile (intégrer bibliothèque Signature Pad JS)
  - [ ] 7.3.6 Alternative : upload fichier signé (JPEG, PNG, PDF, max 10 Mo)
  - [ ] 7.3.7 Bouton de validation
- [ ] 7.4 Créer la vue erreur lien expiré/invalide (`bs_sign_expired.php`)
- [ ] 7.5 Enregistrer la signature (image base64 ou fichier) et l'acceptation avec horodatage
- [ ] 7.6 Marquer le token comme utilisé après signature
- [ ] 7.7 Fichiers de langue FR/EN/NL
- [ ] 7.8 Valider : test PHPUnit validation token, test Playwright parcours complet signature externe

### Lot 8 — Mode papier

- [ ] 8.1 Créer la génération PDF formulaire vierge via TCPDF (format pré-rempli avec espace signature)
- [ ] 8.2 Créer la vue upload document signé (`bs_paper_upload.php`) :
  - [ ] 8.2.1 Champs nom/prénom signataire
  - [ ] 8.2.2 Pour `autorisation` : qualité, nom/prénom bénéficiaire
  - [ ] 8.2.3 Date de signature
  - [ ] 8.2.4 Zone upload (drag & drop, formats JPEG/PNG/PDF, max 10 Mo)
  - [ ] 8.2.5 Case à cocher attestation présence pilote
  - [ ] 8.2.6 Bouton "Valider et archiver"
- [ ] 8.3 Implémenter la compression du fichier uploadé (réutiliser `File_compressor`)
- [ ] 8.4 Enregistrer l'attestation du pilote avec formule automatique
- [ ] 8.5 Fichiers de langue FR/EN/NL
- [ ] 8.6 Valider : test PHPUnit upload et archivage, test Playwright formulaire papier

### Lot 9 — Notifications

- [ ] 9.1 Implémenter la détection des éléments en attente à la connexion (hook dans le contrôleur de login ou layout)
- [ ] 9.2 Afficher un badge dans le menu avec le nombre d'éléments en attente
- [ ] 9.3 Créer le script/cron de détection des acceptations proches de la date limite
- [ ] 9.4 Implémenter l'envoi d'emails de rappel (réutiliser l'infrastructure email existante)
- [ ] 9.5 Notifications pour les doubles validations en attente (instructeur notifié quand élève n'a pas confirmé)
- [ ] 9.6 Valider : test PHPUnit détection en attente, vérifier affichage badge

### Lot 10 — Indicateurs visuels & date limite

- [ ] 10.1 Implémenter les indicateurs visuels Bootstrap 5 :
  - [ ] 10.1.1 Badge "en retard" (rouge) après date limite
  - [ ] 10.1.2 Badge "proche échéance" (orange) dans les X jours avant
  - [ ] 10.1.3 Badge "en attente" (bleu)
  - [ ] 10.1.4 Badge "accepté" (vert) / "refusé" (gris)
- [ ] 10.2 Afficher clairement "À accepter avant le [date]" sur chaque élément
- [ ] 10.3 Implémenter le filtre admin : en retard, proches échéance, en attente
- [ ] 10.4 Valider : vérification visuelle des badges, test Playwright

### Lot 11 — Export et rapports

- [ ] 11.1 Implémenter l'export CSV des acceptations par élément (admin)
- [ ] 11.2 Implémenter l'export de la liste des personnes n'ayant pas encore accepté
- [ ] 11.3 Valider : test PHPUnit format CSV, test Playwright téléchargement

### Lot 12 — Internationalisation complète

- [ ] 12.1 Vérifier que tous les libellés UI utilisent `$this->lang->line()`
- [ ] 12.2 Compléter les traductions EN et NL
- [ ] 12.3 Vérifier les formules d'acceptation dans les 3 langues
- [ ] 12.4 Valider : revue des fichiers de langue, aucune chaîne en dur dans les vues

### Lot 13 — Tests finaux & intégration

- [ ] 13.1 Exécuter `./run-all-tests.sh` — tous les tests PHPUnit passent
- [ ] 13.2 Tests Playwright smoke : accès aux pages admin, membre, externe
- [ ] 13.3 Test Playwright E2E : parcours complet acceptation interne
- [ ] 13.4 Test Playwright E2E : parcours complet double validation formation
- [ ] 13.5 Test Playwright E2E : parcours complet signature externe (lien + QR code)
- [ ] 13.6 Test Playwright E2E : parcours mode papier
- [ ] 13.7 Test Playwright E2E : autorisation parentale
- [ ] 13.8 Test Playwright E2E : rattachement d'une acceptation externe au dossier d'un pilote
- [ ] 13.9 Vérifier les permissions (rôles admin, membre, instructeur, externe)
- [ ] 13.10 Vérifier le nettoyage des tokens expirés
- [ ] 13.11 Revue de sécurité : tokens non devinables, expiration, CSRF, upload sécurisé, XSS

---

## Schéma de base de données proposé

### Table `acceptance_items`

```sql
CREATE TABLE `acceptance_items` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL COMMENT 'Titre de l element',
  `category` ENUM('document','formation','controle','briefing','autorisation') NOT NULL COMMENT 'Categorie acceptation',
  `pdf_path` VARCHAR(255) NULL COMMENT 'Chemin fichier PDF',
  `target_type` ENUM('internal','external') NOT NULL DEFAULT 'internal' COMMENT 'Interne ou externe',
  `version_date` DATE NULL COMMENT 'Date de creation/version',
  `mandatory` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Obligatoire',
  `deadline` DATE NULL COMMENT 'Date limite acceptation',
  `dual_validation` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Necessite double validation',
  `role_1` VARCHAR(64) NULL COMMENT 'Premier role (ex: instructeur)',
  `role_2` VARCHAR(64) NULL COMMENT 'Second role (ex: eleve)',
  `target_roles` VARCHAR(255) NULL COMMENT 'Roles cibles separes par virgule (pilotes, instructeurs, bureau)',
  `active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Element actif',
  `created_by` VARCHAR(25) NOT NULL COMMENT 'Administrateur createur',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_active` (`active`),
  KEY `idx_deadline` (`deadline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

### Table `acceptance_records`

```sql
CREATE TABLE `acceptance_records` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id` INT(11) UNSIGNED NOT NULL COMMENT 'Element concerne',
  `user_login` VARCHAR(25) NULL COMMENT 'Membre (NULL si externe)',
  `external_name` VARCHAR(128) NULL COMMENT 'Nom complet personne externe',
  `status` ENUM('pending','accepted','refused') NOT NULL DEFAULT 'pending',
  `validation_role` VARCHAR(64) NULL COMMENT 'Role dans double validation',
  `partner_record_id` BIGINT(20) UNSIGNED NULL COMMENT 'Enregistrement partenaire (double validation)',
  `formula_text` TEXT NULL COMMENT 'Formule enregistree',
  `acted_at` DATETIME NULL COMMENT 'Date action',
  `created_at` DATETIME NOT NULL,
  `initiated_by` VARCHAR(25) NULL COMMENT 'Responsable ayant initie (si externe)',
  `signature_mode` ENUM('direct','link','qrcode','paper') NULL COMMENT 'Mode signature externe',
  `linked_pilot_login` VARCHAR(25) NULL COMMENT 'Pilote rattache ulterieurement (acceptation externe)',
  `linked_by` VARCHAR(25) NULL COMMENT 'Utilisateur ayant effectue le rattachement',
  `linked_at` DATETIME NULL COMMENT 'Date du rattachement',
  PRIMARY KEY (`id`),
  KEY `idx_item` (`item_id`),
  KEY `idx_user` (`user_login`),
  KEY `idx_status` (`status`),
  KEY `idx_partner` (`partner_record_id`),
  CONSTRAINT `fk_acceptance_records_item` FOREIGN KEY (`item_id`)
    REFERENCES `acceptance_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_acceptance_records_user` FOREIGN KEY (`user_login`)
    REFERENCES `membres` (`mlogin`) ON DELETE SET NULL,
  CONSTRAINT `fk_acceptance_records_partner` FOREIGN KEY (`partner_record_id`)
    REFERENCES `acceptance_records` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_acceptance_records_linked_pilot` FOREIGN KEY (`linked_pilot_login`)
    REFERENCES `membres` (`mlogin`) ON DELETE SET NULL,
  CONSTRAINT `fk_acceptance_records_linked_by` FOREIGN KEY (`linked_by`)
    REFERENCES `membres` (`mlogin`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

### Table `acceptance_signatures`

```sql
CREATE TABLE `acceptance_signatures` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `record_id` BIGINT(20) UNSIGNED NOT NULL,
  `signer_first_name` VARCHAR(64) NOT NULL,
  `signer_last_name` VARCHAR(64) NOT NULL,
  `signer_quality` VARCHAR(64) NULL COMMENT 'pere, mere, tuteur legal (pour autorisation)',
  `beneficiary_first_name` VARCHAR(64) NULL COMMENT 'Prenom mineur (pour autorisation)',
  `beneficiary_last_name` VARCHAR(64) NULL COMMENT 'Nom mineur (pour autorisation)',
  `signature_type` ENUM('tactile','upload') NOT NULL,
  `signature_data` MEDIUMTEXT NULL COMMENT 'Donnees base64 signature tactile',
  `file_path` VARCHAR(255) NULL COMMENT 'Chemin fichier uploade',
  `original_filename` VARCHAR(255) NULL,
  `file_size` INT(11) UNSIGNED NULL,
  `mime_type` VARCHAR(64) NULL,
  `signed_at` DATETIME NOT NULL,
  `pilot_attestation` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Attestation presence pilote (mode papier)',
  PRIMARY KEY (`id`),
  KEY `idx_record` (`record_id`),
  CONSTRAINT `fk_signatures_record` FOREIGN KEY (`record_id`)
    REFERENCES `acceptance_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

### Table `acceptance_tokens`

```sql
CREATE TABLE `acceptance_tokens` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `token` VARCHAR(128) NOT NULL COMMENT 'Token aleatoire',
  `item_id` INT(11) UNSIGNED NOT NULL,
  `record_id` BIGINT(20) UNSIGNED NULL COMMENT 'Enregistrement associe une fois cree',
  `mode` ENUM('direct','link','qrcode') NOT NULL,
  `created_by` VARCHAR(25) NOT NULL,
  `created_at` DATETIME NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used` TINYINT(1) NOT NULL DEFAULT 0,
  `used_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_token` (`token`),
  KEY `idx_item` (`item_id`),
  KEY `idx_expires` (`expires_at`),
  CONSTRAINT `fk_tokens_item` FOREIGN KEY (`item_id`)
    REFERENCES `acceptance_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

---

## Stockage fichiers

```
uploads/
└── acceptances/
    ├── items/              # PDF des éléments à accepter
    │   └── <item_id>/
    │       └── document.pdf
    └── signatures/         # Signatures et documents signés
        └── <record_id>/
            └── signature.png | document_signe.pdf
```

---

## Dépendances externes à intégrer

| Bibliothèque | Usage | Intégration |
|---------------|-------|-------------|
| [Signature Pad](https://github.com/nicejqr/jSignature) ou équivalent JS | Signature tactile | Fichier JS dans `assets/js/` |
| phpqrcode (existant) | Génération QR codes | Déjà en `application/third_party/` |
| TCPDF (existant) | Génération formulaires PDF | Déjà en `application/third_party/` |

---

## Critères de fin

- [ ] Workflow acceptation simple : création élément → notification → lecture → acceptation en un clic → traçabilité
- [ ] Workflow double validation : instructeur valide → élève notifié → élève confirme → traçabilité complète
- [ ] Workflow signature externe : initiation → 4 modes (direct/lien/QR/papier) → signature → archivage
- [ ] Autorisation parentale : champs spécifiques signataire/bénéficiaire → signature → archivage
- [ ] Rattachement différé : acceptation externe non rattachée → rattachement à un pilote → visible dans le dossier pilote
- [ ] Date limite : affichage, indicateurs visuels, filtres en retard
- [ ] Processus lecture obligatoire : défilement complet avant bouton accepter
- [ ] Export des acceptations
- [ ] Tous les tests PHPUnit et Playwright passent
- [ ] Internationalisation FR/EN/NL complète
