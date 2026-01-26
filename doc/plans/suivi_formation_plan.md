# Plan d'Implémentation - Suivi de Formation

**Référence PRD** : [doc/prds/suivi_formation_prd.md](../prds/suivi_formation_prd.md)
**Statut global** : 🟡 En cours (Phases 1, 2 et 3 complétées)
**Date de création** : 25 janvier 2026

---

## Vue d'ensemble

Implémentation d'un système complet de suivi de formation pour les clubs de planeur, incluant la gestion des programmes de formation, l'enregistrement des séances d'instruction (avec ou sans inscription formelle) et le suivi de progression des élèves.

**Nouvelle fonctionnalité clé** : Les séances peuvent être enregistrées pour des pilotes non inscrits à une formation (séances libres), permettant d'archiver les sujets abordés lors de vols de perfectionnement ou de remise à niveau.

### Principes d'implémentation

- Architecture metadata-driven (Gvvmetadata.php)
- Fonctionnalité activée via flag `gestion_formations`
- Tests PHPUnit pour chaque composant (cible: >70% couverture)
- Tests Playwright pour les workflows end-to-end
- Support multi-langue (français, anglais, néerlandais)
- Interface Bootstrap 5

---

## Todo List Globale

### Phase 1 : Infrastructure de Base ✅ 6/6
- [x] 1.1 - Ajouter le flag `gestion_formations` à la configuration
- [x] 1.2 - Créer les migrations de base de données
- [x] 1.3 - Créer les modèles de données
- [x] 1.4 - Définir les métadonnées dans Gvvmetadata.php
- [x] 1.5 - Tests PHPUnit : migrations et modèles
- [x] 1.6 - Middleware d'activation de la fonctionnalité

### Phase 2 : Programmes de Formation ✅ 8/8
- [x] 2.1 - Parser Markdown pour programmes de formation
- [x] 2.2 - Contrôleur de gestion des programmes
- [x] 2.3 - Vues d'administration des programmes
- [x] 2.4 - Import/export de fichiers Markdown
- [x] 2.5 - Gestion des versions de programmes
- [x] 2.6 - Fichiers de langue pour les programmes
- [x] 2.7 - Tests PHPUnit : parser et gestion des programmes
- [x] 2.8 - Tests Playwright : CRUD programmes

### Phase 3 : Inscriptions aux Formations ✅ 7/7
- [x] 3.1 - Contrôleur de gestion des inscriptions
- [x] 3.2 - Vues pour ouvrir/suspendre/clôturer formations
- [x] 3.3 - Gestion du cycle de vie des inscriptions
- [x] 3.4 - Filtrage et recherche d'inscriptions
- [x] 3.5 - Fichiers de langue pour les inscriptions
- [x] 3.6 - Tests PHPUnit : cycle de vie des inscriptions
- [x] 3.7 - Tests Playwright : workflow complet d'inscription

### Phase 4 : Séances de Formation ⏳ 0/9
- [ ] 4.1 - Contrôleur d'enregistrement des séances
- [ ] 4.2 - Support des séances avec et sans inscription
- [ ] 4.3 - Formulaire de saisie de séance (mode inscription/libre)
- [ ] 4.4 - Évaluation par sujet (-, A, R, Q)
- [ ] 4.5 - Gestion des conditions météo
- [ ] 4.6 - Historique des séances (avec distinction inscription/libre)
- [ ] 4.7 - Fichiers de langue pour les séances
- [ ] 4.8 - Tests PHPUnit : enregistrement et évaluation
- [ ] 4.9 - Tests Playwright : saisie de séance complète (avec/sans inscription)

### Phase 5 : Fiches de Progression ⏳ 0/7
- [ ] 5.1 - Calcul de la progression par élève
- [ ] 5.2 - Indicateur de progression (% sujets acquis)
- [ ] 5.3 - Vue arborescente leçons/sujets
- [ ] 5.4 - Export PDF des fiches
- [ ] 5.5 - Fichiers de langue pour les progressions
- [ ] 5.6 - Tests PHPUnit : calcul de progression
- [ ] 5.7 - Tests Playwright : affichage et export des fiches

### Phase 6 : Permissions et Sécurité ⏳ 0/6
- [ ] 6.1 - Définir les rôles et permissions
- [ ] 6.2 - Contrôle d'accès par section
- [ ] 6.3 - Visibilité des programmes (Toutes/Section)
- [ ] 6.4 - Restrictions instructeur/élève
- [ ] 6.5 - Tests PHPUnit : permissions
- [ ] 6.6 - Tests Playwright : accès selon rôles

### Phase 7 : Interface Utilisateur ⏳ 0/5
- [ ] 7.1 - Menu principal (conditionné par flag)
- [ ] 7.2 - Tableaux de bord instructeur
- [ ] 7.3 - Tableau de bord élève
- [ ] 7.4 - Tableaux de bord administrateur
- [ ] 7.5 - Tests Playwright : navigation complète

### Phase 8 : Finalisation ⏳ 0/5
- [ ] 8.1 - Documentation utilisateur
- [ ] 8.2 - Tests de régression complets
- [ ] 8.3 - Test de migration (création + rollback)
- [ ] 8.4 - Validation couverture de tests (>70%)
- [ ] 8.5 - Smoke tests Playwright complet

**Progression globale** : 21/53 tâches (40%)

---

## Phase 1 : Infrastructure de Base

**Statut** : ✅ Complétée
**Date de complétion** : 25 janvier 2026
**Objectif** : Mettre en place la structure de données et le système d'activation

### Résumé de l'implémentation

**Tables créées** (préfixe `suivi_` utilisé pour éviter conflit avec tables `formation_` existantes) :
- `suivi_programmes` - Programmes de formation
- `suivi_lecons` - Leçons d'un programme
- `suivi_sujets` - Sujets d'une leçon
- `suivi_inscriptions` - Inscriptions des pilotes aux programmes
- `suivi_seances` - Séances de formation (avec ou sans inscription)
- `suivi_evaluations` - Évaluations des sujets par séance

**Fichiers créés** :
- `application/migrations/063_add_formation_tables.php` - Migration des 6 tables
- `application/models/formation_programme_model.php` - Modèle des programmes
- `application/models/formation_lecon_model.php` - Modèle des leçons
- `application/models/formation_sujet_model.php` - Modèle des sujets
- `application/models/formation_inscription_model.php` - Modèle des inscriptions
- `application/models/formation_seance_model.php` - Modèle des séances
- `application/models/formation_evaluation_model.php` - Modèle des évaluations
- `application/libraries/Formation_access.php` - Contrôle d'accès par feature flag
- `application/tests/mysql/SuiviFormationMigrationTest.php` - 11 tests
- `application/tests/mysql/SuiviProgrammeModelTest.php` - 9 tests

**Notes techniques** :
- Le flag `gestion_formations` existait déjà dans `application/config/program.php` (ligne 182)
- **Références aux pilotes** : Utilisation de VARCHAR(25) pour `pilote_id`, `instructeur_id`, `instructeur_referent_id` avec FK vers `membres.mlogin`, conformément aux autres tables (`volsa`, `volsp`, `comptes`)
- **Références aux machines** : Utilisation de VARCHAR(10) pour `machine_id` avec FK vers `machinesp.mpimmat`
- Préfixe `suivi_` pour les tables (évite conflit avec tables `formation_` existantes)
- La version de migration a été mise à jour à 63 dans `application/config/migration.php`

