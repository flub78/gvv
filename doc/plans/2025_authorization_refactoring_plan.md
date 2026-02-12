# GVV Authorization System Refactoring Plan

**Document Version:** 2.4
**Date:** 2025-01-08 (Updated: 2026-02-12)
**Status:** Phase 7 Complete, Per-User Migration Strategy Implemented, Qualification Migration Planned
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
- **Current Status**: Phase M1 complete, starting M2

### ⏳ **Path 2: Controller Code Migration (OPTIONAL - 10+ weeks)**
- Code cleanliness: All 53 controllers declare permissions in code
- Long-term maintenance: Permissions visible in controller code
- Performance optimization: Remove database permission lookups
- **Phases**: 8-12 (Controller migration phases)
- **Timeline**: ~10 weeks additional work
- **Status**: Can be done AFTER production deployment via Path 1

### 🔄 **Path 3: Qualification Bitmap Migration (POST-PRODUCTION - 2-3 weeks)**
- Migrate operational qualifications from `membres.mniveaux` bitmap to `user_roles_per_section`
- Single source of truth for roles AND qualifications
- Section-aware qualifications (instructor in section 1, not in section 2)
- Audit trail (granted_at / revoked_at)
- **Phases**: 13 (Qualification migration)
- **Timeline**: ~2-3 weeks additional work
- **Status**: Can be done AFTER Path 1 production deployment
- **Precedent**: `inst_selector()` already migrated to use `user_roles_per_section`

**Recommendation**: Use **Path 1** to go to production quickly, then pursue **Path 3** for qualification consolidation (high value), and optionally **Path 2** for code improvements.

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

### Phase 8: Controller Migration Pilot (v2.0) 🔵 NEW

**Objectives**: Migrate 5-10 simple controllers to code-based permissions

**Pilot Controllers** (simple, low-risk):
- `sections` (ca only)
- `terrains` (ca only)
- `alarmes` (ca only)
- `presences` (ca only)
- `licences` (ca only)
- `tarifs` (ca only)
- `calendar` (user)

**Tasks**:
- [ ] **8.1** For each pilot controller:
  - Add `require_roles()` in constructor
  - Document which permissions were migrated
  - Test all controller methods
  - Verify access denied for unauthorized users
- [ ] **8.2** Create mapping document:
  - Old: `role_permissions` entries → New: code declarations
  - Verification checklist for each controller
- [ ] **8.3** Integration testing:
  - Test with different user roles
  - Verify section-specific permissions
  - Confirm denial messages
- [ ] **8.4** Mark migrated controllers:
  - Add comment `// Authorization: Code-based (v2.0)` in constructor
  - Update controller documentation

**Estimated Effort**: 3-4 days

**Deliverables**:
- 7 migrated controllers (~50 lines changes total)
- Migration mapping document (`doc/phase8_controller_migration_map.md`)
- Integration test updates
- Controller migration checklist

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
  - `page()`, `edit()`: `allow_roles(['auto_planchiste'])` + row-level check
  - `delete()`: Keep `require_roles(['planchiste'])` (no auto)
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

### Phase 13: Qualification Bitmap Migration (v2.4) 🔵 NEW

**Objectives**: Consolidate operational qualifications from `membres.mniveaux` bitmap into the `user_roles_per_section` table, providing a single source of truth for both access control and member qualifications.

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
- Migration SQL (nouveaux rôles + conversion bitmap → user_roles_per_section)
- Sélecteurs migrés (remorqueur_selector, treuillard_selector)
- Formation_access réécrit sans bitmap
- Listes email migrées
- Fiche membre simplifiée
- Tests de non-régression

---

## Project Status Dashboard (v2.0)

### Development Phases

| Phase | Status | Progress | Estimated Duration | Notes |
|-------|--------|----------|-------------------|-------|
| **0-6: Legacy System** | ✅ Complete | 100% | - | Database, UI, dual-mode ready |
| **7: Code-Based API** | ✅ Complete | 100% | 1 day | Completed 2025-10-24 |
| **8: Pilot Migration** | 🔵 Planned | 0% | 3-4 days | 7 simple controllers (Optional) |
| **9: Complex Controllers** | 🔵 Planned | 0% | 5-7 days | 7 controllers with exceptions (Optional) |
| **10: Full Migration** | 🔵 Planned | 0% | 15-20 days | 35 remaining controllers (Optional) |
| **11: Cleanup** | 🔵 Planned | 0% | 5-7 days | Remove legacy code (Optional) |
| **12: Production Deploy** | 🔵 Planned | 0% | 3-5 days + 1 week | Final deployment (Optional) |
| **13: Qualification Migration** | 🔵 Planned | 0% | 10-15 days | Bitmap → user_roles_per_section |

