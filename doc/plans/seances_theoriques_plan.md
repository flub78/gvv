# Plan d'Implémentation – Séances de Formation Théoriques

**Référence PRD** : [doc/prds/gestion_des_seances_theoriques.md](../prds/gestion_des_seances_theoriques.md)
**Statut global** : ✅ Complété (Phases 1, 2 et 3)
**Date de création** : 2026-02-28

---

## Vue d'ensemble

Extension du module de suivi de formation (migration 063) pour :
1. Introduire un référentiel de types de séances (vol / sol) géré par le responsable pédagogique.
2. Permettre la saisie de séances théoriques avec plusieurs participants.
3. Consolider les rapports annuels sur les séances pratiques et théoriques.

**Architecture retenue** : Option B – table séance + table participants.
- `formation_seances` : une ligne par séance ; `pilote_id` nullable pour les séances théoriques.
- `formation_seances_participants` : liste des participants pour les séances théoriques.
- Les champs vol (`machine_id`, `duree`, `nb_atterrissages`) deviennent nullable.
- Rétrocompatibilité totale sur les séances en vol existantes.

### Principes

- Architecture metadata-driven (Gvvmetadata.php)
- Flag `gestion_formations` existant couvre cette extension
- Migrations numérotées à partir de 078
- Tests PHPUnit (>70% couverture), tests Playwright end-to-end
- Multi-langue : français, anglais, néerlandais

---

## Todo List Globale

### Phase 1 – Types de Séances ✅ 7/7

- [x] 1.1 – Migration 078 : créer `formation_types_seance` (avec `periodicite_max_jours`), insérer les types par défaut, ajouter `type_seance_id` sur `formation_seances`
- [x] 1.2 – Modèle `formation_type_seance_model` (inclut `get_with_periodicite()`, `get_eleves_non_conformes()`)
- [x] 1.3 – Métadonnées Gvvmetadata.php pour `formation_types_seance` (inclut champ `periodicite_max_jours`)
- [x] 1.4 – Contrôleur CRUD `formation_types_seances`
- [x] 1.5 – Vues : liste et formulaire des types de séances (affichage de la périodicité)
- [x] 1.6 – Fichiers de langue (français, anglais, néerlandais)
- [x] 1.7 – Tests PHPUnit : migration, modèle, contrôleur (11/11 tests passent)

### Phase 2 – Séances Théoriques ✅ 10/10

- [x] 2.1 – Migration 079 : créer `formation_seances_participants`, rendre nullable `pilote_id`, `machine_id`, `duree`, `nb_atterrissages`, ajouter `lieu`
- [x] 2.2 – Modèle `formation_seance_participants_model`
- [x] 2.3 – Étendre `Formation_seance_model` : `create_theorique()`, `update_theorique()`, `get_participants()`, `get_theoriques_by_instructeur()`, `is_theorique()`
- [x] 2.4 – Contrôleur `formation_seances_theoriques` (create, store, edit, update, delete, detail, ajax_search_membres)
- [x] 2.5 – Vue : formulaire de création/édition d'une séance théorique (type, date, programme, lieu, durée, participants multi-sélection, commentaires)
- [x] 2.6 – Composant JS : sélecteur multi-participants avec recherche par nom (`assets/js/formation_participants.js`)
- [x] 2.7 – Vue : détail d'une séance théorique (liste des participants, programme associé, commentaires)
- [x] 2.8 – Étendre l'historique des séances (`formation_seances/index`) : colonne Nature (badge Vol/Sol), colonne Participants, filtre par nature
- [x] 2.9 – Fichiers de langue (français, anglais, néerlandais) – 24 clés ajoutées
- [x] 2.10 – Tests PHPUnit : 11/11 (migration, modèles, participants) ; Tests Playwright : 5/5 (accès, formulaire, AJAX, filtre)

### Phase 3 – Rapports Annuels Consolidés ✅ 8/8

