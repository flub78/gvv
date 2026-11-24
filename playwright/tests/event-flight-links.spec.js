/**
 * @feature Événements et qualifications
 *
 * Liste des événements d'un membre : le vol planeur associé à un événement
 * n'est un lien que pour qui peut l'ouvrir (planchiste, ou pilote du vol).
 *
 * Les vols et événements sont créés par le test et supprimés à la fin.
 *
 * Usage :
 *   cd playwright
 *   npx playwright test tests/event-flight-links.spec.js --reporter=line
 */

const { test, expect } = require('@playwright/test');
const mysql = require('mysql2/promise');
const { DB_CONFIG } = require('./helpers/gvv-config');

const MEMBER = { username: 'asterix', password: 'password' };
const OTHER_PILOT = 'obelix';
const DATE = '2099-06-01';

async function login(page, user) {
    await page.goto('/index.php/auth/logout');
    await page.goto('/index.php/auth/login');
    await page.fill('input[name="username"]', user.username);
    await page.fill('input[name="password"]', user.password);
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForLoadState('networkidle');
}

test.describe('Événements : liens vers les vols', () => {
    let db;
    const flightIds = [];
    const eventIds = [];

    test.beforeAll(async () => {
        db = await mysql.createConnection(DB_CONFIG);
    });

    test.afterAll(async () => {
        if (eventIds.length) {
            await db.query('DELETE FROM events WHERE id IN (?)', [eventIds]);
        }
        if (flightIds.length) {
            await db.query('DELETE FROM volsp WHERE vpid IN (?)', [flightIds]);
        }
        await db.end();
    });

    /**
     * Scénario: Un pilote ouvre son propre vol depuis ses événements, pas celui d'un autre
     *   Étant donné un pilote sans rôle de planchiste
     *   Et un de ses événements lié à un vol qu'il a lui-même effectué
     *   Et un autre de ses événements lié au vol d'un autre pilote
     *   Quand il consulte la liste de ses événements
     *   Alors son propre vol est un lien qu'il peut ouvrir
     *   Mais le vol de l'autre pilote est affiché sans lien
     */
    test('pilot gets a link to his own flight only', async ({ page }) => {
        const [members] = await db.query('SELECT mlogin FROM membres WHERE mlogin IN (?)', [[MEMBER.username, OTHER_PILOT]]);
        test.skip(members.length < 2, 'Membres de test absents (bin/create_test_users.sh)');
        const [[eventType]] = await db.query('SELECT id FROM events_types ORDER BY id LIMIT 1');

        for (const pilot of [MEMBER.username, OTHER_PILOT]) {
            const [res] = await db.query(
                "INSERT INTO volsp (vpdate, vppilid, vpmacid, vpcdeb, vpcfin, vpduree, vpdc, vpcategorie, vpticcolle) " +
                "VALUES (?, ?, 'TEST', 10, 11, 60, 0, 0, 0)", [DATE, pilot]);
            flightIds.push(res.insertId);
            const [ev] = await db.query(
                'INSERT INTO events (emlogin, etype, edate, evpid, ecomment) VALUES (?, ?, ?, ?, ?)',
                [MEMBER.username, eventType.id, DATE, res.insertId, 'playwright liens vols']);
            eventIds.push(ev.insertId);
        }
        const [ownFlight, otherFlight] = flightIds;

        await login(page, MEMBER);
        await page.goto(`/index.php/event/page/${MEMBER.username}`);
        await expect(page.locator('body')).not.toContainText('A PHP Error was encountered');
        await expect(page.locator(`a[href*="vols_planeur/edit/${ownFlight}"]`)).toHaveCount(1);
        await expect(page.locator(`a[href*="vols_planeur/edit/${otherFlight}"]`)).toHaveCount(0);

        await page.locator(`a[href*="vols_planeur/edit/${ownFlight}"]`).click();
        await page.waitForLoadState('networkidle');
        expect(page.url()).toContain(`vols_planeur/edit/${ownFlight}`);
        await expect(page.locator('body')).not.toContainText('Accès non autorisé');
    });
});
