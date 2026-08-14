# Plan d'implémentation — Système d'Acceptations et Reconnaissances

Date : 8 février 2026

## Références

- PRD : [doc/prds/approbation_de_documents_prd.md](../prds/approbation_de_documents_prd.md)
- Infrastructure existante : Module archivage documentaire (migration 067, contrôleur `archived_documents`, modèles)

## Objectif

Livrer un système complet d'acceptation et reconnaissance de documents, formations et contrôles pour les membres du club identifiés dans GVV, avec :
- Acceptation simple (un clic) pour les membres
- Double validation (instructeur/élève) pour les formations et contrôles
- Traçabilité complète avec horodatage

## Note de réduction de périmètre (révision PRD)

Le PRD a été révisé pour exclure toute acceptation par une personne non-inscrite dans GVV : signature externe (directe, lien, QR code, papier), autorisation parentale et rattachement différé au dossier d'un pilote sont désormais couverts par le module Formulaires, pas par ce système.

Conséquences sur ce plan :
- Les anciens Lots 6 (signature externe), 7 (page publique de signature) et 8 (mode papier) sont retirés — non commencés, ils n'ont plus lieu d'être.
- Les Lots 1 à 3 étaient déjà livrés avant cette révision et incluent des éléments désormais hors périmètre : catégorie `autorisation`, colonne `target_type` (interne/externe), rattachement d'une acceptation externe à un pilote (3.7), table `acceptance_signatures`, table `acceptance_tokens`. Ce code et ce schéma restent en place tels quels (aucun retrait rétroactif) mais ne reçoivent plus de développement complémentaire ; les nouveaux éléments créés par l'administration n'utilisent plus la catégorie `autorisation` ni le mode externe.
- Les lots suivants ont été renumérotés en conséquence (ancien Lot 9 → Lot 6, etc.).

## Note de priorité (premier lot)

Le premier lot fonctionnel livré est le Lot 4 — l'acceptation de documents déjà archivés (catégorie `document`, appuyée sur `archived_documents`/`document_types`, existant réutilisé). Le Lot 5 (double validation formation/contrôle) est secondaire et n'est pas requis pour cette première livraison.

**Évolution probable** : plutôt que d'étendre ce module avec de nouvelles catégories/sources d'éléments à accepter, l'évolution privilégiée consiste à compléter le module Formulaires pour transformer une réponse en document archivé — ce document devient alors un élément à accepter par le mécanisme générique du Lot 4, sans multiplier les sources spécialisées.

## Analyse de l'existant

### Infrastructure réutilisable
- **Module archivage documentaire** : tables `document_types` et `archived_documents` (migration 067), contrôleur, modèles et vues déjà en place
- **Module Messages du jour (MOTD)** : tables `motd_messages`/`motd_user_message_state`, contrôleur, modèles déjà en place (migration 143, module livré) — canal de notification par défaut, voir `doc/prds/messages_du_jour_prd.md`
- **File_compressor** : compression images/PDF pour les uploads
- **Système email** : helpers et contrôleur existants
- **Gvvmetadata** : système de métadonnées pour formulaires et tables

### Gaps identifiés
- Pas de viewer PDF intégré (PDF.js) — le navigateur affiche nativement
- Le module Messages du jour ne supporte pas aujourd'hui un message non masquable (`motd_user_message_state.hidden` toujours modifiable par l'utilisateur) — nécessaire pour les niveaux `mandatory_soft`/`mandatory_hard`, voir Lot 3d
- Pas de mécanisme de blocage applicatif global empêchant toute action tant qu'une validation obligatoire bloquante n'est pas faite, voir Lot 3d

## Architecture

### Nouvelles tables

| Table | Rôle |
|-------|------|
| `acceptance_items` | Éléments à faire accepter (documents, formations, briefings) |
| `acceptance_records` | Enregistrements d'acceptation/refus par personne |
| `acceptance_signatures` | Signatures externes (tactiles, uploads papier) |
| `acceptance_tokens` | Liens temporaires pour signatures externes |

