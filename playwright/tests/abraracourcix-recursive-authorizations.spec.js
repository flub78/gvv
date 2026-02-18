/**
 * Abraracourcix Recursive Authorization Test
 *
 * Logs in as abraracourcix (user in Planeur, Avion, ULM, Général +
 * CA + instructeur via mniveaux flags) and recursively crawls all
 * internal links reachable from menus and dashboards, verifying that
 * every page is accessible.
 *
 * When an access is forbidden it must be filtered out from the menu and dashboard.
 * It does not make sense to present an option to a user, and then to tell him
 * that this choice is forbidden.
 *
 * The test collects all <a href> on every visited page, filters to
 * internal same-domain URLs, deduplicates by route pattern (only one
 * instance per controller/method is visited), and continues until no
 * new URL patterns remain.
 *
 * Usage:
 *   npx playwright test tests/abraracourcix-recursive-authorizations.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const LoginPage = require('./helpers/LoginPage');

const ABRARACOURCIX = { username: 'abraracourcix', password: 'password', section: '1' };
const BASE_URL = 'http://gvv.net';

/**
 * URL patterns to skip during crawling (matched against pathname).
 */
const SKIP_PATTERNS = [
    /\/auth\/logout/,
    /\/auth\/login/,
    /\/auth\/change_password/,
    /\/clear_opcache/,
    /\/ajout\b/,
    /\/modifier\b/,
    /\/delete\b/,
    /\/supprimer\b/,
    /\/upload/,
    /\/save/,
    /\/export/,
    /\/pdf\b/,
    /\/csv\b/,
    /\/csv_/,                 // CSV download endpoints (csv_month, csv_machine, etc.)
    /\/print\b/,
    /\/imprimer/,
    /\/phpinfo/,
    /\/migration/,
    /\/backup/,
    /\/restore/,
    /\/install/,
    /\/testunit/,
    /\/api\//,
    /\/user_guide\//,         // CodeIgniter documentation
    /\/libraries\//,          // CI library docs
    /javascript:/,
    /mailto:/,
    /\.pdf$/i,
    /\.csv$/i,
    /\.xls/i,
    /\.css$/i,
    /\.js$/i,
    /\.html$/i,
    /\.php$/i,                // direct .php files (not routed)
];

/**
 * Extract the "route pattern" from a URL.
 * Replaces numeric IDs and known variable segments with placeholders
 * so that /vols_planeur/edit/123 and /vols_planeur/edit/456 map to
 * the same pattern: /vols_planeur/edit/{id}
 *
 * Returns null for non-app URLs.
 */
function getRoutePattern(url) {
    try {
        const parsed = new URL(url, BASE_URL);
        if (parsed.origin !== new URL(BASE_URL).origin) return null;

        const segments = parsed.pathname.split('/').filter(Boolean);
        if (segments.length === 0) return '/';

        const normalized = segments.map((seg, i) => {
            // Pure numeric segments are IDs
            if (/^\d+$/.test(seg)) return '{id}';
            // Aircraft/glider registrations (F-XXXX pattern)
            if (/^F-[A-Z]{3,4}$/i.test(seg)) return '{registration}';
            // Usernames in event/create/username or vols_planeur/vols_du_pilote/username
            if (i >= 2 && (segments[i - 1] === 'create'
                || segments[i - 1] === 'vols_du_pilote'
                || segments[i - 1] === 'vols_de_la_machine'
                || segments[i - 1] === 'journal_compte'
                || segments[i - 1] === 'mon_compte')) {
                return '{param}';
            }
            return seg;
        });

        return '/' + normalized.join('/');
    } catch {
        return null;
    }
}

/**
 * Check if a URL should be skipped.
 */
function shouldSkipUrl(url) {
    try {
        const parsed = new URL(url, BASE_URL);
        const full = parsed.pathname + parsed.search;
        return SKIP_PATTERNS.some(p => p.test(full));
    } catch {
        return true;
    }
}

/**
 * Normalize a URL for deduplication (strip fragment, trailing slash).
 * Returns null for external URLs.
 */
function normalizeUrl(url) {
    try {
        const parsed = new URL(url, BASE_URL);
        if (parsed.origin !== new URL(BASE_URL).origin) return null;
        const path = parsed.pathname.replace(/\/+$/, '') || '/';
        return parsed.origin + path + parsed.search;
    } catch {
        return null;
    }
}

/**
 * Extract all internal <a href> links from the current page.
 */
async function extractInternalLinks(page) {
    const baseOrigin = new URL(BASE_URL).origin;
    const links = await page.evaluate((origin) => {
        const anchors = document.querySelectorAll('a[href]');
        const urls = [];
        for (const a of anchors) {
            const href = a.getAttribute('href');
            if (!href || href === '#' || href.startsWith('#')
                || href.startsWith('javascript:') || href.startsWith('mailto:')) {
                continue;
            }
            try {
                const resolved = new URL(href, document.location.href);
                if (resolved.origin === origin) {
                    urls.push(resolved.href);
                }
            } catch { /* ignore */ }
        }
        return urls;
    }, baseOrigin);

    const result = new Set();
    for (const url of links) {
        const n = normalizeUrl(url);
        if (n && !shouldSkipUrl(n)) result.add(n);
    }
    return [...result];
}

/**
 * Check if the current page shows an access denial.
 */
function isAccessDenied(url, content) {
    return url.includes('/auth/deny')
        || url.includes('/auth/login')
        || content.includes('Accès non autorisé')
        || content.includes('Accès refusé')
        || content.includes('Accès réservé aux administrateurs');
}

