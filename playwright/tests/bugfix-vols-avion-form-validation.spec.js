/**
 * Playwright tests — BugFix : Formulaire vols_avion, trois anomalies
 *
 * Bug 1 : Repopulation horamètres (mode minutes) après erreur de validation
 *   - La machine F-GSRP utilise le mode minutes (horametre_mode=1).
 *   - Après une erreur de validation, le widget JS relit la valeur hidden
 *     en la traitant comme des centièmes, ce qui donne une conversion double.
 *   - Ex : 19 minutes saisis → hidden "840.19" → JS calcule 0.19×60=11 → affiche 11min ❌
 *
 * Bug 2 : Champ essence doit accepter deux décimales
 *   - Colonne `essence` est INT(11) → règle `integer` → rejette 5.5
 *   - Doit être DECIMAL(8,2) → accepter 5.50 L
 *
 * Bug 3 : Champ vanumvi (numéro de vol de découverte) doit accepter 64 caractères
 *   - Colonne `vanumvi` est VARCHAR(20) → règle max_length[20]
 *   - Doit être VARCHAR(64) → max_length[64]
 *
 * Usage :
 *   cd playwright
 *   npx playwright test tests/bugfix-vols-avion-form-validation.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');

const LOGIN_URL  = '/index.php/auth/login';
const CREATE_URL = '/index.php/vols_avion/create';

const ADMIN = { username: 'testadmin', password: 'password' };

// F-GSRP : DR400, horametre_mode=1 (minutes)
const MACHINE_MINUTES = 'F-GSRP';

async function login(page, user) {
    await page.goto(LOGIN_URL);
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="username"]', user.username);
    await page.fill('input[name="password"]', user.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

// Change la section active (section_id : 1=Planeur, 2=ULM, 3=Avion, 4=Général)
async function switchSection(page, sectionId) {
    await page.evaluate(async (sid) => {
        const fd = new FormData();
        fd.append('section', String(sid));
        await fetch('/index.php/user_roles_per_section/set_section', {
            method: 'POST', body: fd, credentials: 'include'
        });
    }, sectionId);
    // courte pause pour que la session soit écrite
    await page.waitForTimeout(300);
}

// Sélectionne une valeur dans un <select> (compatible Select2)
async function selectValue(page, name, value) {
    await page.evaluate(([n, v]) => {
        const sel = document.querySelector(`select[name="${n}"]`);
        if (!sel) return;
        sel.value = v;
        if (typeof $ !== 'undefined') {
            $(sel).trigger('change');
        } else {
            sel.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }, [name, value]);
}

// ─────────────────────────────────────────────────────────────────────────────

test.describe('BugFix : Formulaire vols_avion — validation et repopulation', () => {

    test.beforeEach(async ({ page }) => {
        await login(page, ADMIN);
    });

    // ── Bug 2 ────────────────────────────────────────────────────────────────

    test('Bug2 : champ essence doit accepter les décimales (ex: 5.50)', async ({ page }) => {
        await page.goto(CREATE_URL);
        await page.waitForLoadState('networkidle');

        // Renseigner uniquement le champ essence avec une valeur décimale
        await page.locator('[name="essence"]').fill('5.5');

        // Soumettre le formulaire
        await page.click('#validate');
        await page.waitForLoadState('networkidle');

        // Après correction : aucune erreur "entier" pour le champ Essence
        // Avant correction : "Le champ Essence doit contenir un entier."
        const body = await page.locator('body').textContent();
        const hasEssenceIntegerError = /Essence[^.]*entier/i.test(body);
        expect(hasEssenceIntegerError,
            'Le champ essence ne doit pas rejeter les décimales').toBe(false);
    });

    // ── Bug 3 ────────────────────────────────────────────────────────────────

    test('Bug3 : champ vanumvi doit accepter jusqu\'à 64 caractères', async ({ page }) => {
        await page.goto(CREATE_URL);
        await page.waitForLoadState('networkidle');

        // Catégorie VD (1) pour rendre vanumvi requis et déclencher la validation
        await selectValue(page, 'vacategorie', '1');
        await page.waitForTimeout(200);

        // Saisir exactement 64 caractères dans vanumvi
        const vanumvi64 = 'V'.repeat(64);
        await page.locator('[name="vanumvi"]').fill(vanumvi64);

        // Soumettre
        await page.click('#validate');
        await page.waitForLoadState('networkidle');

        // Après correction : pas d'erreur "20 caractères" pour vanumvi
        // Avant correction : "ne peut contenir plus de 20 caractères"
        const body = await page.locator('body').textContent();
        const hasLengthError = /20\s*caract/i.test(body);
        expect(hasLengthError,
            'Le champ vanumvi doit accepter 64 caractères sans erreur de longueur').toBe(false);
    });

    // ── Bug 1 ────────────────────────────────────────────────────────────────

    test('Bug1 : horamètres (mode minutes) repopulés correctement après erreur de validation', async ({ page }) => {
        // F-GSRP est dans la section Avion (club=3)
        await switchSection(page, 3);

        await page.goto(CREATE_URL);
        await page.waitForLoadState('networkidle');

        // Vérifier que la machine F-GSRP (mode minutes) est disponible
        const hasMachine = await page.locator(
            `select[name="vamacid"] option[value="${MACHINE_MINUTES}"]`
        ).count();
        expect(hasMachine, `Machine ${MACHINE_MINUTES} doit être présente dans le sélecteur`).toBeGreaterThan(0);

        // Sélectionner la machine en mode minutes
        await selectValue(page, 'vamacid', MACHINE_MINUTES);

        // Attendre la reconstruction du widget (setTimeout 0 dans le JS)
        await page.waitForSelector('#debut_dec', { timeout: 3000 });
        await page.waitForTimeout(200);

        // Vérifier que le widget minutes est actif (select 0-59)
        const maxOption = await page.locator('#debut_dec option').last().getAttribute('value');
        expect(parseInt(maxOption), 'Le sélecteur doit aller jusqu\'à 59 (mode minutes)').toBe(59);

        // Saisir horamètre début : 840h 19min
        await page.locator('#debut_int').fill('840');
        await page.locator('#debut_int').dispatchEvent('input');
        await page.locator('#debut_dec').selectOption('19');

        // Saisir horamètre fin : 841h 05min
        await page.locator('#fin_int').fill('841');
        await page.locator('#fin_int').dispatchEvent('input');
        await page.locator('#fin_dec').selectOption('5');

        // Vérifier que les hidden inputs ont les bonnes valeurs (heures.minutes)
        const debutHidden = await page.locator('#debut').inputValue();
        const finHidden   = await page.locator('#fin').inputValue();
        expect(debutHidden, 'Valeur cachée début doit être "840.19"').toBe('840.19');
        expect(finHidden,   'Valeur cachée fin doit être "841.05"').toBe('841.05');

        // Déclencher une erreur de validation : essence décimale (avant bug2 fix : integer error)
        // ou laisser les champs requis vides (après bug2 fix : autre erreur)
        await page.locator('[name="essence"]').fill('5.5');

        // Soumettre → le formulaire doit être réaffiché avec une erreur
        await page.click('#validate');
        await page.waitForLoadState('networkidle');

        // Le formulaire est réaffiché (il doit y avoir au moins une erreur)
        const errorCount = await page.locator('.error, div.error').count();
        expect(errorCount, 'Le formulaire doit afficher au moins une erreur').toBeGreaterThan(0);

        // Attendre la reconstruction du widget horamètre après rechargement
        await page.waitForSelector('#debut_dec', { timeout: 3000 });
        await page.waitForTimeout(300);

        // ── ASSERTION BUG 1 ──────────────────────────────────────────────────
        // Après correction : le sélecteur minutes doit montrer 19 (valeur saisie)
        // Avant correction : il montre 11 (0.19 × 60 = 11.4 → 11 — double conversion)
        const minutesDisplayed = await page.locator('#debut_dec').inputValue();
        expect(minutesDisplayed,
            `Le widget doit afficher 19 minutes (saisi), pas ${minutesDisplayed}`
        ).toBe('19');
    });

});