### Relations avec l'existant
- `acceptance_items.category` utilise les catégories du PRD (document, formation, controle, briefing) — la valeur `autorisation` reste définie dans l'enum existant pour compatibilité avec les éléments déjà créés avant la révision du PRD, mais n'est plus proposée pour un nouvel élément
- Les fichiers PDF des éléments sont stockés dans `uploads/acceptances/items/`

### Contrôleurs

| Contrôleur | Rôle |
|------------|------|
| `acceptance_admin` | CRUD éléments, suivi acceptations (admin) |
| `acceptance` | Acceptation, historique personnel (membre) |
| `acceptance_training` | Délivrance/réception formations (instructeur/élève) |

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

- [x] 2.1 Créer `acceptance_items_model.php` (CRUD, select_page avec join créateur, get_active_items, get_items_for_user, get_overdue_items, image, selector) — 15 tests
- [x] 2.2 Créer `acceptance_records_model.php` (CRUD, select_page avec joins, get_by_user, get_by_item, get_pending_for_user, count_pending_for_user, accept, refuse, link_to_pilot, get_linked_records, image) — 15 tests
- [x] 2.3 Créer `acceptance_signatures_model.php` (CRUD, select_page avec joins, get_by_record, create_tactile, create_upload avec autorisation parentale, image) — 8 tests
- [x] 2.4 Créer `acceptance_tokens_model.php` (generate_token crypto-sécurisé 64 hex, validate_token, mark_used, cleanup_expired, get_by_item, image) — 16 tests
- [x] 2.5 Ajouter les métadonnées dans `Gvvmetadata.php` pour les 4 tables + 2 vues + default_fields
- [x] 2.6 Écrire les tests MySQL des modèles (54 tests, 186 assertions) + fichiers de langue FR/EN/NL

### Lot 3 — Administration des éléments

- [x] 3.1 Créer le contrôleur `acceptance_admin.php` (liste, création, édition, activation/désactivation)
- [x] 3.2 Créer la vue liste des éléments (`bs_itemsListView.php`)
- [x] 3.3 Créer le formulaire création/édition d'élément (`bs_itemFormView.php`)
- [x] 3.4 Implémenter l'upload PDF lors de la création d'un élément (réutiliser `File_compressor`)
- [x] 3.5 Créer la vue suivi des acceptations par élément (`bs_trackingView.php`)
- [x] 3.6 Implémenter les filtres : en attente, en retard, proches échéance, non rattachées
- [x] 3.7 Implémenter le rattachement d'une acceptation externe à un pilote :
  - [x] 3.7.1 Action "Rattacher à un pilote" dans le suivi des acceptations (sélecteur de membre)
  - [x] 3.7.2 Enregistrer le rattachement (linked_pilot_login, linked_by, linked_at) sans modifier l'acceptation d'origine
  - [x] 3.7.3 Indicateur visuel distinguant les acceptations rattachées et non rattachées
  - [x] 3.7.4 L'acceptation rattachée apparaît dans le dossier du pilote concerné
- [x] 3.8 Ajouter les entrées menu admin dans `bs_menu.php`
- [x] 3.9 Fichiers de langue FR/EN/NL pour l'administration
- [x] 3.10 Valider : test Playwright accès page admin, création d'un élément, suivi des acceptations (4 tests, tous passent)

### Lot 3b — Champ `code` sur `acceptance_items` (prérequis orchestrateur)

- [ ] 3b.1 Créer une migration `09X_acceptance_items_code.php` : ajout colonne `code VARCHAR(50) NULL UNIQUE COMMENT 'Code ASCII snake_case référencé par workflows.json'`
- [ ] 3b.2 Mettre à jour `application/config/migration.php`
- [ ] 3b.3 Ajouter `code` dans `Gvvmetadata.php` pour `acceptance_items` (type string, affichage admin)
- [ ] 3b.4 Ajouter `code` dans les fichiers de langue FR/EN/NL
- [ ] 3b.5 Écrire le test PHPUnit de migration (up, vérification contrainte UNIQUE, down)

