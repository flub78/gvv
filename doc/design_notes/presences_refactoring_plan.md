# Plan de Refonte : Contrôleur Presences

**Date** : 2026-01-13
**Objectif** : Remplacer le contrôleur `presences.php` par une version moderne basée sur FullCalendar v6, stockant les données dans la table `calendar` au lieu de Google Calendar.

---

## 1. Analyse de l'Existant

### 1.1 Contrôleur presences.php actuel

Pour information, il est inutile de préserver l'éxistant.

- **Stockage** : Google Calendar (via bibliothèque GoogleCal.php)
- **Interface** : Ancienne version FullCalendar (v2/v3) avec jQuery
- **Fonctionnalités** :
  - Création de présences avec date, pilote, rôle, commentaire
  - Modification d'événements existants
  - Suppression d'événements
  - Contrôles d'autorisation (CA peut tout modifier, utilisateurs réguliers seulement leurs événements)
  - Support drag & drop et resize (via ancien FullCalendar)

### 1.2 Table calendar
```sql
Field        Type          Null  Key  Default  Extra
id           int(11)       NO    PRI  NULL     auto_increment
date         date          NO         NULL
mlogin       varchar(64)   NO         NULL
role         varchar(64)   NO         NULL
commentaire  varchar(256)  NO         NULL
```

**Problèmes identifiés** :
- `date` est de type `date` (pas `datetime`) → ne supporte pas les heures
- Pas de champs `start_datetime` / `end_datetime` → pas de support multi-jours natif
- Pas de champ `status` → pas de gestion d'état
- Pas de champs d'audit (`created_by`, `updated_by`, `created_at`, `updated_at`)

### 1.3 Contrôleur reservations.php (référence)
- **Stockage** : Table `reservations` dans MySQL
- **Interface** : FullCalendar v6
- **Fonctionnalités** :
  - CRUD complet via API JSON
  - Drag & drop pour changer dates/heures
  - Resize pour étendre durée
  - Support multi-jours natif
  - Modal Bootstrap 5 pour édition
  - Validation de conflits
  - Logging complet

### 1.4 Tests existants
- **PHPUnit** : Aucun test pour `calendar` ou `presences`
- **Playwright** : Aucun test end-to-end pour ces fonctionnalités

---

## 2. Architecture Proposée

### 2.1 Migration de la table calendar

**Option A : Étendre la table existante (recommandé)**
```sql
ALTER TABLE calendar
  MODIFY COLUMN date date NULL COMMENT 'Deprecated - use start_datetime',
  ADD COLUMN start_datetime datetime NOT NULL AFTER date,
  ADD COLUMN end_datetime datetime NOT NULL AFTER start_datetime,
  ADD COLUMN full_day tinyint(1) NOT NULL DEFAULT 1
      COMMENT 'True = journée complète, False = heures spécifiques' AFTER end_datetime,
  ADD COLUMN status enum('confirmed', 'pending', 'completed', 'cancelled')
      DEFAULT 'confirmed' AFTER commentaire,
  ADD COLUMN created_by varchar(64) NULL AFTER status,
  ADD COLUMN updated_by varchar(64) NULL AFTER created_by,
  ADD COLUMN created_at timestamp DEFAULT CURRENT_TIMESTAMP AFTER updated_by,
  ADD COLUMN updated_at timestamp DEFAULT CURRENT_TIMESTAMP
      ON UPDATE CURRENT_TIMESTAMP AFTER created_at,
  ADD INDEX idx_date_range (start_datetime, end_datetime),
  ADD INDEX idx_mlogin (mlogin),
  ADD INDEX idx_status (status),
  ADD INDEX idx_full_day (full_day);

```

Il est inutile de migrer les données existantes.