### 1.1 - Flag de Configuration

**Fichiers à modifier** :
- `application/config/gvv_config.php` (ou table de configuration)

**Actions** :
1. Ajouter le flag `gestion_formations` (booléen, défaut: 0)
2. Documentation du flag
3. Interface d'activation/désactivation pour admin

**Test de validation** :
```bash
# Vérifier la présence du flag
grep -r "gestion_formations" application/config/
```

---

### 1.2 - Migrations de Base de Données

**Fichier** : `application/migrations/0XX_add_formation_tables.php`

**Tables à créer** :

#### Table `suivi_programmes`
```sql
CREATE TABLE suivi_programmes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  titre VARCHAR(255) NOT NULL,
  description TEXT,
  contenu_markdown LONGTEXT NOT NULL,
  section_id INT NULL,  -- NULL = "Toutes"
  version INT DEFAULT 1,
  statut ENUM('actif', 'archive') DEFAULT 'actif',
  date_creation DATETIME NOT NULL,
  date_modification DATETIME,
  FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE SET NULL,
  INDEX idx_section (section_id),
  INDEX idx_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
```

#### Table `suivi_lecons`
```sql
CREATE TABLE suivi_lecons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  programme_id INT NOT NULL,
  numero INT NOT NULL,
  titre VARCHAR(255) NOT NULL,
  description TEXT,
  ordre INT NOT NULL,
  FOREIGN KEY (programme_id) REFERENCES suivi_programmes(id) ON DELETE CASCADE,
  INDEX idx_programme (programme_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
```

#### Table `suivi_sujets`
```sql
CREATE TABLE suivi_sujets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lecon_id INT NOT NULL,
  numero VARCHAR(20) NOT NULL,
  titre VARCHAR(255) NOT NULL,
  description TEXT,
  objectifs TEXT,
  ordre INT NOT NULL,
  FOREIGN KEY (lecon_id) REFERENCES suivi_lecons(id) ON DELETE CASCADE,
  INDEX idx_lecon (lecon_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
```

#### Table `suivi_inscriptions`
```sql
CREATE TABLE suivi_inscriptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pilote_id VARCHAR(25) NOT NULL COMMENT 'Référence vers membres.mlogin',
  programme_id INT NOT NULL,
  version_programme INT NOT NULL,
  instructeur_referent_id VARCHAR(25) NULL COMMENT 'Référence vers membres.mlogin',
  statut ENUM('ouverte', 'suspendue', 'cloturee', 'abandonnee') DEFAULT 'ouverte',
  date_ouverture DATE NOT NULL,
  date_suspension DATE NULL,
  motif_suspension TEXT NULL,
  date_cloture DATE NULL,
  motif_cloture TEXT NULL,
  commentaires TEXT,
  FOREIGN KEY (pilote_id) REFERENCES membres(mlogin) ON DELETE CASCADE,
  FOREIGN KEY (programme_id) REFERENCES suivi_programmes(id) ON DELETE RESTRICT,
  FOREIGN KEY (instructeur_referent_id) REFERENCES membres(mlogin) ON DELETE SET NULL,
  INDEX idx_pilote (pilote_id),
  INDEX idx_programme (programme_id),
  INDEX idx_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
```

**Note** : Comme les tables `volsa`, `volsp`, `comptes`, les références aux pilotes utilisent VARCHAR(25) correspondant à `membres.mlogin`.

#### Table `suivi_seances`
```sql
CREATE TABLE suivi_seances (
  id INT AUTO_INCREMENT PRIMARY KEY,
  inscription_id INT NULL,  -- NULL = séance libre (sans inscription)
  pilote_id VARCHAR(25) NOT NULL COMMENT 'Référence vers membres.mlogin',
  programme_id INT NOT NULL,  -- Programme de référence
  date_seance DATE NOT NULL,
  instructeur_id VARCHAR(25) NOT NULL COMMENT 'Référence vers membres.mlogin',
  machine_id VARCHAR(10) NOT NULL COMMENT 'Référence vers machinesp.mpimmat',
  duree TIME NOT NULL,  -- HH:MM:SS
  nb_atterrissages INT NOT NULL,
  meteo TEXT,  -- JSON array de conditions
  commentaires TEXT,
  prochaines_lecons VARCHAR(255),
  FOREIGN KEY (inscription_id) REFERENCES suivi_inscriptions(id) ON DELETE CASCADE,
  FOREIGN KEY (pilote_id) REFERENCES membres(mlogin) ON DELETE CASCADE,
  FOREIGN KEY (programme_id) REFERENCES suivi_programmes(id) ON DELETE RESTRICT,
  FOREIGN KEY (instructeur_id) REFERENCES membres(mlogin) ON DELETE RESTRICT,
  FOREIGN KEY (machine_id) REFERENCES machinesp(mpimmat) ON DELETE RESTRICT,
  INDEX idx_inscription (inscription_id),
  INDEX idx_pilote (pilote_id),
  INDEX idx_programme (programme_id),
  INDEX idx_date (date_seance),
  INDEX idx_instructeur (instructeur_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
```

**Note importante** : 
- `inscription_id` NULL = séance libre (pilote non inscrit ou vol de perfectionnement)
- `inscription_id` renseigné = séance liée à une formation structurée
- `pilote_id` et `programme_id` sont toujours obligatoires

#### Table `suivi_evaluations`
```sql
CREATE TABLE suivi_evaluations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  seance_id INT NOT NULL,
  sujet_id INT NOT NULL,
  niveau ENUM('-', 'A', 'R', 'Q') NOT NULL DEFAULT '-',
  commentaire TEXT,
  FOREIGN KEY (seance_id) REFERENCES suivi_seances(id) ON DELETE CASCADE,
  FOREIGN KEY (sujet_id) REFERENCES suivi_sujets(id) ON DELETE CASCADE,
  UNIQUE KEY unique_seance_sujet (seance_id, sujet_id),
  INDEX idx_seance (seance_id),
  INDEX idx_sujet (sujet_id),
  INDEX idx_niveau (niveau)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
```

**Actions migration** :
1. Créer le fichier de migration
2. Mettre à jour `application/config/migration.php` avec le nouveau numéro
3. Tester l'exécution de la migration
4. Tester le rollback

**Tests** :
```php
// application/tests/mysql/FormationMigrationTest.php
class FormationMigrationTest extends TestCase {
    public function testMigrationCreatesAllTables() {
        // Vérifier que toutes les tables existent
    }
    
    public function testMigrationRollback() {
        // Vérifier que le rollback supprime les tables
    }
}
```

---

### 1.3 - Modèles de Données

**Fichiers à créer** :

#### `application/models/Formation_programme_model.php`
```php
class Formation_programme_model extends CI_Model {
    protected $table = 'formation_programmes';
    
    public function select_page($attrs, $filters = array()) {
        // Implémentation standard avec jointures
    }
    
    public function get_by_id($id) {}
    public function create($data) {}
    public function update($id, $data) {}
    public function delete($id) {}
    public function get_by_section($section_id) {}
    public function get_visibles($section_id = null) {} // "Toutes" + section
}
```

