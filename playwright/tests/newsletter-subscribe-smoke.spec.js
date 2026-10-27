/**
 * Playwright smoke tests – Newsletter de veille (P1)
 *
 * Scenarios:
 *   1. Public subscribe form: fills name + email → success message displayed
 *   2. Confirm subscription via the token stored in the DB
 *   3. Public unsubscribe via the unsubscribe token
 *   4. A logged-in member can access the newsletter archive
 *   5. A club-admin can access the admin page and the publish form
 *   6. A non-admin member cannot access newsletter_admin (redirected/403)
 *
 * Prerequisites: test users asterix (member), panoramix (club-admin),
 *   migration 181, feature flag veille = true.
 * The test creates its own subscriber row and removes it in afterAll.
 */

const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');
const { DB_CONFIG } = require('./helpers/gvv-config');

test.describe.configure({ mode: 'serial' });

const MEMBER = { username: 'asterix',    password: 'password' };
const ADMIN  = { username: 'panoramix',  password: 'password' };
const TEST_EMAIL = 'zztest_playwright_newsletter@example.test';

let db;
let confirmToken;
let unsubToken;

async function login(page, user) {
    await page.goto('/index.php/auth/login');
    await page.fill('input[name="username"]', user.username);
    await page.fill('input[name="password"]', user.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

async function cleanup(db) {
    await db.query("DELETE FROM newsletter_sendings WHERE subscriber_id IN (SELECT id FROM newsletter_subscribers WHERE email = ?)", [TEST_EMAIL]);
    await db.query('DELETE FROM newsletter_subscribers WHERE email = ?', [TEST_EMAIL]);
    await db.query("DELETE FROM newsletters WHERE title LIKE 'ZZTest Playwright%'");
}

test.beforeAll(async () => {
    db = await mysql.createConnection(DB_CONFIG);
    await cleanup(db);
});

test.afterAll(async () => {
    await cleanup(db);
    await db.end();
});

// ─── Test 1 : formulaire d'abonnement public ──────────────────────────────────
test('subscribe form shows success message', async ({ page }) => {
    await page.goto('/index.php/newsletter_public/subscribe');
    await expect(page.locator('h3')).toContainText("S'abonner");

    await page.fill('input[name="first_name"]', 'PlaywrightTest');
    await page.fill('input[name="last_name"]',  'User');
    await page.fill('input[name="email"]',       TEST_EMAIL);
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    // Le message flash de succès doit contenir l'email
    await expect(page.locator('.alert-success')).toContainText(TEST_EMAIL);

    // Le token de confirmation doit exister en base
    const [rows] = await db.query('SELECT confirmation_token, unsubscribe_token FROM newsletter_subscribers WHERE email = ?', [TEST_EMAIL]);
    expect(rows.length).toBe(1);
    confirmToken = rows[0].confirmation_token;
    unsubToken   = rows[0].unsubscribe_token;
    expect(confirmToken).toBeTruthy();
});

// ─── Test 2 : confirmation de l'abonnement ────────────────────────────────────
test('confirm subscription via token', async ({ page }) => {
    await page.goto('/index.php/newsletter_public/confirm/' + confirmToken);
    await expect(page.locator('.alert-success')).toBeVisible();

    const [rows] = await db.query('SELECT confirmed FROM newsletter_subscribers WHERE email = ?', [TEST_EMAIL]);
    expect(Number(rows[0].confirmed)).toBe(1);
});

// ─── Test 3 : soumission du formulaire d'abonnement pour un email déjà abonné ─
test('re-subscribe confirmed email returns already-subscribed message', async ({ page }) => {
    await page.goto('/index.php/newsletter_public/subscribe');
    await page.fill('input[name="first_name"]', 'PlaywrightTest');
    await page.fill('input[name="last_name"]',  'User');
    await page.fill('input[name="email"]',       TEST_EMAIL);
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    await expect(page.locator('.alert-warning')).toBeVisible();
});

// ─── Test 4 : désinscription via le token ─────────────────────────────────────
test('unsubscribe via token shows success', async ({ page }) => {
    await page.goto('/index.php/newsletter_public/unsubscribe/' + unsubToken);
    // Page de confirmation avec bouton
    await expect(page.locator('button[type="submit"]')).toBeVisible();
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    await expect(page.locator('.alert-success')).toBeVisible();

    const [rows] = await db.query('SELECT unsubscribed_at FROM newsletter_subscribers WHERE email = ?', [TEST_EMAIL]);
    expect(rows[0].unsubscribed_at).not.toBeNull();
});

// ─── Test 5 : archive membres (connexion requise) ─────────────────────────────
test('member can access newsletter archive', async ({ page }) => {
    await login(page, MEMBER);
    await page.goto('/index.php/newsletter');
    await expect(page.locator('h3')).toContainText('Archive');
    // Pas d'erreur 500 ni redirection
    await expect(page).toHaveURL(/newsletter/);
});

// ─── Test 6 : accès admin (club-admin) ───────────────────────────────────────
test('club-admin can access newsletter_admin', async ({ page }) => {
    await login(page, ADMIN);
    await page.goto('/index.php/newsletter_admin');
    await expect(page.locator('h3')).toContainText('Abonnement à la newsletter');
    await expect(page.locator('a:has-text("Publier une newsletter")')).toHaveCount(0);
});

// ─── Test 7 : accès admin refusé à un simple membre ──────────────────────────
test('plain member cannot access newsletter_admin', async ({ page }) => {
    await login(page, MEMBER);
    const response = await page.goto('/index.php/newsletter_admin');
    // Doit être redirigé ou retourner 403
    const status = response ? response.status() : 0;
    const url = page.url();
    const isBlocked = status === 403 || !url.includes('newsletter_admin') || await page.locator('h3:has-text("Abonnement à la newsletter")').count() === 0;
    expect(isBlocked).toBe(true);
});
