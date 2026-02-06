# Plan d’implémentation — Archivage Documentaire

Date : 4 février 2026

## Références
- PRD : [doc/prds/archivage_documentaire_prd.md](doc/prds/archivage_documentaire_prd.md)
- Analyse existante : [doc/design_notes/reuse_archived_documents_attachments.md](doc/design_notes/reuse_archived_documents_attachments.md)

## Objectif
Livrer un module d’archivage documentaire conforme au PRD, réutilisant les mécanismes d’attachements existants, avec versionning, validation, expiration et notifications.

## Hypothèses
- Réutilisation de la table et du stockage existants pour les attachements.
- Types de documents initialement supportés : visite médicale, assurance, brevet (pilotes), documents club/sections.
- Rôles : pilotes et administrateurs (CA).
- Gestion des types de documents avec règles (obligatoire, portée, expiration, stockage).
- Pas de workflow de validation — les documents sont immédiatement actifs.
- Désactivation des alertes par document (clic admin sur l'alerte).

## Tâches à réaliser

### Lot 1 — Modèle de données & migration
- [x] Cartographier les structures existantes (table `attachments`, stockage uploads/documents, helpers de compression)
- [x] Définir la stratégie de réutilisation/extension : nouveaux champs, table de liaison, ou nouvelle table dédiée avec continuité
- [x] Concevoir la migration (schéma, index, contraintes, compatibilité)
- [x] Mettre à jour `application/config/migration.php` (version 67)
- [x] Créer migration `067_archived_documents.php`
- [x] Créer tests de migration `ArchivedDocumentsMigrationTest.php` (18 tests)

### Lot 2 — Modèles & métadonnées
- [x] Implémenter/étendre le modèle pour :
  - [x] association à pilote/section/club (`archived_documents_model.php`)
  - [x] dates de validité et détection d'expiration (actif/proche/expiré) (`compute_expiration_status()`)
  - [x] versionning (liens entre versions) (`create_document()`, `get_version_history()`)
  - [x] statut "manquant" pour documents obligatoires sans document valide (`get_missing_documents()`)
  - [x] désactivation d'alerte par document (`toggle_alarm()`, `disable_alarm()`, `enable_alarm()`)
- [x] Ajouter les métadonnées dans `application/libraries/Gvvmetadata.php`
- [x] Modéliser les types de documents et leurs règles (`document_types_model.php`)
- [x] Créer tests des modèles `ArchivedDocumentsModelTest.php` (20 tests)

### Lot 3 — Contrôleurs & permissions
- [x] Créer/étendre les contrôleurs pour :
  - [x] dépôt document par pilote (`create()`, `formValidation()`)
  - [x] suppression document (`delete()` - pilote : ses documents, admin : tous)
  - [x] liste "expirés" pour administrateurs (`expired()`)
  - [x] désactivation d'alerte par document (`toggle_alarm()` AJAX)
- [x] Vérifier l'accès par rôle (pilote/admin) (`_is_admin()`)
- [x] Contrôleur `archived_documents.php` créé
- [x] Fichier de langue `archived_documents_lang.php` (FR)
- [x] Vues créées : `my_documents`, `expired`, `view`, `formView`, `tableView`
- [x] Structure de stockage `uploads/documents/` créée

### Lot 4 — Vues & UX
- [x] Liste documents pilote (expiration, versions) — `bs_my_documents.php`
- [x] Liste admin "expirés" avec bouton désactivation alerte — `bs_expired.php`
- [x] Détail document avec historique de versions — `bs_view.php`
- [x] Indicateurs visuels (expiré, proche, manquant, alerte désactivée) via Bootstrap 5
- [x] Entrées de menu ajoutées dans `bs_menu.php` (Membres → Mes documents, Admin → Documents expirés)

### Lot 5 — Notifications
- [ ] Requête de détection des documents proches de l'expiration (excluant `alarm_disabled = 1`)
- [ ] Tâche d'envoi d'alertes (cron/script existant ou nouveau)
- [ ] Notification à la connexion (bannière ou alertes en UI)

### Lot 6 — Internationalisation
- [x] Ajouter les libellés FR/EN/NL — fichiers de langue créés dans `application/language/{french,english,dutch}/archived_documents_lang.php`
- [x] Vérifier que tous les libellés UI utilisent `$this->lang->line()` — vues mises à jour

### Lot 7 — Tests & validation
- [ ] Tests unitaires : modèles, helpers, expiration
- [ ] Tests intégration : listes admin, workflow validation, versionning
- [ ] Tests UI Playwright : dépôt, validation, affichage expiré
- [ ] Smoke tests : phpunit + playwright

## Critères de fin
- Workflow complet : dépôt → versionning → expiration → désactivation alerte.
- Liste admin "expirés" fonctionnelle avec désactivation d'alerte.
- Notifications envoyées et affichées.
- Tests unitaires et Playwright green.

---

## Analyse Lot 1 — Résultats

### 1. Cartographie des structures existantes

#### Table `attachments` actuelle
```
+------------------+----------------------+------+-----+---------+----------------+
| Field            | Type                 | Null | Key | Default | Extra          |
+------------------+----------------------+------+-----+---------+----------------+
| id               | bigint(20) unsigned  | NO   | PRI | NULL    | auto_increment |
| referenced_table | varchar(128)         | YES  |     | NULL    |                |
| referenced_id    | varchar(128)         | YES  |     | NULL    |                |
| user_id          | varchar(25)          | YES  |     | NULL    |                |
| filename         | varchar(128)         | YES  |     | NULL    |                |
| description      | varchar(124)         | YES  |     | NULL    |                |
| file             | varchar(255)         | YES  |     | NULL    |                |
| club             | tinyint(4)           | YES  |     | 0       |                |
| file_backup      | varchar(255)         | YES  |     | NULL    |                |
+------------------+----------------------+------+-----+---------+----------------+
```

**Usage actuel** : 254 enregistrements, exclusivement liés aux écritures comptables (`referenced_table = 'ecritures'`).

#### Stockage fichiers
- Chemin : `./uploads/attachments/<année>/<section>/`
- Compression : via `File_compressor` (images redimensionnées, PDF optimisés Ghostscript)
- Miniatures PDF : générées automatiquement

#### Table `membres` (clé primaire)
- Clé primaire : `mlogin` (varchar(25))
- Champ `actif` : permet de distinguer pilotes actifs/inactifs (utile pour désactiver alarmes)

### 2. Stratégie retenue : nouvelle table dédiée

**Décision** : Créer une nouvelle table `archived_documents` plutôt qu'étendre `attachments`.

**Justification** :
1. **Cas d'usage différents** : les attachments actuels sont des justificatifs comptables ponctuels, les documents pilotes ont des cycles de vie longs avec validation et expiration.
2. **Besoins spécifiques** : statut de validation, dates de validité, versionning, types de documents — trop de champs additionnels pour une extension propre.
3. **Pas de risque de régression** : la table `attachments` reste inchangée pour la comptabilité.
4. **Conformité PRD** : le PRD mentionne « compatibilité avec attachments OU continuité fonctionnelle » — la nouvelle table assure la continuité.

**Table additionnelle** : `document_types` pour définir les types de documents et leurs règles.

### 3. Schéma de base de données proposé

#### Table `document_types`
Définit les types de documents disponibles et leurs règles.

```sql
CREATE TABLE `document_types` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(32) NOT NULL COMMENT 'Code unique (ex: medical, insurance, license)',
  `name` VARCHAR(128) NOT NULL COMMENT 'Libellé affiché',
  `section_id` INT(11) NULL COMMENT 'Section spécifique (NULL = toutes sections)',
  `scope` ENUM('pilot', 'section', 'club') NOT NULL DEFAULT 'pilot' COMMENT 'Portée du document',
  `required` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Document obligatoire',
  `has_expiration` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Document avec date expiration',
  `allow_versioning` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Autorise le versionning',
  `storage_by_year` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Stockage organisé par année',
  `alert_days_before` INT(11) NULL DEFAULT 30 COMMENT 'Jours avant expiration pour alerte',
  `active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Type actif',
  `display_order` INT(11) NOT NULL DEFAULT 0 COMMENT 'Ordre affichage',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code_section` (`code`, `section_id`),
  KEY `idx_section` (`section_id`),
  CONSTRAINT `fk_document_types_section` FOREIGN KEY (`section_id`)
    REFERENCES `sections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Types de documents et règles associées';
```

**Notes** :
- `section_id = NULL` : type disponible pour toutes les sections
- `section_id = <id>` : type spécifique à une section
- La contrainte unique `uk_code_section` permet d'avoir le même code pour différentes sections

**Données initiales** (PRD) :
| code | name | section_id | scope | required | has_expiration |
|------|------|------------|-------|----------|----------------|
| medical | Visite médicale | NULL | pilot | 1 | 1 |
| insurance | Assurance | NULL | pilot | 1 | 1 |
| license | Brevet/Licence | NULL | pilot | 0 | 1 |
| club_doc | Document club | NULL | club | 0 | 0 |
| signature | Signature membre | NULL | pilot | 0 | 1 |
| ci | Carte d'identité | NULL | pilot | 0 | 1 |
| parental | Autorisation parentale | NULL | pilot | 0 | 0 |
| bia | Brevet Initiation Aéronautique | NULL | pilot | 0 | 0 |

#### Table `archived_documents`
Stocke les documents avec leur état d'expiration et d'alerte.

```sql
CREATE TABLE `archived_documents` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `document_type_id` INT(11) UNSIGNED NOT NULL COMMENT 'Type de document',
  `pilot_login` VARCHAR(25) NULL COMMENT 'Pilote associé (NULL si club/section)',
  `section_id` INT(11) NULL COMMENT 'Section associée',
  `file_path` VARCHAR(255) NOT NULL COMMENT 'Chemin du fichier',
  `original_filename` VARCHAR(255) NOT NULL COMMENT 'Nom fichier original',
  `description` VARCHAR(255) NULL COMMENT 'Description libre',
  `uploaded_by` VARCHAR(25) NOT NULL COMMENT 'Utilisateur ayant uploadé',
  `uploaded_at` DATETIME NOT NULL COMMENT 'Date upload',
  `valid_from` DATE NULL COMMENT 'Date début validité',
  `valid_until` DATE NULL COMMENT 'Date fin validité',
  `alarm_disabled` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Alerte désactivée par admin',
  `previous_version_id` BIGINT(20) UNSIGNED NULL COMMENT 'Lien vers version précédente',
  `is_current_version` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Version courante',
  `file_size` INT(11) UNSIGNED NULL COMMENT 'Taille fichier en octets',
  `mime_type` VARCHAR(64) NULL COMMENT 'Type MIME',
  PRIMARY KEY (`id`),
  KEY `idx_pilot` (`pilot_login`),
  KEY `idx_section` (`section_id`),
  KEY `idx_type` (`document_type_id`),
  KEY `idx_expiration` (`valid_until`),
  KEY `idx_current` (`is_current_version`),
  KEY `idx_alarm` (`alarm_disabled`),
  CONSTRAINT `fk_archived_documents_type` FOREIGN KEY (`document_type_id`)
    REFERENCES `document_types` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_archived_documents_pilot` FOREIGN KEY (`pilot_login`)
    REFERENCES `membres` (`mlogin`) ON DELETE CASCADE,
  CONSTRAINT `fk_archived_documents_section` FOREIGN KEY (`section_id`)
    REFERENCES `sections` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_archived_documents_prev` FOREIGN KEY (`previous_version_id`)
    REFERENCES `archived_documents` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Documents pilotes avec expiration';
```

**Détection des documents à alerter** :
```sql
SELECT ad.*, dt.name, m.memail
FROM archived_documents ad
JOIN document_types dt ON ad.document_type_id = dt.id
JOIN membres m ON ad.pilot_login = m.mlogin
WHERE ad.valid_until BETWEEN CURDATE()
      AND DATE_ADD(CURDATE(), INTERVAL dt.alert_days_before DAY)
  AND ad.alarm_disabled = 0
  AND ad.is_current_version = 1;
```

### 4. Stockage fichiers

**Structure proposée** (sans année, conformément aux recommandations) :
```
uploads/
└── documents/
    └── pilots/
        └── <pilot_login>/
            └── <document_type_code>/
                └── <timestamp>_<random>_<filename>
    └── sections/
        └── <section_id>/
            └── <document_type_code>/
                └── ...
    └── club/
        └── <document_type_code>/
            └── ...
```

**Avantages** :
- Pas de découpage par année (documents multi-annuels)
- Organisation claire par pilote/section/club puis type
- Compatible avec la compression existante (`File_compressor`)

### 5. Prochaines étapes

1. ~~**Migration 067** : créer les tables `document_types` et `archived_documents`~~ ✅
2. ~~Insérer les types de documents initiaux~~ ✅
3. ~~Tests de migration~~ ✅ (18 tests dans `ArchivedDocumentsMigrationTest.php`)
4. Lot 2 : Modèles et métadonnées