**Nouveau champ `full_day`** :
- **Type** : `tinyint(1)` (booléen)
- **Valeur par défaut** : `1` (true)
- **Objectif** : Distinguer les présences "journée complète" des présences avec heures spécifiques
- **Logique métier** :
  - `full_day = 1` : Présence pour toute la journée (00:00:00 - 23:59:59)
  - `full_day = 0` : Présence avec heures spécifiques (ex: 09:00 - 12:00)
- **Restriction MVP** : Pour l'instant, **non modifiable via GUI**
  - Les pilotes signalent leur présence jour par jour, sans détail horaire
  - Choix intentionnel pour simplifier la saisie
  - Extension future : permettre présences avec heures spécifiques si besoin métier

**Implications sur FullCalendar** :
- Événements `full_day = 1` affichés avec `allDay: true` dans FullCalendar
- Événements multi-jours avec `full_day = 1` : barre horizontale sur plusieurs jours
- Événements `full_day = 0` : affichés avec horaires (futur)


**Recommandation : Option A** - Il est inutile de migrer les données existantes.

### 2.2 Structure du nouveau contrôleur

**Fichier** : `application/controllers/presences.php`

```php
class Presences extends CI_Controller {

    // Méthodes principales
    function index()                    // Affichage FullCalendar v6
    function get_events()               // API JSON : récupérer événements
    function create_presence()          // API JSON : créer présence
    function update_presence()          // API JSON : modifier présence
    function delete_presence()          // API JSON : supprimer présence
    function on_event_drop()            // API JSON : drag & drop
    function on_event_resize()          // API JSON : resize

    // Méthodes d'autorisation
    private function can_modify($event) // Vérifier droits modification
    private function can_create()       // Vérifier droits création

    // Méthodes utilitaires
    private function validate_data($data) // Validation des données
}
```

### 2.3 Modèle calendar_model.php (nouveau)

**Fichier** : `application/models/calendar_model.php`

```php
class Calendar_model extends CI_Model {

    // CRUD operations
    function get_events($start, $end)
    function get_event($id)
    function create_event($data)
    function update_event($id, $data)
    function delete_event($id)

    // Queries
    function get_user_events($mlogin, $start, $end)
    function check_conflict($mlogin, $start, $end, $exclude_id = null)

    // Utilities
    function format_for_fullcalendar($events)

    // Helper: Normalise les dates pour événements full_day
    private function normalize_full_day_dates($start_date, $end_date)
}
```

**Logique spécifique `full_day`** :

La méthode `create_event()` et `update_event()` doivent gérer automatiquement les timestamps :

```php
// Si full_day = 1 (par défaut), normaliser les heures
if (!isset($data['full_day']) || $data['full_day'] == 1) {
    $data['full_day'] = 1;

    // Assurer que start_datetime commence à 00:00:00
    if (isset($data['start_date'])) {
        $data['start_datetime'] = $data['start_date'] . ' 00:00:00';
    }

    // Assurer que end_datetime finit à 23:59:59
    if (isset($data['end_date'])) {
        $data['end_datetime'] = $data['end_date'] . ' 23:59:59';
    }
}
```

La méthode `format_for_fullcalendar()` doit retourner `allDay: true` pour tous les événements :

```php
function format_for_fullcalendar($events) {
    $formatted = [];
    foreach ($events as $event) {
        $formatted[] = [
            'id' => $event['id'],
            'title' => $this->format_title($event),
            'start' => $event['start_datetime'],
            'end' => $event['end_datetime'],
            'allDay' => (bool)$event['full_day'],  // Toujours true pour MVP
            'extendedProps' => [
                'mlogin' => $event['mlogin'],
                'role' => $event['role'],
                'commentaire' => $event['commentaire'],
                'status' => $event['status']
            ]
        ];
    }
    return $formatted;
}
```

### 2.4 Vue presences.php (nouvelle)

**Fichier** : `application/views/presences/presences.php`

