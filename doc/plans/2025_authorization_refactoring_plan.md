# GVV Authorization System Refactoring Plan

**Document Version:** 2.7
**Date:** 2025-01-08 (Updated: 2026-03-04)
**Status:** Phase M2 In Progress — 20 controllers migrated, 5 test users with Playwright coverage
**Author:** Claude Code Analysis
**Based on:** PRD v2.0 - Code-Based Permission Management with Per-User Progressive Migration

---

## Executive Summary

**Major Architecture Change (v2.0):** Following analysis of the implementation (v1.0), the permission management approach has been revised. Instead of managing ~300 permissions in the database (`role_permissions` table), permissions will now be **declared directly in controller code** via declarative API calls. This simplifies maintenance, improves code-permission coherence, and reduces complexity.

**Migration Strategy (v2.3 - Updated):** Per-user progressive deployment with global flag

### 🚀 **Path 1: Per-User Progressive Migration (RECOMMENDED - 3-4 weeks)**
- ✅ **Granular testing**: Start with 2-3 dev users, expand to 5-10 pilot users
- ✅ **Zero risk**: Test in production with real users, others unaffected
- ✅ **Multi-level rollback**: Per-user, pilot group, or global rollback
- ✅ **PRD-compliant**: Follows Section 6.1 (Per-User Progressive Migration)
- ✅ **Global cutover**: Single flag flip moves all users when ready
- **Mechanism**: New table `use_new_authorization` lists users on new system
- **Phases**: M1-M5 (Per-user testing → Global migration → Cleanup)
- **Timeline**: 3-4 weeks to production (includes 1-2 week pilot)
- **Current Status**: Phase M2 in progress — test users enrolled, Playwright tests covering 5 user profiles

### ⏳ **Path 2: Controller Code Migration (OPTIONAL - 10+ weeks)**
- Code cleanliness: All 53 controllers declare permissions in code
- Long-term maintenance: Permissions visible in controller code
- Performance optimization: Remove database permission lookups
- **Phases**: 8-12 (Controller migration phases)
- **Timeline**: ~10 weeks additional work
- **Status**: Can be done AFTER production deployment via Path 1

### 🔴 **Path 3: Qualification Bitmap Migration (MANDATORY — before Phase M3)**
- Migrate operational qualifications from `membres.mniveaux` bitmap to `user_roles_per_section`
- `user_roles_per_section` becomes the **exclusive** source of truth for all authorization decisions and form selectors — no code may read `mniveaux` to make an access control or selection decision
- Section-aware qualifications (instructor in section 1, not in section 2)
- Audit trail (granted_at / revoked_at)
- **Phases**: 13 (Qualification migration)
- **Timeline**: ~2-3 weeks — must complete before M3 (production pilot)
- **Status**: Blocking M3 — required before production pilot
- **Precedent**: `inst_selector()` already migrated to use `user_roles_per_section`
- **Script sync**: `bin/create_test_users.sh` and `_create_test_gaulois_users()` must produce identical `user_roles_per_section` entries — divergence is a defect

**Recommendation**: Use **Path 1** to go to production quickly, **Path 3** (qualification consolidation) is mandatory before M3, and optionally **Path 2** for code improvements.

**Legacy System Status:** The current implementation (Phases 0-6) remains functional and will be maintained during the transition. The `role_permissions` table will be deprecated but preserved for rollback capability.

---

## Current Status Summary

### ✅ Completed Phases (0-7) - Infrastructure Ready

**Phase 0-2: Infrastructure & Data** ✅
- Database schema migrated (042_authorization_refactoring.php)
- Data migration complete (043_populate_authorization_data.php)
- Tables: `types_roles`, `role_permissions`, `data_access_rules`, `user_roles_per_section`, `authorization_audit_log`
- 24 default data access rules created
- Role translations (FR/EN/NL) added
- Migration 048: table `use_new_authorization` pour migration per-user
- Migration 071: remplacement trigger par DEFAULT CURRENT_TIMESTAMP

**Phase 3: Authorization Library** ✅
- `Gvv_Authorization` library (480 lines) - Core authorization logic
- `Authorization_model` (388 lines) - Database operations
- Feature flag in `gvv_config.php` (disabled in production)
- Unit tests: 26 tests passing, 52 assertions (100% pass rate)
- Test bootstraps enhanced with proper CI mocks

**Phase 4: UI Implementation** ✅
- `Authorization` controller (445 lines, 11 endpoints)
- User roles management interface (DataTables, AJAX)
- Roles management interface
- Data access rules interface
- Audit log viewer
- Language translations: 207 keys (FR/EN/NL)
- Menu integration: Admin → Club Admin → Gestion des autorisations
- **Note v2.0**: Permissions tab will be removed in Phase 10

**Phase 5: Testing Framework** ✅
- Unit tests: 26/26 passing (100%)
- Integration tests: 12/12 passing (100%)
- Overall test suite: 213/213 passing
- Test bootstraps with CI mocks
- Database transaction isolation

**Phase 6: Gvv_Controller Base Class** ✅
- Base controller class created (384 lines)
- Foundation for code-based permissions (v2.0)
- Migration 046 created (authorization_comparison_log table)
- **Note v2.0**: Dual-mode dashboard and pilot testing made optional

**Phase 7: Code-Based Permissions API** ✅
- `Gvv_Authorization` extended with new methods (480 → 632 lines, +152 lines)
- `require_roles()`, `allow_roles()`, `can_edit_row()` implemented
- Helper methods added to `Gvv_Controller` (+105 lines)
- Unit tests: 15 new tests added (41 total, 100% pass rate)
- Developer documentation created: `doc/development/code_based_permissions.md` (647 lines)
- All tests passing (213 total)
- **Commit**: 4bbfbab "Authorisations phase 7"

### ⏳ Phase M2 In Progress - Controller Migration & Testing

**Controllers migrés (20 controllers avec `require_roles()`)** ✅
- `welcome` → `['user']` (constructor), `['tresorier']` (compta), `['ca']` (ca)
- `vols_planeur` → `['user']`
- `vols_avion` → `['user']`
- `planeur` → `['user']`
- `avion` → `['user']`
- `alarmes` → `['user']`
- `tickets` → `['user']`
- `tarifs` → `['user']`
- `membre` → `['user']`
- `sections` → `['user']`
- `procedures` → `['user']`
- `terrains` → `['ca']`
- `achats` → `['ca']`
- `comptes` → `['tresorier']`
- `configuration` → `['bureau']`
- `document_types` → `['ca']`
- `rapports` → `['ca']`
- `compta` → `['tresorier']` (sauf `mon_compte` et `journal_compte`)
- `calendar` → `['user']` (via `dx_auth->require_roles()`)
- `reservations` → `['user']` (via `dx_auth->require_roles()`)
- `presences` → `['user']` (via `dx_auth->require_roles()`)

**Controllers avec code prêt mais désactivé (`use_new_auth = FALSE`)** :
- `licences` → `['ca']` — code prêt, désactivé
- `programmes` → utilise `Formation_access` à la place
- `email_lists` → `['secretaire', 'ca']` — code prêt, désactivé

**Test users "Gaulois" enrolled in `use_new_authorization`** ✅
- `asterix` — user simple (Planeur + Général)
- `obelix` — planchiste (Planeur) + auto_planchiste (ULM) + user (Général)
  - En tant que planchiste (Planeur) : peut créer des vols, modifier tous les vols, accéder à la planche automatique (`vols_planeur/plancheauto`, `vols_planeur/plancheauto_select`)
  - En tant que auto_planchiste (ULM) : peut créer des vols ULM et modifier ses propres vols uniquement, pas d'accès à `plancheauto`
- `abraracourcix` — user (4 sections) + CA (section Avion uniquement, selon admin.php) + instructeur (FI avion, section Avion)
  - ⚠️ **Divergence à corriger** : `bin/create_test_users.sh` assigne le rôle CA à toutes les sections, `admin.php` le restreint à la section Avion via `ca_sections`. La définition de référence doit être arrêtée et les deux scripts alignés (Phase 13.0)
