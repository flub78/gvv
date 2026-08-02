# Code Review — PR #84 "tarifs refactoring MVP"

- **Branch**: `refactoring/products` → `main`
- **PR**: https://github.com/flub78/gvv/pull/84 (open, head `9492eeb3`)
- **Scope**: 60 files changed (2542 insertions / 542 deletions) — splits the
  `tarifs` table into `produits` (product identity) + `tarifs` (dated price
  history): 4 migrations (146–149), new `Produits_model`/`Produits` controller/
  views, `Tarifs_model` rewritten as a compatibility façade, ~10 call sites
  switched to joins, a new `MY_Migration` error-handling library, design docs,
  and PHPUnit/Playwright coverage.

## Summary

The refactor is methodically executed: a design note and data audit precede
the schema change, the destructive migration (149) only runs after a
documented backup and a facturation non-regression check (before/after
re-invoicing comparison on real vols), and the `Tarifs_model` façade
preserves existing method signatures so ~15 call sites needed no changes.
Test coverage is substantial (new MySQL integration tests, a new Playwright
CRUD flow, and skip-guards added to historical migration-replay tests that
manipulate now-removed columns).

The one finding worth fixing before merge is a silent-failure gap on
`produits` deletion, newly exposed by the FK constraint this PR introduces.
The rest are minor documentation/maintainability notes.

## Findings (most → least critical)

### 1. Deleting a produit with existing tarifs silently fails, with no error shown to the user [MEDIUM]

`application/migrations/147_tarifs_add_produit_id.php` adds
`fk_tarifs_produit FOREIGN KEY (produit_id) REFERENCES produits(id)` with no
`ON DELETE` clause, so it defaults to InnoDB's `RESTRICT`: MySQL refuses to
delete a `produits` row while any `tarifs` row still references it.

`Gvv_Controller::delete()` (`application/libraries/Gvv_Controller.php:269-283`,
unchanged by this PR but now reachable for `produits` via the generic CRUD
`Produits` controller) calls `$this->gvv_model->delete(...)` and then
unconditionally redirects to the list page:

```php
function delete($id) {
    if (!$this->ensure_modification_rights(MODIFICATION)) { return; }
    $this->pre_delete($id);
    $this->gvv_model->delete(array($this->kid => $id));
    $this->pop_return_url();
    redirect($this->controller . "/page");
}
```

`Common_Model::delete()` (`application/models/common_model.php:157-161`)
ignores the return value of `$this->db->delete()`:

```php
function delete($where = array()) {
    ...
    $this->db->delete($this->table, $where);
}
```

