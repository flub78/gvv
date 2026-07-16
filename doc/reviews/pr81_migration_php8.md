# Code Review — PR #81 "Migration php8"

- **Branch**: `migration_php8` → `main`
- **Date**: 2026-07-16
- **Scope**: 34 files changed, +778/-36 (`git diff origin/main...HEAD`)
- **Commits**: 84d60077, 3e7bcd4a, 0d10367c, faf5c648, 766a9d19

## Summary

The PR upgrades GVV's CodeIgniter 2 core and application code to run under
PHP 8 (target 8.4). The individual compatibility fixes are sound, narrowly
scoped, and well commented (crypt() salt, mysqli error-reporting mode,
`E_STRICT` constant deprecation, `str_replace`/`htmlspecialchars` null
deprecations, required-after-optional parameter fixes, `is_callable` →
`method_exists` in `CI_Migration`). However, the branch also carries two
leftover artifacts that must not reach `main`/production, plus some
unrelated feature work bundled into the migration commits.

## Findings (most → least critical)

### 1. `index.php` — ENVIRONMENT flipped to `"development"` [CRITICAL]
```diff
-define("ENVIRONMENT", "production");
+define("ENVIRONMENT", "development");
```
Introduced in `faf5c648`. If merged as-is, the production/default checkout
runs with CodeIgniter's development error display: full stack traces,
file paths, and query errors get rendered straight into HTTP responses.
This looks like a leftover from debugging the PHP 8.4 Playwright run
(per `CLAUDE.md`'s own workflow: "enable the development mode in index.php,
reproduce the error, fix it") that never got reverted.
**Action**: revert to `define("ENVIRONMENT", "production");` before merging.

### 2. Binary PDF committed to `tmp/` [HIGH]
```
tmp/adhesion aeroclub.pdf | Bin 0 -> 566524 bytes
```
Added in `faf5c648`, alongside the ENVIRONMENT change — almost certainly a
test artifact generated while chasing the PDF-related Playwright failures
that got `git add`-ed by accident (space in the filename, sits under `tmp/`
which is otherwise gitignored-by-convention but has no actual `.gitignore`
entry).
**Action**: `git rm "tmp/adhesion aeroclub.pdf"`, and add `tmp/` to
`.gitignore` so this class of accidental commit can't recur.

### 3. Unrelated feature work bundled into the migration commits [MEDIUM]
- `application/controllers/forms_admin.php`: adds a club-scoping bypass
  (`$allow_workflow_bypass`) to `load_form_or_redirect()` for
  `submission_pdf()`, so admins can view PDFs of cross-club "workflow"
  forms (e.g. `briefing-passager-ulm`). This is a legitimate, well-commented
  change in isolation, but it's a permissions/authorization change, not a
  PHP 8 compatibility fix, and it's folded into commit `0d10367c`
  ("Migration php8. 100% tests OK en php7").
- `doc/prototypes/prototype_bulletin_adhesion.html` (615 lines, new file)
  and its refinement in commit `766a9d19` ("Bulletin d'adhésion") is
  unrelated to the PHP 8 migration entirely.

Neither is a bug, but mixing an authorization change and a new prototype
into a "migration" PR makes it harder to review, bisect, and revert
independently if either needs to be rolled back later.
**Action**: consider splitting into separate PRs/commits going forward, or
at minimum note the scope expansion in the PR description.

### 4. `DX_Auth::_crypt_salt()` — verify against target deployment [LOW / informational]
```php
function _crypt_salt() {
    $chars = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $salt = '$1$';
    for ($i = 0; $i < 8; $i++) {
        $salt .= $chars[random_int(0, 63)];
    }
    return $salt . '$';
}
```
This correctly restores PHP 7.4's implicit MD5-crypt behavior (`crypt()`'s
salt argument became mandatory in PHP 8) and is required because
`users.password` is `varchar(34)`, which only fits an MD5-crypt hash, not
bcrypt's 60 chars. The fix is consistent with the constraint but it does
mean new/changed passwords keep using MD5-crypt indefinitely — a
pre-existing weakness the PHP 8 migration preserves rather than
introduces. Worth a follow-up ticket to widen the column and move to
`password_hash()`/bcrypt, but out of scope for this PR.

## Verified as correct / no action needed

- `system/core/Common.php`, `system/core/Exceptions.php`: `E_STRICT`
  replaced with its literal value `2048` (constant reference deprecated in
  8.4). Value is stable across PHP versions; safe.
- `system/database/drivers/mysqli/mysqli_driver.php`: `mysqli_report(MYSQLI_REPORT_OFF)`
  restores CI2's expected "return FALSE on error" contract instead of PHP
  8.1's default `mysqli_sql_exception` throwing.
- `system/libraries/Migration.php`: `is_callable` → `method_exists`, since
  `is_callable(array($class, $method))` with a class *name* string no
  longer resolves non-static instance methods as callable in PHP 8.
  Confirmed the three new/changed `migration_lang.php` keys
  (`migration_none_found`, `migration_not_found`, etc.) are consumed by
  this same file's `error_string()`.
- `application/models/comptes_model.php`, `ecritures_model.php`,
  `application/libraries/MetaData.php`: removed defaults on parameters that
  precede required ones (deprecated in PHP 8). Checked all call sites
  (`comptes.php`, `openflyers.php`, `Gvv_Controller.php`) — every caller
  already passes the affected arguments explicitly, so this is a
  behavior-neutral compatibility fix.
- `system/core/Output.php`, `system/helpers/form_helper.php`: `(string)`
  cast / explicit `NULL` check guard against PHP 8.1's null-to-non-nullable-
  parameter deprecations in `str_replace`/`htmlspecialchars`. Both preserve
  prior (silent-coercion-to-`''`) behavior.
- `#[AllowDynamicProperties]` added to CI2 core classes and models that rely
  on dynamic property assignment (deprecated in PHP 8.2). Mechanical,
  correctly targeted at the classes that need it.
- Playwright spec updates (`*-authorization.spec.js`): move
  `vols_planeur/statistic` from denied- to granted-access, matching a prior
  application change (commit `a3ea57af`) rather than PHP 8 itself; test
  now correctly reflects app behavior. `saisie-cotisation.spec.js`: added
  a `waitForResponse` around the AJAX-triggered DOM rebuild — a legitimate
  flake fix, not migration-related, but low-risk.

## Suggested todo (ranked)

1. [ ] Revert `index.php` `ENVIRONMENT` to `"production"`.
2. [ ] Remove `tmp/adhesion aeroclub.pdf` from git history/working tree; add `tmp/` to `.gitignore`.
3. [ ] Confirm whether the `forms_admin.php` workflow-bypass and the bulletin d'adhésion prototype should stay in this PR or be split out.
4. [ ] (Follow-up, separate ticket) Track the `users.password` column width / bcrypt migration if the project wants to move off MD5-crypt.
