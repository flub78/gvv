# Code Review — PR #94 "Tampon de l'association sur le PDF des formulaires"

- **Branch**: `feature/tampon-formulaires` → `main`
- **PR**: https://github.com/flub78/gvv/pull/94 (open, head `f438bec2`)
- **Scope**: 19 files (1176 insertions / 14 deletions). Code: `forms_admin.php`,
  `Forms_file_storage.php`, `Forms_renderer.php`, three views, fr/en/nl language files,
  one SVG. Tests: 2 new PHPUnit classes, 1 extended, 1 new Playwright spec. Docs: PRD
  EF19, design §23, plan Lot 17, user docs.

## Summary

The feature is small and fits the existing module patterns:
- The `data-gvv-type` widget convention is the same one used by the signature, payment
  and sub-form widgets.
- Storage reuses `Forms_file_storage`, with fixed file names and a deny-all `.htaccess`.
- Uploads go through `$_FILES` directly, with the same guard as `image_upload()`.

The security-relevant property holds: the stamp image is never reachable from a public
URL. It sits outside `.commun/`, no route serves `.tampons/`, and the public form only
shows the author's placeholder. Stamp resolution follows the form's own section
(`forms.club`), not the session's, so a regenerated PDF always gets the same stamp.
Tests cover storage, rendering rules, upload validation, and the full browser flow down
to the PDF alpha mask.

No blocking defect was found. The most important point is not new to this PR:
`forms_admin` has no CSRF protection. It matters more here than elsewhere because the
asset is an authentication mark that an attacker could replace or delete. The other
findings are about overwrite safety, PNG parsing robustness, and minor duplication.

## Findings (most → least critical)

### 1. Stamp upload/delete can be forged cross-site [MEDIUM, systemic — pre-existing]

`csrf_protection` is `FALSE` (`application/config/config.php:300`), and the session cookie
has no `SameSite` attribute. `stamp_upload()` and `stamp_delete()` only check that the
request is a POST, so any page an admin visits while logged in can send one. A
`multipart/form-data` `fetch(..., {mode: 'no-cors', credentials: 'include'})` is a CORS
"simple request": no preflight, and the cookie is sent wherever the browser does not
default to `SameSite=Lax`. Chromium defaults to Lax; Firefox does not. The attacker
could:
- replace the association stamp with an image of their choice, which then shows up on
  every attestation PDF;
- delete it.

The flaw is shared by every POST action in `forms_admin`, and by most of GVV. The stamp
simply makes the impact concrete: it is precisely the mark that certifies a document.

**Mitigation**:
- **Narrow fix in this PR**: check `Origin`/`Referer` against `base_url()` in
  `stamp_upload()`/`stamp_delete()`. The same few lines could later become a
  `Gvv_Controller` helper.
- **Systemic fix**: set `SameSite=Lax` on the session cookie, or enable CI's CSRF
  protection. Both are separate issues because they affect every form.

### 2. Replacing a stamp is irreversible and unconfirmed [LOW-MEDIUM]

`write_stamp()` overwrites the scope's single file. The "Remplacer" button has no
confirmation and no previous version is kept. This happened during this PR's own
validation: a stamp uploaded through the UI was overwritten a minute later by a script,
and the original content could not be recovered. Because the stamp is a legal mark,
losing the good image silently is worse than for a form logo.

**Mitigation**: keep one level of history (`global.prev.png`, restorable from the
card), or at least ask for confirmation when a stamp already exists for the scope.
Either one adds a small PRD point under EF19.

### 3. `png_has_transparency()` searches raw bytes instead of walking chunks [LOW]

`Forms_file_storage::png_has_transparency()` looks for the strings `tRNS` and `IDAT`
anywhere in the file:
- a `tEXt`/`iTXt`/`zTXt` or `iCCP` chunk placed before `IDAT` can contain the bytes
  `tRNS` (false positive: no warning for an opaque stamp);
- it can also contain `IDAT` (false negative: a spurious warning).

Rare in practice, but a correct parser costs barely more: walk
`length(4) | type(4) | data | crc(4)` from offset 8 until `IDAT`. The colour-type check
on IHDR (`$content[25]`) is correct, since IHDR is mandatory and always first.

### 4. A stamp widget without a width fills its container [LOW]

`inject_stamp()` gives the image `width:100%`. If an author forgets `width:` on the
`<div data-gvv-type="stamp">`, which is easy when not positioning absolutely, the stamp
stretches to the full column or page width in the PDF. The placeholder on the public
form gives no hint, because the SVG has its own size.

**Mitigation**: add `max-width:6cm` (or similar) to the injected `<img>`, and state the
`width` requirement in the user doc table. It is currently implied by the example only.

### 5. Inline `confirm('<?= lang ?>')` breaks on an apostrophe [LOW, pattern pre-existing]

