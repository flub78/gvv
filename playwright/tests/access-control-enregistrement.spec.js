/**
 * @feature Authentification et autorisations
 *
 * L'enregistrement d'un formulaire (création ou modification) est soumis aux
 * mêmes droits que l'ouverture du formulaire : un membre ne peut pas
 * contourner l'interface en envoyant directement un formulaire qu'on ne lui
 * propose pas, et chaque rôle conserve les saisies qui lui sont ouvertes.
 *
 * Technique : pour vérifier un droit sans rien écrire en base, le test envoie
 * un formulaire incomplet. Un utilisateur autorisé passe le contrôle d'accès
 * puis la validation échoue et le formulaire est réaffiché ; un utilisateur non
 * autorisé est renvoyé vers la page d'accès refusé avant toute validation.
 *
 * Utilisateurs de test (bin/create_test_users.sh) :
 *   - asterix       : simple membre (Planeur, Général)
 *   - abraracourcix : CA (Avion)
 *   - goudurix      : auto_planchiste (Avion), trésorier (Avion, Général)
 *   - agecanonix    : pilote de vols de découverte (Planeur)
 *   - testplanchiste, testtresorier, testbureau, testca : un rôle en Planeur
 *
 * Usage:
 *   npx playwright test tests/access-control-enregistrement.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');
const LoginPage = require('./helpers/LoginPage');
const { DB_CONFIG } = require('./helpers/gvv-config');
const { csrfPost, deleteViaApp } = require('./helpers/csrf');

const CREATION = 1;
const MODIFICATION = 2;

const USERS = {
    asterix: { username: 'asterix', section: '1' },
    abraracourcix: { username: 'abraracourcix', section: '3' },
    goudurix: { username: 'goudurix', section: '3' },
    agecanonix: { username: 'agecanonix', section: '1' },
    testplanchiste: { username: 'testplanchiste', section: '1' },
    testtresorier: { username: 'testtresorier', section: '1' },
    testbureau: { username: 'testbureau', section: '1' },
    testca: { username: 'testca', section: '1' },
};

async function login(page, key) {
    const user = USERS[key];
    const loginPage = new LoginPage(page);
    await loginPage.open();
    await loginPage.login(user.username, 'password', user.section);
    return loginPage;
}

/** Envoie un formulaire et indique si l'accès a été refusé. */
async function submit(page, route, form = {}) {
    const response = await csrfPost(page, '/index.php/' + route, { form, maxRedirects: 0 });
    const location = response.headers()['location'] || '';
    return {
        status: response.status(),
        denied: location.includes('/auth/deny') || location.includes('/auth/login'),
    };
}

async function open(page, route) {
    const response = await page.request.get('/index.php/' + route, { maxRedirects: 0 });
    const location = response.headers()['location'] || '';
    return {
        status: response.status(),
        denied: location.includes('/auth/deny') || location.includes('/auth/login'),
    };
}

function expectDenied(result, what) {
    expect(result.denied, `${what} : accès attendu refusé (statut ${result.status})`).toBeTruthy();
}

function expectAllowed(result, what) {
    expect(result.denied, `${what} : accès attendu autorisé`).toBeFalsy();
    expect(result.status, `${what} : la page doit exister`).not.toBe(404);
    expect(result.status, `${what} : pas d'erreur serveur`).toBeLessThan(500);
}