#### `application/models/Formation_lecon_model.php`
```php
class Formation_lecon_model extends CI_Model {
    protected $table = 'formation_lecons';
    
    public function get_by_programme($programme_id) {}
    public function create_batch($lecons) {} // Pour import Markdown
    public function delete_by_programme($programme_id) {}
}
```

#### `application/models/Formation_sujet_model.php`
```php
class Formation_sujet_model extends CI_Model {
    protected $table = 'formation_sujets';
    
    public function get_by_lecon($lecon_id) {}
    public function get_by_programme($programme_id) {} // Tous sujets avec jointure
    public function create_batch($sujets) {}
}
```

#### `application/models/Formation_inscription_model.php`
```php
class Formation_inscription_model extends CI_Model {
    protected $table = 'formation_inscriptions';
    
    public function get_by_pilote($pilote_id, $statut = null) {}
    public function get_ouvertes($pilote_id) {}
    public function ouvrir($data) {}
    public function suspendre($id, $motif) {}
    public function reactiver($id) {}
    public function cloturer($id, $type, $motif = null) {}
}
```

#### `application/models/Formation_seance_model.php`
```php
class Formation_seance_model extends CI_Model {
    protected $table = 'formation_seances';
    
    public function get_by_inscription($inscription_id) {}
    public function get_by_pilote($pilote_id, $filters = array()) {} // Toutes séances (avec/sans inscription)
    public function get_libres_by_pilote($pilote_id) {} // Séances libres uniquement
    public function get_by_instructeur($instructeur_id, $filters = array()) {}
    public function create_with_evaluations($seance_data, $evaluations) {}
    public function update_with_evaluations($id, $seance_data, $evaluations) {}
    public function is_seance_libre($seance_id) {} // Vérifie si inscription_id est NULL
}
```

#### `application/models/Formation_evaluation_model.php`
```php
class Formation_evaluation_model extends CI_Model {
    protected $table = 'formation_evaluations';
    
    public function get_by_seance($seance_id) {}
    public function get_by_sujet($sujet_id) {} // Historique complet
    public function get_dernier_niveau_par_sujet($inscription_id) {} // Pour progression
    public function save_batch($seance_id, $evaluations) {}
}
```

**Tests** :
```php
// application/tests/unit/models/FormationProgrammeModelTest.php
// application/tests/unit/models/FormationInscriptionModelTest.php
// etc.
```

---

### 1.4 - Métadonnées Gvvmetadata.php

**Fichier** : `application/libraries/Gvvmetadata.php`

**Définitions à ajouter** :

```php
// formation_programmes
$this->field['formation_programmes']['code']['Type'] = 'varchar';
$this->field['formation_programmes']['code']['Mandatory'] = TRUE;
$this->field['formation_programmes']['titre']['Type'] = 'varchar';
$this->field['formation_programmes']['titre']['Mandatory'] = TRUE;
$this->field['formation_programmes']['description']['Type'] = 'text';
$this->field['formation_programmes']['contenu_markdown']['Type'] = 'longtext';
$this->field['formation_programmes']['contenu_markdown']['Subtype'] = 'markdown';
$this->field['formation_programmes']['section_id']['Type'] = 'int';
$this->field['formation_programmes']['section_id']['Subtype'] = 'selector';
$this->field['formation_programmes']['section_id']['Selector'] = 'section_selector';
$this->field['formation_programmes']['version']['Type'] = 'int';
$this->field['formation_programmes']['statut']['Type'] = 'enum';
$this->field['formation_programmes']['statut']['Subtype'] = 'enumeration';
$this->field['formation_programmes']['statut']['Enumeration'] = array(
    'actif' => 'Actif',
    'archive' => 'Archivé'
);

// formation_inscriptions
$this->field['formation_inscriptions']['pilote_id']['Type'] = 'int';
$this->field['formation_inscriptions']['pilote_id']['Subtype'] = 'selector';
$this->field['formation_inscriptions']['pilote_id']['Selector'] = 'pilote_selector';
$this->field['formation_inscriptions']['pilote_id']['Mandatory'] = TRUE;
$this->field['formation_inscriptions']['programme_id']['Type'] = 'int';
$this->field['formation_inscriptions']['programme_id']['Subtype'] = 'selector';
$this->field['formation_inscriptions']['programme_id']['Selector'] = 'programme_formation_selector';
$this->field['formation_inscriptions']['programme_id']['Mandatory'] = TRUE;
$this->field['formation_inscriptions']['instructeur_referent_id']['Type'] = 'int';
$this->field['formation_inscriptions']['instructeur_referent_id']['Subtype'] = 'selector';
$this->field['formation_inscriptions']['instructeur_referent_id']['Selector'] = 'instructeur_selector';
$this->field['formation_inscriptions']['statut']['Type'] = 'enum';
$this->field['formation_inscriptions']['statut']['Subtype'] = 'enumeration';
$this->field['formation_inscriptions']['statut']['Enumeration'] = array(
    'ouverte' => 'Ouverte',
    'suspendue' => 'Suspendue',
    'cloturee' => 'Clôturée',
    'abandonnee' => 'Abandonnée'
);
$this->field['formation_inscriptions']['date_ouverture']['Type'] = 'date';
$this->field['formation_inscriptions']['date_ouverture']['Mandatory'] = TRUE;

// formation_seances
$this->field['formation_seances']['inscription_id']['Type'] = 'int';
$this->field['formation_seances']['inscription_id']['Subtype'] = 'selector';
$this->field['formation_seances']['inscription_id']['Selector'] = 'inscription_formation_selector';
$this->field['formation_seances']['inscription_id']['Mandatory'] = FALSE;  // NULL = séance libre
$this->field['formation_seances']['pilote_id']['Type'] = 'int';
$this->field['formation_seances']['pilote_id']['Subtype'] = 'selector';
$this->field['formation_seances']['pilote_id']['Selector'] = 'pilote_selector';
$this->field['formation_seances']['pilote_id']['Mandatory'] = TRUE;
$this->field['formation_seances']['programme_id']['Type'] = 'int';
$this->field['formation_seances']['programme_id']['Subtype'] = 'selector';
$this->field['formation_seances']['programme_id']['Selector'] = 'programme_formation_selector';
$this->field['formation_seances']['programme_id']['Mandatory'] = TRUE;
$this->field['formation_seances']['date_seance']['Type'] = 'date';
$this->field['formation_seances']['date_seance']['Mandatory'] = TRUE;
$this->field['formation_seances']['duree']['Type'] = 'time';
$this->field['formation_seances']['duree']['Subtype'] = 'duration';
$this->field['formation_seances']['nb_atterrissages']['Type'] = 'int';
$this->field['formation_seances']['nb_atterrissages']['Mandatory'] = TRUE;
$this->field['formation_seances']['meteo']['Type'] = 'text';
$this->field['formation_seances']['meteo']['Subtype'] = 'json_array';

// formation_evaluations
$this->field['formation_evaluations']['niveau']['Type'] = 'enum';
$this->field['formation_evaluations']['niveau']['Subtype'] = 'enumeration';
$this->field['formation_evaluations']['niveau']['Enumeration'] = array(
    '-' => 'Non abordé',
    'A' => 'Abordé',
    'R' => 'À revoir',
    'Q' => 'Acquis'
);
```

