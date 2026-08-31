const fs = require('fs');
const path = require('path');
const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');

/**
 * Smoke test: every GVV controller's page()/create() action, plus a
 * safety-filtered set of other read-only pages, load without error.
 *
 * Motivation: a PHP 8 signature-incompatibility fatal in tarifs.php went
 * undetected because no test ever loaded that controller (see
 * doc/reviews/pr84_produits_tarifs_refactoring.md and the fix-phpunit
 * session that found it) — PHPUnit never instantiates controllers, and the
 * ~90 existing Playwright specs are all feature-scoped, none crawling every
 * page generically. This test closes that gap. It has since also caught
 * bugs in event.php (pagination TypeError) and pompes_model.php (another
 * signature incompatibility) — both fixed once found.
 *
 * Target discovery is dynamic (reads application/controllers/*.php at run
 * time), so it stays current as controllers are added/removed:
 *
 * - page(): every controller declaring a `page()` method.
 * - create(): every controller with an actual (non-commented) `create()`
 *   method — always safe to load with no arguments (displays a form; the
 *   only two real ones have optional args and no required id).
 * - "other" read-only pages: any further public method that (a) takes zero
 *   REQUIRED parameters, (b) renders a view via load_last_view(), (c) whose
 *   controller/method name doesn't match a denylist of mutating-sounding
 *   keywords (delete, restore, import, cloture, sync, send, upload, ...),
 *   and (d) whose body contains no detectable write/mutation call
 *   (->insert(/->update(/->delete(/mail(/unlink(/raw SQL writes/etc). This
 *   is a heuristic, not a framework guarantee like page()/create() — it
 *   deliberately errs toward excluding anything ambiguous, since this runs
 *   against the shared gvv2 test database. See the git history of this file
 *   for the classification review (99 pages included, 57 excluded as
 *   destructive/sensitive: backups, restores, imports, account changes,
 *   accounting period close/reopen, session impersonation, email sending).
 *
 * Two scan modes, selected by the GVV_PAGE_SCAN_MODE env var:
 *
 * - "production" (default): the server is expected to be running with
 *   ENVIRONMENT=production. Any fatal error, uncaught exception, or 5xx
 *   response on any page FAILS the test. Meant to run as part of the normal
 *   suite.
 *
 * - "development": the server is expected to be running with
 *   ENVIRONMENT=development (flip index.php's ENVIRONMENT constant first —
 *   this test does not do it for you). PHP surfaces warnings/notices/
 *   deprecations inline that production mode silently swallows. These are
 *   NOT failures yet (first pass — establishing a baseline), so this mode
 *   does not fail the test; it collects every page's findings into
 *   build/playwright-captures/pages_smoke_dev_errors.md (gitignored) for
 *   manual triage.
 *
 * Usage:
 *   npx playwright test all-pages-smoke.spec.js
 *   GVV_PAGE_SCAN_MODE=development npx playwright test all-pages-smoke.spec.js
 */

const MODE = process.env.GVV_PAGE_SCAN_MODE === 'development' ? 'development' : 'production';

const CONTROLLERS_DIR = path.join(__dirname, '../../application/controllers');
// Written only in development mode. Kept under build/ (gitignored) so a test
// run never dirties a version-controlled file.
const REPORT_PATH = path.join(__dirname, '../../build/playwright-captures/pages_smoke_dev_errors.md');

// Markers that indicate a genuine failure — checked in both modes. Deliberately
// does NOT include CI's "An Error Was Encountered" / "A Database Error Occurred"
// show_error() views: several "other pages" targets legitimately need a
// parameter this crawler doesn't supply (a specific $codec, $mlogin...), and
// the app correctly shows a controlled message instead of crashing — that's
// by-design behavior, not a bug. Real crashes always also show one of the
// uncaught-exception/fatal markers below.
const FATAL_MARKERS = [
  'Fatal error',
  'must be compatible with',
  'Uncaught Error',
  'Uncaught Exception',
  'Uncaught TypeError',
  'Uncaught ArgumentCountError',
  'Unable to connect to your database',
];

// CI's own show_error() view: an intentional, controlled response (e.g. "no
// file selected", "missing parameter") — legitimately returns HTTP 500 too,
// but is not a crash. Used to tell it apart from a raw uncaught PHP fatal,
// which in ENVIRONMENT=production produces an EMPTY body (display_errors is
// off) — confirmed empirically (achats/create: HTTP 500, 0 bytes in
// production; the same bug shows "Fatal error" text only in development).
const CONTROLLED_ERROR_MARKERS = ['An Error Was Encountered', 'A Database Error Occurred'];

