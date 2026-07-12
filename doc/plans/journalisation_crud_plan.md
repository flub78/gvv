# Plan de journalisation CRUD

**Date:** 2026-03-25 (mise à jour 2026-07-12)
**Statut:** 🟢 Lots 0 à 6 implémentés (régression corrigée et données historiques rattrapées, voir §2.3) — 🟡 Lot 7 à réaliser

---

## 1. Objectif

Homogénéiser la traçabilité des opérations CRUD sur les ressources métier afin de garantir:

- l'identification de l'auteur de création et de modification pour toute donnée sensible
- la date de création et de dernière modification
- une piste d'audit exploitable en cas de litige ou de besoin de réversibilité

Le sous-système d'autorisation sert de référence: il écrit dans une table d'audit dédiée (`authorization_audit_log`) et journalise en INFO les grant/revoke. C'est le seul domaine actuellement à ce niveau.

---

## 2. État actuel

### 2.1 Socle CRUD commun

`Common_Model` ne porte aucun audit métier:

- `create()` : log debug du payload, retourne l'`insert_id`
- `update()` : log d'erreur uniquement
- `delete()` : aucun log, aucun audit

Tout modèle héritant de `Common_Model` sans surcharge de ces méthodes n'a aucune traçabilité.

### 2.2 Classification par criticité

#### 🔴 Critique — finances et vols

Données réglementaires, financières et comptables. En cas d'erreur ou de fraude, l'absence d'audit empêche toute reconstitution fiable.

| Table | Audit actuel | Manques |
|---|---|---|
| `achats` | `saisie_par` (créateur) + log debug create | auteur update, date création/modif |
| `ecritures` | `saisie_par`, `date_creation` | auteur/date update, audit delete |
| `comptes` | `saisie_par` | date création/modif, auteur update |
| `tickets` | `saisie_par` | date création/modif, auteur update |
| `tarifs` | `saisie_par` | date création/modif, auteur update |
| `volsp` | log debug create/update | auteur, dates, audit delete |
| `volsa` | `saisie_par` | date création/modif, auteur update |
| `vols_decouverte` | `saisie_par`, `created_at`, `updated_at` | `created_by`, `updated_by` |

#### 🟠 Élevée — données membres, flotte, formation

Données opérationnelles durables, parfois à caractère réglementaire (licences, qualifications). Aucune colonne d'audit identifiée.

| Table | Audit actuel |
|---|---|
| `membres` | aucun |
| `licences` | aucun |
| `machinesa` | aucun |
| `machinesp` | aucun |
| `terrains` | aucun |
| `planc` | aucun |
| `pompes` | aucun |
| `formation_chapitres` | aucun |
| `formation_evaluations` | aucun |
| `formation_inscriptions` | aucun |
| `formation_item` | aucun |
| `formation_lecons` | aucun |
| `formation_progres` | aucun |
| `formation_seances` | aucun |
| `formation_seances_participants` | aucun |
| `formation_sujets` | aucun |
| `formation_types_seance` | aucun |
| `formation_autorisations_solo` | `date_creation`, `date_modification` |
| `formation_programmes` | `date_creation`, `date_modification` |

#### 🟡 Moyenne — documents et listes email

Audit partiel ou domaine-spécifique, cycle CRUD non couvert entièrement.

| Table | Audit actuel | Manques |
|---|---|---|
| `archived_documents` | `uploaded_by`, `uploaded_at`, `validated_by`, `validated_at` | auteur/date update générique, audit delete |
| `email_lists` | `created_by`, `created_at`, `updated_at` | `updated_by` manquant, audit delete |
| `email_list_roles` | `granted_by`, `granted_at` | auteur revoke |
| `document_types` | aucun | tout |
| `attachments` | aucun | tout |
| `mails` | aucun | tout |

#### 🟢 Correct — ressources récentes

| Table / Domaine | Audit actuel |
|---|---|
| `reservations` | `created_by`, `created_at`, `updated_by`, `updated_at` dans le code |
| `procedures` | `created_by`, `created_at`, `updated_by`, `updated_at` dans le code |
| `calendar` | `created_at`, `updated_at` + logs info create/update/delete |
| `authorization_audit_log` | table dédiée + logs INFO grant/revoke, avec IP |

#### Tables système sans audit (non prioritaires)