Structure similaire à `bs_reservations_v6.php` :
- FullCalendar v6 (CDN)
- Modal Bootstrap 5 pour CRUD
- Support multi-langues (FR, EN, NL)
- Log des événements FullCalendar (optionnel en dev)
- Formulaire avec :
  - **Date de début** (input type="date", pas datetime-local)
  - **Date de fin** (input type="date", pour événements multi-jours)
  - Pilote (sélecteur)
  - Rôle (dropdown : "", Instructeur, Remorqueur, Élève, Solo, etc.)
  - Commentaire
  - Status
  - **Pas de champ full_day** : toujours `true` par défaut, non modifiable dans cette version

---

## 3. Fonctionnalités Détaillées

### 3.1 Affichage du calendrier

**Vues disponibles** :
- `dayGridMonth` : Vue mois (par défaut, **recommandée pour présences journées complètes**)
- `timeGridWeek` : Vue semaine avec heures (pour visualisation, mais présences = journées complètes)
- `timeGridDay` : Vue jour avec heures (pour visualisation, mais présences = journées complètes)
- `listWeek` : Liste des événements

**Configuration FullCalendar** :
- **Mode principal** : Événements `allDay = true` (journées complètes)
- Locale : basée sur config CodeIgniter (fr/en/nl)
- Editable : true (drag & drop, resize)
- Selectable : true (création par sélection de jours)
- **Note** : Les vues `timeGridWeek` et `timeGridDay` affichent les présences sur toute la hauteur de la journée puisque `allDay = true`

**Comportement des événements** :
- Tous les événements ont `allDay: true` dans FullCalendar (car `full_day = 1` en BDD)
- Format d'affichage : Barre horizontale avec nom du pilote + rôle
- Couleurs : Différenciées par rôle (optionnel : Instructeur = bleu, Remorqueur = vert, etc.)
- Multi-jours : Barre continue sur plusieurs jours

### 3.2 Création de présence

**Déclencheurs** :
- Clic sur un jour (vue mois) → Modal avec dates début/fin pré-remplies (même jour)
- Clic sur un créneau horaire (vue semaine/jour) → Modal avec date cliquée
- Sélection d'une plage de jours → Modal avec dates début/fin de la sélection

**Formulaire** :
```
- Date début : [date input - JJ/MM/AAAA]
- Date fin : [date input - JJ/MM/AAAA]
- Pilote : [select avec membres actifs]
- Rôle : [select : "", Instructeur, Remorqueur, Élève, Solo, etc.]
- Commentaire : [text input]
- Status : [select : confirmed, pending, cancelled]
```

**Notes importantes** :
- **Pas de saisie d'heures** : Les présences sont toujours pour des journées complètes (`full_day = 1`)
- Les champs `start_datetime` et `end_datetime` sont automatiquement définis :
  - `start_datetime` = date_début + `00:00:00`
  - `end_datetime` = date_fin + `23:59:59`
- Champ `full_day` = `1` par défaut, **non modifiable via GUI** dans cette version
- **Raison métier** : Les pilotes signalent leur présence jour par jour, sans détail horaire
  - Simplifie la saisie
  - Évite les erreurs sur les heures
  - Reflète le besoin réel : savoir qui est présent quel jour, pas quelles heures précises

**Validation** :
- Date début <= date fin
- Pilote requis (sauf si commentaire libre pour événement non-nominatif)
- Vérification conflit pour le même pilote sur les mêmes dates (warning, pas blocage)
- Limite de durée max : par exemple 30 jours (configurable)

### 3.3 Modification de présence

**Déclencheurs** :
- Clic sur un événement → Modal pré-rempli
- Drag & drop → Mise à jour automatique via AJAX
- Resize → Mise à jour automatique via AJAX

**Autorisations** :
- CA/Admin : peuvent modifier toutes les présences
- Utilisateur régulier : seulement ses propres présences

### 3.4 Suppression de présence

**Déclencheur** :
- Bouton "Supprimer" dans le modal d'édition

**Autorisations** : Identiques à la modification

**Confirmation** : Modal de confirmation Bootstrap

### 3.5 Support multi-jours (journées complètes)

