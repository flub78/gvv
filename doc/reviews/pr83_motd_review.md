# Code Review — PR #83 "Feature/motd"

- **Branch**: `feature/motd` → `main`
- **PR**: https://github.com/flub78/gvv/pull/83 (open, head `7b8e405f`)
- **Scope**: 56 files changed (~5000 diff lines) — new "Messages du jour" (MOTD)
  feature: 5 DB tables (3 migrations), 5 models, 1 controller, 4 views, 1 JS
  file, metadata/language wiring, 4 Playwright suites, user/design docs.

## Summary

The feature is well structured and the security posture is good: Markdown is
rendered in Parsedown safe mode (no raw-HTML XSS), every AJAX endpoint that
touches a specific message re-checks `user_can_access_message()`, the image
upload/serving endpoints validate real MIME type and ownership, and there is
a dedicated `motd-security-smoke.spec.js` suite covering XSS, cross-user
access, and malformed-request cases. Access control between the admin menu
entry, the sub-dashboard card, and the controller's `can_manage()` check is
consistent (`club-admin` role, checked in all three places).

The findings below are about UX feedback correctness, a PRD requirement that
the implementation quietly doesn't satisfy, and some minor inefficiency/
cleanliness items — nothing that looks exploitable.

## Findings (most → least critical)

