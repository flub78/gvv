# Plan d’implémentation — Archivage Documentaire

Date : 4 février 2026 — mis à jour le 12 octobre 2026 (Lot 5 : notifications et abonnements)

## Références
- PRD : [doc/prds/archivage_documentaire_prd.md](doc/prds/archivage_documentaire_prd.md)
- Analyse existante : [doc/design_notes/reuse_archived_documents_attachments.md](doc/design_notes/reuse_archived_documents_attachments.md)

## Objectif
Livrer un module d’archivage documentaire conforme au PRD, réutilisant les mécanismes d’attachements existants, avec versionning, validation, expiration et notifications.

## Hypothèses
- Réutilisation de la table et du stockage existants pour les attachements.
- Types de documents initialement supportés : visite médicale, assurance, brevet (pilotes), documents club/sections.
- Rôles : pilotes et administrateurs (CA).
- Un type de document est une classe de règles, pas un emplacement unique par pilote. Plusieurs documents du même type peuvent coexister pour une même entité.
- Le versionning est toujours une action explicite (bouton "Nouvelle version") ; plusieurs documents indépendants du même type peuvent coexister pour une même entité.
- L'édition in-place permet de modifier un document existant (libellé, description, fichier) sans créer de version.
- Chaque document dispose d'un libellé (`label`) pour l'identifier parmi d'autres documents du même type.
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
- [x] Créer migration d'ajustement du schéma (modèle classe/instance) — `075_document_types_class_instance.php` :
  - Supprime `document_types.allow_versioning` (versionning désormais toujours explicite)
  - `application/config/migration.php` passé à la version 75
- [x] Mettre à jour les tests de migration en conséquence

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
- [x] Adapter `create_document()` au modèle classe/instance :
  - Auto-remplacement uniquement si `unique_per_entity = 1`
  - Sinon, création d'une nouvelle instance indépendante
- [x] Mettre à jour `Gvvmetadata.php`, fichiers de langue (FR/EN/NL), et `bs_formView.php` : `allow_versioning` → `unique_per_entity`
- [x] Adapter `get_missing_documents()` : un type obligatoire est "manquant" si aucun document valide et non expiré existe, quelle que soit le nombre d'instances (logique déjà correcte, docblock mis à jour)
- [x] Réviser `create_document()` : supprimer la logique d'auto-remplacement (plus de `unique_per_entity`), marquer l'ancienne version non-courante si `previous_version_id` est fourni
- [x] Ajouter `update_document()` au modèle (mise à jour in-place)
- [x] Supprimer `unique_per_entity` de `Gvvmetadata.php`, fichiers de langue (FR/EN/NL) et vues (`bs_formView.php` types de documents)
- [x] Prendre en compte le champ `label` dans create/update/display (contrôleur `formValidation()`, `bs_formView.php`, `bs_formPilotView.php`, `bs_view.php`, `bs_my_documents.php`, libellés FR/EN/NL)

### Lot 3 — Contrôleurs & permissions
- [x] Créer/étendre les contrôleurs pour :
  - [x] dépôt document par pilote (`create()`, `formValidation()`)
  - [x] dépôt document par admin pour pilote/section/club (formulaire admin avec sélecteurs, pré-remplissage `?pilot=`)
  - [x] suppression document (`delete()` - pilote : ses documents, admin : tous)
  - [x] liste "expirés" pour administrateurs (`expired()`)
  - [x] désactivation d'alerte par document (`toggle_alarm()` AJAX)
- [x] Vérifier l'accès par rôle (pilote/admin) (`_is_admin()`)
- [x] Contrôleur `archived_documents.php` créé
- [x] Action "Nouvelle version" : créer un nouveau maillon de version (`previous_version_id`) depuis un document existant (`new_version()`, `bs_formPilotView.php`, `bs_formView.php`)
- [x] Action "Editer" : modification in-place du document (libellé, description, fichier) sans créer de version (`edit()`, `editValidation()`, `bs_editView.php`)
- [x] Avertissement UX à l'ajout si un document du même type existe déjà pour la même entité (`create_pilot()`, `bs_formPilotView.php`)
- [x] Fichier de langue `archived_documents_lang.php` (FR)
- [x] Vues créées : `my_documents`, `expired`, `view`, `formView`, `tableView`
  - [x] Bouton "Ajouter un document" dans la vue liste admin (`bs_documentsListView.php`)
  - [x] Bouton admin contextualisé dans la vue pilote (`bs_my_documents.php`) : lien `create?pilot=<login>` quand admin consulte les docs d'un pilote tiers
- [x] Structure de stockage `uploads/documents/` créée

### Lot 4 — Vues & UX
- [x] Liste documents pilote (expiration, versions) — `bs_my_documents.php`
- [x] Liste admin "expirés" avec bouton désactivation alerte — `bs_expired.php`
- [x] Détail document avec historique de versions — `bs_view.php`
- [x] Indicateurs visuels (expiré, proche, manquant, alerte désactivée) via Bootstrap 5
- [x] Entrées de menu ajoutées dans `bs_menu.php` (Membres → Mes documents, Admin → Documents expirés)