**Sélecteurs à implémenter** :
```php
public function programme_formation_selector($current_value = null) {
    // Retourne les programmes visibles selon section
}

public function instructeur_selector($current_value = null) {
    // Retourne les pilotes ayant le rôle instructeur
}

public function inscription_formation_selector($current_value = null, $pilote_id = null) {
    // Retourne les inscriptions ouvertes d'un pilote
    // Utilisé dans le formulaire de séance pour choisir l'inscription
}
```

**Tests** :
```php
// application/tests/integration/FormationMetadataTest.php
class FormationMetadataTest extends TestCase {
    public function testAllFormationFieldsHaveMetadata() {
        // Vérifier que tous les champs ont des métadonnées
    }
}
```

---

### 1.5 - Middleware d'Activation

**Fichier** : `application/libraries/Formation_access.php`

```php
class Formation_access {
    protected $CI;
    
    public function __construct() {
        $this->CI =& get_instance();
    }
    
    public function is_enabled() {
        return (bool) $this->CI->config->item('gestion_formations');
    }
    
    public function check_access_or_403() {
        if (!$this->is_enabled()) {
            show_error('Formation feature is not enabled', 403);
        }
    }
}
```

**Intégration dans les contrôleurs** :
```php
class Formation_programmes extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('formation_access');
        $this->formation_access->check_access_or_403();
    }
}
```

**Tests** :
```php
// application/tests/unit/FormationAccessTest.php
class FormationAccessTest extends TestCase {
    public function testAccessDeniedWhenDisabled() {}
    public function testAccessAllowedWhenEnabled() {}
}
```

---

## Phase 2 : Programmes de Formation

**Statut** : ✅ Complétée
**Date de complétion** : 26 janvier 2026
**Objectif** : Parser Markdown, CRUD programmes, gestion versions

### Résumé de l'implémentation

**Fichiers créés/modifiés** :
- `application/controllers/programmes.php` - Contrôleur CRUD (index, create, store, edit, update, view, delete, export, import_from_markdown, update_structure)
- `application/views/programmes/index.php` - Liste des programmes avec DataTable
- `application/views/programmes/form.php` - Formulaire création/édition avec onglets (manuel/import Markdown)
- `application/views/programmes/view.php` - Détail programme avec accordéon leçons/sujets
- `application/libraries/Formation_markdown_parser.php` - Parser Markdown → structure leçons/sujets
- `playwright/tests/formation/programmes.spec.js` - 8 tests e2e CRUD complet

**Bugs corrigés lors de la phase 2.8 (tests Playwright)** :
- `programmes.php:store()` : champ `code` manquant (NOT NULL en BDD), `actif => 1` remplacé par `statut => 'actif'`, ajout de `contenu_markdown`
- `programmes.php:delete()` : appel `delete($id)` corrigé en `delete(array('id' => $id))` (signature Common_Model), vérification via `affected_rows()` au lieu du retour void
- `formation_programme_model.php:get_all()` : filtre `actif = 1` corrigé en `statut = 'actif'` (colonne réelle en BDD)
- `formation_inscription_model.php` : ajout méthode manquante `get_by_programme()` (appelée par le contrôleur de suppression)

**Note sur les noms de fichiers** : Le contrôleur est `programmes.php` (pas `Formation_programmes.php`) et les vues sont dans `application/views/programmes/` (pas `application/views/formation/programmes/`).

### 2.1 - Parser Markdown

**Fichier** : `application/libraries/Formation_markdown_parser.php`

```php
class Formation_markdown_parser {
    
    /**
     * Parse un contenu Markdown et extrait la structure
     * @param string $markdown
     * @return array ['titre' => '', 'lecons' => [...], 'erreurs' => [...]]
     */
    public function parse($markdown) {
        $structure = [
            'titre' => '',
            'lecons' => [],
            'erreurs' => []
        ];
        
        // Extraction du titre (# niveau 1)
        // Extraction des leçons (## niveau 2)
        // Extraction des sujets (### niveau 3)
        // Validation de la structure
        
        return $structure;
    }
    
    /**
     * Valide la structure d'un programme
     */
    public function validate($structure) {
        $errors = [];
        // Vérifier titre unique
        // Vérifier que chaque sujet a une leçon parente
        return $errors;
    }
    
    /**
     * Génère le Markdown depuis une structure
     */
    public function generate($structure) {
        // Pour l'export
    }
}
```

**Tests** :
```php
// application/tests/unit/FormationMarkdownParserTest.php
class FormationMarkdownParserTest extends TestCase {
    public function testParseValidMarkdown() {}
    public function testDetectMissingTitle() {}
    public function testDetectOrphanSubject() {}
    public function testGenerateMarkdown() {}
}
```

---

### 2.2 - Contrôleur Programmes

**Fichier** : `application/controllers/programmes.php`

```php
class Programmes extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->library('formation_access');
        $this->formation_access->check_access_or_403();
        $this->load->model('formation_programme_model');
        $this->load->library('formation_markdown_parser');
    }
    
    public function index() {
        // Liste des programmes
    }
    
    public function create() {
        // Formulaire de création
    }
    
    public function edit($id) {
        // Édition d'un programme
    }
    
    public function import_markdown() {
        // Upload et import de fichier .md
    }
    
    public function export_markdown($id) {
        // Export d'un programme en .md
    }
    
    public function preview() {
        // Prévisualisation de la structure (AJAX)
    }
    
    public function save() {
        // Sauvegarde avec parsing
        // Incrémentation de version si modifications structurelles
    }
    
    public function archive($id) {
        // Archiver un programme
    }
}
```

---

### 2.3 - Vues Programmes

**Fichiers créés** :
- `application/views/programmes/index.php` - Liste avec DataTable
- `application/views/programmes/form.php` - Formulaire création/édition (onglets manuel/import)
- `application/views/programmes/view.php` - Détail avec accordéon leçons/sujets

**Composants UI** :
- Tableau avec filtres (section, statut)
- Éditeur Markdown avec coloration syntaxique
- Panneau de prévisualisation en temps réel
- Indicateurs d'erreurs de parsing

---

### 2.4 à 2.8 - Import/Export, Versions, Langues, Tests

**Actions** :
- Import : validation fichier .md, parsing, prévisualisation
- Export : génération fichier téléchargeable
- Versions : détection changements, incrémentation auto
- Langues : traductions français/anglais/néerlandais
- Tests PHPUnit : couverture parser et CRUD
- Tests Playwright : workflow complet import→edit→export

---

## Phase 3 : Inscriptions aux Formations

**Statut** : 🔴 Non commencé  
**Durée estimée** : 2-3 jours  
**Objectif** : Cycle de vie complet des inscriptions

### 3.1 - Contrôleur Inscriptions

**Fichier** : `application/controllers/formation_inscriptions.php`

```php
class Formation_inscriptions extends CI_Controller {
    
    public function index() {
        // Liste des inscriptions avec filtres
    }
    
    public function ouvrir() {
        // Formulaire ouverture nouvelle formation
    }
    
    public function suspendre($id) {
        // Dialogue suspension avec motif
    }
    
    public function reactiver($id) {
        // Réactivation formation suspendue
    }
    
    public function cloturer($id) {
        // Dialogue clôture (succès ou abandon)
    }
    
    public function detail($id) {
        // Détail inscription avec historique
    }
}
```