`associations_ecriture`, `associations_of`, `associations_releve`, `categorie`, `ci_sessions`, `clotures`, `configuration`, `data_access_rules`, `events`, `events_types`, `historique`, `login_attempts`, `migrations`, `permissions`, `reports`, `roles`, `role_permissions`, `sections`, `types_roles`, `type_ticket`, `users`, `user_autologin`, `user_profile`, `user_temp`.

Ces tables sont soit immuables, soit non-sensibles, soit déjà auditées par d'autres mécanismes (sessions, login_attempts).

### 2.3 Régression découverte en juillet 2026

Le mécanisme du Lot 0 (`inject_audit_fields()`) ne fonctionne en réalité **jamais** pour un enregistrement créé via un écran de saisie standard (formulaire HTML généré par `Gvvmetadata` + `Gvv_Controller::formValidation()`/`form2database()`). Constaté sur `volsa` suite à un signalement utilisateur (un `auto_planchiste` ne pouvait plus modifier un vol saisi le jour même), puis confirmé sur `volsp`, `comptes`, `tarifs`.

**Mécanisme du bug :** `Gvvmetadata` construit la liste des champs d'une table (`fields_list()`) à partir de `SHOW FULL FIELDS`, donc **toutes** les colonnes brutes, y compris `created_at`/`created_by`/`updated_at`/`updated_by`, même si elles ne sont jamais rendues dans le HTML du formulaire. `form2database()` boucle sur cette liste et appelle `$this->input->post($field)` pour chacune. Pour un champ absent du POST, CodeIgniter 2.x renvoie **`FALSE`** (pas `NULL`). Ce `FALSE` se retrouve donc explicitement dans `$data['created_at']`/`$data['created_by']`. Or le garde de `inject_audit_fields()` est `!isset($data[$field])`, et `isset(FALSE)` vaut `TRUE` — l'auto-remplissage est donc systématiquement court-circuité. Le `FALSE` résiduel est ensuite échappé par CodeIgniter en littéral `0` non quoté, que MySQL (mode non strict) convertit en date zéro (`0000-00-00 00:00:00`) pour les colonnes `datetime`, et en chaîne `'0'` pour les colonnes `varchar`.

**Pourquoi le test d'intégration du Lot 0 n'a rien détecté :** `CommonModelAuditMySqlTest` appelle le modèle directement avec un `$data` qui n'a jamais possédé les clés `created_at`/`created_by` (donc `isset()` renvoie bien `FALSE` dans ce test, et l'injection fonctionne). Ce test ne reproduit pas le cas réel où ces clés sont présentes avec la valeur `FALSE`, tel que produit par `form2database()`. Aucun test de bout en bout (formulaire → base) n'existait pour ce mécanisme.

**Tables non affectées :** `achats` et la part de `ecritures` générée par le moteur de facturation (`Facturation.php`) construisent `$data` en PHP et appellent le modèle directement, sans jamais passer par `form2database()` — elles ne sont donc pas exposées à ce bug et restent correctement renseignées, y compris aujourd'hui.

**`saisie_par` reste fiable :** ce champ legacy est positionné via un champ caché explicite (`form_hidden('saisie_par', $saisie_par, '')` dans les vues, ex. `vols_avion/bs_formView.php:46`), rempli côté serveur avant affichage. Il est donc réellement soumis dans le POST et jamais affecté par le bug ci-dessus — confirmé sur les enregistrements les plus récents des 8 tables qui le possèdent.

---

## 3. Architecture cible

### 3.1 Principe

Ajouter dans `Common_Model` un mécanisme automatique léger:

- peupler `created_at`, `updated_at` (timestamps) à chaque create/update si la colonne existe dans la table
- peupler `created_by`, `updated_by` (login de l'utilisateur connecté) si la colonne existe

Ce mécanisme est **opt-in par schéma**: si la colonne n'existe pas, il ne fait rien. Pas de migration obligatoire pour toutes les tables.

Pour les suppressions, journaliser en INFO (log applicatif) la table, la clé, l'utilisateur, sans table d'audit centralisée dans un premier temps.

### 3.2 Mécanisme Common_Model

```php
// Dans Common_Model::create()
private function _inject_audit_fields(&$data, $is_create = true) {
    $columns = $this->db->list_fields($this->table);
    $now = date('Y-m-d H:i:s');
    $user = $this->dx_auth->get_username();

    if ($is_create) {
        if (in_array('created_at', $columns) && !isset($data['created_at'])) $data['created_at'] = $now;
        if (in_array('created_by', $columns) && !isset($data['created_by'])) $data['created_by'] = $user;
    }
    if (in_array('updated_at', $columns)) $data['updated_at'] = $now;
    if (in_array('updated_by', $columns)) $data['updated_by'] = $user;
}
```

La liste des colonnes est mise en cache par table pour éviter les requêtes répétées.

### 3.4 Correctif du garde de présence (Lot 5)

Le garde `!isset($data[$field])` doit traiter `FALSE` et `''` comme "non fourni", au même titre que `NULL`, puisqu'aucune de ces trois valeurs n'est une donnée d'audit légitime:

```php
$is_missing = function ($v) { return $v === null || $v === false || $v === ''; };

if (in_array('created_at', $columns, TRUE) && $is_missing($data['created_at'] ?? null)) {
    $data['created_at'] = $now;
}
if (in_array('created_by', $columns, TRUE) && $is_missing($data['created_by'] ?? null) && $username !== NULL) {
    $data['created_by'] = $username;
}
```

Correctif unique, centralisé dans `Common_Model::inject_audit_fields()` — s'applique automatiquement à tout modèle qui appelle cette méthode, y compris ceux qui la surchargent (`Vols_avion_model`, `Vols_planeur_model`, etc.), sans modification par table. Voir Lot 5 pour le détail et les tests associés.

Pour les suppressions:
```php
// Dans Common_Model::delete()
gvv_info("CRUD delete: table=" . $this->table . ", where=" . json_encode($where) . ", by=" . $this->dx_auth->get_username());
```

### 3.3 Colonnes à ajouter par migration

Pour bénéficier de l'audit automatique, chaque lot de tables ajoute les colonnes manquantes:

```sql
ALTER TABLE volsp ADD COLUMN created_by VARCHAR(50) NULL;
ALTER TABLE volsp ADD COLUMN created_at DATETIME NULL;
ALTER TABLE volsp ADD COLUMN updated_by VARCHAR(50) NULL;
ALTER TABLE volsp ADD COLUMN updated_at DATETIME NULL;
```

---

## 4. Plan d'implémentation

### Lot 0 — Socle Common_Model *(prérequis tous les lots)*

- [x] Ajouter `_inject_audit_fields()` dans `Common_Model`
- [x] Appeler dans `create()` avec `$is_create=true`
- [x] Appeler dans `update()` avec `$is_create=false`
- [x] Journaliser les deletes en INFO dans `delete()`
- [x] Tests PHPUnit sur le mécanisme d'injection
- [x] Vérifier la non-régression sur les tables qui n'ont pas les colonnes

### Lot 1 — Finances et vols 🔴

**Migration:** `application/migrations/XXX_audit_finances.php`

Tables: `achats`, `ecritures`, `comptes`, `tickets`, `tarifs`, `volsp`, `volsa`, `vols_decouverte`

- [x] Créer la migration (colonnes `created_at`, `updated_at`, `created_by`, `updated_by` pour les tables manquantes, `created_by`/`updated_by` pour `vols_decouverte`)
- [x] Back-fill `created_at` depuis les champs existants (`date_creation`) quand disponible — one-shot au moment de la migration, sur les données déjà en base
- [x] Pour `achats` et `ecritures`, copier `saisie_par` → `created_by` à la migration
- [x] Vérifier que `Achats_model` et `Vols_planeur_model` ne surchargent pas `create()`/`update()` de façon incompatible
- [x] Test d'intégration migration/backfill (MySQL) ajouté
- [ ] ⚠️ **Régression (§2.3) :** ce lot n'a testé que le backfill ponctuel et l'injection en isolation (modèle appelé directement). Il n'a jamais validé le flux réel formulaire → `form2database()` → `create()`, où le bug `isset(FALSE)` se manifeste. Résultat: `volsa`, `volsp`, `comptes`, `tarifs` n'ont jamais correctement rempli `created_by`/`created_at` pour un enregistrement saisi via l'IHM depuis mars 2026. Voir Lots 5 et 6 pour la correction et le rattrapage.

### Lot 2 — Membres, flotte, formation 🟠

**Migration:** `application/migrations/093_audit_membres_flotte.php`

Tables: `membres`, `licences`, `machinesa`, `machinesp`, `terrains`, `planc`, `pompes`

**Migration:** `application/migrations/094_audit_formation.php`

Tables: `formation_*` (colonnes `created_by`, `updated_by`, `created_at`, `updated_at`; `created_at` sourcé de `date_creation` pour les tables qui l'ont)

- [x] Créer les migrations (idempotentes, raw SQL, rollback)
- [x] Vérifier les modèles concernés: `pompes_model` (create/update/delete surchargés), `licences_model` (create_cotisation), `membres_model` (delete via parent — log géré par Common_Model)
- [x] Patcher `pompes_model`: inject_audit_fields dans create/update, INFO log dans delete
- [x] Patcher `licences_model::create_cotisation`: inject_audit_fields avant db->insert
- [x] Formation models: pas de patch nécessaire — tous délèguent à Common_Model::create/update
- [x] Test d'intégration migration/backfill (MySQL): 6 tests, 88 assertions

### Lot 3 — Documents et email 🟡

- [x] `archived_documents`: ajout `updated_by` + backfill depuis `validated_by`/`uploaded_by`
- [x] `email_lists`: ajout `updated_by` + backfill depuis `created_by`
- [x] `document_types`, `attachments`, `mails`: colonnes complètes (`created_by`, `created_at`, `updated_by`, `updated_at`)
- [x] Modèles vérifiés et patchés: `archived_documents_model::update_document` (inject audit), `email_lists_model` (updated_by + log delete)
- [x] Test d'intégration migration/backfill (MySQL) ajouté

### Lot 4 — Journalisation des suppressions (facultatif v2)

Pour les tables critiques (finances, vols, membres), créer une table d'historique de suppression:

```sql
CREATE TABLE audit_deleted_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(64) NOT NULL,
    record_id VARCHAR(128) NOT NULL,
    deleted_by VARCHAR(50),
    deleted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    record_snapshot TEXT
);
```

- [x] (Décision) Le log applicatif INFO suffit, pas de table dédiée nécessaire
- [x] Implémentation effectuée: suppressions journalisées en INFO via `Common_Model::delete()` + logs explicites sur suppressions métier (`email_lists_model`, `archived_documents_model`)

### Lot 5 — Correction de la régression `isset(FALSE)` ✅

Voir §2.3 et §3.4 pour le diagnostic et le principe du correctif.

- [x] Corriger le garde de présence dans `Common_Model::inject_audit_fields()` (nouvelle méthode `is_audit_value_missing()`, traite `false`/`''`/`null` comme "non fourni")
- [x] Étendre `CommonModelAuditMySqlTest.php` avec deux tests qui reproduisent exactement le bug: `create()`/`update()` appelés avec `$data['created_at'] = false` etc. explicitement (ce que produit réellement `form2database()`), et vérifient qu'un vrai timestamp/username est injecté malgré tout
- [x] Ajouter `VolsAvionVolsPlaneurAuditMySqlTest.php`, test MySQL dédié à `Vols_avion_model::create()` et `Vols_planeur_model::create()` — le bug n'était visible qu'au niveau du contrôleur, pas du modèle isolé
- [x] Ajouter `playwright/tests/audit-fields-vols-avion-create.spec.js` : soumission du vrai formulaire `vols_avion/create` connecté en tant qu'utilisateur, vérification en base que `created_at`/`created_by` sont correctement renseignés. Confirmé comme détecteur valide: le test échoue sans le correctif (`0000-00-00 00:00:00`/`'0'`) et passe avec — c'est le seul niveau qui aurait détecté ce bug, gardé en régression permanente
- [ ] (Optionnel, en second temps, PR séparée) Exclure `created_at`/`created_by`/`updated_at`/`updated_by` de la boucle générique de `formValidation()`/`form2database()` — empêche un client de forger ces valeurs via un POST, et évite qu'un bug similaire ne réapparaisse sous une autre forme. Plus risqué (impacte tous les formulaires de l'appli), à valider avec la suite complète + tests Playwright ciblés sur plusieurs modules avant fusion
- [x] `./run-all-tests.sh` vert (1526/1572, 0 échec, 46 skips pré-existants sans rapport) et suite Playwright ciblée verte (38/38: `obelix-authorization`, `vols-avion-horametre-widget`, `bugfix-vols-avion-form-validation`, `audit-fields-vols-avion-create`)

**Régression trouvée après coup sur update() (signalée par l'utilisateur, vaid=16631) :** le correctif ci-dessus ne gardait le champ `created_at`/`created_by` qu'à l'intérieur de la branche `create()`. Sur `update()`, `form2database()` fournit exactement le même `FALSE` empoisonné pour ces colonnes (toujours pas d'input HTML pour elles), et rien ne l'empêchait de partir tel quel dans l'`UPDATE` SQL — chaque modification d'un vol par vpeignot réécrasait silencieusement `created_by`/`created_at` avec le placeholder zéro. Corrigé dans `inject_audit_fields()` : sur update, si la valeur est manquante/empoisonnée, on `unset()` la clé au lieu de l'écrire, pour que l'`UPDATE` ne touche pas du tout à la colonne.

- [x] `Common_Model::inject_audit_fields()` : branche `else` (update) qui `unset()` `created_at`/`created_by` quand `is_audit_value_missing()`, au lieu de les laisser passer
- [x] `CommonModelAuditMySqlTest::testUpdateNeverOverwritesCreatedFieldsWhenFalseValuesPresent` et `VolsAvionVolsPlaneurAuditMySqlTest::testVolsAvionUpdateNeverOverwritesCreatedFields` — vérifiés dans les deux sens (échouent sans le correctif, passent avec)
- [x] `playwright/tests/audit-fields-vols-avion-create.spec.js` : nouveau test "editing an existing flight ... preserves its original created_at/created_by" — même vérification double-sens en conditions réelles
- [x] Vol vaid=16631 restauré manuellement (`created_by='fpeignot'`, `created_at='2026-07-12 10:52:24'`, valeurs déjà établies par le Lot 6 avant la corruption) — pas re-tiré de `saisie_par`, qui a entre-temps été réécrit à `'vpeignot'` par l'édition (voir §2.3 : `saisie_par` est en fait remis à l'utilisateur courant à chaque sauvegarde, `vols_avion.php:155`, donc pas fiable comme proxy de "créateur d'origine" pour une ligne déjà éditée)
- [x] Non-régression : `./run-all-tests.sh` vert (1534/1580) et suite Playwright ciblée verte (39/39, en mode série pour ce spec — deux tests partagent le même pilote/machine/créneau et se percutaient sur la vérification anti-chevauchement en parallèle)

