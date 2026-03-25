# Plan de journalisation CRUD

**Date:** 2026-03-25
**Statut:** 🟢 Lots 0, 1, 2, 3 et 4 implémentés

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
- [x] Back-fill `created_at` depuis les champs existants (`date_creation`) quand disponible
- [x] Pour `achats` et `ecritures`, copier `saisie_par` → `created_by` à la migration
- [x] Vérifier que `Achats_model` et `Vols_planeur_model` ne surchargent pas `create()`/`update()` de façon incompatible
- [x] Test d'intégration migration/backfill (MySQL) ajouté

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

---

## 5. Critères de succès

| Critère | Mesure |
|---|---|
| Toutes les tables Lot 1 ont `created_by` renseigné après une création | Test d'intégration PHPUnit |
| Les logs INFO contiennent les suppressions sur les tables critiques | Grep sur les logs après opération |
| Aucune régression sur les tables sans colonnes d'audit | Run `./run-all-tests.sh` vert |
| `reservations`, `procedures`, `calendar` : comportement inchangé | Tests existants passent |

---

## 6. Périmètre exclu

- Historisation des valeurs avant modification (diff d'état): hors scope, nécessiterait une table dédiée par ressource.
- Replay d'audit ou interface de consultation d'historique: hors scope.
- Tables système (`ci_sessions`, `migrations`, `users`, `roles`, etc.): non prioritaires, audit natif ou hors périmètre métier.