### 3.2 à 3.7 - Vues, Cycle de Vie, Tests

**Vues** :
- Liste avec indicateurs de statut (couleurs)
- Formulaire d'ouverture
- Dialogues suspension/réactivation/clôture
- Détail avec timeline

**Tests** :

1. **PHPUnit** : `application/tests/integration/FormationInscriptionIntegrationTest.php`
   - `test_ouvrir_inscription_creates_new_inscription()` - Création inscription
   - `test_cannot_open_duplicate_inscription()` - Validation doublons
   - `test_suspendre_inscription_changes_status()` - Suspension
   - `test_reactiver_inscription_restores_open_status()` - Réactivation
   - `test_cloturer_inscription_with_success()` - Clôture succès
   - `test_cloturer_inscription_with_abandon()` - Clôture abandon
   - `test_complete_inscription_lifecycle()` - Workflow complet
   - `test_get_all_with_filters()` - Filtres
   - `test_calculate_progression_returns_structure()` - Calcul progression

2. **Playwright** : `playwright/tests/formation/inscriptions.spec.js`
   - Step 1: Access inscriptions list page
   - Step 2: Verify active programme exists
   - Step 3: Open new inscription
   - Step 4: View inscription details
   - Step 5: Suspend inscription
   - Step 6: Reactivate inscription
   - Step 7: Close inscription (success)
   - Step 8: List closed inscription
   - Step 9: Complete workflow validation

**Commandes de test** :
```bash
# PHPUnit
source setenv.sh
phpunit --bootstrap application/tests/integration_bootstrap.php \
  application/tests/integration/FormationInscriptionIntegrationTest.php

# Playwright
cd playwright
npx playwright test tests/formation/inscriptions.spec.js --reporter=line
```

---

## Phase 4 : Séances de Formation

**Statut** : 🔴 Non commencé  
**Durée estimée** : 4-5 jours  
**Objectif** : Enregistrement séances avec ou sans inscription, évaluations

### 4.1 - Contrôleur Séances

**Fichier** : `application/controllers/Formation_seances.php`

```php
class Formation_seances extends CI_Controller {
    
    public function index() {
        // Liste des séances avec distinction inscription/libre
    }
    
    public function create($inscription_id = null, $pilote_id = null) {
        // Formulaire nouvelle séance
        // Mode 1 : avec inscription (inscription_id fourni)
        // Mode 2 : séance libre (pilote_id fourni, pas d'inscription)
        // Chargement dynamique des sujets du programme
    }
    
    public function edit($id) {
        // Modification séance
    }
    
    public function save() {
        // Sauvegarde avec evaluations
        // Validation : inscription_id OU (pilote_id + programme_id)
    }
    
    public function get_sujets_by_lecon() {
        // AJAX pour charger les sujets d'une leçon
    }
    
    public function get_inscriptions_pilote() {
        // AJAX : retourne les inscriptions ouvertes d'un pilote
    }
}
```

### 4.2 - Support des Séances avec et sans Inscription

**Logique métier** :
- **Séance avec inscription** : `inscription_id` NOT NULL
  - Le programme est celui de l'inscription
  - Contribue à la fiche de progression officielle
  - Validations : inscription doit être ouverte
  
- **Séance libre** : `inscription_id` IS NULL
  - L'instructeur choisit le pilote et le programme
  - Sert d'archivage des sujets abordés
  - Ne génère pas de fiche de progression
  - Utile pour : perfectionnement, remise à niveau, découverte

**Validation dans le contrôleur** :
```php
if (empty($data['inscription_id'])) {
    // Séance libre : pilote_id et programme_id obligatoires
    if (empty($data['pilote_id']) || empty($data['programme_id'])) {
        $this->form_validation->set_message('seance_libre', 'Pilote et programme requis pour séance libre');
        return FALSE;
    }
} else {
    // Séance avec inscription : vérifier que l'inscription est ouverte
    $inscription = $this->formation_inscription_model->get_by_id($data['inscription_id']);
    if ($inscription['statut'] !== 'ouverte') {
        $this->form_validation->set_message('inscription_statut', 'L\'inscription doit être ouverte');
        return FALSE;
    }
}
```

### 4.3 - Formulaire de Saisie de Séance (Mode Inscription/Libre)
    public function get_sujets_by_lecon() {
        // AJAX pour charger les sujets d'une leçon
    }
}
```

### 4.3 - Formulaire de Saisie de Séance (Mode Inscription/Libre)

**Vue** : `application/views/formation/seances/edit.php`

**Sélection du mode** :
1. **Checkbox "Séance libre"** : 
   - Par défaut non cochée (séance avec inscription)
   - Si cochée, bascule en mode séance libre

2. **Mode avec inscription** (checkbox non cochée) :
   - Sélecteur de pilote
   - Liste déroulante des inscriptions ouvertes du pilote
   - Programme automatiquement défini depuis l'inscription
   - Message : "Séance liée à la formation [Nom du programme]"

3. **Mode séance libre** (checkbox cochée) :
   - Sélecteur de pilote
   - Sélecteur de programme (tous les programmes actifs)
   - Message d'info : "Cette séance sera archivée mais ne contribuera pas à une fiche de progression"

**Sections du formulaire** :
1. Choix du mode (avec/sans inscription)
2. Informations générales (date, pilote, inscription/programme, machine, durée, atterrissages)
3. Conditions météo (sélection multiple)
4. Évaluation par leçon (sélection leçon → affichage sujets)
5. Sélecteurs niveau (-, A, R, Q) pour chaque sujet
6. Commentaires généraux
7. Prochaines leçons recommandées

**JavaScript** :
- Toggle entre mode inscription/libre
- Chargement dynamique des inscriptions selon pilote sélectionné
- Chargement dynamique des sujets selon leçon sélectionnée
- Validation côté client

### 4.4 - Évaluation par Sujet

**Identique pour les deux types de séances** :
- Même formulaire d'évaluation
- Mêmes niveaux (-, A, R, Q)
- Différence : séances libres ne contribuent pas à la fiche de progression

### 4.5 - Gestion des Conditions Météo

(Inchangé)

### 4.6 - Historique des Séances (avec Distinction Inscription/Libre)

**Vue** : `application/views/formation/seances/index.php`

**Tableau** :
- Colonnes : Date, Pilote, Type, Programme, Durée, Atterrissages, Instructeur
- Colonne "Type" avec badge :
  - **Badge bleu "Formation"** : séance avec inscription
  - **Badge gris "Libre"** : séance libre
- Filtres :
  - Par pilote
  - Par type (inscription/libre/toutes)
  - Par programme
  - Par période
  - Par instructeur

**Actions** :
- Voir détail
- Modifier (si autorisé)

### 4.7 - Fichiers de Langue pour les Séances

**Traductions à ajouter** :
```php
// french/formation_lang.php
$lang['formation_seance_libre'] = 'Séance libre (sans inscription)';
$lang['formation_seance_inscription'] = 'Séance liée à une formation';
$lang['formation_seance_libre_info'] = 'Cette séance sera archivée mais ne contribuera pas à une fiche de progression';
$lang['formation_type_formation'] = 'Formation';
$lang['formation_type_libre'] = 'Libre';
```

### 4.8 - Tests PHPUnit : Enregistrement et Évaluation

**Fichiers de test** :
```php
// application/tests/unit/FormationSeanceTest.php
class FormationSeanceTest extends TestCase {
    public function testCreateSeanceAvecInscription() {}
    public function testCreateSeanceLibre() {}
    public function testSeanceLibreRequiersPiloteEtProgramme() {}
    public function testSeanceInscriptionRequiertInscriptionOuverte() {}
}
```

### 4.9 - Tests Playwright : Saisie de Séance Complète (avec/sans Inscription)

**Fichier** : `playwright/tests/formation/seances.spec.ts`

```typescript
test('Créer une séance avec inscription', async ({ page }) => {
    // Sélectionner élève avec inscription ouverte
    // Sélectionner l'inscription
    // Remplir le formulaire
    // Vérifier sauvegarde
});