- `goudurix` — auto_planchiste (Avion) + trésorier (Général)
  - En tant que auto_planchiste (Avion) : peut créer des vols avion et modifier ses propres vols uniquement, pas d'accès à `plancheauto`
- `panoramix` — club-admin (toutes sections)

**Playwright authorization test suite** ✅ (8 fichiers, 4 profils couverts)
- `asterix-authorization.spec.js` + `asterix-recursive-authorizations.spec.js`
- `obelix-authorization.spec.js` + `obelix-recursive-authorizations.spec.js`
- `abraracourcix-authorization.spec.js` + `abraracourcix-recursive-authorizations.spec.js`
- `goudurix-authorization.spec.js` + `goudurix-recursive-authorizations.spec.js`

**Manquant** : tests Playwright pour `panoramix` (admin)

---

## Upcoming Phases (v2.0)

### Phase 7: Code-Based Permissions API (v2.0) ✅ COMPLETE

**Objectives**: Implement the API for declaring permissions directly in controller code

**Tasks**:
- [x] **7.1** Extend `Gvv_Authorization` with new methods:
  - `require_roles($roles, $section_id, $replace=true)` - Require specific roles
  - `allow_roles($roles, $section_id)` - Allow additional roles (cumulative)
  - `can_edit_row($table, $row_data, $access_type)` - Row-level security check
  - Internal state management for access decisions
- [x] **7.2** Add helper methods to `Gvv_Controller`:
  - `require_roles()` - Wrapper for authorization library
  - `allow_roles()` - Wrapper for authorization library
  - `can_edit_row()` - Wrapper for row-level checks
- [x] **7.3** Unit tests for new API:
  - Test `require_roles()` with various role combinations
  - Test `allow_roles()` additive behavior
  - Test multi-level permissions (constructor + method)
  - Test row-level security with different scopes
- [x] **7.4** Documentation:
  - Developer guide for using the new API
  - Code examples for common patterns (own vs all, restricted actions)
  - Migration guide from database permissions to code

**Actual Effort**: 1 day (2025-10-24)

**Deliverables** (All Complete):
- ✅ Extended `Gvv_Authorization.php` (+152 lines, now 632 lines)
- ✅ Extended `Gvv_Controller.php` (+105 lines)
- ✅ Unit tests (+15 tests, 41 total, 100% pass rate)
- ✅ Developer documentation (`doc/development/code_based_permissions.md`, 647 lines)

---

### Phase 8: Controller Migration Pilot (v2.0) ✅ SUBSTANTIALLY COMPLETE

**Objectives**: Migrate 5-10 simple controllers to code-based permissions

**Status**: 20 controllers already migrated (far exceeding the 7 planned). All pilot controllers done plus many more.

**Pilot Controllers** (simple, low-risk):
- ✅ `sections` → `['user']`
- ✅ `terrains` → `['ca']`
- ✅ `alarmes` → `['user']`
- ✅ `presences` → `['user']` (via dx_auth)
- ⚠️ `licences` → `['ca']` (code ready, `use_new_auth = FALSE`)
- ✅ `tarifs` → `['user']`
- ✅ `calendar` → `['user']` (via dx_auth)

**Tasks**:
- [x] **8.1** Add `require_roles()` in constructor for pilot controllers
- [ ] **8.2** Create mapping document (old → new) — non réalisé, documentation informelle
- [x] **8.3** Integration testing via Playwright authorization tests (4 profils utilisateur)
- [x] **8.4** Mark migrated controllers with `// Authorization: Code-based (v2.0)`

**Note**: `licences` a le code prêt mais est désactivé (`use_new_auth = FALSE`). À investiguer.

---

### Phase 9: Complex Controllers & Exceptions (v2.0) 🔵 NEW

**Objectives**: Migrate controllers with complex authorization patterns

**Complex Controllers**:
- `membre` (own vs all, multiple exceptions)
- `compta` (own vs bureau vs tresorier)
- `vols_planeur` (auto_planchiste vs planchiste, delete restrictions)
- `vols_avion` (same as vols_planeur)
- `factures` (user vs tresorier)
- `tickets` (user vs ca)
- `attachments` (user vs ca)

**Tasks**:
- [ ] **9.1** Migrate `membre` controller:
  - Constructor: `require_roles(['user'])`
  - `edit($id)`: Check if own user, else `require_roles(['ca'])`
  - `register()`: `require_roles(['ca'])`
  - `delete()`: `require_roles(['ca'])`
- [ ] **9.2** Migrate `compta` controller:
  - Constructor: `require_roles(['tresorier'])`
  - `mon_compte()`: `allow_roles(['user'])`
  - `journal_compte($id)`: Check if own, else `require_roles(['bureau'])`
- [ ] **9.3** Migrate `vols_planeur` controller:
  - Constructor: `require_roles(['planchiste'])`
  - `page()`, `edit()`, `create()`: `allow_roles(['auto_planchiste'])` + row-level check (own flights only for auto_planchiste)
  - `plancheauto()`, `plancheauto_select()`: `require_roles(['planchiste'])` (non accessible aux auto_planchiste)
  - `delete()`: Keep `require_roles(['planchiste'])` (no auto)
  - **Comportements attendus** :
    - planchiste (ex: obelix en section Planeur) : créer des vols, modifier tous les vols, accès à `plancheauto`/`plancheauto_select`
    - auto_planchiste (ex: obelix en section ULM, goudurix en section Avion) : créer des vols, modifier ses propres vols uniquement, pas d'accès à `plancheauto`
- [ ] **9.4** Migrate other complex controllers (factures, tickets, attachments)
- [ ] **9.5** Document exception patterns:
  - Pattern 1: Own vs All (membre, compta, tickets)
  - Pattern 2: Restricted actions (vols_planeur delete)
  - Pattern 3: Cumulative roles (auto_planchiste + planchiste)
- [ ] **9.6** Comprehensive testing:
  - Test all exception scenarios
  - Verify row-level security
  - Test edge cases (user editing own vs others)

**Estimated Effort**: 5-7 days

**Deliverables**:
- 7 complex controllers migrated (~200 lines changes)
- Exception patterns documentation (`doc/authorization_exception_patterns.md`)
- Extended integration tests (+30 tests)
- Row-level security test suite

---

### Phase 10: Remaining Controllers & Database Cleanup (v2.0) 🔵 NEW

**Objectives**: Complete migration of all controllers and deprecate `role_permissions` table

**Remaining Controllers** (~35 controllers):
- Administration: `admin`, `backend`, `config`, `configuration`, `migration`, `dbchecks`
- Financial: `comptes`, `achats`, `rapprochements`, `plan_comptable`
- Reporting: `rapports`, `reports`, `historique`
- Flight operations: `vols_decouverte`, `planeur`, `avion`, `event`
- Technical: `openflyers`, `FFVV`, `mails`
- Other: `pompes`, `procedures`, `tools`, etc.

**Tasks**:
- [ ] **10.1** Batch migrate simple controllers (week 1):
  - Admin controllers: `require_roles(['club-admin'])`
  - Financial controllers: `require_roles(['tresorier'])`
  - ~15 controllers
- [ ] **10.2** Migrate medium complexity controllers (week 2):
  - Reporting, flight operations, technical
  - ~15 controllers
- [ ] **10.3** Final controllers (week 3):
  - Remaining misc controllers
  - ~5 controllers
- [ ] **10.4** Verification phase:
  - Run full test suite (unit + integration + manual)
  - Verify all 53 controllers migrated
  - Check audit logs for any missed permissions
- [ ] **10.5** Database deprecation:
  - Create migration `047_deprecate_role_permissions.php`:
    - Rename `role_permissions` → `role_permissions_legacy`
    - Add migration timestamp and reason
    - Preserve data for rollback
  - Update `Authorization_model` to skip legacy table
  - Remove `add_permission()`, `remove_permission()` methods
- [ ] **10.6** UI updates:
  - Remove "Permissions" tab from authorization dashboard
  - Update documentation to reflect code-based approach
  - Add banner: "Permissions are now managed in code"

