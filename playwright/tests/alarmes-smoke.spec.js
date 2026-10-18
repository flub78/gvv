/**
 * Playwright smoke tests – Alarmes, Lot 1
 *
 * Verifies:
 *   1. A member configures alarms; "Mes alarmes" is hidden without subscription
 *      and reachable once subscribed; a subscription without channel is
 *      refused with a message
 *   2. A club-admin sees the member selector positioned on himself, is offered
 *      more alarms than a plain member (documents he may supervise) and can
 *      configure another member, who then sees the subscription with its author
 *   3. A club-admin adds and deletes a category subscription
 *   4. The scheduler URL answers 403 with a wrong secret
 *
 * Prerequisites: test users asterix (member) and panoramix (club-admin),
 * migration 177, gestion_documentaire enabled.
 * Every subscription and setting created here is removed in afterAll.
 */

const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');
const { DB_CONFIG } = require('./helpers/gvv-config');

const MEMBER = { username: 'asterix', password: 'password' };
const ADMIN = { username: 'panoramix', password: 'password' };

async function login(page, user) {
    await page.goto('/index.php/auth/login');
    await page.fill('input[name="username"]', user.username);
    await page.fill('input[name="password"]', user.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

async function cleanup() {
    const db = await mysql.createConnection(DB_CONFIG);
    try {
        const logins = [MEMBER.username, ADMIN.username];
        await db.query('DELETE FROM alarm_subscriptions WHERE subscriber_login IN (?)', [logins]);
        await db.query('DELETE FROM alarm_subscriptions WHERE subscriber_login IS NULL AND created_by IN (?)', [logins]);
        await db.query('DELETE FROM alarm_member_settings WHERE member_login IN (?)', [logins]);
    } finally {
        await db.end();
    }
}

test.describe('Alarmes – Lot 1', () => {
    // Les tests modifient la configuration des mêmes membres : exécution en série
    test.describe.configure({ mode: 'serial' });
    test.beforeAll(cleanup);
    test.afterAll(cleanup);

    test('un membre configure ses alarmes', async ({ page }) => {
        await login(page, MEMBER);

        // Sans abonnement : pas d'accès à « Mes alarmes », avec un message
        await page.goto('/index.php/mes_alarmes');
        await expect(page).toHaveURL(/alarmes_config/);
        await expect(page.locator('.alert-danger')).toBeVisible();
        await expect(page.locator('a[href*="mes_alarmes"]')).toHaveCount(0);

        // Pas de canal : refus explicite
        const first = page.locator('input[name="alarms[]"]').first();
        await first.check();
        await page.click('#alarm-config-form button[type="submit"]');
        await expect(page.locator('.alert-danger')).toBeVisible();

        // Avec un canal : enregistré
        await page.locator('input[name="alarms[]"]').first().check();
        await page.check('#channel_email');
        await page.click('#alarm-config-form button[type="submit"]');
        await expect(page.locator('.alert-success')).toBeVisible();
        await expect(page.locator('input[name="alarms[]"]').first()).toBeChecked();

        // « Mes alarmes » est maintenant accessible
        await page.goto('/index.php/mes_alarmes');
        await expect(page).toHaveURL(/mes_alarmes/);
        await expect(page.locator('h3')).toBeVisible();
        expect(await page.textContent('body')).not.toMatch(/PHP Error|Fatal error|A Database Error/);
    });

    test('un administrateur configure les alarmes d\'un autre membre', async ({ page }) => {
        await login(page, ADMIN);
        await page.goto('/index.php/alarmes_config');

        // Sélecteur positionné sur son propre profil
        await expect(page.locator('#membre')).toHaveValue(ADMIN.username);
        const adminAlarms = await page.locator('input[name="alarms[]"][type="checkbox"]').count();

        // Configuration d'un autre membre
        await page.selectOption('#membre', MEMBER.username);
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(new RegExp('membre=' + MEMBER.username));
        // Les documents de section ou de club ne sont proposés qu'à ceux qui peuvent les superviser
        expect(await page.locator('input[name="alarms[]"][type="checkbox"]').count()).toBeLessThan(adminAlarms);
        const second = page.locator('input[name="alarms[]"]').nth(1);
        const alarmId = await second.getAttribute('value');
        await second.check();
        await page.check('#channel_motd');
        await page.click('#alarm-config-form button[type="submit"]');
        await expect(page.locator('.alert-success')).toBeVisible();
        await expect(page.locator(`input[name="alarms[]"][value="${alarmId}"]`)).toBeChecked();

        // Le membre voit l'abonnement et son auteur
        await page.goto('/index.php/auth/logout');
        await login(page, MEMBER);
        await page.goto('/index.php/alarmes_config');
        await expect(page.locator(`input[name="alarms[]"][value="${alarmId}"]`)).toBeChecked();
        await expect(page.locator(`label[for="alarm_${alarmId}"]`)).toContainText('Panoramix');
    });

    test('un administrateur gère les abonnements de catégories', async ({ page }) => {
        await login(page, ADMIN);
        await page.goto('/index.php/alarmes_admin');
        await expect(page.locator('h3')).toBeVisible();

        await page.selectOption('#role_id', { label: 'Instructeur' });
        await page.click('#add-category-form button[type="submit"]');
        await expect(page.locator('.alert-success')).toBeVisible();
        await expect(page.locator('#categories-table')).toContainText('Instructeur');

        await page.locator('#categories-table button[type="submit"]').first().click();
        await expect(page.locator('.alert-success')).toBeVisible();
    });

    test('un simple membre n\'accède pas à l\'administration', async ({ page }) => {
        await login(page, MEMBER);
        const response = await page.goto('/index.php/alarmes_admin');
        expect(response.status()).toBe(403);
    });

    test('le déclencheur refuse un mauvais secret', async ({ request }) => {
        const response = await request.get('/index.php/alarm_scheduler/run/mauvais-secret');
        expect(response.status()).toBe(403);
    });
});