**Cas d'usage** :
- Présence sur plusieurs jours consécutifs (stage, campagne, semaine de vol, etc.)
- Extension par resize de la limite de fin (dans vue mois)
- Création directe avec dates début/fin différentes via formulaire

**Comportement** :
- Toujours en mode `full_day = 1` (journées complètes)
- Exemple : Présence du 15/06 au 18/06
  - `start_datetime` = `2024-06-15 00:00:00`
  - `end_datetime` = `2024-06-18 23:59:59`
  - `full_day` = `1`
  - FullCalendar affiche une barre horizontale continue du 15 au 18 inclus

**Affichage** :
- FullCalendar v6 avec `allDay: true` affiche automatiquement les événements multi-jours en barre horizontale
- Drag & drop fonctionne sur toute la durée (déplace tous les jours)
- Resize fonctionne pour étendre/réduire le nombre de jours

**Limitation** :
- Pas de gestion d'heures spécifiques sur multi-jours (volontaire pour cette version)
- Si besoin futur : utiliser `full_day = 0` avec heures spécifiques

---

## 4. Gestion des Autorisations

### 4.1 Règles métier

**Utilisateur CA ou supérieur** :
- Peut créer des présences pour n'importe quel pilote
- Peut modifier toutes les présences
- Peut supprimer toutes les présences

**Utilisateur régulier** :
- Peut créer uniquement ses propres présences
- Peut modifier uniquement ses propres présences
- Peut supprimer uniquement ses propres présences

**Vérification** :
```php
private function can_modify($event_id) {
    if ($this->dx_auth->is_role('ca', true, true)) {
        return true;
    }

    $event = $this->calendar_model->get_event($event_id);
    $current_user = $this->dx_auth->get_username();

    return ($event['mlogin'] === $current_user);
}
```

### 4.2 Implémentation dans le contrôleur

Chaque méthode CRUD vérifie les autorisations :
```php
function update_presence() {
    $event_id = $this->input->post('id');

    if (!$this->can_modify($event_id)) {
        echo json_encode([
            'success' => false,
            'error' => $this->lang->line('presences_error_unauthorized')
        ]);
        return;
    }

    // ... suite du traitement
}
```

---

## 5. Langue et Traductions

### 5.1 Fichiers de langue

**Créer** : `application/language/{french,english,dutch}/presences_lang.php`

**Clés nécessaires** :
```php
$lang['presences_title'] = 'Gestion des Présences';
$lang['presences_modal_new'] = 'Nouvelle Présence';
$lang['presences_modal_edit'] = 'Modifier Présence';
$lang['presences_form_pilot'] = 'Pilote';
$lang['presences_form_role'] = 'Rôle';
$lang['presences_form_comment'] = 'Commentaire';
$lang['presences_form_start'] = 'Début';
$lang['presences_form_end'] = 'Fin';
$lang['presences_form_status'] = 'Statut';
$lang['presences_btn_create'] = 'Créer';
$lang['presences_btn_save'] = 'Enregistrer';
$lang['presences_btn_delete'] = 'Supprimer';
$lang['presences_btn_cancel'] = 'Annuler';
$lang['presences_error_unauthorized'] = 'Non autorisé à modifier cet événement';
$lang['presences_error_conflict'] = 'Conflit détecté';
$lang['presences_success_created'] = 'Présence créée';
$lang['presences_success_updated'] = 'Présence mise à jour';
$lang['presences_success_deleted'] = 'Présence supprimée';
$lang['presences_confirm_delete'] = 'Confirmer la suppression ?';

// Rôles (réutiliser welcome_options existant)
$lang['presences_role_instructeur'] = 'Instructeur';
$lang['presences_role_remorqueur'] = 'Remorqueur';
$lang['presences_role_eleve'] = 'Élève';
$lang['presences_role_solo'] = 'Solo';
// etc.
```

### 5.2 Réutilisation de welcome_options