- [x] 3.1 – Étendre `Formation_seance_model` : `get_stats_annuels_par_instructeur()`, `get_stats_annuels_par_programme()`, `_make_instructor_stats()`
- [x] 3.2 – Étendre `Formation_seance_participants_model` : `count_total_participants_year()`
- [x] 3.3 – `Formation_type_seance_model::get_eleves_non_conformes()` : implémenté en Phase 1
- [x] 3.4 – Étendre `Formation_rapports` : `annuel()`, `new_year_annuel()`, `conformite()`, `export_annuel_csv()`, `export_conformite_csv()`
- [x] 3.5 – Vue `formation_rapports/annuel.php` : onglets par instructeur et par programme, totaux, lien CSV
- [x] 3.6 – Vue `formation_rapports/conformite.php` : par type de séance, liste des élèves avec statut et export CSV
- [x] 3.7 – Export CSV (rapport annuel + conformité) ; lien vers les nouvelles vues depuis `formation_rapports/index.php`
- [x] 3.8 – Tests PHPUnit : 3 tests stats/participants (14/14 passent) ; Tests Playwright : 4 tests rapports (9/9 passent)

---

## Détail des Phases

### Phase 1 – Types de Séances

#### 1.1 Migration 078

```sql
-- Table des types de séances
CREATE TABLE `formation_types_seance` (
    `id`                    INT(11)      NOT NULL AUTO_INCREMENT,
    `nom`                   VARCHAR(100) NOT NULL,
    `nature`                ENUM('vol','theorique') NOT NULL,
    `description`           TEXT         NULL,
    `periodicite_max_jours` INT(11)      NULL COMMENT 'Délai max entre deux séances de ce type pour un même élève (NULL = sans contrainte)',
    `actif`                 TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Types par défaut
INSERT INTO `formation_types_seance` (nom, nature, periodicite_max_jours, actif) VALUES
    ('Vol biplace d\'instruction', 'vol',       NULL, 1),
    ('Vol solo supervisé',         'vol',       NULL, 1),
    ('Cours sol – Général',        'theorique', 365,  1),
    ('Briefing de groupe',         'theorique', NULL, 1);

-- Référence sur formation_seances
ALTER TABLE `formation_seances`
    ADD COLUMN `type_seance_id` INT(11) NULL AFTER `id`,
    ADD CONSTRAINT `fk_seance_type` FOREIGN KEY (`type_seance_id`)
        REFERENCES `formation_types_seance`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;
```

**Down** : DROP FOREIGN KEY, DROP COLUMN `type_seance_id`, DROP TABLE `formation_types_seance`.

#### 1.2 Modèle `formation_type_seance_model`

Méthodes : `get_all()`, `get_active()`, `get_active_by_nature($nature)`, `get($id)`, `create()`, `update()`, `delete_if_unused()`, `deactivate()`, `get_selector()`, `get_with_periodicite()` (types ayant une périodicité définie), `get_eleves_non_conformes($type_id)` (élèves actifs dépassant le seuil pour un type donné).

#### 1.3 Métadonnées Gvvmetadata.php

Définir les champs `formation_types_seance` : `nom`, `nature` (enum), `description`, `periodicite_max_jours` (int, optionnel), `actif` (bool). Sélecteur `formation_types_seance_selector` utilisé dans le formulaire de séance.

#### 1.4 Contrôleur `formation_types_seances`

Routes : `index`, `create`, `store`, `edit`, `update`, `delete`. Réservé aux administrateurs (responsable pédagogique). Protégé par le flag `gestion_formations`.

#### 1.5 Vues

- `formation_types_seances/index.php` : tableau liste (nom, nature badge, description, actif, actions)
- `formation_types_seances/form.php` : formulaire création/édition

#### 1.6 Langue

Clés : `formation_type_seance_*`, `formation_types_seances_title`, `formation_nature_vol`, `formation_nature_theorique`.

---

### Phase 2 – Séances Théoriques

#### 2.1 Migration 079

