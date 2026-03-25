const { test, expect } = require('@playwright/test');

const STOP_WORDS = new Set([
    'BALANCE', 'COMPTES', 'COMPTE', 'SECTION', 'GENERAL', 'GENERALE', 'TOUS',
    'DEBITEURS', 'CREDITEURS', 'SOLDE', 'SOLDES', 'ZERO', 'FILTRE', 'RECHERCHER',
    'CREER', 'TOUT', 'DEVELOPPER', 'REDUIRE', 'EXCEL', 'PDF', 'DATE',
    'ASSOCIATIF', 'DEBITEUR', 'CREDITEUR', 'RESULTAT', 'PAR', 'SECTIONS'
]);

async function loginAndOpenBalance(page) {
    await page.goto('/index.php/auth/login');

    const sectionSelect = page.locator('select[name="section"]');
    await sectionSelect.selectOption('4'); // Général section

    await page.fill('input[name="username"]', 'testadmin');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');

    await page.goto('/index.php/comptes/balance');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);

    const balanceAccordion = page.locator('#balanceAccordion');
    await balanceAccordion.scrollIntoViewIfNeeded();
    await page.waitForTimeout(500);
}

async function getVisibleAccordionTexts(page) {
    return page.evaluate(() => {
        const items = Array.from(document.querySelectorAll('#balanceAccordion .accordion-item'));
        return items
            .filter((item) => window.getComputedStyle(item).display !== 'none')
            .map((item) => (item.textContent || '').replace(/\s+/g, ' ').trim())
            .filter(Boolean);
    });
}

async function getVisibleUserWordsFrom411(page) {
    const itemTexts = await page.evaluate(() => {
        const items = Array.from(document.querySelectorAll('#balanceAccordion .accordion-item'));
        const visibleItems = items.filter((item) => window.getComputedStyle(item).display !== 'none');

        return visibleItems
            .map((item) => item.textContent || '')
            .map((text) => text.replace(/\s+/g, ' ').trim())
            .filter((text) => text.includes('(411)'));
    });

    const words = [];
    for (const text of itemTexts) {
        const normalized = text
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toUpperCase();

        const chunks = normalized.split('(411)').slice(1);
        for (const chunk of chunks) {
            const tokens = (chunk.match(/[A-Z]{3,}/g) || []).filter((w) => !STOP_WORDS.has(w));
            if (tokens.length > 0) {
                words.push(tokens[0]);
            }
        }
    }

    return words;
}

function extractWords(text) {
    return text
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toUpperCase()
        .match(/[A-Z]{3,}/g) || [];
}

function buildDynamicSearchTerms(words, maxTerms = 2) {
    const terms = [];
    for (const word of words) {
        if (!word || word.length < 3) continue;
        const term = word.slice(0, 3);
        if (term.length < 3) continue;
        if (!terms.some((t) => t.term === term)) {
            terms.push({ term, expectedWord: word });
        }
        if (terms.length >= maxTerms) break;
    }

    return terms;
}

/**
 * Test to verify that balance search works correctly
 * Uses terms extracted from the currently displayed balance data
 * so it remains valid with the active database state.
 */
test.describe('Balance Search Bug Fix', () => {
    test('should filter balance using users from current database', async ({ page }) => {
        await loginAndOpenBalance(page);

        const searchInput = page.locator('#accordion-search');
        await expect(searchInput).toBeVisible({ timeout: 10000 });

        const expandAllButton = page.getByRole('button', { name: /Tout développer/i });
        if (await expandAllButton.count() > 0) {
            await expandAllButton.first().click();
            await page.waitForTimeout(1200);
        }

        const initialTexts = await getVisibleAccordionTexts(page);
        expect(initialTexts.length).toBeGreaterThan(0);

        const userWords = await getVisibleUserWordsFrom411(page);
        const dynamicTerms = buildDynamicSearchTerms(userWords, 2);
        expect(dynamicTerms.length).toBeGreaterThan(0);

        let validatedTerms = 0;

        for (const { term, expectedWord } of dynamicTerms) {
            await searchInput.fill(term);
            await searchInput.dispatchEvent('input');
            await searchInput.dispatchEvent('keyup');
            await page.waitForTimeout(1200);

            const filteredTexts = await getVisibleAccordionTexts(page);

            console.log(`Term "${term}" (from "${expectedWord}") -> ${filteredTexts.length} visible items`);

            if (filteredTexts.length === 0) {
                continue;
            }
            const containsExpectedWord = filteredTexts.some((text) =>
                text.toUpperCase().includes(expectedWord)
            );
            expect(containsExpectedWord).toBeTruthy();
            validatedTerms++;
        }

        expect(validatedTerms).toBeGreaterThan(0);
    });

    test('should clear search results when input is empty', async ({ page }) => {
        await loginAndOpenBalance(page);

        const searchInput = page.locator('#accordion-search');
        await expect(searchInput).toBeVisible();

        // First count all items before search
        const accordionItems = page.locator('#balanceAccordion .accordion-item');
        const allItems = await accordionItems.evaluateAll(items => items.length);

        console.log(`Total accordion items: ${allItems}`);

        // Search for something specific
        await searchInput.fill('PEI');
        await page.waitForTimeout(1000);

        const filteredItems = await accordionItems.evaluateAll(items => 
            items.filter(item => item.style.display !== 'none').length
        );

        console.log(`Items after PEI search: ${filteredItems}`);

        // Clear the search
        await searchInput.fill('');
        await page.waitForTimeout(1000);

        const itemsAfterClear = await accordionItems.evaluateAll(items => 
            items.filter(item => item.style.display !== 'none').length
        );

        console.log(`Items after clearing search: ${itemsAfterClear}`);

        // Should show all items again
        expect(itemsAfterClear).toBe(allItems);

        console.log('✓ Search clear test completed');
    });
});