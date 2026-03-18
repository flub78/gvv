# Code Review – Horamètre Repopulation Fix

**Date**: 2026-03-18
**Branch**: `fix/horametre_not_repopulated`
**Files reviewed**:
- `application/controllers/vols_avion.php`
- `application/views/vols_avion/bs_formView.php`

---

## Summary

Two changes fix the issue where horamètre (début and fin) values entered by the user were not preserved when the flight creation form was redisplayed after a validation error.

The bug had two layers:
1. **PHP layer**: `form_static_element()` unconditionally overwrote `$this->data['vacdeb']` with the latest horamètre from the database during CREATION, even when redisplaying after validation failure.
2. **JS layer**: On every page load, `update_hora_format()` reset `#debut` and `#fin` to `horametres_last_data[machine]` whenever `is_new_vol === true`. Since `is_new_vol` was derived solely from `vaid == 0`, it remained `true` on validation-error redisplay, overwriting user-entered values.

---

## Issues Found

### [CRITICAL] PHP check uses falsy evaluation — fails for horamètre value of 0

**File**: `application/controllers/vols_avion.php:102`

```php
// Current (buggy edge case):
if (CREATION == $action && !$this->input->post('vacdeb')) {

// Problem: in PHP, !'0' === true (the string '0' is falsy)
// So if the user enters vacdeb=0, this condition is met and the value is
// overwritten by latest_horametre() instead of preserving the user's entry.

// Fix:
if (CREATION == $action && $this->input->post('vacdeb') === false) {
```

In PHP, the string `'0'` is one of the few non-empty strings that evaluates to `false`. An horamètre of exactly `0` (first-ever flight on a new aircraft) would trigger the wrong branch. The view fix correctly uses `=== false`; the controller fix should match.

---

### [MEDIUM] Inconsistency between the two checks

**Files**: both

The PHP controller uses `!$this->input->post('vacdeb')` (falsy), while the view uses `$this->input->post('vacdeb') === false` (strict). This inconsistency means the two layers can diverge in edge cases (e.g. vacdeb=0). They should use the same logic.

---

### [LOW] Business logic in the view

**File**: `application/views/vols_avion/bs_formView.php:300`

```php
var is_new_vol = <?= (isset($vaid) && $vaid == 0 && $this->input->post('vacdeb') === false) ? 'true' : 'false' ?>;
```

Calling `$this->input->post()` directly in a view is a minor MVC violation. The controller should compute `$is_new_vol` and pass it to the view via `$this->data`. This would also make the view more testable and the logic easier to read.

Suggested refactor in `Vols_avion::create()` and `form_static_element()`:
```php
// In controller:
$this->data['is_new_vol'] = ($action == CREATION && $this->input->post('vacdeb') === false);

// In view:
var is_new_vol = <?= !empty($is_new_vol) ? 'true' : 'false' ?>;
```

---

### [LOW] `vacdeb` is used as the sole indicator of "form previously submitted"

**Both files** use the presence of a `vacdeb` POST field to determine whether a validation error redisplay is occurring. This is implicit coupling between the horamètre field and the re-display detection logic. A more explicit approach would be to pass a dedicated `$validation_failed` flag from the controller, making the intent clearer and decoupling it from a specific field.

---

## Todo List (ordered by criticality)

| Priority | Issue | Status |
|----------|-------|--------|
| CRITICAL | Fix PHP falsy check: use `=== false` instead of `!` in `vols_avion.php:102` | done |
| MEDIUM | Make both checks consistent (`=== false` in both places) | done |
| LOW | Move `is_new_vol` computation from view to controller | done |
| LOW | Use an explicit `$validation_failed` flag instead of inferring from `vacdeb` POST presence | done |