test.describe('Abraracourcix Recursive Authorization Crawl', () => {

    test('all visible links must be accessible (forbidden URLs must not appear in menus)', async ({ page }) => {
        test.setTimeout(300_000);

        // --- Login ---
        const loginPage = new LoginPage(page);
        await loginPage.open();
        await loginPage.login(ABRARACOURCIX.username, ABRARACOURCIX.password, ABRARACOURCIX.section);
        await loginPage.goto('welcome');
        await page.waitForLoadState('domcontentloaded');

        // Verify login succeeded
        const currentUrl = page.url();
        expect(currentUrl, 'Should be logged in and on welcome page')
            .not.toContain('/auth/login');

        // --- State ---
        const visitedPatterns = new Set();   // route patterns already visited
        const visitedUrls = new Set();       // exact URLs visited
        const queue = [];                    // { url, pattern } to visit
        const accessGranted = [];            // patterns granted
        const accessDenied = [];             // patterns denied
        const errors = [];                   // patterns with errors

        /**
         * Enqueue a URL if its route pattern has not been seen yet.
         */
        function enqueue(url) {
            const pattern = getRoutePattern(url);
            if (!pattern) return;
            if (visitedPatterns.has(pattern)) return;
            // Check it's not already in the queue
            if (queue.some(q => q.pattern === pattern)) return;
            queue.push({ url, pattern });
        }

        // Seed from the start page
        const startUrl = normalizeUrl(page.url());
        const startPattern = getRoutePattern(startUrl);
        visitedPatterns.add(startPattern);
        visitedUrls.add(startUrl);
        accessGranted.push({ url: startUrl, pattern: startPattern });

        const initialLinks = await extractInternalLinks(page);
        for (const link of initialLinks) enqueue(link);

        console.log(`Starting crawl from ${startUrl}`);
        console.log(`Initial queue: ${queue.length} unique route patterns`);

        // --- Crawl loop ---
        while (queue.length > 0) {
            const { url, pattern } = queue.shift();

            // Double-check pattern hasn't been visited (may have been added by another URL)
            if (visitedPatterns.has(pattern)) continue;
            visitedPatterns.add(pattern);
            visitedUrls.add(url);

            console.log(`  Testing: ${url}`);

            try {
                const response = await page.goto(url, {
                    waitUntil: 'domcontentloaded',
                    timeout: 15_000,
                });
                await page.waitForLoadState('domcontentloaded');

                const finalUrl = page.url();
                const content = await page.content();

                if (isAccessDenied(finalUrl, content)) {
                    accessDenied.push({ url, pattern });
                    console.log(`    => DENIED`);
                    continue;
                }

                // Access granted
                accessGranted.push({ url, pattern });
                console.log(`    => OK`);

                // Extract and enqueue new links
                const newLinks = await extractInternalLinks(page);
                let newCount = 0;
                for (const link of newLinks) {
                    const p = getRoutePattern(link);
                    if (p && !visitedPatterns.has(p) && !queue.some(q => q.pattern === p)) {
                        enqueue(link);
                        newCount++;
                    }
                }
                if (newCount > 0) {
                    console.log(`    +${newCount} new patterns (queue: ${queue.length})`);
                }

            } catch (err) {
                errors.push({ url, pattern, error: err.message });
                console.log(`  ERROR: ${pattern}  (${url}) - ${err.message}`);
            }
        }

        // --- Report ---
        console.log('\n========================================');
        console.log('  CRAWL RESULTS');
        console.log('========================================');
        console.log(`  Route patterns visited: ${visitedPatterns.size}`);
        console.log(`  Access granted: ${accessGranted.length}`);
        console.log(`  Access denied: ${accessDenied.length}`);
        console.log(`  Errors: ${errors.length}`);

        if (accessGranted.length > 0) {
            console.log('\n  Granted patterns:');
            for (const { pattern } of accessGranted) {
                console.log(`    + ${pattern}`);
            }
        }

        if (accessDenied.length > 0) {
            console.log('\n  Denied patterns:');
            for (const { url, pattern } of accessDenied) {
                console.log(`    - ${pattern}  (${url})`);
            }
        }

        if (errors.length > 0) {
            console.log('\n  Errors:');
            for (const { url, pattern, error } of errors) {
                console.log(`    ! ${pattern}  (${url}): ${error}`);
            }
        }
        console.log('========================================\n');

        // --- Assertions ---

        // 1. We should have visited a reasonable number of route patterns
        expect(visitedPatterns.size, 'Should visit many route patterns').toBeGreaterThan(10);

        // 2. We should have granted access to multiple pages
        expect(accessGranted.length, 'Should have accessible pages').toBeGreaterThan(5);

        // 3. ALL visible links must be accessible.
        //    Since we only discover URLs by following links in menus and pages,
        //    every URL we find is visible to the user. If access is denied,
        //    the link should not have been shown in the first place.
        expect(accessDenied.length,
            `${accessDenied.length} visible link(s) returned access denied. ` +
            `Forbidden URLs must be filtered from menus and dashboards:\n` +
            accessDenied.map(({ url, pattern }) => `  - ${pattern}  (${url})`).join('\n')
        ).toBe(0);

        // 4. No unexpected navigation errors
        expect(errors.length,
            `${errors.length} route(s) had navigation errors:\n` +
            errors.map(({ url, pattern, error }) => `  - ${pattern}  (${url}): ${error}`).join('\n')
        ).toBe(0);
    });
});