Since `db_debug = FALSE` in `application/config/database.php` (already noted
in this PR's own `MY_Migration.php` docblock), a query that fails a
constraint check returns `FALSE` without raising a PHP error — nothing stops
the redirect. The result: clicking "Delete" on a produit row that still has
price history does nothing, the list reloads, the row is still there, and no
message tells the user why. This directly contradicts CLAUDE.md guideline
#14 ("Never reject an action silently. The result of every action must be
obvious to the user.").

This is the common case, not an edge case: every produit populated by
migration 146 originates from at least one `tarifs` row, so any club's
real product catalog will hit this on first attempt to delete a product.
It wasn't caught by `playwright/tests/produits-tarifs-crud.spec.js` because
that test deletes the tarif *before* deleting the produit (step 8 then 9),
so the FK never blocks the delete in the tested path.

**Suggested fix**: either check `$this->db->affected_rows()` /
`$this->db->_error_message()` after the delete in `Produits::delete()` (or a
`Produits_model::delete()` override) and redirect back with an explicit
error message when it's blocked, or add `ON DELETE RESTRICT` handling
upstream (e.g. controller-side check for existing tarifs before allowing the
delete button / attempt).

### 2. Stale comment describing an implementation that migration 149 simplified away [LOW]

`application/tests/mysql/PaiementsEnLigneCotisationPiloteTest.php`, around
the "Toggle off" step:

```php
// Toggle off — is_cotisation vit désormais sur produits (identité du
// produit), get_cotisation_products_for_section() la lit en priorité
// dès qu'un produit est lié (COALESCE(produits.x, tarifs.x)).
```

This describes the étape-7 implementation (`LEFT JOIN` + `COALESCE`), but
étape 12 (migration 149, once `produit_id` is `NOT NULL` and the legacy
columns are gone) simplified `get_cotisation_products_for_section()` /
`get_cotisation_product_by_id()` in `application/models/tarifs_model.php` to
a plain `INNER JOIN produits ON produits.id = tarifs.produit_id` — there's no
`COALESCE` left in the current code. The comment should be updated so a
future reader doesn't go looking for the fallback logic it describes.

### 3. `MY_Migration` duplicates CI core's constructor and `version()` almost verbatim [LOW, informational]

`application/libraries/MY_Migration.php` reimplements
`CI_Migration::__construct()` and `version()` line-for-line rather than
extending them, because `CI_Migration`'s own guard
(`if (get_parent_class($this) !== FALSE) return;`) blocks any subclass from
reusing `parent::__construct()`. The docblock already explains this
trade-off, so it's a deliberate and documented choice, not an oversight —
flagging only as a maintenance note: since `system/libraries/Migration.php`
is off-limits to modify (per CLAUDE.md), any future CodeIgniter core upgrade
that touches `Migration::version()` won't automatically propagate here, and
this file should be diffed against the new core version when that happens.

### 4. `Produits_model::select_page()` ignores its `$nb`/`$debut` pagination parameters [LOW, pre-existing pattern]

`application/models/produits_model.php` (new file) accepts `$nb`/`$debut`
but never calls `->limit()`, so pagination is a no-op — every produit is
returned regardless of page. This isn't a regression introduced by this PR:
`Tarifs_model::select_page()` on `main` (before this PR) has exactly the
same gap. It's just now duplicated into a second model. Not blocking, but
worth fixing in both places once a club's catalog is large enough for it to
matter.

## Not flagged (checked, found to be pre-existing / out of scope)

- `Achats_model`'s joins (`achats JOIN produits ON achats.produit =
  produits.reference JOIN tarifs ON tarifs.produit_id = produits.id`) don't
  filter by club and join against the full price-history table without a
  date bound — but this reproduces the exact same lack of filtering that was
  already present in the pre-PR `FROM achats, tarifs WHERE achats.produit =
  tarifs.reference`. Not introduced by this refactor.
- Raw string date interpolation in `Tarifs_model::get_tarif()`
  (`"tarifs.date <= \"$date\""`) is unparameterized, but it's an unchanged
  carry-over from the pre-PR `get_tarif()`, not new exposure.
- The `achats/create` `DivisionByZeroError` (no `amount`/`pilot` params) is
  explicitly called out in the plan doc as pre-existing and unrelated —
  confirmed by `git diff` showing that code path untouched.

## What's good

- Migrations 146–148 all have explicit guard checks (row-count reconciliation
  in 146, `produit_id IS NULL` check in 147/148) that throw rather than
  silently leaving inconsistent data.
- `Tarifs_model::create()`/`update()` now whitelist writable columns
  (`filter_price_fields()`) instead of passing arbitrary form data through to
  `$this->db->update()` — tightens what the price-history façade can write.
- Good test hygiene: skip-guards added to 4 historical migration-replay tests
  that manipulate now-removed columns, with clear `markTestSkipped()`
  messages explaining why, rather than deleting or leaving them red.
- `application/libraries/Database.php`'s backup/restore table list was
  updated to include `produits` ahead of `tarifs` — easy to miss now that
  `tarifs` has a real FK dependency on it.

## Todo (tracked by criticality)

- [x] Fix #1 — silent-failure produit delete when tarifs still reference it.
      `Produits::delete()` (`application/controllers/produits.php`) now
      checks `tarifs_model->count(array('produit_id' => $id))` before
      deleting and, if non-zero, sets a translated flashdata popup
      (`gvv_produits_delete_has_tarifs` in `produits_lang.php` FR/EN/NL) and
      redirects back to the list instead of attempting the blocked delete.
      `produits/bs_tableView.php` now renders `checkalert($this->session)` so
      the popup is actually shown (it previously wasn't wired on that view).
      Covered by a new negative-path step in
      `playwright/tests/produits-tarifs-crud.spec.js` (run against gvv.net:
      delete attempt on a produit with a tarif is blocked with the message,
      product and tarif both still present; delete succeeds once the tarif
      is removed). Full `./run-all-tests.sh` green (1601/1601, 64 skips).
- [x] Fix #2 — stale COALESCE comment in `PaiementsEnLigneCotisationPiloteTest.php`
      updated to describe the current `INNER JOIN` (no `COALESCE`). While
      verifying it, found and fixed a related regression it had been masking:
      `setUpBeforeClass()` gated the *entire* test class on `tarifs.is_cotisation`
      existing — a guard meant to skip when migration 099 hadn't run yet, but
      migration 149 (this same PR) permanently moved that column to
      `produits.is_cotisation`, so the guard was unconditionally skipping all
      4 tests (cotisation debit/insufficient-balance/duplicate-rejection),
      silently dropping coverage. Guard removed; 3 of the 4 tests now run and
      pass, the 4th keeps its own pre-existing, legitimate skip (insufficient
      test-pilot balance). `./run-all-tests.sh`: 1604/1604 passed, 0 failed,
      61 skips (down from 64 — the 3 recovered tests).
- [x] Note #3 — added `application/tests/unit/libraries/MyMigrationCoreCompatibilityTest.php`,
      a canary test that asserts the specific `CI_Migration` assumptions
      `MY_Migration.php` relies on (subclass-init guard, protected
      properties it reads/writes, `version()`'s signature) via reflection on
      the core class. A future CodeIgniter core upgrade that breaks any of
      these now fails this test immediately instead of silently drifting —
      turns the "re-check on next upgrade" reminder into something
      automatic rather than relying on memory.
- [x] Note #4 — **retracted, not a bug.** `application/libraries/MetaData.php:501`
      ("pagination, obsolete dans la plupart des cas, on utilise datatable")
      confirms this is deliberate: any table rendered with the `datatable`
      CSS class (as `produits`/`tarifs` are) is paginated/sorted/searched
      client-side by DataTables.js over the *full* result set, so
      `select_page()` intentionally skips a SQL `LIMIT`. `membres_model.php`
      shows the same convention explicitly, with `->limit($nb, $debut)`
      present but commented out. Adding a real `LIMIT` here would have
      broken client-side search/sort across the full list, not fixed
      anything — no code change made.