test('Créer une séance libre', async ({ page }) => {
    // Cocher "Séance libre"
    // Sélectionner pilote
    // Sélectionner programme
    // Vérifier message d'info
    // Remplir et sauvegarder
    // Vérifier badge "Libre" dans l'historique
});
```

---

## Phase 5 : Fiches de Progression

**Statut** : 🔴 Non commencé  
**Durée estimée** : 3-4 jours  
**Objectif** : Calcul et affichage progression, export PDF

### 5.1 - Calcul de Progression

**Fichier** : `application/libraries/Formation_progression.php`

```php
class Formation_progression {
    
    /**
     * Calcule la progression d'un élève
     * @param int $inscription_id
     * @return array Structure complète avec stats
     */
    public function calculer($inscription_id) {
        // Charger tous les sujets du programme
        // Pour chaque sujet, récupérer dernière évaluation
        // Calculer % sujets acquis
        // Construire arborescence leçons/sujets avec stats
        
        return [
            'programme' => [...],
            'eleve' => [...],
            'stats' => [
                'nb_seances' => 0,
                'heures_totales' => 0,
                'atterrissages_totaux' => 0,
                'nb_sujets_total' => 0,
                'nb_sujets_acquis' => 0,
                'pourcentage_acquis' => 0
            ],
            'lecons' => [
                [
                    'titre' => '',
                    'sujets' => [
                        [
                            'titre' => '',
                            'nb_seances' => 0,
                            'dernier_niveau' => 'Q',
                            'date_derniere_eval' => '2025-01-15',
                            'historique' => [...]
                        ]
                    ]
                ]
            ]
        ];
    }
}
```

**Tests** :
```php
// application/tests/unit/FormationProgressionTest.php
class FormationProgressionTest extends TestCase {
    public function testCalculPourcentageAcquis() {
        // 10 sujets, 4 acquis = 40%
    }
    
    public function testDernierNiveauParSujet() {}
    public function testHistoriqueChronologique() {}
}
```

---

### 5.2 - Indicateur de Progression

**Composant** : Barre de progression colorée

**HTML/CSS** :
```html
<div class="progression-header">
    <div class="progression-label">
        <strong>45%</strong> des sujets acquis (9/20)
    </div>
    <div class="progress">
        <div class="progress-bar bg-orange" style="width: 45%"></div>
    </div>
</div>
```

**Classes CSS** :
- `bg-red` : 0-25%
- `bg-orange` : 26-50%
- `bg-yellow` : 51-75%
- `bg-green` : 76-100%

---

### 5.3 - Vue Arborescente

**Vue** : `application/views/formation/progression/fiche.php`

**Structure** :
- En-tête avec progression globale
- Accordéon Bootstrap pour leçons
- Liste des sujets avec badges de niveau
- Indicateurs visuels (couleurs selon niveau)
- Liens vers détail du sujet

---

### 5.4 - Export PDF

**Fichier** : `application/controllers/Formation_progression.php`

```php
public function export_pdf($inscription_id) {
    $this->load->library('formation_progression');
    $data = $this->formation_progression->calculer($inscription_id);
    
    $this->load->library('pdf');
    // Génération PDF avec TCPDF
    // Structure similaire à la fiche HTML
}
```

---

### 5.5 à 5.7 - Langues, Tests

**Tests** :
- PHPUnit : calcul de progression, pourcentages
- Playwright : affichage fiche, export PDF, navigation détail sujet

---

## Phase 6 : Permissions et Sécurité

**Statut** : 🔴 Non commencé  
**Durée estimée** : 2 jours  
**Objectif** : Contrôle d'accès selon rôles et sections

### 6.1 - Définition des Rôles

**Table ou configuration** :
- `admin_formations` : administrateur formations
- `instructeur` : instructeur (flag existant sur pilotes)
- `pilote` : élève (accès restreint)

### 6.2 - Contrôle d'Accès par Section

**Library** : `application/libraries/Formation_permissions.php`

```php
class Formation_permissions {
    
    public function can_view_programme($programme_id, $user_section_id) {
        // Règle : "Toutes" ou section = user_section
    }
    
    public function can_edit_programme($programme_id, $user_role) {
        // Seuls les admins
    }
    
    public function can_view_inscription($inscription_id, $user_id, $user_role) {
        // Instructeur : ses élèves
        // Élève : lui-même
        // Admin : tous de sa section
    }
    
    public function can_edit_seance($seance_id, $user_id) {
        // Seul l'instructeur de la séance
    }
}
```

### 6.3 à 6.6 - Implémentation et Tests

**Actions** :
- Appliquer les vérifications dans chaque contrôleur
- Filtrer les listes selon permissions
- Tests PHPUnit : chaque règle de permission
- Tests Playwright : accès refusé selon rôle

---

## Phase 7 : Interface Utilisateur

**Statut** : 🔴 Non commencé  
**Durée estimée** : 2 jours  
**Objectif** : Menus, tableaux de bord, navigation

### 7.1 - Menu Principal

**Fichier** : Template de menu principal

**Ajouts** (si `gestion_formations` activé) :
```php
<?php if ($this->formation_access->is_enabled()): ?>
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
        Formation
    </a>
    <ul class="dropdown-menu">
        <?php if ($this->auth->has_role('admin_formations')): ?>
            <li><a href="/formation_programmes">Programmes</a></li>
            <li><a href="/formation_inscriptions">Inscriptions</a></li>
        <?php endif; ?>
        
        <?php if ($this->auth->has_role('instructeur')): ?>
            <li><a href="/formation_seances">Mes séances</a></li>
            <li><a href="/formation_eleves">Mes élèves</a></li>
        <?php endif; ?>
        
        <li><a href="/formation_progression/ma_progression">Ma progression</a></li>
    </ul>
</li>
<?php endif; ?>
```

---

### 7.2 - Tableau de Bord Instructeur

**Vue** : `application/views/formation/dashboard/instructeur.php`

**Widgets** :
- Liste de mes élèves en formation
- Séances récentes
- Élèves nécessitant attention (aucune séance depuis X jours)
- Raccourcis : nouvelle séance, nouvelle inscription

---

### 7.3 - Tableau de Bord Élève

**Vue** : `application/views/formation/dashboard/eleve.php`

**Widgets** :
- Carte "Ma formation"
- Barre de progression
- Prochaines leçons recommandées
- Dernières séances
- Lien vers fiche détaillée

---

### 7.4 - Tableau de Bord Admin

**Vue** : `application/views/formation/dashboard/admin.php`

**Widgets** :
- Statistiques globales (nb élèves, programmes actifs)
- Répartition par programme
- Activité récente
- Programmes à mettre à jour

---

### 7.5 - Tests Playwright Navigation

**Fichier** : `playwright/tests/formation/navigation.spec.ts`

```typescript
test('Navigation complète formations', async ({ page }) => {
    // Login instructeur
    // Accès menu Formation
    // Navigation vers chaque section
    // Vérification affichage des pages
});

