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
- Possibilité de désactiver les alarmes par pilote (ex. pilote inactif).

## Tâches à réaliser

### Lot 1 — Modèle de données & migration
- [x] Cartographier les structures existantes (table `attachments`, stockage uploads/documents, helpers de compression)
- [x] Définir la stratégie de réutilisation/extension : nouveaux champs, table de liaison, ou nouvelle table dédiée avec continuité
- [x] Concevoir la migration (schéma, index, contraintes, compatibilité)
- [ ] Mettre à jour `application/config/migration.php`
- [ ] Créer tests de migration (up/down) et validation du schéma

### Lot 2 — Modèles & métadonnées
- [ ] Implémenter/étendre le modèle pour :
  - [ ] association à pilote/section/club
  - [ ] statuts (en attente/validé)
  - [ ] dates de validité et détection d'expiration (actif/proche/expiré)
  - [ ] versionning (liens entre versions)
  - [ ] statut "manquant" pour documents obligatoires sans document valide
- [ ] Ajouter les métadonnées dans `application/libraries/Gvvmetadata.php`
- [ ] Définir les règles de validation (types de fichiers, champs requis)
- [ ] Modéliser les types de documents et leurs règles (obligatoire, portée, expiration, stockage)

### Lot 3 — Contrôleurs & permissions
- [ ] Créer/étendre les contrôleurs pour :
  - [ ] dépôt document par pilote
  - [ ] validation admin
  - [ ] suppression conditionnelle (en attente uniquement)
  - [ ] listes "à valider" et "expirés"
  - [ ] activation/désactivation des alarmes par pilote
- [ ] Vérifier l'accès par rôle (pilote/admin)
- [ ] Ajouter les routes nécessaires dans `application/config/routes.php`

### Lot 4 — Vues & UX
- [ ] Liste documents pilote (statuts, expiration, versions)
- [ ] Liste admin "à valider"
- [ ] Liste admin "expirés"
- [ ] Détail document avec historique de versions
- [ ] Indicateurs visuels (expiré, proche, en attente, validé, manquant) via Bootstrap 5

### Lot 5 — Notifications
- [ ] Modèle de préférences d'abonnement (par type de document et délai)
- [ ] Tâche d'envoi d'alertes (cron/script existant ou nouveau)
- [ ] Notification à la connexion (bannière ou alertes en UI)

### Lot 6 — Internationalisation
- [ ] Ajouter les libellés FR/EN/NL
- [ ] Vérifier que tous les libellés UI utilisent `$this->lang->line()`

### Lot 7 — Tests & validation
- [ ] Tests unitaires : modèles, helpers, expiration
- [ ] Tests intégration : listes admin, workflow validation, versionning
- [ ] Tests UI Playwright : dépôt, validation, affichage expiré
- [ ] Smoke tests : phpunit + playwright

## Critères de fin
- Workflow complet : dépôt → validation → versionning → expiration.
- Listes admin fonctionnelles (à valider, expirés).
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
  `default_validity_months` INT(11) NULL COMMENT 'Durée de validité par défaut en mois',
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
| code | name | section_id | scope | required | has_expiration | default_validity_months |
|------|------|------------|-------|----------|----------------|------------------------|
| medical | Visite médicale | NULL | pilot | 1 | 1 | 24 |
| insurance | Assurance | NULL | pilot | 1 | 1 | 12 |
| license | Brevet/Licence | NULL | pilot | 0 | 1 | 60 |
| club_doc | Document club | NULL | club | 0 | 0 | NULL |
| signature | Signature membre | NULL | pilot | 0 | 1 | 180 |
| ci | Carte d'identité | NULL | pilot | 0 | 1 | 180 |
| parental | Autorisation parentale | NULL | pilot | 0 | 0 | NULL |
| bia | Brevet Initiation Aéronautique | NULL | pilot | 0 | 0 | NULL |

#### Table `archived_documents`
Stocke les documents avec leur état de validation et expiration.

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
  `validation_status` ENUM('pending', 'validated', 'rejected') NOT NULL DEFAULT 'pending',
  `validated_by` VARCHAR(25) NULL COMMENT 'Admin ayant validé',
  `validated_at` DATETIME NULL COMMENT 'Date validation',
  `valid_from` DATE NULL COMMENT 'Date début validité',
  `valid_until` DATE NULL COMMENT 'Date fin validité',
  `previous_version_id` BIGINT(20) UNSIGNED NULL COMMENT 'Lien vers version précédente',
  `is_current_version` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Version courante',
  `file_size` INT(11) UNSIGNED NULL COMMENT 'Taille fichier en octets',
  `mime_type` VARCHAR(64) NULL COMMENT 'Type MIME',
  PRIMARY KEY (`id`),
  KEY `idx_pilot` (`pilot_login`),
  KEY `idx_section` (`section_id`),
  KEY `idx_type` (`document_type_id`),
  KEY `idx_status` (`validation_status`),
  KEY `idx_expiration` (`valid_until`),
  KEY `idx_current` (`is_current_version`),
  CONSTRAINT `fk_archived_documents_type` FOREIGN KEY (`document_type_id`)
    REFERENCES `document_types` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_archived_documents_pilot` FOREIGN KEY (`pilot_login`)
    REFERENCES `membres` (`mlogin`) ON DELETE CASCADE,
  CONSTRAINT `fk_archived_documents_section` FOREIGN KEY (`section_id`)
    REFERENCES `sections` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_archived_documents_prev` FOREIGN KEY (`previous_version_id`)
    REFERENCES `archived_documents` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Documents pilotes avec validation et expiration';
```

#### Table `document_alerts` (optionnelle, Lot 5)
Pour les préférences d'abonnement aux alertes.

```sql
CREATE TABLE `document_alerts` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_login` VARCHAR(25) NOT NULL COMMENT 'Utilisateur abonné',
  `document_type_id` INT(11) UNSIGNED NULL COMMENT 'Type spécifique (NULL = tous)',
  `alert_days_before` INT(11) NOT NULL DEFAULT 30 COMMENT 'Jours avant expiration',
  `enabled` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_login`),
  CONSTRAINT `fk_alerts_type` FOREIGN KEY (`document_type_id`)
    REFERENCES `document_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Abonnements alertes expiration';
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

1. **Migration 067** : créer les tables `document_types` et `archived_documents`
2. **Migration 068** (optionnel, Lot 5) : créer `document_alerts`
3. Insérer les types de documents initiaux
4. Tests de migration up/down