// Additional markers only meaningful with ENVIRONMENT=development, where CI
// prints non-fatal PHP errors inline instead of suppressing them. Collected,
// not blocking, in development mode.
const DEV_ONLY_MARKERS = [
  'A PHP Error was encountered',
  '<b>Warning</b>',
  '<b>Notice</b>',
  '<b>Deprecated</b>',
  ...CONTROLLED_ERROR_MARKERS,
];

// Controller/method names that sound mutating or sensitive enough to
// exclude from "other pages" discovery outright, regardless of what the
// body-mutation scan finds (belt-and-suspenders: session impersonation,
// account lifecycle, accounting-period close/reopen, backups, imports...).
const OTHER_ACTION_DENY_KEYWORDS = new RegExp(
  [
    'login_as', 'impersonat', '^deny$', '^ban$', 'restore', 'backup', 'anonymize',
    'generate_test', 'delete', 'remove', 'destroy', 'purge', 'drop', 'sync', 'fusion',
    'import', 'export', 'cloture', 'check$', 'reminder', 'send', 'upload', 'migrate',
    'provision', 'webhook', 'callback', 'cron', 'formvalidation', 'validate', 'submit',
    'clone', 'acknowledge', 'hide', 'reply', 'toggle', 'ajax', 'reset_password',
    'cancel_account', 'change_password', 'activate', 'register', 'create', 'cancel',
  ].join('|'),
  'i'
);

// Body content indicating the method writes data / has a side effect —
// disqualifies it from "other pages" discovery even if the name looked fine.
const MUTATION_PATTERNS = new RegExp(
  [
    '->insert\\(', '->update\\(', '->delete\\(', '->save\\(', '->create\\(',
    '\\bmail\\(', '->send\\(', '\\bunlink\\(', '\\brename\\(', '\\bexec\\(',
    '\\bshell_exec\\(', '\\bsystem\\(', '\\bpassthru\\(', '\\bfwrite\\(',
    'file_put_contents\\(', 'move_uploaded_file\\(', '\\btruncate\\(', 'set_userdata\\(',
    "->query\\([^)]*(INSERT|UPDATE|DELETE|DROP|TRUNCATE|ALTER)",
  ].join('|'),
  'i'
);

function readControllerFiles() {
  return fs.readdirSync(CONTROLLERS_DIR).filter((f) => f.endsWith('.php'));
}

function controllerRouteName(source, file) {
  const match = source.match(/protected\s+\$controller\s*=\s*'([^']+)'/);
  return match ? match[1] : path.basename(file, '.php');
}

function findMatchingBrace(source, openBraceIdx) {
  let depth = 0;
  for (let i = openBraceIdx; i < source.length; i++) {
    if (source[i] === '{') depth++;
    else if (source[i] === '}') {
      depth--;
      if (depth === 0) return i;
    }
  }
  return source.length - 1;
}

