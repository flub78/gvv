# Code Review — PR #99 "Alarmes : Lot 1 (alarmes sur les documents archivés)"

- **Branch**: `feature/alarmes-lot1` → `main`
- **PR**: https://github.com/flub78/gvv/pull/99 (open, head `b38836bb`)
- **Scope**: 56 files, about 5,200 lines added.
  - Migration 177 and four models: `alarm_definitions`, `alarm_subscriptions`, `alarm_member_settings`, `notification_history`.
  - Three libraries: `Document_alarm_provider`, `Alarm_aggregator`, `Notification_manager`.
  - Controllers:
    - three new ones: `mes_alarmes`, `alarmes_admin`, `alarm_scheduler`;
    - changes to `reservation_scheduler`, `document_types` and `welcome`.
  - Other changes:
    - the `toggle` metadata subtype (`MetaData.php`, `gvv.js`);
    - the student role in `formation_inscription_model`;
    - `required_role_id` in `archived_documents_model`.
  - Views, language files (fr/en/nl), 5 MySQL PHPUnit classes, 2 Playwright specs, and the PRD, design, plan and user documentation.

## Summary

The architecture follows the design note:
- **Provider**: it turns archived documents into `AlarmItem`s.
- **Aggregator**: it collects those items from the providers.
- **Subscription model**: it computes the allowed cells (alarm × section, or the Club column) and the effective cells, from roles at the time of use.
- **`Notification_manager`**: it computes the occurrences of a member and sends them on the member's channels. `notification_history` makes it idempotent.

**Security.**
- The SQL built by hand only contains integer casts or fixed values; everything else goes through the query builder.
- The views escape their output.
- Access checks are present:
  - `club-admin` only for the administration page;
  - `?membre=` ignored for non-administrators;
  - the supervision rights of a cell are checked again at every computation.

**Tests.** The tests create and remove their own fixtures. After the full runs, the database was checked and had no leftover rows.

No blocking defect. Two functional bugs (findings 1 and 2) should be fixed before merge. The rest is efficiency, consistency and test coverage.

## Findings (most → least critical)

### 1. Re-enabling alarms on a document type does not re-enable the students' alarm [HIGH, correctness]

`Alarm_definitions_model::sync_document_types()` disables **all** the alarms of a non-eligible type, including `document_<id>_eleve`. When the type becomes eligible again, it only reactivates `document_<id>`.

**Failure scenario**: an administrator unticks then reticks "Alarmes" (or "Actif", or "Expiration") on the medical type in the document types list. The "Certificat médical — Élève" alarm stays inactive for good: instructors no longer receive the expiry of their students' medicals, and nothing on screen says so. Only the catalogue in the administration page can reactivate it, by hand.

**Fix**: when the type is eligible, reactivate every alarm of the type that was deactivated by the sync. Unconditionally reactivating every alarm of the type would also undo an administrator's choice made in the catalogue. Add a PHPUnit test for the off/on sequence on a type that has a targeted alarm.

### 2. An administrator viewing another member's alarms gets links to their own documents [MEDIUM, correctness]

`Notification_manager::occurrences_for()` replaces `link` with `link_own` when the occurrence belongs to the member passed as `$login`. On `mes_alarmes?membre=X`, `$login` is X, so X's own alarms point to `archived_documents/my_documents`.

**Failure scenario**: an administrator selects a member who has a missing medical certificate and clicks "Ouvrir". The page shows the **administrator's** own documents, not the member's.

**Fix**: keep the supervisor link in the item (e.g. `link_supervisor`), or apply `link_own` in the controller only when `is_self`.

### 3. Each display of the personal dashboard computes the alarms of the whole club [MEDIUM, efficiency]

`welcome::section('user')` calls `Notification_manager::active_count_for()`. Through `member_occurrences()`, that call does three things:
- it collects every document item of the catalogue;
- it computes the missing documents of **all** active members;
- it computes `effective_all()` for **all** members, even though only one member is needed.

The same happens on every display of `mes_alarmes`.

**Failure scenario**: on a club with several hundred members and documents, the personal dashboard is the most visited page, and its response time grows with the size of the club rather than with the member's own alarms.

**Fix**:
- Compute the effective cells of one member only: `effective_for()` should filter by member instead of going through `effective_all()`.
- Restrict collection to the definitions the member actually needs: their own items, plus the definitions of their cells.
- Alternatively, cache the count per request or per session.

### 4. Individual mandatory subscriptions can't be created from the UI [MEDIUM, functional gap]

PRD exigence 5: "Un administrateur peut rendre un abonnement, individuel ou par catégorie, non désabonnable". The model supports it (`subscribe(..., $mandatory)`), but no page exposes it. Only category subscriptions can be made mandatory, on the administration page. The `$mandatory` parameter of `subscribe()` is used only by the tests.

**Fix**: either add a "mandatory" option for an administrator configuring another member (e.g. a lock per cell), or narrow the PRD to category subscriptions.

### 5. `Notification_manager::run()` duplicates `member_occurrences()` [LOW, duplication]

`run()` and `member_occurrences()` both build `$definitions` and `$items_by_def` with the same loop. `mes_alarmes::index()` loads the definitions a third time to get the labels.

**Fix**: extract a `collect_items()` method that returns the definitions and the items, and use it in all three places.

### 6. Members with no channel and no SMS are still processed by the cron [LOW, efficiency]

`members_to_notify()` keeps every member who has a settings row, even when the email, message-of-the-day and SMS options are all off. With no subscription, saving the page creates such a row. Their occurrences are computed every hour and then thrown away.

**Fix**: keep only members with `channel_email || channel_motd || sms`.