`bs_config.php` interpolates `forms_stamp_confirm_delete` unescaped into a single-quoted
JS string, as the existing `forms_config_confirm_delete` does. Today's translations
contain no `'`. If one later does (a very natural French phrasing, such as
« Supprimer le tampon de l'association ? »), the inline handler fails to compile and the
form **submits without confirmation**: the deletion happens silently.

**Mitigation**: `onsubmit="return confirm(<?= json_encode($this->lang->line(...)) ?>)"`,
or `htmlspecialchars(json_encode(...))`. Fix both buttons of this view.

### 6. Duplicated stamp resolution and data-URI construction [LOW — maintainability]

- `submission_view()` and `submission_pdf()` contain the same line
  `$stamp = $this->forms_file_storage->stamp_data_uri(!empty($form['club']) ? (int) $form['club'] : null);`.
  A private `_stamp_for_form($form)` would keep the scope rule in one place.
- `config()` builds section previews by hand
  (`'data:image/png;base64,' . base64_encode(file_get_contents(stamp_path(...)))`),
  duplicating `stamp_data_uri()` without its fallback. For the global row it calls
  `has_stamp(null)` before `stamp_data_uri(null)`, which already returns `null` when
  absent.
- **Mitigation**: one storage method `stamp_data_uri($section_id, $fallback = true)`
  used everywhere.

### 7. The page HTML is parsed twice per page [LOW — efficiency]

`inject_stamp()` re-parses with DOMDocument the output that
`_fill_html_values()`/`_fill_html_values_readonly()` just serialised from their own DOM.
For a 2-3 page form this costs milliseconds next to the wkhtmltopdf run. The extra
round-trip could still be avoided by giving those two methods an optional
`$stamp_data_uri` argument and handling the widget inside their existing XPath pass.
Doing so would also remove one place where HTML is normalised twice.

### 8. Workflow-form bypass roles would receive stamped PDFs [INFO]

`_can_access_workflow_form()` lets `instructeur`/`pilote_vd` call `submission_pdf` for
workflow forms (`briefing-passager-ulm`). None carries a stamp widget today. If one ever
does, non-admins will get the real stamp, which contradicts PRD EF19 #4 ("uniquement
dans les rendus réservés aux administrateurs").

**Mitigation**: pass `null` as the stamp when the request comes through the bypass, or
document the exception in the PRD.

### 9. Misplaced docblock [STYLE]

`Forms_file_storage::stamp_dir()` carries `@param int|null $section_id`, but takes no
parameter. The tag belongs on `stamp_path()`/`write_stamp()`.

### 10. Test robustness [LOW — tests]

- `FormsStampTest` asserts on French UI strings ("Tampon enregistré.",
  "Le tampon doit être une image PNG.") and so depends on `testadmin`'s language. This
  is consistent with other HTTP tests, but brittle.
- `FormsStampTest` and `forms-stamp-smoke.spec.js` temporarily replace the **real**
  global stamp on gvv.net. They snapshot and restore it, but another agent or user
  generating a PDF during that window gets the test image.
- The Playwright spec hard-codes the DB password, as the other forms specs do. A shared
  helper that reads `application/config/database.php` would remove all copies.

## Mitigation tracking

| # | Problem | Criticality | Status |
|---|---------|-------------|--------|
| 1 | Stamp upload/delete forgeable cross-site (no CSRF, no SameSite) | MEDIUM | ☑ Fixed in this PR — `_is_same_origin_post()` (Origin, else Referer, host+port vs `base_url()`; neither header ⇒ refused, explicit error message, logged). 3 PHPUnit tests (foreign origin upload/delete, no origin). Systemic fix (SameSite/CSRF) remains a separate issue |
| 2 | Stamp replacement irreversible and unconfirmed | LOW-MEDIUM | ☐ To do — confirmation or one-level history (PRD EF19 to update) |
| 3 | PNG transparency detection scans raw bytes | LOW | ☐ To do |
| 4 | Stamp without explicit width fills its container | LOW | ☐ To do |
| 5 | Unescaped lang string in inline `confirm()` | LOW | ☑ Fixed — both buttons of `bs_config.php` use `html_escape(json_encode(..., JSON_UNESCAPED_UNICODE))`; Playwright checks the dialog text arrives intact |
| 6 | Duplicated stamp resolution / data-URI code | LOW | ☐ To do |
| 7 | Double DOM parse per page | LOW | ☐ To do (optional) |
| 8 | Workflow bypass roles would get stamped PDFs | INFO | ☐ Decide: code guard or PRD exception |
| 9 | Misplaced `@param` docblock | STYLE | ☐ To do |
| 10 | Test brittleness (French strings, shared global stamp, hard-coded password) | LOW | ☐ To do (partly a separate issue) |
