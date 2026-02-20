/**
 * Playwright smoke tests for section menu visibility flags
 *
 * Validates that:
 * - The three new fields (gestion_planeurs, gestion_avions, libelle_menu_avions)
 *   appear in the section edit form.
 * - The Planeurs and Avions/ULM navigation menus are shown/hidden based on
 *   the section flags.
 * - A custom libelle_menu_avions replaces the default menu label.
 *
 * @see application/migrations/072_section_menu_flags.php
 * @see doc/design_notes/section_menu_visibility_plan.md
 */

const { test, expect } = require('@playwright/test');

// Section 1 (Planeur) is used as the test target; it is modified then restored.
const TEST_SECTION_ID = 1;
const CUSTOM_LABEL = 'MoteurTest';

async function login(page) {
    await page.goto('/index.php/auth/login');
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="username"]', 'testadmin');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

async function selectSection(page, sectionId) {
    // POST to set_section to update the session, then navigate to welcome.
    // Avoids the AJAX → window.location.href redirect race condition.
    await page.evaluate(async (id) => {
        await fetch('/index.php/user_roles_per_section/set_section', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: `section=${id}`,
        });
    }, String(sectionId));
    await page.goto('/index.php/welcome');
    await page.waitForLoadState('networkidle');
}

async function editSection(page, sectionId) {
    await page.goto(`/index.php/sections/edit/${sectionId}`);
    await page.waitForLoadState('networkidle');
}

async function saveSection(page) {
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

// ==================== Form field tests ====================

test.describe('Section form — new fields', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('should display gestion_planeurs checkbox in section form', async ({ page }) => {
        await editSection(page, TEST_SECTION_ID);
        const field = page.locator('input[name="gestion_planeurs"][type="checkbox"]');
        await expect(field).toBeVisible();
    });

    test('should display gestion_avions checkbox in section form', async ({ page }) => {
        await editSection(page, TEST_SECTION_ID);
        const field = page.locator('input[name="gestion_avions"][type="checkbox"]');
        await expect(field).toBeVisible();
    });

    test('should display libelle_menu_avions text field in section form', async ({ page }) => {
        await editSection(page, TEST_SECTION_ID);
        const field = page.locator('input[name="libelle_menu_avions"]');
        await expect(field).toBeVisible();
    });
});

// ==================== Menu visibility tests ====================

test.describe('Navigation menu visibility by section flags', () => {
    test.describe.configure({ mode: 'serial' });
    test.beforeEach(async ({ page }) => {
        await login(page);
        // Start with both flags OFF on section 1 (default after migration)
        await editSection(page, TEST_SECTION_ID);
        const planeurs = page.locator('input[name="gestion_planeurs"][type="checkbox"]');
        const avions   = page.locator('input[name="gestion_avions"][type="checkbox"]');
        if (await planeurs.isChecked()) await planeurs.uncheck();
        if (await avions.isChecked())   await avions.uncheck();
        await saveSection(page);
    });

    test.afterEach(async ({ page }) => {
        // Restore section 1 to no flags, no custom label
        await editSection(page, TEST_SECTION_ID);
        const planeurs = page.locator('input[name="gestion_planeurs"][type="checkbox"]');
        const avions   = page.locator('input[name="gestion_avions"][type="checkbox"]');
        const label    = page.locator('input[name="libelle_menu_avions"]');
        if (await planeurs.isChecked()) await planeurs.uncheck();
        if (await avions.isChecked())   await avions.uncheck();
        await label.fill('');
        await saveSection(page);
    });

    test('flags OFF — both menus hidden', async ({ page }) => {
        await page.goto('/index.php/welcome');
        await page.waitForLoadState('networkidle');

        // Select section 1 via JS (section may be inherited from login redirect)
        await selectSection(page, TEST_SECTION_ID);

        const planeurMenu = page.locator('nav a.nav-link', { hasText: /planeur/i });
        const avionMenu   = page.locator('nav a.nav-link', { hasText: /avion/i });
        await expect(planeurMenu).toHaveCount(0);
        await expect(avionMenu).toHaveCount(0);
    });

    test('gestion_planeurs ON — Planeurs menu visible', async ({ page }) => {
        await editSection(page, TEST_SECTION_ID);
        await page.locator('input[name="gestion_planeurs"][type="checkbox"]').check();
        await saveSection(page);

        await selectSection(page, TEST_SECTION_ID);

        const planeurMenu = page.locator('nav a.nav-link.dropdown-toggle', { hasText: /planeur/i });
        await expect(planeurMenu).toBeVisible();
    });

    test('gestion_avions ON — Avions menu visible with default label', async ({ page }) => {
        await editSection(page, TEST_SECTION_ID);
        await page.locator('input[name="gestion_avions"][type="checkbox"]').check();
        await saveSection(page);

        await selectSection(page, TEST_SECTION_ID);

        // Default translation key gvv_menu_airplane — text contains "Avion" in French
        const avionMenu = page.locator('nav a.nav-link.dropdown-toggle', { hasText: /avion/i });
        await expect(avionMenu).toBeVisible();
    });

    test('libelle_menu_avions set — custom label shown in menu', async ({ page }) => {
        await editSection(page, TEST_SECTION_ID);
        await page.locator('input[name="gestion_avions"][type="checkbox"]').check();
        await page.locator('input[name="libelle_menu_avions"]').fill(CUSTOM_LABEL);
        await saveSection(page);

        await selectSection(page, TEST_SECTION_ID);

        const customMenu = page.locator('nav a.nav-link.dropdown-toggle', { hasText: CUSTOM_LABEL });
        await expect(customMenu).toBeVisible();
    });
});