test.describe('Enregistrement des formulaires : refus pour un simple membre', () => {

    /**
     * @feature Authentification et autorisations
     * Scénario: Un simple membre ne peut pas enregistrer de données d'administration
     *   Étant donné un membre connecté sans responsabilité au club
     *   Quand il envoie directement un formulaire de création de catégorie comptable, de type de certificat, de type de ticket, d'historique, de rapport ou de certificat
     *   Alors l'accès lui est refusé
     */
    test('simple member is denied on admin forms', async ({ page }) => {
        await login(page, 'asterix');
        for (const ctrl of ['categorie', 'events_types', 'types_ticket', 'historique', 'reports', 'event', 'associations_of', 'pompes']) {
            expectDenied(await submit(page, `${ctrl}/formValidation/${CREATION}`), `${ctrl} création`);
            expectDenied(await submit(page, `${ctrl}/formValidation/${MODIFICATION}`), `${ctrl} modification`);
        }
        expectDenied(await open(page, 'categorie/create'), 'categorie formulaire de création');
    });

    /**
     * @feature Authentification et autorisations
     * Scénario: Un formulaire complet envoyé par un simple membre n'est pas enregistré
     *   Étant donné un membre connecté sans responsabilité au club
     *   Quand il envoie un formulaire complet de création d'un type de certificat
     *   Alors l'accès lui est refusé
     *   Et aucun type de certificat n'est créé
     */
    test('a complete form sent by a simple member creates nothing', async ({ page }) => {
        const marker = `PW_SEC_${Date.now()}`;
        const db = await mysql.createConnection(DB_CONFIG);
        try {
            await login(page, 'asterix');
            const result = await submit(page, `events_types/formValidation/${CREATION}`,
                { name: marker, activite: '0', en_vol: '0', button: 'Valider' });
            expectDenied(result, 'events_types création complète');
            const [rows] = await db.execute('SELECT COUNT(*) AS n FROM events_types WHERE name = ?', [marker]);
            expect(rows[0].n).toBe(0);
        } finally {
            await db.execute('DELETE FROM events_types WHERE name = ?', [marker]);
            await db.end();
        }
    });

    /**
     * @feature Authentification et autorisations
     * Scénario: Un simple membre ne peut pas supprimer un certificat
     *   Étant donné un membre connecté sans responsabilité au club
     *   Quand il demande la suppression d'un certificat
     *   Alors l'accès lui est refusé
     */
    test('simple member cannot delete a certificate', async ({ page }) => {
        await login(page, 'asterix');
        const response = await deleteViaApp(page, 'event', 999999999, { maxRedirects: 0 });
        expect(response.status()).toBe(404);
    });

    /**
     * @feature Authentification et autorisations
     * Scénario: Un simple membre ne peut pas modifier le vol planeur d'un autre pilote
     *   Étant donné un membre connecté qui n'est pas planchiste
     *   Quand il envoie un formulaire de modification d'un vol planeur qui n'est pas le sien
     *   Alors l'accès lui est refusé
     */
    test('simple member cannot modify another pilot glider flight', async ({ page }) => {
        await login(page, 'asterix');
        expectDenied(await submit(page, `vols_planeur/formValidation/${MODIFICATION}`, { original_vpid: '999999999' }),
            'vols_planeur modification d\'un vol qui n\'est pas le sien');
    });
});