Le fichier `welcome_lang.php` contient déjà `welcome_options` avec les rôles.
→ Réutiliser ces valeurs dans le dropdown "Rôle" du formulaire.

---

## 6. Tests

### 6.1 Tests PHPUnit MySQL

**Fichier** : `application/tests/mysql/Calendar_model_test.php`

**Tests à implémenter** :
```php
class Calendar_model_test extends PHPUnit_Framework_TestCase {

    // Setup & Teardown
    public function setUp()
    public function tearDown()

    // CRUD Tests
    public function test_create_event()
    public function test_get_event()
    public function test_get_events_date_range()
    public function test_update_event()
    public function test_delete_event()

    // Business Logic Tests
    public function test_get_user_events()
    public function test_check_conflict()
    public function test_format_for_fullcalendar()

    // Multi-day Tests
    public function test_create_multi_day_event()
    public function test_extend_event_by_days()

    // Full Day Tests
    public function test_create_event_full_day_default()
    public function test_full_day_normalizes_timestamps()
    public function test_format_for_fullcalendar_with_full_day()

    // Edge Cases
    public function test_create_event_invalid_dates()
    public function test_get_nonexistent_event()
}
```

**Tests spécifiques `full_day`** :

1. `test_create_event_full_day_default()` : Vérifier que `full_day = 1` par défaut si non spécifié
2. `test_full_day_normalizes_timestamps()` : Vérifier normalisation automatique des timestamps
   - Input : `start_date = '2024-06-15'`, `end_date = '2024-06-15'`
   - Output : `start_datetime = '2024-06-15 00:00:00'`, `end_datetime = '2024-06-15 23:59:59'`, `full_day = 1`
3. `test_format_for_fullcalendar_with_full_day()` : Vérifier que `allDay: true` dans le JSON retourné

**Couverture visée** : >75%

### 6.2 Test Playwright End-to-End

**Fichier** : `playwright/tests/presences-fullcalendar.spec.js`

**Scénarios à tester** :
```javascript
describe('Presences FullCalendar v6', () => {

    // Setup
    test.beforeEach(async ({ page }) => {
        await page.goto('/index.php/presences');
        // Login si nécessaire
    });

    // Test 1 : Affichage du calendrier
    test('should display FullCalendar with correct views', async ({ page }) => {
        // Vérifier présence du calendrier
        // Vérifier boutons de navigation
        // Vérifier vues disponibles
    });

    // Test 2 : Création par clic sur jour
    test('should open modal on day click and create presence', async ({ page }) => {
        // Cliquer sur un jour
        // Vérifier ouverture du modal
        // Remplir formulaire
        // Cliquer "Créer"
        // Vérifier que l'événement apparaît
    });

    // Test 3 : Édition d'événement
    test('should edit existing presence', async ({ page }) => {
        // Créer un événement
        // Cliquer sur l'événement
        // Modifier les champs
        // Enregistrer
        // Vérifier mise à jour
    });

    // Test 4 : Drag & Drop
    test('should move presence by drag and drop', async ({ page }) => {
        // Créer un événement
        // Drag vers un autre jour
        // Vérifier nouvelle position
        // Vérifier via API que la date a changé
    });

    // Test 5 : Resize pour multi-jours
    test('should extend presence by resizing', async ({ page }) => {
        // Créer un événement
        // Resize pour étendre sur 3 jours
        // Vérifier affichage multi-jours
        // Vérifier durée via API
    });

    // Test 6 : Suppression
    test('should delete presence', async ({ page }) => {
        // Créer un événement
        // Ouvrir modal
        // Cliquer "Supprimer"
        // Confirmer
        // Vérifier disparition
    });

    // Test 7 : Autorisations utilisateur régulier
    test('should not allow regular user to edit others presences', async ({ page }) => {
        // Login comme utilisateur régulier
        // Créer événement pour autre utilisateur (doit échouer)
        // Modifier événement d'un autre (doit échouer)
    });

    // Test 8 : Autorisations CA
    test('should allow CA to edit all presences', async ({ page }) => {
        // Login comme CA
        // Créer événement pour n'importe quel pilote
        // Modifier événement de quelqu'un d'autre
        // Vérifier succès
    });

    // Test 9 : Vérification full_day = 1 par défaut
    test('should create presence with full_day = true', async ({ page }) => {
        // Créer un événement
        // Vérifier via API que full_day = 1
        // Vérifier que start_datetime = date + 00:00:00
        // Vérifier que end_datetime = date + 23:59:59
        // Vérifier affichage en allDay dans FullCalendar
    });

    // Test 10 : Présence multi-jours en full_day
    test('should create multi-day presence with full_day', async ({ page }) => {
        // Créer événement du 15 au 18
        // Vérifier barre horizontale continue
        // Vérifier via API full_day = 1
        // Vérifier dates correctes (15 00:00 -> 18 23:59)
    });
});
```

