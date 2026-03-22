# Code Review: Briefing Passager Feature

**Date**: 2026-03-21
**Branch**: `feature/breifing_passager`
**Reviewer**: Claude Code

## Scope

Files reviewed:

| File | Role |
|------|------|
| `application/controllers/briefing_passager.php` | UC1/UC3 admin controller |
| `application/controllers/briefing_sign.php` | UC2 public sign controller |
| `application/models/archived_documents_model.php` | Document persistence (briefing-specific methods) |
| `application/migrations/089_vols_decouverte_aerodrome_fk.php` | DB migration: aerodrome FK |
| `application/views/briefing_passager/bs_uploadView.php` | Upload + VLD edit form |
| `application/views/briefing_passager/bs_indexView.php` | VLD search/select page |
| `application/views/briefing_passager/bs_adminListView.php` | Admin list |
| `application/views/briefing_passager/bs_signView.php` | Public signature form |
| `application/language/french/briefing_passager_lang.php` | Language strings |

---

## Problems Found

### P1 — CRITICAL: No CSRF protection on `update_vld` (state-mutating GET-redirected POST)

**File**: `briefing_passager.php:99–133`
**Issue**: The `update_vld()` method accepts arbitrary POST fields and directly updates `vols_decouverte` rows. CodeIgniter 2's CSRF token is only checked if enabled in `config.php`. Even if enabled globally, there is no per-form token embedded in `bs_uploadView.php`.
**Risk**: Any authenticated user can be tricked into submitting a crafted form that overwrites VLD fields (aerodrome, airplane, pilot, beneficiary) of any flight they have no relation to.
**Mitigation**: Add `$this->form_validation->set_rules(...)` with CSRF token check, or verify that global CSRF is enabled and that the form includes `form_open()` (which auto-injects the token).

---

### P2 — HIGH: No authorization check in `update_vld`, `upload`, `upload_submit`, `generate_link`

**File**: `briefing_passager.php:59,99,139,421`
**Issue**: Any logged-in user (role: any) can upload a briefing or modify VLD fields for any flight in any section. The controller sets `$modification_level = 'gestion_vd'` but this is never enforced by the parent `Gvv_Controller::_check_role()` path for these methods — only `admin_list` and `export_pdf` check `_is_admin()`.
**Risk**: A member with read-only access can overwrite pilot assignments or replace existing briefing documents for flights belonging to other sections.
**Mitigation**: Add a section-level ownership check (`$vld['club'] === $this->session->userdata('section_id')`) or a role check (`gestion_vd`) before modifying data.

---

### P3 — HIGH: Signature image data is written to the filesystem without validation

**File**: `briefing_sign.php:271–278`
**Issue**: The base64-decoded PNG from the `signature_data` POST field is written directly to a temp file and passed to `$pdf->Image()`. There is no validation that the decoded data is actually a valid PNG image.
**Risk**: A malformed or malicious payload could trigger TCPDF internals in unexpected ways or consume excessive memory. While it doesn't allow code execution in PHP, it could crash the page or produce a corrupt PDF.
**Mitigation**: After decoding, verify the PNG header (`\x89PNG\r\n\x1a\n`) before writing to disk, or use `getimagesizefromstring()` to validate the image.

---

### P4 — HIGH: `search_vld` has a redundant escaped variable that is never used

**File**: `briefing_passager.php:251–262`
**Issue**: Two variables `$escaped_id` and `$escaped_val` are built by calling `$this->db->escape()` on the already-like-escaped `$escaped`, then both are used in the SQL string. However `$escaped_id` is computed but refers to the same value as `$escaped_val`. The intent was to use `$escaped` directly after `escape_like_str()`, but `db->escape()` adds surrounding quotes, making the LIKE expression `LIKE '"..."'` (with embedded quotes). This may work coincidentally in some MySQL modes but is fragile.
**Mitigation**: Use a single parameterized bind or build the SQL as:
```php
$like = '%' . $this->db->escape_like_str($q) . '%';
$this->db->like('beneficiaire', $q)->or_like('beneficiaire_tel', $q);
```