> Ce champ est requis par le Module 4 (Orchestrateur) qui référence les éléments d'acceptation via `acceptance_code` dans `workflows.json`. NULL autorisé pour les éléments non gérés par l'orchestrateur.

### Lot 3c — Ciblage d'un utilisateur individuel sur `acceptance_items`

- [x] 3c.1 Créer une migration `168_acceptance_items_target_user.php` : ajout colonne `target_user_login VARCHAR(25) NULL COMMENT 'Membre individuel cible, alternative a target_roles'` + FK vers `membres(mlogin)` ON DELETE CASCADE
- [x] 3c.2 Mettre à jour `application/config/migration.php` (version 168)
- [x] 3c.3 Ajouter `target_user_login` dans `Gvvmetadata.php` pour `acceptance_items` (sélecteur membre, optionnel)
- [x] 3c.4 Adapter le formulaire de création/édition d'élément (`bs_itemFormView.php`) : choix exclusif entre un utilisateur individuel (`target_user_login`) et une ou plusieurs catégories (`target_roles`), piloté par un radio `target_mode`
- [x] 3c.5 Adapter la résolution des destinataires dans `acceptance_items_model.php` (`get_items_for_user`) pour exclure les éléments ciblant individuellement un autre utilisateur — `get_active_items` reste inchangé (non spécifique à un utilisateur)
- [x] 3c.6 Fichiers de langue FR/EN/NL
- [x] 3c.7 Écrire le test PHPUnit de migration (up, FK, down) + tests modèle pour la résolution utilisateur individuel — `AcceptanceItemsTargetUserMigrationTest.php` (3 tests) + tests ajoutés à `AcceptanceItemsModelTest.php` (4 tests), 90 tests acceptance au total, tous verts

> Complète le schéma du Lot 1 (`target_roles` seul, catégories uniquement) pour permettre de cibler un pilote précis plutôt qu'une catégorie — cf. PRD, Cas d'utilisation Administrateur.

### Lot 3d — Niveaux d'obligation et intégration message du jour (prérequis Lot 4)

- [ ] 3d.1 Migration : remplacer `acceptance_items.mandatory` (TINYINT booléen, Lot 1) par `mandatory_level ENUM('optional','mandatory_soft','mandatory_hard') NOT NULL DEFAULT 'optional'`
- [ ] 3d.2 Migration sur `motd_messages` : ajout colonne `dismissible TINYINT(1) NOT NULL DEFAULT 1` (message non masquable si `0`) — extension du module Messages du jour (migration 143), cf. `doc/prds/messages_du_jour_prd.md`
- [ ] 3d.3 Adapter `Motd_model`/gestion de `motd_user_message_state` pour refuser le masquage (`hidden`) d'un message `dismissible = 0` tant que la validation associée n'est pas faite
- [ ] 3d.4 À la mise en cible d'un `acceptance_items` obligatoire, créer automatiquement le message du jour associé (non masquable si obligatoire, lien vers la page de validation) ; le retirer ou le rendre masquable une fois la validation faite
- [ ] 3d.5 Implémenter le blocage applicatif global pour `mandatory_hard` : filtre/hook redirigeant toute requête utilisateur vers la page de validation tant que l'élément n'est pas validé (exceptions : page de validation elle-même, déconnexion)
- [ ] 3d.6 Adapter `Gvvmetadata.php` et le formulaire admin (`bs_itemFormView.php`) pour `mandatory_level`
- [ ] 3d.7 Fichiers de langue FR/EN/NL
- [ ] 3d.8 Écrire les tests PHPUnit : migration, non-masquage MOTD pour un message `dismissible = 0`, blocage global `mandatory_hard`

