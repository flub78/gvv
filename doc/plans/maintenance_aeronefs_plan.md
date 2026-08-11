# Plan d'implémentation — Gestion de la Maintenance des Aéronefs

**Date :** 4 août 2026 — mis à jour le 11 août 2026 (Phases 0 à 9 terminées)
**Statut :** Phase 9 terminée (3/3 étapes) — Phase 10 (tests Playwright) à démarrer
**PRD :** [doc/prds/maintenance_aeronefs_prd.md](../prds/maintenance_aeronefs_prd.md)
**Design :** [doc/design_notes/maintenance_aeronefs_design.md](../design_notes/maintenance_aeronefs_design.md)

---

## Résumé

Ce plan couvre l'implémentation du module de maintenance des aéronefs, conçu comme le miroir architectural du module Formation existant (`formation_programmes` / `formation_lecons` / `formation_sujets` / `formation_inscriptions` / `formation_seances` / `formation_evaluations`).

Correspondance retenue (voir PRD, section "Analogie avec le module Formation") :

| Formation | Maintenance |
|---|---|
| `formation_programmes` | `maintenance_programmes` |
| `formation_lecons` | `maintenance_programme_sections` |
| `formation_sujets` | `maintenance_taches` |
| `formation_inscriptions` | `maintenance_dossiers` |
| `formation_seances` | `maintenance_operations` |
| `formation_evaluations` | `maintenance_realisations` |
| Pilote (`membres`) | Entité maintenable : aéronef (`machinesa`) ou équipement (`maintenance_equipements`, nouvelle table) |
| `Formation_markdown_parser` | Réutilisé ou dupliqué en `Maintenance_markdown_parser` (décision Phase 0) |
| `Formation_progression` (calcul d'avancement) | `Maintenance_potentiel` (calcul du potentiel restant) |

Le programme d'entretien est désormais structuré sur trois niveaux, exactement comme le programme de formation : **programme → section → tâche** (`formation_lecons`/`formation_sujets` ↔ `maintenance_programme_sections`/`maintenance_taches`). Le format markdown des deux modules est donc isomorphe (H1/H2/H3 des deux côtés).

**Décision actée (4 août 2026) :** pas de mutualisation du parseur pour l'implémentation initiale, malgré le format isomorphe. `Maintenance_markdown_parser.php` est une classe dédiée, indépendante de `Formation_markdown_parser`. La mutualisation sera reconsidérée après l'implémentation, si aucune raison de diverger entre les deux modules n'a été identifiée en pratique (cf. Phase 4 et tableau des risques).

**Prérequis** : aucun — le module est autonome. Il s'appuie sur le système documentaire existant (`document_types` / `archived_documents`) et sur le rôle `mecano`, tous deux déjà en production.
**Migration de départ** : version courante 154 → prochain numéro disponible = 155 (à revérifier au démarrage effectif si d'autres migrations sont mergées entre-temps).

---

## Phase 0 — Conception

### Étape 0.1 — Design note et schéma des entités

**Objectif :** Documenter l'architecture avant tout développement, conformément au workflow GVV.

**Fichier :** `doc/design_notes/maintenance_aeronefs_design.md` + diagramme `doc/design_notes/diagrams/maintenance_aeronefs_classes.puml`

Points tranchés en amont de la conception détaillée :
- **Parseur markdown : duplication, pas de mutualisation en phase 1.** `Maintenance_markdown_parser` est une classe dédiée, distincte de `Formation_markdown_parser`, malgré un format isomorphe (programme/section/tâche ↔ programme/leçon/sujet, H1/H2/H3). Objectif : ne pas toucher un module Formation stable et déjà en production tant que le module Maintenance n'a pas fait ses preuves. À réévaluer après l'implémentation (voir tableau des risques) : si aucun besoin de divergence entre les deux parseurs n'apparaît en pratique, extraire un composant commun.

Points tranchés dans le design (5 août 2026) :
- Seuil global "échéance proche" (30 jours par défaut, PRD EF7) : nouvelle entrée dans la table `configuration` existante (`configuration_model::get_param()`), pas de nouvelle table.
- Statut des bulletins de service (à traiter/traité/non applicable) : table compagnon légère `maintenance_bulletin_statuts` (relation 1—0..1 avec `archived_documents`, qui reste générique).
- Nommage du niveau intermédiaire du programme : `maintenance_programme_sections` (jamais `maintenance_sections` seul), pour éviter toute confusion avec la table `sections` (clubs/activités) — cf. PRD, table d'analogie mise à jour en conséquence.
- Schéma ci-dessous (Phase 1) confirmé sans changement.

**Validation :**
- [x] Design note relue et cohérente avec le PRD (aucune contradiction, aucune duplication du contenu du PRD)
- [x] Diagramme de classes PlantUML généré en image et lien inséré dans le design
- [x] Décisions ci-dessus tranchées et actées dans le design

---

## Phase 1 — Fondations base de données

**Statut : Terminée (5 août 2026).** Migrations 155–160 créées et testées (up/down + 6 tests métier) contre la base de test `gvv2` (`application/tests/mysql/MaintenanceMigrationsTest.php`, 16 tests). Suite complète (`./run-all-tests.sh`) : 1715 tests, 0 échec, 61 skips préexistants sans rapport avec ce lot.

**Note d'implémentation :** les colonnes `document_id`/`archived_document_id` sont en `BIGINT(20) UNSIGNED` (et non `INT`) pour correspondre au type réel de `archived_documents.id`. Toutes les tables `maintenance_*` sont créées en `utf8`/`utf8_general_ci` (et non `utf8mb4`, malgré la convention plus récente utilisée par d'autres migrations) : `membres.mlogin` et `archived_documents` sont en `utf8mb3`, et MySQL refuse une clé étrangère entre colonnes de jeux de caractères différents (erreur 150) — la contrainte se propage à toute table `maintenance_*` référençant `membres` ou `archived_documents`.

### Étape 1.1 — Extension du système documentaire

**Objectif :** Permettre de rattacher un document à une entité maintenable (PRD EF2, EF6).

**Fichier :** `application/migrations/155_document_types_scope_machine.php`

- Ajouter la valeur `machine` à l'ENUM `document_types.scope`.
- Aucune modification de `archived_documents` : la colonne `machine_immat` existe déjà (migration 076).

**Validation :**
- [x] Migration créée, syntaxe PHP valide (`php -l`)
- [x] `config/migration.php` mis à jour
- [x] `document_types.scope` accepte la valeur `machine` sans casser les valeurs existantes (`pilot`, `section`, `club`)
- [x] Un document existant de type `pilot`/`section`/`club` reste inchangé après migration

---

### Étape 1.2 — Table `maintenance_equipements`

**Objectif :** Modéliser les équipements comme entités maintenables rattachées à un aéronef (PRD EF1).

**Fichier :** `application/migrations/156_maintenance_equipements.php`

Colonnes :
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `aeronef_id` VARCHAR(10) NOT NULL — FK logique vers `machinesa.macimmat`
- `nom` VARCHAR(100) NOT NULL
- `description` VARCHAR(255) NULL
- `actif` TINYINT(1) NOT NULL DEFAULT 1
- `created_at`, `updated_at`, `created_by`, `updated_by` (audit standard)

**Validation :**
- [x] Migration créée et testée (up/down)
- [x] `config/migration.php` mis à jour
- [x] Un équipement peut changer d'`aeronef_id` sans perdre son historique (vérifié après Phase 1.5/1.6)

---

### Étape 1.3 — Tables `maintenance_programmes`, `maintenance_sections` et `maintenance_taches`

**Objectif :** Modéliser le programme d'entretien à trois niveaux, exactement sur le modèle `formation_programmes`/`formation_lecons`/`formation_sujets` (PRD EF2 — programme → section → tâche, note importante : la table `sections` (clubs/activités) existe déjà dans GVV et n'a **aucun rapport** avec `maintenance_sections` ; retenir un nom de table sans ambiguïté est à confirmer en Phase 0, ex. `maintenance_programme_sections`).

**Fichier :** `application/migrations/157_maintenance_programmes.php`