**Estimated Effort**: 15-20 days (3 weeks)

**Deliverables**:
- All 53 controllers migrated (~500 lines changes)
- Migration 047 (deprecate role_permissions)
- Complete migration report (`doc/phase10_complete_migration_report.md`)
- Updated authorization dashboard (removed permissions tab)
- Administrator communication document

---

### Phase 11: Legacy System Cleanup (v2.0) 🔵 NEW

**Objectives**: Remove deprecated code and finalize transition

**Tasks**:
- [ ] **11.1** Code cleanup:
  - Remove `_role_has_permission()` from `Gvv_Authorization` (no longer needed)
  - Remove database permission checking logic
  - Clean up unused methods in `Authorization_model`
  - Update feature flag documentation
- [ ] **11.2** Documentation updates:
  - Mark PRD v1.0 sections as "Legacy"
  - Update architecture diagrams
  - Create "Migration Complete" announcement
  - Update README.md with v2.0 architecture
- [ ] **11.3** Performance optimization:
  - Remove permission caching for database lookups
  - Optimize role checking (already cached in session)
  - Benchmark before/after
- [ ] **11.4** Training & Communication:
  - Create administrator guide for v2.0
  - Developer training session (code-based permissions)
  - Update onboarding documentation
- [ ] **11.5** Final testing:
  - Full regression test suite
  - Security audit (penetration testing)
  - Performance benchmarks
  - User acceptance testing

**Estimated Effort**: 5-7 days

**Deliverables**:
- Cleaned codebase (-300 lines legacy code)
- Performance benchmarks report
- Administrator & developer training materials
- Security audit report
- Project retrospective document

---

### Phase 12: Production Deployment (v2.0) 🔵 NEW

**Objectives**: Deploy code-based authorization system to production

**Pre-Deployment Checklist**:
- [ ] All 53 controllers migrated and tested
- [ ] Full test suite passing (unit + integration + e2e)
- [ ] Performance benchmarks acceptable
- [ ] Database backup complete
- [ ] Rollback plan documented and tested
- [ ] Administrator training complete
- [ ] User communication sent

**Deployment Steps**:
- [ ] **12.1** Staging deployment:
  - Deploy to staging environment
  - Run smoke tests
  - 24-hour monitoring
- [ ] **12.2** Production deployment:
  - Database migration 047 (rename role_permissions)
  - Deploy code changes
  - No feature flag needed (code-based is the new default)
- [ ] **12.3** Post-deployment monitoring (48 hours intensive):
  - Monitor error logs
  - Check audit logs for access denied
  - Monitor performance metrics
  - User feedback collection
- [ ] **12.4** Stabilization (week 1):
  - Address any issues
  - Performance tuning
  - Documentation updates based on feedback
- [ ] **12.5** Final sign-off:
  - Stakeholder approval
  - Project closure
  - Archive legacy code and documentation

**Estimated Effort**: 3-5 days deployment + 1 week stabilization

**Deliverables**:
- Production deployment successful
- Post-deployment report
- Lessons learned document
- Final project retrospective

---

### Phase 13: Qualification Bitmap Migration (v2.7) 🔴 MANDATORY — Blocking M3

**Objectives**: Faire de `user_roles_per_section` l'unique source de vérité pour toutes les décisions d'autorisation et tous les sélecteurs de formulaires. Après cette phase, aucun code ne doit lire `membres.mniveaux` pour prendre une décision d'accès ou de sélection.

**Critère de sortie** : tous les contrôles d'accès et sélecteurs qui lisaient `mniveaux` sont migrés. Le champ `mniveaux` ne contient plus que des bits purement informatifs.

#### Context: Legacy Bitmap System

The `membres.mniveaux` field is a bitmap encoding ~20 qualifications/responsibilities:

```
Bit  Constant        Value     Current Usage
───  ──────────────  ────────  ─────────────────────────────────
 0   INTERNET        1         Responsabilité (obsolète)
 1   PRESIDENT       2         Responsabilité
 2   VICE_PRESIDENT  4         Responsabilité
 3   TRESORIER       8         Responsabilité → déjà dans types_roles
 4   SECRETAIRE      16        Responsabilité
 5   SECRETAIRE_ADJ  32        Responsabilité
 6   CA              64        Responsabilité → déjà dans types_roles
 7   CHEF_PILOTE     128       Qualification opérationnelle
 8   VI_PLANEUR      256       Qualification vol (visiteur instruction)
 9   VI_AVION        512       Qualification vol
10   MECANO          1024      Qualification → déjà dans types_roles (id=12)
11   PILOTE_PLANEUR  2048      Qualification informative
12   PILOTE_AVION    4096      Qualification informative
13   REMORQUEUR      8192      Qualification opérationnelle (sélecteur vols)
14   PLIEUR          16384     Qualification informative
15   ITP             32768     Instructeur → déjà migré via inst_selector()
16   IVV             65536     Instructeur → déjà migré via inst_selector()
17   FI_AVION        131072    Instructeur → déjà migré via inst_selector()
18   FE_AVION        262144    Instructeur → déjà migré via inst_selector()
19   TREUILLARD      524288    Qualification opérationnelle (sélecteur vols)
20   CHEF_DE_PISTE   1048576   Qualification opérationnelle
```

A second bitmap `membres.macces` ("Responsabilités") exists but is only displayed/saved in the member form, no access control usage.

#### Current Bitmap Usage in Code

| Usage | Files | Pattern |
|-------|-------|---------|
| **Sélecteurs formulaires de vol** | `membres_model.php` → `qualif_selector()` | `(mniveaux & $level) != 0` |
| **Sélecteur instructeurs** | `membres_model.php` → `inst_selector()` | **Déjà migré** → `user_roles_per_section` (id=11) |
| **Contrôle accès formation** | `Formation_access.php` → `is_instructeur()` | `(mniveaux & (ITP\|IVV\|FI_AVION\|FE_AVION)) != 0` |
| **Contrôle accès CA formation** | `Formation_access.php` → `can_manage_programmes()` | `(mniveaux & CA) != 0` |
| **Listes email** | `config/program.php` | `(mniveaux & ($instructeurs)) != 0` |
| **Fiche membre** | `controllers/membre.php` | `int2array()` / `array2int()` checkboxes |
| **Backup/restore** | `controllers/admin.php` | `roles_bits` → `mniveaux` |

#### Migration Strategy: What Moves, What Stays