/** Every controller declaring a page() method. */
function discoverPageControllers() {
  const names = [];
  for (const file of readControllerFiles()) {
    const source = fs.readFileSync(path.join(CONTROLLERS_DIR, file), 'utf8');
    if (/function\s+page\s*\(/.test(source)) {
      names.push(controllerRouteName(source, file));
    }
  }
  return names.sort();
}

/** Every controller with a real (non-commented) create() method. */
function discoverCreateControllers() {
  const names = [];
  for (const file of readControllerFiles()) {
    const source = fs.readFileSync(path.join(CONTROLLERS_DIR, file), 'utf8');
    const hasRealCreate = source
      .split('\n')
      .some((line) => /function\s+create\s*\(/.test(line) && !line.trim().startsWith('//'));
    if (hasRealCreate) {
      names.push(controllerRouteName(source, file));
    }
  }
  return names.sort();
}

/** Safety-filtered "other pages": see module docblock for the exact rules. */
function discoverOtherPages() {
  const funcRe = /(public\s+|protected\s+|private\s+)?function\s+(\w+)\s*\(([^)]*)\)\s*\{/g;
  const results = [];

  for (const file of readControllerFiles()) {
    const controllerFileName = path.basename(file, '.php');
    const source = fs.readFileSync(path.join(CONTROLLERS_DIR, file), 'utf8');
    const controllerRoute = controllerRouteName(source, file);

    funcRe.lastIndex = 0;
    let m;
    while ((m = funcRe.exec(source)) !== null) {
      const [full, visibility, name, params] = m;
      if (visibility && /protected|private/.test(visibility)) continue;
      if (name.startsWith('__') || name === 'page' || name === 'create') continue;

      const trimmedParams = params.trim();
      if (trimmedParams) {
        const hasRequired = trimmedParams.split(',').some((p) => p.trim() && !p.includes('='));
        if (hasRequired) continue;
      }

      const openBraceIdx = m.index + full.length - 1;
      const closeBraceIdx = findMatchingBrace(source, openBraceIdx);
      const body = source.slice(openBraceIdx, closeBraceIdx + 1);

      if (!body.includes('load_last_view')) continue;
      if (OTHER_ACTION_DENY_KEYWORDS.test(controllerFileName)) continue;
      if (OTHER_ACTION_DENY_KEYWORDS.test(name)) continue;
      if (MUTATION_PATTERNS.test(body)) continue;

      results.push({ controllerRoute, action: name });
    }
  }

  return results.sort((a, b) => (a.controllerRoute + a.action).localeCompare(b.controllerRoute + b.action));
}

function discoverAllTargets() {
  const targets = [];
  for (const name of discoverPageControllers()) {
    targets.push({ label: `${name}/page`, url: `/index.php/${name}/page` });
  }
  for (const name of discoverCreateControllers()) {
    targets.push({ label: `${name}/create`, url: `/index.php/${name}/create` });
  }
  for (const { controllerRoute, action } of discoverOtherPages()) {
    targets.push({ label: `${controllerRoute}/${action}`, url: `/index.php/${controllerRoute}/${action}` });
  }
  return targets;
}

async function scanTarget(page, target) {
  const found = [];
  let status = null;

  try {
    const response = await page.goto(target.url, { waitUntil: 'networkidle', timeout: 20000 });
    status = response ? response.status() : null;
    const body = await page.content();

    const markersToCheck = MODE === 'development' ? [...FATAL_MARKERS, ...DEV_ONLY_MARKERS] : FATAL_MARKERS;
    for (const marker of markersToCheck) {
      if (body.includes(marker)) {
        found.push(marker);
      }
    }

    // In production, a raw PHP fatal is invisible in the body (display_errors
    // is off) — it returns a >=500 status with an empty/near-empty body and
    // none of the markers above. CI's own controlled show_error() view also
    // returns >=500 but always renders its message (caught by
    // CONTROLLED_ERROR_MARKERS above in dev mode, or just not flagged here).
    // So: a >=500 status with no marker found AND no controlled-error text is
    // the only remaining signal for a suppressed production fatal.
    const hasControlledError = CONTROLLED_ERROR_MARKERS.some((m) => body.includes(m));
    if (status && status >= 500 && found.length === 0 && !hasControlledError) {
      found.push(`HTTP ${status} (empty/opaque error body — likely a PHP fatal suppressed by ENVIRONMENT=production)`);
    }
  } catch (e) {
    found.push(`navigation error: ${e.message}`);
  }

  return { label: target.label, url: target.url, status, markers: found };
}

function writeDevReport(results) {
  const withErrors = results.filter((r) => r.markers.length > 0);
  const lines = [];

  lines.push('# GVV pages smoke scan — development mode findings');
  lines.push('');
  lines.push(`Generated: ${new Date().toISOString()}`);
  lines.push('');
  lines.push(
    `Scanned ${results.length} page(s) with ENVIRONMENT=development. ` +
    `${withErrors.length} page(s) reported something. This is a first-pass baseline — ` +
    'these findings are informational, not yet treated as blocking failures.'
  );
  lines.push('');

  if (withErrors.length === 0) {
    lines.push('No warnings, notices, deprecations, or errors found on any scanned page.');
  } else {
    lines.push('| Page | URL | HTTP Status | Findings |');
    lines.push('|---|---|---|---|');
    for (const r of withErrors) {
      lines.push(`| ${r.label} | ${r.url} | ${r.status ?? 'n/a'} | ${r.markers.join(', ')} |`);
    }
  }
  lines.push('');

  fs.mkdirSync(path.dirname(REPORT_PATH), { recursive: true });
  fs.writeFileSync(REPORT_PATH, lines.join('\n'));
  console.log(`Development-mode scan report written to ${REPORT_PATH}`);
}

test.describe('All GVV pages smoke test', () => {
  test(`every page/create/other action loads without error (mode: ${MODE})`, async ({ page }) => {
    test.setTimeout(15 * 60 * 1000);

    const targets = discoverAllTargets();
    expect(targets.length).toBeGreaterThan(0);
    console.log(`Discovered ${targets.length} page(s) to scan.`);

    const loginPage = new LoginPage(page);
    await loginPage.open();
    await loginPage.login('testadmin', 'password', '1');
    await loginPage.verifyLoggedIn();

    const results = [];
    for (const target of targets) {
      const result = await scanTarget(page, target);
      results.push(result);
      console.log(`${result.markers.length ? 'FAIL' : 'ok  '} ${result.url} (HTTP ${result.status ?? '?'})`);
    }

    await loginPage.logout();

    if (MODE === 'development') {
      writeDevReport(results);
      return;
    }

    const failures = results.filter((r) => r.markers.length > 0);
    const details = failures
      .map((r) => `  - ${r.url}: ${r.markers.join(', ')}`)
      .join('\n');

    expect(failures, `${failures.length} page(s) failed:\n${details}`).toEqual([]);
  });
});