### 1. Hide/acknowledge endpoints always report `success: true`, even if the write failed [MEDIUM]
`application/controllers/motd.php:352-411` — `hide_message()`, `hide_all()`,
`unhide_all()` and `acknowledge_message()` call into
`Motd_user_state_model` and then unconditionally respond
`json_encode(array('success' => TRUE))`, without checking the model call's
return value:
```php
$this->motd_user_state_model->hide_message($id, $username);
header('Content-Type: application/json');
echo json_encode(array('success' => TRUE));
```
`upsert_state()` (`application/models/motd_user_state_model.php:66-79`) does
a plain `get_state()` then `create()`/`update()` — if that write fails for
any reason (DB error, the race in finding #6 below, connection hiccup), the
client is still told the action succeeded. The JS then optimistically fades
out the message / marks it acknowledged (`assets/javascript/motd.js:171-207`),
so the user believes it worked while the DB still shows the message as
active — it reappears on the next reload or on another device. This
directly contradicts the project's own UX rule ("Never reject an action
silently. The result of every action must be obvious to the user.",
`CLAUDE.md` guideline #14). `reply()` (line 448) has the same shape: it
never checks whether `create_reply()` actually returned an id before
building the response from `get_reply($reply_id)`.
**Suggested fix**: have the model calls return a boolean/id and have the
controller answer `success: false` (with an error) when the write didn't
happen.

### 2. The "Niveau" field can never be left empty via the admin UI, contradicting the PRD [MEDIUM]
- PRD: `doc/prds/messages_du_jour_prd.md` EF1 — *"Niveau 'Urgent, Important,
  Info, Alerte' (**optionnel**)."*
- `application/controllers/motd.php:167-169` (`form2database()`) contains
  dead-in-practice handling for this: *"level is optional... no radio
  pre-selected means the browser submits nothing for it... store that as
  NULL"*.
- But `application/views/motd/bs_formView.php:52` sets
  `$level = isset($level) ? $level : 'info';` — on the **create** form
  (where `$level` is never passed in), this defaults to `'info'`.
- `application/libraries/Gvvmetadata.php:1037-1042` defines the `level`
  `Enumerate` with exactly 4 options (urgent/important/info/alerte) and no
  empty choice, rendered as a radio group
  (`enumerate_radio_fields()`, `application/helpers/form_elements_helper.php:119-135`)
  which pre-checks whichever key equals `$value` — i.e. "Info" is
  pre-checked.

Net effect: every message created through the admin GUI without touching
"Niveau" is silently saved with `level = 'info'`, never `NULL` — an admin
cannot create a level-less message as the PRD describes, and there is no
way to un-check a pre-checked HTML radio group from the browser. The
`empty($processed_data['level'])` branch in the controller is only
reachable from a non-browser client (raw POST omitting the field).
**Suggested fix**: either drop the `'info'` fallback in the view (leave
`$level` empty on create, add a "(aucun niveau)" radio option so the field
can be genuinely unset), or update the PRD/controller comment to match the
actual (defaulted) behavior if "info by default" is the desired behavior.

### 3. "Afficher tous les messages" badge count goes stale after a single hide, without reload [MEDIUM]
`assets/javascript/motd.js:171-207` (`.motd-hide-btn` handler) updates the
unread badge (`#motdSectionUnreadBadge`) and the active-count text
(`#motdSectionActiveCount`) via JS after a successful AJAX hide, but never
touches `#motdShowHiddenBtn`'s hidden-count badge — that badge is only
computed server-side (`motd_hidden_count`, `application/views/bs_dashboard.php:111-116`)
and stays stale until the next full page load. By contrast, `#motdHideAllBtn`
and `#motdShowHiddenBtn`'s own handlers both do a full `location.reload()`
on success. Confirmed empirically while capturing documentation screenshots:
hiding one message left the "Afficher tous les messages" button showing no
badge at all, then a reload revealed the correct count.
**Suggested fix**: after a successful single hide, either increment the
hidden-count badge client-side (mirroring `decrementUnreadBadge()`) or
reload like the two "all" actions already do.

### 4. N+1 query for replies on every dashboard load [LOW]
`application/controllers/welcome.php:296` — inside
`_prepare_dashboard_data()`, `foreach ($motd_messages as &$motd_message) {
$motd_message['replies'] = $this->motd_replies_model->replies_for_message($motd_message['id']); ... }`
issues one `SELECT` per active message, on every dashboard/sub-dashboard
render for every logged-in user. Message counts are expected to stay small
so this isn't urgent, but it doesn't scale, and `motd_replies_model` has no
"replies for a set of message ids" method to batch it.

### 5. `hide_all_messages()` does one upsert query per message in a loop [LOW]
`application/models/motd_user_state_model.php:40-47` calls
`hide_message()` (itself a SELECT + INSERT/UPDATE) once per active message
instead of a single bulk `UPDATE ... WHERE message_id IN (...)` /
`INSERT ... ON DUPLICATE KEY UPDATE`. Same low-urgency scaling note as #4.

### 6. Non-atomic get-then-write upsert can race on rapid double-clicks [LOW]
`application/models/motd_user_state_model.php:66-79` and
`application/models/motd_user_prefs_model.php:35-46` both do
`get_first()` then `create()`/`update()` without a transaction or
`INSERT ... ON DUPLICATE KEY UPDATE`. The unique key
(`uk_motd_user_message_state` / `uk_motd_user_prefs_user`, migration
`143_create_motd_tables.php`) means a genuine race would surface as a DB
error rather than a silent duplicate row — but combined with finding #1
(return value never checked), that error would currently be swallowed and
reported to the user as success.

### 7. Three migrations to converge on the final MOTD schema, all within the same unreleased branch [LOW / cleanliness]
`143_create_motd_tables.php` adds FKs on `created_by`/`updated_by`/
`author_login`/`user_login` to `membres.mlogin`; `144_motd_relax_actor_fk.php`
and `145_motd_relax_actor_user_login_fk.php` then immediately drop most of
them because non-member accounts (e.g. `testadmin`) broke every admin
action. Since none of this has shipped to a production DB yet, squashing
143+144+145 into one migration before merge would leave a cleaner history
(the current 3-step "add strict FK, then loosen it twice" is only visible
because it was iterated on directly against a shared dev DB).

### 8. Unrelated build artifact churned by this PR [LOW / cleanliness]
`build/logs/integration_testdox.txt` (a generated PHPUnit testdox report,
not covered by `.gitignore`) is modified in this PR, flipping several
unrelated "Smart Adjustor Correlation Integration" tests from `[x]` to
`[ ]`. This is noise from a local test run getting committed alongside the
feature, not a MOTD-related change — worth a `git checkout` on that file
(or adding `build/` to `.gitignore`) before merging.

## Non-findings (checked, no issue)
- Markdown rendering uses `Parsedown::setSafeMode(true)` for both message
  content and replies — raw `<script>`/`onerror`/`javascript:` payloads are
  neutralized (also covered by `motd-security-smoke.spec.js`).
- `media()` access control and `upload_image()` MIME/size validation look
  correct; uploaded filenames are server-generated (`encrypt_name`), so no
  path-traversal surface.
- `club-admin` gating is consistent across the admin menu link
  (`bs_menu.php:161-168`), the sub-dashboard card
  (`bs_sub_dashboard.php:935-962`), and `Motd::can_manage()`.
- Mailing-list membership resolution (`Motd_model::list_member_logins()`)
  correctly reads `email_lists_model`'s manual-member/role/sublist helpers;
  no login/id type mismatch.
- Migration version bump (`142` → `145`) matches the 3 new migration files.