`maintenance_programmes` :
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `code` VARCHAR(50) NOT NULL
- `titre` VARCHAR(255) NOT NULL
- `section_id` INT NULL (NULL = toutes sections/clubs, cohérent avec `formation_programmes.section_id` — à ne pas confondre avec les sections du programme lui-même, voir ci-dessous)
- `document_id` BIGINT(20) UNSIGNED NULL — FK vers `archived_documents.id` (fichier markdown source, versionné ; type aligné sur `archived_documents.id`)
- `regle_butee_date` TINYINT(1) NOT NULL DEFAULT 0
- `regle_butee_heures` TINYINT(1) NOT NULL DEFAULT 0
- `seuil_heures` DECIMAL(8,2) NULL — seuil d'heures de vol si `regle_butee_heures = 1`
- `periodicite_mois` INT NULL — périodicité calendaire si `regle_butee_date = 1`
- `statut` ENUM('actif','inactif') NOT NULL DEFAULT 'actif'
- audit standard

`maintenance_programme_sections` (niveau intermédiaire, miroir de `formation_lecons`) :
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `programme_id` INT NOT NULL — FK vers `maintenance_programmes.id`
- `ordre` INT NOT NULL
- `titre` VARCHAR(255) NOT NULL
- `actif` TINYINT(1) NOT NULL DEFAULT 1 — **ajouté par la migration 161 (Phase 4)**, absent de ce schéma initial ; nécessaire à la désactivation logique lors du re-parsing d'une nouvelle version (Étape 4.2)
- audit standard

`maintenance_taches` (miroir de `formation_sujets`) :
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `programme_section_id` INT NOT NULL — FK vers `maintenance_programme_sections.id`
- `ordre` INT NOT NULL
- `titre` VARCHAR(255) NOT NULL
- `description` TEXT NULL
- `actif` TINYINT(1) NOT NULL DEFAULT 1 — **ajouté par la migration 161 (Phase 4)**, même raison
- audit standard

**Validation :**
- [x] Migrations créées et testées (up/down)
- [x] `config/migration.php` mis à jour
- [x] Un programme sans section, ou une section sans tâche, reste valide (listes vides acceptées)
- [x] Aucune collision de nommage avec la table `sections` existante (clubs/activités)

---

### Étape 1.4 — Table `maintenance_dossiers`

**Objectif :** Modéliser l'association programme + entité maintenable, sur le modèle `formation_inscriptions` (PRD EF3).

**Fichier :** `application/migrations/158_maintenance_dossiers.php`

Colonnes :
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `entite_type` ENUM('aeronef','equipement') NOT NULL
- `entite_id` VARCHAR(10) NOT NULL — `macimmat` si `aeronef`, `maintenance_equipements.id` si `equipement`
- `programme_id` INT NOT NULL — FK vers `maintenance_programmes.id`
- `mecano_referent_id` VARCHAR(25) NULL — FK vers `membres.mlogin`
- `statut` ENUM('ouvert','suspendu','cloture','abandonne') NOT NULL DEFAULT 'ouvert'
- `date_ouverture` DATE NOT NULL
- `date_suspension` DATE NULL
- `date_cloture` DATE NULL
- `echeance_courante` DATE NULL — calculée, mise à jour à chaque opération
- `heures_restantes_courant` DECIMAL(8,2) NULL — calculée, mise à jour à chaque opération
- `commentaire` VARCHAR(255) NULL
- audit standard

