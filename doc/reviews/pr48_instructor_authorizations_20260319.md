# Code Review — PR #48: Autorisations instructeur

**Date**: 2026-03-19
**PR**: #48 — fix/instructor
**Files**: `application/controllers/programmes.php`, `application/migrations/081_fix_ca_ajax_cumuls_permission.php`, `application/config/migration.php`

---

## Summary

This PR fixes two independent authorization bugs:

1. **Instructeurs with legacy CA flag could manage programmes** — `_can_view()` and `_can_manage()` in `Programmes` were gated on `$this->use_new_auth && $this->_is_new_auth_user()`. With the global flag `use_new_authorization = false`, `MY_Controller::_init_auth()` forced `$this->use_new_auth = FALSE` even for users who had entries in `user_roles_per_section`. Those users fell through to the legacy `can_manage_programmes()` path, where the CA bit flag (64) granted write access.

2. **Tresorerie page blank for legacy CA users** — `ajax_cumuls` was missing from the DX Auth URI permissions for the `ca` role. Migration 074 had restricted `/comptes/` to only `/comptes/dashboard/` and `/comptes/tresorerie/`, omitting the AJAX endpoint that the tresorerie view calls to fetch chart data.

---

## Issues

### 🔴 High

*(none)*

### 🟠 Medium

**[M1] Docblock "Legacy: comportement inchangé" is now incorrect**
`_can_view()` and `_can_manage()` both still say `Legacy: admin ou CA (bit flag mniveaux) — comportement inchangé`. After this change, the legacy path is only reached by users with *no* entries in `user_roles_per_section`. Any user with new auth roles — even if `use_new_auth = FALSE` globally — now goes through the new auth path. The comment is misleading and should be updated.

**[M2] Two independent bug fixes in a single PR**
The programmes authorization fix and the `ajax_cumuls` permission fix are unrelated — different components, different root causes. Bundling them makes the PR history harder to bisect. Low practical impact for a maintenance project but worth noting for discipline.

### 🟡 Low

**[L1] Fragile implicit dependency on `gvv_authorization` being loaded**
After the change, `_can_view()` calls `$this->_is_new_auth_user()` (which loads `gvv_authorization` as a side effect), then directly accesses `$this->gvv_authorization->has_any_role(...)`. The code is safe as written because `_is_new_auth_user()` always loads the library before returning, but the calling code relies on this side effect without any defensive check. If `_can_view()` is ever called via a different code path that bypasses `_is_new_auth_user()`, it will fatal. A guard `if (!isset($this->gvv_authorization)) $this->load->library('Gvv_Authorization');` at the top of `_can_view()` and `_can_manage()` would make this self-contained.

**[L2] Root cause not addressed systemically**
The ambiguity between the global `use_new_auth` flag and per-user role detection (`_is_new_auth_user()`) is patched locally in `Programmes`. Other controllers that use the same `$this->use_new_auth && $this->_is_new_auth_user()` pattern would have the same bug if they integrated new-auth logic. The Formation sub-controllers (`formation_inscriptions`, `formation_autorisations_solo`, etc.) use `formation_access` instead and are not affected — but the pattern remains a latent risk.

**[L3] Migration 081 `down()` does not warn when URI is absent**
If `down()` is called and `/comptes/ajax_cumuls/` is not present (e.g. already removed manually), it silently succeeds. A `log_message('warning', ...)` when the URI is not found would aid debugging.

### 🔵 Info

**[I1] No test coverage for the new authorization behavior**
There is no PHPUnit test asserting that:
- A user with only `instructeur` role (no `rp`, no `club-admin`) cannot call `create()`, `store()`, `edit()`, `update()`, `delete()`, or `update_structure()`.
- A user with the legacy CA bit flag but new auth roles is correctly blocked from managing programmes.

**[I2] Migration 081 is correct and idempotent**
The idempotency guard (`in_array` before adding) is good practice. Error handling covers missing role and missing permissions row. `down()` cleanly reverses the change.

---

## Todo (ordered by criticality)

- [ ] **[M1]** Update docblocks in `_can_view()` and `_can_manage()` — replace "Legacy: comportement inchangé" with accurate description of when the legacy path is reached (users with no entries in `user_roles_per_section`).
- [ ] **[L1]** Add explicit `gvv_authorization` load guard in `_can_view()` and `_can_manage()` instead of relying on the side effect of `_is_new_auth_user()`.
- [ ] **[I1]** Add PHPUnit tests for `_can_manage()`: instructeur-only role → denied, club-admin → allowed, rp → allowed, legacy-only user → legacy path used.
- [ ] **[L3]** Add `log_message('warning', ...)` in `down()` of migration 081 when URI not found.
- [ ] **[L2]** (Future) Resolve the `use_new_auth` / `_is_new_auth_user()` ambiguity at the `MY_Controller` level so per-user new-auth roles always take precedence without per-controller workarounds.