---

### P5 — MEDIUM: QR code temp file is never cleaned up

**File**: `briefing_sign.php:64–68`
**Issue**: The QR code PNG is stored at `sys_get_temp_dir() . '/bp_sign_qr_' . md5($token) . '.png'`. The file is created once and cached across requests (the `if (!file_exists($qr_file))` guard), but it is never deleted. On a shared server this accumulates one file per token in the system temp directory indefinitely.
**Mitigation**: Use `tempnam()` and delete after embedding, or write the QR code directly to a PHP output buffer and base64-encode in memory without touching the filesystem.

---

### P6 — MEDIUM: `_is_admin()` uses non-standard role check

**File**: `briefing_passager.php:457–459`
```php
return $this->dx_auth->is_role('ca', true, true) || $this->dx_auth->is_admin();
```
**Issue**: The `admin_list` is restricted to users with the `ca` role (président/admin) or system admins. The feature PRD states access should be granted to `gestion_vd`. A `gestion_vd` user who is not `ca` or global admin cannot access the admin list despite being responsible for VLD management.
**Mitigation**: Replace with `$this->dx_auth->is_role('gestion_vd', true, true) || $this->dx_auth->is_admin()`.

---

### P7 — MEDIUM: Pilot selector falls back silently to all active members

**File**: `briefing_passager.php:86–90`
```php
$pilote_selector = $this->membres_model->vd_pilots();
if (count($pilote_selector) <= 1) {
    $pilote_selector = $this->membres_model->selector_with_null(array('actif' => 1));
}
```
**Issue**: When no `pilote_vd` role is configured, all active members appear as pilot candidates. This is a large list in clubs with 200+ members and includes non-pilots.  The threshold `<= 1` (counts the NULL entry) is correct logic, but the fallback produces a list that is misleading — all active members, including administrative staff and trainees.
**Mitigation**: At minimum, document this behavior with a comment. A better fix is to filter on a meaningful role (e.g., `FI` or any flight-qualified role) instead of `actif=1`.

---

### P8 — MEDIUM: `upload_submit` reads `$_FILES` directly, bypassing CI input sanitization

**File**: `briefing_passager.php:177,212`
```php
$storage_file = time() . '_' . rand(1000, 9999) . '_' . $this->_sanitize_filename($_FILES['userfile']['name']);
...
'original_filename' => $_FILES['userfile']['name'],
```
**Issue**: `$_FILES['userfile']['name']` is the raw client-supplied filename. It is sanitized for the storage name but stored raw in `original_filename`. This is displayed in the admin list; while it goes through `htmlspecialchars` in the views, it's still raw in the database.
**Mitigation**: Apply `$this->_sanitize_filename()` to `original_filename` before inserting, or use `$this->upload->data()['orig_name']` which CodeIgniter sanitizes automatically.

---

### P9 — MEDIUM: Token expiry is silently treated as "invalid token" instead of a distinct error

**File**: `briefing_sign.php:200–203`
```php
if ($row['expires_at'] && strtotime($row['expires_at']) < time()) {
    return null;
}
```
**Issue**: An expired token returns `null` from `_validate_token()`, which triggers the generic "invalid or expired link" error. The passenger cannot distinguish between a bad link and an expired one. Expired tokens are common (links sent 7+ days ago) and deserve a clearer message.
**Mitigation**: Return a distinguishable value (e.g., `array('expired' => true)`) and show a dedicated expiry message.

---

### P10 — LOW: `update_vld` allows setting `date_vol` to any string, no date format validation

**File**: `briefing_passager.php:112,119`
```php
$date_vol = trim($this->input->post('date_vol', true));
if ($date_vol !== '') $update['date_vol'] = $date_vol;
```
**Issue**: The HTML `<input type="date">` constrains format client-side, but there is no server-side validation. A crafted POST can inject any string into the `date_vol` column.
**Mitigation**: Add `preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_vol)` before inserting.

---