test('Accès refusé sans flag activé', async ({ page }) => {
    // Désactiver flag
    // Tenter accès direct
    // Vérifier 403
});
```

---

## Phase 8 : Finalisation

**Statut** : 🔴 Non commencé  
**Durée estimée** : 2 jours  
**Objectif** : Documentation, tests finaux, validation

### 8.1 - Documentation Utilisateur

**Fichiers à créer** :
- `doc/user_guide/formation.md` - Guide utilisateur complet
- `doc/admin_guide/formation_setup.md` - Guide d'activation et configuration

**Contenu** :
- Activation du flag
- Création d'un premier programme
- Enregistrement de séances
- Lecture des fiches de progression
- FAQ

---

### 8.2 - Tests de Régression

**Script** : `./run-all-tests.sh --coverage`

**Validation** :
- Toutes les suites de tests passent
- Aucune régression sur fonctionnalités existantes
- Couverture >70%

---

### 8.3 - Test de Migration

**Script de test** : `bin/test_formation_migration.sh`

```bash
#!/bin/bash
source setenv.sh

echo "Test migration formations..."

# Sauvegarder l'état actuel
php7.4 run_migration.php current > /tmp/migration_before.txt

# Exécuter la migration
php7.4 run_migration.php latest

# Vérifier les tables
mysql -u gvv -p gvv -e "SHOW TABLES LIKE 'formation_%';"

# Rollback
php7.4 run_migration.php version_before_formation

# Vérifier suppression
mysql -u gvv -p gvv -e "SHOW TABLES LIKE 'formation_%';"

echo "✅ Migration testée avec succès"
```

---

### 8.4 - Validation Couverture

**Commande** :
```bash
./run-all-tests.sh --coverage
firefox build/coverage/index.html
```

**Vérifications** :
- Coverage globale >70%
- Tous les modèles couverts
- Parser Markdown couvert à 100%
- Calcul de progression couvert à 100%

---

### 8.5 - Smoke Tests Playwright

**Fichier** : `playwright/tests/formation/smoke.spec.ts`

```typescript
test('Smoke test complet formation', async ({ page }) => {
    // 1. Activer le flag
    await activerFlagFormations(page);
    
    // 2. Créer un programme
    await creerProgrammeFormation(page, programme_test);
    
    // 3. Ouvrir une formation pour un pilote
    await ouvrirFormation(page, pilote_test, programme_test);
    
    // 4. Enregistrer une séance avec inscription
    await enregistrerSeanceAvecInscription(page, seance_data);
    
    // 5. Enregistrer une séance libre (sans inscription)
    await enregistrerSeanceLibre(page, {
        pilote: pilote_test_2,
        programme: programme_test,
        commentaire: "Vol de perfectionnement"
    });
    
    // 6. Consulter la fiche de progression
    await consulterProgression(page, pilote_test);
    
    // 7. Vérifier le pourcentage affiché
    expect(await page.textContent('.progression-label')).toContain('%');
    
    // 8. Vérifier que la séance libre apparaît dans l'historique
    await consulterHistorique(page, pilote_test_2);
    expect(await page.textContent('.badge-libre')).toContain('Libre');
    
    // 9. Clôturer la formation
    await cloturerFormation(page, inscription_id);
});
```

---

## Tests PHPUnit par Composant

### Tests Unitaires

**Fichiers** :
- `application/tests/unit/FormationMarkdownParserTest.php`
- `application/tests/unit/FormationProgressionTest.php`
- `application/tests/unit/FormationAccessTest.php`
- `application/tests/unit/FormationPermissionsTest.php`

### Tests de Modèles

**Fichiers** :
- `application/tests/unit/models/FormationProgrammeModelTest.php`
- `application/tests/unit/models/FormationInscriptionModelTest.php`
- `application/tests/unit/models/FormationSeanceModelTest.php`
- `application/tests/unit/models/FormationEvaluationModelTest.php`

### Tests d'Intégration

**Fichiers** :
- `application/tests/integration/FormationMetadataTest.php`
- `application/tests/integration/FormationCycleVieTest.php`
- `application/tests/integration/FormationProgressionIntegrationTest.php`

### Tests MySQL

**Fichiers** :
- `application/tests/mysql/FormationMigrationTest.php`
- `application/tests/mysql/FormationCRUDTest.php`
- `application/tests/mysql/FormationRelationsTest.php`

---

## Tests Playwright par Feature

### Programmes de Formation

**Fichier** : `playwright/tests/formation/programmes.spec.ts`

```typescript
test.describe('Programmes de formation', () => {
    test('Créer un programme par import Markdown', async ({ page }) => {
        // Upload fichier .md
        // Vérifier prévisualisation
        // Sauvegarder
        // Vérifier dans la liste
    });
    
    test('Éditer un programme en ligne', async ({ page }) => {
        // Ouvrir éditeur
        // Modifier le Markdown
        // Vérifier prévisualisation temps réel
        // Sauvegarder
    });
    
    test('Exporter un programme', async ({ page }) => {
        // Cliquer export
        // Vérifier téléchargement .md
    });
});
```

### Inscriptions

**Fichier** : `playwright/tests/formation/inscriptions.spec.ts`

```typescript
test.describe('Cycle de vie inscriptions', () => {
    test('Ouvrir une formation', async ({ page }) => {});
    test('Suspendre une formation', async ({ page }) => {});
    test('Réactiver une formation', async ({ page }) => {});
    test('Clôturer une formation (succès)', async ({ page }) => {});
    test('Abandonner une formation', async ({ page }) => {});
});
```

### Séances

**Fichier** : `playwright/tests/formation/seances.spec.ts`

```typescript
test.describe('Enregistrement séances', () => {
    test('Créer une séance avec inscription', async ({ page }) => {
        // Sélectionner élève avec inscription ouverte
        // Sélectionner inscription
        // Remplir infos générales
        // Sélectionner météo
        // Évaluer des sujets
        // Ajouter commentaires
        // Sauvegarder
        // Vérifier badge "Formation" dans historique
    });
    
    test('Créer une séance libre (sans inscription)', async ({ page }) => {
        // Cocher "Séance libre"
        // Sélectionner pilote (non inscrit)
        // Sélectionner programme
        // Vérifier message d'info
        // Remplir infos et évaluations
        // Sauvegarder
        // Vérifier badge "Libre" dans historique
    });
    
    test('Modifier une séance existante', async ({ page }) => {});
    
    test('Filtrer historique par type (inscription/libre)', async ({ page }) => {
        // Créer séances mixtes
        // Appliquer filtre "Formation"
        // Vérifier résultats
        // Appliquer filtre "Libre"
        // Vérifier résultats
    });
});
```

### Fiches de Progression

**Fichier** : `playwright/tests/formation/progression.spec.ts`

```typescript
test.describe('Fiches de progression', () => {
    test('Afficher fiche avec pourcentage correct', async ({ page }) => {
        // Créer données de test
        // Consulter fiche
        // Vérifier pourcentage
        // Vérifier couleur de la jauge
    });
    
    test('Exporter fiche en PDF', async ({ page }) => {
        // Cliquer export PDF
        // Vérifier téléchargement
    });
    
    test('Détail d\'un sujet', async ({ page }) => {
        // Cliquer sur un sujet
        // Vérifier historique des évaluations
        // Vérifier commentaires
    });
});
```

### Permissions

**Fichier** : `playwright/tests/formation/permissions.spec.ts`

```typescript
test.describe('Permissions', () => {
    test('Admin voit tous les programmes', async ({ page }) => {});
    test('Instructeur voit seulement ses élèves', async ({ page }) => {});
    test('Élève voit seulement sa progression', async ({ page }) => {});
    test('Accès refusé à programme d\'autre section', async ({ page }) => {});
});
```

---

## Configuration des Tests

### PHPUnit

**Fichier** : `phpunit_formation.xml`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="application/tests/bootstrap.php"
         colors="true"
         convertErrorsToExceptions="true"
         convertNoticesToExceptions="true"
         convertWarningsToExceptions="true"
         stopOnFailure="false">
    <testsuites>
        <testsuite name="Formation">
            <directory>application/tests/unit/formation</directory>
            <directory>application/tests/integration/formation</directory>
            <directory>application/tests/mysql/formation</directory>
        </testsuite>
    </testsuites>
    <filter>
        <whitelist>
            <directory suffix=".php">application/models/formation_*</directory>
            <directory suffix=".php">application/libraries/Formation_*</directory>
            <directory suffix=".php">application/controllers/Formation_*</directory>
        </whitelist>
    </filter>
</phpunit>
```

