const fs = require('fs');
const path = require('path');
const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');

/**
 * Smoke test: every GVV controller's `page()` action loads without error.
 *
 * Motivation: a PHP 8 signature-incompatibility fatal in tarifs.php went
 * undetected because no test ever loaded that controller (see
 * doc/reviews/pr84_produits_tarifs_refactoring.md and the fix-phpunit
 * session that found it) — PHPUnit never instantiates controllers, and the
 * ~90 existing Playwright specs are all feature-scoped, none crawling every
 * page generically. This test closes that gap.
 *
 * Controller discovery is dynamic (reads application/controllers/*.php at
 * run time for any class declaring a `page()` method), so it stays current
 * as controllers are added/removed rather than relying on a hardcoded list.
 *
 * Two modes, selected by the GVV_PAGE_SCAN_MODE env var:
 *
 * - "production" (default): the server is expected to be running with
 *   ENVIRONMENT=production (application/config/../index.php). Any fatal
 *   error, uncaught exception, or 5xx response on any page FAILS the test.
 *   Meant to run as part of the normal suite.
 *
 * - "development": the server is expected to be running with
 *   ENVIRONMENT=development (flip index.php's ENVIRONMENT constant first —
 *   this test does not do it for you). PHP surfaces warnings/notices/
 *   deprecations inline that production mode silently swallows. These are
 *   NOT failures yet (first pass — establishing a baseline), so this mode
 *   does not fail the test; it collects every page's findings into
 *   doc/testing/pages_smoke_dev_errors.md for manual triage.
 *
 * Usage:
 *   npx playwright test all-pages-smoke.spec.js
 *   GVV_PAGE_SCAN_MODE=development npx playwright test all-pages-smoke.spec.js
 */

const MODE = process.env.GVV_PAGE_SCAN_MODE === 'development' ? 'development' : 'production';

const CONTROLLERS_DIR = path.join(__dirname, '../../application/controllers');
const REPORT_PATH = path.join(__dirname, '../../doc/testing/pages_smoke_dev_errors.md');

// Markers that indicate a genuine failure — checked in both modes.
const FATAL_MARKERS = [
  'Fatal error',
  'must be compatible with',
  'Uncaught Error',
  'Uncaught Exception',
  'Uncaught TypeError',
  'An Error Was Encountered',
  'A Database Error Occurred',
  'Unable to connect to your database',
];

// Additional markers only meaningful with ENVIRONMENT=development, where CI
// prints non-fatal PHP errors inline instead of suppressing them. Collected,
// not blocking, in development mode.
const DEV_ONLY_MARKERS = [
  'A PHP Error was encountered',
  '<b>Warning</b>',
  '<b>Notice</b>',
  '<b>Deprecated</b>',
];

/**
 * Scans application/controllers/*.php for classes declaring a page()
 * method, returning their route name (protected $controller = '...' when
 * declared, otherwise the lowercased filename — CI's default convention).
 */
function discoverPageControllers() {
  const files = fs.readdirSync(CONTROLLERS_DIR).filter((f) => f.endsWith('.php'));
  const names = [];

  for (const file of files) {
    const source = fs.readFileSync(path.join(CONTROLLERS_DIR, file), 'utf8');
    if (!/function\s+page\s*\(/.test(source)) {
      continue;
    }
    const match = source.match(/protected\s+\$controller\s*=\s*'([^']+)'/);
    const name = match ? match[1] : path.basename(file, '.php');
    names.push(name);
  }

  return names.sort();
}

async function scanPage(page, controllerName) {
  const url = `/index.php/${controllerName}/page`;
  const found = [];
  let status = null;

  try {
    const response = await page.goto(url, { waitUntil: 'networkidle', timeout: 20000 });
    status = response ? response.status() : null;
    const body = await page.content();

    const markersToCheck = MODE === 'development' ? [...FATAL_MARKERS, ...DEV_ONLY_MARKERS] : FATAL_MARKERS;
    for (const marker of markersToCheck) {
      if (body.includes(marker)) {
        found.push(marker);
      }
    }
    if (status && status >= 500) {
      found.push(`HTTP ${status}`);
    }
  } catch (e) {
    found.push(`navigation error: ${e.message}`);
  }

  return { controllerName, url, status, markers: found };
}

function writeDevReport(results) {
  const withErrors = results.filter((r) => r.markers.length > 0);
  const lines = [];

  lines.push('# GVV pages smoke scan — development mode findings');
  lines.push('');
  lines.push(`Generated: ${new Date().toISOString()}`);
  lines.push('');
  lines.push(
    `Scanned ${results.length} controller page(s) with ENVIRONMENT=development. ` +
    `${withErrors.length} page(s) reported something. This is a first-pass baseline — ` +
    'these findings are informational, not yet treated as blocking failures.'
  );
  lines.push('');

  if (withErrors.length === 0) {
    lines.push('No warnings, notices, deprecations, or errors found on any scanned page.');
  } else {
    lines.push('| Controller | URL | HTTP Status | Findings |');
    lines.push('|---|---|---|---|');
    for (const r of withErrors) {
      lines.push(`| ${r.controllerName} | ${r.url} | ${r.status ?? 'n/a'} | ${r.markers.join(', ')} |`);
    }
  }
  lines.push('');

  fs.mkdirSync(path.dirname(REPORT_PATH), { recursive: true });
  fs.writeFileSync(REPORT_PATH, lines.join('\n'));
  console.log(`Development-mode scan report written to ${REPORT_PATH}`);
}

test.describe('All GVV pages smoke test', () => {
  test(`every controller page() loads without error (mode: ${MODE})`, async ({ page }) => {
    test.setTimeout(10 * 60 * 1000);

    const controllers = discoverPageControllers();
    expect(controllers.length).toBeGreaterThan(0);

    const loginPage = new LoginPage(page);
    await loginPage.open();
    await loginPage.login('testadmin', 'password', '1');
    await loginPage.verifyLoggedIn();

    const results = [];
    for (const controllerName of controllers) {
      const result = await scanPage(page, controllerName);
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