### Lot 6 — Rattrapage des données existantes ✅

Migration `application/migrations/142_backfill_audit_historique.php` (`config/migration.php` mis à jour à 142), idempotente, sans toucher aux lignes déjà correctement renseignées (`import_of`, `helloasso`, écritures issues de la facturation, etc.).

**6.1 — `created_by` depuis `saisie_par`** (fiable, cf. §2.3), appliqué sur les 8 tables `achats`, `comptes`, `ecritures`, `tarifs`, `tickets`, `volsa`, `volsp`, `vols_decouverte`.

**6.2 — `created_at` par approximation via un champ date proxy**, quand disponible:

| Table | Colonne proxy | Qualité |
|---|---|---|
| `achats` | `date` | correcte |
| `ecritures` | `date_creation` | très bonne (le nom l'indique) |
| `tarifs` | `date` | approximative |
| `tickets` | `date` | approximative |
| `vols_decouverte` | `date_vente` | correcte |
| `volsp` | `vpdate` | approximative (pas d'équivalent à `vahfin` sur cette table) |
| `comptes` | *(aucune)* | **pas de proxy — rien inventé, reste `NULL`** |

Découverte en cours d'implémentation: la colonne proxy elle-même peut porter un placeholder `0000-00-00` (constaté sur 2 lignes `tarifs`, convention de l'appli pour "pas de date définie") — traité comme aussi inutilisable qu'un `NULL`, exclu explicitement (`AND <col> <> '0000-00-00'`), pour ne pas recopier un zéro déguisé.

**6.3 — `created_at` pour `volsa` : approximation affinée à partir de l'heure de fin de vol**

`vadate` + `vahfin` (heure d'atterrissage, décimal-heures) + **20 minutes** (médiane de la fourchette 15-30 min retenue). `volsp` n'a pas de colonne équivalente à `vahfin` (vérifié: seulement `vpcdeb`/`vpcfin`, des horamètres, pas d'heure d'horloge) — reste sur l'approximation générique 6.2 (`vpdate` à minuit).

```sql
UPDATE volsa
SET created_at = DATE_ADD(
    DATE_ADD(vadate, INTERVAL (FLOOR(vahfin) * 3600 + ROUND((vahfin - FLOOR(vahfin)) * 3600)) SECOND),
    INTERVAL 20 MINUTE
)
WHERE (created_at IS NULL OR created_at = '0000-00-00 00:00:00') AND vadate IS NOT NULL;
```

- [x] Migration `application/migrations/142_backfill_audit_historique.php`
- [x] Mise à jour de `application/config/migration.php` (142)
- [x] `updated_by`/`updated_at` mis en miroir de `created_by`/`created_at` une fois rattrapés (même logique que la migration 092)
- [x] `application/tests/mysql/BackfillAuditHistoriqueMigrationTest.php` (6 tests): backfill `created_by` sur les 8 tables, backfill `created_at` par proxy, formule `vadate+vahfin+20min` vérifiée sur une ligne synthétique dédiée (nettoyée après coup), non-fabrication sur `comptes` vérifiée, idempotence, `down()` no-op documenté
- [x] Exécutée pour de vrai sur la base de dev/test: `vaid=16631` (le vol de vpeignot) passe de `created_by='0'`/`created_at='0000-00-00 00:00:00'` à `created_by='fpeignot'`/`created_at='2026-07-12 10:52:24'`
- [x] `./run-all-tests.sh` vert (1532/1578, 0 échec, 46 skips pré-existants sans rapport)

Rapport final (comptages restants après migration, lignes non couvertes par `saisie_par` ni par un proxy de date exploitable — non fabriquées, volontairement laissées telles quelles):

| Table | `created_by` encore vide | `created_at` encore zéro-date |
|---|---|---|
| `achats` | 0 | 0 |
| `comptes` | 44 | 988 |
| `ecritures` | 14 | 0 |
| `tarifs` | 0 | 2 (proxy `date` lui-même à `0000-00-00`) |
| `tickets` | 2 | 0 |
| `volsa` | 13639 (bloc historique/test sans `saisie_par`, cf. §2.3) | 0 |
| `volsp` | 0 | 0 |
| `vols_decouverte` | 970 | 0 |

### Lot 7 — Fusion `saisie_par` / `created_by` (à traiter en dernier, après validation des Lots 5 et 6)

Objectif cible: `created_by` (mécanisme générique, corrigé et fiable) devient le champ canonique sur toutes les tables — y compris les ~37 qui n'ont pas de `saisie_par` — et `saisie_par` est progressivement retiré de l'usage actif, **sans jamais le supprimer dans le cadre de ce lot**.

Démarche volontairement prudente, en étapes séquentielles, pour ne pas régresser sur du code déjà éprouvé:

- [ ] Période d'observation post-Lot 5/6: vérifier (test ou requête ponctuelle) que `saisie_par == created_by` sur toute nouvelle ligne créée dans les 8 tables
- [ ] Recensement exhaustif de tous les lecteurs de `saisie_par` dans le code (modèles, contrôleurs, vues, exports/rapports, factures, requêtes SQL brutes)
- [ ] Migrer ces lecteurs vers `created_by`, un par un, avec tests de non-régression à chaque étape
- [ ] Arrêter l'écriture de `saisie_par` (retirer les `form_hidden('saisie_par', ...)` et la logique contrôleur associée) seulement une fois tous les lecteurs migrés et validés
- [ ] **Ne pas supprimer la colonne `saisie_par`** — décision séparée, ultérieure, hors périmètre de ce plan

---

## 5. Critères de succès

| Critère | Mesure |
|---|---|
| Toutes les tables Lot 1 ont `created_by` renseigné après une création | Test d'intégration PHPUnit |
| Les logs INFO contiennent les suppressions sur les tables critiques | Grep sur les logs après opération |
| Aucune régression sur les tables sans colonnes d'audit | Run `./run-all-tests.sh` vert |
| `reservations`, `procedures`, `calendar` : comportement inchangé | Tests existants passent |
| Un vol créé via le vrai formulaire web a `created_at`/`created_by` correctement renseignés (pas de valeur zéro) | Test Playwright de bout en bout (Lot 5) |
| `inject_audit_fields()` remplit `created_at`/`created_by` même quand `$data` contient explicitement `false` pour ces clés | Test PHPUnit MySQL dédié (Lot 5) |
| Après rattrapage (Lot 6), plus aucune ligne récente (hors `comptes`) n'a `created_by` vide sur les 8 tables du Lot 1 | Requête d'audit avant/après migration |
| `saisie_par` et `created_by` coïncident sur toute nouvelle ligne post-Lot 5 | Test ou requête d'observation (Lot 7) |

---

## 6. Périmètre exclu

- Historisation des valeurs avant modification (diff d'état): hors scope, nécessiterait une table dédiée par ressource.
- Replay d'audit ou interface de consultation d'historique: hors scope.
- Tables système (`ci_sessions`, `migrations`, `users`, `roles`, etc.): non prioritaires, audit natif ou hors périmètre métier.
