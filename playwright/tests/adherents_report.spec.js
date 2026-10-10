// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Tests smoke pour la page Statistiques adhérents (adherents_report)
 *
 * @see doc/plans/statistiques_adherents_plan.md
 */

const LOGIN_URL = '/index.php/auth/login';
const REPORT_URL = '/index.php/adherents_report';
const CA_USER = { username: 'testadmin', password: 'password' };
const MEMBER_USER = { username: 'testuser', password: 'password' };

async function login(page, user) {
    await page.goto('/index.php/auth/logout');
    await page.goto(LOGIN_URL);
    await page.waitForSelector('input[name="username"]', { timeout: 5000 });
    await page.fill('input[name="username"]', user.username);
    await page.fill('input[name="password"]', user.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

/** Lit un tableau de répartition : { cléDeLigne: [valeurs par colonne] } et la ligne total */
async function readRepartition(page, cardId) {
    return page.locator(`#${cardId} table`).evaluate(table => {
        const value = cell => parseInt(cell.innerText.trim().split(/\s/)[0], 10);
        const rows = {};
        table.querySelectorAll('tbody tr').forEach(tr => {
            rows[tr.dataset.ageClass] = Array.from(tr.querySelectorAll('td')).slice(1).map(value);
        });
        const total = Array.from(table.querySelectorAll('tfoot td')).slice(1).map(value);
        return { rows, total };
    });
}

test.describe('Statistiques adhérents', () => {

    test.beforeEach(async ({ page }) => {
        await login(page, CA_USER);
    });

    test('affiche les classes réglementaires et les tranches de 10 ans', async ({ page }) => {
        await page.goto(REPORT_URL);
        await page.waitForLoadState('networkidle');

        await expect(page.locator('h3')).toContainText('Statistiques adhérents');
        await expect(page.locator('#year_selector')).toBeVisible();

        const regl = page.locator('#classes_reglementaires');
        await expect(regl).toBeVisible();
        await expect(regl.locator('tbody')).toContainText('Moins de 25 ans');
        await expect(regl.locator('tbody')).toContainText('25-59 ans');
        await expect(regl.locator('tbody')).toContainText('60 ans et plus');
        await expect(regl.locator('tbody')).toContainText('Âge inconnu');
        await expect(regl.locator('tbody tr')).toHaveCount(4);
        await expect(regl.locator('thead th').last()).toContainText('Total Club');

        const tranches = page.locator('#tranches_10_ans');
        await expect(tranches).toBeVisible();
        await expect(tranches.locator('tbody tr')).toHaveCount(9);
        await expect(tranches.locator('tbody')).toContainText('80 ans et plus');
        await expect(tranches.locator('canvas#tranches_chart')).toBeVisible();

        await expect(page.locator('.alert-info')).toContainText('1er janvier');
    });

    test('les totaux sont cohérents entre lignes et tableaux', async ({ page }) => {
        await page.goto(REPORT_URL);
        await page.waitForLoadState('networkidle');

        const regl = await readRepartition(page, 'classes_reglementaires');
        const tranches = await readRepartition(page, 'tranches_10_ans');

        expect(tranches.total).toEqual(regl.total);
        for (const rep of [regl, tranches]) {
            rep.total.forEach((total, col) => {
                const sum = Object.values(rep.rows).reduce((acc, row) => acc + row[col], 0);
                expect(sum).toBe(total);
            });
        }
        expect(tranches.rows.unknown).toEqual(regl.rows.unknown);
    });

    test('la liste des âges inconnus est accessible', async ({ page }) => {
        await page.goto(REPORT_URL);
        await page.waitForLoadState('networkidle');

        const regl = await readRepartition(page, 'classes_reglementaires');
        const unknownClub = regl.rows.unknown[regl.rows.unknown.length - 1];

        if (unknownClub === 0) {
            await expect(page.locator('.alert-success')).toBeVisible();
            return;
        }

        const list = page.locator('#age_inconnu_list');
        await expect(list).toBeHidden();
        await page.click('button[data-bs-target="#age_inconnu_list"]');
        await expect(list).toBeVisible();
        await expect(list.locator('tbody tr')).toHaveCount(unknownClub);

        const firstLink = list.locator('tbody a').first();
        await expect(firstLink).toHaveAttribute('href', /membre\/edit\//);
        await firstLink.click();
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/membre\/edit\//);
    });

    test('le changement d\'année recharge la page', async ({ page }) => {
        await page.goto(REPORT_URL);
        await page.waitForLoadState('networkidle');

        const currentYear = await page.locator('#year_selector').inputValue();
        const values = await page.locator('#year_selector option').evaluateAll(opts => opts.map(o => o.value));
        const otherYear = values.find(v => v !== currentYear);
        test.skip(!otherYear, 'Une seule année disponible');

        await Promise.all([
            page.waitForNavigation(),
            page.selectOption('#year_selector', otherYear),
        ]);
        await expect(page.locator('h3')).toContainText(otherYear);

        // Restaurer l'année initiale en session
        await page.goto(`/index.php/adherents_report/set_year/${currentYear}`);
    });

    test('un membre sans rôle CA n\'a pas accès aux statistiques', async ({ page }) => {
        await login(page, MEMBER_USER);
        await page.goto(REPORT_URL);
        await page.waitForLoadState('networkidle');
        await expect(page.locator('#classes_reglementaires')).toHaveCount(0);
    });
});