> **Tranché** : le blocage `mandatory_hard` (3d.5) exempte la déconnexion et la page de validation elle-même. Les club-admins (rôle `club-admin`) ne sont jamais bloqués par une acceptation, quel que soit le niveau d'obligation — ils gardent un accès complet à GVV pour ne pas risquer de se bloquer eux-mêmes hors d'état d'administrer le club. Cf. PRD, Canal de notification et niveaux d'obligation.

### Lot 4 — Acceptation interne (utilisateurs membres) — priorité (voir Note de priorité)

Premier lot fonctionnel livré, centré sur la catégorie `document` et son lien avec les documents déjà archivés (`archived_documents`). Dépend du Lot 3d pour les niveaux d'obligation et la notification par message du jour.

> **Tranché** : un élément `acceptance_items` de catégorie `document` référence un `archived_documents.id` existant (nouvelle colonne `archived_document_id` nullable, FK) plutôt que de téléverser un nouveau `pdf_path` propre à l'acceptation — cohérent avec l'intitulé du lot et l'évolution probable notée dans le PRD (formulaires → document archivé → acceptance). `pdf_path` (Lot 1) reste utilisable pour les autres catégories qui n'ont pas de document archivé source.

> **Tranché** : le ciblage par rôle(s) utilise une table de jointure `acceptance_item_roles` (item_id, types_roles_id, section_id nullable = toutes sections), sur le modèle de `email_list_roles`/`_criteria_tab.php` (grille rôle x section), plutôt que le champ texte libre `acceptance_items.target_roles` (noms de rôles en clair, sans notion de section). `target_roles` est conservé en base pour compatibilité mais n'est plus alimenté par le formulaire admin.

