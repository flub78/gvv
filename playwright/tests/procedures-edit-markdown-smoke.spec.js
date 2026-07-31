/**
 * Smoke test for procedures/edit_markdown/{id}
 *
 * Regression test: the view application/views/procedures/bs_editMarkdown.php
 * was missing, causing "Unable to load the requested file: procedures/editMarkdown.php".
 *
 * Usage:
 *   npx playwright test tests/procedures-edit-markdown-smoke.spec.js
 */

const { test, expect } = require('@playwright/test');

const LOGIN_URL = '/index.php/auth/login';
const TEST_USER = { username: 'testadmin', password: 'password' };
const PROCEDURE_ID = 4;

test.describe('Procedures - edit markdown smoke test', () => {
  test('edit_markdown page loads and save works', async ({ page }) => {
    await page.goto(LOGIN_URL);
    await page.fill('input[name="username"]', TEST_USER.username);
    await page.fill('input[name="password"]', TEST_USER.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');

    await page.goto(`/index.php/procedures/edit_markdown/${PROCEDURE_ID}`);
    await page.waitForLoadState('networkidle');

    const bodyText = await page.locator('body').innerText();
    expect(bodyText).not.toContain('Unable to load the requested file');
    expect(bodyText).not.toContain('An Error Was Encountered');

    const textarea = page.locator('#markdown-content');
    await expect(textarea).toBeVisible();

    const marker = `smoke test ${Date.now()}`;
    await textarea.fill(marker);

    await page.click('#save-btn');
    await expect(page.locator('#save-feedback')).toContainText('Contenu sauvegardé');

    await page.reload();
    await page.waitForLoadState('networkidle');
    await expect(page.locator('#markdown-content')).toHaveValue(marker);
  });
});
