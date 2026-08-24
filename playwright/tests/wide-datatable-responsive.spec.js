import { test, expect } from '@playwright/test';

/**
 * Non-régression : pages listées dans doc/plans/datatable_responsive_width_plan.md
 * (tableaux DataTables larges, HIGH/MEDIUM) doivent rester cohérentes sur écran
 * étroit et grand écran :
 *  - le tableau remplit son conteneur sur grand écran (pas de largeur figée
 *    trop étroite par "bAutoWidth")
 *  - #body (filtre/contenu) a la même largeur que le tableau
 *  - le bouton "Quitter" du menu reste dans le viewport à la position de
 *    scroll initiale (accessible sans scroll horizontal), sur tous les
 *    breakpoints
 *  - aucune erreur console
 *
 * Représente au moins un exemple de chaque pattern d'initialisation DataTables
 * (A : bs_footer.php global, B/C : init dédiée par page).
 *
 * Utilisateur de test : testadmin (admin global)
 */

async function login(page) {
    await page.goto('/index.php/auth/login');
    await page.fill('input[name="username"]', 'testadmin');
    await page.fill('input[name="password"]', 'password');
    await page.click('input[type="submit"], button[type="submit"]');
    await page.waitForLoadState('networkidle');

    const dialog = page.locator('.ui-dialog');
    if (await dialog.isVisible().catch(() => false)) {
        const closeBtn = page.locator('.ui-dialog-buttonpane button:has-text("OK")');
        if (await closeBtn.isVisible().catch(() => false)) {
            await closeBtn.click();
            await page.waitForTimeout(500);
        }
    }
}

const PAGES = [
    { name: 'planeur (pattern A, .datatable)', url: '/planeur/page' },
    { name: 'vols_avion (pattern C, table_vols_avion.js)', url: '/vols_avion/page' },
    { name: 'vols_planeur (pattern C, table_vols_planeur.js)', url: '/vols_planeur/page' },
    { name: 'membre (pattern B, inline)', url: '/membre/page' },
    { name: 'compta grand journal (pattern B, inline)', url: '/compta/page' },
];

for (const { name, url } of PAGES) {
    test.describe(`Largeur responsive — ${name}`, () => {
        test('écran étroit : #body suit la largeur du tableau, Quitter reste accessible', async ({ page }) => {
            await page.setViewportSize({ width: 375, height: 800 });
            const consoleErrors = [];
            page.on('console', (msg) => {
                // "Font Awesome Kit" fetch failure: CDN call unrelated to this fix,
                // fails in offline/sandboxed test environments regardless of any
                // code change here.
                if (msg.type() === 'error' && !msg.text().includes('Font Awesome Kit')) {
                    consoleErrors.push(msg.text());
                }
            });
            page.on('pageerror', (err) => consoleErrors.push(err.message));

            await login(page);
            await page.goto(url);
            await page.waitForTimeout(3000);

            const widths = await page.evaluate(() => {
                const table = document.querySelector('table.dataTable');
                const body = document.getElementById('body');
                return {
                    body: body ? body.offsetWidth : null,
                    table: table ? table.offsetWidth : null,
                };
            });

            expect(widths.table).not.toBeNull();
            // Le bug corrigé était #body restant à la largeur de l'écran pendant que
            // le tableau débordait largement (ex. 375 vs 1300+). #body doit toujours
            // être au moins aussi large que le tableau (une marge de padding normale
            // de container-fluid, ~24px, est attendue et sans conséquence visuelle).
            expect(widths.body).toBeGreaterThanOrEqual(widths.table - 30);

            // Le bouton "Quitter" doit être visible et cliquable sans scroll horizontal,
            // à la position de scroll initiale (x=0).
            const quitLink = page.locator('nav.navbar', { hasText: 'Quitter' }).getByText('Quitter');
            await expect(quitLink).toBeVisible();
            const box = await quitLink.boundingBox();
            expect(box.x).toBeGreaterThanOrEqual(0);
            expect(box.x + box.width).toBeLessThanOrEqual(375);

            expect(consoleErrors, `Erreurs console sur ${url}: ${consoleErrors.join(' | ')}`).toEqual([]);
        });

        test('grand écran : le tableau remplit la largeur disponible', async ({ page }) => {
            await page.setViewportSize({ width: 1600, height: 1000 });
            await login(page);
            await page.goto(url);
            await page.waitForTimeout(3000);

            const widths = await page.evaluate(() => {
                const table = document.querySelector('table.dataTable');
                const body = document.getElementById('body');
                return {
                    body: body ? body.offsetWidth : null,
                    table: table ? table.offsetWidth : null,
                    viewport: window.innerWidth,
                };
            });

            expect(widths.table).not.toBeNull();
            expect(widths.body).toBeGreaterThanOrEqual(widths.table - 30);
            // Le tableau ne doit pas rester figé à une largeur nettement plus étroite
            // que l'écran (symptôme historique de "bAutoWidth": true).
            expect(widths.table).toBeGreaterThan(widths.viewport * 0.5);
        });
    });
}
