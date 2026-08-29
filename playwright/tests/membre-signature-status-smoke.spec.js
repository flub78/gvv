// @ts-check
const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');

/**
 * Smoke test — Indicateur d'état de la signature sur la fiche d'édition membre
 *
 * Vérifie que la carte « signature » de application/views/membre/bs_formView.php
 * affiche directement si une signature de référence est enregistrée, sans qu'on
 * ait à ouvrir la page membre/signature/<login>.
 *
 * Fixture auto-gérée : un membre de test est créé, son signature_path est basculé
 * entre les deux états, puis le membre est supprimé (y compris en cas d'échec).
 */

const LOGIN_URL = '/index.php/auth/login';
const ADMIN_USER = { username: 'testadmin', password: 'password' };

const DB_CONFIG = {
    host: 'localhost',
    user: 'gvv_user',
    password: 'lfoyfgbj',
    database: 'gvv2',
};

const TEST_LOGIN = 'pwsigsmoke';

async function login(page, user) {
    await page.goto(LOGIN_URL);
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="username"]', user.username);
    await page.fill('input[name="password"]', user.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

async function checkNoPhpErrors(page) {
    const body = await page.textContent('body');
    expect(body).not.toContain('Fatal error');
    expect(body).not.toContain('Parse error');
    expect(body).not.toContain('A PHP Error was encountered');
    expect(body).not.toContain('An uncaught Exception was encountered');
}

async function cleanup(conn) {
    await conn.query('DELETE FROM membres WHERE mlogin = ?', [TEST_LOGIN]);
}

test.describe.serial('Fiche membre — indicateur de signature', () => {
    let conn;

    test.beforeAll(async () => {
        conn = await mysql.createConnection(DB_CONFIG);
        await cleanup(conn);
        await conn.query(
            "INSERT INTO membres (mlogin, mnom, mprenom, memail, actif, categorie) VALUES (?, 'SigSmoke', 'Test', 'pwsigsmoke@example.invalid', 1, '0')",
            [TEST_LOGIN]
        );
    });

    test.afterAll(async () => {
        await cleanup(conn);
        await conn.end();
    });

    test('aucune signature => la carte indique "Aucune signature enregistrée"', async ({ page }) => {
        await conn.query('UPDATE membres SET signature_path = NULL WHERE mlogin = ?', [TEST_LOGIN]);

        await login(page, ADMIN_USER);
        await page.goto('/index.php/membre/edit/' + TEST_LOGIN);
        await page.waitForLoadState('networkidle');
        await checkNoPhpErrors(page);

        await expect(page.locator('text=Aucune signature enregistrée')).toBeVisible();
        await expect(page.locator('.badge.bg-success', { hasText: 'Signature enregistrée' })).toHaveCount(0);
    });

    test('signature présente => la carte affiche le badge "Signature enregistrée"', async ({ page }) => {
        await conn.query(
            "UPDATE membres SET signature_path = 'uploads/signatures/membres/2026/08/pwsigsmoke.png' WHERE mlogin = ?",
            [TEST_LOGIN]
        );

        await login(page, ADMIN_USER);
        await page.goto('/index.php/membre/edit/' + TEST_LOGIN);
        await page.waitForLoadState('networkidle');
        await checkNoPhpErrors(page);

        await expect(page.locator('.badge.bg-success', { hasText: 'Signature enregistrée' })).toBeVisible();
        await expect(page.locator('text=Aucune signature enregistrée')).toHaveCount(0);
        await expect(page.locator('a', { hasText: 'Remplacer la signature' })).toBeVisible();
    });
});
