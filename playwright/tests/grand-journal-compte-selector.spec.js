import { test, expect } from '@playwright/test';

/**
 * Tests du sélecteur de compte dans le Grand journal et la page journal_compte.
 *
 * Bug #: Sélectionner "Tous..." dans le filtre du Grand Journal redirige vers
 * compta/journal_compte/{id} au lieu de rester sur compta/page.
 *
 * Scénarios testés :
 *  1. Grand journal : déselectionner le compte reste sur compta/page
 *  2. Grand journal : sélectionner un compte redirige vers journal_compte/{id}
 *  3. Grand journal : filtrer par date sans compte
 *  4. Grand journal : filtrer par montant sans compte
 *  5. Grand journal : filtrer avec un compte actif
 *  6. journal_compte : sélectionner "Tous..." retourne au grand journal
 *  7. journal_compte : changer de compte affiche le nouveau compte
 *
 * Utilisateur de test : testadmin (trésorier section 1, accès comptes)
 * Comptes section 1 : id=9 (Subvention CG), id=10 (Bourses FFVV)
 */

const FIRST_ACCOUNT_ID  = 9;
const SECOND_ACCOUNT_ID = 10;

async function login(page) {
    await page.goto('/index.php/auth/login');
    await page.fill('input[name="username"]', 'testadmin');
    await page.fill('input[name="password"]', 'password');
    await page.click('input[type="submit"], button[type="submit"]');
    await page.waitForLoadState('networkidle');

    // Fermer le modal "Message du jour" s'il apparaît
    const dialog = page.locator('.ui-dialog');
    if (await dialog.isVisible().catch(() => false)) {
        const closeBtn = page.locator('.ui-dialog-buttonpane button:has-text("OK")');
        if (await closeBtn.isVisible().catch(() => false)) {
            await closeBtn.click();
            await page.waitForTimeout(500);
        }
    }
}

async function openFilterAccordion(page) {
    // S'assurer que l'accordéon filtre est ouvert
    const accordion = page.locator('#panelsStayOpen-collapseOne, #panel_filter_id');
    const isVisible = await accordion.first().isVisible().catch(() => false);
    if (!isVisible) {
        const btn = page.locator('.accordion-button').first();
        await btn.click();
        await page.waitForTimeout(300);
    }
}