**Commande** :
```bash
source setenv.sh
php7.4 vendor/bin/phpunit -c phpunit_formation.xml --coverage-html build/coverage/formation
```

---

### Playwright

**Fichier** : `playwright/playwright.config.ts`

```typescript
export default {
    testDir: './tests',
    projects: [
        {
            name: 'formation',
            testMatch: /formation\/.*.spec.ts/,
        }
    ]
};
```

**Commande** :
```bash
cd playwright
npx playwright test formation/ --reporter=line
```

---

## Checklist de Livraison

### Code
- [ ] Toutes les migrations créées et testées
- [ ] Tous les modèles implémentés avec CRUD complet
- [ ] Métadonnées définies pour tous les champs
- [ ] Parser Markdown fonctionnel et validé
- [ ] Tous les contrôleurs implémentés
- [ ] Toutes les vues créées avec Bootstrap 5
- [ ] Fichiers de langue complets (FR/EN/NL)

### Tests
- [ ] Tests PHPUnit : >70% couverture globale
- [ ] Tests PHPUnit : parser 100% couvert
- [ ] Tests PHPUnit : progression 100% couverte
- [ ] Tests Playwright : tous les workflows couverts
- [ ] Smoke test Playwright fonctionnel
- [ ] Test de migration (up + down) validé

### Documentation
- [ ] Guide utilisateur rédigé
- [ ] Guide admin rédigé
- [ ] Commentaires de code suffisants
- [ ] README mis à jour

### Sécurité et Permissions
- [ ] Flag d'activation fonctionnel
- [ ] Permissions par rôle implémentées
- [ ] Accès par section validé
- [ ] Tentatives d'accès non autorisé bloquées

### Validation Finale
- [ ] Tests de régression : OK
- [ ] Aucune régression sur fonctionnalités existantes
- [ ] Performance acceptable (temps de chargement <2s)
- [ ] Compatible avec PHP 7.4 et MySQL 5.x
- [ ] Interface responsive (mobile/tablette)

---

## Commandes Utiles

### Environnement
```bash
# TOUJOURS sourcer l'environnement
source setenv.sh

# Vérifier PHP
php -v  # Doit afficher PHP 7.4

# Valider syntaxe
php -l application/controllers/programmes.php
```

### Base de données
```bash
# Exécuter migration
php7.4 run_migration.php latest

# Rollback
php7.4 run_migration.php version <numero>

# Vérifier tables
mysql -u gvv -p gvv -e "SHOW TABLES LIKE 'formation_%';"
```

### Tests
```bash
# Tests formations uniquement
php7.4 vendor/bin/phpunit -c phpunit_formation.xml

# Avec couverture
php7.4 vendor/bin/phpunit -c phpunit_formation.xml --coverage-html build/coverage/formation

# Playwright formations
cd playwright && npx playwright test formation/

# Smoke test complet
cd playwright && npx playwright test formation/smoke.spec.ts
```

### Vérifications
```bash
# Valider un fichier PHP
php -l application/controllers/programmes.php

# Chercher métadonnées manquantes dans les logs
tail -f application/logs/log-*.php | grep "GVV: input_field"

# Vérifier flag activé
mysql -u gvv -p gvv -e "SELECT * FROM configuration WHERE key='gestion_formations';"
```

---

## Notes d'Implémentation

### Priorités
1. **Phase 1** est bloquante pour toutes les autres
2. **Phase 2** doit être terminée avant Phase 3 et 4
3. **Phases 3 et 4** peuvent être parallélisées partiellement
4. **Phase 5** nécessite Phase 4 complète
5. **Phase 6** doit être intégrée progressivement à chaque phase

### Points d'attention
- **Metadata-driven** : toujours définir les métadonnées avant les vues
- **Tests en continu** : tester chaque composant avant de passer au suivant
- **Validation syntaxe** : `php -l` sur chaque fichier créé
- **Smoke tests réguliers** : valider les workflows après chaque phase
- **Séances libres** : 
  - `inscription_id` NULL = séance libre
  - Toujours valider que `pilote_id` et `programme_id` sont renseignés
  - Ne pas inclure les séances libres dans le calcul de progression officielle
  - Afficher clairement la distinction (badges) dans l'interface

### Performance
- Indexer les tables pour les recherches fréquentes
- Optimiser les jointures dans les requêtes de progression (uniquement séances avec inscription)
- Cacher les structures de programmes parsées si nécessaire
- Limiter les requêtes N+1 dans l'affichage des fiches
- Ajouter index sur `inscription_id` et `pilote_id` dans `formation_seances`

---

## Dépendances Externes

### Existantes dans GVV
- CodeIgniter 2.x framework
- Bootstrap 5 (UI)
- TCPDF (export PDF)
- jQuery (interactions)

### Nouvelles (si nécessaire)
- Éditeur Markdown : Utiliser `<textarea>` simple ou intégrer SimpleMDE/CodeMirror
- Coloration syntaxe : Highlight.js pour la prévisualisation

---

## Mise à Jour Continue de ce Plan

Ce plan doit être mis à jour régulièrement pour refléter :
- ✅ Tâches complétées (cocher les cases)
- 🔄 Modifications de scope ou d'approche
- ⚠️ Blocages ou difficultés rencontrées
- 📊 Pourcentage de progression mis à jour

**Dernière mise à jour** : 26 janvier 2026 - Phase 2 complétée (8 tests Playwright CRUD passent)