**Note**: Phases 8-12 are now **optional** with the feature flag approach. System can go to production after Phase 7 by enabling the flag. Phase 13 can be done independently after production deployment.

### Migration Phases (Feature Flag Based)

| Phase | Status | Duration | Flag Status | User Impact | Notes |
|-------|--------|----------|-------------|-------------|-------|
| **M1: Preparation** | ✅ Complete | - | FALSE | None | Infrastructure ready |
| **M2: Role Setup** | ⏳ Current | 1-2 days | FALSE | None | Grant user roles via SQL |
| **M3: Testing** | 🔵 Next | 3-5 days | TRUE (test env) | None | Validate with test users |
| **M4: Pilot** | 🔵 Planned | 1 weekend | TRUE (production) | Minimal | Optional weekend test |
| **M5: Production** | 🔵 Planned | 1 week | TRUE (permanent) | None | Full cutover with monitoring |
| **M6: Cleanup** | 🔵 Future | 1-2 days | TRUE (hardcoded) | None | Remove flag (optional) |

**Total Time to Production**: 2-3 weeks (phases M2-M5)

### Detailed Metrics

| Metric | Target | Current | Status |
|--------|--------|---------|--------|
| **Legacy System (Phases 0-6)** | 100% | 100% | 🟢 Complete |
| **Code-Based API (Phase 7)** | 100% | 100% | 🟢 Complete |
| **Migration Path** | Feature Flag | Feature Flag | 🟢 PRD-Compliant |
| **User Roles Setup** | All users | ~30% | 🟡 Phase M2 In Progress |
| **Feature Flag Status** | TRUE (prod) | FALSE (all envs) | 🔴 Awaiting M3 testing |
| **Tests Passing** | 100% | Unit: 100%, Integration: 100% | 🟢 Complete (213/213) |
| **Documentation** | Complete | ~85% | 🟢 Migration section added |
| **Production Ready** | TRUE | FALSE | 🟡 2-3 weeks (via flag) |

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

### Current Status: Phase M1 → M2 Transition

**Completed**:
- ✅ Database schema ready
- ✅ Authorization library implemented
- ✅ Code-based API ready
- ✅ Test suite passing (213/213 tests)
- ✅ Feature flag configured (currently FALSE)

**Next Steps**:
1. **Immediate**: Grant 'user' roles to all users with compte 411 (SQL script ready)
2. **This week**: Assign specialized roles (planchiste, ca, tresorier) via UI
3. **Next week**: Enable flag on test environment, begin Phase M3 testing

**Timeline**:
- Phase M2 (Role Setup): 1-2 days
- Phase M3 (Testing): 3-5 days  
- Phase M4 (Pilot): 1 weekend (optional)
- Phase M5 (Cutover): 1 week monitoring
- **Total**: 2-3 weeks to production

---

### Feature Flag Status Dashboard

| Environment | Flag Status | User Roles Setup | Testing Status | Production Ready |
|-------------|-------------|------------------|----------------|------------------|
| **Development** | FALSE | ✅ Complete | ✅ Unit tests passing | N/A |
| **Test/Staging** | FALSE → TRUE | ⏳ In progress | ⏳ Pending M3 | Not yet |
| **Production** | FALSE | ⏳ In progress | ⏳ Pending M4 | Not yet |

**Next Milestone**: Enable flag on test environment after user roles setup

---

## Next Immediate Actions (Updated v2.1 - Feature Flag Migration)

### Current Priority: User Role Setup & Testing (Phase M2 → M3)

**Completed**: 
- ✅ Phases 0-7 complete (Infrastructure + API ready)
- ✅ Feature flag configured (currently FALSE in all environments)
- ✅ Migration strategy documented with 6 phases (M1-M6)

### Immediate Actions (This Week - Phase M2)

1. **⏳ Grant User Roles via SQL Script**:
   ```bash
   # Backup first
   mysqldump -h localhost -u gvv_user -p gvv2 user_roles_per_section > backup_roles.sql
   
   # Grant 'user' role to all users with compte 411
   mysql -h localhost -u gvv_user -p gvv2 < grant_user_roles_simple.sql
   ```
   
   **Expected Result**: ~106 users granted 'user' role across sections:
   - Section 1 (Planeur): Already complete (289 users)
   - Section 2 (ULM): ~61 users to be granted
   - Section 3 (Avion): ~45 users to be granted
   - Section 4 (Général): Already complete (278 users)