test.describe('Enregistrement des formulaires : les rôles gardent leurs saisies', () => {

    /**
     * @feature Authentification et autorisations
     * Scénario: Le planchiste saisit les vols planeur
     *   Étant donné un planchiste connecté
     *   Quand il ouvre et envoie le formulaire de saisie d'un vol planeur
     *   Alors le formulaire lui est accessible
     */
    test('planchiste keeps glider flight entry', async ({ page }) => {
        await login(page, 'testplanchiste');
        expectAllowed(await open(page, 'vols_planeur/create'), 'vols_planeur formulaire de création');
        expectAllowed(await submit(page, `vols_planeur/formValidation/${CREATION}`), 'vols_planeur enregistrement');
    });

    /**
     * @feature Authentification et autorisations
     * Scénario: Le trésorier et le bureau saisissent des vols planeur
     *   Étant donné un trésorier ou un membre du bureau connecté
     *   Quand il ouvre et envoie le formulaire de saisie d'un vol planeur
     *   Alors le formulaire lui est accessible
     */
    test('treasurer and board keep glider flight entry', async ({ page }) => {
        for (const key of ['testtresorier', 'testbureau']) {
            await page.context().clearCookies();
            await login(page, key);
            expectAllowed(await open(page, 'vols_planeur/create'), `${key} vols_planeur création`);
            expectAllowed(await submit(page, `vols_planeur/formValidation/${CREATION}`), `${key} vols_planeur enregistrement`);
        }
    });

    /**
     * @feature Authentification et autorisations
     * Scénario: L'auto-planchiste saisit ses vols avion
     *   Étant donné un pilote autorisé à saisir ses propres vols avion
     *   Quand il ouvre et envoie le formulaire de saisie d'un vol avion
     *   Alors le formulaire lui est accessible
     */
    test('auto_planchiste keeps airplane flight entry', async ({ page }) => {
        await login(page, 'goudurix');
        expectAllowed(await open(page, 'vols_avion/create'), 'vols_avion formulaire de création');
        expectAllowed(await submit(page, `vols_avion/formValidation/${CREATION}`), 'vols_avion enregistrement');
    });

    /**
     * @feature Authentification et autorisations
     * Scénario: Le pilote de vols de découverte enregistre les bons
     *   Étant donné un pilote de vols de découverte connecté
     *   Quand il ouvre et envoie le formulaire d'un bon de vol de découverte
     *   Alors le formulaire lui est accessible
     */
    test('discovery flight pilot keeps voucher entry', async ({ page }) => {
        await login(page, 'agecanonix');
        expectAllowed(await open(page, 'vols_decouverte/create'), 'vols_decouverte formulaire de création');
        expectAllowed(await submit(page, `vols_decouverte/formValidation/${CREATION}`), 'vols_decouverte création');
        expectAllowed(await submit(page, `vols_decouverte/formValidation/${MODIFICATION}`), 'vols_decouverte enregistrement du vol');
    });

    /**
     * @feature Authentification et autorisations
     * Scénario: Le trésorier et le bureau créent des fiches membres et des terrains
     *   Étant donné un trésorier ou un membre du bureau connecté
     *   Quand il ouvre et envoie le formulaire de création d'un membre ou d'un terrain
     *   Alors le formulaire lui est accessible
     */
    test('treasurer and board keep member and airfield creation', async ({ page }) => {
        for (const key of ['testtresorier', 'testbureau']) {
            await page.context().clearCookies();
            await login(page, key);
            for (const ctrl of ['membre', 'terrains']) {
                expectAllowed(await open(page, `${ctrl}/create`), `${key} ${ctrl} formulaire de création`);
                expectAllowed(await submit(page, `${ctrl}/formValidation/${CREATION}`), `${key} ${ctrl} enregistrement`);
            }
        }
    });

    /**
     * @feature Authentification et autorisations
     * Scénario: Les responsables créent des comptes, seul le trésorier les modifie
     *   Étant donné un membre du CA connecté
     *   Quand il envoie le formulaire de création d'un compte
     *   Alors le formulaire lui est accessible
     *   Mais l'enregistrement d'une modification de compte lui est refusé
     */
    test('board member creates accounts but only the treasurer modifies them', async ({ page }) => {
        await login(page, 'testca');
        expectAllowed(await open(page, 'comptes/create'), 'comptes formulaire de création');
        expectAllowed(await submit(page, `comptes/formValidation/${CREATION}`), 'comptes création');
        expectDenied(await submit(page, `comptes/formValidation/${MODIFICATION}`), 'comptes modification');

        await page.context().clearCookies();
        await login(page, 'testtresorier');
        expectAllowed(await submit(page, `comptes/formValidation/${MODIFICATION}`), 'comptes modification par le trésorier');
    });

    /**
     * @feature Authentification et autorisations
     * Scénario: Le trésorier crée des types de tickets
     *   Étant donné un trésorier connecté qui n'est pas membre du CA
     *   Quand il ouvre et envoie le formulaire de création d'un type de ticket
     *   Alors le formulaire lui est accessible
     */
    test('treasurer keeps ticket type creation', async ({ page }) => {
        await login(page, 'testtresorier');
        expectAllowed(await open(page, 'types_ticket/create'), 'types_ticket formulaire de création');
        expectAllowed(await submit(page, `types_ticket/formValidation/${CREATION}`), 'types_ticket création');
    });

    /**
     * @feature Authentification et autorisations
     * Scénario: Le CA enregistre les certificats des membres
     *   Étant donné un membre du CA connecté
     *   Quand il ouvre le formulaire de certificat d'un membre et l'envoie
     *   Alors le formulaire lui est accessible
     */
    test('board member keeps certificate entry', async ({ page }) => {
        await login(page, 'abraracourcix');
        expectAllowed(await open(page, 'event/create/asterix'), 'event formulaire de création');
        expectAllowed(await submit(page, `event/formValidation/${CREATION}`, { event_type_flight: 'aucun' }), 'event enregistrement');
    });
});

