# Code Review — PR #49: Fix/autorisations VD

**Date**: 2026-03-20
**PR**: #49 — fix/autorisations_VD
**Files**: `application/config/migration.php`, `application/controllers/vols_decouverte.php`, `application/libraries/Gvv_Controller.php`, `application/libraries/MetaData.php`, `application/migrations/082_gestion_vd_role.php`, `application/models/membres_model.php`, `application/views/bs_dashboard.php`, `application/views/bs_menu.php`, `application/views/vols_decouverte/bs_formMenu.php`, `application/views/vols_decouverte/bs_tableView.php`

---

## Summary

This PR introduces two new section-scoped roles for the discovery flight (VD) module:

- **`gestion_vd`** — full CRUD access on discovery flights, replaces the previous `ca` requirement
- **`pilote_vd`** — appears in the pilot selector of the VD edit form; implies all `gestion_vd` permissions

The approach is a controller-level role override in `Vols_decouverte::user_has_role()`. The `Gvv_Controller` base class is also improved to use `user_has_role()` instead of the legacy `dx_auth->is_role()` for computing `has_modification_rights`, making the mechanism correctly support the new auth system across all controllers.

---

## Issues

### 🔴 High

**[H1] Down migration fails when roles are assigned to users**

`082_gestion_vd_role::down()` executes a plain `DELETE FROM types_roles WHERE nom IN ('gestion_vd', 'pilote_vd')`. If any user has been assigned these roles in `user_roles_per_section`, the DELETE will be rejected by the foreign key constraint.

```php
// current — will fail with FK constraint if roles are assigned
$this->db->query("DELETE FROM types_roles WHERE nom IN ('gestion_vd', 'pilote_vd')");
```

Fix: remove dependent rows first or soft-delete:
```php
$this->db->query(
    "DELETE urps FROM user_roles_per_section urps
     INNER JOIN types_roles tr ON tr.id = urps.types_roles_id
     WHERE tr.nom IN ('gestion_vd', 'pilote_vd')"
);
$this->db->query("DELETE FROM types_roles WHERE nom IN ('gestion_vd', 'pilote_vd')");
```

---

### 🟠 Medium

**[M1] Role implication logic is fragmented**

The rule "pilote_vd implies gestion_vd" is enforced in three distinct places using different mechanisms:

| Location | Mechanism |
|---|---|
| `Vols_decouverte::user_has_role()` | PHP override — applies inside the VD controller |
| `bs_menu.php` | `has_role('gestion_vd') \|\| has_role('pilote_vd')` |
| `bs_dashboard.php` | Same explicit dual check |

The menu and dashboard bypass the controller override because `has_role()` calls `$CI->user_has_role()` on the *current* controller (`Welcome`), not on `Vols_decouverte`. Adding a third VD-related role in the future would require updating all three locations independently. A single helper function or a dedicated role alias in `Gvv_Authorization` would centralise the implication.

**[M2] `action()` inline `has_modification_rights` inconsistent with parent**

The inline computation in `action()`:
```php
$this->data['has_modification_rights'] = $this->dx_auth->is_admin() || $this->user_has_role($this->modification_level);
```
does not guard against `$this->modification_level` being unset (unlike `form_static_element()` and `page()` which prefix `!isset($this->modification_level) ||`). Not a live bug in `Vols_decouverte` where the property is always set, but fragile if copy-pasted to another controller.

**[M3] `vd_pilots()` silently returns empty for legacy-auth users**

`membres_model::vd_pilots()` queries `user_roles_per_section`, which only contains new-auth users. For a section where all users are on the legacy auth system, the method always returns an empty selector (just `['' => '']`), triggering the fallback to all active members. This is correct behaviour, but the caller has no way to distinguish "no pilots defined" from "no pilots because legacy auth". A comment documenting this limitation would help future maintainers.

**[M4] VD menu items duplicated in two locations**

The "Liste des bons" and "Chercher un bon" menu items appear in:
1. Inside the `has_role('ca')` Administration dropdown (with `gestion_vd`/`pilote_vd` sub-check)
2. As a standalone dropdown for non-CA `gestion_vd`/`pilote_vd` users

Any future change to these entries (label, URL, icon) must be made in both places.

---

### 🟡 Low

**[L1] `target="_blank"` missing `rel="noopener noreferrer"` in MetaData.php**

```php
// current
'" target="_blank">'
// recommended
'" target="_blank" rel="noopener noreferrer">'
```
Opening a new tab without `rel="noopener"` allows the opened page to access `window.opener`. Low risk for internal PDF links, but best practice.

**[L2] Translation keys `role_gestion_vd` and `role_pilote_vd` not added to language files**

The migration declares `translation_key = 'role_gestion_vd'` and `role_pilote_vd`, but no corresponding entries were added in `application/language/french/`, `english/`, or `dutch/`. If these keys are ever used (e.g., in role management UI), they will display the raw key instead of a label.

**[L3] `$pilote_selector` fallback threshold comment could mislead**

```php
if (count($pilote_selector) <= 1) {
    // Aucun pilote_vd défini dans la section : repli sur tous les membres actifs
```

The condition is correct (the selector always has a `['' => '']` empty entry making `count = 1` when empty). The comment is accurate but the "magic" threshold of 1 is not obvious. A named constant or a helper method `$selector->isEmpty()` would make intent clearer.

---

## TODO (decreasing criticality)

- [x] **[H1]** Fix `down()` migration to delete `user_roles_per_section` rows before `types_roles` rows
- [x] **[M1]** Centralise `pilote_vd` → `gestion_vd` implication — added `has_vd_role()` in `views_helper.php`; menu and dashboard updated to use it
- [x] **[M2]** Add `!isset($this->modification_level) ||` guard to `action()` inline check
- [x] **[M3]** Add docblock note to `vd_pilots()` about legacy-auth limitation
- [ ] **[M4]** Consider extracting VD menu fragment into a partial view to avoid duplication
- [x] **[L1]** Add `rel="noopener noreferrer"` to `print_vd` link in `MetaData.php`
- [x] **[L2]** Add `role_gestion_vd` and `role_pilote_vd` entries to all three language files
- [x] **[L3]** Document the `<= 1` threshold in the pilote selector fallback