```sql
-- Table des participants aux séances théoriques
CREATE TABLE `formation_seances_participants` (
    `id`        INT(11)      NOT NULL AUTO_INCREMENT,
    `seance_id` INT(11)      NOT NULL,
    `pilote_id` VARCHAR(25)  NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_seance_pilote` (`seance_id`, `pilote_id`),
    CONSTRAINT `fk_part_seance` FOREIGN KEY (`seance_id`)
        REFERENCES `formation_seances`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_part_pilote` FOREIGN KEY (`pilote_id`)
        REFERENCES `membres`(`mlogin`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Assouplissement du modèle formation_seances
ALTER TABLE `formation_seances`
    MODIFY `pilote_id`        VARCHAR(25)  NULL COMMENT 'NULL pour séances théoriques (participants dans table dédiée)',
    MODIFY `machine_id`       VARCHAR(10)  NULL COMMENT 'NULL pour séances théoriques',
    MODIFY `duree`            TIME         NULL COMMENT 'NULL si non renseigné',
    MODIFY `nb_atterrissages` INT(11)      NULL COMMENT 'NULL pour séances théoriques',
    ADD COLUMN `lieu`         VARCHAR(255) NULL AFTER `meteo`;
```

**Invariant de cohérence** : pour les séances de nature `vol` (détecté via `type_seance_id`), `pilote_id`, `machine_id` et `duree` doivent rester NOT NULL (validé au niveau applicatif).

#### 2.3 Extensions de `Formation_seance_model`

| Méthode | Description |
|---------|-------------|
| `create_theorique($data, $participants)` | Crée la séance et insère les participants |
| `update_theorique($id, $data, $participants)` | Met à jour séance et remplace la liste des participants |
| `get_participants($seance_id)` | Retourne la liste des membres participants |
| `get_theoriques_by_instructeur($instructeur_id, $year)` | Séances théoriques pour les rapports |
| `is_theorique($seance_id)` | Vrai si la séance est de nature théorique |

#### 2.4 Contrôleur `formation_seances_theoriques`

Routes : `index` (liste filtrée sol uniquement), `create`, `store`, `edit`, `update`, `delete`, `detail`.

La méthode `store` :
1. Valide date et type (nature `theorique`)
2. Valide la présence d'au moins un participant
3. Appelle `create_theorique()` dans une transaction

#### 2.5 Formulaire de Séance Théorique

- Champ type de séance (sélecteur filtré sur nature = theorique)
- Date, lieu (optionnel), durée (optionnel)
- Programme de formation (optionnel)
- Zone de saisie multi-participants (voir 2.6)
- Zone commentaires généraux

#### 2.6 Sélecteur Multi-Participants

Composant JS dans `assets/js/formation_participants.js` :
- Champ de recherche incrémentale (AJAX sur `membres`)
- Suggestions en liste déroulante
- Badges supprimables pour chaque participant sélectionné
- Champs hidden `participants[]` soumis avec le formulaire

Endpoint AJAX : `formation_seances_theoriques/ajax_search_membres` (retourne JSON).

#### 2.8 Extension de l'Historique des Séances

Dans `formation_seances/index` :
- Colonne **Nature** : badge Bootstrap `bg-primary` (Vol) / `bg-success` (Sol)
- Colonne **Participants** : `pilote_nom` pour les vols ; `N élève(s)` pour les théoriques (comptage depuis `formation_seances_participants`)
- Filtre nature : tous / vol / sol (sélecteur dans les filtres existants)
- Lien « Voir » pointe vers le bon contrôleur selon la nature

---

### Phase 3 – Rapports Annuels Consolidés

#### 3.1 Requêtes de Statistiques

**Par instructeur** :
```sql
-- Séances vol
SELECT instructeur_id, COUNT(*) nb_seances_vol, SUM(TIME_TO_SEC(duree))/3600 heures_vol
FROM formation_seances
WHERE YEAR(date_seance) = ? AND type nature = 'vol'
GROUP BY instructeur_id

-- Séances théoriques
SELECT s.instructeur_id, COUNT(*) nb_seances_sol, SUM(TIME_TO_SEC(s.duree))/3600 heures_sol,
       COUNT(DISTINCT p.pilote_id) nb_participants
FROM formation_seances s
JOIN formation_seances_participants p ON p.seance_id = s.id
WHERE YEAR(s.date_seance) = ? AND nature = 'theorique'
GROUP BY s.instructeur_id
```

**Par programme** : joint sur `formation_inscriptions` pour les élèves actifs, agrégation des deux types de séances.

#### 3.3 Requête de Conformité

