# Code Review — PR #46 fix/auto_planchiste

**Date**: 2026-03-19
**Branch**: `fix/auto_planchiste` → `main`
**Scope**: Enable `auto_planchiste` role to create/edit their own ULM flights and see the relevant menu/dashboard entries.

---

## Summary

The PR correctly pre-computes role variables in `welcome.php::index()` and passes them to views, following the established pattern for `is_ca`, `is_treasurer`, etc. The `RequiredRole` mechanism in `MetaData.php` is updated to use `user_has_role()` for new-auth compatibility. Overall approach is sound.

---

## Issues

### 🔴 BLOCKER — `index.php`: ENVIRONMENT left in development mode

**File**: `index.php`

```diff
-// define('ENVIRONMENT', 'development');
-define('ENVIRONMENT', 'production');
+define('ENVIRONMENT', 'development');
+// define('ENVIRONMENT', 'production');
```

ENVIRONMENT must be `'production'` before merge. Development mode exposes error details, disables OPcache optimizations, and can leak stack traces to end users.

---

### 🔴 HIGH — `welcome.php`: `is_planchiste` not initialised in legacy-auth else block

**File**: `application/controllers/welcome.php` (lines 123–130)

`is_auto_planchiste = false` is added in the legacy `else` block, but `is_planchiste` is not. `bs_dashboard.php` passes both to `bs_menu`:

```php
$this->load->view('bs_menu', array(
    'is_planchiste'      => $is_planchiste,   // undefined for legacy-auth users
    'is_auto_planchiste' => $is_auto_planchiste,
));
```

For a legacy-auth user this triggers an `E_NOTICE: Undefined variable` and silently evaluates to `NULL` (falsy). Add:

```php
$data['is_planchiste'] = $this->dx_auth->is_role('planchiste');
```

---

### 🟠 MEDIUM — `vols_avion.php`: frozen-flight check is immediately overwritten

**File**: `application/controllers/vols_avion.php` (lines 306–307)

```php
$action = (count($this->ecritures_model->select_flight_frozen_lines($id, "vol_avion"))) ? VISUALISATION : MODIFICATION;
$action = MODIFICATION;   // ← overwrites the result above unconditionally
```

Line 306 is dead code. The frozen-flight guard is silently bypassed, meaning auto_planchiste (and everyone else) can edit flights that have accounting entries. Either remove line 306 if the freeze logic is intentionally disabled, or remove line 307 to restore the intended behaviour.

---

### 🟡 LOW — `vols_avion.php`: `modification_level` save/restore is fragile

**File**: `application/controllers/vols_avion.php` (lines 308–315)

```php
if ($bypass_modification_level) {
    $saved_level = $this->modification_level;
    $this->modification_level = '';
    parent::edit($id, FALSE, $action);
    $this->modification_level = $saved_level;
}
```

If `parent::edit()` throws or calls `show_404()` / `redirect()`, `$this->modification_level` is never restored. This leaves the object in a corrupted state for any code that runs afterwards. In the current flow `parent::edit()` either exits via redirect or returns normally, so the risk is theoretical. The intent is correct and the trade-off is acceptable given the constraints of the framework, but it should be documented.

---

### 🟢 COSMETIC — `Gvv_Authorization.php`: unrelated blank line removal

**File**: `application/libraries/Gvv_Authorization.php`

One blank line removed inside `allow_roles()`. Unrelated to the PR. No functional impact.

---

## Checklist

| # | Severity | File | Status |
|---|----------|------|--------|
| 1 | 🔴 BLOCKER | `index.php` — ENVIRONMENT=development | Must fix before merge |
| 2 | 🔴 HIGH | `welcome.php` — `is_planchiste` missing in legacy else block | Must fix |
| 3 | 🟠 MEDIUM | `vols_avion.php` — frozen-flight check overwritten (dead code) | Fix or document intent |
| 4 | 🟡 LOW | `vols_avion.php` — modification_level save/restore fragility | Accept with comment |
| 5 | 🟢 COSMETIC | `Gvv_Authorization.php` — unrelated blank line | Ignore |