**Phase 13A — Qualifications à migrer vers `user_roles_per_section`** (servent au contrôle d'accès ou aux sélecteurs de formulaires) :

| Bitmap | Nouveau rôle `types_roles` | Justification |
|--------|---------------------------|---------------|
| ITP, IVV, FI_AVION, FE_AVION | `instructeur` (id=11) | **Déjà migré** — inst_selector() utilise user_roles_per_section |
| REMORQUEUR | `remorqueur` (à créer, id=13) | Sélecteur dans formulaires de vol (pilrem_selector) |
| TREUILLARD | `treuillard` (à créer, id=14) | Sélecteur dans formulaires de vol (treuillard_selector) |
| MECANO | `mecano` (id=12) | **Déjà dans types_roles** — migrer les données bitmap |
| CHEF_PILOTE | `chef_pilote` (à créer, id=15) | Qualification opérationnelle |
| CHEF_DE_PISTE | `chef_de_piste` (à créer, id=16) | Qualification opérationnelle |

**Phase 13B — Qualifications qui restent dans `membres.mniveaux`** (purement informatives, pas de contrôle d'accès) :

| Bitmap | Raison |
|--------|--------|
| PILOTE_PLANEUR (2048) | Informatif, pas de sélecteur ni contrôle d'accès |
| PILOTE_AVION (4096) | Informatif, idem |
| VI_PLANEUR (256) | Qualification de vol, pas de contrôle d'accès |
| VI_AVION (512) | Idem |
| PLIEUR (16384) | Qualification technique informative |

**Phase 13C — Responsabilités déjà couvertes par `types_roles`** (supprimer du bitmap) :

| Bitmap | Rôle types_roles existant | Action |
|--------|--------------------------|--------|
| CA (64) | `ca` (id=6) | Supprimer du bitmap, utiliser le rôle |
| TRESORIER (8) | `tresorier` (id=8) | Idem |
| PRESIDENT, VICE_PRESIDENT, SECRETAIRE, SECRETAIRE_ADJ | Pas de rôle dédié | Rester dans bitmap ou migrer vers un champ texte "fonction_bureau" |

#### Avantages

1. **Source unique de vérité** : plus de double maintenance entre bits et rôles
2. **Qualifications par section** : un instructeur planeur peut ne pas être instructeur avion (impossible avec un bitmap global)
3. **Audit** : `user_roles_per_section` offre `granted_at` / `revoked_at` automatiquement
4. **Cohérence** : `inst_selector()` est déjà migré, les autres sélecteurs doivent suivre
5. **Simplification du code** : remplacer les opérations bit à bit par des requêtes relationnelles lisibles

#### Tasks

- [ ] **13.0** Synchronisation et cohérence des scripts de création des utilisateurs de test :
  - Décider de la définition de référence pour **abraracourcix** : rôle CA en section Avion uniquement (`admin.php`) ou dans toutes les sections (`bin/create_test_users.sh`) — les deux doivent être alignés
  - Aligner `bin/create_test_users.sh` sur la définition choisie
  - Ajouter dans le commentaire d'en-tête des deux fichiers : _"Cette implémentation doit rester synchronisée avec son équivalent. Toute modification doit être répercutée dans les deux sources."_
  - Créer un test PHPUnit qui compare les `user_roles_per_section` produits par les deux procédures pour chaque utilisateur de test et échoue en cas de divergence
  - Après migration Phase 13 : supprimer les bits de contrôle d'accès de `mniveaux` dans les deux scripts, ne conserver que les bits informatifs

- [ ] **13.1** Créer les nouveaux rôles dans `types_roles` :
  - Migration SQL : INSERT `remorqueur` (13), `treuillard` (14), `chef_pilote` (15), `chef_de_piste` (16)
  - Ajouter traductions FR/EN/NL dans `gvv_lang.php`

- [ ] **13.2** Migration des données bitmap → `user_roles_per_section` :
  - Script SQL qui pour chaque membre actif, lit `mniveaux` et crée les entrées correspondantes
  - Exemple : `(mniveaux & 8192) != 0` → INSERT rôle `remorqueur` pour la section du membre
  - Déterminer la section : utiliser le compte 411 du membre (même logique que `inst_selector`)
  - Gérer les membres multi-sections

- [ ] **13.3** Migrer `qualif_selector()` vers le modèle `user_roles_per_section` :
  - Remplacer `qualif_selector($key, $level)` par des méthodes spécifiques basées sur les rôles
  - Créer `remorqueur_selector()` et `treuillard_selector()` sur le modèle de `inst_selector()`
  - Identifier et mettre à jour tous les appelants de `qualif_selector()`

- [ ] **13.4** Migrer `Formation_access` :
  - `is_instructeur()` : remplacer `(mniveaux & flags)` par vérification du rôle `instructeur` dans `user_roles_per_section`
  - `can_manage_programmes()` : remplacer `(mniveaux & CA)` par vérification du rôle `ca` dans `user_roles_per_section`

- [ ] **13.5** Migrer les requêtes de listes email (`program.php`) :
  - Remplacer `(mniveaux & ($instructeurs)) != 0` par JOIN sur `user_roles_per_section`
  - Mettre à jour `listes_de_requetes` dans `program.php` et `program.example.php`

- [ ] **13.6** Mettre à jour la fiche membre :
  - Retirer des checkboxes les qualifications migrées (remorqueur, treuillard, mecano, chef_pilote, chef_de_piste)
  - Retirer les responsabilités déjà dans types_roles (CA, trésorier)
  - Conserver les qualifications informatives (PILOTE_PLANEUR, PILOTE_AVION, VI_*, PLIEUR)
  - Afficher un lien vers la gestion des rôles pour les qualifications migrées
  - Mettre à jour `int2array()` / `array2int()` pour ne gérer que les bits restants

- [ ] **13.7** Mettre à jour backup/restore (`admin.php`) :
  - Le champ `roles_bits` doit refléter les bits restants uniquement
  - La restauration doit aussi recréer les entrées `user_roles_per_section`

- [ ] **13.8** Tests :
  - Tests unitaires pour les nouveaux sélecteurs
  - Test de migration des données (vérifier que les bits sont correctement convertis en rôles)
  - Test de non-régression des formulaires de vol
  - Test Playwright : vérifier les dropdowns instructeur/remorqueur/treuillard

- [ ] **13.9** Nettoyage :
  - Supprimer les constantes bitmap obsolètes de `constants.php` (celles migrées)
  - Mettre à jour la documentation
  - Supprimer `qualif_selector()` si plus aucun appelant

**Estimated Effort**: 10-15 days (2-3 weeks)

**Deliverables**:
- Scripts de test synchronisés (`bin/create_test_users.sh` ↔ `_create_test_gaulois_users()`) avec test de cohérence
- Migration SQL (nouveaux rôles + conversion bitmap → user_roles_per_section)
- Sélecteurs migrés (remorqueur_selector, treuillard_selector)
- Formation_access réécrit sans bitmap
- Listes email migrées
- Fiche membre simplifiée
- Tests de non-régression

---

## Project Status Dashboard (v2.5)

### Development Phases

| Phase | Status | Progress | Notes |
|-------|--------|----------|-------|
| **0-6: Legacy System** | ✅ Complete | 100% | Database, UI, dual-mode ready |
| **7: Code-Based API** | ✅ Complete | 100% | Completed 2025-10-24 |
| **8: Pilot Migration** | ✅ ~90% | 90% | 20 controllers migrés (7 prévus), 3 désactivés |
| **9: Complex Controllers** | ⏳ Partial | ~40% | `compta`, `welcome` migrés; `membre`, `vols_planeur`, `vols_avion` partiels |
| **10: Full Migration** | 🔵 Planned | 0% | ~30 controllers restants sans `require_roles()` |
| **11: Cleanup** | 🔵 Planned | 0% | Remove legacy code (Optional) |
| **12: Production Deploy** | 🔵 Planned | 0% | Final deployment (Optional) |
| **13: Qualification Migration** | 🔴 Blocking M3 | 0% | Bitmap → user_roles_per_section — obligatoire avant M3 |

### Migration Phases (Feature Flag Based)

| Phase | Status | Duration | Flag Status | User Impact | Notes |
|-------|--------|----------|-------------|-------------|-------|
| **M1: Preparation** | ✅ Complete | - | FALSE | None | Infrastructure ready |
| **M2: Role Setup & Dev Testing** | ⏳ Current | en cours | FALSE | Test users only | 5 Gaulois test users enrolled, Playwright tests OK |
| **Phase 13: Bitmap Migration** | 🔴 Blocking M3 | avant M3 | FALSE | Dev only | Migrate mniveaux → user_roles_per_section, fix script divergence |
| **M3: Production Pilot** | 🔵 Next | 1-2 weeks | FALSE | 5-10 pilot users | Validate with real users — requires Phase 13 complete |
| **M4: Global Migration** | 🔵 Planned | 1 week | TRUE | All users | Flag flip |
| **M5: Cleanup** | 🔵 Future | 1-2 days | TRUE (hardcoded) | None | Remove flag (optional) |

### Detailed Metrics

| Metric | Target | Current | Status |
|--------|--------|---------|--------|
| **Legacy System (Phases 0-6)** | 100% | 100% | 🟢 Complete |
| **Code-Based API (Phase 7)** | 100% | 100% | 🟢 Complete |
| **Controllers migrés** | 53 | 20 (+3 prêts mais désactivés) | 🟡 38% migrés |
| **Test users enrolled** | 5 Gaulois | 5 Gaulois | 🟢 Complete |
| **Playwright auth tests** | 5 profils | 4 profils (manque panoramix) | 🟡 80% |
| **Feature Flag Status** | TRUE (prod) | FALSE (all envs) | 🔴 Awaiting M3 |
| **Production user roles** | All (~400) | Test users only | 🔴 Not started |
| **Global flag `use_new_authorization`** | TRUE | FALSE | Per-user via table |

---

## Risk Assessment (Updated v2.0)

| Risk | Likelihood | Impact | Mitigation | Status |
|------|------------|--------|------------|--------|
| **Permission mismatch during v2.0 migration** | Medium | High | Comprehensive mapping document, extensive testing | 🟡 Active |
| **Missed controller in migration** | Low | High | Migration checklist, automated verification script | 🟡 Planned |
| **Access denial for valid users** | Low | Critical | Progressive rollout (7→7→35 controllers), rollback | 🟢 Managed |
| **Performance degradation** | Very Low | Medium | No DB lookups for permissions = faster | 🟢 Low Risk |
| **Code-permission divergence** | Low | Medium | Code review, documentation, developer training | 🟡 Planned |
| **Legacy code interaction** | Medium | Medium | Careful testing, preserve `data_access_rules` | 🟡 Active |
| **Divergence scripts de test** | High | Medium | Scripts `bin/create_test_users.sh` et `admin.php` produisent des rôles différents pour `abraracourcix` — corriger en Phase 13.0, ajouter test de cohérence automatique | 🔴 Known defect |

**Current Risk Level**: 🟡 Medium - Significant architecture change in progress, thorough planning reduces risk

---

## Key Files (Updated v2.0)

### Application Code - Legacy System (Phases 0-6)
```
application/
├── core/
│   └── Gvv_Controller.php                 (384 lines) ✅ Phase 6 - Will be extended in Phase 7
├── controllers/
│   ├── authorization.php                  (445 lines) ✅ Phase 4
│   └── [53 controllers]                   🔵 To migrate in Phases 8-10
├── libraries/
│   └── Gvv_Authorization.php              (480 lines) ✅ Phase 3 - Will be extended in Phase 7
├── models/
│   └── Authorization_model.php            (388 lines) ✅ Phase 3 - Will be cleaned in Phase 11
├── migrations/
│   ├── 042_authorization_refactoring.php  ✅ Phase 2 - Schema
│   ├── 043_populate_authorization_data.php ✅ Phase 2 - Data (~300 permissions)
│   ├── 046_dual_mode_support.php          ✅ Phase 6 - Comparison log (optional)
│   └── 047_deprecate_role_permissions.php 🔵 Phase 10 - Rename role_permissions
├── views/authorization/
│   ├── user_roles.php, roles.php          ✅ Phase 4 - User/role management
│   ├── data_access_rules.php, audit_log.php ✅ Phase 4 - Row-level & audit
│   └── permissions.php                    ⚠️ To be removed in Phase 10
└── language/*/gvv_lang.php                (+207 keys) ✅ Phase 4
```

### New Code - v2.0 System (Phases 7-12)
```
application/
├── libraries/
│   └── Gvv_Authorization.php              (+152 lines) ✅ Phase 7 - New API methods (632 lines total)
├── core/
│   └── Gvv_Controller.php                 (+105 lines) ✅ Phase 7 - Helper wrappers
├── controllers/
│   ├── sections.php, terrains.php, etc.   (~50 lines changes) 🔵 Phase 8 - Pilot
│   ├── membre.php, compta.php, etc.       (~200 lines changes) 🔵 Phase 9 - Complex
│   └── [35 remaining controllers]         (~500 lines changes) 🔵 Phase 10 - Batch
├── views/authorization/
│   └── permissions.php                    ❌ Removed in Phase 10
└── tests/
    ├── unit/libraries/Gvv_AuthorizationTest.php  (+15 tests, ~250 lines) ✅ Phase 7
    └── integration/AuthorizationIntegrationTest.php  (+30 tests) 🔵 Phase 9
```

**Legacy Lines**: ~3,500 lines (Phases 0-6, essential code)
**New Code (Phase 7)**: ~507 lines (Gvv_Authorization: +152, Gvv_Controller: +105, Tests: +250)
**New Code (Phases 8-12)**: ~750 lines (controller migrations)
**Removed**: ~300 lines (deprecated permissions code)
**Net Change**: ~4,457 lines final (v2.0 system)

### Testing
```
application/tests/
├── unit/
│   ├── libraries/Gvv_AuthorizationTest.php (12 tests)
│   └── models/Authorization_modelTest.php  (14 tests)
├── integration/
│   └── AuthorizationIntegrationTest.php    (12 test methods)
└── *_bootstrap.php files (CI mock infrastructure)
```

### Documentation
```
doc/
├── prds/
│   └── 2025_authorization_refactoring_prd.md     ✅ v2.0 - Code-based permissions
├── plans_and_progress/
│   └── 2025_authorization_refactoring_plan.md    ✅ v2.0 - This file
└── development/
    └── code_based_permissions.md                 🔵 Phase 7 - Developer guide
```

**Note**: Phase 6 dual-mode documentation archived (optional for v2.0)

---

## Migration Strategy with Feature Flag

### Overview

The migration to the new authorization system uses a **feature flag approach** that allows:
- ✅ Testing the new system with a subset of users
- ✅ Setting up all user permissions before full cutover
- ✅ Quick rollback by flipping the flag
- ✅ Gradual, low-risk production deployment

### Per-User Migration Configuration

The migration system now supports **granular per-user testing** before global rollout.

#### Database Table: `use_new_authorization`

**Purpose**: Enable testing the new system with specific users while others remain on legacy system.

**Structure**:
```sql
CREATE TABLE use_new_authorization (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    KEY idx_username (username)
);
```

**Management**: Manual SQL operations (no GUI required)

**Examples**:
```sql
-- Add users to test new system
INSERT INTO use_new_authorization (username) VALUES ('fpeignot');
INSERT INTO use_new_authorization (username) VALUES ('test_user');

-- Remove user from new system (rollback to legacy)
DELETE FROM use_new_authorization WHERE username = 'test_user';

-- List users on new system
SELECT username FROM use_new_authorization ORDER BY username;

-- Clear all test users
TRUNCATE use_new_authorization;
```

#### Global Flag Configuration

**File**: `application/config/gvv_config.php`

```php
/*
|--------------------------------------------------------------------------
| Authorization System
|--------------------------------------------------------------------------
|
| use_new_authorization: Enable the new structured authorization system
|
| Set to TRUE to use the new Gvv_Authorization library with:
|   - Code-based permissions (require_roles, allow_roles)
|   - user_roles_per_section table
|   - data_access_rules for row-level security
|
| Set to FALSE to use the legacy DX_Auth system with PHP-serialized permissions.
|
| DEFAULT: FALSE (use legacy system until migration is complete)
|
*/
$config['use_new_authorization'] = FALSE;
```

#### Decision Logic (Priority Order)

The system determines which authorization to use based on this priority:

1. **Per-User Check**: If username exists in `use_new_authorization` table → **New system**
2. **Global Flag Check**:
   - If `$config['use_new_authorization'] = TRUE` → **New system for all**
   - If `$config['use_new_authorization'] = FALSE` → **Legacy system for all**

**Key Benefit**: When flag is TRUE (Phase M4), the per-user table is **ignored** and everyone uses the new system.

### Migration Phases with Feature Flag

#### **Phase M1: Preparation (Current State)**
**Config**: Flag = FALSE, Table `use_new_authorization` empty

**Actions**:
- ✅ Database tables created (`user_roles_per_section`, `types_roles`, etc.)
- ✅ Authorization library ready (`Gvv_Authorization.php`)
- ✅ Code-based API implemented (`require_roles()`, `allow_roles()`)
- ⏳ **Next Step 1**: Create table `use_new_authorization`
- ⏳ **Next Step 2**: Grant user roles to all existing users

**User Impact**: None - all users on legacy system

---

#### **Phase M2: User Role Setup + Dev Testing**
**Config**: Flag = FALSE, Table `use_new_authorization` has 2-3 dev users

**Actions**:
1. **Create migration table**:
   ```sql
   CREATE TABLE use_new_authorization (
       id INT AUTO_INCREMENT PRIMARY KEY,
       username VARCHAR(255) NOT NULL UNIQUE,
       KEY idx_username (username)
   );
   ```

2. **Grant user roles** (SQL script):
   ```bash
   mysql -h localhost -u gvv_user -p gvv2 < grant_user_roles_simple.sql
   ```

3. **Assign specialized roles** via UI:
   - Navigate: Admin → Club Admin → Gestion des autorisations
   - Assign: planchiste, ca, bureau, tresorier, club-admin roles

4. **Add dev test users**:
   ```sql
   INSERT INTO use_new_authorization (username) VALUES
       ('dev_user1'), ('dev_user2');
   ```

5. **Test with dev users** (development environment):
   - Login as dev users
   - Verify new authorization system is used
   - Test basic access patterns
   - Check audit logs

**User Impact**:
- Production users: None (still on legacy)
- Dev users: Testing new system

**Duration**: 2-3 days

**Rollback**: Remove dev users from table

---

#### **Phase M3: Production Pilot Testing**
**Config**: Flag = FALSE, Table `use_new_authorization` has 5-10 pilot users

**Actions**:
1. **Select pilot users** (production):
   - Choose 5-10 experienced users
   - Mix of roles: user, planchiste, ca, tresorier
   - Include at least 1 administrator

2. **Add pilot users to table**:
   ```sql
   INSERT INTO use_new_authorization (username) VALUES
       ('fpeignot'), ('agnes'), ('pilot_user3'), ('pilot_user4'), ('pilot_user5');
   ```

3. **Notify pilot users**:
   - Explain they're testing new authorization system
   - Provide contact for reporting issues
   - Request feedback on any access problems

4. **Monitor intensively** (1-2 weeks):
   - Check audit logs daily
   - Look for access denials
   - Monitor error logs
   - Gather pilot user feedback

5. **Validation checklist**:
   - [ ] Pilot users can access authorized pages
   - [ ] Unauthorized access properly denied
   - [ ] Audit log shows correct decisions
   - [ ] Performance acceptable (< 10ms)
   - [ ] No errors in logs
   - [ ] Pilot user feedback positive

**User Impact**:
- Pilot users: Using new system (5-10 users)
- Other users: Still on legacy (~400+ users)

**Duration**: 1-2 weeks

**Rollback**: Remove specific users from table, or `TRUNCATE use_new_authorization;`

---

#### **Phase M4: Global Migration**
**Config**: Flag = TRUE (per-user table now ignored - ALL users on new system)

**Actions**:
1. **Pre-cutover validation**:
   - [ ] Phase M3 pilot completed successfully (1-2 weeks)
   - [ ] All pilot user feedback addressed
   - [ ] All users have roles in `user_roles_per_section`
   - [ ] Database backup completed
   - [ ] Rollback plan ready

2. **Monday morning cutover**:
   - Enable flag: `$config['use_new_authorization'] = TRUE;`
   - **Effect**: ALL users immediately switch to new system
   - Announce to users: "Authorization system upgraded"
   - Monitor intensively for 48 hours

3. **Post-cutover monitoring**:
   - Day 1-2: Check logs every 2 hours
   - Day 3-7: Check logs daily
   - Week 2+: Normal monitoring

4. **If major issues arise**:
   - Immediately flip flag: `$config['use_new_authorization'] = FALSE;`
   - All users revert to legacy instantly
   - Investigate and fix issues
   - Retry when ready

**User Impact**: All users on new system - should be transparent

**Duration**: 1 week intensive monitoring

**Rollback**: Set flag to FALSE (< 1 minute)

---

#### **Phase M5: Cleanup and Finalization**
**Config**: Flag = TRUE (table `use_new_authorization` can be dropped)

**Actions** (After 30 days successful operation):

1. **Drop per-user migration table**:
   ```sql
   DROP TABLE use_new_authorization;
   ```
   (No longer needed - flag controls everything)

2. **Archive legacy permissions**:
   ```sql
   RENAME TABLE role_permissions TO role_permissions_legacy_backup;
   ```

3. **Optional code cleanup**:
   - Remove legacy authorization code (if desired)
   - Remove feature flag (hardcode TRUE)
   - Update documentation

**User Impact**: None

**Duration**: 1-2 days

**Note**: This cleanup is optional - systems can coexist indefinitely

---

### Rollback Procedures

The new per-user migration table enables **granular rollback** at different levels:

#### **Level 1: Per-User Rollback (Phases M2-M3)**

**When**: Individual pilot user encounters problems

**Action**:
```sql
-- Remove specific user from new system
DELETE FROM use_new_authorization WHERE username = 'problematic_user';
```

**Effect**: User immediately reverts to legacy system, other pilots unaffected

**Time**: < 30 seconds

**Use Case**: One pilot user reports issues, others are fine

---

#### **Level 2: Full Pilot Rollback (Phase M3)**

**When**: Multiple pilot users have issues, need to abort pilot testing

**Action**:
```sql
-- Remove all pilot users
TRUNCATE use_new_authorization;
```

**Effect**: All pilot users revert to legacy system

**Time**: < 1 minute

**Use Case**: Systemic problem found during pilot, need to regroup

---

#### **Level 3: Global Rollback (Phase M4+)**

**When**: Major issues after global cutover

**Action**:
```php
// In application/config/gvv_config.php
$config['use_new_authorization'] = FALSE;
```

**Effect**: ALL users immediately revert to legacy system

**Time**: < 1 minute (edit config file)

**Use Case**: Critical bug found after global migration

---

#### **Level 4: Complete System Rollback (Emergency)**

**When**: Database corruption or major system failure

**Actions**:
1. Set flag to FALSE: `$config['use_new_authorization'] = FALSE;`
2. Restore database from backup (if needed)
3. Clear per-user table: `TRUNCATE use_new_authorization;`
4. Verify legacy system operational

**Effect**: Complete return to pre-migration state

**Time**: < 5 minutes (assuming backup available)

**Use Case**: Catastrophic failure requiring full restoration

---

### Migration Timeline Summary

| Phase | Duration | Users Affected | Rollback Level |
|-------|----------|----------------|----------------|
| M1 - Preparation | Current | None | N/A |
| M2 - Dev Testing | 2-3 days | 2-3 dev users | Level 1 or 2 |
| M3 - Pilot Testing | 1-2 weeks | 5-10 pilot users | Level 1 or 2 |
| M4 - Global Migration | 1 week | All users (~400+) | Level 3 |
| M5 - Cleanup | 1-2 days | None | Level 3 |

**Total Time to Production**: 3-4 weeks

**Risk Level**: Very Low (granular testing + instant rollback at every stage)

#### **During Phases M2-M4 (Testing)**
**Simple Rollback**:
1. Set `$config['use_new_authorization'] = FALSE;`
2. Clear cache (if any)
3. Test legacy system still works

**Time to Rollback**: < 5 minutes

---

#### **During Phase M5 (Production Cutover)**
**Emergency Rollback** (if issues found):
1. Immediately set `$config['use_new_authorization'] = FALSE;`
2. Verify legacy system operational
3. Communicate to users
4. Investigate issue in test environment

**Time to Rollback**: < 5 minutes

**Data Loss**: None - both systems use same user tables

---

#### **After Phase M6 (Legacy Removed)**
**Full Rollback** (requires code revert):
1. Revert Git commits (restore legacy code)
2. Restore `role_permissions` table from `role_permissions_legacy`
3. Set flag back to FALSE
4. Deploy code

**Time to Rollback**: 30-60 minutes

---

### Current Status: Phase M2 In Progress (2026-02-18)

**Completed**:
- ✅ Database schema ready (migrations 042, 043, 046, 048, 071)
- ✅ Authorization library implemented with `require_roles()` API
- ✅ 20 controllers migrés avec `require_roles()`
- ✅ 5 test users "Gaulois" enrolled dans `use_new_authorization`
- ✅ Playwright authorization tests: 4 profils × 2 tests = 8 fichiers, tous passent
- ✅ Global flag = FALSE, per-user migration active pour les Gaulois
- ✅ Dashboard "Mon espace personnel" : rôle legacy (`$gvv_role`) masqué pour les nouveaux utilisateurs, carte "Mes autorisations" ajoutée
- ✅ Page `membre/mes_autorisations` : liste les rôles de l'utilisateur par section (tableau)

**Reste à faire — Priorité haute (avant Phase M3)**:
1. **Tests Playwright pour panoramix** (admin) — seul profil non couvert
2. **Activer les 3 controllers désactivés** : investiguer pourquoi `licences`, `email_lists`, `programmes` ont `use_new_auth = FALSE` et les corriger
3. **Grant roles aux utilisateurs réels** : script SQL pour attribuer les rôles à tous les ~400 utilisateurs en production
4. **Assigner les rôles spécialisés** (planchiste, ca, tresorier, bureau, club-admin) via UI ou SQL

**Reste à faire — Priorité moyenne (Phase M3-M4)**:
5. **Migrer les ~30 controllers restants** sans `require_roles()` (admin, backend, facturation, historique, openflyers, FFVV, mails, pompes, etc.)
6. **Enrôler 5-10 utilisateurs pilotes** dans `use_new_authorization` en production
7. **Monitoring intensif** pendant 1-2 semaines
8. **Flip du flag global** `use_new_authorization = TRUE`

**Reste à faire — Priorité basse (post-production)**:
9. **Phase 11** : Cleanup du code legacy
10. **Phase 13** : Migration des qualifications bitmap → `user_roles_per_section`

---

### Feature Flag Status Dashboard

| Environment | Flag Status | Per-User Table | Testing Status | Production Ready |
|-------------|-------------|----------------|----------------|------------------|
| **Development** | FALSE | 5 Gaulois users enrolled | ✅ Playwright + Unit tests passing | N/A |
| **Test/Staging** | FALSE | ⏳ Pending pilot users | ⏳ Pending M3 | Not yet |
| **Production** | FALSE | ⏳ Pending all users | ⏳ Pending M4 | Not yet |

**Next Milestone**: Compléter la couverture Playwright (panoramix), activer les 3 controllers désactivés, puis enrôler des pilotes

---

## Reste à Faire (Updated v2.5 - 2026-02-18)

### Priorité 1 : Compléter la couverture de test (Phase M2)

1. **Tests Playwright pour panoramix (admin)**
   - Créer `panoramix-authorization.spec.js` et `panoramix-recursive-authorizations.spec.js`
   - Panoramix = club-admin dans toutes les sections → toutes les routes autorisées sauf celles protégées par des checks legacy spécifiques

2. **Investiguer et activer les 3 controllers désactivés**
   - `licences.php` (`use_new_auth = FALSE`) — code `['ca']` prêt, pourquoi désactivé ?
   - `email_lists.php` (`use_new_auth = FALSE`) — code `['secretaire', 'ca']` prêt
   - `programmes.php` (`use_new_auth = FALSE`) — utilise `Formation_access` à la place

### Priorité 2 : Préparer le déploiement pilote (Phase M2 → M3)

3. **Grant roles à tous les utilisateurs de production** (~400 users)
   ```bash
   mysql -h localhost -u gvv_user -p gvv2 < grant_user_roles_simple.sql
   ```

4. **Assigner les rôles spécialisés** via UI ou SQL :
   - planchiste, ca, bureau, tresorier, club-admin

5. **Enrôler 5-10 utilisateurs pilotes** dans `use_new_authorization`
   - Choisir des utilisateurs expérimentés avec différents profils de rôles
   - Monitoring intensif pendant 1-2 semaines

### Priorité 3 : Migrer les controllers restants (~30)

6. **Controllers sans `require_roles()` à migrer** :
   - Administration : `admin`, `backend`, `config`, `dbchecks`, `migration`
   - Financier : `rapprochements`, `plan_comptable`, `facturation`
   - Rapports : `reports`, `historique`
   - Vol : `vols_decouverte`, `event`
   - Technique : `openflyers`, `FFVV`, `mails`
   - Autres : `pompes`, `tools`, `acceptance_admin`, `formation_*`, `archived_documents`, etc.

### Priorité 4 : Global cutover (Phase M4)

7. **Flip du flag global** : `$config['use_new_authorization'] = TRUE`
8. **Monitoring 48h intensif**, puis 1 semaine de surveillance
9. **Rollback** si problème : flip flag → FALSE

### Priorité 5 : Post-production (Phases 11, 13)

10. **Cleanup legacy** (Phase 11) — supprimer le code ancien
11. **Migration qualifications bitmap** (Phase 13) — `membres.mniveaux` → `user_roles_per_section`

---

## Project Timeline (v2.0)

### Already Completed (Phases 0-6)
- **Weeks 1-10**: Legacy system implementation ✅
- **Status**: Database, UI, dual-mode infrastructure complete

### Completed Work (Phases 7)
| Phase | Duration | Completion Date |
|-------|----------|----------------|
| **7: Code-Based API** | 1 day | 2025-10-24 |

### Remaining Work (Phases 8-12)
| Phase | Duration | Cumulative | Target Week |
|-------|----------|------------|-------------|
| **8: Pilot Migration** | 3-4 days | 4 days | Week 12 |
| **9: Complex Controllers** | 5-7 days | 11 days | Week 13-14 |
| **10: Full Migration** | 15-20 days | 31 days | Week 15-17 |
| **11: Cleanup** | 5-7 days | 38 days | Week 18 |
| **12: Deployment** | 3-5 days + 1 week | 46 days | Week 19-20 |

**Total Estimated Duration**: ~9 weeks (46 days) for Phases 8-12
**Project Total**: ~20 weeks from start (Phases 0-12)

---

## Success Criteria (Updated v2.0)

### ✅ Phase 5 Exit Criteria - COMPLETE
- ✅ All unit tests passing (26/26)
- ✅ All integration tests passing (12/12 authorization, 213/213 total)
- ✅ Integration test framework operational
- ✅ Database transaction isolation working

### ✅ Phase 6 Exit Criteria - COMPLETE (for v2.0)
- ✅ `Gvv_Controller` base class created
- ✅ Foundation ready for code-based permissions
- ✅ Migration 046 executed (comparison_log table)
- **Note v2.0**: Dual-mode dashboard and pilot testing no longer required (superseded by code-based approach)

### ✅ Phase 7 Exit Criteria (Code-Based API) - COMPLETE
- [x] `require_roles()`, `allow_roles()`, `can_edit_row()` implemented
- [x] Unit tests passing (15 new tests, 41 total)
- [x] Developer documentation complete (647 lines)
- [x] API design implemented and tested

### 🔵 Phase 8 Exit Criteria (Pilot Migration)
- [ ] 7 simple controllers migrated
- [ ] Mapping document created (old → new)
- [ ] Integration tests updated
- [ ] No authorization errors in logs

### 🔵 Phase 9 Exit Criteria (Complex Controllers)
- [ ] 7 complex controllers migrated (membre, compta, vols_planeur, etc.)
- [ ] Exception patterns documented
- [ ] Row-level security tests passing
- [ ] No regression in functionality

### 🔵 Phase 10 Exit Criteria (Full Migration)
- [ ] All 53 controllers migrated
- [ ] Migration 047 executed (role_permissions → role_permissions_legacy)
- [ ] Permissions tab removed from UI
- [ ] Verification script passes (no missed controllers)

### 🔵 Phase 11 Exit Criteria (Cleanup)
- [ ] Legacy permission code removed
- [ ] Performance benchmarks show improvement
- [ ] Documentation updated
- [ ] Training materials created

### 🔵 Phase 12 Exit Criteria (Deployment)
- [ ] Staging deployment successful
- [ ] Production deployment successful
- [ ] 48-hour monitoring clean
- [ ] User acceptance sign-off

### 🔵 Phase 13 Exit Criteria (Qualification Migration)
- [ ] New roles created in `types_roles` (remorqueur, treuillard, chef_pilote, chef_de_piste)
- [ ] Bitmap data migrated to `user_roles_per_section` for all active members
- [ ] `qualif_selector()` replaced by role-based selectors
- [ ] `Formation_access` uses roles instead of bitmap
- [ ] Email list queries use `user_roles_per_section` instead of bitmap
- [ ] Member form updated (migrated qualifications removed from checkboxes)
- [ ] Flight form dropdowns functional with new selectors
- [ ] No regression in Playwright tests

### 🎯 Project Completion Criteria (v2.4)
- [ ] All 53 controllers use code-based permissions
- [ ] `role_permissions` table deprecated and renamed
- [ ] Performance improved (no DB lookups for permissions)
- [ ] Operational qualifications consolidated in `user_roles_per_section`
- [ ] `membres.mniveaux` bitmap reduced to informational bits only
- [ ] All documentation updated (PRD, plan, guides)
- [ ] Administrator and developer training complete
- [ ] Project retrospective conducted
- [ ] Lessons learned documented

---

**Document History**:
- v1.0 (2025-01-08): Initial plan
- v1.1 (2025-10-15): Phase 3 completion update
- v1.2 (2025-10-17): Unit tests fixed
- v1.3 (2025-10-18): Phase 4 completion
- v1.4 (2025-10-18): Compressed format, removed redundant details
- v1.5 (2025-10-21): Phase 5 complete (100% test pass rate), Phase 6 planning created
- v1.6 (2025-10-21): Phase 6 ~90% complete - Migration dashboard implemented
- **v2.0 (2025-01-24): Major architecture change**
  - **Breaking Change**: Permissions now managed in code, not database
  - Added Phases 7-12 for code-based permission migration
  - Legacy system (Phases 0-6) preserved for rollback
  - `role_permissions` table to be deprecated in Phase 10
  - Updated timeline: +10 weeks (Phases 7-12)
  - Based on PRD v2.0 analysis
  - Estimated 53 controllers to migrate (~800 lines changes)
  - Performance improvement expected (no DB lookups)
- **v2.1 (2025-10-24): Phase 7 completion**
  - ✅ Phase 7 complete: Code-based permissions API implemented
  - `Gvv_Authorization` extended with `require_roles()`, `allow_roles()`, `can_edit_row()`
  - `Gvv_Controller` extended with helper wrappers
  - 15 new unit tests added (41 total, 100% pass rate)
  - Developer documentation created (647 lines)
  - All 213 tests passing
  - Project now 55% complete, ready for Phase 8 pilot migration
  - Updated timeline: 46 days remaining (Phases 8-12)
- **v2.2 (2025-10-26): Feature flag migration strategy added**
  - ✅ Added comprehensive "Migration Strategy with Feature Flag" section
  - Two paths to production defined:
    - **Path 1 (Recommended)**: Feature flag migration (2-3 weeks) - Phases M1-M6
    - **Path 2 (Optional)**: Controller code migration (10 weeks) - Phases 8-12
  - PRD Section 6.1 (Principe de Migration Progressive) now properly addressed
  - Feature flag usage documented: `use_new_authorization` controls system selection
  - Progressive user-based testing strategy defined
  - Rollback procedures clarified (< 5 min with flag flip)
  - Current status: Phase M1 complete, M2 in progress (user role setup)
  - Created quick reference: `AUTHORIZATION_MIGRATION_QUICKREF.md`
  - Timeline to production: 2-3 weeks via feature flag approach
  - Phases 8-12 marked as optional post-production enhancements
- **v2.4 (2026-02-12): Qualification bitmap migration analysis**
  - Added Phase 13: Migration of operational qualifications from `membres.mniveaux` bitmap to `user_roles_per_section`
  - Added Path 3 in Executive Summary
  - Analysis of 20 bitmap flags: 6 to migrate, 5 to keep, 6 already covered by types_roles
  - Precedent: `inst_selector()` already migrated to use roles
  - New roles to create: remorqueur, treuillard, chef_pilote, chef_de_piste
  - Impact analysis: qualif_selector, Formation_access, email lists, member form
  - Routes and permissions reference document created: `doc/authorization/routes_and_permissions.md`
- **v2.5 (2026-02-18): State assessment and test coverage update**
  - Inventaire réel : 20 controllers migrés (vs 0 dans le plan v2.4)
  - 3 controllers avec code prêt mais `use_new_auth = FALSE` (licences, email_lists, programmes)
  - 5 test users Gaulois enrolled dans `use_new_authorization` table
  - Playwright authorization tests : 8 fichiers couvrant 4 profils (asterix, obelix, abraracourcix, goudurix)
  - Tests abraracourcix et goudurix créés et validés (30 tests chacun, 100% pass)
  - Identification du reste à faire : panoramix tests, 3 controllers désactivés, ~30 controllers à migrer, role assignment pour production users
  - Restructuration des priorités : couverture test → pilote → migration → cutover → cleanup
- **v2.6 (2026-02-20): Comportements attendus par rôle pour vols_planeur**
  - Mise à jour de la description des utilisateurs Gaulois : obelix (planchiste Planeur + auto_planchiste ULM), goudurix (auto_planchiste Avion)
  - Précision des comportements attendus dans la tâche 9.3 (vols_planeur) :
    - planchiste : créer, modifier tous les vols, accès à `plancheauto`/`plancheauto_select`
    - auto_planchiste : créer, modifier ses propres vols uniquement, pas d'accès à `plancheauto`
  - Ajout de `create()` et `plancheauto()`/`plancheauto_select()` dans la spécification de migration

---

## Appendix: Migration from Database to Code-Based Permissions

### Why the Change?

**Problem Identified**: The v1.0 implementation created ~300 permission entries in `role_permissions` table:
- Difficult to maintain (manual entries for each controller/action/role combination)
- Risk of inconsistency between code and database
- Performance overhead (SQL queries for every permission check)
- Complex UI for managing permissions

**Solution**: Declare permissions directly in controller code:
- Permissions visible where they apply (in controller constructors/methods)
- Always consistent with code (versioned together in Git)
- Better performance (no DB lookups, only session-cached roles)
- Simpler maintenance (developers manage permissions in code)

### What Stays the Same

✅ **Unchanged**:
- User roles system (`types_roles`, `user_roles_per_section`)
- Role assignment UI (user roles management)
- Row-level security (`data_access_rules`)
- Audit logging (`authorization_audit_log`)
- Section-aware permissions
- 8 defined roles (club-admin, super-tresorier, bureau, etc.)

### What Changes

⚠️ **Changed**:
- **OLD**: Permissions stored in `role_permissions` table
- **NEW**: Permissions declared in controller code via `require_roles()`, `allow_roles()`
- **OLD**: Permissions UI tab in authorization dashboard
- **NEW**: Permissions tab removed (managed in code)
- **OLD**: `Gvv_Authorization->can_access($user, $controller, $action)`
- **NEW**: `Gvv_Controller->require_roles(['planchiste'])` in constructor

### Migration Example

**Before (v1.0 - Database)**:
```sql
-- In database role_permissions table
INSERT INTO role_permissions (types_roles_id, controller, action, section_id, permission_type)
VALUES (5, 'vols_planeur', NULL, 1, 'view'),
       (5, 'vols_planeur', 'create', 1, 'edit'),
       (5, 'vols_planeur', 'edit', 1, 'edit'),
       (5, 'vols_planeur', 'delete', 1, 'delete');
```

**After (v2.0 - Code)**:
```php
// In application/controllers/vols_planeur.php
class Vols_planeur extends Gvv_Controller {
    function __construct() {
        parent::__construct();
        // Authorization: Code-based (v2.0)
        $this->require_roles(['planchiste'], $this->section_id);
    }

    function delete($id) {
        // More restrictive: only planchiste can delete (not auto_planchiste)
        // No allow_roles() here = keep constructor requirement
        parent::delete($id);
    }
}
```

### Rollback Strategy

If code-based approach fails:
1. Revert Git commits (Phases 7-12)
2. Restore `role_permissions` table from `role_permissions_legacy`
3. Re-enable database permission checking in `Gvv_Authorization`
4. All data preserved, no data loss