`Formation_type_seance_model::get_eleves_non_conformes($type_id)` :
```sql
SELECT m.mlogin, m.mnom, m.mprenom,
       MAX(s.date_seance) AS derniere_seance,
       DATEDIFF(CURDATE(), MAX(s.date_seance)) AS jours_ecoules,
       t.periodicite_max_jours
FROM membres m
JOIN formation_inscriptions fi ON fi.pilote_id = m.mlogin AND fi.statut = 'ouverte'
LEFT JOIN formation_seances s
    ON s.pilote_id = m.mlogin AND s.type_seance_id = ?
LEFT JOIN formation_seances_participants sp
    ON sp.pilote_id = m.mlogin
LEFT JOIN formation_seances s2
    ON s2.id = sp.seance_id AND s2.type_seance_id = ?
CROSS JOIN formation_types_seance t ON t.id = ?
GROUP BY m.mlogin
HAVING derniere_seance IS NULL
    OR jours_ecoules > t.periodicite_max_jours
ORDER BY jours_ecoules DESC
```

#### 3.4 Extension de `Formation_rapports`

- Action `annuel($annee = null)` : filtre année, instructeur, programme, nature ; deux tableaux (par instructeur, par programme) ; exports CSV et PDF.
- Action `conformite()` : liste les types avec périodicité définie et, pour chacun, les élèves actifs non conformes (délai dépassé ou aucune séance). Export CSV.

#### 3.5 Vues Rapport Annuel et Conformité

**Rapport annuel** : deux onglets Bootstrap.
1. **Par instructeur** : Instructeur, Séances vol, Heures vol, Séances sol, Heures sol, Élèves distincts.
2. **Par programme** : Programme, Inscriptions actives, Séances vol, Séances sol, Total heures.

**Rapport de conformité** : un bloc par type de séance périodique.
- En-tête : nom du type, périodicité cible.
- Tableau : élève, dernière séance, délai écoulé, statut (badge vert Conforme / orange Proche / rouge Dépassé / gris Aucune séance).

Boutons d'export CSV et PDF en en-tête de chaque rapport.

---

## Fichiers Créés / Modifiés

| Fichier | Type |
|---------|------|
| `application/migrations/078_formation_types_seance.php` | Nouveau |
| `application/migrations/079_formation_seances_theoriques.php` | Nouveau |
| `application/models/formation_type_seance_model.php` | Nouveau |
| `application/models/formation_seance_participants_model.php` | Nouveau |
| `application/models/formation_seance_model.php` | Modifié |
| `application/controllers/formation_types_seances.php` | Nouveau |
| `application/controllers/formation_seances_theoriques.php` | Nouveau |
| `application/controllers/formation_seances.php` | Modifié (historique) |
| `application/controllers/formation_rapports.php` | Modifié (annuel + conformite) |
| `application/libraries/Gvvmetadata.php` | Modifié |
| `application/views/formation_types_seances/index.php` | Nouveau |
| `application/views/formation_types_seances/form.php` | Nouveau |
| `application/views/formation_seances_theoriques/form.php` | Nouveau |
| `application/views/formation_seances_theoriques/detail.php` | Nouveau |
| `application/views/formation_rapports/annuel.php` | Nouveau |
| `application/views/formation_rapports/conformite.php` | Nouveau |
| `assets/js/formation_participants.js` | Nouveau |
| `application/language/french/formation_lang.php` | Modifié |
| `application/language/english/formation_lang.php` | Modifié |
| `application/language/dutch/formation_lang.php` | Modifié |
| `application/config/migration.php` | Modifié (version → 079) |
| `application/tests/mysql/formation_types_seance_test.php` | Nouveau |
| `application/tests/mysql/formation_seances_theoriques_test.php` | Nouveau |
| `playwright/tests/formation-seances-theoriques.spec.js` | Nouveau |
| `playwright/tests/formation-rapports-annuels.spec.js` | Nouveau |

---

## Dépendances et Ordre d'Exécution

```
Phase 1 (Migration 078 + Types)
  └─► Phase 2 (Migration 079 + Séances théoriques)
        └─► Phase 3 (Rapports annuels)
```

La Phase 2 dépend de la Phase 1 (la table `formation_types_seance` et le champ `type_seance_id` doivent exister). La Phase 3 dépend des deux premières (les données doivent être présentes pour les requêtes d'agrégation).
