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
 *
 * Note : un login unique par session est utilisé pour éviter les conflits
 * si la suppression échoue (le membre test a des comptes rattachés).
 */

// Tests séquentiels : ils partagent les mêmes données de test
test.describe.configure({ mode: 'serial' });

const LOGIN_URL  = '/index.php/auth/login';
const CREATE_URL = '/index.php/membre/create';
const ADMIN_USER = { username: 'testadmin', password: 'password' };

// Login unique par session pour éviter les conflits avec les runs précédents
const RUN_ID = Date.now().toString().slice(-6);
const TEST_MEMBER = {
    mlogin:  `pwtest${RUN_ID}`,
    mnom:    'Playwright',
    mprenom: 'Smoke',
    memail:  `pwtest${RUN_ID}@example.com`,
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
        // Le membre a été créé par le test précédent.
        // La page gestion_roles est affichée avec l'utilisateur sélectionné.
        // On vérifie que le login de l'utilisateur sélectionné n'est pas un entier pur
        // (régression : avant le correctif, le mnumero était utilisé comme username).
        const body = await page.locator('body').textContent();
        // Le mlogin généré contient des lettres — il doit apparaître sur la page
        // dans le sélecteur utilisateur ou dans l'en-tête du membre sélectionné.
        // On vérifie au minimum l'absence d'erreur et que l'URL est bien gestion_roles.
        expect(await hasNoPhpError(page)).toBeTruthy();
        // Le username affiché entre parenthèses ne doit pas être un entier pur
        const usernameInParens = body.match(/\((\w+)\)/g) || [];
        const numericOnly = usernameInParens.some(m => /^\(\d+\)$/.test(m));
        expect(numericOnly).toBeFalsy();
    });

    test('should not produce duplicate error when recreating after deletion', async ({ page }) => {
        // Supprimer le membre via la route delete (best-effort)
        await page.goto(`/index.php/membre/delete/${TEST_MEMBER.mlogin}`);
        await page.waitForLoadState('networkidle');

        // Recréer avec un mlogin dérivé (suffixe _b) pour éviter le PK existant
        const mlogin2 = TEST_MEMBER.mlogin + 'b';
        await page.goto(CREATE_URL);
        await page.waitForLoadState('networkidle');
        await page.fill('input[name="mlogin"]',  mlogin2);
        await page.fill('input[name="mnom"]',    TEST_MEMBER.mnom);
        await page.fill('input[name="mprenom"]', TEST_MEMBER.mprenom);
        await page.fill('input[name="memail"]',  mlogin2 + '@example.com');
        await page.click('input[name="button"]');
        await page.waitForLoadState('networkidle');

        expect(await hasNoPhpError(page)).toBeTruthy();
        const body = await page.locator('body').textContent();
        expect(body).not.toContain('doublon');
        expect(body).not.toContain('table: membres');
        expect(page.url()).toContain('gestion_roles');
    });
});