### Lot 5 — Notifications et abonnements

Exigences : PRD §10 (notifications pilote, abonnements administrateurs, anti-spam). Conception : [gestion_alarmes_design.md § Lot 5](../design_notes/gestion_alarmes_design.md). Périmètre limité aux documents archivés ; l'intégration aux alarmes calculées via `AlarmAggregator` est reportée.

**Hypothèses de réalisation**
- Seuls les membres actifs génèrent des alertes ; `alarm_disabled = 1` et documents non approuvés sont exclus.
- Les emails ne contiennent que des métadonnées et un lien vers GVV, jamais le fichier.
- Redirection test via `test_intercept_email()`, comme les rappels de réservation.
- Déclenchement sur le modèle de `reservation_scheduler` : entrée CLI pour le cron quotidien et URL protégée par un secret.
- Première alerte pilote à `alert_days_before` du type de document (borné à 90 jours), puis calendrier du PRD §10.4.

**5.1 — Détection** (0,5 j)
- [x] Requêtes expirés / expiration proche excluant `alarm_disabled = 1` (`get_expired_documents()`, `get_expiring_soon_documents()`)
- [ ] Filtrer les membres inactifs
- [ ] Collecte des documents obligatoires manquants pour tous les membres actifs (réutilise `get_missing_documents()`)
- [ ] Résolution des sections d'un document selon sa portée (cf. conception)

