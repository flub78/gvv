// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Smoke tests — Création d'un membre
 *
 * Vérifie :
 * - La création d'un membre via le formulaire admin
 * - L'utilisateur correspondant est créé avec le bon mlogin (pas le mnumero)
 * - La redirection vers gestion_roles après création
 * - Pas d'erreur de doublon lors d'une recréation après suppression
 */

// Tests séquentiels : ils partagent le même login de test
test.describe.configure({ mode: 'serial' });

const LOGIN_URL  = '/index.php/auth/login';
const CREATE_URL = '/index.php/membre/create';
const ADMIN_USER = { username: 'testadmin', password: 'password' };

const TEST_MEMBER = {
    mlogin:  'pw_smoke_test',
    mnom:    'Playwright',
    mprenom: 'Test',
    memail:  'playwright.test.membre@example.com',
};

async function loginAs(page, user) {
    await page.goto(LOGIN_URL);
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="username"]', user.username);
    await page.fill('input[name="password"]', user.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

async function hasNoPhpError(page) {
    const body = await page.locator('body').textContent();
    return !body.includes('Fatal error')
        && !body.includes('Parse error')
        && !body.includes('A PHP Error was encountered')
        && !body.includes('Uncaught');
}

async function deleteMemberIfExists(page) {
    await page.goto(`/index.php/membre/delete/${TEST_MEMBER.mlogin}`);
    await page.waitForLoadState('networkidle');
}

async function fillAndSubmitMemberForm(page) {
    await page.goto(CREATE_URL);
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="mlogin"]',  TEST_MEMBER.mlogin);
    await page.fill('input[name="mnom"]',    TEST_MEMBER.mnom);
    await page.fill('input[name="mprenom"]', TEST_MEMBER.mprenom);
    await page.fill('input[name="memail"]',  TEST_MEMBER.memail);
    await page.click('input[name="button"]');
    await page.waitForLoadState('networkidle');
}

test.describe('Création de membre — smoke tests', () => {

    test.beforeAll(async ({ browser }) => {
        const page = await browser.newPage();
        await loginAs(page, ADMIN_USER);
        await deleteMemberIfExists(page);
        await page.close();
    });

    test.afterAll(async ({ browser }) => {
        const page = await browser.newPage();
        await loginAs(page, ADMIN_USER);
        await deleteMemberIfExists(page);
        await page.close();
    });

    test.beforeEach(async ({ page }) => {
        await loginAs(page, ADMIN_USER);
    });

    test('should display the member creation form', async ({ page }) => {
        await page.goto(CREATE_URL);
        await page.waitForLoadState('networkidle');

        expect(await hasNoPhpError(page)).toBeTruthy();
        await expect(page.locator('input[name="mlogin"]')).toBeVisible();
        await expect(page.locator('input[name="mnom"]')).toBeVisible();
        await expect(page.locator('input[name="mprenom"]')).toBeVisible();
    });

    test('should create a member and redirect to gestion_roles', async ({ page }) => {
        await fillAndSubmitMemberForm(page);

        expect(await hasNoPhpError(page)).toBeTruthy();
        expect(page.url()).toContain('gestion_roles');
    });

    test('should create user with mlogin as username (not mnumero)', async ({ page }) => {
        // Le membre et l'user existent depuis le test précédent
        // On vérifie la page gestion_roles via l'URL courante
        await page.goto('/index.php/gestion_roles');
        await page.waitForLoadState('networkidle');

        // Sélectionner le membre créé dans la liste
        const options = await page.locator('select[name="user_id"] option').all();
        let userVal = null;
        for (const opt of options) {
            const text = await opt.textContent();
            if (text && text.includes(TEST_MEMBER.mlogin)) {
                userVal = await opt.getAttribute('value');
                break;
            }
        }

        // Si on trouve l'option, la valeur (user_id) doit être numérique
        // mais le texte (username) doit être le mlogin et non un simple nombre
        if (userVal) {
            // L'identifiant numérique (user_id) est dans l'attribut value — c'est attendu
            // Le texte de l'option doit contenir le mlogin, pas uniquement un chiffre
            const optText = await page.locator(`select[name="user_id"] option[value="${userVal}"]`).textContent();
            expect(optText).toContain(TEST_MEMBER.mlogin);
            expect(optText).not.toMatch(/^\s*\d+\s*$/);  // le texte ne doit pas être purement numérique
        } else {
            // Fallback : vérifier via la DB que le username n'est pas numérique
            // En naviguant directement vers la page du membre dans gestion_roles
            await page.goto(`/index.php/gestion_roles`);
            const body = await page.locator('body').textContent();
            // pw_smoke_test doit apparaître quelque part dans le sélecteur d'utilisateurs
            expect(body).toContain(TEST_MEMBER.mlogin);
        }
    });

    test('should not produce duplicate error when recreating after deletion', async ({ page }) => {
        // Supprimer le membre créé par les tests précédents
        await deleteMemberIfExists(page);

        // Recréer — ne doit pas produire d'erreur de doublon sur mnumero
        await fillAndSubmitMemberForm(page);

        expect(await hasNoPhpError(page)).toBeTruthy();
        const body = await page.locator('body').textContent();
        expect(body).not.toContain('doublon');
        expect(body).not.toContain('existe déjà');
        expect(page.url()).toContain('gestion_roles');
    });
});