### 6.3 Test de migration

**Fichier** : `application/tests/mysql/Calendar_migration_test.php`

**Tests** :
- Vérifier que la migration s'exécute sans erreur
- Vérifier la présence des nouvelles colonnes
- Vérifier les index
- Vérifier la migration des données existantes (date → start_datetime)
- Vérifier le rollback

---

## 7. Menu de Développement

### 7.1 Modification du menu

**Fichier** : `application/views/bs_menu.php`

**Ajouter après la ligne 368** :
```php
<li>
    <a class="dropdown-item" href="<?= controller_url("presences") ?>">
        <i class="fas fa-calendar-check text-success"></i>
        Présences (FullCalendar v6)
    </a>
</li>
```

### 7.2 Clé de langue pour le menu

**Fichier** : `application/language/french/gvv_lang.php` (et EN, NL)

Ajouter :
```php
$lang['gvv_menu_presences'] = 'Présences';
```

---

## 8. Dépendances et Configuration

### 8.1 Bibliothèques externes

**FullCalendar v6** :
- CDN : `https://cdn.jsdelivr.net/npm/fullcalendar@6.1.20/index.global.min.js`
- Locales : `https://cdn.jsdelivr.net/npm/fullcalendar@6.1.20/locales/{fr,nl}.global.min.js`

**Bootstrap 5** : Déjà inclus dans le projet


---

## 9. Plan d'Implémentation

### Phase 1 : Migration base de données
1. Créer migration `application/migrations/XXX_refactor_calendar_table.php`
2. Tester migration up/down
3. Mettre à jour `config/migration.php`
4. Créer test de migration

**Durée estimée** : 1 unité

### Phase 2 : Modèle calendar_model.php
1. Créer le modèle avec toutes les méthodes CRUD
2. Implémenter les queries (get_events, get_user_events, etc.)
3. Créer tests PHPUnit MySQL
4. Viser couverture >75%

**Durée estimée** : 2 unités

### Phase 3 : Contrôleur presences.php
1. Créer structure basée sur reservations.php
2. Implémenter index() avec chargement des options
3. Implémenter API JSON (get_events, create, update, delete, drop, resize)
4. Implémenter gestion des autorisations
5. Ajouter logging

**Durée estimée** : 3 unités

### Phase 4 : Vue presences.php
1. Créer vue basée sur bs_reservations_v6.php
2. Configuration FullCalendar v6
3. Implémenter modal CRUD
4. Implémenter formulaire avec validation
5. Gérer les callbacks FullCalendar (eventClick, select, eventDrop, eventResize)
6. Intégration multi-langues

**Durée estimée** : 3 unités

### Phase 5 : Fichiers de langue
1. Créer presences_lang.php (FR, EN, NL)
2. Traduire toutes les clés
3. Réutiliser welcome_options pour les rôles

**Durée estimée** : 1 unité