test.describe('Grand journal — sélecteur de compte', () => {
    test.describe.configure({ mode: 'serial' });

    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    // ─── Tests sur la page Grand journal (compta/page) ───────────────────────

    test('0. Cliquer "remove all items" (×) sur le grand journal reste sur compta/page sans compte sélectionné', async ({ page }) => {
        // Naviguer vers journal_compte pour avoir un compte sélectionné dans le sélecteur
        await page.goto(`/index.php/compta/journal_compte/${FIRST_ACCOUNT_ID}`);
        await page.waitForLoadState('networkidle');

        // Retourner au grand journal via le bouton retour ou en naviguant directement
        await page.goto('/index.php/compta/page');
        await page.waitForLoadState('networkidle');

        // Attendre que Select2 soit initialisé sur le sélecteur de compte
        await page.waitForSelector('.select2-container', { timeout: 5000 });

        // Sur le grand journal, aucun compte ne doit être sélectionné :
        // le sélecteur doit afficher le placeholder "Filtre..." (pas un compte)
        const select2Rendered = page.locator('.select2-selection__rendered').first();
        await expect(select2Rendered).toHaveAttribute('title', /Filtre|Tous/i);

        // Si un compte était quand même sélectionné (× visible), le cliquer doit rester sur compta/page
        const clearBtn = page.locator('.select2-selection__clear').first();
        if (await clearBtn.isVisible().catch(() => false)) {
            await clearBtn.click();
            await page.waitForLoadState('networkidle');

            // Doit rester sur compta/page, pas être redirigé vers journal_compte
            await expect(page).toHaveURL(/compta\/page/);
            await expect(page).not.toHaveURL(/journal_compte/);

            // Après le clic ×, aucun compte ne doit être sélectionné
            await expect(page.locator('.select2-selection__rendered').first())
                .toHaveAttribute('title', /Filtre|Tous/i);
        }
    });

    test('1. Sélectionner "Tous..." sur le grand journal reste sur compta/page', async ({ page }) => {
        await page.goto('/index.php/compta/page');
        await page.waitForLoadState('networkidle');

        await openFilterAccordion(page);

        // Sélectionner "Tous..." dans le sélecteur de compte
        const selector = page.locator('#selector');
        await expect(selector).toBeVisible();
        await selector.selectOption('all');

        // Attendre la navigation
        await page.waitForLoadState('networkidle');

        // L'URL doit rester sur compta/page (pas journal_compte)
        await expect(page).toHaveURL(/compta\/page/);
        // Ne pas être redirigé vers journal_compte
        await expect(page).not.toHaveURL(/journal_compte/);
    });

    test('2. Sélectionner un compte sur le grand journal redirige vers journal_compte', async ({ page }) => {
        await page.goto('/index.php/compta/page');
        await page.waitForLoadState('networkidle');

        await openFilterAccordion(page);

        const selector = page.locator('#selector');
        await expect(selector).toBeVisible();

        // Sélectionner un compte précis
        await selector.selectOption(String(FIRST_ACCOUNT_ID));
        await page.waitForLoadState('networkidle');

        // Doit être redirigé vers journal_compte/{id}
        await expect(page).toHaveURL(new RegExp(`journal_compte/${FIRST_ACCOUNT_ID}`));
    });

    test('3. Filtrer par date sans compte sur le grand journal', async ({ page }) => {
        await page.goto('/index.php/compta/page');
        await page.waitForLoadState('networkidle');

        await openFilterAccordion(page);

        // S'assurer qu'aucun compte n'est sélectionné (choisir "Tous...")
        const selector = page.locator('#selector');
        if (await selector.isVisible().catch(() => false)) {
            // On ne change pas le compte ici, on vérifie que la page du filtre
            // reste accessible même sans compte sélectionné
        }

        // Remplir le filtre de date
        const filterDate = page.locator('input[name="filter_date"]');
        await expect(filterDate).toBeVisible();
        await filterDate.fill('01/01/2024');

        const dateEnd = page.locator('input[name="date_end"]');
        await expect(dateEnd).toBeVisible();
        await dateEnd.fill('31/12/2024');

        // Soumettre le filtre
        const filterBtn = page.locator('button:has-text("Filtrer"), input[value="Filtrer"]');
        await filterBtn.click();
        await page.waitForLoadState('networkidle');

        // Doit rester sur compta/page
        await expect(page).toHaveURL(/compta\/page/);
        await expect(page).not.toHaveURL(/journal_compte/);
    });

    test('4. Filtrer par montant sans compte sur le grand journal', async ({ page }) => {
        await page.goto('/index.php/compta/page');
        await page.waitForLoadState('networkidle');

        await openFilterAccordion(page);

        // Remplir les filtres de montant
        const montantMin = page.locator('input[name="montant_min"]');
        await expect(montantMin).toBeVisible();
        await montantMin.fill('10');

        const montantMax = page.locator('input[name="montant_max"]');
        await expect(montantMax).toBeVisible();
        await montantMax.fill('1000');

        // Soumettre le filtre
        const filterBtn = page.locator('button:has-text("Filtrer"), input[value="Filtrer"]');
        await filterBtn.click();
        await page.waitForLoadState('networkidle');

        // Doit rester sur compta/page
        await expect(page).toHaveURL(/compta\/page/);
        await expect(page).not.toHaveURL(/journal_compte/);
    });

    test('5. Filtrer par date avec un compte actif sur le grand journal', async ({ page }) => {
        // D'abord naviguer vers journal_compte pour avoir un compte sélectionné, puis
        // utiliser le filtre de date
        await page.goto(`/index.php/compta/journal_compte/${FIRST_ACCOUNT_ID}`);
        await page.waitForLoadState('networkidle');

        // Ouvrir l'accordéon filtre sur la page journal_compte
        const filterBtn = page.locator('#panel-filtre button.accordion-button');
        const isExpanded = await filterBtn.getAttribute('aria-expanded');
        if (isExpanded === 'false') {
            await filterBtn.click();
            await page.waitForTimeout(300);
        }

        // Remplir la date de début
        const filterDate = page.locator('input[name="filter_date"]');
        await expect(filterDate).toBeVisible();
        await filterDate.fill('01/01/2024');

        // Soumettre
        const submitBtn = page.locator('button:has-text("Filtrer"), input[value="Filtrer"]');
        await submitBtn.click();
        await page.waitForLoadState('networkidle');

        // Doit rester sur la page du compte (filterValidation ou journal_compte) avec le bon id
        await expect(page).toHaveURL(new RegExp(`/${FIRST_ACCOUNT_ID}`));
        await expect(page).not.toHaveURL(/comptes\/balance/);
    });

    // ─── Tests sur la page journal_compte ────────────────────────────────────

    test('6. Sélectionner "Tous..." sur journal_compte retourne au grand journal', async ({ page }) => {
        await page.goto(`/index.php/compta/journal_compte/${FIRST_ACCOUNT_ID}`);
        await page.waitForLoadState('networkidle');

        // L'accordéon "Information du compte" doit être visible
        // Ouvrir l'accordéon information du compte
        const compteAccordionBtn = page.locator('#panel-compte button.accordion-button');
        if (await compteAccordionBtn.isVisible().catch(() => false)) {
            const isExpanded = await compteAccordionBtn.getAttribute('aria-expanded');
            if (isExpanded === 'false') {
                await compteAccordionBtn.click();
                await page.waitForTimeout(300);
            }
        }

        // Le sélecteur de compte doit être visible
        const selector = page.locator('#selector');
        await expect(selector).toBeVisible();

        // Vérifier que le compte courant est sélectionné
        const currentValue = await selector.inputValue();
        expect(currentValue).toBe(String(FIRST_ACCOUNT_ID));

        // Sélectionner "Tous..."
        await selector.selectOption('all');
        await page.waitForLoadState('networkidle');

        // Doit retourner sur le grand journal (compta/page)
        await expect(page).toHaveURL(/compta\/page/);
        await expect(page).not.toHaveURL(/journal_compte/);
    });

    test('7. Changer de compte sur journal_compte affiche le nouveau compte', async ({ page }) => {
        await page.goto(`/index.php/compta/journal_compte/${FIRST_ACCOUNT_ID}`);
        await page.waitForLoadState('networkidle');

        // Ouvrir l'accordéon information du compte si nécessaire
        const compteAccordionBtn = page.locator('#panel-compte button.accordion-button');
        if (await compteAccordionBtn.isVisible().catch(() => false)) {
            const isExpanded = await compteAccordionBtn.getAttribute('aria-expanded');
            if (isExpanded === 'false') {
                await compteAccordionBtn.click();
                await page.waitForTimeout(300);
            }
        }

        const selector = page.locator('#selector');
        await expect(selector).toBeVisible();

        // Changer vers le deuxième compte
        await selector.selectOption(String(SECOND_ACCOUNT_ID));
        await page.waitForLoadState('networkidle');

        // Doit naviguer vers journal_compte du nouveau compte
        await expect(page).toHaveURL(new RegExp(`journal_compte/${SECOND_ACCOUNT_ID}`));
    });
});
