# Code Review — Facturation ULM (branch `test/facturation_vols_ulm`)

**Date**: 2026-03-17
**Branch**: `test/facturation_vols_ulm`
**Files reviewed**:
- `application/libraries/Facturation.php`
- `application/libraries/Facturation_aces.php`
- `application/views/vols_planeur/bs_formView.php`
- `playwright/tests/ulm-billing-scenarios.spec.js`

---

## Summary

The branch adds two new free-of-charge flight categories (BIA = 6, porte ouverte = 5) to both billing libraries, temporarily hides the payer/percentage section in the glider form view, and introduces a Playwright end-to-end test suite for ULM billing scenarios.

---

## Issues Found

### P1 — Critical

| # | File | Issue |
|---|------|-------|
| 1 | `ulm-billing-scenarios.spec.js` | **SQL injection in `mysqlRows()`**: SQL queries are built by string interpolation (e.g., `pilote = '${pilotLogin}'`, `vaobs = '${observation}'`). `observation` embeds `machine.immat` read from the database. Any special character (quote, backslash) in an immat would break or inject the query. Use parameterised queries or at minimum escape values with `mysql_real_escape`-equivalent. |
| 2 | `ulm-billing-scenarios.spec.js` | **MySQL password exposed in process list**: `-p${DB.password}` on the command line is visible in `ps`. Use `MYSQL_PWD` environment variable or a `--defaults-extra-file` approach instead. |

### P2 — Major

| # | File | Issue |
|---|------|-------|
| 3 | `Facturation.php` | **Magic numbers instead of named constants**: Categories 5 and 6 are written as raw integers (`(int) $vol['vacategorie'] === 6`), while categories 1–3 already have named constants `VI`, `VE`, `REM` defined in `application/config/constants.php`. Two new constants (`PO = 5`, `BIA = 6`) should be added there and used consistently. |
| 4 | `Facturation_aces.php` | Same issue as #3. The ACES subclass should also reference the constants. |
| 5 | `Facturation.php` | **Inconsistent comparison style**: Existing checks use loose `==` (e.g., `$vol['vacategorie'] == VI`), the new code uses `(int) cast + ===`. Mixing comparison styles in the same if-chain is confusing and could mask type bugs. Adopt one convention (preferred: use named constants with `==` as already done in the rest of the chain). |
| 6 | `Facturation_aces.php` | Same inconsistency as #5. |
| 7 | `bs_formView.php` | **Hidden UI fields still submitted in form**: The payer/percentage block is wrapped in `<div style="display:none">` but remains in the DOM. Hidden inputs are still included in form submissions, so stale payer/percentage values may be persisted to the database silently. If the feature is intentionally disabled, the fields should be conditionally excluded server-side (in the controller/view PHP logic), not hidden in CSS. |
| 8 | `ulm-billing-scenarios.spec.js` | **Single monolithic test case**: All scenarios × all machines are executed inside one `test()`. A mid-run failure aborts all remaining combinations with no partial output. Each scenario (or at least each machine) should be a separate test case, which Playwright supports via `test.each`. |

### P3 — Minor / Style

| # | File | Issue |
|---|------|-------|
| 9 | `Facturation.php` | **Hardcoded label strings**: `" BIA"` and `" PO"` are hardcoded English/abbreviation strings. Other categories use `$this->CI->lang->line("facturation_vi")` etc. Language keys `facturation_bia` and `facturation_po` should be added to the three language files for consistency. |
| 10 | `Facturation_aces.php` | Same as #9. |
| 11 | `ulm-billing-scenarios.spec.js` | **`page.waitForTimeout(300)` polling loop**: The retry loop for flight persistence (`for i < 10 … waitForTimeout(300)`) is an anti-pattern in Playwright. Use `expect.poll()` for cleaner, failure-message-rich retrying. |
| 12 | `ulm-billing-scenarios.spec.js` | **`waitForLoadState('networkidle')` reliability**: `networkidle` is known to be flaky for SPAs and dynamic pages. Prefer explicit selector waits or `domcontentloaded`. |
| 13 | `ulm-billing-scenarios.spec.js` | **Test data not cleaned up**: Flights created during the test are never deleted, polluting the test database across runs. Add a teardown or delete-by-observation query in an `afterAll` hook. |
| 14 | `ulm-billing-scenarios.spec.js` | **Duplicated section constant**: `ULM_SECTION_ID = 2` and `TEST_ADMIN.section = '2'` express the same value in two different forms. Reference one from the other. |
| 15 | `bs_formView.php` | **Temporary hack without a tracking reference**: The comment `<!-- hidden: remove wrapping div to re-enable payeur/pourcentage -->` signals this is intentionally temporary, but there is no issue/ticket reference. This risks the hidden block being forgotten. |

---

## Todo List (decreasing criticality)

- [x] **P1-1** Fix SQL injection in `mysqlRows()` — added `escapeSqlString()` helper; applied to all string interpolations (`pilotLogin`, `reference`, `date`, `observation`).
- [x] **P1-2** Move MySQL password out of command-line args (use `MYSQL_PWD` env var). (rejected only test passwords are exposed)
- [x] **P2-3/4** Add `PO` and `BIA` constants to `application/config/constants.php`; replace magic numbers in both billing libraries.
- [x] **P2-3/4** Add `PO` and `BIA` constants to `application/config/constants.php`; replace magic numbers in both billing libraries.
- [x] **P2-5/6** Standardise category comparison style across the if-chain in both libraries.
- [ ] **P2-7** Remove hidden payer/percentage block from the DOM or gate it properly server-side; document the reason.
- [x] **P2-8** Split Playwright monolithic test into per-scenario test cases using `test.each`.
- [ ] **P3-9/10** Add `facturation_bia` / `facturation_po` keys to all three language files; use `lang->line()` in both billing libraries.
- [ ] **P3-11** Replace polling loop with `expect.poll()`.
- [ ] **P3-12** Replace `waitForLoadState('networkidle')` with explicit element waits.
- [ ] **P3-13** Add `afterAll` teardown to delete test flights from the database.
- [ ] **P3-14** Deduplicate `ULM_SECTION_ID` / `TEST_ADMIN.section`.
- [ ] **P3-15** Add a ticket/issue reference to the hidden-block comment.