### P11 — LOW: Dead variable `$escaped_id` in `search_vld`

**File**: `briefing_passager.php:252`
```php
$escaped_id = $this->db->escape($escaped);
```
This variable is assigned but `$escaped_val` is used for all three LIKE conditions. `$escaped_id` is used only in the `CAST(id AS CHAR) LIKE {$escaped_id}` clause. Since both variables resolve to the same value this works, but the naming is confusing.

---

### P12 — LOW: PDF export in `export_pdf` outputs `mode_upload` label even for digital briefings

**File**: `briefing_passager.php:405`
```php
$pdf->Cell($widths[5], 6, $b['type_code'] === 'briefing_passager' ? $this->lang->line('briefing_passager_mode_upload') : '', 1);
```
**Issue**: When `type_code !== 'briefing_passager'` (i.e., digital signature), the mode cell is left blank instead of showing `briefing_passager_mode_digital`. The admin list HTML view shows the correct badge; the PDF does not.
**Mitigation**: Change `: ''` to `: $this->lang->line('briefing_passager_mode_digital')`.

---

### P13 — LOW: `bs_signView.php` exposes the raw file path of the consignes PDF via `base_url()`

**File**: `bs_signView.php:49,51,57`
```php
<object data="<?= base_url($consignes['file_path']) ?>">
```
**Issue**: `file_path` is a relative path like `uploads/documents/sections/3/...`. If the uploads directory is web-accessible (which it must be for this to work), a user can enumerate other documents by guessing paths. This is an existing concern for the `archived_documents` subsystem, but is newly exposed on an **unauthenticated** public page.
**Mitigation**: Serve the consignes PDF through a controller action that validates the section and serves the file, rather than linking directly to the filesystem path.

---

### P14 — LOW: Migration 089 `down()` does not restore original column size or collation precisely

**File**: `089_vols_decouverte_aerodrome_fk.php:41–48`
The rollback changes `aerodrome` to `VARCHAR(100)` with default collation. If the original was a different size or collation, the rollback is not perfectly reversible. Not critical for a development migration, but worth noting for production.

---

### P15 — LOW: `load_last_view` used inconsistently — `briefing_sign.php` uses `$this->load->view` directly

**File**: `briefing_sign.php:79,128,179,214`
All other controllers use `load_last_view()`. `Briefing_sign` extends `CI_Controller` (not `Gvv_Controller`), so it cannot use `load_last_view()`. This is intentional for the public page but deserves a comment explaining why.

---

## Summary Table

| # | Severity | Category | File | Description |
|---|----------|----------|------|-------------|
| P1 | Critical | Security | briefing_passager.php | No CSRF protection on `update_vld` |
| P2 | High | Security/Auth | briefing_passager.php | No section/role check on write actions |
| P3 | High | Security | briefing_sign.php | Unvalidated image data written to disk |
| P4 | High | Bug | briefing_passager.php | Fragile SQL escaping in `search_vld` |
| P5 | Medium | Resource leak | briefing_sign.php | QR code temp files never cleaned up |
| P6 | Medium | Auth | briefing_passager.php | Admin check excludes `gestion_vd` role |
| P7 | Medium | UX/Logic | briefing_passager.php | Pilot fallback includes all active members |
| P8 | Medium | Security | briefing_passager.php | Raw `$_FILES['name']` stored in DB |
| P9 | Medium | UX | briefing_sign.php | Expired token indistinguishable from invalid |
| P10 | Low | Validation | briefing_passager.php | No server-side date format validation |
| P11 | Low | Dead code | briefing_passager.php | Confusing variable naming in SQL query |
| P12 | Low | Bug | briefing_passager.php | PDF export: digital mode shows blank instead of label |
| P13 | Low | Security | bs_signView.php | Consignes PDF path exposed on unauthenticated page |
| P14 | Low | Migration | 089_vols_decouverte_aerodrome_fk.php | `down()` imprecise column restore |
| P15 | Low | Style | briefing_sign.php | `load_last_view` not used (expected, but uncommented) |