2. **⏳ Assign Specialized Roles via UI**:
   - Navigate to: Admin → Club Admin → Gestion des autorisations
   - Assign roles manually:
     - **planchiste**: Flight loggers who can edit/delete flights
     - **ca**: Board members (Conseil d'Administration)
     - **bureau**: Office members
     - **tresorier**: Treasurers
     - **club-admin**: Full administrators
   
   **Tool**: Use the Authorization UI (completed in Phase 4)
   
   **Estimated Time**: 2-3 hours

3. **⏳ Verify All Users Have Roles**:
   ```sql
   -- Check role distribution
   SELECT s.nom, tr.nom, COUNT(*) as user_count
   FROM user_roles_per_section urps
   JOIN sections s ON urps.section_id = s.id
   JOIN types_roles tr ON urps.types_roles_id = tr.id
   WHERE urps.revoked_at IS NULL
   GROUP BY s.nom, tr.nom
   ORDER BY s.id, tr.id;
   ```

**Duration**: 1-2 days

---

### Next Actions (Next Week - Phase M3)

4. **🔵 Enable Feature Flag on Test Environment**:
   - Edit `application/config/gvv_config.php` on test server:
     ```php
     $config['use_new_authorization'] = TRUE;  // Enable new system
     ```
   - Clear any caches
   - Test with multiple user accounts

5. **🔵 Comprehensive Testing**:
   - Test each role type:
     - [ ] Basic user (role: user) - can view own data
     - [ ] Flight logger (role: planchiste) - can edit flights
     - [ ] Board member (role: ca) - can access admin pages
     - [ ] Treasurer (role: tresorier) - can access accounting
     - [ ] Administrator (role: club-admin) - full access
   
   - Test authorization scenarios:
     - [ ] Access granted for authorized pages
     - [ ] Access denied for unauthorized pages
     - [ ] Audit log records all attempts
     - [ ] Row-level security works (own vs all)
   
   - Performance testing:
     - [ ] Authorization checks < 10ms
     - [ ] No performance degradation

6. **🔵 Review Audit Logs**:
   ```sql
   -- Check recent authorization decisions
   SELECT * FROM authorization_audit_log 
   WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
   ORDER BY created_at DESC
   LIMIT 100;
   ```

**Duration**: 3-5 days

---

### Optional Actions (Phase M4 - Production Pilot)

7. **🔵 Weekend Production Pilot** (Optional but Recommended):
   - Friday evening: Enable flag in production
   - Monitor for 2-4 hours
   - If successful, leave enabled through weekend
   - If issues, revert flag to FALSE
   - Monday: Evaluate results

**Duration**: 1 weekend

---

### Alternative Path: Skip Controller Migration (Phases 8-10)

**Important Note**: With the feature flag approach, **controller migration (Phases 8-10) is now optional**. The system can go to production via the feature flag alone:

- ✅ **With feature flag**: Production-ready in 2-3 weeks (M2-M5)
- ⏳ **With controller migration**: Production-ready in ~10 weeks (Phases 8-12)

**Recommendation**: 
1. Go to production via feature flag first (Phases M2-M5)
2. Optionally migrate controllers later (Phases 8-10) for cleaner code
3. Keep feature flag indefinitely as a safety mechanism

---

### Documentation Updates

8. **🔵 Update Phase 8-12 Status**:
   - Mark Phases 8-12 as "Optional - Post-Production Enhancement"
   - Focus on feature flag migration path (M1-M6)
   - Update PRD to reflect chosen approach

---

### Timeline Summary

| Phase | Action | Duration | Start |
|-------|--------|----------|-------|
| **M2** | Grant user roles, assign specialized roles | 1-2 days | This week |
| **M3** | Test on staging with flag TRUE | 3-5 days | Next week |
| **M4** | Optional production pilot | 1 weekend | Week 3 |
| **M5** | Full production cutover | 1 week | Week 3-4 |
| **Total** | **Ready for production** | **2-3 weeks** | - |

**Current Status**: Phase M1 complete, starting M2 today
     - `tarifs` (ca only)
     - `calendar` (user)
   - [ ] Create mapping document (old permissions → new code)
   - [ ] Integration testing
   - [ ] Verify no authorization errors in logs

3. **🔵 Phase 9: Complex Controllers** (5-7 days):
   - [ ] Migrate 7 complex controllers (membre, compta, vols_planeur)
   - [ ] Document exception patterns
   - [ ] Row-level security testing

4. **Documentation Priorities**:
   - ✅ PRD v2.0 (Complete)
   - ✅ Implementation Plan v2.0 (This document - updated 2025-10-24)
   - ✅ Developer guide for code-based permissions (Complete - Phase 7)
   - 🔵 Migration mapping for all 53 controllers (Phases 8-10)
   - 🔵 Administrator communication (Phase 12)

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