- [x] 4.0 Colonne `acceptance_items.archived_document_id` (migration `169_acceptance_items_archived_document.php`, FK `ON DELETE SET NULL`) et création d'un élément d'acceptation directement depuis la liste des documents archivés : bouton d'action sur chaque document PDF (`archived_documents/page`) menant à `acceptance_admin/create/<archived_document_id>`, formulaire pré-rempli (catégorie verrouillée sur `document`, document affiché en lecture seule à la place de l'upload PDF)
- [x] 4.0bis Simplification du formulaire de création/édition (`bs_itemFormView.php`) : `target_type` et double validation (`dual_validation`/`role_1`/`role_2`) masqués (inutilisés pour l'instant) ; ciblage par rôle remplacé par une grille rôle x section (table `acceptance_item_roles`, migration `170_acceptance_item_roles.php`), réutilisant le sélecteur des listes d'email (`Email_lists_model::get_available_roles/get_available_sections`) ; date de version verrouillée sur la date de dépôt du document archivé lorsque l'élément en référence un
- [ ] 4.1 Créer le contrôleur `acceptance.php` (tableau de bord, lecture, acceptation, refus, historique)
- [ ] 4.2 Créer la vue tableau de bord des éléments en attente (`bs_acceptance_dashboard.php`)
- [ ] 4.3 Implémenter le badge/notification du nombre d'éléments en attente (intégrer dans `bs_menu.php` ou layout) — en complément du message du jour (canal par défaut, Lot 3d)
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

### Lot 5 — Double validation (formations et contrôles) — secondaire (voir Note de priorité)

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

### Lot 6 — Notifications

- [ ] 6.1 Implémenter la détection des éléments en attente à la connexion (hook dans le contrôleur de login ou layout)
- [ ] 6.2 Afficher un badge dans le menu avec le nombre d'éléments en attente
- [ ] 6.3 Créer le script/cron de détection des acceptations proches de la date limite
- [ ] 6.4 Implémenter l'envoi d'emails de rappel (réutiliser l'infrastructure email existante)
- [ ] 6.5 Notifications pour les doubles validations en attente (instructeur notifié quand élève n'a pas confirmé)
- [ ] 6.6 Valider : test PHPUnit détection en attente, vérifier affichage badge

### Lot 7 — Indicateurs visuels & date limite

- [ ] 7.1 Implémenter les indicateurs visuels Bootstrap 5 :
  - [ ] 7.1.1 Badge "en retard" (rouge) après date limite
  - [ ] 7.1.2 Badge "proche échéance" (orange) dans les X jours avant
  - [ ] 7.1.3 Badge "en attente" (bleu)
  - [ ] 7.1.4 Badge "accepté" (vert) / "refusé" (gris)
- [ ] 7.2 Afficher clairement "À accepter avant le [date]" sur chaque élément
- [ ] 7.3 Implémenter le filtre admin : en retard, proches échéance, en attente
- [ ] 7.4 Valider : vérification visuelle des badges, test Playwright

### Lot 8 — Export et rapports

- [ ] 8.1 Implémenter l'export CSV des acceptations par élément (admin)
- [ ] 8.2 Implémenter l'export de la liste des personnes n'ayant pas encore accepté
- [ ] 8.3 Valider : test PHPUnit format CSV, test Playwright téléchargement

### Lot 9 — Internationalisation complète

- [ ] 9.1 Vérifier que tous les libellés UI utilisent `$this->lang->line()`
- [ ] 9.2 Compléter les traductions EN et NL
- [ ] 9.3 Vérifier les formules d'acceptation dans les 3 langues
- [ ] 9.4 Valider : revue des fichiers de langue, aucune chaîne en dur dans les vues

### Lot 10 — Tests finaux & intégration

- [ ] 10.1 Exécuter `./run-all-tests.sh` — tous les tests PHPUnit passent
- [ ] 10.2 Tests Playwright smoke : accès aux pages admin, membre
- [ ] 10.3 Test Playwright E2E : parcours complet acceptation
- [ ] 10.4 Test Playwright E2E : parcours complet double validation formation
- [ ] 10.5 Vérifier les permissions (rôles admin, membre, instructeur)
- [ ] 10.6 Revue de sécurité : CSRF, XSS

---

## Schéma de base de données proposé

Schéma tel que livré par le Lot 1 (migration `068_acceptance_system.php`), avant la révision du PRD. Les colonnes/tables `target_type` (externe), `signature_mode`, `linked_pilot_login`/`linked_by`/`linked_at`, `acceptance_signatures` et `acceptance_tokens` couvrent la signature externe et le rattachement différé, désormais hors périmètre — elles restent en base sans retrait rétroactif mais ne sont plus alimentées par un nouveau développement.

`target_roles` ne couvre que le ciblage par catégorie. Le Lot 3c ajoute `target_user_login` pour cibler un utilisateur individuel, cf. PRD (Cas d'utilisation Administrateur).

`mandatory` (TINYINT booléen) ne couvre qu'un obligatoire/facultatif binaire. Le Lot 3d le remplace par `mandatory_level` (trois niveaux) et ajoute `motd_messages.dismissible`, cf. PRD (Canal de notification et niveaux d'obligation).

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
    └── items/              # PDF des éléments à accepter
        └── <item_id>/
            └── document.pdf
```

Le sous-répertoire `signatures/` (signatures et documents signés externes) a été livré avec le Lot 1 mais ne reçoit plus de nouveau contenu depuis la révision du PRD.

---

## Dépendances externes à intégrer

Aucune — les dépendances propres à la signature externe (Signature Pad JS, `phpqrcode`) ne sont plus nécessaires depuis la révision du PRD.

---

## Critères de fin

- [ ] Workflow acceptation simple : création élément → notification → lecture → acceptation en un clic → traçabilité
- [ ] Workflow double validation : instructeur valide → élève notifié → élève confirme → traçabilité complète
- [ ] Date limite : affichage, indicateurs visuels, filtres en retard
- [ ] Processus lecture obligatoire : défilement complet avant bouton accepter
- [ ] Export des acceptations
- [ ] Tous les tests PHPUnit et Playwright passent
- [ ] Internationalisation FR/EN/NL complète