test.describe('Saisie réelle de ses propres vols avion', () => {

    /**
     * @feature Authentification et autorisations
     * Scénario: L'auto-planchiste enregistre puis corrige son propre vol avion
     *   Étant donné un pilote autorisé à saisir ses propres vols avion
     *   Quand il saisit un vol sur un avion de sa section
     *   Alors le vol est enregistré à son nom
     *   Et il peut ensuite corriger ce vol
     */
    test('auto_planchiste records then corrects his own airplane flight', async ({ page }) => {
        const marker = `PW_SEC_AUTO_${Date.now()}`;
        const machine = 'F-GSRP';
        const db = await mysql.createConnection(DB_CONFIG);
        let vaid = null;
        try {
            const loginPage = await login(page, 'goudurix');
            await loginPage.goto('vols_avion/create');
            await page.locator('input[name="vadate"]').waitFor();

            const today = new Date().toISOString().slice(0, 10);
            await page.fill('input[name="vadate"]', today.split('-').reverse().join('/'));
            await page.keyboard.press('Escape');
            await page.locator('h3, h2').first().click();
            await page.selectOption('select[name="vamacid"]', machine);
            await page.getByRole('radio', { name: 'Standard pilote breveté' }).check();
            await page.fill('input[name="vahdeb"]', '06:00');
            await page.fill('input[name="vahfin"]', '07:00');
            const atterrissages = page.locator('input[name="vaatt"]');
            if (await atterrissages.count()) {
                await atterrissages.fill('1');
            }
            // Le compteur de fin part du dernier relevé de la machine : +1 heure
            await page.locator('#fin_widget button', { hasText: '+' }).first().click();
            await page.fill('textarea[name="vaobs"], input[name="vaobs"]', marker);

            await page.locator('button[type="submit"], input[type="submit"]').first().click();
            await page.waitForLoadState('domcontentloaded');
            expect(page.url()).not.toContain('/auth/deny');

            let row = [];
            await expect.poll(async () => {
                [row] = await db.execute('SELECT vaid, vapilid FROM volsa WHERE vaobs = ?', [marker]);
                return row.length;
            }, { message: 'vol non enregistré', timeout: 5000 }).toBe(1);
            vaid = row[0].vaid;
            expect(row[0].vapilid).toBe('goudurix');

            await loginPage.goto(`vols_avion/edit/${vaid}`);
            await page.locator('input[name="vadate"]').waitFor();
            await page.fill('textarea[name="vaobs"], input[name="vaobs"]', `${marker}_corrige`);
            await page.locator('button[type="submit"], input[type="submit"]').first().click();
            await page.waitForLoadState('domcontentloaded');
            expect(page.url()).not.toContain('/auth/deny');

            await expect.poll(async () => {
                const [r] = await db.execute('SELECT vaobs FROM volsa WHERE vaid = ?', [vaid]);
                return r.length ? r[0].vaobs : null;
            }, { message: 'correction non enregistrée', timeout: 5000 }).toBe(`${marker}_corrige`);
        } finally {
            if (vaid) {
                // Suppression par l'application pour annuler la facturation du vol
                await deleteViaApp(page, 'vols_avion', vaid);
                await page.waitForLoadState('domcontentloaded');
            }
            const [left] = await db.execute('SELECT vaid FROM volsa WHERE vaobs LIKE ?', [`${marker}%`]);
            await db.end();
            expect(left.length, 'le vol de test doit être supprimé').toBe(0);
        }
    });
});