**Validation :**
- [x] Migration créée et testée (up/down)
- [x] `config/migration.php` mis à jour
- [x] Une entité maintenable peut avoir plusieurs dossiers `ouvert` simultanément sur des programmes différents (contrainte vérifiée en base : pas d'unicité programme+entité forcée)

---

### Étape 1.5 — Tables `maintenance_operations` et `maintenance_realisations`

**Objectif :** Modéliser l'opération de maintenance et la réalisation de chaque tâche, sur le modèle `formation_seances`/`formation_evaluations` (PRD EF4).

**Fichier :** `application/migrations/159_maintenance_operations.php`

`maintenance_operations` :
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `dossier_id` INT NOT NULL — FK vers `maintenance_dossiers.id`
- `date_operation` DATE NOT NULL
- `mecano_id` VARCHAR(25) NOT NULL — FK vers `membres.mlogin`
- `mode_saisie` ENUM('directe','compte_rendu') NOT NULL
- `document_id` BIGINT(20) UNSIGNED NULL — FK vers `archived_documents.id` si `mode_saisie = compte_rendu` (type aligné sur `archived_documents.id`)
- `horametre_releve` DECIMAL(8,2) NULL
- `nouvelle_echeance` DATE NULL
- `commentaire` VARCHAR(500) NULL
- audit standard

`maintenance_realisations` :
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `operation_id` INT NOT NULL — FK vers `maintenance_operations.id`
- `tache_id` INT NOT NULL — FK vers `maintenance_taches.id`
- `statut` ENUM('fait','non_fait','non_applicable') NOT NULL DEFAULT 'non_fait'
- `commentaire` VARCHAR(255) NULL

**Validation :**
- [x] Migrations créées et testées (up/down)
- [x] `config/migration.php` mis à jour
- [x] Une opération de type `compte_rendu` sans aucune tâche cochée reste valide (le détail reste dans le document joint, PRD EF4)

---

### Étape 1.6 — Table `maintenance_bulletin_statuts`

**Objectif :** Suivre le statut d'un bulletin de service sans polluer le schéma générique `archived_documents` (PRD EF6).

**Fichier :** `application/migrations/160_maintenance_bulletin_statuts.php`

Colonnes :
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `archived_document_id` BIGINT(20) UNSIGNED NOT NULL UNIQUE — FK vers `archived_documents.id` (type aligné sur `archived_documents.id`)
- `statut` ENUM('a_traiter','traite','non_applicable') NOT NULL DEFAULT 'a_traiter'
- audit standard (`updated_by`/`updated_at` suffisent, pas de `created_*` distinct du document)

**Validation :**
- [x] Migration créée et testée (up/down)
- [x] `config/migration.php` mis à jour
- [ ] Seuls `mecano` et `admin` peuvent modifier `statut` (vérifié en Phase 6 — hors périmètre de la Phase 1, aucun contrôleur n'existe encore)

---

## Phase 2 — Modèles

**Statut : Terminée (5 août 2026).** Les 8 modèles sont créés et testés (`application/tests/mysql/MaintenanceModelsTest.php`, 6 tests / 71 assertions). Suite complète : 1721 tests, 0 échec, 61 skips préexistants.

**Note d'implémentation :** le harnais de test (`application/tests/integration_bootstrap.php`, classe `RealDatabase`) ne portait pas `insert_batch()`, utilisée par `maintenance_realisation_model::save_batch()` (comme par les modèles Formation équivalents). Méthode ajoutée au double de test — infrastructure de test uniquement, aucun changement côté application.

### Étape 2.1 — Modèles CRUD de base

**Objectif :** Un modèle par table, sur le pattern `Common_Model` déjà utilisé par les modèles Formation.

**Fichiers :**
- `application/models/maintenance_equipement_model.php`
- `application/models/maintenance_programme_model.php`
- `application/models/maintenance_programme_section_model.php`
- `application/models/maintenance_tache_model.php`
- `application/models/maintenance_dossier_model.php`
- `application/models/maintenance_operation_model.php`
- `application/models/maintenance_realisation_model.php`
- `application/models/maintenance_bulletin_model.php`

Méthodes minimales par modèle (calquées sur `Formation_*_model`) : `get($id)`, `get_by_section()`/`get_visibles()` où pertinent, `get_by_parent(...)` (ex. `get_by_aeronef`, `get_by_dossier`, `get_by_programme`), `get_full($id)` avec jointures utiles à l'affichage.

**Validation :**
- [x] Fichiers créés, syntaxe valide
- [x] Chaque modèle testé par au moins un test d'intégration CRUD
- [x] `get_by_section()` de `maintenance_programme_model` reproduit la logique de `Formation_programme_model::get_by_section()` (section + programmes globaux)

---

### Étape 2.2 — Transfert d'un équipement (PRD Parcours 5)

**Objectif :** Permettre le changement d'`aeronef_id` d'un équipement sans perte d'historique.

Dans `maintenance_equipement_model` : méthode `transferer($equipement_id, $nouvel_aeronef_id)` — met à jour uniquement `aeronef_id`, ne touche à aucune ligne de `maintenance_dossiers`/`maintenance_operations` (celles-ci référencent l'équipement par `entite_id`, indépendant de l'aéronef courant).

**Validation :**
- [x] Après transfert, l'historique des dossiers et opérations de l'équipement reste identique (vérifié au niveau modèle : `entite_id` inchangé, `get_by_aeronef()` reflète le nouvel aéronef)
- [ ] La fiche du nouvel aéronef affiche l'équipement transféré avec son potentiel inchangé (vue non encore construite — Phase 5)
- [ ] La fiche de l'ancien aéronef ne l'affiche plus (vue non encore construite — Phase 5)

---

## Phase 3 — Calcul du potentiel

**Statut : Terminée (5 août 2026).** `Maintenance_potentiel` créée et testée : logique pure (`calculer_etat`) en test unitaire (`application/tests/unit/libraries/MaintenancePotentielTest.php`, 11 tests), méthodes touchant la base (`appliquer_operation`, `etat_pire_cas`, `mise_a_jour_manuelle`) en test MySQL (`application/tests/mysql/MaintenancePotentielTest.php`, 9 tests). Suite complète : 1741 tests, 0 échec, 61 skips préexistants.

**Décisions d'implémentation (non tranchées explicitement par le PRD, actées ici) :**
- `heures_restantes_courant` est un instantané figé à chaque opération (`= seuil_heures` du programme dès qu'un `horametre_releve` est saisi), pas une valeur qui décompte en continu au fil des vols entre deux opérations — pas de lecture live de l'horamètre courant de l'aéronef en phase 1, cohérent avec la NFR « rester simple » et l'absence de toute automatisation live dans le PRD.
- Pas de notion d'« échéance proche » côté heures (seul le seuil calendaire de 30 jours est défini par le PRD, EF7.2) : la dimension horaire n'a que deux sous-états, `a_jour` (≥ 0) et `depasse` (< 0).
- Quand un dossier suit à la fois une règle calendaire et une règle horaire, ou quand plusieurs dossiers/entités sont combinés (`etat_pire_cas`), l'état retenu est le pire des sous-états (`depasse` > `echeance_proche` > `a_jour`).
- Un dossier sans aucune donnée de potentiel encore renseignée (avant la première opération) est considéré `a_jour` par défaut.

### Étape 3.1 — Bibliothèque `Maintenance_potentiel`

**Objectif :** Centraliser le calcul et la mise à jour du potentiel, sur le modèle de `Formation_progression` (PRD EF5).

**Fichier :** `application/libraries/Maintenance_potentiel.php`

Méthodes :
- `calculer_etat($dossier)` → `'a_jour' | 'echeance_proche' | 'depasse'`, à partir de `echeance_courante`/`heures_restantes_courant` du dossier et du seuil global (30 jours par défaut)
- `appliquer_operation($operation_id)` — met à jour `maintenance_dossiers.echeance_courante` et `heures_restantes_courant` à partir des champs saisis sur l'opération (`nouvelle_echeance`, `horametre_releve` + règle du programme)
- `etat_pire_cas($aeronef_id)` — pour la vue de synthèse flotte (pire état parmi aéronef + ses équipements)
- `mise_a_jour_manuelle($dossier_id, $data, $user)` — corrige le potentiel hors opération, journalise dans les logs avec le marqueur `MAINTENANCE`

**Validation :**
- [x] Fichier créé, syntaxe valide
- [x] `appliquer_operation()` met à jour `echeance_courante` et/ou `heures_restantes_courant` correctement pour les 3 combinaisons de règle de butée (date seule, heures seules, les deux)
- [x] `calculer_etat()` retourne `echeance_proche` uniquement si l'échéance est à moins de 30 jours (valeur par défaut, configurable)
- [x] `mise_a_jour_manuelle()` écrit une ligne de log contenant le marqueur `MAINTENANCE`
- [x] Tests unitaires couvrant les cas limites (échéance dépassée de 1 jour, exactement au seuil, potentiel négatif)

---

## Phase 4 — Programmes d'entretien : parsing et versioning

**Statut : Terminée (5 août 2026)** pour le mécanisme de parsing/versioning lui-même ; l'écran d'upload proprement dit reste à construire en Phase 5 (aucun contrôleur n'existe encore).

**Schéma corrigé au passage (migration 161) :** le schéma de la Phase 1 ne portait aucune colonne permettant la désactivation logique requise par l'Étape 4.2 (`maintenance_programme_sections`/`maintenance_taches` n'avaient pas de colonne `actif`). Ajoutée par la migration `161_maintenance_actif_column.php`.

**Fichiers :**
- `application/libraries/Maintenance_markdown_parser.php`
- `doc/test-data/maintenance_visite_100h.md`
- `application/migrations/161_maintenance_actif_column.php`, `162_maintenance_document_types.php`
- `Maintenance_programme_model::synchroniser_structure($programme_id, $markdown_content, $document_id = null)` — orchestration parse + validate + réconciliation (nouveau, cf. Étape 4.2 ci-dessous)

**Tests :** `application/tests/unit/libraries/MaintenanceMarkdownParserTest.php` (15 tests, parsing pur), `application/tests/mysql/MaintenanceProgrammeSyncTest.php` (5 tests, réconciliation avec vraies données), `application/tests/mysql/MaintenanceMigrationsTest.php` étendu (161/162). Suite complète : 1765 tests, 0 échec, 61 skips préexistants.

### Étape 4.1 — Parsing du markdown structuré

**Objectif :** Extraire la structure à trois niveaux (programme/section/tâche) d'un programme d'entretien déposé en markdown (PRD EF2), sur le modèle exact de `Formation_markdown_parser`, sans le réutiliser (décision actée, cf. Résumé).

**Fichier :** `application/libraries/Maintenance_markdown_parser.php` — classe dédiée, indépendante de `Formation_markdown_parser`.

Format attendu, désormais isomorphe au format Formation : H1 = titre du programme, H2 = section (`maintenance_programme_sections`), H3 = tâche (`maintenance_taches`), contenu = description de la tâche. Plus simple que `Formation_markdown_parser` : ni `maintenance_programme_sections` ni `maintenance_taches` ne portent de colonne `numero` (seulement `ordre`/`titre`), donc aucun préfixe "Leçon X :"/"Sujet X.Y :" à reconnaître, et pas de split description/objectifs (une seule colonne `description`). Le texte placé directement sous un H2, avant la première H3, est ignoré silencieusement (aucune colonne pour le conserver), comme `Formation_markdown_parser` ignore déjà le texte placé avant la première leçon.

**Validation :**
- [x] Parseur créé, syntaxe valide
- [x] Import d'un fichier markdown de test produit les lignes `maintenance_programme_sections` et `maintenance_taches` attendues, dans l'ordre et avec le bon rattachement section → tâche
- [x] Test avec un fichier markdown de test au format documenté (`doc/test-data/maintenance_visite_100h.md`)

---

### Étape 4.2 — Upload et versioning via le système documentaire

**Objectif :** Réutiliser `archived_documents`/`document_types` pour stocker et versionner le fichier source d'un programme d'entretien et les bulletins de service (PRD EF2, EF6).

- Création des `document_types` nécessaires : programme d'entretien (`maintenance_programme`, scope `machine`), bulletin de service (`maintenance_bulletin`, scope `machine`) — migration `162_maintenance_document_types.php`. **Correction :** le plan mentionnait `allow_versioning = 1`, mais cette colonne a été supprimée de `document_types` par la migration 075 (le versioning est désormais toujours explicite via l'action "Nouvelle version", jamais piloté par un indicateur sur le type) ; aucune valeur à fixer pour ce comportement.
- À l'upload d'une nouvelle version d'un programme, ré-exécution du parsing (Étape 4.1) et mise à jour de `maintenance_programme_sections`/`maintenance_taches`, via `Maintenance_programme_model::synchroniser_structure()` : réconciliation par titre (une section/tâche retrouvée à l'identique réutilise sa ligne existante, réactivée si besoin ; sinon nouvelle ligne créée). Une section/tâche absente de la nouvelle version est supprimée si elle n'est référencée par aucune `maintenance_realisation`, sinon désactivée (`actif = 0`, migration 161) — son historique reste consultable.
- L'écran d'upload lui-même (contrôleur documentaire, déclenchement de `synchroniser_structure()` au moment de l'upload) est du ressort de la Phase 5 (Étape 5.2) ; ce qui est livré ici est le mécanisme complet et testé, prêt à être appelé par ce contrôleur.

**Validation :**
- [ ] Nouveau programme créé via l'écran document existant, avec scope `machine` (dépend du contrôleur — Phase 5)
- [x] Nouvelle version d'un programme déclenche le re-parsing et met à jour les sections/tâches actives (validé au niveau modèle, `synchroniser_structure()`)
- [x] Une tâche déjà utilisée dans une réalisation reste consultable après une nouvelle version qui la supprime

---

## Phase 5 — Contrôleurs et vues

**Approche :** contrairement aux Phases 1 à 4 (backend), cette phase est traitée étape par étape avec vérification navigateur réelle à chaque livraison (pas seulement PHPUnit), conformément à la politique du projet sur les changements d'UI.

**Décision d'implémentation transverse (5.1) :** les contrôleurs miroir de Formation (`Formation_types_seances` et consorts) n'utilisent **pas** le `Gvv_Controller`/`Gvvmetadata->table()` générique historique (utilisé par `avion.php`, `categorie.php`, `terrains.php`) mais un style direct — `MY_Controller` + `form_validation` manuel + vues Bootstrap écrites à la main (`index.php`/`form.php`, pas de préfixe `bs_`, pas de `load_last_view()`). Puisque le PRD/plan est explicite sur le fait que le module Maintenance mirroir Formation, **tous les contrôleurs de la Phase 5 suivent ce même style Formation**, pas le style générique. Un développeur qui connaît un contrôleur Formation reconnaît directement la structure d'un contrôleur Maintenance.

**Correction de trajectoire sur le tableau de bord (après 5.1) :** une carte "Équipements" a été ajoutée au tableau de bord Maintenance existant (`bs_sub_dashboard.php`) lors de l'Étape 5.1. À partir de l'Étape 5.2, **aucune nouvelle carte n'est ajoutée** à ce tableau de bord générique pour les étapes suivantes (programmes, dossiers, opérations, bulletins, synthèse) : l'Étape 5.7 prévoit explicitement un tableau de bord Maintenance dédié qui regroupe tout, et multiplier les cartes une par une créerait du travail redondant que 5.7 devrait ensuite défaire. La navigation vers les nouveaux contrôleurs pendant les Étapes 5.2 à 5.6 se fait par URL directe (vérifications manuelle/Playwright), en attendant la consolidation en 5.7.

### Étape 5.1 — Équipements

**Statut : Terminée (5 août 2026).**

**Fichier :** `application/controllers/maintenance_equipements.php` + vues `application/views/maintenance_equipements/` (`index.php`, `form.php`, `transfer.php`)

CRUD équipement + action de transfert (Étape 2.2). Suppression toujours logique (`actif=0`/`reactivate`), jamais de suppression définitive (PRD EF1.3) — pas d'action `delete`.

**Ajouts complémentaires :**
- `maintenance_equipement_model` : `get_all($actif_only)` (jointure `machinesa` pour affichage), `get_aeronef_selector()`, `desactiver()`/`reactiver()`.
- Accès réservé au rôle `mecano` (admin bypass automatique via `user_has_role()`), `show_error(..., 403)` sinon — jamais silencieux.
- Fichiers de langue `maintenance_lang.php` (FR/EN/NL) créés avec les clés de cette étape uniquement (la Phase 8 complètera pour les étapes suivantes — `LanguageCompletenessTest` exige les 3 fichiers synchronisés à tout moment, donc chaque étape doit livrer ses propres traductions plutôt que d'attendre la Phase 8).
- Une carte "Équipements" fonctionnelle ajoutée au tableau de bord Maintenance (`bs_sub_dashboard.php`), sans toucher aux 2 cartes placeholder existantes (réservées à la Phase 5.7).

**Tests :** `playwright/tests/maintenance-equipements-smoke.spec.js` (2 tests : parcours mécano complet création→édition→transfert→désactivation avec vérifications réelles en base ; refus d'accès pour un non-mécano), exécuté avec succès contre gvv.net. Suite PHPUnit complète : 1765 tests, 0 échec. Ce fichier est conservé pour la régression ; la Phase 10 l'étendra plutôt que de le dupliquer.

**Validation :**
- [x] CRUD complet accessible et fonctionnel
- [x] Formulaire de transfert avec sélecteur d'aéronef cible, confirmation explicite

---

### Étape 5.2 — Programmes d'entretien

**Statut : Terminée (5 août 2026).**

**Fichier :** `application/controllers/maintenance_programmes.php` + vues `application/views/maintenance_programmes/` (`index.php`, `form.php`, `view.php`, `upload.php`)

Liste (métadonnées + compteur sections/tâches), création/édition des métadonnées (code, titre, section, règle de butée), détail (structure sections/tâches dans l'ordre + document lié), dépôt d'une version markdown.

**Précision sur « délègue l'upload au contrôleur documentaire existant »** (reformulée après investigation du contrôleur `archived_documents.php`, 1485 lignes) : la réutilisation se fait au niveau **modèle**, pas en redirigeant vers l'écran générique de `archived_documents` (dont l'UI — sélecteurs pilote/section/type multiples — ne convient pas à ce cas d'usage precis). Le contrôleur `maintenance_programmes` appelle directement `Archived_documents_model::create_document()` (méthode déjà réutilisable telle quelle, gère le chaînage `previous_version_id`/`is_current_version`) avec `document_type_id` résolu via `document_types_model->get_by_code('maintenance_programme')` (migration 162). Le stockage physique suit exactement la même convention que le contrôleur existant pour tout type hors `pilot`/`section` (`./uploads/documents/club/<code>/`), vérifiée en lisant `_get_storage_path()`. Le parsing (`Maintenance_markdown_parser`) et la validation ont lieu **avant** tout upload physique : un fichier invalide n'est jamais archivé (vérifié par test).

**Bug trouvé et corrigé pendant la vérification navigateur :** la vue `upload.php` affichait une variable locale `$error` (toujours vide) au lieu de lire `session->flashdata('error')` — l'échec de dépôt d'un markdown invalide ne remontait aucun message. Corrigé pour lire la flashdata, cohérent avec le reste du module.

**Leçon pour le nettoyage des données de test :** `create_document()` ne supprime jamais l'ancienne version lors d'un nouveau dépôt (elle passe seulement `is_current_version=0`, chaînée via `previous_version_id`) — nettoyer uniquement le document courant (`maintenance_programmes.document_id`) laisse les anciennes versions orphelines en base (bloque ensuite un `DELETE` sur `document_types` par contrainte FK). Toujours retrouver toute la chaîne de versions avant de nettoyer des documents de test.

**Tests :** `playwright/tests/maintenance-programmes-smoke.spec.js` (2 tests : création → dépôt → structure parsée affichée → nouvelle version → désactivation/réactivation ; rejet d'un markdown invalide sans archivage). Suite PHPUnit complète : 1765 tests, 0 échec.

**Validation :**
- [x] Liste filtrée par section club/activité (cohérent avec `formation_programmes`)
- [x] Détail d'un programme affiche ses sections et leurs tâches, dans l'ordre

---

### Étape 5.3 — Dossiers d'entretien

**Statut : Terminée (5 août 2026).**

**Fichier :** `application/controllers/maintenance_dossiers.php` + vues `application/views/maintenance_dossiers/` (`index.php`, `ouvrir.php`, `view.php`)

Ouverture (sélection entité + programme), suspension, réactivation, clôture, abandon — mêmes transitions d'état que `formation_inscriptions`.

**Précision sur « depuis la fiche d'une entité »** : ce module ne modifie pas les fiches aéronef/planeur existantes (`avion.php`/`planeur.php`, hors périmètre). L'historique par entité est exposé via `maintenance_dossiers?entite_type=...&entite_id=...` (le même contrôleur/vue `index`, filtré) — la Phase 5.7 (synthèse) et une éventuelle intégration future dans les fiches existantes pourront pointer vers cette URL.

**Ajouts complémentaires :**
- `maintenance_dossier_model` : `get_all($section_id)` (liste admin tout statut, scopée par section via le programme lié), `entite_label($entite_type, $entite_id)` (libellé lisible, résolu applicativement puisque `entite_type`/`entite_id` est une clé polymorphe sans FK).
- `maintenance_equipement_model::get_all_selector()` : sélecteur tous équipements actifs (toutes machines), nécessaire pour choisir l'entité à l'ouverture d'un dossier de type équipement.
- La liste des programmes proposés à l'ouverture réutilise `get_visibles()` (Étape 5.2), donc filtrée par section comme le reste du module.

**Tests :** `playwright/tests/maintenance-dossiers-smoke.spec.js` (1 test : ouverture sur un aéronef → suspendre → réactiver → clôturer → historique consultable via le filtre entité). Suite PHPUnit complète : 1765 tests, 0 échec.

**Validation :**
- [x] Ouverture d'un dossier depuis la fiche d'une entité ou depuis un programme
- [x] Changements de statut (suspendre/clôturer/abandonner) journalisés avec date
- [x] Historique des dossiers (y compris non ouverts) consultable depuis l'entité

---

### Étape 5.4 — Opérations de maintenance

**Statut : Terminée (5 août 2026).**

**Fichier :** `application/controllers/maintenance_operations.php` + vue unique `application/views/maintenance_operations/form.php` (PRD EF4 : un seul écran pour les deux modes)

- Bloc "saisie directe" : radio fait/non fait/non applicable par tâche (regroupées par section, dans l'ordre) + commentaire par tâche + commentaire global.
- Champ de dépôt du compte rendu papier sur le même écran, réutilisant le système documentaire existant (mécanisme identique à l'Étape 5.2). Le mode (`directe`/`compte_rendu`) est déterminé par la présence d'un fichier déposé — jamais un choix exclusif, conformément à EF4.2 ("jamais combinés obligatoirement") : un mécano peut cocher des tâches ET joindre un compte rendu sur la même opération.
- À la validation : appel à `Maintenance_potentiel::appliquer_operation()`. Vérifié en base (pas seulement à l'écran) : `heures_restantes_courant` se met bien à jour à la valeur du `seuil_heures` du programme.

**Migration manquante trouvée et corrigée :** aucun `document_type` n'existait pour le compte rendu d'opération lui-même (la migration 162, Phase 4, n'avait anticipé que le programme d'entretien et le bulletin de service). Ajouté par `163_maintenance_compte_rendu_document_type.php` (code `maintenance_compte_rendu`, scope `machine`).

**Correction d'une opération existante (EF4.4)** : `edit()`/`update()`, jamais de suppression — les réalisations sont remplacées (`delete_by_operation` + `save_batch`) et le potentiel systématiquement recalculé après une correction, y compris si seul le compte rendu ou le commentaire change.

**Tests :** `playwright/tests/maintenance-operations-smoke.spec.js` (1 test : opération en saisie directe → potentiel vérifié en base après coup → correction de l'opération). Suite PHPUnit complète : 1767 tests, 0 échec.

**Validation :**
- [x] Une opération en saisie directe mémorise l'état de chaque tâche cochée
- [x] Une opération avec compte rendu déposé affiche une miniature/lien vers le document depuis l'historique
- [x] Dans les deux modes, le potentiel du dossier est mis à jour après validation
- [x] Correction d'une opération existante possible selon droits, sans suppression silencieuse

---

### Étape 5.5 — Bulletins de service

**Statut : Terminée (5 août 2026).**

**Fichier :** `application/controllers/maintenance_bulletins.php` + vues `application/views/maintenance_bulletins/` (`index.php`, `upload.php`)

Liste par aéronef (sélecteur), dépôt (réutilise le système documentaire existant comme les Étapes 5.2/5.4, `document_type` `maintenance_bulletin` avec `machine_immat` renseigné cette fois — seul cas du module où ce champ est réellement utilisé), changement de statut. Accès mecano/admin déjà garanti par le filtre de rôle du contrôleur (EF6.3), donc aucune vérification supplémentaire nécessaire au niveau de l'action `set_statut()`.

**Bug réel trouvé et corrigé grâce à la vérification navigateur (PHPUnit ne l'aurait pas vu) :** `Maintenance_bulletin_model::get_by_machine()` provoquait une erreur fatale (`result_array() on false`) dès qu'un filtre `document_type_id` était appliqué. Cause : CodeIgniter 2 découpe naïvement la chaîne passée à `select()` sur chaque virgule avant de ré-échapper chaque fragment — `COALESCE(bs.statut, 'a_traiter')` était donc explosé en fragments invalides. Le correctif établi ailleurs dans la base (`formation_seance_model.php`, `form_submissions_model.php`) est de passer `$escape = FALSE` en second argument de `select()`, ce qui rend le découpage/réassemblage neutre. Corrigé dans `maintenance_bulletin_model.php`.

**Pourquoi les tests ne l'avaient pas détecté :** le mock `RealDatabase` (`application/tests/integration_bootstrap.php`) utilisé par tous les tests MySQL de ce plan ne reproduit pas ce découpage — sa méthode `select()` stocke la chaîne complète telle quelle. Le test Phase 2 de ce modèle (`get_by_machine('F-MBUL01')`) n'exerçait d'ailleurs jamais la branche avec filtre `document_type_id`. **Ceci confirme la valeur de la vérification Playwright en conditions réelles à chaque étape** (politique adoptée dès l'Étape 5.1) : c'est elle, et uniquement elle, qui a révélé ce bug.

**Tests :** `playwright/tests/maintenance-bulletins-smoke.spec.js` (2 tests : dépôt d'un bulletin + changement de statut ; refus d'accès pour un non-mécano). Suite PHPUnit complète : 1767 tests, 0 échec.

**Validation :**
- [x] Liste des bulletins avec statut visible
- [x] Changement de statut réservé à mecano/admin (autre rôle bloqué avec message explicite)

---

### Étape 5.6 — Vue de synthèse navigabilité et export PDF

**Statut : Terminée (5 août 2026).**

**Fichier :** `application/controllers/maintenance_synthese.php` + vues `application/views/maintenance_synthese/` (`index.php`, `aeronef.php`)

- Vue par aéronef : état de chaque entité maintenable (l'aéronef lui-même + chacun de ses équipements actifs comme entités distinctes), détail des dossiers ouverts et de leur échéance/potentiel individuels.
- Vue flotte filtrable par section : pire état par aéronef (`Maintenance_potentiel::etat_pire_cas()`).
- Export PDF de synthèse par aéronef (réutilisation de TCPDF exactement comme `programmes.php::export_pdf()`).
- Codes couleur centralisés dans une seule constante (`Maintenance_synthese::ETAT_BADGES`) partagée par les deux vues et le PDF, plutôt que dupliqués — garantit la cohérence demandée par la validation.

**Ajout complémentaire :** `Maintenance_potentiel::etat_entite($entite_type, $entite_id)` — pire état des dossiers ouverts d'une seule entité (nouveau, extrait de `etat_pire_cas()` qui l'utilise maintenant en interne pour l'aéronef et chacun de ses équipements ; non-régression vérifiée par la suite de tests existante de la Phase 3). `maintenance_equipement_model::get_aeronefs_by_section()` pour la liste flotte.

**Second bug réel du même type trouvé en amont, corrigé avant même d'écrire ce contrôleur** (cf. Étape 5.5) : par prudence après la découverte du bug `select()`/virgule, ce contrôleur n'utilise aucune expression `COALESCE`/fonction multi-arguments dans un `select()` — les agrégations (pire état) sont calculées en PHP à partir de plusieurs requêtes simples plutôt qu'en SQL, ce qui élimine le risque pour cette étape.

**Tests :** `playwright/tests/maintenance-synthese-smoke.spec.js` (1 test : vue flotte → filtrage par section → détail aéronef avec état par entité → export PDF, vérifié `Content-Type: application/pdf` et statut 200 réels). Suite PHPUnit complète : 1767 tests, 0 échec.

**Validation :**
- [x] Codes couleur cohérents entre vue aéronef et vue flotte
- [x] Export PDF généré et lisible, contient l'ensemble des entités et leur état
- [x] Filtrage par section fonctionnel

---

### Étape 5.7 — Dashboard maintenance dédié

**Statut : Terminée (5 août 2026).**

**Fichier :** `application/controllers/maintenance_dashboard.php` + vue dédiée `application/views/maintenance_dashboard/index.php`

- Regroupe les 6 cartes : équipements, programmes, dossiers, opérations, bulletins, synthèse.
- Activation des deux cartes existantes (`db_card_maintenance_prog`, `db_card_maintenance_ops`) sur le dashboard principal (`bs_sub_dashboard.php`), pointant vers ce dashboard dédié plutôt que directement vers un sous-écran. Les deux autres cartes placeholder de cette section (`db_card_airworthiness`, `db_card_fleet_mgmt`) restent désactivées, hors périmètre de ce plan.
- **Nettoyage de la carte "Équipements" ajoutée en 5.1** : la carte ad hoc ajoutée directement sur le dashboard principal lors de l'Étape 5.1 (avant que ce dashboard dédié n'existe) a été retirée de `bs_sub_dashboard.php` ; elle est désormais uniquement dans le dashboard dédié, évitant la duplication annoncée dans la note de correction de trajectoire (cf. début de Phase 5).
- **Ajout complémentaire :** `maintenance_operations` n'avait jusqu'ici aucune vue de liste globale (Étape 5.4 ne construit que les écrans liés à un dossier). Une action `index()` a été ajoutée (`maintenance_operation_model::get_all()`, liste des opérations récentes toutes entités confondues, scopée par section) pour donner une destination réelle à la carte "Opérations de maintenance", plutôt qu'un lien qui ne mène nulle part.

**Tests :** `playwright/tests/maintenance-dashboard-smoke.spec.js` (2 tests : les deux cartes du dashboard principal ne sont plus "Bientôt disponible" et mènent au dashboard dédié, qui affiche les 6 cartes pointant chacune vers le bon contrôleur ; un non-mécano reçoit un refus explicite). Suite PHPUnit complète : 1767 tests, 0 échec.

**Incident hors périmètre du code, résolu en cours d'étape :** le fichier de test `application/tests/unit/libraries/MaintenanceMarkdownParserTest.php` (créé en Phase 4, 15 tests) a été retrouvé déplacé dans la corbeille système (`~/.local/share/Trash/`) en cours de session — cause exacte inconnue (pas une action délibérée de l'agent). Détecté par une baisse inattendue du nombre de tests dans `run-all-tests.sh` (481 → 466), restauré depuis la corbeille, contenu identique confirmé par `diff`. Aucune perte.

**Validation :**
- [x] Dashboard maintenance accessible et affiche toutes les cartes du module
- [x] Les deux cartes existantes ne sont plus en état "bientôt disponible" et pointent correctement
- [x] Visibilité conditionnée à `is_mecano || is_admin` (cohérent avec l'existant)

---

## Phase 5 — Bilan

**Phase 5 terminée dans son intégralité (5 août 2026), 7/7 étapes.** Tous les contrôleurs suivent le style Formation (`MY_Controller` direct, pas de `Gvv_Controller`/`Gvvmetadata` générique). Chaque étape a été vérifiée en navigateur réel (Playwright contre gvv.net) en plus de la suite PHPUnit, conformément à la politique du projet sur les changements d'UI — ce choix a permis de détecter deux bugs réels invisibles aux tests unitaires/MySQL (Étapes 5.5/5.6) : le découpage naïf des virgules par `CodeIgniter 2::select()` sur toute expression contenant une fonction SQL multi-arguments (`COALESCE`), non reproduit par le mock `RealDatabase` des tests.

Récapitulatif des ajouts non prévus explicitement par le plan initial, mais nécessaires à son exécution :
- Migration 163 (document_type manquant pour les comptes rendus d'opération, Étape 5.4).
- `Maintenance_potentiel::etat_entite()` (Étape 5.6, extrait de `etat_pire_cas()`).
- `maintenance_operations::index()` (Étape 5.7, destination pour la carte dashboard).
- Correctif `select(..., FALSE)` dans `maintenance_bulletin_model.php` (Étape 5.5).

La Phase 6 (rôles et accès) doit maintenant affiner la matrice de droits PRD EF8 : tous les contrôleurs de la Phase 5 sont actuellement gardés uniformément par `mecano || admin`, sans encore distinguer les accès en lecture seule (responsable de section, trésorier, pilote) prévus par le PRD.

## Phase 6 — Rôles et accès

**Statut : Terminée (11 août 2026).**

### Étape 6.1 — Vérifications d'autorisation

**Objectif :** Appliquer la matrice de droits du PRD (EF8) sur l'ensemble des contrôleurs créés en Phase 5.

- Mecano/admin : écriture complète sur leur périmètre (section pour mecano, toutes sections pour admin).
- Responsable de section/trésorier : lecture seule (synthèse + historique).
- Pilote : lecture seule limitée à l'état de navigabilité, sans détail d'intervention.

**Fichier :** `application/libraries/Maintenance_access.php` (nouveau) — centralise la matrice de droits, miroir de `Formation_access` (déjà utilisé par le module Formation), plutôt que de dupliquer des `user_has_role()` bruts dans chaque contrôleur. Méthodes : `is_mecano()`/`can_write()` (mecano ou admin, section courante pour mecano), `can_view_historique()` (mecano/admin + rôles `ca`/`tresorier`, section courante), `can_view_synthese()` (tout membre connecté), `require_write()` (403 explicite si écriture refusée).

**Décision d'implémentation — correspondance des rôles PRD → rôles GVV existants :** le PRD emploie « responsable de section » sans nom de rôle technique. Le rôle GVV le plus proche, déjà utilisé partout ailleurs dans la base pour ce même profil (lecture globale d'une section, y compris données financières), est `ca` (Conseil d'Administration) — c'est ce rôle qui a été retenu pour `can_view_historique()`, aux côtés de `tresorier` (rôle explicite dans le PRD). Le « pilote » du PRD n'est pas un rôle technique séparé : c'est tout membre connecté ne disposant d'aucun des rôles ci-dessus, donc `can_view_synthese()` ne vérifie que `dx_auth->is_logged_in()`.

**Répartition retenue par contrôleur** (lecture élargie seulement là où le PRD la prévoit explicitement — EF8.3 « synthèse + historique », EF7.4 « documents rattachés accessibles selon les droits ») :
- `maintenance_synthese` (état de navigabilité, EF7) : lecture (`index`/`aeronef`/`export_pdf`) ouverte à **tout membre connecté**, y compris le pilote — aucune méthode de ce contrôleur n'expose de détail d'intervention (commentaires, tâches réalisées), seulement des états agrégés (`a_jour`/`echeance_proche`/`depasse`).
- `maintenance_dossiers`, `maintenance_operations`, `maintenance_bulletins`, `maintenance_programmes` (historique) : lecture (`index`/`view`/`code_unique`) ouverte à **mecano/admin/ca/tresorier** ; toute action d'écriture (`ouvrir_form`, `store`, `edit`, `update`, `upload`, `set_statut`, `deactivate`...) reste réservée à **mecano/admin** via `require_write()` appelé en tête de chaque méthode. Le pilote reste bloqué (403 explicite) sur ces quatre contrôleurs — pas de mode "détail d'intervention" en lecture seule pour lui (PRD EF8.4 l'exclut explicitement, et `maintenance_operations::edit()` n'a de toute façon pas de variante lecture seule, une seule vue sert les deux modes).
- `maintenance_equipements` : **inchangé**, reste réservé à mecano/admin dans son intégralité. Ce contrôleur gère des données maîtres (fiches équipement, transfert) qui ne relèvent ni de la « synthèse » ni de l'« historique » du PRD — les ouvrir en lecture n'était pas demandé.
- `maintenance_dashboard` : **inchangé**, reste réservé à mecano/admin (cohérent avec EF9.3, qui fige explicitement la visibilité des cartes du tableau de bord principal sur `is_mecano || is_admin` sans mention de révision en Phase 6). Conséquence assumée : le responsable de section/trésorier/pilote n'a pour l'instant aucun point d'entrée menu vers `maintenance_synthese` — uniquement l'URL directe (`/maintenance_synthese`). Câbler une navigation dédiée pour ces rôles n'était pas dans le périmètre de cette étape ; à reconsidérer si le besoin est exprimé.

**Sectionnement (mecano vs admin, ca/tresorier) :** le sectionnement était déjà en place au niveau modèle depuis la Phase 5 (`$this->session->userdata('section')` passé à `get_all()`/`get_by_section_admin()`/`get_aeronefs_by_section()`) ; `Maintenance_access` réutilise la même section courante de session pour les vérifications de rôle, donc un mecano/ca/trésorier ne voit que les données de sa section, tandis qu'un admin (bypass `dx_auth->is_admin()`) voit tout sans restriction.

**Tests :** `playwright/tests/maintenance-roles-smoke.spec.js` (3 tests contre gvv.net, utilisateurs `asterix` (pilote), `testca` (responsable de section), `testtresorier` (trésorier), tous prévus par `bin/create_test_users.sh`) : pilote → synthèse OK, 403 explicite partout ailleurs ; ca/trésorier → lecture OK sur synthèse + les 4 contrôleurs d'historique, 403 explicite sur toute action d'écriture et sur équipements. Suite complète des 7 tests smoke Phase 5 rejouée sans régression (mecano/admin inchangés). Suite PHPUnit complète : 1860 tests, 0 échec, 61 skips préexistants.

**Validation :**
- [x] Chaque contrôleur vérifie le rôle avant toute action d'écriture
- [x] Un pilote ne peut pas ouvrir le détail d'une opération (403 explicite)
- [x] Un responsable de section ne voit que sa section, un admin voit tout

---

## Phase 7 — Point d'ancrage alarmes et réservations (sans implémentation)

**Statut : Terminée (11 août 2026).**

### Étape 7.1 — API interne d'exposition des échéances

**Objectif :** Permettre au futur mécanisme d'alarmes génériques de lire les échéances de maintenance sans dupliquer le calcul (PRD EF10).

**Fichier :** `application/libraries/Maintenance_potentiel.php` — nouvelle méthode publique `lister_echeances_actives($section_id = null)`. Ne touche à aucun autre fichier : lecture seule, réutilise les modèles déjà chargés par le constructeur de la bibliothèque (`maintenance_dossier_model::get_all()` pour les dossiers scopés par section — filtrage en PHP sur `statut === 'ouvert'`, `maintenance_programme_model::get()` pour la règle de butée du programme, `entite_label()` pour le libellé) plutôt que d'ajouter une méthode dédiée aux modèles.

Structure retournée, un tableau associatif par dossier ouvert : `dossier_id`, `entite_type`, `entite_id`, `entite_label`, `programme_id`, `programme_code`, `programme_titre`, `regle_butee_date`, `regle_butee_heures`, `echeance_courante`, `heures_restantes_courant`, `etat` (réutilise `calculer_etat()`, pas de recalcul dupliqué).

**Tests :** `application/tests/mysql/MaintenancePotentielTest.php` étendu (3 tests : ne retient que les dossiers `ouvert` — un suspendu et un clôturé sont explicitement exclus ; structure complète vérifiée sans aucun balisage HTML dans les valeurs ; filtrage par section vérifié avec deux sections réelles distinctes). Suite complète : 1863 tests, 0 échec, 61 skips préexistants — y compris la suite réservations (`aircraft_booking`), intégralement inchangée.

**Validation :**
- [x] Méthode retourne une structure exploitable indépendamment de l'UI (pas de HTML, pas de dépendance vue)
- [x] Aucune modification du statut "Maintenance" des réservations (`doc/prds/aircraft_booking_prd.md`) — vérifié par non-régression des tests réservations existants (aucun fichier du module réservations touché)

---

## Phase 8 — Fichiers de langue

**Statut : Terminée (11 août 2026), sans aucune modification de code.**

### Étape 8.1 — Clés FR / EN / NL

**Fichiers :**
- `application/language/french/maintenance_lang.php`
- `application/language/english/maintenance_lang.php`
- `application/language/dutch/maintenance_lang.php`

Couvrant : équipements, programmes, tâches, dossiers (statuts), opérations, bulletins, synthèse, messages de confirmation/erreur.

**Constat :** cette étape était déjà entièrement satisfaite par construction — chaque étape de la Phase 5 (5.1 à 5.7) a livré ses propres clés dans les trois langues au fur et à mesure (décision actée dès l'Étape 5.1, cf. note correspondante), plutôt que d'attendre cette Phase 8 pour tout centraliser. Vérifié explicitement ici (11 août 2026), sans code à écrire :
- `php -l` sans erreur sur les 3 fichiers `maintenance_lang.php` (206 lignes chacun, FR/EN/NL).
- `LanguageCompletenessTest` (6 tests) : passe, les 3 fichiers ont un jeu de clés identique.
- Audit complémentaire (grep de tous les appels `$this->lang->line(...)` dans `application/controllers/maintenance_*.php`, `application/views/maintenance_*/*.php`, `application/libraries/Maintenance_*.php`, y compris les clés composées dynamiquement — `'maintenance_bulletin_statut_' . $statut`, `'maintenance_realisation_' . $statut`) : toutes les clés utilisées sont définies, soit dans `maintenance_lang.php`, soit dans `tableaux_de_bord_lang.php` (cartes/libellés du dashboard, chargé explicitement par chaque contrôleur), sans aucune clé manquante.

**Validation :**
- [x] Fichiers créés sans erreur de syntaxe
- [x] Tous les `$this->lang->line(...)` utilisés ont une clé définie dans les trois langues (couvert par le test existant `LanguageCompletenessTest`, complété par un audit manuel des clés composées dynamiquement, hors de portée d'un grep statique simple)

---

## Phase 9 — Tests PHPUnit

**Statut : Terminée (11 août 2026).**

### Étape 9.1 — Tests unitaires

**Statut : déjà satisfaite**, livrée dès les Phases 3 et 4 plutôt qu'en fin de plan (même logique que la Phase 8 pour les langues) :
- `application/tests/unit/libraries/MaintenancePotentielTest.php` (11 tests, Étape 3.1)
- `application/tests/unit/libraries/MaintenanceMarkdownParserTest.php` (15 tests, Étape 4.1)

Cas de test : calcul d'état selon règle de butée (date/heures/les deux), seuil "échéance proche", mise à jour manuelle avec marqueur de log, parsing markdown (ordre, titres, cas limites).

---

### Étape 9.2 — Tests d'intégration

**Décision d'implémentation — localisation réelle des tests :** contrairement au sketch initial de cette étape (`application/tests/integration/Maintenance*ModelTest.php`), les tests de modèles du module vivent dans `application/tests/mysql/` (CRUD réel contre la base de test, cohérent avec la distinction du projet entre `tests/integration/` — dépendances framework sans accès BDD — et `tests/mysql/` — opérations CRUD réelles). Les 5 cas de test explicitement demandés par cette étape étaient déjà couverts, livrés incrémentalement dès les Phases 1 et 2 :
- **CRUD** : `application/tests/mysql/MaintenanceModelsTest.php` (Phase 2, 7 tests après extension ci-dessous)
- **Transfert d'équipement (historique préservé)** : `MaintenanceMigrationsTest::testEquipementCanBeTransferredToAnotherAeronef` (Phase 1) + `MaintenanceModelsTest::testEquipementCrudAndTransfer` (Phase 2) + vérification end-to-end réelle en base via Playwright (Étape 5.1)
- **Dossiers multiples simultanés sur une même entité** : `MaintenanceMigrationsTest::testEntiteCanHaveMultipleOpenDossiersOnDifferentProgrammes` (Phase 1)
- **Opération `compte_rendu` sans tâche cochée** : `MaintenanceMigrationsTest::testCompteRenduOperationWithoutRealisationIsValid` (Phase 1) + `MaintenanceModelsTest::testOperationAndRealisationModels` (`save_batch()` avec tableau vide, Phase 2)
- **Changement de statut bulletin restreint par rôle** : testé en conditions réelles via Playwright (`maintenance-bulletins-smoke.spec.js` Étape 5.5, `maintenance-roles-smoke.spec.js` Étape 6.1), pas en PHPUnit — cohérent avec l'absence de précédent PHPUnit pour ce type de vérification dans tout le projet (aucun test de ce genre n'existe non plus pour `Formation_access`) ; la restriction de rôle est un comportement de contrôleur (403 via `show_error()`, qui termine le process — non testable en isolation PHPUnit), pas de modèle.

**Ajouts faits dans cette étape** pour combler les seuls véritables trous de couverture identifiés en 9.3 (voir ci-dessous) :
- `application/tests/mysql/MaintenanceAccessTest.php` (nouveau, 5 tests) : couvre `Maintenance_access` (Phase 6), qui n'avait encore aucun test PHPUnit (seulement Playwright) — `is_mecano()`/`can_write()`/`can_view_historique()`/`can_view_synthese()` pour aucun rôle, mecano, ca, trésorier, et un rôle accordé dans une autre section (sans effet sur la section courante). Utilise le même pattern que `AuthorizationIntegrationTest` (attribution/retrait réel de rôles en base pour l'utilisateur fixé par le mock de test, `user_id=1`, créé à la volée si absent). `require_write()` n'est testé que sur son chemin positif (son chemin de refus appelle `show_error()`, qui termine le process — non testable en PHPUnit, déjà couvert par Playwright).
- `application/tests/mysql/MaintenanceModelsTest.php` étendu : nouveau test `testEquipementModelListingsAndDeactivation` (`get_all()`, `get_all_selector()`, `get_aeronef_selector()`, `get_aeronefs_by_section()`, `desactiver()`/`reactiver()` — méthodes ajoutées en Phase 5 sans test modèle dédié jusqu'ici) ; `testOperationAndRealisationModels` complété avec `maintenance_operation_model::get_all()` (Étape 5.7).

**Incident de test corrigé en cours d'étape :** la première version de `testRoleScopeSurUneAutreSectionNeDonneAucunDroitIci` nettoyait sa section secondaire à la fin du corps de test plutôt que dans `tearDown()` — un run intermédiaire ayant échoué avant ce correctif a laissé une section orpheline en base, faisant échouer `TestUsersCoherenceTest::testPanoramixIsClubAdminInAllSections` (qui énumère toutes les sections). Section orpheline supprimée, test corrigé pour stocker l'id dans une propriété nettoyée par `tearDown()` (donc sûr même si une assertion échoue en cours de test) — leçon similaire à celle déjà notée en Étape 5.2 sur le nettoyage de données de test.

---

### Étape 9.3 — Exécution de la suite complète

**Résultats (11 août 2026) :** suite complète verte, 1869 tests (1808 passants, 61 skips préexistants sans rapport avec ce module, 0 échec). Couverture (`./run-all-tests.sh --coverage`) de tous les fichiers `libraries`/`models` du module ≥ 70 %, y compris après comblement des trous identifiés :

| Fichier | Avant complément | Après complément |
|---|---|---|
| `Maintenance_access.php` | 0 % (aucun test PHPUnit) | 85.7 % |
| `maintenance_equipement_model.php` | 39.3 % | 95.1 % |
| `maintenance_operation_model.php` | 60.5 % | 88.4 % |
| `maintenance_programme_model.php` | 70.9 % (déjà conforme) | 70.9 % |
| Autres fichiers `Maintenance_*`/`maintenance_*_model` | 90–97 % | inchangé |

**Note sur les contrôleurs :** aucun contrôleur `maintenance_*.php` n'apparaît dans le rapport de couverture PHPUnit — cohérent avec le reste du projet (un seul contrôleur sur ~50 apparaît dans `build/logs/clover.xml`) : les contrôleurs de ce module, comme ceux de Formation, sont vérifiés par Playwright en conditions réelles plutôt que par PHPUnit, qui ne les charge jamais. Le seuil de 70 % s'applique donc naturellement aux `libraries`/`models`, seuls fichiers réellement mesurés par la suite PHPUnit.

**Validation :**
- [x] `source setenv-php7.sh && ./run-all-tests.sh` (et/ou `setenv-php8.sh`) passe sans régression
- [x] Couverture des nouveaux fichiers ≥ 70 %

---

## Phase 10 — Tests Playwright (smoke tests)

### Étape 10.1 — Parcours mécano

**Fichier :** `playwright/tests/maintenance-smoke.spec.js`

Scénarios :
- [ ] Connexion mecano → dashboard maintenance accessible
- [ ] Création d'un équipement, ouverture d'un dossier, enregistrement d'une opération en saisie directe → potentiel mis à jour visible
- [ ] Enregistrement d'une opération avec dépôt d'un compte rendu → document consultable depuis l'historique
- [ ] Transfert d'un équipement vers un autre aéronef → historique préservé

### Étape 10.2 — Parcours pilote (lecture seule)

- [ ] Connexion pilote → accès à la synthèse de navigabilité en lecture seule
- [ ] Aucun accès au détail des opérations (contrôle négatif)

---

## Phase 11 — Documentation

### Étape 11.1 — Compléter le design

- [ ] Schéma final des tables intégré au design (`doc/design_notes/maintenance_aeronefs_design.md`)
- [ ] Décisions de la Phase 0 confirmées ou ajustées selon ce qui a été effectivement implémenté
- [ ] Réévaluation de la mutualisation `Formation_markdown_parser`/`Maintenance_markdown_parser` : conclusion consignée (mutualiser maintenant, ou reporter à nouveau avec justification)

### Étape 11.2 — Documentation utilisateur

**Fichier :** `doc/users/fr/15_maintenance_aeronefs.md` (numérotation à ajuster selon le sommaire courant de `doc/users/fr/README.md`)

Contenu : équipements, programmes d'entretien (dépôt et versioning), ouverture d'un dossier, enregistrement d'une opération (les deux modes), bulletins de service, lecture de la synthèse de navigabilité, export PDF.

**Captures d'écran :** `doc/users/screenshots/maintenance_aeronefs/`

**Validation :**
- [ ] Fichier créé et référencé dans `doc/users/fr/README.md` et `doc/users/README.md`
- [ ] Captures d'écran produites (Playwright)

### Étape 11.3 — Release notes

- [ ] Entrée ajoutée dans `doc/release_notes.md`

---

## Suivi global

| Phase | Description | Statut |
|---|---|---|
| 0 | Conception (design + schéma) | ✅ Terminé |
| 1 | Fondations base de données (migrations 155–160) | ✅ Terminé |
| 2 | Modèles | ✅ Terminé |
| 3 | Calcul du potentiel (`Maintenance_potentiel`) | ✅ Terminé |
| 4 | Parsing et versioning des programmes | ✅ Terminé (mécanisme ; écran Phase 5) |
| 5 | Contrôleurs et vues | ✅ Terminé (7/7 étapes) |
| 6 | Rôles et accès | ✅ Terminé |
| 7 | Point d'ancrage alarmes/réservations | ✅ Terminé |
| 8 | Fichiers de langue FR/EN/NL | ✅ Terminé (déjà satisfait, livré incrémentalement en Phase 5) |
| 9 | Tests PHPUnit | ✅ Terminé |
| 10 | Tests Playwright | ⬜ Non démarré |
| 11 | Documentation | ⬜ Non démarré |

---

## Risques et points d'attention

| Risque | Mitigation |
|---|---|
| Divergence progressive entre le module Formation et le module Maintenance (deux implémentations qui devaient être miroir) | Revue de code croisée référant explicitement aux fichiers Formation équivalents ; envisager l'extraction d'un composant commun si la duplication devient trop importante |
| Complexité du parsing markdown dupliquée entre deux modules (`Maintenance_markdown_parser` vs `Formation_markdown_parser`, format isomorphe) | Duplication assumée en phase 1 pour ne pas risquer de régression sur le module Formation stable. Revue explicite après implémentation : si aucune raison de diverger n'est apparue en pratique, extraire un composant commun (Phase 4/11) |
| Collision de nommage entre `maintenance_programme_sections` (subdivision d'un programme) et la table `sections` existante (clubs/activités : planeur/avion/ULM) | Nom de table explicite retenu (`maintenance_programme_sections`, jamais `maintenance_sections` seul) ; vigilance dans le code et les tests pour ne jamais confondre les deux notions |
| Ambiguïté sur l'entité `entite_type`/`entite_id` polymorphe dans `maintenance_dossiers`/`maintenance_operations` (pas de contrainte FK native possible) | Valider systématiquement l'existence de l'entité au niveau applicatif (modèle), couvrir par tests d'intégration dédiés |
| Confusion possible entre le statut "Maintenance" des réservations et les statuts internes des dossiers d'entretien | Vocabulaire distinct dans l'UI, aucune automatisation entre les deux en phase 1 (cf. PRD non-objectifs) |
| Reprise de données pour les clubs ayant déjà un suivi papier/tableur de maintenance | Hors périmètre de ce plan ; à traiter comme migration de données séparée si demandée |
| `$this->db->select("...COALESCE(a, b)...")` sans `$escape = FALSE` provoque une erreur fatale (CodeIgniter 2 découpe la chaîne sur chaque virgule) — trouvé en Étape 5.5, invisible pour les tests PHPUnit car le mock `RealDatabase` ne reproduit pas ce découpage | Toujours passer `FALSE` en second argument de `select()` dès qu'une expression contient une virgule (fonction SQL multi-arguments) ; vigilance particulière en Étape 5.6 (agrégations pour la synthèse) ; la vérification Playwright en conditions réelles reste indispensable pour ce type de bug |