**5.2 — Modèle de données** (1 j)
- [ ] Migration `177_document_alarm_subscriptions.php` :
  - table `document_alarm_subscriptions` (critères facultatifs, statuts surveillés, champs d'audit)
  - table `notification_history` (champs d'audit inclus)
  - colonnes `membres.doc_alarm_personal` et `membres.doc_alarm_digest_frequency`
  - abonnement « tous documents » créé pour chaque `club-admin` actif ; supprimé au `down()`
- [ ] `application/config/migration.php` → 177
- [ ] Modèles `document_alarm_subscriptions_model.php` (CRUD + correspondance document ↔ abonnement) et `notification_history_model.php`
- [ ] Métadonnées `Gvvmetadata.php` (sélecteurs type de document, section, document ; statuts surveillés ; fréquence)

**5.3 — Bibliothèque `Notification_manager`** (2 j)
- [ ] Alertes pilote : calendrier anti-spam évalué contre `notification_history`, respect de la désinscription
- [ ] Synthèses abonnés : union des documents correspondant aux abonnements, contrôle des droits, dédoublonnage, fréquence hebdomadaire (lundi) ou quotidienne
- [ ] Composition des emails HTML (alerte pilote ; synthèse groupée par section puis par statut, avec lien vers la liste admin)
- [ ] Envoi, journalisation dans `notification_history` et dans les logs (`grep "GVV: DOCALARM"`), poursuite du traitement si un envoi échoue
- [ ] Idempotence : une exécution répétée le même jour n'envoie rien de plus

**5.4 — Déclencheur** (0,5 j)
- [ ] Contrôleur `document_alarm_scheduler.php` : `cron()` (CLI uniquement) et `run($secret)` (URL publique, réponse JSON)
- [ ] Secret `document_alarm_scheduler_secret` dans `program.php` et `program.example.php`
- [ ] Ligne crontab d'exemple (quotidienne) dans l'en-tête du contrôleur et la doc utilisateur

**5.5 — Gestion des abonnements** (2 j)
- [ ] Contrôleur `document_alarm_subscriptions.php`, réservé aux rôles `ca`, `bureau`, `club-admin` : liste « Mes abonnements », création, modification, suppression ; réglage de la fréquence de synthèse sur la même page
- [ ] Refus explicite (message d'erreur) d'un abonnement hors des droits de consultation de l'abonné
- [ ] Bouton « S'abonner » sur la fiche document (`bs_view.php`), pré-remplissant l'abonnement sur ce document
- [ ] Entrée de menu (Admin → Mes abonnements aux alarmes)
- [ ] Liste admin des documents expirés (`bs_expired.php`) : badge « non couvert » sur les documents qu'aucun abonnement ne couvre
- [ ] Formulaire membre (`membre/bs_formView.php`, à côté des préférences de rappel) : case de désinscription des alertes personnelles
- [ ] Message de confirmation après chaque action

**5.6 — Administration des abonnements** (1,5 j)
- [ ] Page réservée à `club-admin` dans le même contrôleur : liste de tous les abonnements filtrable par abonné, type de document et section (`$this->gvvmetadata->table()`)
- [ ] Création pour n'importe quel membre (sélecteur d'abonné), modification et suppression de n'importe quel abonnement
- [ ] Validation des droits appliquée à l'abonné, avec message explicite en cas de refus
- [ ] Colonne « créé par » sur « Mes abonnements » pour les abonnements créés par un administrateur
- [ ] Entrée de menu (Admin → Abonnements aux alarmes)

**5.7 — Bannière à la connexion** (1 j)
- [ ] Dashboard (`welcome`) : alerte Bootstrap refermable listant les documents expirés, proches ou manquants du membre connecté, avec lien vers « Mes documents »
- [ ] Pour les abonnés : alerte refermable indiquant le nombre de documents en alerte couverts par leurs abonnements, avec lien vers la liste admin
- [ ] Pas de bannière quand il n'y a rien à signaler

**5.8 — Langues** (0,5 j)
- [ ] Libellés FR/EN/NL : emails, bannières, pages des abonnements (personnelle et administration), préférences, messages du scheduler

**5.9 — Tests** (2,5 j)
- [ ] PHPUnit MySQL `DocumentAlarmSubscriptionsModelTest.php` : CRUD, correspondance par type / section / document précis, résolution de section des documents pilote via `user_roles_per_section`, documents club, détection des documents non couverts, création pour un tiers (`created_by` ≠ abonné) et refus quand l'abonné n'a pas les droits
- [ ] PHPUnit MySQL `NotificationManagerTest.php` : dédoublonnage multi-abonnements, contrôle des droits (dont retrait de rôle et types privés), fréquences, calendrier anti-spam pilote, désinscription, idempotence, exclusion des membres inactifs et des alertes désactivées ; envoi d'email bouchonné
- [ ] PHPUnit migration 177 : up/down avec sauvegarde et restauration des colonnes `membres` et des abonnements existants
- [ ] Toutes les fixtures sont créées et supprimées par les tests (y compris en cas d'échec), sans dépendance aux données existantes
- [ ] Playwright `document-alarm-subscriptions-smoke.spec.js` : création / modification / suppression d'un abonnement, bouton « S'abonner », refus hors droits, page d'administration (accès `club-admin` seul, abonnement créé pour un autre membre visible dans sa liste avec son auteur), bannières pilote et abonné, déclenchement `run($secret)` (403 avec un mauvais secret, JSON avec le bon)
- [ ] Smoke tests complets phpunit + playwright avant clôture

**5.10 — Documentation** (0,5 j)
- [ ] Guide utilisateur `doc/users/fr/19_alertes_documents.md` : alertes pilote, abonnements, administration des abonnements, configuration du cron et du secret

**Estimation totale Lot 5 : ~12 jours.** Ressources : serveur de test gvv.net (redirection email `test_email` active), accès crontab pour la validation en conditions réelles.

**Validation**
- Un administrateur abonné aux certificats médicaux de la section ULM reçoit, dans la boîte de test, une synthèse ne contenant que ces documents ; un `club-admin` avec l'abonnement par défaut reçoit tout ; un abonnement créé par un `club-admin` pour un autre membre produit la même synthèse que s'il l'avait créé lui-même.
- Un document couvert par deux abonnements d'une même personne n'apparaît qu'une fois.
- Une deuxième exécution le même jour n'envoie aucun email supplémentaire.
- Un document avec alerte désactivée n'apparaît ni dans les emails ni dans les bannières ; un document sans abonné est signalé dans la liste admin.

### Lot 6 — Internationalisation
- [x] Ajouter les libellés FR/EN/NL — fichiers de langue créés dans `application/language/{french,english,dutch}/archived_documents_lang.php`
- [x] Vérifier que tous les libellés UI utilisent `$this->lang->line()` — vues mises à jour

### Lot 7 — Tests & validation
- [x] Tests MySQL : 8 nouveaux tests dans `ArchivedDocumentsModelTest.php` (GetPilotDocuments, GetPilotDocumentStatus, GetMissingDocuments, UpdateDocument, DeleteDocument x4)
- [x] Correction `delete_document()` : retourne un bool via `affected_rows()`
- [x] Tests UI Playwright : `archived-documents-smoke.spec.js` (6 tests : accès admin, liste, expirés, formulaire création, accès pilote, contrôle d'accès)
- [x] Smoke tests : phpunit (1005 tests, 1 échec pré-existant sans rapport) + playwright (6/6 verts)

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
  -- `allow_versioning` supprimé en migration 075 (versionning toujours explicite)
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
| code | name | scope | required | has_expiration |
|------|------|-------|----------|----------------|
| medical | Visite médicale | pilot | 1 | 1 |
| insurance | Assurance | pilot | 1 | 1 |
| license | Brevet/Licence | pilot | 0 | 1 |
| club_doc | Document club | club | 0 | 0 |
| signature | Signature membre | pilot | 0 | 1 |
| ci | Carte d'identité | pilot | 0 | 1 |
| parental | Autorisation parentale | pilot | 0 | 0 |
| bia | Brevet Initiation Aéronautique | pilot | 0 | 0 |

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
  `label` VARCHAR(128) NULL COMMENT 'Libellé identifiant l\'instance parmi d\'autres du même type',
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
Note : `is_current_version = 1` reste valide avec le modèle classe/instance — il filtre les documents non remplacés, qu'il y en ait un ou plusieurs du même type.

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