### 7. A new medical document type never gets a students' alarm [LOW, consistency]

The `document_<id>_eleve` alarm is only created by the migration, for the `medical` type that exists at that time. `sync_document_types()` creates alarms for new types but not this targeted one. The same goes for a document type whose section changes: `alarm_definitions.section_id` is not updated.

**Fix**: create the targeted alarm in the sync when a pilot-scope type is required for a role (`required_role_id`), and sync `section_id`.

### 8. Missing documents: one SQL query per pilot and per role-restricted type [LOW, efficiency]

`Archived_documents_model::get_missing_documents()` calls `pilot_has_role()`, which is one query, for every required type restricted to a role. This runs on the existing document pages, which are displayed per pilot. The cost stays small (one type is restricted today), but it adds up on lists.

**Fix**: load the pilot's roles once at the top of the method.

### 9. Hard-coded French message in `gvv.js` [LOW, i18n]

`'Erreur de communication avec le serveur.'` is not translated. Also, when the session has expired, the server answers with the login page in HTML, `response.json()` fails, and the user sees this generic message instead of an invitation to log in again.

**Fix**: pass the message through a `data-` attribute generated by the view, or through a global JS variable set from the language file.

### 10. Dead parameter `Alarm_aggregator::collect($definitions, $section_id)` [LOW, dead code]

No caller passes `$section_id`. The filtering by section now happens through the subscription cells.

**Fix**: remove the parameter and its filter.

### 11. Test coverage gaps [LOW, tests]

Not covered:
- `document_types/ajax_toggle`: refusal without rights, and an invalid field;
- `mes_alarmes/save`: SMS refused without a mobile number;
- the alarms branch of `reservation_scheduler` (`_execute_alarms`, the `alarms:` output line). It could be tested without sending reservation reminders by calling the method on a controller whose reminder part is stubbed out.

## Positive points

- **Idempotent notifications.** `notification_history` makes repeated runs safe, which is what allowed the move to the hourly cron without any risk of duplicate sends. A PHPUnit test checks it.
- **Rights at the time of use.** The cells are recomputed from the roles on every use, so a role withdrawal takes effect without touching the subscriptions.
- **Generic `toggle` subtype.** It is opt-in through metadata (`ToggleUrl`), disabled outside `rw` mode, and the JavaScript puts the box back in its previous state when the request fails.
- **Self-cleaning tests.** The PHPUnit and Playwright tests create their own users, roles, types and documents, and clean up even on failure.
- **Safe migration test.** The migration test runs the down/up round trip only when no real alarm data exists.

## Mitigation todo list

| # | Criticality | Item | Status |
|---|---|---|---|
| 1 | HIGH | Re-enable the targeted alarms when the type becomes eligible again, plus a test | Fixed |
| 2 | MEDIUM | Supervisor links when an administrator views another member | Fixed |
| 3 | MEDIUM | Per-member computation for the dashboard and `mes_alarmes` | To do |
| 4 | MEDIUM | Individual mandatory subscription: UI or PRD | To decide |
| 5 | LOW | Factor out the item collection (`run`, `member_occurrences`, `mes_alarmes`) | Fixed |
| 6 | LOW | Exclude members with no channel from the cron | Fixed |
| 7 | LOW | Targeted alarm and `section_id` in `sync_document_types()` | To do |
| 8 | LOW | Roles loaded once in `get_missing_documents()` | Fixed |
| 9 | LOW | Translated error message in `gvv.js` | Fixed |
| 10 | LOW | Remove `$section_id` from `Alarm_aggregator::collect()` | Fixed |
| 11 | LOW | Missing tests (ajax_toggle, SMS without mobile, cron branch) | Fixed |

## Mitigations applied

- **1** — `sync_document_types()` marks the alarms it deactivates (`updated_by = 'alarm_sync'`) and, when the type becomes eligible again, reactivates exactly those, the students' alarm included. `configure()` always writes a different `updated_by`. Side effect fixed at the same time: a deactivation made in the catalogue was also reverted by the sync, which `alarmes_admin` runs on every display; it is now kept. Tests: on/off of `alarm_enabled`, `active` and `has_expiration`, and a catalogue deactivation that survives the sync.
- **2** — `occurrences_for()` no longer rewrites `link` and only flags `own`. `Notification_manager::item_link($item, $reader_is_member)` picks the link: `link_own` for the member, `link` for a supervisor. `mes_alarmes` passes `is_self`, and the emails use `item_link()`.
- **5** — `Notification_manager::collect_items()` is shared by `run()`, `member_occurrences()` and `mes_alarmes`.
- **6** — `members_to_notify()` keeps only members with email, message of the day or SMS.
- **8** — `get_missing_documents()` loads the pilot's roles once (`pilot_role_ids()`, which replaces `pilot_has_role()`), and only if a required type is restricted to a role.
- **9** — The toggle messages come from `gvv_lang` (`gvv_toggle_error`, `gvv_toggle_session`) through `data-error` and `data-session`. A non-JSON or redirected answer, such as an expired session, shows the session message.
- **10** — The `$section_id` parameter of `Alarm_aggregator::collect()` is removed.
- **11** — New tests:
  - Playwright: `ajax_toggle` with an invalid field and with a plain member, whose request is refused and the database unchanged; SMS refused without a mobile number.
  - PHPUnit: `run_scheduled()` without document management, the cron report line (`format_stats()`), members without channel, the link depending on the reader, and `get_missing_documents()` with a role-restricted type.
  - The feature flag and table checks of the cron branch moved from `reservation_scheduler` to `run_scheduled()` so that they can be tested.

Points 3, 4 and 7 remain open.