### Phase 6 : Tests Playwright
1. Créer fichier de test presences-fullcalendar.spec.js
2. Implémenter tous les scénarios (affichage, CRUD, drag, resize, autorisations)
3. Configurer les fixtures et helpers nécessaires
4. Exécuter et corriger les bugs

**Durée estimée** : 2 unités

### Phase 7 : Intégration menu
1. Ajouter entrée dans bs_menu.php (section Dev)
2. Ajouter clés de langue
3. Tester navigation

**Durée estimée** : 0.5 unité

### Phase 8 : Documentation et finalisation
1. Documenter l'API dans le code (PHPDoc)
2. Mettre à jour CLAUDE.md si nécessaire
3. Créer note de design (ce document)
4. Tests de régression complets
5. Vérifier la couverture de code

**Durée estimée** : 1.5 unités

**Durée totale estimée** : 14 unités

---

## 10. Risques et Considérations

### 10.1 Conflits de présences

**Risque** : Plusieurs présences pour le même pilote au même moment

**Mitigation** :
- Vérification de conflit dans le modèle
- Warning dans l'interface (pas de blocage, car présence ≠ réservation)
- Affichage clair des conflits dans le calendrier

---

## 11. Extensions Futures (Hors Scope MVP)

1. **Présences avec heures spécifiques (`full_day = 0`)** :
   - Ajouter checkbox "Journée complète" dans le formulaire
   - Si décoché, afficher inputs `datetime-local` pour start/end avec heures
   - Afficher ces présences avec horaires dans FullCalendar (vues timeGrid)
   - Cas d'usage : Présence seulement le matin (9h-12h), seulement l'après-midi (14h-18h), etc.
   - **Note** : Nécessite validation métier avant implémentation

2. **Export/Import** : Export iCal, import CSV

3. **Notifications** : Email/SMS de rappel de présence

4. **Statistiques** : Dashboard de présences par pilote, par rôle

5. **Récurrence** : Présences récurrentes (tous les mardis, etc.)

6. **Intégration avec Vols** : Lier présence et vols réalisés


---

## 12. Checklist de Validation

Avant de considérer la refonte terminée :

### Fonctionnel
- [ ] Affichage du calendrier en vues mois/semaine/jour/liste
- [ ] Création de présence par clic sur jour
- [ ] Création de présence par sélection de plage
- [ ] Édition de présence par clic sur événement
- [ ] Suppression de présence avec confirmation
- [ ] Drag & drop pour changer date/heure
- [ ] Resize pour étendre sur plusieurs jours
- [ ] Événements multi-jours affichés correctement
- [ ] **Événements affichés en `allDay: true` (journées complètes)**
- [ ] **Champ `full_day = 1` par défaut en BDD**
- [ ] **Formulaire utilise inputs type="date" (pas datetime-local)**
- [ ] **Timestamps normalisés automatiquement (00:00:00 - 23:59:59)**
- [ ] Validation des formulaires (dates, champs requis)
- [ ] Gestion des autorisations (CA vs utilisateur régulier)
- [ ] Support multi-langues (FR, EN, NL)

### Technique
- [ ] Migration base de données testée (up/down)
- [ ] Modèle calendar_model.php créé et testé
- [ ] Contrôleur presences.php implémenté
- [ ] Vue presences.php créée
- [ ] Fichiers de langue créés (FR, EN, NL)
- [ ] Tests PHPUnit MySQL >75% couverture
- [ ] Tests Playwright end-to-end tous passants
- [ ] Test de migration avec vérification données
- [ ] Entrée menu ajoutée
- [ ] Documentation code (PHPDoc)
- [ ] Logs appropriés (info, debug, error)

### Performance
- [ ] Chargement rapide du calendrier (<2s)
- [ ] Pas de requêtes N+1
- [ ] Index SQL optimisés
- [ ] Pas de memory leak côté client

### Sécurité
- [ ] Protection CSRF sur toutes les API
- [ ] Validation des entrées côté serveur
- [ ] Échappement XSS dans les affichages
- [ ] Autorisations vérifiées sur chaque action
- [ ] SQL injection prévenue (prepared statements)

