# Code Review — PR #52 Fix/affichage selectif (2026-03-20)

**Branch:** `fix/affichage_selectif`
**Commits:**
- `b654a09` — HEVA n'est affiché que pour les sections planeur
- `e79944c` — droit backup_db

**Files changed:** `migration.php`, `welcome.php`, `views_helper.php`,
`084_backup_db_role.php`, `bs_dashboard.php`, `bs_menu.php`

---

## Summary

Two independent changes:

1. **HEVA submenu** — restricted to sections with `gestion_planeurs` flag active.
2. **backup_db role** — new global role granting access to the database backup page
   (restore and migration remain admin-only).

---

## Issues

### CRITICAL

#### C1 — `Admin` controller always requires `is_admin()` — `backup_db` users are blocked

`application/controllers/admin.php` extends `CI_Controller` and enforces
`$this->dx_auth->is_admin()` in its constructor. Any user with only the
`backup_db` role will be denied access when clicking "Sauvegarde des données"
(`admin/backup_form`), despite the menu/dashboard showing the link.

```php
// admin.php constructor (unchanged by this PR)
if (!$this->dx_auth->is_admin()) {
    $this->dx_auth->deny_access();
    return;
}
```

The menu and dashboard visibility are correct, but the feature is non-functional
for `backup_db` users without admin. The controller-level guard must be relaxed
for `backup_form` (and `backup`, `backup_media`) to allow `backup_db` users.

---

### HIGH

#### H1 — Missing language keys for `role_backup_db` in all three language files

The migration registers `translation_key = 'role_backup_db'` but the key is
absent from `french/`, `english/`, and `dutch/` sections language files.
Confirmed by log entries:

```
ERROR - 2026-03-20 19:20:31 --> Could not find the language line "role_backup_db"
```

Requires adding `$lang['role_backup_db']` (and optionally a short variant) to
all three files.

#### H2 — Migration description does not match final access policy

The docblock in `084_backup_db_role.php` says:

> backup_db: global-scoped role granting access to **backup, restore, and
> database migration** pages

But the actual implementation (after the last revision) grants access only to
**backup**; restore and migration remain admin-only. The description should be
corrected to avoid confusing future maintainers.

#### H3 — `backup_db`-only users see "Rapports" submenu with almost no content

The outer Gestion menu condition was widened to `has_role('ca') || has_role('backup_db')`.
Inside that menu, the "Rapports" submenu has no role guard on its container —
it is always rendered for anyone who enters the Gestion menu. Items inside
are individually guarded by `ca`/`bureau`, so a `backup_db`-only user sees
the submenu header with a single "Alarmes" item.

This is unexpected UX. The "Rapports" container should also verify at least
one of its items is accessible before rendering, or the container itself
should be guarded by `has_role('ca') || has_role('bureau')`.

---

### MEDIUM

#### M1 — `has_role()` fallback uses `section_id = NULL`, bypassing section context

The new fallback path in `views_helper.php`:

```php
if ($CI->session->userdata('use_new_auth')) {
    $CI->load->library('Gvv_Authorization');
    $user_id = $CI->dx_auth->get_user_id();
    return $CI->gvv_authorization->has_role($user_id, $role, NULL);
}
```

Calling `has_role(..., NULL)` in `Gvv_Authorization` fetches *all* user roles
with no section filter. For global roles (`backup_db`) this is correct.
For section-scoped roles (`ca`, `tresorier`), it may over-grant: a user who
is `ca` only in section 1 will appear as `ca` even when browsing section 2.

This matches legacy `dx_auth` behaviour (which is also section-agnostic), so
it does not introduce a regression. However, it diverges from the
`user_has_role` path (which receives the controller's `section_id`).
A comment explaining this limitation is warranted.

#### M2 — `is_backup_db` legacy path always returns `false`

```php
$data['is_backup_db'] = $this->dx_auth->is_role('backup_db'); // legacy fallback
```

`backup_db` is only stored in `types_roles` / `user_roles_per_section`; it
does not exist in the legacy dx_auth role table. Legacy users can therefore
never obtain `is_backup_db = true` via this path. The dashboard card will
remain hidden for any non-migrated user regardless of any manual role
assignment.

This is arguably correct behaviour (backup_db is explicitly a new-auth role),
but it is undocumented and may surprise future maintainers. A short comment
would clarify intent.

---

### LOW

#### L1 — Indentation inconsistency in `bs_menu.php` after HEVA split

The `<?php if (has_role('ca')) : ?>` block for the "Admin club" submenu
(line 128) now has a different leading indentation level than the surrounding
`<?php if/endif ?>` blocks. The structural split of the former single
`has_role('ca')` block introduced inconsistent whitespace.

#### L2 — Stray blank line in `bs_menu.php` system submenu (line 180)

An empty line was left between "Sauvegarde des données" and the
`<?php if (has_role('admin')) : ?>` guard inside the Système submenu.
Minor but breaks visual consistency with other submenus.

---

## Todo (by decreasing criticality)

- [x] **C1** — `Admin::__construct()` now checks `backup_db` via
  `_has_backup_db_role()` for `backup_form`, `backup`, `backup_media` before
  denying access.
- [x] **H1** — `$lang['role_backup_db']` added to `french/`, `english/`, and
  `dutch/` `gvv_lang.php` files.
- [x] **H2** — Docblock in `084_backup_db_role.php` corrected.
- [x] **H3** — "Rapports" submenu container guarded with
  `has_role('ca') || has_role('bureau') || has_role('admin')`.
- [x] **M1** — Comment added to the `has_role()` fallback in `views_helper.php`.
- [x] **M2** — Comment added to the legacy `$data['is_backup_db']` line in
  `welcome.php`.
- [x] **L1** — Indentation normalised around `has_role('ca')` Admin-club block.
- [x] **L2** — Stray blank line removed from Système submenu.
