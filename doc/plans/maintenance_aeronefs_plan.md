# Plan d'implémentation — Gestion de la Maintenance des Aéronefs

**Date :** 4 août 2026 — mis à jour le 5 août 2026 (Phase 0 terminée)
**Statut :** Phase 0 terminée
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

### Étape 1.1 — Extension du système documentaire

**Objectif :** Permettre de rattacher un document à une entité maintenable (PRD EF2, EF6).

**Fichier :** `application/migrations/155_document_types_scope_machine.php`

- Ajouter la valeur `machine` à l'ENUM `document_types.scope`.
- Aucune modification de `archived_documents` : la colonne `machine_immat` existe déjà (migration 076).

**Validation :**
- [ ] Migration créée, syntaxe PHP valide (`php -l`)
- [ ] `config/migration.php` mis à jour
- [ ] `document_types.scope` accepte la valeur `machine` sans casser les valeurs existantes (`pilot`, `section`, `club`)
- [ ] Un document existant de type `pilot`/`section`/`club` reste inchangé après migration

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
- [ ] Migration créée et testée (up/down)
- [ ] `config/migration.php` mis à jour
- [ ] Un équipement peut changer d'`aeronef_id` sans perdre son historique (vérifié après Phase 1.5/1.6)

---

### Étape 1.3 — Tables `maintenance_programmes`, `maintenance_sections` et `maintenance_taches`

**Objectif :** Modéliser le programme d'entretien à trois niveaux, exactement sur le modèle `formation_programmes`/`formation_lecons`/`formation_sujets` (PRD EF2 — programme → section → tâche, note importante : la table `sections` (clubs/activités) existe déjà dans GVV et n'a **aucun rapport** avec `maintenance_sections` ; retenir un nom de table sans ambiguïté est à confirmer en Phase 0, ex. `maintenance_programme_sections`).

**Fichier :** `application/migrations/157_maintenance_programmes.php`

`maintenance_programmes` :
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `code` VARCHAR(50) NOT NULL
- `titre` VARCHAR(255) NOT NULL
- `section_id` INT NULL (NULL = toutes sections/clubs, cohérent avec `formation_programmes.section_id` — à ne pas confondre avec les sections du programme lui-même, voir ci-dessous)
- `document_id` INT NULL — FK vers `archived_documents.id` (fichier markdown source, versionné)
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
- audit standard

`maintenance_taches` (miroir de `formation_sujets`) :
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `programme_section_id` INT NOT NULL — FK vers `maintenance_programme_sections.id`
- `ordre` INT NOT NULL
- `titre` VARCHAR(255) NOT NULL
- `description` TEXT NULL
- audit standard

