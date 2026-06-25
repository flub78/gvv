/**
 * Playwright tests — Comportement du widget horamètre sur vols_avion/create
 *
 * Comportement 1 : Au chargement sans machine sélectionnée, les widgets
 *   horamètre début et fin sont vides (pas de valeur affichée).
 *
 * Comportement 2 : Après sélection d'une machine, les horamètres début et fin
 *   sont initialisés avec la dernière valeur d'horamètre de cette machine.
 *
 * Comportement 3 : Lors d'un changement de machine (ou désélection), les
 *   horamètres sont mis à jour / vidés en conséquence.
 *
 * Usage :
 *   cd playwright
 *   npx playwright test tests/vols-avion-horametre-widget.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');

const LOGIN_URL  = '/index.php/auth/login';
const CREATE_URL = '/index.php/vols_avion/create';

const ADMIN = { username: 'testadmin', password: 'password' };

// Machine active en section Avion (club=3), mode=1 (minutes)
const MACHINE_AVION = 'F-GSRP';

async function login(page, user) {
    await page.goto(LOGIN_URL);
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="username"]', user.username);
    await page.fill('input[name="password"]', user.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

async function switchSection(page, sectionId) {
    await page.evaluate(async (sid) => {
        const fd = new FormData();
        fd.append('section', String(sid));
        await fetch('/index.php/user_roles_per_section/set_section', {
            method: 'POST', body: fd, credentials: 'include'
        });
    }, sectionId);
    await page.waitForTimeout(300);
}

// Déclenche le changement de sélection Select2
async function selectMachine(page, machineId) {
    await page.evaluate((mid) => {
        const sel = document.querySelector('select[name="vamacid"]');
        if (!sel) return;
        sel.value = mid;
        if (typeof $ !== 'undefined') {
            $(sel).trigger('change');
        } else {
            sel.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }, machineId);
}

// ─────────────────────────────────────────────────────────────────────────────

test.describe('Widget horamètre — vols_avion/create', () => {

    test.beforeEach(async ({ page }) => {
        await login(page, ADMIN);
        // Section Avion (club=3) pour avoir accès à F-GSRP
        await switchSection(page, 3);
    });

    // ── Comportement 1 ───────────────────────────────────────────────────────

    test('Comp1 : sans machine sélectionnée, les widgets horamètre sont vides', async ({ page }) => {
        await page.goto(CREATE_URL);
        await page.waitForLoadState('networkidle');

        // Attendre que l'initialisation JS soit terminée (setTimeout 0)
        await page.waitForTimeout(500);

        // Le widget ne doit pas être construit sans machine : #debut_int absent
        const debutIntCount = await page.locator('#debut_int').count();
        expect(debutIntCount,
            'Le widget horamètre début ne doit pas exister sans machine sélectionnée'
        ).toBe(0);

        const finIntCount = await page.locator('#fin_int').count();
        expect(finIntCount,
            'Le widget horamètre fin ne doit pas exister sans machine sélectionnée'
        ).toBe(0);

        // Les conteneurs de widget doivent être vides
        const debutWidgetHtml = await page.locator('#debut_widget').innerHTML();
        expect(debutWidgetHtml.trim(),
            'Le conteneur #debut_widget doit être vide'
        ).toBe('');

        // Les valeurs cachées doivent être vides
        const debutVal = await page.locator('#debut').inputValue();
        expect(debutVal,
            'La valeur cachée #debut doit être vide sans machine'
        ).toBe('');
    });

    // ── Comportement 2 ───────────────────────────────────────────────────────

    test('Comp2 : sélection d\'une machine initialise les horamètres avec le dernier vol', async ({ page }) => {
        await page.goto(CREATE_URL);
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(300);

        // Vérifier que F-GSRP est disponible dans le sélecteur
        const optionCount = await page.locator(
            `select[name="vamacid"] option[value="${MACHINE_AVION}"]`
        ).count();
        expect(optionCount, `Machine ${MACHINE_AVION} doit être dans le sélecteur`).toBeGreaterThan(0);

        // Sélectionner la machine
        await selectMachine(page, MACHINE_AVION);

        // Attendre que le widget se construise
        await page.waitForSelector('#debut_int', { timeout: 3000 });
        await page.waitForTimeout(200);

        // Le widget doit exister
        await expect(page.locator('#debut_int')).toBeVisible();
        await expect(page.locator('#debut_dec')).toBeVisible();

        // La valeur cachée #debut doit être non vide (initialisée avec le dernier horamètre)
        const debutVal = await page.locator('#debut').inputValue();
        expect(debutVal,
            'La valeur cachée #debut doit être initialisée après sélection machine'
        ).not.toBe('');

        // F-GSRP est en mode minutes : le sélecteur doit aller jusqu'à 59
        const maxOption = await page.locator('#debut_dec option').last().getAttribute('value');
        expect(parseInt(maxOption),
            'F-GSRP en mode minutes doit avoir un sélecteur jusqu\'à 59'
        ).toBe(59);

        // Même chose pour fin
        const finVal = await page.locator('#fin').inputValue();
        expect(finVal,
            'La valeur cachée #fin doit être initialisée après sélection machine'
        ).not.toBe('');

        // début et fin sont initialisés à la même valeur (dernier horamètre)
        expect(debutVal,
            'début et fin doivent être égaux (dernier horamètre de la machine)'
        ).toBe(finVal);
    });

    // ── Comportement 3 ───────────────────────────────────────────────────────

    test('Comp3 : désélection de la machine vide les widgets horamètre', async ({ page }) => {
        await page.goto(CREATE_URL);
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(300);

        // D'abord sélectionner la machine
        await selectMachine(page, MACHINE_AVION);
        await page.waitForSelector('#debut_int', { timeout: 3000 });
        await page.waitForTimeout(200);

        // Vérifier que le widget est construit avec une valeur
        const debutValAfterSelect = await page.locator('#debut').inputValue();
        expect(debutValAfterSelect).not.toBe('');

        // Désélectionner la machine (retour à la valeur vide)
        await selectMachine(page, '');
        await page.waitForTimeout(400);

        // Le widget doit disparaître et les valeurs doivent être vidées
        const debutIntCount = await page.locator('#debut_int').count();
        expect(debutIntCount,
            'Le widget horamètre doit disparaître après désélection machine'
        ).toBe(0);

        const debutValAfterDeselect = await page.locator('#debut').inputValue();
        expect(debutValAfterDeselect,
            'La valeur cachée #debut doit être vide après désélection machine'
        ).toBe('');
    });

    test('Comp3 : changement de machine met à jour les horamètres', async ({ page }) => {
        await page.goto(CREATE_URL);
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(300);

        // Sélectionner F-GSRP (mode minutes)
        await selectMachine(page, MACHINE_AVION);
        await page.waitForSelector('#debut_int', { timeout: 3000 });
        await page.waitForTimeout(200);

        // Modifier manuellement les horamètres pour prouver qu'ils vont être réinitialisés
        await page.locator('#debut_int').fill('999');
        await page.locator('#debut_int').dispatchEvent('input');
        const horaModifie = await page.locator('#debut_int').inputValue();
        expect(horaModifie).toBe('999');

        // Désélectionner puis re-sélectionner la machine : simule un changement
        await selectMachine(page, '');
        await page.waitForTimeout(300);
        await selectMachine(page, MACHINE_AVION);
        await page.waitForSelector('#debut_int', { timeout: 3000 });
        await page.waitForTimeout(300);

        // La valeur doit avoir été réinitialisée au dernier horamètre (pas 999)
        const horaApresChangement = await page.locator('#debut_int').inputValue();
        expect(parseInt(horaApresChangement),
            'Le changement de machine doit réinitialiser les horamètres (pas 999)'
        ).not.toBe(999);
    });

});