---

## 13. Ressources et Références

### Documentation FullCalendar v6
- [FullCalendar Docs](https://fullcalendar.io/docs/v6)
- [API Events](https://fullcalendar.io/docs/v6/event-object)
- [Callbacks](https://fullcalendar.io/docs/v6/handlers)

### Références dans le projet GVV
- Contrôleur : `application/controllers/reservations.php`
- Vue : `application/views/reservations/bs_reservations_v6.php`
- Modèle : `application/models/reservations_model.php`
- Tests : Pas encore implémentés pour reservations

### CodeIgniter 2.x
- [Database Reference](https://codeigniter.com/userguide2/database/index.html)
- [Migrations](https://codeigniter.com/userguide2/libraries/migration.html)

---

## 14. Décisions Architecturales

### 14.1 Pourquoi FullCalendar v6 ?
- Déjà utilisé dans le projet (reservations)
- Mature et bien maintenu
- Support drag & drop et resize natif
- Multi-vues (mois, semaine, jour, liste)
- Excellent support multi-langues
- Pas de dépendances lourdes (standalone)

### 14.2 Pourquoi étendre la table `calendar` au lieu de créer une nouvelle ?
- Préserve les données existantes
- Compatibilité descendante possible
- Évite la duplication de tables
- Migration progressive possible

### 14.3 Pourquoi séparer du contrôleur `calendar.php` ?
- `calendar.php` est très simple et expérimental
- Préserve la séparation des préoccupations

### 14.4 Pourquoi `full_day = 1` par défaut et non modifiable ?
**Décision métier** : Les pilotes de planeur signalent leur présence jour par jour, sans préciser les heures.

**Justifications** :
1. **Simplicité d'utilisation** :
   - Saisie rapide : juste sélectionner le(s) jour(s) de présence
   - Pas de complexité sur les heures de début/fin
   - Réduit les erreurs de saisie

2. **Besoin métier réel** :
   - L'important est de savoir **qui est présent quel jour** pour organiser les vols
   - Les horaires précis ne sont pas nécessaires pour la planification
   - Les présences sont pour des journées de vol entières (pas des créneaux horaires)

3. **Cohérence avec l'historique** :
   - L'ancien système (Google Calendar) fonctionnait aussi en journées complètes
   - Migration des données existantes préservée
   - Pas de changement d'habitudes pour les utilisateurs

4. **Architecture extensible** :
   - Le champ `full_day` existe en BDD
   - L'architecture supporte `full_day = 0` (avec heures)
   - Activation possible dans une version future si besoin métier émerge
   - Pas de refonte nécessaire, juste ajout du toggle dans le GUI

**Alternative rejetée** : Permettre la saisie d'heures dès le MVP
- Augmente la complexité du formulaire
- Risque de confusion pour les utilisateurs
- Pas de besoin métier identifié actuellement
- YAGNI (You Ain't Gonna Need It) : on implémente quand le besoin est réel

---

## 15. Conclusion

Cette refonte transforme le système de gestion des présences d'une solution obsolète (Google Calendar + ancien FullCalendar) vers une architecture moderne, maintenable et testable :

**Avantages** :
- ✅ Interface moderne (FullCalendar v6 + Bootstrap 5)
- ✅ Données locales (pas de dépendance externe)
- ✅ Support multi-jours natif
- ✅ Drag & drop / Resize fluides
- ✅ Tests automatisés (PHPUnit + Playwright)
- ✅ Cohérence avec le reste du projet (reservations)
- ✅ Multi-langues complet
- ✅ Autorisations granulaires

**Prérequis pour démarrage** :
1. Approbation de ce plan
2. Backup de la base de données
3. Environnement de test disponible

**Prêt pour implémentation** : Oui ✅

---

**Auteur** : Claude Sonnet 4.5
**Révision** : Validé par l'utilisateur