**Validation :**
- [ ] Migrations créées et testées (up/down)
- [ ] `config/migration.php` mis à jour
- [ ] Un programme sans section, ou une section sans tâche, reste valide (listes vides acceptées)
- [ ] Aucune collision de nommage avec la table `sections` existante (clubs/activités)

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
- [ ] Migration créée et testée (up/down)
- [ ] `config/migration.php` mis à jour
- [ ] Une entité maintenable peut avoir plusieurs dossiers `ouvert` simultanément sur des programmes différents (contrainte vérifiée en base : pas d'unicité programme+entité forcée)

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
- `document_id` INT NULL — FK vers `archived_documents.id` si `mode_saisie = compte_rendu`
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
- [ ] Migrations créées et testées (up/down)
- [ ] `config/migration.php` mis à jour
- [ ] Une opération de type `compte_rendu` sans aucune tâche cochée reste valide (le détail reste dans le document joint, PRD EF4)

---

### Étape 1.6 — Table `maintenance_bulletin_statuts`

**Objectif :** Suivre le statut d'un bulletin de service sans polluer le schéma générique `archived_documents` (PRD EF6).

**Fichier :** `application/migrations/160_maintenance_bulletin_statuts.php`

Colonnes :
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `archived_document_id` INT NOT NULL UNIQUE — FK vers `archived_documents.id`
- `statut` ENUM('a_traiter','traite','non_applicable') NOT NULL DEFAULT 'a_traiter'
- audit standard (`updated_by`/`updated_at` suffisent, pas de `created_*` distinct du document)

**Validation :**
- [ ] Migration créée et testée (up/down)
- [ ] `config/migration.php` mis à jour
- [ ] Seuls `mecano` et `admin` peuvent modifier `statut` (vérifié en Phase 6)

---

## Phase 2 — Modèles

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
- [ ] Fichiers créés, syntaxe valide
- [ ] Chaque modèle testé par au moins un test d'intégration CRUD
- [ ] `get_by_section()` de `maintenance_programme_model` reproduit la logique de `Formation_programme_model::get_by_section()` (section + programmes globaux)

---

### Étape 2.2 — Transfert d'un équipement (PRD Parcours 5)

**Objectif :** Permettre le changement d'`aeronef_id` d'un équipement sans perte d'historique.

Dans `maintenance_equipement_model` : méthode `transferer($equipement_id, $nouvel_aeronef_id)` — met à jour uniquement `aeronef_id`, ne touche à aucune ligne de `maintenance_dossiers`/`maintenance_operations` (celles-ci référencent l'équipement par `entite_id`, indépendant de l'aéronef courant).

**Validation :**
- [ ] Après transfert, l'historique des dossiers et opérations de l'équipement reste identique
- [ ] La fiche du nouvel aéronef affiche l'équipement transféré avec son potentiel inchangé
- [ ] La fiche de l'ancien aéronef ne l'affiche plus

---

## Phase 3 — Calcul du potentiel

### Étape 3.1 — Bibliothèque `Maintenance_potentiel`

**Objectif :** Centraliser le calcul et la mise à jour du potentiel, sur le modèle de `Formation_progression` (PRD EF5).

**Fichier :** `application/libraries/Maintenance_potentiel.php`

Méthodes :
- `calculer_etat($dossier)` → `'a_jour' | 'echeance_proche' | 'depasse'`, à partir de `echeance_courante`/`heures_restantes_courant` du dossier et du seuil global (30 jours par défaut)
- `appliquer_operation($operation_id)` — met à jour `maintenance_dossiers.echeance_courante` et `heures_restantes_courant` à partir des champs saisis sur l'opération (`nouvelle_echeance`, `horametre_releve` + règle du programme)
- `etat_pire_cas($aeronef_id)` — pour la vue de synthèse flotte (pire état parmi aéronef + ses équipements)
- `mise_a_jour_manuelle($dossier_id, $data, $user)` — corrige le potentiel hors opération, journalise dans les logs avec le marqueur `MAINTENANCE`

**Validation :**
- [ ] Fichier créé, syntaxe valide
- [ ] `appliquer_operation()` met à jour `echeance_courante` et/ou `heures_restantes_courant` correctement pour les 3 combinaisons de règle de butée (date seule, heures seules, les deux)
- [ ] `calculer_etat()` retourne `echeance_proche` uniquement si l'échéance est à moins de 30 jours (valeur par défaut, configurable)
- [ ] `mise_a_jour_manuelle()` écrit une ligne de log contenant le marqueur `MAINTENANCE`
- [ ] Tests unitaires couvrant les cas limites (échéance dépassée de 1 jour, exactement au seuil, potentiel négatif)

---

## Phase 4 — Programmes d'entretien : parsing et versioning

### Étape 4.1 — Parsing du markdown structuré

**Objectif :** Extraire la structure à trois niveaux (programme/section/tâche) d'un programme d'entretien déposé en markdown (PRD EF2), sur le modèle exact de `Formation_markdown_parser`, sans le réutiliser (décision actée, cf. Résumé).

**Fichier :** `application/libraries/Maintenance_markdown_parser.php` — classe dédiée, indépendante de `Formation_markdown_parser`.

Format attendu, désormais isomorphe au format Formation : H1 = titre du programme, H2 = section (`maintenance_programme_sections`), H3 = tâche (`maintenance_taches`), contenu = description de la tâche.

**Validation :**
- [ ] Parseur créé, syntaxe valide
- [ ] Import d'un fichier markdown de test produit les lignes `maintenance_programme_sections` et `maintenance_taches` attendues, dans l'ordre et avec le bon rattachement section → tâche
- [ ] Test avec un fichier markdown de test au format documenté (`doc/test-data/maintenance_visite_100h.md` ou équivalent)

---

### Étape 4.2 — Upload et versioning via le système documentaire

**Objectif :** Réutiliser `archived_documents`/`document_types` pour stocker et versionner le fichier source d'un programme d'entretien et les bulletins de service (PRD EF2, EF6).

- Création des `document_types` nécessaires : programme d'entretien (scope `machine`, `allow_versioning = 1`), bulletin de service (scope `machine`).
- À l'upload d'une nouvelle version d'un programme, ré-exécution du parsing (Étape 4.1) et mise à jour de `maintenance_programme_sections`/`maintenance_taches` (les sections/tâches obsolètes ne sont pas supprimées si déjà référencées par une `maintenance_realisation` existante — désactivation logique).

**Validation :**
- [ ] Nouveau programme créé via l'écran document existant, avec scope `machine`
- [ ] Nouvelle version d'un programme déclenche le re-parsing et met à jour les sections/tâches actives
- [ ] Une tâche déjà utilisée dans une réalisation reste consultable après une nouvelle version qui la supprime

---

## Phase 5 — Contrôleurs et vues

### Étape 5.1 — Équipements

**Fichier :** `application/controllers/maintenance_equipements.php` + vues `application/views/maintenance_equipements/`

CRUD équipement + action de transfert (Étape 2.2).

**Validation :**
- [ ] CRUD complet accessible et fonctionnel
- [ ] Formulaire de transfert avec sélecteur d'aéronef cible, confirmation explicite

---

### Étape 5.2 — Programmes d'entretien

**Fichier :** `application/controllers/maintenance_programmes.php` + vues `application/views/maintenance_programmes/`

Liste, création/édition (délègue l'upload au contrôleur documentaire existant), affichage des sections et de leurs tâches parsées.

**Validation :**
- [ ] Liste filtrée par section club/activité (cohérent avec `formation_programmes`)
- [ ] Détail d'un programme affiche ses sections et leurs tâches, dans l'ordre

---

### Étape 5.3 — Dossiers d'entretien

**Fichier :** `application/controllers/maintenance_dossiers.php` + vues `application/views/maintenance_dossiers/`

Ouverture (sélection entité + programme), suspension, clôture, abandon — mêmes transitions d'état que `formation_inscriptions`.

**Validation :**
- [ ] Ouverture d'un dossier depuis la fiche d'une entité ou depuis un programme
- [ ] Changements de statut (suspendre/clôturer/abandonner) journalisés avec date
- [ ] Historique des dossiers (y compris non ouverts) consultable depuis l'entité

---

### Étape 5.4 — Opérations de maintenance

**Fichier :** `application/controllers/maintenance_operations.php` + vue unique `application/views/maintenance_operations/form.php` (PRD EF4 : un seul écran pour les deux modes)

- Bloc "saisie directe" : cases à cocher par tâche + commentaire par tâche + commentaire global.
- Bouton de téléchargement du compte rendu papier sur le même écran, réutilisant le composant d'upload documentaire existant.
- À la validation : appel à `Maintenance_potentiel::appliquer_operation()`.

**Validation :**
- [ ] Une opération en saisie directe mémorise l'état de chaque tâche cochée
- [ ] Une opération avec compte rendu déposé affiche une miniature/lien vers le document depuis l'historique
- [ ] Dans les deux modes, le potentiel du dossier est mis à jour après validation
- [ ] Correction d'une opération existante possible selon droits, sans suppression silencieuse

---

### Étape 5.5 — Bulletins de service

**Fichier :** `application/controllers/maintenance_bulletins.php` + vues `application/views/maintenance_bulletins/`

Liste par entité maintenable, changement de statut (mecano/admin uniquement).

**Validation :**
- [ ] Liste des bulletins avec statut visible
- [ ] Changement de statut réservé à mecano/admin (autre rôle bloqué avec message explicite)

---

### Étape 5.6 — Vue de synthèse navigabilité et export PDF

**Fichier :** `application/controllers/maintenance_synthese.php` + vues `application/views/maintenance_synthese/`

- Vue par aéronef : état de chaque entité maintenable (à jour/proche/dépassé).
- Vue flotte filtrable par section : pire état par aéronef (`Maintenance_potentiel::etat_pire_cas()`).
- Export PDF de synthèse par aéronef (réutilisation TCPDF, déjà utilisé ailleurs dans GVV).

**Validation :**
- [ ] Codes couleur cohérents entre vue aéronef et vue flotte
- [ ] Export PDF généré et lisible, contient l'ensemble des entités et leur état
- [ ] Filtrage par section fonctionnel

---

### Étape 5.7 — Dashboard maintenance dédié

**Fichier :** `application/controllers/maintenance_dashboard.php` (ou intégration dans un contrôleur existant selon convention GVV) + vue dédiée

- Regroupe les cartes : équipements, programmes, dossiers, opérations, bulletins, synthèse.
- Activation des deux cartes existantes (`db_card_maintenance_prog`, `db_card_maintenance_ops`) sur le dashboard principal, pointant vers ce dashboard.

**Validation :**
- [ ] Dashboard maintenance accessible et affiche toutes les cartes du module
- [ ] Les deux cartes existantes ne sont plus en état "bientôt disponible" et pointent correctement
- [ ] Visibilité conditionnée à `is_mecano || is_admin` (cohérent avec l'existant)

---

## Phase 6 — Rôles et accès

### Étape 6.1 — Vérifications d'autorisation

**Objectif :** Appliquer la matrice de droits du PRD (EF8) sur l'ensemble des contrôleurs créés en Phase 5.

- Mecano/admin : écriture complète sur leur périmètre (section pour mecano, toutes sections pour admin).
- Responsable de section/trésorier : lecture seule (synthèse + historique).
- Pilote : lecture seule limitée à l'état de navigabilité, sans détail d'intervention.

**Validation :**
- [ ] Chaque contrôleur vérifie le rôle avant toute action d'écriture
- [ ] Un pilote ne peut pas ouvrir le détail d'une opération (redirection ou message explicite)
- [ ] Un responsable de section ne voit que sa section, un admin voit tout

---

## Phase 7 — Point d'ancrage alarmes et réservations (sans implémentation)

### Étape 7.1 — API interne d'exposition des échéances

**Objectif :** Permettre au futur mécanisme d'alarmes génériques de lire les échéances de maintenance sans dupliquer le calcul (PRD EF10).

**Fichier :** méthode publique sur `Maintenance_potentiel`, ex. `lister_echeances_actives($section_id = null)` retournant entité, type de butée, échéance/heures restantes, état.

**Validation :**
- [ ] Méthode retourne une structure exploitable indépendamment de l'UI (pas de HTML, pas de dépendance vue)
- [ ] Aucune modification du statut "Maintenance" des réservations (`doc/prds/aircraft_booking_prd.md`) — vérifié par non-régression des tests réservations existants

---

## Phase 8 — Fichiers de langue

### Étape 8.1 — Clés FR / EN / NL

**Fichiers :**
- `application/language/french/maintenance_lang.php`
- `application/language/english/maintenance_lang.php`
- `application/language/dutch/maintenance_lang.php`

Couvrant : équipements, programmes, tâches, dossiers (statuts), opérations, bulletins, synthèse, messages de confirmation/erreur.

**Validation :**
- [ ] Fichiers créés sans erreur de syntaxe
- [ ] Tous les `$this->lang->line(...)` utilisés ont une clé définie dans les trois langues (couvert par le test existant `LanguageCompletenessTest`)

---

## Phase 9 — Tests PHPUnit

### Étape 9.1 — Tests unitaires

**Fichiers :**
- `application/tests/unit/libraries/MaintenancePotentielTest.php`
- `application/tests/unit/libraries/MaintenanceMarkdownParserTest.php`

Cas de test : calcul d'état selon règle de butée (date/heures/les deux), seuil "échéance proche", mise à jour manuelle avec marqueur de log, parsing markdown (ordre, titres, cas limites).

---

### Étape 9.2 — Tests d'intégration

**Fichiers :**
- `application/tests/integration/MaintenanceEquipementModelTest.php`
- `application/tests/integration/MaintenanceDossierModelTest.php`
- `application/tests/integration/MaintenanceOperationModelTest.php`
- `application/tests/integration/MaintenanceBulletinModelTest.php`

Cas de test : CRUD, transfert d'équipement (historique préservé), dossiers multiples simultanés sur une même entité, opération en mode `compte_rendu` sans tâche cochée, changement de statut bulletin restreint par rôle.

---

### Étape 9.3 — Exécution de la suite complète

**Validation :**
- [ ] `source setenv-php7.sh && ./run-all-tests.sh` (et/ou `setenv-php8.sh`) passe sans régression
- [ ] Couverture des nouveaux fichiers ≥ 70 %

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
| 1 | Fondations base de données (migrations 155–160) | ⬜ Non démarré |
| 2 | Modèles | ⬜ Non démarré |
| 3 | Calcul du potentiel (`Maintenance_potentiel`) | ⬜ Non démarré |
| 4 | Parsing et versioning des programmes | ⬜ Non démarré |
| 5 | Contrôleurs et vues | ⬜ Non démarré |
| 6 | Rôles et accès | ⬜ Non démarré |
| 7 | Point d'ancrage alarmes/réservations | ⬜ Non démarré |
| 8 | Fichiers de langue FR/EN/NL | ⬜ Non démarré |
| 9 | Tests PHPUnit | ⬜ Non démarré |
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
