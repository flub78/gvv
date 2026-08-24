/**
 * Playwright smoke test — formulaire public "Test en vol SPL" (7 pages,
 * uploads/formulaires/test_en_vol_spl/), et par la même occasion le
 * mécanisme d'accumulation multi-page de Forms_public::submit() (nav_action
 * prev/next/finalize) qui a été ajouté pour que ce formulaire à 7 pages
 * fonctionne réellement — avant ce correctif, "Page suivante"/"précédente"
 * étaient de simples liens <a> et seule la dernière page soumise atteignait
 * form_submissions.
 *
 * Parcourt les 7 pages du vrai formulaire publié (id=4811, slug
 * test-en-vol-spl), pré-rempli via pilot_login/instructor_login avec des
 * membres de test qui possèdent déjà les événements SPL/FE Sailplane
 * nécessaires (gpruvost, fduvollet — voir doc/design_notes), remplit le
 * strict minimum requis sur chaque page, signe électroniquement (onglet
 * "Taper") et vérifie que la soumission finale contient bien des champs
 * des 7 pages (pas seulement de la dernière).
 *
 * Usage:
 *   cd playwright
 *   npx playwright test tests/forms-test-en-vol-spl-smoke.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');

const SLUG = 'test-en-vol-spl';
const PILOT_LOGIN = 'gpruvost';
const INSTRUCTOR_LOGIN = 'fduvollet';

const DB_CONFIG = {
    host: 'localhost',
    user: 'gvv_user',
    password: 'lfoyfgbj',
    database: 'gvv2',
};

async function checkRadio(page, name, value) {
    await page.locator(`input[name="${name}"][value="${value}"]`).check();
}

async function fillAllRequiredSelects(page, value) {
    const selects = page.locator('table.gvv-check-table select');
    const count = await selects.count();
    for (let i = 0; i < count; i++) {
        await selects.nth(i).selectOption(value);
    }
}

async function fillAllInitials(page, value) {
    const inputs = page.locator('table.gvv-check-table input[type="text"]');
    const count = await inputs.count();
    for (let i = 0; i < count; i++) {
        await inputs.nth(i).fill(value);
    }
}

test('walk the 7-page test-en-vol-spl form end to end and submit it', async ({ page }) => {
    const connection = await mysql.createConnection(DB_CONFIG);
    let submissionId = null;

    try {
        const [formRows] = await connection.execute(
            'SELECT id FROM forms WHERE public_slug = ? AND status = "published"', [SLUG]
        );
        expect(formRows.length, 'Le formulaire test-en-vol-spl doit être publié.').toBe(1);
        const formId = formRows[0].id;

        // --- Page 1 : informations sur le candidat (pré-remplies par member.*) ---
        await page.goto(`/index.php/forms/${SLUG}?pilot_login=${PILOT_LOGIN}&instructor_login=${INSTRUCTOR_LOGIN}`);
        await page.waitForLoadState('networkidle');
        expect(await page.textContent('body')).not.toContain('Fatal error');

        await expect(page.locator('input[name="nom"]')).toHaveValue('Pruvost');
        await expect(page.locator('input[name="prenom"]')).toHaveValue('Guillaume');
        await expect(page.locator('input[name="nom"]')).toHaveAttribute('readonly', '');

        await checkRadio(page, 'civilite', 'M');
        await page.click('button[name="nav_action"][value="next"]');
        await page.waitForLoadState('networkidle');
        expect(page.url()).toContain('page=2');

        // --- Page 2 : détail du vol ---
        await page.fill('input[name="type_planeur"]', 'ASK21');
        await checkRadio(page, 'methode_lancement', 'Aerotracte');
        await page.fill('input[name="immatriculation"]', 'F-CJRG');
        await page.fill('input[name="trajet"]', 'Local Abbeville');
        await page.fill('input[name="temps_vol"]', '0:55');
        await page.fill('input[name="nb_atterrissages"]', '1');
        await checkRadio(page, 'tentative', '1ere');
        await page.click('button[name="nav_action"][value="next"]');
        await page.waitForLoadState('networkidle');
        expect(page.url()).toContain('page=3');

        // --- Page 3 : Section 1 ---
        await checkRadio(page, 'resultat_section1', 'Reussite');
        await fillAllRequiredSelects(page, 'Reussite');
        await fillAllInitials(page, 'PF');
        await page.click('button[name="nav_action"][value="next"]');
        await page.waitForLoadState('networkidle');
        expect(page.url()).toContain('page=4');

        // --- Page 4 : Section 2 (3 sous-tableaux) ---
        await checkRadio(page, 'resultat_section2', 'Reussite');
        await fillAllRequiredSelects(page, 'Reussite');
        await page.click('button[name="nav_action"][value="next"]');
        await page.waitForLoadState('networkidle');
        expect(page.url()).toContain('page=5');

        // --- Page 5 : Section 3 (maniabilité) ---
        await checkRadio(page, 'resultat_section3', 'Reussite');
        await fillAllRequiredSelects(page, 'Reussite');
        await fillAllInitials(page, 'PF');
        await page.click('button[name="nav_action"][value="next"]');
        await page.waitForLoadState('networkidle');
        expect(page.url()).toContain('page=6');

        // --- Page 6 : Section 4 (circuit, approche, atterrissage) ---
        await checkRadio(page, 'resultat_section4', 'Reussite');
        await fillAllRequiredSelects(page, 'Reussite');
        await fillAllInitials(page, 'PF');
        await page.click('button[name="nav_action"][value="next"]');
        await page.waitForLoadState('networkidle');
        expect(page.url()).toContain('page=7');

        // --- Page 7 : résultat du test + signature ---
        await expect(page.locator('input[name="commune_epreuve"]')).toHaveValue(/.+/); // club.ville
        await checkRadio(page, 'resultat_test', 'Reussite');
        await checkRadio(page, 'attestation_provisoire', 'Non');
        await checkRadio(page, 'envoi_email_candidat', 'Non');
        await checkRadio(page, 'langue_examen', 'Francais');
        await page.check('#confirm_experience');
        await page.check('#confirm_interruption');
        await page.check('#confirm_formation_25');

        await page.click('[data-sig-tab="text"]');
        await page.fill('.gvv-sig-text-input', 'F. Duvollet');

        await page.click('button[name="nav_action"][value="finalize"]');
        await page.waitForLoadState('networkidle');

        const thanksBody = await page.textContent('body');
        expect(thanksBody).not.toContain('Fatal error');
        expect(thanksBody).not.toMatch(/obligatoire/);
        expect(thanksBody).toContain('Merci');

        // --- Vérification en base : les 7 pages doivent avoir été fusionnées ---
        const [submissionRows] = await connection.execute(
            'SELECT id FROM form_submissions WHERE form_id = ? ORDER BY id DESC LIMIT 1', [formId]
        );
        expect(submissionRows.length).toBe(1);
        submissionId = submissionRows[0].id;

        const [valueRows] = await connection.execute(
            'SELECT field_name, value_text FROM form_submission_values WHERE submission_id = ?', [submissionId]
        );
        const byName = {};
        for (const row of valueRows) byName[row.field_name] = row.value_text;

        // Champ de la page 1 (le premier soumis, sept "next" avant la finalisation).
        expect(byName['nom']).toBe('Pruvost');
        // Champ de la page 2.
        expect(byName['type_planeur']).toBe('ASK21');
        // Champ de la page 6 (avant-dernière).
        expect(byName['s4_1_resultat']).toBe('Reussite');
        // Champ de la page 7 (dernière, celle qui déclenche la finalisation).
        expect(byName['resultat_test']).toBe('Reussite');

        const [fileRows] = await connection.execute(
            'SELECT storage_path FROM form_submission_files WHERE submission_id = ? AND widget_name = "signature_examinateur"',
            [submissionId]
        );
        expect(fileRows.length).toBe(1);
    } finally {
        if (submissionId) {
            const [fileRows] = await connection.execute(
                'SELECT storage_path FROM form_submission_files WHERE submission_id = ?', [submissionId]
            );
            const fs = require('fs');
            const path = require('path');
            for (const f of fileRows) {
                try { fs.rmSync(path.join(__dirname, '..', '..', f.storage_path), { force: true }); } catch (e) { /* ignore */ }
            }
            await connection.execute('DELETE FROM form_submission_values WHERE submission_id = ?', [submissionId]);
            await connection.execute('DELETE FROM form_submission_files WHERE submission_id = ?', [submissionId]);
            await connection.execute('DELETE FROM form_submissions WHERE id = ?', [submissionId]);
        }
        await connection.end();
    }
});
